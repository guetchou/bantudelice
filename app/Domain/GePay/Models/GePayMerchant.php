<?php

namespace App\Domain\GePay\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class GePayMerchant extends Model
{
    use SoftDeletes;

    protected $table = 'gepay_merchants';

    protected $fillable = [
        'ulid',
        'name',
        'slug',
        'country',
        'email',
        'status',
        'portal_client_id',
    ];

    protected $casts = [
        'deleted_at' => 'datetime',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(GePayMerchantUser::class, 'merchant_id');
    }

    public function portalClient(): BelongsTo
    {
        return $this->belongsTo(GePayClient::class, 'portal_client_id');
    }

    public function wallets(): HasMany
    {
        return $this->hasMany(GePayWallet::class, 'merchant_id');
    }

    public function payoutDestinations(): HasMany
    {
        return $this->hasMany(GePayPayoutDestination::class, 'merchant_id');
    }

    public function ledgerEntries(): HasMany
    {
        return $this->hasMany(GePayLedgerEntry::class, 'merchant_id');
    }

    public function payoutRequests(): HasMany
    {
        return $this->hasMany(GePayPayoutRequest::class, 'merchant_id');
    }

    public function wallet(string $currency = 'XAF'): ?GePayWallet
    {
        return $this->wallets()->where('currency', $currency)->first();
    }
}
