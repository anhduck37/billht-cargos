<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SmartImportRow extends Model
{
    protected $fillable = [
        'smart_import_batch_id',
        'order_id',
        'row_number',
        'status',
        'raw_data',
        'editable_data',
        'analysis',
        'errors',
        'warnings',
        'imported_at',
    ];

    protected $casts = [
        'raw_data' => 'array',
        'editable_data' => 'array',
        'analysis' => 'array',
        'errors' => 'array',
        'warnings' => 'array',
        'imported_at' => 'datetime',
    ];

    public function batch()
    {
        return $this->belongsTo(SmartImportBatch::class, 'smart_import_batch_id');
    }

    public function order()
    {
        return $this->belongsTo(\App\Models\Order::class, 'order_id');
    }
}
