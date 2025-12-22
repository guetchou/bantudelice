<?php

namespace App\Domain\Transport\Events;

use App\Domain\Transport\Models\TransportBooking;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TransportTrackingUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $booking_uuid;
    public $lat;
    public $lng;
    public $speed;

    /**
     * Create a new event instance.
     */
    public function __construct(TransportBooking $booking, $lat, $lng, $speed = null)
    {
        $this->booking_uuid = $booking->uuid;
        $this->lat = $lat;
        $this->lng = $lng;
        $this->speed = $speed;
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn()
    {
        return new Channel('transport.booking.' . $this->booking_uuid . '.tracking');
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs()
    {
        return 'location.updated';
    }
}

