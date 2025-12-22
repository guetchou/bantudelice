<?php

namespace Tests\Feature\Colis;

use App\User;
use App\Driver;
use App\Domain\Colis\Models\Shipment;
use App\Domain\Colis\Enums\ShipmentStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShipmentApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_get_quote()
    {
        $response = $this->postJson('/api/v1/colis/quotes', [
            'weight_kg' => 2,
            'service_level' => 'standard',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['price_breakdown', 'total_price']);
    }

    public function test_customer_can_create_shipment()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/colis/shipments', [
            'weight_kg' => 1.5,
            'service_level' => 'express',
            'pickup_address' => [
                'full_name' => 'Jean Exp',
                'phone' => '061234567',
                'city' => 'Brazzaville',
                'district' => 'Centre',
                'address_line' => 'Avenue Foch',
            ],
            'dropoff_address' => [
                'full_name' => 'Marie Dest',
                'phone' => '051234567',
                'city' => 'Pointe-Noire',
                'district' => 'Lumumba',
                'address_line' => 'Rue de la Gare',
            ],
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('shipments', ['customer_id' => $user->id]);
        $this->assertDatabaseHas('shipment_addresses', ['full_name' => 'Jean Exp', 'type' => 'pickup']);
    }

    public function test_public_can_track_shipment()
    {
        $shipment = Shipment::factory()->create(['tracking_number' => 'BD-TEST-123']);
        
        $response = $this->getJson('/api/v1/colis/track/BD-TEST-123');

        $response->assertStatus(200)
            ->assertJsonPath('tracking_number', 'BD-TEST-123')
            ->assertJsonPath('status', 'created');
    }
}

