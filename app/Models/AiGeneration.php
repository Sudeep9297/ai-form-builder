<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiGeneration extends Model
{
    protected $fillable = [
        'tenant_id',
        'form_id',
        'user_id',
        'mode',
        'status',
        'prompt',
        'model',
        'prompt_tokens',
        'completion_tokens',
        'latency_ms',
        'result_schema',
        'error',
        'started_at',
        'finished_at',
    ];

    protected $casts = [
        'result_schema' => 'array',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    public function form()
    {
        return $this->belongsTo(Form::class);
    }
}
