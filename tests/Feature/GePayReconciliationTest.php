<?php

namespace Tests\Feature;

use App\Domain\GePay\Enums\TransactionStatus;
use App\Domain\GePay\Enums\TransactionType;
use App\Domain\GePay\Models\GePayClient;
use App\Domain\GePay\Models\GePayTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

class GePayReconciliationTest extends TestCase
{
    use RefreshDatabase;

    private GePayClient $client;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();

        $this->client = GePayClient::create([
            'uuid' => (string) Str::uuid(),
            'name' => 'Reconcile Test',
            'api_key' => 'gpk_rec',
            'api_secret' => Str::random(64),
            'capabilities' => ['collection'],
            'is_active' => true,
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

    private function makeTransaction(string $status, string $ref): GePayTransaction
    {
        return GePayTransaction::create([
            'uuid' => (string) Str::uuid(),
            'client_id' => $this->client->id,
            'type' => TransactionType::COLLECTION,
            'provider' => 'mtn_momo',
            'external_reference' => $ref,
            'provider_reference' => (string) Str::uuid(),
            'idempotency_key' => 'rk-'.$ref,
            'request_hash' => hash('sha256', $ref),
            'amount' => 5000,
            'currency' => 'XAF',
            'phone' => '24206001234',
            'phone_masked' => '24•••••34',
            'status' => $status,
        ]);
    }

    public function test_reconcile_marks_pending_as_successful_when_mtn_confirms(): void
    {
        $tx = $this->makeTransaction('pending', 'REC-001');

        Http::fake([
            'https://mtn.test/collection/token/' => Http::response(['access_token' => 'tok', 'expires_in' => 3600], 200),
            'https://mtn.test/collection/v1_0/requesttopay/*' => Http::response(['status' => 'SUCCESSFUL'], 200),
        ]);

        $this->artisan('gepay:reconcile --limit=10')->assertSuccessful();

        $this->assertSame('successful', $tx->fresh()->status->value);
        $this->assertNotNull($tx->fresh()->completed_at);
    }

    public function test_reconcile_marks_pending_as_failed_when_mtn_rejects(): void
    {
        $tx = $this->makeTransaction('pending', 'REC-002');

        Http::fake([
            'https://mtn.test/collection/token/' => Http::response(['access_token' => 'tok', 'expires_in' => 3600], 200),
            'https://mtn.test/collection/v1_0/requesttopay/*' => Http::response(['status' => 'FAILED', 'reason' => 'NOT_ENOUGH_FUNDS'], 200),
        ]);

        $this->artisan('gepay:reconcile --limit=10')->assertSuccessful();

        $this->assertSame('failed', $tx->fresh()->status->value);
    }

    public function test_reconcile_keeps_unknown_on_mtn_check_failure(): void
    {
        $tx = $this->makeTransaction('unknown', 'REC-003');

        Http::fake([
            'https://mtn.test/collection/token/' => Http::response(['access_token' => 'tok', 'expires_in' => 3600], 200),
            'https://mtn.test/collection/v1_0/requesttopay/*' => Http::response(null, 500),
        ]);

        $this->artisan('gepay:reconcile --limit=10')->assertSuccessful();

        $this->assertSame('unknown', $tx->fresh()->status->value);
    }

    public function test_reconcile_skips_terminal_transactions(): void
    {
        $tx = $this->makeTransaction('successful', 'REC-004');

        Http::fake([]);

        $this->artisan('gepay:reconcile --limit=10')->assertSuccessful();

        Http::assertNothingSent();
        $this->assertSame('successful', $tx->fresh()->status->value);
    }

    public function test_reconcile_does_not_alter_status_when_terminal_reached(): void
    {
        $tx = $this->makeTransaction('pending', 'REC-005');
        $tx->forceFill(['status' => TransactionStatus::SUCCESSFUL, 'completed_at' => now()])->save();

        Http::fake([
            'https://mtn.test/collection/token/' => Http::response(['access_token' => 'tok', 'expires_in' => 3600], 200),
            'https://mtn.test/collection/v1_0/requesttopay/*' => Http::response(['status' => 'FAILED'], 200),
        ]);

        $this->artisan('gepay:reconcile --limit=10')->assertSuccessful();

        $this->assertSame('successful', $tx->fresh()->status->value);
    }
}
