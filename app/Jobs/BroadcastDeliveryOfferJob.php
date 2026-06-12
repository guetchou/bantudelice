<?php

namespace App\Jobs;

use App\Delivery;
use App\DeliveryOffer;
use App\Services\DispatchService;
use App\Services\NotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Support\Facades\Log;

/**
 * Broadcast une offre de livraison aux N meilleurs livreurs disponibles.
 * Le premier qui accepte dans OFFER_WINDOW secondes reçoit la mission.
 * Si personne n'accepte → ExpireDeliveryOfferJob tente le batch suivant.
 */
class BroadcastDeliveryOfferJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    const OFFER_WINDOW   = 45;  // secondes pour accepter
    const BATCH_SIZE     = 3;   // livreurs notifiés simultanément
    const MAX_ROUNDS     = 4;   // rounds max avant abandon

    public $timeout = 30;
    public $tries   = 1;

    public function __construct(
        protected int $deliveryId,
        protected int $round = 1
    ) {
        $this->onConnection(config('module_queues.modules.food.connection', 'database_food'));
        $this->onQueue(config('module_queues.modules.food.queue', 'food'));
    }

    public function handle(DispatchService $dispatchService): void
    {
        $delivery = Delivery::with(['order.restaurant'])->find($this->deliveryId);

        if (!$delivery || $delivery->status !== 'PENDING') {
            return;
        }

        if ($this->round > self::MAX_ROUNDS) {
            Log::warning('BroadcastDeliveryOffer: max rounds atteint — assignation forcée', [
                'delivery_id' => $this->deliveryId,
            ]);
            // Dernier recours : assignation directe sans confirmation
            $dispatchService->autoAssignResult($delivery);
            return;
        }

        // Livreurs déjà sollicités pour cette livraison
        $alreadyOffered = DeliveryOffer::where('delivery_id', $this->deliveryId)
            ->pluck('driver_id')
            ->toArray();

        // Obtenir les N meilleurs livreurs non encore sollicités
        $candidates = $dispatchService->findTopDrivers($delivery, self::BATCH_SIZE, $alreadyOffered);

        if ($candidates->isEmpty()) {
            Log::info('BroadcastDeliveryOffer: aucun nouveau livreur disponible', [
                'delivery_id' => $this->deliveryId,
                'round'       => $this->round,
            ]);
            // Réessayer dans 60s si un livreur se connecte
            self::dispatch($this->deliveryId, $this->round)->delay(now()->addSeconds(60));
            return;
        }

        $expiresAt = now()->addSeconds(self::OFFER_WINDOW);

        foreach ($candidates as $rank => $candidate) {
            DeliveryOffer::create([
                'delivery_id'  => $this->deliveryId,
                'driver_id'    => $candidate['driver']->id,
                'status'       => 'pending',
                'offer_rank'   => ($this->round - 1) * self::BATCH_SIZE + $rank + 1,
                'driver_score' => $candidate['score'],
                'distance_km'  => $candidate['distance_km'] ?? null,
                'expires_at'   => $expiresAt,
            ]);

            // Notification push + email au livreur
            $this->notifyDriver($candidate['driver'], $delivery, $expiresAt);
        }

        Log::info('BroadcastDeliveryOffer: offres envoyées', [
            'delivery_id'  => $this->deliveryId,
            'round'        => $this->round,
            'driver_count' => $candidates->count(),
            'expires_at'   => $expiresAt->toIso8601String(),
        ]);

        // Planifier l'expiration dans OFFER_WINDOW secondes
        ExpireDeliveryOfferJob::dispatch($this->deliveryId, $this->round)
            ->delay($expiresAt);
    }

    protected function notifyDriver($driver, Delivery $delivery, $expiresAt): void
    {
        $order    = $delivery->order;
        $orderNo  = $order->order_no ?? '—';
        $restName = $order->restaurant->name ?? 'Restaurant';

        try {
            NotificationService::sendToDriver(
                $driver->id,
                '🛵 Nouvelle mission disponible',
                "Commande #$orderNo — $restName. Acceptez dans " . self::OFFER_WINDOW . "s.",
                'newDeliveryOffer',
                $driver->id,
                'driver',
                [
                    'module'      => 'food',
                    'order_no'    => $orderNo,
                    'delivery_id' => $delivery->id,
                    'expires_at'  => $expiresAt->toIso8601String(),
                    'accept_url'  => url('/driver/deliveries/' . $delivery->id . '/offer/accept'),
                    'decline_url' => url('/driver/deliveries/' . $delivery->id . '/offer/decline'),
                    'audio_cue'   => 'driver_order_assignment',
                    'sound_key'   => 'food_driver_mission',
                ]
            );
        } catch (\Throwable $e) {
            Log::warning('BroadcastDeliveryOffer: notification driver échouée', [
                'driver_id' => $driver->id,
                'error'     => $e->getMessage(),
            ]);
        }

        // Email fallback si pas d'app mobile
        try {
            if ($driver->email ?? false) {
                $acceptUrl  = url('/driver/deliveries/' . $delivery->id . '/offer/accept');
                $declineUrl = url('/driver/deliveries/' . $delivery->id . '/offer/decline');
                \Illuminate\Support\Facades\Mail::send([], [], function ($m) use ($driver, $orderNo, $restName, $acceptUrl, $declineUrl) {
                    $m->to($driver->email, $driver->name ?? 'Livreur')
                      ->subject("🛵 Nouvelle mission #$orderNo — BantuDelice")
                      ->html(
                        "<div style='font-family:sans-serif;max-width:520px;margin:0 auto;padding:24px;'>"
                        . "<img src='" . url('frontend/images/BuntuDelice.png') . "' alt='BantuDelice' style='height:36px;margin-bottom:16px;'>"
                        . "<h2 style='color:#009543;'>Nouvelle mission disponible</h2>"
                        . "<p>Commande <strong>#$orderNo</strong> depuis <strong>$restName</strong>.</p>"
                        . "<p>Vous avez <strong>" . self::OFFER_WINDOW . " secondes</strong> pour accepter.</p>"
                        . "<div style='margin:20px 0;display:flex;gap:12px;'>"
                        . "<a href='$acceptUrl' style='background:#009543;color:#fff;padding:12px 24px;border-radius:99px;text-decoration:none;font-weight:700;'>✓ Accepter</a>"
                        . "<a href='$declineUrl' style='background:#f8fafc;color:#64748b;padding:12px 24px;border-radius:99px;text-decoration:none;font-weight:700;border:1px solid #e2e8f0;'>Refuser</a>"
                        . "</div>"
                        . "<p style='color:#94a3b8;font-size:12px;'>BantuDelice — Brazzaville &amp; Pointe-Noire</p>"
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
