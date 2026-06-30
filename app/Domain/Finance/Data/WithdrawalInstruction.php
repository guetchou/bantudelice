<?php

namespace App\Domain\Finance\Data;

final class WithdrawalInstruction
{
    public function __construct(
        public readonly string $partnerType,
        public readonly int $partnerId,
        public readonly int $withdrawalId,
        public readonly int $amount,
    ) {
        if (! in_array($partnerType, ['restaurant', 'driver'], true)) {
            throw new \InvalidArgumentException('Partner type must be restaurant or driver.');
        }

        if ($partnerId <= 0 || $withdrawalId <= 0 || $amount <= 0) {
            throw new \InvalidArgumentException('Partner, withdrawal and amount values must be positive.');
        }
    }
}
