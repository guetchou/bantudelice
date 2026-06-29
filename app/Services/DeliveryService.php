<?php

namespace App\Services;

use App\Delivery;
use App\Driver;
use App\Order;
use App\Payment;
use App\Restaurant;
use App\Services\CommerceRefundService;
use App\Services\CommerceSignalService;
use App\Services\DeliveryDispatchService;
use App\Services\DeliveryProofService;
use App\Services\FinancialLedgerService;
use App\Services\AuditLogService;
use App\Services\RiskService;
use App\Services\SupportTicketService;
use App\Domain\Food\Enums\OrderPaymentStatus;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Cycle de vie d'une livraison : création, assignation, transitions de statut,
 * incidents, re-livraison, résolution support.
 *
 * Le dispatch géographique est délégué à DeliveryDispatchService.
 * L'OTP et les preuves sont délégués à DeliveryProofService.
 * Les méthodes publiques de dispatch/proof sont conservées ici en façade
 * pour ne pas casser les callers existants.
 */
class DeliveryService
{
    public function __construct(
        protected ?FoodOrderStateMachineService $foodOrderStateMachine = null,
        protected ?CommerceRefundService $refunds = null,
        protected ?SupportTicketService $supportTickets = null,
        protected ?DeliveryDispatchService $dispatch = null,
        protected ?DeliveryProofService $proof = null,
    ) {}

    // ── Création ─────────────────────────────────────────────────────────────

    public function createForOrder(Order $order): Delivery
    {
        return Delivery::create([
            'order_id'      => $order->id,
            'restaurant_id' => $order->restaurant_id,
            'status'        => 'PENDING',
            'delivery_fee'  => (int) ($order->delivery_charges ?? 0),
        ]);
    }

    // ── Façades dispatch (délèguent à DeliveryDispatchService) ───────────────

    public function countOperationalDriversForRestaurant(
        Restaurant $restaurant,
        ?float $targetLat = null,
        ?float $targetLng = null,
        float $radiusKm = 8.0
    ): int {
        return $this->dispatch()->countOperationalDriversForRestaurant($restaurant, $targetLat, $targetLng, $radiusKm);
    }

    public function bestOperationalDriverForRestaurant(
        Restaurant $restaurant,
        ?float $targetLat = null,
        ?float $targetLng = null,
        float $radiusKm = 8.0
    ): ?Driver {
        return $this->dispatch()->bestOperationalDriverForRestaurant($restaurant, $targetLat, $targetLng, $radiusKm);
    }

    public function estimateDeliveryWindowForRestaurant(
        Restaurant $restaurant,
        ?float $targetLat = null,
        ?float $targetLng = null,
        float $radiusKm = 8.0
    ): array {
        return $this->dispatch()->estimateDeliveryWindowForRestaurant($restaurant, $targetLat, $targetLng, $radiusKm);
    }

    public function activeKitchenLoadForRestaurant(Restaurant $restaurant): int
    {
        return $this->dispatch()->activeKitchenLoadForRestaurant($restaurant);
    }

    public function preparationWindowForRestaurant(Restaurant $restaurant, ?int $kitchenLoad = null): array
    {
        return $this->dispatch()->preparationWindowForRestaurant($restaurant, $kitchenLoad);
    }

    public function restaurantCapacityState(int $kitchenLoad): string
    {
        return $this->dispatch()->restaurantCapacityState($kitchenLoad);
    }

    public function operationalDriversForRestaurant(
        Restaurant $restaurant,
        ?float $targetLat = null,
        ?float $targetLng = null,
        float $radiusKm = 8.0
    ) {
        return $this->dispatch()->operationalDriversForRestaurant($restaurant, $targetLat, $targetLng, $radiusKm);
    }

    // ── Façades OTP/proof (délèguent à DeliveryProofService) ─────────────────

    public function ensureDeliveryOtp(Delivery $delivery): Delivery
    {
        return $this->proof()->ensureDeliveryOtp($delivery);
    }

    public function verifyDeliveryOtp(Delivery $delivery, ?string $otp): bool
    {
        return $this->proof()->verifyDeliveryOtp($delivery, $otp);
    }

    public function storeProofFile(UploadedFile $file, string $prefix = 'delivery'): string
    {
        return $this->proof()->storeProofFile($file, $prefix);
    }

    // ── Assignation ──────────────────────────────────────────────────────────

