<?php

namespace App\Domain\Finance\Services;

use App\Domain\Finance\Models\FinancialAccount;
use Illuminate\Support\Str;

final class PaymentClearingAccountService
{
    public function providerCollections(string $provider): FinancialAccount
    {
        $originalProvider = strtolower(trim($provider));
        $canonicalProvider = $this->canonicalProvider($originalProvider);
        $normalized = strtoupper(substr(Str::slug($canonicalProvider, '_'), 0, 32));

        if ($normalized === '') {
            throw new \InvalidArgumentException('Le fournisseur de paiement est obligatoire.');
        }

        return FinancialAccount::firstOrCreate(
            ['code' => 'ASSET:PAYMENT_PROVIDER:' . $normalized . ':COLLECTIONS'],
            [
                'uuid' => (string) Str::uuid(),
                'name' => 'Encaissements fournisseur ' . $normalized,
                'category' => 'asset',
                'purpose' => 'provider_collections_' . strtolower($normalized),
                'owner_type' => 'platform',
                'owner_id' => 1,
                'currency' => 'XAF',
                'status' => 'active',
                'metadata' => [
                    'provider' => $canonicalProvider,
                    'first_seen_alias' => $originalProvider,
                    'provisioned_by' => self::class,
                ],
            ]
        );
    }

    public function paymentClearing(): FinancialAccount
    {
        return FinancialAccount::firstOrCreate(
            ['code' => 'LIABILITY:PAYMENT:CLEARING'],
            [
                'uuid' => (string) Str::uuid(),
                'name' => 'Fonds clients encaissés à affecter',
                'category' => 'liability',
                'purpose' => 'payment_clearing',
                'owner_type' => 'platform',
                'owner_id' => 1,
                'currency' => 'XAF',
                'status' => 'active',
                'metadata' => ['provisioned_by' => self::class],
            ]
        );
    }

    private function canonicalProvider(string $provider): string
    {
        return match ($provider) {
            'momo', 'mtn', 'mtn_momo' => 'mtn_momo',
            'airtel', 'airtel_money' => 'airtel_money',
            'cash', 'cod', 'demo' => 'cash',
            default => $provider,
        };
    }
}
