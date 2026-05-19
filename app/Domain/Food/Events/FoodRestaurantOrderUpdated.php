<?php

namespace App\Domain\Food\Events;

use App\Order;
use App\Services\NotificationService;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class FoodRestaurantOrderUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(protected Order $order)
    {
    }

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('food.restaurant.' . $this->order->restaurant_id . '.orders');
    }

    public function broadcastAs(): string
    {
        return 'food.restaurant.order.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'order_no' => $this->order->order_no,
            'restaurant_id' => (int) $this->order->restaurant_id,
            'business_status' => $this->order->resolveEffectiveBusinessStatus(),
            'tracking_status' => $this->order->resolveTrackingStatus(),
            'tracking_progress' => $this->order->resolveTrackingProgress(),
            'route_path' => NotificationService::routePath('restaurant.all_orders', ['focus' => $this->order->order_no]),
            'deep_link' => 'bantudelice://food/restaurant/orders/' . $this->order->order_no,
        ];
    }
}
