<?php

namespace App\Console\Commands;

use App\Services\FoodIntegrityConstraintService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class EnforceFoodIntegrityConstraints extends Command
{
    protected $signature = 'food:enforce-integrity-constraints
        {--apply : Créer les contraintes si l’audit est propre}
        {--confirm= : Confirmation obligatoire ENFORCE_FOOD_CONSTRAINTS}';

    protected $description = 'Vérifie puis active les contraintes uniques du workflow food';

    public function __construct(
        protected FoodIntegrityConstraintService $constraints
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $status = $this->constraints->status();

        if (! $this->option('apply')) {
            $this->line(json_encode([
                'mode' => 'dry_run',
                'status' => $status,
                'apply_command' => 'php artisan food:enforce-integrity-constraints --apply --confirm=ENFORCE_FOOD_CONSTRAINTS',
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

            return (int) $status['violations_count'] === 0 ? self::SUCCESS : self::FAILURE;
        }

        if ((string) $this->option('confirm') !== 'ENFORCE_FOOD_CONSTRAINTS') {
            $this->error('Confirmation refusée. Utilisez --confirm=ENFORCE_FOOD_CONSTRAINTS.');
            return self::FAILURE;
        }

        try {
            $result = $this->constraints->apply();
            Log::warning('Food workflow integrity constraints enabled', $result);
            $this->line(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            return self::SUCCESS;
        } catch (\Throwable $e) {
            Log::error('Food workflow integrity constraints rejected', [
                'error' => $e->getMessage(),
                'status' => $status,
            ]);
            $this->error($e->getMessage());
            return self::FAILURE;
        }
    }
}
