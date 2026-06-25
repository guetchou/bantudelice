<?php

namespace App\Services;

use App\Delivery;
use App\Driver;
use App\Order;
use App\Restaurant;
use Illuminate\Support\Facades\Schema;

/**
 * Sélection géographique des livreurs réellement opérationnels.
 */
class DeliveryDispatchService
{
    public function countOperationalDriversForRestaurant(
        Restaurant $restaurant,
        ?float $targetLat = null,
        ?float $targetLng = null,
        float $radiusKm = 8.0
    ): int {
        return $this->operationalDriversForRestaurant(
            $restaurant,
            $targetLat,
            $targetLng,
            $radiusKm
        )->count();
    }

    public function bestOperationalDriverForRestaurant(
        Restaurant $restaurant,
        ?float $targetLat = null,
        ?float $targetLng = null,
        float $radiusKm = 8.0
    ): ?Driver {
        return $this->operationalDriversForRestaurant(
            $restaurant,
            $targetLat,
            $targetLng,
            $radiusKm
        )->first();
    }

    public function estimateDeliveryWindowForRestaurant(
        Restaurant $restaurant,
        ?float $targetLat = null,
        ?float $targetLng = null,
        float $radiusKm = 8.0
    ): array {
        $bestDriver = $this->bestOperationalDriverForRestaurant($restaurant, $targetLat, $targetLng, $radiusKm);
        $kitchenLoad = $this->activeKitchenLoadForRestaurant($restaurant);
        $prepWindow = $this->preparationWindowForRestaurant($restaurant, $kitchenLoad);
        $restaurantLat = $restaurant->latitude !== null ? (float) $restaurant->latitude : null;
        $restaurantLng = $restaurant->longitude !== null ? (float) $restaurant->longitude : null;

        $pickupEtaMinutes = null;
        if ($bestDriver && $restaurantLat !== null && $restaurantLng !== null) {
            $pickupDistance = $this->haversineKm(
                (float) $bestDriver->latitude,
                (float) $bestDriver->longitude,
                $restaurantLat,
                $restaurantLng
            );
            $pickupEtaMinutes = (int) max(4, ceil(($pickupDistance / 28) * 60));
        }

        $lastMileMinutes = 12;
        if ($restaurantLat !== null && $restaurantLng !== null && $targetLat !== null && $targetLng !== null) {
            $lastMileDistance = $this->haversineKm(
                $restaurantLat,
                $restaurantLng,
                $targetLat,
                $targetLng
            );
            $lastMileMinutes = (int) max(8, ceil(($lastMileDistance / 24) * 60));
        }

        $windowMin = ($prepWindow['min'] ?? 18) + ($pickupEtaMinutes ?? 8) + $lastMileMinutes;
        $windowMax = $windowMin + max(8, (int) ceil($windowMin * 0.25));

        return [
            'kitchen_load' => $kitchenLoad,
            'capacity_state' => $this->restaurantCapacityState($kitchenLoad),
            'prep_window_minutes' => $prepWindow,
            'pickup_eta_minutes' => $pickupEtaMinutes,
            'pickup_window_minutes' => [
                'min' => $pickupEtaMinutes !== null ? max(4, $pickupEtaMinutes) : 8,
                'max' => $pickupEtaMinutes !== null
                    ? max(8, $pickupEtaMinutes + max(4, (int) ceil($pickupEtaMinutes * 0.35)))
                    : 14,
            ],
            'delivery_window_minutes' => [
                'min' => $windowMin,
                'max' => $windowMax,
            ],
            'next_capacity_check_minutes' => $bestDriver
                ? 0
                : max(6, min(18, (int) ceil(($prepWindow['min'] ?? 18) / 2))),
        ];
    }

    public function activeKitchenLoadForRestaurant(Restaurant $restaurant): int
    {
        return (int) Order::query()
            ->where('restaurant_id', $restaurant->id)
            ->whereIn('business_status', [
                'pending_restaurant_acceptance',
                'accepted',
                'in_kitchen',
                'ready_for_pickup',
                'dispatching',
                'driver_assigned',
            ])
            ->distinct('order_no')
            ->count('order_no');
    }

