<?php

namespace Tests\Feature;

use App\Domain\Food\Services\OrderAcceptanceService;
use App\Order;
use App\User;
use App\Driver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CheckoutApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_checkout_requires_authentication(): void
    {
        $this->postJson('/api/checkout', [
            'payment_method' => 'cash',
            'delivery_address' => 'Adresse test',
        ])
            ->assertStatus(401)
            ->assertJson([
                'status' => false,
                'message' => 'Non authentifié',
            ]);
    }

    public function test_checkout_rejects_foreign_address_id(): void
    {
        $user = User::factory()->create(['type' => 'user']);
        $otherUser = User::factory()->create(['type' => 'user']);

        $foreignAddressId = DB::table('user_address')->insertGetId([
            'user_id' => $otherUser->id,
            'title' => 'Maison',
            'building_no' => '12',
            'street_no' => 'A',
            'area' => 'Centre',
            'floor' => null,
            'latitude' => -4.2634,
            'longitude' => 15.2429,
            'complete_address' => 'Avenue test',
            'is_default' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($user, 'api')
            ->postJson('/api/checkout', [
                'payment_method' => 'cash',
                'address_id' => $foreignAddressId,
            ])
            ->assertStatus(404)
            ->assertJson([
                'status' => false,
                'message' => 'Adresse introuvable',
            ]);
    }

    public function test_checkout_rejects_delivery_when_no_operational_driver_is_available(): void
    {
        $user = User::factory()->create(['type' => 'user']);
        $owner = User::factory()->create(['type' => 'user']);

        $restaurantId = DB::table('restaurants')->insertGetId([
            'user_id' => $owner->id,
            'name' => 'Restaurant test',
            'user_name' => 'restaurant-test',
            'email' => 'restaurant-test@example.com',
            'password' => bcrypt('secret'),
            'services' => 'food',
            'delivery_charges' => 1000,
            'city' => 'Brazzaville',
            'tax' => 5,
            'address' => 'Avenue de la Paix',
            'latitude' => -4.2634,
            'longitude' => 15.2429,
            'phone' => '0600001000',
            'description' => 'Test',
            'min_order' => 1000,
            'admin_commission' => 5,
            'approved' => 1,
            'featured' => 0,
            'account_name' => 'Restaurant test',
            'account_number' => 'ACC-REST-001',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $categoryId = DB::table('categories')->insertGetId([
            'restaurant_id' => $restaurantId,
            'name' => 'Plats',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $productId = DB::table('products')->insertGetId([
            'category_id' => $categoryId,
            'restaurant_id' => $restaurantId,
            'name' => 'Poulet braise',
            'image' => 'test.webp',
            'price' => 3500,
            'discount_price' => 0,
            'description' => 'Plat test',
            'featured' => 0,
            'size' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('carts')->insert([
            'restaurant_id' => $restaurantId,
            'user_id' => $user->id,
            'product_id' => $productId,
            'qty' => 1,
            'price' => 3500,
            'latitude' => null,
            'longitude' => null,
            'description' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($user, 'api')
            ->postJson('/api/checkout', [
                'payment_method' => 'cash',
                'delivery_address' => 'Avenue de la Paix',
                'd_lat' => -4.2700,
                'd_lng' => 15.2800,
            ])
            ->assertStatus(422)
            ->assertJson([
                'status' => false,
                'message' => 'Aucun livreur disponible autour du restaurant pour le moment.',
            ])
            ->assertJsonPath('delivery_serviceability.available_drivers_count', 0)
            ->assertJsonPath('delivery_serviceability.serviceable', false)
            ->assertJsonPath('delivery_serviceability.capacity_state', 'stable')
            ->assertJsonPath('delivery_serviceability.next_capacity_check_minutes', 8)
            ->assertJsonPath('delivery_address_quality.level', 'street')
            ->assertJsonPath('delivery_address_quality.coordinates_present', true);
    }

    public function test_checkout_rejects_delivery_without_coordinates_for_typed_address(): void
    {
        $user = User::factory()->create(['type' => 'user']);

        $this->actingAs($user, 'api')
            ->postJson('/api/checkout', [
                'payment_method' => 'cash',
                'delivery_address' => 'Avenue de la Paix',
            ])
            ->assertStatus(422)
            ->assertJson([
                'status' => false,
                'message' => 'Coordonnees de livraison requises',
            ])
            ->assertJsonPath('errors.d_lat.0', 'Latitude de livraison requise')
            ->assertJsonPath('errors.d_lng.0', 'Longitude de livraison requise');
    }

    public function test_checkout_requires_explicit_confirmation_for_imprecise_delivery_address(): void
    {
        $user = User::factory()->create(['type' => 'user']);

        $this->actingAs($user, 'api')
            ->postJson('/api/checkout', [
                'payment_method' => 'cash',
                'delivery_address' => 'Bloc',
                'delivery_area' => 'Ouenzé',
                'delivery_city' => 'Brazzaville',
                'delivery_department' => 'Brazzaville',
                'd_lat' => -4.2438,
                'd_lng' => 15.2819,
            ])
            ->assertStatus(422)
            ->assertJson([
                'status' => false,
                'message' => 'Confirmez precisement l adresse de livraison avant de continuer.',
            ])
            ->assertJsonPath('delivery_address_quality.level', 'district')
            ->assertJsonPath('delivery_address_quality.administrative.district', 'Ouenzé')
            ->assertJsonPath('errors.delivery_address_confirmed.0', 'Confirmation precise de l adresse requise');
    }

    public function test_checkout_assigns_delivery_immediately_when_driver_is_available(): void
    {
        $user = User::factory()->create(['type' => 'user']);
        $owner = User::factory()->create(['type' => 'user']);

        $restaurantId = DB::table('restaurants')->insertGetId([
            'user_id' => $owner->id,
            'name' => 'Restaurant assignation',
            'user_name' => 'restaurant-assignation',
            'email' => 'restaurant-assignation@example.com',
            'password' => bcrypt('secret'),
            'services' => 'food',
            'delivery_charges' => 1000,
            'city' => 'Brazzaville',
            'tax' => 5,
            'address' => 'Avenue de la Paix',
            'latitude' => -4.2634,
            'longitude' => 15.2429,
            'phone' => '0600001001',
            'description' => 'Test',
            'min_order' => 1000,
            'admin_commission' => 5,
            'approved' => 1,
            'featured' => 0,
            'account_name' => 'Restaurant assignation',
            'account_number' => 'ACC-REST-002',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $driver = Driver::create([
            'restaurant_id' => $restaurantId,
            'name' => 'Livreur dispo',
            'user_name' => 'livreur-dispo',
            'hourly_pay' => 0,
            'email' => 'livreur-dispo@example.com',
            'cnic' => 'CNIC-LIV-001',
            'password' => bcrypt('secret'),
            'phone' => '0500001001',
            'image' => null,
            'address' => 'Brazzaville',
            'latitude' => -4.2635,
            'longitude' => 15.2430,
            'status' => 'online',
            'approved' => true,
        ]);

        DB::table('driver_locations')->insert([
            'driver_id' => $driver->id,
            'latitude' => -4.2635,
            'longitude' => 15.2430,
            'accuracy' => 10,
            'heading' => 0,
            'speed' => 0,
            'timestamp' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $categoryId = DB::table('categories')->insertGetId([
            'restaurant_id' => $restaurantId,
            'name' => 'Plats',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $productId = DB::table('products')->insertGetId([
            'category_id' => $categoryId,
            'restaurant_id' => $restaurantId,
            'name' => 'Poisson braise',
            'image' => 'test.webp',
            'price' => 4500,
            'discount_price' => 0,
            'description' => 'Plat test',
            'featured' => 0,
            'size' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('carts')->insert([
            'restaurant_id' => $restaurantId,
            'user_id' => $user->id,
            'product_id' => $productId,
            'qty' => 1,
            'price' => 4500,
            'latitude' => null,
            'longitude' => null,
            'description' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($user, 'api')
            ->postJson('/api/checkout', [
                'payment_method' => 'cash',
                'delivery_address' => 'Avenue de la Paix',
                'delivery_address_confirmed' => true,
                'd_lat' => -4.2700,
                'd_lng' => 15.2800,
            ]);

        $response = $response
            ->assertOk()
            ->json();

        $this->assertNotNull($response['order']['order_no'] ?? null);
        $this->assertSame(1, $response['delivery_serviceability']['available_drivers_count'] ?? null);
        $this->assertTrue($response['delivery_serviceability']['serviceable'] ?? false);
        $this->assertSame('stable', $response['delivery_serviceability']['capacity_state'] ?? null);
        $this->assertSame('street', $response['delivery_address_quality']['level'] ?? null);
        $this->assertTrue($response['delivery_address_quality']['coordinates_present'] ?? false);
        $this->assertTrue($response['delivery_address_quality']['requires_manual_confirmation'] ?? false);
        $this->assertSame(0, $response['delivery_serviceability']['kitchen_load'] ?? null);
        $this->assertGreaterThanOrEqual(16, $response['delivery_serviceability']['prep_window_minutes']['min'] ?? 0);
        $this->assertGreaterThanOrEqual(4, $response['delivery_serviceability']['pickup_window_minutes']['min'] ?? 0);
        $this->assertGreaterThanOrEqual(30, $response['delivery_serviceability']['delivery_window_minutes']['min'] ?? 0);
        $this->assertGreaterThan(
            $response['delivery_serviceability']['delivery_window_minutes']['min'] ?? 0,
            $response['delivery_serviceability']['delivery_window_minutes']['max'] ?? 0
        );
        // Plus de Payment/Delivery créé au checkout : paiement et livraison sont différés
        // à l'acceptation restaurant (voir OrderAcceptanceService). Le checkout ne fait
        // qu'évaluer la capacité de livraison de manière informative.
        $this->assertArrayNotHasKey('delivery_assignment', $response);
        $orderNo = $response['order']['order_no'];

        $this->assertDatabaseMissing('deliveries', [
            'restaurant_id' => $restaurantId,
        ]);

        $this->assertDatabaseHas('orders', [
            'order_no' => $orderNo,
            'driver_id' => null,
            'total_items' => 1,
            'business_status' => 'pending_restaurant_acceptance',
        ]);

        // Le restaurant accepte la commande -> déclenchement différé du paiement/livraison.
        $order = Order::where('order_no', $orderNo)->first();
        app(OrderAcceptanceService::class)->handleAccepted($order);

        $this->assertDatabaseHas('orders', [
            'order_no' => $orderNo,
            'business_status' => 'in_kitchen',
            'payment_status' => 'cash_due',
            'cash_collection_status' => 'pending_collection',
        ]);

        $this->assertDatabaseHas('deliveries', [
            'restaurant_id' => $restaurantId,
            'status' => 'PENDING',
        ]);
    }
}
