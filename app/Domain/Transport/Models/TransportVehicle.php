<?php

namespace App\Domain\Transport\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;

class TransportVehicle extends Model
{
    use SoftDeletes, HasFactory;

    protected $fillable = [
        'uuid', 'owner_id', 'make', 'model', 'year', 'plate_number', 'color', 
        'type', 'seats', 'description', 'image', 'features', 'daily_rate', 
        'is_available', 'status', 'rejection_reason', 'documents', 
        'approved_at', 'approved_by'
    ];

    protected $casts = [
        'features' => 'json',
        'documents' => 'json',
        'is_available' => 'boolean',
        'daily_rate' => 'decimal:2',
        'approved_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            $model->uuid = (string) Str::uuid();
        });
    }

    public function owner()
    {
        return $this->belongsTo(\App\User::class, 'owner_id');
    }

    public function bookings()
    {
        return $this->hasMany(TransportBooking::class, 'vehicle_id');
    }
}
