<?php

namespace App\Services;

use App\Domain\Food\Enums\OrderPaymentStatus;
use App\Domain\Food\Events\FoodDriverOrderUpdated;
use App\Domain\Food\Events\FoodMissionPresenceUpdated;
use App\Domain\Food\Events\FoodOrderStatusUpdated;
use App\Domain\Food\Events\FoodRestaurantOrderUpdated;
use App\Order;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class FoodOrderStateMachineService
{
    public const FLOW_DELIVERY = 'food_delivery';
    public const FLOW_PICKUP = 'food_pickup';

    /** Statuts à partir desquels une livraison peut être dispatchée à un livreur — jamais avant acceptation + paiement. */
    public const DISPATCHABLE_BUSINESS_STATUSES = ['in_kitchen', 'ready_for_pickup'];

    public function __construct(
        protected ?NotificationService $notificationService = null,
        protected ?AuditLogService $auditLogService = null
    ) {}

    public function transitionOrderGroup(string $orderNo, string $targetStatus, array $context = []): Collection
    {
        $orders = Order::where('order_no', $orderNo)->orderBy('id')->get();

        if ($orders->isEmpty()) {
            throw new RuntimeException("Commande introuvable: {$orderNo}");
        }

        return $this->transitionOrders($orders, $targetStatus, $context);
    }

    public function transitionOrders(Collection $orders, string $targetStatus, array $context = []): Collection
    {
        $targetBusinessStatus = $this->normalizeBusinessStatus($targetStatus);
        $flow = $this->resolveFlow($orders->first());
        $currentBusinessStatus = $this->resolveCurrentBusinessStatus($orders->first());
        $orderIds = $orders->pluck('id')->filter()->values()->all();

        if (!$this->canTransition($flow, $currentBusinessStatus, $targetBusinessStatus, !empty($context['force']))) {
            throw new RuntimeException("Transition invalide: {$currentBusinessStatus} -> {$targetBusinessStatus}");
        }

        if ($targetBusinessStatus === 'in_kitchen') {
            $this->guardInKitchenRequiresConfirmedAndPaid($orders->first(), $currentBusinessStatus, $context);
        }

        $transitioned = DB::transaction(function () use ($orders, $orderIds, $currentBusinessStatus, $targetBusinessStatus, $context, $flow) {
            $now = now();
            $legacyStatus = $this->mapBusinessToLegacy($targetBusinessStatus, $orders->first());
            $technicalStatus = $context['technical_status'] ?? null;

            foreach ($orders as $order) {
                $payload = [
                    'status' => $legacyStatus,
                    'updated_at' => $now,
                ];

                if (Schema::hasColumn('orders', 'business_status')) {
                    $payload['business_status'] = $targetBusinessStatus;
                }

                if (Schema::hasColumn('orders', 'technical_status')) {
                    $payload['technical_status'] = $technicalStatus;
                }

                if (Schema::hasColumn('orders', 'accepted_at') && in_array($targetBusinessStatus, ['accepted', 'accepted_awaiting_payment', 'confirmed'], true) && empty($order->accepted_at)) {
                    $payload['accepted_at'] = $now;
                }

                if (Schema::hasColumn('orders', 'preparation_started_at') && $targetBusinessStatus === 'in_kitchen' && empty($order->preparation_started_at)) {
                    $payload['preparation_started_at'] = $now;
                }

                if (Schema::hasColumn('orders', 'ready_at') && $targetBusinessStatus === 'ready_for_pickup') {
                    $payload['ready_at'] = $now;
                }

                if (Schema::hasColumn('orders', 'customer_arrived_at') && $targetBusinessStatus === 'customer_arrived') {
                    $payload['customer_arrived_at'] = $now;
                }

                if (Schema::hasColumn('orders', 'customer_picked_up_at') && in_array($targetBusinessStatus, ['picked_up_by_customer', 'closed'], true)) {
                    $payload['customer_picked_up_at'] = $now;
                }

                if (Schema::hasColumn('orders', 'cancelled_at') && $targetBusinessStatus === 'cancelled') {
                    $payload['cancelled_at'] = $now;
                }

                if ($targetBusinessStatus === 'delivered' || ($flow === self::FLOW_PICKUP && in_array($targetBusinessStatus, ['picked_up_by_customer', 'closed'], true))) {
                    $payload['delivered_time'] = $now;
                }

                if (
                    $order->payment_method === 'cash'
                    && Schema::hasColumn('orders', 'cash_collection_status')
                    && !in_array($order->cash_collection_status, ['disputed', 'collection_failed'], true)
                    && ($targetBusinessStatus === 'delivered'
                        || ($flow === self::FLOW_PICKUP && in_array($targetBusinessStatus, ['picked_up_by_customer', 'closed'], true)))
                ) {
                    if (($context['cash_collection_outcome'] ?? 'collected') === 'collection_failed') {
                        $payload['cash_collection_status'] = 'collection_failed';
                    } else {
                        $payload['cash_collection_status'] = 'collected';
                        $payload['cash_collected_at'] = $now;
                        $payload['cash_collected_by'] = $flow === self::FLOW_PICKUP
                            ? optional($order->restaurant)->user_id
                            : ($context['actor_id'] ?? $order->driver_id);
                        $payload['cash_collection_confirmed_at'] = $now;
                    }
                }

                if ($targetBusinessStatus === 'cancelled') {
                    $payload['payment_status'] = $context['payment_status'] ?? $order->payment_status;
                }

                $order->forceFill($payload)->save();

                $this->logTransition($order, $currentBusinessStatus, $targetBusinessStatus, $legacyStatus, $context, $now);
                $this->auditLogs()->record([
                    'actor_type' => $context['actor_type'] ?? 'system',
                    'actor_id' => $context['actor_id'] ?? null,
                    'target_type' => 'food_order',
                    'target_id' => $order->id,
                    'target_ref' => $order->order_no,
                    'action' => 'status_transition',
                    'status' => $targetBusinessStatus,
                    'meta' => [
                        'from' => $currentBusinessStatus,
                        'to' => $targetBusinessStatus,
                        'legacy_status' => $legacyStatus,
                        'flow' => $flow,
                    ],
                ]);
            }

            Log::info('Food order status transitioned', [
                'order_no' => $orders->first()->order_no,
                'from' => $currentBusinessStatus,
                'to' => $targetBusinessStatus,
                'legacy_status' => $legacyStatus,
                'actor_type' => $context['actor_type'] ?? null,
                'actor_id' => $context['actor_id'] ?? null,
            ]);

            return Order::query()
                ->whereIn('id', $orderIds)
                ->orderBy('id')
                ->get();
        });

        if (empty($context['suppress_notifications'])) {
            $freshFirst = $transitioned->first()?->loadMissing(['user', 'restaurant', 'delivery.driver']);
            if ($freshFirst) {
                $this->notificationService()?->notifyFoodOrderStatusChange($freshFirst, $targetBusinessStatus, $context);
            }
        }

        if (empty($context['suppress_realtime'])) {
            $freshFirst = $transitioned->first()?->loadMissing(['restaurant', 'delivery.driver']);

            if ($freshFirst) {
                event(new FoodOrderStatusUpdated($freshFirst));

                if ($freshFirst->restaurant_id) {
                    event(new FoodRestaurantOrderUpdated($freshFirst));
                }

                $driverId = $freshFirst->delivery?->driver_id ?: $freshFirst->driver_id;
                if ($driverId) {
                    event(new FoodDriverOrderUpdated($freshFirst, (int) $driverId));
                }

                event(new FoodMissionPresenceUpdated($freshFirst));

                if ($targetBusinessStatus === 'ready_for_pickup' && !$freshFirst->isPickup() && $freshFirst->delivery && !$driverId) {
                    \App\Jobs\AutoAssignDeliveryJob::dispatch($freshFirst->delivery);
                }
            }
        }

        return $transitioned;
    }

    /**
     * Règle dure : in_kitchen exige business_status=confirmed ET payment_status=paid.
     * Override possible uniquement via context['force_admin']=true, avec audit log obligatoire
     * (action distincte 'status_transition_forced_unpaid' pour traçabilité/alerting).
     */
    protected function guardInKitchenRequiresConfirmedAndPaid(Order $order, string $currentBusinessStatus, array $context): void
    {
        $isConfirmed = $currentBusinessStatus === 'confirmed';
        $isPaid = ((string) $order->payment_status) === OrderPaymentStatus::PAID->value;

        if ($isConfirmed && $isPaid) {
            return;
        }

        if (empty($context['force_admin'])) {
            throw new RuntimeException(
                "Transition in_kitchen refusée : business_status doit être 'confirmed' et payment_status 'paid' "
                . "(actuel: {$currentBusinessStatus}/{$order->payment_status})."
            );
        }

        $this->auditLogs()->record([
            'actor_type' => $context['actor_type'] ?? 'admin',
            'actor_id' => $context['actor_id'] ?? null,
            'target_type' => 'food_order',
            'target_id' => $order->id,
            'target_ref' => $order->order_no,
            'action' => 'status_transition_forced_unpaid',
            'status' => 'in_kitchen',
            'meta' => [
                'from' => $currentBusinessStatus,
                'payment_status' => $order->payment_status,
                'reason' => $context['force_admin_reason'] ?? null,
            ],
        ]);

        Log::warning('FoodOrderStateMachineService: transition in_kitchen forcée sans confirmation/paiement (force_admin)', [
            'order_id' => $order->id,
            'order_no' => $order->order_no,
            'business_status' => $currentBusinessStatus,
            'payment_status' => $order->payment_status,
            'actor_id' => $context['actor_id'] ?? null,
        ]);
    }

    public function resolveCurrentBusinessStatus(Order $order): string
    {
        if (!$order->isPickup() && !empty($order->delivery) && strtoupper((string) $order->delivery->status) !== 'PENDING') {
            $deliveryBusinessStatus = $this->mapDeliveryStatusToBusiness($order->delivery->status);
            if ($deliveryBusinessStatus !== null) {
                return $deliveryBusinessStatus;
            }
        }

        if (Schema::hasColumn('orders', 'business_status') && !empty($order->business_status)) {
            return $order->business_status;
        }

        return $this->mapLegacyToBusiness($order->status, $this->resolveFlow($order));
    }

    public function normalizeBusinessStatus(string $status): string
    {
        $status = trim(strtolower($status));

        $aliases = [
            'pending' => 'pending_restaurant_acceptance',
            'accepted' => 'accepted',
            'accepted_awaiting_payment' => 'accepted_awaiting_payment',
            'confirmed' => 'confirmed',
            'prepairing' => 'in_kitchen',
            'preparing' => 'in_kitchen',
            'in_kitchen' => 'in_kitchen',
            'ready' => 'ready_for_pickup',
            'ready_for_pickup' => 'ready_for_pickup',
            'dispatching' => 'dispatching',
            'driver_assigned' => 'driver_assigned',
            'driver_arrived_at_restaurant' => 'driver_arrived_at_restaurant',
            'picked_up' => 'picked_up',
            'pickup' => 'picked_up',
            'out_for_delivery' => 'out_for_delivery',
            'delivery_attempt_failed' => 'delivery_attempt_failed',
            'onway' => 'out_for_delivery',
            'completed' => 'delivered',
            'delivered' => 'delivered',
            'incident_open' => 'incident_open',
            'customer_arrived' => 'customer_arrived',
            'picked_up_by_customer' => 'picked_up_by_customer',
            'pickedup_by_customer' => 'picked_up_by_customer',
            'no_show' => 'no_show',
            'scheduled' => 'pending_restaurant_acceptance',
            'cancelled' => 'cancelled',
            'canceled' => 'cancelled',
            'refunded' => 'refunded',
            'closed' => 'closed',
        ];

        if (!isset($aliases[$status])) {
            throw new RuntimeException("Statut repas non reconnu: {$status}");
        }

        return $aliases[$status];
    }

    protected function canTransition(string $flow, string $from, string $to, bool $force = false): bool
    {
        if ($from === $to) {
            return true;
        }

        if ($force) {
            return true;
        }

        $transitions = config("bantudelice_state_machine.{$flow}.transitions", []);
        $allowed = $transitions[$from] ?? [];

        return in_array($to, $allowed, true);
    }

    protected function mapLegacyToBusiness(?string $legacyStatus, string $flow): string
    {
        $legacyStatus = strtolower((string) $legacyStatus);

        if ($flow === self::FLOW_PICKUP) {
            return match ($legacyStatus) {
                'pending' => 'pending_restaurant_acceptance',
                'prepairing' => 'in_kitchen',
                'assign' => 'ready_for_pickup',
                'pickup' => 'customer_arrived',
                'completed' => 'picked_up_by_customer',
                'cancelled' => 'cancelled',
                default => 'pending_restaurant_acceptance',
            };
        }

        return match ($legacyStatus) {
            'pending' => 'pending_restaurant_acceptance',
            'scheduled' => 'pending_restaurant_acceptance',
            'prepairing' => 'in_kitchen',
            'assign' => 'dispatching',
            'pickup' => 'picked_up',
            'onway' => 'out_for_delivery',
            'completed' => 'delivered',
            'cancelled' => 'cancelled',
            default => 'pending_restaurant_acceptance',
        };
    }

    protected function mapBusinessToLegacy(string $businessStatus, Order $order): string
    {
        if ($this->resolveFlow($order) === self::FLOW_PICKUP) {
            return match ($businessStatus) {
                'pending_restaurant_acceptance', 'accepted_awaiting_payment', 'confirmed', 'accepted' => 'pending',
                'in_kitchen' => 'prepairing',
                'ready_for_pickup', 'customer_arrived' => 'assign',
                'picked_up_by_customer', 'closed' => 'completed',
                'no_show', 'cancelled', 'refunded' => 'cancelled',
                default => $order->status ?: 'pending',
            };
        }

        return match ($businessStatus) {
            'pending_restaurant_acceptance', 'accepted_awaiting_payment', 'confirmed', 'accepted' => 'pending',
            'in_kitchen' => 'prepairing',
            'ready_for_pickup',
            'dispatching',
            'driver_assigned',
            'driver_arrived_at_restaurant',
            'picked_up',
            'out_for_delivery',
            'delivery_attempt_failed',
            'incident_open',
            'partially_delivered' => 'assign',
            'delivered', 'closed' => 'completed',
            'cancelled', 'refunded' => 'cancelled',
            default => $order->status ?: 'pending',
        };
    }

    protected function mapDeliveryStatusToBusiness(?string $deliveryStatus): ?string
    {
        return match (strtoupper((string) $deliveryStatus)) {
            'PENDING' => 'dispatching',
            'ASSIGNED' => 'driver_assigned',
            'PICKED_UP' => 'picked_up',
            'ON_THE_WAY' => 'out_for_delivery',
            'DELIVERED' => 'delivered',
            'CANCELLED' => 'cancelled',
            default => null,
        };
    }

    protected function logTransition(Order $order, string $from, string $to, string $legacyStatus, array $context, $occurredAt): void
    {
        if (!Schema::hasTable('order_status_logs')) {
            return;
        }

        DB::table('order_status_logs')->insert([
            'order_no' => $order->order_no,
            'order_id' => $order->id,
            'from_status' => $from,
            'to_status' => $to,
            'legacy_status' => $legacyStatus,
            'actor_type' => $context['actor_type'] ?? 'system',
            'actor_id' => $context['actor_id'] ?? null,
            'reason_code' => $context['reason_code'] ?? null,
            'notes' => $context['notes'] ?? null,
            'context' => !empty($context) ? json_encode($context) : null,
            'created_at' => $occurredAt,
            'updated_at' => $occurredAt,
        ]);
    }

    protected function notificationService(): NotificationService
    {
        return $this->notificationService ?? app(NotificationService::class);
    }

    protected function auditLogs(): AuditLogService
    {
        return $this->auditLogService ?? app(AuditLogService::class);
    }

    protected function resolveFlow(Order $order): string
    {
        return $order->isPickup() ? self::FLOW_PICKUP : self::FLOW_DELIVERY;
    }
}
