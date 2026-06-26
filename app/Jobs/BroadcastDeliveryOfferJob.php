<?php

namespace App\Jobs;

use App\Delivery;
use App\DeliveryOffer;
use App\Services\CommerceSignalService;
use App\Services\DispatchService;
use App\Services\NotificationService;
use App\Services\ProgressiveDispatchPlan;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Offre une mission aux meilleurs livreurs. Aucune assignation n'est imposée.
 */
class BroadcastDeliveryOfferJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public const OFFER_WINDOW = 45;
    public const BATCH_SIZE = 3;
    public const MAX_ROUNDS = 4;

    public $timeout = 30;
    public $tries = 1;

    public function __construct(
        protected int $deliveryId,
        protected int $round = 1
    ) {
        $this->onConnection(config('module_queues.modules.food.connection', 'database_food'));
        $this->onQueue(config('module_queues.modules.food.queue', 'food'));
    }

    public function handle(DispatchService $dispatchService, ProgressiveDispatchPlan $plan): void
    {
        $delivery = Delivery::with(['order.restaurant'])->find($this->deliveryId);

        if (! $delivery || $delivery->status !== 'PENDING') {
            return;
        }

        $order = $delivery->order;
        if (! $order || $order->business_status !== 'ready_for_pickup') {
            Log::warning('BroadcastDeliveryOffer: commande non prête, job ignoré', [
                'delivery_id' => $this->deliveryId,
                'round' => $this->round,
                'business_status' => $order?->business_status,
            ]);
            return;
        }

        if ($this->round > $plan->maxRounds()) {
            $this->scheduleConsentRetry($delivery);
            return;
        }

        $cooldownMinutes = max(5, (int) config('food.dispatch.reoffer_cooldown_minutes', 10));
        $alreadyOffered = DeliveryOffer::where('delivery_id', $this->deliveryId)
            ->where('created_at', '>=', now()->subMinutes($cooldownMinutes))
            ->pluck('driver_id')
            ->toArray();

        $rankedCandidates = $dispatchService->findTopDrivers(
            $delivery,
            $plan->candidatePoolSize(),
            $alreadyOffered
        );
        $candidates = $plan->selectCandidates($rankedCandidates, $this->round);
        $radiusKm = $plan->radiusForRound($this->round);

        if ($candidates->isEmpty()) {
            Log::info('BroadcastDeliveryOffer: aucun candidat dans le rayon courant', [
                'delivery_id' => $this->deliveryId,
                'round' => $this->round,
                'radius_km' => $radiusKm,
            ]);

            if ($this->round >= $plan->maxRounds()) {
                $this->scheduleConsentRetry($delivery);
                return;
            }

            self::dispatch($this->deliveryId, $this->round + 1)
                ->delay(now()->addSeconds($plan->noCandidateDelaySeconds()));
            return;
        }

        $offerWindow = $plan->offerWindowSeconds();
        $expiresAt = now()->addSeconds($offerWindow);

        foreach ($candidates as $rank => $candidate) {
            DeliveryOffer::create([
                'delivery_id' => $this->deliveryId,
                'driver_id' => $candidate['driver']->id,
                'status' => 'pending',
                'offer_rank' => ($this->round - 1) * $plan->batchSize() + $rank + 1,
                'driver_score' => $candidate['score'],
                'distance_km' => $candidate['distance_km'] ?? null,
                'expires_at' => $expiresAt,
            ]);

            $this->notifyDriver($candidate['driver'], $delivery, $expiresAt, $offerWindow);
        }

        Log::info('BroadcastDeliveryOffer: offres envoyées', [
            'delivery_id' => $this->deliveryId,
            'round' => $this->round,
            'radius_km' => $radiusKm,
            'driver_count' => $candidates->count(),
            'expires_at' => $expiresAt->toIso8601String(),
        ]);

        ExpireDeliveryOfferJob::dispatch($this->deliveryId, $this->round)
            ->delay($expiresAt);
    }

    protected function notifyDriver($driver, Delivery $delivery, $expiresAt, int $offerWindow): void
    {
        $order = $delivery->order;
        $orderNo = $order->order_no ?? '—';
        $restaurantName = $order->restaurant->name ?? 'Restaurant';
        $portalUrl = url('/driver/deliveries');

        try {
            NotificationService::sendToDriver(
                $driver->id,
                'Nouvelle mission disponible',
                "Commande #{$orderNo} — {$restaurantName}. Acceptez dans {$offerWindow}s.",
                [
                    'key' => 'newDeliveryOffer',
                    'module' => 'food',
                    'order_no' => $orderNo,
                    'delivery_id' => $delivery->id,
                    'expires_at' => $expiresAt->toIso8601String(),
                    'route_path' => '/driver/deliveries',
                    'deep_link' => 'bantudelice://food/delivery-offers/' . $delivery->id,
                    'audio_cue' => 'driver_order_assignment',
                    'sound_key' => 'food_driver_mission',
                ]
            );
        } catch (\Throwable $e) {
            Log::warning('BroadcastDeliveryOffer: notification livreur échouée', [
                'driver_id' => $driver->id,
                'error' => $e->getMessage(),
            ]);
        }

        try {
            if ($driver->email ?? false) {
                \Illuminate\Support\Facades\Mail::send([], [], function ($message) use ($driver, $orderNo, $restaurantName, $portalUrl, $offerWindow) {
                    $message->to($driver->email, $driver->name ?? 'Livreur')
                        ->subject("Nouvelle mission #{$orderNo} — BantuDelice")
                        ->html(
                            "<div style='font-family:sans-serif;max-width:520px;margin:0 auto;padding:24px;'>"
                            . "<h2 style='color:#009543;'>Nouvelle mission disponible</h2>"
                            . "<p>Commande <strong>#{$orderNo}</strong> depuis <strong>{$restaurantName}</strong>.</p>"
                            . "<p>Vous avez <strong>{$offerWindow} secondes</strong> pour répondre depuis votre espace sécurisé.</p>"
                            . "<p><a href='{$portalUrl}'>Ouvrir l’espace livreur</a></p>"
                            . '</div>'
                        );
                });
            }
        } catch (\Throwable $e) {
            Log::warning('BroadcastDeliveryOffer: e-mail livreur échoué', [
                'driver_id' => $driver->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function scheduleConsentRetry(Delivery $delivery): void
    {
        $retrySeconds = max(120, (int) config('food.dispatch.exhausted_retry_seconds', 300));

        Log::warning('BroadcastDeliveryOffer: paliers épuisés, nouvelle recherche planifiée sans assignation forcée', [
            'delivery_id' => $delivery->id,
            'round' => $this->round,
            'retry_seconds' => $retrySeconds,
        ]);

        app(CommerceSignalService::class)->emitDelivery($delivery, 'delivery.dispatch_exhausted', [
            'module' => 'food',
            'severity' => 'warning',
            'technical_status' => 'driver_timeout',
            'retry_seconds' => $retrySeconds,
        ]);

        self::dispatch($delivery->id, 1)->delay(now()->addSeconds($retrySeconds));
    }

    public function middleware(): array
    {
        return [
            (new WithoutOverlapping("food:broadcast-offer-delivery:{$this->deliveryId}:{$this->round}"))
                ->expireAfter(120),
        ];
    }
}
