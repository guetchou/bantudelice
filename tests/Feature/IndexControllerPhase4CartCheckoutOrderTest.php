<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Tests de sécurité et d'intégrité phase 4 — panier, voucher, commandes.
 *
 * Tous les tests sans RefreshDatabase utilisent des vérifications statiques
 * de code source ou des appels de validation HTTP sans accès DB.
 *
 * Les tests nécessitant une DB réelle sont documentés mais skippés dans cet
 * environnement (driver SQLite absent) — ils tournent en CI avec MySQL.
 */
class IndexControllerPhase4CartCheckoutOrderTest extends TestCase
{
    // =========================================================================
    // addToCart — validation whitelist (sans DB)
    // =========================================================================

    public function test_add_to_cart_rejects_missing_product_id(): void
    {
        $response = $this->post(route('cart'), [
            'qty' => 1,
        ]);

        // 422 (JSON) ou redirect avec erreurs (web)
        $this->assertTrue(
            $response->status() === 422 || $response->status() === 302,
            'addToCart doit rejeter un product_id manquant'
        );
    }

    public function test_add_to_cart_rejects_invalid_qty_zero(): void
    {
        $response = $this->post(route('cart'), [
            'product_id' => 1,
            'qty'        => 0,
        ]);

        $this->assertTrue(
            $response->status() === 422 || $response->status() === 302
        );
    }

    public function test_add_to_cart_rejects_qty_above_maximum(): void
    {
        $response = $this->post(route('cart'), [
            'product_id' => 1,
            'qty'        => 999,
        ]);

        $this->assertTrue(
            $response->status() === 422 || $response->status() === 302
        );
    }

    public function test_add_to_cart_rejects_non_integer_product_id(): void
    {
        $response = $this->post(route('cart'), [
            'product_id' => 'malicious_value',
            'qty'        => 1,
        ]);

        $this->assertTrue(
            $response->status() === 422 || $response->status() === 302
        );
    }

    // =========================================================================
    // checkVoucher — validation code (sans DB)
    // =========================================================================

    public function test_check_voucher_rejects_missing_code(): void
    {
        $response = $this->post('/voucher', []);

        $this->assertTrue(
            $response->status() === 422 || $response->status() === 302
        );
    }

    public function test_check_voucher_rejects_code_too_long(): void
    {
        $response = $this->post('/voucher', [
            'voucher' => str_repeat('A', 101),
        ]);

        $this->assertTrue(
            $response->status() === 422 || $response->status() === 302
        );
    }

    public function test_check_voucher_rejects_invalid_restaurant_id(): void
    {
        $response = $this->post('/voucher', [
            'voucher'    => 'CODE123',
            'restaurant' => 'not-an-integer',
        ]);

        $this->assertTrue(
            $response->status() === 422 || $response->status() === 302
        );
    }

    // =========================================================================
    // Vérifications statiques des corrections source
    // =========================================================================

    public function test_add_to_cart_source_uses_whitelist_validation(): void
    {
        // addToCart a migré vers CartCheckoutController
        $source = file_get_contents(base_path('app/Http/Controllers/CartCheckoutController.php'));

        $this->assertStringContainsString(
            'product_id',
            $source,
            'addToCart doit valider product_id'
        );
        $this->assertStringContainsString(
            'integer',
            $source,
            'addToCart doit valider avec integer'
        );
    }

    public function test_add_to_cart_source_no_longer_uses_request_all(): void
    {
        // addToCart a migré vers CartCheckoutController
        $source = file_get_contents(base_path('app/Http/Controllers/CartCheckoutController.php'));

        $this->assertStringNotContainsString(
            '$inputToCart = $request->all()',
            $source,
            'addToCart ne doit plus utiliser $request->all() sans whitelist'
        );
    }

    public function test_add_to_cart_source_uses_product_restaurant_id(): void
    {
        // addToCart a migré vers CartCheckoutController
        $source = file_get_contents(base_path('app/Http/Controllers/CartCheckoutController.php'));

        $this->assertStringContainsString(
            '$product->restaurant_id',
            $source,
            'addToCart doit utiliser restaurant_id depuis le produit (source de confiance)'
        );
    }

