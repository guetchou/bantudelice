<?php

namespace App\Services;

use App\Domain\Payment\MtnErrorCatalog;
use App\Domain\Payment\PaymentGatewayFactory;
use App\Services\DisbursementService;
use App\Domain\Payment\ValueObjects\GatewayStatus;
use App\Payment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Service de réconciliation automatique des paiements.
 *
 * Phase 3 : getProviderStatus() passe désormais par PaymentGatewayFactory.
 * Les statuts PSP sont obtenus via PaymentGatewayFactory (adapters).
 */
class PaymentReconciliationService
{
    public function __construct(
        protected ?PaymentGatewayFactory $gatewayFactory = null,
    ) {}

    protected function gateway(): PaymentGatewayFactory
    {
        return $this->gatewayFactory ?? app(PaymentGatewayFactory::class);
    }

    // =========================================================================
    // API publique
    // =========================================================================

    public function reconcile(Payment $payment): array
    {
        if ($payment->status === 'PAID') {
            return $this->verifyPaidPayment($payment);
        }

        if ($payment->status === 'PENDING') {
            return $this->checkPendingPayment($payment);
        }

        return [
            'reconciled' => false,
            'status'     => $payment->status,
            'message'    => 'Statut non réconciliable : ' . $payment->status,
        ];
    }

    public function reconcilePendingPayments(int $limit = 50): array
    {
        $pendingPayments = Payment::where('status', 'PENDING')
            ->where('created_at', '>=', now()->subDays(7))
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();

        $processed  = 0;
        $reconciled = 0;
        $failed     = 0;

        foreach ($pendingPayments as $payment) {
            $processed++;
            $result = $this->reconcile($payment);
            if ($result['reconciled']) {
                $reconciled++;
            } elseif ($result['status'] === 'FAILED') {
                $failed++;
            }
        }

        return compact('processed', 'reconciled', 'failed');
    }

    public function backfillFailedPaymentDiagnostics(int $limit = 50, ?int $paymentId = null, bool $dryRun = false): array
    {
        $payments = $this->loadFailedDiagnosticCandidates($limit, $paymentId);
        $result   = ['processed' => 0, 'updated' => 0, 'skipped' => 0, 'errors' => 0, 'items' => []];

        foreach ($payments as $payment) {
            $result['processed']++;

            if (! $this->shouldBackfillFailureDiagnostics($payment)) {
                $result['skipped']++;
                continue;
            }

            try {
                // Interroger l'adapter directement
                $gatewayStatus = $this->getAdapterStatus($payment);
                $failureMeta   = $this->failureMetaFromGatewayStatus($gatewayStatus);

                if (empty($failureMeta)) {
                    $failureMeta = $this->buildHistoricalFailureFallback($payment, $gatewayStatus);
                }

                $providerState = $gatewayStatus->providerStatus ?? $gatewayStatus->status;

                if (
                    ! in_array(strtoupper((string) $providerState), ['FAILED', 'CANCELLED', 'REJECTED', 'DECLINED', 'EXPIRED'], true)
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
                    $this->persistFailedPaymentDiagnostics($payment, $gatewayStatus, $failureMeta);
                }

                $result['updated']++;
                $result['items'][] = [
                    'payment_id'         => $payment->id,
                    'provider_reference' => $payment->provider_reference,
                    'failure_reason'     => $failureMeta['failure_reason'] ?? null,
                    'failure_message'    => $failureMeta['failure_message'] ?? null,
                    'failure_action'     => $failureMeta['failure_action'] ?? null,
                    'dry_run'            => $dryRun,
                ];
            } catch (\Exception $e) {
                $result['errors']++;
                Log::error('Erreur backfill diagnostic paiement', [
                    'payment_id'         => $payment->id,
                    'provider_reference' => $payment->provider_reference,
                    'error'              => $e->getMessage(),
                ]);
            }
        }

        return $result;
    }

    // =========================================================================
    // Vérification / réconciliation
    // =========================================================================

