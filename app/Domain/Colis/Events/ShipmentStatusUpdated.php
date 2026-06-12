<?php

namespace App\Domain\Colis\Events;

use App\Domain\Colis\Models\Shipment;
use App\Services\NotificationService;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ShipmentStatusUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(protected Shipment $shipment)
    {
    }

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('colis.shipment.' . $this->shipment->id . '.status');
    }

    public function broadcastAs(): string
    {
        return 'colis.shipment.status.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'shipment_id' => (int) $this->shipment->id,
            'tracking_number' => $this->shipment->tracking_number,
            'status' => $this->shipment->status->value,
            'status_label' => $this->shipment->status->label(),
            'assigned_courier_id' => $this->shipment->assigned_courier_id ? (int) $this->shipment->assigned_courier_id : null,
            'route_path' => NotificationService::routePath('colis.show', [$this->shipment->id]),
            'deep_link' => 'bantudelice://mema/shipments/' . $this->shipment->id,
            'public_tracking_path' => NotificationService::routePath('colis.track_public') . '?tracking=' . urlencode($this->shipment->tracking_number),
        ];
    }
}
