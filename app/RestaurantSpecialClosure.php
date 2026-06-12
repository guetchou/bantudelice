<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class RestaurantSpecialClosure extends Model
{
    protected $fillable = [
        'restaurant_id',
        'label',
        'starts_on',
        'ends_on',
        'notes',
    ];

    protected $casts = [
        'starts_on' => 'date',
        'ends_on' => 'date',
    ];

    public function restaurant()
    {
        return $this->belongsTo(Restaurant::class);
    }
}
