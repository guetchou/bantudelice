<?php

namespace Tests\Feature\Food;

use App\Cart;
use App\Charge;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class WebCheckoutViewTest extends TestCase
{
    use RefreshDatabase;

    public function test_checkout_view_uses_shared_pricing_totals(): void
    {
        $user = User::factory()->create(['type' => 'user']);
        $restaurantId = $this->createRestaurantFixture();
        $productId = $this->createProductFixture($restaurantId, 3500);

        Charge::create([
            'delivery_fee' => 1000,
            'tax' => 5,
            'service_fee' => 2,
        ]);

        Cart::create([
            'restaurant_id' => $restaurantId,
            'user_id' => $user->id,
            'product_id' => $productId,
            'qty' => 2,
            'price' => 3500,
        ]);

        $response = $this->actingAs($user)->get('/checkout');

        $response->assertOk()
            ->assertViewIs('frontend.checkout')
            ->assertViewHas('total', 7000.0)
            ->assertViewHas('tax', 350.0)
            ->assertViewHas('service_fee', 167.0)
            ->assertViewHas('grandTotal', 8517.0);
    }

    private function createRestaurantFixture(): int
    {
        $owner = User::factory()->create(['type' => 'restaurant']);

        return DB::table('restaurants')->insertGetId([
            'user_id' => $owner->id,
            'name' => 'Restaurant checkout web',
            'user_name' => 'restaurant-web-' . uniqid(),
            'email' => 'restaurant-web-' . uniqid() . '@example.com',
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
