<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class DriverToken extends Model
{
    protected $table = 'driver_tokens';

    protected $fillable = [
        'driver_id',
        'device_tokens',
        'platform',
        'locale',
        'site_key',
        'active',
        'last_seen_at',
        'metadata',
    ];

    protected $casts = [
        'active' => 'boolean',
        'last_seen_at' => 'datetime',
        'metadata' => 'array',
    ];
}
