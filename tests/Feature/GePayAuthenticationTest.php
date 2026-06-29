<?php

namespace Tests\Feature;

use App\Domain\GePay\Models\GePayClient;
use App\Domain\GePay\Services\GePaySigner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class GePayAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_signed_client_can_read_its_profile(): void
    {
        $secret = 'test-secret';
        $client = GePayClient::create([
            'uuid' => (string) Str::uuid(),
            'name' => 'Test Client',
            'api_key' => 'gpk_test',
            'api_secret' => $secret,
            'capabilities' => ['collection'],
            'is_active' => true,
        ]);

        $timestamp = (string) now()->timestamp;
        $uri = '/api/gepay/v1/client';
        $nonce = Str::uuid()->toString();
        $signature = GePaySigner::sign($secret, $timestamp, 'GET', $uri, '', $nonce);

        $this->withHeaders([
            'X-GePay-Key' => $client->api_key,
            'X-GePay-Timestamp' => $timestamp,
            'X-GePay-Nonce' => $nonce,
            'X-GePay-Signature' => $signature,
        ])->get($uri)
            ->assertOk()
            ->assertJsonPath('client.uuid', $client->uuid);
    }

    public function test_invalid_signature_is_rejected(): void
    {
        GePayClient::create([
            'uuid' => (string) Str::uuid(),
            'name' => 'Test Client',
            'api_key' => 'gpk_test',
            'api_secret' => 'test-secret',
            'capabilities' => ['collection'],
            'is_active' => true,
        ]);

        $this->withHeaders([
            'X-GePay-Key' => 'gpk_test',
            'X-GePay-Timestamp' => (string) now()->timestamp,
            'X-GePay-Nonce' => Str::uuid()->toString(),
            'X-GePay-Signature' => str_repeat('0', 64),
        ])->getJson('/api/gepay/v1/client')->assertUnauthorized();
    }
}
