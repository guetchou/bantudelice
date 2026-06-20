<?php

namespace Tests\Feature\Food;

use App\Domain\Food\Events\FoodDriverOrderUpdated;
use App\Domain\Food\Events\FoodMissionPresenceUpdated;
use App\Domain\Food\Events\FoodOrderStatusUpdated;
use App\Domain\Food\Events\FoodRestaurantOrderUpdated;
use App\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class RestaurantHoursCheckoutTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Event::fake([
            FoodOrderStatusUpdated::class,
            FoodRestaurantOrderUpdated::class,
            FoodDriverOrderUpdated::class,
            FoodMissionPresenceUpdated::class,
        ]);
    }

    private function createRestaurantWithCart(User $customer): int
    {
        $owner = User::factory()->create(['type' => 'user']);

        $restaurantId = DB::table('restaurants')->insertGetId([
            'user_id'          => $owner->id,
            'name'             => 'Restaurant Horaires Test',
            'user_name'        => 'restaurant-hours-' . uniqid(),
            'email'            => 'hours-' . uniqid() . '@example.com',
            'password'         => bcrypt('secret'),
            'services'         => 'food',
            'delivery_charges' => 500,
            'city'             => 'Brazzaville',
            'tax'              => 0,
            'address'          => 'Avenue Test',
            'phone'            => '0600' . substr(uniqid(), -6),
            'min_order'        => 0,
            'admin_commission' => 5,
            'approved'         => 1,
            'account_name'     => 'Test',
            'account_number'   => '0000',
            'created_at'       => now(),
            'updated_at'       => now(),
        ]);

        $categoryId = DB::table('categories')->insertGetId([
            'restaurant_id' => $restaurantId,
            'name'          => 'Plats',
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);

        $productId = DB::table('products')->insertGetId([
            'category_id'    => $categoryId,
            'restaurant_id'  => $restaurantId,
            'name'           => 'Plat test',
            'image'          => 'test.webp',
            'price'          => 2000,
            'discount_price' => 0,
            'description'    => 'Test',
            'featured'       => 0,
            'created_at'     => now(),
            'updated_at'     => now(),
        ]);

        DB::table('carts')->insert([
            'restaurant_id' => $restaurantId,
            'user_id'       => $customer->id,
            'product_id'    => $productId,
            'qty'           => 1,
            'price'         => 2000,
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);

        return $restaurantId;
    }

    private function checkout(User $customer): \Illuminate\Testing\TestResponse
    {
        // fulfillment_mode=pickup évite le check de disponibilité livreurs,
        // ce qui permet de tester le guard horaires de façon isolée.
        return $this->actingAs($customer, 'api')
            ->postJson('/api/checkout', [
                'payment_method'   => 'cash',
                'fulfillment_mode' => 'pickup',
                'delivery_address' => 'Au restaurant',
                'd_lat'            => -4.27,
                'd_lng'            => 15.28,
            ]);
    }

    public function test_checkout_allowed_when_restaurant_is_open(): void
    {
        $customer = User::factory()->create(['type' => 'user']);
        $restaurantId = $this->createRestaurantWithCart($customer);

        // Horaire qui couvre toute la journée
        $currentDay = strtolower(now()->format('l'));
        $dayMap = [
            'monday' => 'lundi', 'tuesday' => 'mardi', 'wednesday' => 'mercredi',
            'thursday' => 'jeudi', 'friday' => 'vendredi', 'saturday' => 'samedi', 'sunday' => 'dimanche',
        ];
        DB::table('working_hours')->insert([
            'restaurant_id' => $restaurantId,
            'Day'           => $dayMap[$currentDay] ?? $currentDay,
            'opening_time'  => '00:00:00',
            'closing_time'  => '23:59:00',
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);

        $this->checkout($customer)->assertOk();

        $this->assertDatabaseHas('orders', ['restaurant_id' => $restaurantId]);
    }

    public function test_checkout_blocked_when_restaurant_is_closed(): void
    {
        $customer = User::factory()->create(['type' => 'user']);
        $restaurantId = $this->createRestaurantWithCart($customer);

        // Horaire configuré uniquement pour un jour différent du jour courant
        $otherDay = now()->addDay()->format('l');
        $dayMap = [
            'Monday' => 'lundi', 'Tuesday' => 'mardi', 'Wednesday' => 'mercredi',
            'Thursday' => 'jeudi', 'Friday' => 'vendredi', 'Saturday' => 'samedi', 'Sunday' => 'dimanche',
        ];
        DB::table('working_hours')->insert([
            'restaurant_id' => $restaurantId,
            'Day'           => $dayMap[$otherDay] ?? strtolower($otherDay),
            'opening_time'  => '09:00:00',
            'closing_time'  => '22:00:00',
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);

        $response = $this->checkout($customer)->assertStatus(422);
        $this->assertFalse($response->json('status'));
        $this->assertStringStartsWith(
            'Ce restaurant est actuellement fermé.',
            $response->json('message') ?? ''
        );

        $this->assertDatabaseMissing('orders', ['restaurant_id' => $restaurantId]);
    }

    public function test_checkout_allowed_when_no_schedule_configured(): void
    {
        $customer = User::factory()->create(['type' => 'user']);
        $restaurantId = $this->createRestaurantWithCart($customer);
        // Aucun working_hours → 24/7

        $this->checkout($customer)->assertOk();

        $this->assertDatabaseHas('orders', ['restaurant_id' => $restaurantId]);
    }

    public function test_checkout_blocked_by_active_special_closure(): void
    {
        $customer = User::factory()->create(['type' => 'user']);
        $restaurantId = $this->createRestaurantWithCart($customer);
        // Pas de working_hours — mais special_closure active

        DB::table('restaurant_special_closures')->insert([
            'restaurant_id' => $restaurantId,
            'label'         => 'Congés annuels',
            'starts_on'     => now()->subDay()->toDateString(),
            'ends_on'       => now()->addDay()->toDateString(),
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);

        $this->checkout($customer)
            ->assertStatus(422)
            ->assertJsonPath('status', false);

        $this->assertDatabaseMissing('orders', ['restaurant_id' => $restaurantId]);
    }

    public function test_checkout_allowed_when_special_closure_expired(): void
    {
        $customer = User::factory()->create(['type' => 'user']);
        $restaurantId = $this->createRestaurantWithCart($customer);

        // Fermeture expirée (ends_on avant aujourd'hui)
        DB::table('restaurant_special_closures')->insert([
            'restaurant_id' => $restaurantId,
            'label'         => 'Fermeture passée',
            'starts_on'     => now()->subDays(5)->toDateString(),
            'ends_on'       => now()->subDay()->toDateString(),
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);

        $this->checkout($customer)->assertOk();

        $this->assertDatabaseHas('orders', ['restaurant_id' => $restaurantId]);
    }
}
