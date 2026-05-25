<?php

namespace App;

use App\Models\Order;
use Illuminate\Database\Eloquent\Model;

class OrderPartnerLog extends Model
{
    protected $fillable = [
        'order_id',
        'partner_code',
        'status',
        'payload',
        'response',
        'user_id'
    ];

    const STATUS_SUCCESS = 1;
    const STATUS_FAILD = 0;

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id', 'id');
    }
}
