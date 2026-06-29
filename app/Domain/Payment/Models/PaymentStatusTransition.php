<?php

namespace App\Domain\Payment\Models;

use App\Domain\Payment\Enums\PaymentStatus;
use App\Payment;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Str;

class PaymentStatusTransition extends Model
{
    protected $fillable = [
        'uuid',
        'payment_id',
        'from_status',
        'to_status',
        'source',
        'actor_type',
        'actor_id',
        'reason',
        'evidence',
        'occurred_at',
        'idempotency_key',
    ];

    protected $casts = [
        'from_status' => PaymentStatus::class,
        'to_status' => PaymentStatus::class,
        'evidence' => 'array',
        'occurred_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $transition): void {
            if (!$transition->uuid) {
                $transition->uuid = (string) Str::uuid();
            }
        });
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function actor(): MorphTo
    {
        return $this->morphTo();
    }
}
