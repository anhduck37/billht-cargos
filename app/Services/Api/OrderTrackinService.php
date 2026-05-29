<?php

namespace App\Services\Api;

use App\Services\_Abstract\BaseService;
use App\Models\Order;
use App\PartnerTracking;
use App\Services\MickeyService;
use App\Services\MickeyTrackingSyncService;
use App\Services\ViettelPostService;

class OrderTrackinService extends BaseService
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

    public function list($order_code)
    {
        $order = Order::with(['order_trackings'])->where('order_code', $order_code)->first();
        if (!$order) {
            return $this->sendErrorResponse('Mã vận đơn không tồn tại hoặc chưa chính xác, vui lòng kiểm tra lại.');
        }
        $data_trackings = [];

        if (
            isset($order) && (isset($order->order_partner_code) || (isset(Order::MAP_MESSAGE_NOTI_PARTNER[$order->partner_code])))
        ) {
            if ($order->partner_code === Order::CODE_VIETTEL_POST) {
                $data_trackings = $this->viettelPostService->refreshTrackingForOrder($order);
            }

            if (!$data_trackings || $data_trackings->isEmpty()) {
                $query = PartnerTracking::where('order_id', $order->id);
                if ($order->partner_code === Order::CODE_VIETTEL_POST && $order->order_partner_code) {
                    $query->where('order_partner_code', $order->order_partner_code);
                }
                $data_trackings = $query->orderBy('id', 'DESC')->get();
            }
            $data_trackings = $data_trackings->isEmpty() ? [] : $data_trackings;
        } else {
            $mickeyTracking = $this->mickeyService->tracking($order, $order_code);
            if ($this->mickeyTrackingSyncService->hasTrackingData($mickeyTracking)) {
                $this->mickeyTrackingSyncService->syncOrder($order, $mickeyTracking);
            }
            $data_trackings = $mickeyTracking['table'] ?? [];
        }

        if (empty($data_trackings)) {
            $data_trackings = $order->order_trackings;
        }
        $data = $this->mapTracking($order, $data_trackings);
        return $this->sendSuccessResponse($data);
    }

    private function mapTracking($order, $dataTrackings)
    {
        $mapData = [];
        foreach ($dataTrackings as $item) {
            $dataItem = [
                'code' => $order->order_code,
                'date' => isset($item->updated_at) ? $item->updated_at : null,
                'status' => isset($item->delivery_status) ? $order->getDeliveryStatusName($item->delivery_status) : null,
                'sender_name' => isset($order->sender) ? $order->sender->sender_name : '',
                'receiver_name' => isset($order->receiver) ? $order->receiver->receiver_name : '',
                'signator' => isset($item->signator) ? $item->signator : '',
                'note' => isset($item->note) ? $item->note : '',
                'address' => isset($order->receiver->address) ? $order->receiver->address : ''
            ];
            switch ($order->partner_code) {
                case Order::CODE_VIETTEL_POST:
                case Order::CODE_EMS:
                    $dataItem['status'] = $item['status_name'];
                    $dataItem['note'] = $item['note'];
                    $dataItem['order_statusdate'] = $item['order_statusdate'];
                    break;
                default:
                    $dataItem['date'] = isset($item['ngay_den']) ? $item['ngay_den'] . ' ' . $item['gio'] : $dataItem['date'];
                    $dataItem['status'] = isset($item['trang_thai']) ? $item['trang_thai'] : $dataItem['status'];
                    $dataItem['address'] = isset($item['dchi']) ? $item['dchi'] : $dataItem['address'];
                    break;
            }
            array_push($mapData, $dataItem);
        }
        return $mapData;
    }
}
