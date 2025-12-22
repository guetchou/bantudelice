<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * Modèle pour l'historique des positions GPS des livreurs
 */
class DriverLocation extends Model
{
    protected $fillable = [
        'driver_id',
        'latitude',
        'longitude',
        'accuracy',
        'heading',
        'speed',
        'timestamp',
    ];

    protected $casts = [
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'accuracy' => 'decimal:2',
        'heading' => 'decimal:2',
        'speed' => 'decimal:2',
        'timestamp' => 'datetime',
    ];

    /**
     * Relation avec le livreur
     */
    public function driver()
    {
        return $this->belongsTo(Driver::class);
    }

    /**
     * Scope pour récupérer la dernière position d'un livreur
     */
    public function scopeLatestForDriver($query, $driverId)
    {
        return $query->where('driver_id', $driverId)
            ->orderBy('timestamp', 'desc')
            ->limit(1);
    }

    /**
     * Scope pour récupérer les positions récentes (dernières 30 minutes)
     */
    public function scopeRecent($query, $minutes = 30)
    {
        return $query->where('timestamp', '>=', now()->subMinutes($minutes));
    }
}

