<?php

namespace App\Http\Controllers;

use App\Services\DriverLocationIngestionService;
use App\Support\Auth\AuthenticatedDriverResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Mise à jour GPS du livreur depuis l'interface web.
 */
class DriverLocationWebController extends Controller
{
    public function __construct(
        private DriverLocationIngestionService $driverLocations,
        private AuthenticatedDriverResolver $driverResolver
    ) {
        $this->middleware('auth');
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

        $driver = $this->driverResolver->current();
        if (! $driver) {
            return response()->json([
                'status' => false,
                'message' => 'Compte livreur non autorisé.',
            ], 403);
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
}
