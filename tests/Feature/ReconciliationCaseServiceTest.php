<?php

namespace Tests\Feature;

use App\Domain\Payment\Enums\PaymentStatus;
use App\Domain\Payment\Enums\ReconciliationCaseStatus;
use App\Domain\Payment\Enums\ReconciliationDiscrepancy;
use App\Domain\Payment\Services\ReconciliationCaseService;
use App\Payment;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReconciliationCaseServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_same_financial_difference_opens_only_one_case(): void
    {
        $user = User::factory()->create();
        $payment = Payment::query()->create([
            'user_id' => $user->id,
            'provider' => 'mtn_momo',
            'provider_reference' => 'MTN-REC-001',
            'idempotency_key' => 'payment-rec-001',
            'status' => 'PENDING',
            'canonical_status' => PaymentStatus::UNKNOWN->value,
            'amount' => 12000,
            'currency' => 'XAF',
        ]);

        $facts = [
            'provider' => 'mtn_momo',
            'provider_reference' => 'MTN-REC-001',
            'expected_amount' => 12000,
            'observed_amount' => 10000,
            'expected_status' => 'successful',
            'observed_status' => 'unknown',
            'evidence' => ['source' => 'provider_refresh'],
        ];

        $service = app(ReconciliationCaseService::class);
        $first = $service->open(
            $payment,
            ReconciliationDiscrepancy::AMOUNT_MISMATCH,
            $facts
        );
        $second = $service->open(
            $payment,
            ReconciliationDiscrepancy::AMOUNT_MISMATCH,
            $facts
        );

        $this->assertSame($first->id, $second->id);
        $this->assertSame(ReconciliationCaseStatus::OPEN, $first->status);
        $this->assertDatabaseCount('payment_reconciliation_cases', 1);
    }
}
