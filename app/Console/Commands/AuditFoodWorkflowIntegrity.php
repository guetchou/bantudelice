<?php

namespace App\Console\Commands;

use App\Services\FoodIntegrityReportService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class AuditFoodWorkflowIntegrity extends Command
{
    protected $signature = 'food:audit-integrity {--json : Retourner le rapport au format JSON}';

    protected $description = 'Détecte les doublons et incohérences avant activation des contraintes SQL du workflow food';

    public function __construct(
        protected FoodIntegrityReportService $reports
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $report = $this->reports->report();
        $total = (int) $report['violations_count'];

        Log::log($total === 0 ? 'info' : 'warning', 'Food workflow integrity audit', $report);

        if ($this->option('json')) {
            $this->line(json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        } else {
            $this->info('Audit d’intégrité du workflow restaurant');
            $this->line('Statut : ' . $report['status']);
            $this->line('Violations : ' . $total);

            foreach ($report['checks'] as $name => $rows) {
                $this->line(sprintf('- %s : %d', $name, count($rows)));
                foreach (array_slice($rows, 0, 10) as $row) {
                    $this->line('  ' . json_encode($row, JSON_UNESCAPED_UNICODE));
                }
                if (count($rows) > 10) {
                    $this->line('  … ' . (count($rows) - 10) . ' autre(s)');
                }
            }
        }

        return $total === 0 ? self::SUCCESS : self::FAILURE;
    }
}
