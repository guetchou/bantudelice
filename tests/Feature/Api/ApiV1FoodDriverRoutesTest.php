<?php

namespace Tests\Feature\Api;

use Illuminate\Routing\Route as LaravelRoute;
use Tests\TestCase;

class ApiV1FoodDriverRoutesTest extends TestCase
{
    public function test_versioned_driver_and_food_routes_are_registered(): void
    {
        $driver = $this->findRoute('api/v1/driver/deliveries', 'GET');
        $checkout = $this->findRoute('api/v1/food/checkout', 'POST');
        $tracking = $this->findRoute('api/v1/food/orders/{order}/tracking', 'GET');

        $this->assertSame('api.v1.driver.deliveries.index', $driver->getName());
        $this->assertContains('auth:driver_api', $driver->gatherMiddleware());
        $this->assertContains('module:food', $driver->gatherMiddleware());

        $this->assertContains('auth.web_or_api', $checkout->gatherMiddleware());
        $this->assertContains('module:food', $checkout->gatherMiddleware());
        $this->assertContains('auth.web_or_api', $tracking->gatherMiddleware());
    }

    public function test_legacy_routes_remain_registered(): void
    {
        $this->assertNotNull($this->findRoute('api/driver/deliveries', 'GET'));
        $this->assertNotNull($this->findRoute('api/checkout', 'POST'));
        $this->assertNotNull($this->findRoute('api/orders/{order}/tracking', 'GET'));
    }

    private function findRoute(string $uri, string $method): LaravelRoute
    {
        $route = collect(app('router')->getRoutes()->getRoutes())
            ->first(fn (LaravelRoute $candidate) => $candidate->uri() === $uri
                && in_array($method, $candidate->methods(), true));

        $this->assertNotNull($route, "Route {$method} {$uri} absente");

        return $route;
    }
}
