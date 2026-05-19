<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class UserToken extends Model
{
    protected $table = 'user_tokens';

    protected $fillable = [
        'user_id',
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
