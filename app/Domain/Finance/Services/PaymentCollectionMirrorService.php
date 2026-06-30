<?php

namespace App\Domain\Finance\Services;

use App\Domain\Finance\Contracts\FinancialLedgerGateway;
use App\Domain\Finance\Data\CollectedPayment;
use App\Domain\Finance\Models\FinancialMirrorEvent;
use App\Payment;
use Illuminate\Support\Str;

final class PaymentCollectionMirrorService
{
    public function __construct(
        private readonly FinancialLedgerGateway $ledger,
    ) {
    }

    public function mirror(Payment $payment, bool $force = false): ?FinancialMirrorEvent
    {
        if (! $force && ! config('financial-mirror.collections_enabled', false)) {
            return null;
        }

        $event = FinancialMirrorEvent::firstOrCreate(
            ['event_key' => 'payment:' . $payment->id . ':collection-received:v1'],
            [
                'uuid' => (string) Str::uuid(),
                'event_type' => 'payment_collection_received',
                'source_type' => 'payment',
                'source_id' => (int) $payment->id,
                'status' => 'pending',
                'attempts' => 0,
                'payload' => $this->snapshot($payment),
            ]
        );

        if ($event->status === 'posted') {
            return $event;
        }

        $event->increment('attempts');
        $event->update([
            'status' => 'processing',
            'payload' => $this->snapshot($payment),
            'last_error' => null,
            'failed_at' => null,
        ]);

        try {
            $this->assertPaid($payment);

            if ($this->isCashProvider((string) $payment->provider)) {
                $event->update([
                    'status' => 'skipped',
                    'last_error' => 'Cash collections require the dedicated cash collection workflow.',
                    'processed_at' => now(),
                ]);

                return $event->fresh();
            }

            $accountProvider = $this->accountProvider($payment);
            $receipt = $this->ledger->recordCollectedPayment(new CollectedPayment(
                paymentId: (int) $payment->id,
                amount: $this->integerAmount($payment->amount),
                currency: (string) $payment->currency,
                provider: $accountProvider,
                providerReference: $payment->provider_reference,
                metadata: array_replace($this->snapshot($payment), [
                    'collection_route' => $accountProvider,
                ]),
            ));

            $event->update([
                'status' => 'posted',
                'posting_batch_uuid' => $receipt->batchUuid,
                'processed_at' => now(),
                'failed_at' => null,
                'last_error' => null,
            ]);

            return $event->fresh();
        } catch (\Throwable $exception) {
            $maxLength = max(250, (int) config('financial-mirror.max_error_length', 2000));

            $event->update([
                'status' => 'failed',
                'last_error' => mb_substr($exception->getMessage(), 0, $maxLength),
                'failed_at' => now(),
            ]);

            throw $exception;
        }
    }

    private function assertPaid(Payment $payment): void
    {
        if (strtoupper((string) $payment->status) !== 'PAID') {
            throw new \DomainException('Only confirmed PAID payments can be mirrored.');
        }

        if (strtoupper(trim((string) $payment->currency)) !== 'XAF') {
            throw new \DomainException('Only XAF payment collections can be mirrored.');
        }

        if (trim((string) $payment->provider) === '') {
            throw new \DomainException('The payment provider is missing.');
        }
    }

    private function accountProvider(Payment $payment): string
    {
        $provider = strtolower(trim((string) $payment->provider));
        $hasGePayReference = trim((string) data_get($payment->meta, 'gepay.reference', '')) !== ''
            || trim((string) data_get($payment->meta, 'gepay_reference', '')) !== '';

        if ($hasGePayReference && in_array($provider, ['momo', 'mtn', 'mtn_momo'], true)) {
            return 'gepay_mtn';
        }

        return $provider;
    }

    private function isCashProvider(string $provider): bool
    {
        return in_array(strtolower(trim($provider)), ['cash', 'cod', 'demo'], true);
    }

    private function integerAmount(mixed $amount): int
    {
        if (! is_numeric($amount)) {
            throw new \DomainException('The payment amount is not numeric.');
        }

        $numeric = (float) $amount;
        $rounded = (int) round($numeric);

        if ($rounded <= 0 || abs($numeric - $rounded) > 0.0001) {
            throw new \DomainException('The payment amount must be a positive integer in XAF.');
        }

        return $rounded;
    }

    private function snapshot(Payment $payment): array
    {
        return [
            'payment_id' => (int) $payment->id,
            'order_id' => $payment->order_id ? (int) $payment->order_id : null,
            'shipment_id' => $payment->shipment_id ? (int) $payment->shipment_id : null,
            'transport_booking_id' => $payment->transport_booking_id ? (int) $payment->transport_booking_id : null,
            'provider' => $payment->provider,
            'provider_reference' => $payment->provider_reference,
            'status' => $payment->status,
            'amount' => $payment->amount,
            'currency' => $payment->currency,
        ];
    }
}
