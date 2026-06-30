<?php

namespace App\Domain\Finance\Data;

final class CollectedPayment
{
    public function __construct(
        public readonly int $paymentId,
        public readonly int $amount,
        public readonly string $currency,
        public readonly string $provider,
        public readonly ?string $providerReference = null,
        public readonly array $metadata = [],
    ) {
        if ($paymentId <= 0 || $amount <= 0) {
            throw new \InvalidArgumentException('Payment ID and amount must be positive.');
        }

        if (strtoupper(trim($currency)) !== 'XAF') {
            throw new \InvalidArgumentException('Only XAF collections are supported by this ledger.');
        }

        if (trim($provider) === '') {
            throw new \InvalidArgumentException('A payment provider is required.');
        }
    }
}
