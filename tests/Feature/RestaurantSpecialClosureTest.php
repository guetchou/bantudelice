<?php

namespace Tests\Feature;

use App\Restaurant;
use App\Services\RestaurantStatusService;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RestaurantSpecialClosureTest extends TestCase
{
    use RefreshDatabase;

    public function test_restaurant_can_store_special_closure(): void
    {
        [$user, $restaurant] = $this->createRestaurantAccount();

        $response = $this->actingAs($user)->post(route('restaurant.special_closures.store'), [
            'label' => 'Férié',
            'starts_on' => now()->toDateString(),
            'ends_on' => now()->addDay()->toDateString(),
            'notes' => 'Test fermeture',
        ]);

        $response->assertRedirect(route('working_hour.index'));
        $response->assertSessionHas('alert.message', 'Fermeture spéciale ajoutée avec succès');

        $this->assertDatabaseHas('restaurant_special_closures', [
            'restaurant_id' => $restaurant->id,
            'label' => 'Férié',
            'notes' => 'Test fermeture',
        ]);
    }

    public function test_special_closure_forces_restaurant_status_to_closed(): void
    {
        [, $restaurant] = $this->createRestaurantAccount();

        DB::table('working_hours')->insert([
            'restaurant_id' => $restaurant->id,
            'Day' => strtolower(now()->englishDayOfWeek),
            'opening_time' => '08:00:00',
            'closing_time' => '23:00:00',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('restaurant_special_closures')->insert([
            'restaurant_id' => $restaurant->id,
            'label' => 'Travaux',
            'starts_on' => now()->toDateString(),
            'ends_on' => now()->toDateString(),
            'notes' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $status = RestaurantStatusService::getStatus($restaurant->fresh());

        $this->assertFalse($status['is_open']);
        $this->assertSame('Fermé : Travaux', $status['message']);
    }

    protected function createRestaurantAccount(): array
    {
        $user = User::factory()->create([
            'type' => 'restaurant',
            'phone' => '0660001000',
        ]);

        $restaurantId = DB::table('restaurants')->insertGetId([
            'user_id' => $user->id,
            'name' => 'Restaurant fermeture test',
            'user_name' => 'restaurant-fermeture-test',
            'email' => 'restaurant-fermeture-test@example.com',
            'password' => bcrypt('secret'),
            'city' => 'Brazzaville',
            'address' => 'Adresse test',
            'phone' => '0660001001',
            'description' => 'Test',
            'services' => 'both',
            'service_charges' => 0,
            'delivery_charges' => 500,
            'tax' => 0,
            'admin_commission' => 15,
            'approved' => 1,
            'featured' => 0,
            'account_name' => 'Restaurant fermeture test',
            'account_number' => 'REST-CLOSURE-001',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [$user, Restaurant::findOrFail($restaurantId)];
    }
}
