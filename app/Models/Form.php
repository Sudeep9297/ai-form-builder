<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class Form extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'user_id',
        'title',
        'slug',
        'public_token',
        'description',
        'schema',
        'settings',
        'is_published',
        'version',
        'published_at',
    ];

    protected $casts = [
        'schema' => 'array',
        'settings' => 'array',
        'is_published' => 'boolean',
        'published_at' => 'datetime',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function versions()
    {
        return $this->hasMany(FormVersion::class);
    }

    public function submissions()
    {
        return $this->hasMany(Submission::class);
    }

    public function webhooks()
    {
        return $this->hasMany(WebhookEndpoint::class);
    }
}
