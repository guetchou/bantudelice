<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Payment;
use App\Services\PaymentExperienceService;
use App\Services\PaymentReconciliationService;
use App\Services\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    public function __construct(
        protected PaymentExperienceService $paymentExperienceService
    ) {}

    /**
     * Récupérer le statut d'un paiement.
     */
    public function show(Request $request, $payment)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'Non authentifié',
            ], 401);
        }

        $payment = $this->resolveOwnedPayment($payment, $user->id);

        if (!$payment) {
            return response()->json([
                'status' => false,
                'message' => 'Paiement introuvable',
            ], 404);
        }

        $reconciliation = null;

        if ($payment->status === 'PENDING') {
            try {
                $reconciliation = app(PaymentReconciliationService::class)->reconcile($payment);
            } catch (\Throwable $e) {
                Log::warning('Erreur de réconciliation pendant le polling paiement', [
                    'payment_id' => $payment->id,
                    'provider' => $payment->provider,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $payment = Payment::with('order')->findOrFail($payment->id);

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
            ],
            'payment_experience' => $this->paymentExperienceService->describe($payment),
            'reconciliation' => $reconciliation,
        ]);
    }

    /**
     * Confirmation manuelle réservée aux paiements réellement manuels.
     *
     * Cette route appartient à l'espace client et ne doit jamais pouvoir
     * confirmer un paiement MTN, Airtel, PayPal ou tout autre PSP externe.
     */
    public function confirm(Request $request, $payment)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'Non authentifié',
            ], 401);
        }

        $payment = $this->resolveOwnedPayment($payment, $user->id);

        if (!$payment) {
            return response()->json([
                'status' => false,
                'message' => 'Paiement introuvable',
            ], 404);
        }

        $provider = strtolower(trim((string) $payment->provider));

        if (!in_array($provider, ['cash', 'cod'], true)) {
            Log::warning('Tentative de confirmation manuelle d’un paiement externe bloquée', [
                'payment_id' => $payment->id,
                'provider' => $payment->provider,
                'user_id' => $user->id,
                'ip' => $request->ip(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Un paiement externe doit être confirmé directement auprès du fournisseur de paiement.',
            ], 403);
        }

        if ($payment->status !== 'PENDING') {
            return response()->json([
                'status' => false,
                'message' => 'Ce paiement ne peut pas être confirmé (statut: ' . $payment->status . ')',
            ], 422);
        }

        try {
            app(PaymentService::class)->markPaymentAsPaid($payment, [
                'manual_confirmation' => true,
                'confirmed_by_user_id' => $user->id,
                'confirmed_at' => now()->toIso8601String(),
            ]);

            return response()->json([
                'status' => true,
                'message' => 'Paiement confirmé avec succès',
                'payment' => [
                    'id' => $payment->id,
                    'status' => $payment->fresh()->status,
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('Erreur lors de la confirmation manuelle d’un paiement', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Erreur lors de la confirmation du paiement.',
            ], 500);
        }
    }

    private function resolveOwnedPayment($payment, int $userId): ?Payment
    {
        if ($payment instanceof Payment) {
            return $payment->user_id === $userId ? $payment : null;
        }

        return Payment::where('id', $payment)
            ->where('user_id', $userId)
            ->first();
    }
}
