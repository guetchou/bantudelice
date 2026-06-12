<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class FinancialLedgerEntry extends Model
{
    protected $fillable = [
        'module',
        'entry_type',
        'direction',
        'status',
        'order_id',
        'order_no',
        'payment_id',
        'reference',
        'currency',
        'amount',
        'balance_before',
        'balance_after',
        'actor_type',
        'actor_id',
        'payload',
    ];

    protected $casts = [
        'payload' => 'array',
        'amount' => 'decimal:2',
        'balance_before' => 'decimal:2',
        'balance_after' => 'decimal:2',
    ];
}
