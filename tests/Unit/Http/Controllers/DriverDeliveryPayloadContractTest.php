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

class DriverDeliveryPayloadContractTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_driver_payload_marks_cash_to_collect(): void
    {
        $payload = $this->payloadFor('cash', 'pending_collection', 6500);
        $item = $payload['data'][0];

        $this->assertTrue($payload['status']);
        $this->assertSame('cash', $item['payment_method']);
        $this->assertSame('pending_collection', $item['cash_collection_status']);
        $this->assertTrue($item['cash_collection']['required']);
        $this->assertTrue($item['cash_collection']['attention_required']);
        $this->assertSame('Espèces à collecter', $item['cash_collection']['badge']);
        $this->assertSame(6500, $item['cash_collection']['amount']);
    }

    public function test_driver_payload_does_not_request_cash_for_online_payment(): void
    {
        $item = $this->payloadFor('momo', null, 6500)['data'][0];

        $this->assertFalse($item['cash_collection']['required']);
        $this->assertFalse($item['cash_collection']['attention_required']);
        $this->assertNull($item['cash_collection']['badge']);
        $this->assertSame(0, $item['cash_collection']['amount']);
    }

    private function payloadFor(string $method, ?string $collectionStatus, int $amount): array
    {
        $driver = new Driver();
        $driver->id = 77;

        $order = new Order([
            'order_no' => 'TD-PAYLOAD-001',
            'payment_method' => $method,
            'payment_status' => 'paid',
            'cash_collection_status' => $collectionStatus,
            'total' => $amount,
            'business_status' => 'out_for_delivery',
        ]);
        $order->setRelation('user', new User(['name' => 'Client Test']));

        $restaurant = new Restaurant(['name' => 'Restaurant Test']);
        $restaurant->id = 12;

        $delivery = new Delivery([
            'order_id' => 42,
            'status' => 'ON_THE_WAY',
            'delivery_fee' => 1000,
        ]);
        $delivery->id = 99;
        $delivery->created_at = now();
        $delivery->setRelation('order', $order);
        $delivery->setRelation('restaurant', $restaurant);

        $deliveryService = Mockery::mock(DeliveryService::class);
        $deliveryService->shouldReceive('getActiveDeliveriesForDriver')
            ->once()
            ->with($driver)
            ->andReturn(collect([$delivery]));

        $resolver = Mockery::mock(AuthenticatedDriverResolver::class);
        $resolver->shouldReceive('current')->once()->andReturn($driver);

        $controller = new DriverDeliveriesController($deliveryService, $resolver);

        return $controller
            ->index(Request::create('/api/driver/deliveries', 'GET'))
            ->getData(true);
    }
}
