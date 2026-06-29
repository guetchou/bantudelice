<?php

namespace Tests\Feature;

use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentDashboardPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_payment_dashboard_is_a_decision_first_operations_center(): void
    {
        $admin = User::factory()->create([
            'type' => 'admin',
            'phone' => '0600050001',
        ]);
        $this->grantAdminWorkspace($admin);

        $response = $this->actingAs($admin)->get(route('admin.payments.dashboard'));

        $response->assertOk();
        $response->assertSee('Centre d’opérations de paiement');
        $response->assertSee('Encaissement confirmé');
        $response->assertSee('File de rapprochement');
        $response->assertSee('Alertes opérationnelles');
        $response->assertSee('Journal des transactions');
        $response->assertSee('Initialisation');
        $response->assertSee('En attente');
        $response->assertSee('Traitement');
        $response->assertSee('Payé');
        $response->assertSee('Échoué');
        $response->assertDontSee('Cockpit Paiements');
        $response->assertDontSee('Anomalies paiement');
    }
}
