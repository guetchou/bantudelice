<?php

namespace Tests\Feature;

use App\Order;
use App\Services\FoodOrderStateMachineService;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class FoodOrderStateMachineServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_transition_order_group_returns_fresh_orders_collection(): void
    {
        $customer = User::factory()->create(['type' => 'user']);
        $owner = User::factory()->create(['type' => 'restaurant']);

        $restaurantId = DB::table('restaurants')->insertGetId([
            'user_id' => $owner->id,
            'name' => 'Restaurant state machine',
            'user_name' => 'restaurant-state-machine',
            'email' => 'restaurant-state-machine@example.com',
            'password' => bcrypt('secret'),
            'services' => 'food',
            'delivery_charges' => 1000,
            'city' => 'Brazzaville',
            'tax' => 5,
            'address' => 'Avenue de la Paix',
            'latitude' => -4.2634,
            'longitude' => 15.2429,
            'phone' => '0600001300',
            'description' => 'Test',
            'min_order' => 1000,
            'admin_commission' => 5,
            'approved' => 1,
            'featured' => 0,
            'account_name' => 'Restaurant state machine',
            'account_number' => 'ACC-REST-SM-001',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Order::create([
            'order_no' => 'FD-SM-001',
            'user_id' => $customer->id,
            'restaurant_id' => $restaurantId,
            'qty' => 1,
            'price' => 3500,
            'total_items' => 1,
            'offer_discount' => 0,
            'tax' => 0,
            'delivery_charges' => 1000,
            'sub_total' => 3500,
            'total' => 4500,
            'admin_commission' => 0,
            'restaurant_commission' => 0,
            'driver_tip' => 0,
            'delivery_address' => 'Avenue de la Paix',
            'd_lat' => '-4.2700',
            'd_lng' => '15.2800',
            'ordered_time' => now(),
            'payment_method' => 'cash',
            'payment_status' => 'pending',
            'status' => 'pending',
            'business_status' => 'pending_restaurant_acceptance',
            'fulfillment_mode' => 'delivery',
        ]);

        $updatedOrders = app(FoodOrderStateMachineService::class)->transitionOrderGroup('FD-SM-001', 'confirmed', [
            'suppress_notifications' => true,
            'suppress_realtime' => true,
        ]);

        $this->assertCount(1, $updatedOrders);
        $this->assertSame('confirmed', $updatedOrders->first()->business_status);
        $this->assertSame('pending', $updatedOrders->first()->status);
        $this->assertDatabaseHas('orders', [
            'order_no' => 'FD-SM-001',
            'business_status' => 'confirmed',
        ]);
    }
}
