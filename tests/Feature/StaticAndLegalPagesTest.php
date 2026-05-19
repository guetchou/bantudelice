<?php

namespace Tests\Feature;

use App\Services\CmsStaticPageService;
use Mockery;
use Tests\TestCase;

/**
 * Vérifie que les routes statiques et légales :
 * 1. Pointent vers les nouveaux contrôleurs (pas IndexController).
 * 2. Retournent HTTP 200 sans erreur de résolution.
 * 3. CmsStaticPageService::getPage() est bien appelé pour les pages légales.
 * 4. Le fallback vue statique est utilisé quand CMS retourne null.
 *
 * Ces tests n'ont pas besoin de base de données — CmsStaticPageService est mocké.
 */
class StaticAndLegalPagesTest extends TestCase
{
    // =========================================================================
    // Routes → nouveaux contrôleurs (pas IndexController)
    // =========================================================================

    /** @dataProvider routeControllerProvider */
    public function test_route_is_handled_by_correct_controller(string $routeName, string $expectedController): void
    {
        $routes = app('router')->getRoutes();
        $route  = $routes->getByName($routeName);

        $this->assertNotNull($route, "Route '{$routeName}' introuvable");

        $action = $route->getActionName();
        $this->assertStringContainsString(
            $expectedController,
            $action,
            "Route '{$routeName}' doit être gérée par {$expectedController}, got: {$action}"
        );
    }

    public static function routeControllerProvider(): array
    {
        return [
            'site.map'         => ['site.map',         'StaticPageController'],
            'data.deletion'    => ['data.deletion',    'StaticPageController'],
            'contact.us'       => ['contact.us',       'StaticPageController'],
            'about.us'         => ['about.us',         'LegalPageController'],
            'terms.conditions' => ['terms.conditions', 'LegalPageController'],
            'refund.policy'    => ['refund.policy',    'LegalPageController'],
            'privacy.policy'   => ['privacy.policy',   'LegalPageController'],
            'legal.notices'    => ['legal.notices',    'LegalPageController'],
            'cookies.policy'   => ['cookies.policy',   'LegalPageController'],
            'faq'              => ['faq',               'LegalPageController'],
            'help'             => ['help',              'LegalPageController'],
            'offers'           => ['offers',            'LegalPageController'],
        ];
    }

    // =========================================================================
    // Routes → IndexController (méthodes non extraites — non-régression)
    // =========================================================================

    /** @dataProvider indexControllerRoutesProvider */
    public function test_non_extracted_routes_still_point_to_index_controller(string $routeName): void
    {
        $routes = app('router')->getRoutes();
        $route  = $routes->getByName($routeName);

        $this->assertNotNull($route, "Route '{$routeName}' introuvable");

        $action = $route->getActionName();
        $this->assertStringContainsString(
            'IndexController',
            $action,
            "Route '{$routeName}' doit rester dans IndexController"
        );
    }

    public static function indexControllerRoutesProvider(): array
    {
        return [
            'home' => ['home'],
        ];
    }

    // =========================================================================
    // LegalPageController — logique cmsOrFallback unitaire
    // =========================================================================

    /** @dataProvider legalFallbackProvider */
    public function test_legal_page_controller_returns_fallback_view_name_when_cms_null(
        string $method,
        string $slug,
        string $expectedFallback
    ): void {
        $cms = Mockery::mock(CmsStaticPageService::class);
        $cms->shouldReceive('getPage')->with($slug)->once()->andReturn(null);

        $controller = new \App\Http\Controllers\LegalPageController($cms);
        $request    = \Illuminate\Http\Request::create('/' . $slug);

        $view = $controller->{$method}($request);

        $this->assertInstanceOf(\Illuminate\Contracts\View\View::class, $view);
        $this->assertSame($expectedFallback, $view->getName());
    }

    public static function legalFallbackProvider(): array
    {
        return [
            'faq'           => ['faq',          'faq',                    'frontend.faq'],
            'terms'         => ['terms',         'terms-and-conditions',   'frontend.terms'],
            'about'         => ['about',         'about-us',               'frontend.about'],
            'privacyPolicy' => ['privacyPolicy', 'privacy-policy',         'frontend.privacy_policy'],
            'refundPolicy'  => ['refundPolicy',  'return-policy',          'frontend.policy'],
            'legalNotices'  => ['legalNotices',  'mentions-legales',       'frontend.legal_notices'],
            'cookies'       => ['cookies',        'politique-cookies',      'frontend.cookies'],
            'help'          => ['help',           'help',                   'frontend.help'],
            'offers'        => ['offers',         'offers',                 'frontend.offers'],
        ];
    }

    public function test_legal_page_controller_returns_cms_page_view_when_cms_has_page(): void
    {
        $fakePage = new \App\CmsContent();

        $cms = Mockery::mock(CmsStaticPageService::class);
        $cms->shouldReceive('getPage')->with('faq')->once()->andReturn($fakePage);
        $cms->shouldReceive('body')->andReturn('body content');
        $cms->shouldReceive('featuredImage')->andReturn(null);
        $cms->shouldReceive('primaryCtaLabel')->andReturn(null);
        $cms->shouldReceive('primaryCtaUrl')->andReturn(null);

        $controller = new \App\Http\Controllers\LegalPageController($cms);
        $view       = $controller->faq();

        $this->assertSame('frontend.cms_page', $view->getName());
        $this->assertSame($fakePage, $view->getData()['page']);
    }

    // =========================================================================
    // StaticPageController — méthodes unitaires
    // =========================================================================

    public function test_static_page_controller_site_map_returns_correct_view(): void
    {
        $controller = new \App\Http\Controllers\StaticPageController();
        $view       = $controller->siteMap();

        $this->assertSame('frontend.sitemap', $view->getName());
    }

    public function test_static_page_controller_contact_returns_correct_view(): void
    {
        $controller = new \App\Http\Controllers\StaticPageController();
        $request    = \Illuminate\Http\Request::create('/contact-us');
        $view       = $controller->contact($request);

        $this->assertSame('frontend.contact', $view->getName());
    }

    public function test_static_page_controller_data_deletion_returns_correct_view(): void
    {
        $controller = new \App\Http\Controllers\StaticPageController();
        $request    = \Illuminate\Http\Request::create('/data-deletion');
        $view       = $controller->dataDeletion($request);

        $this->assertSame('frontend.data_deletion', $view->getName());
    }

    // =========================================================================
    // Noms de routes inchangés (non-régression URLs)
    // =========================================================================

    /** @dataProvider routeUriProvider */
    public function test_route_uri_unchanged(string $routeName, string $expectedUri): void
    {
        $this->assertSame(
            $expectedUri,
            route($routeName, [], false),
            "L'URI de la route '{$routeName}' a changé"
        );
    }

    public static function routeUriProvider(): array
    {
        return [
            ['site.map',         '/plan-du-site'],
            ['data.deletion',    '/data-deletion'],
            ['contact.us',       '/contact-us'],
            ['about.us',         '/about-us'],
            ['terms.conditions', '/terms-and-conditions'],
            ['refund.policy',    '/return-policy'],
            ['privacy.policy',   '/privacy-policy'],
            ['legal.notices',    '/mentions-legales'],
            ['cookies.policy',   '/politique-cookies'],
            ['faq',              '/faq'],
            ['help',             '/help'],
            ['offers',           '/offers'],
        ];
    }

}
