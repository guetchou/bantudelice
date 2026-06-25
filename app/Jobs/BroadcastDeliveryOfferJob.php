<?php

namespace App\Jobs;

use App\Delivery;
use App\DeliveryOffer;
use App\Services\DispatchService;
use App\Services\FoodOrderStateMachineService;
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
 * Broadcast une offre de livraison aux meilleurs livreurs du rayon courant.
 * Le rayon est élargi à chaque round jusqu'au dernier palier configuré.
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
        if (! $order || ! in_array(
            $order->business_status,
            FoodOrderStateMachineService::DISPATCHABLE_BUSINESS_STATUSES,
            true
        )) {
            Log::warning('BroadcastDeliveryOffer: commande non dispatchable, job ignoré', [
                'delivery_id' => $this->deliveryId,
                'round' => $this->round,
                'business_status' => $order?->business_status,
            ]);
            return;
        }

        if ($this->round > $plan->maxRounds()) {
            Log::warning('BroadcastDeliveryOffer: paliers épuisés, assignation de dernier recours', [
                'delivery_id' => $this->deliveryId,
                'round' => $this->round,
            ]);
            $dispatchService->autoAssignResult($delivery);
            return;
        }

        $alreadyOffered = DeliveryOffer::where('delivery_id', $this->deliveryId)
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
                $dispatchService->autoAssignResult($delivery);
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
        $restName = $order->restaurant->name ?? 'Restaurant';

        try {
            NotificationService::sendToDriver(
                $driver->id,
                '🛵 Nouvelle mission disponible',
                "Commande #{$orderNo} — {$restName}. Acceptez dans {$offerWindow}s.",
                'newDeliveryOffer',
                $driver->id,
                'driver',
                [
                    'module' => 'food',
                    'order_no' => $orderNo,
                    'delivery_id' => $delivery->id,
                    'expires_at' => $expiresAt->toIso8601String(),
                    'accept_url' => url('/driver/deliveries/' . $delivery->id . '/offer/accept'),
                    'decline_url' => url('/driver/deliveries/' . $delivery->id . '/offer/decline'),
                    'audio_cue' => 'driver_order_assignment',
                    'sound_key' => 'food_driver_mission',
                ]
            );
        } catch (\Throwable $e) {
            Log::warning('BroadcastDeliveryOffer: notification driver échouée', [
                'driver_id' => $driver->id,
                'error' => $e->getMessage(),
            ]);
        }

        try {
            if ($driver->email ?? false) {
                $acceptUrl = url('/driver/deliveries/' . $delivery->id . '/offer/accept');
                $declineUrl = url('/driver/deliveries/' . $delivery->id . '/offer/decline');
                \Illuminate\Support\Facades\Mail::send([], [], function ($message) use ($driver, $orderNo, $restName, $acceptUrl, $declineUrl, $offerWindow) {
                    $message->to($driver->email, $driver->name ?? 'Livreur')
                        ->subject("Nouvelle mission #{$orderNo} — BantuDelice")
                        ->html(
                            "<div style='font-family:sans-serif;max-width:520px;margin:0 auto;padding:24px;'>"
                            . "<h2 style='color:#009543;'>Nouvelle mission disponible</h2>"
                            . "<p>Commande <strong>#{$orderNo}</strong> depuis <strong>{$restName}</strong>.</p>"
                            . "<p>Vous avez <strong>{$offerWindow} secondes</strong> pour accepter.</p>"
                            . "<p><a href='{$acceptUrl}'>Accepter</a> · <a href='{$declineUrl}'>Refuser</a></p>"
                            . "</div>"
                        );
                });
            }
        } catch (\Throwable $e) {
            Log::warning('BroadcastDeliveryOffer: email driver échoué', ['driver_id' => $driver->id]);
        }
    }

    public function middleware(): array
    {
        return [
            (new WithoutOverlapping("food:broadcast-offer-delivery:{$this->deliveryId}:{$this->round}"))->expireAfter(120),
        ];
    }
}
