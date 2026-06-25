<?php

namespace App\Console\Commands;

use App\Services\DispatchService;
use Illuminate\Console\Command;

class ProcessPendingDeliveries extends Command
{
    protected $signature = 'dispatch:process-pending {--limit=10 : Nombre max de livraisons à traiter}';

    protected $description = 'Relancer les offres de missions pour les livraisons en attente';

    public function handle(DispatchService $dispatchService): int
    {
        $limit = max(1, (int) $this->option('limit'));
        $this->info("Traitement de {$limit} livraisons en attente...");

        $result = $dispatchService->processPendingDeliveries($limit);

        $this->info('Traitées : ' . ($result['processed'] ?? 0));
        $this->info('Offres mises en file : ' . ($result['queued'] ?? 0));
        $this->info('Déjà couvertes par une offre active : ' . ($result['skipped'] ?? 0));
        $this->info('Échecs : ' . ($result['failed'] ?? 0));

        return self::SUCCESS;
    }
}
