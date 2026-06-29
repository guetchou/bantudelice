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
        'entry_key',
        'owner_type',
        'owner_id',
        'account_code',
        'source_type',
        'source_id',
        'withdrawal_id',
        'reversal_of_id',
        'effective_at',
        'metadata',
        'created_by',
    ];

    protected $casts = [
        'payload' => 'array',
        'metadata' => 'array',
        'amount' => 'decimal:2',
        'balance_before' => 'decimal:2',
        'balance_after' => 'decimal:2',
        'effective_at' => 'datetime',
    ];

    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }

    public function withdrawal()
    {
        return $this->belongsTo(PartnerWithdrawal::class, 'withdrawal_id');
    }

    public function reversalOf()
    {
        return $this->belongsTo(self::class, 'reversal_of_id');
    }
}
