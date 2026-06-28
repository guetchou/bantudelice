<?php

namespace Tests\Feature;

use Tests\TestCase;

class SocialAuthControllerTest extends TestCase
{
    public function test_google_redirect_stores_state_and_intended_destination(): void
    {
        config([
            'external-services.social_auth.google.enabled' => true,
            'external-services.social_auth.google.client_id' => 'client-id.apps.googleusercontent.com',
            'external-services.social_auth.google.client_secret' => 'secret',
            'external-services.social_auth.google.redirect' => '/auth/google/callback',
        ]);

        $response = $this
            ->withServerVariables([
                'HTTP_HOST' => 'bantudelice.cg',
                'HTTPS' => 'on',
            ])
            ->get('/auth/google?redirect=/checkout');

        $response->assertRedirect();

        $redirectUrl = $response->headers->get('Location');
        $this->assertNotNull($redirectUrl);
        $this->assertStringContainsString('https://accounts.google.com/o/oauth2/v2/auth', $redirectUrl);

        parse_str((string) parse_url($redirectUrl, PHP_URL_QUERY), $query);
        $this->assertSame(url('/auth/google/callback'), $query['redirect_uri'] ?? null);

        $state = session('social_auth.google.state');
        $this->assertIsString($state);
        $this->assertNotSame('', $state);
        $this->assertSame('/checkout', session('url.intended'));
        $this->assertSame($state, $query['state'] ?? null);
    }

    public function test_google_callback_rejects_invalid_state_before_token_exchange(): void
    {
        $response = $this
            ->withSession(['social_auth.google.state' => 'expected-state'])
            ->get('/auth/google/callback?code=dummy-code&state=wrong-state');

        $response
            ->assertRedirect(route('user.login'))
            ->assertSessionHas('alert.type', 'danger');
    }
}
