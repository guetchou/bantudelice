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
        $payments = $this->planPaymentRepairs();
        $deliveries = $this->planDeliveryRepairs();

        return [
            'generated_at' => now()->toIso8601String(),
            'quarantine_schema_ready' => $this->quarantineSchemaReady(),
            'safe_repairs_count' => count($payments['safe']) + count($deliveries['safe']),
            'manual_reviews_count' => count($payments['manual']) + count($deliveries['manual']),
            'payments' => $payments,
            'deliveries' => $deliveries,
        ];
    }

    public function apply(): array
    {
        if (! $this->quarantineSchemaReady()) {
            throw new RuntimeException(
                'Les colonnes de quarantaine sont absentes. Exécutez les migrations avant la réparation.'
            );
        }

        return DB::transaction(function (): array {
            $plan = $this->plan();
            $quarantinedPaymentIds = [];
            $quarantinedDeliveryIds = [];

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

                foreach ($fresh['quarantine_ids'] as $duplicateId) {
                    $this->quarantinePayment(
                        duplicateId: $duplicateId,
                        survivorId: (int) $fresh['survivor_id'],
                        originalOrderId: $repair['order_id'],
                        provider: (string) $repair['provider'],
                        reason: (string) $fresh['reason']
                    );
                    $quarantinedPaymentIds[] = $duplicateId;
                }
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

                foreach ($fresh['quarantine_ids'] as $duplicateId) {
                    $this->quarantineDelivery(
                        duplicateId: $duplicateId,
                        survivorId: (int) $fresh['survivor_id'],
                        originalOrderId: $repair['order_id'],
                        reason: (string) $fresh['reason']
                    );
                    $quarantinedDeliveryIds[] = $duplicateId;
                }
            }

            return [
                'applied_at' => now()->toIso8601String(),
                'quarantined_payment_ids' => array_values($quarantinedPaymentIds),
                'quarantined_delivery_ids' => array_values($quarantinedDeliveryIds),
                'manual_reviews' => [
                    'payments' => $plan['payments']['manual'],
                    'deliveries' => $plan['deliveries']['manual'],
                ],
            ];
        }, 3);
    }

    private function quarantineSchemaReady(): bool
    {
        foreach (['payments', 'deliveries'] as $table) {
            if (! Schema::hasTable($table)) {
                return false;
            }

            foreach ([
                'integrity_duplicate_of_id',
                'integrity_quarantined_at',
                'integrity_quarantine_reason',
            ] as $column) {
                if (! Schema::hasColumn($table, $column)) {
                    return false;
                }
            }
        }

        return true;
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
                'quarantine_ids' => $classification['quarantine_ids'],
                'reason' => $classification['reason'],
            ];

            ($classification['safe'] ? $safe : $manual)[] = $payload;
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
                'quarantine_ids' => $classification['quarantine_ids'],
                'reason' => $classification['reason'],
            ];

            ($classification['safe'] ? $safe : $manual)[] = $payload;
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

        return $this->safe(
            survivorId: (int) $survivor->id,
            quarantineIds: $rows->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->reject(fn ($id) => $id === (int) $survivor->id)
                ->values()
                ->all(),
            reason: 'identical_duplicate_without_double_charge_signal'
        );
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

        return $this->safe(
            survivorId: (int) $survivor->id,
            quarantineIds: $rows->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->reject(fn ($id) => $id === (int) $survivor->id)
                ->values()
                ->all(),
            reason: 'unused_pending_duplicate'
        );
    }

    private function selectPaymentSurvivor(Collection $rows): mixed
    {
        $priority = [
            'PAID' => 0,
            'PENDING' => 1,
            'AUTHORIZED' => 2,
            'FAILED' => 3,
            'CANCELLED' => 4,
            'REFUNDED' => 5,
        ];

        return $rows->sort(function ($left, $right) use ($priority): int {
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
            $comparison = ($priority[$leftStatus] ?? 99) <=> ($priority[$rightStatus] ?? 99);

            return $comparison !== 0
                ? $comparison
                : ((int) $left->id <=> (int) $right->id);
        })->first();
    }

    private function quarantinePayment(
        int $duplicateId,
        int $survivorId,
        mixed $originalOrderId,
        string $provider,
        string $reason
    ): void {
        $row = DB::table('payments')->where('id', $duplicateId)->first();
        if (! $row) {
            return;
        }

        $payload = [
            'order_id' => null,
            'integrity_duplicate_of_id' => $survivorId,
            'integrity_quarantined_at' => now(),
            'integrity_quarantine_reason' => $reason,
        ];

        if (Schema::hasColumn('payments', 'deleted_at')) {
            $payload['deleted_at'] = now();
        }
        if (Schema::hasColumn('payments', 'updated_at')) {
            $payload['updated_at'] = now();
        }
        if (Schema::hasColumn('payments', 'meta')) {
            $meta = $this->decodeMeta($row->meta ?? null);
            $meta['integrity_quarantine'] = [
                'quarantined_at' => now()->toIso8601String(),
                'duplicate_of_id' => $survivorId,
                'original_order_id' => $originalOrderId,
                'provider' => $provider,
                'reason' => $reason,
            ];
            $payload['meta'] = json_encode($meta, JSON_UNESCAPED_UNICODE);
        }

        DB::table('payments')->where('id', $duplicateId)->update($payload);
    }

    private function quarantineDelivery(
        int $duplicateId,
        int $survivorId,
        mixed $originalOrderId,
        string $reason
    ): void {
        $payload = [
            'order_id' => null,
            'status' => 'CANCELLED',
            'integrity_duplicate_of_id' => $survivorId,
            'integrity_quarantined_at' => now(),
            'integrity_quarantine_reason' => $reason,
        ];

        if (Schema::hasColumn('deliveries', 'updated_at')) {
            $payload['updated_at'] = now();
        }
        if (Schema::hasColumn('deliveries', 'delivery_notes')) {
            $payload['delivery_notes'] = 'Quarantaine intégrité. Commande d’origine : '
                . $originalOrderId . '. Doublon de la livraison #' . $survivorId . '.';
        }

        DB::table('deliveries')->where('id', $duplicateId)->update($payload);
    }

    private function decodeMeta(mixed $value): array
    {
        if (is_array($value)) {
            return $value;
        }

        if ($value !== null && $value !== '') {
            $decoded = json_decode((string) $value, true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        return [];
    }

    private function safe(int $survivorId, array $quarantineIds, string $reason): array
    {
        return [
            'safe' => true,
            'survivor_id' => $survivorId,
            'quarantine_ids' => $quarantineIds,
            'reason' => $reason,
        ];
    }

    private function unsafe(string $reason): array
    {
        return [
            'safe' => false,
            'survivor_id' => null,
            'quarantine_ids' => [],
            'reason' => $reason,
        ];
    }
}
