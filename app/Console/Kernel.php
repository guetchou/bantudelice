<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        \App\Console\Commands\PublishScheduledCmsContent::class,
        \App\Console\Commands\RefreshMissionPresence::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // Dispatch automatique : traiter les livraisons en attente toutes les 2 minutes
        $schedule->call(function () {
            $result = app(\App\Services\DispatchService::class)->processPendingDeliveries(20);
            \Log::info('Dispatch automatique exécuté', $result);
        })->name('dispatch-automatique')->everyTwoMinutes()->withoutOverlapping();
        
        // Réconciliation automatique : vérifier les paiements en attente chaque minute
        $schedule->call(function () {
            $reconciliationService = new \App\Services\PaymentReconciliationService();
            $result = $reconciliationService->reconcilePendingPayments(50); // Max 50 paiements par exécution
            
            \Log::info('Réconciliation automatique exécutée', $result);
        })->name('reconciliation-automatique')->everyMinute()->withoutOverlapping();
        
        // Génération métriques quotidiennes : chaque jour à 1h du matin
        $schedule->command('metrics:generate-daily')
            ->dailyAt('01:00')
            ->name('metrics-quotidiennes')
            ->withoutOverlapping();
        
        // Vérification alertes : toutes les 5 minutes
        $schedule->call(function () {
            $alertingService = new \App\Services\AlertingService();
            $alerts = $alertingService->checkAndSendAlerts();
            
            if (!empty($alerts)) {
                \Log::info('Alertes envoyées', ['count' => count($alerts)]);
            }
        })->name('alertes-automatiques')->everyFiveMinutes()->withoutOverlapping();

        // Vérification des contenus CMS planifies chaque minute
        $schedule->command('cms:publish-scheduled')
            ->everyMinute()
            ->name('cms-publication-planifiee')
            ->withoutOverlapping();

        // Rafraîchir les présences de mission même sans nouveau GPS
        $schedule->command('missions:refresh-presence')
            ->everyMinute()
            ->name('missions-presence-refresh')
            ->withoutOverlapping();

        // T1.2 — Auto-pause restaurants inactifs (E2C Brazzaville)
        $schedule->command('restaurants:auto-pause --minutes=20')
            ->everyFiveMinutes()
            ->name('auto-pause-restaurants')
            ->withoutOverlapping();

        // Vérification fraude : toutes les heures (transactions suspectes, double paiement)
        $schedule->command('payments:check-fraud')
            ->hourly()
            ->name('fraud-check')
            ->withoutOverlapping();

        // Optimisation DB : index, stats — chaque nuit à 3h
        $schedule->command('db:optimize')
            ->dailyAt('03:00')
            ->name('db-optimize')
            ->withoutOverlapping();

        // Livraisons bloquées : forcer le traitement des PENDING
        $schedule->command('dispatch:process-pending --limit=20')
            ->everyFiveMinutes()
            ->name('dispatch-process-pending')
            ->withoutOverlapping();
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
