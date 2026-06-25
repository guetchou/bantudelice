<?php

namespace App\Domain\Food\Enums;

enum OrderPaymentStatus: string
{
    case NOT_STARTED = 'not_started';
    case PENDING = 'pending';
    case CASH_DUE = 'cash_due';
    case PAID = 'paid';
    case FAILED = 'failed';
    case EXPIRED = 'expired';
    case REFUNDED = 'refunded';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::NOT_STARTED => 'Non démarré',
            self::PENDING => 'En attente',
            self::CASH_DUE => 'À encaisser',
            self::PAID => 'Payé',
            self::FAILED => 'Échoué',
            self::EXPIRED => 'Expiré',
            self::REFUNDED => 'Remboursé',
            self::CANCELLED => 'Annulé',
        };
    }

    public function isTerminal(): bool
    {
        return match ($this) {
            self::PAID, self::REFUNDED, self::CANCELLED => true,
            default => false,
        };
    }
}
