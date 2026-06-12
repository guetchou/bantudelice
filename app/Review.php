<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
protected $fillable=[
'driver_id',
'user_id',
'order_id',
'rating',
'reviews',
];

public function driver()
{
    return $this->belongsTo(Driver::class);
}

public function user()
{
    return $this->belongsTo(User::class);
}

public function order()
{
    return $this->belongsTo(Order::class);
}
}
