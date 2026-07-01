<?php

namespace Tests\Feature;

use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentDashboardPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_payment_dashboard_exposes_business_truth_and_decision_queue(): void
    {
        $admin = User::factory()->create([
            'type' => 'admin',
            'phone' => '0600050001',
        ]);
        $this->grantAdminWorkspace($admin);

        $response = $this->actingAs($admin)->get(route('admin.payments.dashboard'));

        $response->assertOk();
        $response->assertSee('Centre financier des paiements');
        $response->assertSee('Encaissements confirmés');
        $response->assertSee('Affecté aux opérations');
        $response->assertSee('Non affecté ou bloqué');
        $response->assertSee('File de décision financière');
        $response->assertSee('Retraits réservés');
        $response->assertSee('Règles métier actives');
        $response->assertSee('Encaissement ≠ affectation');
        $response->assertSee('Inversion = contre-écriture');
        $response->assertSee('Journal des transactions');
        $response->assertDontSee('Cockpit Paiements');
        $response->assertDontSee('Anomalies paiement');
    }
}
