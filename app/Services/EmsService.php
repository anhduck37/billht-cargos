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

class EmsService
{

    public $headers = [
        'Content-Type' => 'application/json'
    ];
    public $url;
    private $access_key;
    private $secret_key;

    const STATUS_SUCCESS = '00';
    const STATUS_ERROR = '01';

    public function __construct()
    {
        $this->url = config('ems.url', 'http://uat.emsone.com.vn/Execute');
        $this->access_key = config('ems.access_key');
        $this->secret_key = config('ems.secret_key');
        
        $this->headers['Authorization'] = 'Bearer ' . $this->access_key;
    }

    public function executeRequest($code, $dataPayload)
    {
        $startedAt = microtime(true);
        $dataJson = json_encode($dataPayload, JSON_UNESCAPED_UNICODE);
        
        $signatureString = $code . $dataJson . $this->secret_key;
        $signature = hash('sha256', $signatureString);

        $body = [
            'Code' => $code,
            'Data' => $dataJson,
            'Signature' => $signature
        ];

        $client = new Client([
            'timeout' => 30, // Tăng thêm timeout
        ]);

        try {
            $response = $client->post(
                $this->url,
                [
                    'headers' => $this->headers,
                    'body' => json_encode($body, JSON_UNESCAPED_UNICODE),
                    'connect_timeout' => 10,
                    'read_timeout' => 30,
                ]
            );

            $rawBody = $response->getBody()->getContents();
            $result = json_decode($rawBody, true);
            if (!is_array($result)) {
                $result = [
                    'code' => self::STATUS_ERROR,
                    'message' => 'EMS trả về dữ liệu không hợp lệ',
                    'raw_response' => $rawBody,
                ];
            }
            // Chuẩn hóa kết quả trả về
            if (isset($result['Code'])) {
                $result['code'] = $result['Code'];
            }
            if (isset($result['Data'])) {
                $result['data'] = $result['Data'];
            }
            if (isset($result['Message'])) {
                $result['message'] = $result['Message'];
            }
            
            // Xử lý json decode cái Data string nếu nó hợp lệ
            if (isset($result['data']) && is_string($result['data'])) {
                $decodedData = json_decode($result['data'], true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $result['data'] = $decodedData;
                }
            }

            $result['_meta'] = [
                'endpoint' => $this->url,
                'http_status' => $response->getStatusCode(),
                'duration_ms' => (int)round((microtime(true) - $startedAt) * 1000),
                'request_code' => $code,
            ];

            \Log::info('EMS API Response', [
                'request_code' => $code,
                'http_status' => $response->getStatusCode(),
                'duration_ms' => $result['_meta']['duration_ms'],
                'ems_code' => $result['code'] ?? null,
                'ems_message' => $result['message'] ?? ($result['Message'] ?? null),
            ]);

            return $result;

        } catch (\Exception $e) {
            $durationMs = (int)round((microtime(true) - $startedAt) * 1000);
            \Log::error('EMS API Error: ' . $e->getMessage(), [
                'request_code' => $code,
                'endpoint' => $this->url,
                'duration_ms' => $durationMs,
            ]);
            return [
                'code' => self::STATUS_ERROR,
                'message' => 'Lỗi kết nối API EMS: ' . $e->getMessage(),
                '_meta' => [
                    'endpoint' => $this->url,
                    'http_status' => null,
                    'duration_ms' => $durationMs,
                    'request_code' => $code,
                    'exception' => get_class($e),
                ],
            ];
        }
    }

