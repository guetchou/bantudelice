<?php

namespace Tests\Feature;

use App\Restaurant;
use App\Services\RestaurantDashboardDataService;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RestaurantDashboardTrackedOrdersTest extends TestCase
{
    use RefreshDatabase;

    public function test_tracked_orders_count_keeps_assigned_and_scheduled_orders(): void
    {
        $restaurant = $this->createRestaurant();
        $customer = User::factory()->create(['type' => 'user', 'phone' => '0611100001']);

        DB::table('orders')->insert([
            $this->orderPayload($customer->id, $restaurant->id, 'ORD-PENDING', 'pending', 'pending_restaurant_acceptance'),
            $this->orderPayload($customer->id, $restaurant->id, 'ORD-ASSIGNED', 'assign', 'driver_assigned'),
            $this->orderPayload($customer->id, $restaurant->id, 'ORD-SCHEDULED', 'scheduled', 'scheduled', now()->addDay()),
        ]);

        DB::table('completed_orders')->insert([
            $this->completedOrderPayload($customer->id, $restaurant->id, 'ORD-COMPLETED', 'completed', 7000),
            $this->completedOrderPayload($customer->id, $restaurant->id, 'ORD-CANCELLED', 'cancelled', 4000),
        ]);

        $dashboard = app(RestaurantDashboardDataService::class)->build($restaurant);

        $this->assertSame(5, $dashboard['trackedOrdersCount']);
        $this->assertSame(1, $dashboard['scheduleOrders']);
        $this->assertSame(1, $dashboard['getPendings']);
        $this->assertSame(1, $dashboard['getComleted']);
        $this->assertSame(33, $dashboard['getPendingAvg']);
        $this->assertSame(33, $dashboard['getCompletedAvg']);
        $this->assertSame(33, $dashboard['getCanceledAvg']);
    }

    protected function createRestaurant(): Restaurant
    {
        $user = User::factory()->create([
            'type' => 'restaurant',
            'phone' => '0611100000',
        ]);

        $restaurantId = DB::table('restaurants')->insertGetId([
            'user_id' => $user->id,
            'name' => 'Audit Dashboard Restaurant',
            'user_name' => 'audit-dashboard-restaurant',
            'email' => 'audit-dashboard-restaurant@example.com',
            'password' => bcrypt('secret'),
            'slogan' => 'Test',
            'services' => 'both',
            'service_charges' => 0,
            'delivery_charges' => 500,
            'city' => 'Brazzaville',
            'tax' => 0,
            'address' => 'Adresse test',
            'phone' => '0611100002',
            'admin_commission' => 20,
            'approved' => 1,
            'featured' => 0,
            'account_name' => 'Audit Dashboard Restaurant',
            'account_number' => 'AUDIT-REST-001',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('drivers')->insert([
            'restaurant_id' => $restaurantId,
            'name' => 'Driver Audit Restaurant',
            'user_name' => 'driver-audit-restaurant',
            'phone' => '0611100010',
            'email' => 'driver-audit-restaurant@example.com',
            'image' => null,
            'password' => bcrypt('secret'),
            'hourly_pay' => 0,
            'address' => 'Adresse driver',
            'cnic' => 'CNIC-AUDIT-REST-001',
            'approved' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return Restaurant::findOrFail($restaurantId);
    }

    protected function orderPayload(
        int $userId,
        int $restaurantId,
        string $orderNo,
        string $status,
        string $businessStatus,
        $scheduledDate = null
    ): array {
        return [
            'order_no' => $orderNo,
            'user_id' => $userId,
            'restaurant_id' => $restaurantId,
            'driver_id' => DB::table('drivers')->where('restaurant_id', $restaurantId)->value('id'),
            'qty' => 1,
            'price' => 2500,
            'total_items' => 1,
            'offer_discount' => 0,
            'tax' => 0,
            'delivery_charges' => 0,
            'sub_total' => 2500,
            'total' => 2500,
            'admin_commission' => 0,
            'restaurant_commission' => 0,
            'driver_tip' => 0,
            'status' => $status,
            'business_status' => $businessStatus,
            'delivery_address' => 'Brazzaville',
            'scheduled_date' => $scheduledDate,
            'd_lat' => '0',
            'd_lng' => '0',
            'ordered_time' => now(),
            'delivered_time' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    protected function completedOrderPayload(
        int $userId,
        int $restaurantId,
        string $orderNo,
        string $status,
        float $total
    ): array {
        return [
            'order_no' => $orderNo,
            'user_id' => $userId,
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
            'status' => $status,
            'delivery_address' => 'Brazzaville',
            'ordered_time' => now(),
            'delivered_time' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
