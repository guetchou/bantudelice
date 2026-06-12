<?php

namespace App\Domain\Colis\Services;

use App\Domain\Colis\Models\Shipment;
use App\Domain\Colis\Enums\ShipmentStatus;
use App\Driver;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

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
        $pickupAddress = $shipment->pickupAddress();
        $courier = null;

        if ($pickupAddress && $pickupAddress->lat !== null && $pickupAddress->lng !== null) {
            $courier = $this->availableCouriersNear(
                (float) $pickupAddress->lat,
                (float) $pickupAddress->lng
            )->sortBy(function (Driver $driver) use ($pickupAddress) {
                return $this->distanceKm(
                    (float) $driver->latitude,
                    (float) $driver->longitude,
                    (float) $pickupAddress->lat,
                    (float) $pickupAddress->lng
                );
            })->first();
        }

        if (! $courier) {
            $courier = Driver::query()
                ->when(Schema::hasColumn('drivers', 'status'), fn ($query) => $query->where('status', 'online'))
                ->when(Schema::hasColumn('drivers', 'is_available'), fn ($query) => $query->where('is_available', true))
                ->get()
                ->first(fn (Driver $driver) => $this->courierCanTakeShipment($driver));
        }

        if ($courier) {
            $this->assignToCourier($shipment, $courier->id);
            return $courier;
        }

        return null;
    }

    public function countAvailableCouriersNear(float $lat, float $lng, float $radiusKm = 8.0): int
    {
        return $this->availableCouriersNear($lat, $lng, $radiusKm)->count();
    }

    public function bestAvailableCourierNear(float $lat, float $lng, float $radiusKm = 8.0): ?Driver
    {
        return $this->availableCouriersNear($lat, $lng, $radiusKm)
            ->sortBy(function (Driver $driver) use ($lat, $lng) {
                return $this->distanceKm(
                    (float) $driver->latitude,
                    (float) $driver->longitude,
                    $lat,
                    $lng
                );
            })
            ->first();
    }

    public function availableCouriersNear(float $lat, float $lng, float $radiusKm = 8.0)
    {
        return Driver::query()
            ->when(Schema::hasColumn('drivers', 'status'), fn ($query) => $query->where('status', 'online'))
            ->when(Schema::hasColumn('drivers', 'is_available'), fn ($query) => $query->where('is_available', true))
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->get()
            ->filter(function (Driver $driver) use ($lat, $lng, $radiusKm) {
                if (! $this->courierCanTakeShipment($driver)) {
                    return false;
                }

                return $this->distanceKm(
                    (float) $driver->latitude,
                    (float) $driver->longitude,
                    $lat,
                    $lng
                ) <= $radiusKm;
            })
            ->values();
    }

    protected function courierCanTakeShipment(Driver $driver): bool
    {
        if (Schema::hasColumn('drivers', 'status') && $driver->status !== 'online') {
            return false;
        }

        if (Schema::hasColumn('drivers', 'is_available') && ! $driver->is_available) {
            return false;
        }

        $activeShipments = Shipment::where('assigned_courier_id', $driver->id)
            ->whereIn('status', [
                ShipmentStatus::CREATED->value,
                ShipmentStatus::PRICED->value,
                ShipmentStatus::PAID->value,
                ShipmentStatus::PICKED_UP->value,
                ShipmentStatus::IN_TRANSIT->value,
                ShipmentStatus::AT_RELAY->value,
                ShipmentStatus::OUT_FOR_DELIVERY->value,
            ])
            ->count();

        return $activeShipments < 3;
    }

    protected function distanceKm(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) * sin($dLat / 2)
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2))
            * sin($dLng / 2) * sin($dLng / 2);

        return $earthRadius * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }
}
