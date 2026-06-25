<?php

namespace App\Domain\Transport\Events;

use App\Domain\Transport\Models\TransportBooking;
use Carbon\CarbonInterface;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TransportTrackingUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public string $booking_uuid;
    public float $lat;
    public float $lng;
    public ?float $speed;
    public string $recorded_at;
    public ?int $tracking_point_id;

    public function __construct(
        TransportBooking $booking,
        float $lat,
        float $lng,
        ?float $speed = null,
        CarbonInterface|string|null $recordedAt = null,
        ?int $trackingPointId = null
    ) {
        $this->booking_uuid = $booking->uuid;
        $this->lat = $lat;
        $this->lng = $lng;
        $this->speed = $speed;
        $this->recorded_at = $recordedAt instanceof CarbonInterface
            ? $recordedAt->toIso8601String()
            : ($recordedAt ?: now()->toIso8601String());
        $this->tracking_point_id = $trackingPointId;
    }

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('transport.booking.' . $this->booking_uuid . '.tracking');
    }

    public function broadcastAs(): string
    {
        return 'location.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'booking_uuid' => $this->booking_uuid,
            'lat' => $this->lat,
            'lng' => $this->lng,
            'speed' => $this->speed,
            'recorded_at' => $this->recorded_at,
            'tracking_point_id' => $this->tracking_point_id,
        ];
    }
}
