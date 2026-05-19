<?php

namespace App\Domain\Transport\Events;

use App\Domain\Transport\Models\TransportBooking;
use App\Services\NotificationService;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TransportBookingStatusUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(protected TransportBooking $booking)
    {
    }

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('transport.booking.' . $this->booking->uuid . '.status');
    }

    public function broadcastAs(): string
    {
        return 'transport.booking.status.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'booking_uuid' => $this->booking->uuid,
            'booking_no' => $this->booking->booking_no,
            'type' => $this->booking->type?->value,
            'status' => $this->booking->status?->value,
            'status_label' => $this->booking->status?->label(),
            'payment_status' => $this->booking->payment_status,
            'driver_id' => $this->booking->driver_id ? (int) $this->booking->driver_id : null,
            'vehicle_id' => $this->booking->vehicle_id ? (int) $this->booking->vehicle_id : null,
            'estimated_price' => $this->booking->estimated_price !== null ? (float) $this->booking->estimated_price : null,
            'total_price' => $this->booking->total_price !== null ? (float) $this->booking->total_price : null,
            'route_path' => NotificationService::routePath('transport.booking.show', ['id' => $this->booking->uuid]),
            'deep_link' => 'bantudelice://kende/bookings/' . $this->booking->uuid,
        ];
    }
}
