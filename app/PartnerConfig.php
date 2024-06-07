<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PartnerConfig extends Model
{
    protected $fillable = [
        'partner_code',
        'token'
    ];

    const CODE_VIETTEL_POST = 'VIETTEL_POST';
}
