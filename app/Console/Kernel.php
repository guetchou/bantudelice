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
            $result = $reconciliationService->reconcilePendingPayments(50);

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

            if (! empty($alerts)) {
                \Log::info('Alertes envoyées', ['count' => count($alerts)]);
            }
        })->name('alertes-automatiques')->everyFiveMinutes()->withoutOverlapping();

        // Vérification des contenus CMS planifiés chaque minute
        $schedule->command('cms:publish-scheduled')
            ->everyMinute()
            ->name('cms-publication-planifiee')
            ->withoutOverlapping();

        // Rafraîchir les présences de mission même sans nouveau GPS
        $schedule->command('missions:refresh-presence')
            ->everyMinute()
            ->name('missions-presence-refresh')
            ->withoutOverlapping();

        // Auto-pause restaurants inactifs
        $schedule->command('restaurants:auto-pause --minutes=20')
            ->everyFiveMinutes()
            ->name('auto-pause-restaurants')
            ->withoutOverlapping();

        // Une commande ignorée par le restaurant ne reste jamais ouverte indéfiniment.
        $schedule->command('food:expire-unaccepted --limit=100')
            ->everyMinute()
            ->name('food-expire-unaccepted')
            ->withoutOverlapping();

        // Vérification fraude : toutes les heures
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

        // Expire les commandes en accepted_awaiting_payment dont le paiement n'est pas arrivé à temps
        $schedule->command('food:expire-unpaid-accepted')
            ->everyTwoMinutes()
            ->name('food-expire-unpaid-accepted')
            ->withoutOverlapping();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
