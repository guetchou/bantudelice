<?php

namespace App\Domain\GePay\Models;

use Illuminate\Database\Eloquent\Model;

class GePayWebhookEvent extends Model
{
    protected $table = 'gepay_webhook_events';

    protected $fillable = [
        'provider',
        'event_key',
        'payload_hash',
        'status',
        'payload',
        'processed_at',
        'error_message',
    ];

    protected $hidden = ['payload'];

    protected $casts = [
        'payload' => 'encrypted:array',
        'processed_at' => 'datetime',
    ];
}
