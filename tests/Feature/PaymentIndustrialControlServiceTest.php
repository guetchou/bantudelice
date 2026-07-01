<?php

namespace Tests\Feature;

use App\Domain\Payment\PaymentOperatingModel;
use App\Services\PaymentIndustrialControlService;
use App\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PaymentIndustrialControlServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_industrial_read_model_separates_confirmed_unresolved_unallocated_and_duplicates(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 6, 29, 12, 0, 0));

        $user = User::factory()->create([
            'type' => 'user',
            'phone' => '0600080001',
        ]);

        DB::table('payments')->insert([
            $this->payment($user->id, 'momo', 'PAID', 5000, 'REF-DUPLICATE', now()->subMinutes(20)),
            $this->payment($user->id, 'momo', 'PENDING', 3000, 'REF-DUPLICATE', now()->subMinutes(5)),
            $this->payment($user->id, 'airtel_money', 'FAILED', 2000, 'REF-FAILED', now()->subMinutes(10)),
        ]);

        $result = app(PaymentIndustrialControlService::class)->build();

        $this->assertSame(5000, $result['financialPosition']['confirmed_collections']['amount']);
        $this->assertSame(1, $result['financialPosition']['confirmed_collections']['count']);
        $this->assertSame(3000, $result['financialPosition']['unresolved_collections']['amount']);
        $this->assertSame(5000, $result['financialPosition']['unallocated_collections']['amount']);
        $this->assertSame(1, $result['collectionsControl']['duplicate_reference_count']);
        $this->assertSame(8000, $result['collectionsControl']['duplicate_reference_amount']);

        $types = $result['industrialQueue']->pluck('control_type')->all();
        $this->assertContains('duplicate_reference', $types);
        $this->assertContains('unallocated_collection', $types);
        $this->assertContains('collection_exception', $types);
        $this->assertSame('danger', $result['industrialHealth']['tone']);
    }

    public function test_operating_model_does_not_offer_unsupported_reconciliation_actions(): void
    {
        $this->assertTrue(PaymentOperatingModel::canReconcileCollection('PAID'));
        $this->assertTrue(PaymentOperatingModel::canReconcileCollection('PENDING'));
        $this->assertFalse(PaymentOperatingModel::canReconcileCollection('UNKNOWN'));
        $this->assertFalse(PaymentOperatingModel::canReconcileCollection('REVERSED'));
        $this->assertSame('confirmed', PaymentOperatingModel::collectionFamily('SUCCESSFUL'));
        $this->assertSame('reserved', PaymentOperatingModel::withdrawalFamily('unknown'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function payment(
        int $userId,
        string $provider,
        string $status,
        int $amount,
        string $reference,
        Carbon $createdAt
    ): array {
        return [
            'user_id' => $userId,
            'order_id' => null,
            'provider' => $provider,
            'provider_reference' => $reference,
            'status' => $status,
            'amount' => $amount,
            'currency' => 'XAF',
            'meta' => json_encode(['phone' => '2420600080001']),
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ];
    }
}
