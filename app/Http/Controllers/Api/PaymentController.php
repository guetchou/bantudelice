<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Payment;
use App\Services\PaymentExperienceService;
use App\Services\PaymentReconciliationService;
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
     * Cette route est conservée pour compatibilité, mais aucune confirmation
     * financière ne peut venir du client. Les PSP sont vérifiés auprès du
     * fournisseur ; le cash est confirmé par le livreur ou le back-office.
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

        Log::warning('Tentative de confirmation manuelle client bloquée', [
            'payment_id' => $payment->id,
            'provider' => $payment->provider,
            'user_id' => $user->id,
            'ip' => $request->ip(),
        ]);

        return response()->json([
            'status' => false,
            'message' => 'La confirmation d’un paiement est réservée au fournisseur de paiement, au livreur ou au back-office.',
        ], 403);
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
