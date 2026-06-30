<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class FinancialStateTransition extends Model
{
    protected $fillable = [
        'uuid',
        'subject_type',
        'subject_id',
        'from_status',
        'to_status',
        'source',
        'reason',
        'actor_id',
        'idempotency_key',
        'occurred_at',
        'context',
    ];

    protected $casts = [
        'occurred_at' => 'datetime',
        'context' => 'array',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $transition) {
            if (empty($transition->uuid)) {
                $transition->uuid = (string) Str::uuid();
            }
            if (empty($transition->occurred_at)) {
                $transition->occurred_at = now();
            }
        });

        static::updating(function () {
            throw new \LogicException('Le journal des transitions est immuable.');
        });

        static::deleting(function () {
            throw new \LogicException('Le journal des transitions est immuable.');
        });
    }
}
