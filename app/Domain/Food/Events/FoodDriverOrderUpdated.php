<?php

namespace App\Domain\Food\Events;

use App\Order;
use App\Services\NotificationService;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class FoodDriverOrderUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    protected int $driverId;

    public function __construct(protected Order $order, int $driverId)
    {
        $this->driverId = $driverId;
    }

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('food.delivery.' . $this->driverId . '.orders');
    }

    public function broadcastAs(): string
    {
        return 'food.delivery.assignment.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'order_no' => $this->order->order_no,
            'driver_id' => $this->driverId,
            'restaurant_id' => (int) $this->order->restaurant_id,
            'business_status' => $this->order->resolveEffectiveBusinessStatus(),
            'tracking_status' => $this->order->resolveTrackingStatus(),
            'tracking_progress' => $this->order->resolveTrackingProgress(),
            'route_path' => NotificationService::routePath('driver.deliveries', ['order_no' => $this->order->order_no]),
            'deep_link' => 'bantudelice://food/deliveries/' . $this->order->order_no,
        ];
    }
}