    public function createOrder($order)
    {
        $mappingError = $this->validateNewAddressEmsMapping($order);
        $formatData = $this->formatDataBody($order);

        if ($mappingError) {
            $result = [
                'code' => self::STATUS_ERROR,
                'message' => $mappingError,
            ];
        } else {
            $result = $this->executeRequest("PARTNER_ORDER_ADD", $formatData);
            // Log the request for debugging
            \Log::debug('EMS API Request', [
                'order_id' => $order->id,
                'order_code' => $formatData['OrderCode'] ?? 'N/A',
                'request' => 'PARTNER_ORDER_ADD',
                'data' => $formatData
            ]);
        }

        $result = app(PartnerErrorMessageService::class)->normalizeResult(Order::CODE_EMS, $result, $formatData);

        $orderPartnerLog = new OrderPartnerLog();
        $statusLog = OrderPartnerLog::STATUS_FAILD;
        
        if (isset($result['code']) && $result['code'] === self::STATUS_SUCCESS) {
            $statusLog = OrderPartnerLog::STATUS_SUCCESS;
            $order->order_partner_code = $result['data']['ShippingCode'] ?? ($result['data']['EMSOneCode'] ?? null);
            $order->partner_code = Order::CODE_EMS;
            $order->push_error = null; // Clear any previous errors
            $order->save();
            
            \Log::info('EMS Order Pushed Successfully', [
                'order_id' => $order->id,
                'shipping_code' => $order->order_partner_code
            ]);
        } else {
            // Log failed push with detailed error info
            $errorMsg = $result['message'] ?? 'Push EMS thất bại (không có chi tiết lỗi)';
            if (!empty($result['data']) && is_array($result['data'])) {
                $errorItems = [];
                foreach ($result['data'] as $item) {
                    if (is_array($item) && isset($item['Parameter']) && isset($item['Message'])) {
                        $errorItems[] = $item['Parameter'] . ': ' . $item['Message'];
                    }
                }
                if (!empty($errorItems)) {
                    $errorMsg = implode('; ', $errorItems);
                }
            }
            
            \Log::warning('EMS Order Push Failed', [
                'order_id' => $order->id,
                'error' => $errorMsg,
                'full_response' => $result
            ]);
        }
        
        $orderPartnerLog->order_id = $order->id ?? 0;
        $orderPartnerLog->status = $statusLog;
        $orderPartnerLog->partner_code = PartnerConfig::CODE_EMS;
        $orderPartnerLog->payload = json_encode([
            'Code' => 'PARTNER_ORDER_ADD',
            'Data' => json_encode($formatData, JSON_UNESCAPED_UNICODE)
        ], JSON_UNESCAPED_UNICODE);
        
        $orderPartnerLog->response = json_encode($result, JSON_UNESCAPED_UNICODE);
        $orderPartnerLog->user_id = auth()->user()->id ?? 0;
        $orderPartnerLog->save();
        
        return $result;
    }

    private function validateNewAddressEmsMapping($order)
    {
        foreach (['sender' => 'người gửi', 'receiver' => 'người nhận'] as $relation => $label) {
            $addressModel = $order->{$relation} ?? null;
            $addressScheme = $addressModel->address_scheme ?? $order->address_scheme ?? 'old';
            if (!$addressModel || $addressScheme !== 'new') {
                continue;
            }

            if (empty($addressModel->new_ward_id)) {
                return "Địa chỉ {$label} dùng địa chỉ mới nhưng chưa có Xã/Phường mới.";
            }

            $mapping = app(\App\Services\Address2025Service::class)->getPartnerMapping($addressModel->new_ward_id, 'EMS');
            if (!$mapping || empty($mapping->partner_province_code)) {
                return "Địa chỉ {$label} dùng địa chỉ mới nhưng chưa có mapping EMS cho Tỉnh/Thành phố. Vui lòng bổ sung mapping EMS trước khi đồng bộ.";
            }
        }

        return null;
    }

    public function cancelOrder($order, $reasonCancel = null)
    {
        $formatData = [
            'CrmOrPaypostCode' => config('ems.crm_code'),
            'ShippingCode' => $order->order_partner_code,
            'ReasonCancel' => $reasonCancel ?: 'Huy don tu BillHT',
        ];

        $result = $this->executeRequest('PARTNER_ORDER_CANCEL', $formatData);
        $statusLog = OrderPartnerLog::STATUS_FAILD;

        if (isset($result['code']) && $result['code'] === self::STATUS_SUCCESS) {
            $statusLog = OrderPartnerLog::STATUS_SUCCESS;
        }

        $orderPartnerLog = new OrderPartnerLog();
        $orderPartnerLog->order_id = $order->id ?? 0;
        $orderPartnerLog->status = $statusLog;
        $orderPartnerLog->partner_code = PartnerConfig::CODE_EMS;
        $orderPartnerLog->payload = json_encode([
            'Code' => 'PARTNER_ORDER_CANCEL',
            'Data' => json_encode($formatData, JSON_UNESCAPED_UNICODE),
        ], JSON_UNESCAPED_UNICODE);
        $orderPartnerLog->response = json_encode($result, JSON_UNESCAPED_UNICODE);
        $orderPartnerLog->user_id = auth()->user()->id ?? 0;
        $orderPartnerLog->save();

        return $result;
    }

