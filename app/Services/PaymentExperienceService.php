<?php

namespace App\Services;

use App\Payment;

class PaymentExperienceService
{
    protected const NON_RETRYABLE_REASONS = [
        'PAYER_NOT_FOUND',
        'PAYEE_NOT_ALLOWED_TO_RECEIVE',
        'PAYEE_NOT_FOUND',
        'SENDER_ACCOUNT_NOT_ACTIVE',
        'VALIDATION_ERROR',
        'TRANSFER_TYPE_UNKNOWN',
        'TRANSACTION_NOT_FOUND',
    ];

    public function describe(?Payment $payment): ?array
    {
        if (!$payment) {
            return null;
        }

        $status = strtoupper((string) $payment->status);
        $failureReason = $this->failureReason($payment);
        $failureMessage = $this->failureMessage($payment);
        $failureAction = $this->failureAction($payment);

        return [
            'payment_id' => $payment->id,
            'module' => $this->resolveModule($payment),
            'status' => $status,
            'is_terminal' => in_array($status, ['PAID', 'FAILED', 'CANCELLED'], true),
            'retry_allowed' => $this->isRetryAllowed($status, $failureReason),
            'customer_message' => $this->customerMessage($payment, $status, $failureMessage),
            'support_action' => $this->supportAction($status, $failureAction),
            'failure_reason' => $failureReason,
            'failure_message' => $failureMessage,
            'failure_action' => $failureAction,
            'provider' => $payment->provider,
            'provider_reference' => $payment->provider_reference,
            'amount' => $payment->amount,
            'currency' => $payment->currency,
            'updated_at' => optional($payment->updated_at)->toIso8601String(),
        ];
    }

    protected function resolveModule(Payment $payment): string
    {
        $type = strtolower((string) data_get($payment->meta, 'checkout_data.type', data_get($payment->meta, 'type', '')));

        if ($type !== '') {
            return $type;
        }

        if ($payment->shipment_id) {
            return 'colis';
        }

        if ($payment->transport_booking_id) {
            return 'transport';
        }

        return 'food';
    }

    protected function failureReason(Payment $payment): ?string
    {
        $reason = trim((string) data_get($payment->meta, 'failure_reason', ''));

        return $reason !== '' ? $reason : null;
    }

    protected function failureMessage(Payment $payment): ?string
    {
        $message = trim((string) data_get($payment->meta, 'failure_message', ''));

        if ($message !== '') {
            return $message;
        }

        $legacyMessage = trim((string) data_get($payment->meta, 'message', ''));

        return $legacyMessage !== '' ? $legacyMessage : null;
    }

    protected function failureAction(Payment $payment): ?string
    {
        $action = trim((string) data_get($payment->meta, 'failure_action', ''));

        return $action !== '' ? $action : null;
    }

    protected function customerMessage(Payment $payment, string $status, ?string $failureMessage): string
    {
        return match ($status) {
            'PAID' => 'Paiement confirme.',
            'FAILED', 'CANCELLED' => $failureMessage ?: 'Le paiement a echoue.',
            'PENDING' => $this->pendingMessage($payment),
            default => 'Statut de paiement en cours de verification.',
        };
    }

    protected function supportAction(string $status, ?string $failureAction): ?string
    {
        if (in_array($status, ['FAILED', 'CANCELLED'], true)) {
            return $failureAction ?: 'Verifiez le detail du paiement et accompagnez le client avant une nouvelle tentative.';
        }

        if ($status === 'PENDING') {
            return 'Attendez la confirmation du provider puis relancez la verification si necessaire.';
        }

        return null;
    }

    protected function pendingMessage(Payment $payment): string
    {
        $provider = strtolower((string) $payment->provider);

        if ($provider === 'cash') {
            if (data_get($payment->meta, 'cash_on_pickup')) {
                return 'Paiement en espece prevu au retrait.';
            }

            if (data_get($payment->meta, 'cash_on_delivery')) {
                return 'Paiement en espece prevu a la livraison.';
            }

            return 'Paiement en espece en attente d encaissement.';
        }

        $instructions = data_get($payment->meta, 'instructions');
        if (is_array($instructions) && !empty($instructions)) {
            return (string) $instructions[0];
        }

        $message = trim((string) data_get($payment->meta, 'message', ''));

        return $message !== '' ? $message : 'Confirmez le paiement sur votre telephone.';
    }

    protected function isRetryAllowed(string $status, ?string $failureReason): bool
    {
        if (!in_array($status, ['FAILED', 'CANCELLED'], true)) {
            return false;
        }

        if ($failureReason === null) {
            return true;
        }

        return !in_array($failureReason, self::NON_RETRYABLE_REASONS, true);
    }
}
