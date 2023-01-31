<?php

namespace App;

use App\Models\Order;
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

    public function order() {
        return $this->hasOne(Order::class, 'id', 'order_id');
    }
}
