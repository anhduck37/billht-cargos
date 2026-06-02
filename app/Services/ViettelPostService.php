<?php

namespace App\Services;

use App\City;
use App\Models\Order;
use App\OrderPartnerLog;
use App\Partner;
use App\PartnerConfig;
use App\PartnerTracking;
use App\Service;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;

class ViettelPostService
{

    private $username;
    private $password;
    public $headers = [
        'Content-Type' => 'application/json'
    ];
    public $url;
    private $api;
    private $service_viettel;

    public function __construct($service_viettel = null)
    {
        $this->username = config('viettel_post.username');
        $this->password = config('viettel_post.password');
        $this->url = config('viettel_post.url');
        $this->api = config('viettel_post.api');
        $this->service_viettel = $service_viettel;
    }

    public function refreshToken()
    {
        $client = new Client([
            'headers' => $this->headers,
            'timeout' => 30
        ]);
        try {
            $response = $client->post(
                $this->url . '/v2/user/Login',
                [
                    'body' => json_encode([
                        'USERNAME' => $this->username,
                        'PASSWORD' => $this->password,
                    ]),
                ]
            );
            $loginResult = json_decode($response->getBody()->getContents(), true);

            if (empty($loginResult['data']['token'])) {
                return $loginResult;
            }

            $ownerResponse = $client->post(
                $this->url . '/v2/user/ownerconnect',
                [
                    'headers' => array_merge($this->headers, [
                        'Token' => $loginResult['data']['token'],
                    ]),
                    'body' => json_encode([
                        'USERNAME' => $this->username,
                        'PASSWORD' => $this->password,
                    ]),
                ]
            );
            $result = json_decode($ownerResponse->getBody()->getContents(), true);
        } catch (\Exception $e) {
            \Log::error('VTP API Error refreshToken: ' . $e->getMessage());
            $result = ['error' => true, 'message' => 'Lỗi kết nối Viettel Post: ' . $e->getMessage(), 'data' => []];
        }
        if (empty($result['data'])) {
            return $result;
        }
        $partnerConfig = PartnerConfig::where('partner_code', PartnerConfig::CODE_VIETTEL_POST)->first();
        if (!$partnerConfig) {
            $partnerConfig = new PartnerConfig();
            $partnerConfig->partner_code = PartnerConfig::CODE_VIETTEL_POST;
        }
        $partnerConfig->token = $result['data']['token'];
        $partnerConfig->save();
        return $result;
    }

