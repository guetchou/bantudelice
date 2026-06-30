<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

final class AuditFinancialCoreArchitecture extends Command
{
    protected $signature = 'finance:audit-core-architecture
        {--path= : Alternate migration directory}
        {--json : Return machine-readable output}';

    protected $description = 'Detect incompatible financial migrations before merge or deployment.';

    private const WATCHED_TABLES = [
        'financial_accounts',
        'financial_journal_entries',
        'financial_journal_lines',
        'financial_posting_batches',
        'financial_postings',
        'financial_mirror_events',
        'financial_ledger_entries',
        'payment_allocations',
        'payment_reconciliation_cases',
        'payment_refunds',
        'financial_state_transitions',
    ];

    public function handle(): int
    {
        $path = trim((string) $this->option('path')) ?: database_path('migrations');

        if (! is_dir($path)) {
            $this->error('Migration directory not found: ' . $path);
            return self::FAILURE;
        }

        $creators = [];
        foreach (glob(rtrim($path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . '*.php') ?: [] as $file) {
            $content = file_get_contents($file);
            if ($content === false) {
                continue;
            }

            preg_match_all("/Schema::create\\(\s*['\"]([^'\"]+)['\"]/", $content, $matches);
            foreach ($matches[1] ?? [] as $table) {
                if (! in_array($table, self::WATCHED_TABLES, true)) {
                    continue;
                }
                $creators[$table][] = basename($file);
            }
        }

        ksort($creators);
        $conflicts = collect($creators)
            ->filter(fn (array $files) => count(array_unique($files)) > 1)
            ->map(fn (array $files) => array_values(array_unique($files)))
            ->all();

        $engine = (string) config('financial-core.engine', 'legacy');
        $allowed = (array) config('financial-core.allowed_engines', []);
        $engineValid = in_array($engine, $allowed, true);

        $result = [
            'engine' => $engine,
            'engine_valid' => $engineValid,
            'migration_path' => $path,
            'watched_table_creators' => $creators,
            'conflicts' => $conflicts,
        ];

        if ($this->option('json')) {
            $this->line(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        } else {
            $this->line('Configured financial engine: ' . $engine);

            $rows = [];
            foreach ($creators as $table => $files) {
                $rows[] = [$table, implode(', ', array_unique($files)), count(array_unique($files)) > 1 ? 'CONFLICT' : 'OK'];
            }

            $this->table(['Business table', 'Creating migration(s)', 'Status'], $rows);
        }

        if (! $engineValid) {
            $this->error('Unknown FINANCIAL_CORE_ENGINE value: ' . $engine);
            return self::FAILURE;
        }

        if ($conflicts !== [] && (bool) config('financial-core.fail_on_migration_conflict', true)) {
            foreach ($conflicts as $table => $files) {
                $this->error($table . ' is created by: ' . implode(', ', $files));
            }
            $this->error('Do not merge or deploy competing financial cores together.');
            return self::FAILURE;
        }

        $this->info('No competing financial table creation detected.');
        return self::SUCCESS;
    }
}
