<?php

namespace App\Jobs;

use App\Delivery;
use App\Jobs\BroadcastDeliveryOfferJob;
use App\Order;
use App\Services\DispatchService;
use App\Services\FoodOrderStateMachineService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Support\Facades\Log;

/**
 * Job pour assigner automatiquement un livreur à une livraison
 */
class AutoAssignDeliveryJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 90;
    public $failOnTimeout = true;

    protected $delivery;

    /**
     * Create a new job instance.
     *
     * @param Delivery $delivery
     */
    public function __construct(Delivery $delivery)
    {
        $this->delivery = $delivery;
        $this->onConnection(config('module_queues.modules.food.connection', 'database_food'));
        $this->onQueue(config('module_queues.modules.food.queue', 'food'));
    }

    /**
     * Execute the job — délègue au broadcast offer model.
     */
    public function handle(DispatchService $dispatchService): void
    {
        $delivery = Delivery::find($this->delivery->id);

        if (!$delivery) {
            Log::warning('Livraison introuvable dans AutoAssignDeliveryJob', ['delivery_id' => $this->delivery->id]);
            return;
        }

        if ($delivery->status !== 'PENDING') {
            Log::info('Livraison déjà assignée, job ignoré', ['delivery_id' => $delivery->id, 'status' => $delivery->status]);
            return;
        }

        // Garde-fou : ne jamais dispatcher si la commande n'est pas encore en cuisine.
        // Protège contre tout chemin legacy ou régression qui enqueuerait ce job prématurément.
        $order = Order::find($delivery->order_id);
        $dispatchableStatuses = FoodOrderStateMachineService::DISPATCHABLE_BUSINESS_STATUSES;
        if (! $order || ! in_array($order->business_status, $dispatchableStatuses, true)) {
            Log::warning('AutoAssignDeliveryJob: order pas en in_kitchen/ready_for_pickup, job ignoré', [
                'delivery_id'     => $delivery->id,
                'order_id'        => $delivery->order_id,
                'business_status' => $order?->business_status,
            ]);
            return; // pas de retry — ce n'est pas transitoire
        }

        // Lancer le broadcast offer (round 1) — les livreurs ont OFFER_WINDOW secondes pour accepter
        BroadcastDeliveryOfferJob::dispatch($delivery->id, 1);

        Log::info('AutoAssignDeliveryJob: broadcast offre lancé', ['delivery_id' => $delivery->id]);
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

    public function middleware(): array
    {
        return [
            (new WithoutOverlapping("food:auto-assign-delivery:{$this->delivery->id}"))->expireAfter(180),
        ];
    }
}
