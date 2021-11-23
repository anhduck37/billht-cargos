<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\OrderTracking;
use Illuminate\Http\Request;

class OrderTrackingController extends Controller
{
    public function tracking(Request $request) {
        $order_code = $request->order_code;
        $order_trackings = [];
        $delivery_status = 0;
        if($order_code) {
            $order_trackings = OrderTracking::where('order_code', $order_code)->get();
            if($order_trackings && count($order_trackings) > 0) {
                $delivery_status = $order_trackings[count($order_trackings) - 1]->delivery_status;
            }
        }
        return view('tracking', ['order_trackings' => $order_trackings, 'delivery_status' => $delivery_status]);
    }
}
