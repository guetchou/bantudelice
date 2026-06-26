<?php

namespace App\Services;

use App\Delivery;
use App\Driver;
use App\Domain\Food\Enums\OrderPaymentStatus;
use App\Order;
use App\Payment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Durcissement du workflow food sans casser le contrat historique DeliveryService.
 */
class SecureDeliveryService extends DeliveryService
{
    public function createForOrder(Order $order): Delivery
    {
        return Delivery::firstOrCreate(
            ['order_id' => $order->id],
            [
                'restaurant_id' => $order->restaurant_id,
                'status' => 'PENDING',
                'delivery_fee' => (int) ($order->delivery_charges ?? 0),
            ]
        );
    }

    public function assignDriver(Delivery $delivery, Driver $driver): Delivery
    {
        $assigned = DB::transaction(function () use ($delivery, $driver): Delivery {
            $lockedDelivery = Delivery::query()
                ->with('order')
                ->lockForUpdate()
                ->findOrFail($delivery->id);
            $lockedDriver = Driver::query()->lockForUpdate()->findOrFail($driver->id);

            if ($lockedDelivery->status !== 'PENDING') {
                throw new \RuntimeException(
                    'Cette livraison ne peut plus être assignée (statut : ' . $lockedDelivery->status . ').'
                );
            }

            if (! $lockedDelivery->order || $lockedDelivery->order->business_status !== 'ready_for_pickup') {
                throw new \RuntimeException('La commande doit être déclarée prête avant l’assignation d’un livreur.');
            }

            if (! (bool) $lockedDriver->approved) {
                throw new \RuntimeException('Ce compte livreur n’est pas approuvé.');
            }

            if ($lockedDriver->status !== 'online') {
                throw new \RuntimeException('Ce livreur n’est pas en ligne.');
            }

            if (Schema::hasColumn('drivers', 'is_available') && ! (bool) $lockedDriver->is_available) {
                throw new \RuntimeException('Ce livreur n’est pas disponible.');
            }

            $activeDeliveries = Delivery::where('driver_id', $lockedDriver->id)
                ->whereIn('status', ['ASSIGNED', 'PICKED_UP', 'ON_THE_WAY'])
                ->count();

            if ($activeDeliveries >= 3) {
                throw new \RuntimeException('Ce livreur a atteint sa capacité maximale.');
            }

            $this->stateMachine()->transitionOrderGroup($lockedDelivery->order->order_no, 'driver_assigned', [
                'actor_type' => 'driver',
                'actor_id' => $lockedDriver->id,
                'reason_code' => 'delivery_offer_accepted',
            ]);

            $lockedDelivery->update([
                'driver_id' => $lockedDriver->id,
                'status' => 'ASSIGNED',
                'assigned_at' => now(),
            ]);

            if (Schema::hasColumn('drivers', 'is_available')) {
                $lockedDriver->update(['is_available' => false]);
            }

            Order::where('order_no', $lockedDelivery->order->order_no)
                ->update(['driver_id' => $lockedDriver->id]);

            return $lockedDelivery->fresh(['order', 'driver']);
        }, 3);

        $this->proof()->ensureDeliveryOtp($assigned);

        return $assigned->fresh(['order', 'driver']);
    }

