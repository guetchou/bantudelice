<?php

namespace App\Domain\Food\Events;

use App\Order;
use App\Services\NotificationService;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class FoodOrderStatusUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(protected Order $order)
    {
    }

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('food.order.' . $this->order->order_no . '.status');
    }

    public function broadcastAs(): string
    {
        return 'food.order.status.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'order_no' => $this->order->order_no,
            'business_status' => $this->order->resolveEffectiveBusinessStatus(),
            'tracking_status' => $this->order->resolveTrackingStatus(),
            'tracking_progress' => $this->order->resolveTrackingProgress(),
            'fulfillment_mode' => $this->order->fulfillment_mode ?? 'delivery',
            'restaurant_id' => (int) $this->order->restaurant_id,
            'driver_id' => $this->resolvedDriverId(),
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
