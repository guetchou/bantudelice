<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Payment;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    /**
     * Récupérer le statut d'un paiement
     * 
     * GET /api/payments/{payment}
     * 
     * @param Request $request
     * @param int|Payment $payment
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Request $request, $payment)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'Non authentifié'
            ], 401);
        }

        // Si $payment est un ID, récupérer le modèle
        if (!($payment instanceof Payment)) {
            $payment = Payment::where('id', $payment)
                ->where('user_id', $user->id)
                ->firstOrFail();
        } else {
            // Vérifier l'appartenance
            if ($payment->user_id !== $user->id) {
                return response()->json([
                    'status' => false,
                    'message' => 'Accès non autorisé à ce paiement'
                ], 403);
            }
        }

        return response()->json([
            'status' => true,
            'payment' => [
                'id' => $payment->id,
                'status' => $payment->status,
                'amount' => $payment->amount,
                'currency' => $payment->currency,
                'provider' => $payment->provider,
                'provider_reference' => $payment->provider_reference,
                'order_id' => $payment->order_id,
                'order_no' => $payment->order->order_no ?? null,
                'created_at' => $payment->created_at->toIso8601String(),
                'updated_at' => $payment->updated_at->toIso8601String(),
            ]
        ]);
    }

    /**
     * Confirmer manuellement un paiement (pour modes manuels)
     * 
     * POST /api/payments/{payment}/confirm
     * 
     * @param Request $request
     * @param int|Payment $payment
     * @return \Illuminate\Http\JsonResponse
     */
    public function confirm(Request $request, $payment)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'Non authentifié'
            ], 401);
        }

        // Si $payment est un ID, récupérer le modèle
        if (!($payment instanceof Payment)) {
            $payment = Payment::where('id', $payment)
                ->where('user_id', $user->id)
                ->firstOrFail();
        } else {
            if ($payment->user_id !== $user->id) {
                return response()->json([
                    'status' => false,
                    'message' => 'Accès non autorisé'
                ], 403);
            }
        }

        // Seuls les paiements PENDING peuvent être confirmés manuellement
        if ($payment->status !== 'PENDING') {
            return response()->json([
                'status' => false,
                'message' => 'Ce paiement ne peut pas être confirmé (statut: ' . $payment->status . ')'
            ], 422);
        }

        try {
            $paymentService = new \App\Services\PaymentService();
            $paymentService->markPaymentAsPaid($payment, ['manual_confirmation' => true]);

            return response()->json([
                'status' => true,
                'message' => 'Paiement confirmé avec succès',
                'payment' => [
                    'id' => $payment->id,
                    'status' => $payment->fresh()->status,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Erreur lors de la confirmation: ' . $e->getMessage()
            ], 500);
        }
    }
}

