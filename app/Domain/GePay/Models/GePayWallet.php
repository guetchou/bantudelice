<?php

namespace App\Domain\GePay\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GePayWallet extends Model
{
    protected $table = 'gepay_wallets';

    public $timestamps = false;

    protected $fillable = [
        'merchant_id',
        'currency',
        'available',
        'pending',
        'reserved',
    ];

    protected $casts = [
        'available' => 'integer',
        'pending' => 'integer',
        'reserved' => 'integer',
        'updated_at' => 'datetime',
    ];

    public function merchant(): BelongsTo
    {
        return $this->belongsTo(GePayMerchant::class, 'merchant_id');
    }

    public function ledgerEntries(): HasMany
    {
        return $this->hasMany(GePayLedgerEntry::class, 'wallet_id');
    }
}
