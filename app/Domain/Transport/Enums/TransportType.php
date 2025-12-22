<?php

namespace App\Domain\Transport\Enums;

enum TransportType: string
{
    case TAXI = 'taxi';
    case CARPOOL = 'carpool';
    case RENTAL = 'rental';

    public function label(): string
    {
        return match($this) {
            self::TAXI => 'Taxi (Ride-hailing)',
            self::CARPOOL => 'Covoiturage (Ride-sharing)',
            self::RENTAL => 'Location de voiture (Rental)',
        };
    }
}

