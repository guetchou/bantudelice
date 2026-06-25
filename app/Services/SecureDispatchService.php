<?php

namespace App\Services;

use App\Delivery;
use App\Driver;
use App\Jobs\AutoAssignDeliveryJob;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Le dispatch ne peut plus imposer une mission : il lance uniquement des offres.
 */
class SecureDispatchService extends DispatchService
{
    public function autoAssignResult(Delivery $delivery): array
    {
        Log::warning('Assignation directe désactivée : acceptation livreur obligatoire', [
            'delivery_id' => $delivery->id,
        ]);

        return [
            'status' => 'no_driver',
            'reason' => 'driver_consent_required',
        ];
    }

    public function autoAssign(Delivery $delivery): bool
    {
        return false;
    }

    public function processPendingDeliveries(int $limit = 10): array
    {
        $pendingDeliveries = Delivery::query()
            ->where('status', 'PENDING')
            ->whereHas('order', function ($query) {
                $query->whereIn(
                    'business_status',
                    FoodOrderStateMachineService::DISPATCHABLE_BUSINESS_STATUSES
                );
            })
            ->orderBy('created_at')
            ->limit($limit)
            ->get();

        $processed = 0;
        $queued = 0;
        $skipped = 0;

        foreach ($pendingDeliveries as $delivery) {
            $processed++;

            $hasLiveOffer = $delivery->offers()
                ->where('status', 'pending')
                ->where('expires_at', '>', now())
                ->exists();

            if ($hasLiveOffer) {
                $skipped++;
                continue;
            }

            AutoAssignDeliveryJob::dispatch($delivery);
            $queued++;
        }

        return [
            'processed' => $processed,
            'assigned' => 0,
            'queued' => $queued,
            'failed' => 0,
            'skipped' => $skipped,
        ];
    }

    protected function getAvailableDrivers(\App\Restaurant $restaurant)
    {
        $query = Driver::query()
            ->where(function ($builder) use ($restaurant) {
                $builder->where('restaurant_id', $restaurant->id)
                    ->orWhereNull('restaurant_id');
            });

        if (Schema::hasColumn('drivers', 'approved')) {
            $query->where('approved', true);
        }
        if (Schema::hasColumn('drivers', 'status')) {
            $query->where('status', 'online');
        }
        if (Schema::hasColumn('drivers', 'is_available')) {
            $query->where('is_available', true);
        }
        if (Schema::hasTable('driver_locations')) {
            $query->whereHas('locations', function ($locations) {
                $locations->where(
                    'timestamp',
                    '>=',
                    now()->subSeconds(max(60, (int) config('food.dispatch.location_freshness_seconds', 180)))
                );
            });
        }

        return $query->get();
    }
}
