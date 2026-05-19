<?php

namespace Tests\Feature\Colis;

use App\Domain\Colis\Enums\ShipmentStatus;
use App\Domain\Colis\Models\Shipment;
use App\Domain\Colis\Services\ShipmentStateMachine;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Exception;

class ShipmentStateMachineTest extends TestCase
{
    use RefreshDatabase;

    private ShipmentStateMachine $stateMachine;

    protected function setUp(): void
    {
        parent::setUp();
        $this->stateMachine = new ShipmentStateMachine();
    }

    public function test_valid_transition()
    {
        $shipment = Shipment::factory()->create(['status' => ShipmentStatus::CREATED]);

        $this->stateMachine->transitionTo($shipment, ShipmentStatus::PRICED);

        $this->assertEquals(ShipmentStatus::PRICED, $shipment->fresh()->status);
        $this->assertDatabaseHas('shipment_events', [
            'shipment_id' => $shipment->id,
            'status' => ShipmentStatus::PRICED->value,
        ]);
    }

    public function test_invalid_transition_throws_exception()
    {
        $shipment = Shipment::factory()->create(['status' => ShipmentStatus::CREATED]);

        $this->expectException(Exception::class);
        $this->stateMachine->transitionTo($shipment, ShipmentStatus::DELIVERED);
    }

    public function test_delivered_status_sets_timestamp()
    {
        // On doit passer par le cycle normal pour arriver à DELIVERED ou tricher sur le statut initial pour le test
        $shipment = Shipment::factory()->create(['status' => ShipmentStatus::OUT_FOR_DELIVERY]);

        $this->stateMachine->transitionTo($shipment, ShipmentStatus::DELIVERED);

        $this->assertNotNull($shipment->fresh()->delivered_at);
    }
}

