<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class GoogleDrive extends Model
{
    protected $fillable = [
        'folder_id',
        'month',
    ];
}
