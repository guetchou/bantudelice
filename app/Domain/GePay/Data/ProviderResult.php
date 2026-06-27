<?php

namespace App\Domain\GePay\Data;

use App\Domain\GePay\Enums\TransactionStatus;

final class ProviderResult
{
    public function __construct(
        public readonly TransactionStatus $status,
        public readonly ?string $providerReference = null,
        public readonly ?string $failureCode = null,
        public readonly ?string $failureMessage = null,
        public readonly array $metadata = [],
    ) {
    }
}
