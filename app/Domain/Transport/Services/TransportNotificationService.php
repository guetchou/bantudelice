<?php

namespace App\Domain\Transport\Services;

use App\Domain\Transport\Models\TransportBooking;
use App\Services\NotificationService;
use App\Services\SmsService;
use Illuminate\Support\Facades\Log;

class TransportNotificationService
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Notify user when a booking is accepted by a driver
     */
    public function notifyBookingAccepted(TransportBooking $booking)
    {
        $user = $booking->user;
        $driver = $booking->driver;

        $title = "Chauffeur assigné !";
        $message = "Votre chauffeur {$driver->name} a accepté votre course #{$booking->booking_no}.";

        // Push Notification
        $this->notificationService->sendToUser($user->id, $title, $message, [
            'type' => 'transport_accepted',
            'booking_uuid' => $booking->uuid
        ]);

        // SMS (Optional based on config)
        if ($user->phone) {
            SmsService::send($user->phone, $message);
        }

        Log::info("Notification: Booking accepted for {$booking->booking_no}");
    }

    /**
     * Notify user when driver is arriving
     */
    public function notifyDriverArriving(TransportBooking $booking)
    {
        $user = $booking->user;
        $message = "Votre chauffeur est arrivé au point de départ !";

        $this->notificationService->sendToUser($user->id, "Chauffeur arrivé", $message, [
            'type' => 'driver_arriving',
            'booking_uuid' => $booking->uuid
        ]);
    }

    /**
     * Notify user when booking is completed
     */
    public function notifyBookingCompleted(TransportBooking $booking)
    {
        $user = $booking->user;
        $message = "Course terminée ! Merci d'avoir utilisé BantuDelice.";

        $this->notificationService->sendToUser($user->id, "Course terminée", $message, [
            'type' => 'transport_completed',
            'booking_uuid' => $booking->uuid
        ]);
    }
}

