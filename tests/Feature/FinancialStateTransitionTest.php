<?php

namespace Tests\Feature;

use App\FinancialStateTransition;
use App\PartnerWithdrawal;
use App\Payment;
use App\Services\PaymentBusinessStateService;
use App\Services\PaymentRefundService;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinancialStateTransitionTest extends TestCase
{
    use RefreshDatabase;

    public function test_payment_transition_is_recorded_as_an_immutable_event(): void
    {
        $user = User::factory()->create(['phone' => '0600019030']);
        $payment = Payment::create([
            'user_id' => $user->id,
            'provider' => 'momo',
            'status' => 'PENDING',
            'business_status' => 'pending',
            'amount' => 5000,
            'currency' => 'XAF',
        ]);

        app(PaymentBusinessStateService::class)->transition($payment, 'confirmed', [
            'source' => 'provider_callback',
            'reason' => 'provider_successful',
            'idempotency_key' => 'payment-transition:1',
        ]);

        $event = FinancialStateTransition::where('subject_type', 'payment')
            ->where('subject_id', $payment->id)
            ->firstOrFail();

        $this->assertSame('pending', $event->from_status);
        $this->assertSame('confirmed', $event->to_status);
        $this->assertSame('provider_callback', $event->source);

        $this->expectException(\LogicException::class);
        $event->update(['to_status' => 'failed']);
    }

    public function test_refund_lifecycle_records_each_business_step(): void
    {
        $user = User::factory()->create(['phone' => '0600019031']);
        $payment = Payment::create([
            'user_id' => $user->id,
            'provider' => 'momo',
            'status' => 'PAID',
            'business_status' => 'confirmed',
            'amount' => 3000,
            'currency' => 'XAF',
        ]);

        $service = app(PaymentRefundService::class);
        $refund = $service->request($payment, 3000, 'Commande annulée', 'refund:lifecycle:1', $user->id);
        $refund = $service->approve($refund, $user->id);
        $refund = $service->submit($refund, 'PROVIDER-REFUND-LIFECYCLE');
        $service->markRefunded($refund);

        $statuses = FinancialStateTransition::where('subject_type', 'payment_refund')
            ->where('subject_id', $refund->id)
            ->orderBy('id')
            ->pluck('to_status')
            ->all();

        $this->assertSame(['requested', 'approved', 'submitted', 'refunded'], $statuses);
        $this->assertDatabaseHas('financial_state_transitions', [
            'subject_type' => 'payment',
            'subject_id' => $payment->id,
            'from_status' => 'confirmed',
            'to_status' => 'refunded',
        ]);
    }

    public function test_paid_withdrawal_can_only_be_reversed(): void
    {
        $withdrawal = PartnerWithdrawal::create([
            'partner_type' => 'restaurant',
            'partner_id' => 1,
            'operator' => 'mtn',
            'provider' => 'mtn_momo',
            'phone' => '0600019032',
            'requested_amount' => 5000,
            'fee_amount' => 0,
            'net_amount' => 5000,
            'currency' => 'XAF',
            'status' => 'paid',
            'external_reference' => 'WD-TRANSITION-TEST',
            'idempotency_key' => 'withdrawal:transition:test',
            'paid_at' => now(),
        ]);

        $this->assertTrue($withdrawal->canTransitionTo('reversed'));
        $this->assertFalse($withdrawal->canTransitionTo('pending'));

        $this->expectException(\DomainException::class);
        $withdrawal->update(['status' => 'pending']);
    }
}
