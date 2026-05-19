<?php

namespace App\Domain\Transport\Events;

use App\Domain\Transport\Models\TransportBooking;
use App\Services\NotificationService;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TransportRequestBroadcasted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        protected TransportBooking $booking,
        protected array $driverIds,
        protected int $offerWindowSeconds = 45
    ) {
    }

    public function broadcastOn(): array
    {
        return collect($this->driverIds)
            ->unique()
            ->values()
            ->map(fn (int $driverId) => new PrivateChannel('transport.driver.' . $driverId . '.requests'))
            ->all();
    }

    public function broadcastAs(): string
    {
        return 'transport.request.broadcasted';
    }

    public function broadcastWith(): array
    {
        $offerExpiresAt = now()->addSeconds($this->offerWindowSeconds);

        return [
            'booking_uuid' => $this->booking->uuid,
            'booking_no' => $this->booking->booking_no,
            'type' => $this->booking->type?->value,
            'status' => $this->booking->status?->value,
            'pickup_address' => $this->booking->pickup_address,
            'pickup_lat' => $this->booking->pickup_lat !== null ? (float) $this->booking->pickup_lat : null,
            'pickup_lng' => $this->booking->pickup_lng !== null ? (float) $this->booking->pickup_lng : null,
            'dropoff_address' => $this->booking->dropoff_address,
            'dropoff_lat' => $this->booking->dropoff_lat !== null ? (float) $this->booking->dropoff_lat : null,
            'dropoff_lng' => $this->booking->dropoff_lng !== null ? (float) $this->booking->dropoff_lng : null,
            'estimated_price' => $this->booking->estimated_price !== null ? (float) $this->booking->estimated_price : null,
            'total_price' => $this->booking->total_price !== null ? (float) $this->booking->total_price : null,
            'currency' => 'FCFA',
            'route_path' => NotificationService::routePath('transport.booking.show', ['id' => $this->booking->uuid]),
            'deep_link' => 'bantudelice://kende/bookings/' . $this->booking->uuid,
            'offer_window_seconds' => $this->offerWindowSeconds,
            'offer_expires_at' => $offerExpiresAt->toIso8601String(),
            'first_accept_wins' => true,
        ];
    }
}
