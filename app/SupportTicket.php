<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SupportTicket extends Model
{
    protected $fillable = [
        'module',
        'category',
        'priority',
        'status',
        'title',
        'description',
        'subject_type',
        'subject_id',
        'order_id',
        'order_no',
        'payment_id',
        'delivery_id',
        'shipment_id',
        'transport_booking_id',
        'opened_by_type',
        'opened_by_id',
        'assigned_to_id',
        'assigned_to_type',
        'last_activity_at',
        'resolved_at',
        'resolution_notes',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
        'last_activity_at' => 'datetime',
        'resolved_at' => 'datetime',
    ];

    public function scopeOpen($query)
    {
        return $query->whereIn('status', config('commerce.support.open_statuses', ['open']));
    }
}
