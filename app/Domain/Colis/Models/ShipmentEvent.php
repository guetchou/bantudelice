<?php

namespace App\Domain\Colis\Models;

use App\Domain\Colis\Enums\ShipmentStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShipmentEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'shipment_id',
        'status',
        'actor_type',
        'actor_id',
        'notes',
        'meta',
    ];

    protected $casts = [
        'status' => ShipmentStatus::class,
        'meta' => 'array',
    ];

    public function shipment(): BelongsTo
    {
        return $this->belongsTo(Shipment::class);
    }

    protected static function newFactory()
    {
        return \Database\Factories\ShipmentEventFactory::new();
    }
}

