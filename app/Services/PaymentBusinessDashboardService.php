<?php

namespace App\Services;

use App\PaymentReconciliationCase;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PaymentBusinessDashboardService
{
    public function build(array $filters = []): array
    {
        $today = now()->startOfDay();
        $paymentQuery = DB::table('payments')
            ->whereIn('status', ['PAID', 'SUCCESS', 'SUCCESSFUL'])
            ->where('created_at', '>=', $today);

        if (Schema::hasColumn('payments', 'deleted_at')) {
            $paymentQuery->whereNull('deleted_at');
        }
        if (Schema::hasColumn('payments', 'financial_state')) {
            $paymentQuery->where(function ($query): void {
                $query->whereNull('financial_state')
                    ->orWhere('financial_state', 'confirmed');
            });
        }

        $this->applyPaymentFilters($paymentQuery, $filters);
        $confirmedPayments = $paymentQuery->get(['id', 'amount', 'currency']);
        $confirmedIds = $confirmedPayments->pluck('id')->all();
        $confirmedAmount = (float) $confirmedPayments->sum('amount');

        $allocatedAmount = 0.0;
        $heldAmount = 0.0;
        $allocationCount = 0;

        if (Schema::hasTable('payment_allocations') && $confirmedIds !== []) {
            $allocations = DB::table('payment_allocations')
                ->whereIn('payment_id', $confirmedIds)
                ->get();

            $allocatedAmount = (float) $allocations->where('status', 'allocated')->sum('amount');
            $heldAmount = (float) $allocations->where('status', 'held')->sum('amount');
            $allocationCount = $allocations->where('status', 'allocated')->count();
        }

        $unallocatedAmount = max(0, $confirmedAmount - $allocatedAmount);
        $openCases = $this->openCases();
        $openCaseAmount = (float) $openCases
            ->groupBy(fn ($case) => $case->payment_id ?: ('case:' . $case->id))
            ->sum(fn (Collection $group) => (float) $group->max('observed_amount'));
        $criticalCases = $openCases->where('severity', 'critical')->count();

        $reservedWithdrawals = 0.0;
        $paidWithdrawals = 0.0;
        if (Schema::hasTable('partner_withdrawals')) {
            $reservedWithdrawals = (float) DB::table('partner_withdrawals')
                ->whereIn('status', ['created', 'reserved', 'submitted', 'pending', 'unknown'])
                ->sum('net_amount');
            $paidWithdrawals = (float) DB::table('partner_withdrawals')
                ->where('status', 'paid')
                ->where('paid_at', '>=', $today)
                ->sum('net_amount');
        }

        $providerClearing = $this->ledgerNet('provider_clearing', debitPositive: true);
        $customerFunds = $this->ledgerNet('customer_funds', debitPositive: false);

        return [
            'businessSummary' => [
                'confirmed_amount' => $confirmedAmount,
                'allocated_amount' => $allocatedAmount,
                'held_amount' => $heldAmount,
                'unallocated_amount' => $unallocatedAmount,
                'allocation_count' => $allocationCount,
                'open_cases' => $openCases->count(),
                'critical_cases' => $criticalCases,
                'open_case_amount' => $openCaseAmount,
                'reserved_withdrawals' => $reservedWithdrawals,
                'paid_withdrawals' => $paidWithdrawals,
                'provider_clearing' => $providerClearing,
                'customer_funds' => $customerFunds,
            ],
            'businessHealth' => $this->health($criticalCases, $openCases->count(), $unallocatedAmount),
            'caseQueue' => $this->caseQueue($openCases),
        ];
    }

    private function openCases(): Collection
    {
        if (! Schema::hasTable('payment_reconciliation_cases')) {
            return collect();
        }

        return PaymentReconciliationCase::query()
            ->with('payment')
            ->whereIn('status', ['open', 'investigating'])
            ->orderByRaw("CASE WHEN severity = 'critical' THEN 0 ELSE 1 END")
            ->orderBy('opened_at')
            ->limit(20)
            ->get();
    }

    private function caseQueue(Collection $cases): Collection
    {
        return $cases->map(function (PaymentReconciliationCase $case): array {
            $payment = $case->payment;
            $openedAt = $case->opened_at ?? $case->created_at ?? now();

            return [
                'case_id' => $case->id,
                'case_key' => $case->case_key,
                'type' => $case->case_type,
                'severity' => $case->severity,
                'summary' => $case->summary,
                'payment_id' => $case->payment_id,
                'payment_reference' => $payment?->provider_reference ?: ('PAY-' . ($case->payment_id ?? '—')),
                'provider' => $payment?->provider ?? $case->provider ?? '—',
                'internal_status' => $payment?->financialState() ?? '—',
                'expected_amount' => (float) ($case->expected_amount ?? 0),
                'observed_amount' => (float) ($case->observed_amount ?? 0),
                'currency' => $case->currency ?: 'XAF',
                'age_label' => Carbon::parse($openedAt)->diffForHumans(),
                'can_reconcile' => (bool) $case->payment_id,
            ];
        });
    }

    private function ledgerNet(string $accountCode, bool $debitPositive): float
    {
        if (
            ! Schema::hasTable('financial_ledger_entries')
            || ! Schema::hasColumn('financial_ledger_entries', 'account_code')
        ) {
            return 0.0;
        }

        $entries = DB::table('financial_ledger_entries')
            ->where('account_code', $accountCode)
            ->where('status', 'posted')
            ->get(['direction', 'amount']);

        return (float) $entries->sum(function ($entry) use ($debitPositive): float {
            $amount = (float) $entry->amount;
            $isDebit = strtolower((string) $entry->direction) === 'debit';

            return ($isDebit === $debitPositive) ? $amount : -$amount;
        });
    }

    private function health(int $criticalCases, int $openCases, float $unallocatedAmount): array
    {
        if ($criticalCases > 0) {
            return [
                'tone' => 'danger',
                'label' => 'Intervention financière requise',
                'message' => $criticalCases . ' dossier(s) critique(s) bloquent une décision métier.',
            ];
        }

        if ($openCases > 0 || $unallocatedAmount > 0) {
            return [
                'tone' => 'warning',
                'label' => 'Position à rapprocher',
                'message' => 'Des fonds encaissés restent non affectés ou en cours d’analyse.',
            ];
        }

        return [
            'tone' => 'success',
            'label' => 'Position financière cohérente',
            'message' => 'Les encaissements confirmés sont affectés et aucun dossier n’est ouvert.',
        ];
    }

    private function applyPaymentFilters($query, array $filters): void
    {
        $provider = strtolower(trim((string) ($filters['provider'] ?? 'all')));
        if ($provider !== '' && $provider !== 'all') {
            $providers = match ($provider) {
                'mtn' => ['momo', 'mtn_momo', 'mtn'],
                'airtel' => ['airtel', 'airtel_money'],
                'card' => ['card', 'stripe'],
                default => [$provider],
            };
            $query->whereIn('provider', $providers);
        }
    }
}
