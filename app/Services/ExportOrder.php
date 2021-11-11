<?php

namespace App\Services;

use App\Models\Order;

class ExportOrder
{
    public function exportDataOrder($dataOrder)
    {
        $columns = ['customer_name', 'customer_phone', 'customer_email', 'order_status', 'delivery_status', 'tracking_code', 'User', 'created_at', 'delivery_date', 'total', 'percent_commission', 'is_paid_profit', 'note'];
        $data[] = $columns;
        foreach ($dataOrder as $order) {
            $dataRow = [];
            foreach ($columns as $row) {
                if ($row == 'order_status') {
                    $dataRow[] = $order->status_name;
                } else if ($row == 'delivery_status') {
                    $dataRow[] = $order->delivery_name;
                } else if ($row == 'tracking_code') {
                    $dataRow[] = ($order->user) ? $order->user->tracking_code : '';
                } else if ($row == 'User') {
                    $dataRow[] = ($order->user) ? $order->user->email : '';
                } else if ($row == 'is_paid_profit') {
                    $dataRow[] = (array_key_exists($order->is_paid_profit, Order::ORDER_PAYMENT_PROFIT_MAP)) ? Order::ORDER_PAYMENT_PROFIT_MAP[$order->is_paid_profit] : $order->is_paid_profit;
                } else {
                    $dataRow[] = $order->{$row};
                }                                
            }
            $data[] = $dataRow;
        }
        return $data;        
    }
}