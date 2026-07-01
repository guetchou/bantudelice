<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Payment extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'order_id',
        'shipment_id',
        'transport_booking_id',
        'provider',
        'provider_reference',
        'idempotency_key',
        'status',
        'financial_state',
        'financial_state_changed_at',
        'amount',
        'currency',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
        'financial_state_changed_at' => 'datetime',
    ];

    public function setStatusAttribute($value): void
    {
        $normalized = strtoupper(trim((string) $value));

        if (in_array($normalized, ['REVERSED', 'REVERSAL', 'ROLLED_BACK'], true)) {
            $this->attributes['financial_state'] = 'reversed';
            $this->attributes['financial_state_changed_at'] = now();
            $this->attributes['status'] ??= 'PENDING';
            return;
        }

        if (in_array($normalized, ['DISPUTED', 'CHARGEBACK'], true)) {
            $this->attributes['financial_state'] = 'disputed';
            $this->attributes['financial_state_changed_at'] = now();
            $this->attributes['status'] ??= 'PENDING';
            return;
        }

        $this->attributes['status'] = $normalized;
        $this->attributes['financial_state'] = match ($normalized) {
            'PAID', 'SUCCESS', 'SUCCESSFUL' => 'confirmed',
            'FAILED', 'REJECTED', 'DECLINED' => 'failed',
            'CANCELLED', 'CANCELED' => 'cancelled',
            'EXPIRED', 'TIMEOUT' => 'expired',
            'REFUNDED', 'PARTIALLY_REFUNDED' => 'refunded',
            default => 'pending',
        };
        $this->attributes['financial_state_changed_at'] = now();
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function shipment()
    {
        return $this->belongsTo(\App\Domain\Colis\Models\Shipment::class);
    }

    public function transportBooking()
    {
        return $this->belongsTo(\App\Domain\Transport\Models\TransportBooking::class, 'transport_booking_id');
    }

    public function allocations()
    {
        return $this->hasMany(PaymentAllocation::class);
    }

    public function reconciliationCases()
    {
        return $this->hasMany(PaymentReconciliationCase::class);
    }

    public function ledgerEntries()
    {
        return $this->hasMany(FinancialLedgerEntry::class);
    }

    public function isPaid(): bool
    {
        return strtoupper((string) $this->status) === 'PAID';
    }

    public function isPending(): bool
    {
        return strtoupper((string) $this->status) === 'PENDING';
    }

    public function isFailed(): bool
    {
        return strtoupper((string) $this->status) === 'FAILED';
    }

    public function financialState(): string
    {
        $state = strtolower(trim((string) $this->financial_state));

        if ($state !== '') {
            return $state;
        }

        return match (strtoupper((string) $this->status)) {
            'PAID', 'SUCCESS', 'SUCCESSFUL' => 'confirmed',
            'FAILED', 'REJECTED', 'DECLINED' => 'failed',
            'CANCELLED', 'CANCELED' => 'cancelled',
            default => 'pending',
        };
    }
}