    public function assignDriver(Delivery $delivery, Driver $driver): Delivery
    {
        if ($delivery->status !== 'PENDING') {
            throw new \Exception('Cette livraison ne peut pas être assignée (statut: ' . $delivery->status . ')');
        }

        $isAvailable = $driver->status === 'online' || ($driver->is_available ?? true);
        if (!$isAvailable) {
            throw new \Exception("Ce livreur n'est pas disponible");
        }

        return DB::transaction(function () use ($delivery, $driver) {
            $this->proof()->ensureDeliveryOtp($delivery);
            $this->stateMachine()->transitionOrderGroup($delivery->order->order_no, 'driver_assigned', [
                'actor_type' => 'system_dispatch',
                'actor_id'   => $driver->id,
                'reason_code' => 'driver_assigned',
            ]);

            $delivery->update([
                'driver_id'   => $driver->id,
                'status'      => 'ASSIGNED',
                'assigned_at' => now(),
            ]);

            if (Schema::hasColumn('drivers', 'is_available')) {
                $driver->update(['is_available' => false]);
            } elseif (Schema::hasColumn('drivers', 'status')) {
                $driver->update(['status' => 'busy']);
            }

            $delivery->order->update(['driver_id' => $driver->id]);

            return $delivery->fresh();
        });
    }

    // ── Transitions de statut ────────────────────────────────────────────────

