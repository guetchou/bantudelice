<?php

namespace App\Jobs;

use App\Delivery;
use App\DeliveryOffer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Déclenché OFFER_WINDOW secondes après BroadcastDeliveryOfferJob.
 * Si aucun livreur n'a accepté → expire les offres en attente → lance le round suivant.
 */
class ExpireDeliveryOfferJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 30;
    public $tries   = 1;

    public function __construct(
        protected int $deliveryId,
        protected int $round
    ) {
        $this->onConnection(config('module_queues.modules.food.connection', 'database_food'));
        $this->onQueue(config('module_queues.modules.food.queue', 'food'));
    }

    public function handle(): void
    {
        $delivery = Delivery::find($this->deliveryId);

        // Déjà assignée → rien à faire
        if (!$delivery || $delivery->status !== 'PENDING') {
            return;
        }

        // Expirer toutes les offres pending de ce round
        $expired = DeliveryOffer::where('delivery_id', $this->deliveryId)
            ->where('status', 'pending')
            ->where('expires_at', '<=', now())
            ->update(['status' => 'expired']);

        Log::info('ExpireDeliveryOffer: offres expirées', [
            'delivery_id' => $this->deliveryId,
            'round'       => $this->round,
            'expired'     => $expired,
        ]);

        // Lancer le round suivant immédiatement
        BroadcastDeliveryOfferJob::dispatch($this->deliveryId, $this->round + 1);
    }
}
