<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class AdminNavigationConfigTest extends TestCase
{
    public function test_every_configured_admin_route_exists(): void
    {
        $this->assertFileExists(config_path('admin_navigation.php'));

        $navigation = config('admin_navigation');
        $routes = collect($navigation['workspaces'] ?? [])
            ->flatMap(fn (array $workspace) => collect($workspace['sections'] ?? [])->flatMap(fn (array $section) => $section['items'] ?? []))
            ->concat($navigation['platform']['items'] ?? [])
            ->pluck('route')
            ->unique()
            ->values();

        foreach ($routes as $routeName) {
            $this->assertTrue(Route::has($routeName), "La route admin [{$routeName}] n'existe pas.");
        }
    }

    public function test_food_only_entries_do_not_leak_into_kende_or_mema(): void
    {
        $foodOnlyRoutes = [
            'admin.payments.dashboard',
            'restaurant_payout',
            'driver_payout',
            'admin.commerce-analytics.index',
            'admin.support-tickets.index',
            'admin.cms.dashboard',
        ];

        foreach (['kende', 'mema'] as $workspace) {
            $routes = collect(config("admin_navigation.workspaces.{$workspace}.sections", []))
                ->flatMap(fn (array $section) => $section['items'] ?? [])
                ->pluck('route');

            foreach ($foodOnlyRoutes as $routeName) {
                $this->assertFalse($routes->contains($routeName), "La route food [{$routeName}] ne doit pas apparaître dans {$workspace}.");
            }
        }
    }

    public function test_each_workspace_has_a_valid_entry_route(): void
    {
        foreach (config('admin_navigation.workspaces', []) as $key => $workspace) {
            $this->assertArrayHasKey('dashboard_route', $workspace, "Point d'entrée absent pour {$key}.");
            $this->assertTrue(Route::has($workspace['dashboard_route']), "Point d'entrée invalide pour {$key}.");
        }
    }

    public function test_admin_layout_compiles_and_uses_named_routes(): void
    {
        $source = file_get_contents(resource_path('views/layouts/admin-modern.blade.php'));

        $this->assertIsString($source);
        $this->assertStringNotContainsString('href="/admin', $source);
        $this->assertStringNotContainsString("admin.partials._profile_drawer", $source);
        $this->assertStringContainsString("admin.partials._admin_profile_drawer", $source);
        $this->assertStringContainsString("config('admin_navigation.workspaces.'", $source);
        $this->assertStringContainsString("route('admin.portal'", $source);

        $compiled = Blade::compileString($source);

        $this->assertStringContainsString('adm-nav-details', $compiled);
        $this->assertStringContainsString('adm-workspace-pill', $compiled);
        $this->assertStringContainsString('admProfileOpen', $compiled);
    }

    public function test_admin_profile_drawer_does_not_link_to_food_settings(): void
    {
        $source = file_get_contents(resource_path('views/admin/partials/_admin_profile_drawer.blade.php'));

        $this->assertIsString($source);
        $this->assertStringNotContainsString("route('charge.index')", $source);
        $this->assertStringContainsString("route('admin.profile')", $source);
        $this->assertStringContainsString("route('admin.portal')", $source);
        $this->assertStringContainsString("route('admin.audit_trail')", $source);
    }
}
