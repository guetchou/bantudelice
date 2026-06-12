<?php

namespace App\Domain\Food\Events;

use App\DriverLocation;
use App\Order;
use App\Services\NotificationService;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class FoodMissionPresenceUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    protected const LIVE_WINDOW_SECONDS = 120;

    public function __construct(protected Order $order)
    {
    }

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('food.order.' . $this->order->order_no . '.presence');
    }

    public function broadcastAs(): string
    {
        return 'food.order.presence.updated';
    }

    public function broadcastWith(): array
    {
        $driverId = $this->resolvedDriverId();
        $latestLocation = $driverId
            ? DriverLocation::query()->latestForDriver($driverId)->first()
            : null;
        $driver = $this->order->delivery?->driver ?: $this->order->driver;
        $recordedAt = $latestLocation?->timestamp;
        $freshnessSeconds = $recordedAt ? now()->diffInSeconds($recordedAt) : null;
        $presenceState = $freshnessSeconds === null
            ? 'offline'
            : ($freshnessSeconds <= self::LIVE_WINDOW_SECONDS ? 'live' : 'stale');
        $presenceExpiresAt = $recordedAt?->copy()->addSeconds(self::LIVE_WINDOW_SECONDS);

        return [
            'order_no' => $this->order->order_no,
            'business_status' => $this->order->resolveEffectiveBusinessStatus(),
            'delivery_status' => $this->order->delivery?->status,
            'driver_id' => $driverId,
            'driver_status' => $driver?->status,
            'location' => [
                'lat' => $latestLocation?->latitude !== null ? (float) $latestLocation->latitude : ($driver?->latitude !== null ? (float) $driver->latitude : null),
                'lng' => $latestLocation?->longitude !== null ? (float) $latestLocation->longitude : ($driver?->longitude !== null ? (float) $driver->longitude : null),
                'speed' => $latestLocation?->speed !== null ? (float) $latestLocation->speed : null,
                'recorded_at' => optional($recordedAt)->toIso8601String(),
            ],
            'presence_freshness_seconds' => $freshnessSeconds,
            'presence_stale_after_seconds' => self::LIVE_WINDOW_SECONDS,
            'presence_expires_at' => optional($presenceExpiresAt)->toIso8601String(),
            'presence_state' => $presenceState,
            'is_live' => $presenceState === 'live',
            'route_path' => NotificationService::routePath('track.order', ['orderNo' => $this->order->order_no]),
            'deep_link' => 'bantudelice://food/orders/' . $this->order->order_no,
        ];
    }

    protected function resolvedDriverId(): ?int
    {
        return $this->order->delivery?->driver_id
            ? (int) $this->order->delivery->driver_id
            : ($this->order->driver_id ? (int) $this->order->driver_id : null);
    }
}
