<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Address extends Model
{
	protected $table='user_address';
    protected $fillable=['title','user_id','building_no','street_no','area','floor','latitude','longitude','complete_address','is_default'];

    protected $casts = [
        'is_default' => 'boolean',
        'latitude' => 'float',
        'longitude' => 'float',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
   
}
