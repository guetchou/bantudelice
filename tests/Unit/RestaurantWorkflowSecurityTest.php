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

        self::assertStringContainsString('$restaurant->is_paused', $source);
        self::assertStringContainsString('commandes programmées sont temporairement indisponibles', $source);
        self::assertStringContainsString('guardRestaurantAvailableForOrdering', $source);
    }

    public function test_unaccepted_orders_have_a_scheduled_timeout(): void
    {
        $command = $this->source('app/Console/Commands/ExpireUnacceptedFoodOrders.php');
        $kernel = $this->source('app/Console/Kernel.php');
        $config = $this->source('config/food.php');
        $finance = $this->source('app/Services/FoodOrderFinanceService.php');

        self::assertStringContainsString("where('business_status', 'pending_restaurant_acceptance')", $command);
        self::assertStringContainsString('lockForUpdate()', $command);
        self::assertStringContainsString("'reason_code' => 'restaurant_timeout'", $command);
        self::assertStringContainsString('groupBy(\'order_no\')', $command);
        self::assertStringContainsString('food:expire-unaccepted --limit=100', $kernel);
        self::assertStringContainsString('FOOD_RESTAURANT_ACCEPTANCE_TIMEOUT_MINUTES', $config);
        self::assertStringContainsString("whereNull('order_id')", $finance);
        self::assertStringContainsString("'loyalty_points_used'", $finance);
    }
}
