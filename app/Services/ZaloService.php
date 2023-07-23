<?php
namespace App\Services;
use App\OrderLog;
use GuzzleHttp\Client;
use App\ZaloConfig;
use App\Services\OrderLogService;
class ZaloService {

    protected $app_id;
    protected $secret_key;
    protected $template_id;
    private $client;
    private $refresh_token;
    private $access_token;
    private $model;
    protected $url;
    protected $zalo_config;
    protected $headers = ['Content-Type' => 'application/json'];
    protected $url_refresh_token = 'https://oauth.zaloapp.com/v4/oa/access_token';
    private $textSendSMS;
    protected $orderLogService;

    public function __construct() {
        $this->model = new ZaloConfig();
        $this->config();
        $this->textSendSMS = config('sms.textSendSMS');
        $this->orderLogService = new OrderLogService(new OrderLog());
    }

    public function sendZNS($order) {
        $phone = $order->receiver->receiver_phone;
        $phone = $this->formatPhone($phone);
        $data = [
            'phone' => $phone,
            'template_id' => $this->template_id,
            'template_data' => [
                'customer_name' => $order->receiver->receiver_name,
                'product_name' => $order->order_code
            ],
            'tracking_id' => $order->order_code
        ];
        $this->headers['access_token'] = $this->access_token;
        $client = new Client([
            'headers' => $this->headers
        ]);
        $response = $client->post($this->url, ['json' => $data]);
        $response = json_decode($response->getBody()->getContents(), true);
        if($response['error'] == ZaloConfig::SUCCESS_CODE) {
            $order->note = str_replace($this->textSendSMS, '',$order->note);
            $order->save();
        }

        return $response;
    }

    public function refresh_token() {
        $data = [
            'refresh_token' => $this->refresh_token,
            'app_id' => $this->app_id,
            'grant_type' => 'refresh_token'
        ];
        $this->headers['secret_key'] = $this->secret_key;
        $client = new Client([
            'headers' => $this->headers
        ]);
        $response = $client->post($this->url_refresh_token, ['form_params' => $data]);
        $response = json_decode($response->getBody()->getContents(), true);
        if(isset($response['access_token'])) {
            $this->zalo_config->access_token = $response['access_token'];
            $this->zalo_config->refresh_token = $response['refresh_token'];
            $this->zalo_config->save();
        }
        return $this->zalo_config;
    }

    public function formatPhone($phone) {
        $phone = preg_replace('/[^0-9]/','',$phone);
        $phone = substr($phone, 1);
        return 84 . $phone;
    }

    public function config() {
        $this->app_id = config('zalo.app_id');
        $this->secret_key = config('zalo.secret_key');
        $this->template_id = config('zalo.template_id');
        $this->refresh_token = config('zalo.refresh_token');
        $this->url = config('zalo.url');
        $data = [
            'app_id' => $this->app_id,
            'secret_key' => $this->secret_key,
            'template_id' => $this->template_id,
        ];
        $zaloConfig = $this->model->where('app_id', $this->app_id)->where('status',  ZaloConfig::STATUS_ACTIVE)->first();
        if(!$zaloConfig) {
            $zaloConfig = $this->model;
            $data['refresh_token'] = $this->refresh_token;
            $data['status'] = ZaloConfig::STATUS_ACTIVE;
            $zaloConfig->fill($data);
            $zaloConfig->save();
        }
        $this->zalo_config = $zaloConfig;
        $this->access_token = $zaloConfig->access_token;
        $this->refresh_token = $zaloConfig->refresh_token;
        return $this;
    }
    
}