<?php

namespace Tests\Feature;

use App\Domain\Finance\Models\FinancialAccount;
use App\Domain\Finance\Models\FinancialMirrorEvent;
use App\Domain\Finance\Services\LedgerPostingService;
use App\Domain\Payment\Events\PaymentConfirmed;
use App\Payment;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class PaymentCollectionMirrorTest extends TestCase
{
    use RefreshDatabase;

    public function test_mirror_is_disabled_by_default(): void
    {
        config()->set('financial-mirror.collections_enabled', false);
        $payment = $this->payment(['provider_reference' => 'MIRROR-OFF-1']);

        event(new PaymentConfirmed($payment));

        $this->assertDatabaseCount('financial_mirror_events', 0);
        $this->assertDatabaseCount('financial_posting_batches', 0);
    }

    public function test_confirmed_payment_is_recorded_as_provider_cash_and_unallocated_customer_funds(): void
    {
        config()->set('financial-mirror.collections_enabled', true);
        $payment = $this->payment([
            'provider' => 'momo',
            'provider_reference' => 'MIRROR-PAID-1',
            'amount' => 4700,
        ]);

        event(new PaymentConfirmed($payment));

        $event = FinancialMirrorEvent::query()->firstOrFail();
        $this->assertSame('posted', $event->status);
        $this->assertSame(1, $event->attempts);
        $this->assertNotNull($event->posting_batch_uuid);
        $this->assertDatabaseCount('financial_posting_batches', 1);
        $this->assertDatabaseCount('financial_postings', 2);

        $postings = app(LedgerPostingService::class);
        $provider = FinancialAccount::where('code', 'ASSET:PAYMENT_PROVIDER:MTN_MOMO:COLLECTIONS')->firstOrFail();
        $clearing = FinancialAccount::where('code', 'LIABILITY:PAYMENT:CLEARING')->firstOrFail();

        $this->assertSame(4700, $postings->balance($provider));
        $this->assertSame(4700, $postings->balance($clearing));
        $this->assertDatabaseMissing('financial_accounts', ['owner_type' => 'restaurant']);
        $this->assertDatabaseMissing('financial_accounts', ['owner_type' => 'driver']);
    }

    public function test_mtn_provider_aliases_share_one_collection_account(): void
    {
        config()->set('financial-mirror.collections_enabled', true);

        event(new PaymentConfirmed($this->payment([
            'provider' => 'momo',
            'provider_reference' => 'MIRROR-ALIAS-1',
            'amount' => 1000,
        ])));
        event(new PaymentConfirmed($this->payment([
            'provider' => 'mtn_momo',
            'provider_reference' => 'MIRROR-ALIAS-2',
            'amount' => 1500,
        ])));
        event(new PaymentConfirmed($this->payment([
            'provider' => 'mtn',
            'provider_reference' => 'MIRROR-ALIAS-3',
            'amount' => 2000,
        ])));

        $this->assertSame(
            1,
            FinancialAccount::where('code', 'ASSET:PAYMENT_PROVIDER:MTN_MOMO:COLLECTIONS')->count()
        );
        $this->assertDatabaseCount('financial_posting_batches', 3);

        $account = FinancialAccount::where('code', 'ASSET:PAYMENT_PROVIDER:MTN_MOMO:COLLECTIONS')->firstOrFail();
        $this->assertSame(4500, app(LedgerPostingService::class)->balance($account));
    }

    public function test_duplicate_payment_event_reuses_the_same_financial_batch(): void
    {
        config()->set('financial-mirror.collections_enabled', true);
        $payment = $this->payment(['provider_reference' => 'MIRROR-DUP-1']);

        event(new PaymentConfirmed($payment));
        event(new PaymentConfirmed($payment->fresh()));

        $this->assertDatabaseCount('financial_mirror_events', 1);
        $this->assertDatabaseCount('financial_posting_batches', 1);
        $this->assertDatabaseCount('financial_postings', 2);
        $this->assertSame(1, FinancialMirrorEvent::query()->firstOrFail()->attempts);
    }

    public function test_mirror_failure_does_not_change_paid_payment_status(): void
    {
        config()->set('financial-mirror.collections_enabled', true);
        $payment = $this->payment([
            'provider' => null,
            'provider_reference' => 'MIRROR-FAIL-1',
        ]);

        event(new PaymentConfirmed($payment));

        $this->assertSame('PAID', $payment->fresh()->status);
        $this->assertSame('failed', FinancialMirrorEvent::query()->firstOrFail()->status);
        $this->assertDatabaseCount('financial_posting_batches', 0);
    }

    public function test_cash_payment_is_skipped_until_cash_collection_workflow_exists(): void
    {
        config()->set('financial-mirror.collections_enabled', true);
        $payment = $this->payment([
            'provider' => 'cash',
            'provider_reference' => null,
        ]);

        event(new PaymentConfirmed($payment));

        $this->assertSame('skipped', FinancialMirrorEvent::query()->firstOrFail()->status);
        $this->assertDatabaseCount('financial_posting_batches', 0);
    }

    private function payment(array $overrides = []): Payment
    {
        $user = User::factory()->create(['type' => 'user']);

        return Payment::create(array_replace([
            'user_id' => $user->id,
            'provider' => 'momo',
            'provider_reference' => 'MIRROR-' . uniqid(),
            'status' => 'PAID',
            'amount' => 2500,
            'currency' => 'XAF',
            'meta' => [],
        ], $overrides));
    }
}
