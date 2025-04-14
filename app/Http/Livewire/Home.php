<?php

namespace App\Http\Livewire;

use Livewire\Component;
use App\Models\Splitter_box;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Livewire\LivewireManager;
use Illuminate\Support\Facades\Config;
use App\Mail\NotifyUser;
use Mail;
use Carbon\Carbon;

class Home extends Component
{
    public $address;
    public $geoData;
    private $baseUrl;
    public $result;
    public $geoAddress;
    public $selectedaddress;
    public $isLocated = false;
    public $isSubmitted = false;
    public $isClose = false;
    public $isFound = false;

    public $fullname;
    public $email;
    public $phone;
    public $closestSplitterboxDistance;
    public $customerLatitude;
    public $customerLongitude;
    public $errorMessage = '';
    public $closestSplitterBoxname;
    public $closestSplitterLat;
    public $closestSplitterLon;

    public function mount()
    {
        $this->geoData = $this->splitterboxes();
    }

    public function convertAddress()
    {
        try {
            $endpoint = Config::get('constants.GOOGLE_GEO_API');
            $key = Config::get('constants.GOOGLE_API_KEY');
            $this->baseUrl = $endpoint.'address='.$this->address.'&key='.$key;

            $response = Http::get($this->baseUrl);

            if ($response->successful()) {
                $res = $response->json()['results'] ?? [];

                if(count($res) >= 1) {
                    $this->isLocated = true;
                    $this->geoAddress = $res;
                } else {
                    $this->result = "Please enter a valid address";
                    session()->flash('message', $this->result);
                }
            } else {
                $this->result = "Please enter a valid address";
                session()->flash('message', $this->result);
            }
        } catch (\Exception $e) {
            $this->result = "Something went wrong. Try again!";
            session()->flash('message', $this->result);
        }
    }

    public function location()
    {   
        try {
            if($this->selectedaddress != "") {
                $this->resetSplitterDetails();
                
                $geoData = explode("-", $this->selectedaddress);
                $lat = (float)$geoData[0];
                $lon = (float)$geoData[1];

                $this->customerLatitude = $lat;
                $this->customerLongitude = $lon;

                $this->result = $this->findLocation($lat, $lon, $this->geoData);

                if(str_contains($this->result, "FIBERONE BROADBAND IS AVAILABLE")) {
                    session()->flash('success_message', $this->result);
                } elseif(str_contains($this->result, "ADDITIONAL SURVEY REQUIRED")) {
                    session()->flash('survey_message', $this->result);
                } else {
                    session()->flash('failed_message', $this->result);
                }
            } else {
                $this->result = "Please select a specific location";
                session()->flash('message', $this->result);
            }
        } catch (\Exception $e) {
            $this->result = "Something went wrong. Try again!";
            session()->flash('message', $this->result);
        }
    }

    private function resetSplitterDetails()
    {
        $this->closestSplitterBoxname = 'N/A';
        $this->closestSplitterLat = 'N/A';
        $this->closestSplitterLon = 'N/A';
        $this->closestSplitterboxDistance = 0;
    }

    public function splitterboxes()
    {
        return splitter_box::all(['Longitude', 'Latitude', 'Splitter_B']);
    }

    public function findLocation($lat, $long, $locations = [])
    {
        $maxDistance = 0.25; // 0.25 km = 250 meters
        $maxSurveyDistance = 1; // 1 km
        $closestLocation = null;
        $closestDistance = INF;
        $this->isClose = false;
        $this->isFound = false;

        foreach ($locations as $location) {
            $distanceMeters = $this->getRouteWithGoogle(
                $lat,
                $long,
                (float)$location->Latitude,
                (float)$location->Longitude
            );

            if ($distanceMeters === null) continue;

            $distanceKm = $distanceMeters / 1000;

            if ($distanceKm <= $maxDistance) {
                if ($distanceKm < $closestDistance) {
                    $closestLocation = $location;
                    $closestDistance = $distanceKm;
                    $this->isFound = true;
                }
            } elseif ($distanceKm <= $maxSurveyDistance) {
                if ($distanceKm < $closestDistance) {
                    $closestLocation = $location;
                    $closestDistance = $distanceKm;
                    $this->isClose = true;
                }
            }
        }

        if ($closestLocation) {
            $this->setClosestSplitterDetails($closestLocation, $closestDistance * 1000);
        }

        return $this->generateResultMessage();
    }

    private function setClosestSplitterDetails($location, $distance)
    {
        $this->closestSplitterBoxname = $location->Splitter_B;
        $this->closestSplitterLat = $location->Latitude;
        $this->closestSplitterLon = $location->Longitude;
        $this->closestSplitterboxDistance = $distance;
    }

    private function generateResultMessage()
    {
        $splitterDetails = sprintf(
            "Splitter ID: %s, Coordinates: %s, %s, Distance: %s m (%s km)",
            $this->closestSplitterBoxname,
            $this->closestSplitterLat,
            $this->closestSplitterLon,
            number_format($this->closestSplitterboxDistance),
            number_format($this->closestSplitterboxDistance / 1000, 2)
        );

        if ($this->isFound) {
            $message = "FIBERONE BROADBAND IS AVAILABLE IN YOUR AREA. $splitterDetails";
            $this->saveLog($message);
            return $message;
        }

        if ($this->isClose) {
            $message = "ADDITIONAL SURVEY REQUIRED. $splitterDetails";
            $this->saveLog2($message);
            return $message;
        }

        $message = "COMING SOON";
        $this->saveLog3($message);
        return $message;
    }

    public function getRouteWithGoogle($lat1, $long1, $lat2, $long2)
    {
        try {
            $data = [
                "origin" => [
                    "location" => [
                        "latLng" => [
                            "latitude" => $lat1,
                            "longitude" => $long1
                        ]
                    ]
                ],
                "destination" => [
                    "location" => [
                        "latLng" => [
                            "latitude" => $lat2,
                            "longitude" => $long2
                        ]
                    ]
                ],
                "travelMode" => "WALK",
                "routingPreference" => "TRAFFIC_AWARE",
                "departureTime" => Carbon::now()->addMinutes(10)->toIso8601ZuluString(),
                "computeAlternativeRoutes" => false,
                "languageCode" => "en-US",
                "units" => "IMPERIAL"
            ];

            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'X-Goog-Api-Key' => Config::get('constants.GOOGLE_API_KEY'),
                'X-Goog-FieldMask' => 'routes.distanceMeters'
            ])->post('https://routes.googleapis.com/directions/v2:computeRoutes', $data);

            if ($response->successful()) {
                return $response->json()['routes'][0]['distanceMeters'] ?? null;
            }

            return null;
        } catch (\Exception $e) {
            return null;
        }
    }

    // Database logging methods
    public function saveLog($message)
    {
        DB::table('coverage_log')->insert($this->createLogData($message));
    }

    public function saveLog2($message)
    {
        DB::table('Need_Survey')->insert($this->createLogData($message));
    }

    public function saveLog3($message)
    {
        DB::table('Coming_Soon')->insert($this->createLogData($message));
    }

    private function createLogData($message)
    {
        return [
            'splitter_id' => $this->closestSplitterBoxname,
            'splitter_lat' => $this->closestSplitterLat,
            'splitter_lon' => $this->closestSplitterLon,
            'customer_lat' => $this->customerLatitude,
            'customer_lon' => $this->customerLongitude,
            'search_distance' => $this->closestSplitterboxDistance,
            'search_result' => $message,
            'created_at' => now()
        ];
    }

    public function render()
    {
        return view('livewire.home');
    }
}