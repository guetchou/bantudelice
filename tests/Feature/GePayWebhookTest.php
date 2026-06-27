<?php

namespace Tests\Feature;

use App\Domain\GePay\Enums\TransactionStatus;
use App\Domain\GePay\Enums\TransactionType;
use App\Domain\GePay\Models\GePayClient;
use App\Domain\GePay\Models\GePayTransaction;
use App\Domain\GePay\Models\GePayWebhookEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

class GePayWebhookTest extends TestCase
{
    use RefreshDatabase;

    private GePayClient $client;
    private GePayTransaction $transaction;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = GePayClient::create([
            'uuid' => (string) Str::uuid(),
            'name' => 'Webhook Test',
            'api_key' => 'gpk_wh',
            'api_secret' => Str::random(64),
            'capabilities' => ['collection'],
            'is_active' => true,
        ]);

        $providerRef = (string) Str::uuid();
        $this->transaction = GePayTransaction::create([
            'uuid' => (string) Str::uuid(),
            'client_id' => $this->client->id,
            'type' => TransactionType::COLLECTION,
            'provider' => 'mtn_momo',
            'external_reference' => 'WH-ORDER-001',
            'provider_reference' => $providerRef,
            'idempotency_key' => 'wh-ikey-001',
            'request_hash' => hash('sha256', 'wh-001'),
            'amount' => 5000,
            'currency' => 'XAF',
            'phone' => '24206001111',
            'phone_masked' => '24•••••11',
            'status' => TransactionStatus::PENDING,
        ]);

        config([
            'gepay.providers.mtn_momo.enabled' => true,
            'gepay.providers.mtn_momo.environment' => 'production',
            'gepay.providers.mtn_momo.target_environment' => 'mtncongo',
            'gepay.providers.mtn_momo.base_url.production' => 'https://mtn.test',
            'gepay.providers.mtn_momo.collections' => [
                'subscription_key' => 'col-sub',
                'api_user' => 'col-user',
                'api_key' => 'col-key',
            ],
        ]);
    }

    public function test_valid_webhook_updates_transaction_via_mtn_recontrole(): void
    {
        Http::fake([
            'https://mtn.test/collection/token/' => Http::response(['access_token' => 'tok', 'expires_in' => 3600], 200),
            'https://mtn.test/collection/v1_0/requesttopay/*' => Http::response(['status' => 'SUCCESSFUL'], 200),
        ]);

        $payload = ['referenceId' => $this->transaction->provider_reference, 'status' => 'SUCCESSFUL'];

        $this->postJson('/api/gepay/v1/webhooks/mtn', $payload)
            ->assertOk()
            ->assertJsonPath('matched', true);

        $this->assertSame('successful', $this->transaction->fresh()->status->value);
    }

    public function test_duplicate_webhook_is_deduplicated(): void
    {
        Http::fake([
            'https://mtn.test/collection/token/' => Http::response(['access_token' => 'tok', 'expires_in' => 3600], 200),
            'https://mtn.test/collection/v1_0/requesttopay/*' => Http::response(['status' => 'SUCCESSFUL'], 200),
        ]);

        $payload = ['referenceId' => $this->transaction->provider_reference];
        $body = json_encode($payload) ?: '';

        $this->postJson('/api/gepay/v1/webhooks/mtn', $payload)->assertOk();

        GePayWebhookEvent::query()
            ->where('payload_hash', hash('sha256', $body))
            ->update(['processed_at' => now()]);

        $resp = $this->postJson('/api/gepay/v1/webhooks/mtn', $payload)->assertOk();
        $this->assertTrue($resp->json('duplicate'));
    }

    public function test_unknown_webhook_is_stored_for_investigation(): void
    {
        $payload = ['referenceId' => 'unknown-ref-' . Str::random(8)];

        $this->postJson('/api/gepay/v1/webhooks/mtn', $payload)
            ->assertStatus(202)
            ->assertJsonPath('matched', false);

        $this->assertDatabaseHas('gepay_webhook_events', ['status' => 'ignored']);
    }

    public function test_webhook_does_not_trust_callback_status_without_recontrole(): void
    {
        Http::fake([
            'https://mtn.test/collection/token/' => Http::response(['access_token' => 'tok', 'expires_in' => 3600], 200),
            'https://mtn.test/collection/v1_0/requesttopay/*' => Http::response(['status' => 'PENDING'], 200),
        ]);

        $payload = ['referenceId' => $this->transaction->provider_reference, 'status' => 'SUCCESSFUL'];

        $this->postJson('/api/gepay/v1/webhooks/mtn', $payload)->assertOk();

        $this->assertSame('pending', $this->transaction->fresh()->status->value);
    }

    public function test_terminal_status_is_not_reversed_by_webhook(): void
    {
        $this->transaction->forceFill(['status' => TransactionStatus::SUCCESSFUL, 'completed_at' => now()])->save();

        Http::fake([
            'https://mtn.test/collection/token/' => Http::response(['access_token' => 'tok', 'expires_in' => 3600], 200),
            'https://mtn.test/collection/v1_0/requesttopay/*' => Http::response(['status' => 'FAILED'], 200),
        ]);

        $payload = ['referenceId' => $this->transaction->provider_reference];
        $this->postJson('/api/gepay/v1/webhooks/mtn', $payload)->assertOk();

        $this->assertSame('successful', $this->transaction->fresh()->status->value);
    }
}
