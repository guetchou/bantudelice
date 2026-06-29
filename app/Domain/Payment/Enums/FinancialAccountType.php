<?php

namespace App\Domain\Payment\Enums;

enum FinancialAccountType: string
{
    case CASH_CLEARING = 'cash_clearing';
    case PROVIDER_CLEARING = 'provider_clearing';
    case PLATFORM_REVENUE = 'platform_revenue';
    case RESTAURANT_PAYABLE = 'restaurant_payable';
    case DRIVER_PAYABLE = 'driver_payable';
    case CUSTOMER_CREDIT = 'customer_credit';
    case REFUND_PAYABLE = 'refund_payable';
    case FEES_EXPENSE = 'fees_expense';
    case SUSPENSE = 'suspense';

    public function normalBalance(): LedgerDirection
    {
        return match ($this) {
            self::CASH_CLEARING,
            self::PROVIDER_CLEARING,
            self::FEES_EXPENSE => LedgerDirection::DEBIT,

            self::PLATFORM_REVENUE,
            self::RESTAURANT_PAYABLE,
            self::DRIVER_PAYABLE,
            self::CUSTOMER_CREDIT,
            self::REFUND_PAYABLE,
            self::SUSPENSE => LedgerDirection::CREDIT,
        };
    }
}
