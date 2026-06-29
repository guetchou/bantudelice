<?php

namespace App\Domain\Payment\Enums;

enum PaymentAllocationStatus: string
{
    case ACTIVE = 'active';
    case REVERSED = 'reversed';
}
