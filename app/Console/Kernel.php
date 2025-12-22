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
        //
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
            $dispatchService = new \App\Services\DispatchService();
            $result = $dispatchService->processPendingDeliveries(20); // Max 20 livraisons par exécution
            
            \Log::info('Dispatch automatique exécuté', $result);
        })->everyTwoMinutes()->withoutOverlapping();
        
        // Réconciliation automatique : vérifier les paiements en attente toutes les 10 minutes
        $schedule->call(function () {
            $reconciliationService = new \App\Services\PaymentReconciliationService();
            $result = $reconciliationService->reconcilePendingPayments(50); // Max 50 paiements par exécution
            
            \Log::info('Réconciliation automatique exécutée', $result);
        })->everyTenMinutes()->withoutOverlapping();
        
        // Génération métriques quotidiennes : chaque jour à 1h du matin
        $schedule->command('metrics:generate-daily')
            ->dailyAt('01:00')
            ->withoutOverlapping();
        
        // Vérification alertes : toutes les 5 minutes
        $schedule->call(function () {
            $alertingService = new \App\Services\AlertingService();
            $alerts = $alertingService->checkAndSendAlerts();
            
            if (!empty($alerts)) {
                \Log::info('Alertes envoyées', ['count' => count($alerts)]);
            }
        })->everyFiveMinutes()->withoutOverlapping();
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
