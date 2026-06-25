<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ApplyFoodIntegrityConstraints extends Command
{
    protected $signature = 'food:apply-integrity-constraints
        {--dry-run : Vérifie uniquement la possibilité d’appliquer les contraintes}
        {--force : Confirme l’activation des index uniques}';

    protected $description = 'Active les contraintes uniques du workflow food après contrôle des données historiques';

    public function handle(): int
    {
        $violations = [
            'duplicate_payments' => $this->duplicatePayments(),
            'duplicate_deliveries' => $this->duplicateDeliveries(),
        ];

        $count = collect($violations)->sum(fn (array $rows) => count($rows));
        if ($count > 0) {
            $this->error("Contraintes non appliquées : {$count} doublon(s) doivent être corrigés.");
            foreach ($violations as $name => $rows) {
                $this->line(sprintf('- %s : %d', $name, count($rows)));
            }
            return self::FAILURE;
        }

        $planned = $this->plannedConstraints();
        if ($planned === []) {
            $this->info('Aucune contrainte à ajouter.');
            return self::SUCCESS;
        }

        foreach ($planned as $constraint) {
            $this->line('- ' . $constraint['name'] . ' sur ' . $constraint['table']);
        }

        if ($this->option('dry-run')) {
            $this->info('Dry-run terminé. Aucune modification effectuée.');
            return self::SUCCESS;
        }

        if (! $this->option('force')) {
            $this->error('Ajoutez --force après validation du dry-run.');
            return self::FAILURE;
        }

        foreach ($planned as $constraint) {
            Schema::table($constraint['table'], function ($table) use ($constraint) {
                $table->unique($constraint['columns'], $constraint['name']);
            });
            $this->info('Ajouté : ' . $constraint['name']);
        }

        return self::SUCCESS;
    }

    private function plannedConstraints(): array
    {
        $constraints = [];

        if ($this->supportsPaymentConstraint()
            && ! $this->indexExists('payments', 'payments_order_provider_unique')) {
            $constraints[] = [
                'table' => 'payments',
                'columns' => ['order_id', 'provider'],
                'name' => 'payments_order_provider_unique',
            ];
        }

        if ($this->supportsDeliveryConstraint()
            && ! $this->indexExists('deliveries', 'deliveries_order_unique')) {
            $constraints[] = [
                'table' => 'deliveries',
                'columns' => ['order_id'],
                'name' => 'deliveries_order_unique',
            ];
        }

        return $constraints;
    }

    private function supportsPaymentConstraint(): bool
    {
        return Schema::hasTable('payments')
            && Schema::hasColumn('payments', 'order_id')
            && Schema::hasColumn('payments', 'provider');
    }

    private function supportsDeliveryConstraint(): bool
    {
        return Schema::hasTable('deliveries')
            && Schema::hasColumn('deliveries', 'order_id');
    }

    private function duplicatePayments(): array
    {
        if (! $this->supportsPaymentConstraint()) {
            return [];
        }

        return DB::table('payments')
            ->select('order_id', 'provider', DB::raw('COUNT(*) as occurrences'))
            ->whereNotNull('order_id')
            ->groupBy('order_id', 'provider')
            ->havingRaw('COUNT(*) > 1')
            ->get()
            ->map(fn ($row) => (array) $row)
            ->all();
    }

    private function duplicateDeliveries(): array
    {
        if (! $this->supportsDeliveryConstraint()) {
            return [];
        }

        return DB::table('deliveries')
            ->select('order_id', DB::raw('COUNT(*) as occurrences'))
            ->whereNotNull('order_id')
            ->groupBy('order_id')
            ->havingRaw('COUNT(*) > 1')
            ->get()
            ->map(fn ($row) => (array) $row)
            ->all();
    }

    private function indexExists(string $table, string $index): bool
    {
        try {
            $manager = DB::connection()->getDoctrineSchemaManager();
            $indexes = $manager->listTableIndexes($table);
            return array_key_exists(strtolower($index), array_change_key_case($indexes));
        } catch (\Throwable) {
            return false;
        }
    }
}
