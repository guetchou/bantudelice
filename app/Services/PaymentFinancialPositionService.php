<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PaymentFinancialPositionService
{
    public function summary(): array
    {
        if (!Schema::hasTable('payments')) {
            return $this->emptySummary();
        }

        $capturedPayments = DB::table('payments')
            ->whereNull('deleted_at')
            ->where(function ($query) {
                $query->whereIn('business_status', [
                    'confirmed',
                    'partially_refunded',
                    'refunded',
                    'disputed',
                ])->orWhere(function ($legacy) {
                    $legacy->whereNull('business_status')->where('status', 'PAID');
                });
            });

        $capturedGross = (int) (clone $capturedPayments)->sum('amount');
        $capturedCount = (int) (clone $capturedPayments)->count();

        $refundedAmount = Schema::hasTable('payment_refunds')
            ? (int) DB::table('payment_refunds')->where('status', 'refunded')->sum('amount')
            : (int) DB::table('payments')
                ->whereNull('deleted_at')
                ->where('business_status', 'refunded')
                ->sum('amount');

        $committedRefunds = Schema::hasTable('payment_refunds')
            ? (int) DB::table('payment_refunds')
                ->whereIn('status', ['requested', 'approved', 'submitted', 'pending', 'unknown'])
                ->sum('amount')
            : 0;

        $netCaptured = max(0, $capturedGross - $refundedAmount);

        $allocatedAmount = Schema::hasTable('payment_allocations')
            ? (int) DB::table('payment_allocations')
                ->join('payments', 'payments.id', '=', 'payment_allocations.payment_id')
                ->where('payment_allocations.status', 'active')
                ->whereNull('payments.deleted_at')
                ->where(function ($query) {
                    $query->whereIn('payments.business_status', [
                        'confirmed',
                        'partially_refunded',
                        'refunded',
                        'disputed',
                    ])->orWhere(function ($legacy) {
                        $legacy->whereNull('payments.business_status')->where('payments.status', 'PAID');
                    });
                })
                ->sum('payment_allocations.amount')
            : 0;

        $unknownAmount = (int) DB::table('payments')
            ->whereNull('deleted_at')
            ->where(function ($query) {
                $query->where('business_status', 'unknown')
                    ->orWhere(function ($legacy) {
                        $legacy->whereNull('business_status')->where('status', 'UNKNOWN');
                    });
            })
            ->sum('amount');

        $reversedAmount = (int) DB::table('payments')
            ->whereNull('deleted_at')
            ->where('business_status', 'reversed')
            ->sum('amount');

        $disputedAmount = (int) DB::table('payments')
            ->whereNull('deleted_at')
            ->where('business_status', 'disputed')
            ->sum('amount');

        $reservedWithdrawals = Schema::hasTable('partner_withdrawals')
            ? (int) DB::table('partner_withdrawals')
                ->whereIn('status', ['created', 'reserved', 'submitted', 'pending', 'unknown'])
                ->sum('net_amount')
            : 0;

        $paidWithdrawals = Schema::hasTable('partner_withdrawals')
            ? (int) DB::table('partner_withdrawals')->where('status', 'paid')->sum('net_amount')
            : 0;

        $openCases = Schema::hasTable('payment_reconciliation_cases')
            ? (int) DB::table('payment_reconciliation_cases')
                ->whereIn('status', ['open', 'investigating'])
                ->count()
            : 0;

        $amountMismatch = Schema::hasTable('payment_reconciliation_cases')
            ? (int) DB::table('payment_reconciliation_cases')
                ->whereIn('status', ['open', 'investigating'])
                ->get(['expected_amount', 'observed_amount'])
                ->sum(fn ($case) => abs((int) $case->expected_amount - (int) $case->observed_amount))
            : 0;

        $unallocated = max(0, $netCaptured - $allocatedAmount);
        $overallocated = max(0, $allocatedAmount - $netCaptured);

        return [
            'captured' => [
                'count' => $capturedCount,
                'gross' => $capturedGross,
                'refunded' => $refundedAmount,
                'net' => $netCaptured,
            ],
            'confirmed' => [
                'count' => $capturedCount,
                'amount' => $netCaptured,
            ],
            'allocation' => [
                'allocated' => $allocatedAmount,
                'unallocated' => $unallocated,
                'overallocated' => $overallocated,
                'coverage_rate' => $netCaptured > 0
                    ? round(($allocatedAmount / $netCaptured) * 100, 2)
                    : 0.0,
            ],
            'refunds' => [
                'committed' => $committedRefunds,
                'refunded' => $refundedAmount,
            ],
            'exceptions' => [
                'unknown_amount' => $unknownAmount,
                'reversed_amount' => $reversedAmount,
                'disputed_amount' => $disputedAmount,
                'overallocated_amount' => $overallocated,
                'open_cases' => $openCases,
                'amount_mismatch' => $amountMismatch,
            ],
            'withdrawals' => [
                'reserved' => $reservedWithdrawals,
                'paid' => $paidWithdrawals,
            ],
            'currency' => 'XAF',
            'generated_at' => now(),
        ];
    }

    private function emptySummary(): array
    {
        return [
            'captured' => ['count' => 0, 'gross' => 0, 'refunded' => 0, 'net' => 0],
            'confirmed' => ['count' => 0, 'amount' => 0],
            'allocation' => [
                'allocated' => 0,
                'unallocated' => 0,
                'overallocated' => 0,
                'coverage_rate' => 0.0,
            ],
            'refunds' => ['committed' => 0, 'refunded' => 0],
            'exceptions' => [
                'unknown_amount' => 0,
                'reversed_amount' => 0,
                'disputed_amount' => 0,
                'overallocated_amount' => 0,
                'open_cases' => 0,
                'amount_mismatch' => 0,
            ],
            'withdrawals' => ['reserved' => 0, 'paid' => 0],
            'currency' => 'XAF',
            'generated_at' => now(),
        ];
    }
}
