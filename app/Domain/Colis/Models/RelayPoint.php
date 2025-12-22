<?php

namespace App\Domain\Colis\Models;

use Illuminate\Database\Eloquent\Model;

class RelayPoint extends Model
{
    protected $fillable = [
        'name',
        'city',
        'district',
        'address',
        'lat',
        'lng',
        'opening_hours',
        'contact_phone',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}

