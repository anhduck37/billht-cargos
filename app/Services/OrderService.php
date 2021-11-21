<?php

namespace App\Services;

use App\Models\Order;
use App\Partner;
use App\Service;

class OrderService
{
    public function getOrderCode($prefix) {
        $order = Order::latest()->first();
        $order_id = 0;
        if($order){
            $order_id = $order->id;
        }
        for ($i = 0; $i < 6 - strlen($order_id + 1); $i++){
            $prefix .= '0';
        }
        return $prefix.($order_id + 1);
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
        foreach (Service::SERVICE_MAP as $key => $item) {
            if(in_array($service, $item['value'])){
                $service_key = array_search($service, $item['value']);
                $service_type = $key;
                return ['type' => $key, 'service_key' => $service_key];
            }
        }
        return null;
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
