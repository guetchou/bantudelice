<?php

namespace Tests\Unit\Http\Controllers;

use App\Delivery;
use App\Driver;
use App\Http\Controllers\Api\DriverDeliveriesController;
use App\Order;
use App\Restaurant;
use App\Services\DeliveryService;
use App\Support\Auth\AuthenticatedDriverResolver;
use App\User;
use Illuminate\Http\Request;
use Mockery;
use Tests\TestCase;

class DriverDeliveriesCashPayloadTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_cash_delivery_exposes_collection_badge_and_amount(): void
    {
        $driver = new Driver();
        $driver->id = 77;

        $order = new Order([
            'order_no' => 'TD-CASH-DRIVER-001',
            'payment_method' => 'cash',
            'payment_status' => 'paid',
            'cash_collection_status' => 'pending_collection',
            'total' => 6500,
            'delivery_address' => 'Bacongo',
            'business_status' => 'out_for_delivery',
        ]);
        $order->setRelation('user', new User(['name' => 'Client Test', 'phone' => '0600000000']));

        $restaurant = new Restaurant([
            'name' => 'Restaurant Test',
            'address' => 'Poto-Poto',
            'phone' => '0500000000',
        ]);
        $restaurant->id = 12;

        $delivery = new Delivery([
            'order_id' => 42,
            'status' => 'ON_THE_WAY',
            'delivery_fee' => 1000,
            'assigned_at' => now()->subMinutes(20),
        ]);
        $delivery->id = 99;
        $delivery->created_at = now();
        $delivery->setRelation('order', $order);
        $delivery->setRelation('restaurant', $restaurant);

        $deliveryService = Mockery::mock(DeliveryService::class);
        $deliveryService
            ->shouldReceive('getActiveDeliveriesForDriver')
            ->once()
            ->with($driver)
            ->andReturn(collect([$delivery]));

        $resolver = Mockery::mock(AuthenticatedDriverResolver::class);
        $resolver->shouldReceive('current')->once()->andReturn($driver);

        $controller = new DriverDeliveriesController($deliveryService, $resolver);
        $response = $controller->index(Request::create('/api/driver/deliveries', 'GET'));
        $payload = $response->getData(true);

        $this->assertTrue($payload['status']);
        $this->assertSame('cash', $payload['data'][0]['payment_method']);
        $this->assertSame('pending_collection', $payload['data'][0]['cash_collection_status']);
        $this->assertTrue($payload['data'][0]['cash_collection']['required']);
        $this->assertTrue($payload['data'][0]['cash_collection']['attention_required']);
        $this->assertSame('Espèces à collecter', $payload['data'][0]['cash_collection']['badge']);
        $this->assertSame(6500.0, $payload['data'][0]['cash_collection']['amount']);
    }

    public function test_online_payment_does_not_request_cash_collection(): void
    {
        $driver = new Driver();
        $driver->id = 77;

        $order = new Order([
            'order_no' => 'TD-MOMO-DRIVER-001',
            'payment_method' => 'momo',
            'payment_status' => 'completed',
            'total' => 6500,
            'business_status' => 'out_for_delivery',
        ]);
        $order->setRelation('user', new User(['name' => 'Client Test']));

        $restaurant = new Restaurant(['name' => 'Restaurant Test']);
        $restaurant->id = 12;

        $delivery = new Delivery([
            'order_id' => 43,
            'status' => 'ON_THE_WAY',
            'delivery_fee' => 1000,
        ]);
        $delivery->id = 100;
        $delivery->created_at = now();
        $delivery->setRelation('order', $order);
        $delivery->setRelation('restaurant', $restaurant);

        $deliveryService = Mockery::mock(DeliveryService::class);
        $deliveryService->shouldReceive('getActiveDeliveriesForDriver')->andReturn(collect([$delivery]));

        $resolver = Mockery::mock(AuthenticatedDriverResolver::class);
        $resolver->shouldReceive('current')->andReturn($driver);

        $controller = new DriverDeliveriesController($deliveryService, $resolver);
        $payload = $controller->index(Request::create('/api/driver/deliveries', 'GET'))->getData(true);

        $this->assertFalse($payload['data'][0]['cash_collection']['required']);
        $this->assertFalse($payload['data'][0]['cash_collection']['attention_required']);
        $this->assertNull($payload['data'][0]['cash_collection']['badge']);
        $this->assertSame(0, $payload['data'][0]['cash_collection']['amount']);
    }
}
