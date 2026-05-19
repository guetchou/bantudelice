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
        'pickup_notes',
        'delivery_notes',
        'pickup_proof_path',
        'delivery_proof_path',
        'delivery_otp_code',
        'delivery_otp_expires_at',
        'otp_verified_at',
        'delivery_confirmation_method',
        'pickup_latitude',
        'pickup_longitude',
        'delivery_latitude',
        'delivery_longitude',
        'customer_confirmed_at',
        'cash_collected_at',
        'incident_status',
        'incident_reason',
        'incident_notes',
        'incident_reported_by',
        'incident_reported_by_id',
        'incident_reported_at',
        'failed_attempts',
        'last_failed_attempt_at',
        'customer_absent_at',
        'redelivery_requested_at',
        'support_status',
        'support_notes',
        'support_resolved_at',
        'support_resolved_by',
    ];
    
    protected $casts = [
        'assigned_at' => 'datetime',
        'picked_up_at' => 'datetime',
        'delivered_at' => 'datetime',
        'delivery_otp_expires_at' => 'datetime',
        'otp_verified_at' => 'datetime',
        'customer_confirmed_at' => 'datetime',
        'cash_collected_at' => 'datetime',
        'incident_reported_at' => 'datetime',
        'last_failed_attempt_at' => 'datetime',
        'customer_absent_at' => 'datetime',
        'redelivery_requested_at' => 'datetime',
        'support_resolved_at' => 'datetime',
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

    public function requiresOtp(): bool
    {
        return !empty($this->delivery_otp_code) && !$this->otp_verified_at;
    }

    public function hasOpenIncident(): bool
    {
        return $this->incident_status === 'open';
    }
}
