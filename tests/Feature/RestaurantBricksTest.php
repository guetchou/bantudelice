<?php

namespace Tests\Feature;

use App\Category;
use App\Product;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RestaurantBricksTest extends TestCase
{
    use RefreshDatabase;

    private function createRestaurantUserWithRestaurant(): array
    {
        /** @var User $user */
        $user = User::factory()->create([
            'type' => 'restaurant',
            'phone' => '0100000001',
        ]);

        $restaurantId = DB::table('restaurants')->insertGetId([
            'user_id' => $user->id,
            'name' => 'Restaurant Test',
            'user_name' => 'restaurant-test',
            'email' => 'restaurant-test@example.com',
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
            'phone' => '0600000001',
            'description' => null,
            'min_order' => 0,
            'avg_delivery_time' => null,
            'delivery_range' => null,
            'admin_commission' => 10,
            'approved' => 1,
            'featured' => 0,
            'account_name' => 'Test',
            'account_number' => '0000',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // driver obligatoire (FK orders.driver_id)
        $driverId = DB::table('drivers')->insertGetId([
            'restaurant_id' => $restaurantId,
            'name' => 'Driver Test',
            'user_name' => 'driver-test',
            'phone' => '0700000001',
            'email' => 'driver-test@example.com',
            'image' => null,
            'password' => bcrypt('secret'),
            'hourly_pay' => 0,
            'address' => 'Adresse driver',
            'cnic' => 'CNIC12345',
            'approved' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [$user, $restaurantId, $driverId];
    }

    public function test_restaurant_can_manage_media_external_url()
    {
        [$user, $restaurantId] = $this->createRestaurantUserWithRestaurant();

        $this->actingAs($user);

        $resp = $this->postJson(route('restaurant.media.store'), [
            'external_url' => 'https://example.com/image.jpg',
            'alt_text' => 'Image test',
        ]);

        $resp->assertOk();
        $this->assertDatabaseHas('restaurant_media', [
            'restaurant_id' => $restaurantId,
            'source' => 'external',
            'external_url' => 'https://example.com/image.jpg',
        ]);

        $mediaId = DB::table('restaurant_media')->where('restaurant_id', $restaurantId)->value('id');

        $this->patchJson(route('restaurant.media.reorder'), ['ids' => [$mediaId]])
            ->assertOk();

        $this->deleteJson(route('restaurant.media.destroy', ['media' => $mediaId]))
            ->assertOk();
    }

    public function test_restaurant_can_reorder_and_toggle_menu()
    {
        [$user, $restaurantId] = $this->createRestaurantUserWithRestaurant();
        $this->actingAs($user);

        $c1 = Category::create(['restaurant_id' => $restaurantId, 'name' => 'Entrées', 'sort_order' => 1, 'is_available' => true]);
        $c2 = Category::create(['restaurant_id' => $restaurantId, 'name' => 'Plats', 'sort_order' => 2, 'is_available' => true]);

        $p1 = Product::create([
            'restaurant_id' => $restaurantId,
            'category_id' => $c1->id,
            'name' => 'Salade',
            'image' => 'default-food.jpg',
            'price' => 1000,
            'discount_price' => null,
            'description' => null,
            'size' => null,
            'sort_order' => 1,
            'is_available' => true,
        ]);

        $this->get(route('restaurant.menu.index'))
            ->assertOk()
            ->assertSee('Entrées')
            ->assertSee('Salade');

        $this->patchJson(route('restaurant.menu.products.availability', ['product' => $p1->id]))
            ->assertOk()
            ->assertJsonFragment(['is_available' => false]);

        $this->assertDatabaseHas('products', [
            'id' => $p1->id,
            'is_available' => 0,
        ]);

        $this->patchJson(route('restaurant.menu.categories.reorder'), [
            'ids' => [$c2->id, $c1->id],
        ])->assertOk();

        $this->assertDatabaseHas('categories', ['id' => $c2->id, 'sort_order' => 1]);
        $this->assertDatabaseHas('categories', ['id' => $c1->id, 'sort_order' => 2]);
    }

    public function test_kitchen_orders_json_and_status_update()
    {
        [$user, $restaurantId, $driverId] = $this->createRestaurantUserWithRestaurant();
        $this->actingAs($user);

        $customer = User::factory()->create(['type' => 'user', 'phone' => '0100000002']);

        $cat = Category::create(['restaurant_id' => $restaurantId, 'name' => 'Plats']);
        $p1 = Product::create([
            'restaurant_id' => $restaurantId,
            'category_id' => $cat->id,
            'name' => 'Poulet',
            'image' => 'default-food.jpg',
            'price' => 1500,
        ]);
        $p2 = Product::create([
            'restaurant_id' => $restaurantId,
            'category_id' => $cat->id,
            'name' => 'Riz',
            'image' => 'default-food.jpg',
            'price' => 800,
        ]);

        $orderNo = 'T-100';
        foreach ([
            [$p1, 2, 1500],
            [$p2, 1, 800],
        ] as $row) {
            [$prod, $qty, $price] = $row;
            DB::table('orders')->insert([
                'user_id' => $customer->id,
                'restaurant_id' => $restaurantId,
                'driver_id' => $driverId,
                'total_items' => 3,
                'offer_discount' => 0,
                'tax' => 0,
                'delivery_charges' => 500,
                'sub_total' => 3800,
                'total' => 4300,
                'admin_commission' => 0,
                'restaurant_commission' => 0,
                'driver_tip' => 0,
                'status' => 'pending',
                'delivery_address' => 'Adresse client',
                'scheduled_date' => null,
                'd_lat' => '0',
                'd_lng' => '0',
                'ordered_time' => now(),
                'delivered_time' => now(),
                'order_no' => $orderNo,
                'product_id' => $prod->id,
                'qty' => $qty,
                'price' => $price,
                'latitude' => null,
                'longitude' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $this->getJson(route('restaurant.kitchen.orders'))
            ->assertOk()
            ->assertJsonFragment(['order_no' => $orderNo]);

        $this->patchJson(route('restaurant.kitchen.orders.status', ['orderNo' => $orderNo]), [
            'status' => 'prepairing',
        ])->assertOk();

        $this->assertDatabaseHas('orders', ['order_no' => $orderNo, 'status' => 'prepairing']);
    }

    public function test_add_to_cart_is_blocked_for_unavailable_product()
    {
        [$user, $restaurantId] = $this->createRestaurantUserWithRestaurant();
        $this->actingAs($user);

        $cat = Category::create(['restaurant_id' => $restaurantId, 'name' => 'Plats']);
        $p = Product::create([
            'restaurant_id' => $restaurantId,
            'category_id' => $cat->id,
            'name' => 'Plat indisponible',
            'image' => 'default-food.jpg',
            'price' => 1000,
            'is_available' => false,
        ]);

        $this->postJson(route('cart'), [
            'restaurant_id' => $restaurantId,
            'product_id' => $p->id,
            'qty' => 1,
        ])->assertStatus(422);
    }
}


