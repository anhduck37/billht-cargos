<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class NewAddressPartnerMapping extends Model
{
    protected $table = 'new_address_partner_mappings';

    protected $fillable = [
        'new_province_id',
        'new_ward_id',
        'partner_code',
        'partner_province_code',
        'partner_district_code',
        'partner_ward_code',
        'mapping_status',
        'note',
    ];

    public function newProvince()
    {
        return $this->belongsTo(NewProvince::class, 'new_province_id', 'id');
    }

    public function newWard()
    {
        return $this->belongsTo(NewWard::class, 'new_ward_id', 'id');
    }
}
