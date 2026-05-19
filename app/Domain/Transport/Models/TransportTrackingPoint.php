<?php

namespace App\Domain\Transport\Models;

use Illuminate\Database\Eloquent\Model;

class TransportTrackingPoint extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'booking_id', 'lat', 'lng', 'speed', 'recorded_at'
    ];

    protected $casts = [
        'recorded_at' => 'datetime',
        'lat' => 'float',
        'lng' => 'float',
        'speed' => 'float',
    ];

    public function booking()
    {
        return $this->belongsTo(TransportBooking::class, 'booking_id');
    }
}

