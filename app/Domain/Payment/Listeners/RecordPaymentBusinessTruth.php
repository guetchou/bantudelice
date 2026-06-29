<?php

namespace App\Domain\Payment\Listeners;

use App\Domain\Payment\Events\PaymentConfirmed;
use App\Services\PaymentBusinessService;
use Illuminate\Support\Facades\Log;

class RecordPaymentBusinessTruth
{
    public function __construct(
        private readonly PaymentBusinessService $business,
    ) {}

    /**
     * Retourner false arrête la propagation vers les listeners métier.
     * Le paiement reste encaissé, mais la commande, le transport ou le colis
     * ne sont pas libérés tant que l'affectation n'est pas fiable.
     */
    public function handle(PaymentConfirmed $event): ?bool
    {
        $result = $this->business->recordConfirmedPayment($event->payment);

        if ($result['release_target'] ?? false) {
            return null;
        }

        $payment = $event->payment->fresh();
        $case = $result['case'] ?? null;

        $payment->update([
            'meta' => array_merge($payment->meta ?? [], [
                'business_hold' => true,
                'business_hold_at' => now()->toIso8601String(),
                'business_hold_reason' => $result['allocation_status'] ?? 'unallocated',
                'business_case_id' => $case?->id,
            ]),
        ]);

        Log::critical('Paiement encaissé mais cible métier bloquée', [
            'payment_id' => $payment->id,
            'allocation_status' => $result['allocation_status'] ?? null,
            'case_id' => $case?->id,
        ]);

        return false;
    }
}
