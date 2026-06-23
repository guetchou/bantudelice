<?php

namespace Tests\Feature;

use App\Services\PaymentDashboardService;
use App\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PaymentDashboardServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_payment_dashboard_keeps_success_turnover_pending_and_provider_breakdown_strict(): void
    {
        // Horloge figée à midi pour éviter que les now()->subMinutes() ci-dessous
        // ne retombent la veille si le test s'exécute dans les minutes suivant minuit
        // (le dashboard filtre "aujourd'hui" sur created_at >= startOfDay).
        Carbon::setTestNow(Carbon::create(2026, 1, 1, 12, 0, 0));

        $user = User::factory()->create([
            'type' => 'user',
            'phone' => '0600080001',
        ]);

        DB::table('payments')->insert([
            $this->paymentPayload($user->id, 'momo', 'PAID', 5000, ['phone' => '2420600080001'], now()->subMinutes(30)),
            $this->paymentPayload($user->id, 'airtel_money', 'FAILED', 2000, ['phone' => '2420600080002', 'failure_reason' => 'Rejected'], now()->subMinutes(20)),
            $this->paymentPayload($user->id, 'cash', 'PENDING', 3000, ['phone' => '2420600080003'], now()->subMinutes(5)),
            $this->paymentPayload($user->id, 'mtn_momo', 'AUTHORIZED', 4000, ['phone' => '2420600080004'], now()->subMinutes(3)),
            $this->paymentPayload($user->id, 'paypal', 'CANCELLED', 1500, ['phone' => '2420600080005'], now()->subMinutes(10)),
        ]);

        $dashboard = app(PaymentDashboardService::class)->build(12);

        $this->assertSame(5000, $dashboard['kpis']['turnover']);
        $this->assertSame(5, $dashboard['kpis']['transactions']);
        $this->assertSame(2, $dashboard['kpis']['pending']);
        $this->assertSame(20.0, $dashboard['kpis']['success_rate']);
        $this->assertSame(1, $dashboard['statusBreakdown']['paid']);
        $this->assertSame(1, $dashboard['statusBreakdown']['processing']);
        $this->assertSame(1, $dashboard['statusBreakdown']['pending']);
        $this->assertSame(1, $dashboard['statusBreakdown']['failed']);
        $this->assertSame(1, $dashboard['statusBreakdown']['cancelled']);
        $this->assertSame('MTN MoMo', $dashboard['providerBreakdown'][0]['provider']);
        $this->assertSame(9000, $dashboard['providerBreakdown'][0]['amount']);
        $this->assertSame('En attente', $dashboard['filterOptions']['statuses'][2]['label']);
        $this->assertSame('Traitement', $dashboard['filterOptions']['statuses'][3]['label']);
        $this->assertSame('Payé', $dashboard['filterOptions']['statuses'][4]['label']);
        $this->assertContains('Réussi', $dashboard['tablePayments']->pluck('status_label')->all());
        $this->assertContains('Échoué', $dashboard['tablePayments']->pluck('status_label')->all());
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function paymentPayload(
        int $userId,
        string $provider,
        string $status,
        int $amount,
        array $meta,
        $createdAt
    ): array {
        return [
            'user_id' => $userId,
            'order_id' => null,
            'provider' => $provider,
            'provider_reference' => strtoupper($provider) . '-' . $status . '-' . $amount,
            'status' => $status,
            'amount' => $amount,
            'currency' => 'XAF',
            'meta' => json_encode($meta),
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ];
    }
}
