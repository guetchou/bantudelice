<?php

namespace App\Domain\Transport\Models;

use App\Domain\Transport\Enums\TransportType;
use Illuminate\Database\Eloquent\Model;

class TransportPricingRule extends Model
{
    protected $fillable = [
        'type', 'zone', 'base_fare', 'price_per_km', 'price_per_minute',
        'minimum_fare', 'surge_multiplier', 'is_active'
    ];

    protected $casts = [
        'type' => TransportType::class,
        'base_fare' => 'decimal:2',
        'price_per_km' => 'decimal:2',
        'price_per_minute' => 'decimal:2',
        'minimum_fare' => 'decimal:2',
        'surge_multiplier' => 'decimal:2',
        'is_active' => 'boolean',
    ];
}

