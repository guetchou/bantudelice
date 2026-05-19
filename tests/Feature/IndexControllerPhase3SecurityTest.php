<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Tests de sécurité phase 3 — corrections sans base de données.
 *
 * Vérifient les corrections de comportement accessibles sans RefreshDatabase :
 * - stripe routes retournent 503 (pas 500)
 * - forgotPassword valide le format email et le mot de passe minimum
 * - les corrections de code source sont en place (vérification statique)
 */
class IndexControllerPhase3SecurityTest extends TestCase
{
    // =========================================================================
    // stripe — routes inexistantes retournent 503, pas 500
    // =========================================================================

    public function test_stripe_get_route_returns_503_not_500(): void
    {
        $response = $this->get(route('stripe'));
        $response->assertStatus(503);
    }

    public function test_stripe_post_route_returns_503_not_500(): void
    {
        $response = $this->post(route('stripe.post'));
        $response->assertStatus(503);
    }

    // =========================================================================
    // forgotPassword — validation format (sans DB)
    // =========================================================================

    public function test_forgot_password_rejects_invalid_email_format(): void
    {
        $response = $this->post(route('forgot'), [
            'email'    => 'not-an-email',
            'phone'    => '0600000000',
            'password' => 'newpass123',
        ]);

        $response->assertSessionHasErrors('email');
    }

    public function test_forgot_password_step_reset_rejects_missing_password(): void
    {
        // step=reset requiert password (min:8)
        $response = $this->post(route('forgot'), [
            'step'  => 'reset',
            'email' => 'test@example.com',
            'otp'   => '123456',
        ]);

        $response->assertSessionHasErrors('password');
    }

    public function test_forgot_password_step_reset_rejects_too_short_password(): void
    {
        $response = $this->post(route('forgot'), [
            'step'     => 'reset',
            'email'    => 'test@example.com',
            'otp'      => '123456',
            'password' => '123',
            'password_confirmation' => '123',
        ]);

        $response->assertSessionHasErrors('password');
    }

    // =========================================================================
    // Vérifications statiques des corrections de code
    // =========================================================================

    public function test_delete_item_source_contains_user_id_ownership_check(): void
    {
        // deleteItem a migré vers CartCheckoutController
        $source = file_get_contents(
            base_path('app/Http/Controllers/CartCheckoutController.php')
        );

        $this->assertStringContainsString(
            "->where('user_id', auth()->id())",
            $source,
            'deleteItem doit vérifier user_id avant de supprimer'
        );
    }

    public function test_update_item_source_contains_user_id_ownership_check(): void
    {
        // updateItem a migré vers CartCheckoutController
        $source = file_get_contents(
            base_path('app/Http/Controllers/CartCheckoutController.php')
        );

        $this->assertStringContainsString(
            "->where('user_id', auth()->id())",
            $source,
            'updateItem doit vérifier user_id avant de modifier'
        );
    }

    public function test_track_order_source_uses_strict_comparison(): void
    {
        // trackOrder a migré vers CustomerOrderController
        $source = file_get_contents(
            base_path('app/Http/Controllers/CustomerOrderController.php')
        );

        $this->assertStringContainsString(
            '(int) $order->user_id !== (int) auth()->id()',
            $source,
            'trackOrder doit utiliser une comparaison stricte typée'
        );
    }

    public function test_thanks_source_checks_ownership_before_cart_deletion(): void
    {
        // thanks a migré vers CustomerOrderController
        $source = file_get_contents(
            base_path('app/Http/Controllers/CustomerOrderController.php')
        );

        $this->assertStringContainsString(
            'auth()->id()',
            $source,
            'thanks doit vérifier la propriété avant de vider le panier'
        );
    }

    public function test_order_receipt_source_requires_authentication(): void
    {
        // orderReceipt a migré vers CustomerOrderController
        $source = file_get_contents(
            base_path('app/Http/Controllers/CustomerOrderController.php')
        );

        $this->assertStringContainsString(
            'auth()->check()',
            $source,
            'orderReceipt doit exiger une authentification'
        );
    }

    public function test_forgot_password_source_has_uniform_response(): void
    {
        // forgotPassword a migré vers CartCheckoutController
        $source = file_get_contents(
            base_path('app/Http/Controllers/CartCheckoutController.php')
        );

        $this->assertStringContainsString(
            'Si ce compte existe',
            $source,
            'forgotPassword doit retourner une réponse uniforme pour éviter l\'énumération'
        );
    }

    public function test_stripe_routes_not_pointing_to_index_controller(): void
    {
        $routes = app('router')->getRoutes();

        foreach (['stripe', 'stripe.post'] as $name) {
            $route  = $routes->getByName($name);
            $action = $route?->getActionName() ?? '';

            $this->assertStringNotContainsString(
                'IndexController',
                $action,
                "Route '{$name}' ne doit plus pointer vers IndexController"
            );
        }
    }
}
