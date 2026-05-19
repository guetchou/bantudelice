<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Domain\Transport\Models\TransportBooking;
use App\Domain\Transport\Enums\TransportType;
use App\Domain\Transport\Enums\TransportStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TransportBookingTest extends TestCase
{
    /**
     * Test transport booking creation
     */
    public function test_can_create_transport_booking()
    {
        $booking = new TransportBooking([
            'type' => TransportType::TAXI,
            'status' => TransportStatus::REQUESTED,
            'pickup_address' => 'Airport',
            'dropoff_address' => 'Hotel',
            'estimated_price' => 5000
        ]);

        $this->assertEquals(TransportType::TAXI, $booking->type);
        $this->assertEquals(TransportStatus::REQUESTED, $booking->status);
        $this->assertEquals('Airport', $booking->pickup_address);
    }

    /**
     * Test status transitions
     */
    public function test_status_labels()
    {
        $this->assertEquals('En cours', TransportStatus::IN_PROGRESS->label());
        $this->assertEquals('Annulé', TransportStatus::CANCELLED->label());
    }
}
