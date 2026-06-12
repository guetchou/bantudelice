<?php

namespace Tests\Feature;

use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginRedirectTest extends TestCase
{
    use RefreshDatabase;

    public function test_driver_login_redirects_to_driver_deliveries(): void
    {
        $password = 'secret-pass';
        $driver = User::factory()->create([
            'email' => 'driver.redirect@example.com',
            'password' => bcrypt($password),
            'type' => 'driver',
        ]);

        $this->post('/login', [
            'identifier' => $driver->email,
            'password'   => $password,
        ])->assertRedirect(route('driver.deliveries'));

        $this->assertAuthenticatedAs($driver);
    }
}
