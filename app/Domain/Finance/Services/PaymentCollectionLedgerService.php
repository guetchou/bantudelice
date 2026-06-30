<?php

namespace App\Domain\Finance\Services;

use App\Domain\Finance\Data\CollectedPayment;

final class PaymentCollectionLedgerService
{
    public function __construct(
        private readonly LedgerPostingService $postings,
        private readonly PaymentClearingAccountService $accounts,
    ) {
    }

    public function record(CollectedPayment $payment): array
    {
        $providerAccount = $this->accounts->providerCollections($payment->provider);
        $clearingAccount = $this->accounts->paymentClearing();

        return $this->postings->postBatch(
            'payment_collection_received',
            'payment:' . $payment->paymentId . ':collection-received:v1',
            [
                [
                    'account' => $providerAccount,
                    'direction' => 'debit',
                    'amount' => $payment->amount,
                    'currency' => strtoupper($payment->currency),
                    'description' => 'Confirmed provider collection',
                ],
                [
                    'account' => $clearingAccount,
                    'direction' => 'credit',
                    'amount' => $payment->amount,
                    'currency' => strtoupper($payment->currency),
                    'description' => 'Customer funds pending business allocation',
                ],
            ],
            [
                'source_type' => 'payment',
                'source_id' => $payment->paymentId,
                'metadata' => array_replace($payment->metadata, [
                    'provider' => strtolower($payment->provider),
                    'provider_reference' => $payment->providerReference,
                    'allocation_status' => 'pending',
                ]),
            ]
        );
    }
}
