<?php

namespace App\Services;

use App\Domain\GePay\Enums\TransactionStatus;
use App\Domain\GePay\Enums\TransactionType;
use App\Domain\GePay\Models\GePayTransaction;
use App\Domain\GePay\Services\GePayGateway;
use App\Domain\GePay\Services\GePayInternalClientResolver;
use App\Jobs\ReconcileWithdrawalJob;
use App\PartnerWithdrawal;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;

class PartnerWithdrawalService
{
    const MIN_AMOUNT = 500;

    public function __construct(
        private readonly GePayGateway $gePayGateway,
        private readonly GePayInternalClientResolver $gePayResolver,
    ) {}

    // ─── Public entry point ────────────────────────────────────────────────

    /**
     * Initiate a self-service withdrawal. Idempotent: same idempotency_key
     * returns the existing record without re-calling MTN.
     */
    public function initiate(
        string $partnerType,
        int    $partnerId,
        int    $amount,
        string $phone,
        string $idempotencyKey
    ): array {
        // 1. Idempotency guard
        $existing = PartnerWithdrawal::where('idempotency_key', $idempotencyKey)->first();
        if ($existing) {
            return $this->formatResponse($existing, reused: true);
        }

        // 2. Validate amount
        if ($amount < self::MIN_AMOUNT) {
            return $this->error('Le montant minimum est de ' . number_format(self::MIN_AMOUNT, 0, ',', ' ') . ' FCFA.', 422);
        }

        // 3. Validate operator support
        $operator = $this->detectOperator($phone);
        if ($operator !== 'mtn') {
            return $this->error('Seul MTN MoMo est disponible pour les retraits automatiques. Airtel Money sera disponible prochainement.', 422);
        }

        // 4. DB transaction: lock, check balance, reserve
        try {
            $withdrawal = DB::transaction(function () use ($partnerType, $partnerId, $amount, $phone, $operator, $idempotencyKey) {
                // Lock the partner row to prevent concurrent withdrawals
                $this->lockPartner($partnerType, $partnerId);

                // Recalculate available inside the lock
                $available = $this->getAvailableBalance($partnerType, $partnerId);

                if ($amount > $available) {
                    throw new \DomainException('Solde insuffisant. Disponible : ' . number_format($available, 0, ',', ' ') . ' FCFA.');
                }

                // Check no other pending withdrawal
                $hasPending = PartnerWithdrawal::where('partner_type', $partnerType)
                    ->where('partner_id', $partnerId)
                    ->whereIn('status', ['created', 'reserved', 'submitted', 'pending'])
                    ->lockForUpdate()
                    ->exists();

                if ($hasPending) {
                    throw new \DomainException('Un retrait est déjà en cours de traitement. Veuillez patienter.');
                }

                $useGePay    = config('gepay.bantudelice.withdrawals_enabled', false);
                $externalRef = $useGePay ? null : 'WD-' . strtoupper(Str::random(12)) . '-' . time();

                return PartnerWithdrawal::create([
                    'partner_type'      => $partnerType,
                    'partner_id'        => $partnerId,
                    'operator'          => $operator,
                    'provider'          => $useGePay ? 'gepay' : 'mtn_momo',
                    'phone'             => $phone,
                    'requested_amount'  => $amount,
                    'fee_amount'        => 0,
                    'net_amount'        => $amount,
                    'currency'          => 'XAF',
                    'status'            => 'reserved',
                    'external_reference'=> $externalRef,
                    'idempotency_key'   => $idempotencyKey,
                    'source'            => 'self_service',
                    'initiated_at'      => now(),
                ]);
            });
        } catch (\DomainException $e) {
            $code = str_contains($e->getMessage(), 'déjà en cours') ? 409 : 422;
            return $this->error($e->getMessage(), $code);
        } catch (\Exception $e) {
            Log::error('PartnerWithdrawalService: transaction failed', ['error' => $e->getMessage()]);
            return $this->error('Une erreur technique est survenue. Veuillez réessayer.', 500);
        }

        // 5. Appel réseau hors transaction — routé selon feature flag
        if ($withdrawal->provider === 'gepay') {
            return $this->initiateViaGePay($withdrawal, $phone, $amount, $partnerType);
        }

        return $this->initiateViaDisbursement($withdrawal, $phone, $amount);
    }

