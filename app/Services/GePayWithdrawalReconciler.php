<?php

namespace App\Services;

use App\Domain\GePay\Enums\TransactionStatus;
use App\Domain\GePay\Enums\TransactionType;
use App\Domain\GePay\Models\GePayTransaction;
use App\Domain\GePay\Services\GePayGateway;
use App\Domain\GePay\Services\GePayInternalClientResolver;
use App\PartnerWithdrawal;
use Illuminate\Support\Facades\Log;

final class GePayWithdrawalReconciler
{
    public function __construct(
        private readonly GePayGateway $gateway,
        private readonly GePayInternalClientResolver $clientResolver,
    ) {}

    public function reconcile(PartnerWithdrawal $withdrawal): PartnerWithdrawal
    {
        if ($withdrawal->provider !== 'gepay' || $withdrawal->isPaid() || $withdrawal->isFailed()) {
            return $withdrawal;
        }

        $transaction = $this->findTransaction($withdrawal);

        if (! $transaction) {
            $metadata = array_merge($withdrawal->metadata ?? [], [
                'manual_review_required' => true,
                'gepay_lookup' => [
                    'provider_reference' => $withdrawal->provider_reference,
                    'external_reference' => $withdrawal->external_reference,
                ],
            ]);

            $withdrawal->forceFill([
                'status' => 'unknown',
                'failure_code' => 'GEPAY_TRANSACTION_NOT_FOUND',
                'failure_message' => 'Transaction GePay introuvable. Le solde reste réservé pour vérification.',
                'metadata' => $metadata,
                'reconciled_at' => now(),
            ])->save();

            Log::warning('GePayWithdrawalReconciler: transaction introuvable', [
                'withdrawal_id' => $withdrawal->id,
                'external_reference' => $withdrawal->external_reference,
            ]);

            return $withdrawal->fresh();
        }

        if ($withdrawal->provider_reference !== $transaction->uuid) {
            $withdrawal->forceFill(['provider_reference' => $transaction->uuid])->save();
        }

        if (! $transaction->status->isTerminal()) {
            $transaction = $this->gateway->refresh($transaction);
        }

        $this->applyStatus($withdrawal, $transaction);

        return $withdrawal->fresh();
    }

    private function findTransaction(PartnerWithdrawal $withdrawal): ?GePayTransaction
    {
        if ($withdrawal->provider_reference) {
            $byUuid = GePayTransaction::query()
                ->where('uuid', $withdrawal->provider_reference)
                ->where('type', TransactionType::DISBURSEMENT->value)
                ->first();

            if ($byUuid) {
                return $byUuid;
            }
        }

        try {
            $client = $this->clientResolver->resolve();
        } catch (\Throwable $exception) {
            Log::warning('GePayWithdrawalReconciler: client interne indisponible', [
                'withdrawal_id' => $withdrawal->id,
                'error' => $exception->getMessage(),
            ]);

            return null;
        }

        return GePayTransaction::query()
            ->where('client_id', $client->id)
            ->where('type', TransactionType::DISBURSEMENT->value)
            ->where('external_reference', $withdrawal->external_reference)
            ->first();
    }

    private function applyStatus(PartnerWithdrawal $withdrawal, GePayTransaction $transaction): void
    {
        $base = [
            'provider_reference' => $transaction->uuid,
            'reconciled_at' => now(),
        ];

        match ($transaction->status) {
            TransactionStatus::SUCCESSFUL => $withdrawal->forceFill(array_merge($base, [
                'status' => 'paid',
                'paid_at' => $withdrawal->paid_at ?? now(),
                'failed_at' => null,
                'failure_code' => null,
                'failure_message' => null,
            ]))->save(),

            TransactionStatus::FAILED,
            TransactionStatus::CANCELLED,
            TransactionStatus::EXPIRED => $withdrawal->forceFill(array_merge($base, [
                'status' => 'failed',
                'failed_at' => $withdrawal->failed_at ?? now(),
                'failure_code' => $transaction->failure_code ?? $transaction->status->value,
                'failure_message' => $transaction->failure_message ?? 'Décaissement GePay échoué.',
            ]))->save(),

            TransactionStatus::REVERSED,
            TransactionStatus::REFUNDED => $withdrawal->forceFill(array_merge($base, [
                'status' => 'reversed',
                'paid_at' => null,
                'failed_at' => null,
                'failure_code' => $transaction->status->value,
                'failure_message' => 'Le décaissement a été inversé ou remboursé par le fournisseur.',
                'metadata' => array_merge($withdrawal->metadata ?? [], [
                    'financial_reversal' => true,
                    'gepay_status' => $transaction->status->value,
                ]),
            ]))->save(),

            TransactionStatus::UNKNOWN => $withdrawal->forceFill(array_merge($base, [
                'status' => 'unknown',
                'failure_code' => $transaction->failure_code,
                'failure_message' => $transaction->failure_message
                    ?? 'Le résultat du décaissement est incertain. Le solde reste réservé.',
            ]))->save(),

            default => $withdrawal->forceFill(array_merge($base, [
                'status' => 'pending',
                'failure_code' => $transaction->failure_code,
                'failure_message' => $transaction->failure_message,
            ]))->save(),
        };
    }
}
