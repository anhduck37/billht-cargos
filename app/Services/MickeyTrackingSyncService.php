<?php

namespace App\Services;

use App\Models\Order;
use App\OrderHistory;
use App\OrderTracking;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class MickeyTrackingSyncService
{
    public function hasTrackingData($tracking)
    {
        return is_array($tracking)
            && (!empty($tracking['table']) || !empty($tracking['table1']));
    }

    public function syncOrder(Order $order, $tracking = null, $dryRun = false)
    {
        if (!$this->hasTrackingData($tracking)) {
            return [
                'detected' => false,
                'updated' => false,
                'message' => 'Mickey khong co du lieu',
            ];
        }

        $mappedStatus = $this->mapDeliveryStatus($tracking);
        $statusText = $this->extractStatusText($tracking);
        $signator = $this->extractSignator($tracking);
        $trackingTime = $this->extractTrackingTime($tracking);
        $oldStatus = (int)$order->delivery_status;
        $newStatus = $mappedStatus ?: $oldStatus;
        $willUpdateStatus = $mappedStatus && $oldStatus !== $mappedStatus;
        $willUpdateProvider = $order->tracking_provider !== Order::TRACKING_PROVIDER_MICKEY;
        $willUpdateSignator = $signator && $order->signator !== $signator;

        if ($dryRun) {
            return [
                'detected' => true,
                'updated' => $willUpdateStatus || $willUpdateProvider || $willUpdateSignator,
                'message' => 'Dry run',
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
                'status_text' => $statusText,
            ];
        }

        if ($willUpdateProvider) {
            $order->tracking_provider = Order::TRACKING_PROVIDER_MICKEY;
        }

        if ($willUpdateStatus) {
            $order->delivery_status = $mappedStatus;
        }

        if ($willUpdateSignator) {
            $order->signator = $signator;
        }

        if ($willUpdateProvider || $willUpdateStatus || $willUpdateSignator) {
            $order->save();
        }

        if ($mappedStatus) {
            $this->createOrderTrackingIfNeeded($order, $mappedStatus, $statusText, $signator, $trackingTime);
        } elseif ($statusText) {
            Log::info('Mickey status is not mapped', [
                'order_id' => $order->id,
                'order_code' => $order->order_code,
                'status_text' => $statusText,
            ]);
        }

        if ($willUpdateStatus || $willUpdateProvider || $willUpdateSignator) {
            app(OrderHistoryService::class)->createOrderHistory(
                null,
                $order,
                null,
                OrderHistory::NOT_TOTAL_ORDER,
                OrderHistory::TYPE_ORDER_UPDATE,
                'MICKEY_SYNC',
                [
                    'action_desc' => 'Dong bo trang thai Mickey',
                    'old_status' => $oldStatus,
                    'new_status' => $newStatus,
                    'status_text' => $statusText,
                    'tracking_provider' => Order::TRACKING_PROVIDER_MICKEY,
                ],
                $order->order_code,
                Order::TRACKING_PROVIDER_MICKEY
            );
        }

        return [
            'detected' => true,
            'updated' => $willUpdateStatus || $willUpdateProvider || $willUpdateSignator,
            'message' => $mappedStatus ? 'Synced' : 'Detected Mickey, status not mapped',
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'status_text' => $statusText,
        ];
    }

    private function createOrderTrackingIfNeeded(Order $order, $deliveryStatus, $statusText, $signator, $trackingTime = null)
    {
        $latest = OrderTracking::where('order_id', $order->id)->orderBy('id', 'desc')->first();
        if (
            $latest
            && (int)$latest->delivery_status === (int)$deliveryStatus
            && (string)$latest->status_text === (string)$statusText
            && (string)$latest->signator === (string)$signator
        ) {
            return;
        }

        $createdAt = $trackingTime ?: Carbon::now();

        $orderTracking = new OrderTracking();
        $orderTracking->order_id = $order->id;
        $orderTracking->order_code = $order->order_code;
        $orderTracking->order_status = $order->order_status;
        $orderTracking->user_id = auth()->user() ? auth()->user()->id : 0;
        $orderTracking->delivery_status = $deliveryStatus;
        $orderTracking->city_id = $order->city_id;
        $orderTracking->person_charge = $order->person_charge;
        $orderTracking->signator = $signator ?: $order->signator;
        $orderTracking->status_text = $statusText;
        $orderTracking->created_at = $createdAt;
        $orderTracking->updated_at = $createdAt;
        $orderTracking->save();
    }

    private function mapDeliveryStatus($tracking)
    {
        $status = $this->normalizeStatus($this->extractStatusText($tracking));

        if ($status === '') {
            return null;
        }

        if ($this->containsAny($status, ['phat thanh cong', 'giao thanh cong', 'da phat thanh cong'])) {
            return Order::DELIVERY_STATUS_OK;
        }

        if ($this->containsAny($status, ['dang phat', 'di phat', 'buu ta', 'di giao'])) {
            return Order::DELIVERY_STATUS_PERSON_CHARGE;
        }

        if ($this->containsAny($status, ['da den', 'den buu cuc', 'nhap buu cuc', 'den bc'])) {
            return Order::DELIVERY_STATUS_RECEIVED;
        }

        if ($this->containsAny($status, ['chap nhan', 'nhan gui', 'da nhan gui', 'nhan hang'])) {
            return Order::DELIVERY_STATUS_PROCESSING;
        }

        return null;
    }

    private function extractStatusText($tracking)
    {
        $table1 = $tracking['table1'][0] ?? [];
        if (!empty($table1['tinh_trang'])) {
            return trim($table1['tinh_trang']);
        }

        $table = $tracking['table'] ?? [];
        if (!empty($table)) {
            $last = end($table);
            return trim($last['trang_thai'] ?? '');
        }

        return '';
    }

    private function extractSignator($tracking)
    {
        $table1 = $tracking['table1'][0] ?? [];
        return trim($table1['nguoi_nhan'] ?? '');
    }

    private function extractTrackingTime($tracking)
    {
        $table1 = $tracking['table1'][0] ?? [];
        $date = trim(($table1['ngay_phat'] ?? '') . ' ' . ($table1['gio_phat'] ?? ''));
        if ($date === '') {
            $table = $tracking['table'] ?? [];
            if (!empty($table)) {
                $last = end($table);
                $date = trim(($last['ngay_den'] ?? '') . ' ' . ($last['gio'] ?? ''));
            }
        }

        if ($date === '') {
            return null;
        }

        foreach (['d/m/Y H:i:s', 'd/m/Y H:i', 'Y-m-d H:i:s', 'Y-m-d H:i'] as $format) {
            try {
                $parsed = Carbon::createFromFormat($format, $date);
                if ($parsed) {
                    return $parsed;
                }
            } catch (\Exception $e) {
                //
            }
        }

        return null;
    }

    private function normalizeStatus($status)
    {
        $status = function_exists('mb_strtolower') ? mb_strtolower($status, 'UTF-8') : strtolower($status);
        $status = str_replace('đ', 'd', $status);
        $status = str_replace('Đ', 'D', $status);
        $converted = function_exists('iconv') ? @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $status) : false;
        if ($converted !== false) {
            $status = $converted;
        }

        return trim(preg_replace('/\s+/', ' ', $status));
    }

    private function containsAny($haystack, array $needles)
    {
        foreach ($needles as $needle) {
            if (strpos($haystack, $needle) !== false) {
                return true;
            }
        }

        return false;
    }
}
