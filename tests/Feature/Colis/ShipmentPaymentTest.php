<?php

namespace Tests\Feature\Colis;

use App\Domain\Colis\Enums\ShipmentStatus;
use App\Domain\Colis\Models\Shipment;
use App\Payment;
use App\Services\PaymentService;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShipmentPaymentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // No seeder, manual data creation in tests
    }

    public function test_customer_can_choose_cod_payment()
    {
        $user = User::factory()->create();

        $shipment = Shipment::factory()->create([
            'customer_id' => $user->id,
            'status' => ShipmentStatus::CREATED,
            'payment_status' => 'unpaid'
        ]);

        $response = $this->actingAs($user, 'api')->postJson("/api/v1/colis/shipments/{$shipment->id}/payment", [
            'provider' => 'cod'
        ]);

        $response->assertStatus(200);
        $this->assertEquals('cod_pending', $shipment->refresh()->payment_status);
        $this->assertEquals(ShipmentStatus::PAID, $shipment->status); // PAID means confirmed for pickup
    }

    public function test_customer_can_initiate_momo_payment()
    {
        $user = User::factory()->create();

        $shipment = Shipment::factory()->create([
            'customer_id' => $user->id,
            'status' => ShipmentStatus::CREATED,
            'payment_status' => 'unpaid',
            'total_price' => 5000
        ]);

        $payment = Payment::create([
            'user_id' => $user->id,
            'shipment_id' => $shipment->id,
            'provider' => 'momo',
            'provider_reference' => 'TEST-MOMO-REF-001',
            'status' => 'PENDING',
            'amount' => 5000,
            'currency' => 'XAF',
            'meta' => [],
        ]);

        $this->mock(PaymentService::class, function ($mock) use ($payment) {
            $mock->shouldReceive('startManagedPayment')
                ->once()
                ->andReturn([
                    'payment' => $payment,
                    'payment_payload' => [
                        'redirect_url' => null,
                    ],
                ]);
        });

        $response = $this->actingAs($user, 'api')->postJson("/api/v1/colis/shipments/{$shipment->id}/payment", [
            'provider' => 'momo',
            'phone' => '060000000',
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure(['payment_id', 'checkout_url', 'status']);
        $this->assertEquals('unpaid', $shipment->refresh()->payment_status);

        $this->assertDatabaseHas('payments', [
            'id' => $response->json('payment_id'),
            'shipment_id' => $shipment->id,
            'status' => 'PENDING'
        ]);
    }
}
