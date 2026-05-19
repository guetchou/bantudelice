<?php

namespace App\Console\Commands;

use App\Services\MetricsService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * Commande Artisan pour générer les métriques quotidiennes
 * 
 * Usage: php artisan metrics:generate-daily [--date=2025-12-17]
 */
class GenerateDailyMetrics extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'metrics:generate-daily {--date= : Date au format Y-m-d (par défaut: hier)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Générer les métriques quotidiennes pour une date donnée';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $dateStr = $this->option('date');
        
        if ($dateStr) {
            try {
                $date = Carbon::createFromFormat('Y-m-d', $dateStr);
            } catch (\Exception $e) {
                $this->error('Format de date invalide. Utilisez Y-m-d (ex: 2025-12-17)');
                return 1;
            }
        } else {
            $date = now()->subDay(); // Hier par défaut
        }

        $this->info("Génération des métriques pour le {$date->format('Y-m-d')}...");

        $metricsService = new MetricsService();
        $metrics = $metricsService->generateDailyMetrics($date);

        // Enregistrer dans la DB
        try {
            DB::table('daily_metrics')->updateOrInsert(
                ['date' => $metrics['date']],
                $metrics
            );

            $this->info("✅ Métriques générées avec succès:");
            $this->line("  - Commandes: {$metrics['orders_count']}");
            $this->line("  - Revenus: " . number_format($metrics['revenue'], 0, ',', ' ') . " FCFA");
            $this->line("  - Temps moyen livraison: " . ($metrics['avg_delivery_time'] ?? 'N/A') . " min");
            $this->line("  - Taux succès paiement: " . ($metrics['payment_success_rate'] ?? 'N/A') . "%");

            return 0;
        } catch (\Exception $e) {
            $this->error("Erreur lors de l'enregistrement: " . $e->getMessage());
            return 1;
        }
    }
}

