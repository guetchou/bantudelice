<?php

namespace App;

use App\Domain\Payment\Enums\PaymentStatus;
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
        'status',
        'amount',
        'currency',
        'meta',
    ];

    protected $casts = [
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

    public function allocations()
    {
        return $this->hasMany(PaymentAllocation::class);
    }

    public function canonicalStatus(): PaymentStatus
    {
        return PaymentStatus::fromRaw($this->status);
    }

    public function isPaid(): bool
    {
        return $this->canonicalStatus() === PaymentStatus::PAID;
    }

    public function isPending(): bool
    {
        return in_array($this->canonicalStatus(), [
            PaymentStatus::INITIATED,
            PaymentStatus::PENDING,
            PaymentStatus::PROCESSING,
        ], true);
    }

    public function isFailed(): bool
    {
        return $this->canonicalStatus() === PaymentStatus::FAILED;
    }

    public function isUnknown(): bool
    {
        return $this->canonicalStatus() === PaymentStatus::UNKNOWN;
    }

    public function isReconcilable(): bool
    {
        return $this->canonicalStatus()->isReconcilable();
    }
}
