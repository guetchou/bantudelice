<?php

namespace Tests\Feature;

use App\User;
use Tests\TestCase;
use App\Services\AddressQualityService;
use App\Services\CheckoutService;
use App\Services\PaymentExperienceService;
use Illuminate\Http\Request;
use Mockery;

class PaymentExperienceEndpointTest extends TestCase
{
    public function test_checkout_controller_returns_payment_experience_payload()
    {
        require_once app_path('Http/Controllers/Api/CheckoutController.php');

        $user = new User([
            'id' => 99,
            'name' => 'Client test',
            'phone' => '068000000',
        ]);
        $user->exists = true;

        $order = new \App\Order(['id' => 1]);
        $order->exists = true;

        $checkoutService = Mockery::mock(CheckoutService::class);
        $checkoutService->shouldReceive('startCheckout')->once()->andReturn([
            'order'    => $order,
            'order_no' => 'ORD-TEST-001',
        ]);

        $paymentExperienceService = new PaymentExperienceService();
        $addressQualityService = app(AddressQualityService::class);
        $controller = new \App\Http\Controllers\Api\CheckoutController($checkoutService, $paymentExperienceService, $addressQualityService);

        $request = Request::create('/api/checkout', 'POST', [
            'payment_method' => 'momo',
            'delivery_address' => '12 Rue Test',
            'd_lat' => '-4.2700',
            'd_lng' => '15.2800',
            'delivery_address_confirmed' => true,
            'phone' => '068000000',
        ]);
        $request->setUserResolver(fn () => $user);

        $response = $controller($request);
        $payload = $response->getData(true);

        $this->assertTrue($payload['status']);
        $this->assertTrue($payload['awaiting_restaurant_acceptance']);
        $this->assertNull($payload['payment']);
        $this->assertFalse($payload['requires_external_payment']);
        $this->assertSame('ORD-TEST-001', $payload['order']['order_no']);
    }

    public function test_track_order_view_uses_payment_experience_contract()
    {
        $contents = file_get_contents(base_path('resources/views/frontend/track_order.blade.php'));

        $this->assertStringContainsString("paymentExperience['customer_message']", $contents);
        $this->assertStringContainsString("paymentExperience['failure_reason']", $contents);
    }

    public function test_order_receipt_view_uses_payment_experience_contract()
    {
        $contents = file_get_contents(base_path('resources/views/frontend/order_receipt.blade.php'));

        $this->assertStringContainsString("paymentExperience['customer_message']", $contents);
        $this->assertStringContainsString("paymentExperience['failure_reason']", $contents);
    }

    public function test_admin_transport_show_view_uses_payment_experience_contract()
    {
        $viewContents = file_get_contents(base_path('resources/views/admin/transport/bookings/show.blade.php'));
        $controllerContents = file_get_contents(base_path('app/Http/Controllers/admin/Transport/AdminTransportController.php'));
        $routesContents = file_get_contents(base_path('routes/web.php'));

        $this->assertStringContainsString("paymentExperience['customer_message']", $viewContents);
        $this->assertStringContainsString("showBooking", $controllerContents);
        $this->assertStringContainsString("admin.transport.bookings.show", $routesContents);
    }

    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }
}
