<?php

namespace App\Domain\Colis\Models;

use Illuminate\Database\Eloquent\Model;
use App\Driver;
use App\User;

class ShipmentReconciliation extends Model
{
    protected $fillable = [
        'courier_id',
        'admin_id',
        'amount_collected',
        'amount_reconciled',
        'shipment_ids', // JSON array of shipment IDs
        'notes',
        'status', // 'pending', 'completed'
    ];

    protected $casts = [
        'shipment_ids' => 'array',
    ];

    public function courier()
    {
        return $this->belongsTo(Driver::class, 'courier_id');
    }

    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id');
    }
}

