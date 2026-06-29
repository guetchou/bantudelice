<?php

namespace App\Domain\Payment\Enums;

use InvalidArgumentException;

enum PaymentStatus: string
{
    case CREATED = 'created';
    case SUBMITTED = 'submitted';
    case PENDING = 'pending';
    case PROCESSING = 'processing';
    case SUCCESSFUL = 'successful';
    case FAILED = 'failed';
    case CANCELLED = 'cancelled';
    case EXPIRED = 'expired';
    case UNKNOWN = 'unknown';
    case REVERSED = 'reversed';
    case REFUNDED = 'refunded';
    case DISPUTED = 'disputed';

    public static function fromStorage(?string $status): self
    {
        return match (strtoupper(trim((string) $status))) {
            'CREATED' => self::CREATED,
            'SUBMITTED' => self::SUBMITTED,
            'PENDING' => self::PENDING,
            'AUTHORIZED', 'PROCESSING' => self::PROCESSING,
            'PAID', 'SUCCESS', 'SUCCESSFUL', 'COMPLETED', 'APPROVED' => self::SUCCESSFUL,
            'FAILED', 'REJECTED', 'DECLINED' => self::FAILED,
            'CANCELLED', 'CANCELED' => self::CANCELLED,
            'EXPIRED', 'TIMEOUT' => self::EXPIRED,
            'UNKNOWN', '' => self::UNKNOWN,
            'REVERSED', 'REVERSAL', 'ROLLED_BACK' => self::REVERSED,
            'REFUNDED', 'PARTIALLY_REFUNDED' => self::REFUNDED,
            'DISPUTED', 'CHARGEBACK' => self::DISPUTED,
            default => throw new InvalidArgumentException('Statut de paiement non reconnu : ' . $status),
        };
    }

    public function storageValue(): string
    {
        return match ($this) {
            self::SUCCESSFUL => 'PAID',
            default => strtoupper($this->value),
        };
    }

    public function isFinanciallyConfirmed(): bool
    {
        return $this === self::SUCCESSFUL;
    }

    public function requiresManualReview(): bool
    {
        return in_array($this, [
            self::UNKNOWN,
            self::REVERSED,
            self::DISPUTED,
        ], true);
    }

    public function canTransitionTo(self $target): bool
    {
        if ($target === $this) {
            return true;
        }

        return in_array($target, $this->allowedTransitions(), true);
    }

    public function allowedTransitions(): array
    {
        return match ($this) {
            self::CREATED => [
                self::SUBMITTED,
                self::CANCELLED,
            ],
            self::SUBMITTED => [
                self::PENDING,
                self::PROCESSING,
                self::SUCCESSFUL,
                self::FAILED,
                self::UNKNOWN,
                self::CANCELLED,
                self::EXPIRED,
            ],
            self::PENDING,
            self::PROCESSING => [
                self::SUCCESSFUL,
                self::FAILED,
                self::UNKNOWN,
                self::CANCELLED,
                self::EXPIRED,
            ],
            self::UNKNOWN => [
                self::PENDING,
                self::PROCESSING,
                self::SUCCESSFUL,
                self::FAILED,
                self::REVERSED,
                self::REFUNDED,
                self::DISPUTED,
            ],
            self::SUCCESSFUL => [
                self::REVERSED,
                self::REFUNDED,
                self::DISPUTED,
            ],
            self::FAILED,
            self::CANCELLED,
            self::EXPIRED => [
                self::UNKNOWN,
                self::SUCCESSFUL,
            ],
            self::DISPUTED => [
                self::SUCCESSFUL,
                self::REVERSED,
                self::REFUNDED,
            ],
            self::REVERSED,
            self::REFUNDED => [],
        };
    }
}
