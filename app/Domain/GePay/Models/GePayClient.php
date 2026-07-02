<?php

namespace App\Domain\GePay\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GePayClient extends Model
{
    protected $table = 'gepay_clients';

    protected $fillable = [
        'uuid',
        'merchant_id',
        'name',
        'api_key',
        'api_secret',
        'capabilities',
        'allowed_ips',
        'webhook_url',
        'webhook_secret',
        'is_active',
    ];

    protected $hidden = ['api_secret', 'webhook_secret'];

    protected $casts = [
        'api_secret' => 'encrypted',
        'webhook_secret' => 'encrypted',
        'capabilities' => 'array',
        'allowed_ips' => 'array',
        'is_active' => 'boolean',
    ];

    public function merchant(): BelongsTo
    {
        return $this->belongsTo(GePayMerchant::class, 'merchant_id');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(GePayTransaction::class, 'client_id');
    }

    public function can(string $capability): bool
    {
        return in_array($capability, $this->capabilities ?? [], true);
    }
}