    public function updateStatus(Delivery $delivery, string $status, array $context = []): Delivery
    {
        if ($status === 'ARRIVED_AT_RESTAURANT') {
            return $this->markArrivedAtRestaurant($delivery, $context);
        }

        $allowedStatuses = ['ASSIGNED', 'PICKED_UP', 'ON_THE_WAY', 'DELIVERED', 'CANCELLED'];

        if (!in_array($status, $allowedStatuses)) {
            throw new \Exception('Statut invalide: ' . $status);
        }

        $validTransitions = [
            'PENDING'   => ['ASSIGNED', 'CANCELLED'],
            'ASSIGNED'  => ['PICKED_UP', 'CANCELLED'],
            'PICKED_UP' => ['ON_THE_WAY', 'CANCELLED'],
            'ON_THE_WAY' => ['DELIVERED', 'CANCELLED'],
            'DELIVERED' => [],
            'CANCELLED' => [],
        ];

        if (!in_array($status, $validTransitions[$delivery->status] ?? [])) {
            throw new \Exception('Transition de statut invalide: ' . $delivery->status . ' → ' . $status);
        }

        return DB::transaction(function () use ($delivery, $status, $context) {
            $now = now();
            $data = ['status' => $status];

            if ($status === 'PICKED_UP') {
                $data['picked_up_at'] = $now;
                if (Schema::hasColumn('deliveries', 'pickup_notes')) {
                    $data['pickup_notes'] = $context['pickup_notes'] ?? $delivery->pickup_notes;
                }
                if (Schema::hasColumn('deliveries', 'pickup_proof_path')) {
                    $data['pickup_proof_path'] = $context['pickup_proof_path'] ?? $delivery->pickup_proof_path;
                }
                if (Schema::hasColumn('deliveries', 'pickup_latitude')) {
                    $data['pickup_latitude'] = $context['pickup_latitude'] ?? $delivery->pickup_latitude;
                }
                if (Schema::hasColumn('deliveries', 'pickup_longitude')) {
                    $data['pickup_longitude'] = $context['pickup_longitude'] ?? $delivery->pickup_longitude;
                }
                $this->stateMachine()->transitionOrderGroup($delivery->order->order_no, 'picked_up', [
                    'actor_type' => 'driver',
                    'actor_id' => $delivery->driver_id,
                    'pickup_notes' => $context['pickup_notes'] ?? null,
                ]);
            } elseif ($status === 'ON_THE_WAY') {
                if (Schema::hasColumn('deliveries', 'delivery_notes') && !empty($context['delivery_notes'])) {
                    $data['delivery_notes'] = $context['delivery_notes'];
                }
                $this->stateMachine()->transitionOrderGroup($delivery->order->order_no, 'out_for_delivery', [
                    'actor_type' => 'driver',
                    'actor_id' => $delivery->driver_id,
                    'delivery_notes' => $context['delivery_notes'] ?? null,
                ]);
            } elseif ($status === 'DELIVERED') {
                $this->proof()->assertDeliveryProofOrConfirmation($delivery, $context);
                $data['delivered_at'] = $now;
                if (Schema::hasColumn('deliveries', 'delivery_notes')) {
                    $data['delivery_notes'] = $context['delivery_notes'] ?? $delivery->delivery_notes;
                }
                if (Schema::hasColumn('deliveries', 'delivery_proof_path')) {
                    $data['delivery_proof_path'] = $context['delivery_proof_path'] ?? $delivery->delivery_proof_path;
                }
                if (Schema::hasColumn('deliveries', 'delivery_latitude')) {
                    $data['delivery_latitude'] = $context['delivery_latitude'] ?? $delivery->delivery_latitude;
                }
                if (Schema::hasColumn('deliveries', 'delivery_longitude')) {
                    $data['delivery_longitude'] = $context['delivery_longitude'] ?? $delivery->delivery_longitude;
                }
                if (Schema::hasColumn('deliveries', 'customer_confirmed_at') && !empty($context['customer_confirmed'])) {
                    $data['customer_confirmed_at'] = $now;
                }
                if (Schema::hasColumn('deliveries', 'otp_verified_at') && !empty($context['delivery_otp'])) {
                    $data['otp_verified_at'] = $now;
                }
                if (Schema::hasColumn('deliveries', 'delivery_confirmation_method')) {
                    $data['delivery_confirmation_method'] = $this->proof()->resolveConfirmationMethod($context);
                }
                $cashCollectionOutcome = $context['cash_collection_outcome'] ?? 'collected';
                if (Schema::hasColumn('deliveries', 'cash_collected_at') && $this->shouldMarkCashCollected($delivery->order) && $cashCollectionOutcome !== 'collection_failed') {
                    $data['cash_collected_at'] = $now;
                }

                $this->releaseDriverIfAssigned($delivery);
                $this->stateMachine()->transitionOrderGroup($delivery->order->order_no, 'delivered', [
                    'actor_type' => 'driver',
                    'actor_id'   => $delivery->driver_id,
                    'delivery_notes' => $context['delivery_notes'] ?? null,
                    'customer_confirmed' => !empty($context['customer_confirmed']),
                    'cash_collection_outcome' => $cashCollectionOutcome,
                ]);
                $delivery->order->update([
                    'payment_status' => $this->resolveOrderPaymentStatus($delivery->order),
                ]);
                $this->markPaymentCompletedIfNeeded($delivery->order, $now);
                $this->awardLoyaltyIfEligible($delivery->order);
            } elseif ($status === 'CANCELLED') {
                $this->releaseDriverIfAssigned($delivery);
                $this->stateMachine()->transitionOrderGroup($delivery->order->order_no, 'cancelled', [
                    'actor_type' => 'driver',
                    'actor_id'   => $delivery->driver_id,
                    'reason_code' => 'delivery_cancelled',
                ]);
                $delivery->order->update(['payment_status' => OrderPaymentStatus::FAILED->value]);
            }

            $delivery->update($data);

            app(CommerceSignalService::class)->emitDelivery($delivery, 'delivery.status_changed', [
                'module'     => 'food',
                'severity'   => $status === 'CANCELLED' ? 'warning' : 'info',
                'status'     => $status,
                'actor_type' => $context['actor_type'] ?? 'system',
                'actor_id'   => $context['actor_id'] ?? null,
            ]);

            app(RiskService::class)->assessOrder($delivery->order, [
                'module'           => 'food',
                'delivery_status'  => $status,
                'customer_confirmed' => !empty($context['customer_confirmed']),
                'delivery_proof'   => !empty($data['delivery_proof_path'] ?? null),
                'incident'         => false,
            ], 'delivery_status_changed');

            if ($status === 'DELIVERED') {
                app(FinancialLedgerService::class)->capture($delivery->order, (float) ($delivery->order->total ?? 0), [
                    'reference'  => $delivery->order->order_no,
                    'entry_type' => 'delivery_completed',
                    'module'     => 'food',
                    'actor_type' => 'driver',
                    'actor_id'   => $delivery->driver_id,
                    'status'     => 'posted',
                ]);
            } elseif ($status === 'CANCELLED') {
                app(FinancialLedgerService::class)->refund($delivery->order, (float) ($delivery->order->total ?? 0), [
                    'reference'  => $delivery->order->order_no,
                    'entry_type' => 'delivery_cancelled',
                    'module'     => 'food',
                    'actor_type' => 'driver',
                    'actor_id'   => $delivery->driver_id,
                    'status'     => 'posted',
                ]);
            }

            return $delivery->fresh();
        });
    }

