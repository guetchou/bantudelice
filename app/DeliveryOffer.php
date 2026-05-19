<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class DeliveryOffer extends Model
{
    protected $fillable = [
        'delivery_id', 'driver_id', 'status', 'offer_rank',
        'driver_score', 'distance_km', 'expires_at', 'responded_at', 'decline_reason',
    ];

    protected $casts = [
        'expires_at'    => 'datetime',
        'responded_at'  => 'datetime',
        'driver_score'  => 'float',
        'distance_km'   => 'float',
    ];

    public function delivery() { return $this->belongsTo(Delivery::class); }
    public function driver()   { return $this->belongsTo(Driver::class); }

    public function isPending(): bool  { return $this->status === 'pending'; }
    public function isExpired(): bool  { return $this->status === 'expired' || now()->isAfter($this->expires_at); }
}
