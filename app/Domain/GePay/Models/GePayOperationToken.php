<?php

namespace App\Domain\GePay\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GePayOperationToken extends Model
{
    protected $table = 'gepay_operation_tokens';

    public $timestamps = false;

    protected $fillable = [
        'token',
        'merchant_id',
        'user_id',
        'operation_type',
        'request_hash',
        'operation_ref',
        'expires_at',
        'used_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'created_at' => 'datetime',
        'used_at' => 'datetime',
    ];

    public function merchant(): BelongsTo
    {
        return $this->belongsTo(GePayMerchant::class, 'merchant_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(GePayMerchantUser::class, 'user_id');
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function isUsed(): bool
    {
        return $this->used_at !== null;
    }

    /** True only on first (pristine) use — token not yet consumed, not expired. */
    public function isValid(): bool
    {
        return ! $this->isExpired() && ! $this->isUsed();
    }

    /**
     * True on first use OR on replay (same request_hash, token used but not expired).
     * Replay allows idempotent retries without re-executing the operation.
     */
    public function isValidForRequest(string $requestHash): bool
    {
        if ($this->isExpired()) {
            return false;
        }

        if ($this->isUsed()) {
            // Replay: same hash → safe to return cached result
            return $this->request_hash === $requestHash;
        }

        return true; // first use
    }

    public function isReplay(string $requestHash): bool
    {
        return $this->isUsed()
            && ! $this->isExpired()
            && $this->request_hash === $requestHash;
    }
}
