<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class DriverPayment extends Model
{
    // Seuls les champs d'initialisation sont mass-assignables.
    // status, transaction_id et paid_at sont définis côté serveur uniquement.
    protected $fillable = [
        'driver_id',
        'payout_amount',
    ];

    public function driver()
    {
        return $this->belongsTo(Driver::class);
    }
}
