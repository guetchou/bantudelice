<?php

namespace Tests\Feature;

use App\FinancialLedgerEntry;
use App\Payment;
use App\PaymentAllocation;
use App\PaymentReconciliationCase;
use App\Services\FinancialLedgerService;
use App\Services\PaymentAllocationService;
use App\Services\PaymentBusinessStateService;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentDomainFoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_confirmed_payment_can_be_allocated_once_without_exceeding_amount(): void
    {
        $user = User::factory()->create(['phone' => '0600019001']);
        $payment = Payment::create([
            'user_id' => $user->id,
            'provider' => 'momo',
            'status' => 'PAID',
            'business_status' => 'confirmed',
            'amount' => 10000,
            'currency' => 'XAF',
        ]);

        $service = app(PaymentAllocationService::class);
        $first = $service->allocate($payment, 'order', 99, 6000, 'alloc:payment:1:order:99');
        $same = $service->allocate($payment, 'order', 99, 6000, 'alloc:payment:1:order:99');

        $this->assertSame($first->id, $same->id);
        $this->assertSame(6000, $payment->fresh()->allocatedAmount());
        $this->assertSame(4000, $payment->fresh()->unallocatedAmount());

        $this->expectException(\DomainException::class);
        $service->allocate($payment, 'order', 100, 5000, 'alloc:payment:1:order:100');
    }

    public function test_unconfirmed_payment_cannot_be_allocated(): void
    {
        $user = User::factory()->create(['phone' => '0600019002']);
        $payment = Payment::create([
            'user_id' => $user->id,
            'provider' => 'momo',
            'status' => 'PENDING',
            'business_status' => 'pending',
            'amount' => 5000,
            'currency' => 'XAF',
        ]);

        $this->expectException(\DomainException::class);
        app(PaymentAllocationService::class)
            ->allocate($payment, 'order', 10, 5000, 'alloc:pending:10');
    }

    public function test_payment_business_state_rejects_illegal_transition(): void
    {
        $user = User::factory()->create(['phone' => '0600019003']);
        $payment = Payment::create([
            'user_id' => $user->id,
            'provider' => 'momo',
            'status' => 'PENDING',
            'business_status' => 'pending',
            'amount' => 5000,
            'currency' => 'XAF',
        ]);

        $service = app(PaymentBusinessStateService::class);
        $confirmed = $service->transition($payment, 'confirmed', ['reason' => 'provider_confirmed']);

        $this->assertSame('confirmed', $confirmed->business_status);
        $this->assertSame('PAID', $confirmed->status);
        $this->assertNotNull($confirmed->confirmed_at);

        $this->expectException(\DomainException::class);
        $service->transition($confirmed, 'pending');
    }

    public function test_ledger_is_idempotent_and_reversed_by_counter_entry(): void
    {
        $ledger = app(FinancialLedgerService::class);

        $entry = $ledger->record([
            'module' => 'food',
            'account_type' => 'restaurant',
            'account_id' => 44,
            'entry_type' => 'order_credit',
            'direction' => 'credit',
            'status' => 'posted',
            'source_type' => 'order',
            'source_id' => 88,
            'idempotency_key' => 'ledger:order:88:restaurant:44',
            'amount' => 12000,
            'currency' => 'XAF',
        ]);

        $same = $ledger->record([
            'module' => 'food',
            'account_type' => 'restaurant',
            'account_id' => 44,
            'entry_type' => 'order_credit',
            'direction' => 'credit',
            'status' => 'posted',
            'source_type' => 'order',
            'source_id' => 88,
            'idempotency_key' => 'ledger:order:88:restaurant:44',
            'amount' => 12000,
            'currency' => 'XAF',
        ]);

        $reversal = $ledger->reverse($entry, 'Commande annulée', 'ledger:order:88:restaurant:44:reversal');
        $position = $ledger->position('restaurant', 44);

        $this->assertSame($entry->id, $same->id);
        $this->assertSame('debit', $reversal->direction);
        $this->assertSame($entry->id, $reversal->related_entry_id);
        $this->assertSame(0.0, $position['balance']);
        $this->assertSame(2, $position['entries_count']);
    }

    public function test_ledger_entries_cannot_be_edited_or_deleted(): void
    {
        $entry = FinancialLedgerEntry::create([
            'module' => 'food',
            'account_type' => 'platform',
            'entry_type' => 'commission',
            'direction' => 'credit',
            'status' => 'posted',
            'amount' => 1000,
            'currency' => 'XAF',
            'source_type' => 'order',
            'source_id' => 1,
            'idempotency_key' => 'ledger:immutability:1',
        ]);

        try {
            $entry->update(['amount' => 500]);
            $this->fail('La modification d’une écriture aurait dû être refusée.');
        } catch (\LogicException $e) {
            $this->assertStringContainsString('contre-écriture', $e->getMessage());
        }

        $this->expectException(\LogicException::class);
        $entry->delete();
    }

    public function test_reconciliation_case_keeps_expected_and_observed_truths(): void
    {
        $case = PaymentReconciliationCase::create([
            'subject_type' => 'payment',
            'subject_id' => 77,
            'case_type' => 'amount_mismatch',
            'severity' => 'critical',
            'status' => 'open',
            'expected_amount' => 10000,
            'observed_amount' => 9000,
            'currency' => 'XAF',
            'internal_status' => 'confirmed',
            'provider_status' => 'SUCCESSFUL',
            'provider_reference' => 'PROVIDER-77',
            'evidence' => ['source' => 'provider_status_api'],
        ]);

        $this->assertNotNull($case->uuid);
        $this->assertTrue($case->isOpen());
        $this->assertSame(1000, $case->expected_amount - $case->observed_amount);
    }
}
