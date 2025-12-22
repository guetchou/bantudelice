<?php

namespace Database\Factories;

use App\Domain\Colis\Models\Shipment;
use App\Domain\Colis\Enums\ShipmentStatus;
use App\User;
use App\Driver;
use Illuminate\Database\Eloquent\Factories\Factory;

class ShipmentFactory extends Factory
{
    protected $model = Shipment::class;

    public function definition(): array
    {
        return [
            'uuid' => $this->faker->uuid(),
            'tracking_number' => 'BD-CG-' . now()->format('Ym') . '-' . strtoupper($this->faker->unique()->bothify('?????')),
            'customer_id' => User::factory(),
            'status' => ShipmentStatus::CREATED,
            'service_level' => $this->faker->randomElement(['standard', 'express']),
            'pickup_type' => 'door',
            'dropoff_type' => 'door',
            'declared_value' => $this->faker->randomFloat(2, 5000, 50000),
            'cod_amount' => $this->faker->randomElement([0, 10000, 15000]),
            'currency' => 'XAF',
            'distance_km' => $this->faker->randomFloat(2, 1, 50),
            'weight_kg' => $this->faker->randomFloat(2, 0.5, 30),
            'price_breakdown' => ['base_price' => 1500],
            'total_price' => 1500,
            'payment_status' => 'unpaid',
            'assigned_courier_id' => null,
        ];
    }
}

