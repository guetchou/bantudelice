<?php

namespace App\Domain\Payment\Services;

use App\Domain\Payment\Enums\ReconciliationCaseStatus;
use App\Domain\Payment\Enums\ReconciliationDiscrepancy;
use App\Domain\Payment\Models\PaymentReconciliationCase;
use App\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use LogicException;

class ReconciliationCaseService
{
    public function open(
        Model $subject,
        ReconciliationDiscrepancy $discrepancy,
        array $facts
    ): PaymentReconciliationCase {
        $fingerprint = $this->fingerprint($subject, $discrepancy, $facts);

        return DB::transaction(function () use ($subject, $discrepancy, $facts, $fingerprint) {
            $existing = PaymentReconciliationCase::query()
                ->where('fingerprint', $fingerprint)
                ->lockForUpdate()
                ->first();

            if ($existing) {
                return $existing;
            }

            return PaymentReconciliationCase::query()->create([
                'fingerprint' => $fingerprint,
                'subject_type' => $subject->getMorphClass(),
                'subject_id' => $subject->getKey(),
                'provider' => $facts['provider'] ?? null,
                'external_reference' => $facts['external_reference'] ?? null,
                'provider_reference' => $facts['provider_reference'] ?? null,
                'status' => ReconciliationCaseStatus::OPEN->value,
                'discrepancy_code' => $discrepancy->value,
                'expected_amount' => $this->nullableAmount($facts['expected_amount'] ?? null),
                'observed_amount' => $this->nullableAmount($facts['observed_amount'] ?? null),
                'currency' => strtoupper((string) ($facts['currency'] ?? 'XAF')),
                'expected_status' => $facts['expected_status'] ?? null,
                'observed_status' => $facts['observed_status'] ?? null,
                'evidence' => $facts['evidence'] ?? null,
                'detected_at' => $facts['detected_at'] ?? now(),
            ]);
        });
    }

    public function markInvestigating(
        PaymentReconciliationCase $case,
        array $evidence = []
    ): PaymentReconciliationCase {
        return DB::transaction(function () use ($case, $evidence) {
            $locked = PaymentReconciliationCase::query()
                ->lockForUpdate()
                ->findOrFail($case->getKey());

            if ($locked->status === ReconciliationCaseStatus::RESOLVED) {
                throw new LogicException('Un dossier résolu ne peut pas repasser en investigation.');
            }

            $locked->forceFill([
                'status' => ReconciliationCaseStatus::INVESTIGATING->value,
                'evidence' => array_merge($locked->evidence ?? [], $evidence),
            ])->save();

            return $locked->fresh();
        });
    }

    public function resolve(
        PaymentReconciliationCase $case,
        User $resolver,
        string $resolutionNote,
        array $evidence = []
    ): PaymentReconciliationCase {
        $resolutionNote = trim($resolutionNote);
        if ($resolutionNote === '') {
            throw new InvalidArgumentException('La note de résolution est obligatoire.');
        }

        return DB::transaction(function () use ($case, $resolver, $resolutionNote, $evidence) {
            $locked = PaymentReconciliationCase::query()
                ->lockForUpdate()
                ->findOrFail($case->getKey());

            if ($locked->status === ReconciliationCaseStatus::RESOLVED) {
                return $locked;
            }

            $locked->forceFill([
                'status' => ReconciliationCaseStatus::RESOLVED->value,
                'resolved_at' => now(),
                'resolved_by' => $resolver->getKey(),
                'resolution_note' => $resolutionNote,
                'evidence' => array_merge($locked->evidence ?? [], $evidence),
            ])->save();

            return $locked->fresh();
        });
    }

    private function fingerprint(
        Model $subject,
        ReconciliationDiscrepancy $discrepancy,
        array $facts
    ): string {
        return hash('sha256', implode('|', [
            $subject->getMorphClass(),
            (string) $subject->getKey(),
            $discrepancy->value,
            (string) ($facts['provider'] ?? ''),
            (string) ($facts['external_reference'] ?? ''),
            (string) ($facts['provider_reference'] ?? ''),
            (string) ($facts['expected_amount'] ?? ''),
            (string) ($facts['observed_amount'] ?? ''),
            (string) ($facts['expected_status'] ?? ''),
            (string) ($facts['observed_status'] ?? ''),
        ]));
    }

    private function nullableAmount(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        $amount = (int) $value;
        if ($amount < 0) {
            throw new InvalidArgumentException('Un montant de rapprochement ne peut pas être négatif.');
        }

        return $amount;
    }
}
