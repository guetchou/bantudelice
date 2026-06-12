<?php

namespace Tests\Feature;

use App\Payment;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_cannot_show_unknown_or_foreign_payment(): void
    {
        $customer = User::factory()->create(['type' => 'user']);
        $foreignCustomer = User::factory()->create(['type' => 'user']);

        $foreignPayment = Payment::create([
            'user_id' => $foreignCustomer->id,
            'provider' => 'momo',
            'provider_reference' => 'PAY-FOREIGN-1',
            'status' => 'PENDING',
            'amount' => 4700,
            'currency' => 'XAF',
            'meta' => ['instructions' => ['Confirmez le paiement sur votre telephone.']],
        ]);

        $this->actingAs($customer, 'api')
            ->getJson('/api/payments/' . $foreignPayment->id)
            ->assertStatus(404)
            ->assertJson([
                'status' => false,
                'message' => 'Paiement introuvable',
            ]);
    }

    public function test_customer_cannot_confirm_unknown_or_foreign_payment(): void
    {
        $customer = User::factory()->create(['type' => 'user']);
        $foreignCustomer = User::factory()->create(['type' => 'user']);

        $foreignPayment = Payment::create([
            'user_id' => $foreignCustomer->id,
            'provider' => 'momo',
            'provider_reference' => 'PAY-FOREIGN-2',
            'status' => 'PENDING',
            'amount' => 4700,
            'currency' => 'XAF',
            'meta' => ['instructions' => ['Confirmez le paiement sur votre telephone.']],
        ]);

        $this->actingAs($customer, 'api')
            ->postJson('/api/payments/' . $foreignPayment->id . '/confirm')
            ->assertStatus(404)
            ->assertJson([
                'status' => false,
                'message' => 'Paiement introuvable',
            ]);
    }
}
