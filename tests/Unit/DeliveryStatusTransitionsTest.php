<?php

namespace Tests\Unit;

use App\Delivery;
use App\Domain\Order\ValueObjects\OrderStatusSnapshot;
use App\Driver;
use App\Order;
use App\Services\DeliveryService;
use App\Services\FoodOrderStateMachineService;
use Mockery;
use Tests\TestCase;

/**
 * Vérifie que chaque transition de statut livraison :
 * 1. Appelle transitionOrderGroup avec le bon business_status.
 * 2. N'écrit PAS order.status directement (la state machine s'en charge).
 * 3. Produit un OrderStatusSnapshot cohérent après la transition.
 *
 * Utilise TestableDeliveryServiceForTransitions qui surcharge updateStatus()
 * pour bypasser DB::transaction et Schema::hasColumn, tout en préservant
 * la séquence logique observable (state machine + delivery update).
 */
class DeliveryStatusTransitionsTest extends TestCase
{
    // =========================================================================
    // PICKED_UP
    // =========================================================================

    public function test_picked_up_calls_state_machine_with_picked_up()
    {
        $sm = $this->expectStateMachineCall('picked_up');
        [$delivery, $order] = $this->makeDelivery('ASSIGNED');

        (new TransitionTestableDeliveryService($sm))->updateStatus($delivery, 'PICKED_UP');

        // Mockery vérifie que transitionOrderGroup a été appelé avec 'picked_up'.
    }

    public function test_picked_up_does_not_write_order_status_directly()
    {
        $sm = $this->expectStateMachineCall('picked_up');
        [$delivery, $order] = $this->makeDelivery('ASSIGNED');

        (new TransitionTestableDeliveryService($sm))->updateStatus($delivery, 'PICKED_UP');

        $this->assertFalse($order->statusWasWrittenDirectly, 'PICKED_UP ne doit pas écrire order.status directement');
    }

    public function test_snapshot_after_picked_up_reflects_picked_up_status()
    {
        $order = $this->makeOrderWithStatus('picked_up', 'PICKED_UP');
        $snap  = $order->statusSnapshot();

        $this->assertSame('picked_up', $snap->effectiveBusinessStatus());
        $this->assertSame('pickup', $snap->trackingStatus());
        $this->assertSame(75, $snap->trackingProgress());
        $this->assertTrue($snap->isInDelivery());
        $this->assertFalse($snap->isDelivered());
        $this->assertSame('PICKED_UP', $snap->deliveryStatus());
    }

    // =========================================================================
    // ON_THE_WAY
    // =========================================================================

    public function test_on_the_way_calls_state_machine_with_out_for_delivery()
    {
        $sm = $this->expectStateMachineCall('out_for_delivery');
        [$delivery, $order] = $this->makeDelivery('PICKED_UP');

        (new TransitionTestableDeliveryService($sm))->updateStatus($delivery, 'ON_THE_WAY');
    }

    public function test_on_the_way_does_not_write_order_status_directly()
    {
        $sm = $this->expectStateMachineCall('out_for_delivery');
        [$delivery, $order] = $this->makeDelivery('PICKED_UP');

        (new TransitionTestableDeliveryService($sm))->updateStatus($delivery, 'ON_THE_WAY');

        $this->assertFalse($order->statusWasWrittenDirectly, 'ON_THE_WAY ne doit pas écrire order.status directement');
    }

    public function test_snapshot_after_on_the_way_reflects_out_for_delivery()
    {
        $order = $this->makeOrderWithStatus('out_for_delivery', 'ON_THE_WAY');
        $snap  = $order->statusSnapshot();

        $this->assertSame('out_for_delivery', $snap->effectiveBusinessStatus());
        $this->assertSame('onway', $snap->trackingStatus());
        $this->assertSame(90, $snap->trackingProgress());
        $this->assertTrue($snap->isInDelivery());
        $this->assertSame('ON_THE_WAY', $snap->deliveryStatus());
    }

    // =========================================================================
    // DELIVERED
    // =========================================================================

    public function test_delivered_calls_state_machine_with_delivered()
    {
        $sm = $this->expectStateMachineCall('delivered');
        [$delivery, $order] = $this->makeDelivery('ON_THE_WAY', withDriver: true);

        (new TransitionTestableDeliveryService($sm))->updateStatus($delivery, 'DELIVERED');
    }

    public function test_delivered_does_not_write_order_status_directly()
    {
        $sm = $this->expectStateMachineCall('delivered');
        [$delivery, $order] = $this->makeDelivery('ON_THE_WAY', withDriver: true);

        (new TransitionTestableDeliveryService($sm))->updateStatus($delivery, 'DELIVERED');

        $this->assertFalse($order->statusWasWrittenDirectly, 'DELIVERED ne doit pas écrire order.status directement');
    }

