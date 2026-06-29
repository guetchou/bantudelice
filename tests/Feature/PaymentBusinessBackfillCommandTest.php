<?php

namespace Tests\Feature;

use App\Payment;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentBusinessBackfillCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_backfill_is_idempotent_and_opens_case_for_unallocated_payment(): void
    {
        $user = User::factory()->create([
            'type' => 'user',
            'phone' => '0600090101',
        ]);

        $allocated = Payment::create([
            'user_id' => $user->id,
            'transport_booking_id' => 901,
            'provider' => 'momo',
            'provider_reference' => 'BACKFILL-ALLOCATED',
            'idempotency_key' => 'backfill-allocated',
            'status' => 'PAID',
            'amount' => 6000,
            'currency' => 'XAF',
            'meta' => [],
        ]);
        $allocated->forceFill(['financial_state' => null])->saveQuietly();

        $unallocated = Payment::create([
            'user_id' => $user->id,
            'provider' => 'momo',
            'provider_reference' => 'BACKFILL-UNALLOCATED',
            'idempotency_key' => 'backfill-unallocated',
            'status' => 'PAID',
            'amount' => 2500,
            'currency' => 'XAF',
            'meta' => [],
        ]);
        $unallocated->forceFill(['financial_state' => null])->saveQuietly();

        $this->artisan('payments:backfill-business', ['--days' => 30, '--limit' => 100])
            ->assertSuccessful();
        $this->artisan('payments:backfill-business', ['--days' => 30, '--limit' => 100])
            ->assertSuccessful();

        $this->assertDatabaseCount('payment_allocations', 1);
        $this->assertDatabaseHas('payment_allocations', [
            'payment_id' => $allocated->id,
            'status' => 'allocated',
        ]);
        $this->assertDatabaseHas('payment_reconciliation_cases', [
            'payment_id' => $unallocated->id,
            'case_type' => 'unallocated_payment',
            'status' => 'open',
        ]);
        $this->assertSame(2, \DB::table('financial_ledger_entries')->where('payment_id', $allocated->id)->count());
        $this->assertSame(2, \DB::table('financial_ledger_entries')->where('payment_id', $unallocated->id)->count());
    }

    public function test_dry_run_does_not_write_financial_data(): void
    {
        $user = User::factory()->create([
            'type' => 'user',
            'phone' => '0600090102',
        ]);

        $payment = Payment::create([
            'user_id' => $user->id,
            'shipment_id' => 902,
            'provider' => 'momo',
            'provider_reference' => 'BACKFILL-DRY-RUN',
            'idempotency_key' => 'backfill-dry-run',
            'status' => 'PAID',
            'amount' => 3000,
            'currency' => 'XAF',
            'meta' => [],
        ]);
        $payment->forceFill(['financial_state' => null])->saveQuietly();

        $this->artisan('payments:backfill-business', ['--dry-run' => true])
            ->assertSuccessful();

        $this->assertDatabaseCount('payment_allocations', 0);
        $this->assertDatabaseCount('financial_ledger_entries', 0);
    }
}
