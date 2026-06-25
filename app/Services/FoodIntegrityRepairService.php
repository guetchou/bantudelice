<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class FoodIntegrityRepairService
{
    public function __construct(
        protected FoodIntegrityReportService $reports
    ) {}

    public function plan(): array
    {
        $paymentPlan = $this->planPaymentRepairs();
        $deliveryPlan = $this->planDeliveryRepairs();

        return [
            'generated_at' => now()->toIso8601String(),
            'safe_repairs_count' => count($paymentPlan['safe']) + count($deliveryPlan['safe']),
            'manual_reviews_count' => count($paymentPlan['manual']) + count($deliveryPlan['manual']),
            'payments' => $paymentPlan,
            'deliveries' => $deliveryPlan,
        ];
    }

    public function apply(): array
    {
        return DB::transaction(function (): array {
            $plan = $this->plan();
            $deletedPaymentIds = [];
            $deletedDeliveryIds = [];

            foreach ($plan['payments']['safe'] as $repair) {
                $group = DB::table('payments')
                    ->where('order_id', $repair['order_id'])
                    ->where('provider', $repair['provider'])
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->get();

                $fresh = $this->classifyPaymentGroup($group);
                if (! $fresh['safe']) {
                    throw new RuntimeException(
                        'Le groupe de paiements a changé pendant la réparation : commande '
                        . $repair['order_id'] . ', fournisseur ' . $repair['provider'] . '.'
                    );
                }

                $removeIds = $fresh['remove_ids'];
                if ($removeIds === []) {
                    continue;
                }

                $this->annotatePaymentSurvivor($fresh['survivor_id'], $removeIds);
                DB::table('payments')->whereIn('id', $removeIds)->delete();
                $deletedPaymentIds = array_merge($deletedPaymentIds, $removeIds);
            }

            foreach ($plan['deliveries']['safe'] as $repair) {
                $group = DB::table('deliveries')
                    ->where('order_id', $repair['order_id'])
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->get();

                $fresh = $this->classifyDeliveryGroup($group);
                if (! $fresh['safe']) {
                    throw new RuntimeException(
                        'Le groupe de livraisons a changé pendant la réparation : commande '
                        . $repair['order_id'] . '.'
                    );
                }

                $removeIds = $fresh['remove_ids'];
                if ($removeIds === []) {
                    continue;
                }

                DB::table('deliveries')->whereIn('id', $removeIds)->delete();
                $deletedDeliveryIds = array_merge($deletedDeliveryIds, $removeIds);
            }

            return [
                'applied_at' => now()->toIso8601String(),
                'deleted_payment_ids' => array_values($deletedPaymentIds),
                'deleted_delivery_ids' => array_values($deletedDeliveryIds),
                'manual_reviews' => [
                    'payments' => $plan['payments']['manual'],
                    'deliveries' => $plan['deliveries']['manual'],
                ],
            ];
        }, 3);
    }

    private function planPaymentRepairs(): array
    {
        $safe = [];
        $manual = [];

        foreach ($this->reports->duplicatePayments(includeSoftDeleted: true) as $duplicate) {
            $rows = DB::table('payments')
                ->where('order_id', $duplicate['order_id'])
                ->where('provider', $duplicate['provider'])
                ->orderBy('id')
                ->get();

            $classification = $this->classifyPaymentGroup($rows);
            $payload = [
                'order_id' => $duplicate['order_id'],
                'provider' => $duplicate['provider'],
                'payment_ids' => $rows->pluck('id')->map(fn ($id) => (int) $id)->all(),
                'survivor_id' => $classification['survivor_id'],
                'remove_ids' => $classification['remove_ids'],
                'reason' => $classification['reason'],
            ];

            if ($classification['safe']) {
                $safe[] = $payload;
            } else {
                $manual[] = $payload;
            }
        }

        return ['safe' => $safe, 'manual' => $manual];
    }

    private function planDeliveryRepairs(): array
    {
        $safe = [];
        $manual = [];

        foreach ($this->reports->duplicateDeliveries() as $duplicate) {
            $rows = DB::table('deliveries')
                ->where('order_id', $duplicate['order_id'])
                ->orderBy('id')
                ->get();

            $classification = $this->classifyDeliveryGroup($rows);
            $payload = [
                'order_id' => $duplicate['order_id'],
                'delivery_ids' => $rows->pluck('id')->map(fn ($id) => (int) $id)->all(),
                'survivor_id' => $classification['survivor_id'],
                'remove_ids' => $classification['remove_ids'],
                'reason' => $classification['reason'],
            ];

            if ($classification['safe']) {
                $safe[] = $payload;
            } else {
                $manual[] = $payload;
            }
        }

        return ['safe' => $safe, 'manual' => $manual];
    }

    private function classifyPaymentGroup(Collection $rows): array
    {
        if ($rows->count() < 2) {
            return $this->unsafe('not_a_duplicate');
        }

        $paidRows = $rows->filter(
            fn ($row) => strtoupper((string) ($row->status ?? '')) === 'PAID'
        );
        if ($paidRows->count() > 1) {
            return $this->unsafe('possible_double_charge');
        }

        foreach (['user_id', 'amount', 'currency'] as $column) {
            if (! Schema::hasColumn('payments', $column)) {
                continue;
            }

            $values = $rows->pluck($column)
                ->filter(fn ($value) => $value !== null && $value !== '')
                ->map(fn ($value) => (string) $value)
                ->unique();

            if ($values->count() > 1) {
                return $this->unsafe('financial_fields_mismatch');
            }
        }

        $references = Schema::hasColumn('payments', 'provider_reference')
            ? $rows->pluck('provider_reference')
                ->filter(fn ($value) => $value !== null && trim((string) $value) !== '')
                ->map(fn ($value) => trim((string) $value))
                ->unique()
            : collect();

        if ($references->count() > 1) {
            return $this->unsafe('conflicting_provider_references');
        }

        $survivor = $this->selectPaymentSurvivor($rows);
        if (! $survivor) {
            return $this->unsafe('no_survivor');
        }

        $removeIds = $rows->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->reject(fn ($id) => $id === (int) $survivor->id)
            ->values()
            ->all();

        return [
            'safe' => true,
            'survivor_id' => (int) $survivor->id,
            'remove_ids' => $removeIds,
            'reason' => 'identical_duplicate_without_double_charge_signal',
        ];
    }

    private function classifyDeliveryGroup(Collection $rows): array
    {
        if ($rows->count() < 2) {
            return $this->unsafe('not_a_duplicate');
        }

        foreach ($rows as $row) {
            if (strtoupper((string) ($row->status ?? 'PENDING')) !== 'PENDING') {
                return $this->unsafe('delivery_already_progressed');
            }

            foreach ([
                'driver_id',
                'assigned_at',
                'picked_up_at',
                'delivered_at',
                'pickup_proof_path',
                'delivery_proof_path',
                'incident_status',
            ] as $column) {
                if (property_exists($row, $column) && $row->{$column} !== null && $row->{$column} !== '') {
                    return $this->unsafe('delivery_contains_operational_data');
                }
            }
        }

        foreach (['restaurant_id', 'delivery_fee'] as $column) {
            if (! Schema::hasColumn('deliveries', $column)) {
                continue;
            }

            $values = $rows->pluck($column)
                ->filter(fn ($value) => $value !== null && $value !== '')
                ->map(fn ($value) => (string) $value)
                ->unique();

            if ($values->count() > 1) {
                return $this->unsafe('delivery_fields_mismatch');
            }
        }

        $survivor = $rows->sortBy('id')->first();
        $removeIds = $rows->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->reject(fn ($id) => $id === (int) $survivor->id)
            ->values()
            ->all();

        return [
            'safe' => true,
            'survivor_id' => (int) $survivor->id,
            'remove_ids' => $removeIds,
            'reason' => 'unused_pending_duplicate',
        ];
    }

    private function selectPaymentSurvivor(Collection $rows): mixed
    {
        $statusPriority = [
            'PAID' => 0,
            'PENDING' => 1,
            'AUTHORIZED' => 2,
            'FAILED' => 3,
            'CANCELLED' => 4,
            'REFUNDED' => 5,
        ];

        return $rows->sort(function ($left, $right) use ($statusPriority): int {
            $leftDeleted = ! empty($left->deleted_at);
            $rightDeleted = ! empty($right->deleted_at);
            if ($leftDeleted !== $rightDeleted) {
                return $leftDeleted <=> $rightDeleted;
            }

            $leftHasReference = ! empty($left->provider_reference);
            $rightHasReference = ! empty($right->provider_reference);
            if ($leftHasReference !== $rightHasReference) {
                return $rightHasReference <=> $leftHasReference;
            }

            $leftStatus = strtoupper((string) ($left->status ?? ''));
            $rightStatus = strtoupper((string) ($right->status ?? ''));
            $statusComparison = ($statusPriority[$leftStatus] ?? 99) <=> ($statusPriority[$rightStatus] ?? 99);
            if ($statusComparison !== 0) {
                return $statusComparison;
            }

            return (int) $left->id <=> (int) $right->id;
        })->first();
    }

    private function annotatePaymentSurvivor(?int $survivorId, array $removedIds): void
    {
        if (! $survivorId || ! Schema::hasColumn('payments', 'meta')) {
            return;
        }

        $row = DB::table('payments')->where('id', $survivorId)->first();
        if (! $row) {
            return;
        }

        $meta = [];
        if (! empty($row->meta)) {
            $decoded = json_decode((string) $row->meta, true);
            if (is_array($decoded)) {
                $meta = $decoded;
            }
        }

        $meta['integrity_repair'] = [
            'repaired_at' => now()->toIso8601String(),
            'removed_duplicate_ids' => array_values($removedIds),
        ];

        DB::table('payments')->where('id', $survivorId)->update([
            'meta' => json_encode($meta, JSON_UNESCAPED_UNICODE),
            'updated_at' => Schema::hasColumn('payments', 'updated_at') ? now() : ($row->updated_at ?? null),
        ]);
    }

    private function unsafe(string $reason): array
    {
        return [
            'safe' => false,
            'survivor_id' => null,
            'remove_ids' => [],
            'reason' => $reason,
        ];
    }
}