    public function updateStatus(Delivery $delivery, string $status, array $context = []): Delivery
    {
        $allowedStatuses = ['ASSIGNED', 'PICKED_UP', 'ON_THE_WAY', 'DELIVERED', 'CANCELLED'];
        if (! in_array($status, $allowedStatuses, true)) {
            throw new \RuntimeException('Statut invalide : ' . $status);
        }

        if (($context['actor_type'] ?? null) === 'driver' && $status === 'CANCELLED') {
            throw new \RuntimeException('Un livreur ne peut pas annuler commercialement une commande. Signalez un incident.');
        }

        return DB::transaction(function () use ($delivery, $status, $context): Delivery {
            $locked = Delivery::query()
                ->with(['order', 'driver'])
                ->lockForUpdate()
                ->findOrFail($delivery->id);

            $validTransitions = [
                'PENDING' => ['ASSIGNED', 'CANCELLED'],
                'ASSIGNED' => ['PICKED_UP', 'CANCELLED'],
                'PICKED_UP' => ['ON_THE_WAY', 'CANCELLED'],
                'ON_THE_WAY' => ['DELIVERED', 'CANCELLED'],
                'DELIVERED' => [],
                'CANCELLED' => [],
            ];

            if (! in_array($status, $validTransitions[$locked->status] ?? [], true)) {
                throw new \RuntimeException(
                    'Transition de statut invalide : ' . $locked->status . ' → ' . $status
                );
            }

            $now = now();
            $data = ['status' => $status];
            $cashOutcome = null;
            $confirmationMethod = null;

            if ($status === 'PICKED_UP') {
                $data['picked_up_at'] = $now;
                $this->copyOptionalDeliveryFields($locked, $data, $context, [
                    'pickup_notes',
                    'pickup_proof_path',
                    'pickup_latitude',
                    'pickup_longitude',
                ]);

                $this->stateMachine()->transitionOrderGroup($locked->order->order_no, 'picked_up', [
                    'actor_type' => $context['actor_type'] ?? 'driver',
                    'actor_id' => $context['actor_id'] ?? $locked->driver_id,
                    'pickup_notes' => $context['pickup_notes'] ?? null,
                ]);
            } elseif ($status === 'ON_THE_WAY') {
                $this->copyOptionalDeliveryFields($locked, $data, $context, ['delivery_notes']);

                $this->stateMachine()->transitionOrderGroup($locked->order->order_no, 'out_for_delivery', [
                    'actor_type' => $context['actor_type'] ?? 'driver',
                    'actor_id' => $context['actor_id'] ?? $locked->driver_id,
                    'delivery_notes' => $context['delivery_notes'] ?? null,
                ]);
            } elseif ($status === 'DELIVERED') {
                $isCash = strtolower((string) $locked->order->payment_method) === 'cash';
                $cashOutcome = $context['cash_collection_outcome'] ?? null;

                if ($isCash && ! in_array($cashOutcome, ['collected', 'collection_failed'], true)) {
                    throw new \RuntimeException('Le résultat de l’encaissement cash doit être indiqué.');
                }

                $confirmationMethod = $this->proof()->assertDeliveryProofOrConfirmation($locked, $context);
                $data['delivered_at'] = $now;
                $this->copyOptionalDeliveryFields($locked, $data, $context, [
                    'delivery_notes',
                    'delivery_proof_path',
                    'delivery_latitude',
                    'delivery_longitude',
                ]);

                if (Schema::hasColumn('deliveries', 'otp_verified_at') && $confirmationMethod === 'otp') {
                    $data['otp_verified_at'] = $now;
                }
                if (Schema::hasColumn('deliveries', 'delivery_confirmation_method')) {
                    $data['delivery_confirmation_method'] = $confirmationMethod;
                }
                if (Schema::hasColumn('deliveries', 'cash_collected_at') && $isCash && $cashOutcome === 'collected') {
                    $data['cash_collected_at'] = $now;
                }

                $this->stateMachine()->transitionOrderGroup($locked->order->order_no, 'delivered', [
                    'actor_type' => $context['actor_type'] ?? 'driver',
                    'actor_id' => $context['actor_id'] ?? $locked->driver_id,
                    'delivery_notes' => $context['delivery_notes'] ?? null,
                    'customer_confirmed' => ! empty($locked->customer_confirmed_at),
                    'cash_collection_outcome' => $cashOutcome,
                ]);

                $paymentStatus = $this->settlePaymentOutcome($locked->order, $cashOutcome, $now);
                Order::where('order_no', $locked->order->order_no)
                    ->update(['payment_status' => $paymentStatus]);

                $this->releaseDriverIfAssigned($locked);

                if ($paymentStatus === OrderPaymentStatus::PAID->value) {
                    $freshOrder = Order::where('order_no', $locked->order->order_no)->orderBy('id')->firstOrFail();
                    $this->awardLoyaltyIfEligible($freshOrder);
                }
            } elseif ($status === 'CANCELLED') {
                $this->releaseDriverIfAssigned($locked);
                $this->stateMachine()->transitionOrderGroup($locked->order->order_no, 'cancelled', [
                    'actor_type' => $context['actor_type'] ?? 'admin',
                    'actor_id' => $context['actor_id'] ?? null,
                    'reason_code' => $context['reason_code'] ?? 'delivery_cancelled',
                ]);
            }

            $locked->update($data);
            $fresh = $locked->fresh(['order', 'driver']);

            app(CommerceSignalService::class)->emitDelivery($fresh, 'delivery.status_changed', [
                'module' => 'food',
                'severity' => $status === 'CANCELLED' || $cashOutcome === 'collection_failed' ? 'warning' : 'info',
                'status' => $status,
                'actor_type' => $context['actor_type'] ?? 'system',
                'actor_id' => $context['actor_id'] ?? null,
                'cash_collection_outcome' => $cashOutcome,
                'confirmation_method' => $confirmationMethod,
            ]);

            app(RiskService::class)->assessOrder($fresh->order, [
                'module' => 'food',
                'delivery_status' => $status,
                'customer_confirmed' => ! empty($fresh->customer_confirmed_at),
                'delivery_proof' => ! empty($fresh->delivery_proof_path),
                'cash_collection_failed' => $cashOutcome === 'collection_failed',
                'incident' => false,
            ], 'delivery_status_changed');

            if ($status === 'DELIVERED' && $fresh->order->payment_status === OrderPaymentStatus::PAID->value) {
                app(FinancialLedgerService::class)->capture($fresh->order, (float) ($fresh->order->total ?? 0), [
                    'reference' => $fresh->order->order_no,
                    'entry_type' => 'delivery_completed',
                    'module' => 'food',
                    'actor_type' => $context['actor_type'] ?? 'driver',
                    'actor_id' => $context['actor_id'] ?? $fresh->driver_id,
                    'status' => 'posted',
                ]);
            }

            return $fresh;
        }, 3);
    }

