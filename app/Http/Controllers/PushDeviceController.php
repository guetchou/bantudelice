<?php

namespace App\Http\Controllers;

use App\Driver;
use App\Services\PushDeviceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class PushDeviceController extends BaseController
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'device_token' => ['required', 'string', 'max:500'],
            'platform' => ['nullable', 'string', 'max:32'],
            'locale' => ['nullable', 'string', 'max:10'],
            'site_key' => ['nullable', 'string', 'max:64'],
            'modules' => ['nullable', 'array'],
            'modules.*' => ['string', 'in:food,transport,colis'],
            'audio_enabled' => ['nullable', 'boolean'],
            'interactive_enabled' => ['nullable', 'boolean'],
            'realtime_enabled' => ['nullable', 'boolean'],
            'metadata' => ['nullable', 'array'],
        ]);

        $actor = $request->user() ?? Auth::user();

        if (!$actor) {
            throw ValidationException::withMessages([
                'auth' => 'Authentification requise pour enregistrer un appareil.',
            ]);
        }

        $service = app(PushDeviceService::class);
        $isDriver = ($actor instanceof Driver) || (($actor->type ?? null) === 'driver');

        if ($isDriver) {
            $token = $service->registerForDriver($actor, $data['device_token'], $data);
        } else {
            $token = $service->registerForUser($actor, $data['device_token'], $data);
        }

        return response()->json([
            'status' => true,
            'message' => 'Appareil enregistré pour les notifications push.',
            'token' => $token,
            'subscriptions' => $token->metadata['subscriptions'] ?? null,
        ], 201);
    }

    public function destroy(Request $request): JsonResponse
    {
        $data = $request->validate([
            'device_token' => ['required', 'string', 'max:500'],
        ]);

        $actor = $request->user() ?? Auth::user();

        if (!$actor) {
            throw ValidationException::withMessages([
                'auth' => 'Authentification requise pour désactiver un appareil.',
            ]);
        }

        $service = app(PushDeviceService::class);
        $isDriver = ($actor instanceof Driver) || (($actor->type ?? null) === 'driver');

        $updated = $isDriver
            ? $service->deactivateForDriver($actor, $data['device_token'])
            : $service->deactivateForUser($actor, $data['device_token']);

        return response()->json([
            'status' => (bool) $updated,
            'message' => $updated ? 'Appareil désactivé.' : 'Aucun appareil trouvé.',
        ]);
    }
}
