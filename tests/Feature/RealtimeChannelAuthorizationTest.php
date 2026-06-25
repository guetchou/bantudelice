<?php

namespace Tests\Feature;

use App\Delivery;
use App\Driver;
use App\Services\RealtimeChannelAuthorizer;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RealtimeChannelAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_food_presence_channel_authorizes_only_order_participants(): void
    {
        $customer = User::factory()->create(['type' => 'user']);
        $restaurantOwner = User::factory()->create(['type' => 'restaurant']);
        $driverUser = User::factory()->create([
            'type' => 'driver',
            'email' => 'channel-driver-user@example.com',
            'phone' => '0708111000',
        ]);
        $intruder = User::factory()->create(['type' => 'user']);

        $restaurantId = DB::table('restaurants')->insertGetId([
            'user_id' => $restaurantOwner->id,
            'name' => 'Restaurant realtime auth',
            'user_name' => 'restaurant-realtime-auth',
            'email' => 'restaurant-realtime-auth@example.com',
            'password' => bcrypt('secret'),
            'services' => 'food',
            'delivery_charges' => 500,
            'city' => 'Brazzaville',
            'tax' => 0,
            'address' => 'Brazzaville',
            'phone' => '0608111000',
            'admin_commission' => 10,
            'approved' => 1,
            'account_name' => 'Restaurant realtime auth',
            'account_number' => 'REST-REALTIME-AUTH',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $driverId = DB::table('drivers')->insertGetId([
            'restaurant_id' => $restaurantId,
            'user_id' => $driverUser->id,
            'name' => 'Livreur realtime auth',
            'user_name' => 'driver-realtime-auth',
            'phone' => $driverUser->phone,
            'email' => $driverUser->email,
            'password' => bcrypt('secret'),
            'hourly_pay' => 0,
            'address' => 'Brazzaville',
            'cnic' => 'CNIC-REALTIME-AUTH',
            'approved' => 1,
            'status' => 'online',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $orderId = DB::table('orders')->insertGetId([
            'order_no' => 'FD-REALTIME-AUTH-001',
            'user_id' => $customer->id,
            'restaurant_id' => $restaurantId,
            'driver_id' => null,
            'total_items' => 1,
            'offer_discount' => 0,
            'tax' => 0,
            'delivery_charges' => 500,
            'sub_total' => 2500,
            'total' => 3000,
            'admin_commission' => 0,
            'restaurant_commission' => 0,
            'driver_tip' => 0,
            'status' => 'assign',
            'business_status' => 'driver_assigned',
            'delivery_address' => 'Brazzaville',
            'd_lat' => '-4.27',
            'd_lng' => '15.28',
            'ordered_time' => now(),
            'delivered_time' => now(),
            'fulfillment_mode' => 'delivery',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Delivery::create([
            'order_id' => $orderId,
            'restaurant_id' => $restaurantId,
            'driver_id' => $driverId,
            'status' => 'ASSIGNED',
            'delivery_fee' => 500,
            'assigned_at' => now(),
        ]);

        $authorizer = app(RealtimeChannelAuthorizer::class);

        $this->assertTrue($authorizer->canAccessFoodOrderStatus($customer, 'FD-REALTIME-AUTH-001'));
        $this->assertTrue($authorizer->canAccessFoodOrderStatus($restaurantOwner, 'FD-REALTIME-AUTH-001'));
        $this->assertTrue($authorizer->canAccessFoodOrderStatus($driverUser, 'FD-REALTIME-AUTH-001'));
        $this->assertFalse($authorizer->canAccessFoodOrderStatus($intruder, 'FD-REALTIME-AUTH-001'));
    }
}
