<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class OrderHistory extends Model
{
    protected $fillable = [
        'order_id',
        'user_id',
        // 'order_old',
        // 'order_new',
        // 'request',
        'type_order',
        'user_level',
        'is_total_order'
    ];
    protected $table = 'order_historys';

    const TYPE_ORDER_CREATE = 1;
    const TYPE_ORDER_UPDATE = 2;

    const TYPE_ORDER_PRINT = 3;

    const IS_TOTAL_ORDER = 1;
    const NOT_TOTAL_ORDER = 0;
}