    public function createOrder($order)
    {
        $path = '/v2/order/createOrder';
        $partnerConfig = PartnerConfig::where('partner_code', PartnerConfig::CODE_VIETTEL_POST)->first();

        $this->headers['Token'] = $partnerConfig->token ?? '';
        $senderAddress = $this->formatViettelStreetAddress($order->sender, $order->address_scheme ?? 'old');
        $receiverAddress = $this->formatViettelStreetAddress($order->receiver, $order->address_scheme ?? 'old');

        $senderAddressScheme = $order->sender->address_scheme ?? $order->address_scheme ?? 'old';
        $senderWard = $order->sender->ward->ward_code ?? 0;
        $senderDistrict = $order->sender->district->district_code ?? 0;
        $senderProvince = $order->sender->city->city_code ?? 0;

        if ($senderAddressScheme === 'new') {
            $senderWard = 0;
            $senderDistrict = 0;
            $senderProvince = 0;

            if (isset($order->sender->new_ward_id)) {
                $mapping = app(\App\Services\Address2025Service::class)->getPartnerMapping($order->sender->new_ward_id, 'VTP');
                if ($mapping) {
                    $senderWard = $mapping->partner_ward_code ?? $senderWard;
                    $senderDistrict = $mapping->partner_district_code ?? $senderDistrict;
                    $senderProvince = $mapping->partner_province_code ?? $senderProvince;
                }
            }
        }

        $receiverAddressScheme = $order->receiver->address_scheme ?? $order->address_scheme ?? 'old';
        $receiverWard = $order->receiver->ward->ward_code ?? 0;
        $receiverDistrict = $order->receiver->district->district_code ?? 0;
        $receiverProvince = $order->receiver->city->city_code ?? 0;
        
        if ($receiverAddressScheme === 'new') {
            $receiverWard = 0;
            $receiverDistrict = 0;
            $receiverProvince = 0;

            if (isset($order->receiver->new_ward_id)) {
                $mapping = app(\App\Services\Address2025Service::class)->getPartnerMapping($order->receiver->new_ward_id, 'VTP');
                if ($mapping) {
                    $receiverWard = $mapping->partner_ward_code ?? $receiverWard;
                    $receiverDistrict = $mapping->partner_district_code ?? $receiverDistrict;
                    $receiverProvince = $mapping->partner_province_code ?? $receiverProvince;
                }
            }
        }

        $senderAddress = $this->formatViettelPayloadAddress($order->sender, $senderAddressScheme, $senderProvince, $senderDistrict, $senderWard);
        $receiverAddress = $this->formatViettelPayloadAddress($order->receiver, $receiverAddressScheme, $receiverProvince, $receiverDistrict, $receiverWard);

        $orderPayment = 1;
        switch ($order->payment_method) {
            case Order::PAYMENT_METHOD_LAST:
                if ($order->collection > 0) {
                    $orderPayment = 3;
                }
                break;
            case Order::PAYMENT_METHOD_INTERNET_BANKING:
                $orderPayment = 4;
                break;
        }
        if ($order->collection > 0) $orderPayment = 3;
        $orderService = $this->getPriceAllNlp($order);
        $formatData = [
            "ORDER_NUMBER" => !empty($order->invoice_code) ? $order->invoice_code : $order->order_code,
            "SENDER_FULLNAME" => $order->sender->sender_name ?? '',
            "SENDER_ADDRESS" => $senderAddressScheme === 'new' ? trim($senderAddress) : ($this->getGroupId($senderAddress) ?? trim($senderAddress)),
            "SENDER_PHONE" => $order->sender->sender_phone ?? '',
            "SENDER_EMAIL" => $order->sender->sender_email ?? '',
            "SENDER_WARD" => $senderWard,
            "SENDER_DISTRICT" => $senderDistrict,
            "SENDER_PROVINCE" => $senderProvince,
            "RECEIVER_FULLNAME" => $order->receiver->receiver_name ?? 0,
            "RECEIVER_ADDRESS" => $receiverAddress,
            "RECEIVER_PHONE" => $order->receiver->receiver_phone,
            "RECEIVER_EMAIL" => $order->receiver->receiver_email,
            "RECEIVER_WARD" => $receiverWard,
            "RECEIVER_DISTRICT" => $receiverDistrict,
            "RECEIVER_PROVINCE" => $receiverProvince,
            "PRODUCT_NAME" => $order->note,
            "PRODUCT_QUANTITY" => $order->quantity,
            "PRODUCT_WEIGHT" => $order->weight,
            "PRODUCT_WIDTH" => $order->width,
            "PRODUCT_HEIGHT" => $order->height,
            "PRODUCT_LENGTH" => $order->long,
            "PRODUCT_PRICE" => $order->total,
            "PRODUCT_TYPE" => $order->type == Order::ORDER_TYPE_DOCUMENT ? "TH" : "HH",
            "ORDER_PAYMENT" => $orderPayment,
            "ORDER_SERVICE" => $this->service_viettel ?? $orderService,
            "ORDER_NOTE" => $order->note,
            "MONEY_COLLECTION" => $order->collection,
            "MONEY_TOTALFEE" => 0,
            "MONEY_FEECOD" => 0,
            "MONEY_FEEVAS" => 0,
            "MONEY_FEEINSURRANCE" => 0,
            "MONEY_FEE" => 0,
            "MONEY_FEEOTHER" => 0,
            "MONEY_TOTALVAT" => 0,
            "MONEY_TOTAL" => 0
        ];

        if ($senderAddressScheme !== 'new') {
            unset($formatData['SENDER_WARD'], $formatData['SENDER_DISTRICT'], $formatData['SENDER_PROVINCE']);
        }

        if ($senderAddressScheme === 'new' && (!$senderWard || !$senderDistrict || !$senderProvince)) {
            $result = [
                'error' => true,
                'message' => 'Địa chỉ người gửi dùng địa chỉ mới nhưng chưa có mapping Viettel Post đầy đủ cho xã/phường. Vui lòng chọn lại Xã/Phường hoặc bổ sung mapping VTP trước khi đồng bộ.',
                'data' => []
            ];

            $orderPartnerLog = new OrderPartnerLog();
            $orderPartnerLog->order_id = $order->id;
            $orderPartnerLog->status = OrderPartnerLog::STATUS_FAILD;
            $orderPartnerLog->partner_code = PartnerConfig::CODE_VIETTEL_POST;
            $orderPartnerLog->payload = json_encode($formatData, JSON_UNESCAPED_UNICODE);
            $orderPartnerLog->response = json_encode($result, JSON_UNESCAPED_UNICODE);
            $orderPartnerLog->user_id = auth()->user()->id ?? 0;
            $orderPartnerLog->save();

            return $result;
        }

        if ($receiverAddressScheme === 'new' && (!$receiverWard || !$receiverDistrict || !$receiverProvince)) {
            $result = [
                'error' => true,
                'message' => 'Địa chỉ người nhận dùng địa chỉ mới nhưng chưa có mapping Viettel Post đầy đủ cho xã/phường. Vui lòng chọn lại Xã/Phường hoặc bổ sung mapping VTP trước khi đồng bộ.',
                'data' => []
            ];

            $orderPartnerLog = new OrderPartnerLog();
            $orderPartnerLog->order_id = $order->id;
            $orderPartnerLog->status = OrderPartnerLog::STATUS_FAILD;
            $orderPartnerLog->partner_code = PartnerConfig::CODE_VIETTEL_POST;
            $orderPartnerLog->payload = json_encode($formatData, JSON_UNESCAPED_UNICODE);
            $orderPartnerLog->response = json_encode($result, JSON_UNESCAPED_UNICODE);
            $orderPartnerLog->user_id = auth()->user()->id ?? 0;
            $orderPartnerLog->save();

            return $result;
        }

        $client = new Client([
            'headers' => $this->headers,
            'timeout' => 30
        ]);
        
        try {
            $response = $client->post(
                $this->url . $path,
                [
                    'body' => json_encode($formatData)
                ]
            );

            $result = json_decode($response->getBody()->getContents(), true);
        } catch (\Exception $e) {
            \Log::error('VTP API Error createOrder: ' . $e->getMessage());
            $result = [
                'error' => true,
                'message' => 'Lỗi kết nối API Viettel Post: ' . $e->getMessage(),
                'data' => []
            ];
        }
        $orderPartnerLog = new OrderPartnerLog();
        $status = OrderPartnerLog::STATUS_FAILD;
        if (!empty($result['data'])) {
            $status = OrderPartnerLog::STATUS_SUCCESS;
            $order->order_partner_code = $result['data']['ORDER_NUMBER'] ?? null;
            $order->partner_code = Order::CODE_VIETTEL_POST;
            $order->save();
        }
        $orderPartnerLog->order_id = $order->id;
        $orderPartnerLog->status = $status;
        $orderPartnerLog->partner_code = PartnerConfig::CODE_VIETTEL_POST;
        $orderPartnerLog->payload = json_encode($formatData);
        $orderPartnerLog->response = json_encode($result);
        $orderPartnerLog->user_id = auth()->user()->id ?? 0;
        $orderPartnerLog->save();
        return $result;
    }

