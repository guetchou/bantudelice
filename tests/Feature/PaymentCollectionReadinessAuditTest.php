<?php

namespace Tests\Feature;

use App\Domain\Finance\Models\FinancialMirrorEvent;
use App\Domain\Finance\Services\PaymentCollectionReadinessAuditService;
use App\Payment;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

final class PaymentCollectionReadinessAuditTest extends TestCase
{
    use RefreshDatabase;

    public function test_audit_command_is_registered(): void
    {
        $this->artisan('finance:audit-payment-collection-readiness')->assertExitCode(0);
    }

    public function test_clean_history_is_ready(): void
    {
        $direct = $this->payment([
            'provider' => 'momo',
            'provider_reference' => 'MTN-1',
            'amount' => 1000,
        ]);
        $this->payment([
            'provider' => 'momo',
            'provider_reference' => 'GEPAY-1',
            'amount' => 2000,
            'meta' => ['gepay' => ['reference' => 'GEPAY-INTERNAL-1']],
        ]);
        $this->payment([
            'provider' => 'cash',
            'provider_reference' => null,
            'amount' => 500,
        ]);
        $this->mirrorEvent($direct, 'posted');

        $result = app(PaymentCollectionReadinessAuditService::class)->audit();

        $this->assertSame(3, $result['summary']['paid_count']);
        $this->assertSame(2, $result['summary']['eligible_online_count']);
        $this->assertSame(1, $result['summary']['already_posted_count']);
        $this->assertSame(1, $result['summary']['unmirrored_eligible_count']);
        $this->assertSame(0, $result['summary']['blocked_payment_count']);
        $this->assertTrue($result['summary']['ready_for_activation']);
        $this->assertSame(1, $result['routes']['mtn_momo']['count']);
        $this->assertSame(1, $result['routes']['gepay_mtn']['count']);
        $this->assertSame(1, $result['routes']['cash']['count']);
    }

    private function payment(array $overrides = []): Payment
    {
        $user = User::factory()->create(['type' => 'user']);

        return Payment::create(array_replace([
            'user_id' => $user->id,
            'provider' => 'momo',
            'provider_reference' => 'READY-' . uniqid(),
            'status' => 'PAID',
            'amount' => 2500,
            'currency' => 'XAF',
            'meta' => [],
        ], $overrides));
    }

    private function mirrorEvent(Payment $payment, string $status): FinancialMirrorEvent
    {
        return FinancialMirrorEvent::create([
            'uuid' => (string) Str::uuid(),
            'event_key' => 'payment:' . $payment->id . ':collection-received:v1',
            'event_type' => 'payment_collection_received',
            'source_type' => 'payment',
            'source_id' => $payment->id,
            'status' => $status,
            'attempts' => 1,
            'payload' => ['payment_id' => $payment->id],
            'posting_batch_uuid' => $status === 'posted' ? (string) Str::uuid() : null,
            'processed_at' => $status === 'posted' ? now() : null,
        ]);
    }
}
