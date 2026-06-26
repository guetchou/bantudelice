<?php

namespace Tests\Feature;

use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LoginDashboardRedirectTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_login_ignores_stale_profile_intended_url_and_opens_dashboard(): void
    {
        $user = User::factory()->create([
            'type' => 'user',
            'password' => Hash::make('secret-login'),
        ]);

        $response = $this->withSession(['url.intended' => '/profile'])->post('/login', [
            'identifier' => $user->email,
            'password' => 'secret-login',
        ]);

        $response->assertRedirect(route('user.dashboard', [], false));
    }

    public function test_customer_profile_lives_under_dashboard_routes(): void
    {
        $this->assertSame('/dashboard', route('user.dashboard', [], false));
        $this->assertSame('/dashboard/profile', route('user.dashboard.profile', [], false));
    }
}