    public function cancelOrder($order, $note = null)
    {
        $path = '/v2/order/UpdateOrder';
        $partnerConfig = PartnerConfig::where('partner_code', PartnerConfig::CODE_VIETTEL_POST)->first();
        $this->headers['Token'] = $partnerConfig->token ?? '';

        $formatData = [
            'TYPE' => 4,
            'ORDER_NUMBER' => $order->order_partner_code,
            'NOTE' => $note ?: 'Huy don tu BillHT',
        ];

        $client = new Client([
            'headers' => $this->headers,
            'timeout' => 30,
        ]);

        try {
            $response = $client->post(
                $this->url . $path,
                [
                    'body' => json_encode($formatData, JSON_UNESCAPED_UNICODE),
                ]
            );
            $result = json_decode($response->getBody()->getContents(), true) ?: [];
            $result['http_status'] = $response->getStatusCode();
        } catch (\Exception $e) {
            \Log::error('VTP API Error cancelOrder: ' . $e->getMessage());
            $result = [
                'error' => true,
                'message' => 'Lỗi kết nối API Viettel Post: ' . $e->getMessage(),
                'data' => [],
            ];
        }

        $result = app(PartnerErrorMessageService::class)->normalizeResult(Order::CODE_VIETTEL_POST, $result, $formatData);

        $orderPartnerLog = new OrderPartnerLog();
        $orderPartnerLog->order_id = $order->id ?? 0;
        $orderPartnerLog->status = $this->isCancelSuccessful($result) ? OrderPartnerLog::STATUS_SUCCESS : OrderPartnerLog::STATUS_FAILD;
        $orderPartnerLog->partner_code = PartnerConfig::CODE_VIETTEL_POST;
        $orderPartnerLog->payload = json_encode($formatData, JSON_UNESCAPED_UNICODE);
        $orderPartnerLog->response = json_encode($result, JSON_UNESCAPED_UNICODE);
        $orderPartnerLog->user_id = auth()->user()->id ?? 0;
        $orderPartnerLog->save();

        return $result;
    }

