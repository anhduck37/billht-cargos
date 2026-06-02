<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SmartImportBatch extends Model
{
    protected $fillable = [
        'token',
        'user_id',
        'file_name',
        'file_path',
        'status',
        'total_rows',
        'valid_rows',
        'error_rows',
        'imported_rows',
        'summary',
    ];

    protected $casts = [
        'summary' => 'array',
    ];

    public function rows()
    {
        return $this->hasMany(SmartImportRow::class, 'smart_import_batch_id');
    }
}
