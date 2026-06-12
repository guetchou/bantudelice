<?php

namespace Tests\Feature;

use App\Domain\Colis\Models\Shipment;
use App\Domain\Colis\Services\ShipmentPaymentService;
use App\Domain\Transport\Models\TransportBooking;
use App\Domain\Transport\Services\TransportNotificationService;
use App\Domain\Transport\Services\TransportService;
use App\Payment;
use App\Services\AuditLogService;
use App\Services\FinancialEventService;
use App\Services\PaymentService;
use Mockery;
use Tests\TestCase;

class PaymentFlowRegressionTest extends TestCase
{
    public function test_food_reference_flow_uses_shared_external_payment_preparation()
    {
        $payment = new FakePayment([
            'id' => 101,
            'provider' => 'momo',
            'status' => 'PENDING',
            'amount' => 1,
            'currency' => 'XAF',
            'meta' => [],
        ]);

        $service = Mockery::mock(PaymentService::class)->makePartial();
        $service->shouldReceive('initiateExternalPayment')
            ->once()
            ->andReturnUsing(function () use ($payment) {
                $payment->meta = array_merge($payment->meta ?? [], [
                    'provider' => 'momo',
                    'provider_reference' => 'food-ref-1',
                ]);

                return [
                'provider_reference' => 'food-ref-1',
                'meta' => [
                    'provider' => 'momo',
                    'provider_reference' => 'food-ref-1',
                ],
                'redirect_url' => null,
                ];
            });

        $result = $service->prepareExternalPayment($payment, collect([]), [
            'phone' => '068006730',
            'fulfillment_mode' => 'pickup',
        ], [
            'totals' => ['total' => 1],
        ]);

        $freshPayment = $payment->fresh();

        $this->assertSame('food-ref-1', $freshPayment->provider_reference);
        $this->assertSame('068006730', data_get($freshPayment->meta, 'checkout_data.phone'));
        $this->assertSame(1, data_get($freshPayment->meta, 'totals.total'));
        $this->assertSame('food-ref-1', data_get($freshPayment->meta, 'provider_reference'));
        $this->assertSame($freshPayment->id, $result['payment']->id);
    }

    public function test_shipment_payment_uses_shared_payment_core_and_keeps_context()
    {
        $user = new FakeUser(['id' => 501, 'phone' => '068006730']);

        $payment = new FakePersistedPayment([
            'id' => 201,
            'user_id' => $user->id,
            'shipment_id' => 9001,
            'provider' => 'momo',
            'status' => 'PENDING',
            'amount' => 1,
            'currency' => 'XAF',
            'provider_reference' => 'shipment-ref-1',
        ]);
        $payment->exists = true;

        $paymentService = new CapturingPaymentService($payment);

        $financialEvents = Mockery::mock(FinancialEventService::class);
        $financialEvents->shouldReceive('recordForPayment')->once();

        $shipment = new Shipment([
            'id' => 9001,
            'customer_id' => $user->id,
            'total_price' => 1,
            'currency' => 'XAF',
            'tracking_number' => 'SHIP-001',
        ]);
        $shipment->exists = true;
        $shipment->setRelation('customer', $user);

        $service = new ShipmentPaymentService($financialEvents);
        $this->app->instance(PaymentService::class, $paymentService);

        $result = $service->initiatePayment($shipment, 'momo', ['phone' => '068006730']);

        [$attributes, $checkoutData, $baseMeta] = $paymentService->capturedArgs;

        $this->assertSame($user->id, $attributes['user_id']);
        $this->assertSame('momo', $attributes['provider']);
        $this->assertSame(1, $attributes['amount']);
        $this->assertSame('colis', $checkoutData['type']);
        $this->assertSame('068006730', $checkoutData['phone']);
        $this->assertSame('SHIP-001', $checkoutData['shipment_payment_context']['tracking_number']);
        $this->assertSame('SHIP-001', $baseMeta['tracking_number']);
        $this->assertSame($payment->id, $result['payment_id']);
        $this->assertSame('pending', $result['status']);
        $this->assertArrayHasKey('checkout_url', $result);
    }

    public function test_transport_payment_uses_shared_payment_core_and_keeps_context()
    {
        $user = new FakeUser(['id' => 601, 'phone' => '069552091']);

        $payment = new FakePersistedPayment([
            'id' => 301,
            'user_id' => $user->id,
            'transport_booking_id' => 7001,
            'provider' => 'momo',
            'status' => 'PENDING',
            'amount' => 5796,
            'currency' => 'XAF',
            'provider_reference' => 'transport-ref-1',
        ]);
        $payment->exists = true;

        $paymentService = new CapturingPaymentService($payment);

        $financialEvents = Mockery::mock(FinancialEventService::class);
        $financialEvents->shouldReceive('recordForPayment')->once();

        $notificationService = Mockery::mock(TransportNotificationService::class);
        $auditLogService = Mockery::mock(AuditLogService::class);

        $booking = new FakePersistedTransportBooking([
            'id' => 7001,
            'user_id' => $user->id,
            'booking_no' => 'TR-001',
            'payment_method' => 'momo',
            'estimated_price' => 5796,
            'total_price' => 5796,
        ]);
        $booking->setRelation('user', $user);

        $service = new TransportService($notificationService, $financialEvents, $auditLogService);
        $this->app->instance(PaymentService::class, $paymentService);

        $result = $service->initiatePayment($booking, 'momo', ['phone' => '069552091']);

        [$attributes, $checkoutData] = $paymentService->capturedArgs;

        $this->assertSame($user->id, $attributes['user_id']);
        $this->assertSame('momo', $attributes['provider']);
        $this->assertSame(5796, $attributes['amount']);
        $this->assertSame('transport', $checkoutData['type']);
        $this->assertSame('069552091', $checkoutData['phone']);
        $this->assertSame('TR-001', $checkoutData['transport_payment_context']['booking_no']);
        $this->assertSame($payment->id, $result['payment']->id);
        $this->assertNull($result['redirect_url']);
    }
}

class FakePayment
{
    public function __construct(array $attributes = [])
    {
        foreach ($attributes as $key => $value) {
            $this->{$key} = $value;
        }
    }

    public function update(array $attributes): bool
    {
        foreach ($attributes as $key => $value) {
            $this->{$key} = $value;
        }

        return true;
    }

    public function fresh()
    {
        return $this;
    }
}

class FakeUser
{
    public function __construct(array $attributes = [])
    {
        foreach ($attributes as $key => $value) {
            $this->{$key} = $value;
        }
    }
}

class CapturingPaymentService extends PaymentService
{
    public array $capturedArgs = [];

    public function __construct(private Payment $payment) {}

    public function startManagedPayment(array $paymentAttributes, array $checkoutData = [], array $baseMeta = [], $cartItems = null): array
    {
        $this->capturedArgs = [$paymentAttributes, $checkoutData, $baseMeta, $cartItems];

        return [
            'payment' => $this->payment,
            'payment_payload' => ['redirect_url' => null],
        ];
    }
}

class FakePersistedPayment extends Payment
{
    public function fresh($with = [])
    {
        return $this;
    }
}

class FakePersistedTransportBooking extends TransportBooking
{
    public function update(array $attributes = [], array $options = []): bool
    {
        foreach ($attributes as $key => $value) {
            $this->setAttribute($key, $value);
        }

        return true;
    }

    public function fresh($with = [])
    {
        return $this;
    }
}