    public function isCancelSuccessful($result)
    {
        if (!is_array($result) || !empty($result['error'])) {
            return false;
        }

        if (isset($result['status']) && is_numeric($result['status']) && (int)$result['status'] >= 400) {
            return false;
        }

        if (!empty($result['message'])) {
            $message = function_exists('mb_strtolower') ? mb_strtolower($result['message'], 'UTF-8') : strtolower($result['message']);
            if (
                strpos($message, 'không') !== false ||
                strpos($message, 'khong') !== false ||
                strpos($message, 'thất bại') !== false ||
                strpos($message, 'that bai') !== false ||
                strpos($message, 'fail') !== false ||
                strpos($message, 'error') !== false
            ) {
                return false;
            }
        }

        return isset($result['http_status']) && (int)$result['http_status'] >= 200 && (int)$result['http_status'] < 300;
    }

    public function webhookTracking($data)
    {
        app(LogFileService::class)->writeLog('viettel_post', json_encode($data));
        $dataWebhook = isset($data['DATA']['ORDER_NUMBER']) ? $data['DATA'] : null;
        if (!$dataWebhook) return;

        $order = Order::where('order_partner_code', $dataWebhook['ORDER_NUMBER'])->first();

        if (!$order) {
            return;
        }

        $dataTracking = $this->formatDataWebhook($order, $dataWebhook);
        PartnerTracking::create($dataTracking);
        if (isset(PartnerConfig::MAP_STATUS_VIETTEL_POST[$dataWebhook['ORDER_STATUS']])) {
            $order->delivery_status = PartnerConfig::MAP_STATUS_VIETTEL_POST[$dataWebhook['ORDER_STATUS']];
            
            $statusDesc = $dataWebhook['STATUS_NAME'] ?? $dataWebhook['ORDER_STATUS'] ?? 'Cập nhật trạng thái';
            $historyData = [
                'action_desc' => 'Webhook VTP: ' . $statusDesc,
            ];
            if (isset($dataWebhook['NOTE']) && !empty($dataWebhook['NOTE'])) {
                $historyData['message'] = $dataWebhook['NOTE'];
            }
            app(\App\Services\OrderHistoryService::class)->createOrderHistory(null, $order, null, \App\OrderHistory::NOT_TOTAL_ORDER, \App\OrderHistory::TYPE_ORDER_UPDATE, 'SYNC', $historyData, $dataWebhook['ORDER_NUMBER'], 'VIETTEL_POST');

            $order->save();
        }
        return;
    }

    public function tracking($order)
    {
        $path = '/api/setting/listOrderTrackingVTP3';
        $result = $this->requestTracking($path, $order);

        if ($this->isExpiredTokenResponse($result)) {
            $refreshResult = $this->refreshToken();
            if (!empty($refreshResult['data']['token'])) {
                $result = $this->requestTracking($path, $order);
            } else {
                \Log::warning('VTP tracking token refresh failed', [
                    'order_id' => $order->id ?? null,
                    'order_partner_code' => $order->order_partner_code ?? null,
                    'refresh_result' => $refreshResult,
                ]);
            }
        }

        return $result['data'] ?? null;
    }

    public function refreshTrackingForOrder($order)
    {
        $logOrderNumber = $this->latestSuccessfulOrderNumberFromLogs($order);
        if ($logOrderNumber && $logOrderNumber !== $order->order_partner_code) {
            $order->order_partner_code = $logOrderNumber;
            $order->partner_code = Order::CODE_VIETTEL_POST;
            $order->push_error = null;
            $order->save();
        }

        $tracking = $this->tracking($order);
        $items = $this->normalizeTrackingItems($tracking);

        if (empty($items)) {
            return null;
        }

        $orderPartnerCode = $this->extractTrackingOrderNumber($items[0]);
        if ($orderPartnerCode && $orderPartnerCode !== $order->order_partner_code) {
            $order->order_partner_code = $orderPartnerCode;
            $order->partner_code = Order::CODE_VIETTEL_POST;
            $order->push_error = null;
        }

        $currentItem = $this->latestTrackingItem($items);
        if (isset($currentItem['ORDER_STATUS']) && isset(PartnerConfig::MAP_STATUS_VIETTEL_POST[$currentItem['ORDER_STATUS']])) {
            $order->delivery_status = PartnerConfig::MAP_STATUS_VIETTEL_POST[$currentItem['ORDER_STATUS']];
        }
        $order->save();

        foreach ($items as $item) {
            $dataTracking = $this->formatDataWebhook($order, $item);
            if (empty($dataTracking['order_partner_code'])) {
                continue;
            }

            $exists = PartnerTracking::where('order_id', $dataTracking['order_id'])
                ->where('order_partner_code', $dataTracking['order_partner_code'])
                ->where('order_statusdate', $dataTracking['order_statusdate'])
                ->where('order_status', $dataTracking['order_status'])
                ->where('status_name', $dataTracking['status_name'])
                ->exists();

            if (!$exists) {
                PartnerTracking::create($dataTracking);
            }
        }

        $query = PartnerTracking::where('order_id', $order->id);
        if ($orderPartnerCode) {
            $query->where('order_partner_code', $orderPartnerCode);
        }

        return $query->orderBy('id', 'DESC')->get();
    }

