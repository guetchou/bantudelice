<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class FoodOrderHeader extends Model
{
    protected $fillable = [
        'order_no',
        'restaurant_id',
        'user_id',
        'primary_order_id',
        'items_count',
        'total_quantity',
        'total',
        'currency',
        'fulfillment_mode',
        'business_status',
        'payment_status',
        'scheduled_at',
        'source_created_at',
    ];

    protected $casts = [
        'total' => 'decimal:2',
        'scheduled_at' => 'datetime',
        'source_created_at' => 'datetime',
    ];

    public function primaryOrder()
    {
        return $this->belongsTo(Order::class, 'primary_order_id');
    }
}
