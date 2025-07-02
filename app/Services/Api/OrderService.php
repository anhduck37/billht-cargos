<?php

namespace App\Services\Api;

use App\Services\_Abstract\BaseService;
use App\Models\Order;

class OrderService extends BaseService
{

    public function detail($order_code)
    {
        $order = Order::with([
            'sender.city',
            'sender.district',
            'sender.ward',
            'receiver.city',
            'receiver.district',
            'receiver.ward',
        ])->where('order_code', '=', $order_code)->first();
        if (!$order) {
            return $this->sendErrorResponse('Mã vận đơn không tồn tại');
        }
        $data = [
            'order_code' => $order->order_code,
            'sender' => $order->sender,
            'receiver' => $order->receiver,
            'weight' => $order->weight,
            'width' => $order->width,
            'height' => $order->height,
            'delivery_status_code' => $order->delivery_status,
            'delivery_status_name' => Order::DELIVERY_MAP[$order->delivery_status] ?? '',
            'payment_method_name' => Order::PAYMENT_METHOD_MAP[$order->payment_method] ?? '',
            'payment_method_code' => $order->payment_method,
            'signator' => $order->signator,
            'note' => $order->note
        ];
        return $this->sendSuccessResponse($data, 'Success');
    }
}
