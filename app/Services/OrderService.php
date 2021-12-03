<?php

namespace App\Services;

use App\Models\Order;
use App\Partner;
use App\Service;

class OrderService
{
    public function getOrderCode($prefix) {
        $order = Order::orderBy('id', 'DESC')->first();
        $order_id = 1;
        if($order){
            $order_id = (int)$order->id + 1;
        }
        for ($i = 0; $i < 6 - strlen($order_id); $i++){
            $prefix .= '0';
        }
        return $prefix.($order_id);
    }

    public function explodeDate($date) {
        $times = explode('/',$date);
        $convertDate = $times[2].'-'.$times[1].'-'.$times[0];
        return $convertDate;
    }

    public function implodeDate($date) {
        if(strtotime($date)){
            $times = explode('-',$date);
            $convertDate = $times[2].'/'.$times[1].'/'.$times[0];
            return $convertDate;
        }
        return null;
    }

    public function getKeyService($service) {
        $data = null;
        $value = ucfirst(mb_strtolower(trim($service), 'UTF-8'));
        foreach (Service::SERVICE_MAP as $key => $item) {
            if(in_array($value, $item['value'])){
                $service_key = array_search($value, $item['value']);
                $service_type = $key;
                $data = ['type' => $key, 'service_key' => $service_key];
            }
        }
        return $data;
    }

    public function getKeyPaymentMethod($name) {
        $convertName = ucfirst(mb_strtolower(trim($name), 'UTF-8'));
        if($convertName == 'Cod') {
            $convertName = 'COD';
        }
        $key = array_search($convertName, Order::PAYMENT_METHOD_MAP);
        if($key) {
            return $key;
        }
        return 0;
    }

    public function insertService($services, $order_id) {
        $data = [];
        foreach ($services as $key => $value) {
            foreach ($value as $item) {
                $dataOrdeService = [
                    'order_id' => $order_id,
                    'service' => $item,
                    'type' => $key
                ];
                array_push($data, $dataOrdeService);
            }
        }
        if(!empty($data)){
            Service::insert($data);
        }
    }
}
