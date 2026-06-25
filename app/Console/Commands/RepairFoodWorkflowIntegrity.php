<?php

namespace App\Console\Commands;

use App\Services\FoodIntegrityRepairService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class RepairFoodWorkflowIntegrity extends Command
{
    protected $signature = 'food:repair-integrity
        {--apply : Mettre en quarantaine uniquement les doublons classés sans ambiguïté}
        {--confirm= : Confirmation obligatoire APPLY_SAFE_REPAIRS}';

    protected $description = 'Planifie ou applique la quarantaine réversible des doublons food';

    public function __construct(
        protected FoodIntegrityRepairService $repairs
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $plan = $this->repairs->plan();

        if (! $this->option('apply')) {
            $this->line(json_encode([
                'mode' => 'dry_run',
                'plan' => $plan,
                'apply_command' => 'php artisan food:repair-integrity --apply --confirm=APPLY_SAFE_REPAIRS',
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

            return $plan['manual_reviews_count'] > 0 ? self::FAILURE : self::SUCCESS;
        }

        if ((string) $this->option('confirm') !== 'APPLY_SAFE_REPAIRS') {
            $this->error('Confirmation refusée. Utilisez --confirm=APPLY_SAFE_REPAIRS.');
            return self::FAILURE;
        }

        if (! $plan['quarantine_schema_ready']) {
            $this->error('Les colonnes de quarantaine sont absentes. Exécutez les migrations.');
            return self::FAILURE;
        }

        if ((int) $plan['safe_repairs_count'] === 0) {
            $this->info('Aucun doublon sûr à mettre en quarantaine.');
            if ((int) $plan['manual_reviews_count'] > 0) {
                $this->warn('Des groupes nécessitent encore une vérification humaine.');
            }
            return $plan['manual_reviews_count'] > 0 ? self::FAILURE : self::SUCCESS;
        }

        $result = $this->repairs->apply();
        Log::warning('Food workflow integrity duplicates quarantined', $result);

        $this->line(json_encode([
            'mode' => 'applied',
            'result' => $result,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        return count($result['manual_reviews']['payments']) + count($result['manual_reviews']['deliveries']) > 0
            ? self::FAILURE
            : self::SUCCESS;
    }
}
