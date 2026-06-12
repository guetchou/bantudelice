<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CommerceSignal extends Model
{
    protected $fillable = [
        'signal_type',
        'domain',
        'module',
        'severity',
        'order_id',
        'order_no',
        'user_id',
        'restaurant_id',
        'driver_id',
        'subject_type',
        'subject_id',
        'payload',
    ];

    protected $casts = [
        'payload' => 'array',
    ];
}
