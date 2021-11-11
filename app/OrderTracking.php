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
        'request'
    ];

    public function order() {
        return $this->hasOne(Order::class, 'id', 'order_id');
    }
    public function getOrderStatusName($key)
    {
        return (array_key_exists($key, Order::MAP_ORDER_STATUS)) ? Order::MAP_ORDER_STATUS[$this->order_status] : '';
    }
}
