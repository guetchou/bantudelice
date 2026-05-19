<?php

namespace App\Domain\Transport\Listeners;

use App\Domain\Payment\Events\PaymentConfirmed;
use App\Domain\Transport\Enums\TransportStatus;
use App\Domain\Transport\Services\TransportService;
use Illuminate\Support\Facades\Log;

class TransportPaymentConfirmed
{
    public function handle(PaymentConfirmed $event): void
    {
        $payment = $event->payment;

        if (! $payment->transport_booking_id) {
            return;
        }

        $booking = $payment->transportBooking;

        if (! $booking) {
            Log::warning('TransportPaymentConfirmed : réservation introuvable', [
                'payment_id'          => $payment->id,
                'transport_booking_id' => $payment->transport_booking_id,
            ]);
            return;
        }

        $booking->update([
            'payment_status' => 'paid',
            'payment_method' => $payment->provider ?: $booking->payment_method,
        ]);

        if ($booking->status === TransportStatus::COMPLETED) {
            app(TransportService::class)->updateStatus($booking->fresh(), TransportStatus::PAID);
        }
    }
}
