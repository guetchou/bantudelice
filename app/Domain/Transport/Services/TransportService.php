<?php

namespace App\Domain\Transport\Services;

use App\Domain\Transport\Models\TransportBooking;
use App\Domain\Transport\Models\TransportPricingRule;
use App\Domain\Transport\Enums\TransportType;
use App\Domain\Transport\Enums\TransportStatus;
use Illuminate\Support\Facades\DB;

class TransportService
{
    protected $notificationService;

    public function __construct(TransportNotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Estimate price for a transport request
     */
    public function estimate(array $data)
    {
        $type = TransportType::from($data['type']);
        $distance = $data['distance'] ?? 0; // km
        $duration = $data['duration'] ?? 0; // minutes

        $rule = TransportPricingRule::where('type', $type)
            ->where('is_active', true)
            ->first();

        if (!$rule) {
            // Default pricing logic if no rule found
            $rule = (object)[
                'base_fare' => 500,
                'price_per_km' => 200,
                'price_per_minute' => 50,
                'minimum_fare' => 1000,
                'surge_multiplier' => 1.0
            ];
        }

        $price = $rule->base_fare + ($distance * $rule->price_per_km) + ($duration * $rule->price_per_minute);
        $price *= $rule->surge_multiplier;

        return max($price, $rule->minimum_fare);
    }

    /**
     * Create a new transport booking
     */
    public function createBooking(array $data)
    {
        // Validation for pre-assigned vehicles (Rental)
        if (isset($data['vehicle_id'])) {
            $vehicle = TransportVehicle::find($data['vehicle_id']);
            if ($vehicle && $vehicle->status !== 'active') {
                throw new \Exception("Ce véhicule n'est pas encore approuvé par l'administration.");
            }
        }

        return DB::transaction(function () use ($data) {
            $booking = TransportBooking::create($data);
            
            TransportLogger::info("New booking created", [
                'booking_uuid' => $booking->uuid,
                'user_id' => $booking->user_id,
                'type' => $booking->type->value
            ]);

            event(new \App\Domain\Transport\Events\TransportRequestCreated($booking));
            
            return $booking;
        });
    }

    /**
     * Update booking status
     */
    public function updateStatus(TransportBooking $booking, TransportStatus $status)
    {
        $oldStatus = $booking->status;
        $booking->update(['status' => $status]);
        
        TransportLogger::info("Status updated", [
            'booking_uuid' => $booking->uuid,
            'old_status' => $oldStatus->value,
            'new_status' => $status->value
        ]);

        // Handle specific status changes
        if ($status === TransportStatus::ASSIGNED && $oldStatus !== TransportStatus::ASSIGNED) {
            event(new \App\Domain\Transport\Events\BookingAssigned($booking));
            $this->notificationService->notifyBookingAccepted($booking);
        }

        if ($status === TransportStatus::DRIVER_ARRIVING) {
            $this->notificationService->notifyDriverArriving($booking);
        }

        if ($status === TransportStatus::IN_PROGRESS) {
            $booking->update(['started_at' => now()]);
        } elseif ($status === TransportStatus::COMPLETED) {
            $booking->update(['completed_at' => now()]);
            $this->notificationService->notifyBookingCompleted($booking);
        }
        
        return $booking;
    }

    /**
     * Initiate payment for a booking
     */
    public function initiatePayment(TransportBooking $booking, string $provider)
    {
        return DB::transaction(function () use ($booking, $provider) {
            $payment = \App\Payment::create([
                'user_id' => $booking->user_id,
                'transport_booking_id' => $booking->id,
                'amount' => $booking->total_price ?? $booking->estimated_price,
                'currency' => 'XAF',
                'provider' => $provider,
                'status' => 'PENDING',
            ]);

            $paymentService = app(\App\Services\PaymentService::class);
            $result = $paymentService->initiateExternalPayment($payment, collect([]), [
                'type' => 'transport',
                'booking_id' => $booking->id
            ]);

            $payment->update([
                'provider_reference' => $result['provider_reference'],
                'meta' => array_merge($payment->meta ?? [], $result['meta'] ?? [])
            ]);

            return [
                'payment' => $payment,
                'redirect_url' => $result['redirect_url'] ?? null
            ];
        });
    }
}

