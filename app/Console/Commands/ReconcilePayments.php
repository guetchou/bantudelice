<?php

namespace App\Console\Commands;

use App\Services\PaymentReconciliationService;
use Illuminate\Console\Command;

/**
 * Commande Artisan pour réconcilier les paiements
 * 
 * Usage: php artisan payments:reconcile [--limit=50] [--payment-id=123] [--backfill-failed] [--dry-run]
 */
class ReconcilePayments extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'payments:reconcile
                            {--limit=50 : Nombre max de paiements à traiter}
                            {--payment-id= : ID d\'un paiement spécifique}
                            {--backfill-failed : Recalcule les diagnostics des paiements FAILED}
                            {--dry-run : Prévisualise les mises à jour sans écrire en base}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Réconcilier les paiements avec les providers (vérifier statut réel)';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $reconciliationService = new PaymentReconciliationService();

        if ($this->option('backfill-failed')) {
            $limit = (int) $this->option('limit');
            $paymentId = $this->option('payment-id') ? (int) $this->option('payment-id') : null;
            $dryRun = (bool) $this->option('dry-run');

            $this->info(
                $paymentId
                    ? "Backfill diagnostic du paiement #{$paymentId}..."
                    : "Backfill diagnostic de {$limit} paiements FAILED..."
            );

            $result = $reconciliationService->backfillFailedPaymentDiagnostics($limit, $paymentId, $dryRun);

            $this->info("Traités: {$result['processed']}");
            $this->info("Mis à jour: {$result['updated']}");
            $this->info("Ignorés: {$result['skipped']}");
            $this->info("Erreurs: {$result['errors']}");

            foreach ($result['items'] as $item) {
                $this->line(sprintf(
                    '#%d %s %s',
                    $item['payment_id'],
                    $item['failure_reason'] ?? 'N/A',
                    $item['provider_reference'] ?? 'sans-reference'
                ));
            }

            return $result['errors'] > 0 ? 1 : 0;
        }
        
        // Si un ID spécifique est fourni
        if ($this->option('payment-id')) {
            $payment = \App\Payment::find($this->option('payment-id'));
            
            if (!$payment) {
                $this->error('Paiement non trouvé');
                return 1;
            }
            
            $this->info("Réconciliation du paiement #{$payment->id}...");
            $result = $reconciliationService->reconcile($payment);
            
            $this->info("Statut: {$result['status']}");
            $this->info("Message: {$result['message']}");
            $this->info("Réconcilié: " . ($result['reconciled'] ? 'Oui' : 'Non'));
            
            return 0;
        }
        
        // Sinon, traiter les paiements en attente
        $limit = (int) $this->option('limit');
        $this->info("Réconciliation de {$limit} paiements en attente...");
        
        $result = $reconciliationService->reconcilePendingPayments($limit);
        
        $this->info("✅ Traités: {$result['processed']}");
        $this->info("✅ Réconciliés: {$result['reconciled']}");
        $this->info("❌ Échecs: {$result['failed']}");
        
        return 0;
    }
}
