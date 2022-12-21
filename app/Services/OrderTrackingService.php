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
            'request' => json_encode($request),
            'delivery_status' => $order->delivery_status,
            'city_id' => $order->city_id,
            'person_charge' => $order->person_charge,
            'signator' => $order->signator
        ];
        OrderTracking::create($data);
    }

    public function createMany($orderIds, $delivery_status, $request) {
        $dataInsert = [];
        foreach ($orderIds as $id) {
            $order = Order::where('id', $id)->first();
            if($order) {
                $dataInsert[] = [
                    'order_id' => $order->id,
                    'order_code' => $order->order_code,
                    'order_status' => $order->order_status,
                    'user_id' => auth()->user()->id,
                    'request' => json_encode($request),
                    'delivery_status' => $delivery_status,
                    'city_id' => $order->city_id,
                    'person_charge' => $order->person_charge,
                    'signator' => $order->signator,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ];
            }
        }
        if(!empty($dataInsert)) {
            OrderTracking::insert($dataInsert);
        }
    }

    public function update($order, $delivery_status) {
        $orderTracking = OrderTracking::where('order_id', $order->id)->where('delivery_status', $delivery_status)->update(['signator' => $order->signator]);
        return $orderTracking;
    }
}
