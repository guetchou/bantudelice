<?php

namespace Tests\Feature;

use App\Domain\Payment\Enums\ReconciliationCaseStatus;
use App\Domain\Payment\Enums\ReconciliationDiscrepancy;
use App\Domain\Payment\Services\ReconciliationCaseService;
use App\Payment;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class ReconciliationCaseServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_same_discrepancy_opens_only_one_case(): void
    {
        $user = User::factory()->create();
        $payment = Payment::query()->create([
            'user_id' => $user->id,
            'provider' => 'mtn_momo',
            'provider_reference' => 'MTN-REC-001',
            'idempotency_key' => 'payment-rec-001',
            'status' => 'UNKNOWN',
            'amount' => 12000,
            'currency' => 'XAF',
        ]);

        $facts = [
            'provider' => 'mtn_momo',
            'provider_reference' => 'MTN-REC-001',
            'expected_amount' => 12000,
            'observed_amount' => 10000,
            'expected_status' => 'SUCCESSFUL',
            'observed_status' => 'UNKNOWN',
            'evidence' => ['source' => 'gepay_refresh'],
        ];

        $service = app(ReconciliationCaseService::class);
        $case = $service->open(
            $payment,
            ReconciliationDiscrepancy::AMOUNT_MISMATCH,
            $facts
        );
        $sameCase = $service->open(
            $payment,
            ReconciliationDiscrepancy::AMOUNT_MISMATCH,
            $facts
        );

        $this->assertSame($case->id, $sameCase->id);
        $this->assertSame(ReconciliationCaseStatus::OPEN, $case->status);
        $this->assertSame(ReconciliationDiscrepancy::AMOUNT_MISMATCH, $case->discrepancy_code);
        $this->assertDatabaseCount('payment_reconciliation_cases', 1);
    }

    public function test_case_requires_evidence_and_resolution_note_to_close(): void
    {
        $resolver = User::factory()->create();
        $payment = Payment::query()->create([
            'user_id' => $resolver->id,
            'provider' => 'airtel_money',
            'provider_reference' => 'AIRTEL-REC-001',
            'idempotency_key' => 'payment-rec-002',
            'status' => 'REVERSED',
            'amount' => 9000,
            'currency' => 'XAF',
        ]);

        $service = app(ReconciliationCaseService::class);
        $case = $service->open(
            $payment,
            ReconciliationDiscrepancy::REVERSED,
            [
                'provider' => 'airtel_money',
                'provider_reference' => 'AIRTEL-REC-001',
                'expected_amount' => 9000,
                'observed_amount' => 9000,
                'expected_status' => 'SUCCESSFUL',
                'observed_status' => 'REVERSED',
            ]
        );

        $investigating = $service->markInvestigating($case, [
            'operator_response' => 'Transaction inversée.',
        ]);

        $resolved = $service->resolve(
            $investigating,
            $resolver,
            'Contre-écriture créée et solde partenaire restauré.',
            ['journal_entry' => 'REV-001']
        );

        $this->assertSame(ReconciliationCaseStatus::RESOLVED, $resolved->status);
        $this->assertSame($resolver->id, $resolved->resolved_by);
        $this->assertNotNull($resolved->resolved_at);
        $this->assertSame('REV-001', $resolved->evidence['journal_entry']);
    }

    public function test_empty_resolution_note_is_rejected(): void
    {
        $resolver = User::factory()->create();
        $payment = Payment::query()->create([
            'user_id' => $resolver->id,
            'provider' => 'mtn_momo',
            'provider_reference' => 'MTN-REC-003',
            'idempotency_key' => 'payment-rec-003',
            'status' => 'UNKNOWN',
            'amount' => 5000,
            'currency' => 'XAF',
        ]);

        $case = app(ReconciliationCaseService::class)->open(
            $payment,
            ReconciliationDiscrepancy::UNKNOWN,
            ['provider' => 'mtn_momo']
        );

        $this->expectException(InvalidArgumentException::class);

        app(ReconciliationCaseService::class)->resolve($case, $resolver, '   ');
    }
}
