<?php

namespace Tests\Feature\Restaurant;

use App\User;
use App\Restaurant;
use Tests\TestCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AnalyticsControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // AnalyticsController utilise HOUR(), une fonction MySQL absente de SQLite
        // (moteur de test isolé en mémoire). On l'enregistre ici pour pouvoir
        // exécuter le contrôleur réel sans le modifier.
        DB::connection()->getPdo()->sqliteCreateFunction('HOUR', function ($datetime) {
            return $datetime ? (int) date('H', strtotime($datetime)) : 0;
        });
    }

    private function makeRestaurant(User $owner): Restaurant
    {
        $restaurant = new Restaurant([
            'user_id' => $owner->id,
            'name' => 'Resto ' . uniqid(),
            'email' => uniqid() . '@restos.test',
            'password' => bcrypt('password'),
            'services' => 'delivery',
            'city' => 'Brazzaville',
            'address' => 'Centre-ville',
            'phone' => '06' . random_int(10000000, 99999999),
            'account_name' => 'Compte test',
            'account_number' => '0000000000',
        ]);
        $restaurant->delivery_charges = 0;
        $restaurant->tax = 0;
        $restaurant->admin_commission = 0;
        $restaurant->save();

        return $restaurant;
    }

    private function makeOrder(Restaurant $restaurant, User $client, array $attrs = []): void
    {
        DB::table('orders')->insert(array_merge([
            'user_id' => $client->id,
            'restaurant_id' => $restaurant->id,
            'driver_id' => null,
            'order_no' => 'ORD-' . uniqid(),
            'total_items' => 1,
            'offer_discount' => 0,
            'tax' => 0,
            'delivery_charges' => 0,
            'sub_total' => 1000,
            'total' => 1000,
            'admin_commission' => 0,
            'restaurant_commission' => 0,
            'driver_tip' => 0,
            'status' => 'completed',
            'payment_method' => 'cash',
            'delivery_address' => 'Centre-ville',
            'd_lat' => '0',
            'd_lng' => '0',
            'ordered_time' => now(),
            'delivered_time' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ], $attrs));
    }

    public function test_user_without_restaurant_is_redirected_to_dashboard(): void
    {
        $user = User::factory()->create(['type' => 'restaurant']);

        $this->actingAs($user)
            ->get(route('restaurant.analytics'))
            ->assertRedirect(route('restaurant.dashboard'));
    }

    public function test_restaurant_owner_only_sees_its_own_orders_in_analytics(): void
    {
        $ownerA = User::factory()->create(['type' => 'restaurant']);
        $ownerB = User::factory()->create(['type' => 'restaurant']);
        $restaurantA = $this->makeRestaurant($ownerA);
        $restaurantB = $this->makeRestaurant($ownerB);

        $clientA = User::factory()->create(['type' => 'user']);
        $clientB = User::factory()->create(['type' => 'user']);

        $this->makeOrder($restaurantA, $clientA);
        $this->makeOrder($restaurantB, $clientB);
        $this->makeOrder($restaurantB, $clientB);

        $response = $this->actingAs($ownerA)->get(route('restaurant.analytics'));

        $response->assertOk();
        $response->assertViewHas('totalOrders', 1);
    }
}
