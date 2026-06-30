<?php

namespace App\Domain\Finance\Services;

use App\Payment;

final class PaymentCollectionRouteResolver
{
    public function resolve(Payment $payment): string
    {
        $provider = strtolower(trim((string) $payment->provider));

        if ($provider === '') {
            throw new \InvalidArgumentException('The payment provider is missing.');
        }

        if ($this->isCash($provider)) {
            return 'cash';
        }

        if ($this->hasGePayReference($payment)
            && in_array($provider, ['momo', 'mtn', 'mtn_momo'], true)) {
            return 'gepay_mtn';
        }

        return $this->canonicalProvider($provider);
    }

    public function canonicalProvider(string $provider): string
    {
        $provider = strtolower(trim($provider));

        return match ($provider) {
            'momo', 'mtn', 'mtn_momo' => 'mtn_momo',
            'gepay_mtn' => 'gepay_mtn',
            'airtel', 'airtel_money' => 'airtel_money',
            'paypal' => 'paypal',
            'cash', 'cod', 'demo' => 'cash',
            default => throw new \InvalidArgumentException(
                'Unknown payment provider for financial mirror: ' . $provider
            ),
        };
    }

    public function isCash(string $provider): bool
    {
        return in_array(strtolower(trim($provider)), ['cash', 'cod', 'demo'], true);
    }

    private function hasGePayReference(Payment $payment): bool
    {
        return trim((string) data_get($payment->meta, 'gepay.reference', '')) !== ''
            || trim((string) data_get($payment->meta, 'gepay_reference', '')) !== '';
    }
}
