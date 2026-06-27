<?php

namespace App\Console\Commands;

use App\Domain\GePay\Models\GePayClient;
use App\Domain\GePay\Models\GePayTransaction;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class GePayHealth extends Command
{
    protected $signature = 'gepay:health';
    protected $description = "Vérifier l'état opérationnel de la passerelle GePay.";

    public function handle(): int
    {
        $issues = [];

        $enabled = config('gepay.enabled', false);
        $mtnEnabled = config('gepay.providers.mtn_momo.enabled', false);

        $this->info('=== GePay Health Check ===');
        $this->line('Service    : '.($enabled ? '<fg=green>activé</>' : '<fg=yellow>désactivé</>'));
        $this->line('MTN MoMo   : '.($mtnEnabled ? '<fg=green>activé</>' : '<fg=yellow>désactivé</>'));
        $this->line('Environnement MTN : '.config('gepay.providers.mtn_momo.environment', '?'));
        $this->line('Target env MTN    : '.config('gepay.providers.mtn_momo.target_environment', '?'));

        try {
            DB::connection()->getPdo();
            $clients = GePayClient::query()->where('is_active', true)->count();
            $pending = GePayTransaction::query()->whereIn('status', ['submitted', 'pending', 'unknown'])->count();
            $this->line("Clients actifs : {$clients}");
            $this->line("Transactions non terminales : {$pending}");
        } catch (\Throwable $e) {
            $issues[] = 'DB inaccessible : '.$e->getMessage();
        }

        if ($issues !== []) {
            foreach ($issues as $issue) {
                $this->error($issue);
            }
            return self::FAILURE;
        }

        $this->info('OK — aucun problème détecté.');
        return self::SUCCESS;
    }
}
