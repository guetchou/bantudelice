<?php

namespace App\Http\Controllers\restaurant;

use App\Http\Controllers\Controller;
use App\Restaurant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * T1.1 — Toggle fermeture temporaire restaurant (E2C, météo, surcharge).
 * T1.2 — Auto-pause : le scheduler vérifie last_activity_at toutes les 5 min.
 */
class RestaurantAvailabilityController extends Controller
{
    const PAUSE_REASONS = [
        'e2c'        => 'Coupure électrique',
        'weather'    => 'Routes impraticables / pluie',
        'overloaded' => 'Trop de commandes en cours',
        'short_break'=> 'Pause courte',
        'manual'     => 'Fermeture manuelle',
        'other'      => 'Autre raison',
    ];

    /**
     * Affiche le statut de disponibilité du restaurant (JSON pour le dashboard).
     */
    public function status()
    {
        $restaurant = $this->resolveRestaurant();
        if (!$restaurant) {
            return response()->json(['status' => false], 404);
        }

        // Auto-réouverture si paused_until est passé
        if ($restaurant->is_paused && $restaurant->paused_until && now()->isAfter($restaurant->paused_until)) {
            $restaurant->update(['is_paused' => false, 'paused_until' => null, 'pause_reason' => null]);
            $restaurant->refresh();
        }

        return response()->json([
            'status'      => true,
            'is_paused'   => (bool) $restaurant->is_paused,
            'paused_until'=> $restaurant->paused_until?->toIso8601String(),
            'pause_reason'=> $restaurant->pause_reason,
            'pause_label' => self::PAUSE_REASONS[$restaurant->pause_reason] ?? null,
        ]);
    }

    /**
     * T1.1 — Pause manuelle avec durée optionnelle.
     * POST /restaurant/availability/pause
     * Body: reason (string), duration_minutes (int, optional)
     */
    public function pause(Request $request)
    {
        $request->validate([
            'reason'           => 'required|string|in:e2c,weather,overloaded,short_break,manual,other',
            'duration_minutes' => 'nullable|integer|min:5|max:480',
        ]);

        $restaurant = $this->resolveRestaurant();
        if (!$restaurant) {
            return back()->with('alert', ['type' => 'danger', 'message' => 'Restaurant introuvable.']);
        }

        $pausedUntil = $request->filled('duration_minutes')
            ? now()->addMinutes((int) $request->duration_minutes)
            : null;

        $restaurant->update([
            'is_paused'    => true,
            'paused_until' => $pausedUntil,
            'pause_reason' => $request->reason,
        ]);

        Log::info('Restaurant mis en pause', [
            'restaurant_id' => $restaurant->id,
            'reason'        => $request->reason,
            'until'         => $pausedUntil?->toIso8601String(),
        ]);

        $msg = 'Restaurant mis en pause — vous ne recevrez plus de nouvelles commandes.';
        if ($pausedUntil) {
            $msg .= ' Réouverture automatique à ' . $pausedUntil->format('H:i') . '.';
        }

        if ($request->expectsJson()) {
            return response()->json(['status' => true, 'message' => $msg, 'paused_until' => $pausedUntil?->toIso8601String()]);
        }

        return back()->with('alert', ['type' => 'warning', 'message' => $msg]);
    }

    /**
     * T1.1 — Reprise manuelle.
     * POST /restaurant/availability/resume
     */
    public function resume(Request $request)
    {
        $restaurant = $this->resolveRestaurant();
        if (!$restaurant) {
            return back()->with('alert', ['type' => 'danger', 'message' => 'Restaurant introuvable.']);
        }

        $restaurant->update([
            'is_paused'    => false,
            'paused_until' => null,
            'pause_reason' => null,
        ]);

        Log::info('Restaurant reprise activité', ['restaurant_id' => $restaurant->id]);

        if ($request->expectsJson()) {
            return response()->json(['status' => true, 'message' => 'Restaurant en ligne. Vous recevez à nouveau des commandes.']);
        }

        return back()->with('alert', ['type' => 'success', 'message' => 'Votre restaurant est de nouveau en ligne.']);
    }

    private function resolveRestaurant(): ?Restaurant
    {
        return Restaurant::where('user_id', auth()->id())->first();
    }
}
