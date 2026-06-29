<?php

namespace Tests\Feature;

use App\Domain\Payment\Events\PaymentConfirmed;
use App\Domain\Payment\Listeners\RecordPaymentBusinessTruth;
use App\PartnerWithdrawal;
use App\Payment;
use App\Services\PaymentBusinessDashboardService;
use App\Services\PaymentBusinessService;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PaymentBusinessCoreTest extends TestCase
{
    use RefreshDatabase;

    public function test_confirmed_payment_is_allocated_and_posted_once(): void
    {
        $payment = $this->payment([
            'transport_booking_id' => 7001,
            'amount' => 12500,
        ]);

        $service = app(PaymentBusinessService::class);
        $first = $service->recordConfirmedPayment($payment, ['source' => 'test']);
        $second = $service->recordConfirmedPayment($payment->fresh(), ['source' => 'replay']);

        $this->assertTrue($first['release_target']);
        $this->assertTrue($second['release_target']);
        $this->assertSame('allocated', $first['allocation_status']);
        $this->assertDatabaseCount('payment_allocations', 1);
        $this->assertDatabaseHas('payment_allocations', [
            'payment_id' => $payment->id,
            'allocatable_type' => 'transport_booking',
            'allocatable_id' => 7001,
            'status' => 'allocated',
        ]);
        $this->assertSame(2, DB::table('financial_ledger_entries')->where('payment_id', $payment->id)->count());
        $this->assertDatabaseHas('financial_ledger_entries', [
            'entry_key' => 'payment:' . $payment->id . ':provider-clearing:debit',
            'account_code' => 'provider_clearing',
            'direction' => 'debit',
        ]);
        $this->assertDatabaseHas('financial_ledger_entries', [
            'entry_key' => 'payment:' . $payment->id . ':customer-funds:credit',
            'account_code' => 'customer_funds',
            'direction' => 'credit',
        ]);
    }

    public function test_confirmed_payment_without_target_is_held_and_creates_case(): void
    {
        $payment = $this->payment(['amount' => 8500]);

        $result = app(PaymentBusinessService::class)->recordConfirmedPayment($payment);

        $this->assertFalse($result['release_target']);
        $this->assertSame('unallocated', $result['allocation_status']);
        $this->assertDatabaseHas('payment_reconciliation_cases', [
            'payment_id' => $payment->id,
            'case_type' => 'unallocated_payment',
            'severity' => 'critical',
            'status' => 'open',
        ]);
        $this->assertDatabaseCount('payment_allocations', 0);
        $this->assertSame(2, DB::table('financial_ledger_entries')->where('payment_id', $payment->id)->count());
    }

    public function test_reversal_releases_allocation_and_posts_counter_entries(): void
    {
        $payment = $this->payment([
            'shipment_id' => 9001,
            'amount' => 4200,
        ]);
        $service = app(PaymentBusinessService::class);
        $service->recordConfirmedPayment($payment);

        $result = $service->reverseConfirmedPayment($payment->fresh(), 'Provider reversal', [
            'provider_status' => 'REVERSED',
        ]);

        $this->assertSame('REVERSED', $result['payment']->status);
        $this->assertDatabaseHas('payment_allocations', [
            'payment_id' => $payment->id,
            'status' => 'released',
        ]);
        $this->assertDatabaseHas('payment_reconciliation_cases', [
            'payment_id' => $payment->id,
            'case_type' => 'provider_reversed',
            'status' => 'open',
        ]);
        $this->assertSame(4, DB::table('financial_ledger_entries')->where('payment_id', $payment->id)->count());
        $this->assertDatabaseHas('financial_ledger_entries', [
            'entry_key' => 'payment:' . $payment->id . ':customer-funds:reversal:debit',
            'direction' => 'debit',
        ]);
    }

    public function test_withdrawal_reservation_is_not_released_while_unknown(): void
    {
        $withdrawal = $this->withdrawal(['status' => 'reserved', 'net_amount' => 5000]);

        $this->assertSame(2, DB::table('financial_ledger_entries')->where('withdrawal_id', $withdrawal->id)->count());

        $withdrawal->update(['status' => 'unknown']);
        $this->assertSame(2, DB::table('financial_ledger_entries')->where('withdrawal_id', $withdrawal->id)->count());

        $withdrawal->update(['status' => 'failed', 'failed_at' => now()]);
        $this->assertSame(4, DB::table('financial_ledger_entries')->where('withdrawal_id', $withdrawal->id)->count());
        $this->assertDatabaseHas('financial_ledger_entries', [
            'entry_key' => 'withdrawal:' . $withdrawal->id . ':available:credit:release',
            'account_code' => 'partner_available',
            'direction' => 'credit',
        ]);
    }

    public function test_paid_withdrawal_consumes_reserved_balance(): void
    {
        $withdrawal = $this->withdrawal(['status' => 'reserved', 'net_amount' => 7000]);
        $withdrawal->update(['status' => 'paid', 'paid_at' => now()]);

        $this->assertSame(3, DB::table('financial_ledger_entries')->where('withdrawal_id', $withdrawal->id)->count());
        $this->assertDatabaseHas('financial_ledger_entries', [
            'entry_key' => 'withdrawal:' . $withdrawal->id . ':reserved:debit:paid',
            'account_code' => 'partner_reserved',
            'direction' => 'debit',
        ]);
    }

    public function test_business_dashboard_separates_confirmed_allocated_and_unallocated_amounts(): void
    {
        $allocated = $this->payment([
            'transport_booking_id' => 6001,
            'amount' => 10000,
        ]);
        $unallocated = $this->payment(['amount' => 3500]);

        $service = app(PaymentBusinessService::class);
        $service->recordConfirmedPayment($allocated);
        $service->recordConfirmedPayment($unallocated);

        $dashboard = app(PaymentBusinessDashboardService::class)->build();
        $summary = $dashboard['businessSummary'];

        $this->assertSame(13500.0, $summary['confirmed_amount']);
        $this->assertSame(10000.0, $summary['allocated_amount']);
        $this->assertSame(3500.0, $summary['unallocated_amount']);
        $this->assertSame(1, $summary['open_cases']);
        $this->assertSame(1, $summary['critical_cases']);
        $this->assertSame(13500.0, $summary['provider_clearing']);
        $this->assertSame(13500.0, $summary['customer_funds']);
        $this->assertSame('danger', $dashboard['businessHealth']['tone']);
    }

    public function test_business_listener_stops_domain_workflows_when_target_is_missing(): void
    {
        $payment = $this->payment(['amount' => 3000]);

        $result = app(RecordPaymentBusinessTruth::class)->handle(new PaymentConfirmed($payment));

        $this->assertFalse($result);
        $payment->refresh();
        $this->assertTrue((bool) data_get($payment->meta, 'business_hold'));
        $this->assertSame('unallocated', data_get($payment->meta, 'business_hold_reason'));
    }

    public function test_business_listener_allows_domain_workflow_after_valid_allocation(): void
    {
        $payment = $this->payment([
            'shipment_id' => 4100,
            'amount' => 5500,
        ]);

        $result = app(RecordPaymentBusinessTruth::class)->handle(new PaymentConfirmed($payment));

        $this->assertNull($result);
        $this->assertDatabaseHas('payment_allocations', [
            'payment_id' => $payment->id,
            'allocatable_type' => 'shipment',
            'status' => 'allocated',
        ]);
    }

    private function payment(array $overrides = []): Payment
    {
        $user = User::factory()->create([
            'type' => 'user',
            'phone' => '0600' . random_int(100000, 999999),
        ]);

        return Payment::create(array_merge([
            'user_id' => $user->id,
            'order_id' => null,
            'shipment_id' => null,
            'transport_booking_id' => null,
            'provider' => 'momo',
            'provider_reference' => 'PAY-' . bin2hex(random_bytes(6)),
            'idempotency_key' => 'idem-' . bin2hex(random_bytes(8)),
            'status' => 'PAID',
            'amount' => 5000,
            'currency' => 'XAF',
            'meta' => [],
        ], $overrides));
    }

    private function withdrawal(array $overrides = []): PartnerWithdrawal
    {
        return PartnerWithdrawal::create(array_merge([
            'partner_type' => 'restaurant',
            'partner_id' => 1101,
            'operator' => 'mtn',
            'provider' => 'mtn_momo',
            'phone' => '242060000000',
            'requested_amount' => 5000,
            'fee_amount' => 0,
            'net_amount' => 5000,
            'currency' => 'XAF',
            'status' => 'reserved',
            'external_reference' => 'WD-' . bin2hex(random_bytes(6)),
            'idempotency_key' => 'wd-idem-' . bin2hex(random_bytes(8)),
            'source' => 'test',
            'initiated_at' => now(),
            'metadata' => [],
        ], $overrides));
    }
}
