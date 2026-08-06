<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FormVersion extends Model
{
    protected $fillable = ['form_id', 'user_id', 'version', 'change_summary', 'schema', 'settings'];

    protected $casts = [
        'schema' => 'array',
        'settings' => 'array',
    ];

    public function form()
    {
        return $this->belongsTo(Form::class);
    }
}
