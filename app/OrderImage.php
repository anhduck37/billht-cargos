<?php

namespace App;

use App\Models\Order;
use Illuminate\Database\Eloquent\Model;

class OrderImage extends Model
{
    protected $fillable = [
        'order_id',
        'image',
        'type_upload',
        'type_save',
        'file_id',
        'google_drive_id',
        'url'
    ];

    const TYPE_IMAGE_FILE = 1;
    const TYPE_IMAGE_WEBCAM = 2;
    const TYPE_IMAGE_PATH = 3;

    const SAVE_SERVER = 1;
    const SAVE_GOOGLE_DRIVE = 2;

    public function order() {
        return $this->hasOne(Order::class, 'id', 'order_id');
    }
}
