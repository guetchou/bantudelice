<?php

namespace App\Services;

use App\Delivery;
use App\Domain\Colis\Enums\ShipmentStatus;
use App\Domain\Colis\Events\ShipmentMissionPresenceUpdated;
use App\Domain\Colis\Models\Shipment;
use App\Domain\Food\Events\FoodMissionPresenceUpdated;
use App\Domain\Transport\Enums\TransportStatus;
use App\Domain\Transport\Events\TransportMissionPresenceUpdated;
use App\Domain\Transport\Models\TransportBooking;

class MissionPresenceRefreshService
{
    public function refreshActiveMissions(): array
    {
        $food = $this->refreshFoodMissions();
        $transport = $this->refreshTransportMissions();
        $colis = $this->refreshColisMissions();

        return [
            'food' => $food,
            'transport' => $transport,
            'colis' => $colis,
            'total' => $food + $transport + $colis,
        ];
    }

    protected function refreshFoodMissions(): int
    {
        $count = 0;

        Delivery::query()
            ->with(['order.delivery.driver', 'order.driver'])
            ->whereIn('status', ['ASSIGNED', 'PICKED_UP', 'ON_THE_WAY'])
            ->get()
            ->pluck('order')
            ->filter()
            ->unique('id')
            ->each(function ($order) use (&$count) {
                event(new FoodMissionPresenceUpdated($order));
                $count++;
            });

        return $count;
    }

    protected function refreshTransportMissions(): int
    {
        $count = 0;

        TransportBooking::query()
            ->with(['driver', 'vehicle', 'trackingPoints'])
            ->whereIn('status', [
                TransportStatus::ASSIGNED->value,
                TransportStatus::DRIVER_ARRIVING->value,
                TransportStatus::PICKED_UP->value,
                TransportStatus::IN_PROGRESS->value,
            ])
            ->get()
            ->each(function (TransportBooking $booking) use (&$count) {
                event(new TransportMissionPresenceUpdated($booking));
                $count++;
            });

        return $count;
    }

    protected function refreshColisMissions(): int
    {
        $count = 0;

        Shipment::query()
            ->with(['courier'])
            ->whereNotNull('assigned_courier_id')
            ->whereNotIn('status', [
                ShipmentStatus::DELIVERED->value,
                ShipmentStatus::CANCELED->value,
                ShipmentStatus::RETURNED->value,
                ShipmentStatus::LOST->value,
            ])
            ->get()
            ->each(function (Shipment $shipment) use (&$count) {
                event(new ShipmentMissionPresenceUpdated($shipment));
                $count++;
            });

        return $count;
    }
}
