<?php

namespace Tests\Feature;

use App\Domain\GePay\Models\GePayClient;
use App\Domain\GePay\Models\GePayLedgerEntry;
use App\Domain\GePay\Models\GePayMerchant;
use App\Domain\GePay\Models\GePayMerchantUser;
use App\Domain\GePay\Models\GePayOperationToken;
use App\Domain\GePay\Models\GePayPayoutDestination;
use App\Domain\GePay\Models\GePayPayoutRequest;
use App\Domain\GePay\Models\GePayWallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;
use Tests\TestCase;

class GePayPortalSchemaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    private function createMerchant(array $overrides = []): GePayMerchant
    {
        return GePayMerchant::create(array_merge([
            'ulid' => (string) Str::ulid(),
            'name' => 'Merchant Test',
            'slug' => 'merchant-' . Str::random(8),
            'country' => 'CG',
            'email' => Str::random(8) . '@test.cg',
            'status' => 'active',
        ], $overrides));
    }

    private function createWallet(GePayMerchant $merchant, array $overrides = []): GePayWallet
    {
        return GePayWallet::create(array_merge([
            'merchant_id' => $merchant->id,
            'currency' => 'XAF',
            'available' => 0,
            'pending' => 0,
            'reserved' => 0,
        ], $overrides));
    }

    // ── Merchants ─────────────────────────────────────────────────────────────

    public function test_merchant_can_be_created(): void
    {
        $merchant = $this->createMerchant();

        $this->assertDatabaseHas('gepay_merchants', ['slug' => $merchant->slug]);
    }

    public function test_merchant_slug_is_unique(): void
    {
        $this->expectException(\Illuminate\Database\QueryException::class);

        $slug = 'duplicate-slug';
        $this->createMerchant(['slug' => $slug]);
        $this->createMerchant(['slug' => $slug]);
    }

    public function test_merchant_portal_client_id_is_nullable(): void
    {
        $merchant = $this->createMerchant();

        $this->assertNull($merchant->portal_client_id);
        $this->assertDatabaseHas('gepay_merchants', ['id' => $merchant->id, 'portal_client_id' => null]);
    }

    // ── Merchant users ────────────────────────────────────────────────────────

    public function test_merchant_user_can_be_created(): void
    {
        $merchant = $this->createMerchant();
        $user = GePayMerchantUser::create([
            'merchant_id' => $merchant->id,
            'name' => 'Admin User',
            'email' => 'admin@gepay.test',
            'password' => bcrypt('secret'),
            'role' => 'admin',
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('gepay_merchant_users', ['email' => 'admin@gepay.test']);
        $this->assertSame($merchant->id, $user->merchant->id);
    }

    // ── Wallets ───────────────────────────────────────────────────────────────

    public function test_wallet_unique_per_merchant_currency(): void
    {
        $this->expectException(\Illuminate\Database\QueryException::class);

        $merchant = $this->createMerchant();
        $this->createWallet($merchant);
        $this->createWallet($merchant); // duplicate
    }

    // ── Ledger entries ────────────────────────────────────────────────────────

    public function test_ledger_entry_can_be_created(): void
    {
        $merchant = $this->createMerchant();
        $wallet = $this->createWallet($merchant);

        $entry = GePayLedgerEntry::create([
            'merchant_id' => $merchant->id,
            'wallet_id' => $wallet->id,
            'type' => 'disbursement_debit',
            'amount' => 5000,
            'source_bucket' => 'available',
            'destination_bucket' => null,
            'idempotency_key' => 'disbursement:test-token-1',
        ]);

        $this->assertDatabaseHas('gepay_ledger_entries', ['idempotency_key' => 'disbursement:test-token-1']);
        $this->assertNotNull($entry->created_at);
    }

    public function test_ledger_entry_save_on_existing_throws(): void
    {
        $merchant = $this->createMerchant();
        $wallet = $this->createWallet($merchant);

        $entry = GePayLedgerEntry::create([
            'merchant_id' => $merchant->id,
            'wallet_id' => $wallet->id,
            'type' => 'collection_pending',
            'amount' => 1000,
            'idempotency_key' => 'collection:token-immutable',
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('immutable');

        $entry->note = 'tampered';
        $entry->save();
    }

    public function test_ledger_entry_delete_throws(): void
    {
        $merchant = $this->createMerchant();
        $wallet = $this->createWallet($merchant);

        $entry = GePayLedgerEntry::create([
            'merchant_id' => $merchant->id,
            'wallet_id' => $wallet->id,
            'type' => 'fee_debit',
            'amount' => 100,
            'idempotency_key' => 'fee:token-del',
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('immutable');

        $entry->delete();
    }

    public function test_ledger_idempotency_unique_constraint(): void
    {
        $this->expectException(\Illuminate\Database\QueryException::class);

        $merchant = $this->createMerchant();
        $wallet = $this->createWallet($merchant);

        $shared = [
            'merchant_id' => $merchant->id,
            'wallet_id' => $wallet->id,
            'type' => 'disbursement_debit',
            'amount' => 2000,
            'idempotency_key' => 'disbursement:same-key',
        ];

        GePayLedgerEntry::create($shared);
        GePayLedgerEntry::create($shared); // must violate UNIQUE
    }

    // ── Payout destinations ───────────────────────────────────────────────────

    public function test_payout_destination_destination_is_encrypted(): void
    {
        $merchant = $this->createMerchant();

        GePayPayoutDestination::create([
            'merchant_id' => $merchant->id,
            'label' => 'Compte MTN principal',
            'destination_type' => 'mtn_momo',
            'destination' => '242060000000',
            'verified' => false,
        ]);

        $raw = DB::table('gepay_payout_destinations')->value('destination');
        $this->assertNotEquals('242060000000', $raw, 'destination must be stored encrypted');
    }

    // ── Operation tokens ──────────────────────────────────────────────────────

    public function test_operation_token_validity_checks(): void
    {
        $merchant = $this->createMerchant();
        $user = GePayMerchantUser::create([
            'merchant_id' => $merchant->id,
            'name' => 'U',
            'email' => 'u@test.cg',
            'password' => bcrypt('x'),
            'role' => 'admin',
            'is_active' => true,
        ]);

        $token = GePayOperationToken::create([
            'token' => (string) Str::uuid(),
            'merchant_id' => $merchant->id,
            'user_id' => $user->id,
            'operation_type' => 'disbursement',
            'expires_at' => now()->addMinutes(15),
        ]);

        $this->assertTrue($token->isValid());
        $this->assertFalse($token->isExpired());
        $this->assertFalse($token->isUsed());
    }

    public function test_expired_token_is_invalid(): void
    {
        $merchant = $this->createMerchant();
        $user = GePayMerchantUser::create([
            'merchant_id' => $merchant->id,
            'name' => 'U',
            'email' => 'exp@test.cg',
            'password' => bcrypt('x'),
            'role' => 'admin',
            'is_active' => true,
        ]);

        $token = GePayOperationToken::create([
            'token' => (string) Str::uuid(),
            'merchant_id' => $merchant->id,
            'user_id' => $user->id,
            'operation_type' => 'collection',
            'expires_at' => now()->subMinute(),
        ]);

        $this->assertTrue($token->isExpired());
        $this->assertFalse($token->isValid());
    }

    // ── Payout requests ───────────────────────────────────────────────────────

    public function test_payout_request_idempotency_unique(): void
    {
        $this->expectException(\Illuminate\Database\QueryException::class);

        $merchant = $this->createMerchant();
        $wallet = $this->createWallet($merchant);
        $dest = GePayPayoutDestination::create([
            'merchant_id' => $merchant->id,
            'label' => 'Dest',
            'destination_type' => 'mtn_momo',
            'destination' => '242060000001',
            'verified' => true,
        ]);

        $shared = [
            'merchant_id' => $merchant->id,
            'wallet_id' => $wallet->id,
            'payout_destination_id' => $dest->id,
            'amount' => 10000,
            'currency' => 'XAF',
            'destination_snapshot' => 'snap',
            'status' => 'submitted',
            'idempotency_key' => 'payout:same-ikey',
        ];

        GePayPayoutRequest::create($shared);
        GePayPayoutRequest::create($shared); // must violate UNIQUE
    }

    // ── Circular FK (migration 9) ─────────────────────────────────────────────

    public function test_portal_client_id_can_be_set_after_client_is_linked(): void
    {
        $merchant = $this->createMerchant();

        $client = GePayClient::create([
            'uuid' => (string) Str::uuid(),
            'merchant_id' => $merchant->id,
            'name' => 'Portal Client',
            'api_key' => 'gpk_portal_' . Str::random(8),
            'api_secret' => Str::random(64),
            'capabilities' => ['disbursement', 'collection'],
            'is_active' => true,
        ]);

        $merchant->update(['portal_client_id' => $client->id]);
        $merchant->refresh();

        $this->assertSame($client->id, $merchant->portal_client_id);
        $this->assertSame($merchant->id, $client->fresh()->merchant_id);
    }
}
