<?php

namespace App\Services;

use App\Models\Order;
use App\Partner;
use App\Service;
use Illuminate\Support\Facades\File;

class OrderService
{
    public $order_id_current = 1;

    public function getOrderCode($prefix) {
        $order = Order::orderBy('id', 'DESC')->first();
        if($order){
            $this->order_id_current = (int)$order->id + 1;
        }
        $order_code = $this->genCode($prefix);
        $checkOrder = Order::where('order_code', $order_code)->first();
        do {
            $order_code = $this->genCode($prefix);
            $checkOrder = Order::where('order_code', $order_code)->first();
            $this->order_id_current += 1;
        } while(isset($checkOrder));
        return $order_code;
    }

    public function genCode($prefix) {
        for ($i = 0; $i < 6 - strlen($this->order_id_current); $i++){
            $prefix .= '0';
        }
        return $prefix.($this->order_id_current);
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
        return Service::VIETTEL_POST_SERVICE_ADD[$service] ?? $data;
    }

    public function getType($type) {
        $data = Order::ORDER_TYPE_DOCUMENT;
        $value = ucfirst(mb_strtolower(trim($type), 'UTF-8'));
        foreach (Order::MAP_ORDER_TYPE as $key => $item) {
            if($value == ucfirst(mb_strtolower(trim($item), 'UTF-8'))) {
                $data = $key;
                break;
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

    public function renameImage($orderImage) {
        $regex = '/\.[a-z]*$/';
        $path = public_path()."/uploads/";
        if(File::exists($path . $orderImage->image)) {
            $name = preg_replace($regex, '', $orderImage->image);
            $new_name = str_replace($name, $orderImage->order->order_code, $orderImage->image);
            rename($path . $orderImage->image, $path . $new_name);
            $orderImage->image = $new_name;
            $orderImage->save();
        }
    }
}
