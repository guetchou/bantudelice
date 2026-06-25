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
        $payments = $this->planPayments();
        $deliveries = $this->planDeliveries();

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
            throw new RuntimeException('Les colonnes de quarantaine sont absentes. Exécutez les migrations.');
        }

        return DB::transaction(function (): array {
            $plan = $this->plan();
            $paymentIds = [];
            $deliveryIds = [];

            foreach ($plan['payments']['safe'] as $repair) {
                $rows = DB::table('payments')
                    ->where('order_id', $repair['order_id'])
                    ->where('provider', $repair['provider'])
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->get();
                $fresh = $this->classifyPayments($rows);
                $this->assertStillSafe($fresh, 'paiements', (string) $repair['order_id']);

                foreach ($fresh['quarantine_ids'] as $id) {
                    $this->quarantinePayment(
                        (int) $id,
                        (int) $fresh['survivor_id'],
                        $repair['order_id'],
                        (string) $repair['provider'],
                        (string) $fresh['reason']
                    );
                    $paymentIds[] = (int) $id;
                }
            }

            foreach ($plan['deliveries']['safe'] as $repair) {
                $rows = DB::table('deliveries')
                    ->where('order_id', $repair['order_id'])
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->get();
                $fresh = $this->classifyDeliveries($rows);
                $this->assertStillSafe($fresh, 'livraisons', (string) $repair['order_id']);

                foreach ($fresh['quarantine_ids'] as $id) {
                    $this->quarantineDelivery(
                        (int) $id,
                        (int) $fresh['survivor_id'],
                        $repair['order_id'],
                        (string) $fresh['reason']
                    );
                    $deliveryIds[] = (int) $id;
                }
            }

            return [
                'applied_at' => now()->toIso8601String(),
                'quarantined_payment_ids' => $paymentIds,
                'quarantined_delivery_ids' => $deliveryIds,
                'manual_reviews' => [
                    'payments' => $plan['payments']['manual'],
                    'deliveries' => $plan['deliveries']['manual'],
                ],
            ];
        }, 3);
    }

    private function planPayments(): array
    {
        $safe = [];
        $manual = [];

        foreach ($this->reports->duplicatePayments(includeSoftDeleted: true) as $group) {
            $rows = DB::table('payments')
                ->where('order_id', $group['order_id'])
                ->where('provider', $group['provider'])
                ->orderBy('id')
                ->get();
            $classification = $this->classifyPayments($rows);
            $payload = [
                'order_id' => $group['order_id'],
                'provider' => $group['provider'],
                'payment_ids' => $rows->pluck('id')->map(fn ($id) => (int) $id)->all(),
                'survivor_id' => $classification['survivor_id'],
                'quarantine_ids' => $classification['quarantine_ids'],
                'reason' => $classification['reason'],
            ];

            if ($classification['safe']) {
                $safe[] = $payload;
            } else {
                $manual[] = $payload;
            }
        }

        return compact('safe', 'manual');
    }

    private function planDeliveries(): array
    {
        $safe = [];
        $manual = [];

        foreach ($this->reports->duplicateDeliveries() as $group) {
            $rows = DB::table('deliveries')
                ->where('order_id', $group['order_id'])
                ->orderBy('id')
                ->get();
            $classification = $this->classifyDeliveries($rows);
            $payload = [
                'order_id' => $group['order_id'],
                'delivery_ids' => $rows->pluck('id')->map(fn ($id) => (int) $id)->all(),
                'survivor_id' => $classification['survivor_id'],
                'quarantine_ids' => $classification['quarantine_ids'],
                'reason' => $classification['reason'],
            ];

            if ($classification['safe']) {
                $safe[] = $payload;
            } else {
                $manual[] = $payload;
            }
        }

        return compact('safe', 'manual');
    }

    private function classifyPayments(Collection $rows): array
    {
        if ($rows->count() < 2) {
            return $this->unsafe('not_a_duplicate');
        }

        if ($rows->filter(fn ($row) => strtoupper((string) ($row->status ?? '')) === 'PAID')->count() > 1) {
            return $this->unsafe('possible_double_charge');
        }

        foreach (['user_id', 'amount', 'currency'] as $column) {
            if ($this->distinctNonEmpty($rows, 'payments', $column) > 1) {
                return $this->unsafe('financial_fields_mismatch');
            }
        }

        if ($this->distinctNonEmpty($rows, 'payments', 'provider_reference') > 1) {
            return $this->unsafe('conflicting_provider_references');
        }

        $survivor = $this->selectPaymentSurvivor($rows);
        if (! $survivor) {
            return $this->unsafe('no_survivor');
        }

        return $this->safe($rows, (int) $survivor->id, 'identical_duplicate_without_double_charge_signal');
    }

    private function classifyDeliveries(Collection $rows): array
    {
        if ($rows->count() < 2) {
            return $this->unsafe('not_a_duplicate');
        }

        foreach ($rows as $row) {
            if (strtoupper((string) ($row->status ?? 'PENDING')) !== 'PENDING') {
                return $this->unsafe('delivery_already_progressed');
            }

            foreach (['driver_id', 'assigned_at', 'picked_up_at', 'delivered_at', 'pickup_proof_path', 'delivery_proof_path', 'incident_status'] as $column) {
                if (property_exists($row, $column) && $row->{$column} !== null && $row->{$column} !== '') {
                    return $this->unsafe('delivery_contains_operational_data');
                }
            }
        }

        foreach (['restaurant_id', 'delivery_fee'] as $column) {
            if ($this->distinctNonEmpty($rows, 'deliveries', $column) > 1) {
                return $this->unsafe('delivery_fields_mismatch');
            }
        }

        $survivor = $rows->sortBy('id')->first();
        return $this->safe($rows, (int) $survivor->id, 'unused_pending_duplicate');
    }

    private function selectPaymentSurvivor(Collection $rows): mixed
    {
        $priority = ['PAID' => 0, 'PENDING' => 1, 'AUTHORIZED' => 2, 'FAILED' => 3, 'CANCELLED' => 4, 'REFUNDED' => 5];

        return $rows->sort(function ($left, $right) use ($priority): int {
            $deletedComparison = (! empty($left->deleted_at)) <=> (! empty($right->deleted_at));
            if ($deletedComparison !== 0) {
                return $deletedComparison;
            }

            $referenceComparison = (! empty($right->provider_reference)) <=> (! empty($left->provider_reference));
            if ($referenceComparison !== 0) {
                return $referenceComparison;
            }

            $statusComparison = ($priority[strtoupper((string) ($left->status ?? ''))] ?? 99)
                <=> ($priority[strtoupper((string) ($right->status ?? ''))] ?? 99);

            return $statusComparison !== 0
                ? $statusComparison
                : ((int) $left->id <=> (int) $right->id);
        })->first();
    }

    private function quarantinePayment(int $id, int $survivorId, mixed $orderId, string $provider, string $reason): void
    {
        $row = DB::table('payments')->where('id', $id)->first();
        if (! $row) {
            return;
        }

        $payload = $this->quarantinePayload($survivorId, $reason);
        $payload['order_id'] = null;

        if (Schema::hasColumn('payments', 'deleted_at')) {
            $payload['deleted_at'] = now();
        }
        if (Schema::hasColumn('payments', 'meta')) {
            $meta = $this->decodeMeta($row->meta ?? null);
            $meta['integrity_quarantine'] = [
                'quarantined_at' => now()->toIso8601String(),
                'duplicate_of_id' => $survivorId,
                'original_order_id' => $orderId,
                'provider' => $provider,
                'reason' => $reason,
            ];
            $payload['meta'] = json_encode($meta, JSON_UNESCAPED_UNICODE);
        }
        if (Schema::hasColumn('payments', 'updated_at')) {
            $payload['updated_at'] = now();
        }

        DB::table('payments')->where('id', $id)->update($payload);
    }

    private function quarantineDelivery(int $id, int $survivorId, mixed $orderId, string $reason): void
    {
        $payload = $this->quarantinePayload($survivorId, $reason);
        $payload['order_id'] = null;
        $payload['status'] = 'CANCELLED';

        if (Schema::hasColumn('deliveries', 'delivery_notes')) {
            $payload['delivery_notes'] = 'Quarantaine intégrité. Commande d’origine : '
                . $orderId . '. Doublon de la livraison #' . $survivorId . '.';
        }
        if (Schema::hasColumn('deliveries', 'updated_at')) {
            $payload['updated_at'] = now();
        }

        DB::table('deliveries')->where('id', $id)->update($payload);
    }

    private function quarantinePayload(int $survivorId, string $reason): array
    {
        return [
            'integrity_duplicate_of_id' => $survivorId,
            'integrity_quarantined_at' => now(),
            'integrity_quarantine_reason' => $reason,
        ];
    }

    private function quarantineSchemaReady(): bool
    {
        foreach (['payments', 'deliveries'] as $table) {
            foreach (['integrity_duplicate_of_id', 'integrity_quarantined_at', 'integrity_quarantine_reason'] as $column) {
                if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) {
                    return false;
                }
            }
        }

        return true;
    }

    private function distinctNonEmpty(Collection $rows, string $table, string $column): int
    {
        if (! Schema::hasColumn($table, $column)) {
            return 0;
        }

        return $rows->pluck($column)
            ->filter(fn ($value) => $value !== null && trim((string) $value) !== '')
            ->map(fn ($value) => (string) $value)
            ->unique()
            ->count();
    }

    private function safe(Collection $rows, int $survivorId, string $reason): array
    {
        return [
            'safe' => true,
            'survivor_id' => $survivorId,
            'quarantine_ids' => $rows->pluck('id')->map(fn ($id) => (int) $id)
                ->reject(fn ($id) => $id === $survivorId)->values()->all(),
            'reason' => $reason,
        ];
    }

    private function unsafe(string $reason): array
    {
        return ['safe' => false, 'survivor_id' => null, 'quarantine_ids' => [], 'reason' => $reason];
    }

    private function assertStillSafe(array $classification, string $type, string $reference): void
    {
        if (! $classification['safe']) {
            throw new RuntimeException("Le groupe de {$type} {$reference} a changé pendant la réparation.");
        }
    }

    private function decodeMeta(mixed $value): array
    {
        if (is_array($value)) {
            return $value;
        }

        $decoded = $value !== null && $value !== '' ? json_decode((string) $value, true) : null;
        return is_array($decoded) ? $decoded : [];
    }
}