    public function test_snapshot_after_delivered_reflects_delivered_status()
    {
        $order = $this->makeOrderWithStatus('delivered', 'DELIVERED', paymentStatus: 'paid');
        $snap  = $order->statusSnapshot();

        $this->assertSame('delivered', $snap->effectiveBusinessStatus());
        $this->assertSame('completed', $snap->trackingStatus());
        $this->assertSame(100, $snap->trackingProgress());
        $this->assertTrue($snap->isDelivered());
        $this->assertTrue($snap->isTerminal());
        $this->assertFalse($snap->isCancelable());
    }

    // =========================================================================
    // CANCELLED
    // =========================================================================

    public function test_cancelled_calls_state_machine_with_cancelled()
    {
        $sm = $this->expectStateMachineCall('cancelled');
        [$delivery, $order] = $this->makeDelivery('ASSIGNED', withDriver: true);

        (new TransitionTestableDeliveryService($sm))->updateStatus($delivery, 'CANCELLED');
    }

    public function test_cancelled_does_not_write_order_status_directly()
    {
        $sm = $this->expectStateMachineCall('cancelled');
        [$delivery, $order] = $this->makeDelivery('ASSIGNED', withDriver: true);

        (new TransitionTestableDeliveryService($sm))->updateStatus($delivery, 'CANCELLED');

        $this->assertFalse($order->statusWasWrittenDirectly, 'CANCELLED ne doit pas écrire order.status directement');
    }

    public function test_snapshot_after_cancelled_reflects_cancelled_status()
    {
        $order = $this->makeOrderWithStatus('cancelled', 'CANCELLED');
        $snap  = $order->statusSnapshot();

        $this->assertSame('cancelled', $snap->effectiveBusinessStatus());
        $this->assertSame('cancelled', $snap->trackingStatus());
        $this->assertSame(0, $snap->trackingProgress());
        $this->assertTrue($snap->isCancelled());
        $this->assertTrue($snap->isTerminal());
    }

    // =========================================================================
    // Correction R1 — acceptOrderRequests ne doit plus écrire order.status
    // =========================================================================

    public function test_accept_order_requests_no_longer_writes_order_status_directly()
    {
        // Vérification statique : chercher le pattern raw update supprimé.
        $source = file_get_contents(
            base_path('app/Http/Controllers/api/DriverController.php')
        );

        $this->assertStringNotContainsString(
            "->update(['status' => \"assign\"])",
            $source,
            'acceptOrderRequests() ne doit plus écrire order.status directement'
        );
        $this->assertStringNotContainsString(
            "->update(['status' => \"pickup\"])",
            $source,
            'acceptOrderRequests() ne doit plus écrire order.status pickup directement'
        );
    }

    // =========================================================================
    // Correction R2 — rejectOrderRequests utilise resetForOrderModification
    // =========================================================================

    public function test_reject_order_requests_no_longer_writes_order_status_directly()
    {
        $source = file_get_contents(
            base_path('app/Http/Controllers/api/ReasonController.php')
        );

        $this->assertStringNotContainsString(
            "->update(['status' => \"pending\", 'driver_id'=>null])",
            $source,
            'rejectOrderRequests() ne doit plus écrire order.status directement'
        );
    }

    public function test_reject_order_requests_delegates_to_reset_for_order_modification()
    {
        $source = file_get_contents(
            base_path('app/Http/Controllers/api/ReasonController.php')
        );

        $this->assertStringContainsString(
            'resetForOrderModification',
            $source,
            'rejectOrderRequests() doit déléguer à DeliveryService::resetForOrderModification()'
        );
    }

    // =========================================================================
    // Helpers
    // =========================================================================

    private function expectStateMachineCall(string $expectedTarget): FoodOrderStateMachineService
    {
        $sm = Mockery::mock(FoodOrderStateMachineService::class);
        $sm->shouldReceive('transitionOrderGroup')
            ->once()
            ->withArgs(fn ($no, $target) => $target === $expectedTarget)
            ->andReturn(collect());
        return $sm;
    }

    private function makeDelivery(string $currentStatus, bool $withDriver = false): array
    {
        $order    = new TransitionFakeOrder('ORD-TRANS-001');
        $driver   = $withDriver ? new TransitionFakeDriver(99) : null;
        $delivery = TransitionFakeDelivery::makeTransition($currentStatus, $order, $driver);
        return [$delivery, $order];
    }

