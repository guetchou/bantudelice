<?php

namespace Tests\Unit;

use App\Order;
use Tests\TestCase;

/**
 * Teste la logique de résolution du statut dans Order.php, sans base de données.
 *
 * Vérifie la chaîne de priorité :
 *   delivery.status (si chargée) > business_status > legacy status
 *
 * Teste aussi que statusSnapshot() retourne un OrderStatusSnapshot
 * cohérent avec les méthodes existantes.
 */
class OrderStatusResolutionTest extends TestCase
{
    // =========================================================================
    // resolveEffectiveBusinessStatus — chaîne de priorité
    // =========================================================================

    public function test_prefers_delivery_status_over_business_status()
    {
        $order = $this->makeOrder([
            'business_status'  => 'accepted',
            'fulfillment_mode' => 'delivery',
        ]);
        $order->setRelation('delivery', $this->makeDelivery('ASSIGNED'));

        // delivery.status=ASSIGNED prime sur business_status='accepted'
        $this->assertSame('driver_assigned', $order->resolveEffectiveBusinessStatus());
    }

    public function test_ignores_delivery_dispatching_and_falls_back_to_business_status()
    {
        // delivery.status=PENDING → mapDeliveryStatusToBusiness → 'dispatching'
        // La règle : si mappé = 'dispatching', on ignore le delivery et on prend business_status
        $order = $this->makeOrder([
            'business_status'  => 'driver_assigned',
            'fulfillment_mode' => 'delivery',
        ]);
        $order->setRelation('delivery', $this->makeDelivery('PENDING'));

        $this->assertSame('driver_assigned', $order->resolveEffectiveBusinessStatus());
    }

    public function test_falls_back_to_business_status_when_delivery_not_loaded()
    {
        $order = $this->makeOrder([
            'business_status'  => 'in_kitchen',
            'fulfillment_mode' => 'delivery',
        ]);
        // Pas de relation delivery chargée

        $this->assertSame('in_kitchen', $order->resolveEffectiveBusinessStatus());
    }

    public function test_falls_back_to_legacy_status_when_business_status_empty()
    {
        $order = $this->makeOrder([
            'status'           => 'prepairing',
            'business_status'  => null,
            'fulfillment_mode' => 'delivery',
        ]);

        $this->assertSame('in_kitchen', $order->resolveEffectiveBusinessStatus());
    }

    public function test_legacy_pending_maps_to_pending_restaurant_acceptance()
    {
        $order = $this->makeOrder([
            'status'          => 'pending',
            'business_status' => null,
        ]);

        $this->assertSame('pending_restaurant_acceptance', $order->resolveEffectiveBusinessStatus());
    }

    public function test_pickup_order_ignores_delivery_status()
    {
        $order = $this->makeOrder([
            'business_status'  => 'accepted',
            'fulfillment_mode' => 'pickup',
        ]);
        $order->setRelation('delivery', $this->makeDelivery('ASSIGNED'));

        // Pour un retrait, delivery.status est ignoré
        $this->assertSame('accepted', $order->resolveEffectiveBusinessStatus());
    }

    // =========================================================================
    // resolveTrackingStatus
    // =========================================================================

    public function test_tracking_status_pending_when_pending_restaurant_acceptance()
    {
        $order = $this->makeOrder(['business_status' => 'pending_restaurant_acceptance']);

        $this->assertSame('pending', $order->resolveTrackingStatus());
    }

    public function test_tracking_status_pending_when_accepted()
    {
        // 'accepted' n'est pas encore en préparation — la préparation commence à
        // 'in_kitchen' seulement (cf. correction tracking_status).
        $order = $this->makeOrder(['business_status' => 'accepted']);

        $this->assertSame('pending', $order->resolveTrackingStatus());
    }

    public function test_tracking_status_prepairing_when_in_kitchen()
    {
        $order = $this->makeOrder(['business_status' => 'in_kitchen']);

        $this->assertSame('prepairing', $order->resolveTrackingStatus());
    }

    public function test_tracking_status_assign_when_driver_assigned()
    {
        $order = $this->makeOrder(['business_status' => 'driver_assigned', 'fulfillment_mode' => 'delivery']);

        $this->assertSame('assign', $order->resolveTrackingStatus());
    }

    public function test_tracking_status_pickup_when_picked_up()
    {
        $order = $this->makeOrder(['business_status' => 'picked_up', 'fulfillment_mode' => 'delivery']);

        $this->assertSame('pickup', $order->resolveTrackingStatus());
    }

    public function test_tracking_status_onway_when_out_for_delivery()
    {
        $order = $this->makeOrder(['business_status' => 'out_for_delivery', 'fulfillment_mode' => 'delivery']);

        $this->assertSame('onway', $order->resolveTrackingStatus());
    }

