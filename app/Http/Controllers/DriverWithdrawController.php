<?php

namespace App\Http\Controllers;

use App\Services\PartnerWithdrawalService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class DriverWithdrawController extends Controller
{
    public function store(Request $request, PartnerWithdrawalService $service)
    {
        $driver = auth()->user()->driver()->first();

        if (!$driver) {
            return response()->json(['success' => false, 'message' => 'Profil livreur introuvable.'], 404);
        }

        $validated = $request->validate([
            'amount' => 'required|integer|min:500|max:10000000',
        ]);

        // Phone always from authenticated driver profile
        $phone = $driver->phone;
        if (!$phone) {
            return response()->json([
                'success' => false,
                'message' => 'Aucun numéro de téléphone configuré sur votre profil. Contactez le support.',
            ], 422);
        }

        $idempotencyKey = $request->header('Idempotency-Key') ?: Str::uuid()->toString();

        $result = $service->initiate('driver', $driver->id, (int) $validated['amount'], $phone, $idempotencyKey);

        return response()->json($result, $result['http_status'] ?? 202);
    }
}
