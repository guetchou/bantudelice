<?php

namespace Tests\Feature;

use App\Delivery;
use App\Driver;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DriverDeliveriesApiTest extends TestCase
{
    use RefreshDatabase;

    private function createDriverAccountFixture(string $suffix = '1'): array
    {
        $driverEmail = "driver-api-{$suffix}@example.com";
        $driverPhone = '07000030' . str_pad($suffix, 2, '0', STR_PAD_LEFT);

        $restaurantUser = User::factory()->create([
            'type' => 'restaurant',
            'email' => "restaurant-api-{$suffix}@example.com",
            'phone' => '06000030' . str_pad($suffix, 2, '0', STR_PAD_LEFT),
        ]);

        $restaurantId = DB::table('restaurants')->insertGetId([
            'user_id' => $restaurantUser->id,
            'name' => 'Restaurant API ' . $suffix,
            'user_name' => 'restaurant-api-' . $suffix,
            'email' => "restaurant-api-{$suffix}@example.com",
            'password' => bcrypt('secret'),
            'slogan' => 'API',
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
            'phone' => '06000031' . str_pad($suffix, 2, '0', STR_PAD_LEFT),
            'description' => null,
            'min_order' => 0,
            'avg_delivery_time' => null,
            'delivery_range' => null,
            'admin_commission' => 10,
            'approved' => 1,
            'featured' => 0,
            'account_name' => 'Restaurant API',
            'account_number' => 'REST-API-' . $suffix,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $driverId = DB::table('drivers')->insertGetId([
            'restaurant_id' => $restaurantId,
            'name' => 'Livreur API ' . $suffix,
            'user_name' => 'driver-api-' . $suffix,
            'phone' => $driverPhone,
            'email' => $driverEmail,
            'image' => null,
            'password' => bcrypt('secret'),
            'hourly_pay' => 0,
            'address' => 'Adresse driver',
            'cnic' => 'CNIC-API-' . $suffix,
            'approved' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [Driver::findOrFail($driverId), $restaurantId, $driverId];
    }

    private function createDeliveryForDriver(int $restaurantId, int $driverId, string $orderNo): Delivery
    {
        $customer = User::factory()->create([
            'type' => 'user',
            'phone' => '061100' . substr($orderNo, -4),
        ]);

        $orderId = DB::table('orders')->insertGetId([
            'order_no' => $orderNo,
            'user_id' => $customer->id,
            'restaurant_id' => $restaurantId,
            'driver_id' => $driverId,
            'product_id' => null,
            'qty' => 1,
            'price' => 4200,
            'total_items' => 1,
            'offer_discount' => 0,
            'tax' => 0,
            'delivery_charges' => 500,
            'sub_total' => 4200,
            'total' => 4700,
            'admin_commission' => 0,
            'restaurant_commission' => 0,
            'driver_tip' => 0,
            'status' => 'assign',
            'business_status' => 'out_for_delivery',
            'technical_status' => null,
            'payment_method' => 'cash',
            'payment_status' => 'pending',
            'delivery_address' => 'Adresse client',
            'scheduled_date' => null,
            'd_lat' => '0',
            'd_lng' => '0',
            'ordered_time' => now()->subHour(),
            'delivered_time' => null,
            'latitude' => null,
            'longitude' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return Delivery::create([
            'order_id' => $orderId,
            'restaurant_id' => $restaurantId,
            'driver_id' => $driverId,
            'status' => 'ASSIGNED',
            'delivery_fee' => 500,
            'assigned_at' => now()->subMinutes(15),
        ]);
    }

    public function test_non_driver_account_is_rejected_on_driver_deliveries_index(): void
    {
        $user = User::factory()->create([
            'type' => 'user',
        ]);

        $this->actingAs($user, 'api')
            ->getJson('/api/driver/deliveries')
            ->assertStatus(401)
            ->assertJson([
                'status' => false,
                'message' => 'Non authentifié',
            ]);
    }

    public function test_driver_cannot_update_status_for_unknown_or_unowned_delivery(): void
    {
        [$driverUser] = $this->createDriverAccountFixture('11');
        [, $foreignRestaurantId, $foreignDriverId] = $this->createDriverAccountFixture('12');
        $foreignDelivery = $this->createDeliveryForDriver($foreignRestaurantId, $foreignDriverId, 'ORD-DRV-2012');

        $this->actingAs($driverUser, 'driver_api')
            ->patchJson('/api/driver/deliveries/' . $foreignDelivery->id . '/status', [
                'status' => 'PICKED_UP',
            ])
            ->assertStatus(404)
            ->assertJson([
                'status' => false,
                'message' => 'Livraison introuvable',
            ]);
    }

    public function test_driver_cannot_report_incident_for_unknown_or_unowned_delivery(): void
    {
        [$driverUser] = $this->createDriverAccountFixture('21');
        [, $foreignRestaurantId, $foreignDriverId] = $this->createDriverAccountFixture('22');
        $foreignDelivery = $this->createDeliveryForDriver($foreignRestaurantId, $foreignDriverId, 'ORD-DRV-2022');

        $this->actingAs($driverUser, 'driver_api')
            ->postJson('/api/driver/deliveries/' . $foreignDelivery->id . '/incident', [
                'reason' => 'customer_absent',
                'notes' => 'Tentative sur une livraison non rattachée',
            ])
            ->assertStatus(404)
            ->assertJson([
                'status' => false,
                'message' => 'Livraison introuvable',
            ]);
    }
}
