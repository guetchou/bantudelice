<?php

namespace App\Console\Commands;

use App\Restaurant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * T1.2 — Auto-pause des restaurants inactifs.
 * Si un restaurant n'a accepté/refusé aucune commande depuis INACTIVITY_MINUTES,
 * il est automatiquement mis en pause avec reason='auto_inactive'.
 * Utile pour détecter les coupures d'électricité (E2C) sans action manuelle.
 */
class AutoPauseInactiveRestaurants extends Command
{
    protected $signature   = 'restaurants:auto-pause {--minutes=20 : Inactivité max avant pause auto}';
    protected $description = 'Mettre en pause les restaurants sans activité (E2C Brazzaville)';

    // Délai avant réouverture auto après une pause automatique (minutes)
    const AUTO_RESUME_MINUTES = 60;

    public function handle(): int
    {
        $threshold  = (int) $this->option('minutes');
        $cutoff     = now()->subMinutes($threshold);

        // Restaurants actifs (non en pause) sans activité récente
        $candidates = Restaurant::where('is_paused', false)
            ->where('approved', true)
            ->where(function ($q) use ($cutoff) {
                $q->whereNotNull('last_activity_at')
                  ->where('last_activity_at', '<', $cutoff);
            })
            ->get();

        $paused = 0;
        foreach ($candidates as $restaurant) {
            // Vérifier s'il y a des commandes en attente actives
            $hasPendingOrders = \App\Order::where('restaurant_id', $restaurant->id)
                ->whereIn('business_status', ['pending_restaurant_acceptance', 'accepted', 'in_kitchen'])
                ->where('created_at', '>', now()->subHours(3))
                ->exists();

            if ($hasPendingOrders) {
                // Commandes actives → ne pas mettre en pause, juste logger
                continue;
            }

            $restaurant->update([
                'is_paused'    => true,
                'paused_until' => now()->addMinutes(self::AUTO_RESUME_MINUTES),
                'pause_reason' => 'auto_inactive',
            ]);

            Log::info('AutoPause: restaurant mis en pause automatiquement', [
                'restaurant_id'   => $restaurant->id,
                'name'            => $restaurant->name,
                'last_activity'   => $restaurant->last_activity_at?->toIso8601String(),
                'auto_resume_at'  => now()->addMinutes(self::AUTO_RESUME_MINUTES)->toIso8601String(),
            ]);

            $paused++;
        }

        // Réouvrir automatiquement les pauses expirées
        $resumed = Restaurant::where('is_paused', true)
            ->whereNotNull('paused_until')
            ->where('paused_until', '<=', now())
            ->update(['is_paused' => false, 'paused_until' => null, 'pause_reason' => null]);

        $this->info("Auto-pause: {$paused} mis en pause, {$resumed} réouverts.");

        return Command::SUCCESS;
    }
}
