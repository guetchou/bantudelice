<?php

namespace App\Domain\Finance\Data;

final class CollectionDistribution
{
    public function __construct(
        public readonly int $paymentId,
        public readonly int $totalAmount,
        public readonly ?int $restaurantId,
        public readonly int $restaurantNet,
        public readonly ?int $driverId,
        public readonly int $driverNet,
        public readonly int $platformCommission,
        public readonly int $platformServiceFee = 0,
        public readonly int $taxPayable = 0,
        public readonly array $metadata = [],
    ) {
        if ($paymentId <= 0 || $totalAmount <= 0) {
            throw new \InvalidArgumentException('Payment ID and total amount must be positive.');
        }

        $components = [
            $restaurantNet,
            $driverNet,
            $platformCommission,
            $platformServiceFee,
            $taxPayable,
        ];

        if (collect($components)->contains(fn (int $amount) => $amount < 0)) {
            throw new \InvalidArgumentException('Distribution amounts cannot be negative.');
        }

        if (array_sum($components) !== $totalAmount) {
            throw new \InvalidArgumentException('Distribution amounts must equal the collected total.');
        }

        if ($restaurantNet > 0 && (! $restaurantId || $restaurantId <= 0)) {
            throw new \InvalidArgumentException('A restaurant is required for a restaurant allocation.');
        }

        if ($driverNet > 0 && (! $driverId || $driverId <= 0)) {
            throw new \InvalidArgumentException('A driver is required for a driver allocation.');
        }
    }
}
