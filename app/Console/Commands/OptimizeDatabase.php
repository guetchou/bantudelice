<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Commande Artisan pour analyser et optimiser la base de données
 * 
 * Usage: php artisan db:optimize
 */
class OptimizeDatabase extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:optimize {--analyze : Analyser les tables sans optimiser}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Optimiser les tables de la base de données (ANALYZE TABLE, OPTIMIZE TABLE)';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $tables = [
            'orders',
            'restaurants',
            'products',
            'categories',
            'deliveries',
            'payments',
            'users',
            'drivers',
            'carts',
        ];

        if ($this->option('analyze')) {
            $this->info('Analyse des tables...');
            foreach ($tables as $table) {
                if (Schema::hasTable($table)) {
                    try {
                        DB::statement("ANALYZE TABLE `{$table}`");
                        $this->line("  ✓ {$table}");
                    } catch (\Exception $e) {
                        $this->warn("  ✗ {$table}: " . $e->getMessage());
                    }
                }
            }
            $this->info('Analyse terminée.');
            return 0;
        }

        $this->info('Optimisation des tables...');
        foreach ($tables as $table) {
            if (Schema::hasTable($table)) {
                try {
                    DB::statement("OPTIMIZE TABLE `{$table}`");
                    $this->line("  ✓ {$table}");
                } catch (\Exception $e) {
                    $this->warn("  ✗ {$table}: " . $e->getMessage());
                }
            }
        }
        $this->info('Optimisation terminée.');
        
        return 0;
    }
}