    public function markArrivedAtRestaurant(Delivery $delivery, array $context = []): Delivery
    {
        if ($delivery->status !== 'ASSIGNED') {
            throw new \Exception('Transition de statut invalide: ' . $delivery->status . ' → ARRIVED_AT_RESTAURANT');
        }

        $delivery->loadMissing(['order', 'restaurant']);

        if (! $delivery->order) {
            throw new \RuntimeException('Commande introuvable pour cette livraison.');
        }

        return DB::transaction(function () use ($delivery, $context) {
            $fresh = Delivery::with(['order', 'restaurant'])
                ->whereKey($delivery->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($fresh->status !== 'ASSIGNED') {
                throw new \Exception('Transition de statut invalide: ' . $fresh->status . ' → ARRIVED_AT_RESTAURANT');
            }

            if (! empty($fresh->restaurant_arrived_at)) {
                return $fresh;
            }

            $latitude = $context['restaurant_arrival_latitude'] ?? null;
            $longitude = $context['restaurant_arrival_longitude'] ?? null;
            $this->assertArrivalNearRestaurant($fresh, $latitude, $longitude);

            $now = now();
            $payload = [
                'restaurant_arrived_at' => $now,
            ];

            if (Schema::hasColumn('deliveries', 'restaurant_arrival_latitude')) {
                $payload['restaurant_arrival_latitude'] = $latitude;
            }

            if (Schema::hasColumn('deliveries', 'restaurant_arrival_longitude')) {
                $payload['restaurant_arrival_longitude'] = $longitude;
            }

            $fresh->update($payload);

            $this->stateMachine()->transitionOrderGroup($fresh->order->order_no, 'driver_arrived_at_restaurant', [
                'actor_type' => $context['actor_type'] ?? 'driver',
                'actor_id' => $context['actor_id'] ?? $fresh->driver_id,
                'reason_code' => 'driver_arrived_at_restaurant',
            ]);

            app(CommerceSignalService::class)->emitDelivery($fresh, 'delivery.driver_arrived_at_restaurant', [
                'module' => 'food',
                'severity' => 'info',
                'status' => 'ARRIVED_AT_RESTAURANT',
                'actor_type' => $context['actor_type'] ?? 'driver',
                'actor_id' => $context['actor_id'] ?? $fresh->driver_id,
            ]);

            app(RiskService::class)->assessOrder($fresh->order, [
                'module' => 'food',
                'delivery_status' => 'ARRIVED_AT_RESTAURANT',
                'incident' => false,
            ], 'delivery_arrived_at_restaurant');

            return $fresh->fresh();
        });
    }

    protected function assertArrivalNearRestaurant(Delivery $delivery, $latitude, $longitude): void
    {
        $restaurant = $delivery->restaurant;
        $restaurantLat = $restaurant?->latitude;
        $restaurantLng = $restaurant?->longitude;

        if ($restaurantLat === null || $restaurantLng === null) {
            return;
        }

        if ($latitude === null || $longitude === null) {
            throw new \RuntimeException('Position GPS requise pour confirmer l’arrivée au restaurant.');
        }

        $distanceMeters = $this->distanceMeters(
            (float) $latitude,
            (float) $longitude,
            (float) $restaurantLat,
            (float) $restaurantLng
        );
        $radiusMeters = max(25, (int) config('food.delivery.restaurant_arrival_radius_meters', 500));

        if ($distanceMeters > $radiusMeters) {
            throw new \RuntimeException('Arrivée restaurant refusée: position trop éloignée du restaurant.');
        }
    }

    protected function distanceMeters(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadiusMeters = 6371000;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        return 2 * $earthRadiusMeters * atan2(sqrt($a), sqrt(1 - $a));
    }

    // ── Incidents ────────────────────────────────────────────────────────────

    public function reportIncident(Delivery $delivery, string $reason, array $context = []): Delivery
    {
        $allowedStatuses = ['ASSIGNED', 'PICKED_UP', 'ON_THE_WAY', 'DELIVERED'];
        if (!in_array($delivery->status, $allowedStatuses, true)) {
            throw new \RuntimeException('Cette livraison ne peut pas être signalée en incident dans son état actuel.');
        }

        $normalizedReason = trim(strtolower($reason));
        $failedAttemptReasons = ['customer_absent', 'address_issue', 'delivery_failed', 'recipient_unreachable', 'zone_inaccessible'];
        $targetStatus = in_array($normalizedReason, $failedAttemptReasons, true) ? 'delivery_attempt_failed' : 'incident_open';

        return DB::transaction(function () use ($delivery, $normalizedReason, $targetStatus, $context) {
            $now = now();
            $payload = [
                'incident_status'          => 'open',
                'incident_reason'          => $normalizedReason,
                'incident_notes'           => $context['notes'] ?? null,
                'incident_reported_by'     => $context['actor_type'] ?? 'system',
                'incident_reported_by_id'  => $context['actor_id'] ?? null,
                'incident_reported_at'     => $now,
                'support_status'           => $context['support_status'] ?? 'open',
            ];

            if (!empty($context['support_notes']) || !empty($context['notes'])) {
                $payload['support_notes'] = $context['support_notes'] ?? $context['notes'];
            }

            if ($targetStatus === 'delivery_attempt_failed') {
                $payload['failed_attempts'] = (int) ($delivery->failed_attempts ?? 0) + 1;
                $payload['last_failed_attempt_at'] = $now;
                if ($normalizedReason === 'customer_absent') {
                    $payload['customer_absent_at'] = $now;
                }
            }

            $delivery->update($payload);

            $this->stateMachine()->transitionOrderGroup($delivery->order->order_no, $targetStatus, [
                'actor_type'       => $context['actor_type'] ?? 'system',
                'actor_id'         => $context['actor_id'] ?? null,
                'reason_code'      => $normalizedReason,
                'notes'            => $context['notes'] ?? null,
                'technical_status' => $targetStatus === 'delivery_attempt_failed' ? 'dispatch_retry' : null,
                'force'            => !empty($context['force']),
            ]);

            app(CommerceSignalService::class)->emitDelivery($delivery, 'delivery.incident_reported', [
                'module'          => 'food',
                'severity'        => 'warning',
                'incident_reason' => $normalizedReason,
                'target_status'   => $targetStatus,
                'actor_type'      => $context['actor_type'] ?? 'system',
                'actor_id'        => $context['actor_id'] ?? null,
                'notes'           => $context['notes'] ?? null,
            ]);

            app(RiskService::class)->assessOrder($delivery->order, [
                'module'          => 'food',
                'incident_reason' => $normalizedReason,
                'failed_attempt'  => $targetStatus === 'delivery_attempt_failed',
                'customer_absent' => $normalizedReason === 'customer_absent',
            ], 'delivery_incident');

            if (config('commerce.support.auto_ticket_on_incident', true)) {
                $this->supportTickets()?->openFromDelivery($delivery->fresh(), 'incident', 'Incident livraison', 'Un incident de livraison a été signalé.', [
                    'opened_by_type' => $context['actor_type'] ?? 'system',
                    'opened_by_id'   => $context['actor_id'] ?? null,
                    'priority'       => $normalizedReason === 'zone_inaccessible' ? 'urgent' : ($targetStatus === 'delivery_attempt_failed' ? 'high' : 'normal'),
                    'status'         => 'open',
                    'incident_reason' => $normalizedReason,
                    'target_status'  => $targetStatus,
                ]);
            }

            // T1.4 — Zone inaccessible : log critique + notification admin
            if ($normalizedReason === 'zone_inaccessible') {
                \Log::critical('T1.4 Zone inaccessible signalée', [
                    'delivery_id'   => $delivery->id,
                    'order_no'      => $delivery->order->order_no ?? null,
                    'driver_id'     => $context['actor_id'] ?? null,
                    'notes'         => $context['notes'] ?? null,
                    'restaurant'    => $delivery->restaurant->name ?? null,
                    'address'       => $delivery->order->delivery_address ?? null,
                    'reported_at'   => now()->toIso8601String(),
                ]);
            }

            return $delivery->fresh();
        });
    }

    // ── Encaissement cash ───────────────────────────────────────────────────

    public function disputeCashCollection(Order $order, array $context = []): Order
    {
        if ($order->payment_method !== 'cash') {
            throw new \RuntimeException('Cette commande n\'est pas en paiement cash.');
        }

        if ($order->cash_collection_status !== 'collected') {
            throw new \RuntimeException("Impossible de contester : statut actuel '{$order->cash_collection_status}', seul 'collected' peut être contesté.");
        }

        return DB::transaction(function () use ($order, $context) {
            $order->update([
                'cash_collection_status' => 'disputed',
                'cash_collection_reference' => $context['notes'] ?? null,
            ]);

            app(AuditLogService::class)->record([
                'actor_type' => $context['actor_type'] ?? 'restaurant',
                'actor_id' => $context['actor_id'] ?? null,
                'target_type' => 'food_order',
                'target_id' => $order->id,
                'target_ref' => $order->order_no,
                'action' => 'cash_collection_disputed',
                'meta' => ['notes' => $context['notes'] ?? null],
            ]);

            app(RiskService::class)->assessOrder($order, [
                'module' => 'food',
                'cash_dispute' => true,
            ], 'cash_collection_disputed');

            return $order->fresh();
        });
    }

    public function resolveCashDispute(Order $order, string $resolution, array $context = []): Order
    {
        if (!in_array($order->cash_collection_status, ['disputed', 'collection_failed'], true)) {
            throw new \RuntimeException("Aucun litige actif sur cette commande (statut actuel : '{$order->cash_collection_status}').");
        }

        $targetStatus = match ($resolution) {
            'confirmed_collected' => 'collected',
            'confirmed_not_collected' => 'collection_failed',
            default => throw new \RuntimeException("Résolution invalide : {$resolution}"),
        };

        return DB::transaction(function () use ($order, $targetStatus, $context) {
            $order->update(['cash_collection_status' => $targetStatus]);

            app(AuditLogService::class)->record([
                'actor_type' => $context['actor_type'] ?? 'admin',
                'actor_id' => $context['actor_id'] ?? null,
                'target_type' => 'food_order',
                'target_id' => $order->id,
                'target_ref' => $order->order_no,
                'action' => 'cash_dispute_resolved',
                'meta' => ['resolution' => $targetStatus],
            ]);

            return $order->fresh();
        });
    }

    // ── Re-livraison ─────────────────────────────────────────────────────────

    public function requestRedelivery(Delivery $delivery, array $context = []): Delivery
    {
        if ($delivery->status === 'CANCELLED') {
            throw new \RuntimeException('Cette livraison est annulée et ne peut pas être relancée.');
        }

        return DB::transaction(function () use ($delivery, $context) {
            $now = now();
            $nextDeliveryStatus = $delivery->picked_up_at ? 'ON_THE_WAY' : ($delivery->driver_id ? 'ASSIGNED' : 'PENDING');
            $nextBusinessStatus = $delivery->picked_up_at ? 'out_for_delivery' : 'driver_assigned';

            $delivery->update([
                'status'                   => $nextDeliveryStatus,
                'redelivery_requested_at'  => $now,
                'incident_status'          => 'open',
                'support_status'           => 'pending_redelivery',
                'support_notes'            => $context['support_notes'] ?? $context['notes'] ?? $delivery->support_notes,
            ]);

            $this->stateMachine()->transitionOrderGroup($delivery->order->order_no, $nextBusinessStatus, [
                'actor_type'       => $context['actor_type'] ?? 'system',
                'actor_id'         => $context['actor_id'] ?? null,
                'reason_code'      => 'redelivery_requested',
                'notes'            => $context['notes'] ?? null,
                'technical_status' => 'dispatch_retry',
                'force'            => true,
            ]);

            app(CommerceSignalService::class)->emitDelivery($delivery, 'delivery.redelivery_requested', [
                'module'     => 'food',
                'severity'   => 'warning',
                'actor_type' => $context['actor_type'] ?? 'system',
                'actor_id'   => $context['actor_id'] ?? null,
                'notes'      => $context['notes'] ?? null,
            ]);

            $this->supportTickets()?->updateForDelivery($delivery->fresh(), 'pending_redelivery', [
                'resolution_notes' => $context['support_notes'] ?? $context['notes'] ?? null,
                'resolved_by_id'   => $context['actor_id'] ?? null,
            ]);

            return $delivery->fresh();
        });
    }

    // ── Reset modification commande ───────────────────────────────────────────

    public function resetForOrderModification(Delivery $delivery, array $context = []): Delivery
    {
        $allowedStatuses = ['PENDING', 'ASSIGNED'];
        if (!in_array($delivery->status, $allowedStatuses, true)) {
            throw new \RuntimeException('Cette livraison ne peut plus être modifiée à ce stade.');
        }

        return DB::transaction(function () use ($delivery, $context) {
            $this->releaseDriverIfAssigned($delivery);

            $delivery->update([
                'driver_id'                   => null,
                'status'                      => 'PENDING',
                'assigned_at'                 => null,
                'picked_up_at'                => null,
                'delivered_at'                => null,
                'delivery_notes'              => $context['delivery_notes'] ?? $delivery->delivery_notes,
                'pickup_notes'                => $context['pickup_notes'] ?? $delivery->pickup_notes,
                'pickup_proof_path'           => null,
                'delivery_proof_path'         => null,
                'delivery_otp_code'           => null,
                'delivery_otp_expires_at'     => null,
                'otp_verified_at'             => null,
                'delivery_confirmation_method' => null,
                'pickup_latitude'             => $context['pickup_latitude'] ?? $delivery->pickup_latitude,
                'pickup_longitude'            => $context['pickup_longitude'] ?? $delivery->pickup_longitude,
                'delivery_latitude'           => $context['delivery_latitude'] ?? $delivery->delivery_latitude,
                'delivery_longitude'          => $context['delivery_longitude'] ?? $delivery->delivery_longitude,
                'incident_status'             => null,
                'incident_reason'             => null,
                'incident_notes'              => null,
                'incident_reported_by'        => null,
                'incident_reported_by_id'     => null,
                'incident_reported_at'        => null,
                'failed_attempts'             => 0,
                'last_failed_attempt_at'      => null,
                'customer_absent_at'          => null,
                'redelivery_requested_at'     => null,
                'support_status'              => null,
                'support_notes'               => null,
                'support_resolved_at'         => null,
                'support_resolved_by'         => null,
            ]);

            return $delivery->fresh();
        });
    }

    // ── Résolution support ────────────────────────────────────────────────────

    public function resolveSupportCase(Delivery $delivery, string $resolution, array $context = []): Delivery
    {
        $resolution = trim(strtolower($resolution));

        return match ($resolution) {
            'redelivery' => $this->requestRedelivery($delivery, array_merge($context, ['support_status' => 'pending_redelivery'])),
            'cancelled' => DB::transaction(function () use ($delivery, $context) {
                $this->releaseDriverIfAssigned($delivery);
                $delivery->update([
                    'status'              => 'CANCELLED',
                    'incident_status'     => 'resolved',
                    'support_status'      => 'resolved',
                    'support_notes'       => $context['support_notes'] ?? $context['notes'] ?? $delivery->support_notes,
                    'support_resolved_at' => now(),
                    'support_resolved_by' => $context['actor_id'] ?? null,
                ]);
                $this->refunds()?->refundOrder($delivery->order, 'delivery_cancelled', [
                    'actor_type'       => $context['actor_type'] ?? 'admin',
                    'actor_id'         => $context['actor_id'] ?? null,
                    'idempotency_key'  => 'delivery-cancelled-' . $delivery->order_id,
                    'amount'           => (float) ($delivery->order->total ?? 0),
                ]);
                $this->stateMachine()->transitionOrderGroup($delivery->order->order_no, 'cancelled', [
                    'actor_type'     => $context['actor_type'] ?? 'admin',
                    'actor_id'       => $context['actor_id'] ?? null,
                    'reason_code'    => 'support_cancelled',
                    'notes'          => $context['notes'] ?? null,
                    'payment_status' => $context['payment_status'] ?? OrderPaymentStatus::FAILED->value,
                    'force'          => true,
                ]);
                app(CommerceSignalService::class)->emitDelivery($delivery, 'delivery.support_cancelled', [
                    'module'     => 'food',
                    'severity'   => 'warning',
                    'actor_type' => $context['actor_type'] ?? 'admin',
                    'actor_id'   => $context['actor_id'] ?? null,
                    'notes'      => $context['notes'] ?? null,
                ]);
                $this->supportTickets()?->updateForDelivery($delivery->fresh(), 'resolved', [
                    'resolution_notes' => $context['support_notes'] ?? $context['notes'] ?? null,
                    'resolved_by_id'   => $context['actor_id'] ?? null,
                ]);
                return $delivery->fresh();
            }),
            'resolved', 'closed' => DB::transaction(function () use ($delivery, $context) {
                $delivery->update([
                    'incident_status'     => 'resolved',
                    'support_status'      => 'resolved',
                    'support_notes'       => $context['support_notes'] ?? $context['notes'] ?? $delivery->support_notes,
                    'support_resolved_at' => now(),
                    'support_resolved_by' => $context['actor_id'] ?? null,
                ]);
                if ($delivery->status === 'DELIVERED') {
                    $this->stateMachine()->transitionOrderGroup($delivery->order->order_no, 'closed', [
                        'actor_type'  => $context['actor_type'] ?? 'admin',
                        'actor_id'    => $context['actor_id'] ?? null,
                        'reason_code' => 'support_resolved',
                        'notes'       => $context['notes'] ?? null,
                        'force'       => true,
                    ]);
                }
                app(CommerceSignalService::class)->emitDelivery($delivery, 'delivery.support_resolved', [
                    'module'     => 'food',
                    'severity'   => 'info',
                    'actor_type' => $context['actor_type'] ?? 'admin',
                    'actor_id'   => $context['actor_id'] ?? null,
                    'notes'      => $context['notes'] ?? null,
                ]);
                $this->supportTickets()?->updateForDelivery($delivery->fresh(), 'resolved', [
                    'resolution_notes' => $context['support_notes'] ?? $context['notes'] ?? null,
                    'resolved_by_id'   => $context['actor_id'] ?? null,
                ]);
                return $delivery->fresh();
            }),
            default => throw new \RuntimeException('Résolution support non reconnue.'),
        };
    }

    // ── Requêtes simples ─────────────────────────────────────────────────────

    public function getActiveDeliveriesForDriver(Driver $driver)
    {
        return Delivery::with(['order', 'restaurant', 'order.user'])
            ->where('driver_id', $driver->id)
            ->whereIn('status', ['ASSIGNED', 'PICKED_UP', 'ON_THE_WAY'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function getPendingDeliveries()
    {
        return Delivery::with(['order', 'restaurant'])
            ->where('status', 'PENDING')
            ->orderBy('created_at', 'asc')
            ->get();
    }

    // ── Helpers privés ────────────────────────────────────────────────────────

    protected function releaseDriverIfAssigned(Delivery $delivery): void
    {
        if (!$delivery->driver) {
            return;
        }

        if (Schema::hasColumn('drivers', 'is_available')) {
            $delivery->driver->update(['is_available' => true]);
        } elseif (Schema::hasColumn('drivers', 'status')) {
            $delivery->driver->update(['status' => 'online']);
        }
    }

    protected function shouldMarkCashCollected(Order $order): bool
    {
        return in_array($order->payment_method, ['cash'], true);
    }

    protected function resolveOrderPaymentStatus(Order $order): string
    {
        if ($this->shouldMarkCashCollected($order)) {
            return OrderPaymentStatus::PAID->value;
        }

        return $order->payment_status === OrderPaymentStatus::PAID->value
            ? OrderPaymentStatus::PAID->value
            : $order->payment_status;
    }

    protected function markPaymentCompletedIfNeeded(Order $order, $paidAt): void
    {
        $payment = Payment::where('order_id', $order->id)->latest('id')->first();

        if (!$payment) {
            return;
        }

        if ($this->shouldMarkCashCollected($order) && $payment->status !== 'PAID') {
            $payment->update([
                'status' => 'PAID',
                'meta'   => array_merge($payment->meta ?? [], [
                    'cash_collected_at' => $paidAt->toIso8601String(),
                ]),
            ]);
            return;
        }

        if ($payment->status === 'PAID') {
            $payment->update([
                'meta' => array_merge($payment->meta ?? [], [
                    'delivered_at' => $paidAt->toIso8601String(),
                ]),
            ]);
        }
    }

    protected function awardLoyaltyIfEligible(Order $order): void
    {
        if (LoyaltyService::hasEarnedTransactionForOrder($order->id)) {
            return;
        }

        LoyaltyService::addPointsFromOrder($order->user_id, $order->id, (float) $order->total);
    }

    protected function stateMachine(): FoodOrderStateMachineService
    {
        return $this->foodOrderStateMachine ?? app(FoodOrderStateMachineService::class);
    }

    protected function dispatch(): DeliveryDispatchService
    {
        return $this->dispatch ?? app(DeliveryDispatchService::class);
    }

    protected function proof(): DeliveryProofService
    {
        return $this->proof ?? app(DeliveryProofService::class);
    }

    protected function refunds(): ?CommerceRefundService
    {
        return $this->refunds ?: app(CommerceRefundService::class);
    }

    protected function supportTickets(): ?SupportTicketService
    {
        return $this->supportTickets ?: app(SupportTicketService::class);
    }
}
