<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PaymentAllocation extends Model
{
    protected $fillable = [
        'payment_id',
        'order_id',
        'allocatable_type',
        'allocatable_id',
        'allocation_key',
        'amount',
        'currency',
        'status',
        'allocated_at',
        'released_at',
        'metadata',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'allocated_at' => 'datetime',
        'released_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'allocated';
    }
}
