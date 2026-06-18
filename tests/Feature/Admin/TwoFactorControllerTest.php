<?php

namespace Tests\Feature\Admin;

use App\User;
use Tests\TestCase;
use PragmaRX\Google2FA\Google2FA;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TwoFactorControllerTest extends TestCase
{
    use RefreshDatabase;

    private function admin(array $attrs = []): User
    {
        return User::factory()->create(array_merge(['type' => 'admin'], $attrs));
    }

    public function test_non_admin_cannot_access_2fa_setup(): void
    {
        $user = User::factory()->create(['type' => 'user']);

        $this->actingAs($user)
            ->get(route('admin.2fa.setup'))
            ->assertStatus(302);
    }

    public function test_admin_can_view_2fa_setup_page(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->get(route('admin.2fa.setup'))
            ->assertOk();

        $this->assertNotNull(session('2fa_setup_secret'));
    }

    public function test_enabling_2fa_with_invalid_code_fails(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->get(route('admin.2fa.setup'));

        $this->actingAs($admin)
            ->post(route('admin.2fa.enable'), ['code' => '000000'])
            ->assertSessionHasErrors('code');

        $this->assertFalse((bool) $admin->refresh()->two_factor_enabled);
    }

    public function test_enabling_2fa_with_valid_code_succeeds(): void
    {
        $admin = $this->admin();
        $google2fa = new Google2FA();
        $secret = $google2fa->generateSecretKey();

        $this->withSession(['2fa_setup_secret' => $secret])
            ->actingAs($admin)
            ->post(route('admin.2fa.enable'), ['code' => $google2fa->getCurrentOtp($secret)])
            ->assertRedirect(route('admin.profile'));

        $admin->refresh();
        $this->assertTrue((bool) $admin->two_factor_enabled);
        $this->assertSame($secret, $admin->two_factor_secret);
    }

    public function test_admin_with_2fa_enabled_is_redirected_to_challenge_until_verified(): void
    {
        $google2fa = new Google2FA();
        $secret = $google2fa->generateSecretKey();
        $admin = $this->admin([
            'two_factor_enabled' => true,
            'two_factor_secret' => $secret,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.portal'))
            ->assertRedirect(route('admin.2fa.challenge'));
    }

    public function test_verify_challenge_with_valid_code_unlocks_session(): void
    {
        $google2fa = new Google2FA();
        $secret = $google2fa->generateSecretKey();
        $admin = $this->admin([
            'two_factor_enabled' => true,
            'two_factor_secret' => $secret,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.2fa.verify'), ['code' => $google2fa->getCurrentOtp($secret)])
            ->assertRedirect(route('admin.portal'));

        $this->assertTrue(session('admin_2fa_verified'));

        $this->actingAs($admin)
            ->get(route('admin.audit_trail'))
            ->assertOk();
    }

    public function test_verify_challenge_with_invalid_code_does_not_unlock_session(): void
    {
        $google2fa = new Google2FA();
        $secret = $google2fa->generateSecretKey();
        $admin = $this->admin([
            'two_factor_enabled' => true,
            'two_factor_secret' => $secret,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.2fa.verify'), ['code' => '000000'])
            ->assertSessionHasErrors('code');

        $this->assertNull(session('admin_2fa_verified'));
    }
}
