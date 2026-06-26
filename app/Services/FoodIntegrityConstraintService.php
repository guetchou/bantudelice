<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class FoodIntegrityConstraintService
{
    public const PAYMENT_INDEX = 'payments_order_provider_unique';
    public const DELIVERY_INDEX = 'deliveries_order_unique';

    public function __construct(
        protected FoodIntegrityReportService $reports
    ) {}

    public function status(): array
    {
        $audit = $this->reports->report();
        $driver = DB::connection()->getDriverName();

        return [
            'driver' => $driver,
            'audit_status' => $audit['status'],
            'violations_count' => $audit['violations_count'],
            'payments_ready' => $this->paymentsSchemaReady(),
            'deliveries_ready' => Schema::hasTable('deliveries')
                && Schema::hasColumn('deliveries', 'order_id'),
            'payment_constraint_active' => $this->indexExists('payments', self::PAYMENT_INDEX),
            'delivery_constraint_active' => $this->indexExists('deliveries', self::DELIVERY_INDEX),
            'audit' => $audit,
        ];
    }

    public function apply(): array
    {
        $before = $this->status();

        if ((int) $before['violations_count'] > 0) {
            throw new RuntimeException(
                'Contraintes refusées : l’audit contient encore '
                . $before['violations_count']
                . ' violation(s).'
            );
        }

        if (! $before['payments_ready'] || ! $before['deliveries_ready']) {
            throw new RuntimeException(
                'Contraintes refusées : schéma incomplet. Exécutez les migrations avant cette commande.'
            );
        }

        if (! $before['payment_constraint_active']) {
            $this->createPaymentIndex((string) $before['driver']);
        }

        if (! $before['delivery_constraint_active']) {
            $this->createDeliveryIndex((string) $before['driver']);
        }

        $after = $this->status();
        if (! $after['payment_constraint_active'] || ! $after['delivery_constraint_active']) {
            throw new RuntimeException('Impossible de confirmer les contraintes après création.');
        }

        return [
            'applied_at' => now()->toIso8601String(),
            'driver' => $after['driver'],
            'payment_constraint_active' => true,
            'delivery_constraint_active' => true,
        ];
    }

    private function paymentsSchemaReady(): bool
    {
        return Schema::hasTable('payments')
            && Schema::hasColumn('payments', 'order_id')
            && Schema::hasColumn('payments', 'provider');
    }

    private function createPaymentIndex(string $driver): void
    {
        if ($driver === 'mysql') {
            DB::statement(
                'CREATE UNIQUE INDEX ' . self::PAYMENT_INDEX
                . ' ON payments (order_id, provider)'
            );
            return;
        }

        $filter = 'order_id IS NOT NULL AND provider IS NOT NULL';
        if (Schema::hasColumn('payments', 'deleted_at')) {
            $filter .= ' AND deleted_at IS NULL';
        }

        if (in_array($driver, ['sqlite', 'pgsql', 'sqlsrv'], true)) {
            DB::statement(
                'CREATE UNIQUE INDEX ' . self::PAYMENT_INDEX
                . ' ON payments (order_id, provider) WHERE ' . $filter
            );
            return;
        }

        throw new RuntimeException('Pilote non pris en charge : ' . $driver);
    }

    private function createDeliveryIndex(string $driver): void
    {
        if ($driver === 'mysql') {
            DB::statement(
                'CREATE UNIQUE INDEX ' . self::DELIVERY_INDEX
                . ' ON deliveries (order_id)'
            );
            return;
        }

        if (in_array($driver, ['sqlite', 'pgsql', 'sqlsrv'], true)) {
            DB::statement(
                'CREATE UNIQUE INDEX ' . self::DELIVERY_INDEX
                . ' ON deliveries (order_id) WHERE order_id IS NOT NULL'
            );
            return;
        }

        throw new RuntimeException('Pilote non pris en charge : ' . $driver);
    }

    private function indexExists(string $table, string $index): bool
    {
        if (! Schema::hasTable($table)) {
            return false;
        }

        $driver = DB::connection()->getDriverName();

        if ($driver === 'sqlite') {
            $safeTable = str_replace("'", "''", $table);
            $rows = DB::select("PRAGMA index_list('{$safeTable}')");
            return collect($rows)->contains(fn ($row) => (string) ($row->name ?? '') === $index);
        }

        if ($driver === 'mysql') {
            return DB::table('information_schema.statistics')
                ->whereRaw('table_schema = DATABASE()')
                ->where('table_name', $table)
                ->where('index_name', $index)
                ->exists();
        }

        if ($driver === 'pgsql') {
            return DB::table('pg_indexes')
                ->whereRaw('schemaname = current_schema()')
                ->where('tablename', $table)
                ->where('indexname', $index)
                ->exists();
        }

        if ($driver === 'sqlsrv') {
            return count(DB::select(
                'SELECT 1 FROM sys.indexes WHERE name = ? AND object_id = OBJECT_ID(?)',
                [$index, $table]
            )) > 0;
        }

        return false;
    }
}
