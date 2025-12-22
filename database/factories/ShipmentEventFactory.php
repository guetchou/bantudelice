<?php

namespace Database\Factories;

use App\Domain\Colis\Models\ShipmentEvent;
use App\Domain\Colis\Models\Shipment;
use App\Domain\Colis\Enums\ShipmentStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

class ShipmentEventFactory extends Factory
{
    protected $model = ShipmentEvent::class;

    public function definition(): array
    {
        return [
            'shipment_id' => Shipment::factory(),
            'status' => ShipmentStatus::CREATED,
            'actor_type' => 'customer',
            'actor_id' => null,
            'notes' => 'Envoi créé par le client',
        ];
    }
}

