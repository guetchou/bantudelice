<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Rating extends Model
{
    protected $fillable = [
        'restaurant_id',
        'user_id',
        'order_id',
        'rating',
        'reviews',
    ];
    
    public function restaurant()
    {
        return $this->belongsTo(Restaurant::class);
    }
    
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    
    public function order()
    {
        return $this->belongsTo(Order::class);
    }
    
    // Alias pour compatibilité avec l'ancien code
    public function restaurants()
    {
        return $this->belongsTo(Restaurant::class);
    }
}
