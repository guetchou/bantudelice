<?php

namespace Tests\Feature;

use App\Payment;
use App\PaymentAllocation;
use App\PaymentReconciliationCase;
use App\Services\PaymentFinancialPositionService;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentFinancialPositionServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_summary_separates_confirmed_allocated_unallocated_and_exceptions(): void
    {
        $user = User::factory()->create(['phone' => '0600019010']);

        $confirmed = Payment::create([
            'user_id' => $user->id,
            'provider' => 'momo',
            'status' => 'PAID',
            'business_status' => 'confirmed',
            'amount' => 10000,
            'currency' => 'XAF',
        ]);

        Payment::create([
            'user_id' => $user->id,
            'provider' => 'airtel_money',
            'status' => 'PENDING',
            'business_status' => 'unknown',
            'amount' => 4000,
            'currency' => 'XAF',
        ]);

        Payment::create([
            'user_id' => $user->id,
            'provider' => 'momo',
            'status' => 'PAID',
            'business_status' => 'reversed',
            'amount' => 2000,
            'currency' => 'XAF',
        ]);

        PaymentAllocation::create([
            'payment_id' => $confirmed->id,
            'target_type' => 'order',
            'target_id' => 7,
            'amount' => 6000,
            'currency' => 'XAF',
            'status' => 'active',
            'idempotency_key' => 'position:allocation:7',
            'allocated_at' => now(),
        ]);

        PaymentReconciliationCase::create([
            'subject_type' => 'payment',
            'subject_id' => $confirmed->id,
            'case_type' => 'amount_mismatch',
            'severity' => 'critical',
            'status' => 'open',
            'expected_amount' => 10000,
            'observed_amount' => 9000,
            'currency' => 'XAF',
        ]);

        $summary = app(PaymentFinancialPositionService::class)->summary();

        $this->assertSame(1, $summary['confirmed']['count']);
        $this->assertSame(10000, $summary['confirmed']['amount']);
        $this->assertSame(6000, $summary['allocation']['allocated']);
        $this->assertSame(4000, $summary['allocation']['unallocated']);
        $this->assertSame(60.0, $summary['allocation']['coverage_rate']);
        $this->assertSame(4000, $summary['exceptions']['unknown_amount']);
        $this->assertSame(2000, $summary['exceptions']['reversed_amount']);
        $this->assertSame(1, $summary['exceptions']['open_cases']);
        $this->assertSame(1000, $summary['exceptions']['amount_mismatch']);
    }
}
