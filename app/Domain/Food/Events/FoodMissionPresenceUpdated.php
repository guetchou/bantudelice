<?php

namespace App\Domain\Food\Events;

use App\DriverLocation;
use App\Order;
use App\Services\NotificationService;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;

class FoodMissionPresenceUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets;

    protected const LIVE_WINDOW_SECONDS = 120;

    protected int $orderId;

    protected string $orderNo;

    protected ?int $driverId;

    protected ?string $businessStatus;

    protected ?string $deliveryStatus;

    public function __construct(Order $order)
    {
        $this->orderId = (int) $order->id;
        $this->orderNo = (string) $order->order_no;
        $this->driverId = $this->resolvedDriverIdFromOrder($order);
        $this->businessStatus = $order->resolveEffectiveBusinessStatus();
        $this->deliveryStatus = $order->delivery?->status;
    }

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('food.order.' . $this->orderNo . '.presence');
    }

    public function broadcastAs(): string
    {
        return 'food.order.presence.updated';
    }

    public function broadcastWith(): array
    {
        $order = Order::query()->find($this->orderId);

        if (! $order) {
            return $this->offlinePayload();
        }

        $driverId = $this->resolvedDriverIdFromOrder($order);
        $latestLocation = $driverId
            ? DriverLocation::query()->latestForDriver($driverId)->first()
            : null;
        $driver = $order->delivery?->driver ?: $order->driver;
        $recordedAt = $latestLocation?->timestamp;
        $freshnessSeconds = $recordedAt ? now()->diffInSeconds($recordedAt) : null;
        $presenceState = $freshnessSeconds === null
            ? 'offline'
            : ($freshnessSeconds <= self::LIVE_WINDOW_SECONDS ? 'live' : 'stale');
        $presenceExpiresAt = $recordedAt?->copy()->addSeconds(self::LIVE_WINDOW_SECONDS);

        return [
            'order_no' => $order->order_no,
            'business_status' => $order->resolveEffectiveBusinessStatus(),
            'delivery_status' => $order->delivery?->status,
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
            'entity_exists' => true,
            'route_path' => NotificationService::routePath('track.order', ['orderNo' => $order->order_no]),
            'deep_link' => 'bantudelice://food/orders/' . $order->order_no,
        ];
    }

    protected function offlinePayload(): array
    {
        return [
            'order_no' => $this->orderNo,
            'business_status' => $this->businessStatus,
            'delivery_status' => $this->deliveryStatus,
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
            'route_path' => NotificationService::routePath('track.order', ['orderNo' => $this->orderNo]),
            'deep_link' => 'bantudelice://food/orders/' . $this->orderNo,
        ];
    }

    protected function resolvedDriverIdFromOrder(Order $order): ?int
    {
        return $order->delivery?->driver_id
            ? (int) $order->delivery->driver_id
            : ($order->driver_id ? (int) $order->driver_id : null);
    }
}
