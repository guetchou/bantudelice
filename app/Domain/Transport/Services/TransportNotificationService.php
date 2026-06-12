<?php

namespace App\Domain\Transport\Services;

use App\Domain\Transport\Models\TransportBooking;
use App\Services\NotificationService;
use App\Services\NotificationLogService;
use App\Services\SmsService;
use Illuminate\Support\Facades\Log;

class TransportNotificationService
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    public function notifyBookingAccepted(TransportBooking $booking)
    {
        $user = $booking->user;
        $driver = $booking->driver;

        $title = "Chauffeur assigné !";
        $message = "Votre chauffeur {$driver->name} a accepté votre course #{$booking->booking_no}.";

        $this->notificationService->sendToUser($user->id, $title, $message, [
            'module' => 'transport',
            'type' => 'transport_accepted',
            'booking_no' => $booking->booking_no,
            'booking_uuid' => $booking->uuid,
            'route_path' => NotificationService::routePath('transport.booking.show', ['id' => $booking->uuid]),
            'deep_link' => 'bantudelice://kende/bookings/' . $booking->uuid,
            'sound_key' => 'kende_assignment',
            'audio_cue' => 'driver_assigned_bright',
            'actions' => [
                ['id' => 'open_booking', 'label' => 'Voir la course', 'path' => NotificationService::routePath('transport.booking.show', ['id' => $booking->uuid])],
                ['id' => 'open_bookings', 'label' => 'Mes réservations', 'path' => NotificationService::routePath('transport.my_bookings')],
            ],
            'websocket_channel' => 'transport.booking.' . $booking->uuid . '.status',
            'websocket_event' => 'transport.booking.status.updated',
            'tracking_channel' => 'transport.booking.' . $booking->uuid . '.tracking',
            'presence_channel' => 'transport.booking.' . $booking->uuid . '.presence',
        ]);

        if ($user->phone) {
            SmsService::send($user->phone, $message);
            app(NotificationLogService::class)->record([
                'channel' => 'sms',
                'recipient_type' => 'user',
                'recipient_id' => $user->id,
                'recipient_address' => $user->phone,
                'title' => $title,
                'body' => $message,
                'provider' => 'sms',
                'status' => 'sent',
                'context' => [
                    'module' => 'transport',
                    'booking_uuid' => $booking->uuid,
                    'route_path' => NotificationService::routePath('transport.booking.show', ['id' => $booking->uuid]),
                    'deep_link' => 'bantudelice://kende/bookings/' . $booking->uuid,
                    'websocket_channel' => 'transport.booking.' . $booking->uuid . '.status',
                    'websocket_event' => 'transport.booking.status.updated',
                    'presence_channel' => 'transport.booking.' . $booking->uuid . '.presence',
                ],
            ]);
        }

        Log::info("Notification: Booking accepted for {$booking->booking_no}");
    }

    public function notifyDriverArriving(TransportBooking $booking)
    {
        $user = $booking->user;
        $message = "Votre chauffeur est arrivé au point de départ.";

        $this->notificationService->sendToUser($user->id, "Chauffeur arrivé", $message, [
            'module' => 'transport',
            'type' => 'driver_arriving',
            'booking_no' => $booking->booking_no,
            'booking_uuid' => $booking->uuid,
            'route_path' => NotificationService::routePath('transport.booking.show', ['id' => $booking->uuid]),
            'deep_link' => 'bantudelice://kende/bookings/' . $booking->uuid,
            'sound_key' => 'kende_driver_arrived',
            'audio_cue' => 'driver_arrived_attention',
            'actions' => [
                ['id' => 'open_booking', 'label' => 'Voir le chauffeur', 'path' => NotificationService::routePath('transport.booking.show', ['id' => $booking->uuid])],
            ],
            'websocket_channel' => 'transport.booking.' . $booking->uuid . '.status',
            'websocket_event' => 'transport.booking.status.updated',
            'tracking_channel' => 'transport.booking.' . $booking->uuid . '.tracking',
            'presence_channel' => 'transport.booking.' . $booking->uuid . '.presence',
        ]);
    }

    public function notifyTripStarted(TransportBooking $booking)
    {
        $user = $booking->user;
        $message = "Votre course {$booking->booking_no} a commencé.";

        $this->notificationService->sendToUser($user->id, "Course démarrée", $message, [
            'module' => 'transport',
            'type' => 'transport_started',
            'booking_no' => $booking->booking_no,
            'booking_uuid' => $booking->uuid,
            'route_path' => NotificationService::routePath('transport.booking.show', ['id' => $booking->uuid]),
            'deep_link' => 'bantudelice://kende/bookings/' . $booking->uuid,
            'sound_key' => 'kende_trip_started',
            'audio_cue' => 'trip_started_soft',
            'actions' => [
                ['id' => 'open_booking', 'label' => 'Suivre la course', 'path' => NotificationService::routePath('transport.booking.show', ['id' => $booking->uuid])],
            ],
            'websocket_channel' => 'transport.booking.' . $booking->uuid . '.status',
            'websocket_event' => 'transport.booking.status.updated',
            'tracking_channel' => 'transport.booking.' . $booking->uuid . '.tracking',
            'presence_channel' => 'transport.booking.' . $booking->uuid . '.presence',
        ]);
    }

    public function notifyBookingCompleted(TransportBooking $booking)
    {
        $user = $booking->user;
        $message = "Course terminée. Merci d'avoir utilisé Kende.";

        $this->notificationService->sendToUser($user->id, "Course terminée", $message, [
            'module' => 'transport',
            'type' => 'transport_completed',
            'booking_no' => $booking->booking_no,
            'booking_uuid' => $booking->uuid,
            'route_path' => NotificationService::routePath('transport.booking.show', ['id' => $booking->uuid]),
            'deep_link' => 'bantudelice://kende/bookings/' . $booking->uuid,
            'sound_key' => 'kende_trip_completed',
            'audio_cue' => 'trip_completed_soft',
            'actions' => [
                ['id' => 'open_booking', 'label' => 'Voir le reçu', 'path' => NotificationService::routePath('transport.booking.show', ['id' => $booking->uuid])],
            ],
            'websocket_channel' => 'transport.booking.' . $booking->uuid . '.status',
            'websocket_event' => 'transport.booking.status.updated',
            'presence_channel' => 'transport.booking.' . $booking->uuid . '.presence',
        ]);
    }

    public function notifyBookingCancelled(TransportBooking $booking): void
    {
        $user = $booking->user;
        if (! $user) {
            return;
        }

        $message = "Votre réservation {$booking->booking_no} a été annulée.";

        $this->notificationService->sendToUser($user->id, "Réservation annulée", $message, [
            'module' => 'transport',
            'type' => 'transport_cancelled',
            'booking_no' => $booking->booking_no,
            'booking_uuid' => $booking->uuid,
            'route_path' => NotificationService::routePath('transport.booking.show', ['id' => $booking->uuid]),
            'deep_link' => 'bantudelice://kende/bookings/' . $booking->uuid,
            'sound_key' => 'kende_trip_cancelled',
            'audio_cue' => 'trip_cancelled_soft',
            'actions' => [
                ['id' => 'open_bookings', 'label' => 'Mes réservations', 'path' => NotificationService::routePath('transport.my_bookings')],
            ],
            'websocket_channel' => 'transport.booking.' . $booking->uuid . '.status',
            'websocket_event' => 'transport.booking.status.updated',
            'presence_channel' => 'transport.booking.' . $booking->uuid . '.presence',
        ]);
    }
}
