<?php

namespace Tests\Feature\Restaurant;

use App\User;
use App\Rating;
use App\Restaurant;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class RatingsControllerTest extends TestCase
{
    use RefreshDatabase;

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

    public function test_user_without_restaurant_is_redirected_to_dashboard(): void
    {
        $user = User::factory()->create(['type' => 'restaurant']);

        $this->actingAs($user)
            ->get(route('restaurant.ratings'))
            ->assertRedirect(route('restaurant.dashboard'));
    }

    public function test_restaurant_owner_only_sees_its_own_ratings(): void
    {
        $ownerA = User::factory()->create(['type' => 'restaurant']);
        $ownerB = User::factory()->create(['type' => 'restaurant']);
        $restaurantA = $this->makeRestaurant($ownerA);
        $restaurantB = $this->makeRestaurant($ownerB);

        $clientA = User::factory()->create(['type' => 'user']);
        $clientB = User::factory()->create(['type' => 'user']);

        Rating::create([
            'user_id' => $clientA->id,
            'restaurant_id' => $restaurantA->id,
            'rating' => 5,
            'reviews' => 'Excellent service restaurant A',
        ]);

        Rating::create([
            'user_id' => $clientB->id,
            'restaurant_id' => $restaurantB->id,
            'rating' => 1,
            'reviews' => 'Tres mauvais service restaurant B',
        ]);

        $response = $this->actingAs($ownerA)->get(route('restaurant.ratings'));

        $response->assertOk();
        $response->assertSee('Excellent service restaurant A');
        $response->assertDontSee('Tres mauvais service restaurant B');
    }
}
