<?php

namespace App\Http\Controllers\Api\V1\Colis;

use App\Http\Controllers\Controller;
use App\Domain\Colis\Models\Shipment;
use Illuminate\Http\JsonResponse;

class TrackingController extends Controller
{
    public function __invoke(string $trackingNumber): JsonResponse
    {
        $shipment = Shipment::where('tracking_number', $trackingNumber)
            ->with(['events' => function($query) {
                $query->orderBy('created_at', 'desc');
            }, 'addresses', 'courier'])
            ->firstOrFail();

        $pickup = $shipment->addresses->where('type', 'pickup')->first();
        $dropoff = $shipment->addresses->where('type', 'dropoff')->first();

        // Récupérer la position GPS du livreur si en cours de livraison
        $driverLocation = null;
        if ($shipment->courier && ($shipment->status->value === 'out_for_delivery' || $shipment->status->value === 'in_transit')) {
            $latestLocation = \App\DriverLocation::where('driver_id', $shipment->courier->id)
                ->orderBy('timestamp', 'desc')
                ->first();
            
            if ($latestLocation) {
                $driverLocation = [
                    'lat' => (float) $latestLocation->latitude,
                    'lng' => (float) $latestLocation->longitude,
                    'timestamp' => $latestLocation->timestamp->toIso8601String(),
                ];
            } elseif ($shipment->courier->latitude && $shipment->courier->longitude) {
                $driverLocation = [
                    'lat' => (float) $shipment->courier->latitude,
                    'lng' => (float) $shipment->courier->longitude,
                    'timestamp' => $shipment->courier->updated_at->toIso8601String(),
                ];
            }
        }

        // Données limitées pour le tracking public mais incluant la carte si besoin
        return response()->json([
            'tracking_number' => $shipment->tracking_number,
            'status' => $shipment->status,
            'status_label' => $shipment->status->label(),
            'locations' => [
                'pickup' => $pickup ? ['lat' => $pickup->lat, 'lng' => $pickup->lng, 'address' => $pickup->address_line] : null,
                'dropoff' => $dropoff ? ['lat' => $dropoff->lat, 'lng' => $dropoff->lng, 'address' => $dropoff->address_line] : null,
                'current_driver' => $driverLocation,
            ],
            'courier' => $shipment->courier ? [
                'name' => $shipment->courier->name,
                'phone' => $shipment->courier->phone,
            ] : null,
            'events' => $shipment->events->map(fn($event) => [
                'status' => $event->status,
                'status_label' => $event->status->label(),
                'notes' => $event->notes,
                'created_at' => $event->created_at,
            ]),
        ]);
    }
}

