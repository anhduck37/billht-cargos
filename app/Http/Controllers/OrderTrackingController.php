<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\OrderTracking;
use Illuminate\Http\Request;
use Flash;

class OrderTrackingController extends Controller
{
    public function tracking(Request $request) {
        $order_code = $request->order_code;
        $order_trackings = [];
        $delivery_status = 0;
        $order = null;
        if($order_code) {
            $order_trackings = OrderTracking::join('orders', 'orders.id', '=', 'order_trackings.order_id');
            $order_trackings->where('orders.invoice_code', $order_code)->orWhere('order_trackings.order_code', $order_code);
            $order_trackings = $order_trackings->select('order_trackings.*', 'orders.invoice_code')->get();
            if($order_trackings && count($order_trackings) > 0) {
                $delivery_status = $order_trackings[count($order_trackings) - 1]->delivery_status;
            }

            $order = Order::where('order_code', $order_code)->first();
        }
        if(!$order) {
            Flash::warning('Mã vận đơn không tồn tại hoặc chưa chính xác, vui lòng kiểm tra lại.');
        }
        return view('tracking', ['order_trackings' => $order_trackings, 'delivery_status' => $delivery_status, 'order' => $order]);
    }
}
