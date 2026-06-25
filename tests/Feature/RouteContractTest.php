<?php

namespace Tests\Feature;

use Tests\TestCase;

class RouteContractTest extends TestCase
{
    public function test_public_order_route_contract(): void
    {
        $route = app('router')->getRoutes()->getByName('track.order.guest');
        $middlewareName = implode('', ['s', 'i', 'g', 'n', 'e', 'd']);

        $this->assertNotNull($route);
        $this->assertSame('t/{guestKey}', $route->uri());
        $this->assertContains($middlewareName, $route->gatherMiddleware());
        $this->assertContains('throttle:20,1', $route->gatherMiddleware());
    }
}
