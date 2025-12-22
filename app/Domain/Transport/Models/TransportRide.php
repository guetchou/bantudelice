<?php

namespace App\Domain\Transport\Models;

use App\Driver;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;

class TransportRide extends Model
{
    use SoftDeletes, HasFactory;

    protected $fillable = [
        'uuid', 'driver_id', 'vehicle_id', 'origin_address', 'origin_lat', 'origin_lng',
        'destination_address', 'destination_lat', 'destination_lng',
        'departure_time', 'available_seats', 'price_per_seat', 'status', 'description'
    ];

    protected $casts = [
        'departure_time' => 'datetime',
        'price_per_seat' => 'decimal:2',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            $model->uuid = (string) Str::uuid();
        });
    }

    public function driver()
    {
        return $this->belongsTo(Driver::class);
    }

    public function vehicle()
    {
        return $this->belongsTo(TransportVehicle::class, 'vehicle_id');
    }

    public function bookings()
    {
        return $this->hasMany(TransportBooking::class, 'ride_id'); // Need to add ride_id to transport_bookings if we want to link them directly
    }
}

