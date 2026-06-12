<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ExternalIntegration extends Model
{
    protected $fillable = [
        'provider',
        'module',
        'status',
        'last_healthy_at',
        'last_error_at',
        'last_error_message',
        'metadata',
    ];

    protected $casts = [
        'last_healthy_at' => 'datetime',
        'last_error_at' => 'datetime',
        'metadata' => 'array',
    ];
}
