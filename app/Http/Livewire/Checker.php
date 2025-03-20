<?php

namespace App\Http\Livewire;

use Livewire\Component;
use App\Http\Controllers\BaseController as BaseController;
use App\Models\Splitter_box;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Livewire;
use Livewire\LivewireManager;
use Config;
use Validator;

class Checker extends BaseController
{
    public $address;
    public $geoData;
    private $baseUrl;
    public $result;
    public $geoAddress;
    public $isLocated = false;


    public function convertAddress(Request $request)
    {
        $input = $request->all();
   
        $validator = Validator::make($input, [
            'address' => 'required|string',
        ]);
   
        if($validator->fails()){
            return $this->sendError('Validation Error.', $validator->errors(), 403);
        }

        try {
            $this->address = $input['address'];
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

                        return $this->sendResponse($this->geoAddress, 'address converted successfully');
                    } else{
                        return $this->sendError('Error.', 'Please enter a valid address', 403);
                    }
     
            } else {
                return $this->sendError('Error.', 'Please enter a valid address', 403);
            }
            
        } catch (Exception $e) {
            return $this->sendError('Error.', 'Something went wrong. Try again!', 403);
        }
    }



    // retrieve user selected 
    public function location(Request $request)
    {   
        $input = $request->all();
   
        $validator = Validator::make($input, [
            'lat' => 'required|numeric',
            'lon' => 'required|numeric',
        ]);
   
        if($validator->fails()){
            return $this->sendError('Validation Error.', $validator->errors(), 403);       
        }

        try {
            $lat = $input['lat'];
            $lon = $input['lon'];

            $home = app()->make(Home::class);
            $this->result = $home->findLocation($lat, $lon);

            return $this->sendResponse($this->result, 'success');
                
        } catch (Exception $e) {
            return $this->sendError('Error.', 'Something went wrong. Try again!', 403);
        }
    }



    public function render()
    {
        return view('livewire.checker');
    }
}
