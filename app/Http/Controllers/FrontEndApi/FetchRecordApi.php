<?php

namespace App\Http\Controllers\FrontEndApi;

use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Mail\UserVerificationEmail;
use App\Http\Controllers\Controller;
use App\Models\Amenties;
use App\Models\City;
use App\Models\Place;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class FetchRecordApi extends Controller
{
    protected function cities()
    {
        try {
            $cities = City::orderBy('id', 'DESC')->paginate(6);
            if ($cities->isEmpty()) {
                return response()->json(['messsage' => 'Places fetch not found.'], 200);
            }
            return response()->json(['cities' => $cities, 'messsage' => 'Cities Fetch Records.', 'status' => 200]);
        } catch (\Throwable $th) {
            return response()->json(['cities' => [], 'error' => $th->getMessage()], 422);
        }
    }

    protected function places()
    {
        try {
            $places = Place::with(['placeImages', 'user'])->orderBy('id', 'DESC')->get();
            if ($places->isEmpty()) {
                return response()->json(['messsage' => 'Places fetch not found.'], 200);
            }
            return response()->json(['places' => $places, 'messsage' => 'Places Fetch Records.', 'status' => 200]);
        } catch (\Throwable $th) {
            return response()->json(['error' => $th->getMessage()], 422);
        }
    }
    protected function destinationPlace($slug)
    {
        try {
            $destinationPlace = City::with(['places.placeImages'])->where('slug', $slug)->first();
            return response()->json(['destinationPlace' => $destinationPlace, 'messsage' => 'Destination place Fetch Records.', 'status' => 200]);
        } catch (\Throwable $th) {
            return response()->json(['error' => $th->getMessage()], 422);
        }
    }
    protected function place_detail($slug)
    {
        try {
            $placeDetail = Place::with(['placeImages', 'user', 'property'])->where('slug', $slug)->first();
            $amenities_id = explode(',', $placeDetail->amenities_id);
            $amenities = Amenties::whereIn('id', $amenities_id)->get();
            return response()->json(['placeDetail' => $placeDetail, 'amenities' =>  $amenities, 'messsage' => 'Place detail Fetch Records.', 'status' => 200]);
        } catch (\Throwable $th) {
            return response()->json(['error' => $th->getMessage()], 422);
        }
    }
}
