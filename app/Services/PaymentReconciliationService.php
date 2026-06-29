<?php

namespace App\Services;

use App\Domain\Payment\Enums\PaymentStatus;
use App\Domain\Payment\MtnErrorCatalog;
use App\Domain\Payment\PaymentGatewayFactory;
use App\Domain\Payment\Services\PaymentStateMachine;
use App\Domain\Payment\ValueObjects\GatewayStatus;
use App\Payment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaymentReconciliationService
{
    public function __construct(
        protected ?PaymentGatewayFactory $gatewayFactory = null,
        protected ?PaymentStateMachine $paymentStateMachine = null,
    ) {
    }

    protected function gateway(): PaymentGatewayFactory
    {
        return $this->gatewayFactory ?? app(PaymentGatewayFactory::class);
    }

    protected function stateMachine(): PaymentStateMachine
    {
        return $this->paymentStateMachine ?? app(PaymentStateMachine::class);
    }

    public function reconcile(Payment $payment): array
    {
        $status = $payment->canonicalStatus();

        return match (true) {
            $status === PaymentStatus::PAID => $this->verifyPaidPayment($payment),
            $status === PaymentStatus::FAILED => $this->verifyFailedPayment($payment),
            $status->isUnresolved() => $this->checkPendingPayment($payment),
            $status === PaymentStatus::DISPUTED => $this->verifyDisputedPayment($payment),
            default => [
                'reconciled' => false,
                'status' => $status->value,
                'message' => 'Statut non réconciliable : ' . $status->value,
            ],
        };
    }

    public function reconcilePendingPayments(int $limit = 50): array
    {
        $payments = Payment::query()
            ->whereIn('status', [
                'INITIATED',
                'CREATED',
                'PENDING',
                'PROCESSING',
                'AUTHORIZED',
                'SUBMITTED',
                'UNKNOWN',
            ])
            ->where('created_at', '>=', now()->subDays(7))
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();

        $processed = 0;
        $reconciled = 0;
        $failed = 0;
        $unknown = 0;

        foreach ($payments as $payment) {
            $processed++;
            $result = $this->reconcile($payment);

            if ($result['reconciled']) {
                $reconciled++;
            }
            if (($result['status'] ?? null) === PaymentStatus::FAILED->value) {
                $failed++;
            }
            if (($result['status'] ?? null) === PaymentStatus::UNKNOWN->value) {
                $unknown++;
            }
        }

        return compact('processed', 'reconciled', 'failed', 'unknown');
    }

    public function backfillFailedPaymentDiagnostics(
        int $limit = 50,
        ?int $paymentId = null,
        bool $dryRun = false
    ): array {
        $payments = $this->loadFailedDiagnosticCandidates($limit, $paymentId);
        $result = [
            'processed' => 0,
            'updated' => 0,
            'skipped' => 0,
            'errors' => 0,
            'items' => [],
        ];

        foreach ($payments as $payment) {
            $result['processed']++;

            if (! $this->shouldBackfillFailureDiagnostics($payment)) {
                $result['skipped']++;
                continue;
            }

            try {
                $gatewayStatus = $this->getAdapterStatus($payment);
                $failureMeta = $this->failureMetaFromGatewayStatus($gatewayStatus);

                if (empty($failureMeta)) {
                    $failureMeta = $this->buildHistoricalFailureFallback(
                        $payment,
                        $gatewayStatus
                    );
                }

                $providerState = $gatewayStatus->providerStatus ?? $gatewayStatus->status;
                if (
                    ! in_array(
                        strtoupper((string) $providerState),
                        ['FAILED', 'CANCELLED', 'REJECTED', 'DECLINED', 'EXPIRED'],
                        true
                    )
                    && empty($failureMeta)
                ) {
                    $result['skipped']++;
                    continue;
                }

                if (empty($failureMeta)) {
                    $result['skipped']++;
                    continue;
                }

                if (! $dryRun) {
                    $this->persistFailedPaymentDiagnostics(
                        $payment,
                        $gatewayStatus,
                        $failureMeta
                    );
                }

                $result['updated']++;
                $result['items'][] = [
                    'payment_id' => $payment->id,
                    'provider_reference' => $payment->provider_reference,
                    'failure_reason' => $failureMeta['failure_reason'] ?? null,
                    'failure_message' => $failureMeta['failure_message'] ?? null,
                    'failure_action' => $failureMeta['failure_action'] ?? null,
                    'dry_run' => $dryRun,
                ];
            } catch (\Exception $e) {
                $result['errors']++;
                Log::error('Erreur backfill diagnostic paiement', [
                    'payment_id' => $payment->id,
                    'provider_reference' => $payment->provider_reference,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $result;
    }

    protected function verifyPaidPayment(Payment $payment): array
    {
        try {
            $gatewayStatus = $this->getAdapterStatus($payment);

            if ($gatewayStatus->isPaid()) {
                $this->updateReconciliationMeta($payment, 'VERIFIED', $gatewayStatus);
                $this->logReconciliation(
                    $payment,
                    'VERIFIED',
                    'Paiement confirmé auprès du provider'
                );

                return [
                    'reconciled' => true,
                    'status' => 'VERIFIED',
                    'message' => 'Paiement confirmé',
                ];
            }

            if ($gatewayStatus->isFailed()) {
                $this->logReconciliation(
                    $payment,
                    'INCONSISTENT',
                    'PAID en base mais FAILED chez le provider'
                );

                return [
                    'reconciled' => false,
                    'status' => 'INCONSISTENT',
                    'message' => 'Incohérence critique : paiement encaissé en base mais refusé par le provider.',
                ];
            }

            $status = $gatewayStatus->isPending() ? 'PENDING' : 'UNKNOWN';
            $this->updateReconciliationMeta($payment, $status, $gatewayStatus);

            return [
                'reconciled' => false,
                'status' => $status,
                'message' => 'Le provider ne confirme pas encore le paiement encaissé.',
            ];
        } catch (\Exception $e) {
            Log::error('Erreur vérification paiement PAID', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'reconciled' => false,
                'status' => 'ERROR',
                'message' => 'Erreur : ' . $e->getMessage(),
            ];
        }
    }

    protected function verifyFailedPayment(Payment $payment): array
    {
        try {
            $gatewayStatus = $this->getAdapterStatus($payment);

            if ($gatewayStatus->isFailed()) {
                $failureMeta = $this->failureMetaFromGatewayStatus($gatewayStatus);
                $this->updateReconciliationMeta(
                    $payment,
                    'FAILED_VERIFIED',
                    $gatewayStatus,
                    $failureMeta
                );
                $this->logReconciliation(
                    $payment,
                    'FAILED_VERIFIED',
                    'Échec confirmé par le provider'
                );

                return [
                    'reconciled' => true,
                    'status' => PaymentStatus::FAILED->value,
                    'message' => 'Échec confirmé par le provider.',
                ];
            }

            if ($gatewayStatus->isPaid()) {
                $this->logReconciliation(
                    $payment,
                    'INCONSISTENT',
                    'FAILED en base mais PAID chez le provider'
                );

                return [
                    'reconciled' => false,
                    'status' => 'INCONSISTENT',
                    'message' => 'Paiement reçu après un échec local : validation manuelle obligatoire.',
                ];
            }

            $status = $gatewayStatus->isPending() ? 'PENDING' : 'UNKNOWN';
            $this->updateReconciliationMeta($payment, $status, $gatewayStatus);

            return [
                'reconciled' => false,
                'status' => $status,
                'message' => 'Le provider ne confirme pas l’échec enregistré.',
            ];
        } catch (\Exception $e) {
            return [
                'reconciled' => false,
                'status' => 'ERROR',
                'message' => 'Erreur : ' . $e->getMessage(),
            ];
        }
    }

    protected function verifyDisputedPayment(Payment $payment): array
    {
        $result = $this->verifyPaidPayment($payment);

        if (($result['status'] ?? null) === 'VERIFIED') {
            $result['reconciled'] = false;
            $result['status'] = PaymentStatus::DISPUTED->value;
            $result['message'] = 'Le provider confirme le paiement, mais le litige reste ouvert.';
        }

        return $result;
    }

    protected function checkPendingPayment(Payment $payment): array
    {
        try {
            $gatewayStatus = $this->getAdapterStatus($payment);

            if ($gatewayStatus->isPaid()) {
                $this->makePaymentService()->markPaymentAsPaid($payment, [
                    'provider_status' => $this->buildProviderStatusSnapshot($gatewayStatus),
                    'reconciled' => true,
                ]);

                $fresh = $payment->fresh() ?? $payment;
                $this->updateReconciliationMeta($fresh, 'RECONCILED', $gatewayStatus);
                $this->logReconciliation(
                    $fresh,
                    'RECONCILED',
                    'Paiement non résolu mis à jour vers PAID'
                );

                return [
                    'reconciled' => true,
                    'status' => 'RECONCILED',
                    'message' => 'Paiement confirmé et affecté.',
                ];
            }

            if ($gatewayStatus->isFailed()) {
                $failureMeta = $this->failureMetaFromGatewayStatus($gatewayStatus);
                $failed = $this->stateMachine()->transition(
                    $payment,
                    PaymentStatus::FAILED,
                    array_merge([
                        'failed_at' => now()->toIso8601String(),
                        'provider_status' => $this->buildProviderStatusSnapshot($gatewayStatus),
                    ], $failureMeta),
                    'provider_reconciliation_failed'
                );

                $this->updateReconciliationMeta(
                    $failed,
                    'FAILED',
                    $gatewayStatus,
                    $failureMeta
                );
                $this->logReconciliation(
                    $failed,
                    'FAILED',
                    'Paiement échoué chez le provider'
                );

                return [
                    'reconciled' => true,
                    'status' => PaymentStatus::FAILED->value,
                    'message' => 'Échec confirmé par le provider.',
                ];
            }

            if ($gatewayStatus->isPending()) {
                $current = $payment->canonicalStatus();
                if (in_array($current, [
                    PaymentStatus::INITIATED,
                    PaymentStatus::UNKNOWN,
                ], true)) {
                    $payment = $this->stateMachine()->transition(
                        $payment,
                        PaymentStatus::PENDING,
                        ['provider_status' => $this->buildProviderStatusSnapshot($gatewayStatus)],
                        'provider_reconciliation_pending'
                    );
                }

                $this->updateReconciliationMeta(
                    $payment->fresh() ?? $payment,
                    'PENDING',
                    $gatewayStatus
                );

                return [
                    'reconciled' => false,
                    'status' => PaymentStatus::PENDING->value,
                    'message' => 'Paiement toujours en attente.',
                ];
            }

            $unknown = $payment->canonicalStatus() === PaymentStatus::UNKNOWN
                ? $payment
                : $this->stateMachine()->transition(
                    $payment,
                    PaymentStatus::UNKNOWN,
                    ['provider_status' => $this->buildProviderStatusSnapshot($gatewayStatus)],
                    'provider_reconciliation_unknown'
                );

            $this->updateReconciliationMeta(
                $unknown->fresh() ?? $unknown,
                'UNKNOWN',
                $gatewayStatus
            );
            $this->logReconciliation(
                $unknown,
                'UNKNOWN',
                'Statut provider non déterminé'
            );

            return [
                'reconciled' => false,
                'status' => PaymentStatus::UNKNOWN->value,
                'message' => 'Statut indéterminé : ne pas relancer de débit avant vérification.',
            ];
        } catch (\Exception $e) {
            Log::error('Erreur vérification paiement non résolu', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'reconciled' => false,
                'status' => 'ERROR',
                'message' => 'Erreur : ' . $e->getMessage(),
            ];
        }
    }

    protected function getAdapterStatus(Payment $payment): GatewayStatus
    {
        return $this->gatewayStatusFromProviderArray(
            $this->getProviderStatus($payment)
        );
    }

    protected function getProviderStatus(Payment $payment): array
    {
        $reference = trim((string) ($payment->provider_reference ?? ''));

        if ($reference === '') {
            return [
                'status' => 'UNKNOWN',
                'provider_status' => 'MISSING_REFERENCE',
                'data' => [],
                'message' => 'Référence provider manquante',
            ];
        }

        $adapter = $this->gateway()->for($payment->provider);
        $gatewayStatus = $adapter->checkStatus($reference);

        return [
            'status' => $gatewayStatus->status,
            'provider_status' => $gatewayStatus->providerStatus,
            'provider' => $payment->provider,
            'reason' => $gatewayStatus->failureReason,
            'action' => $gatewayStatus->failureAction,
            'message' => $gatewayStatus->failureReason
                ?? ($gatewayStatus->meta['message'] ?? null),
            'data' => $gatewayStatus->meta,
        ];
    }

    private function gatewayStatusFromProviderArray(array $raw): GatewayStatus
    {
        $status = strtoupper((string) (
            $raw['status']
            ?? $raw['provider_status']
            ?? data_get($raw, 'data.status')
            ?? 'UNKNOWN'
        ));
        $providerStatus = (string) (
            $raw['provider_status']
            ?? data_get($raw, 'data.status')
            ?? $status
        );
        $reason = $this->firstNonEmptyProviderText([
            $raw['reason'] ?? null,
            data_get($raw, 'data.reason'),
        ], null, ['Statut récupéré', 'Impossible de vérifier le statut']);
        $action = $raw['action'] ?? null;
        $message = $raw['message'] ?? null;

        if ($reason !== null && MtnErrorCatalog::has($reason)) {
            $failure = DisbursementService::buildFailureMetadata('mtn_momo', $raw);
            $reason = $failure['failure_reason'] ?? $reason;
            $message = $failure['failure_message'] ?? $message;
            $action = $failure['failure_action'] ?? $action;
        }

        $meta = array_merge($raw, array_filter([
            'failure_reason' => $reason,
            'failure_message' => $message,
            'failure_action' => $action,
            'provider_status' => $providerStatus,
        ], fn ($value) => $value !== null));

        return match (true) {
            in_array($status, [
                'PAID',
                'SUCCESS',
                'SUCCESSFUL',
                'COMPLETED',
                'CAPTURED',
                'APPROVED',
            ], true) => GatewayStatus::paid(
                array_merge($raw, ['provider_status' => $providerStatus]),
                $providerStatus
            ),
            in_array($status, [
                'FAILED',
                'CANCELLED',
                'REJECTED',
                'DECLINED',
                'EXPIRED',
            ], true) => GatewayStatus::failed(
                $providerStatus,
                $reason,
                $action,
                $meta
            ),
            in_array($status, [
                'PENDING',
                'PROCESSING',
                'AUTHORIZED',
                'SUBMITTED',
                'CREATED',
                'INITIATED',
            ], true) => GatewayStatus::pending($providerStatus, $raw),
            default => GatewayStatus::unknown($providerStatus ?: 'UNKNOWN', $raw),
        };
    }

    protected function failureMetaFromGatewayStatus(GatewayStatus $gatewayStatus): array
    {
        $failureMessage = $gatewayStatus->meta['failure_message']
            ?? $gatewayStatus->failureReason;

        return array_filter([
            'failure_reason' => $gatewayStatus->failureReason,
            'failure_message' => $failureMessage,
            'failure_action' => $gatewayStatus->failureAction,
        ], fn ($value) => $value !== null && $value !== '');
    }

    protected function buildHistoricalFailureFallback(
        Payment $payment,
        GatewayStatus $gatewayStatus
    ): array {
        if ($gatewayStatus->status !== GatewayStatus::UNKNOWN
            && $gatewayStatus->status !== 'ERROR') {
            return [];
        }

        $manualNote = trim((string) data_get($payment->meta, 'manual_note', ''));
        if ($manualNote === '') {
            return [];
        }

        return [
            'failure_reason' => 'MTN_STATUS_UNAVAILABLE',
            'failure_message' => $manualNote,
            'failure_action' => 'Relancer uniquement après confirmation de l’absence de débit.',
        ];
    }

    protected function extractProviderFailureReason(GatewayStatus $gatewayStatus): ?string
    {
        return $gatewayStatus->failureReason;
    }

    protected function updateReconciliationMeta(
        Payment $payment,
        string $reconciliationStatus,
        GatewayStatus $gatewayStatus,
        array $extraMeta = []
    ): void {
        $payment->update([
            'meta' => array_replace_recursive($payment->meta ?? [], [
                'reconciled_at' => now()->toIso8601String(),
                'reconciliation_status' => $reconciliationStatus,
                'provider_status' => $this->buildProviderStatusSnapshot($gatewayStatus),
            ], $extraMeta),
        ]);
    }

    protected function buildProviderStatusSnapshot(GatewayStatus $gatewayStatus): array
    {
        return array_filter([
            'status' => $gatewayStatus->status,
            'provider_status' => $gatewayStatus->providerStatus,
            'reason' => $gatewayStatus->failureReason,
            'action' => $gatewayStatus->failureAction,
            'message' => $gatewayStatus->failureReason,
        ], fn ($value) => $value !== null);
    }

    protected function persistFailedPaymentDiagnostics(
        Payment $payment,
        GatewayStatus $gatewayStatus,
        array $failureMeta
    ): void {
        $existingMeta = $payment->meta ?? [];

        $this->updateReconciliationMeta($payment, 'FAILED', $gatewayStatus, [
            'failed_at' => data_get($existingMeta, 'failed_at')
                ?? now()->toIso8601String(),
            'reconciled_at' => data_get($existingMeta, 'reconciled_at')
                ?? now()->toIso8601String(),
        ] + $failureMeta);
    }

    protected function makePaymentService(): PaymentService
    {
        return app(PaymentService::class);
    }

    protected function loadFailedDiagnosticCandidates(int $limit, ?int $paymentId = null)
    {
        if ($paymentId !== null) {
            $payment = Payment::find($paymentId);

            return $payment ? collect([$payment]) : collect();
        }

        return Payment::query()
            ->whereIn('provider', ['momo', 'mtn_momo', 'mtn'])
            ->where('status', PaymentStatus::FAILED->value)
            ->whereNotNull('provider_reference')
            ->orderBy('id')
            ->limit($limit)
            ->get();
    }

    protected function shouldBackfillFailureDiagnostics(Payment $payment): bool
    {
        if (
            ! in_array($payment->provider, ['momo', 'mtn_momo', 'mtn'], true)
            || $payment->canonicalStatus() !== PaymentStatus::FAILED
            || ! $payment->provider_reference
        ) {
            return false;
        }

        $failureReason = trim((string) data_get($payment->meta, 'failure_reason', ''));
        $failureMessage = trim((string) data_get($payment->meta, 'failure_message', ''));
        $failureAction = trim((string) data_get($payment->meta, 'failure_action', ''));

        return $failureReason === ''
            || $failureReason === 'Statut récupéré'
            || $failureMessage === ''
            || $failureAction === '';
    }

    protected function logReconciliation(Payment $payment, string $status, string $message): void
    {
        try {
            DB::table('payment_reconciliation_logs')->insert([
                'payment_id' => $payment->id,
                'status' => $status,
                'message' => $message,
                'provider' => $payment->provider,
                'provider_reference' => $payment->provider_reference,
                'amount' => $payment->amount,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Exception $e) {
            Log::info('Réconciliation', [
                'payment_id' => $payment->id,
                'status' => $status,
                'message' => $message,
            ]);
        }
    }

    protected function firstNonEmptyProviderText(
        array $candidates,
        ?string $default = null,
        array $ignoredValues = []
    ): ?string {
        $normalizedIgnored = array_map('strtolower', $ignoredValues);

        foreach ($candidates as $candidate) {
            $value = trim((string) $candidate);
            if ($value === '' || in_array(strtolower($value), $normalizedIgnored, true)) {
                continue;
            }

            return $value;
        }

        return $default;
    }
}
