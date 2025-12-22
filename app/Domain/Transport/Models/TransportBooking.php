<?php

namespace App\Domain\Transport\Models;

use App\Domain\Transport\Enums\TransportStatus;
use App\Domain\Transport\Enums\TransportType;
use App\Driver;
use App\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;

class TransportBooking extends Model
{
    use SoftDeletes, HasFactory;

    protected $fillable = [
        'uuid', 'booking_no', 'type', 'user_id', 'driver_id', 'vehicle_id',
        'pickup_address', 'pickup_lat', 'pickup_lng', 
        'dropoff_address', 'dropoff_lat', 'dropoff_lng',
        'scheduled_at', 'started_at', 'completed_at', 'cancelled_at',
        'return_date', 'estimated_distance', 'estimated_duration',
        'estimated_price', 'actual_price', 'tax', 'discount', 'total_price',
        'payment_method', 'payment_status', 'status', 'notes'
    ];

    protected $casts = [
        'type' => TransportType::class,
        'status' => TransportStatus::class,
        'scheduled_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'return_date' => 'datetime',
        'estimated_price' => 'decimal:2',
        'actual_price' => 'decimal:2',
        'tax' => 'decimal:2',
        'discount' => 'decimal:2',
        'total_price' => 'decimal:2',
        'estimated_distance' => 'decimal:2',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            $model->uuid = (string) Str::uuid();
            if (empty($model->booking_no)) {
                $model->booking_no = 'TR-' . strtoupper(Str::random(8));
            }
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function driver()
    {
        return $this->belongsTo(Driver::class);
    }

    public function vehicle()
    {
        return $this->belongsTo(TransportVehicle::class, 'vehicle_id');
    }

    public function trackingPoints()
    {
        return $this->hasMany(TransportTrackingPoint::class, 'booking_id');
    }

    public function payments()
    {
        return $this->hasMany(\App\Payment::class, 'transport_booking_id');
    }

    protected static function newFactory()
    {
        return \Database\Factories\TransportBookingFactory::new();
    }
}

