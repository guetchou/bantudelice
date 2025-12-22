<?php

namespace App\Domain\Colis\Services;

use App\Domain\Colis\Models\Shipment;
use App\Driver;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Log;

class ShipmentAssignmentService
{
    /**
     * Assigner manuellement un coursier
     */
    public function assignToCourier(Shipment $shipment, int $courierId): bool
    {
        $courier = Driver::findOrFail($courierId);
        
        $shipment->update([
            'assigned_courier_id' => $courierId
        ]);

        $shipment->events()->create([
            'status' => $shipment->status,
            'actor_type' => 'admin',
            'actor_id' => auth()->id(),
            'notes' => "Coursier {$courier->name} assigné manuellement.",
        ]);

        // Notification (si service existant)
        try {
            // Simulation notification push
            Log::info("Notification envoyée au coursier {$courier->id} pour le colis {$shipment->tracking_number}");
        } catch (\Exception $e) {
            Log::error("Erreur notification attribution: " . $e->getMessage());
        }

        return true;
    }

    /**
     * Logique d'auto-assignation (Nearest Driver)
     */
    public function autoAssign(Shipment $shipment): ?Driver
    {
        // Pour le MVP, on prend le premier coursier disponible
        // Une logique plus complexe utiliserait GeolocationService
        $courier = Driver::where('status', 1)->first();

        if ($courier) {
            $this->assignToCourier($shipment, $courier->id);
            return $courier;
        }

        return null;
    }
}

