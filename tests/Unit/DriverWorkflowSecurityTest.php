<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class DriverWorkflowSecurityTest extends TestCase
{
    public function test_sensitive_legacy_driver_routes_are_authenticated(): void
    {
        $routes = $this->source('routes/api.php');

        $this->assertStringContainsString("Route::middleware('auth:driver_api')->group", $routes);
        $this->assertStringContainsString('driver_change_password', $routes);
        $this->assertStringContainsString('driver_update_profile', $routes);
        $this->assertStringContainsString('set_driver_online', $routes);
        $this->assertStringContainsString('driver_earning_history', $routes);
        $this->assertStringContainsString('driver/offers/{delivery}/accept', $routes);
    }

    public function test_driver_endpoints_cannot_cancel_or_self_confirm_delivery(): void
    {
        $apiController = $this->source('app/Http/Controllers/Api/DriverDeliveriesController.php');
        $webController = $this->source('app/Http/Controllers/DriverDeliveriesController.php');

        $this->assertStringContainsString('required|in:PICKED_UP,ON_THE_WAY,DELIVERED', $apiController);
        $this->assertStringContainsString('required|in:PICKED_UP,ON_THE_WAY,DELIVERED', $webController);
        $this->assertStringNotContainsString("'customer_confirmed' => \$request", $apiController);
        $this->assertStringNotContainsString("'customer_confirmed' => \$request", $webController);
    }

    public function test_otp_is_hashed_limited_and_never_returned_to_driver(): void
    {
        $proof = $this->source('app/Services/DeliveryProofService.php');
        $webController = $this->source('app/Http/Controllers/DriverDeliveriesController.php');

        $this->assertStringContainsString('Hash::make($code)', $proof);
        $this->assertStringContainsString('OTP_MAX_ATTEMPTS = 5', $proof);
        $this->assertStringContainsString('OTP_TTL_MINUTES = 30', $proof);
        $this->assertStringNotContainsString("'delivery_otp_code' =>", $webController);
    }

    public function test_failed_cash_collection_is_not_marked_paid_or_withdrawable(): void
    {
        $deliveryService = $this->source('app/Services/SecureDeliveryService.php');
        $dashboard = $this->source('app/Services/PartnerFinancialDashboardService.php');

        $this->assertStringContainsString('OrderPaymentStatus::CASH_DUE->value', $deliveryService);
        $this->assertStringContainsString("\$collected ? 'PAID' : 'PENDING'", $deliveryService);
        $this->assertStringContainsString("cash_collection_status', 'collected'", $dashboard);
        $this->assertStringNotContainsString("orWhere('orders.payment_method', 'cash')", $dashboard);
    }

    public function test_dispatch_uses_consent_approved_fresh_drivers_and_ready_orders(): void
    {
        $dispatch = $this->source('app/Services/SecureDispatchService.php');
        $geo = $this->source('app/Services/DeliveryDispatchService.php');
        $offerJob = $this->source('app/Jobs/BroadcastDeliveryOfferJob.php');
        $deliveryService = $this->source('app/Services/SecureDeliveryService.php');
        $cashAcceptance = $this->source('app/Domain/Food/Services/WorkflowOrderAcceptanceService.php');
        $onlinePayment = $this->source('app/Domain/Food/Listeners/FoodOrderPaymentConfirmed.php');

        $this->assertStringContainsString('return false;', $dispatch);
        $this->assertStringContainsString("where('business_status', 'ready_for_pickup')", $dispatch);
        $this->assertStringContainsString("business_status !== 'ready_for_pickup'", $offerJob);
        $this->assertStringContainsString("business_status !== 'ready_for_pickup'", $deliveryService);
        $this->assertStringContainsString("where('approved', true)", $geo);
        $this->assertStringContainsString('locationFreshnessSeconds()', $geo);
        $this->assertStringContainsString('nouvelle recherche planifiée sans assignation forcée', $offerJob);
        $this->assertStringNotContainsString("enqueue_job('food', 'auto_assign_delivery'", $cashAcceptance);
        $this->assertStringNotContainsString("enqueue_job('food', 'auto_assign_delivery'", $onlinePayment);
    }

    private function source(string $relativePath): string
    {
        $path = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
        $contents = file_get_contents($path);

        $this->assertNotFalse($contents, "Fichier introuvable : {$relativePath}");

        return $contents;
    }
}
