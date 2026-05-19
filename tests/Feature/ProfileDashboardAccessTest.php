<?php

namespace Tests\Feature;

use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileDashboardAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_profile_displays_administration_link(): void
    {
        $user = User::factory()->create(['type' => 'admin']);

        $this->actingAs($user)
            ->get('/profile')
            ->assertOk()
            ->assertSee(route('admin.dashboard'), false)
            ->assertSee("Acc\u{00E9}der \u{00E0} l'administration");
    }

    public function test_restaurant_profile_displays_restaurant_dashboard_link(): void
    {
        $user = User::factory()->create(['type' => 'restaurant']);

        $this->actingAs($user)
            ->get('/profile')
            ->assertOk()
            ->assertSee(route('restaurant.dashboard'), false)
            ->assertSeeText("Acc\u{00E9}der \u{00E0} mon espace restaurant");
    }

    public function test_driver_profile_displays_driver_dashboard_link(): void
    {
        $user = User::factory()->create(['type' => 'driver']);

        $this->actingAs($user)
            ->get('/profile')
            ->assertOk()
            ->assertSee(route('driver.deliveries'), false)
            ->assertSeeText("Acc\u{00E9}der \u{00E0} mon espace livreur");
    }

    public function test_customer_profile_does_not_display_dashboard_link(): void
    {
        $user = User::factory()->create(['type' => 'user']);

        $this->actingAs($user)
            ->get('/profile')
            ->assertOk()
            ->assertDontSeeText("Acc\u{00E9}der \u{00E0} mon espace livreur")
            ->assertDontSeeText("Acc\u{00E9}der \u{00E0} mon espace restaurant")
            ->assertDontSee("Acc\u{00E9}der \u{00E0} l'administration");
    }
}
