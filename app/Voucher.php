<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Voucher extends Model
{
    protected $fillable = [
        'restaurant_id',
        'name',
        'discount',
        'discount_type',
        'discount_value',
        'min_order_amount',
        'max_discount_amount',
        'usage_limit',
        'used_count',
        'per_user_limit',
        'stackable',
        'is_active',
        'start_date',
        'end_date',
        'starts_at',
        'ends_at',
        'rules',
    ];

    protected $casts = [
        'discount_value' => 'decimal:2',
        'min_order_amount' => 'decimal:2',
        'max_discount_amount' => 'decimal:2',
        'used_count' => 'integer',
        'usage_limit' => 'integer',
        'per_user_limit' => 'integer',
        'stackable' => 'boolean',
        'is_active' => 'boolean',
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'rules' => 'array',
    ];

    public function restaurant()
    {
        return $this->belongsTo(Restaurant::class);
    }

    public function redemptions()
    {
        return $this->hasMany(VoucherRedemption::class);
    }

    public function getCodeAttribute(): ?string
    {
        return $this->attributes['name'] ?? null;
    }

    public function setCodeAttribute($value): void
    {
        $this->attributes['name'] = $value;
    }

    public function scopeActive($query)
    {
        return $query->where(function ($builder) {
            $builder->whereNull('is_active')
                ->orWhere('is_active', true);
        });
    }
}
