<?php

namespace App\Services;

use App\Delivery;
use App\Driver;
use App\Domain\Colis\Enums\ShipmentStatus;
use App\Domain\Colis\Events\ShipmentMissionPresenceUpdated;
use App\Domain\Colis\Models\Shipment;
use App\Domain\Food\Events\FoodMissionPresenceUpdated;
use App\Domain\Transport\Enums\TransportStatus;
use App\Domain\Transport\Events\TransportMissionPresenceUpdated;
use App\Domain\Transport\Models\TransportBooking;

class MissionPresenceBroadcastService
{
    public function broadcastForDriver(Driver $driver): void
    {
        $this->broadcastFoodPresence($driver);
        $this->broadcastTransportPresence($driver);
        $this->broadcastColisPresence($driver);
    }

    protected function broadcastFoodPresence(Driver $driver): void
    {
        Delivery::query()
            ->with(['order.delivery.driver', 'order.driver'])
            ->where('driver_id', $driver->id)
            ->whereIn('status', ['ASSIGNED', 'PICKED_UP', 'ON_THE_WAY'])
            ->get()
            ->pluck('order')
            ->filter()
            ->each(fn ($order) => event(new FoodMissionPresenceUpdated($order)));
    }

    protected function broadcastTransportPresence(Driver $driver): void
    {
        TransportBooking::query()
            ->with(['driver', 'vehicle'])
            ->where('driver_id', $driver->id)
            ->whereIn('status', [
                TransportStatus::ASSIGNED->value,
                TransportStatus::DRIVER_ARRIVING->value,
                TransportStatus::PICKED_UP->value,
                TransportStatus::IN_PROGRESS->value,
            ])
            ->get()
            ->each(fn (TransportBooking $booking) => event(new TransportMissionPresenceUpdated($booking)));
    }

    protected function broadcastColisPresence(Driver $driver): void
    {
        Shipment::query()
            ->with(['courier'])
            ->where('assigned_courier_id', $driver->id)
            ->whereNotIn('status', [
                ShipmentStatus::DELIVERED->value,
                ShipmentStatus::CANCELED->value,
                ShipmentStatus::RETURNED->value,
                ShipmentStatus::LOST->value,
            ])
            ->get()
            ->each(fn (Shipment $shipment) => event(new ShipmentMissionPresenceUpdated($shipment)));
    }
}
