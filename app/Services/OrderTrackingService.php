<?php

namespace App\Services;

use App\Models\Order;
use App\OrderTracking;
use App\Partner;
use App\Service;

class OrderTrackingService
{
    public function create($order, $request) {
        $data = [
            'order_id' => $order->id,
            'order_code' => $order->order_code,
            'order_status' => $order->order_status,
            'user_id' => auth()->user()->id,
            'request' => json_encode($request)
        ];
        OrderTracking::create($data);
    }


}
