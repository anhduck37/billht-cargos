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
        'department',
        'address_scheme',
        'new_province_id',
        'new_ward_id'
    ];

    protected $appends = ['city_name', 'district_name', 'ward_name', 'full_address_text'];
    public function city() {
        return $this->hasOne(City::class, 'id', 'city_id');
    }

    public function district() {
        return $this->hasOne(District::class, 'id', 'district_id');
    }
    public function ward() {
        return $this->hasOne(Ward::class, 'id','ward_id');
    }

    public function newProvince() {
        return $this->belongsTo(NewProvince::class, 'new_province_id', 'id');
    }

    public function newWard() {
        return $this->belongsTo(NewWard::class, 'new_ward_id', 'id');
    }

    public function getFullAddressAttribute()
    {
        return app(\App\Services\AddressFormatterService::class)->getFullAddress($this);
    }

    public function getCityNameAttribute()
    {
        if ($this->address_scheme === 'new') {
            return $this->newProvince ? $this->newProvince->name : '';
        }
        return $this->city ? $this->city->city_name : '';
    }

    public function getDistrictNameAttribute()
    {
        if ($this->address_scheme === 'new') {
            return '';
        }
        return $this->district ? $this->district->district_name : '';
    }

    public function getWardNameAttribute()
    {
        if ($this->address_scheme === 'new') {
            return $this->newWard ? $this->newWard->name : '';
        }
        return $this->ward ? $this->ward->ward_name : '';
    }

    public function getFullAddressTextAttribute()
    {
        $parts = [];
        if ($this->address) $parts[] = $this->address;
        
        if ($this->address_scheme === 'new') {
            if ($this->newWard) $parts[] = $this->newWard->name;
            if ($this->newProvince) $parts[] = $this->newProvince->name;
        } else {
            if ($this->ward) $parts[] = $this->ward->ward_name;
            if ($this->district) $parts[] = $this->district->district_name;
            if ($this->city) $parts[] = $this->city->city_name;
        }
        return implode(', ', array_filter($parts));
    }
}
