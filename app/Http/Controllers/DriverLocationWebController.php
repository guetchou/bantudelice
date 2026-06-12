<?php

namespace App\Http\Controllers;

use App\Driver;
use App\DriverLocation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Mise à jour GPS du livreur depuis l'interface web (pas l'app mobile).
 * Équivalent web de POST /api/driver/{id}/location (auth:driver_api).
 */
class DriverLocationWebController extends Controller
{
    public function update(Request $request)
    {
        $request->validate([
            'latitude'  => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'accuracy'  => 'nullable|numeric|min:0',
            'heading'   => 'nullable|numeric',
            'speed'     => 'nullable|numeric|min:0',
        ]);

        $user = auth()->user();
        if (!$user) {
            return response()->json(['status' => false], 401);
        }

        // Résoudre le driver depuis l'utilisateur connecté
        $driver = $this->resolveDriver($user);
        if (!$driver) {
            return response()->json(['status' => false, 'message' => 'Livreur introuvable'], 404);
        }

        // Mettre à jour la position dans drivers
        $driver->latitude  = $request->latitude;
        $driver->longitude = $request->longitude;
        $driver->status    = 'online';
        $driver->save();

        // Historique dans driver_locations
        try {
            if (true) {
                DriverLocation::create([
                    'driver_id' => $driver->id,
                    'latitude'  => $request->latitude,
                    'longitude' => $request->longitude,
                    'accuracy'  => $request->accuracy,
                    'heading'   => $request->heading,
                    'speed'     => $request->speed,
                    'timestamp' => now(),
                ]);
            }
        } catch (\Throwable $e) {
            Log::warning('Erreur historique GPS livreur web', ['driver_id' => $driver->id, 'error' => $e->getMessage()]);
        }

        return response()->json(['status' => true]);
    }

    private function resolveDriver($user): ?Driver
    {
        // S3.4 — si user_id est présent sur drivers, liaison directe
        $driver = Driver::where('user_id', $user->id)->first();
        if ($driver) return $driver;

        // Fallback : email ET téléphone doivent correspondre tous les deux
        // (l'orWhere permettait un IDOR par correspondance partielle).
        $driver = Driver::where('email', $user->email)
            ->where('phone', $user->phone)
            ->first();

        if ($driver && !$driver->user_id) {
            $driver->update(['user_id' => $user->id]);
        }

        return $driver;
    }
}
