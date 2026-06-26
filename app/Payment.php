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

    public function isPaid(): bool
    {
        return $this->status === 'PAID';
    }

    public function isPending(): bool
    {
        return $this->status === 'PENDING';
    }

    public function isFailed(): bool
    {
        return $this->status === 'FAILED';
    }
}
