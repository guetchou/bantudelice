<?php

namespace Tests\Feature;

use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class LegacyOrderApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_legacy_place_orders_endpoint_uses_normalized_order_creation(): void
    {
        $user = User::factory()->create(['type' => 'user']);
        $restaurantId = $this->createRestaurantFixture();
        $productId = $this->createProductFixture($restaurantId);

        DB::table('carts')->insert([
            'restaurant_id' => $restaurantId,
            'user_id' => $user->id,
            'product_id' => $productId,
            'qty' => 2,
            'price' => 3500,
            'latitude' => null,
            'longitude' => null,
            'description' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->postJson('/api/place_orders/', [
            'user_id' => $user->id,
            'sub_total' => 7000,
            'total' => 8350,
            'delivery_fee' => 1000,
            'tax' => 350,
            'delivery_address' => 'Avenue de la Paix',
            'd_lat' => '-4.2700',
            'd_lng' => '15.2800',
            'driver_tip' => 0,
            'payment_method' => 'cash',
        ]);

        $response->assertOk()
            ->assertJsonPath('status', true);

        $orderNo = $response->json('order_no');

        $this->assertNotEmpty($orderNo);
        $this->assertDatabaseHas('orders', [
            'order_no' => $orderNo,
            'user_id' => $user->id,
            'payment_method' => 'cash',
            'payment_status' => 'pending',
            'business_status' => 'pending_restaurant_acceptance',
            'total_items' => 2,
            'delivered_time' => null,
        ]);
    }

    public function test_legacy_complete_orders_keeps_actual_financial_values(): void
    {
        $user = User::factory()->create(['type' => 'user']);
        $restaurantId = $this->createRestaurantFixture();
        $productId = $this->createProductFixture($restaurantId);
        $driver = DB::table('drivers')->insertGetId([
            'name'       => 'Driver Test',
            'user_name'  => 'driver-legacy-' . uniqid(),
            'email'      => 'driver-legacy-' . uniqid() . '@example.com',
            'phone'      => '0600' . random_int(100000, 999999),
            'password'   => bcrypt('secret'),
            'address'    => 'Adresse test',
            'cnic'       => 'CNIC-LEGACY-' . uniqid(),
            'latitude'   => '-4.2700',
            'longitude'  => '15.2800',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('orders')->insert([
            'order_no' => 'LEGACY-COMPLETE-001',
            'user_id' => $user->id,
            'restaurant_id' => $restaurantId,
            'product_id' => $productId,
            'driver_id' => $driver,
            'qty' => 2,
            'price' => 3500,
            'latitude' => '-4.2700',
            'longitude' => '15.2800',
            'total_items' => 2,
            'offer_discount' => 1250,
            'tax' => 350,
            'delivery_charges' => 1000,
            'sub_total' => 7000,
            'total' => 7100,
            'admin_commission' => 560,
            'restaurant_commission' => 6540,
            'driver_tip' => 250,
            'delivery_address' => 'Avenue de la Paix',
            'scheduled_date' => null,
            'd_lat' => '-4.2700',
            'd_lng' => '15.2800',
            'ordered_time' => now()->subHour(),
            'delivered_time' => now(),
            'status' => 'completed',
            'created_at' => now()->subHour(),
            'updated_at' => now()->subMinutes(5),
        ]);

        $this->postJson('/api/complete_orders', [
            'user_id' => $user->id,
            'driver_id' => $driver,
        ])->assertOk()
            ->assertJsonPath('status', true);

        $this->assertDatabaseHas('completed_orders', [
            'order_no' => 'LEGACY-COMPLETE-001',
            'user_id' => $user->id,
            'price' => 3500,
            'total_items' => 2,
            'offer_discount' => 1250,
            'admin_commission' => 560,
            'restaurant_commission' => 6540,
            'driver_tip' => 250,
        ]);

        $this->assertSoftDeleted('orders', [
            'order_no' => 'LEGACY-COMPLETE-001',
        ]);
    }

    private function createRestaurantFixture(): int
    {
        $owner = User::factory()->create(['type' => 'restaurant']);

        return DB::table('restaurants')->insertGetId([
            'user_id' => $owner->id,
            'name' => 'Restaurant legacy api',
            'user_name' => 'restaurant-legacy-' . uniqid(),
            'email' => 'restaurant-legacy-' . uniqid() . '@example.com',
            'password' => bcrypt('secret'),
            'services' => 'food',
            'delivery_charges' => 1000,
            'city' => 'Brazzaville',
            'tax' => 5,
            'address' => 'Avenue de la Paix',
            'latitude' => '-4.2634',
            'longitude' => '15.2429',
            'phone' => '0600' . random_int(100000, 999999),
            'description' => 'Test',
            'min_order' => 1000,
            'admin_commission' => 5,
            'approved' => 1,
            'featured' => 0,
            'account_name' => 'Restaurant test',
            'account_number' => 'ACC-' . uniqid(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createProductFixture(int $restaurantId): int
    {
        $categoryId = DB::table('categories')->insertGetId([
            'restaurant_id' => $restaurantId,
            'name' => 'Plats',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return DB::table('products')->insertGetId([
            'category_id' => $categoryId,
            'restaurant_id' => $restaurantId,
            'name' => 'Poulet braise',
            'image' => 'test.webp',
            'price' => 3500,
            'discount_price' => 0,
            'description' => 'Plat test',
            'featured' => 0,
            'size' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
