<?php

namespace App\Domain\Payment\ValueObjects;

/**
 * Statut renvoyé par un PSP lors d'un check ou d'un callback.
 */
final class GatewayStatus
{
    public const PAID = 'PAID';
    public const FAILED = 'FAILED';
    public const PENDING = 'PENDING';
    public const UNKNOWN = 'UNKNOWN';

    public function __construct(
        public readonly string $status,
        public readonly ?string $providerStatus = null,
        public readonly ?string $failureReason = null,
        public readonly ?string $failureAction = null,
        public readonly array $meta = [],
    ) {
    }

    public static function paid(array $meta = [], ?string $providerStatus = null): self
    {
        if (
            strtoupper((string) $providerStatus) === 'DEMO'
            && ! app()->environment(['local', 'testing'])
        ) {
            return self::unknown('DEMO_BLOCKED', array_merge($meta, [
                'error' => 'Statut de démonstration refusé hors environnement local ou testing.',
                'demo_blocked' => true,
            ]));
        }

        return new self(self::PAID, $providerStatus, null, null, $meta);
    }

    public static function failed(
        ?string $providerStatus = null,
        ?string $reason = null,
        ?string $action = null,
        array $meta = [],
    ): self {
        return new self(self::FAILED, $providerStatus, $reason, $action, $meta);
    }

    public static function pending(?string $providerStatus = null, array $meta = []): self
    {
        return new self(self::PENDING, $providerStatus, null, null, $meta);
    }

    public static function unknown(?string $providerStatus = null, array $meta = []): self
    {
        return new self(self::UNKNOWN, $providerStatus, null, null, $meta);
    }

    public function isPaid(): bool
    {
        return $this->status === self::PAID;
    }

    public function isFailed(): bool
    {
        return $this->status === self::FAILED;
    }

    public function isPending(): bool
    {
        return $this->status === self::PENDING;
    }

    public function isUnknown(): bool
    {
        return $this->status === self::UNKNOWN;
    }

    public function toArray(): array
    {
        return [
            'status' => $this->status,
            'provider_status' => $this->providerStatus,
            'failure_reason' => $this->failureReason,
            'failure_action' => $this->failureAction,
            'meta' => $this->meta,
        ];
    }
}
