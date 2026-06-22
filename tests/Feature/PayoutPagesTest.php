<?php

namespace Tests\Feature;

use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PayoutPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_restaurant_payout_page_uses_reversement_scope_copy(): void
    {
        $admin = User::factory()->create(['type' => 'admin']);
        $this->grantAdminWorkspace($admin);

        $response = $this->actingAs($admin)->get(route('restaurant_payout'));

        $response->assertOk();
        $response->assertSee('Reversements restaurants');
        $response->assertSee('Demandes de reversement');
        $response->assertSee('Historique des reversements');
        $response->assertSee("lancer l'API MTN", false);
        $response->assertSee('reversements restaurants', false);
    }

    public function test_admin_driver_payout_page_uses_reversement_scope_copy(): void
    {
        $admin = User::factory()->create(['type' => 'admin']);
        $this->grantAdminWorkspace($admin);

        $response = $this->actingAs($admin)->get(route('driver_payout'));

        $response->assertOk();
        $response->assertSee('Reversements livreurs');
        $response->assertSee('Demandes de reversement');
        $response->assertSee('Historique des reversements');
        $response->assertSee("lancer l'API MTN", false);
        $response->assertSee('reversements livreurs', false);
    }
}
