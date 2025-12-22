<?php

namespace Database\Factories;

use App\Domain\Colis\Models\ShipmentAddress;
use App\Domain\Colis\Models\Shipment;
use Illuminate\Database\Eloquent\Factories\Factory;

class ShipmentAddressFactory extends Factory
{
    protected $model = ShipmentAddress::class;

    public function definition(): array
    {
        return [
            'shipment_id' => Shipment::factory(),
            'type' => 'pickup',
            'full_name' => $this->faker->name,
            'phone' => '24206' . $this->faker->numerify('#######'),
            'city' => 'Brazzaville',
            'district' => $this->faker->randomElement(['Poto-Poto', 'Moungali', 'Bacongo', 'Talangaï']),
            'address_line' => $this->faker->streetAddress,
            'landmark' => 'Près de ' . $this->faker->company,
            'lat' => $this->faker->latitude(-4.3, -4.2),
            'lng' => $this->faker->longitude(15.2, 15.3),
        ];
    }
}

