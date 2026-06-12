<?php

namespace Tests\Unit;

use App\Domain\Order\ValueObjects\OrderStatusSnapshot;
use Tests\TestCase;

/**
 * Teste OrderStatusSnapshot en isolation complète — pas de base de données.
 *
 * Chaque test construit un snapshot directement avec les valeurs voulues
 * et vérifie les prédicats métier. La logique de résolution (priorité
 * delivery > business_status > legacy) est testée via Order.php
 * séparément (OrderStatusResolutionTest).
 */
class OrderStatusSnapshotTest extends TestCase
{
    // =========================================================================
    // Construction et accesseurs de base
    // =========================================================================

    public function test_exposes_effective_business_status()
    {
        $s = $this->make(effectiveBusinessStatus: 'accepted');

        $this->assertSame('accepted', $s->effectiveBusinessStatus());
    }

    public function test_exposes_tracking_status()
    {
        $s = $this->make(trackingStatus: 'prepairing');

        $this->assertSame('prepairing', $s->trackingStatus());
    }

    public function test_exposes_tracking_progress()
    {
        $s = $this->make(trackingStatus: 'prepairing', trackingProgress: 25);

        $this->assertSame(25, $s->trackingProgress());
    }

    public function test_exposes_technical_status_when_present()
    {
        $s = $this->make(technicalStatus: 'dispatch_retry');

        $this->assertSame('dispatch_retry', $s->technicalStatus());
    }

    public function test_technical_status_is_null_by_default()
    {
        $s = $this->make();

        $this->assertNull($s->technicalStatus());
    }

    public function test_exposes_delivery_status()
    {
        $s = $this->make(deliveryStatus: 'ASSIGNED');

        $this->assertSame('ASSIGNED', $s->deliveryStatus());
    }

    public function test_delivery_status_is_null_for_pickup()
    {
        $s = $this->make(isPickup: true, deliveryStatus: null);

        $this->assertNull($s->deliveryStatus());
    }

    // =========================================================================
    // isPaid
    // =========================================================================

    public function test_is_paid_when_payment_status_is_paid()
    {
        $s = $this->make(paymentStatus: 'paid');

        $this->assertTrue($s->isPaid());
    }

    public function test_is_not_paid_when_payment_status_is_pending()
    {
        $s = $this->make(paymentStatus: 'pending');

        $this->assertFalse($s->isPaid());
    }

    public function test_is_not_paid_when_payment_status_is_failed()
    {
        $s = $this->make(paymentStatus: 'failed');

        $this->assertFalse($s->isPaid());
    }

    // =========================================================================
    // isCancelable
    // =========================================================================

    public function test_is_cancelable_when_pending_restaurant_acceptance()
    {
        $s = $this->make(effectiveBusinessStatus: 'pending_restaurant_acceptance');

        $this->assertTrue($s->isCancelable());
    }

    public function test_is_cancelable_when_accepted()
    {
        $s = $this->make(effectiveBusinessStatus: 'accepted');

        $this->assertTrue($s->isCancelable());
    }

    public function test_is_not_cancelable_when_in_kitchen()
    {
        $s = $this->make(effectiveBusinessStatus: 'in_kitchen');

        $this->assertFalse($s->isCancelable());
    }

    public function test_is_not_cancelable_when_delivered()
    {
        $s = $this->make(effectiveBusinessStatus: 'delivered');

        $this->assertFalse($s->isCancelable());
    }

    // =========================================================================
    // isReadyForDelivery
    // =========================================================================

    public function test_is_ready_for_delivery_when_ready_for_pickup()
    {
        $s = $this->make(effectiveBusinessStatus: 'ready_for_pickup');

        $this->assertTrue($s->isReadyForDelivery());
    }

    public function test_is_ready_for_delivery_when_dispatching()
    {
        $s = $this->make(effectiveBusinessStatus: 'dispatching');

        $this->assertTrue($s->isReadyForDelivery());
    }

    public function test_is_not_ready_for_delivery_when_accepted()
    {
        $s = $this->make(effectiveBusinessStatus: 'accepted');

        $this->assertFalse($s->isReadyForDelivery());
    }

    // =========================================================================
    // isInDelivery
    // =========================================================================

    public function test_is_in_delivery_when_driver_assigned()
    {
        $s = $this->make(effectiveBusinessStatus: 'driver_assigned');

        $this->assertTrue($s->isInDelivery());
    }

    public function test_is_in_delivery_when_out_for_delivery()
    {
        $s = $this->make(effectiveBusinessStatus: 'out_for_delivery');

        $this->assertTrue($s->isInDelivery());
    }

    public function test_is_in_delivery_when_delivery_attempt_failed()
    {
        $s = $this->make(effectiveBusinessStatus: 'delivery_attempt_failed');

        $this->assertTrue($s->isInDelivery());
    }

    public function test_is_not_in_delivery_when_delivered()
    {
        $s = $this->make(effectiveBusinessStatus: 'delivered');

        $this->assertFalse($s->isInDelivery());
    }

    // =========================================================================
    // isDelivered
    // =========================================================================

    public function test_is_delivered_when_status_delivered()
    {
        $s = $this->make(effectiveBusinessStatus: 'delivered');

        $this->assertTrue($s->isDelivered());
    }

    public function test_is_delivered_when_status_closed()
    {
        $s = $this->make(effectiveBusinessStatus: 'closed');

        $this->assertTrue($s->isDelivered());
    }

