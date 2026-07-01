<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class FinancialLedgerEntry extends Model
{
    protected $fillable = [
        'uuid',
        'module',
        'account_type',
        'account_id',
        'entry_type',
        'direction',
        'status',
        'order_id',
        'order_no',
        'payment_id',
        'source_type',
        'source_id',
        'reference',
        'idempotency_key',
        'related_entry_id',
        'effective_at',
        'currency',
        'amount',
        'balance_before',
        'balance_after',
        'actor_type',
        'actor_id',
        'created_by',
        'payload',
    ];

    protected $casts = [
        'payload' => 'array',
        'effective_at' => 'datetime',
        'amount' => 'decimal:2',
        'balance_before' => 'decimal:2',
        'balance_after' => 'decimal:2',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $entry) {
            $entry->uuid ??= (string) Str::uuid();
            $entry->effective_at ??= now();
        });

        static::updating(function () {
            throw new \LogicException('Une écriture financière est immuable. Utilisez une contre-écriture.');
        });

        static::deleting(function () {
            throw new \LogicException('Une écriture financière ne peut pas être supprimée. Utilisez une contre-écriture.');
        });
    }

    public function relatedEntry()
    {
        return $this->belongsTo(self::class, 'related_entry_id');
    }
}
