<?php

namespace App\Domain\Payment\Adapters;

use App\Domain\GePay\Enums\TransactionStatus;
use App\Domain\GePay\Enums\TransactionType;
use App\Domain\GePay\Models\GePayTransaction;
use App\Domain\GePay\Services\GePayGateway;
use App\Domain\GePay\Services\GePayInternalClientResolver;
use App\Domain\Payment\Contracts\PaymentGatewayAdapterInterface;
use App\Domain\Payment\ValueObjects\GatewayResult;
use App\Domain\Payment\ValueObjects\GatewayStatus;
use App\Payment;
use App\Services\SmsService;
use Illuminate\Support\Facades\Log;
use RuntimeException;

final class GePayAdapter implements PaymentGatewayAdapterInterface
{
    public function __construct(
        private readonly GePayGateway $gateway,
        private readonly GePayInternalClientResolver $resolver,
        private readonly MtnMomoAdapter $legacyMtn,
    ) {}

    public function provider(): string
    {
        return 'momo';
    }

    public function initiate(Payment $payment, array $context): GatewayResult
    {
        $rawPhone = trim((string) data_get($context, 'phone', ''));
        if ($rawPhone === '') {
            return GatewayResult::failure('Numéro de téléphone manquant pour le paiement GePay.');
        }

        $phone = trim((string) SmsService::normalizePhone($rawPhone));
        if ($phone === '') {
            return GatewayResult::failure('Numéro de téléphone invalide pour le paiement GePay.');
        }

        try {
            $client = $this->resolver->resolve();
        } catch (RuntimeException $e) {
            Log::error('GePayAdapter::initiate — client resolver failed', ['payment_id' => $payment->id, 'error' => $e->getMessage()]);
            return GatewayResult::failure($e->getMessage());
        }

        $externalReference = 'PAYMENT-' . $payment->id;
        $idempotencyKey = 'payment:' . $payment->id . ':collection';
        $orderNo = optional($payment->order)->order_no ?? (string) $payment->id;

        try {
            $transaction = $this->gateway->initiate(
                client: $client,
                type: TransactionType::COLLECTION,
                payload: [
                    'amount' => (int) round((float) $payment->amount),
                    'phone' => $phone,
                    'currency' => 'XAF',
                    'external_reference' => $externalReference,
                    'payer_message' => 'Commande BantuDelice ' . $orderNo,
                    'payee_note' => 'Paiement commande',
                ],
                idempotencyKey: $idempotencyKey,
            );
        } catch (RuntimeException $e) {
            Log::error('GePayAdapter::initiate — gateway exception', ['payment_id' => $payment->id, 'error' => $e->getMessage()]);
            return GatewayResult::failure($e->getMessage());
        }

        if (in_array($transaction->status, [
            TransactionStatus::FAILED,
            TransactionStatus::CANCELLED,
            TransactionStatus::EXPIRED,
        ], true)) {
            return GatewayResult::failure(
                $transaction->failure_message ?? 'Le paiement GePay a échoué.',
                ['gepay' => $this->gePayMeta($transaction)]
            );
        }

        return GatewayResult::success($transaction->uuid, [
            'provider' => 'momo',
            'demo' => false,
            'amount' => (int) $transaction->amount,
            'currency' => $transaction->currency,
            'phone' => $phone,
            'operator' => 'mtn',
            'gepay' => $this->gePayMeta($transaction),
            'gepay_reference' => $transaction->uuid,
            'gepay_status' => $transaction->status->value,
            'provider_reference' => $transaction->provider_reference,
            'instructions' => [
                'Vous allez recevoir une notification sur votre téléphone',
                'Entrez votre code PIN MTN MoMo pour confirmer',
                'Le paiement sera validé automatiquement',
            ],
            'message' => 'Veuillez confirmer le paiement sur votre téléphone MTN MoMo',
        ]);
    }

    public function checkStatus(string $providerReference): GatewayStatus
    {
        $transaction = GePayTransaction::where('uuid', $providerReference)->first();

        if (! $transaction) {
            Log::info('GePayAdapter::checkStatus — fallback vers MTN historique', [
                'provider_reference' => $providerReference,
            ]);

            $legacyStatus = $this->legacyMtn->checkStatus($providerReference);

            return new GatewayStatus(
                status: $legacyStatus->status,
                providerStatus: $legacyStatus->providerStatus,
                failureReason: $legacyStatus->failureReason,
                failureAction: $legacyStatus->failureAction,
                meta: array_merge($legacyStatus->meta, [
                    'legacy_mtn_fallback' => true,
                    'provider_reference' => $providerReference,
                ]),
            );
        }

        if (! $transaction->status->isTerminal()) {
            $transaction = $this->gateway->refresh($transaction);
        }

        return $this->mapToGatewayStatus($transaction);
    }

    public function handleCallback(array $payload): GatewayStatus
    {
        return GatewayStatus::unknown('CALLBACK_ROUTED_TO_GEPAY', [
            'note' => 'Les callbacks MTN passent par /api/gepay/v1/webhooks/mtn, non par PaymentService.',
        ]);
    }

    public function verifySignature(array $payload): bool
    {
        return false;
    }

    private function mapToGatewayStatus(GePayTransaction $transaction): GatewayStatus
    {
        $meta = ['gepay' => $this->gePayMeta($transaction)];

        return match ($transaction->status) {
            TransactionStatus::SUCCESSFUL => GatewayStatus::paid($meta, 'SUCCESSFUL'),
            TransactionStatus::FAILED => GatewayStatus::failed('FAILED', $transaction->failure_code, null, $meta),
            TransactionStatus::CANCELLED => GatewayStatus::failed('CANCELLED', $transaction->failure_code, null, $meta),
            TransactionStatus::EXPIRED => GatewayStatus::failed('EXPIRED', $transaction->failure_code, null, $meta),
            TransactionStatus::REVERSED => GatewayStatus::failed('REVERSED', null, null, [
                ...$meta,
                'financial_reversal' => true,
                'gepay_status' => 'reversed',
            ]),
            TransactionStatus::REFUNDED => GatewayStatus::failed('REFUNDED', null, null, [
                ...$meta,
                'financial_reversal' => true,
                'gepay_status' => 'refunded',
            ]),
            TransactionStatus::UNKNOWN => GatewayStatus::unknown('UNKNOWN', $meta),
            default => GatewayStatus::pending($transaction->status->value, $meta),
        };
    }

    private function gePayMeta(GePayTransaction $transaction): array
    {
        return [
            'reference' => $transaction->uuid,
            'provider_reference' => $transaction->provider_reference,
            'provider' => $transaction->provider,
            'status' => $transaction->status->value,
        ];
    }
}
