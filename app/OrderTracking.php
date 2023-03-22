<?php

namespace App;

use App\Models\Order;
use Illuminate\Database\Eloquent\Model;

class OrderTracking extends Model
{
    protected $fillable = [
        'order_id',
        'order_code',
        'user_id',
        'order_status',
        // 'request',
        'delivery_status',
        'city_id',
        'person_charge',
        'signator'
    ];

    public function order() {
        return $this->hasOne(Order::class, 'id', 'order_id');
    }
    public function getDeliveryStatusName($key)
    {
        return (array_key_exists($key, Order::DELIVERY_MAP)) ? Order::DELIVERY_MAP[$key] : '';
    }

    public function location() {
        return $this->hasOne(City::class, 'id', 'city_id');
    }
}