    public function formatDataBody($order)
    {
        $senderAddress = $this->formatStreetAddress($order->sender);
        $receiverAddress = $this->formatStreetAddress($order->receiver);
        
        $receiverProvinceID = (int)($order->receiver->city->ems_code ?? 0);
        $receiverDistrictID = (int)($order->receiver->district->ems_code ?? 0);
        $receiverWardID = (int)($order->receiver->ward->ems_code ?? 0);

        $receiverAddressScheme = $order->receiver->address_scheme ?? $order->address_scheme ?? 'old';
        if ($receiverAddressScheme === 'new') {
            $receiverProvinceID = 0;
            $receiverDistrictID = 0;
            $receiverWardID = 0;

            if (isset($order->receiver->new_ward_id)) {
                $mapping = app(\App\Services\Address2025Service::class)->getPartnerMapping($order->receiver->new_ward_id, 'EMS');
                if ($mapping) {
                    $receiverProvinceID = (int)($mapping->partner_province_code ?? $receiverProvinceID);
                    $receiverDistrictID = 0;
                    $receiverWardID = 0;
                }
            }
        }

        $senderProvinceID = (int)($order->sender->city->ems_code ?? 0);
        $senderDistrictID = (int)($order->sender->district->ems_code ?? 0);
        $senderWardID = (int)($order->sender->ward->ems_code ?? 0);

        $senderAddressScheme = $order->sender->address_scheme ?? $order->address_scheme ?? 'old';
        if ($senderAddressScheme === 'new') {
            $senderProvinceID = 0;
            $senderDistrictID = 0;
            $senderWardID = 0;

            if (isset($order->sender->new_ward_id)) {
                $mapping = app(\App\Services\Address2025Service::class)->getPartnerMapping($order->sender->new_ward_id, 'EMS');
                if ($mapping) {
                    $senderProvinceID = (int)($mapping->partner_province_code ?? $senderProvinceID);
                    $senderDistrictID = (int)($mapping->partner_district_code ?? $senderDistrictID);
                    $senderWardID = (int)($mapping->partner_ward_code ?? $senderWardID);
                }
            }
        }

        // Determine OrderCode - prioritize invoice_code, fallback to order_code with prefix
        $orderCode = !empty($order->invoice_code) ? trim($order->invoice_code) : trim($order->order_code ?? '');
        if (empty($orderCode)) {
            $orderCode = 'HE' . ($order->id ?? date('YmdHis'));
        }
        
        // Determine OrderName - use TYPE map or description
        $orderName = isset($order->type) && isset(Order::MAP_ORDER_TYPE[$order->type]) 
            ? Order::MAP_ORDER_TYPE[$order->type] 
            : (trim($order->note ?? '') ?: 'Hàng hóa');

        // Validate required sender/receiver information
        $receiverName = trim($order->receiver->receiver_name ?? '');
        if (empty($receiverName)) {
            $receiverName = 'Người nhận';
        }
        
        $receiverPhone = trim($order->receiver->receiver_phone ?? '');
        if (empty($receiverPhone)) {
            $receiverPhone = '0000000000';
        }
        
        $senderName = trim($order->sender->sender_name ?? '');
        if (empty($senderName)) {
            $senderName = 'Người gửi';
        }
        
        $senderPhone = trim($order->sender->sender_phone ?? '');
        if (empty($senderPhone)) {
            $senderPhone = '0000000000';
        }

        $data = [
            "CrmOrPaypostCode" => config('ems.crm_code', ''),
            "CustomerToken" => config('ems.crm_code', ''),
            "OrderCode" => $orderCode,
            "OrderName" => $orderName,
            "OrderValue" => $order->total ?? 0,
            "OrderQuantity" => $order->quantity ?? 1,
            "Note" => trim($order->note ?? ''),
            "Message" => trim($order->note ?? ''),
            "Channel" => "WEB",
            "IsTransport" => "Y",
            "IsSendTransport" => "Y",
            "WareHouseID" => 0,
            
            "BuyerInfo" => [
                "FullName" => $receiverName,
                "MobileNumber" => $receiverPhone,
                "ProvinceID" => $receiverProvinceID,
                "DistrictID" => $receiverDistrictID,
                "WardID" => $receiverWardID,
                "Street" => $receiverAddress,
                "IsUpdate" => "N"
            ],
            
            "SenderInfo" => [
                "FullName" => $senderName,
                "MobileNumber" => $senderPhone,
                "ProvinceID" => $senderProvinceID,
                "DistrictID" => $senderDistrictID,
                "WardID" => $senderWardID,
                "Street" => $senderAddress
            ],
            
            "ReceiverInfo" => [
                "FullName" => $receiverName,
                "MobileNumber" => $receiverPhone,
                "ProvinceID" => $receiverProvinceID,
                "DistrictID" => $receiverDistrictID,
                "WardID" => $receiverWardID,
                "Street" => $receiverAddress
            ],
            
            "TransportInfo" => [
                "TransportMainServiceID" => 21, // Mặc định CPN TMĐT Nhanh
                "TransportExtraServiceID" => "",
                "CollectionType" => 1, // Thu gom tận nơi = 1, Gửi tại bưu cục = 2
                "TotalCOD" => $order->collection ?? 0,
                "TransportPayer" => 1 // 1: Shop trả, 2: Người nhận trả
            ]
        ];
        
        // Validation size/weight
        $weight = (int)($order->weight ?? 0);
        if ($weight > 0) {
            if ($weight < 100) $weight = 100; // EMS API crashes with Error 99 if weight is too small
            $data["TransportInfo"]["TransportWeight"] = $weight;
        }
        
        $length = (int)($order->long ?? 0);
        if ($length > 0) $data["TransportInfo"]["TransportSizeLength"] = $length;
        
        $width = (int)($order->width ?? 0);
        if ($width > 0) $data["TransportInfo"]["TransportSizeWidth"] = $width;
        
        $height = (int)($order->height ?? 0);
        if ($height > 0) $data["TransportInfo"]["TransportSizeHeight"] = $height;

        return $data;
    }

