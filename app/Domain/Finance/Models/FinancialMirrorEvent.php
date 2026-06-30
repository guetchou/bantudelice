<?php

namespace App\Domain\Finance\Models;

use Illuminate\Database\Eloquent\Model;

final class FinancialMirrorEvent extends Model
{
    protected $fillable = [
        'uuid',
        'event_key',
        'event_type',
        'source_type',
        'source_id',
        'status',
        'attempts',
        'payload',
        'posting_batch_uuid',
        'last_error',
        'processed_at',
        'failed_at',
    ];

    protected $casts = [
        'source_id' => 'integer',
        'attempts' => 'integer',
        'payload' => 'array',
        'processed_at' => 'datetime',
        'failed_at' => 'datetime',
    ];
}
