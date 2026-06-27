<?php

namespace Tests\Feature;

use App\Driver;
use App\Restaurant;
use App\Services\PartnerFinancialDashboardService;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PartnerFinancialDashboardServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_restaurant_financial_dashboard_uses_strict_business_formula(): void
    {
        [$restaurant] = $this->createRestaurantFixture();

        DB::table('completed_orders')->insert([
            $this->completedOrderPayload($restaurant->id, 10000, 'FOOD-1'),
            $this->completedOrderPayload($restaurant->id, 5000, 'FOOD-2'),
        ]);

        DB::table('restaurant_payments')->insert([
            [
                'restaurant_id' => $restaurant->id,
                'payout_amount' => 3000,
                'transaction_id' => 'REST-PAID-1',
                'status' => 'paid',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'restaurant_id' => $restaurant->id,
                'payout_amount' => 2000,
                'transaction_id' => 'REST-PENDING-1',
                'status' => 'pending',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $dashboard = app(PartnerFinancialDashboardService::class)->forRestaurant($restaurant);

        $this->assertSame(15000.0, $dashboard['cards'][0]['amount']);
        $this->assertSame(3000.0, $dashboard['cards'][1]['amount']);
        $this->assertSame(12000.0, $dashboard['cards'][2]['amount']);
        $this->assertSame(3000.0, $dashboard['cards'][3]['amount']);
        $this->assertSame(7000.0, $dashboard['cards'][4]['amount']);
        $this->assertSame(2000.0, $dashboard['cards'][5]['amount']);
    }

    public function test_restaurant_financial_dashboard_prefers_subtotal_over_total_for_gross_sales(): void
    {
        [$restaurant] = $this->createRestaurantFixture();

        DB::table('completed_orders')->insert([
            [
                'order_no' => 'FOOD-SUBTOTAL-1',
                'user_id' => User::factory()->create([
                    'type' => 'user',
                    'phone' => '0600090001',
                ])->id,
                'restaurant_id' => $restaurant->id,
                'qty' => 1,
                'price' => 10000,
                'total_items' => 1,
                'offer_discount' => 0,
                'tax' => 0,
                'delivery_charges' => 2000,
                'sub_total' => 10000,
                'total' => 12000,
                'admin_commission' => 20,
                'restaurant_commission' => 0,
                'driver_tip' => 0,
                'status' => 'completed',
                'delivery_address' => 'Brazzaville',
                'ordered_time' => now(),
                'delivered_time' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'order_no' => 'FOOD-SUBTOTAL-2',
                'user_id' => User::factory()->create([
                    'type' => 'user',
                    'phone' => '0600090002',
                ])->id,
                'restaurant_id' => $restaurant->id,
                'qty' => 1,
                'price' => 5000,
                'total_items' => 1,
                'offer_discount' => 0,
                'tax' => 0,
                'delivery_charges' => 1000,
                'sub_total' => 5000,
                'total' => 6000,
                'admin_commission' => 20,
                'restaurant_commission' => 0,
                'driver_tip' => 0,
                'status' => 'completed',
                'delivery_address' => 'Brazzaville',
                'ordered_time' => now(),
                'delivered_time' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $dashboard = app(PartnerFinancialDashboardService::class)->forRestaurant($restaurant);

        $this->assertSame(15000.0, $dashboard['cards'][0]['amount']);
        $this->assertSame(3000.0, $dashboard['cards'][1]['amount']);
        $this->assertSame(12000.0, $dashboard['cards'][2]['amount']);
    }

    public function test_delivery_driver_financial_dashboard_keeps_delivery_scope_only(): void
    {
        [, $driver] = $this->createRestaurantFixture();
        $customer = User::factory()->create([
            'type' => 'user',
            'phone' => '0600003001',
        ]);

        $paidOrderId = DB::table('orders')->insertGetId($this->orderPayload($customer->id, $driver->restaurant_id, $driver->id, 'paid', 'mobile_money', 3500));
        $cashOrderId = DB::table('orders')->insertGetId($this->orderPayload($customer->id, $driver->restaurant_id, $driver->id, 'pending', 'cash', 2200));
        $ignoredOrderId = DB::table('orders')->insertGetId($this->orderPayload($customer->id, $driver->restaurant_id, $driver->id, 'pending', 'mobile_money', 1800));

        DB::table('deliveries')->insert([
            [
                'order_id' => $paidOrderId,
                'restaurant_id' => $driver->restaurant_id,
                'driver_id' => $driver->id,
                'status' => 'DELIVERED',
                'delivery_fee' => 1000,
                'assigned_at' => now(),
                'picked_up_at' => now(),
                'delivered_at' => now(),
                'cash_collected_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'order_id' => $cashOrderId,
                'restaurant_id' => $driver->restaurant_id,
                'driver_id' => $driver->id,
                'status' => 'DELIVERED',
                'delivery_fee' => 1500,
                'assigned_at' => now(),
                'picked_up_at' => now(),
                'delivered_at' => now(),
                'cash_collected_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'order_id' => $ignoredOrderId,
                'restaurant_id' => $driver->restaurant_id,
                'driver_id' => $driver->id,
                'status' => 'ON_THE_WAY',
                'delivery_fee' => 900,
                'assigned_at' => now(),
                'picked_up_at' => now(),
                'delivered_at' => null,
                'cash_collected_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        DB::table('driver_payments')->insert([
            [
                'driver_id' => $driver->id,
                'payout_amount' => 400,
                'transaction_id' => 'DRV-PAID-1',
                'status' => 'paid',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'driver_id' => $driver->id,
                'payout_amount' => 300,
                'transaction_id' => 'DRV-PENDING-1',
                'status' => 'pending',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $dashboard = app(PartnerFinancialDashboardService::class)->forDeliveryDriver($driver);

        $this->assertSame(2500.0, $dashboard['cards'][0]['amount']);
        $this->assertSame(0.0, $dashboard['cards'][1]['amount']);
        $this->assertSame(2500.0, $dashboard['cards'][2]['amount']);
        $this->assertSame(400.0, $dashboard['cards'][3]['amount']);
        $this->assertSame(1800.0, $dashboard['cards'][4]['amount']);
        $this->assertSame(300.0, $dashboard['cards'][5]['amount']);
    }

    public function test_transport_driver_financial_dashboard_does_not_mix_with_delivery_payouts(): void
    {
        [, $driver] = $this->createRestaurantFixture();
        $customer = User::factory()->create([
            'type' => 'user',
            'phone' => '0600004001',
        ]);

        DB::table('transport_bookings')->insert([
            [
                'uuid' => '11111111-1111-1111-1111-111111111111',
                'booking_no' => 'TR-TEST-1',
                'type' => 'taxi',
                'user_id' => $customer->id,
                'driver_id' => $driver->id,
                'pickup_address' => 'Plateau',
                'dropoff_address' => 'Moungali',
                'total_price' => 5000,
                'payment_method' => 'mobile_money',
                'payment_status' => 'paid',
                'status' => 'paid',
                'cash_collected_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'uuid' => '22222222-2222-2222-2222-222222222222',
                'booking_no' => 'TR-TEST-2',
                'type' => 'taxi',
                'user_id' => $customer->id,
                'driver_id' => $driver->id,
                'pickup_address' => 'Poto-Poto',
                'dropoff_address' => 'Bacongo',
                'total_price' => 3000,
                'payment_method' => 'cash',
                'payment_status' => 'pending',
                'status' => 'closed',
                'cash_collected_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        DB::table('driver_payments')->insert([
            'driver_id' => $driver->id,
            'payout_amount' => 999,
            'transaction_id' => 'DRV-DELIVERY-SHARED',
            'status' => 'paid',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $dashboard = app(PartnerFinancialDashboardService::class)->forTransportDriver($driver);

        $this->assertSame(8000.0, $dashboard['cards'][0]['amount']);
        $this->assertSame(0.0, $dashboard['cards'][1]['amount']);
        $this->assertSame(8000.0, $dashboard['cards'][2]['amount']);
        $this->assertSame(0.0, $dashboard['cards'][3]['amount']);
        $this->assertSame(8000.0, $dashboard['cards'][4]['amount']);
        $this->assertSame(0.0, $dashboard['cards'][5]['amount']);
    }

    private function createRestaurantFixture(): array
    {
        $user = User::factory()->create([
            'type' => 'restaurant',
            'phone' => '0600002001',
        ]);

        $restaurantId = DB::table('restaurants')->insertGetId([
            'user_id' => $user->id,
            'name' => 'Dashboard Finance Restaurant',
            'user_name' => 'dashboard-finance-restaurant',
            'email' => 'dashboard-finance-restaurant@example.com',
            'password' => bcrypt('secret'),
            'slogan' => 'Test',
            'logo' => null,
            'cover_image' => null,
            'services' => 'both',
            'service_charges' => 0,
            'delivery_charges' => 500,
            'city' => 'Brazzaville',
            'tax' => 0,
            'address' => 'Adresse test',
            'latitude' => null,
            'longitude' => null,
            'phone' => '0600002002',
            'description' => null,
            'min_order' => 0,
            'avg_delivery_time' => null,
            'delivery_range' => null,
            'admin_commission' => 20,
            'approved' => 1,
            'featured' => 0,
            'account_name' => 'Restaurant Finance',
            'account_number' => 'REST-FIN-1',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $driverId = DB::table('drivers')->insertGetId([
            'restaurant_id' => $restaurantId,
            'name' => 'Driver Finance',
            'user_name' => 'driver-finance',
            'phone' => '0700002001',
            'email' => 'driver-finance@example.com',
            'image' => null,
            'password' => bcrypt('secret'),
            'hourly_pay' => 0,
            'address' => 'Adresse driver',
            'cnic' => 'CNIC-FIN-1',
            'approved' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [Restaurant::findOrFail($restaurantId), Driver::findOrFail($driverId)];
    }

    private function completedOrderPayload(int $restaurantId, float $total, string $orderNo): array
    {
        $customer = User::factory()->create([
            'type' => 'user',
            'phone' => '060001' . random_int(1000, 9999),
        ]);

        return [
            'order_no' => $orderNo,
            'user_id' => $customer->id,
            'restaurant_id' => $restaurantId,
            'qty' => 1,
            'price' => $total,
            'total_items' => 1,
            'offer_discount' => 0,
            'tax' => 0,
            'delivery_charges' => 0,
            'sub_total' => $total,
            'total' => $total,
            'admin_commission' => 20,
            'restaurant_commission' => 0,
            'driver_tip' => 0,
            'status' => 'completed',
            'delivery_address' => 'Brazzaville',
            'ordered_time' => now(),
            'delivered_time' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    private function orderPayload(
        int $userId,
        int $restaurantId,
        int $driverId,
        string $paymentStatus,
        string $paymentMethod,
        float $total
    ): array {
        $payload = [
            'user_id' => $userId,
            'restaurant_id' => $restaurantId,
            'driver_id' => $driverId,
            'total_items' => 1,
            'offer_discount' => 0,
            'tax' => 0,
            'delivery_charges' => 0,
            'sub_total' => $total,
            'total' => $total,
            'admin_commission' => 0,
            'restaurant_commission' => 0,
            'driver_tip' => 0,
            'status' => 'completed',
            'delivery_address' => 'Brazzaville',
            'scheduled_date' => now(),
            'd_lat' => '0',
            'd_lng' => '0',
            'ordered_time' => now(),
            'delivered_time' => now(),
            'payment_method' => $paymentMethod,
            'payment_status' => $paymentStatus,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        if ($paymentMethod === 'cash') {
            $payload['cash_collection_status'] = 'collected';
            $payload['cash_collection_confirmed_at'] = now();
        }

        return $payload;
    }
}
