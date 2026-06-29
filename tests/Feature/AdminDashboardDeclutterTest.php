<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class AdminDashboardDeclutterTest extends TestCase
{
    public function test_admin_dashboard_template_compiles(): void
    {
        $source = file_get_contents(resource_path('views/admin/home.blade.php'));

        $this->assertIsString($source);
        $this->assertNotSame('', $source);

        $compiled = Blade::compileString($source);

        $this->assertStringContainsString('admin-focus', $compiled);
        $this->assertStringContainsString('Actions requises', $compiled);
    }

    public function test_admin_dashboard_does_not_restore_removed_noise(): void
    {
        $source = file_get_contents(resource_path('views/admin/home.blade.php'));

        $this->assertStringNotContainsString('Activité live', $source);
        $this->assertStringNotContainsString('Structure opérationnelle', $source);
        $this->assertStringNotContainsString('dashDrawer', $source);
        $this->assertStringNotContainsString('setInterval(', $source);
    }
}
