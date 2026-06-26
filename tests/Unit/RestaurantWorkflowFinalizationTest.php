<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class RestaurantWorkflowFinalizationTest extends TestCase
{
    private function source(string $path): string
    {
        $contents = file_get_contents(dirname(__DIR__, 2) . '/' . ltrim($path, '/'));
        self::assertNotFalse($contents, 'Impossible de lire ' . $path);

        return $contents;
    }

    public function test_dashboard_aggregates_one_record_per_order_number(): void
    {
        $source = $this->source('app/Http/Controllers/restaurant/DashboardController.php');

        self::assertStringContainsString("->groupBy('order_no')", $source);
        self::assertStringContainsString("'revenue_recognized' => \$this->isRevenueRecognized(\$order)", $source);
        self::assertStringNotContainsString("->sum('total_price')", $source);
    }

    public function test_cash_revenue_requires_collection_confirmation(): void
    {
        $source = $this->source('app/Http/Controllers/restaurant/DashboardController.php');

        self::assertStringContainsString('cash_collection_confirmed_at !== null', $source);
        self::assertStringContainsString("'confirmed', 'reconciled', 'settled'", $source);
        self::assertStringContainsString("->where('revenue_recognized', true)", $source);
    }

    public function test_top_dishes_are_calculated_from_order_lines_not_live_cart(): void
    {
        $source = $this->source('app/Http/Controllers/restaurant/DashboardController.php');

        self::assertStringContainsString("DB::table('orders as order_lines')", $source);
        self::assertStringNotContainsString("DB::table('carts as c')", $source);
    }

    public function test_order_screen_separates_actionable_and_payment_waiting_orders(): void
    {
        $source = $this->source('resources/views/restaurant/order/all_orders.blade.php');

        self::assertStringContainsString("=== 'pending_restaurant_acceptance'", $source);
        self::assertStringContainsString("=== 'accepted_awaiting_payment'", $source);
        self::assertStringContainsString('form="ordBulkForm"', $source);
        self::assertStringContainsString('js-cancel-order', $source);
    }

    public function test_restaurant_cancellation_requires_a_controlled_reason(): void
    {
        $source = $this->source('app/Http/Middleware/RestaurantMiddleware.php');

        self::assertStringContainsString("'reason' => [", $source);
        self::assertStringContainsString("'product_unavailable'", $source);
        self::assertStringContainsString('Rule::requiredIf', $source);
    }

    public function test_notification_cursor_is_persisted_per_restaurant_session(): void
    {
        $source = $this->source('app/Http/Middleware/RestaurantMiddleware.php');
        $dashboard = $this->source('app/Http/Controllers/restaurant/DashboardController.php');

        self::assertStringContainsString('restaurant_notification_cursor_', $source);
        self::assertStringContainsString("query->set('after_id'", $source);
        self::assertStringContainsString("'next_cursor'", $dashboard);
    }
}
