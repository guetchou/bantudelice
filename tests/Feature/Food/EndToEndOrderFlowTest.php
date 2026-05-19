<?php

namespace Tests\Feature\Food;

use App\Driver;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class EndToEndOrderFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_full_cash_delivery_flow_from_checkout_to_delivered(): void
    {
        // ── Acteurs ──────────────────────────────────────────────────────────
        $customer = User::factory()->create(['type' => 'user']);
        $owner    = User::factory()->create(['type' => 'user']);

        // ── Restaurant ───────────────────────────────────────────────────────
        $restaurantId = DB::table('restaurants')->insertGetId([
            'user_id'            => $owner->id,
            'name'               => 'Restaurant E2E',
            'user_name'          => 'restaurant-e2e',
            'email'              => 'restaurant-e2e@example.com',
            'password'           => bcrypt('secret'),
            'services'           => 'food',
            'delivery_charges'   => 1000,
            'city'               => 'Brazzaville',
            'tax'                => 5,
            'address'            => 'Avenue de la Paix',
            'latitude'           => -4.2634,
            'longitude'          => 15.2429,
            'phone'              => '0600002000',
            'description'        => 'Test E2E',
            'min_order'          => 1000,
            'admin_commission'   => 5,
            'approved'           => 1,
            'featured'           => 0,
            'account_name'       => 'Restaurant E2E',
            'account_number'     => 'ACC-E2E-001',
            'created_at'         => now(),
            'updated_at'         => now(),
        ]);

        // ── Livreur disponible à proximité du restaurant ──────────────────────
        $driver = Driver::create([
            'restaurant_id' => $restaurantId,
            'name'          => 'Livreur E2E',
            'user_name'     => 'livreur-e2e',
            'hourly_pay'    => 0,
            'email'         => 'livreur-e2e@example.com',
            'cnic'          => 'CNIC-E2E-001',
            'password'      => bcrypt('secret'),
            'phone'         => '0500002001',
            'image'         => null,
            'address'       => 'Brazzaville',
            'latitude'      => -4.2635,
            'longitude'     => 15.2430,
            'status'        => 'online',
            'approved'      => true,
        ]);

        // ── Catalogue ────────────────────────────────────────────────────────
        $categoryId = DB::table('categories')->insertGetId([
            'restaurant_id' => $restaurantId,
            'name'          => 'Plats',
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);

        $productId = DB::table('products')->insertGetId([
            'category_id'    => $categoryId,
            'restaurant_id'  => $restaurantId,
            'name'           => 'Saka-saka',
            'image'          => 'test.webp',
            'price'          => 2500,
            'discount_price' => 0,
            'description'    => 'Plat test E2E',
            'featured'       => 0,
            'size'           => null,
            'created_at'     => now(),
            'updated_at'     => now(),
        ]);

        // ── Panier client ────────────────────────────────────────────────────
        DB::table('carts')->insert([
            'restaurant_id' => $restaurantId,
            'user_id'       => $customer->id,
            'product_id'    => $productId,
            'qty'           => 1,
            'price'         => 2500,
            'latitude'      => null,
            'longitude'     => null,
            'description'   => null,
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);

        // ── Étape 1 : Checkout cash ───────────────────────────────────────────
        $checkoutResponse = $this->actingAs($customer, 'api')
            ->postJson('/api/checkout', [
                'payment_method'   => 'cash',
                'delivery_address' => 'Avenue de la Paix',
                'd_lat'            => -4.2700,
                'd_lng'            => 15.2800,
            ])
            ->assertOk()
            ->json();

        $this->assertSame('assigned', $checkoutResponse['delivery_assignment']['status'] ?? null);
        $this->assertSame($driver->id, $checkoutResponse['delivery_assignment']['driver_id'] ?? null);
        $this->assertNotNull($checkoutResponse['order']['order_no'] ?? null);

        $orderNo    = $checkoutResponse['order']['order_no'];
        $deliveryId = $checkoutResponse['delivery_assignment']['delivery_id'];

        $this->assertDatabaseHas('orders', [
            'order_no'        => $orderNo,
            'driver_id'       => $driver->id,
            'business_status' => 'driver_assigned',
            'payment_method'  => 'cash',
            'payment_status'  => 'pending',
        ]);

        $this->assertDatabaseHas('deliveries', [
            'id'        => $deliveryId,
            'driver_id' => $driver->id,
            'status'    => 'ASSIGNED',
        ]);

        // ── Étape 2 : Livreur prend en charge (PICKED_UP) ───────────────────
        $this->actingAs($driver, 'driver_api')
            ->patchJson("/api/driver/deliveries/{$deliveryId}/status", [
                'status' => 'PICKED_UP',
            ])
            ->assertOk()
            ->assertJsonPath('data.status', 'PICKED_UP');

        $this->assertDatabaseHas('orders', [
            'order_no'        => $orderNo,
            'business_status' => 'picked_up',
        ]);

        $this->assertDatabaseHas('deliveries', [
            'id'     => $deliveryId,
            'status' => 'PICKED_UP',
        ]);

        // ── Étape 3 : Livreur en route (ON_THE_WAY) ──────────────────────────
        $this->actingAs($driver, 'driver_api')
            ->patchJson("/api/driver/deliveries/{$deliveryId}/status", [
                'status' => 'ON_THE_WAY',
            ])
            ->assertOk()
            ->assertJsonPath('data.status', 'ON_THE_WAY');

        $this->assertDatabaseHas('orders', [
            'order_no'        => $orderNo,
            'business_status' => 'out_for_delivery',
        ]);

        // ── Étape 4 : Livreur livre (DELIVERED + confirmation client) ─────────
        $this->actingAs($driver, 'driver_api')
            ->patchJson("/api/driver/deliveries/{$deliveryId}/status", [
                'status'             => 'DELIVERED',
                'customer_confirmed' => true,
            ])
            ->assertOk()
            ->assertJsonPath('data.status', 'DELIVERED');

        $this->assertDatabaseHas('orders', [
            'order_no'        => $orderNo,
            'business_status' => 'delivered',
            'payment_status'  => 'paid',
        ]);

        $this->assertDatabaseHas('deliveries', [
            'id'     => $deliveryId,
            'status' => 'DELIVERED',
        ]);

        $delivery = \App\Delivery::find($deliveryId);
        $this->assertNotNull($delivery->delivered_at);
        $this->assertNotNull($delivery->customer_confirmed_at);
    }
}
