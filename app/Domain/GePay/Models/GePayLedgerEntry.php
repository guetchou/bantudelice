<?php

namespace App\Domain\GePay\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use RuntimeException;

class GePayLedgerEntry extends Model
{
    protected $table = 'gepay_ledger_entries';

    // created_at set by Eloquent on insert; no updated_at column exists
    const UPDATED_AT = null;

    public const TYPES = [
        'collection_pending', 'collection_confirm', 'collection_fail',
        'disbursement_debit', 'disbursement_refund',
        'payout_reserve', 'payout_release', 'payout_debit',
        'fee_debit', 'adjustment_credit', 'adjustment_debit',
    ];

    public const BUCKETS = ['available', 'pending', 'reserved'];

    protected $fillable = [
        'merchant_id',
        'wallet_id',
        'type',
        'amount',
        'source_bucket',
        'destination_bucket',
        'reference_type',
        'reference_id',
        'idempotency_key',
        'metadata',
        'note',
    ];

    protected $casts = [
        'amount' => 'integer',
        'metadata' => 'array',
    ];

    public function merchant(): BelongsTo
    {
        return $this->belongsTo(GePayMerchant::class, 'merchant_id');
    }

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(GePayWallet::class, 'wallet_id');
    }

    public function save(array $options = []): bool
    {
        if ($this->exists) {
            throw new RuntimeException('GePayLedgerEntry is immutable — UPDATE is forbidden.');
        }

        $amount = $this->getAttribute('amount');
        if (! is_int($amount) || $amount <= 0) {
            throw new \InvalidArgumentException(
                "GePayLedgerEntry amount must be a strictly positive integer, got: {$amount}"
            );
        }

        return parent::save($options);
    }

    public function delete(): bool|null
    {
        throw new RuntimeException('GePayLedgerEntry is immutable — DELETE is forbidden.');
    }
}
