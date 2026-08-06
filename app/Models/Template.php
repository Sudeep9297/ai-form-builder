<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Template extends Model
{
    protected $fillable = ['tenant_id', 'name', 'category', 'description', 'schema', 'is_system'];

    protected $casts = [
        'schema' => 'array',
        'is_system' => 'boolean',
    ];
}
