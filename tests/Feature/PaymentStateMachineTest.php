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

    public function test_submitted_payment_can_move_to_pending(): void
    {
        $user = User::factory()->create();
        $payment = Payment::query()->create([
            'user_id' => $user->id,
            'provider' => 'mtn_momo',
            'provider_reference' => 'MTN-STATE-001',
            'idempotency_key' => 'payment-state-001',
            'status' => 'SUBMITTED',
            'amount' => 10000,
            'currency' => 'XAF',
        ]);

        $transition = app(PaymentStateMachine::class)->transition(
            $payment,
            PaymentStatus::PENDING,
            'provider_poll',
            'transition:MTN-STATE-001:pending'
        );

        $this->assertSame(PaymentStatus::PENDING, $transition->to_status);
        $this->assertSame('PENDING', $payment->fresh()->status);
        $this->assertDatabaseCount('payment_status_transitions', 1);
    }
}
