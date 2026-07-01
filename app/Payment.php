<?php

namespace App;

use App\Domain\Payment\Enums\PaymentAllocationStatus;
use App\Domain\Payment\Enums\PaymentStatus;
use App\Domain\Payment\Models\PaymentAllocation;
use App\Domain\Payment\Models\PaymentStatusTransition;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
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
        'canonical_status',
        'status_version',
        'status_updated_at',
        'amount',
        'currency',
        'meta',
    ];

    protected $casts = [
        'amount' => 'integer',
        'status_version' => 'integer',
        'status_updated_at' => 'datetime',
        'meta' => 'array',
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

    public function allocations(): HasMany
    {
        return $this->hasMany(PaymentAllocation::class);
    }

    public function activeAllocations(): HasMany
    {
        return $this->allocations()->where('status', PaymentAllocationStatus::ACTIVE->value);
    }

    public function statusTransitions(): HasMany
    {
        return $this->hasMany(PaymentStatusTransition::class);
    }

    public function canonicalStatus(): PaymentStatus
    {
        return PaymentStatus::fromCanonicalOrStorage(
            $this->canonical_status,
            $this->status
        );
    }

    public function allocatedAmount(): int
    {
        return (int) $this->activeAllocations()->sum('amount');
    }

    public function unallocatedAmount(): int
    {
        return max((int) $this->amount - $this->allocatedAmount(), 0);
    }

    public function isPaid(): bool
    {
        return $this->canonicalStatus()->isFinanciallyConfirmed();
    }

    public function isPending(): bool
    {
        return in_array($this->canonicalStatus(), [
            PaymentStatus::CREATED,
            PaymentStatus::SUBMITTED,
            PaymentStatus::PENDING,
            PaymentStatus::PROCESSING,
            PaymentStatus::UNKNOWN,
        ], true);
    }

    public function isFailed(): bool
    {
        return in_array($this->canonicalStatus(), [
            PaymentStatus::FAILED,
            PaymentStatus::CANCELLED,
            PaymentStatus::EXPIRED,
            PaymentStatus::REVERSED,
        ], true);
    }
}
