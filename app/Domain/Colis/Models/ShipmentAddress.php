<?php

namespace App\Domain\Colis\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShipmentAddress extends Model
{
    use HasFactory;

    protected $fillable = [
        'shipment_id',
        'type',
        'full_name',
        'phone',
        'city',
        'district',
        'address_line',
        'landmark',
        'lat',
        'lng',
    ];

    public function shipment(): BelongsTo
    {
        return $this->belongsTo(Shipment::class);
    }

    protected static function newFactory()
    {
        return \Database\Factories\ShipmentAddressFactory::new();
    }
}

