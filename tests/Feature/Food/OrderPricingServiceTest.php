<?php

namespace Tests\Feature\Food;

use App\Cart;
use App\Charge;
use App\Domain\Food\Services\OrderPricingService;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class OrderPricingServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_calculates_delivery_totals_from_cart_and_charge_profile(): void
    {
        $user = User::factory()->create(['type' => 'user']);
        $restaurantId = $this->createRestaurantFixture();
        $productId = $this->createProductFixture($restaurantId, 3500);

        Charge::create([
            'delivery_fee' => 1000,
            'tax' => 5,
            'service_fee' => 2,
        ]);

        $cartItems = collect([
            Cart::create([
                'restaurant_id' => $restaurantId,
                'user_id' => $user->id,
                'product_id' => $productId,
                'qty' => 2,
                'price' => 3500,
            ]),
        ]);

        $totals = app(OrderPricingService::class)->calculate($cartItems, [
            'driver_tip' => 500,
        ]);

        $this->assertSame(7000.0, $totals['sub_total']);
        $this->assertSame(350.0, $totals['tax']);
        $this->assertSame(1000.0, $totals['delivery_fee']);
        $this->assertSame(167.0, $totals['service_fee']);
        $this->assertSame(500.0, $totals['driver_tip']);
        $this->assertSame(0.0, $totals['discount']);
        $this->assertSame(9017.0, $totals['total']);
    }

    public function test_it_applies_voucher_discount_and_removes_driver_tip_for_pickup(): void
    {
        $user = User::factory()->create(['type' => 'user']);
        $restaurantId = $this->createRestaurantFixture();
        $productId = $this->createProductFixture($restaurantId, 3500);

        Charge::create([
            'delivery_fee' => 1000,
            'tax' => 5,
            'service_fee' => 2,
        ]);

        DB::table('vouchers')->insert([
            'restaurant_id' => $restaurantId,
            'name' => 'PROMO10',
            'discount' => 10,
            'start_date' => now()->subDay(),
            'end_date' => now()->addDay(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $cartItems = collect([
            Cart::create([
                'restaurant_id' => $restaurantId,
                'user_id' => $user->id,
                'product_id' => $productId,
                'qty' => 2,
                'price' => 3500,
            ]),
        ]);

        $totals = app(OrderPricingService::class)->calculate($cartItems, [
            'fulfillment_mode' => 'pickup',
            'driver_tip' => 700,
            'voucher_code' => 'PROMO10',
        ]);

        $this->assertSame(7000.0, $totals['sub_total']);
        $this->assertSame(350.0, $totals['tax']);
        $this->assertSame(0.0, $totals['delivery_fee']);
        $this->assertSame(147.0, $totals['service_fee']);
        $this->assertSame(0.0, $totals['driver_tip']);
        $this->assertSame(700.0, $totals['discount']);
        $this->assertSame(6797.0, $totals['total']);
    }

    private function createRestaurantFixture(): int
    {
        $owner = User::factory()->create(['type' => 'restaurant']);

        return DB::table('restaurants')->insertGetId([
            'user_id' => $owner->id,
            'name' => 'Restaurant Test',
            'user_name' => 'restaurant-pricing-' . uniqid(),
            'email' => 'restaurant-pricing-' . uniqid() . '@example.com',
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

    private function createProductFixture(int $restaurantId, int $price): int
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
            'name' => 'Produit test',
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
