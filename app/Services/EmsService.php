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

    private $username;
    private $password;
    public $headers = [
        'Content-Type' => 'application/json'
    ];
    public $url;
    private $api_key;
    private $service_viettel;

    public function __construct()
    {
        $this->url = config('viettel_post.url');
        $this->api_key = config('viettel_post.api');
    }

    public function formatBody($order)
    {
        $data = [
            "order_code" => $order->order_code,
            "inventory_name" => $this->getGroupId($order->address),
            "from_name" => "Nguyen Van A",
            "from_phone" => "0123456789",
            "from_province" => 17,
            "from_district" => 1754,
            "from_ward" => 1751,
            "from_address" => "123 Nguyen Trai, Thanh Xuan, Hanoi",
            "to_name" => "Tran Thi B",
            "to_phone" => "0987654321",
            "to_province" => 17,
            "to_district" => 1754,
            "to_ward" => 1755,
            "to_address" => "NUUUUUUUUUUUU",
            "product_name" => "Laptop",
            "total_amount" => 15000000,
            "total_quantity" => 1,
            "total_weight" => 2000,
            "description" => "High-end gaming laptop",
            "size" => "30x20x5",
            "service" => 1
        ];
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
        return $str;
    }
}
