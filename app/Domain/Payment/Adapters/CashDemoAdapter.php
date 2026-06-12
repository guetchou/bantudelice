<?php

namespace App\Domain\Payment\Adapters;

use App\Domain\Payment\Contracts\PaymentGatewayAdapterInterface;
use App\Domain\Payment\ValueObjects\GatewayResult;
use App\Domain\Payment\ValueObjects\GatewayStatus;
use App\Payment;

/**
 * Adapter pour les paiements cash et les providers non-implémentés (mode démo).
 * Aucun appel HTTP — la confirmation est manuelle via PaymentController::confirm().
 */
final class CashDemoAdapter implements PaymentGatewayAdapterInterface
{
    public function provider(): string
    {
        return 'cash';
    }

    public function initiate(Payment $payment, array $context): GatewayResult
    {
        $provider  = $payment->provider ?? 'cash';
        $reference = 'DEMO-' . strtoupper($provider) . '-' . $payment->id . '-' . time();

        return GatewayResult::demo($reference, [
            'provider' => $provider,
            'note'     => 'Paiement en attente de confirmation manuelle',
        ]);
    }

    public function checkStatus(string $providerReference): GatewayStatus
    {
        // Cash/démo n'a pas de PSP — statut toujours PENDING jusqu'à confirmation manuelle.
        return GatewayStatus::pending('MANUAL_PENDING');
    }

    public function handleCallback(array $payload): GatewayStatus
    {
        // Aucun webhook attendu pour cash/démo.
        return GatewayStatus::pending('NO_CALLBACK');
    }

    public function verifySignature(array $payload): bool
    {
        return true;
    }
}
