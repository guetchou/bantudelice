<?php

namespace Tests\Feature;

use App\Delivery;
use App\Domain\Food\Events\FoodDriverOrderUpdated;
use App\Domain\Food\Events\FoodMissionPresenceUpdated;
use App\Domain\Food\Events\FoodOrderStatusUpdated;
use App\Domain\Food\Events\FoodRestaurantOrderUpdated;
use App\Driver;
use App\Order;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class DriverDeliveryJourneyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Event::fake([
            FoodOrderStatusUpdated::class,
            FoodRestaurantOrderUpdated::class,
            FoodDriverOrderUpdated::class,
            FoodMissionPresenceUpdated::class,
        ]);
    }

    public function test_assigned_driver_can_mark_arrival_at_restaurant_before_pickup(): void
    {
        [$driver, $order, $delivery] = $this->assignedDelivery();

        $this->actingAs($driver, 'driver_api')
            ->patchJson("/api/driver/deliveries/{$delivery->id}/status", [
                'status' => 'ARRIVED_AT_RESTAURANT',
                'restaurant_arrival_latitude' => -4.26,
                'restaurant_arrival_longitude' => 15.24,
            ])
            ->assertOk()
            ->assertJsonPath('data.status', 'ASSIGNED')
            ->assertJsonPath('data.payment_status', 'cash_due');

        $delivery->refresh();
        $order->refresh()->load('delivery');

        $this->assertNotNull($delivery->restaurant_arrived_at);
        $this->assertSame('ASSIGNED', $delivery->status);
        $this->assertSame('driver_arrived_at_restaurant', $order->business_status);
        $this->assertSame('driver_arrived_at_restaurant', $order->resolveEffectiveBusinessStatus());

        $this->actingAs($driver, 'driver_api')
            ->patchJson("/api/driver/deliveries/{$delivery->id}/status", [
                'status' => 'PICKED_UP',
            ])
            ->assertOk()
            ->assertJsonPath('data.status', 'PICKED_UP');
    }

    public function test_restaurant_arrival_is_idempotent_for_double_submit(): void
    {
        [$driver, , $delivery] = $this->assignedDelivery();

        $payload = [
            'status' => 'ARRIVED_AT_RESTAURANT',
            'restaurant_arrival_latitude' => -4.26,
            'restaurant_arrival_longitude' => 15.24,
        ];

        $this->actingAs($driver, 'driver_api')
            ->patchJson("/api/driver/deliveries/{$delivery->id}/status", $payload)
            ->assertOk();

        $firstArrivedAt = $delivery->refresh()->restaurant_arrived_at;
        $this->assertNotNull($firstArrivedAt);

        $this->travel(2)->minutes();

        $this->actingAs($driver, 'driver_api')
            ->patchJson("/api/driver/deliveries/{$delivery->id}/status", $payload)
            ->assertOk()
            ->assertJsonPath('data.status', 'ASSIGNED');

        $this->assertTrue($firstArrivedAt->equalTo($delivery->refresh()->restaurant_arrived_at));
    }

    public function test_restaurant_arrival_rejects_far_gps_position(): void
    {
        [$driver, $order, $delivery] = $this->assignedDelivery();

        $this->actingAs($driver, 'driver_api')
            ->patchJson("/api/driver/deliveries/{$delivery->id}/status", [
                'status' => 'ARRIVED_AT_RESTAURANT',
                'restaurant_arrival_latitude' => -4.90,
                'restaurant_arrival_longitude' => 15.90,
            ])
            ->assertStatus(422)
            ->assertJsonPath('status', false);

        $this->assertNull($delivery->refresh()->restaurant_arrived_at);
        $this->assertSame('driver_assigned', $order->refresh()->business_status);
    }

    public function test_other_driver_cannot_mark_arrival_for_delivery(): void
    {
        [, , $delivery] = $this->assignedDelivery();
        $otherDriver = $this->driver($delivery->restaurant_id, 'other');

        $this->actingAs($otherDriver, 'driver_api')
            ->patchJson("/api/driver/deliveries/{$delivery->id}/status", [
                'status' => 'ARRIVED_AT_RESTAURANT',
                'restaurant_arrival_latitude' => -4.26,
                'restaurant_arrival_longitude' => 15.24,
            ])
            ->assertNotFound();

        $this->assertNull($delivery->refresh()->restaurant_arrived_at);
    }

    private function assignedDelivery(): array
    {
        $owner = User::factory()->create(['type' => 'restaurant']);
        $customer = User::factory()->create(['type' => 'user']);

        $restaurantId = DB::table('restaurants')->insertGetId([
            'user_id' => $owner->id,
            'name' => 'Restaurant Arrivée Test',
            'user_name' => 'restaurant-arrivee-' . uniqid(),
            'email' => 'restaurant-arrivee-' . uniqid() . '@example.com',
            'password' => bcrypt('secret'),
            'services' => 'food',
            'delivery_charges' => 500,
            'city' => 'Brazzaville',
            'tax' => 0,
            'address' => 'Avenue test',
            'latitude' => -4.26,
            'longitude' => 15.24,
            'phone' => '0600009000',
            'min_order' => 0,
            'admin_commission' => 5,
            'approved' => 1,
            'account_name' => 'Restaurant Test',
            'account_number' => 'ACC-ARR-001',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $driver = $this->driver($restaurantId, 'assigned');

        $order = Order::create([
            'restaurant_id' => $restaurantId,
            'user_id' => $customer->id,
            'driver_id' => $driver->id,
            'total_items' => 1,
            'offer_discount' => 0,
            'tax' => 0,
            'delivery_charges' => 500,
            'sub_total' => 2000,
            'total' => 2500,
            'admin_commission' => 0,
            'restaurant_commission' => 0,
            'driver_tip' => 0,
            'status' => 'assign',
            'business_status' => 'driver_assigned',
            'payment_method' => 'cash',
            'payment_status' => 'cash_due',
            'cash_collection_status' => 'pending_collection',
            'delivery_address' => 'Adresse client',
            'd_lat' => '-4.27',
            'd_lng' => '15.28',
            'ordered_time' => now(),
            'order_no' => 'ARRIVAL-' . uniqid(),
            'qty' => 1,
            'price' => 2000,
        ]);

        $delivery = Delivery::create([
            'order_id' => $order->id,
            'restaurant_id' => $restaurantId,
            'driver_id' => $driver->id,
            'status' => 'ASSIGNED',
            'assigned_at' => now(),
            'delivery_fee' => 500,
        ]);

        return [$driver, $order, $delivery];
    }

    private function driver(int $restaurantId, string $suffix): Driver
    {
        return Driver::create([
            'restaurant_id' => $restaurantId,
            'name' => 'Livreur ' . $suffix,
            'user_name' => 'livreur-' . $suffix . '-' . uniqid(),
            'hourly_pay' => 0,
            'email' => 'livreur-' . $suffix . '-' . uniqid() . '@example.com',
            'cnic' => 'CNIC-' . $suffix . '-' . uniqid(),
            'password' => bcrypt('secret'),
            'phone' => '05' . substr(preg_replace('/\D+/', '', uniqid('', true)), -8),
            'address' => 'Brazzaville',
            'latitude' => -4.26,
            'longitude' => 15.24,
            'status' => 'online',
            'approved' => true,
        ]);
    }
}
