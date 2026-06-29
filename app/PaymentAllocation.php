<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PaymentAllocation extends Model
{
    protected $fillable = [
        'payment_id',
        'target_type',
        'target_id',
        'target_reference',
        'amount',
        'currency',
        'status',
        'idempotency_key',
        'metadata',
        'allocated_at',
        'reversed_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'allocated_at' => 'datetime',
        'reversed_at' => 'datetime',
    ];

    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'allocated';
    }
}
