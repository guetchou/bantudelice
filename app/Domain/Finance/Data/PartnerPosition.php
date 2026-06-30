<?php

namespace App\Domain\Finance\Data;

final class PartnerPosition
{
    public function __construct(
        public readonly string $partnerType,
        public readonly int $partnerId,
        public readonly string $currency,
        public readonly int $pending,
        public readonly int $available,
        public readonly int $reserved,
        public readonly int $totalDue,
    ) {
    }
}
