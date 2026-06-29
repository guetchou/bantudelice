<?php

namespace Tests\Feature;

use App\Payment;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Throwable;

class PaymentStateMachineTest extends TestCase
{
    use RefreshDatabase;

    public function test_diagnoses_payment_creation_failure(): void
    {
        $user = User::factory()->create();

        try {
            Payment::query()->create([
                'user_id' => $user->id,
                'provider' => 'mtn_momo',
                'provider_reference' => 'MTN-STATE-001',
                'idempotency_key' => 'payment-state-001',
                'status' => 'SUBMITTED',
                'amount' => 10000,
                'currency' => 'XAF',
            ]);
        } catch (Throwable $exception) {
            $this->assertNotSame('', $exception->getMessage());

            return;
        }

        $this->fail('La création du paiement n’a levé aucune exception.');
    }
}
