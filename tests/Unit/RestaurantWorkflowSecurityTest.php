<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class RestaurantWorkflowSecurityTest extends TestCase
{
    private function source(string $relativePath): string
    {
        $contents = file_get_contents(dirname(__DIR__, 2) . '/' . ltrim($relativePath, '/'));
        self::assertNotFalse($contents, 'Impossible de lire ' . $relativePath);

        return $contents;
    }

    public function test_legacy_checkout_cannot_bypass_checkout_service(): void
    {
        $source = $this->source('app/Http/Requests/Order/PlaceOrderRequest.php');

        self::assertStringContainsString("routeIs('place.order')", $source);
        self::assertStringContainsString('return ! $this->routeIs', $source);
    }

    public function test_restaurant_middleware_enforces_ownership_and_blocks_legacy_actions(): void
    {
        $source = $this->source('app/Http/Middleware/RestaurantMiddleware.php');

        self::assertStringContainsString("where('restaurant_id', \$restaurantId)", $source);
        self::assertStringContainsString("'restaurant.deliver_order'", $source);
        self::assertStringContainsString("'restaurant.assign_driver'", $source);
        self::assertStringContainsString("'restaurant.assign_order'", $source);
        self::assertStringContainsString("\$request->isMethod('get')", $source);
    }

    public function test_order_acceptance_is_serialized_and_idempotent(): void
    {
        $source = $this->source('app/Domain/Food/Services/OrderAcceptanceService.php');

        self::assertStringContainsString('lockForUpdate()', $source);
        self::assertStringContainsString('firstOrCreate(', $source);
        self::assertStringContainsString("'accepted_awaiting_payment'", $source);
    }

    public function test_kitchen_cannot_mark_delivery_as_completed(): void
    {
        $source = $this->source('app/Http/Controllers/restaurant/KitchenController.php');

        self::assertStringContainsString(
            'in:in_kitchen,ready_for_pickup,customer_arrived,picked_up_by_customer,no_show',
            $source
        );
        self::assertStringNotContainsString(
            'in:pending,accepted,accepted_awaiting_payment,confirmed,prepairing,in_kitchen,assign,ready_for_pickup,dispatching,completed,delivered',
            $source
        );
    }

    public function test_checkout_honours_restaurant_pause_and_rejects_fake_scheduling(): void
    {
        $source = $this->source('app/Services/CheckoutService.php');

        self::assertStringContainsString('\$restaurant->is_paused', $source);
        self::assertStringContainsString('commandes programmées sont temporairement indisponibles', $source);
        self::assertStringContainsString('guardRestaurantAvailableForOrdering', $source);
    }
}
