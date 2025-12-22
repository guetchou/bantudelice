<?php

namespace Database\Factories;

use App\Domain\Transport\Models\TransportBooking;
use App\Domain\Transport\Enums\TransportType;
use App\Domain\Transport\Enums\TransportStatus;
use App\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class TransportBookingFactory extends Factory
{
    protected $model = TransportBooking::class;

    public function definition()
    {
        return [
            'uuid' => (string) Str::uuid(),
            'booking_no' => 'TR-' . strtoupper(Str::random(8)),
            'type' => TransportType::TAXI,
            'user_id' => User::factory(),
            'pickup_address' => $this->faker->address,
            'pickup_lat' => $this->faker->latitude,
            'pickup_lng' => $this->faker->longitude,
            'dropoff_address' => $this->faker->address,
            'dropoff_lat' => $this->faker->latitude,
            'dropoff_lng' => $this->faker->longitude,
            'estimated_price' => $this->faker->numberBetween(1000, 10000),
            'status' => TransportStatus::REQUESTED,
            'payment_method' => 'cash',
            'payment_status' => 'pending',
        ];
    }
}

