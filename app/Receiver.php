<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Receiver extends Model
{
    protected $fillable = [
        'receiver_name',
        'receiver_email',
        'receiver_phone',
        'city_id',
        'district_id',
        'ward_id',
        'address',
        'language',
        'department'
    ];
    public function city() {
        return $this->hasOne(City::class, 'id', 'city_id');
    }

    public function district() {
        return $this->hasOne(District::class, 'id', 'district_id');
    }
    public function ward() {
        return $this->hasOne(Ward::class, 'id','ward_id');
    }
}
