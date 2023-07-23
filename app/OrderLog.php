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
        'path'
    ];

    const ACTION_ZALO = 1;
}
