<?php

namespace App\Domain\Transport\Events;

use App\DriverLocation;
use App\Domain\Transport\Models\TransportBooking;
use App\Services\NotificationService;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TransportMissionPresenceUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    protected const LIVE_WINDOW_SECONDS = 120;

    public function __construct(protected TransportBooking $booking)
    {
    }

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('transport.booking.' . $this->booking->uuid . '.presence');
    }

    public function broadcastAs(): string
    {
        return 'transport.booking.presence.updated';
    }

    public function broadcastWith(): array
    {
        $latestTrackingPoint = $this->booking->trackingPoints
            ->sortByDesc(fn ($point) => optional($point->recorded_at)->getTimestamp() ?? 0)
            ->first();
        $driver = $this->booking->driver;
        $latestDriverLocation = $this->booking->driver_id
            ? DriverLocation::query()->latestForDriver($this->booking->driver_id)->first()
            : null;
        $trackingTimestamp = $latestTrackingPoint?->recorded_at;
        $driverTimestamp = $latestDriverLocation?->timestamp;
        $useTrackingPoint = $trackingTimestamp !== null
            && ($driverTimestamp === null || $trackingTimestamp->greaterThanOrEqualTo($driverTimestamp));
        $recordedAt = $useTrackingPoint ? $trackingTimestamp : $driverTimestamp;
        $freshnessSeconds = $recordedAt ? now()->diffInSeconds($recordedAt) : null;
        $latitude = $useTrackingPoint
            ? ($latestTrackingPoint?->lat !== null ? (float) $latestTrackingPoint->lat : null)
            : ($latestDriverLocation?->latitude !== null ? (float) $latestDriverLocation->latitude : null);
        $longitude = $useTrackingPoint
            ? ($latestTrackingPoint?->lng !== null ? (float) $latestTrackingPoint->lng : null)
            : ($latestDriverLocation?->longitude !== null ? (float) $latestDriverLocation->longitude : null);
        $speed = $useTrackingPoint
            ? ($latestTrackingPoint?->speed !== null ? (float) $latestTrackingPoint->speed : null)
            : ($latestDriverLocation?->speed !== null ? (float) $latestDriverLocation->speed : null);
        $presenceState = $freshnessSeconds === null
            ? 'offline'
            : ($freshnessSeconds <= self::LIVE_WINDOW_SECONDS ? 'live' : 'stale');
        $presenceExpiresAt = $recordedAt?->copy()->addSeconds(self::LIVE_WINDOW_SECONDS);

        return [
            'booking_uuid' => $this->booking->uuid,
            'booking_no' => $this->booking->booking_no,
            'booking_status' => $this->booking->status?->value,
            'driver_id' => $this->booking->driver_id ? (int) $this->booking->driver_id : null,
            'driver_status' => $driver?->status,
            'location' => [
                'lat' => $latitude ?? ($driver?->latitude !== null ? (float) $driver->latitude : null),
                'lng' => $longitude ?? ($driver?->longitude !== null ? (float) $driver->longitude : null),
                'speed' => $speed,
                'recorded_at' => optional($recordedAt)->toIso8601String(),
            ],
            'presence_freshness_seconds' => $freshnessSeconds,
            'presence_stale_after_seconds' => self::LIVE_WINDOW_SECONDS,
            'presence_expires_at' => optional($presenceExpiresAt)->toIso8601String(),
            'presence_state' => $presenceState,
            'is_live' => $presenceState === 'live',
            'route_path' => NotificationService::routePath('transport.booking.show', ['id' => $this->booking->uuid]),
            'deep_link' => 'bantudelice://kende/bookings/' . $this->booking->uuid,
        ];
    }
}
