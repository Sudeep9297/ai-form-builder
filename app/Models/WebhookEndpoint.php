<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WebhookEndpoint extends Model
{
    protected $fillable = ['tenant_id', 'form_id', 'url', 'secret', 'is_active', 'last_delivered_at', 'failure_count'];

    protected $casts = [
        'is_active' => 'boolean',
        'last_delivered_at' => 'datetime',
    ];
}