    private function formatStreetAddress($addressModel)
    {
        if (!$addressModel) {
            return '';
        }

        $detailAddress = trim((string)($addressModel->address ?? ''));
        $addressScheme = $addressModel->address_scheme ?? 'old';

        if ($addressScheme === 'new') {
            return trim(implode(', ', array_filter([
                $detailAddress,
                $addressModel->ward_name ?? '',
                $addressModel->city_name ?? '',
            ])));
        }

        if (!empty($addressModel->city_id) && !empty($addressModel->district_id) && !empty($addressModel->ward_id)) {
            return $detailAddress;
        }

        return trim($detailAddress . ' ' . ($addressModel->ward_name ?? '') . ' ' . ($addressModel->district_name ?? '') . ' ' . ($addressModel->city_name ?? ''));
    }

    public function webhookTracking($data)
    {
        app(LogFileService::class)->writeLog('ems', json_encode($data));
        if (!$data) return;

        $order = Order::where('order_partner_code', $data['tracking_code'])->first();

        if (!$order) {
            return;
        }

        $dataTracking = $this->formatDataWebhook($order, $data);
        PartnerTracking::create($dataTracking);
        if (isset(PartnerConfig::MAP_STATUS_EMS[$data['status_code']])) {
            $order->delivery_status = PartnerConfig::MAP_STATUS_EMS[$data['status_code']];
        } else {
            // Nếu mã trạng thái không có trong MAP, dựa vào từ khóa trong status_name
            $statusName = mb_strtolower($data['status_name'] ?? '', 'UTF-8');
            
            if (strpos($statusName, 'phát thành công') !== false || strpos($statusName, 'giao thành công') !== false) {
                $order->delivery_status = Order::DELIVERY_STATUS_OK;
            } elseif (strpos($statusName, 'đang phát') !== false || strpos($statusName, 'đang vận chuyển') !== false) {
                $order->delivery_status = Order::DELIVERY_STATUS_PERSON_CHARGE;
            } elseif (strpos($statusName, 'chấp nhận') !== false || strpos($statusName, 'nhập bưu cục') !== false) {
                $order->delivery_status = Order::DELIVERY_STATUS_PROCESSING;
            } elseif (strpos($statusName, 'hoàn') !== false || strpos($statusName, 'hủy') !== false) {
                $order->delivery_status = Order::DELIVERY_STATUS_RETURN;
            }
        }
        
        $statusDesc = $data['status_name'] ?? $data['status_code'] ?? 'Cập nhật trạng thái';
        $historyData = [
            'action_desc' => 'Webhook EMS: ' . $statusDesc,
        ];
        if (isset($data['note']) && !empty($data['note'])) {
            $historyData['message'] = $data['note'];
        }
        app(\App\Services\OrderHistoryService::class)->createOrderHistory(null, $order, null, \App\OrderHistory::NOT_TOTAL_ORDER, \App\OrderHistory::TYPE_ORDER_UPDATE, 'SYNC', $historyData, $data['tracking_code'], 'EMS');

        $order->save();
        return;
    }

    private function formatDataWebhook($order, $data)
    {
        $mapData = [
            'order_id' => $order->id ?? 0,
            'order_partner_code' => $data['tracking_code'] ?? null,
            'order_statusdate' => $data['datetime'] ?? null,
            'order_status' => $data['status_code'] ?? null,
            'status_name' => $data['status_name'] ?? null,
            'note' => $data['note'] ?? null,
            'money_conllection' => $data['money_collect'] ?? null,
            'money_feecod' => $data['main_fee'] ?? null,
            'product_weight' => $data['total_weight'] ?? null,
            'location_currently' => $data['locate'] ?? null,
            'money_totalfee' => $data['total_fee'] ?? null,
        ];
        return $mapData;
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
