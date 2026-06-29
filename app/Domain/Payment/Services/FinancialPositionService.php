<?php

namespace App\Domain\Payment\Services;

use App\Domain\Payment\Enums\FinancialAccountType;
use App\Domain\Payment\Enums\JournalEntryStatus;
use Illuminate\Support\Facades\DB;

class FinancialPositionService
{
    public function summary(string $currency = 'XAF'): array
    {
        $currency = strtoupper($currency);

        $balances = DB::table('financial_accounts')
            ->leftJoin(
                'financial_journal_lines',
                'financial_journal_lines.financial_account_id',
                '=',
                'financial_accounts.id'
            )
            ->leftJoin(
                'financial_journal_entries',
                'financial_journal_entries.id',
                '=',
                'financial_journal_lines.journal_entry_id'
            )
            ->where('financial_accounts.currency', $currency)
            ->where(function ($query) {
                $query->whereNull('financial_journal_entries.id')
                    ->orWhereIn('financial_journal_entries.status', [
                        JournalEntryStatus::POSTED->value,
                        JournalEntryStatus::REVERSED->value,
                    ]);
            })
            ->groupBy('financial_accounts.type')
            ->select('financial_accounts.type')
            ->selectRaw(
                'COALESCE(SUM(CASE WHEN financial_journal_lines.direction = financial_accounts.normal_balance THEN financial_journal_lines.amount ELSE -financial_journal_lines.amount END), 0) AS balance'
            )
            ->pluck('balance', 'type')
            ->map(fn ($value) => (int) $value);

        $value = fn (FinancialAccountType $type): int => (int) ($balances[$type->value] ?? 0);

        return [
            'currency' => $currency,
            'cash_clearing' => $value(FinancialAccountType::CASH_CLEARING),
            'provider_clearing' => $value(FinancialAccountType::PROVIDER_CLEARING),
            'platform_revenue' => $value(FinancialAccountType::PLATFORM_REVENUE),
            'restaurant_payable' => $value(FinancialAccountType::RESTAURANT_PAYABLE),
            'driver_payable' => $value(FinancialAccountType::DRIVER_PAYABLE),
            'customer_credit' => $value(FinancialAccountType::CUSTOMER_CREDIT),
            'refund_payable' => $value(FinancialAccountType::REFUND_PAYABLE),
            'provider_fees' => $value(FinancialAccountType::FEES_EXPENSE),
            'suspense' => $value(FinancialAccountType::SUSPENSE),
            'partner_liability' => $value(FinancialAccountType::RESTAURANT_PAYABLE)
                + $value(FinancialAccountType::DRIVER_PAYABLE),
        ];
    }
}
