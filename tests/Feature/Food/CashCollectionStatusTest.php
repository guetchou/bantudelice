<?php

namespace Tests\Feature\Food;

use App\Delivery;
use App\Domain\Food\Events\FoodDriverOrderUpdated;
use App\Domain\Food\Events\FoodMissionPresenceUpdated;
use App\Domain\Food\Events\FoodOrderStatusUpdated;
use App\Domain\Food\Events\FoodRestaurantOrderUpdated;
use App\Driver;
use App\Order;
use App\Services\DeliveryService;
use App\Services\FoodOrderStateMachineService;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class CashCollectionStatusTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Pas de serveur Pusher/websocket disponible en environnement de test —
        // évite les échecs de diffusion temps réel sans rapport avec ce qui est testé ici.
        Event::fake([
            FoodOrderStatusUpdated::class,
            FoodRestaurantOrderUpdated::class,
            FoodDriverOrderUpdated::class,
            FoodMissionPresenceUpdated::class,
        ]);
    }

    private function createRestaurant(): array
    {
        $owner = User::factory()->create(['type' => 'restaurant']);

        $restaurantId = DB::table('restaurants')->insertGetId([
            'user_id' => $owner->id,
            'name' => 'Restaurant Cash Test',
            'user_name' => 'restaurant-cash-test-' . uniqid(),
            'email' => 'restaurant-cash-' . uniqid() . '@example.com',
            'password' => bcrypt('secret'),
            'services' => 'food',
            'delivery_charges' => 500,
            'city' => 'Brazzaville',
            'tax' => 0,
            'address' => 'Adresse test',
            'phone' => '06' . substr(uniqid(), -8),
            'min_order' => 0,
            'admin_commission' => 5,
            'approved' => 1,
            'account_name' => 'Test',
            'account_number' => '0000',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [$owner, $restaurantId];
    }

    private function createOrder(int $restaurantId, array $overrides = []): Order
    {
        $customer = User::factory()->create(['type' => 'user']);

        return Order::create(array_merge([
            'restaurant_id' => $restaurantId,
            'user_id' => $customer->id,
            'total_items' => 1,
            'offer_discount' => 0,
            'tax' => 0,
            'delivery_charges' => 500,
            'sub_total' => 2000,
            'total' => 2500,
            'admin_commission' => 0,
            'restaurant_commission' => 0,
            'driver_tip' => 0,
            'status' => 'pending',
            'business_status' => 'out_for_delivery',
            'payment_method' => 'cash',
            'payment_status' => 'paid',
            'cash_collection_status' => 'pending_collection',
            'delivery_address' => 'Adresse client',
            'd_lat' => '0',
            'd_lng' => '0',
            'ordered_time' => now(),
            'order_no' => 'CASH-TEST-' . uniqid(),
            'qty' => 1,
            'price' => 2000,
        ], $overrides));
    }

    public function test_pickup_cash_collection_marks_collected_with_restaurant_as_collector(): void
    {
        [$owner, $restaurantId] = $this->createRestaurant();
        $order = $this->createOrder($restaurantId, [
            'business_status' => 'ready_for_pickup',
            'fulfillment_mode' => 'pickup',
        ]);

        app(FoodOrderStateMachineService::class)->transitionOrderGroup($order->order_no, 'customer_arrived', [
            'actor_type' => 'customer',
            'actor_id' => $order->user_id,
        ]);
        app(FoodOrderStateMachineService::class)->transitionOrderGroup($order->order_no, 'picked_up_by_customer', [
            'actor_type' => 'customer',
            'actor_id' => $order->user_id,
        ]);

        $order->refresh();
        $this->assertSame('collected', $order->cash_collection_status);
        $this->assertSame($owner->id, $order->cash_collected_by);
        $this->assertNotNull($order->cash_collected_at);
        $this->assertNotNull($order->cash_collection_confirmed_at);
    }

    public function test_delivery_collection_failed_outcome_does_not_block_delivered_status(): void
    {
        [, $restaurantId] = $this->createRestaurant();
        $driver = Driver::create([
            'restaurant_id' => $restaurantId,
            'name' => 'Livreur Cash Test',
            'user_name' => 'livreur-cash-test-' . uniqid(),
            'hourly_pay' => 0,
            'email' => 'livreur-cash-' . uniqid() . '@example.com',
            'cnic' => 'CNIC-CASH-' . uniqid(),
            'password' => bcrypt('secret'),
            'phone' => '0500003000',
            'address' => 'Brazzaville',
            'status' => 'online',
            'approved' => true,
        ]);

        $order = $this->createOrder($restaurantId, ['driver_id' => $driver->id]);

        $deliveryId = DB::table('deliveries')->insertGetId([
            'order_id' => $order->id,
            'restaurant_id' => $restaurantId,
            'driver_id' => $driver->id,
            'status' => 'ON_THE_WAY',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($driver, 'driver_api')
            ->patchJson("/api/driver/deliveries/{$deliveryId}/status", [
                'status' => 'DELIVERED',
                'customer_confirmed' => true,
                'cash_collection_outcome' => 'collection_failed',
            ])
            ->assertOk();

        $order->refresh();
        $this->assertSame('delivered', $order->resolveEffectiveBusinessStatus());
        $this->assertSame('collection_failed', $order->cash_collection_status);
        $this->assertNull($order->cash_collected_at);
    }

    public function test_non_cash_order_delivered_does_not_set_cash_collection_status(): void
    {
        [, $restaurantId] = $this->createRestaurant();
        $driver = Driver::create([
            'restaurant_id' => $restaurantId,
            'name' => 'Livreur Momo Test',
            'user_name' => 'livreur-momo-test-' . uniqid(),
            'hourly_pay' => 0,
            'email' => 'livreur-momo-' . uniqid() . '@example.com',
            'cnic' => 'CNIC-MOMO-' . uniqid(),
            'password' => bcrypt('secret'),
            'phone' => '0500004000',
            'address' => 'Brazzaville',
            'status' => 'online',
            'approved' => true,
        ]);

        $order = $this->createOrder($restaurantId, [
            'driver_id' => $driver->id,
            'payment_method' => 'momo',
            'cash_collection_status' => null,
        ]);

        $deliveryId = DB::table('deliveries')->insertGetId([
            'order_id' => $order->id,
            'restaurant_id' => $restaurantId,
            'driver_id' => $driver->id,
            'status' => 'ON_THE_WAY',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($driver, 'driver_api')
            ->patchJson("/api/driver/deliveries/{$deliveryId}/status", [
                'status' => 'DELIVERED',
                'customer_confirmed' => true,
            ])
            ->assertOk();

        $order->refresh();
        $this->assertNull($order->cash_collection_status);
    }

    public function test_paypal_order_delivered_does_not_set_cash_collection_status(): void
    {
        [, $restaurantId] = $this->createRestaurant();
        $driver = Driver::create([
            'restaurant_id' => $restaurantId,
            'name' => 'Livreur PayPal Test',
            'user_name' => 'livreur-paypal-test-' . uniqid(),
            'hourly_pay' => 0,
            'email' => 'livreur-paypal-' . uniqid() . '@example.com',
            'cnic' => 'CNIC-PAYPAL-' . uniqid(),
            'password' => bcrypt('secret'),
            'phone' => '0500005000',
            'address' => 'Brazzaville',
            'status' => 'online',
            'approved' => true,
        ]);

        $order = $this->createOrder($restaurantId, [
            'driver_id' => $driver->id,
            'payment_method' => 'paypal',
            'cash_collection_status' => null,
        ]);

        $deliveryId = DB::table('deliveries')->insertGetId([
            'order_id' => $order->id,
            'restaurant_id' => $restaurantId,
            'driver_id' => $driver->id,
            'status' => 'ON_THE_WAY',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($driver, 'driver_api')
            ->patchJson("/api/driver/deliveries/{$deliveryId}/status", [
                'status' => 'DELIVERED',
                'customer_confirmed' => true,
            ])
            ->assertOk();

        $order->refresh();
        $this->assertNull($order->cash_collection_status);
    }

    public function test_delivery_cash_collection_marks_collected_with_driver_as_collector(): void
    {
        [, $restaurantId] = $this->createRestaurant();
        $driver = Driver::create([
            'restaurant_id' => $restaurantId,
            'name' => 'Livreur Cash Nominal Test',
            'user_name' => 'livreur-cash-nominal-' . uniqid(),
            'hourly_pay' => 0,
            'email' => 'livreur-cash-nominal-' . uniqid() . '@example.com',
            'cnic' => 'CNIC-CASH-NOM-' . uniqid(),
            'password' => bcrypt('secret'),
            'phone' => '0500006000',
            'address' => 'Brazzaville',
            'status' => 'online',
            'approved' => true,
        ]);

        $order = $this->createOrder($restaurantId, ['driver_id' => $driver->id]);

        $deliveryId = DB::table('deliveries')->insertGetId([
            'order_id' => $order->id,
            'restaurant_id' => $restaurantId,
            'driver_id' => $driver->id,
            'status' => 'ON_THE_WAY',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($driver, 'driver_api')
            ->patchJson("/api/driver/deliveries/{$deliveryId}/status", [
                'status' => 'DELIVERED',
                'customer_confirmed' => true,
            ])
            ->assertOk();

        $order->refresh();
        $this->assertSame('delivered', $order->resolveEffectiveBusinessStatus());
        $this->assertSame('collected', $order->cash_collection_status);
        $this->assertSame($driver->id, $order->cash_collected_by);
        $this->assertNotNull($order->cash_collected_at);
        $this->assertNotNull($order->cash_collection_confirmed_at);
    }

    public function test_admin_deliver_order_force_does_not_overwrite_disputed_cash_status(): void
    {
        [, $restaurantId] = $this->createRestaurant();
        $admin = User::factory()->create(['type' => 'admin']);
        $this->grantAdminWorkspace($admin);

        $order = $this->createOrder($restaurantId, ['cash_collection_status' => 'disputed']);

        $this->actingAs($admin)
            ->post(route('admin.deliver_order', ['order' => $order->id]))
            ->assertRedirect();

        $order->refresh();
        $this->assertSame('disputed', $order->cash_collection_status);
        $this->assertNull($order->cash_collected_at);
    }

    public function test_restaurant_deliver_order_force_does_not_overwrite_collection_failed_cash_status(): void
    {
        [$owner, $restaurantId] = $this->createRestaurant();

        $order = $this->createOrder($restaurantId, ['cash_collection_status' => 'collection_failed']);

        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);

        $this->actingAs($owner)
            ->post(route('restaurant.deliver_order', ['order' => $order->id]))
            ->assertRedirect();

        $order->refresh();
        $this->assertSame('collection_failed', $order->cash_collection_status);
        $this->assertNull($order->cash_collected_at);
    }

    public function test_dispute_cash_collection_validates_current_status(): void
    {
        [, $restaurantId] = $this->createRestaurant();
        $order = $this->createOrder($restaurantId, ['cash_collection_status' => 'collected']);

        $disputed = app(DeliveryService::class)->disputeCashCollection($order, [
            'actor_type' => 'restaurant',
            'notes' => 'Espèces jamais remises',
        ]);
        $this->assertSame('disputed', $disputed->cash_collection_status);
        $this->assertSame('Espèces jamais remises', $disputed->cash_collection_reference);

        $notCollectedOrder = $this->createOrder($restaurantId, ['cash_collection_status' => 'pending_collection']);
        $this->expectException(\RuntimeException::class);
        app(DeliveryService::class)->disputeCashCollection($notCollectedOrder);
    }

    public function test_resolve_cash_dispute_both_resolutions(): void
    {
        [, $restaurantId] = $this->createRestaurant();

        $orderConfirmedCollected = $this->createOrder($restaurantId, ['cash_collection_status' => 'disputed']);
        $resolved = app(DeliveryService::class)->resolveCashDispute($orderConfirmedCollected, 'confirmed_collected', ['actor_type' => 'admin']);
        $this->assertSame('collected', $resolved->cash_collection_status);

        $orderConfirmedNotCollected = $this->createOrder($restaurantId, ['cash_collection_status' => 'disputed']);
        $resolved2 = app(DeliveryService::class)->resolveCashDispute($orderConfirmedNotCollected, 'confirmed_not_collected', ['actor_type' => 'admin']);
        $this->assertSame('collection_failed', $resolved2->cash_collection_status);
    }

    public function test_restaurant_cannot_dispute_another_restaurants_order(): void
    {
        [$owner, $restaurantId] = $this->createRestaurant();
        [, $otherRestaurantId] = $this->createRestaurant();
        $order = $this->createOrder($otherRestaurantId, ['cash_collection_status' => 'collected']);

        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);

        $this->actingAs($owner)
            ->post(route('restaurant.orders.cash_dispute', $order->order_no), ['notes' => 'test'])
            ->assertRedirect();

        $order->refresh();
        $this->assertSame('collected', $order->cash_collection_status);
    }
}
