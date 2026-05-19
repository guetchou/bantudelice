<?php

namespace Tests\Feature\Food;

use App\Cart;
use App\Domain\Food\Services\PlaceOrderService;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PlaceOrderServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_consistent_order_lines_with_shared_order_number(): void
    {
        $user = User::factory()->create(['type' => 'user']);
        $restaurantId = $this->createRestaurantFixture();
        $firstProductId = $this->createProductFixture($restaurantId, 'Poulet', 3500);
        $secondProductId = $this->createProductFixture($restaurantId, 'Poisson', 4200);

        $cartItems = collect([
            Cart::create([
                'restaurant_id' => $restaurantId,
                'user_id' => $user->id,
                'product_id' => $firstProductId,
                'qty' => 1,
                'price' => 3500,
            ]),
            Cart::create([
                'restaurant_id' => $restaurantId,
                'user_id' => $user->id,
                'product_id' => $secondProductId,
                'qty' => 2,
                'price' => 4200,
            ]),
        ]);

        $orderNo = app(PlaceOrderService::class)->placeFromCart($user, $cartItems, [
            'order_no' => 'TEST-ORDER-001',
            'tax' => 250,
            'delivery_charges' => 1000,
            'sub_total' => 11900,
            'total' => 13150,
            'driver_tip' => 0,
            'delivery_address' => 'Avenue de la Paix',
            'd_lat' => '-4.2700',
            'd_lng' => '15.2800',
            'payment_method' => 'cash',
            'payment_status' => 'pending',
        ]);

        $this->assertSame('TEST-ORDER-001', $orderNo);
        $this->assertDatabaseCount('orders', 2);
        $this->assertDatabaseHas('orders', [
            'order_no' => 'TEST-ORDER-001',
            'product_id' => $firstProductId,
            'total_items' => 3,
            'business_status' => 'pending_restaurant_acceptance',
        ]);
        $this->assertDatabaseHas('orders', [
            'order_no' => 'TEST-ORDER-001',
            'product_id' => $secondProductId,
            'total_items' => 3,
            'business_status' => 'pending_restaurant_acceptance',
        ]);
    }

    public function test_it_generates_pickup_address_and_coordinates_from_restaurant_context(): void
    {
        $user = User::factory()->create(['type' => 'user']);
        $restaurantId = $this->createRestaurantFixture(
            latitude: '-4.2634',
            longitude: '15.2429',
            address: 'Centre-ville'
        );
        $productId = $this->createProductFixture($restaurantId, 'Burger', 2800);

        $cartItem = Cart::create([
            'restaurant_id' => $restaurantId,
            'user_id' => $user->id,
            'product_id' => $productId,
            'qty' => 1,
            'price' => 2800,
        ]);

        $orderNo = app(PlaceOrderService::class)->placeFromCart($user, [$cartItem], [
            'order_no' => 'TEST-PICKUP-001',
            'fulfillment_mode' => 'pickup',
            'pickup_note' => 'Client pressé',
            'tax' => 140,
            'delivery_charges' => 0,
            'sub_total' => 2800,
            'total' => 2940,
            'driver_tip' => 0,
            'payment_method' => 'cash',
            'payment_status' => 'pending',
        ]);

        $this->assertSame('TEST-PICKUP-001', $orderNo);
        $this->assertDatabaseHas('orders', [
            'order_no' => 'TEST-PICKUP-001',
            'delivery_address' => 'Retrait sur place | Restaurant Test | Centre-ville | Note: Client pressé',
            'latitude' => '-4.2634',
            'longitude' => '15.2429',
            'd_lat' => '-4.2634',
            'd_lng' => '15.2429',
            'fulfillment_mode' => 'pickup',
        ]);
    }

    private function createRestaurantFixture(
        ?string $latitude = null,
        ?string $longitude = null,
        string $address = 'Avenue test'
    ): int {
        $owner = User::factory()->create(['type' => 'restaurant']);

        return DB::table('restaurants')->insertGetId([
            'user_id' => $owner->id,
            'name' => 'Restaurant Test',
            'user_name' => 'restaurant-test-' . uniqid(),
            'email' => 'restaurant-' . uniqid() . '@example.com',
            'password' => bcrypt('secret'),
            'services' => 'food',
            'delivery_charges' => 1000,
            'city' => 'Brazzaville',
            'tax' => 5,
            'address' => $address,
            'latitude' => $latitude,
            'longitude' => $longitude,
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

    private function createProductFixture(int $restaurantId, string $name, int $price): int
    {
        $categoryId = DB::table('categories')->insertGetId([
            'restaurant_id' => $restaurantId,
            'name' => 'Categorie ' . uniqid(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return DB::table('products')->insertGetId([
            'category_id' => $categoryId,
            'restaurant_id' => $restaurantId,
            'name' => $name,
            'image' => 'test.webp',
            'price' => $price,
            'discount_price' => 0,
            'description' => 'Produit test',
            'featured' => 0,
            'size' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