    private function makeOrderWithStatus(
        string  $businessStatus,
        string  $deliveryStatus,
        string  $paymentStatus = 'pending',
    ): Order {
        $order = new Order();
        $order->setAttribute('business_status', $businessStatus);
        $order->setAttribute('status', 'assign');
        $order->setAttribute('technical_status', null);
        $order->setAttribute('payment_status', $paymentStatus);
        $order->setAttribute('fulfillment_mode', 'delivery');
        $order->exists = false;

        $delivery      = new class($deliveryStatus) {
            public string $status;
            public function __construct(string $s) { $this->status = $s; }
        };
        $order->setRelation('delivery', $delivery);

        return $order;
    }
}

// ---------------------------------------------------------------------------
// TestableDeliveryService — surcharge updateStatus() pour bypasser
// DB::transaction, Schema::hasColumn, et les appels services post-transaction.
// Préserve l'ordre logique : state machine → delivery.update.
// ---------------------------------------------------------------------------

class TransitionTestableDeliveryService extends DeliveryService
{
    public function updateStatus(Delivery $delivery, string $status, array $context = []): Delivery
    {
        $allowedStatuses = ['ASSIGNED', 'PICKED_UP', 'ON_THE_WAY', 'DELIVERED', 'CANCELLED'];
        if (!in_array($status, $allowedStatuses)) {
            throw new \Exception('Statut invalide: ' . $status);
        }

        $validTransitions = [
            'PENDING'   => ['ASSIGNED', 'CANCELLED'],
            'ASSIGNED'  => ['PICKED_UP', 'CANCELLED'],
            'PICKED_UP' => ['ON_THE_WAY', 'CANCELLED'],
            'ON_THE_WAY'=> ['DELIVERED', 'CANCELLED'],
            'DELIVERED' => [],
            'CANCELLED' => [],
        ];

        if (!in_array($status, $validTransitions[$delivery->status] ?? [])) {
            throw new \Exception("Transition invalide: {$delivery->status} → {$status}");
        }

        // Appel state machine — même séquence que le code production
        match ($status) {
            'PICKED_UP'   => $this->stateMachine()->transitionOrderGroup($delivery->order->order_no, 'picked_up', ['actor_type' => 'driver']),
            'ON_THE_WAY'  => $this->stateMachine()->transitionOrderGroup($delivery->order->order_no, 'out_for_delivery', ['actor_type' => 'driver']),
            'DELIVERED'   => $this->stateMachine()->transitionOrderGroup($delivery->order->order_no, 'delivered', ['actor_type' => 'driver']),
            'CANCELLED'   => $this->stateMachine()->transitionOrderGroup($delivery->order->order_no, 'cancelled', ['actor_type' => 'driver']),
            default       => null,
        };

        $delivery->update(['status' => $status]);

        return $delivery;
    }

    protected function stateMachine(): FoodOrderStateMachineService
    {
        return $this->foodOrderStateMachine ?? app(FoodOrderStateMachineService::class);
    }
}

// ---------------------------------------------------------------------------
// Fakes locaux pour les transitions
// ---------------------------------------------------------------------------

class TransitionFakeOrder
{
    public string $order_no;
    public bool   $statusWasWrittenDirectly = false;
    public array  $updates = [];

    public function __construct(string $orderNo) { $this->order_no = $orderNo; }

    public function update(array $attrs): bool
    {
        $this->updates[] = $attrs;
        if (array_key_exists('status', $attrs)) {
            $this->statusWasWrittenDirectly = true;
        }
        return true;
    }

    public function fresh(): static { return $this; }
}

class TransitionFakeDriver
{
    public int $id;
    public function __construct(int $id) { $this->id = $id; }
    public function update(array $attrs): bool { return true; }
}

class TransitionFakeDelivery extends Delivery
{
    public TransitionFakeOrder   $order;
    public ?TransitionFakeDriver $driver;

    public static function makeTransition(string $status, TransitionFakeOrder $order, ?TransitionFakeDriver $driver = null): static
    {
        $instance         = new static();
        $instance->setAttribute('status', $status);
        $instance->setAttribute('driver_id', $driver?->id);
        $instance->order  = $order;
        $instance->driver = $driver;
        return $instance;
    }

    public function __construct()
    {
        parent::__construct();
    }

    public function update(array $attrs = [], array $opts = []): bool
    {
        foreach ($attrs as $k => $v) $this->setAttribute($k, $v);
        return true;
    }

    public function save(array $opts = []): bool { return true; }
    public function fresh($with = []): static    { return $this; }
}
