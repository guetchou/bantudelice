<?php

namespace App\Console\Commands;

use App\Services\DispatchService;
use Illuminate\Console\Command;

/**
 * Commande Artisan pour traiter manuellement les livraisons en attente
 * 
 * Usage: php artisan dispatch:process-pending [--limit=10]
 */
class ProcessPendingDeliveries extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dispatch:process-pending {--limit=10 : Nombre max de livraisons à traiter}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Traiter les livraisons en attente et assigner automatiquement des livreurs';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $limit = (int) $this->option('limit');
        
        $this->info("Traitement de {$limit} livraisons en attente...");
        
        $dispatchService = new DispatchService();
        $result = $dispatchService->processPendingDeliveries($limit);
        
        $this->info("✅ Traitées: {$result['processed']}");
        $this->info("✅ Assignées: {$result['assigned']}");
        $this->info("❌ Échecs: {$result['failed']}");
        
        return 0;
    }
}

