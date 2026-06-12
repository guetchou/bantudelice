<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Charge extends Model
{
    protected $fillable=['delivery_fee','tax','service_fee','pickup_fee'];
    
}