    // ─── Testable GePay path (also used internally) ────────────────────────

    public function initiateForWithdrawal(PartnerWithdrawal $withdrawal, string $phone, int $amount, string $partnerType): array
    {
        return $this->initiateViaGePay($withdrawal, $phone, $amount, $partnerType);
    }

    // ─── Called by ReconcileWithdrawalJob ──────────────────────────────────

    public function reconcile(int $withdrawalId): void
    {
        $withdrawal = PartnerWithdrawal::find($withdrawalId);
        if (! $withdrawal || $withdrawal->isPaid() || $withdrawal->isFailed()) {
            return;
        }

        if ($withdrawal->provider === 'gepay') {
            $this->reconcileViaGePay($withdrawal);
        } else {
            $this->reconcileViaDisbursement($withdrawal);
        }

        $withdrawal->update(['reconciled_at' => now()]);

        Log::info('PartnerWithdrawalService: reconcile', [
            'id'       => $withdrawal->id,
            'provider' => $withdrawal->provider,
            'status'   => $withdrawal->fresh()->status,
        ]);
    }

    private function reconcileViaGePay(PartnerWithdrawal $withdrawal): void
    {
        $gePayUuid = $withdrawal->provider_reference;
        if (! $gePayUuid) {
            $this->markFailed($withdrawal, ['error' => 'Aucune référence GePay — impossible de réconcilier.', 'failure_code' => 'NO_GEPAY_REF']);
            return;
        }

        $transaction = GePayTransaction::where('uuid', $gePayUuid)->first();
        if (! $transaction) {
            $this->markFailed($withdrawal, ['error' => 'Transaction GePay introuvable : ' . $gePayUuid, 'failure_code' => 'GEPAY_NOT_FOUND']);
            return;
        }

        if (! $transaction->status->isTerminal()) {
            $transaction = $this->gePayGateway->refresh($transaction);
        }

        $this->applyGePayStatus($withdrawal, $transaction);
    }

    private function reconcileViaDisbursement(PartnerWithdrawal $withdrawal): void
    {
        $ref = $withdrawal->provider_reference;
        if (! $ref) {
            $this->markFailed($withdrawal, ['error' => 'Aucune référence MTN — impossible de réconcilier.', 'failure_code' => 'NO_PROVIDER_REF']);
            return;
        }

        $status = DisbursementService::checkDisbursementStatus('mtn_momo', $ref);
        $this->applySettlementStatus($withdrawal, $status);
    }

