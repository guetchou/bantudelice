<?php

namespace App\Domain\GePay\Enums;

enum TransactionStatus: string
{
    case CREATED = 'created';
    case SUBMITTED = 'submitted';
    case PENDING = 'pending';
    case SUCCESSFUL = 'successful';
    case FAILED = 'failed';
    case CANCELLED = 'cancelled';
    case EXPIRED = 'expired';
    case UNKNOWN = 'unknown';
    case REVERSED = 'reversed';
    case REFUNDED = 'refunded';

    public function isTerminal(): bool
    {
        return in_array($this, [
            self::SUCCESSFUL,
            self::FAILED,
            self::CANCELLED,
            self::EXPIRED,
            self::REVERSED,
            self::REFUNDED,
        ], true);
    }
}
