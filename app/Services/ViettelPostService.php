<?php
namespace App\Services;

use App\Models\Order;
use App\OrderPartnerLog;
use App\Partner;
use App\PartnerConfig;
use App\PartnerTracking;
use GuzzleHttp\Client;

class ViettelPostService {

    private $username;
    private $password;
    public $headers = [
        'Content-Type' => 'application/json'
    ];
    public $url;
    private $api;

    public function __construct() {
        $this->username = config('viettel_post.username');
        $this->password = config('viettel_post.password');
        $this->url = config('viettel_post.url');
        $this->api = config('viettel_post.api');
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
        $formatData = [
            "ORDER_NUMBER" => $order->order_code,
            "SENDER_FULLNAME" => $order->sender->sender_name ?? '',
            "SENDER_ADDRESS" => !empty(trim($senderAddress)) ? $senderAddress : '',
            "SENDER_PHONE" => $order->sender->sender_phone ?? '',
            "SENDER_EMAIL" => $order->sender->sender_email ?? '',
            "SENDER_WARD" => $order->sender->ward->ward_code ?? 0,
            "SENDER_DISTRICT" => $order->sender->district->district_code ?? 0,
            "SENDER_PROVINCE" => $order->sender->city->city_code ?? 0,
            "RECEIVER_FULLNAME" => $order->receiver->receiver_name ?? 0,
            "RECEIVER_ADDRESS" => $receiverAddress,
            "RECEIVER_PHONE" => $order->receiver->receiver_phone,
            "RECEIVER_EMAIL" => $order->receiver->receiver_email,
            "RECEIVER_WARD" => $order->receiver->ward->ward_code ?? 0,
            "RECEIVER_DISTRICT" => $order->receiver->district->district_code ?? 0,
            "RECEIVER_PROVINCE" => $order->receiver->city->city_code ?? 0,
            "PRODUCT_NAME" => $order->note,
            "PRODUCT_QUANTITY" => $order->quantity,
            "PRODUCT_WEIGHT" => $order->weight,
            "PRODUCT_WIDTH" => $order->width,
            "PRODUCT_HEIGHT" => $order->height,
            "PRODUCT_LENGTH" => $order->long,
            "PRODUCT_TYPE" => $order->type == Order::ORDER_TYPE_DOCUMENT ? "TH" : "HH",
            "ORDER_PAYMENT" => 2,
            "ORDER_SERVICE" => "VCN",
            "ORDER_NOTE" => $order->note,
            "MONEY_COLLECTION" => 0,
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
        app(LogFileService::class)->writeLog('viett_post', json_encode($data));
        $dataWebhook = isset($data['DATA']['ORDER_NUMBER']) ? $data['DATA'] : null;
        if(!$dataWebhook) return;

        $order = Order::where('order_partner_code', $dataWebhook['ORDER_NUMBER'])->first();
        if(!$order) {
            return;
        }
        $dataPartnerTracking = $this->formatDataWebhook($order, $dataWebhook);
        $partnerTracking = new PartnerTracking();
        $partnerTracking->fill($dataPartnerTracking);
        $partnerTracking->save();
        return $partnerTracking;
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
}