    public function preparationWindowForRestaurant(Restaurant $restaurant, ?int $kitchenLoad = null): array
    {
        $load = $kitchenLoad ?? $this->activeKitchenLoadForRestaurant($restaurant);
        $baseMin = 16;
        $baseMax = 24;
        $extraBlocks = (int) floor($load / 3);
        $extraMinutes = $extraBlocks * 4;

        return [
            'min' => $baseMin + $extraMinutes,
            'max' => $baseMax + $extraMinutes + ($load >= 8 ? 6 : 0),
        ];
    }

    public function restaurantCapacityState(int $kitchenLoad): string
    {
        return match (true) {
            $kitchenLoad >= 9 => 'busy',
            $kitchenLoad >= 4 => 'elevated',
            default => 'stable',
        };
    }

    public function operationalDriversForRestaurant(
        Restaurant $restaurant,
        ?float $targetLat = null,
        ?float $targetLng = null,
        float $radiusKm = 8.0
    ) {
        $drivers = Driver::query()
            ->where(function ($query) use ($restaurant) {
                $query->where('restaurant_id', $restaurant->id)
                    ->orWhereNull('restaurant_id');
            });

        if (Schema::hasColumn('drivers', 'approved')) {
            $drivers->where('approved', true);
        }
        if (Schema::hasColumn('drivers', 'status')) {
            $drivers->where('status', 'online');
        }
        if (Schema::hasColumn('drivers', 'is_available')) {
            $drivers->where('is_available', true);
        }

        if (Schema::hasTable('driver_locations')) {
            $freshAfter = now()->subSeconds($this->locationFreshnessSeconds());
            $drivers->whereHas('locations', function ($query) use ($freshAfter) {
                $query->where('timestamp', '>=', $freshAfter);
            });
        }

        $drivers = $drivers
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->get();

        $restaurantLat = $restaurant->latitude !== null ? (float) $restaurant->latitude : null;
        $restaurantLng = $restaurant->longitude !== null ? (float) $restaurant->longitude : null;

        return $drivers->filter(function (Driver $driver) use ($restaurant, $restaurantLat, $restaurantLng, $radiusKm) {
            if (! $this->driverCanTakeNewDelivery($driver)) {
                return false;
            }

            if ($restaurantLat === null || $restaurantLng === null) {
                return (int) $driver->restaurant_id === (int) $restaurant->id;
            }

            return $this->haversineKm(
                (float) $driver->latitude,
                (float) $driver->longitude,
                $restaurantLat,
                $restaurantLng
            ) <= $radiusKm;
        })->sortBy(function (Driver $driver) use ($restaurantLat, $restaurantLng) {
            if ($restaurantLat === null || $restaurantLng === null) {
                return 0;
            }

            return $this->haversineKm(
                (float) $driver->latitude,
                (float) $driver->longitude,
                $restaurantLat,
                $restaurantLng
            );
        })->values();
    }

    public function driverCanTakeNewDelivery(Driver $driver): bool
    {
        if (! (bool) $driver->approved) {
            return false;
        }
        if (Schema::hasColumn('drivers', 'status') && $driver->status !== 'online') {
            return false;
        }
        if (Schema::hasColumn('drivers', 'is_available') && ! $driver->is_available) {
            return false;
        }

        if (Schema::hasTable('driver_locations')) {
            $hasFreshLocation = $driver->locations()
                ->where('timestamp', '>=', now()->subSeconds($this->locationFreshnessSeconds()))
                ->exists();

            if (! $hasFreshLocation) {
                return false;
            }
        }

        $activeDeliveries = Delivery::where('driver_id', $driver->id)
            ->whereIn('status', ['ASSIGNED', 'PICKED_UP', 'ON_THE_WAY'])
            ->count();

        return $activeDeliveries < 3;
    }

    public function haversineKm(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) * sin($dLat / 2)
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2))
            * sin($dLng / 2) * sin($dLng / 2);

        return $earthRadius * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }

    private function locationFreshnessSeconds(): int
    {
        return max(60, (int) config('food.dispatch.location_freshness_seconds', 180));
    }
}
