<?php

namespace App\Http\Controllers\restaurant;

use App\Http\Controllers\Controller;
use App\Services\PartnerWithdrawalService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class WithdrawController extends Controller
{
    public function store(Request $request, PartnerWithdrawalService $service)
    {
        $restaurant = auth()->user()->restaurant()->first();

        if (!$restaurant) {
            return response()->json(['success' => false, 'message' => 'Restaurant introuvable.'], 404);
        }

        $validated = $request->validate([
            'amount' => 'required|integer|min:500|max:10000000',
        ]);

        // Phone always from authenticated restaurant profile — never from request body
        $phone = $restaurant->phone;
        if (!$phone) {
            return response()->json([
                'success' => false,
                'message' => 'Aucun numéro de téléphone configuré sur votre profil. Contactez le support.',
            ], 422);
        }

        $idempotencyKey = $request->header('Idempotency-Key') ?: Str::uuid()->toString();

        $result = $service->initiate('restaurant', $restaurant->id, (int) $validated['amount'], $phone, $idempotencyKey);

        return response()->json($result, $result['http_status'] ?? 202);
    }
}