    protected function verifyPaidPayment(Payment $payment): array
    {
        try {
            $gatewayStatus = $this->getAdapterStatus($payment);

            if ($gatewayStatus->isPaid()) {
                $this->logReconciliation($payment, 'VERIFIED', 'Paiement confirmé auprès du provider');
                return ['reconciled' => true, 'status' => 'VERIFIED', 'message' => 'Paiement confirmé'];
            }

            if ($gatewayStatus->isFailed()) {
                Log::warning('Incohérence détectée : PAID en DB mais FAILED chez provider', [
                    'payment_id'      => $payment->id,
                    'provider_status' => $gatewayStatus->providerStatus,
                ]);
                $this->logReconciliation($payment, 'INCONSISTENT', 'PAID en DB mais FAILED chez provider');
                return ['reconciled' => false, 'status' => 'INCONSISTENT', 'message' => 'Incohérence : PAID en DB mais FAILED chez provider'];
            }

            return ['reconciled' => false, 'status' => 'UNKNOWN', 'message' => 'Statut provider inconnu : ' . $gatewayStatus->providerStatus];
        } catch (\Exception $e) {
            Log::error('Erreur vérification paiement PAID', ['payment_id' => $payment->id, 'error' => $e->getMessage()]);
            return ['reconciled' => false, 'status' => 'ERROR', 'message' => 'Erreur : ' . $e->getMessage()];
        }
    }

    protected function checkPendingPayment(Payment $payment): array
    {
        try {
            $gatewayStatus = $this->getAdapterStatus($payment);

            if ($gatewayStatus->isPaid()) {
                Log::info('Paiement PENDING en DB mais PAID chez provider — mise à jour', ['payment_id' => $payment->id]);

                $paymentService = $this->makePaymentService();
                $paymentService->markPaymentAsPaid($payment, [
                    'provider_status' => $this->buildProviderStatusSnapshot($gatewayStatus),
                    'reconciled'      => true,
                ]);

                $this->updateReconciliationMeta($payment->fresh() ?? $payment, 'RECONCILED', $gatewayStatus);
                $this->logReconciliation($payment, 'RECONCILED', 'Paiement PENDING mis à jour vers PAID');
                return ['reconciled' => true, 'status' => 'RECONCILED', 'message' => 'Paiement réconcilié et mis à jour'];
            }

            if ($gatewayStatus->isFailed()) {
                $failureMeta = $this->failureMetaFromGatewayStatus($gatewayStatus);

                $payment->update(['status' => 'FAILED']);
                $this->updateReconciliationMeta($payment, 'FAILED', $gatewayStatus, array_filter([
                    'failed_at'      => now()->toIso8601String(),
                    'failure_reason' => $gatewayStatus->failureReason ?? $this->extractProviderFailureReason($gatewayStatus) ?? 'Paiement échoué',
                ]) + $failureMeta);

                $this->logReconciliation($payment, 'FAILED', 'Paiement échoué chez provider');
                return ['reconciled' => true, 'status' => 'FAILED', 'message' => 'Paiement échoué'];
            }

            return ['reconciled' => false, 'status' => 'PENDING', 'message' => 'Paiement toujours en attente'];
        } catch (\Exception $e) {
            Log::error('Erreur vérification paiement PENDING', ['payment_id' => $payment->id, 'error' => $e->getMessage()]);
            return ['reconciled' => false, 'status' => 'ERROR', 'message' => 'Erreur : ' . $e->getMessage()];
        }
    }

    // =========================================================================
    // Accès aux adapters — cœur de la phase 3
    // =========================================================================

    /**
     * Interroger le PSP et retourner un GatewayStatus typé.
     *
     * Délègue à getProviderStatus() pour permettre aux sous-classes de test
     * de stuber la réponse du provider sans toucher à la factory.
     */
    protected function getAdapterStatus(Payment $payment): GatewayStatus
    {
        $raw = $this->getProviderStatus($payment);
        return $this->gatewayStatusFromProviderArray($raw);
    }

