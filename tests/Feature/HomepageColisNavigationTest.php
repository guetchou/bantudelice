<?php

namespace Tests\Feature;

use Tests\TestCase;

class HomepageColisNavigationTest extends TestCase
{
    public function test_homepage_exposes_colis_landing_link(): void
    {
        $modernHome = file_get_contents(base_path('resources/views/frontend/index-modern.blade.php'));
        $modernLayout = file_get_contents(base_path('resources/views/frontend/layouts/app-modern.blade.php'));

        $this->assertStringContainsString("route('colis.landing')", $modernHome);
        $this->assertStringContainsString("route('colis.landing')", $modernLayout);
        $this->assertStringContainsString('Accueil Mema', $modernLayout);
    }
}
