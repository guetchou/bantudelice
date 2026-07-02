<?php

namespace App\Domain\GePay\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class GePayPayoutDestination extends Model
{
    use SoftDeletes;

    protected $table = 'gepay_payout_destinations';

    protected $fillable = [
        'merchant_id',
        'label',
        'destination_type',
        'destination',
        'verified',
        'verified_by',
        'verified_at',
        'is_default',
    ];

    protected $hidden = ['destination'];

    protected $casts = [
        'destination' => 'encrypted',
        'verified' => 'boolean',
        'is_default' => 'boolean',
        'verified_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function merchant(): BelongsTo
    {
        return $this->belongsTo(GePayMerchant::class, 'merchant_id');
    }

    public function payoutRequests(): HasMany
    {
        return $this->hasMany(GePayPayoutRequest::class, 'payout_destination_id');
    }
}
