<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class PaymentRefund extends Model
{
    protected $fillable = [
        'uuid',
        'payment_id',
        'amount',
        'currency',
        'status',
        'reason',
        'provider_reference',
        'idempotency_key',
        'requested_by',
        'approved_by',
        'requested_at',
        'approved_at',
        'submitted_at',
        'refunded_at',
        'failed_at',
        'metadata',
    ];

    protected $casts = [
        'requested_at' => 'datetime',
        'approved_at' => 'datetime',
        'submitted_at' => 'datetime',
        'refunded_at' => 'datetime',
        'failed_at' => 'datetime',
        'metadata' => 'array',
    ];

    private const TRANSITIONS = [
        'requested' => ['approved', 'cancelled'],
        'approved' => ['submitted', 'cancelled'],
        'submitted' => ['pending', 'refunded', 'failed', 'unknown'],
        'pending' => ['refunded', 'failed', 'unknown'],
        'unknown' => ['pending', 'refunded', 'failed', 'reversed'],
        'refunded' => ['reversed'],
        'failed' => [],
        'cancelled' => [],
        'reversed' => [],
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $refund) {
            $refund->uuid ??= (string) Str::uuid();
            $refund->requested_at ??= now();
        });

        static::updating(function (self $refund) {
            if (!$refund->isDirty('status')) {
                return;
            }

            $from = (string) $refund->getOriginal('status');
            $to = (string) $refund->status;

            if ($from !== $to && !in_array($to, self::TRANSITIONS[$from] ?? [], true)) {
                throw new \DomainException("Transition remboursement interdite : {$from} → {$to}.");
            }
        });
    }

    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }

    public function isOpen(): bool
    {
        return in_array($this->status, ['requested', 'approved', 'submitted', 'pending', 'unknown'], true);
    }

    public function canTransitionTo(string $status): bool
    {
        return $this->status === $status
            || in_array($status, self::TRANSITIONS[$this->status] ?? [], true);
    }
}
