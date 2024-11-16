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
    private $api_key;
    private $service_viettel;
    public $params;

    const STATUS_SUCCESS = 'success';
    const STATUS_ERROR = 'error';

    public function __construct()
    {
        $this->url = config('ems.url');
        $this->api_key = config('ems.api_key');
        $this->params['merchant_token'] = $this->api_key;
    }

    public function createOrder($order)
    {
        $path = '/api/v1/orders/create-v2?merchant_token=' . $this->api_key;
        $client = new Client([
            'headers' => $this->headers
        ]);
        $formatData = $this->formatDataBody($order);

        $response = $client->post(
            $this->url . $path,
            [
                'body' => json_encode($formatData)
            ]
        );

        $result = json_decode($response->getBody()->getContents(), true);
        $orderPartnerLog = new OrderPartnerLog();
        $status = OrderPartnerLog::STATUS_FAILD;
        if ($result['code'] == self::STATUS_SUCCESS) {
            $status = OrderPartnerLog::STATUS_SUCCESS;
            $order->order_partner_code = $result['data']['tracking_code'] ?? null;
            $order->partner_code = Order::CODE_EMS;
            $order->save();
        }
        $orderPartnerLog->order_id = $order->id;
        $orderPartnerLog->status = $status;
        $orderPartnerLog->partner_code = PartnerConfig::CODE_EMS;
        $orderPartnerLog->payload = json_encode($formatData);
        $orderPartnerLog->response = json_encode($result);
        $orderPartnerLog->user_id = auth()->user()->id ?? 0;
        $orderPartnerLog->save();
        return $result;
    }

    public function formatDataBody($order)
    {
        $senderAddress = ($order->sender->address ?? '') . ' ' . ($order->sender->ward->ward_name ?? '') . ' ' . ($order->sender->city->city_name ?? '');
        $receiverAddress = ($order->receiver->address ?? '') . ' ' . ($order->receiver->ward->ward_name ?? '') . ' ' . ($order->receiver->city->city_name ?? '');
        $data = [
            "order_code" => !empty($order->invoice_code) ? $order->invoice_code : $order->order_code,
            "inventory_name" => $this->getGroupId($order->address ?? $senderAddress),
            "from_name" => $order->sender->sender_name ?? '',
            "from_phone" => $order->sender->sender_phone ?? '',
            // "from_province" => $order->sender->city->ems_code ?? 0,
            // "from_district" => $order->sender->district->ems_code ?? 0,
            // "from_ward" => $order->sender->district->ems_code ?? 0,
            "from_address" => $senderAddress,
            "to_name" => $order->receiver->receiver_name ?? 0,
            "to_phone" => $order->receiver->receiver_phone,
            "to_province" => $order->receiver->city->ems_code ?? 0,
            "to_district" => $order->receiver->district->ems_code ?? 0,
            "to_ward" => $order->receiver->ward->ems_code ?? 0,
            // "to_province" => 17,
            // "to_district" => 1754,
            // "to_ward" => 17542,
            "to_address" => $receiverAddress,
            "product_name" => Order::MAP_ORDER_TYPE[$order->type] ?? $order->note,
            "total_amount" => 0,
            "total_quantity" => $order->quantity,
            "total_weight" => $order->weight,
            "description" => $order->note,
            "size" => ($order->width ?? 0) . 'x' . ($order->height ?? 0) . 'x' . ($order->long ?? 0),
            "service" => 1
        ];
        return $data;
    }

    public function createWebhook($link)
    {
        $path = '/api/v1/metadata/webhook?merchant_token=' . $this->api_key;
        $this->headers['Accept'] = 'application/javascript';

        $client = new Client([
            'headers' => $this->headers
        ]);

        $response = $client->post(
            $this->url . $path,
            [
                'body' => json_encode([
                    'link' => $link
                ])
            ]
        );

        $result = json_decode($response->getBody()->getContents(), true);
        return $result;
    }

    public function updateWebhook($link, $status = 1)
    {
        $path = '/api/v1/metadata/webhook';
        $this->headers['Accept'] = 'application/javascript';

        $client = new Client([
            'headers' => $this->headers
        ]);

        $response = $client->put(
            $this->url . $path,
            [
                'query' => [
                    'link' => $link,
                    'merchant_token' => $this->api_key,
                    'status' => $status
                ]
            ]
        );

        $result = json_decode($response->getBody()->getContents(), true);
        return $result;
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
        // if (isset(PartnerConfig::MAP_STATUS_VIETTEL_POST[$dataWebhook['ORDER_STATUS']])) {
        //     $order->delivery_status = PartnerConfig::MAP_STATUS_VIETTEL_POST[$dataWebhook['ORDER_STATUS']];
        //     $order->save();
        // }
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

    public function getCities()
    {
        $path = '/api/v1/address/province';
        $client = new Client([
            'headers' => $this->headers
        ]);
        $response = $client->get(
            $this->url . $path,
            [
                'query' => $this->params
            ]
        );
        $result = json_decode($response->getBody()->getContents(), true);
        return $result['data'] ?? [];
    }

    public function getDistrict()
    {
        $path = '/api/v1/address/district';
        $client = new Client([
            'headers' => $this->headers
        ]);
        $response = $client->get(
            $this->url . $path,
            [
                'query' => $this->params
            ]
        );
        $result = json_decode($response->getBody()->getContents(), true);
        return $result['data'] ?? [];
    }

    public function getWard()
    {
        $path = '/api/v1/address/ward';
        $client = new Client([
            'headers' => $this->headers
        ]);
        $response = $client->get(
            $this->url . $path,
            [
                'query' => $this->params
            ]
        );
        $result = json_decode($response->getBody()->getContents(), true);
        return $result['data'] ?? [];
    }
}
