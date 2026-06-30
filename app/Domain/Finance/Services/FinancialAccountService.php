<?php

namespace App\Domain\Finance\Services;

use App\Domain\Finance\Models\FinancialAccount;
use Illuminate\Support\Str;

final class FinancialAccountService
{
    public const PARTNER_AVAILABLE = 'partner_payable_available';
    public const PARTNER_RESERVED = 'partner_withdrawal_reserved';

    public function partner(string $partnerType, int $partnerId, string $purpose): FinancialAccount
    {
        $partnerType = strtolower(trim($partnerType));

        if (! in_array($partnerType, ['restaurant', 'driver'], true)) {
            throw new \InvalidArgumentException('Type de partenaire financier invalide.');
        }

        if (! in_array($purpose, [self::PARTNER_AVAILABLE, self::PARTNER_RESERVED], true)) {
            throw new \InvalidArgumentException('Objet de compte partenaire invalide.');
        }

        $suffix = $purpose === self::PARTNER_AVAILABLE ? 'AVAILABLE' : 'RESERVED';
        $label = $purpose === self::PARTNER_AVAILABLE ? 'disponible' : 'réservé aux retraits';
        $code = sprintf('LIABILITY:%s:%d:%s', strtoupper($partnerType), $partnerId, $suffix);

        return FinancialAccount::firstOrCreate(
            ['code' => $code],
            [
                'uuid' => (string) Str::uuid(),
                'name' => sprintf('%s #%d — solde %s', ucfirst($partnerType), $partnerId, $label),
                'category' => 'liability',
                'purpose' => $purpose,
                'owner_type' => $partnerType,
                'owner_id' => $partnerId,
                'currency' => 'XAF',
                'status' => 'active',
                'metadata' => ['provisioned_by' => self::class],
            ]
        );
    }

    public function platform(string $purpose): FinancialAccount
    {
        $definition = match ($purpose) {
            'mtn_collections_cash' => ['ASSET:MTN:COLLECTIONS', 'Trésorerie MTN Collections', 'asset'],
            'mtn_disbursement_cash' => ['ASSET:MTN:DISBURSEMENT', 'Trésorerie MTN Disbursements', 'asset'],
            'cash_in_transit' => ['ASSET:CASH:IN_TRANSIT', 'Espèces en transit', 'asset'],
            'platform_commission_revenue' => ['REVENUE:BANTUDELICE:COMMISSION', 'Revenus de commissions BantuDelice', 'revenue'],
            'platform_service_fee_revenue' => ['REVENUE:BANTUDELICE:SERVICE_FEE', 'Revenus de frais de service BantuDelice', 'revenue'],
            'operator_fee_expense' => ['EXPENSE:PAYMENT:OPERATOR_FEE', 'Frais opérateurs de paiement', 'expense'],
            'tax_payable' => ['LIABILITY:TAX:PAYABLE', 'Taxes collectées à reverser', 'liability'],
            'legacy_control' => ['ASSET:LEGACY:CONTROL', 'Compte de contrôle des reprises historiques', 'asset'],
            default => throw new \InvalidArgumentException('Objet de compte plateforme inconnu.'),
        };

        [$code, $name, $category] = $definition;

        return FinancialAccount::firstOrCreate(
            ['code' => $code],
            [
                'uuid' => (string) Str::uuid(),
                'name' => $name,
                'category' => $category,
                'purpose' => $purpose,
                'owner_type' => 'platform',
                'owner_id' => 1,
                'currency' => 'XAF',
                'status' => 'active',
                'metadata' => ['provisioned_by' => self::class],
            ]
        );
    }

    public function provisionPartner(string $partnerType, int $partnerId): array
    {
        return [
            'available' => $this->partner($partnerType, $partnerId, self::PARTNER_AVAILABLE),
            'reserved' => $this->partner($partnerType, $partnerId, self::PARTNER_RESERVED),
        ];
    }

    public function provisionPlatform(): array
    {
        $purposes = [
            'mtn_collections_cash',
            'mtn_disbursement_cash',
            'cash_in_transit',
            'platform_commission_revenue',
            'platform_service_fee_revenue',
            'operator_fee_expense',
            'tax_payable',
            'legacy_control',
        ];

        return collect($purposes)
            ->mapWithKeys(fn (string $purpose) => [$purpose => $this->platform($purpose)])
            ->all();
    }
}
