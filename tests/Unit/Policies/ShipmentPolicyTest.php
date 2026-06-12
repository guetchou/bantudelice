<?php

namespace Tests\Unit\Policies;

use App\Driver;
use App\Policies\ShipmentPolicy;
use App\Domain\Colis\Models\Shipment;
use App\User;
use Tests\TestCase;

class ShipmentPolicyTest extends TestCase
{
    public function test_admin_user_can_view_any_shipment(): void
    {
        $policy = new ShipmentPolicy();
        $admin = User::factory()->make(['id' => 1, 'type' => 'admin']);
        $shipment = Shipment::factory()->make(['customer_id' => 99]);

        $this->assertTrue($policy->view($admin, $shipment));
    }

    public function test_customer_can_only_view_own_shipment(): void
    {
        $policy = new ShipmentPolicy();
        $customer = User::factory()->make(['id' => 10, 'type' => 'user']);
        $ownedShipment = Shipment::factory()->make(['customer_id' => 10]);
        $foreignShipment = Shipment::factory()->make(['customer_id' => 99]);

        $this->assertTrue($policy->view($customer, $ownedShipment));
        $this->assertFalse($policy->view($customer, $foreignShipment));
    }

    public function test_courier_update_is_bound_to_assigned_driver_only(): void
    {
        $policy = new ShipmentPolicy();
        $courier = Driver::factory()->make(['id' => 15]);
        $shipment = Shipment::factory()->make(['customer_id' => 99, 'assigned_courier_id' => 15]);
        $foreignShipment = Shipment::factory()->make(['customer_id' => 99, 'assigned_courier_id' => 16]);

        $this->assertTrue($policy->updateAsCourier($courier, $shipment));
        $this->assertFalse($policy->updateAsCourier($courier, $foreignShipment));
    }
}
