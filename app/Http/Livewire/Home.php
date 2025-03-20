<?php

namespace App\Http\Livewire;

use Livewire\Component;
use App\Models\Splitter_box;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Livewire;
use Livewire\LivewireManager;
use Config;
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


    public function mount()
    {
        $this->geoData = $this->splitterBoxes();
    }

    public $closestSplitterLat;
    public $closestSplitterLon;

    /*public function convertAddress2()
    {
        try {
            $endpoint = Config::get('constants.LOCATIONIQ_API');
            $key = Config::get('constants.LOCATIONIQ_API_KEY');
            $this->baseUrl = $endpoint.$key.'&q=';

            $response = Http::get($this->baseUrl.$this->address.'&format=json');

            if ($response->successful()) {
                    $res = $response->json();

                    if(count($res) >= 1){
                        $this->isLocated = true;
                        $this->geoAddress = $res;
                 
                    } else{
                        //$this->convertAddress2();
                        $this->result = "Please enter a valid address";
                        session()->flash('message', $this->result);
                    }
     
            } else {
                $this->result = "Please enter a valid address";
                session()->flash('message', $this->result);
            }
            
        } catch (Exception $e) {
            $this->result = "Something went wrong. Try again!";
            session()->flash('message', $this->result);
        }
    }*/


    public function convertAddress()
    {
        try {
            $endpoint = Config::get('constants.GOOGLE_GEO_API');
            $key = Config::get('constants.GOOGLE_API_KEY');
            $this->baseUrl = $endpoint.'address='.$this->address.'&key='.$key;

            $response = Http::get($this->baseUrl);

            if ($response->successful()) {
                    $res = $response->json();
                    $res = $res['results'];

                    if(count($res) >= 1){
                        $this->isLocated = true;
                        $this->geoAddress = $res;
                    } else{
                        $this->result = "Please enter a valid address";
                        session()->flash('message', $this->result);
                    }
     
            } else {
                $this->result = "Please enter a valid address";
                session()->flash('message', $this->result);
            }
            
        } catch (Exception $e) {
            $this->result = "Something went wrong. Try again!";
            session()->flash('message', $this->result);
        }
    }



    // retrieve user selected address
    public function location()
{   
    try {
        if($this->selectedaddress != "") {
            $geoData = explode("-", $this->selectedaddress);
            $lat = $geoData[0];
            $lon = $geoData[1];

            $this->customerLatitude = $lat;
            $this->customerLongitude = $lon;

            $this->result = $this->findLocation($lat, $lon, $this->geoData);

            // Build message after getting result
            if($this->result == "FIBERONE BROADBAND is available in your area") {
                $fullMessage = $this->result . " | Splitter ID: {$this->closestSplitterBoxname} | " 
                            . "Coordinates: {$this->closestSplitterLat}, {$this->closestSplitterLon}";
                session()->flash('success_message', $fullMessage);
            } else if($this->result == "ADDITIONAL SURVEY NEEDED") {
                session()->flash('message', $this->result);
            } else {
                session()->flash('failed_message', $this->result);
            }
        } else {
            $this->result = "Please select a specific location";
            session()->flash('message', $this->result);
        }
    } catch (Exception $e) {
        $this->result = "Something went wrong. Try again!";
        session()->flash('message', $this->result);
    }
}


    //retrieve splitter boxes
    public function splitterboxes()
    {
        try {
            $boxes = DB::table('splitterboxes')
            ->select('Longitude','Latitude', 'Splitter_B')
            ->get();

            if (count($boxes) <= 0) {
                return 'No record found';
            } else {
                return $boxes;
            }
        } catch (Exception $e) {
            $this->result = "Something went wrong. Try again!";
            session()->flash('message', $this->result);
        }
    }


    // Find location using GOOGLE ROUTE
    public function getRouteWithGoogle($lat1, $long1, $lat2, $long2)
    {
        $dateTime = Carbon::now();
        $futureDateTime = $dateTime->addMinutes(10);
        $currentDateTime = $futureDateTime->format('Y-m-d\TH:i:s.u\Z');

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
            "departureTime" => $currentDateTime,
            "computeAlternativeRoutes" => false,
            "routeModifiers" => [
                "avoidTolls" => false,
                "avoidHighways" => false,
                "avoidFerries" => false
            ],
            "languageCode" => "en-US",
            "units" => "IMPERIAL"
        ];

        // Define your request headers
        $headers = [
            'Content-Type' => 'application/json',
            'X-Goog-Api-Key' => 'AIzaSyCvxKeZON2z3FqFRHOjzWJZglJR-5B9Jx0',
            'X-Goog-FieldMask' => 'routes.duration,routes.distanceMeters,routes.polyline.encodedPolyline'
        ];

        // Make the HTTP request using Laravel HTTP client
        $response = Http::withHeaders($headers)
            ->post('https://routes.googleapis.com/directions/v2:computeRoutes', $data);

        if ($response->successful()) {
            $responseData = $response->json();
            return $responseData['routes'][0]['distanceMeters'];
        } else {
            $error = $response->status() . ' ' . $response->body();
            // dd($error);
        }

    }


    // Find splitter box location
    public function findLocation($lat, $long, $locations = []) {
        $maxDistance = 0.25; // in kilometers
        $maxDistance2 = 1;
        $closestLocation = null;
        if (is_a($closestLocation, 'stdClass')) {
            $this->closestSplitterBoxname = $closestLocation->Splitter_B;
            $this->closestSplitterLat = $closestLocation->Latitude; // New
            $this->closestSplitterLon = $closestLocation->Longitude; // New
        } else {
            $this->closestSplitterBoxname = $closestLocation['Splitter_B'];
            $this->closestSplitterLat = $closestLocation['Latitude']; // New
            $this->closestSplitterLon = $closestLocation['Longitude']; // New
        }
        $closestDistance = INF;
        $this->isClose = false;
        $this->isFound = false;

        if(empty($locations)){
            $this->mount();
            $locations = $this->geoData;
        }

        // Iterate over all locations and find the closest one
        foreach ($locations as $location) {
            $lat = (double)$lat;
            $lon = (double)$long;

            if (is_a($location, 'stdClass')) {
                $lat2 = (double)$location->Latitude;
                $lon2 = (double)$location->Longitude;
            } else {
                $lat2 = (double)$location['Latitude'];
                $lon2 = (double)$location['Longitude'];
            }

            $dist = 0;

            // Calculate the distance between the user's location and this location
            $dist = $this->HaversineFormula($lat, $lon, $lat2, $lon2);
     
            // Check if this location is closer than the current closest one
            if($dist < $closestDistance && $dist <= $maxDistance) { 
                $this->isFound = true;
                $closestLocation = $location;
                $closestDistance = $dist;
            }
            else if($dist < $closestDistance && $dist <= $maxDistance2) {
                $this->isClose = true;
                $closestLocation = $location;
                $closestDistance = $dist;
            }
        }

        if($closestLocation !== null){
            if (is_a($closestLocation, 'stdClass')) {
                $this->closestSplitterBoxname = $closestLocation ? $closestLocation->Splitter_B : 'None';
                $latitude2 = $closestLocation->Latitude;
                $longitude2 = $closestLocation->Longitude;
            } else {

                $this->closestSplitterBoxname = $closestLocation ? $closestLocation['Splitter_B'] : 'None';
                $latitude2 = $closestLocation['Latitude'];
                $longitude2 = $closestLocation['Longitude'];
            }

            $routeClosestDistance = $this->getRouteWithGoogle($lat, $lon, $latitude2, $longitude2);

            $this->closestSplitterboxDistance = $closestDistance 
            == INF ? 'out of bound' :  $routeClosestDistance;
        }
        

        $found = "FIBERONE BROADBAND is available Provided not a Court norrequire expansion expansion";
        $veryClose = "ADDITIONAL SURVEY NEEDED";
        $notFound = "COMING SOON";

        if($this->isFound){
            $this->saveLog($found);
            return $found;
        } else{
            if($this->isClose){
                $this->saveLog2($veryClose);
                return $veryClose;
            } else {
                $this->saveLog3($notFound);
                return $notFound;
            }
        }
    }



    // Function to calculate the distance between two points using the Haversine formula
    private function HaversineFormula($lat1, $long1, $lat2, $long2)
    {
        $R = 6371; // radius of the Earth in kilometers
        $dLat = deg2rad($lat2 - $lat1);
        $dLong = deg2rad($long2 - $long1);

        $a =
            sin($dLat / 2) * sin($dLat / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($dLong / 2) * sin($dLong / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        $distance = $R * $c;

        return $distance;
    } 


    // Refresh page
    public function refresh()
    {
        return redirect(request()->header('Referer'));
    }



    // Save customer info
    public function storeContact()
    {
        $date = date('Y-m-d H:i:s');

        try {
            if($this->fullname != '' || $this->email != '' || 
                $this->phone != '')
            {
                DB::table('coverage_user')->insert([
                    'fullname' => $this->fullname,
                    'email' => $this->email,
                    'phone' => $this->phone,
                    'latitude' => $this->customerLatitude,
                    'longitude' => $this->customerLongitude,
                    'closest_splitterbox_distance' => $this->closestSplitterboxDistance,
                    'closest_splitterbox_name' => $this->closestSplitterBoxname,
                    'search_result' => $this->result,
                    'created_at' => $date,
                ]);

                $this->refresh();
                $this->isSubmitted = true;
                $this->result = "Thank you for contacting FiberOne, one of our Representatives will reach out to you soon";
                session()->flash('thankyou_message', $this->result);

                $emailData = [
                    'fullname' => $this->fullname,
                    'email' => $this->email,
                ];

                //$this->sendEmail($emailData);

            } else{
                $this->errorMessage = "All fields are required";
            }
            
        } catch (Exception $e) {
            $this->result = "Something went wrong. Try again!";
            session()->flash('errorMessage', $this->result);
        }
    }



    // Save all search log
    public function saveLog($responseMessage)
    {
        $date = date('Y-m-d H:i:s');

        try {
            DB::table('coverage_log')->insert([
                'latitude' => $this->customerLatitude,
                'longitude' => $this->customerLongitude,
                'closest_splitterbox_distance' => $this->closestSplitterboxDistance,
                'closest_splitterbox_name' => $this->closestSplitterBoxname,
                'search_result' => $responseMessage,
                'created_at' => $date,
            ]);
        } catch (Exception $e) {
            $this->result = "Something went wrong. Try again!";
            session()->flash('message', $this->result);
        }
    }


    public function saveLog2($responseMessage)
    {
        $date = date('Y-m-d H:i:s');

        try {
            DB::table('Need_Survey')->insert([
                'latitude' => $this->customerLatitude,
                'longitude' => $this->customerLongitude,
                'search_distance' => $this->closestSplitterboxDistance,
                'search_result' => $responseMessage,
                'created_at' => $date,
            ]);
        } catch (Exception $e) {
            $this->result = "Something went wrong. Try again!";
            session()->flash('message', $this->result);
        }
    }


    public function saveLog3($responseMessage)
    {
        $date = date('Y-m-d H:i:s');

        try {
            DB::table('Coming_Soon')->insert([
                'latitude' => $this->customerLatitude,
                'longitude' => $this->customerLongitude,
                'search_distance' => $this->closestSplitterboxDistance,
                'search_result' => $responseMessage,
                'created_at' => $date,
            ]);
        } catch (Exception $e) {
            $this->result = "Something went wrong. Try again!";
            session()->flash('message', $this->result);
        }
    }


    private function sendEmail($data){
        try {
            $name = $data['fullname'] != '' ? $data['fullname'] : 'there';
            Mail::to($data['email'])
            ->send(new NotifyUser($name));
        } catch (Exception $e) {
            $this->result = "Server Error! Email was not send";
            session()->flash('message', $this->result);
        }
    }



    public function render()
    {
        return view('livewire.home');
    }
}
