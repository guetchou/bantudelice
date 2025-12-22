<?php

namespace App\Domain\Colis\Models;

use App\Domain\Colis\Enums\ShipmentStatus;
use App\User;
use App\Driver;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Shipment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid',
        'tracking_number',
        'customer_id',
        'status',
        'service_level',
        'pickup_type',
        'dropoff_type',
        'declared_value',
        'cod_amount',
        'currency',
        'distance_km',
        'weight_kg',
        'volume_cm3',
        'price_breakdown',
        'total_price',
        'payment_status',
        'assigned_courier_id',
        'pickup_scheduled_at',
        'delivered_at',
    ];

    protected $casts = [
        'status' => ShipmentStatus::class,
        'price_breakdown' => 'array',
        'pickup_scheduled_at' => 'datetime',
        'delivered_at' => 'datetime',
        'declared_value' => 'decimal:2',
        'cod_amount' => 'decimal:2',
        'total_price' => 'decimal:2',
        'distance_km' => 'decimal:2',
        'weight_kg' => 'decimal:2',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function courier(): BelongsTo
    {
        return $this->belongsTo(Driver::class, 'assigned_courier_id');
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(ShipmentAddress::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(ShipmentEvent::class);
    }

    public function proofs(): HasMany
    {
        return $this->hasMany(ShipmentProof::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(\App\Payment::class);
    }

    // --- Scopes de filtrage ---

    public function scopeFilter($query, array $filters)
    {
        $query->when($filters['search'] ?? null, function ($query, $search) {
            $query->where(function ($q) use ($search) {
                $q->where('tracking_number', 'like', "%{$search}%")
                  ->orWhereHas('customer', function ($q) use ($search) {
                      $q->where('name', 'like', "%{$search}%");
                  });
            });
        })->when($filters['status'] ?? null, function ($query, $status) {
            $query->where('status', $status);
        })->when($filters['courier_id'] ?? null, function ($query, $courierId) {
            $query->where('assigned_courier_id', $courierId);
        })->when($filters['date_from'] ?? null, function ($query, $date) {
            $query->whereDate('created_at', '>=', $date);
        })->when($filters['date_to'] ?? null, function ($query, $date) {
            $query->whereDate('created_at', '<=', $date);
        });
    }

    public function pickupAddress()
    {
        return $this->addresses()->where('type', 'pickup')->first();
    }

    public function dropoffAddress()
    {
        return $this->addresses()->where('type', 'dropoff')->first();
    }

    protected static function newFactory()
    {
        return \Database\Factories\ShipmentFactory::new();
    }
}

