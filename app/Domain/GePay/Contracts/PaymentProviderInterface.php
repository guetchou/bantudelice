<?php

namespace App\Domain\GePay\Contracts;

use App\Domain\GePay\Data\ProviderResult;
use App\Domain\GePay\Enums\TransactionType;
use App\Domain\GePay\Models\GePayTransaction;

interface PaymentProviderInterface
{
    public function code(): string;

    public function supports(TransactionType $type): bool;

    public function initiate(GePayTransaction $transaction): ProviderResult;

    public function checkStatus(GePayTransaction $transaction): ProviderResult;
}
