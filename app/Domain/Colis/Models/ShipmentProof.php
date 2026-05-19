<?php

namespace App\Domain\Colis\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShipmentProof extends Model
{
    use HasFactory;

    protected $fillable = [
        'shipment_id',
        'type',
        'storage_path',
        'hash',
    ];

    public function shipment(): BelongsTo
    {
        return $this->belongsTo(Shipment::class);
    }
}

