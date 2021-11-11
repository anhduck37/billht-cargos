<?php

namespace App\Http\Controllers\API;

use App\District;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class DistrictController extends Controller
{
    public function getDistricts(Request $request) {
        $city_id = $request->city_id;
        $districts = District::where('city_id', $city_id)->get();
        return response()->json($districts);
    }
}
