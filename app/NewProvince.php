<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class NewProvince extends Model
{
    protected $table = 'new_provinces';

    protected $fillable = [
        'name',
        'official_code',
        'normalized_name',
        'is_active',
        'effective_from',
        'effective_to',
    ];

    public function newWards()
    {
        return $this->hasMany(NewWard::class, 'new_province_id', 'id');
    }
}
