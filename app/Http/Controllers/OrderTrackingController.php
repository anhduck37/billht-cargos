<?php

namespace App\Http\Controllers;

use App\OrderTracking;
use Illuminate\Http\Request;

class OrderTrackingController extends Controller
{
    public function tracking(Request $request) {
        $order_code = $request->order_code;
        $order_trackings = [];
        if($order_code) {
            $order_trackings = OrderTracking::where('order_code', $order_code)->get();
        }
        return view('tracking', ['order_trackings' => $order_trackings]);
    }
}
