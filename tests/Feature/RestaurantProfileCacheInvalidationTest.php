<?php

namespace Tests\Feature;

use App\Restaurant;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RestaurantProfileCacheInvalidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_restaurant_profile_update_clears_home_restaurants_cache(): void
    {
        [$user, $restaurant] = $this->createRestaurantAccount();

        $cacheKey = 'restaurants_active_all_8_' . md5(json_encode([]));
        Cache::put($cacheKey, collect([
            (object) ['id' => $restaurant->id, 'name' => $restaurant->name, 'logo' => 'old-logo.webp'],
        ]), 60);

        $this->assertTrue(Cache::has($cacheKey));

        $this->actingAs($user)
            ->post(route('restaurant.profile.profile_update'), [
                'profile_section' => 'restaurant',
                'restaurant_name' => 'Chez Gaspard Premium',
            ])
            ->assertRedirect(route('restaurant.profile'))
            ->assertSessionHas('alert.message', 'Identité du restaurant mise à jour avec succès');

        $this->assertFalse(Cache::has($cacheKey));
    }

    protected function createRestaurantAccount(): array
    {
        $user = User::factory()->create([
            'type' => 'restaurant',
            'phone' => '0661002000',
        ]);

        $restaurantId = DB::table('restaurants')->insertGetId([
            'user_id' => $user->id,
            'name' => 'Chez Gaspard',
            'user_name' => 'chez-gaspard-test',
            'email' => 'chez-gaspard-test@example.com',
            'password' => bcrypt('secret'),
            'city' => 'Brazzaville',
            'address' => 'Adresse test',
            'phone' => '0661002001',
            'description' => 'Test',
            'services' => 'both',
            'service_charges' => 0,
            'delivery_charges' => 500,
            'tax' => 0,
            'admin_commission' => 15,
            'approved' => 1,
            'featured' => 1,
            'account_name' => 'Chez Gaspard',
            'account_number' => 'REST-CACHE-001',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [$user, Restaurant::findOrFail($restaurantId)];
    }
}
