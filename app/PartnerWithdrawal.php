<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class PartnerWithdrawal extends Model
{
    protected $table = 'partner_withdrawals';

    protected $fillable = [
        'uuid', 'partner_type', 'partner_id', 'operator', 'provider', 'phone',
        'requested_amount', 'fee_amount', 'net_amount', 'currency', 'status',
        'external_reference', 'idempotency_key', 'provider_reference',
        'failure_code', 'failure_message', 'source',
        'initiated_at', 'paid_at', 'failed_at', 'reconciled_at', 'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
        'initiated_at' => 'datetime',
        'paid_at' => 'datetime',
        'failed_at' => 'datetime',
        'reconciled_at' => 'datetime',
    ];

    public const TERMINAL_SUCCESS = ['SUCCESSFUL', 'SUCCESS', 'PAID', 'COMPLETED', 'APPROVED'];
    public const TERMINAL_FAILURE = ['FAILED', 'REJECTED', 'DECLINED', 'CANCELLED', 'EXPIRED'];

    private const TRANSITIONS = [
        'created' => ['reserved', 'cancelled', 'failed'],
        'reserved' => ['submitted', 'pending', 'paid', 'failed', 'unknown', 'cancelled'],
        'submitted' => ['pending', 'paid', 'failed', 'unknown', 'reversed', 'cancelled'],
        'pending' => ['paid', 'failed', 'unknown', 'reversed', 'cancelled'],
        'unknown' => ['pending', 'paid', 'failed', 'reversed', 'cancelled'],
        'paid' => ['reversed'],
        'failed' => [],
        'reversed' => [],
        'cancelled' => [],
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });

        static::updating(function (self $model) {
            if (!$model->isDirty('status')) {
                return;
            }

            $from = (string) $model->getOriginal('status');
            $to = (string) $model->status;

            if ($from === $to) {
                return;
            }

            if (!in_array($to, self::TRANSITIONS[$from] ?? [], true)) {
                throw new \DomainException("Transition retrait interdite : {$from} → {$to}.");
            }
        });
    }

    public function reference(): string
    {
        return 'WD-' . strtoupper(substr($this->uuid, 0, 8));
    }

    public function maskedPhone(): string
    {
        $digits = preg_replace('/\D/', '', $this->phone);
        $len = strlen($digits);

        if ($len < 4) {
            return str_repeat('•', $len);
        }

        return substr($digits, 0, 2) . str_repeat('•', max(0, $len - 4)) . substr($digits, -2);
    }

    public function canTransitionTo(string $status): bool
    {
        return $this->status === $status
            || in_array($status, self::TRANSITIONS[$this->status] ?? [], true);
    }

    public function isPending(): bool
    {
        return in_array($this->status, ['created', 'reserved', 'submitted', 'pending', 'unknown'], true);
    }

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    public function isFailed(): bool
    {
        return in_array($this->status, ['failed', 'reversed', 'cancelled'], true);
    }

    public function isUnknown(): bool
    {
        return $this->status === 'unknown';
    }
}
