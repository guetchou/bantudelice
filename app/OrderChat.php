<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class OrderChat extends Model
{
    protected $fillable = [
        'order_id',
        'order_no',
        'customer_user_id',
        'restaurant_user_id',
        'driver_id',
        'last_message_at',
        'last_message_by_role',
        'customer_last_read_at',
        'restaurant_last_read_at',
        'driver_last_read_at',
        'admin_last_read_at',
    ];

    protected $casts = [
        'last_message_at' => 'datetime',
        'customer_last_read_at' => 'datetime',
        'restaurant_last_read_at' => 'datetime',
        'driver_last_read_at' => 'datetime',
        'admin_last_read_at' => 'datetime',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function customer()
    {
        return $this->belongsTo(User::class, 'customer_user_id');
    }

    public function restaurantUser()
    {
        return $this->belongsTo(User::class, 'restaurant_user_id');
    }

    public function driver()
    {
        return $this->belongsTo(Driver::class);
    }

    public function messages()
    {
        return $this->hasMany(OrderChatMessage::class);
    }
}
