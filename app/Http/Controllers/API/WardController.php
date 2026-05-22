<?php

namespace App\Http\Controllers\API;

use App\District;
use App\Http\Controllers\Controller;
use App\Ward;
use Illuminate\Http\Request;

class WardController extends Controller
{
    public function getWards(Request $request) {
        $district_id = $request->district_id;
        $districts = Ward::where('district_id', $district_id)->get();
        return response()->json($districts);
    }

    public function getNewWards(Request $request, $province_id) {
        $wards = \App\NewWard::where('new_province_id', $province_id)->get();
        return response()->json($wards);
    }
}