    private function latestSuccessfulOrderNumberFromLogs($order)
    {
        if (!$order || !$order->id) {
            return null;
        }

        $logs = OrderPartnerLog::where('order_id', $order->id)
            ->whereIn('partner_code', [PartnerConfig::CODE_VIETTEL_POST, Order::CODE_VIETTEL_POST])
            ->orderBy('id', 'DESC')
            ->limit(30)
            ->get();

        foreach ($logs as $log) {
            $payload = json_decode($log->payload, true);
            if (isset($payload['TYPE']) && (int)$payload['TYPE'] === 4) {
                continue;
            }

            $response = json_decode($log->response, true);
            if (!$this->isSuccessfulCreateOrderLog($log, $response)) {
                continue;
            }

            $orderNumber = $this->findOrderNumberInArray($response);
            if ($orderNumber) {
                return $orderNumber;
            }
        }

        return null;
    }

    private function isSuccessfulCreateOrderLog($log, $response)
    {
        if ((int)$log->status === OrderPartnerLog::STATUS_SUCCESS) {
            return true;
        }

        if (!is_array($response)) {
            return false;
        }

        if (isset($response['error']) && $response['error']) {
            return false;
        }

        if (isset($response['status']) && is_numeric($response['status']) && (int)$response['status'] >= 400) {
            return false;
        }

        return !empty($this->findOrderNumberInArray($response));
    }

    private function findOrderNumberInArray($data)
    {
        if (!is_array($data)) {
            return null;
        }

        foreach (['ORDER_NUMBER', 'OrderNumber', 'order_number', 'MA_VAN_DON', 'tracking_code'] as $key) {
            if (!empty($data[$key]) && is_scalar($data[$key])) {
                return (string)$data[$key];
            }
        }

        foreach ($data as $value) {
            if (is_array($value)) {
                $orderNumber = $this->findOrderNumberInArray($value);
                if ($orderNumber) {
                    return $orderNumber;
                }
            }
        }

        return null;
    }

    private function requestTracking($path, $order)
    {
        $partnerConfig = PartnerConfig::where('partner_code', PartnerConfig::CODE_VIETTEL_POST)->first();
        $headers = $this->headers;
        $headers['Token'] = $partnerConfig->token ?? '';
        $headers['Accept'] = 'application/json';
        $orderNumbers = array_filter(array_unique([
            $order->order_code ?? null,
            $order->order_partner_code ?? null,
        ]));
        $lastResult = [];
        $fallbackResult = [];

        $client = new Client([
            'headers' => $headers,
            'timeout' => 30
        ]);

        foreach ($orderNumbers as $orderNumber) {
            try {
                $response = $client->get($this->api . $path, [
                    'query' => [
                        'OrderNumber' => $orderNumber
                    ]
                ]);

                $result = json_decode($response->getBody()->getContents(), true);
                $lastResult = is_array($result) ? $result : [];

                if ($this->isExpiredTokenResponse($lastResult)) {
                    return $lastResult;
                }

                if (!empty($lastResult['data'])) {
                    $activeResult = $this->selectActiveTrackingResult($lastResult);
                    if ($activeResult) {
                        return $activeResult;
                    }

                    if (empty($fallbackResult)) {
                        $fallbackResult = $lastResult;
                    }
                }
            } catch (\Exception $e) {
                \Log::error('VTP API Error tracking: ' . $e->getMessage());
            }
        }

        return !empty($fallbackResult) ? $fallbackResult : $lastResult;
    }

    private function selectActiveTrackingResult($result)
    {
        $items = $this->normalizeTrackingItems($result['data'] ?? null);
        if (empty($items)) {
            return null;
        }

        $groups = [];
        foreach ($items as $item) {
            $orderNumber = $this->extractTrackingOrderNumber($item);
            if (!$orderNumber) {
                $orderNumber = '_unknown';
            }
            $groups[$orderNumber][] = $item;
        }

        foreach ($groups as $orderNumber => $groupItems) {
            $currentItem = $this->latestTrackingItem($groupItems);
            if (!$this->isCancelledTrackingItem($currentItem)) {
                usort($groupItems, function ($a, $b) {
                    $timeA = strtotime($a['ORDER_STATUSDATE'] ?? $a['order_statusdate'] ?? '') ?: 0;
                    $timeB = strtotime($b['ORDER_STATUSDATE'] ?? $b['order_statusdate'] ?? '') ?: 0;
                    return $timeB <=> $timeA;
                });
                $result['data'] = $groupItems;
                $result['_selected_order_number'] = $orderNumber === '_unknown' ? null : $orderNumber;
                return $result;
            }
        }

        return null;
    }

