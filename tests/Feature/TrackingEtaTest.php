<?php

namespace Tests\Feature;

use App\Delivery;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * ETA de suivi (remainingMinutes) — point de départ métier au lieu de created_at seul.
 */
class TrackingEtaTest extends TestCase
{
    use RefreshDatabase;

    private const ESTIMATED_TIME = 30;

    private function createRestaurant(): int
    {
        $restUserId = User::factory()->create(['type' => 'restaurant', 'phone' => '0600066001'])->id;

        return DB::table('restaurants')->insertGetId([
            'user_id' => $restUserId,
            'name' => 'Restaurant ETA Test',
            'user_name' => 'restaurant-eta-test',
            'email' => 'eta-test@example.com',
            'password' => bcrypt('secret'),
            'services' => 'both',
            'service_charges' => 0,
            'delivery_charges' => 500,
            'city' => 'Brazzaville',
            'tax' => 0,
            'address' => 'Adresse test',
            'phone' => '0600066002',
            'admin_commission' => 10,
            'approved' => 1,
            'featured' => 0,
            'avg_delivery_time' => self::ESTIMATED_TIME,
            'account_name' => 'Restaurant ETA Test',
            'account_number' => 'REST-ETA-001',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createOrder(int $restaurantId, string $orderNo, string $businessStatus, array $overrides = []): array
    {
        $owner = User::factory()->create(['type' => 'user', 'phone' => '06000' . random_int(60000, 69999)]);

        $orderId = DB::table('orders')->insertGetId(array_merge([
            'order_no' => $orderNo,
            'user_id' => $owner->id,
            'restaurant_id' => $restaurantId,
            'qty' => 1,
            'price' => 1000,
            'total_items' => 1,
            'offer_discount' => 0,
            'tax' => 0,
            'delivery_charges' => 0,
            'sub_total' => 1000,
            'total' => 1000,
            'admin_commission' => 0,
            'restaurant_commission' => 0,
            'driver_tip' => 0,
            'status' => 'pending',
            'business_status' => $businessStatus,
            'payment_method' => 'cash',
            'payment_status' => 'pending',
            'delivery_address' => 'Test',
            'd_lat' => '0',
            'd_lng' => '0',
            'ordered_time' => now(),
            'created_at' => now()->subMinutes(20),
            'updated_at' => now(),
        ], $overrides));

        return [$owner, $orderId];
    }

    private function extractRemainingMinutes($response): int
    {
        return $response->viewData('remainingMinutes');
    }

    public function test_no_eta_displayed_while_awaiting_payment_even_if_order_is_old(): void
    {
        $restaurantId = $this->createRestaurant();
        [$owner] = $this->createOrder($restaurantId, 'TD-ETA-0001', 'accepted_awaiting_payment');

        $response = $this->actingAs($owner)->get(route('track.order', ['orderNo' => 'TD-ETA-0001']));

        $response->assertStatus(200);
        $this->assertSame(0, $this->extractRemainingMinutes($response));
    }

    public function test_eta_close_to_estimated_time_when_preparation_just_started(): void
    {
        $restaurantId = $this->createRestaurant();
        [$owner] = $this->createOrder($restaurantId, 'TD-ETA-0002', 'in_kitchen', [
            'preparation_started_at' => now(),
        ]);

        $response = $this->actingAs($owner)->get(route('track.order', ['orderNo' => 'TD-ETA-0002']));

        $response->assertStatus(200);
        $remaining = $this->extractRemainingMinutes($response);
        $this->assertGreaterThanOrEqual(self::ESTIMATED_TIME - 1, $remaining);
        $this->assertLessThanOrEqual(self::ESTIMATED_TIME, $remaining);
    }

    public function test_eta_calculated_from_assigned_at_when_driver_assigned(): void
    {
        $restaurantId = $this->createRestaurant();
        [$owner, $orderId] = $this->createOrder($restaurantId, 'TD-ETA-0003', 'driver_assigned', [
            'preparation_started_at' => now()->subMinutes(25),
        ]);

        Delivery::create([
            'order_id' => $orderId,
            'restaurant_id' => $restaurantId,
            'status' => 'ASSIGNED',
            'delivery_fee' => 500,
            'assigned_at' => now()->subMinutes(5),
        ]);

        $response = $this->actingAs($owner)->get(route('track.order', ['orderNo' => 'TD-ETA-0003']));

        $response->assertStatus(200);
        $remaining = $this->extractRemainingMinutes($response);
        // ETA calculée depuis assigned_at (-5 min), pas depuis preparation_started_at (-25 min)
        $this->assertGreaterThanOrEqual(self::ESTIMATED_TIME - 6, $remaining);
        $this->assertLessThanOrEqual(self::ESTIMATED_TIME - 4, $remaining);
    }

    public function test_eta_calculated_from_picked_up_at_when_out_for_delivery(): void
    {
        $restaurantId = $this->createRestaurant();
        [$owner, $orderId] = $this->createOrder($restaurantId, 'TD-ETA-0004', 'out_for_delivery', [
            'preparation_started_at' => now()->subMinutes(40),
        ]);

        Delivery::create([
            'order_id' => $orderId,
            'restaurant_id' => $restaurantId,
            'status' => 'ON_THE_WAY',
            'delivery_fee' => 500,
            'assigned_at' => now()->subMinutes(15),
            'picked_up_at' => now()->subMinutes(3),
        ]);

        $response = $this->actingAs($owner)->get(route('track.order', ['orderNo' => 'TD-ETA-0004']));

        $response->assertStatus(200);
        $remaining = $this->extractRemainingMinutes($response);
        // ETA calculée depuis picked_up_at (-3 min), pas depuis assigned_at (-15) ni preparation (-40)
        $this->assertGreaterThanOrEqual(self::ESTIMATED_TIME - 4, $remaining);
        $this->assertLessThanOrEqual(self::ESTIMATED_TIME - 2, $remaining);
    }
}
