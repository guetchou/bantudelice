<?php

namespace Tests\Feature;

use App\Delivery;
use App\Order;
use App\Services\OrderTrackingTokenService;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class OrderGuestTrackingTest extends TestCase
{
    use RefreshDatabase;

    private function createOrder(): Order
    {
        $owner = User::factory()->create(['type' => 'user', 'phone' => '0600011111']);
        $restUserId = User::factory()->create(['type' => 'restaurant', 'phone' => '0600022222'])->id;

        $restaurantId = DB::table('restaurants')->insertGetId([
            'user_id' => $restUserId,
            'name' => 'Restaurant Guest Tracking',
            'user_name' => 'restaurant-guest-tracking',
            'email' => 'guest-tracking@example.com',
            'password' => bcrypt('secret'),
            'services' => 'food',
            'delivery_charges' => 500,
            'city' => 'Brazzaville',
            'tax' => 0,
            'address' => 'Adresse restaurant',
            'phone' => '0600033333',
            'admin_commission' => 10,
            'approved' => 1,
            'account_name' => 'Restaurant Guest Tracking',
            'account_number' => 'REST-GUEST-001',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $driverId = DB::table('drivers')->insertGetId([
            'restaurant_id' => $restaurantId,
            'name' => 'Driver Guest Tracking',
            'user_name' => 'driver-guest-tracking',
            'phone' => '0600044444',
            'email' => 'driver-guest-tracking@example.com',
            'password' => bcrypt('secret'),
            'hourly_pay' => 0,
            'address' => 'Adresse driver',
            'cnic' => 'CNIC-GUEST-001',
            'approved' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $orderId = DB::table('orders')->insertGetId([
            'order_no' => 'TD-GUEST-0001',
            'user_id' => $owner->id,
            'restaurant_id' => $restaurantId,
            'driver_id' => $driverId,
            'qty' => 1,
            'price' => 3000,
            'total_items' => 1,
            'offer_discount' => 0,
            'tax' => 0,
            'delivery_charges' => 500,
            'sub_total' => 3000,
            'total' => 3500,
            'admin_commission' => 0,
            'restaurant_commission' => 0,
            'driver_tip' => 0,
            'status' => 'onway',
            'business_status' => 'out_for_delivery',
            'payment_method' => 'cash',
            'payment_status' => 'pending',
            'delivery_address' => 'Adresse client confidentielle',
            'd_lat' => '-4.27',
            'd_lng' => '15.28',
            'ordered_time' => now()->subHour(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Delivery::create([
            'order_id' => $orderId,
            'restaurant_id' => $restaurantId,
            'driver_id' => $driverId,
            'status' => 'ON_THE_WAY',
            'delivery_fee' => 500,
            'delivery_otp_code' => '9999',
            'delivery_otp_expires_at' => now()->addHour(),
            'assigned_at' => now()->subMinutes(30),
            'picked_up_at' => now()->subMinutes(15),
        ]);

        return Order::findOrFail($orderId);
    }

    public function test_guest_key_allows_minimal_tracking(): void
    {
        $order = $this->createOrder();
        $key = app(OrderTrackingTokenService::class)->generateForOrder($order);

        $response = $this->get(route('track.order.guest', [$key]));

        $response->assertOk();
        $response->assertSee('TD-GUEST-0001');
        $response->assertDontSee('Adresse client confidentielle', false);
        $response->assertDontSee('0600044444', false);
        $response->assertDontSee('9999', false);
    }

    public function test_invalid_guest_key_is_not_found(): void
    {
        $this->createOrder();

        $this->get('/t/' . str_repeat('A', 64))->assertNotFound();
    }

    public function test_order_number_alone_stays_protected(): void
    {
        $order = $this->createOrder();

        $this->get(route('track.order', ['orderNo' => $order->order_no]))
            ->assertRedirect(route('login'));
    }

    public function test_expired_guest_key_is_not_found(): void
    {
        $order = $this->createOrder();
        $key = app(OrderTrackingTokenService::class)->generateForOrder($order, now()->subMinute());

        $this->get(route('track.order.guest', [$key]))->assertNotFound();
    }
}
