<?php

namespace Tests\Feature;

use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginMultiCanalTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(array $attrs = []): User
    {
        return User::factory()->create(array_merge([
            'password' => bcrypt('secret123'),
            'type'     => 'user',
        ], $attrs));
    }

    // ── Canal email ──────────────────────────────────────────────────────────

    public function test_login_with_email(): void
    {
        $user = $this->makeUser(['email' => 'test@bantudelice.cg']);

        $this->post('/login', [
            'identifier' => 'test@bantudelice.cg',
            'password'   => 'secret123',
        ])->assertRedirect();

        $this->assertAuthenticatedAs($user);
    }

    // ── Canal téléphone ──────────────────────────────────────────────────────

    public function test_login_with_phone_plus242(): void
    {
        $user = $this->makeUser(['phone' => '+242 06 500 00 01']);

        $this->post('/login', [
            'identifier' => '+242 06 500 00 01',
            'password'   => 'secret123',
        ])->assertRedirect();

        $this->assertAuthenticatedAs($user);
    }

    public function test_login_with_phone_local_format(): void
    {
        $user = $this->makeUser(['phone' => '06 500 00 01']);

        $this->post('/login', [
            'identifier' => '06 500 00 01',
            'password'   => 'secret123',
        ])->assertRedirect();

        $this->assertAuthenticatedAs($user);
    }

    // ── Canal username ───────────────────────────────────────────────────────

    public function test_login_with_username(): void
    {
        $user = $this->makeUser(['username' => 'jean_paul']);

        $this->post('/login', [
            'identifier' => 'jean_paul',
            'password'   => 'secret123',
        ])->assertRedirect();

        $this->assertAuthenticatedAs($user);
    }

    // ── Mauvais mot de passe ─────────────────────────────────────────────────

    public function test_wrong_password_fails(): void
    {
        $this->makeUser(['email' => 'fail@bantudelice.cg']);

        $response = $this->post('/login', [
            'identifier' => 'fail@bantudelice.cg',
            'password'   => 'mauvais',
        ]);
        $response->assertSessionHas('alert.type', 'danger');
        $this->assertGuest();
    }

    // ── Identifiant inexistant ───────────────────────────────────────────────

    public function test_unknown_identifier_fails(): void
    {
        $response = $this->post('/login', [
            'identifier' => 'inconnu@nowhere.cg',
            'password'   => 'secret123',
        ]);
        $response->assertSessionHas('alert.type', 'danger');
        $this->assertGuest();
    }

    // ── Champ vide ───────────────────────────────────────────────────────────

    public function test_empty_identifier_returns_validation_error(): void
    {
        $this->post('/login', [
            'identifier' => '',
            'password'   => 'secret123',
        ])->assertSessionHasErrors('identifier');

        $this->assertGuest();
    }
}
