<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class OrderImage extends Model
{
    protected $fillable = [
        'order_id',
        'image'
    ];
}
