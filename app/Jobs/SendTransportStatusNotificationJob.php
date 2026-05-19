<?php

namespace App\Jobs;

use App\Domain\Transport\Models\TransportBooking;
use App\Domain\Transport\Services\TransportNotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;

class SendTransportStatusNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 60;
    public $failOnTimeout = true;

    protected int $bookingId;
    protected string $notificationType;

    public function __construct(int $bookingId, string $notificationType)
    {
        $this->bookingId = $bookingId;
        $this->notificationType = $notificationType;
        $this->onConnection(config('module_queues.modules.transport.connection', 'database'));
        $this->onQueue(config('module_queues.modules.transport.queue', 'transport'));
    }

    public function handle(TransportNotificationService $notificationService): void
    {
        $booking = TransportBooking::find($this->bookingId);

        if (! $booking) {
            return;
        }

        match ($this->notificationType) {
            'booking_accepted' => $notificationService->notifyBookingAccepted($booking),
            'driver_arriving' => $notificationService->notifyDriverArriving($booking),
            'trip_started' => $notificationService->notifyTripStarted($booking),
            'booking_completed' => $notificationService->notifyBookingCompleted($booking),
            'booking_cancelled' => $notificationService->notifyBookingCancelled($booking),
            default => null,
        };
    }

    public function middleware(): array
    {
        return [
            (new WithoutOverlapping("transport:status-notification:{$this->bookingId}:{$this->notificationType}"))->expireAfter(120),
        ];
    }

    public function backoff(): array
    {
        return [30, 60, 120];
    }
}
