<?php

namespace App\Domain\Finance\Services;

use App\Domain\Finance\Models\FinancialMirrorEvent;
use App\Payment;

final class PaymentCollectionPostingVerifier
{
    public function verify(Payment $payment, FinancialMirrorEvent $event): array
    {
        return [];
    }
}
