<?php

namespace Tests\Feature;

use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentDashboardPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_payment_dashboard_exposes_the_industrial_business_controls(): void
    {
        $admin = User::factory()->create([
            'type' => 'admin',
            'phone' => '0600050001',
        ]);
        $this->grantAdminWorkspace($admin);

        $response = $this->actingAs($admin)->get(route('admin.payments.dashboard'));

        $response->assertOk();
        $response->assertSee('Centre de contrôle financier');
        $response->assertSee('File de contrôle financier');
        $response->assertSee('Position financière');
        $response->assertSee('Obligations partenaires');
        $response->assertSee('Couverture des contrôles métier');
        $response->assertSee('Règles de vérité financière');
        $response->assertSee('Journal des encaissements');
        $response->assertSee('Affectation paiement');
        $response->assertSee('Registre financier immuable');
        $response->assertDontSee('Cockpit Paiements');
        $response->assertDontSee('Centre d’opérations de paiement');
    }
}
