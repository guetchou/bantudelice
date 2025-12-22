<?php

namespace App\Console\Commands;

use App\Services\PaymentReconciliationService;
use Illuminate\Console\Command;

/**
 * Commande Artisan pour réconcilier les paiements
 * 
 * Usage: php artisan payments:reconcile [--limit=50] [--payment-id=123]
 */
class ReconcilePayments extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'payments:reconcile {--limit=50 : Nombre max de paiements à traiter} {--payment-id= : ID d\'un paiement spécifique}';

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

