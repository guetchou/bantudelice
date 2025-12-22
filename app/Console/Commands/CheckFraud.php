<?php

namespace App\Console\Commands;

use App\Payment;
use App\Services\FraudDetectionService;
use Illuminate\Console\Command;

/**
 * Commande Artisan pour vérifier la fraude sur un paiement
 * 
 * Usage: php artisan payments:check-fraud --payment-id=123
 */
class CheckFraud extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'payments:check-fraud {--payment-id= : ID du paiement à vérifier}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Vérifier si un paiement est suspect (anti-fraude)';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        if (!$this->option('payment-id')) {
            $this->error('--payment-id requis');
            return 1;
        }
        
        $payment = Payment::find($this->option('payment-id'));
        
        if (!$payment) {
            $this->error('Paiement non trouvé');
            return 1;
        }
        
        $fraudService = new FraudDetectionService();
        $result = $fraudService->checkFraud($payment, [
            'ip' => request()->ip() ?? '127.0.0.1'
        ]);
        
        $this->info("Paiement #{$payment->id}");
        $this->info("Score de risque: {$result['risk_score']}/100");
        $this->info("Fraude détectée: " . ($result['is_fraud'] ? 'OUI' : 'NON'));
        $this->info("Recommandation: {$result['recommendation']}");
        
        if (!empty($result['reasons'])) {
            $this->warn("Raisons:");
            foreach ($result['reasons'] as $reason) {
                $this->line("  - {$reason}");
            }
        }
        
        return 0;
    }
}

