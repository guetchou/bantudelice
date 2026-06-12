<?php

namespace App\Domain\Food\Enums;

enum OrderPaymentStatus: string
{
    case PENDING   = 'pending';
    case PAID      = 'paid';
    case FAILED    = 'failed';
    case REFUNDED  = 'refunded';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match($this) {
            self::PENDING   => 'En attente',
            self::PAID      => 'Payé',
            self::FAILED    => 'Échoué',
            self::REFUNDED  => 'Remboursé',
            self::CANCELLED => 'Annulé',
        };
    }

    public function isTerminal(): bool
    {
        return match($this) {
            self::PAID, self::REFUNDED, self::CANCELLED => true,
            default => false,
        };
    }
}
