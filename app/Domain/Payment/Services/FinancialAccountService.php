<?php

namespace App\Domain\Payment\Services;

use App\Domain\Payment\Enums\FinancialAccountType;
use App\Domain\Payment\Models\FinancialAccount;
use Illuminate\Database\Eloquent\Model;

class FinancialAccountService
{
    public function getOrCreate(
        FinancialAccountType $type,
        ?Model $owner = null,
        string $currency = 'XAF',
        array $metadata = []
    ): FinancialAccount {
        $currency = strtoupper(trim($currency));
        $ownerType = $owner?->getMorphClass();
        $ownerId = $owner?->getKey();

        return FinancialAccount::query()->firstOrCreate(
            [
                'owner_type' => $ownerType,
                'owner_id' => $ownerId,
                'type' => $type->value,
                'currency' => $currency,
            ],
            [
                'code' => $this->buildCode($type, $owner, $currency),
                'normal_balance' => $type->normalBalance()->value,
                'status' => 'active',
                'metadata' => $metadata,
            ]
        );
    }

    private function buildCode(
        FinancialAccountType $type,
        ?Model $owner,
        string $currency
    ): string {
        $scope = $owner
            ? class_basename($owner) . ':' . $owner->getKey()
            : 'SYSTEM';

        return strtoupper($currency . ':' . $type->value . ':' . $scope);
    }
}
