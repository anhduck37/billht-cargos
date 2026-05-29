<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\OrderTracking;
use App\PartnerTracking;
use App\Services\MickeyService;
use App\Services\MickeyTrackingSyncService;
use App\Services\ViettelPostService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Flash;

class OrderTrackingController extends Controller
{
    protected $mickeyService;
    protected $viettelPostService;
    protected $mickeyTrackingSyncService;

    public function __construct(
        MickeyService $mickeyService,
        ViettelPostService $viettelPostService,
        MickeyTrackingSyncService $mickeyTrackingSyncService
    )
    {
        $this->mickeyService = $mickeyService;
        $this->viettelPostService = $viettelPostService;
        $this->mickeyTrackingSyncService = $mickeyTrackingSyncService;
    }

    public function tracking(Request $request)
    {
        $order_code = $request->order_code;
        $order_trackings = [];
        $delivery_status = 0;
        $order = null;
        $mickey_tracking = null;
        $data_tracking = null;
        $orders = [];
        if ($request->search && !$order_code) {
            $monthsAgo = Carbon::now()->subMonths(config('order_manager.months_ago_to_get_bill'));
            $firstMonthAgo = $monthsAgo->startOfMonth();
            $pageSize = config('order_manager.page_size');
            $orders = Order::with([
                'sender.city',
                'sender.ward',
                'sender.district',
                'receiver.city',
                'receiver.ward',
                'receiver.district'
            ])->join('senders', 'senders.id', '=', 'orders.sender_id')
                ->join('receivers', 'receivers.id', '=', 'orders.receiver_id')
                ->where(function ($q) use ($firstMonthAgo) {
                    $q->where('orders.created_at', '>=', $firstMonthAgo)
                        ->orWhere('orders.updated_at', '>=', $firstMonthAgo);
                });
            if (isset($request->search)) {
                $orders->where(function ($q) use ($request) {
                    $q->orWhere('senders.sender_name', 'LIKE', '%' . $request->search . '%')
                        ->orWhere('senders.sender_phone', '=', trim($request->search))
                        ->orWhere('receivers.receiver_name', 'LIKE', '%' . $request->search . '%')
                        ->orWhere('receivers.receiver_phone', '=', trim($request->search))
                        ->orWhere('orders.order_code', 'LIKE', '%' . trim($request->search) . '%');
                });
            }
            $orders = $orders->select('orders.*')->orderBy('orders.id', 'DESC')->groupBy('orders.id')->paginate($pageSize);
        } else {
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
                if (
                    isset($order) && (isset($order->order_partner_code) || (isset(Order::MAP_MESSAGE_NOTI_PARTNER[$order->partner_code])))
                ) {
                    if ($order->partner_code === Order::CODE_VIETTEL_POST) {
                        $data_tracking = $this->viettelPostService->refreshTrackingForOrder($order);
                    }

                    if (!$data_tracking || $data_tracking->isEmpty()) {
                        $query = PartnerTracking::where('order_id', $order->id);
                        if ($order->partner_code === Order::CODE_VIETTEL_POST && $order->order_partner_code) {
                            $query->where('order_partner_code', $order->order_partner_code);
                        }
                        $data_tracking = $query->orderBy('id', 'DESC')->get();
                    }
                    $data_tracking = $data_tracking->isEmpty() ? null : $data_tracking;
                } else {
                    $mickey_tracking = $this->mickeyService->tracking($order, $order_code);
                    if ($order && $this->mickeyTrackingSyncService->hasTrackingData($mickey_tracking)) {
                        $this->mickeyTrackingSyncService->syncOrder($order, $mickey_tracking);
                    }
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
        }

        return view('tracking', [
            'order_trackings' => $order_trackings,
            'delivery_status' => $delivery_status,
            'order' => $order,
            'mickey_tracking' => $mickey_tracking,
            'data_tracking' => $data_tracking,
            'orders' => $orders
        ]);
    }
}
