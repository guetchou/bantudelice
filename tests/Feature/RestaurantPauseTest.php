<?php

namespace Tests\Feature;

use App\Restaurant;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RestaurantPauseTest extends TestCase
{
    use RefreshDatabase;

    // ── T1.1 : pause/resume — logique DB ────────────────────────────────────

    public function test_restaurant_can_be_paused_and_reason_saved(): void
    {
        [, $restaurant] = $this->createRestaurantAccount();

        $restaurant->update([
            'is_paused'    => true,
            'pause_reason' => 'Coupure de courant',
        ]);

        $fresh = $restaurant->fresh();
        $this->assertTrue((bool) $fresh->is_paused);
        $this->assertEquals('Coupure de courant', $fresh->pause_reason);
    }

    public function test_restaurant_resume_clears_pause_fields(): void
    {
        [, $restaurant] = $this->createRestaurantAccount();

        $restaurant->update(['is_paused' => true, 'pause_reason' => 'Test']);
        $restaurant->update(['is_paused' => false, 'pause_reason' => null, 'paused_until' => null]);

        $fresh = $restaurant->fresh();
        $this->assertFalse((bool) $fresh->is_paused);
        $this->assertNull($fresh->pause_reason);
    }

    public function test_pause_with_expiry_stores_paused_until(): void
    {
        [, $restaurant] = $this->createRestaurantAccount();
        $until = now()->addHours(3);

        $restaurant->update([
            'is_paused'    => true,
            'paused_until' => $until,
        ]);

        $this->assertNotNull($restaurant->fresh()->paused_until);
    }

    public function test_restaurant_model_is_paused_field_exists(): void
    {
        [, $restaurant] = $this->createRestaurantAccount();
        $this->assertFalse((bool) $restaurant->is_paused);

        DB::table('restaurants')->where('id', $restaurant->id)->update(['is_paused' => 1]);
        $this->assertTrue((bool) $restaurant->fresh()->is_paused);
    }

    // ── Admin force-pause — via RestaurantPauseController ────────────────────

    public function test_admin_force_pause_updates_db(): void
    {
        $admin = User::factory()->create(['type' => 'admin']);
        $this->grantAdminWorkspace($admin);
        [, $restaurant] = $this->createRestaurantAccount();

        $this->withoutExceptionHandling()
             ->actingAs($admin, 'web')
             ->post(
                 route('admin.restaurants.force_pause', $restaurant->id),
                 ['reason' => 'Inspection sanitaire']
             )
             ->assertRedirect();

        $this->assertDatabaseHas('restaurants', [
            'id'        => $restaurant->id,
            'is_paused' => 1,
        ]);
    }

    public function test_admin_force_resume_clears_pause(): void
    {
        $admin = User::factory()->create(['type' => 'admin']);
        $this->grantAdminWorkspace($admin);
        [, $restaurant] = $this->createRestaurantAccount();

        DB::table('restaurants')->where('id', $restaurant->id)->update(['is_paused' => 1]);

        $this->withoutExceptionHandling()
             ->actingAs($admin, 'web')
             ->post(
                 route('admin.restaurants.force_resume', $restaurant->id)
             )
             ->assertRedirect();

        $this->assertDatabaseHas('restaurants', [
            'id'        => $restaurant->id,
            'is_paused' => 0,
        ]);
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    protected function createRestaurantAccount(): array
    {
        $user = User::factory()->create([
            'type'  => 'restaurant',
            'phone' => '0660002000',
        ]);

        $id = DB::table('restaurants')->insertGetId([
            'user_id'          => $user->id,
            'name'             => 'Restaurant Pause Test',
            'user_name'        => 'restaurant-pause-test',
            'email'            => 'pause-test@example.com',
            'password'         => bcrypt('secret'),
            'city'             => 'Brazzaville',
            'address'          => 'Adresse test',
            'phone'            => '0660002001',
            'description'      => 'Test',
            'services'         => 'both',
            'service_charges'  => 0,
            'delivery_charges' => 500,
            'tax'              => 0,
            'admin_commission' => 15,
            'approved'         => 1,
            'featured'         => 0,
            'account_name'     => 'Restaurant Pause Test',
            'account_number'   => 'REST-PAUSE-001',
            'is_paused'        => 0,
            'created_at'       => now(),
            'updated_at'       => now(),
        ]);

        return [$user, Restaurant::findOrFail($id)];
    }
}
