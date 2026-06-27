<?php

namespace App\Services;

use App\PartnerWithdrawal;
use App\Jobs\ReconcileWithdrawalJob;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PartnerWithdrawalService
{
    const MIN_AMOUNT = 500;

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

                $externalRef = 'WD-' . strtoupper(Str::random(12)) . '-' . time();

                return PartnerWithdrawal::create([
                    'partner_type'      => $partnerType,
                    'partner_id'        => $partnerId,
                    'operator'          => $operator,
                    'provider'          => 'mtn_momo',
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

        // 5. Call MTN outside the transaction (network I/O)
        try {
            $disbursement = DisbursementService::initiateDisbursement($phone, $amount, [
                'external_reference' => $withdrawal->external_reference,
                'payer_message'      => 'Retrait ' . $partnerType . ' BantuDelice',
                'payee_note'         => 'Retrait BantuDelice',
            ]);
        } catch (\Exception $e) {
            Log::error('PartnerWithdrawalService: MTN call failed', ['id' => $withdrawal->id, 'error' => $e->getMessage()]);
            $this->markUnknown($withdrawal, 'Exception lors de l\'appel MTN: ' . $e->getMessage());
            ReconcileWithdrawalJob::dispatch($withdrawal->id)->delay(now()->addMinutes(2));
            return $this->formatResponse($withdrawal->fresh());
        }

        if (!($disbursement['success'] ?? false)) {
            $this->markFailed($withdrawal, $disbursement);
            return $this->formatResponse($withdrawal->fresh());
        }

        // 6. Record MTN reference and mark submitted
        $providerRef = $disbursement['provider_reference'] ?? null;
        $withdrawal->update([
            'status'             => 'submitted',
            'provider_reference' => $providerRef,
        ]);

        // 7. Quick poll (2 attempts × 1.5s = 3s max) — best-effort only
        if ($providerRef) {
            $settlement = DisbursementService::waitForDisbursementFinalStatus('mtn_momo', $providerRef, 2, 1500);
            $this->applySettlementStatus($withdrawal, $settlement);
        }

        // 8. Dispatch async reconciliation job regardless
        ReconcileWithdrawalJob::dispatch($withdrawal->id)->delay(now()->addMinutes(3));

        return $this->formatResponse($withdrawal->fresh());
    }

    // ─── Called by ReconcileWithdrawalJob ──────────────────────────────────

    public function reconcile(int $withdrawalId): void
    {
        $withdrawal = PartnerWithdrawal::find($withdrawalId);
        if (!$withdrawal || $withdrawal->isPaid() || $withdrawal->isFailed()) {
            return; // already terminal
        }

        $ref = $withdrawal->provider_reference;
        if (!$ref) {
            $this->markFailed($withdrawal, ['error' => 'Aucune référence MTN — impossible de réconcilier.', 'failure_code' => 'NO_PROVIDER_REF']);
            return;
        }

        $status = DisbursementService::checkDisbursementStatus('mtn_momo', $ref);
        $this->applySettlementStatus($withdrawal, $status);

        $withdrawal->update(['reconciled_at' => now()]);

        Log::info('PartnerWithdrawalService: reconcile', [
            'id'     => $withdrawal->id,
            'status' => $withdrawal->fresh()->status,
        ]);
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
