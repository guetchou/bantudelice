<?php

namespace Tests\Feature;

use App\Domain\Payment\Enums\PaymentAllocationStatus;
use App\Domain\Payment\Enums\PaymentStatus;
use App\Domain\Payment\Services\PaymentAllocationService;
use App\Payment;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LogicException;
use Tests\TestCase;

class PaymentAllocationServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_confirmed_payment_can_be_allocated_once_without_overallocation(): void
    {
        $payer = User::factory()->create();
        $target = User::factory()->create();
        $payment = Payment::query()->create([
            'user_id' => $payer->id,
            'provider' => 'mtn_momo',
            'provider_reference' => 'MTN-ALLOC-001',
            'idempotency_key' => 'payment-alloc-001',
            'status' => 'PAID',
            'canonical_status' => PaymentStatus::SUCCESSFUL->value,
            'amount' => 10000,
            'currency' => 'XAF',
        ]);

        $service = app(PaymentAllocationService::class);

        $allocation = $service->allocate(
            $payment,
            $target,
            7000,
            'allocation:MTN-ALLOC-001:target-1'
        );
        $sameAllocation = $service->allocate(
            $payment,
            $target,
            7000,
            'allocation:MTN-ALLOC-001:target-1'
        );

        $this->assertSame($allocation->id, $sameAllocation->id);
        $this->assertSame(PaymentAllocationStatus::ACTIVE, $allocation->status);
        $this->assertSame(3000, $service->unallocatedAmount($payment));
        $this->assertDatabaseCount('payment_allocations', 1);

        $this->expectException(LogicException::class);

        $service->allocate(
            $payment,
            $target,
            4000,
            'allocation:MTN-ALLOC-001:target-2'
        );
    }

    public function test_pending_payment_cannot_be_allocated(): void
    {
        $payer = User::factory()->create();
        $target = User::factory()->create();
        $payment = Payment::query()->create([
            'user_id' => $payer->id,
            'provider' => 'airtel_money',
            'provider_reference' => 'AIRTEL-ALLOC-001',
            'idempotency_key' => 'payment-alloc-pending-001',
            'status' => 'PENDING',
            'canonical_status' => PaymentStatus::PENDING->value,
            'amount' => 5000,
            'currency' => 'XAF',
        ]);

        $this->expectException(LogicException::class);

        app(PaymentAllocationService::class)->allocate(
            $payment,
            $target,
            5000,
            'allocation:AIRTEL-ALLOC-001:target-1'
        );
    }

    public function test_reversal_restores_unallocated_amount_without_deleting_history(): void
    {
        $payer = User::factory()->create();
        $target = User::factory()->create();
        $payment = Payment::query()->create([
            'user_id' => $payer->id,
            'provider' => 'mtn_momo',
            'provider_reference' => 'MTN-ALLOC-REV-001',
            'idempotency_key' => 'payment-alloc-rev-001',
            'status' => 'PAID',
            'canonical_status' => PaymentStatus::SUCCESSFUL->value,
            'amount' => 8000,
            'currency' => 'XAF',
        ]);

        $service = app(PaymentAllocationService::class);
        $allocation = $service->allocate(
            $payment,
            $target,
            8000,
            'allocation:MTN-ALLOC-REV-001:target-1'
        );

        $reversed = $service->reverse(
            $allocation,
            'Affectation à la mauvaise commande.'
        );

        $this->assertSame(PaymentAllocationStatus::REVERSED, $reversed->status);
        $this->assertNotNull($reversed->reversed_at);
        $this->assertSame(8000, $service->unallocatedAmount($payment));
        $this->assertDatabaseCount('payment_allocations', 1);
    }
}
