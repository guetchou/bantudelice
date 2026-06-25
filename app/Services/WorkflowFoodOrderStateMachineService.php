<?php

namespace App\Services;

use App\Domain\Food\Enums\OrderPaymentStatus;
use App\Order;
use App\Payment;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class WorkflowFoodOrderStateMachineService extends FoodOrderStateMachineService
{
    public function transitionOrders(Collection $orders, string $targetStatus, array $context = []): Collection
    {
        $normalizedTarget = $this->normalizeBusinessStatus($targetStatus);
        $transitioned = parent::transitionOrders($orders, $targetStatus, $context);
        $orderIds = $transitioned->pluck('id')->filter()->values()->all();

        if ($normalizedTarget === 'accepted_scheduled') {
            Order::whereIn('id', $orderIds)
                ->whereNull('accepted_at')
                ->update(['accepted_at' => now()]);
            $transitioned = Order::whereIn('id', $orderIds)->orderBy('id')->get();
        }

        $first = $transitioned->first();
        if (! $first || strtolower((string) $first->payment_method) !== 'cash') {
            return $transitioned;
        }

        $businessStatus = $this->resolveCurrentBusinessStatus($first);
        $terminal = $businessStatus === 'delivered'
            || ($first->isPickup() && in_array($businessStatus, ['picked_up_by_customer', 'closed'], true));

        if (! $terminal || empty($first->cash_collection_confirmed_at)) {
            return $transitioned;
        }

        Order::whereIn('id', $orderIds)->update([
            'payment_status' => OrderPaymentStatus::PAID->value,
        ]);

        Payment::query()
            ->whereIn('order_id', $orderIds)
            ->where('provider', 'cash')
            ->where('status', '!=', 'PAID')
            ->update([
                'status' => 'PAID',
                'updated_at' => now(),
            ]);

        return Order::whereIn('id', $orderIds)->orderBy('id')->get();
    }

    protected function guardInKitchenRequiresConfirmedAndPaid(
        Order $order,
        string $currentBusinessStatus,
        array $context
    ): void {
        $isConfirmed = $currentBusinessStatus === 'confirmed';
        $isPaid = (string) $order->payment_status === OrderPaymentStatus::PAID->value;
        $isCashDue = strtolower((string) $order->payment_method) === 'cash'
            && (string) $order->payment_status === OrderPaymentStatus::CASH_DUE->value
            && (string) $order->cash_collection_status === 'pending_collection';

        if ($isConfirmed && ($isPaid || $isCashDue)) {
            return;
        }

        if (empty($context['force_admin'])) {
            throw new RuntimeException(
                "Transition in_kitchen refusée : la commande doit être confirmée et payée, "
                . "ou être une commande cash à encaisser "
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

        Log::warning('Transition cuisine forcée sans paiement ou autorisation cash', [
            'order_no' => $order->order_no,
            'payment_status' => $order->payment_status,
            'actor_id' => $context['actor_id'] ?? null,
        ]);
    }

    public function normalizeBusinessStatus(string $status): string
    {
        return match (trim(strtolower($status))) {
            'accepted_scheduled', 'scheduled' => 'accepted_scheduled',
            'preparation_due' => 'preparation_due',
            default => parent::normalizeBusinessStatus($status),
        };
    }

    protected function mapLegacyToBusiness(?string $legacyStatus, string $flow): string
    {
        if (strtolower((string) $legacyStatus) === 'scheduled') {
            return 'accepted_scheduled';
        }

        return parent::mapLegacyToBusiness($legacyStatus, $flow);
    }

    protected function mapBusinessToLegacy(string $businessStatus, Order $order): string
    {
        if (in_array($businessStatus, ['accepted_scheduled', 'preparation_due'], true)) {
            return 'scheduled';
        }

        return parent::mapBusinessToLegacy($businessStatus, $order);
    }
}
