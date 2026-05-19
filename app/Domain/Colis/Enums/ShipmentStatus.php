<?php

namespace App\Domain\Colis\Enums;

enum ShipmentStatus: string
{
    case CREATED = 'created';
    case PRICED = 'priced';
    case PAID = 'paid';
    case PICKED_UP = 'picked_up';
    case IN_TRANSIT = 'in_transit';
    case AT_RELAY = 'at_relay';
    case OUT_FOR_DELIVERY = 'out_for_delivery';
    case DELIVERED = 'delivered';
    case FAILED = 'failed';
    case RETURNED = 'returned';
    case CANCELED = 'canceled';
    case DAMAGED = 'damaged';
    case LOST = 'lost';

    public function label(): string
    {
        return match($this) {
            self::CREATED => 'Créé',
            self::PRICED => 'Tarifié',
            self::PAID => 'Payé',
            self::PICKED_UP => 'Ramassé',
            self::IN_TRANSIT => 'En transit',
            self::AT_RELAY => 'Au point relais',
            self::OUT_FOR_DELIVERY => 'En cours de livraison',
            self::DELIVERED => 'Livré',
            self::FAILED => 'Échec de livraison',
            self::RETURNED => 'Retourné à l\'expéditeur',
            self::CANCELED => 'Annulé',
            self::DAMAGED => 'Endommagé',
            self::LOST => 'Perdu',
        };
    }
}

