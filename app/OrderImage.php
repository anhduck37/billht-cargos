<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class OrderImage extends Model
{
    protected $fillable = [
        'order_id',
        'image',
        'type_upload'
    ];

    const TYPE_IMAGE_FILE = 1;
    const TYPE_IMAGE_WEBCAM = 2;
}
