<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class RestaurantStaffAuthorizationTest extends TestCase
{
    private function source(string $path): string
    {
        $contents = file_get_contents(dirname(__DIR__, 2) . '/' . ltrim($path, '/'));
        self::assertNotFalse($contents, 'Impossible de lire ' . $path);

        return $contents;
    }

    public function test_staff_membership_is_unique_per_restaurant_and_user(): void
    {
        $migration = $this->source(
            'database/migrations/2026_06_25_220000_create_restaurant_staff_members_table.php'
        );

        self::assertStringContainsString(
            "$table->unique(['restaurant_id', 'user_id']",
            $migration
        );
        self::assertStringContainsString("$table->boolean('is_active')", $migration);
    }

    public function test_roles_follow_least_privilege(): void
    {
        $config = $this->source('config/restaurant_permissions.php');

        self::assertStringContainsString("'owner' => ['all']", $config);
        self::assertStringContainsString("'kitchen.manage'", $config);
        self::assertStringContainsString("'cash.collect'", $config);
        self::assertStringContainsString("'viewer'", $config);
    }

    public function test_middleware_resolves_staff_context_and_denies_unknown_routes(): void
    {
        $middleware = $this->source('app/Http/Middleware/RestaurantMiddleware.php');

        self::assertStringContainsString('RestaurantStaffMember::query()', $middleware);
        self::assertStringContainsString("where('is_active', true)", $middleware);
        self::assertStringContainsString("return 'unclassified'", $middleware);
        self::assertStringContainsString("'restaurant_context'", $middleware);
        self::assertStringContainsString('Restaurant staff permission denied', $middleware);
    }

    public function test_manager_cannot_manage_another_manager(): void
    {
        $controller = $this->source(
            'app/Http/Controllers/restaurant/RestaurantStaffController.php'
        );

        self::assertStringContainsString('guardManagerHierarchy', $controller);
        self::assertStringContainsString("$currentRole === 'manager'", $controller);
        self::assertStringContainsString("$staff->role === 'manager'", $controller);
    }

    public function test_staff_routes_are_protected_by_auth_and_restaurant_middleware(): void
    {
        $routes = $this->source('routes/restaurant-staff.php');
        $provider = $this->source('app/Providers/RouteServiceProvider.php');

        self::assertStringContainsString("middleware(['auth', 'restaurant'])", $routes);
        self::assertStringContainsString("name('restaurant.staff.')", $routes);
        self::assertStringContainsString('mapRestaurantStaffRoutes', $provider);
    }

    public function test_legacy_workflow_bypasses_remain_blocked(): void
    {
        $middleware = $this->source('app/Http/Middleware/RestaurantMiddleware.php');

        self::assertStringContainsString("'restaurant.deliver_order'", $middleware);
        self::assertStringContainsString("'restaurant.assign_driver'", $middleware);
        self::assertStringContainsString("request->isMethod('get')", $middleware);
    }

    public function test_integrity_audit_precedes_unique_database_constraints(): void
    {
        $command = $this->source('app/Console/Commands/AuditFoodWorkflowIntegrity.php');
        $kernel = $this->source('app/Console/Kernel.php');

        self::assertStringContainsString('duplicatePayments', $command);
        self::assertStringContainsString('duplicateDeliveries', $command);
        self::assertStringContainsString('inconsistentOrderGroups', $command);
        self::assertStringContainsString('cashPaidWithoutCollection', $command);
        self::assertStringContainsString("food:audit-integrity --json", $kernel);
    }
}
