<?php

namespace Tests\Feature;

use Tests\TestCase;

class AdminLayoutFooterLinkTest extends TestCase
{
    public function test_admin_layout_footer_links_to_home_route_instead_of_dead_anchor(): void
    {
        $layoutPath = resource_path('views/layouts/app.blade.php');
        $contents = file_get_contents($layoutPath);

        $this->assertNotFalse($contents, sprintf('Unable to read layout file: %s', $layoutPath));
        $this->assertStringContainsString(
            '<a href="{{ route(\'home\') }}">Buntu Delice</a>',
            $contents,
            sprintf('Expected home route footer link in %s', $layoutPath)
        );
        $this->assertStringNotContainsString(
            '<a href="#">Buntu Delice</a>',
            $contents,
            sprintf('Unexpected dead footer link in %s', $layoutPath)
        );
    }

    public function test_legacy_app_view_is_only_a_shim_to_canonical_layout(): void
    {
        $legacyPath = resource_path('views/app.blade.php');
        $contents = file_get_contents($legacyPath);

        $this->assertNotFalse($contents, sprintf('Unable to read view file: %s', $legacyPath));
        $this->assertSame("@extends('layouts.app')", trim($contents));
    }
}
