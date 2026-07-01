<?php

namespace App\Domain\Payment\Models;

use App\Domain\Payment\Enums\PaymentAllocationStatus;
use App\Payment;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Str;

class PaymentAllocation extends Model
{
    protected $fillable = [
        'uuid',
        'payment_id',
        'allocatable_type',
        'allocatable_id',
        'amount',
        'currency',
        'status',
        'idempotency_key',
        'allocated_at',
        'reversed_at',
        'metadata',
    ];

    protected $casts = [
        'status' => PaymentAllocationStatus::class,
        'amount' => 'integer',
        'allocated_at' => 'datetime',
        'reversed_at' => 'datetime',
        'metadata' => 'array',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $allocation): void {
            if (!$allocation->uuid) {
                $allocation->uuid = (string) Str::uuid();
            }
        });
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function allocatable(): MorphTo
    {
        return $this->morphTo();
    }
}
