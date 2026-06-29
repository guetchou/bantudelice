<?php

namespace App\Domain\Payment\Enums;

enum JournalEntryType: string
{
    case COLLECTION_SETTLEMENT = 'collection_settlement';
    case ORDER_DISTRIBUTION = 'order_distribution';
    case PROVIDER_FEE = 'provider_fee';
    case WITHDRAWAL_RESERVATION = 'withdrawal_reservation';
    case WITHDRAWAL_PAYMENT = 'withdrawal_payment';
    case WITHDRAWAL_RELEASE = 'withdrawal_release';
    case REFUND = 'refund';
    case REVERSAL = 'reversal';
    case ADJUSTMENT = 'adjustment';
    case OPENING_BALANCE = 'opening_balance';
}
