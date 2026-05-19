<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class VoucherRedemption extends Model
{
    protected $fillable = [
        'voucher_id',
        'voucher_code',
        'user_id',
        'restaurant_id',
        'order_id',
        'order_no',
        'subtotal',
        'discount_amount',
        'discount_type',
        'discount_rate',
        'discount_cap',
        'idempotency_key',
        'status',
        'redeemed_at',
        'released_at',
        'details',
        'payload',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'discount_rate' => 'decimal:2',
        'discount_cap' => 'decimal:2',
        'redeemed_at' => 'datetime',
        'released_at' => 'datetime',
        'details' => 'array',
        'payload' => 'array',
    ];

    public function voucher()
    {
        return $this->belongsTo(Voucher::class);
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
