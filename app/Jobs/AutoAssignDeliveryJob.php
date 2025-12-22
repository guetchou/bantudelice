<?php

namespace App\Jobs;

use App\Delivery;
use App\Services\DispatchService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Job pour assigner automatiquement un livreur à une livraison
 */
class AutoAssignDeliveryJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $delivery;

    /**
     * Create a new job instance.
     *
     * @param Delivery $delivery
     */
    public function __construct(Delivery $delivery)
    {
        $this->delivery = $delivery;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        // Recharger la livraison depuis la DB (au cas où elle a changé)
        $delivery = Delivery::find($this->delivery->id);
        
        if (!$delivery) {
            Log::warning('Livraison introuvable dans AutoAssignDeliveryJob', [
                'delivery_id' => $this->delivery->id
            ]);
            return;
        }

        // Vérifier que la livraison est toujours en attente
        if ($delivery->status !== 'PENDING') {
            Log::info('Livraison déjà assignée, job ignoré', [
                'delivery_id' => $delivery->id,
                'status' => $delivery->status
            ]);
            return;
        }

        $dispatchService = new DispatchService();
        $success = $dispatchService->autoAssign($delivery);

        if (!$success) {
            // Si échec, réessayer plus tard (retry automatique par Laravel)
            Log::info('Échec assignation auto, job sera retenté', [
                'delivery_id' => $delivery->id
            ]);
            throw new \Exception('Aucun livreur disponible pour cette livraison');
        }
    }

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * Calculate the number of seconds to wait before retrying the job.
     *
     * @return array
     */
    public function backoff()
    {
        return [30, 60, 120]; // Retry après 30s, 60s, 120s
    }
}

