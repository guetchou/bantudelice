<?php

namespace App\Domain\Transport\Services;

use App\Domain\Transport\Events\TransportBookingStatusUpdated;
use App\Domain\Transport\Events\TransportMissionPresenceUpdated;
use App\Domain\Transport\Models\TransportBooking;
use App\Domain\Transport\Models\TransportPricingRule;
use App\Domain\Transport\Models\TransportVehicle;
use App\Domain\Transport\Enums\TransportType;
use App\Domain\Transport\Enums\TransportStatus;
use App\Services\AuditLogService;
use App\Services\FinancialEventService;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class TransportService
{
    protected $notificationService;

    protected array $allowedTransitions = [
        'requested' => ['assigned', 'cancelled'],
        'assigned' => ['driver_arriving', 'cancelled'],
        'driver_arriving' => ['picked_up', 'cancelled'],
        'picked_up' => ['in_progress', 'cancelled'],
        'in_progress' => ['completed', 'cancelled'],
        'completed' => ['paid', 'closed'],
        'paid' => ['closed'],
        'cancelled' => ['closed'],
        'closed' => [],
        'published' => ['booked', 'cancelled'],
        'booked' => ['confirmed', 'cancelled'],
        'confirmed' => ['completed', 'cancelled'],
        'draft' => ['requested', 'cancelled'],
        'offered' => ['booked', 'cancelled'],
        'returned' => ['closed'],
        'inspected' => ['closed'],
    ];

    public function __construct(
        TransportNotificationService $notificationService,
        protected ?FinancialEventService $financialEventService = null,
        protected ?AuditLogService $auditLogService = null
    ) {
        $this->notificationService = $notificationService;
    }

    public function estimate(array $data)
    {
        $type = TransportType::from($data['type']);
        $distance = $data['distance'] ?? 0;
        $duration = $data['duration'] ?? 0;

        $rule = TransportPricingRule::where('type', $type)
            ->where('is_active', true)
            ->first();

        if (! $rule) {
            $rule = (object) [
                'base_fare' => 500,
                'price_per_km' => 200,
                'price_per_minute' => 50,
                'minimum_fare' => 1000,
                'surge_multiplier' => 1.0,
            ];
        }

        $price = $rule->base_fare + ($distance * $rule->price_per_km) + ($duration * $rule->price_per_minute);
        $price *= $rule->surge_multiplier;

        return max($price, $rule->minimum_fare);
    }

    public function createBooking(array $data)
    {
        if (isset($data['vehicle_id'])) {
            $vehicle = TransportVehicle::find($data['vehicle_id']);
            if ($vehicle && $vehicle->status !== 'active') {
                throw new RuntimeException("Ce véhicule n'est pas encore approuvé par l'administration.");
            }
        }

        return DB::transaction(function () use ($data) {
            if (empty($data['total_price'])) {
                $data['total_price'] = $data['estimated_price'] ?? 0;
            }

            if (empty($data['payment_method'])) {
                $data['payment_method'] = 'cash';
            }

            $data = $this->normalizeBookingPaymentPayload($data);

            $booking = TransportBooking::create($data);

            TransportLogger::info("New booking created", [
                'booking_uuid' => $booking->uuid,
                'user_id' => $booking->user_id,
                'type' => $booking->type->value,
            ]);

            $this->financialEvents()->record([
                'order_no' => $booking->booking_no,
                'order_id' => null,
                'payment_id' => null,
                'user_id' => $booking->user_id,
                'event_type' => 'transport_booking_created',
                'provider' => $booking->payment_method,
                'amount' => $booking->total_price ?? $booking->estimated_price,
                'currency' => 'XAF',
                'status' => $booking->payment_status,
                'meta' => [
                    'booking_id' => $booking->id,
                    'transport_type' => $booking->type->value,
                ],
            ]);

            event(new \App\Domain\Transport\Events\TransportRequestCreated($booking));
            event(new TransportBookingStatusUpdated($booking->fresh(['driver', 'vehicle'])));

            return $booking;
        });
    }

    public function updateStatus(TransportBooking $booking, TransportStatus $status)
    {
        $oldStatus = $booking->status;

        if (! $this->canTransition($oldStatus, $status)) {
            throw new RuntimeException("Transition de {$oldStatus->value} vers {$status->value} non autorisée.");
        }

        $payload = [
            'status' => $status,
            'last_status_changed_at' => now(),
        ];

        if ($status === TransportStatus::DRIVER_ARRIVING) {
            $payload['driver_arrived_at'] = now();
        } elseif ($status === TransportStatus::PICKED_UP) {
            $payload['picked_up_at'] = now();
        } elseif ($status === TransportStatus::IN_PROGRESS && ! $booking->started_at) {
            $payload['started_at'] = now();
        } elseif ($status === TransportStatus::COMPLETED) {
            $payload['completed_at'] = now();
            if (empty($payload['actual_price'])) {
                $payload['actual_price'] = $booking->actual_price ?: ($booking->total_price ?: $booking->estimated_price);
            }
        } elseif ($status === TransportStatus::CANCELLED) {
            $payload['cancelled_at'] = now();
        } elseif ($status === TransportStatus::CLOSED) {
            $payload['closed_at'] = now();
        }

        if ($status === TransportStatus::PAID) {
            $payload['payment_status'] = 'paid';
        } elseif ($booking->payment_method === 'cash' && !in_array($status, [TransportStatus::COMPLETED, TransportStatus::PAID, TransportStatus::CLOSED], true)) {
            $payload['payment_status'] = 'pending_cash';
        }

        $booking->update($payload);
        $booking = $booking->fresh(['driver', 'vehicle', 'trackingPoints']);

        TransportLogger::info("Status updated", [
            'booking_uuid' => $booking->uuid,
            'old_status' => $oldStatus->value,
            'new_status' => $status->value,
        ]);

        $this->financialEvents()->record([
            'order_no' => $booking->booking_no,
            'order_id' => null,
            'payment_id' => optional($booking->payments()->latest('id')->first())->id,
            'user_id' => $booking->user_id,
            'event_type' => 'transport_status_changed',
            'provider' => $booking->payment_method,
            'amount' => $booking->actual_price ?: ($booking->total_price ?: $booking->estimated_price),
            'currency' => 'XAF',
            'status' => $booking->payment_status,
            'meta' => [
                'booking_id' => $booking->id,
                'old_status' => $oldStatus->value,
                'new_status' => $status->value,
                'driver_id' => $booking->driver_id,
            ],
        ]);

        $this->auditLogs()->record([
            'actor_type' => 'system',
            'actor_id' => null,
            'target_type' => 'transport_booking',
            'target_id' => $booking->id,
            'target_ref' => $booking->booking_no,
            'action' => 'status_transition',
            'status' => $status->value,
            'meta' => [
                'old_status' => $oldStatus->value,
                'new_status' => $status->value,
                'driver_id' => $booking->driver_id,
            ],
        ]);

        if ($status === TransportStatus::ASSIGNED && $oldStatus !== TransportStatus::ASSIGNED) {
            event(new \App\Domain\Transport\Events\BookingAssigned($booking));
            enqueue_job('transport', 'send_transport_status_notification', [
                'booking_id' => $booking->id,
                'notification_type' => 'booking_accepted',
            ]);
        }

        event(new TransportBookingStatusUpdated($booking));

        if ($booking->driver_id) {
            event(new TransportMissionPresenceUpdated($booking));
        }

        if ($status === TransportStatus::DRIVER_ARRIVING) {
            enqueue_job('transport', 'send_transport_status_notification', [
                'booking_id' => $booking->id,
                'notification_type' => 'driver_arriving',
            ]);
        }

        if ($status === TransportStatus::PICKED_UP || $status === TransportStatus::IN_PROGRESS) {
            enqueue_job('transport', 'send_transport_status_notification', [
                'booking_id' => $booking->id,
                'notification_type' => 'trip_started',
            ]);
        }

        if ($status === TransportStatus::COMPLETED) {
            $this->completeFinancialSideIfNeeded($booking->fresh());
            enqueue_job('transport', 'send_transport_status_notification', [
                'booking_id' => $booking->id,
                'notification_type' => 'booking_completed',
            ]);

            $freshCompletedBooking = $booking->fresh();
            if (
                $freshCompletedBooking->payment_method !== 'cash' &&
                strtolower((string) $freshCompletedBooking->payment_status) === 'paid'
            ) {
                return $this->updateStatus($freshCompletedBooking, TransportStatus::PAID);
            }
        }

        if ($status === TransportStatus::CANCELLED) {
            enqueue_job('transport', 'send_transport_status_notification', [
                'booking_id' => $booking->id,
                'notification_type' => 'booking_cancelled',
            ]);
        }

        return $booking->fresh();
    }

    public function initiatePayment(TransportBooking $booking, string $provider, array $context = [])
    {
        return DB::transaction(function () use ($booking, $provider, $context) {
            if ($provider === 'cash') {
                throw new RuntimeException("Le paiement cash ne s'initie pas en ligne.");
            }

            $booking->update([
                'payment_method' => $provider,
                'payment_status' => 'pending',
            ]);

            $paymentFlow = app(\App\Services\PaymentService::class)->startManagedPayment([
                'user_id' => $booking->user_id,
                'transport_booking_id' => $booking->id,
                'amount' => $this->normalizePaymentAmount($booking->total_price ?? $booking->estimated_price),
                'currency' => 'XAF',
                'provider' => $provider,
            ], [
                'type' => 'transport',
                'booking_id' => $booking->id,
                'phone' => $context['phone'] ?? ($booking->user->phone ?? null),
                'transport_payment_context' => [
                    'booking_no' => $booking->booking_no,
                    'payment_method' => $booking->payment_method,
                ],
            ]);

            $payment = $paymentFlow['payment'];

            $this->financialEvents()->recordForPayment($payment, 'transport_payment_initiated', [
                'booking_id' => $booking->id,
                'booking_no' => $booking->booking_no,
            ]);

            return [
                'payment' => $payment,
                'redirect_url' => $paymentFlow['payment_payload']['redirect_url'] ?? null,
            ];
        });
    }

    protected function canTransition(TransportStatus $from, TransportStatus $to): bool
    {
        return in_array($to->value, $this->allowedTransitions[$from->value] ?? [], true);
    }

    protected function normalizePaymentAmount($amount): int
    {
        if (!is_numeric($amount)) {
            return 0;
        }

        return max(0, (int) round((float) $amount));
    }

    protected function normalizeBookingPaymentPayload(array $data): array
    {
        $paymentMethod = strtolower((string) ($data['payment_method'] ?? 'cash'));
        $paymentStatus = strtolower((string) ($data['payment_status'] ?? ''));

        if ($paymentMethod === 'cash') {
            $data['payment_status'] = in_array($paymentStatus, ['paid', 'refunded', 'cancelled'], true)
                ? $paymentStatus
                : 'pending_cash';

            return $data;
        }

        $data['payment_status'] = in_array($paymentStatus, ['paid', 'failed', 'cancelled', 'refunded'], true)
            ? $paymentStatus
            : 'pending';

        return $data;
    }

    protected function completeFinancialSideIfNeeded(TransportBooking $booking): void
    {
        if ($booking->payment_method !== 'cash') {
            return;
        }

        $payment = $booking->payments()->latest('id')->first();
        if ($payment && strtoupper((string) $payment->status) !== 'PAID') {
            $payment->update([
                'status' => 'PAID',
                'meta' => array_merge($payment->meta ?? [], [
                    'cash_collected_at' => now()->toIso8601String(),
                ]),
            ]);
            $this->financialEvents()->recordForPayment($payment->fresh(), 'transport_cash_collected', [
                'booking_no' => $booking->booking_no,
            ]);
        }

        $booking->update([
            'payment_status' => 'paid',
            'cash_collected_at' => now(),
        ]);

        $this->financialEvents()->record([
            'order_no' => $booking->booking_no,
            'order_id' => null,
            'payment_id' => optional($payment)->id,
            'user_id' => $booking->user_id,
            'event_type' => 'transport_completed_paid',
            'provider' => $booking->payment_method,
            'amount' => $booking->actual_price ?: ($booking->total_price ?: $booking->estimated_price),
            'currency' => 'XAF',
            'status' => $booking->payment_status,
            'meta' => [
                'booking_id' => $booking->id,
                'driver_id' => $booking->driver_id,
            ],
        ]);
    }

    protected function financialEvents(): FinancialEventService
    {
        return $this->financialEventService ?? app(FinancialEventService::class);
    }

    protected function auditLogs(): AuditLogService
    {
        return $this->auditLogService ?? app(AuditLogService::class);
    }
}