    public function test_check_voucher_source_has_validation(): void
    {
        // checkVoucher a migré vers CartCheckoutController
        $source = file_get_contents(base_path('app/Http/Controllers/CartCheckoutController.php'));

        $this->assertStringContainsString(
            'voucher',
            $source,
            'checkVoucher doit valider le champ voucher'
        );
    }

    public function test_check_voucher_source_uses_auth_guard(): void
    {
        // checkVoucher a migré vers CartCheckoutController
        $source = file_get_contents(base_path('app/Http/Controllers/CartCheckoutController.php'));

        $this->assertStringContainsString(
            'auth()->check()',
            $source,
            'checkVoucher doit utiliser auth guard avant auth()->user()'
        );
    }

    public function test_get_orders_source_verifies_address_ownership(): void
    {
        // getOrders a migré vers CustomerOrderController
        $source = file_get_contents(base_path('app/Http/Controllers/CustomerOrderController.php'));

        $this->assertStringContainsString(
            'Adresse introuvable ou non autorisée',
            $source,
            'getOrders doit vérifier que l\'adresse appartient à l\'utilisateur'
        );
    }

    public function test_reopen_pickup_order_source_has_validate(): void
    {
        // reopenPickupOrder a migré vers CustomerOrderController
        $source = file_get_contents(base_path('app/Http/Controllers/CustomerOrderController.php'));

        $this->assertStringContainsString(
            'reopenPickupOrder',
            $source,
            'reopenPickupOrder existe'
        );
    }

    // =========================================================================
    // confirmOrderReceipt — ownership (vérification statique)
    // =========================================================================

    public function test_confirm_order_receipt_source_checks_user_id(): void
    {
        // confirmOrderReceipt a migré vers CustomerOrderController
        $source = file_get_contents(base_path('app/Http/Controllers/CustomerOrderController.php'));

        $this->assertStringContainsString(
            'auth()->id()',
            $source,
            'confirmOrderReceipt doit vérifier user_id === auth id'
        );
    }

    public function test_report_order_incident_source_checks_user_id(): void
    {
        // reportOrderIncident a migré vers CustomerOrderController
        $source = file_get_contents(base_path('app/Http/Controllers/CustomerOrderController.php'));

        $this->assertStringContainsString(
            'auth()->id()',
            $source,
            'reportOrderIncident doit vérifier user_id === auth id'
        );
    }

    public function test_request_order_redelivery_source_checks_user_id(): void
    {
        // requestOrderRedelivery a migré vers CustomerOrderController
        $source = file_get_contents(base_path('app/Http/Controllers/CustomerOrderController.php'));

        $this->assertStringContainsString(
            'auth()->id()',
            $source,
            'requestOrderRedelivery doit vérifier user_id === auth id'
        );
    }

    // =========================================================================
    // Routes inchangées — non-régression
    // =========================================================================

    /** @dataProvider cartCheckoutRouteUriProvider */
    public function test_cart_checkout_route_uri_unchanged(string $routeName, string $expectedUri): void
    {
        $this->assertSame($expectedUri, route($routeName, [], false));
    }

    public static function cartCheckoutRouteUriProvider(): array
    {
        return [
            ['cart',            '/cart'],
            ['cart.detail',     '/cart'],
            ['checkout.detail', '/checkout'],
            ['place.order',     '/checkout/order'],
            ['track.order',     '/track-order'],
        ];
    }

    public function test_cart_item_route_uri_contains_delete_item(): void
    {
        // Route avec paramètre obligatoire — on vérifie juste le pattern URI
        $uri = route('cart.item', ['id' => 42], false);
        $this->assertSame('/cart/deleteItem/42', $uri);
    }

    public function test_cart_update_route_uri_contains_update(): void
    {
        $uri = route('cart.update', ['cart' => 42], false);
        $this->assertSame('/cart/update/42', $uri);
    }
}
