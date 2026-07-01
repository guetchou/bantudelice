<?php

namespace Tests\Feature;

use App\FinancialLedgerEntry;
use App\Payment;
use App\Services\PaymentRefundService;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentRefundServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_partial_then_total_refund_updates_payment_and_ledger(): void
    {
        $user = User::factory()->create(['phone' => '0600019020']);
        $payment = Payment::create([
            'user_id' => $user->id,
            'provider' => 'momo',
            'status' => 'PAID',
            'business_status' => 'confirmed',
            'amount' => 10000,
            'currency' => 'XAF',
        ]);

        $service = app(PaymentRefundService::class);

        $first = $service->request(
            $payment,
            4000,
            'Article indisponible',
            'refund:payment:1:part-1',
            $user->id,
        );
        $first = $service->approve($first, $user->id);
        $first = $service->submit($first, 'REFUND-PROVIDER-1');
        $first = $service->markRefunded($first);

        $this->assertSame('refunded', $first->status);
        $this->assertSame('partially_refunded', $payment->fresh()->business_status);
        $this->assertSame(6000, $payment->fresh()->refundableAmount());
        $this->assertDatabaseHas('financial_ledger_entries', [
            'payment_id' => $payment->id,
            'source_type' => 'payment_refund',
            'source_id' => $first->id,
            'entry_type' => 'refund',
            'direction' => 'debit',
            'amount' => 4000,
        ]);

        $second = $service->request(
            $payment->fresh(),
            6000,
            'Annulation du solde',
            'refund:payment:1:part-2',
            $user->id,
        );
        $second = $service->approve($second, $user->id);
        $second = $service->submit($second, 'REFUND-PROVIDER-2');
        $service->markRefunded($second);

        $this->assertSame('refunded', $payment->fresh()->business_status);
        $this->assertSame(0, $payment->fresh()->refundableAmount());
        $this->assertSame(10000, $payment->fresh()->refundedAmount());
        $this->assertSame(2, FinancialLedgerEntry::where('entry_type', 'refund')->count());
    }

    public function test_refund_request_is_idempotent_and_cannot_exceed_payment(): void
    {
        $user = User::factory()->create(['phone' => '0600019021']);
        $payment = Payment::create([
            'user_id' => $user->id,
            'provider' => 'airtel_money',
            'status' => 'PAID',
            'business_status' => 'confirmed',
            'amount' => 5000,
            'currency' => 'XAF',
        ]);

        $service = app(PaymentRefundService::class);
        $first = $service->request($payment, 3000, 'Remboursement partiel', 'refund:idem:1');
        $same = $service->request($payment, 3000, 'Remboursement partiel', 'refund:idem:1');

        $this->assertSame($first->id, $same->id);

        $this->expectException(\DomainException::class);
        $service->request($payment, 3000, 'Dépassement', 'refund:idem:2');
    }

    public function test_pending_payment_cannot_be_refunded(): void
    {
        $user = User::factory()->create(['phone' => '0600019022']);
        $payment = Payment::create([
            'user_id' => $user->id,
            'provider' => 'momo',
            'status' => 'PENDING',
            'business_status' => 'pending',
            'amount' => 5000,
            'currency' => 'XAF',
        ]);

        $this->expectException(\DomainException::class);
        app(PaymentRefundService::class)
            ->request($payment, 1000, 'Pas encore confirmé', 'refund:pending:1');
    }

    public function test_refund_reversal_restores_payment_and_creates_counter_entry(): void
    {
        $user = User::factory()->create(['phone' => '0600019023']);
        $payment = Payment::create([
            'user_id' => $user->id,
            'provider' => 'momo',
            'status' => 'PAID',
            'business_status' => 'confirmed',
            'amount' => 7000,
            'currency' => 'XAF',
        ]);

        $service = app(PaymentRefundService::class);
        $refund = $service->request($payment, 7000, 'Annulation', 'refund:reverse:1');
        $refund = $service->approve($refund, $user->id);
        $refund = $service->submit($refund, 'REFUND-REV-1');
        $refund = $service->markRefunded($refund);
        $refund = $service->reverse($refund, 'Remboursement inversé par le fournisseur', $user->id);

        $this->assertSame('reversed', $refund->status);
        $this->assertSame('confirmed', $payment->fresh()->business_status);
        $this->assertDatabaseHas('financial_ledger_entries', [
            'source_type' => 'ledger_entry',
            'entry_type' => 'reversal',
            'direction' => 'credit',
            'amount' => 7000,
        ]);
    }
}
