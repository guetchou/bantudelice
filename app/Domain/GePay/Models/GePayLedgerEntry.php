<?php

namespace App\Domain\GePay\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use RuntimeException;

class GePayLedgerEntry extends Model
{
    protected $table = 'gepay_ledger_entries';

    public $timestamps = false;

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
        'created_at' => 'datetime',
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

        return parent::save($options);
    }

    public function delete(): bool|null
    {
        throw new RuntimeException('GePayLedgerEntry is immutable — DELETE is forbidden.');
    }
}
