<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class OrderLog extends Model
{
    protected $fillable = [
        'order_code',
        'request',
        'response',
        'action',
        'path',
        'status'
    ];

    const ACTION_ZALO = 1;
    const STATUS_SUCCESS = 1;
    const STATUS_ERROR = 0;
}