    public function test_tracking_status_completed_when_delivered()
    {
        $order = $this->makeOrder(['business_status' => 'delivered', 'fulfillment_mode' => 'delivery']);

        $this->assertSame('completed', $order->resolveTrackingStatus());
    }

    public function test_tracking_status_cancelled_when_cancelled()
    {
        $order = $this->makeOrder(['business_status' => 'cancelled']);

        $this->assertSame('cancelled', $order->resolveTrackingStatus());
    }

    // =========================================================================
    // resolveTrackingProgress
    // =========================================================================

    public function test_tracking_progress_0_for_pending()
    {
        $order = $this->makeOrder(['business_status' => 'pending_restaurant_acceptance']);

        $this->assertSame(0, $order->resolveTrackingProgress());
    }

    public function test_tracking_progress_25_for_prepairing()
    {
        $order = $this->makeOrder(['business_status' => 'in_kitchen']);

        $this->assertSame(25, $order->resolveTrackingProgress());
    }

    public function test_tracking_progress_100_for_delivered()
    {
        $order = $this->makeOrder(['business_status' => 'delivered', 'fulfillment_mode' => 'delivery']);

        $this->assertSame(100, $order->resolveTrackingProgress());
    }

    public function test_tracking_progress_0_for_cancelled()
    {
        $order = $this->makeOrder(['business_status' => 'cancelled']);

        $this->assertSame(0, $order->resolveTrackingProgress());
    }

    // =========================================================================
    // statusSnapshot() — cohérence avec les méthodes existantes
    // =========================================================================

    public function test_status_snapshot_coherent_with_existing_methods()
    {
        $order = $this->makeOrder([
            'business_status'  => 'out_for_delivery',
            'technical_status' => 'dispatch_retry',
            'payment_status'   => 'paid',
            'fulfillment_mode' => 'delivery',
        ]);
        $order->setRelation('delivery', $this->makeDelivery('ON_THE_WAY'));

        $snap = $order->statusSnapshot();

        $this->assertSame($order->resolveEffectiveBusinessStatus(), $snap->effectiveBusinessStatus());
        $this->assertSame($order->resolveTrackingStatus(), $snap->trackingStatus());
        $this->assertSame($order->resolveTrackingProgress(), $snap->trackingProgress());
        $this->assertSame('dispatch_retry', $snap->technicalStatus());
        $this->assertSame('ON_THE_WAY', $snap->deliveryStatus());
        $this->assertTrue($snap->isPaid());
        $this->assertFalse($snap->isCancelable());
        $this->assertTrue($snap->isInDelivery());
        $this->assertFalse($snap->isDelivered());
        $this->assertFalse($snap->requiresManualReview());
    }

    public function test_status_snapshot_pickup_order()
    {
        $order = $this->makeOrder([
            'business_status'  => 'ready_for_pickup',
            'payment_status'   => 'paid',
            'fulfillment_mode' => 'pickup',
        ]);
        $order->exists = false;

        $snap = $order->statusSnapshot();

        $this->assertTrue($snap->isPickup());
        $this->assertTrue($snap->isDelivery() === false);
        $this->assertSame('ready_for_pickup', $snap->effectiveBusinessStatus());
        $this->assertTrue($snap->isReadyForDelivery());
        $this->assertNull($snap->deliveryStatus());
    }

    public function test_status_snapshot_cancelled_order_is_terminal()
    {
        $order = $this->makeOrder(['business_status' => 'cancelled']);
        $order->exists = false;

        $snap = $order->statusSnapshot();

        $this->assertTrue($snap->isCancelled());
        $this->assertTrue($snap->isTerminal());
        $this->assertFalse($snap->isCancelable());
    }

    public function test_status_snapshot_fraud_hold_requires_manual_review()
    {
        $order = $this->makeOrder([
            'business_status'  => 'pending_restaurant_acceptance',
            'technical_status' => 'fraud_hold',
        ]);
        $order->exists = false;

        $snap = $order->statusSnapshot();

        $this->assertTrue($snap->requiresManualReview());
    }

    // =========================================================================
    // Helpers
    // =========================================================================

    private function makeOrder(array $attributes = []): Order
    {
        $order = new Order();
        $defaults = [
            'status'           => 'pending',
            'business_status'  => null,
            'technical_status' => null,
            'payment_status'   => 'pending',
            'fulfillment_mode' => 'delivery',
        ];

        foreach (array_merge($defaults, $attributes) as $key => $value) {
            $order->setAttribute($key, $value);
        }

        return $order;
    }

    private function makeDelivery(string $status): object
    {
        return new class($status) {
            public string $status;
            public function __construct(string $s) { $this->status = $s; }
        };
    }
}
