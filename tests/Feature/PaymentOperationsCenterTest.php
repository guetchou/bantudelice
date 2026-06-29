<?php

namespace Tests\Feature;

use App\Services\PaymentDashboardService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Blade;
use ReflectionMethod;
use Tests\TestCase;

class PaymentOperationsCenterTest extends TestCase
{
    public function test_payment_dashboard_template_compiles_without_legacy_noise(): void
    {
        $source = file_get_contents(resource_path('views/admin/payments/dashboard.blade.php'));

        $this->assertIsString($source);
        $this->assertStringContainsString('Centre d’opérations de paiement', $source);
        $this->assertStringContainsString('File de rapprochement', $source);
        $this->assertStringContainsString('Journal des transactions', $source);
        $this->assertStringNotContainsString('control_hub_nav', $source);
        $this->assertStringNotContainsString('$cards', $source);
        $this->assertStringNotContainsString('$tables', $source);
        $this->assertStringNotContainsString('new Chart(', $source);
        $this->assertStringNotContainsString('setInterval(', $source);

        $compiled = Blade::compileString($source);

        $this->assertStringContainsString('payops-kpis', $compiled);
        $this->assertStringContainsString('payops-reconcile', $compiled);
    }

    public function test_unknown_and_reversed_statuses_are_not_downgraded_to_pending(): void
    {
        $service = new PaymentDashboardService();
        $method = new ReflectionMethod($service, 'canonicalStatus');
        $method->setAccessible(true);

        $this->assertSame('unknown', $method->invoke($service, 'UNKNOWN'));
        $this->assertSame('unknown', $method->invoke($service, 'provider_specific_unmapped_status'));
        $this->assertSame('reversed', $method->invoke($service, 'REVERSED'));
        $this->assertSame('disputed', $method->invoke($service, 'CHARGEBACK'));
    }

    public function test_provider_share_is_calculated_against_total_confirmed_amount(): void
    {
        $service = new PaymentDashboardService();
        $method = new ReflectionMethod($service, 'providerBreakdown');
        $method->setAccessible(true);

        $payments = new Collection([
            (object) ['provider' => 'momo', 'status' => 'PAID', 'amount' => 750],
            (object) ['provider' => 'airtel', 'status' => 'SUCCESS', 'amount' => 250],
        ]);

        $result = $method->invoke($service, $payments)->keyBy('provider');

        $this->assertSame(75.0, $result['MTN MoMo']['share_percent']);
        $this->assertSame(25.0, $result['Airtel Money']['share_percent']);
    }

    public function test_dashboard_service_exposes_decision_first_structures(): void
    {
        $source = file_get_contents(app_path('Services/PaymentDashboardService.php'));

        $this->assertIsString($source);
        $this->assertStringContainsString("'workQueue'", $source);
        $this->assertStringContainsString("'exceptions'", $source);
        $this->assertStringContainsString("'health'", $source);
        $this->assertStringContainsString("default => 'unknown'", $source);
    }
}
