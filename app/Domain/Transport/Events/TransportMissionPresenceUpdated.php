<?php

namespace App\Domain\Transport\Events;

use App\DriverLocation;
use App\Domain\Transport\Models\TransportBooking;
use App\Services\NotificationService;
use BackedEnum;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;

class TransportMissionPresenceUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets;

    protected const LIVE_WINDOW_SECONDS = 120;

    protected int $bookingId;

    protected string $bookingUuid;

    protected ?string $bookingNo;

    protected ?string $bookingStatus;

    protected ?int $driverId;

    public function __construct(TransportBooking $booking)
    {
        $this->bookingId = (int) $booking->id;
        $this->bookingUuid = (string) $booking->uuid;
        $this->bookingNo = $booking->booking_no;
        $this->bookingStatus = $this->stringValue($booking->status);
        $this->driverId = $booking->driver_id ? (int) $booking->driver_id : null;
    }

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('transport.booking.' . $this->bookingUuid . '.presence');
    }

    public function broadcastAs(): string
    {
        return 'transport.booking.presence.updated';
    }

    public function broadcastWith(): array
    {
        $booking = TransportBooking::query()->find($this->bookingId);

        if (! $booking) {
            return $this->offlinePayload();
        }

        $latestTrackingPoint = $booking->trackingPoints()
            ->orderByDesc('recorded_at')
            ->orderByDesc('id')
            ->first();
        $driver = $booking->driver;
        $latestDriverLocation = $booking->driver_id
            ? DriverLocation::query()->latestForDriver($booking->driver_id)->first()
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
            'booking_uuid' => $booking->uuid,
            'booking_no' => $booking->booking_no,
            'booking_status' => $this->stringValue($booking->status),
            'driver_id' => $booking->driver_id ? (int) $booking->driver_id : null,
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
            'entity_exists' => true,
            'route_path' => NotificationService::routePath('transport.booking.show', ['id' => $booking->uuid]),
            'deep_link' => 'bantudelice://kende/bookings/' . $booking->uuid,
        ];
    }

    protected function offlinePayload(): array
    {
        return [
            'booking_uuid' => $this->bookingUuid,
            'booking_no' => $this->bookingNo,
            'booking_status' => $this->bookingStatus,
            'driver_id' => $this->driverId,
            'driver_status' => null,
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
            'route_path' => NotificationService::routePath('transport.booking.show', ['id' => $this->bookingUuid]),
            'deep_link' => 'bantudelice://kende/bookings/' . $this->bookingUuid,
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
