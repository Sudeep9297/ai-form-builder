<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ImportBatch extends Model
{
    protected $fillable = [
        'tenant_id',
        'user_id',
        'form_id',
        'status',
        'original_name',
        'path',
        'mime_type',
        'detected_schema',
        'mapping',
        'warnings',
        'error',
        'finished_at',
    ];

    protected $casts = [
        'detected_schema' => 'array',
        'mapping' => 'array',
        'warnings' => 'array',
        'finished_at' => 'datetime',
    ];
}
