<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ZaloConfig extends Model
{
    protected $fillable = [
        'app_id',
        'template_id',
        'secret_key',
        'access_token',
        'refresh_token',
        'status'
    ];
    protected $table = 'zalo_configs';

    const ERROR_TOKEN_INVALID = -124;
    const SUCCESS_CODE = 0;

    const STATUS_ACTIVE = 1;
    const STATUS_UNACTIVE = 0;
}
