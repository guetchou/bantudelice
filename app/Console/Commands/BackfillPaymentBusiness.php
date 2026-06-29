<?php

namespace App\Console\Commands;

use App\Payment;
use App\Services\PaymentBusinessService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Throwable;

class BackfillPaymentBusiness extends Command
{
    protected $signature = 'payments:backfill-business
        {--days=30 : Période historique à analyser}
        {--limit=500 : Nombre maximal de paiements à traiter}
        {--dry-run : Afficher le périmètre sans écrire}';

    protected $description = 'Rattrape les allocations, écritures et dossiers métier des paiements confirmés';

    public function handle(PaymentBusinessService $business): int
    {
        if (! Schema::hasTable('payments')) {
            $this->error('Table payments absente.');
            return self::FAILURE;
        }

        if (! Schema::hasTable('payment_allocations') || ! Schema::hasTable('payment_reconciliation_cases')) {
            $this->error('Exécuter les migrations du métier Paiements avant ce rattrapage.');
            return self::FAILURE;
        }

        $days = max(1, min(3650, (int) $this->option('days')));
        $limit = max(1, min(10000, (int) $this->option('limit')));
        $dryRun = (bool) $this->option('dry-run');

        $query = Payment::query()
            ->whereIn('status', ['PAID', 'SUCCESS', 'SUCCESSFUL'])
            ->where('created_at', '>=', now()->subDays($days))
            ->where(function ($stateQuery): void {
                $stateQuery->whereNull('financial_state')
                    ->orWhere('financial_state', 'confirmed');
            })
            ->orderBy('id')
            ->limit($limit);

        $count = (clone $query)->count();
        $this->info("Paiements confirmés à contrôler : {$count}");

        if ($dryRun || $count === 0) {
            return self::SUCCESS;
        }

        $summary = [
            'processed' => 0,
            'released' => 0,
            'held' => 0,
            'cases' => 0,
            'errors' => 0,
        ];

        $query->get()->each(function (Payment $payment) use ($business, &$summary): void {
            try {
                if ($payment->financial_state === null) {
                    $payment->forceFill([
                        'financial_state' => 'confirmed',
                        'financial_state_changed_at' => $payment->updated_at ?? now(),
                    ])->save();
                }

                $result = $business->recordConfirmedPayment($payment, [
                    'source' => 'payments:backfill-business',
                    'backfilled_at' => now()->toIso8601String(),
                ]);

                $summary['processed']++;
                $summary[($result['release_target'] ?? false) ? 'released' : 'held']++;
                if (! empty($result['case'])) {
                    $summary['cases']++;
                }
            } catch (Throwable $exception) {
                $summary['errors']++;
                Log::error('Échec du rattrapage métier d’un paiement', [
                    'payment_id' => $payment->id,
                    'error' => $exception->getMessage(),
                ]);
                $this->warn("Paiement #{$payment->id} : {$exception->getMessage()}");
            }
        });

        $this->table(
            ['Traités', 'Libérés', 'Bloqués', 'Dossiers', 'Erreurs'],
            [[
                $summary['processed'],
                $summary['released'],
                $summary['held'],
                $summary['cases'],
                $summary['errors'],
            ]]
        );

        return $summary['errors'] > 0 ? self::FAILURE : self::SUCCESS;
    }
}
