<?php

namespace App\Console\Commands;

use App\Domain\Finance\Models\FinancialAccount;
use App\Domain\Finance\Services\FinancialAccountService;
use App\Domain\Finance\Services\LedgerPostingService;
use App\Driver;
use App\Restaurant;
use App\Services\PartnerFinancialDashboardService;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

final class AuditPartnerFinancialPositions extends Command
{
    protected $signature = 'finance:audit-partner-positions
        {--type=all : all, restaurant or driver}
        {--id= : Optional partner ID}';

    protected $description = 'Compare legacy partner positions with ledger accounts without writing entries.';

    public function handle(
        PartnerFinancialDashboardService $legacy,
        LedgerPostingService $postings
    ): int {
        if (! Schema::hasTable('financial_accounts') || ! Schema::hasTable('financial_postings')) {
            $this->error('Financial ledger tables are not installed.');
            return self::FAILURE;
        }

        $type = strtolower((string) $this->option('type'));
        if (! in_array($type, ['all', 'restaurant', 'driver'], true)) {
            $this->error('The type must be all, restaurant or driver.');
            return self::INVALID;
        }

        $partnerId = $this->option('id') !== null ? (int) $this->option('id') : null;
        $rows = collect();

        if (in_array($type, ['all', 'restaurant'], true)) {
            $query = Restaurant::query()->orderBy('id');
            if ($partnerId !== null) {
                $query->whereKey($partnerId);
            }
            $query->chunkById(100, function ($partners) use ($rows, $legacy, $postings): void {
                foreach ($partners as $partner) {
                    $rows->push($this->buildRow(
                        'restaurant',
                        (int) $partner->id,
                        (string) $partner->name,
                        $legacy->forRestaurant($partner),
                        $postings
                    ));
                }
            });
        }

        if (in_array($type, ['all', 'driver'], true)) {
            $query = Driver::query()->orderBy('id');
            if ($partnerId !== null) {
                $query->whereKey($partnerId);
            }
            $query->chunkById(100, function ($partners) use ($rows, $legacy, $postings): void {
                foreach ($partners as $partner) {
                    $rows->push($this->buildRow(
                        'driver',
                        (int) $partner->id,
                        (string) $partner->name,
                        $legacy->forDeliveryDriver($partner),
                        $postings
                    ));
                }
            });
        }

        $this->table(
            ['Type', 'ID', 'Partner', 'Legacy due', 'Ledger due', 'Difference', 'Accounts'],
            $rows->map(fn (array $row) => [
                $row['partner_type'],
                $row['partner_id'],
                $row['partner_name'],
                $row['legacy_total_due'],
                $row['ledger_total_due'],
                $row['difference'],
                $row['account_status'],
            ])->all()
        );

        $missing = $rows->where('account_status', 'missing')->count();
        $different = $rows->filter(fn (array $row) => $row['difference'] !== 0)->count();

        $this->line('Partners audited: ' . $rows->count());
        $this->line('Missing account sets: ' . $missing);
        $this->line('Non-zero differences: ' . $different);

        if ($missing > 0 || $different > 0) {
            $this->warn('Do not activate ledger reads until every difference is explained and approved.');
        }

        return self::SUCCESS;
    }

    private function buildRow(
        string $partnerType,
        int $partnerId,
        string $partnerName,
        array $legacyDashboard,
        LedgerPostingService $postings
    ): array {
        $legacyAvailable = $this->cardAmount($legacyDashboard, 'Disponible au retrait');
        $legacyInProgress = $this->cardAmount($legacyDashboard, 'En attente de reversement');
        $legacyTotalDue = $legacyAvailable + $legacyInProgress;

        $accounts = FinancialAccount::query()
            ->where('owner_type', $partnerType)
            ->where('owner_id', $partnerId)
            ->whereIn('purpose', [
                FinancialAccountService::PARTNER_PENDING,
                FinancialAccountService::PARTNER_AVAILABLE,
                FinancialAccountService::PARTNER_RESERVED,
            ])
            ->get()
            ->keyBy('purpose');

        $pending = $this->accountBalance($accounts, FinancialAccountService::PARTNER_PENDING, $postings);
        $available = $this->accountBalance($accounts, FinancialAccountService::PARTNER_AVAILABLE, $postings);
        $reserved = $this->accountBalance($accounts, FinancialAccountService::PARTNER_RESERVED, $postings);
        $ledgerTotalDue = $pending + $available + $reserved;

        return [
            'partner_type' => $partnerType,
            'partner_id' => $partnerId,
            'partner_name' => $partnerName,
            'legacy_total_due' => $legacyTotalDue,
            'ledger_total_due' => $ledgerTotalDue,
            'difference' => $ledgerTotalDue - $legacyTotalDue,
            'account_status' => $accounts->count() === 3 ? 'complete' : 'missing',
        ];
    }

    private function cardAmount(array $dashboard, string $label): int
    {
        $card = collect($dashboard['cards'] ?? [])->firstWhere('label', $label);
        return (int) round((float) ($card['amount'] ?? 0));
    }

    private function accountBalance(
        Collection $accounts,
        string $purpose,
        LedgerPostingService $postings
    ): int {
        $account = $accounts->get($purpose);
        return $account ? $postings->balance($account) : 0;
    }
}