    /**
     * Interroger le PSP via la factory — implémentation réelle.
     * Peut être surchargée dans les tests pour injecter un statut fictif.
     */
    protected function getProviderStatus(Payment $payment): array
    {
        $reference = (string) ($payment->provider_reference ?? '');

        if ($reference === '') {
            return ['status' => 'UNKNOWN', 'provider_status' => 'MISSING_REFERENCE', 'data' => [], 'message' => 'Référence provider manquante'];
        }

        $adapter = $this->gateway()->for($payment->provider);
        $gs      = $adapter->checkStatus($reference);

        return [
            'status'          => $gs->status,
            'provider_status' => $gs->providerStatus,
            'provider'        => $payment->provider,
            'reason'          => $gs->failureReason,
            'action'          => $gs->failureAction,
            'message'         => $gs->failureReason ?? ($gs->meta['message'] ?? null),
            'data'            => $gs->meta,
        ];
    }

    /**
     * Convertit le tableau legacy ['status', 'reason', 'action', 'data', ...]
     * en GatewayStatus typé. Gère les tableaux venant soit des adapters,
     * soit des faux injectés par les tests.
     */
    private function gatewayStatusFromProviderArray(array $raw): GatewayStatus
    {
        // Résoudre le statut normalisé depuis plusieurs sources possibles
        $status = strtoupper((string) (
            $raw['status']
            ?? $raw['provider_status']
            ?? data_get($raw, 'data.status')
            ?? 'UNKNOWN'
        ));

        // Résoudre le statut "brut" PSP
        $providerStatus = (string) (
            $raw['provider_status']
            ?? data_get($raw, 'data.status')
            ?? $status
        );

        // Résoudre la raison d'échec depuis toutes les sources possibles
        $reason = $this->firstNonEmptyProviderText([
            $raw['reason'] ?? null,
            data_get($raw, 'data.reason'),
        ], null, ['Statut récupéré', 'Impossible de vérifier le statut']);

        $action  = $raw['action'] ?? null;
        $message = $raw['message'] ?? null;

        // Si la raison est un code catalogue MTN, enrichir via buildFailureMetadata
        if ($reason !== null && MtnErrorCatalog::has($reason)) {
            $fm      = DisbursementService::buildFailureMetadata('mtn_momo', $raw);
            $reason  = $fm['failure_reason']  ?? $reason;
            $message = $fm['failure_message'] ?? $message;
            $action  = $fm['failure_action']  ?? $action;
        }

        // failure_message est séparé de failure_reason dans le catalogue MTN
        // (reason = code machine, message = texte humain lisible)
        $meta = array_merge($raw, array_filter([
            'failure_reason'  => $reason,
            'failure_message' => $message,
            'failure_action'  => $action,
            'provider_status' => $providerStatus,
        ], fn($v) => $v !== null));

        return match (true) {
            in_array($status, ['PAID', 'SUCCESS', 'SUCCESSFUL', 'COMPLETED', 'CAPTURED', 'APPROVED'], true)
                => GatewayStatus::paid(array_merge($raw, ['provider_status' => $providerStatus]), $providerStatus),
            in_array($status, ['FAILED', 'CANCELLED', 'REJECTED', 'DECLINED', 'EXPIRED'], true)
                => GatewayStatus::failed($providerStatus, $reason, $action, $meta),
            $status === 'ERROR'
                => GatewayStatus::unknown('ERROR', $raw),
            default
                => GatewayStatus::pending($providerStatus, $raw),
        };
    }

    // =========================================================================
    // Helpers de métadonnées d'échec
    // =========================================================================

    /**
     * Extraire les metadata d'échec depuis un GatewayStatus typé.
     * Remplace DisbursementService::buildFailureMetadata().
     *
     * failure_message est stocké dans meta['failure_message'] quand il diffère de failure_reason
     * (cas catalogue MTN : reason=code, message=texte humain).
     */
    protected function failureMetaFromGatewayStatus(GatewayStatus $gs): array
    {
        $failureMessage = $gs->meta['failure_message'] ?? $gs->failureReason;

        return array_filter([
            'failure_reason'  => $gs->failureReason,
            'failure_message' => $failureMessage,
            'failure_action'  => $gs->failureAction,
        ], fn($v) => $v !== null && $v !== '');
    }

