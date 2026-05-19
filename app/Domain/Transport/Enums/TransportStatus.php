<?php

namespace App\Domain\Transport\Enums;

enum TransportStatus: string
{
    case DRAFT = 'draft';
    case REQUESTED = 'requested';
    case PUBLISHED = 'published'; // For carpool
    case OFFERED = 'offered';
    case ASSIGNED = 'assigned';
    case BOOKED = 'booked';
    case CONFIRMED = 'confirmed';
    case DRIVER_ARRIVING = 'driver_arriving';
    case PICKED_UP = 'picked_up';
    case IN_PROGRESS = 'in_progress';
    case RETURNED = 'returned'; // For rental
    case INSPECTED = 'inspected'; // For rental
    case COMPLETED = 'completed';
    case PAID = 'paid';
    case CANCELLED = 'cancelled';
    case CLOSED = 'closed';

    public function label(): string
    {
        return match($this) {
            self::DRAFT => 'Brouillon',
            self::REQUESTED => 'Demandé',
            self::PUBLISHED => 'Publié',
            self::OFFERED => 'Offre envoyée',
            self::ASSIGNED => 'Assigné',
            self::BOOKED => 'Réservé',
            self::CONFIRMED => 'Confirmé',
            self::DRIVER_ARRIVING => 'Chauffeur en route',
            self::PICKED_UP => 'Pris en charge',
            self::IN_PROGRESS => 'En cours',
            self::RETURNED => 'Retourné',
            self::INSPECTED => 'Inspecté',
            self::COMPLETED => 'Terminé',
            self::PAID => 'Payé',
            self::CANCELLED => 'Annulé',
            self::CLOSED => 'Clôturé',
        };
    }
}

