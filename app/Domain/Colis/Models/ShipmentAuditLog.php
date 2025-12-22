<?php

namespace App\Domain\Colis\Models;

use App\User;
use Illuminate\Database\Eloquent\Model;

class ShipmentAuditLog extends Model
{
    protected $fillable = [
        'shipment_id', 'user_id', 'event', 'old_values', 'new_values', 'ip_address', 'user_agent'
    ];

    protected $casts = [
        'old_values' => 'json',
        'new_values' => 'json',
    ];

    public function shipment()
    {
        return $this->belongsTo(Shipment::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

