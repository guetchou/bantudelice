<?php

namespace App\Domain\Payment\Enums;

enum PaymentStatus: string
{
    case INITIATED = 'INITIATED';
    case PENDING = 'PENDING';
    case PROCESSING = 'PROCESSING';
    case PAID = 'PAID';
    case FAILED = 'FAILED';
    case CANCELLED = 'CANCELLED';
    case EXPIRED = 'EXPIRED';
    case REFUNDED = 'REFUNDED';
    case UNKNOWN = 'UNKNOWN';
    case REVERSED = 'REVERSED';
    case DISPUTED = 'DISPUTED';

    public static function fromRaw(?string $status): self
    {
        return match (strtoupper(trim((string) $status))) {
            'INITIATED', 'CREATED' => self::INITIATED,
            'PENDING' => self::PENDING,
            'AUTHORIZED', 'PROCESSING', 'SUBMITTED' => self::PROCESSING,
            'PAID', 'SUCCESS', 'SUCCESSFUL', 'COMPLETED', 'CAPTURED', 'APPROVED' => self::PAID,
            'FAILED', 'REJECTED', 'DECLINED' => self::FAILED,
            'CANCELLED', 'CANCELED' => self::CANCELLED,
            'EXPIRED', 'TIMEOUT' => self::EXPIRED,
            'REFUNDED', 'PARTIALLY_REFUNDED' => self::REFUNDED,
            'REVERSED', 'REVERSAL', 'ROLLED_BACK' => self::REVERSED,
            'DISPUTED', 'CHARGEBACK' => self::DISPUTED,
            default => self::UNKNOWN,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::INITIATED => 'Initialisé',
            self::PENDING => 'En attente',
            self::PROCESSING => 'En traitement',
            self::PAID => 'Payé',
            self::FAILED => 'Échoué',
            self::CANCELLED => 'Annulé',
            self::EXPIRED => 'Expiré',
            self::REFUNDED => 'Remboursé',
            self::UNKNOWN => 'Inconnu',
            self::REVERSED => 'Inversé',
            self::DISPUTED => 'Contesté',
        };
    }

    public function isSuccessful(): bool
    {
        return $this === self::PAID;
    }

    public function isUnresolved(): bool
    {
        return in_array($this, [self::INITIATED, self::PENDING, self::PROCESSING, self::UNKNOWN], true);
    }

    public function isTerminal(): bool
    {
        return in_array($this, [
            self::PAID,
            self::FAILED,
            self::CANCELLED,
            self::EXPIRED,
            self::REFUNDED,
            self::REVERSED,
        ], true);
    }

    public function isReconcilable(): bool
    {
        return in_array($this, [
            self::INITIATED,
            self::PENDING,
            self::PROCESSING,
            self::UNKNOWN,
            self::PAID,
            self::FAILED,
            self::DISPUTED,
        ], true);
    }

    public function canTransitionTo(self $target): bool
    {
        if ($this === $target) {
            return true;
        }

        return in_array($target, match ($this) {
            self::INITIATED => [self::PENDING, self::PROCESSING, self::PAID, self::FAILED, self::CANCELLED, self::EXPIRED, self::UNKNOWN],
            self::PENDING => [self::PROCESSING, self::PAID, self::FAILED, self::CANCELLED, self::EXPIRED, self::UNKNOWN],
            self::PROCESSING => [self::PENDING, self::PAID, self::FAILED, self::EXPIRED, self::UNKNOWN],
            self::UNKNOWN => [self::PENDING, self::PROCESSING, self::PAID, self::FAILED, self::EXPIRED, self::REVERSED],
            self::PAID => [self::REFUNDED, self::REVERSED, self::DISPUTED],
            self::DISPUTED => [self::PAID, self::REFUNDED, self::REVERSED],
            self::FAILED, self::CANCELLED, self::EXPIRED, self::REFUNDED, self::REVERSED => [],
        }, true);
    }
}
