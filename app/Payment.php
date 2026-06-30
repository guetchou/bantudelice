<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Payment extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'order_id',
        'shipment_id',
        'transport_booking_id',
        'provider',
        'provider_reference',
        'idempotency_key',
        'status',
        'business_status',
        'confirmed_at',
        'failed_at',
        'reversed_at',
        'refunded_at',
        'reconciled_at',
        'amount',
        'currency',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
        'confirmed_at' => 'datetime',
        'failed_at' => 'datetime',
        'reversed_at' => 'datetime',
        'refunded_at' => 'datetime',
        'reconciled_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function shipment()
    {
        return $this->belongsTo(\App\Domain\Colis\Models\Shipment::class);
    }

    public function transportBooking()
    {
        return $this->belongsTo(\App\Domain\Transport\Models\TransportBooking::class, 'transport_booking_id');
    }

    public function allocations()
    {
        return $this->hasMany(PaymentAllocation::class);
    }

    public function activeAllocations()
    {
        return $this->allocations()->where('status', 'active');
    }

    public function canonicalBusinessStatus(): string
    {
        if ($this->business_status) {
            return strtolower($this->business_status);
        }

        return match (strtoupper((string) $this->status)) {
            'AUTHORIZED' => 'authorized',
            'PAID', 'SUCCESS', 'SUCCESSFUL' => 'confirmed',
            'FAILED', 'REJECTED', 'DECLINED' => 'failed',
            'CANCELLED', 'CANCELED' => 'cancelled',
            'EXPIRED' => 'expired',
            'REFUNDED' => 'refunded',
            'REVERSED' => 'reversed',
            'DISPUTED', 'CHARGEBACK' => 'disputed',
            'UNKNOWN' => 'unknown',
            default => 'pending',
        };
    }

    public function isConfirmed(): bool
    {
        return $this->canonicalBusinessStatus() === 'confirmed';
    }

    public function isPaid(): bool
    {
        return $this->isConfirmed();
    }

    public function isPending(): bool
    {
        return in_array($this->canonicalBusinessStatus(), ['initiated', 'pending', 'authorized', 'unknown'], true);
    }

    public function isFailed(): bool
    {
        return in_array($this->canonicalBusinessStatus(), ['failed', 'cancelled', 'expired'], true);
    }

    public function allocatedAmount(): int
    {
        return (int) $this->activeAllocations()->sum('amount');
    }

    public function unallocatedAmount(): int
    {
        return max(0, (int) $this->amount - $this->allocatedAmount());
    }
}
