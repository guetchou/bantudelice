<?php

namespace App\Domain\Payment\Enums;

enum ReconciliationDiscrepancy: string
{
    case MISSING_INTERNAL = 'missing_internal';
    case MISSING_PROVIDER = 'missing_provider';
    case AMOUNT_MISMATCH = 'amount_mismatch';
    case STATUS_MISMATCH = 'status_mismatch';
    case DUPLICATE = 'duplicate';
    case UNKNOWN = 'unknown';
    case REVERSED = 'reversed';
}
