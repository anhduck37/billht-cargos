<?php

namespace App;

use App\Models\Order;
use Illuminate\Database\Eloquent\Model;

class OrderCodeAlias extends Model
{
    protected $fillable = [
        'order_id',
        'old_code',
        'new_code',
        'reason',
        'created_by',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id', 'id');
    }
}