    private function initiateViaGePay(PartnerWithdrawal $withdrawal, string $phone, int $amount, string $partnerType): array
    {
        try {
            $client = $this->gePayResolver->resolve();
        } catch (RuntimeException $e) {
            Log::error('PartnerWithdrawalService: GePay resolver failed', ['id' => $withdrawal->id, 'error' => $e->getMessage()]);
            $this->markUnknown($withdrawal, 'GePay non configuré : ' . $e->getMessage());
            ReconcileWithdrawalJob::dispatch($withdrawal->id)->delay(now()->addMinutes(2));
            return $this->formatResponse($withdrawal->fresh());
        }

        $gePayExternalRef = 'WITHDRAWAL-' . $withdrawal->uuid;
        $gePayIdemKey     = 'partner-withdrawal:' . $withdrawal->uuid . ':disbursement';

        // Mettre à jour l'external_reference avec la valeur GePay déterministe
        $withdrawal->update(['external_reference' => $gePayExternalRef]);

        try {
            $transaction = $this->gePayGateway->initiate(
                client: $client,
                type: TransactionType::DISBURSEMENT,
                payload: [
                    'amount'             => $amount,
                    'phone'              => $phone,
                    'currency'           => 'XAF',
                    'external_reference' => $gePayExternalRef,
                    'payer_message'      => 'Retrait ' . $partnerType . ' BantuDelice',
                    'payee_note'         => 'Retrait BantuDelice',
                ],
                idempotencyKey: $gePayIdemKey,
            );
        } catch (RuntimeException $e) {
            Log::error('PartnerWithdrawalService: GePay initiate failed', ['id' => $withdrawal->id, 'error' => $e->getMessage()]);
            $this->markUnknown($withdrawal, 'Exception GePay : ' . $e->getMessage());
            ReconcileWithdrawalJob::dispatch($withdrawal->id)->delay(now()->addMinutes(2));
            return $this->formatResponse($withdrawal->fresh());
        }

        // Stocker l'UUID GePay (pas la référence MTN) dans provider_reference
        $withdrawal->update([
            'status'             => 'submitted',
            'provider_reference' => $transaction->uuid,
        ]);

        // Appliquer immédiatement le statut GePay reçu
        $this->applyGePayStatus($withdrawal, $transaction);

        ReconcileWithdrawalJob::dispatch($withdrawal->id)->delay(now()->addMinutes(3));

        return $this->formatResponse($withdrawal->fresh());
    }

    private function initiateViaDisbursement(PartnerWithdrawal $withdrawal, string $phone, int $amount): array
    {
        try {
            $disbursement = DisbursementService::initiateDisbursement($phone, $amount, [
                'external_reference' => $withdrawal->external_reference,
                'payer_message'      => 'Retrait partenaire BantuDelice',
                'payee_note'         => 'Retrait BantuDelice',
            ]);
        } catch (\Exception $e) {
            Log::error('PartnerWithdrawalService: MTN call failed', ['id' => $withdrawal->id, 'error' => $e->getMessage()]);
            $this->markUnknown($withdrawal, 'Exception lors de l\'appel MTN : ' . $e->getMessage());
            ReconcileWithdrawalJob::dispatch($withdrawal->id)->delay(now()->addMinutes(2));
            return $this->formatResponse($withdrawal->fresh());
        }

        if (! ($disbursement['success'] ?? false)) {
            $this->markFailed($withdrawal, $disbursement);
            return $this->formatResponse($withdrawal->fresh());
        }

        $providerRef = $disbursement['provider_reference'] ?? null;
        $withdrawal->update([
            'status'             => 'submitted',
            'provider_reference' => $providerRef,
        ]);

        if ($providerRef) {
            $settlement = DisbursementService::waitForDisbursementFinalStatus('mtn_momo', $providerRef, 2, 1500);
            $this->applySettlementStatus($withdrawal, $settlement);
        }

        ReconcileWithdrawalJob::dispatch($withdrawal->id)->delay(now()->addMinutes(3));

        return $this->formatResponse($withdrawal->fresh());
    }

    private function applyGePayStatus(PartnerWithdrawal $withdrawal, GePayTransaction $transaction): void
    {
        match ($transaction->status) {
            TransactionStatus::SUCCESSFUL =>
                $withdrawal->update(['status' => 'paid', 'paid_at' => now()]),

            TransactionStatus::FAILED,
            TransactionStatus::CANCELLED,
            TransactionStatus::EXPIRED =>
                $this->markFailed($withdrawal, [
                    'failure_code'    => $transaction->failure_code ?? $transaction->status->value,
                    'failure_message' => $transaction->failure_message ?? 'Décaissement GePay échoué.',
                ]),

            // unknown, reversed, refunded, created, submitted, pending → ne pas libérer le solde
            default => null,
        };
    }

    // ─── Balance ───────────────────────────────────────────────────────────

