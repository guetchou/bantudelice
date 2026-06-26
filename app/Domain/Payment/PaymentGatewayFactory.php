<?php

namespace App\Domain\Payment;

use App\Domain\Payment\Adapters\AirtelMoneyAdapter;
use App\Domain\Payment\Adapters\CashDemoAdapter;
use App\Domain\Payment\Adapters\MtnMomoAdapter;
use App\Domain\Payment\Adapters\PayPalAdapter;
use App\Domain\Payment\Contracts\PaymentGatewayAdapterInterface;
use App\Services\DisbursementService;

/**
 * Résout l'adapter approprié à partir de la valeur Payment::provider.
 *
 * La factory est le seul endroit où les adapters sont instanciés.
 * PaymentService n'importe aucun adapter directement.
 */
final class PaymentGatewayFactory
{
    public function __construct(
        private readonly MtnMomoAdapter      $mtn,
        private readonly AirtelMoneyAdapter $airtel,
        private readonly PayPalAdapter      $paypal,
        private readonly CashDemoAdapter    $cash,
    ) {}

    /**
     * Résoudre l'adapter pour un provider donné.
     *
     * Un provider inconnu est toujours une erreur. Un système financier ne doit
     * jamais transformer une faute de configuration en paiement cash simulé.
     */
    public function for(string $provider, bool $strict = true): PaymentGatewayAdapterInterface
    {
        $normalized = strtolower(trim($provider));

        $adapter = match ($normalized) {
            'momo', 'mtn_momo', 'mtn' => $this->mtn,
            'airtel', 'airtel_money'  => $this->airtel,
            'paypal'                  => $this->paypal,
            'cash', 'cod', 'demo'     => $this->cash,
            default                   => null,
        };

        if ($adapter === null) {
            throw new \InvalidArgumentException("Provider de paiement inconnu : {$provider}");
        }

        return $adapter;
    }

    /**
     * Vrai si le provider donné est connu et a un adapter dédié.
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
     */
    public function forMomoPhone(string $phone): PaymentGatewayAdapterInterface
    {
        $operator = DisbursementService::detectOperator($phone);

        return match ($operator) {
            'mtn'    => $this->mtn,
            'airtel' => $this->airtel,
            default  => throw new \InvalidArgumentException(
                'Impossible de déterminer l’opérateur Mobile Money pour ce numéro.'
            ),
        };
    }
}
