<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class PaymentAllocation extends Model
{
    protected $fillable = [
        'uuid',
        'payment_id',
        'target_type',
        'target_id',
        'target_reference',
        'amount',
        'currency',
        'status',
        'idempotency_key',
        'allocated_at',
        'reversed_at',
        'reversal_reason',
        'created_by',
        'metadata',
    ];

    protected $casts = [
        'allocated_at' => 'datetime',
        'reversed_at' => 'datetime',
        'metadata' => 'array',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $allocation) {
            $allocation->uuid ??= (string) Str::uuid();
            $allocation->allocated_at ??= now();
        });
    }

    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}
