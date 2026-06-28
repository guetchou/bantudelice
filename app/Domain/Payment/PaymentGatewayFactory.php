<?php

namespace App\Domain\Payment;

use App\Domain\Payment\Adapters\AirtelMoneyAdapter;
use App\Domain\Payment\Adapters\CashDemoAdapter;
use App\Domain\Payment\Adapters\GePayAdapter;
use App\Domain\Payment\Adapters\MtnMomoAdapter;
use App\Domain\Payment\Adapters\PayPalAdapter;
use App\Domain\Payment\Contracts\PaymentGatewayAdapterInterface;
use App\Services\DisbursementService;
use Illuminate\Support\Facades\Log;

/**
 * Résout l'adapter approprié à partir de la valeur Payment::provider.
 *
 * La factory est le seul endroit où les adapters sont instanciés.
 * PaymentService n'importe aucun adapter directement.
 */
final class PaymentGatewayFactory
{
    public function __construct(
        private readonly MtnMomoAdapter     $mtn,
        private readonly AirtelMoneyAdapter $airtel,
        private readonly PayPalAdapter      $paypal,
        private readonly CashDemoAdapter    $cash,
        private readonly GePayAdapter       $gepay,
    ) {}

    /**
     * Résoudre l'adapter pour un provider donné.
     *
     * La valeur $provider correspond à Payment::provider stocké en base
     * (ex: 'momo', 'airtel', 'paypal', 'cash').
     *
     * L'opérateur MoMo (mtn|airtel) est détecté à l'initiation via
     * DisbursementService::detectOperator() et non ici — la factory
     * résout le provider de haut niveau, pas l'opérateur réseau.
     *
     * Quand le provider est 'momo', on retourne l'adapter MTN ;
     * DisbursementService détectera l'opérateur (06→MTN, 05→Airtel).
     *
     * @throws \InvalidArgumentException si le provider est inconnu et $strict = true.
     */
    public function for(string $provider, bool $strict = false): PaymentGatewayAdapterInterface
    {
        $normalized = strtolower(trim($provider));

        if (in_array($normalized, ['momo', 'mtn_momo', 'mtn'], true)
            && config('gepay.bantudelice.collections_enabled', false)) {
            return $this->gepay;
        }

        $adapter = match ($normalized) {
            'momo', 'mtn_momo', 'mtn' => $this->mtn,
            'airtel', 'airtel_money'  => $this->airtel,
            'paypal'                  => $this->paypal,
            'cash', 'cod', 'demo'     => $this->cash,
            default                   => null,
        };

        if ($adapter === null) {
            Log::warning('PaymentGatewayFactory : provider inconnu, fallback CashDemoAdapter', [
                'provider' => $provider,
            ]);

            if ($strict) {
                throw new \InvalidArgumentException("Provider de paiement inconnu : {$provider}");
            }

            return $this->cash;
        }

        return $adapter;
    }

    /**
     * Vrai si le provider donné est connu et a un adapter dédié (non fallback).
     */
    public function supports(string $provider): bool
    {
        $normalized = strtolower(trim($provider));

        return in_array($normalized, [
            'momo', 'mtn_momo', 'mtn',
            'airtel', 'airtel_money',
            'paypal',
            'cash', 'cod', 'demo',
        ], true);
    }

    /**
     * Retourne l'adapter MoMo en tenant compte du numéro de téléphone.
     * Utile quand on veut router après détection de l'opérateur.
     */
    public function forMomoPhone(string $phone): PaymentGatewayAdapterInterface
    {
        $operator = DisbursementService::detectOperator($phone);

        return match ($operator) {
            'airtel' => $this->airtel,
            default  => $this->mtn,
        };
    }
}
