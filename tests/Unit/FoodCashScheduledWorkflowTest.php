<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class FoodCashScheduledWorkflowTest extends TestCase
{
    private function source(string $path): string
    {
        $contents = file_get_contents(dirname(__DIR__, 2) . '/' . ltrim($path, '/'));
        self::assertNotFalse($contents, 'Impossible de lire ' . $path);

        return $contents;
    }

    public function test_cash_due_is_distinct_from_paid(): void
    {
        $enum = $this->source('app/Domain/Food/Enums/OrderPaymentStatus.php');
        $acceptance = $this->source('app/Domain/Food/Services/WorkflowOrderAcceptanceService.php');
        $stateMachine = $this->source('app/Services/WorkflowFoodOrderStateMachineService.php');

        self::assertStringContainsString("case CASH_DUE = 'cash_due'", $enum);
        self::assertStringContainsString('OrderPaymentStatus::CASH_DUE->value', $acceptance);
        self::assertStringContainsString('cash_collection_confirmed_at', $stateMachine);
        self::assertStringContainsString("'status' => 'PAID'", $stateMachine);
    }

    public function test_scheduled_order_is_accepted_without_starting_fulfillment(): void
    {
        $acceptance = $this->source('app/Domain/Food/Services/WorkflowOrderAcceptanceService.php');

        self::assertStringContainsString("'accepted_scheduled'", $acceptance);
        self::assertStringContainsString('scheduledAt->isFuture()', $acceptance);
        self::assertStringContainsString('releaseScheduled', $acceptance);
        self::assertStringContainsString("'preparation_due'", $acceptance);
    }

    public function test_scheduled_checkout_validates_future_opening_and_defers_driver_assignment(): void
    {
        $checkout = $this->source('app/Services/WorkflowCheckoutService.php');
        $availability = $this->source('app/Services/ScheduledRestaurantAvailabilityService.php');

        self::assertStringContainsString('ScheduledRestaurantAvailabilityService::class', $checkout);
        self::assertStringContainsString("'assignment_deferred' => true", $checkout);
        self::assertStringContainsString('special_closures', $availability);
        self::assertStringContainsString('previousDate', $availability);
    }

    public function test_scheduler_releases_and_retries_due_scheduled_orders(): void
    {
        $command = $this->source('app/Console/Commands/ReleaseScheduledFoodOrders.php');
        $kernel = $this->source('app/Console/Kernel.php');
        $config = $this->source('config/food.php');
        $acceptance = $this->source('app/Domain/Food/Services/WorkflowOrderAcceptanceService.php');

        self::assertStringContainsString(
            "whereIn('business_status', ['accepted_scheduled', 'preparation_due'])",
            $command
        );
        self::assertStringContainsString("currentStatus !== 'preparation_due'", $acceptance);
        self::assertStringContainsString('food:release-scheduled --limit=100', $kernel);
        self::assertStringContainsString('FOOD_SCHEDULED_PREPARATION_LEAD_MINUTES', $config);
    }

    public function test_online_payment_timeout_starts_when_payment_is_requested(): void
    {
        $acceptance = $this->source('app/Domain/Food/Services/WorkflowOrderAcceptanceService.php');

        self::assertStringContainsString("'accepted_at' => now()", $acceptance);
        self::assertStringContainsString("'accepted_awaiting_payment'", $acceptance);
    }

    public function test_loyalty_transaction_is_linked_to_created_order(): void
    {
        $checkout = $this->source('app/Services/WorkflowCheckoutService.php');

        self::assertStringContainsString(
            'LoyaltyService::usePoints($user->id, $loyaltyPointsUsed, $primaryOrder->id)',
            $checkout
        );
    }

    public function test_container_uses_extended_workflow_services(): void
    {
        $provider = $this->source('app/Providers/AppServiceProvider.php');

        self::assertStringContainsString('WorkflowCheckoutService::class', $provider);
        self::assertStringContainsString('WorkflowFoodOrderStateMachineService::class', $provider);
        self::assertStringContainsString('WorkflowOrderAcceptanceService::class', $provider);
    }
}
