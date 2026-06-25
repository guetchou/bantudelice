<?php

namespace App\Http\Controllers;

use App\Driver;
use App\Services\DriverLocationIngestionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Mise à jour GPS du livreur depuis l'interface web.
 */
class DriverLocationWebController extends Controller
{
    public function __construct(
        private DriverLocationIngestionService $driverLocations
    ) {
    }

    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'accuracy' => ['nullable', 'numeric', 'between:0,5000'],
            'heading' => ['nullable', 'numeric', 'between:0,360'],
            'speed' => ['nullable', 'numeric', 'between:0,100'],
            'recorded_at' => ['nullable', 'date'],
        ]);

        $user = auth()->user();
        if (! $user) {
            return response()->json(['status' => false, 'message' => 'Non authentifié'], 401);
        }

        $driver = $this->resolveDriver($user);
        if (! $driver) {
            return response()->json(['status' => false, 'message' => 'Livreur introuvable'], 404);
        }

        $result = $this->driverLocations->ingest(
            $driver,
            $validated,
            markOnline: true,
            broadcast: true
        );

        return response()->json([
            'status' => true,
            'accepted' => $result['accepted'],
            'duplicate' => $result['duplicate'],
            'stale' => $result['stale'],
            'recorded_at' => optional($result['location']?->timestamp)->toIso8601String(),
        ], $result['stale'] ? 202 : 200);
    }

    private function resolveDriver($user): ?Driver
    {
        $driver = Driver::where('user_id', $user->id)->first();
        if ($driver) {
            return $driver;
        }

        if (! $user->email || ! $user->phone) {
            return null;
        }

        $driver = Driver::where('email', $user->email)
            ->where('phone', $user->phone)
            ->first();

        if ($driver && ! $driver->user_id) {
            $driver->forceFill(['user_id' => $user->id])->save();
        }

        return $driver;
    }
}
