<?php

namespace App\Domain\GePay\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GePayPayoutRequest extends Model
{
    protected $table = 'gepay_payout_requests';

    protected $fillable = [
        'merchant_id',
        'wallet_id',
        'payout_destination_id',
        'amount',
        'currency',
        'destination_snapshot',
        'status',
        'idempotency_key',
        'execution_transaction_id',
        'processed_by',
        'operator_reference',
        'rejection_reason',
        'processed_at',
        'expires_at',
    ];

    protected $hidden = ['destination_snapshot'];

    protected $casts = [
        'destination_snapshot' => 'encrypted',
        'amount' => 'integer',
        'processed_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function merchant(): BelongsTo
    {
        return $this->belongsTo(GePayMerchant::class, 'merchant_id');
    }

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(GePayWallet::class, 'wallet_id');
    }

    public function destination(): BelongsTo
    {
        return $this->belongsTo(GePayPayoutDestination::class, 'payout_destination_id');
    }

    public function executionTransaction(): BelongsTo
    {
        return $this->belongsTo(GePayTransaction::class, 'execution_transaction_id');
    }

    public function isTerminal(): bool
    {
        return in_array($this->status, ['successful', 'failed', 'cancelled', 'expired'], true);
    }
}
