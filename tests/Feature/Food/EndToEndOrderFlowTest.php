<?php

namespace Tests\Feature\Food;

use App\Delivery;
use App\Domain\Food\Events\FoodDriverOrderUpdated;
use App\Domain\Food\Events\FoodMissionPresenceUpdated;
use App\Domain\Food\Events\FoodOrderStatusUpdated;
use App\Domain\Food\Events\FoodRestaurantOrderUpdated;
use App\Domain\Food\Services\OrderAcceptanceService;
use App\Driver;
use App\Order;
use App\Services\DeliveryService;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class EndToEndOrderFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Pas de serveur Pusher/websocket disponible en environnement de test —
        // évite les échecs de diffusion temps réel sans rapport avec le flux testé ici.
        Event::fake([
            FoodOrderStatusUpdated::class,
            FoodRestaurantOrderUpdated::class,
            FoodDriverOrderUpdated::class,
            FoodMissionPresenceUpdated::class,
        ]);
    }

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

        // Plus de Payment/Delivery créé au checkout : différé à l'acceptation restaurant.
        $this->assertArrayNotHasKey('delivery_assignment', $checkoutResponse);
        $this->assertNotNull($checkoutResponse['order']['order_no'] ?? null);

        $orderNo = $checkoutResponse['order']['order_no'];

        $this->assertDatabaseHas('orders', [
            'order_no'        => $orderNo,
            'driver_id'       => null,
            'business_status' => 'pending_restaurant_acceptance',
            'payment_method'  => 'cash',
            'payment_status'  => 'pending',
        ]);

        // ── Étape 1.b : le restaurant accepte -> paiement cash promis + livraison créée ────
        $order = Order::where('order_no', $orderNo)->first();
        app(OrderAcceptanceService::class)->handleAccepted($order);

        $this->assertDatabaseHas('orders', [
            'order_no'        => $orderNo,
            'business_status' => 'in_kitchen',
            'payment_status'  => 'paid',
        ]);

        $delivery = Delivery::where('order_id', $order->id)->first();
        $this->assertNotNull($delivery);
        $this->assertSame('PENDING', $delivery->status);
        $deliveryId = $delivery->id;

        // ── Étape 1.c : la cuisine termine la préparation ───────────────────────
        app(\App\Services\FoodOrderStateMachineService::class)->transitionOrderGroup($orderNo, 'ready_for_pickup', [
            'actor_type' => 'restaurant',
            'actor_id' => $owner->id,
            'reason_code' => 'kitchen_ready',
        ]);

        // ── Étape 1.d : le livreur accepte l'offre de livraison ────────────────
        app(DeliveryService::class)->assignDriver($delivery, $driver);

        $this->assertDatabaseHas('orders', [
            'order_no'        => $orderNo,
            'driver_id'       => $driver->id,
            'business_status' => 'driver_assigned',
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

        // ── cash_collection_status (issue #3 / lacune L1) ──────────────────────
        $finalOrder = Order::where('order_no', $orderNo)->first();
        $this->assertSame('collected', $finalOrder->cash_collection_status);
        $this->assertSame($driver->id, $finalOrder->cash_collected_by);
        $this->assertNotNull($finalOrder->cash_collected_at);
        $this->assertNotNull($finalOrder->cash_collection_confirmed_at);
    }
}
