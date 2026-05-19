<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Non-régression phase 2 de l'extraction IndexController.
 *
 * Vérifie pour chaque méthode extraite :
 * 1. La route pointe vers le bon contrôleur.
 * 2. L'URI est identique à l'avant.
 * 3. Le nom de route est conservé.
 */
class IndexControllerPhase2Test extends TestCase
{
    // =========================================================================
    // Routes → nouveaux contrôleurs (pas IndexController)
    // =========================================================================

    /** @dataProvider extractedRouteProvider */
    public function test_route_handled_by_correct_controller(string $routeName, string $expectedController): void
    {
        $route = app('router')->getRoutes()->getByName($routeName);
        $this->assertNotNull($route, "Route '{$routeName}' introuvable");

        $this->assertStringContainsString(
            $expectedController,
            $route->getActionName(),
            "Route '{$routeName}' doit être gérée par {$expectedController}"
        );
    }

    public static function extractedRouteProvider(): array
    {
        return [
            // AuthViewController
            'user.login'          => ['user.login',          'AuthViewController'],
            'user.signup'         => ['user.signup',         'AuthViewController'],
            'user.forgot'         => ['user.forgot',         'AuthViewController'],
            'forgot.password'     => ['forgot.password',     'AuthViewController'],
            'user.logout'         => ['user.logout',         'AuthViewController'],
            // GuidanceController
            'guidance.execution'  => ['guidance.execution',  'GuidanceController'],
            'guidance.tasks'      => ['guidance.execution.tasks.update', 'GuidanceController'],
            'guidance.reset'      => ['guidance.execution.reset',        'GuidanceController'],
            // PublicCatalogController
            'cart.count'          => ['cart.count',          'PublicCatalogController'],
            'restaurant.cuisine'  => ['restaurant.cuisine',  'PublicCatalogController'],
            'colis.index'         => ['colis.index',         'PublicCatalogController'],
            'colis.landing'       => ['colis.landing',       'PublicCatalogController'],
            'colis.create'        => ['colis.create',        'PublicCatalogController'],
            'colis.create.alias'  => ['colis.create.alias',  'PublicCatalogController'],
        ];
    }

    // =========================================================================
    // Routes non extraites restent dans IndexController
    // =========================================================================

    /** @dataProvider keptIndexControllerProvider */
    public function test_non_extracted_routes_still_in_index_controller(string $routeName): void
    {
        $route = app('router')->getRoutes()->getByName($routeName);
        $this->assertNotNull($route, "Route '{$routeName}' introuvable");

        $this->assertStringContainsString(
            'IndexController',
            $route->getActionName(),
            "Route '{$routeName}' doit rester dans IndexController"
        );
    }

    public static function keptIndexControllerProvider(): array
    {
        return [
            'home' => ['home'],
        ];
    }

    // =========================================================================
    // Routes extraites phase 3+ — vérification des nouveaux contrôleurs
    // =========================================================================

    /** @dataProvider laterExtractedRouteProvider */
    public function test_later_extracted_routes_handled_by_correct_controller(string $routeName, string $expectedController): void
    {
        $route = app('router')->getRoutes()->getByName($routeName);
        $this->assertNotNull($route, "Route '{$routeName}' introuvable");

        $this->assertStringContainsString(
            $expectedController,
            $route->getActionName(),
            "Route '{$routeName}' doit être gérée par {$expectedController}"
        );
    }

    public static function laterExtractedRouteProvider(): array
    {
        return [
            'cart.detail'     => ['cart.detail',     'CartCheckoutController'],
            'checkout.detail' => ['checkout.detail', 'CartCheckoutController'],
            'place.order'     => ['place.order',     'CustomerOrderController'],
            'track.order'     => ['track.order',     'CustomerOrderController'],
            'user.profile'    => ['user.profile',    'ProfileController'],
        ];
    }

    // =========================================================================
    // URIs inchangées — non-régression
    // =========================================================================

    /** @dataProvider uriProvider */
    public function test_uri_unchanged(string $routeName, string $expectedUri): void
    {
        $this->assertSame($expectedUri, route($routeName, [], false));
    }

    public static function uriProvider(): array
    {
        return [
            ['user.login',      '/user/login'],
            ['user.signup',     '/signup'],
            ['user.forgot',     '/user/forgot'],
            ['forgot.password', '/forgot-password'],
            ['user.logout',     '/user-logout'],
            ['guidance.execution', '/guidance/execution'],
            ['cart.count',      '/cart/count'],
            ['colis.index',     '/colis'],
            ['colis.landing',   '/livraison-colis'],
            ['colis.create',    '/colis/nouveau'],
            ['colis.create.alias', '/colis/create'],
        ];
    }

    // =========================================================================
    // AuthViewController — logique unitaire (sans DB ni vues complètes)
    // =========================================================================

    public function test_auth_view_controller_login_view_returned(): void
    {
        $controller = new \App\Http\Controllers\AuthViewController();
        $request    = \Illuminate\Http\Request::create('/user/login', 'GET');

        $response = $controller->Login($request);

        $this->assertInstanceOf(\Illuminate\Contracts\View\View::class, $response);
        $this->assertSame('frontend.login', $response->getName());
    }

    public function test_auth_view_controller_signup_view_returned(): void
    {
        $controller = new \App\Http\Controllers\AuthViewController();
        $request    = \Illuminate\Http\Request::create('/signup', 'GET');

        $response = $controller->SignUp($request);

        $this->assertInstanceOf(\Illuminate\Contracts\View\View::class, $response);
        $this->assertSame('frontend.signup', $response->getName());
    }

    public function test_auth_view_controller_forgot_view_returned(): void
    {
        $controller = new \App\Http\Controllers\AuthViewController();
        $request    = \Illuminate\Http\Request::create('/forgot-password', 'GET');

        $response = $controller->forgot($request);

        $this->assertInstanceOf(\Illuminate\Contracts\View\View::class, $response);
        $this->assertSame('frontend.forgot', $response->getName());
    }

    // =========================================================================
    // GuidanceController — helpers FS (sans base de données)
    // =========================================================================

    public function test_guidance_controller_parse_sections_returns_array(): void
    {
        $controller = new \App\Http\Controllers\GuidanceController();

        $markdown = "## Section A\n- item 1\n- item 2\n\n## Section B\nTexte simple\n";
        $method   = new \ReflectionMethod($controller, 'parseExecutionGuideSections');
        $method->setAccessible(true);

        $sections = $method->invoke($controller, $markdown);

        $this->assertCount(2, $sections);
        $this->assertSame('Section A', $sections[0]['title']);
        $this->assertSame(2, $sections[0]['item_count']);
        $this->assertSame('Section B', $sections[1]['title']);
    }

    public function test_guidance_controller_reset_state_returns_empty_items(): void
    {
        $controller = new \App\Http\Controllers\GuidanceController();

        $readMethod = new \ReflectionMethod($controller, 'readExecutionGuideState');
        $readMethod->setAccessible(true);

        $state = $readMethod->invoke($controller);

        $this->assertArrayHasKey('items', $state);
        $this->assertIsArray($state['items']);
    }

    // =========================================================================
    // PublicCatalogController — unitaire
    // =========================================================================

    public function test_public_catalog_get_cart_count_returns_json(): void
    {
        $controller = new \App\Http\Controllers\PublicCatalogController();
        $response   = $controller->getCartCount();

        $this->assertInstanceOf(\Illuminate\Http\JsonResponse::class, $response);
        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('count', $data);
    }
}
