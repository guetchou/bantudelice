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

        $confirmedPayments = DB::table('payments')
            ->whereNull('deleted_at')
            ->where(function ($query) {
                $query->where('business_status', 'confirmed')
                    ->orWhere(function ($legacy) {
                        $legacy->whereNull('business_status')->where('status', 'PAID');
                    });
            });

        $confirmedAmount = (int) (clone $confirmedPayments)->sum('amount');
        $confirmedCount = (int) (clone $confirmedPayments)->count();

        $allocatedAmount = Schema::hasTable('payment_allocations')
            ? (int) DB::table('payment_allocations')
                ->join('payments', 'payments.id', '=', 'payment_allocations.payment_id')
                ->where('payment_allocations.status', 'active')
                ->where(function ($query) {
                    $query->where('payments.business_status', 'confirmed')
                        ->orWhere(function ($legacy) {
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

        $refundedAmount = (int) DB::table('payments')
            ->whereNull('deleted_at')
            ->where('business_status', 'refunded')
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

        return [
            'confirmed' => [
                'count' => $confirmedCount,
                'amount' => $confirmedAmount,
            ],
            'allocation' => [
                'allocated' => $allocatedAmount,
                'unallocated' => max(0, $confirmedAmount - $allocatedAmount),
                'coverage_rate' => $confirmedAmount > 0
                    ? round(($allocatedAmount / $confirmedAmount) * 100, 2)
                    : 0.0,
            ],
            'exceptions' => [
                'unknown_amount' => $unknownAmount,
                'reversed_amount' => $reversedAmount,
                'refunded_amount' => $refundedAmount,
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
            'confirmed' => ['count' => 0, 'amount' => 0],
            'allocation' => ['allocated' => 0, 'unallocated' => 0, 'coverage_rate' => 0.0],
            'exceptions' => [
                'unknown_amount' => 0,
                'reversed_amount' => 0,
                'refunded_amount' => 0,
                'open_cases' => 0,
                'amount_mismatch' => 0,
            ],
            'withdrawals' => ['reserved' => 0, 'paid' => 0],
            'currency' => 'XAF',
            'generated_at' => now(),
        ];
    }
}
