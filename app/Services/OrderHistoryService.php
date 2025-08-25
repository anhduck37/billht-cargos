<?php

namespace App\Services;

use App\OrderHistory;

class OrderHistoryService
{
    public function createOrderHistory($order_old, $order_new, $dataForm, $is_total_order, $type_order)
    {
        $data = [
            // 'order_new' => json_encode($order_new),
            // 'request' => json_encode($dataForm),
            'user_id' => auth()->user() ? auth()->user()->id : 0,
            'order_id' => $order_new->id,
            'user_level' => auth()->user() ? auth()->user()->level : 0,
            'is_total_order' => $is_total_order,
            'type_order' => $type_order
        ];
        // if(isset($order_old)) {
        //     $data['order_old'] = json_encode($order_old);
        // }
        $orderHistory = new OrderHistory();
        $orderHistory->fill($data);
        $orderHistory->save();
    }

    public function insertManyOrderHistory($orders, $type_order, $is_total_order = OrderHistory::NOT_TOTAL_ORDER)
    {
        $insertOrders = [];
        foreach ($orders as $order) {
            $item = [
                'user_id' => auth()->user() ? auth()->user()->id : 0,
                'order_id' => $order->id,
                'user_level' => auth()->user() ? auth()->user()->level : 0,
                'is_total_order' => $is_total_order,
                'type_order' => $type_order,
                'created_at' => now(),
                'updated_at' => now()
            ];
            array_push($insertOrders, $item);
        }
        if (count($insertOrders) <= 0) {
            return;
        }
        OrderHistory::insert($insertOrders);
    }
}
