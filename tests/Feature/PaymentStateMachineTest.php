<?php

namespace Tests\Feature;

use App\Domain\Payment\Enums\PaymentStatus;
use App\Domain\Payment\Services\PaymentStateMachine;
use App\Payment;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
}
