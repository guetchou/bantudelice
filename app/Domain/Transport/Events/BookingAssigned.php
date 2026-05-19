<?php

namespace App\Domain\Transport\Events;

use App\Domain\Transport\Models\TransportBooking;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BookingAssigned
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $booking;

    public function __construct(TransportBooking $booking)
    {
        $this->booking = $booking;
    }
}

