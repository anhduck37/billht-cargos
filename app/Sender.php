<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Sender extends Model
{
    protected $fillable = [
        'sender_name',
        'sender_email',
        'sender_phone',
        'city_id',
        'district_id',
        'ward_id',
        'address',
        'language',
        'department' // phòng ban
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
