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
    ) {}

    public function provider(): string
    {
        return 'momo';
    }

    public function initiate(Payment $payment, array $context): GatewayResult
    {
        $phone = trim((string) SmsService::normalizePhone(data_get($context, 'phone', '')));

        if ($phone === '') {
            return GatewayResult::failure('Numéro de téléphone manquant pour le paiement GePay.');
        }

        try {
            $client = $this->resolver->resolve();
        } catch (RuntimeException $e) {
            Log::error('GePayAdapter::initiate — client resolver failed', ['payment_id' => $payment->id, 'error' => $e->getMessage()]);
            return GatewayResult::failure($e->getMessage());
        }

        $externalReference = 'PAYMENT-' . $payment->id;
        $idempotencyKey    = 'payment:' . $payment->id . ':collection';

        $orderNo = optional($payment->order)->order_no ?? (string) $payment->id;

        try {
            $transaction = $this->gateway->initiate(
                client: $client,
                type: TransactionType::COLLECTION,
                payload: [
                    'amount'             => (int) round((float) $payment->amount),
                    'phone'              => $phone,
                    'currency'           => 'XAF',
                    'external_reference' => $externalReference,
                    'payer_message'      => 'Commande BantuDelice ' . $orderNo,
                    'payee_note'         => 'Paiement commande',
                ],
                idempotencyKey: $idempotencyKey,
            );
        } catch (RuntimeException $e) {
            Log::error('GePayAdapter::initiate — gateway exception', ['payment_id' => $payment->id, 'error' => $e->getMessage()]);
            return GatewayResult::failure($e->getMessage());
        }

        $status = $transaction->status;

        if (in_array($status, [TransactionStatus::FAILED, TransactionStatus::CANCELLED, TransactionStatus::EXPIRED], true)) {
            return GatewayResult::failure(
                $transaction->failure_message ?? 'Le paiement GePay a échoué.',
                ['gepay' => $this->gePayMeta($transaction)]
            );
        }

        // created / submitted / pending / successful / unknown → réussite d'initiation
        // unknown inclus : l'appel a peut-être atteint MTN, un retry créerait un double paiement
        return GatewayResult::success($transaction->uuid, [
            'provider'    => 'momo',
            'demo'        => false,
            'amount'      => (int) $transaction->amount,
            'currency'    => $transaction->currency,
            'phone'       => $phone,
            'operator'    => 'mtn',
            'gepay'       => $this->gePayMeta($transaction),
            'gepay_reference'      => $transaction->uuid,
            'gepay_status'         => $transaction->status->value,
            'provider_reference'   => $transaction->provider_reference,
            'instructions' => [
                'Vous allez recevoir une notification sur votre téléphone',
                'Entrez votre code PIN MTN MoMo pour confirmer',
                'Le paiement sera validé automatiquement',
            ],
            'message' => 'Veuillez confirmer le paiement sur votre téléphone MTN MoMo',
        ]);
    }

    /**
     * $providerReference = GePayTransaction::uuid (pas la référence MTN).
     * BantuDelice stocke l'UUID GePay dans Payment::provider_reference.
     */
    public function checkStatus(string $providerReference): GatewayStatus
    {
        $transaction = GePayTransaction::where('uuid', $providerReference)->first();

        if (! $transaction) {
            Log::warning('GePayAdapter::checkStatus — transaction introuvable', ['gepay_uuid' => $providerReference]);
            return GatewayStatus::unknown('GEPAY_NOT_FOUND', [
                'error'      => 'Transaction GePay introuvable : ' . $providerReference,
                'gepay_uuid' => $providerReference,
            ]);
        }

        if (! $transaction->status->isTerminal()) {
            $transaction = $this->gateway->refresh($transaction);
        }

        return $this->mapToGatewayStatus($transaction);
    }

    /**
     * Les webhooks MTN entrent via /api/gepay/v1/webhooks/mtn.
     * GePayAdapter ne traite pas les callbacks directs : la réconciliation
     * passe par PaymentReconciliationService → checkStatus() → refresh().
     */
    public function handleCallback(array $payload): GatewayStatus
    {
        return GatewayStatus::unknown('CALLBACK_ROUTED_TO_GEPAY', [
            'note' => 'Les callbacks MTN passent par /api/gepay/v1/webhooks/mtn, non par PaymentService.',
        ]);
    }

    public function verifySignature(array $payload): bool
    {
        // La vérification HMAC MTN est faite dans GePayWebhookController.
        return true;
    }

    private function mapToGatewayStatus(GePayTransaction $transaction): GatewayStatus
    {
        $meta = ['gepay' => $this->gePayMeta($transaction)];

        return match ($transaction->status) {
            TransactionStatus::SUCCESSFUL =>
                GatewayStatus::paid($meta, 'SUCCESSFUL'),

            TransactionStatus::FAILED =>
                GatewayStatus::failed('FAILED', $transaction->failure_code, null, $meta),

            TransactionStatus::CANCELLED =>
                GatewayStatus::failed('CANCELLED', $transaction->failure_code, null, $meta),

            TransactionStatus::EXPIRED =>
                GatewayStatus::failed('EXPIRED', $transaction->failure_code, null, $meta),

            TransactionStatus::REVERSED =>
                GatewayStatus::failed('REVERSED', null, null, [
                    ...$meta,
                    'financial_reversal' => true,
                    'gepay_status'       => 'reversed',
                ]),

            TransactionStatus::REFUNDED =>
                GatewayStatus::failed('REFUNDED', null, null, [
                    ...$meta,
                    'financial_reversal' => true,
                    'gepay_status'       => 'refunded',
                ]),

            TransactionStatus::UNKNOWN =>
                GatewayStatus::unknown('UNKNOWN', $meta),

            default => // created / submitted / pending
                GatewayStatus::pending($transaction->status->value, $meta),
        };
    }

    private function gePayMeta(GePayTransaction $transaction): array
    {
        return [
            'reference'          => $transaction->uuid,
            'provider_reference' => $transaction->provider_reference,
            'provider'           => $transaction->provider,
            'status'             => $transaction->status->value,
        ];
    }
}
