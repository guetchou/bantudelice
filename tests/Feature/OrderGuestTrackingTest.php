<?php

namespace Tests\Feature;

use App\Http\Middleware\EnsureModuleEnabled;
use App\Order;
use App\Services\OrderTrackingTokenService;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class OrderGuestTrackingTest extends TestCase
{
    use RefreshDatabase;

    public function test_signed_link_displays_only_minimal_tracking_data(): void
    {
        $order = $this->createOrder();
        $url = app(OrderTrackingTokenService::class)
            ->publicUrlForOrder($order, now()->addHour());

        $response = $this->withoutMiddleware(EnsureModuleEnabled::class)->get($url);

        $response->assertOk();
        $response->assertSee('TD-GUEST-0001');
        $response->assertDontSee('Adresse client confidentielle', false);
        $response->assertDontSee('0699999999', false);
    }

    public function test_unsigned_link_is_forbidden(): void
    {
        $order = $this->createOrder();
        $key = app(OrderTrackingTokenService::class)->generateForOrder($order);

        $this->withoutMiddleware(EnsureModuleEnabled::class)
            ->get(route('track.order.guest', ['guestKey' => $key]))
            ->assertForbidden();
    }

    public function test_signed_unknown_key_is_not_found(): void
    {
        $this->createOrder();
        $url = URL::temporarySignedRoute(
            'track.order.guest',
            now()->addHour(),
            ['guestKey' => str_repeat('A', 64)]
        );

        $this->withoutMiddleware(EnsureModuleEnabled::class)
            ->get($url)
            ->assertNotFound();
    }

    public function test_expired_signed_link_is_forbidden(): void
    {
        $order = $this->createOrder();
        $url = app(OrderTrackingTokenService::class)
            ->publicUrlForOrder($order, now()->addSecond());

        $this->travel(2)->seconds();

        $this->withoutMiddleware(EnsureModuleEnabled::class)
            ->get($url)
            ->assertForbidden();
    }

    public function test_order_number_alone_remains_protected(): void
    {
        $order = $this->createOrder();

        $this->get(route('track.order', ['orderNo' => $order->order_no]))
            ->assertRedirect(route('login'));
    }

    private function createOrder(): Order
    {
        $owner = User::factory()->create([
            'type' => 'user',
            'phone' => '0699999999',
        ]);
        $restaurantUser = User::factory()->create(['type' => 'restaurant']);

        $restaurantId = DB::table('restaurants')->insertGetId([
            'user_id' => $restaurantUser->id,
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

        $orderId = DB::table('orders')->insertGetId([
            'order_no' => 'TD-GUEST-0001',
            'user_id' => $owner->id,
            'restaurant_id' => $restaurantId,
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

        return Order::findOrFail($orderId);
    }
}
