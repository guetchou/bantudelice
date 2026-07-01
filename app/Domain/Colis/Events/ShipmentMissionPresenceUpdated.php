<?php

namespace App\Domain\Colis\Events;

use App\Domain\Colis\Models\Shipment;
use App\DriverLocation;
use App\Services\NotificationService;
use BackedEnum;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;

class ShipmentMissionPresenceUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets;

    protected const LIVE_WINDOW_SECONDS = 120;

    protected int $shipmentId;

    protected ?string $trackingNumber;

    protected ?string $shipmentStatus;

    protected ?int $courierId;

    public function __construct(Shipment $shipment)
    {
        $this->shipmentId = (int) $shipment->id;
        $this->trackingNumber = $shipment->tracking_number;
        $this->shipmentStatus = $this->stringValue($shipment->status);
        $this->courierId = $shipment->assigned_courier_id ? (int) $shipment->assigned_courier_id : null;
    }

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('colis.shipment.' . $this->shipmentId . '.presence');
    }

    public function broadcastAs(): string
    {
        return 'colis.shipment.presence.updated';
    }

    public function broadcastWith(): array
    {
        $shipment = Shipment::query()->find($this->shipmentId);

        if (! $shipment) {
            return $this->offlinePayload();
        }

        $courierId = $shipment->assigned_courier_id ? (int) $shipment->assigned_courier_id : null;
        $latestLocation = $courierId
            ? DriverLocation::query()->latestForDriver($courierId)->first()
            : null;
        $courier = $shipment->courier;
        $recordedAt = $latestLocation?->timestamp;
        $freshnessSeconds = $recordedAt ? now()->diffInSeconds($recordedAt) : null;
        $presenceState = $freshnessSeconds === null
            ? 'offline'
            : ($freshnessSeconds <= self::LIVE_WINDOW_SECONDS ? 'live' : 'stale');
        $presenceExpiresAt = $recordedAt?->copy()->addSeconds(self::LIVE_WINDOW_SECONDS);

        return [
            'shipment_id' => (int) $shipment->id,
            'tracking_number' => $shipment->tracking_number,
            'shipment_status' => $this->stringValue($shipment->status),
            'courier_id' => $courierId,
            'courier_status' => $courier?->status,
            'location' => [
                'lat' => $latestLocation?->latitude !== null ? (float) $latestLocation->latitude : ($courier?->latitude !== null ? (float) $courier->latitude : null),
                'lng' => $latestLocation?->longitude !== null ? (float) $latestLocation->longitude : ($courier?->longitude !== null ? (float) $courier->longitude : null),
                'speed' => $latestLocation?->speed !== null ? (float) $latestLocation->speed : null,
                'recorded_at' => optional($recordedAt)->toIso8601String(),
            ],
            'presence_freshness_seconds' => $freshnessSeconds,
            'presence_stale_after_seconds' => self::LIVE_WINDOW_SECONDS,
            'presence_expires_at' => optional($presenceExpiresAt)->toIso8601String(),
            'presence_state' => $presenceState,
            'is_live' => $presenceState === 'live',
            'entity_exists' => true,
            'route_path' => NotificationService::routePath('colis.show', [$shipment->id]),
            'deep_link' => 'bantudelice://mema/shipments/' . $shipment->id,
        ];
    }

    protected function offlinePayload(): array
    {
        return [
            'shipment_id' => $this->shipmentId,
            'tracking_number' => $this->trackingNumber,
            'shipment_status' => $this->shipmentStatus,
            'courier_id' => $this->courierId,
            'courier_status' => null,
            'location' => [
                'lat' => null,
                'lng' => null,
                'speed' => null,
                'recorded_at' => null,
            ],
            'presence_freshness_seconds' => null,
            'presence_stale_after_seconds' => self::LIVE_WINDOW_SECONDS,
            'presence_expires_at' => null,
            'presence_state' => 'offline',
            'is_live' => false,
            'entity_exists' => false,
            'route_path' => NotificationService::routePath('colis.show', [$this->shipmentId]),
            'deep_link' => 'bantudelice://mema/shipments/' . $this->shipmentId,
        ];
    }

    protected function stringValue(mixed $value): ?string
    {
        if ($value instanceof BackedEnum) {
            return (string) $value->value;
        }

        return $value !== null ? (string) $value : null;
    }
}
