<?php

namespace App\Domain\GePay\Models;

use App\Domain\GePay\Enums\TransactionStatus;
use App\Domain\GePay\Enums\TransactionType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GePayTransaction extends Model
{
    protected $table = 'gepay_transactions';

    protected $fillable = [
        'uuid',
        'client_id',
        'type',
        'provider',
        'external_reference',
        'provider_reference',
        'idempotency_key',
        'request_hash',
        'amount',
        'currency',
        'phone',
        'phone_masked',
        'status',
        'failure_code',
        'failure_message',
        'metadata',
        'provider_metadata',
        'submitted_at',
        'completed_at',
        'last_checked_at',
    ];

    protected $hidden = ['phone', 'provider_metadata'];

    protected $casts = [
        'type' => TransactionType::class,
        'status' => TransactionStatus::class,
        'phone' => 'encrypted',
        'metadata' => 'array',
        'provider_metadata' => 'encrypted:array',
        'submitted_at' => 'datetime',
        'completed_at' => 'datetime',
        'last_checked_at' => 'datetime',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(GePayClient::class, 'client_id');
    }
}