    protected function buildHistoricalFailureFallback(Payment $payment, GatewayStatus $gatewayStatus): array
    {
        if ($gatewayStatus->status !== GatewayStatus::UNKNOWN && $gatewayStatus->status !== 'ERROR') {
            return [];
        }

        $manualNote = trim((string) data_get($payment->meta, 'manual_note', ''));
        if ($manualNote === '') {
            return [];
        }

        return [
            'failure_reason'  => 'MTN_STATUS_UNAVAILABLE',
            'failure_message' => $manualNote,
            'failure_action'  => 'Relancez une nouvelle requête de paiement MTN si le client souhaite réessayer.',
        ];
    }

    protected function extractProviderFailureReason(GatewayStatus $gs): ?string
    {
        return $gs->failureReason;
    }

    // =========================================================================
    // Métadonnées de réconciliation en base
    // =========================================================================

    protected function updateReconciliationMeta(Payment $payment, string $reconciliationStatus, GatewayStatus $gatewayStatus, array $extraMeta = []): void
    {
        $payment->update([
            'meta' => array_merge($payment->meta ?? [], [
                'reconciled_at'          => now()->toIso8601String(),
                'reconciliation_status'  => $reconciliationStatus,
                'provider_status'        => $this->buildProviderStatusSnapshot($gatewayStatus),
            ], $extraMeta),
        ]);
    }

    protected function buildProviderStatusSnapshot(GatewayStatus $gatewayStatus): array
    {
        return array_filter([
            'status'          => $gatewayStatus->status,
            'provider_status' => $gatewayStatus->providerStatus,
            'reason'          => $gatewayStatus->failureReason,
            'action'          => $gatewayStatus->failureAction,
            'message'         => $gatewayStatus->failureReason,
        ], fn($v) => $v !== null);
    }

    protected function persistFailedPaymentDiagnostics(Payment $payment, GatewayStatus $gatewayStatus, array $failureMeta): void
    {
        $existingMeta = $payment->meta ?? [];

        $this->updateReconciliationMeta($payment, 'FAILED', $gatewayStatus, [
            'failed_at'     => data_get($existingMeta, 'failed_at') ?? now()->toIso8601String(),
            'reconciled_at' => data_get($existingMeta, 'reconciled_at') ?? now()->toIso8601String(),
        ] + $failureMeta);
    }

    // =========================================================================
    // Helpers divers
    // =========================================================================

    protected function makePaymentService(): PaymentService
    {
        return new PaymentService();
    }

    protected function loadFailedDiagnosticCandidates(int $limit, ?int $paymentId = null)
    {
        if ($paymentId !== null) {
            $payment = Payment::find($paymentId);
            return $payment ? collect([$payment]) : collect();
        }

        return Payment::query()
            ->where('provider', 'momo')
            ->where('status', 'FAILED')
            ->whereNotNull('provider_reference')
            ->orderBy('id')
            ->limit($limit)
            ->get();
    }

    protected function shouldBackfillFailureDiagnostics(Payment $payment): bool
    {
        if ($payment->provider !== 'momo' || $payment->status !== 'FAILED' || ! $payment->provider_reference) {
            return false;
        }

        $failureReason  = trim((string) data_get($payment->meta, 'failure_reason', ''));
        $failureMessage = trim((string) data_get($payment->meta, 'failure_message', ''));
        $failureAction  = trim((string) data_get($payment->meta, 'failure_action', ''));

        return $failureReason === ''
            || $failureReason === 'Statut récupéré'
            || $failureMessage === ''
            || $failureAction === '';
    }

    protected function logReconciliation(Payment $payment, string $status, string $message): void
    {
        try {
            DB::table('payment_reconciliation_logs')->insert([
                'payment_id'         => $payment->id,
                'status'             => $status,
                'message'            => $message,
                'provider'           => $payment->provider,
                'provider_reference' => $payment->provider_reference,
                'amount'             => $payment->amount,
                'created_at'         => now(),
                'updated_at'         => now(),
            ]);
        } catch (\Exception $e) {
            Log::info('Réconciliation', [
                'payment_id' => $payment->id,
                'status'     => $status,
                'message'    => $message,
            ]);
        }
    }

    protected function firstNonEmptyProviderText(array $candidates, ?string $default = null, array $ignoredValues = []): ?string
    {
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