    public function getAvailableBalance(string $partnerType, int $partnerId): float
    {
        if ($partnerType === 'restaurant') {
            $restaurant = \App\Restaurant::find($partnerId);
            if (!$restaurant) return 0.0;
            $dash = app(PartnerFinancialDashboardService::class)->forRestaurant($restaurant);
        } else {
            $driver = \App\Driver::find($partnerId);
            if (!$driver) return 0.0;
            $dash = app(PartnerFinancialDashboardService::class)->forDeliveryDriver($driver);
        }

        $availableCard = collect($dash['cards'])->firstWhere('label', 'Disponible au retrait');
        return (float) ($availableCard['amount'] ?? 0);
    }

    // ─── Private helpers ───────────────────────────────────────────────────

    private function lockPartner(string $partnerType, int $partnerId): void
    {
        // Lock partner row to serialize concurrent withdrawals
        if ($partnerType === 'restaurant') {
            DB::table('restaurants')->where('id', $partnerId)->lockForUpdate()->value('id');
        } else {
            DB::table('drivers')->where('id', $partnerId)->lockForUpdate()->value('id');
        }
    }

    private function applySettlementStatus(PartnerWithdrawal $withdrawal, array $status): void
    {
        $normalized = strtoupper((string) ($status['status'] ?? 'UNKNOWN'));

        if (in_array($normalized, PartnerWithdrawal::TERMINAL_SUCCESS, true)) {
            $withdrawal->update(['status' => 'paid', 'paid_at' => now()]);
            return;
        }

        if (in_array($normalized, PartnerWithdrawal::TERMINAL_FAILURE, true)) {
            $this->markFailed($withdrawal, $status);
            return;
        }

        // Truly unknown (timeout, no response) — keep reserved, job will retry
        if ($normalized !== 'PENDING') {
            $this->markUnknown($withdrawal, $status['error'] ?? $status['message'] ?? 'Statut inconnu');
        } else {
            $withdrawal->update(['status' => 'pending']);
        }
    }

    private function markFailed(PartnerWithdrawal $withdrawal, array $payload): void
    {
        $withdrawal->update([
            'status'          => 'failed',
            'failed_at'       => now(),
            'failure_code'    => $payload['failure_code'] ?? $payload['reason'] ?? null,
            'failure_message' => $payload['error'] ?? $payload['message'] ?? $payload['failure_message'] ?? 'Décaissement MTN échoué.',
        ]);
    }

    private function markUnknown(PartnerWithdrawal $withdrawal, string $reason): void
    {
        $withdrawal->update([
            'status'          => 'unknown',
            'failure_message' => $reason,
        ]);
    }

    private function detectOperator(string $phone): string
    {
        return DisbursementService::detectOperator($phone);
    }

    private function formatResponse(PartnerWithdrawal $w, bool $reused = false): array
    {
        $httpStatus = match(true) {
            $w->isPaid()    => 200,
            $w->isFailed()  => 200, // failure info returned with 200 for UX
            $w->isUnknown() => 202,
            default         => 202,
        };

        $message = match($w->status) {
            'paid'      => number_format($w->net_amount, 0, ',', ' ') . ' FCFA ont été envoyés sur votre compte MTN MoMo.',
            'failed'    => $w->failure_message ?? 'Le retrait a échoué. Votre solde n\'a pas été débité.',
            'unknown'   => 'Votre retrait est en cours de vérification. Vous serez notifié.',
            default     => 'Votre retrait est en cours de traitement.',
        };

        return [
            'success'     => !$w->isFailed(),
            'status'      => $w->status,
            'http_status' => $httpStatus,
            'message'     => $message,
            'reused'      => $reused,
            'withdrawal'  => [
                'id'          => $w->id,
                'reference'   => $w->reference(),
                'amount'      => $w->net_amount,
                'currency'    => $w->currency,
                'operator'    => $w->operator,
                'phone_masked'=> $w->maskedPhone(),
                'status'      => $w->status,
            ],
        ];
    }

    private function error(string $message, int $httpStatus): array
    {
        return [
            'success'     => false,
            'status'      => 'rejected',
            'http_status' => $httpStatus,
            'message'     => $message,
            'withdrawal'  => null,
        ];
    }
}