    private function normalizeTrackingItems($data)
    {
        if (empty($data) || !is_array($data)) {
            return [];
        }

        if (isset($data['ORDER_NUMBER']) || isset($data['order_partner_code'])) {
            return [$data];
        }

        return array_values(array_filter($data, 'is_array'));
    }

    private function extractTrackingOrderNumber($item)
    {
        if (!is_array($item)) {
            return null;
        }

        return $item['ORDER_NUMBER'] ?? $item['order_partner_code'] ?? $item['OrderNumber'] ?? null;
    }

    private function latestTrackingItem($items)
    {
        $items = $this->normalizeTrackingItems($items);
        if (empty($items)) {
            return [];
        }

        usort($items, function ($a, $b) {
            $timeA = strtotime($a['ORDER_STATUSDATE'] ?? $a['order_statusdate'] ?? '') ?: 0;
            $timeB = strtotime($b['ORDER_STATUSDATE'] ?? $b['order_statusdate'] ?? '') ?: 0;
            return $timeB <=> $timeA;
        });

        return $items[0];
    }

    private function isCancelledTrackingItem($item)
    {
        if (!is_array($item)) {
            return false;
        }

        $statusCode = (string)($item['ORDER_STATUS'] ?? $item['order_status'] ?? '');
        if (in_array($statusCode, ['101', '107', '201', '503', '510'], true)) {
            return true;
        }

        $text = implode(' ', [
            $item['STATUS_NAME'] ?? '',
            $item['status_name'] ?? '',
            $item['NOTE'] ?? '',
            $item['note'] ?? '',
        ]);
        $text = function_exists('mb_strtolower') ? mb_strtolower($text, 'UTF-8') : strtolower($text);

        return strpos($text, 'hủy') !== false
            || strpos($text, 'huỷ') !== false
            || strpos($text, ' huy ') !== false
            || strpos($text, 'huy don') !== false
            || strpos($text, 'huy lay') !== false
            || strpos($text, 'cancel') !== false;
    }

    private function isExpiredTokenResponse($result)
    {
        if (!is_array($result)) {
            return false;
        }

        $messageKey = strtoupper((string)($result['messageKey'] ?? ''));
        $message = function_exists('mb_strtolower')
            ? mb_strtolower((string)($result['message'] ?? ''), 'UTF-8')
            : strtolower((string)($result['message'] ?? ''));

        return $messageKey === 'EXPIRED_TOKEN'
            || strpos($message, 'hết hạn') !== false
            || strpos($message, 'het han') !== false
            || strpos($message, 'expired') !== false;
    }

    private function formatDataWebhook($order, $data)
    {
        $mapData = [
            'order_id' => $order->id ?? 0,
            'order_partner_code' => $data['ORDER_NUMBER'] ?? null,
            'order_reference' => $data['ORDER_REFERENCE'] ?? null,
            'order_statusdate' => $data['ORDER_STATUSDATE'] ?? null,
            'order_status' => $data['ORDER_STATUS'] ?? null,
            'status_name' => $data['STATUS_NAME'] ?? null,
            'note' => $data['NOTE'] ?? null,
            'money_conllection' => $data['MONEY_COLLECTION'] ?? null,
            'money_feecod' => $data['MONEY_FEECOD'] ?? null,
            'money_total' => $data['MONEY_TOTAL'] ?? null,
            'expected_delivery' => $data['EXPECTED_DELIVERY'] ?? null,
            'product_weight' => $data['PRODUCT_WEIGHT'] ?? null,
            'order_service' => $data['ORDER_SERVICE'] ?? null,
            'location_currently' => $data['LOCALION_CURRENTLY'] ?? null,
            'money_totalfee' => $data['MONEY_TOTALFEE'] ?? null,
            'order_payment' => $data['ORDER_PAYMENT'] ?? null,
            'expected_delivery_date' => $data['EXPECTED_DELIVERY_DATE'] ?? null,
            'detail' => isset($data['DETAIL']) ? json_encode($data['DETAIL']) : null,
            'voucher_value' => $data['VOUCHER_VALUE'] ?? null,
            'money_collection_origin' => $data['MONEY_COLLECTION_ORIGIN'] ?? null,
            'employee_name' => $data['EMPLOYEE_NAME'] ?? null,
            'employee_phone' => $data['EMPLOYEE_PHONE'] ?? null,
            'is_returning' => $data['IS_RETURNING'] ?? null,
            'pod' => $data['POD'] ?? (is_array($data['POD']) ? json_encode($data['POD']) : $data['POD']),
            'receiver_fullname' => $data['RECEIVER_FULLNAME'] ?? null,
        ];
        return $mapData;
    }

