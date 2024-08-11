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

class ViettelPostService {

    private $username;
    private $password;
    public $headers = [
        'Content-Type' => 'application/json'
    ];
    public $url;
    private $api;
    private $service_viettel;

    public function __construct($service_viettel=null) {
        $this->username = config('viettel_post.username');
        $this->password = config('viettel_post.password');
        $this->url = config('viettel_post.url');
        $this->api = config('viettel_post.api');
        $this->service_viettel = $service_viettel;
    }
    
    public function refreshToken() {
        $path = '/v2/user/Login';
        $client = new Client([
            'headers' => $this->headers
        ]);
        $response = $client->post(
            $this->url . $path,
            [
                'body' => json_encode([
                    'USERNAME' => $this->username,
                    'PASSWORD' => $this->password,
                ]),
            ]
        );
        $result = json_decode($response->getBody()->getContents(), true);
        if(empty($result['data'])) {
            return $result;
        }
        $partnerConfig = PartnerConfig::where('partner_code', PartnerConfig::CODE_VIETTEL_POST)->first();
        if(!$partnerConfig) {
            $partnerConfig = new PartnerConfig();
            $partnerConfig->partner_code = PartnerConfig::CODE_VIETTEL_POST;
        }
        $partnerConfig->token = $result['data']['token'];
        $partnerConfig->save();
        return $result;
    }

