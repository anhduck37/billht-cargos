<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\OrderTracking;
use App\PartnerTracking;
use App\Services\MickeyService;
use App\Services\ViettelPostService;
use Illuminate\Http\Request;
use Flash;

class OrderTrackingController extends Controller
{
    protected $mickeyService;
    protected $viettelPostService;

    public function __construct(MickeyService $mickeyService, ViettelPostService $viettelPostService)
    {
        $this->mickeyService = $mickeyService;
        $this->viettelPostService = $viettelPostService;
    }

    public function tracking(Request $request)
    {
        $order_code = $request->order_code;
        $order_trackings = [];
        $delivery_status = 0;
        $order = null;
        $mickey_tracking = null;
        $viettel_post = null;
        if ($order_code) {
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
        if ($order || $order_code) {
            if (isset($order->order_partner_code) || $order->partner_code == Order::CODE_VIETTEL_POST) {
                $viettel_post = PartnerTracking::where('order_id', $order->id)->orderBy('id', 'DESC')->get();
            } else {
                $mickey_tracking = $this->mickeyService->tracking($order, $order_code);
            }
        }
        if (!$order && $order_code && empty($mickey_tracking['table']) && empty($mickey_tracking['table1'])) {
            Flash::warning('Mã vận đơn không tồn tại hoặc chưa chính xác, vui lòng kiểm tra lại.');
        } else if ($order) {
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
            'mickey_tracking' => $mickey_tracking,
            'viettel_post' => $viettel_post
        ]);
    }
}
