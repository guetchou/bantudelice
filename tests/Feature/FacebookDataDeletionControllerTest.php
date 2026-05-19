<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Cache;
use Mockery;
use Tests\TestCase;

class FacebookDataDeletionControllerTest extends TestCase
{
    public function test_facebook_data_deletion_requires_signed_request(): void
    {
        config([
            'external-services.social_auth.facebook.client_secret' => 'fb-secret',
        ]);

        $this->postJson(route('facebook.data_deletion'))
            ->assertStatus(400)
            ->assertJson([
                'error' => 'signed_request manquant',
            ]);
    }

    public function test_facebook_data_deletion_returns_confirmation_and_stores_status(): void
    {
        config([
            'external-services.social_auth.facebook.client_secret' => 'fb-secret',
        ]);

        Mockery::mock('alias:App\Services\UserDeletionService')
            ->shouldReceive('anonymizeByFacebookUserId')
            ->once()
            ->with('fb-user-123', Mockery::type('array'))
            ->andReturn(true);

        $signedRequest = $this->makeSignedRequest([
            'algorithm' => 'HMAC-SHA256',
            'user_id' => 'fb-user-123',
            'issued_at' => now()->timestamp,
        ], 'fb-secret');

        $response = $this->postJson(route('facebook.data_deletion'), [
            'signed_request' => $signedRequest,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['url', 'confirmation_code']);

        $confirmationCode = $response->json('confirmation_code');
        $this->assertNotSame('', $confirmationCode);
        $this->assertSame(
            route('facebook.data_deletion.status', ['code' => $confirmationCode]),
            $response->json('url')
        );

        $cached = Cache::get("fb_data_deletion:{$confirmationCode}");

        $this->assertSame('processed', $cached['status'] ?? null);
        $this->assertSame('fb-user-123', $cached['facebook_user_id'] ?? null);
    }

    protected function makeSignedRequest(array $payload, string $secret): string
    {
        $encodedPayload = $this->base64UrlEncode(json_encode($payload, JSON_UNESCAPED_SLASHES));
        $signature = hash_hmac('sha256', $encodedPayload, $secret, true);
        $encodedSignature = $this->base64UrlEncode($signature);

        return $encodedSignature . '.' . $encodedPayload;
    }

    protected function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }
}
