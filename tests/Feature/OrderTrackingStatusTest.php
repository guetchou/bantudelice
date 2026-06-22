<?php

namespace Tests\Feature;

use App\Order;
use Tests\TestCase;

/**
 * Projection business_status -> tracking_status (Order::resolveTrackingStatus()).
 * Couvre explicitement la correction du mapping (confirmed/accepted_awaiting_payment
 * ne doivent pas être traités comme "prepairing" — la préparation commence à in_kitchen).
 */
class OrderTrackingStatusTest extends TestCase
{
    private function makeOrder(string $businessStatus, string $fulfillmentMode = 'delivery'): Order
    {
        $order = new Order();
        $order->setAttribute('status', 'pending');
        $order->setAttribute('business_status', $businessStatus);
        $order->setAttribute('fulfillment_mode', $fulfillmentMode);
        $order->exists = false;

        return $order;
    }

    /**
     * @dataProvider deliveryMappingProvider
     */
    public function test_delivery_flow_tracking_status_mapping(string $businessStatus, string $expected): void
    {
        $order = $this->makeOrder($businessStatus, 'delivery');

        $this->assertSame($expected, $order->resolveTrackingStatus());
    }

    public static function deliveryMappingProvider(): array
    {
        return [
            'pending_restaurant_acceptance' => ['pending_restaurant_acceptance', 'pending'],
            'accepted_awaiting_payment'     => ['accepted_awaiting_payment', 'pending'],
            'confirmed'                     => ['confirmed', 'pending'],
            'in_kitchen'                    => ['in_kitchen', 'prepairing'],
            'ready_for_pickup'              => ['ready_for_pickup', 'assign'],
            'dispatching'                   => ['dispatching', 'assign'],
            'driver_assigned'               => ['driver_assigned', 'assign'],
            'driver_arrived_at_restaurant'  => ['driver_arrived_at_restaurant', 'assign'],
            'picked_up'                     => ['picked_up', 'pickup'],
            'out_for_delivery'              => ['out_for_delivery', 'onway'],
            'delivery_attempt_failed'       => ['delivery_attempt_failed', 'onway'],
            'incident_open'                 => ['incident_open', 'onway'],
            'delivered'                     => ['delivered', 'completed'],
            'closed'                        => ['closed', 'completed'],
            'cancelled'                     => ['cancelled', 'cancelled'],
            'refunded'                      => ['refunded', 'cancelled'],
        ];
    }

    /**
     * @dataProvider pickupMappingProvider
     */
    public function test_pickup_flow_tracking_status_mapping(string $businessStatus, string $expected): void
    {
        $order = $this->makeOrder($businessStatus, 'pickup');

        $this->assertSame($expected, $order->resolveTrackingStatus());
    }

    public static function pickupMappingProvider(): array
    {
        return [
            'pending_restaurant_acceptance' => ['pending_restaurant_acceptance', 'pending'],
            'accepted_awaiting_payment'     => ['accepted_awaiting_payment', 'pending'],
            'confirmed'                     => ['confirmed', 'pending'],
            'in_kitchen'                    => ['in_kitchen', 'prepairing'],
            'ready_for_pickup'              => ['ready_for_pickup', 'assign'],
            'customer_arrived'              => ['customer_arrived', 'assign'],
            'picked_up_by_customer'         => ['picked_up_by_customer', 'completed'],
            'closed'                        => ['closed', 'completed'],
            'no_show'                       => ['no_show', 'cancelled'],
            'cancelled'                     => ['cancelled', 'cancelled'],
        ];
    }

    public function test_confirmed_is_not_treated_as_prepairing(): void
    {
        $order = $this->makeOrder('confirmed', 'delivery');

        $this->assertNotSame('prepairing', $order->resolveTrackingStatus());
        $this->assertSame('pending', $order->resolveTrackingStatus());
    }

    public function test_preparation_starts_only_at_in_kitchen(): void
    {
        $confirmed = $this->makeOrder('confirmed', 'delivery');
        $inKitchen = $this->makeOrder('in_kitchen', 'delivery');

        $this->assertSame('pending', $confirmed->resolveTrackingStatus());
        $this->assertSame('prepairing', $inKitchen->resolveTrackingStatus());
    }
}
