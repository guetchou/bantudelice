<?php

namespace App\Domain\Colis\Models;

use Illuminate\Database\Eloquent\Model;
use App\User;

class ShipmentIncident extends Model
{
    protected $fillable = [
        'shipment_id',
        'reported_by',
        'type', // 'damage', 'loss', 'theft', 'other'
        'description',
        'resolution_notes',
        'resolved_at',
        'photos', // JSON array
    ];

    protected $casts = [
        'photos' => 'array',
        'resolved_at' => 'datetime',
    ];

    public function shipment()
    {
        return $this->belongsTo(Shipment::class);
    }

    public function reporter()
    {
        return $this->belongsTo(User::class, 'reported_by');
    }
}

