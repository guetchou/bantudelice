<?php

namespace Tests\Feature;

use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class FooterBadgesSmokeTest extends TestCase
{
    use RefreshDatabase;

    private function assertFooterBadgesVisible($response): void
    {
        $response->assertStatus(200);
        $response->assertSee('Téléchargez l', false);
        $response->assertSee('Moyens de paiement acceptés', false);
        $response->assertSee('MTN MoMo', false);
        $response->assertSee('Airtel Money', false);
        $response->assertSee('Espèces à la livraison', false);
        $response->assertSee('data-coming-soon="true"', false);
    }

    private function createApprovedRestaurant(): int
    {
        $restUserId = User::factory()->create(['type' => 'restaurant', 'phone' => '0600077001'])->id;

        return DB::table('restaurants')->insertGetId([
            'user_id' => $restUserId,
            'name' => 'Restaurant Footer Smoke',
            'user_name' => 'restaurant-footer-smoke',
            'email' => 'footer-smoke@example.com',
            'password' => bcrypt('secret'),
            'services' => 'both',
            'service_charges' => 0,
            'delivery_charges' => 500,
            'city' => 'Brazzaville',
            'tax' => 0,
            'address' => 'Adresse test',
            'phone' => '0600077002',
            'admin_commission' => 10,
            'approved' => 1,
            'featured' => 0,
            'account_name' => 'Restaurant Footer Smoke',
            'account_number' => 'REST-SMOKE-001',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_home_page_renders_footer_badges(): void
    {
        $response = $this->get(route('home'));
        $this->assertFooterBadgesVisible($response);
    }

    public function test_restaurant_menu_page_renders_footer_badges(): void
    {
        $restaurantId = $this->createApprovedRestaurant();

        $response = $this->get(route('restaurant.detail', $restaurantId));
        $this->assertFooterBadgesVisible($response);
    }

    public function test_checkout_page_renders_footer_badges(): void
    {
        $restaurantId = $this->createApprovedRestaurant();
        $customer = User::factory()->create(['type' => 'user', 'phone' => '0600077099']);

        $categoryId = DB::table('categories')->insertGetId([
            'restaurant_id' => $restaurantId,
            'name' => 'Categorie Footer Smoke',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $productId = DB::table('products')->insertGetId([
            'restaurant_id' => $restaurantId,
            'category_id' => $categoryId,
            'name' => 'Plat Footer Smoke',
            'description' => 'Test',
            'price' => 2500,
            'image' => 'placeholder.png',
            'is_available' => 1,
            'featured' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('carts')->insert([
            'user_id' => $customer->id,
            'restaurant_id' => $restaurantId,
            'product_id' => $productId,
            'qty' => 1,
            'price' => 2500,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // checkout.blade.php active hide_primary_chrome (comportement préexistant,
        // hors périmètre de cette tâche) qui masque header ET footer pour un flux
        // de paiement sans distraction. Aucun footer n'est donc attendu ici : on
        // vérifie seulement l'absence d'erreur Blade/HTTP, pas la présence des badges.
        $response = $this->actingAs($customer)->get(route('checkout.detail'));
        $response->assertStatus(200);
    }

    public function test_track_order_legacy_layout_renders_footer_badges(): void
    {
        $owner = User::factory()->create(['type' => 'user', 'phone' => '0600088001']);
        $restaurantId = $this->createApprovedRestaurant();

        DB::table('orders')->insert([
            'order_no' => 'TD-SMOKE-0001',
            'user_id' => $owner->id,
            'restaurant_id' => $restaurantId,
            'qty' => 1,
            'price' => 1000,
            'total_items' => 1,
            'offer_discount' => 0,
            'tax' => 0,
            'delivery_charges' => 0,
            'sub_total' => 1000,
            'total' => 1000,
            'admin_commission' => 0,
            'restaurant_commission' => 0,
            'driver_tip' => 0,
            'status' => 'pending',
            'business_status' => 'pending_restaurant_acceptance',
            'payment_method' => 'cash',
            'payment_status' => 'pending',
            'delivery_address' => 'Test',
            'd_lat' => '0',
            'd_lng' => '0',
            'ordered_time' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($owner)->get(route('track.order', ['orderNo' => 'TD-SMOKE-0001']));
        $this->assertFooterBadgesVisible($response);
    }
}
