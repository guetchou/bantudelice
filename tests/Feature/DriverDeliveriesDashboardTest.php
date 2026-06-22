<?php

namespace Tests\Feature;

use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DriverDeliveriesDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_driver_dashboard_flags_cash_collection_before_delivery_confirmation(): void
    {
        $restaurantUserId = User::factory()->create([
            'type' => 'restaurant',
            'phone' => '0600060000',
        ])->id;

        $restaurantId = DB::table('restaurants')->insertGetId([
            'user_id' => $restaurantUserId,
            'name' => 'Restaurant Cash Test',
            'user_name' => 'restaurant-cash-test',
            'email' => 'restaurant-cash-test@example.com',
            'password' => bcrypt('secret'),
            'slogan' => 'Test',
            'services' => 'both',
            'service_charges' => 0,
            'delivery_charges' => 500,
            'city' => 'Brazzaville',
            'tax' => 0,
            'address' => 'Adresse test',
            'phone' => '0600060009',
            'admin_commission' => 20,
            'approved' => 1,
            'featured' => 0,
            'account_name' => 'Restaurant Cash Test',
            'account_number' => 'REST-CASH-001',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $driverUser = User::factory()->create([
            'type' => 'driver',
            'name' => 'Livreur Cash',
            'email' => 'livreur-cash@example.com',
            'phone' => '0600060001',
        ]);

        $driverId = DB::table('drivers')->insertGetId([
            'restaurant_id' => $restaurantId,
            'name' => 'Livreur Cash',
            'user_name' => 'livreur-cash',
            'phone' => '0600060001',
            'email' => 'livreur-cash@example.com',
            'image' => null,
            'password' => bcrypt('secret'),
            'hourly_pay' => 0,
            'address' => 'Adresse driver',
            'cnic' => 'CNIC-CASH-001',
            'approved' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $customerId = User::factory()->create([
            'type' => 'user',
            'phone' => '0600060002',
        ])->id;

        $orderId = DB::table('orders')->insertGetId([
            'order_no' => 'ORD-CASH-001',
            'user_id' => $customerId,
            'restaurant_id' => $restaurantId,
            'driver_id' => $driverId,
            'qty' => 1,
            'price' => 3500,
            'total_items' => 1,
            'offer_discount' => 0,
            'tax' => 0,
            'delivery_charges' => 500,
            'sub_total' => 3500,
            'total' => 4000,
            'admin_commission' => 0,
            'restaurant_commission' => 0,
            'driver_tip' => 0,
            'status' => 'pickup',
            'business_status' => 'out_for_delivery',
            'delivery_address' => 'Poto-Poto',
            'scheduled_date' => now(),
            'd_lat' => '0',
            'd_lng' => '0',
            'ordered_time' => now()->subMinutes(20),
            'delivered_time' => now(),
            'payment_method' => 'cash',
            'payment_status' => 'pending',
            'created_at' => now()->subMinutes(20),
            'updated_at' => now()->subMinutes(2),
        ]);

        DB::table('deliveries')->insert([
            'order_id' => $orderId,
            'restaurant_id' => $restaurantId,
            'driver_id' => $driverId,
            'status' => 'ON_THE_WAY',
            'delivery_fee' => 500,
            'assigned_at' => now()->subMinutes(15),
            'picked_up_at' => now()->subMinutes(10),
            'delivery_otp_code' => '1234',
            'delivery_otp_expires_at' => now()->addHour(),
            'created_at' => now()->subMinutes(15),
            'updated_at' => now()->subMinutes(2),
        ]);

        $response = $this->actingAs($driverUser)->get(route('driver.deliveries'));

        $response->assertOk();
        $response->assertSee('Cash &mdash; à encaisser', false);
        $response->assertSee('Encaisser 4 000 FCFA avant de confirmer.', false);
    }
}
