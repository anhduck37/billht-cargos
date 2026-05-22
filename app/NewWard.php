<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class NewWard extends Model
{
    protected $table = 'new_wards';

    protected $fillable = [
        'new_province_id',
        'name',
        'official_code',
        'normalized_name',
        'is_active',
        'effective_from',
        'effective_to',
    ];

    public function newProvince()
    {
        return $this->belongsTo(NewProvince::class, 'new_province_id', 'id');
    }
}
