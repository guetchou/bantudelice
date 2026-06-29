<?php

namespace App\Services;

use App\Order;
use App\Payment;
use App\PaymentAllocation;
use App\PaymentReconciliationCase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PaymentBusinessService
{
    public function __construct(
        private readonly FinancialLedgerService $ledger,
    ) {}

    /**
     * Enregistre la vérité métier d'un paiement confirmé.
     *
     * Le paiement peut être encaissé sans que la cible métier soit libérée :
     * paiement sans commande, sous-paiement ou double financement. Dans ces cas,
     * les fonds restent visibles, une exception est ouverte et aucun workflow
     * commande/transport/colis n'est déclenché.
     */
    public function recordConfirmedPayment(Payment $payment, array $context = []): array
    {
        return DB::transaction(function () use ($payment, $context): array {
            $locked = Payment::query()->lockForUpdate()->findOrFail($payment->id);
            $order = $locked->order_id
                ? Order::query()->lockForUpdate()->find($locked->order_id)
                : null;

            $this->ledger->recordConfirmedCollection($locked, $order, $context);

            if (! Schema::hasTable('payment_allocations') || ! Schema::hasTable('payment_reconciliation_cases')) {
                return [
                    'release_target' => true,
                    'allocation_status' => 'legacy',
                    'case' => null,
                ];
            }

            if ($locked->order_id) {
                return $this->allocateFoodOrder($locked, $order, $context);
            }

            if ($locked->transport_booking_id) {
                return $this->allocateGenericTarget(
                    $locked,
                    'transport_booking',
                    (int) $locked->transport_booking_id,
                    $context
                );
            }

            if ($locked->shipment_id) {
                return $this->allocateGenericTarget(
                    $locked,
                    'shipment',
                    (int) $locked->shipment_id,
                    $context
                );
            }

            $case = $this->openCase(
                payment: $locked,
                type: 'unallocated_payment',
                severity: 'critical',
                summary: 'Paiement confirmé sans cible métier identifiable.',
                expected: null,
                observed: (float) $locked->amount,
                details: $context
            );

            return [
                'release_target' => false,
                'allocation_status' => 'unallocated',
                'case' => $case,
            ];
        });
    }

    public function reverseConfirmedPayment(Payment $payment, string $reason, array $context = []): array
    {
        return DB::transaction(function () use ($payment, $reason, $context): array {
            $locked = Payment::query()->lockForUpdate()->findOrFail($payment->id);

            if (Schema::hasTable('payment_allocations')) {
                PaymentAllocation::query()
                    ->where('payment_id', $locked->id)
                    ->whereIn('status', ['allocated', 'held'])
                    ->lockForUpdate()
                    ->get()
                    ->each(function (PaymentAllocation $allocation) use ($reason): void {
                        $allocation->update([
                            'status' => 'released',
                            'released_at' => now(),
                            'metadata' => array_merge($allocation->metadata ?? [], [
                                'release_reason' => $reason,
                            ]),
                        ]);
                    });
            }

            $this->ledger->reverseConfirmedCollection($locked, $reason, $context);

            $locked->update([
                'status' => 'REVERSED',
                'meta' => array_merge($locked->meta ?? [], [
                    'reversed_at' => now()->toIso8601String(),
                    'reversal_reason' => $reason,
                    'reversal_context' => $context,
                ]),
            ]);

            $case = $this->openCase(
                payment: $locked,
                type: 'provider_reversed',
                severity: 'critical',
                summary: 'Encaissement inversé par le fournisseur.',
                expected: (float) $locked->amount,
                observed: 0,
                details: array_merge($context, ['reason' => $reason])
            );

            return ['payment' => $locked->fresh(), 'case' => $case];
        });
    }

    public function flagProviderUnknown(Payment $payment, array $details = []): ?PaymentReconciliationCase
    {
        return $this->openCase(
            payment: $payment,
            type: 'provider_unknown',
            severity: 'critical',
            summary: 'Le fournisseur ne confirme pas le statut final du paiement.',
            expected: (float) $payment->amount,
            observed: null,
            details: $details
        );
    }

    public function flagDispute(Payment $payment, array $details = []): ?PaymentReconciliationCase
    {
        return DB::transaction(function () use ($payment, $details): ?PaymentReconciliationCase {
            $locked = Payment::query()->lockForUpdate()->findOrFail($payment->id);
            $locked->update([
                'status' => 'DISPUTED',
                'meta' => array_merge($locked->meta ?? [], [
                    'disputed_at' => now()->toIso8601String(),
                    'dispute' => $details,
                ]),
            ]);

            return $this->openCase(
                payment: $locked,
                type: 'provider_disputed',
                severity: 'critical',
                summary: 'Paiement contesté : les fonds doivent rester bloqués.',
                expected: (float) $locked->amount,
                observed: null,
                details: $details
            );
        });
    }

    private function allocateFoodOrder(Payment $payment, ?Order $anchor, array $context): array
    {
        if (! $anchor) {
            $case = $this->openCase(
                payment: $payment,
                type: 'missing_order',
                severity: 'critical',
                summary: 'La commande rattachée au paiement est introuvable.',
                expected: null,
                observed: (float) $payment->amount,
                details: $context
            );

            return ['release_target' => false, 'allocation_status' => 'unallocated', 'case' => $case];
        }

        $group = Order::query()
            ->where('order_no', $anchor->order_no)
            ->lockForUpdate()
            ->get();
        $expected = $this->resolveOrderGroupAmount($anchor, $group, $payment);
        $observed = (float) $payment->amount;
        $allocationKey = 'payment:' . $payment->id . ':order:' . $anchor->order_no;

        $existing = PaymentAllocation::where('allocation_key', $allocationKey)->first();
        if ($existing) {
            return [
                'release_target' => $existing->status === 'allocated',
                'allocation_status' => $existing->status,
                'case' => null,
            ];
        }

        $groupIds = $group->pluck('id')->all();
        $alreadyFunded = (float) PaymentAllocation::query()
            ->whereIn('order_id', $groupIds)
            ->where('payment_id', '!=', $payment->id)
            ->where('status', 'allocated')
            ->sum('amount');

        if ($alreadyFunded >= max(0, $expected - 1)) {
            $case = $this->openCase(
                payment: $payment,
                type: 'duplicate_payment',
                severity: 'critical',
                summary: 'La commande est déjà intégralement financée par un autre paiement.',
                expected: $expected,
                observed: $observed,
                details: array_merge($context, [
                    'order_no' => $anchor->order_no,
                    'already_funded' => $alreadyFunded,
                ])
            );

            return ['release_target' => false, 'allocation_status' => 'duplicate', 'case' => $case];
        }

        if ($expected <= 0) {
            $case = $this->openCase(
                payment: $payment,
                type: 'missing_expected_amount',
                severity: 'critical',
                summary: 'Le montant attendu de la commande est indéterminable.',
                expected: null,
                observed: $observed,
                details: array_merge($context, ['order_no' => $anchor->order_no])
            );

            return ['release_target' => false, 'allocation_status' => 'unallocated', 'case' => $case];
        }

        if ($observed + 1 < $expected) {
            $allocation = $this->createAllocation(
                payment: $payment,
                order: $anchor,
                key: $allocationKey,
                amount: $observed,
                status: 'held',
                metadata: [
                    'order_no' => $anchor->order_no,
                    'group_line_count' => $group->count(),
                    'expected_amount' => $expected,
                    'reason' => 'underpayment',
                ]
            );

            $case = $this->openCase(
                payment: $payment,
                type: 'amount_mismatch',
                severity: 'critical',
                summary: 'Sous-paiement : la commande ne doit pas être libérée.',
                expected: $expected,
                observed: $observed,
                details: array_merge($context, ['order_no' => $anchor->order_no])
            );

            return ['release_target' => false, 'allocation_status' => $allocation->status, 'case' => $case];
        }

        $allocatedAmount = min($observed, $expected);
        $allocation = $this->createAllocation(
            payment: $payment,
            order: $anchor,
            key: $allocationKey,
            amount: $allocatedAmount,
            status: 'allocated',
            metadata: [
                'order_no' => $anchor->order_no,
                'group_line_count' => $group->count(),
                'expected_amount' => $expected,
            ]
        );

        $case = null;
        if ($observed > $expected + 1) {
            $case = $this->openCase(
                payment: $payment,
                type: 'overpayment',
                severity: 'warning',
                summary: 'Surpaiement : le reliquat doit être remboursé ou réaffecté.',
                expected: $expected,
                observed: $observed,
                details: array_merge($context, [
                    'order_no' => $anchor->order_no,
                    'unallocated_amount' => $observed - $expected,
                ])
            );
        }

        return [
            'release_target' => true,
            'allocation_status' => $allocation->status,
            'case' => $case,
        ];
    }

    private function allocateGenericTarget(Payment $payment, string $type, int $id, array $context): array
    {
        $key = 'payment:' . $payment->id . ':' . $type . ':' . $id;
        $allocation = PaymentAllocation::firstOrCreate(
            ['allocation_key' => $key],
            [
                'payment_id' => $payment->id,
                'allocatable_type' => $type,
                'allocatable_id' => $id,
                'amount' => (float) $payment->amount,
                'currency' => $payment->currency ?: 'XAF',
                'status' => 'allocated',
                'allocated_at' => now(),
                'metadata' => $context,
            ]
        );

        return ['release_target' => true, 'allocation_status' => $allocation->status, 'case' => null];
    }

    private function createAllocation(
        Payment $payment,
        Order $order,
        string $key,
        float $amount,
        string $status,
        array $metadata
    ): PaymentAllocation {
        return PaymentAllocation::firstOrCreate(
            ['allocation_key' => $key],
            [
                'payment_id' => $payment->id,
                'order_id' => $order->id,
                'allocatable_type' => 'food_order_group',
                'allocatable_id' => $order->id,
                'amount' => $amount,
                'currency' => $payment->currency ?: 'XAF',
                'status' => $status,
                'allocated_at' => now(),
                'metadata' => $metadata,
            ]
        );
    }

    private function resolveOrderGroupAmount(Order $anchor, Collection $group, Payment $payment): float
    {
        $snapshotTotal = (float) data_get($anchor->checkout_snapshot ?? [], 'totals.total', 0);
        if ($snapshotTotal > 0) {
            return $snapshotTotal;
        }

        $groupTotal = (float) $group->max('total');
        if ($groupTotal > 0) {
            return $groupTotal;
        }

        return (float) $payment->amount;
    }

    private function openCase(
        Payment $payment,
        string $type,
        string $severity,
        string $summary,
        ?float $expected,
        ?float $observed,
        array $details = []
    ): ?PaymentReconciliationCase {
        if (! Schema::hasTable('payment_reconciliation_cases')) {
            return null;
        }

        return PaymentReconciliationCase::firstOrCreate(
            ['case_key' => 'payment:' . $payment->id . ':' . $type],
            [
                'payment_id' => $payment->id,
                'case_type' => $type,
                'severity' => $severity,
                'status' => 'open',
                'expected_amount' => $expected,
                'observed_amount' => $observed,
                'currency' => $payment->currency ?: 'XAF',
                'provider' => $payment->provider,
                'provider_reference' => $payment->provider_reference,
                'summary' => $summary,
                'details' => $details,
                'opened_at' => now(),
            ]
        );
    }
}
