<?php
namespace App\Services;

use GuzzleHttp\Client;

class SendSMSService {

    private $apiKey;
    private $secretKey;
    private $textSendSMS;
    private $url;
    private $brandName;
    private $client;
    private $data;


    public function __construct()
    {
        $this->apiKey = config('sms.apiKey');
        $this->secretKey = config('sms.secretKey');
        $this->textSendSMS = config('sms.textSendSMS');
        $this->url = config('sms.url');
        $this->brandName = config('sms.brandName');
        $this->client = new Client([
            'headers' => ['Content-Type' => 'application/json']
        ]);
        $this->data = [
            'ApiKey'    => $this->apiKey,
            'SecretKey' => $this->secretKey,
            'Brandname' => $this->brandName,
            'SmsType'   => 2,
            'Unicode'   => 0,
        ];
    }

    public function sendSMS($phone, $content=null, $order, $isSend=false) {
        if(!str_contains($order->note, $this->textSendSMS) && !$isSend) {
            return;
        }
        $this->data['Phone'] = $phone;
        if($content) {
            $this->data['Content'] = $content;
        } else if($order) {
            // $this->data['Content'] = '[HTExpress] Xin chao QK HTExpress dang thuc hien giao hang cho QK. Theo doi tai day: ' . route('tracking', ['order_code' => $order->order_code]) . '. Lien he: 1900633656';
            $this->data['Content'] = 'Cam on quy khach da su dung dich vu cua chung toi. Chuc quy khach mot ngay tot lanh!'
        }
        $response = $this->client->post($this->url, ['json' => $this->data]);
        return json_decode($response->getBody()->getContents());
    }
}
