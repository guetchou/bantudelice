<?php

namespace App\Domain\Payment\Enums;

enum LedgerDirection: string
{
    case DEBIT = 'debit';
    case CREDIT = 'credit';

    public function opposite(): self
    {
        return $this === self::DEBIT ? self::CREDIT : self::DEBIT;
    }
}
