<?php

namespace App\Domain\Colis\Events;

use App\Domain\Colis\Models\Shipment;
use App\DriverLocation;
use App\Services\NotificationService;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ShipmentMissionPresenceUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    protected const LIVE_WINDOW_SECONDS = 120;

    public function __construct(protected Shipment $shipment)
    {
    }

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('colis.shipment.' . $this->shipment->id . '.presence');
    }

    public function broadcastAs(): string
    {
        return 'colis.shipment.presence.updated';
    }

    public function broadcastWith(): array
    {
        $courierId = $this->shipment->assigned_courier_id ? (int) $this->shipment->assigned_courier_id : null;
        $latestLocation = $courierId
            ? DriverLocation::query()->latestForDriver($courierId)->first()
            : null;
        $courier = $this->shipment->courier;
        $recordedAt = $latestLocation?->timestamp;
        $freshnessSeconds = $recordedAt ? now()->diffInSeconds($recordedAt) : null;
        $presenceState = $freshnessSeconds === null
            ? 'offline'
            : ($freshnessSeconds <= self::LIVE_WINDOW_SECONDS ? 'live' : 'stale');
        $presenceExpiresAt = $recordedAt?->copy()->addSeconds(self::LIVE_WINDOW_SECONDS);

        return [
            'shipment_id' => (int) $this->shipment->id,
            'tracking_number' => $this->shipment->tracking_number,
            'shipment_status' => $this->shipment->status->value,
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
            'route_path' => NotificationService::routePath('colis.show', [$this->shipment->id]),
            'deep_link' => 'bantudelice://mema/shipments/' . $this->shipment->id,
        ];
    }
}
