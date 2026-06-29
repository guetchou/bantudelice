<?php

namespace App\Services;

use App\Domain\Payment\PaymentGatewayFactory;
use App\Payment;
use App\PaymentReconciliationCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PaymentOperationsReconciliationService
{
    public function __construct(
        private readonly PaymentGatewayFactory $gatewayFactory,
        private readonly PaymentService $paymentService,
        private readonly PaymentBusinessService $business,
    ) {}

    public function reconcile(Payment $payment): array
    {
        $reference = trim((string) $payment->provider_reference);
        if ($reference === '') {
            $case = $this->business->flagProviderUnknown($payment, [
                'reason' => 'missing_provider_reference',
            ]);

            return [
                'reconciled' => false,
                'status' => 'UNKNOWN',
                'message' => 'Référence fournisseur manquante.',
                'case_id' => $case?->id,
            ];
        }

        $adapter = $this->gatewayFactory->for($payment->provider);
        $gatewayStatus = $adapter->checkStatus($reference);
        $providerStatus = strtoupper((string) ($gatewayStatus->providerStatus ?: $gatewayStatus->status));

        if (in_array($providerStatus, ['REVERSED', 'REVERSAL', 'ROLLED_BACK'], true)) {
            $result = $this->business->reverseConfirmedPayment(
                $payment,
                'Statut fournisseur : ' . $providerStatus,
                ['provider_status' => $providerStatus, 'gateway_meta' => $gatewayStatus->meta]
            );

            return [
                'reconciled' => true,
                'status' => 'REVERSED',
                'message' => 'Paiement inversé et contre-écrit.',
                'case_id' => $result['case']?->id,
            ];
        }

        if (in_array($providerStatus, ['DISPUTED', 'CHARGEBACK'], true)) {
            $case = $this->business->flagDispute($payment, [
                'provider_status' => $providerStatus,
                'gateway_meta' => $gatewayStatus->meta,
            ]);

            return [
                'reconciled' => true,
                'status' => 'DISPUTED',
                'message' => 'Paiement placé en litige et fonds bloqués.',
                'case_id' => $case?->id,
            ];
        }

        if ($gatewayStatus->isPaid()) {
            if (! $payment->isPaid()) {
                $this->paymentService->markPaymentAsPaid($payment, [
                    'reconciled' => true,
                    'provider_status' => $providerStatus,
                    'gateway_meta' => $gatewayStatus->meta,
                ]);

                return [
                    'reconciled' => true,
                    'status' => 'RECONCILED',
                    'message' => 'Paiement confirmé, affecté et rapproché.',
                ];
            }

            $this->resolveOpenCases($payment, ['provider_unknown', 'status_mismatch']);

            return [
                'reconciled' => true,
                'status' => 'VERIFIED',
                'message' => 'Paiement confirmé auprès du fournisseur.',
            ];
        }

        if ($gatewayStatus->isFailed()) {
            if ($payment->isPaid()) {
                $case = $this->openStatusMismatchCase($payment, $providerStatus, $gatewayStatus->meta);

                return [
                    'reconciled' => false,
                    'status' => 'INCONSISTENT',
                    'message' => 'Paiement encaissé en base mais déclaré échoué par le fournisseur.',
                    'case_id' => $case?->id,
                ];
            }

            $payment->update([
                'status' => 'FAILED',
                'meta' => array_merge($payment->meta ?? [], [
                    'reconciled_at' => now()->toIso8601String(),
                    'provider_status' => $providerStatus,
                    'failure_reason' => $gatewayStatus->failureReason,
                    'failure_action' => $gatewayStatus->failureAction,
                ], $gatewayStatus->meta),
            ]);

            $this->resolveOpenCases($payment, ['provider_unknown']);

            return [
                'reconciled' => true,
                'status' => 'FAILED',
                'message' => 'Échec confirmé par le fournisseur.',
            ];
        }

        if ($gatewayStatus->status === 'UNKNOWN' || in_array($providerStatus, ['UNKNOWN', 'ERROR', 'MISSING_REFERENCE'], true)) {
            $case = $this->business->flagProviderUnknown($payment, [
                'provider_status' => $providerStatus,
                'gateway_meta' => $gatewayStatus->meta,
            ]);

            return [
                'reconciled' => false,
                'status' => 'UNKNOWN',
                'message' => 'Le fournisseur ne confirme aucun statut final.',
                'case_id' => $case?->id,
            ];
        }

        if ($payment->isPaid()) {
            $case = $this->openStatusMismatchCase($payment, $providerStatus, $gatewayStatus->meta);

            return [
                'reconciled' => false,
                'status' => 'INCONSISTENT',
                'message' => 'Paiement encaissé en base mais encore non terminal chez le fournisseur.',
                'case_id' => $case?->id,
            ];
        }

        return [
            'reconciled' => false,
            'status' => 'PENDING',
            'message' => 'Paiement toujours en traitement chez le fournisseur.',
        ];
    }

    private function openStatusMismatchCase(Payment $payment, string $providerStatus, array $details): ?PaymentReconciliationCase
    {
        if (! Schema::hasTable('payment_reconciliation_cases')) {
            return null;
        }

        return PaymentReconciliationCase::firstOrCreate(
            ['case_key' => 'payment:' . $payment->id . ':status_mismatch'],
            [
                'payment_id' => $payment->id,
                'case_type' => 'status_mismatch',
                'severity' => 'critical',
                'status' => 'open',
                'expected_amount' => (float) $payment->amount,
                'observed_amount' => null,
                'currency' => $payment->currency ?: 'XAF',
                'provider' => $payment->provider,
                'provider_reference' => $payment->provider_reference,
                'summary' => 'Le statut interne et le statut fournisseur sont incompatibles.',
                'details' => array_merge($details, [
                    'internal_status' => $payment->status,
                    'provider_status' => $providerStatus,
                ]),
                'opened_at' => now(),
            ]
        );
    }

    private function resolveOpenCases(Payment $payment, array $types): void
    {
        if (! Schema::hasTable('payment_reconciliation_cases')) {
            return;
        }

        DB::table('payment_reconciliation_cases')
            ->where('payment_id', $payment->id)
            ->whereIn('case_type', $types)
            ->whereIn('status', ['open', 'investigating'])
            ->update([
                'status' => 'resolved',
                'resolved_at' => now(),
                'updated_at' => now(),
            ]);
    }
}
