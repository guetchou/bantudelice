<?php

namespace Tests\Feature;

use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentDashboardPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_payment_dashboard_keeps_incoming_payments_scope_and_french_labels(): void
    {
        $admin = User::factory()->create([
            'type' => 'admin',
            'phone' => '0600050001',
        ]);
        $this->grantAdminWorkspace($admin);

        $response = $this->actingAs($admin)->get(route('admin.payments.dashboard'));

        $response->assertOk();
        $response->assertSee('Cockpit Paiements');
        $response->assertSee('Anomalies paiement');
        $response->assertSee('Initialisation + attente + traitement');
        $response->assertSee('Initialisation');
        $response->assertSee('En attente');
        $response->assertSee('Traitement');
        $response->assertSee('Payé');
        $response->assertSee('Échoué');
    }
}
