<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Delivery extends Model
{
    protected $fillable = [
        'order_id',
        'restaurant_id',
        'driver_id',
        'status',
        'delivery_fee',
        'assigned_at',
        'picked_up_at',
        'delivered_at',
    ];
    
    protected $casts = [
        'assigned_at' => 'datetime',
        'picked_up_at' => 'datetime',
        'delivered_at' => 'datetime',
    ];
    
    public function order()
    {
        return $this->belongsTo(Order::class);
    }
    
    public function restaurant()
    {
        return $this->belongsTo(Restaurant::class);
    }
    
    public function driver()
    {
        return $this->belongsTo(Driver::class);
    }
    
    /**
     * Vérifier si la livraison est en cours
     */
    public function isInProgress(): bool
    {
        return in_array($this->status, ['ASSIGNED', 'PICKED_UP', 'ON_THE_WAY']);
    }
    
    /**
     * Vérifier si la livraison est terminée
     */
    public function isCompleted(): bool
    {
        return $this->status === 'DELIVERED';
    }
    
    /**
     * Vérifier si la livraison est annulée
     */
    public function isCancelled(): bool
    {
        return $this->status === 'CANCELLED';
    }
}

