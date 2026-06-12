<?php

namespace Tests\Feature\Colis;

use App\User;
use App\Driver;
use App\Domain\Colis\Models\Shipment;
use App\Domain\Colis\Enums\ShipmentStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
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
        $courier = $this->createNearbyCourier(-4.2636, 15.2431);

        $response = $this->actingAs($user, 'api')->postJson('/api/v1/colis/shipments', [
            'weight_kg' => 1.5,
            'service_level' => 'express',
            'pickup_address' => [
                'full_name' => 'Jean Exp',
                'phone' => '061234567',
                'city' => 'Brazzaville',
                'district' => 'Centre',
                'address_line' => 'Avenue Foch',
                'lat' => -4.2634,
                'lng' => 15.2429,
            ],
            'dropoff_address' => [
                'full_name' => 'Marie Dest',
                'phone' => '051234567',
                'city' => 'Pointe-Noire',
                'district' => 'Lumumba',
                'address_line' => 'Rue de la Gare',
                'lat' => -4.7692,
                'lng' => 11.8664,
            ],
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('address_quality.pickup.level', 'street')
            ->assertJsonPath('address_quality.dropoff.level', 'street')
            ->assertJsonPath('address_quality.pickup.coordinates_present', true)
            ->assertJsonPath('address_quality.pickup.administrative.department', 'Brazzaville')
            ->assertJsonPath('address_quality.pickup.administrative.commune', 'Brazzaville')
            ->assertJsonPath('address_quality.dropoff.administrative.department', 'Pointe-Noire')
            ->assertJsonPath('address_quality.dropoff.administrative.commune', 'Pointe-Noire')
            ->assertJsonPath('address_quality.dropoff.administrative.district', 'Lumumba')
            ->assertJsonPath('serviceability.available_couriers_count', 1)
            ->assertJsonPath('serviceability.serviceable', true)
            ->assertJsonPath('serviceability.capacity_state', 'tight')
            ->assertJsonPath('serviceability.dispatch_mode', 'immediate')
            ->assertJsonPath('serviceability.dispatch_retry_after_minutes', 0)
            ->assertJsonPath('serviceability.auto_assigned_courier_id', $courier->id)
            ->assertJsonPath('serviceability.best_courier.id', $courier->id)
            ->assertJsonPath('serviceability.assigned_courier.id', $courier->id)
            ->assertJsonPath('serviceability.pickup_window_minutes.min', 5);

        $payload = $response->json();
        $this->assertGreaterThan(600, data_get($payload, 'serviceability.delivery_window_minutes.min'));
        $this->assertGreaterThan(
            data_get($payload, 'serviceability.delivery_window_minutes.min'),
            data_get($payload, 'serviceability.delivery_window_minutes.max')
        );

        $this->assertDatabaseHas('shipments', [
            'customer_id' => $user->id,
            'assigned_courier_id' => $courier->id,
        ]);
        $this->assertDatabaseHas('shipment_addresses', ['full_name' => 'Jean Exp', 'type' => 'pickup']);
    }

    public function test_express_shipment_is_rejected_when_no_courier_is_available_near_pickup()
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'api')
            ->postJson('/api/v1/colis/shipments', [
                'weight_kg' => 1.5,
                'service_level' => 'express',
                'pickup_address' => [
                    'full_name' => 'Jean Exp',
                    'phone' => '061234567',
                    'city' => 'Brazzaville',
                    'district' => 'Centre',
                    'address_line' => 'Avenue Foch',
                    'lat' => -4.2634,
                    'lng' => 15.2429,
                ],
                'dropoff_address' => [
                    'full_name' => 'Marie Dest',
                    'phone' => '051234567',
                    'city' => 'Pointe-Noire',
                    'district' => 'Lumumba',
                    'address_line' => 'Rue de la Gare',
                    'lat' => -4.7692,
                    'lng' => 11.8664,
                ],
            ])
            ->assertStatus(409)
            ->assertJson([
                'message' => 'Aucun coursier disponible autour du point de ramassage pour le moment.',
                'available_couriers_count' => 0,
                'serviceable' => false,
            ])
            ->assertJsonPath('capacity_state', 'unavailable')
            ->assertJsonPath('dispatch_retry_after_minutes', 8);
    }

    public function test_standard_shipment_is_created_and_queued_when_no_courier_is_available()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'api')
            ->postJson('/api/v1/colis/shipments', [
                'weight_kg' => 1.5,
                'service_level' => 'standard',
                'pickup_address' => [
                    'full_name' => 'Jean Exp',
                    'phone' => '061234567',
                    'city' => 'Brazzaville',
                    'district' => 'Centre',
                    'address_line' => 'Avenue Foch',
                    'lat' => -4.2634,
                    'lng' => 15.2429,
                ],
                'dropoff_address' => [
                    'full_name' => 'Marie Dest',
                    'phone' => '051234567',
                    'city' => 'Pointe-Noire',
                    'district' => 'Lumumba',
                    'address_line' => 'Rue de la Gare',
                    'lat' => -4.7692,
                    'lng' => 11.8664,
                ],
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('serviceability.available_couriers_count', 0)
            ->assertJsonPath('serviceability.serviceable', false)
            ->assertJsonPath('serviceability.capacity_state', 'unavailable')
            ->assertJsonPath('serviceability.assignment_status', 'queued_for_dispatch')
            ->assertJsonPath('serviceability.dispatch_mode', 'queued')
            ->assertJsonPath('serviceability.dispatch_retry_after_minutes', 15)
            ->assertJsonPath('serviceability.auto_assigned_courier_id', null)
            ->assertJsonPath('address_quality.pickup.level', 'street');
    }

    public function test_standard_shipment_requires_explicit_confirmation_for_imprecise_dropoff_address()
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'api')
            ->postJson('/api/v1/colis/shipments', [
                'weight_kg' => 1.5,
                'service_level' => 'standard',
                'pickup_address' => [
                    'full_name' => 'Jean Exp',
                    'phone' => '061234567',
                    'city' => 'Brazzaville',
                    'district' => 'Centre',
                    'address_line' => 'Avenue Foch',
                    'lat' => -4.2634,
                    'lng' => 15.2429,
                ],
                'pickup_address_confirmed' => true,
                'dropoff_address' => [
                    'full_name' => 'Marie Dest',
                    'phone' => '051234567',
                    'city' => 'Pointe-Noire',
                    'district' => 'Lumumba',
                    'address_line' => 'Bloc',
                    'lat' => -4.7692,
                    'lng' => 11.8664,
                ],
            ])
            ->assertStatus(422)
            ->assertJson([
                'message' => 'Confirmez precisement le point de livraison avant de continuer.',
            ])
            ->assertJsonPath('address_quality.dropoff.level', 'district')
            ->assertJsonPath('address_quality.dropoff.administrative.district', 'Lumumba')
            ->assertJsonPath('errors.dropoff_address_confirmed.0', 'Confirmation precise de la livraison requise');
    }

    public function test_express_shipment_rejects_imprecise_pickup_address(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'api')
            ->postJson('/api/v1/colis/shipments', [
                'weight_kg' => 1.5,
                'service_level' => 'express',
                'pickup_address' => [
                    'full_name' => 'Jean Exp',
                    'phone' => '061234567',
                    'city' => 'Brazzaville',
                    'district' => 'Centre',
                    'address_line' => 'Bloc',
                    'lat' => -4.2634,
                    'lng' => 15.2429,
                ],
                'dropoff_address' => [
                    'full_name' => 'Marie Dest',
                    'phone' => '051234567',
                    'city' => 'Pointe-Noire',
                    'district' => 'Lumumba',
                    'address_line' => 'Rue de la Gare',
                    'lat' => -4.7692,
                    'lng' => 11.8664,
                ],
            ])
            ->assertStatus(422)
            ->assertJson([
                'message' => 'Adresse de ramassage trop imprecise pour un envoi express.',
            ])
            ->assertJsonPath('address_quality.pickup.level', 'district')
            ->assertJsonPath('address_quality.pickup.coordinates_present', true);
    }

    public function test_public_can_track_shipment()
    {
        $shipment = Shipment::factory()->create(['tracking_number' => 'BD-TEST-123']);
        
        $response = $this->getJson('/api/v1/colis/track/BD-TEST-123');

        $response->assertStatus(200)
            ->assertJsonPath('tracking_number', 'BD-TEST-123')
            ->assertJsonPath('status', 'created');
    }

    public function test_public_tracking_returns_not_found_for_unknown_tracking_number()
    {
        $response = $this->getJson('/api/v1/colis/track/BD-UNKNOWN-999');

        $response->assertStatus(404)
            ->assertJson([
                'status' => false,
                'message' => 'Colis introuvable',
            ]);
    }

    public function test_customer_cannot_view_or_check_payment_status_for_foreign_shipment()
    {
        $user = User::factory()->create();
        $foreignShipment = Shipment::factory()->create();

        $this->actingAs($user, 'api')
            ->getJson('/api/v1/colis/shipments/' . $foreignShipment->id)
            ->assertStatus(403);

        $this->actingAs($user, 'api')
            ->getJson('/api/v1/colis/shipments/' . $foreignShipment->id . '/payment-status')
            ->assertStatus(403);
    }

    public function test_customer_cannot_pay_foreign_shipment_and_cannot_repay_paid_shipment()
    {
        $user = User::factory()->create();
        $foreignShipment = Shipment::factory()->create([
            'payment_status' => 'unpaid',
        ]);
        $ownedPaidShipment = Shipment::factory()->create([
            'customer_id' => $user->id,
            'payment_status' => 'paid',
        ]);

        $this->actingAs($user, 'api')
            ->postJson('/api/v1/colis/shipments/' . $foreignShipment->id . '/payment', [
                'provider' => 'cod',
            ])
            ->assertStatus(403);

        $this->actingAs($user, 'api')
            ->postJson('/api/v1/colis/shipments/' . $ownedPaidShipment->id . '/payment', [
                'provider' => 'cod',
            ])
            ->assertStatus(422)
            ->assertJson([
                'message' => 'Ce colis est déjà payé.',
            ]);
    }

    protected function createNearbyCourier(float $lat, float $lng): Driver
    {
        $owner = User::factory()->create(['type' => 'user']);

        $restaurantId = DB::table('restaurants')->insertGetId([
            'user_id' => $owner->id,
            'name' => 'Hub coursier',
            'user_name' => 'hub-coursier-' . uniqid(),
            'email' => 'hub-' . uniqid() . '@example.com',
            'password' => bcrypt('secret'),
            'services' => 'delivery',
            'delivery_charges' => 0,
            'city' => 'Brazzaville',
            'tax' => 0,
            'address' => 'Centre ville',
            'latitude' => $lat,
            'longitude' => $lng,
            'phone' => '06' . str_pad((string) random_int(1000000, 9999999), 7, '0', STR_PAD_LEFT),
            'description' => 'Hub test',
            'min_order' => 0,
            'admin_commission' => 0,
            'approved' => 1,
            'featured' => 0,
            'account_name' => 'Hub test',
            'account_number' => 'ACC-' . uniqid(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return Driver::create([
            'restaurant_id' => $restaurantId,
            'name' => 'Coursier dispo',
            'user_name' => 'courier-' . uniqid(),
            'hourly_pay' => 0,
            'email' => 'courier-' . uniqid() . '@example.com',
            'cnic' => 'CNIC-' . uniqid(),
            'password' => bcrypt('secret'),
            'phone' => '05' . str_pad((string) random_int(1000000, 9999999), 7, '0', STR_PAD_LEFT),
            'image' => null,
            'address' => 'Brazzaville',
            'latitude' => $lat,
            'longitude' => $lng,
            'status' => 'online',
            'approved' => true,
        ]);
    }
}
