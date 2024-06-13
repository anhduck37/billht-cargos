<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\OrderTracking;
use App\Services\MickeyService;
use Illuminate\Http\Request;
use Flash;

class OrderTrackingController extends Controller
{
    protected $mickeyService;

    public function __construct(MickeyService $mickeyService)
    {
        $this->mickeyService = $mickeyService;
    }

    public function tracking(Request $request) {
        $order_code = $request->order_code;
        $order_trackings = [];
        $delivery_status = 0;
        $order = null;
        $mickey_tracking = null;
        if($order_code) {
            // $order_trackings = OrderTracking::join('orders', 'orders.id', '=', 'order_trackings.order_id')
            // ->where('orders.invoice_code', $order_code)->orWhere('order_trackings.order_code', $order_code)
            // ->select('order_trackings.*', 'orders.invoice_code')->get();
            // if($order_trackings && count($order_trackings) > 0) {
            //     $delivery_status = $order_trackings[count($order_trackings) - 1]->delivery_status;
            // }

            $order = Order::with(['order_trackings'])->where('order_code', $order_code)
                // ->orWhere('invoice_code', $order_code)
                ->first();
        }
        if($order || $order_code) {
            $mickey_tracking = $this->mickeyService->tracking($order, $order_code);
        }
        if(!$order && $order_code && !$mickey_tracking) {
            Flash::warning('Mã vận đơn không tồn tại hoặc chưa chính xác, vui lòng kiểm tra lại.');
        } else if($order) {
            $order_trackings = $order->order_trackings;
            $delivery_status = $order->delivery_status;
            // if($order_trackings && isset($order_trackings)) {
                // $delivery_status = $order_trackings[count($order_trackings) - 1]->delivery_status;
            // }
        }

        return view('tracking', [
            'order_trackings' => $order_trackings, 
            'delivery_status' => $delivery_status, 
            'order' => $order,
            'mickey_tracking' => $mickey_tracking
        ]);
    }
}
