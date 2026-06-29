<?php

namespace Tests\Feature;

use App\Domain\Payment\Enums\PaymentStatus;
use App\Domain\Payment\Services\PaymentStateMachine;
use App\Payment;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use LogicException;
use Tests\TestCase;

class PaymentStateMachineTest extends TestCase
{
    use RefreshDatabase;

    public function test_provider_flow_is_audited_idempotent_and_legacy_compatible(): void
    {
        $user = User::factory()->create();
        $payment = Payment::query()->create([
            'user_id' => $user->id,
            'provider' => 'mtn_momo',
            'provider_reference' => 'MTN-STATE-001',
            'idempotency_key' => 'payment-state-001',
            'status' => 'PENDING',
            'canonical_status' => PaymentStatus::SUBMITTED->value,
            'status_version' => 0,
            'amount' => 10000,
            'currency' => 'XAF',
        ]);

        $machine = app(PaymentStateMachine::class);

        $pending = $machine->transition(
            $payment,
            PaymentStatus::PENDING,
            'provider_poll',
            'transition:MTN-STATE-001:pending'
        );
        $successful = $machine->transition(
            $payment->fresh(),
            PaymentStatus::SUCCESSFUL,
            'provider_callback',
            'transition:MTN-STATE-001:successful',
            null,
            ['provider_status' => 'SUCCESSFUL']
        );
        $sameSuccessful = $machine->transition(
            $payment->fresh(),
            PaymentStatus::SUCCESSFUL,
            'provider_callback',
            'transition:MTN-STATE-001:successful',
            null,
            ['provider_status' => 'SUCCESSFUL']
        );

        $fresh = $payment->fresh();

        $this->assertSame(PaymentStatus::PENDING, $pending->to_status);
        $this->assertSame(PaymentStatus::SUCCESSFUL, $successful->to_status);
        $this->assertSame($successful->id, $sameSuccessful->id);
        $this->assertSame('PAID', $fresh->status);
        $this->assertSame(PaymentStatus::SUCCESSFUL->value, $fresh->canonical_status);
        $this->assertSame(2, $fresh->status_version);
        $this->assertDatabaseCount('payment_status_transitions', 2);
    }

    public function test_forbidden_transition_is_rejected_from_canonical_status(): void
    {
        $user = User::factory()->create();
        $payment = Payment::query()->create([
            'user_id' => $user->id,
            'provider' => 'airtel_money',
            'provider_reference' => 'AIRTEL-STATE-001',
            'idempotency_key' => 'payment-state-002',
            'status' => 'PENDING',
            'canonical_status' => PaymentStatus::REFUNDED->value,
            'amount' => 5000,
            'currency' => 'XAF',
        ]);

        $this->expectException(LogicException::class);

        app(PaymentStateMachine::class)->transition(
            $payment,
            PaymentStatus::PENDING,
            'admin',
            'transition:AIRTEL-STATE-001:pending',
            'Tentative de réouverture.'
        );
    }

    public function test_reversal_requires_a_reason(): void
    {
        $user = User::factory()->create();
        $payment = Payment::query()->create([
            'user_id' => $user->id,
            'provider' => 'mtn_momo',
            'provider_reference' => 'MTN-STATE-REV-001',
            'idempotency_key' => 'payment-state-003',
            'status' => 'PAID',
            'canonical_status' => PaymentStatus::SUCCESSFUL->value,
            'amount' => 15000,
            'currency' => 'XAF',
        ]);

        $this->expectException(InvalidArgumentException::class);

        app(PaymentStateMachine::class)->transition(
            $payment,
            PaymentStatus::REVERSED,
            'provider_callback',
            'transition:MTN-STATE-REV-001:reversed'
        );
    }
}
