<?php

namespace Tests\Feature;

use App\Payment;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentStateMachineTest extends TestCase
{
    use RefreshDatabase;

    public function test_submitted_payment_can_be_created_for_state_transition(): void
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

        $this->assertSame('SUBMITTED', $payment->status);
        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => 'SUBMITTED',
        ]);
    }
}