    public function createOrder($order) {
        $path = '/v2/order/createOrder';
        $partnerConfig = PartnerConfig::where('partner_code', PartnerConfig::CODE_VIETTEL_POST)->first();
        
        $this->headers['Token'] = $partnerConfig->token ?? '';
        $senderAddress = ($order->sender->address ?? '') . ' ' .($order->sender->ward->ward_name ?? '') . ' ' . ($order->sender->city->city_name ?? '');
        $receiverAddress = ($order->receiver->address ?? '') . ' ' .($order->receiver->ward->ward_name ?? '') . ' ' . ($order->receiver->city->city_name ?? '');
        $orderPayment = 1;
        switch($order->payment_method) {
            case Order::PAYMENT_METHOD_LAST:
                if($order->collection > 0) {
                    $orderPayment = 3;
                }
                break;
            case Order::PAYMENT_METHOD_INTERNET_BANKING:
                $orderPayment = 4;
                break;
        }
        if($order->collection > 0) $orderPayment = 3;
        $orderService = $this->getPriceAllNlp($order);
        $formatData = [
            "ORDER_NUMBER" => !empty($order->invoice_code) ? $order->invoice_code : $order->order_code,
            "SENDER_FULLNAME" => $order->sender->sender_name ?? '',
            "SENDER_ADDRESS" => $this->getGroupId($senderAddress) ??  trim($senderAddress),
            "SENDER_PHONE" => $order->sender->sender_phone ?? '',
            "SENDER_EMAIL" => $order->sender->sender_email ?? '',
            // "SENDER_WARD" => $order->sender->ward->ward_code ?? 0,
            // "SENDER_DISTRICT" => $order->sender->district->district_code ?? 0,
            // "SENDER_PROVINCE" => $order->sender->city->city_code ?? 0,
            "RECEIVER_FULLNAME" => $order->receiver->receiver_name ?? 0,
            "RECEIVER_ADDRESS" => $receiverAddress,
            "RECEIVER_PHONE" => $order->receiver->receiver_phone,
            "RECEIVER_EMAIL" => $order->receiver->receiver_email,
            "RECEIVER_WARD" => $order->receiver->ward->ward_code ?? 0,
            "RECEIVER_DISTRICT" => $order->receiver->district->district_code ?? 0,
            "RECEIVER_PROVINCE" => $order->receiver->city->city_code ?? 0,
            "PRODUCT_NAME" => $order->note,
            "PRODUCT_QUANTITY" => $order->quantity,
            "PRODUCT_WEIGHT" =>$order->weight,
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
        $client = new Client([
            'headers' => $this->headers
        ]);
        $response = $client->post(
            $this->url . $path,
            [
                'body' => json_encode($formatData)
            ]
        );

        $result = json_decode($response->getBody()->getContents(), true);
        $orderPartnerLog = new OrderPartnerLog();
        $status = OrderPartnerLog::STATUS_FAILD;
        if(!empty($result['data'])) {
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

    public function webhookTracking($data) {
        app(LogFileService::class)->writeLog('viettel_post', json_encode($data));
        $dataWebhook = isset($data['DATA']['ORDER_NUMBER']) ? $data['DATA'] : null;
        if(!$dataWebhook) return;

        $order = Order::where('order_partner_code', $dataWebhook['ORDER_NUMBER'])->first();
        if(!$order) {
            return;
        }

        if(isset(PartnerConfig::MAP_STATUS_VIETTEL_POST[$dataWebhook['ORDER_STATUS']])) {
            $order->delivery_status = PartnerConfig::MAP_STATUS_VIETTEL_POST[$dataWebhook['ORDER_STATUS']];
            $order->save();
        }
        return;
    }

    public function tracking($order) {
        $path = '/api/setting/listOrderTrackingVTP3';
        $partnerConfig = PartnerConfig::where('partner_code', PartnerConfig::CODE_VIETTEL_POST)->first();
        $this->headers['token'] = $partnerConfig->token ?? '';
        $client = new Client([
            'headers' => $this->headers
        ]);

        $response = $client->get($this->api.$path, [
            'query' => [
                'OrderNumber' => $order->order_partner_code
            ]
        ]);
        $result = json_decode($response->getBody()->getContents(), true);
        return $result['data'] ?? null;
    }

    private function formatDataWebhook($order, $data) {
        $mapData = [
            'order_id' => $order->id ?? 0,
            'order_partner_code' => $data['ORDER_NUMBER'] ?? null,
            'order_reference' => $data['ORDER_REFERENCE'] ?? null,
            'order_statusdate' => $data['ORDER_STATUSDATE'] ?? null,
            'order_status' => $data['ORDER_STATUS'] ?? null,
            'status_name' => $data['STATUS_NAME'],
            'note' => $data['NOTE'],
            'money_conllection' => $data['MONEY_COLLECTION'],
            'money_feecod' => $data['MONEY_FEECOD'],
            'money_total' => $data['MONEY_TOTAL'],
            'expected_delivery' => $data['EXPECTED_DELIVERY'],
            'product_weight' => $data['PRODUCT_WEIGHT'],
            'order_service' => $data['ORDER_SERVICE'],
            'location_currently' => $data['LOCALION_CURRENTLY']
        ];
        return $mapData;
    }

    public function getPriceAllNlp($order, $orderService = true) {
        $path = '/v2/order/getPriceAllNlp';
        $senderAddress = ($order->sender->address ?? '') . ' ' .($order->sender->ward->ward_name ?? '') . ' ' . ($order->sender->city->city_name ?? '');
        $receiverAddress = ($order->receiver->address ?? '') . ' ' .($order->receiver->ward->ward_name ?? '') . ' ' . ($order->receiver->city->city_name ?? '');
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
            'headers' => $this->headers
        ]);
        $response = $client->post(
            $this->url . $path,
            [
                'body' => json_encode($formatData)
            ]
        );
        $result = json_decode($response->getBody()->getContents(), true);
        return $orderService ? ($result['RESULT'][0]['MA_DV_CHINH'] ?? null) : $result;
    }

    public function getGroupId($address) {
        $replaceAddress = $this->stripVN($address);
        $address = strtoupper($replaceAddress);
        $groupId = null;
        $isBreak = false;
        foreach(City::MAP_CITY_VIETTEL_POST as $city) {
            foreach($city['city'] as $item) {
                $replaceItem = $this->stripVN($item);
                if(str_contains($address, $replaceItem)) {
                    $groupId = $city['group_id'];
                    $isBreak = true;
                    break;
                }
            }
            if($isBreak) {
                break;
            }
        }
        return $groupId;
    }

    public function stripVN($str) {
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
        return $str;
    }
}