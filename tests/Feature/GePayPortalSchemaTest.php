<?php

namespace Tests\Feature;

use App\Domain\GePay\Models\GePayClient;
use App\Domain\GePay\Models\GePayLedgerEntry;
use App\Domain\GePay\Models\GePayMerchant;
use App\Domain\GePay\Models\GePayMerchantUser;
use App\Domain\GePay\Models\GePayOperationToken;
use App\Domain\GePay\Models\GePayPayoutDestination;
use App\Domain\GePay\Models\GePayPayoutRequest;
use App\Domain\GePay\Models\GePayTransaction;
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

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function createMerchant(array $overrides = []): GePayMerchant
    {
        return GePayMerchant::create(array_merge([
            'ulid'    => (string) Str::ulid(),
            'name'    => 'Merchant Test',
            'slug'    => 'merchant-' . Str::random(8),
            'country' => 'CG',
            'email'   => Str::random(8) . '@test.cg',
            'status'  => 'active',
        ], $overrides));
    }

    private function createClient(GePayMerchant $merchant, array $overrides = []): GePayClient
    {
        return GePayClient::create(array_merge([
            'uuid'         => (string) Str::uuid(),
            'merchant_id'  => $merchant->id,
            'name'         => 'Client Test',
            'api_key'      => 'gpk_' . Str::random(12),
            'api_secret'   => Str::random(64),
            'capabilities' => ['disbursement', 'collection'],
            'is_active'    => true,
        ], $overrides));
    }

    private function createWallet(GePayMerchant $merchant, array $overrides = []): GePayWallet
    {
        return GePayWallet::create(array_merge([
            'merchant_id' => $merchant->id,
            'currency'    => 'XAF',
            'available'   => 0,
            'pending'     => 0,
            'reserved'    => 0,
        ], $overrides));
    }

    private function createLedgerEntry(GePayMerchant $merchant, GePayWallet $wallet, array $overrides = []): GePayLedgerEntry
    {
        return GePayLedgerEntry::create(array_merge([
            'merchant_id'    => $merchant->id,
            'wallet_id'      => $wallet->id,
            'type'           => 'disbursement_debit',
            'amount'         => 5000,
            'source_bucket'  => 'available',
            'reference_type' => 'gepay_payout_requests',
            'reference_id'   => 1,
            'idempotency_key' => 'disbursement:' . Str::uuid(),
        ], $overrides));
    }

    private function createUser(GePayMerchant $merchant, array $overrides = []): GePayMerchantUser
    {
        return GePayMerchantUser::create(array_merge([
            'merchant_id' => $merchant->id,
            'name'        => 'Admin User',
            'email'       => Str::random(8) . '@gepay.test',
            'password'    => bcrypt('secret'),
            'role'        => 'admin',
            'is_active'   => true,
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

    public function test_merchant_portal_client_id_is_nullable_by_default(): void
    {
        $merchant = $this->createMerchant();

        $this->assertNull($merchant->portal_client_id);
    }

    // ── Merchant users ────────────────────────────────────────────────────────

    public function test_merchant_user_can_be_created(): void
    {
        $merchant = $this->createMerchant();
        $user     = $this->createUser($merchant);

        $this->assertDatabaseHas('gepay_merchant_users', ['email' => $user->email]);
        $this->assertSame($merchant->id, $user->merchant->id);
    }

    // ── Wallets ───────────────────────────────────────────────────────────────

    public function test_wallet_unique_per_merchant_currency(): void
    {
        $this->expectException(\Illuminate\Database\QueryException::class);

        $merchant = $this->createMerchant();
        $this->createWallet($merchant);
        $this->createWallet($merchant); // duplicate XAF
    }

    // ── FK RESTRICT on client delete ──────────────────────────────────────────

    public function test_deleting_client_with_transactions_is_refused(): void
    {
        $merchant = $this->createMerchant();
        $client   = $this->createClient($merchant);

        // Create a transaction linked to this client
        GePayTransaction::create([
            'uuid'              => (string) Str::uuid(),
            'client_id'         => $client->id,
            'merchant_id'       => $merchant->id,
            'type'              => 'collection',
            'provider'          => 'mtn_momo',
            'external_reference' => 'EXT-' . Str::random(8),
            'idempotency_key'   => 'coll:' . Str::uuid(),
            'request_hash'      => str_repeat('a', 64),
            'amount'            => 1000,
            'currency'          => 'XAF',
            'phone'             => '242060000000',
            'phone_masked'      => '242060****00',
            'status'            => 'pending',
        ]);

        $this->expectException(\Illuminate\Database\QueryException::class);
        $client->delete();
    }

    public function test_transaction_merchant_id_matches_client_merchant_id(): void
    {
        $merchant = $this->createMerchant();
        $client   = $this->createClient($merchant);

        $tx = GePayTransaction::create([
            'uuid'              => (string) Str::uuid(),
            'client_id'         => $client->id,
            'merchant_id'       => $merchant->id,
            'type'              => 'disbursement',
            'provider'          => 'mtn_momo',
            'external_reference' => 'EXT-' . Str::random(8),
            'idempotency_key'   => 'disb:' . Str::uuid(),
            'request_hash'      => str_repeat('b', 64),
            'amount'            => 5000,
            'currency'          => 'XAF',
            'phone'             => '242060000001',
            'phone_masked'      => '242060****01',
            'status'            => 'pending',
        ]);

        $this->assertSame($client->merchant_id, $tx->merchant_id);
        $this->assertSame($merchant->id, $tx->merchant()->value('id'));
    }

    // ── Ledger entries ────────────────────────────────────────────────────────

    public function test_ledger_entry_can_be_created_and_has_created_at(): void
    {
        $merchant = $this->createMerchant();
        $wallet   = $this->createWallet($merchant);
        $entry    = $this->createLedgerEntry($merchant, $wallet);

        $this->assertDatabaseHas('gepay_ledger_entries', ['id' => $entry->id]);
        $this->assertNotNull($entry->created_at);
    }

    public function test_ledger_entry_save_on_existing_throws(): void
    {
        $merchant = $this->createMerchant();
        $wallet   = $this->createWallet($merchant);
        $entry    = $this->createLedgerEntry($merchant, $wallet);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('immutable');

        $entry->note = 'tampered';
        $entry->save();
    }

    public function test_ledger_entry_delete_via_model_throws(): void
    {
        $merchant = $this->createMerchant();
        $wallet   = $this->createWallet($merchant);
        $entry    = $this->createLedgerEntry($merchant, $wallet);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('immutable');

        $entry->delete();
    }

    public function test_ledger_entry_update_via_query_builder_blocked(): void
    {
        $merchant = $this->createMerchant();
        $wallet   = $this->createWallet($merchant);
        $entry    = $this->createLedgerEntry($merchant, $wallet);

        $this->expectException(\Illuminate\Database\QueryException::class);

        // Trigger fires even via raw query builder
        DB::table('gepay_ledger_entries')
            ->where('id', $entry->id)
            ->update(['amount' => 99]);
    }

    public function test_ledger_entry_delete_via_query_builder_blocked(): void
    {
        $merchant = $this->createMerchant();
        $wallet   = $this->createWallet($merchant);
        $entry    = $this->createLedgerEntry($merchant, $wallet);

        $this->expectException(\Illuminate\Database\QueryException::class);

        DB::table('gepay_ledger_entries')->where('id', $entry->id)->delete();
    }

    public function test_ledger_amount_zero_is_rejected_by_model(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $merchant = $this->createMerchant();
        $wallet   = $this->createWallet($merchant);

        // Model must refuse amount <= 0 before reaching DB
        GePayLedgerEntry::create([
            'merchant_id'    => $merchant->id,
            'wallet_id'      => $wallet->id,
            'type'           => 'disbursement_debit',
            'amount'         => 0,
            'reference_type' => 'gepay_payout_requests',
            'reference_id'   => 1,
            'idempotency_key' => 'disb:zero',
        ]);
    }

    public function test_ledger_amount_negative_is_rejected_by_model(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $merchant = $this->createMerchant();
        $wallet   = $this->createWallet($merchant);

        GePayLedgerEntry::create([
            'merchant_id'    => $merchant->id,
            'wallet_id'      => $wallet->id,
            'type'           => 'disbursement_debit',
            'amount'         => -100,
            'reference_type' => 'gepay_payout_requests',
            'reference_id'   => 1,
            'idempotency_key' => 'disb:negative',
        ]);
    }

    public function test_ledger_reference_type_is_required(): void
    {
        $this->expectException(\Illuminate\Database\QueryException::class);

        $merchant = $this->createMerchant();
        $wallet   = $this->createWallet($merchant);

        DB::table('gepay_ledger_entries')->insert([
            'merchant_id'    => $merchant->id,
            'wallet_id'      => $wallet->id,
            'type'           => 'disbursement_debit',
            'amount'         => 1000,
            'reference_type' => null, // NOT NULL violation
            'reference_id'   => 1,
            'idempotency_key' => 'disb:no-ref',
            'created_at'     => now(),
        ]);
    }

    public function test_ledger_idempotency_unique_constraint(): void
    {
        $this->expectException(\Illuminate\Database\QueryException::class);

        $merchant = $this->createMerchant();
        $wallet   = $this->createWallet($merchant);
        $shared   = [
            'merchant_id'    => $merchant->id,
            'wallet_id'      => $wallet->id,
            'type'           => 'disbursement_debit',
            'amount'         => 2000,
            'reference_type' => 'gepay_payout_requests',
            'reference_id'   => 1,
            'idempotency_key' => 'disbursement:same-key',
        ];

        $this->createLedgerEntry($merchant, $wallet, ['idempotency_key' => 'disbursement:same-key']);
        $this->createLedgerEntry($merchant, $wallet, ['idempotency_key' => 'disbursement:same-key']);
    }

    // ── Payout destinations ───────────────────────────────────────────────────

    public function test_payout_destination_is_encrypted_at_rest(): void
    {
        $merchant = $this->createMerchant();
        GePayPayoutDestination::create([
            'merchant_id'      => $merchant->id,
            'label'            => 'Compte MTN principal',
            'destination_type' => 'mobile_mtn',
            'destination'      => '242060000000',
            'verified'         => false,
        ]);

        $raw = DB::table('gepay_payout_destinations')->value('destination');
        $this->assertNotEquals('242060000000', $raw, 'destination must be stored encrypted');
    }

    // ── Payout requests ───────────────────────────────────────────────────────

    public function test_payout_request_idempotency_unique(): void
    {
        $this->expectException(\Illuminate\Database\QueryException::class);

        $merchant = $this->createMerchant();
        $wallet   = $this->createWallet($merchant);
        $dest     = GePayPayoutDestination::create([
            'merchant_id'      => $merchant->id,
            'label'            => 'Dest',
            'destination_type' => 'mobile_mtn',
            'destination'      => '242060000001',
            'verified'         => true,
        ]);

        $shared = [
            'merchant_id'           => $merchant->id,
            'wallet_id'             => $wallet->id,
            'payout_destination_id' => $dest->id,
            'amount'                => 10000,
            'currency'              => 'XAF',
            'destination_snapshot'  => json_encode(['masked' => '2420****001', 'type' => 'mobile_mtn']),
            'status'                => 'submitted',
            'idempotency_key'       => 'payout:same-ikey',
        ];

        GePayPayoutRequest::create($shared);
        GePayPayoutRequest::create($shared);
    }

    public function test_payout_snapshot_is_encrypted_and_structured(): void
    {
        $merchant = $this->createMerchant();
        $wallet   = $this->createWallet($merchant);
        $dest     = GePayPayoutDestination::create([
            'merchant_id'      => $merchant->id,
            'label'            => 'Snap dest',
            'destination_type' => 'mobile_mtn',
            'destination'      => '242060000002',
            'verified'         => true,
        ]);

        $snapshot = json_encode([
            'masked'           => '2420****002',
            'destination_type' => 'mobile_mtn',
            'verified'         => true,
            'verified_at'      => '2026-07-01T00:00:00Z',
        ]);

        $pr = GePayPayoutRequest::create([
            'merchant_id'           => $merchant->id,
            'wallet_id'             => $wallet->id,
            'payout_destination_id' => $dest->id,
            'amount'                => 20000,
            'currency'              => 'XAF',
            'destination_snapshot'  => $snapshot,
            'status'                => 'draft',
            'idempotency_key'       => 'payout:snap-test',
        ]);

        $raw = DB::table('gepay_payout_requests')->where('id', $pr->id)->value('destination_snapshot');
        $this->assertNotEquals($snapshot, $raw, 'snapshot must be encrypted at rest');

        // Decrypted via model cast must match original
        $this->assertEquals($snapshot, $pr->fresh()->destination_snapshot);
    }

    // ── Operation tokens ──────────────────────────────────────────────────────

    public function test_token_first_use_is_valid(): void
    {
        $merchant = $this->createMerchant();
        $user     = $this->createUser($merchant);
        $hash     = hash('sha256', 'phone=242060000000&amount=5000');

        $token = GePayOperationToken::create([
            'token'          => (string) Str::uuid(),
            'merchant_id'    => $merchant->id,
            'user_id'        => $user->id,
            'operation_type' => 'disbursement',
            'request_hash'   => $hash,
            'expires_at'     => now()->addMinutes(15),
        ]);

        $this->assertTrue($token->isValid());
        $this->assertTrue($token->isValidForRequest($hash));
        $this->assertFalse($token->isReplay($hash));
    }

    public function test_token_replay_same_hash_is_allowed(): void
    {
        $merchant = $this->createMerchant();
        $user     = $this->createUser($merchant);
        $hash     = hash('sha256', 'phone=242060000000&amount=5000');

        $token = GePayOperationToken::create([
            'token'          => (string) Str::uuid(),
            'merchant_id'    => $merchant->id,
            'user_id'        => $user->id,
            'operation_type' => 'disbursement',
            'request_hash'   => $hash,
            'expires_at'     => now()->addMinutes(15),
            'used_at'        => now(),
        ]);

        // Used but not expired, same hash → replay allowed
        $this->assertFalse($token->isValid()); // not pristine
        $this->assertTrue($token->isValidForRequest($hash));
        $this->assertTrue($token->isReplay($hash));
    }

    public function test_token_replay_different_hash_is_rejected(): void
    {
        $merchant = $this->createMerchant();
        $user     = $this->createUser($merchant);
        $hash     = hash('sha256', 'phone=242060000000&amount=5000');
        $otherHash = hash('sha256', 'phone=242060000000&amount=9999');

        $token = GePayOperationToken::create([
            'token'          => (string) Str::uuid(),
            'merchant_id'    => $merchant->id,
            'user_id'        => $user->id,
            'operation_type' => 'disbursement',
            'request_hash'   => $hash,
            'expires_at'     => now()->addMinutes(15),
            'used_at'        => now(),
        ]);

        $this->assertFalse($token->isValidForRequest($otherHash));
        $this->assertFalse($token->isReplay($otherHash));
    }

    public function test_expired_token_is_invalid_even_with_correct_hash(): void
    {
        $merchant = $this->createMerchant();
        $user     = $this->createUser($merchant);
        $hash     = hash('sha256', 'phone=242060000000&amount=5000');

        $token = GePayOperationToken::create([
            'token'          => (string) Str::uuid(),
            'merchant_id'    => $merchant->id,
            'user_id'        => $user->id,
            'operation_type' => 'collection',
            'request_hash'   => $hash,
            'expires_at'     => now()->subMinute(),
        ]);

        $this->assertTrue($token->isExpired());
        $this->assertFalse($token->isValidForRequest($hash));
    }

    // ── Circular FK (migration 9) ─────────────────────────────────────────────

    public function test_portal_client_id_links_bidirectionally(): void
    {
        $merchant = $this->createMerchant();
        $client   = $this->createClient($merchant);

        $merchant->update(['portal_client_id' => $client->id]);
        $merchant->refresh();

        $this->assertSame($client->id, $merchant->portal_client_id);
        $this->assertSame($merchant->id, $client->fresh()->merchant_id);
    }
}