    private function settlePaymentOutcome(Order $order, ?string $cashOutcome, $occurredAt): string
    {
        $isCash = strtolower((string) $order->payment_method) === 'cash';
        $payment = Payment::where('order_id', $order->id)->latest('id')->first();

        if ($isCash) {
            $collected = $cashOutcome === 'collected';
            $status = $collected
                ? OrderPaymentStatus::PAID->value
                : OrderPaymentStatus::CASH_DUE->value;

            if ($payment) {
                $meta = array_merge($payment->meta ?? [], $collected
                    ? ['cash_collected_at' => $occurredAt->toIso8601String(), 'collection_status' => 'collected']
                    : ['cash_collection_failed_at' => $occurredAt->toIso8601String(), 'collection_status' => 'collection_failed']);

                $payment->update([
                    'status' => $collected ? 'PAID' : 'PENDING',
                    'meta' => $meta,
                ]);
            }

            return $status;
        }

        if ($payment && strtoupper((string) $payment->status) === 'PAID') {
            $payment->update([
                'meta' => array_merge($payment->meta ?? [], [
                    'delivered_at' => $occurredAt->toIso8601String(),
                ]),
            ]);

            return OrderPaymentStatus::PAID->value;
        }

        return (string) $order->payment_status;
    }

    private function copyOptionalDeliveryFields(
        Delivery $delivery,
        array &$data,
        array $context,
        array $fields
    ): void {
        foreach ($fields as $field) {
            if (! Schema::hasColumn('deliveries', $field)) {
                continue;
            }

            if (array_key_exists($field, $context) && $context[$field] !== null && $context[$field] !== '') {
                $data[$field] = $context[$field];
            } elseif ($delivery->{$field} !== null) {
                $data[$field] = $delivery->{$field};
            }
        }
    }
}
