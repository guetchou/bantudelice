<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class WorkingHour extends Model
{
    protected $fillable=['restaurant_id','Day','opening_time','closing_time'];

    public function restaurant()
    {
        return $this->belongsTo(Restaurant::class);
    }
}