    public function test_is_not_delivered_when_out_for_delivery()
    {
        $s = $this->make(effectiveBusinessStatus: 'out_for_delivery');

        $this->assertFalse($s->isDelivered());
    }

    // =========================================================================
    // isCancelled
    // =========================================================================

    public function test_is_cancelled_when_cancelled()
    {
        $s = $this->make(effectiveBusinessStatus: 'cancelled');

        $this->assertTrue($s->isCancelled());
    }

    public function test_is_cancelled_when_refunded()
    {
        $s = $this->make(effectiveBusinessStatus: 'refunded');

        $this->assertTrue($s->isCancelled());
    }

    // =========================================================================
    // isIncidentOpen
    // =========================================================================

    public function test_is_incident_open()
    {
        $s = $this->make(effectiveBusinessStatus: 'incident_open');

        $this->assertTrue($s->isIncidentOpen());
    }

    public function test_is_not_incident_open_when_delivered()
    {
        $s = $this->make(effectiveBusinessStatus: 'delivered');

        $this->assertFalse($s->isIncidentOpen());
    }

    // =========================================================================
    // requiresManualReview
    // =========================================================================

    public function test_requires_manual_review_for_fraud_hold()
    {
        $s = $this->make(technicalStatus: 'fraud_hold');

        $this->assertTrue($s->requiresManualReview());
    }

    public function test_requires_manual_review_for_restaurant_timeout()
    {
        $s = $this->make(technicalStatus: 'restaurant_timeout');

        $this->assertTrue($s->requiresManualReview());
    }

    public function test_requires_manual_review_for_driver_timeout()
    {
        $s = $this->make(technicalStatus: 'driver_timeout');

        $this->assertTrue($s->requiresManualReview());
    }

    public function test_does_not_require_manual_review_when_technical_status_null()
    {
        $s = $this->make(technicalStatus: null);

        $this->assertFalse($s->requiresManualReview());
    }

    public function test_does_not_require_manual_review_for_dispatch_retry()
    {
        // dispatch_retry est opérationnel, pas une revue manuelle
        $s = $this->make(technicalStatus: 'dispatch_retry');

        $this->assertFalse($s->requiresManualReview());
    }

    // =========================================================================
    // isTerminal
    // =========================================================================

    public function test_is_terminal_when_delivered()
    {
        $this->assertTrue($this->make(effectiveBusinessStatus: 'delivered')->isTerminal());
    }

    public function test_is_terminal_when_cancelled()
    {
        $this->assertTrue($this->make(effectiveBusinessStatus: 'cancelled')->isTerminal());
    }

    public function test_is_terminal_when_picked_up_by_customer()
    {
        $this->assertTrue($this->make(effectiveBusinessStatus: 'picked_up_by_customer')->isTerminal());
    }

    public function test_is_terminal_when_no_show()
    {
        $this->assertTrue($this->make(effectiveBusinessStatus: 'no_show')->isTerminal());
    }

    public function test_is_not_terminal_when_in_kitchen()
    {
        $this->assertFalse($this->make(effectiveBusinessStatus: 'in_kitchen')->isTerminal());
    }

    // =========================================================================
    // canBeModified
    // =========================================================================

    public function test_can_be_modified_when_flag_true()
    {
        $s = $this->make(canBeModified: true);

        $this->assertTrue($s->canBeModified());
    }

    public function test_cannot_be_modified_when_flag_false()
    {
        $s = $this->make(canBeModified: false);

        $this->assertFalse($s->canBeModified());
    }

    // =========================================================================
    // toArray — clés attendues
    // =========================================================================

    public function test_to_array_contains_all_expected_keys()
    {
        $arr = $this->make()->toArray();

        $this->assertArrayHasKey('effective_business_status', $arr);
        $this->assertArrayHasKey('tracking_status', $arr);
        $this->assertArrayHasKey('tracking_progress', $arr);
        $this->assertArrayHasKey('technical_status', $arr);
        $this->assertArrayHasKey('delivery_status', $arr);
        $this->assertArrayHasKey('payment_status', $arr);
        $this->assertArrayHasKey('is_pickup', $arr);
        $this->assertArrayHasKey('is_paid', $arr);
        $this->assertArrayHasKey('is_cancelable', $arr);
        $this->assertArrayHasKey('is_ready_for_delivery', $arr);
        $this->assertArrayHasKey('is_in_delivery', $arr);
        $this->assertArrayHasKey('is_delivered', $arr);
        $this->assertArrayHasKey('is_cancelled', $arr);
        $this->assertArrayHasKey('is_terminal', $arr);
        $this->assertArrayHasKey('requires_manual_review', $arr);
    }

    // =========================================================================
    // Helpers
    // =========================================================================

    private function make(
        string  $effectiveBusinessStatus = 'pending_restaurant_acceptance',
        string  $trackingStatus          = 'pending',
        int     $trackingProgress        = 0,
        ?string $technicalStatus         = null,
        ?string $deliveryStatus          = null,
        string  $paymentStatus           = 'pending',
        bool    $isPickup                = false,
        bool    $canBeModified           = true,
    ): OrderStatusSnapshot {
        return new OrderStatusSnapshot(
            effectiveBusinessStatus: $effectiveBusinessStatus,
            trackingStatus:          $trackingStatus,
            trackingProgress:        $trackingProgress,
            technicalStatus:         $technicalStatus,
            deliveryStatus:          $deliveryStatus,
            paymentStatus:           $paymentStatus,
            isPickup:                $isPickup,
            canBeModified:           $canBeModified,
        );
    }
}
