<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected $commands = [
        \App\Console\Commands\PublishScheduledCmsContent::class,
        \App\Console\Commands\RefreshMissionPresence::class,
    ];

    protected function schedule(Schedule $schedule)
    {
        $schedule->command('dispatch:process-pending --limit=20')
            ->everyTwoMinutes()
            ->name('dispatch-offres-livreurs')
            ->withoutOverlapping();

        $schedule->call(function () {
            $reconciliationService = new \App\Services\PaymentReconciliationService();
            $result = $reconciliationService->reconcilePendingPayments(50);
            \Log::info('Réconciliation automatique exécutée', $result);
        })->name('reconciliation-automatique')->everyMinute()->withoutOverlapping();

        $schedule->call(function () {
            $result = app(\App\Services\DisbursementReconciliationService::class)
                ->reconcilePending(50);

            if (($result['processed'] ?? 0) > 0 || ($result['errors'] ?? 0) > 0) {
                \Log::info('Réconciliation automatique des décaissements exécutée', $result);
            }
        })->name('reconciliation-decaissements-mtn')->everyMinute()->withoutOverlapping();

        $schedule->command('metrics:generate-daily')
            ->dailyAt('01:00')
            ->name('metrics-quotidiennes')
            ->withoutOverlapping();

        $schedule->call(function () {
            $alertingService = new \App\Services\AlertingService();
            $alerts = $alertingService->checkAndSendAlerts();
            if (! empty($alerts)) {
                \Log::info('Alertes envoyées', ['count' => count($alerts)]);
            }
        })->name('alertes-automatiques')->everyFiveMinutes()->withoutOverlapping();

        $schedule->command('cms:publish-scheduled')
            ->everyMinute()
            ->name('cms-publication-planifiee')
            ->withoutOverlapping();

        $schedule->command('missions:refresh-presence')
            ->everyMinute()
            ->name('missions-presence-refresh')
            ->withoutOverlapping();

        $schedule->command('restaurants:auto-pause --minutes=20')
            ->everyFiveMinutes()
            ->name('auto-pause-restaurants')
            ->withoutOverlapping();

        $schedule->command('food:expire-unaccepted --limit=100')
            ->everyMinute()
            ->name('food-expire-unaccepted')
            ->withoutOverlapping();

        $schedule->command('food:release-scheduled --limit=100')
            ->everyMinute()
            ->name('food-release-scheduled')
            ->withoutOverlapping();

        $schedule->command('food:project-order-headers --minutes=15')
            ->everyFiveMinutes()
            ->name('food-project-order-headers')
            ->withoutOverlapping();

        $schedule->command('food:audit-integrity --json')
            ->dailyAt('02:15')
            ->name('food-audit-integrity')
            ->withoutOverlapping();

        $schedule->command('payments:backfill-business --days=7 --limit=1000')
            ->dailyAt('02:35')
            ->name('payments-business-integrity')
            ->withoutOverlapping();

        $schedule->command('payments:check-fraud')
            ->hourly()
            ->name('fraud-check')
            ->withoutOverlapping();

        $schedule->command('db:optimize')
            ->dailyAt('03:00')
            ->name('db-optimize')
            ->withoutOverlapping();

        $schedule->command('food:expire-unpaid-accepted')
            ->everyTwoMinutes()
            ->name('food-expire-unpaid-accepted')
            ->withoutOverlapping();
    }

    protected function commands()
    {
        $this->load(__DIR__.'/Commands');
        require base_path('routes/console.php');
    }
}