    public function getPriceAllNlp($order, $orderService = true)
    {
        $path = '/v2/order/getPriceAllNlp';
        $senderAddress = $this->formatViettelStreetAddress($order->sender, $order->address_scheme ?? 'old');
        $receiverAddress = $this->formatViettelStreetAddress($order->receiver, $order->address_scheme ?? 'old');
        $formatData = [
            "SENDER_ADDRESS" => $senderAddress,
            "RECEIVER_ADDRESS" => $receiverAddress,
            "PRODUCT_TYPE" => $order->type == Order::ORDER_TYPE_DOCUMENT ? "TH" : "HH",
            "PRODUCT_WEIGHT" => $order->weight,
            "PRODUCT_WIDTH" => $order->width,
            "PRODUCT_HEIGHT" => $order->height,
            "PRODUCT_LENGTH" => $order->long,
            "PRODUCT_PRICE" => $order->total,
            "MONEY_COLLECTION" => $order->collection,
            "TYPE" => 1
        ];
        $partnerConfig = PartnerConfig::where('partner_code', PartnerConfig::CODE_VIETTEL_POST)->first();
        $this->headers['Token'] = $partnerConfig->token ?? '';
        $client = new Client([
            'headers' => $this->headers,
            'timeout' => 30
        ]);
        try {
            $response = $client->post(
                $this->url . $path,
                [
                    'body' => json_encode($formatData)
                ]
            );
            $result = json_decode($response->getBody()->getContents(), true);
        } catch (\Exception $e) {
            \Log::error('VTP API Error getPriceAllNlp: ' . $e->getMessage());
            $result = [];
        }
        return $orderService ? ($result['RESULT'][0]['MA_DV_CHINH'] ?? null) : $result;
    }

    public function getGroupId($address)
    {
        $replaceAddress = $this->stripVN($address);
        $address = strtoupper($replaceAddress);
        $groupId = null;
        $isBreak = false;
        foreach (City::MAP_CITY_VIETTEL_POST as $city) {
            foreach ($city['city'] as $item) {
                $replaceItem = $this->stripVN($item);
                if (str_contains($address, $replaceItem)) {
                    $groupId = $city['group_id'];
                    $isBreak = true;
                    break;
                }
            }
            if ($isBreak) {
                break;
            }
        }
        return $groupId;
    }

    private function formatViettelStreetAddress($addressModel, $fallbackScheme = 'old')
    {
        if (!$addressModel) {
            return '';
        }

        $addressScheme = $addressModel->address_scheme ?? $fallbackScheme;
        $streetAddress = trim((string)($addressModel->address ?? ''));

        if ($addressScheme === 'new') {
            return $this->stripViettelAdministrativeSuffix($streetAddress, $addressModel);
        }

        return trim($streetAddress . ' ' . ($addressModel->ward_name ?? '') . ' ' . ($addressModel->city_name ?? ''));
    }

    private function formatViettelPayloadAddress($addressModel, $addressScheme, $provinceCode, $districtCode, $wardCode)
    {
        $streetAddress = $this->formatViettelStreetAddress($addressModel, $addressScheme);

        if ($addressScheme !== 'new') {
            return $streetAddress;
        }

        $adminNames = $this->getViettelAdministrativeNamesByCodes($provinceCode, $districtCode, $wardCode);

        return trim(implode(', ', array_filter([
            $streetAddress,
            $adminNames['ward'] ?? null,
            $adminNames['district'] ?? null,
            $adminNames['city'] ?? null,
        ])));
    }

    private function getViettelAdministrativeNamesByCodes($provinceCode, $districtCode, $wardCode)
    {
        $city = City::where('city_code', $provinceCode)->first();

        $districtQuery = \App\District::where('district_code', $districtCode);
        if ($city) {
            $districtQuery->where('city_id', $city->id);
        }
        $district = $districtQuery->first();

        $wardQuery = \App\Ward::where('ward_code', $wardCode);
        if ($district) {
            $wardQuery->where('district_id', $district->id);
        }
        $ward = $wardQuery->first();

        return [
            'city' => $city->city_name ?? null,
            'district' => $district->district_name ?? null,
            'ward' => $ward->ward_name ?? null,
        ];
    }

