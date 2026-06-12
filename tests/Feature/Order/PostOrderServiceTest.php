<?php

namespace Tests\Feature\Order;

use App\Order;
use App\Restaurant;
use App\Services\CommerceSignalService;
use App\Services\FinancialLedgerService;
use App\Services\PostOrderService;
use App\Services\RiskService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class PostOrderServiceTest extends TestCase
{
    private function makeService(): PostOrderService
    {
        return new PostOrderService(
            $this->createMock(CommerceSignalService::class),
            $this->createMock(RiskService::class),
            $this->createMock(FinancialLedgerService::class),
        );
    }

    public function test_run_does_not_throw_when_order_has_no_restaurant(): void
    {
        $order = $this->createMock(Order::class);
        $order->method('__get')->willReturnMap([
            ['order_no', 'TD-TEST-0001'],
            ['user_id', 1],
            ['total', 5000],
            ['sub_total', 5000],
            ['delivery_charges', 0],
            ['tax', 0],
            ['driver_tip', 0],
            ['fulfillment_mode', 'delivery'],
            ['payment_method', 'cash'],
            ['restaurant', null],
            ['id', 1],
        ]);

        Mail::fake();
        Log::shouldReceive('error')->zeroOrMoreTimes();

        $service = $this->makeService();

        $this->assertNull($service->run($order, [
            'total'            => 5000,
            'payment_method'   => 'cash',
            'fulfillment_mode' => 'delivery',
            'scheduled'        => false,
        ]));
    }

    public function test_run_accepts_pickup_context(): void
    {
        $order = $this->createMock(Order::class);
        $order->method('__get')->willReturnMap([
            ['order_no', 'TD-TEST-0002'],
            ['user_id', 2],
            ['total', 3000],
            ['sub_total', 3000],
            ['delivery_charges', 0],
            ['tax', 0],
            ['driver_tip', 0],
            ['fulfillment_mode', 'pickup'],
            ['payment_method', 'cash'],
            ['restaurant', null],
            ['id', 2],
        ]);

        Mail::fake();
        Log::shouldReceive('error')->zeroOrMoreTimes();

        $service = $this->makeService();

        $this->assertNull($service->run($order, [
            'total'            => 3000,
            'payment_method'   => 'cash',
            'fulfillment_mode' => 'pickup',
            'scheduled'        => false,
        ]));
    }
}
