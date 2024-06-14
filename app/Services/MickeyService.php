<?php
namespace App\Services;

use GuzzleHttp\Client;
use Exception;

class MickeyService {

    private $api;
    protected $headers = ['Content-Type' => 'application/json'];

    public function __construct() {
        $this->api = config('tracking.mickey_url');
    }

    public function tracking($order, $order_code=null) {
        $path = '/api/tracking';
        $client = new Client(['headers' => $this->headers]);
        $data = [
            'ma_dvi' => "190",
            'so_hieu' => $order->order_code ?? $order_code
        ];
        try{
            $response = $client->post(
                $this->api.$path, 
                [
                    'body' => json_encode($data)
                ]
            );
        }catch(Exception $e) {
            return null;
        }
        
        $result = $response = json_decode($response->getBody()->getContents(), true);
        return $result['json'] ?? null;
    }
}