    private function stripViettelAdministrativeSuffix($address, $addressModel)
    {
        $administrativeNames = array_filter([
            optional($addressModel->newWard)->name,
            optional($addressModel->newProvince)->name,
            optional($addressModel->ward)->ward_name,
            optional($addressModel->district)->district_name,
            optional($addressModel->city)->city_name,
            $addressModel->ward_name ?? null,
            $addressModel->district_name ?? null,
            $addressModel->city_name ?? null,
        ]);

        $address = trim((string)$address, " \t\n\r\0\x0B,");

        foreach ($administrativeNames as $name) {
            $address = $this->removeViettelAdministrativeText($address, $name);
        }

        $parts = array_map('trim', explode(',', trim($address, " \t\n\r\0\x0B,")));
        $parts = array_values(array_filter($parts, function ($part) {
            return $part !== '';
        }));

        while (count($parts) > 1) {
            $lastPart = $this->normalizeViettelAddressPart(end($parts));
            if ($lastPart !== '') {
                break;
            }
            array_pop($parts);
        }

        return trim(implode(', ', $parts));
    }

    private function removeViettelAdministrativeText($address, $name)
    {
        $name = trim((string)$name);
        if ($name === '') {
            return $address;
        }

        $variants = array_filter(array_unique([
            $name,
            preg_replace('/^(Tỉnh|Thành phố|TP|Quận|Huyện|Thị xã|Xã|Phường|Thị trấn)\s+/iu', '', $name),
        ]));

        foreach ($variants as $variant) {
            $address = preg_replace('/(?:^|[\s,]+)' . preg_quote($variant, '/') . '(?=$|[\s,]+)/iu', ' ', $address);
        }

        $address = preg_replace('/\s*,\s*,+/', ', ', $address);
        $address = preg_replace('/\s+/', ' ', $address);

        return trim($address, " \t\n\r\0\x0B,");
    }

    private function normalizeViettelAddressPart($value)
    {
        $value = strtolower($this->stripVN((string)$value));
        $value = preg_replace('/\b(tinh|thanh pho|tp|quan|huyen|thi xa|xa|phuong|thi tran)\b/u', '', $value);
        $value = preg_replace('/[^a-z0-9]+/', ' ', $value);

        return trim(preg_replace('/\s+/', ' ', $value));
    }

    public function stripVN($str)
    {
        $str = preg_replace("/(à|á|ạ|ả|ã|â|ầ|ấ|ậ|ẩ|ẫ|ă|ằ|ắ|ặ|ẳ|ẵ)/", 'a', $str);
        $str = preg_replace("/(è|é|ẹ|ẻ|ẽ|ê|ề|ế|ệ|ể|ễ)/", 'e', $str);
        $str = preg_replace("/(ì|í|ị|ỉ|ĩ)/", 'i', $str);
        $str = preg_replace("/(ò|ó|ọ|ỏ|õ|ô|ồ|ố|ộ|ổ|ỗ|ơ|ờ|ớ|ợ|ở|ỡ)/", 'o', $str);
        $str = preg_replace("/(ù|ú|ụ|ủ|ũ|ư|ừ|ứ|ự|ử|ữ)/", 'u', $str);
        $str = preg_replace("/(ỳ|ý|ỵ|ỷ|ỹ)/", 'y', $str);
        $str = preg_replace("/(đ)/", 'd', $str);

        $str = preg_replace("/(À|Á|Ạ|Ả|Ã|Â|Ầ|Ấ|Ậ|Ẩ|Ẫ|Ă|Ằ|Ắ|Ặ|Ẳ|Ẵ)/", 'A', $str);
        $str = preg_replace("/(È|É|Ẹ|Ẻ|Ẽ|Ê|Ề|Ế|Ệ|Ể|Ễ)/", 'E', $str);
        $str = preg_replace("/(Ì|Í|Ị|Ỉ|Ĩ)/", 'I', $str);
        $str = preg_replace("/(Ò|Ó|Ọ|Ỏ|Õ|Ô|Ồ|Ố|Ộ|Ổ|Ỗ|Ơ|Ờ|Ớ|Ợ|Ở|Ỡ)/", 'O', $str);
        $str = preg_replace("/(Ù|Ú|Ụ|Ủ|Ũ|Ư|Ừ|Ứ|Ự|Ử|Ữ)/", 'U', $str);
        $str = preg_replace("/(Ỳ|Ý|Ỵ|Ỷ|Ỹ)/", 'Y', $str);
        $str = preg_replace("/(Đ)/", 'D', $str);
        $str = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $str);
        $str = preg_replace('/[^\x20-\x7E]/', '', $str);
        return $str;
    }
}
