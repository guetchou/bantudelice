<?php

namespace App\Services;

use App\PaymentReconciliationCase;
use Illuminate\Support\Facades\DB;

class PaymentReconciliationCaseService
{
    public function openOrUpdate(
        string $subjectType,
        int $subjectId,
        string $caseType,
        array $finding,
    ): PaymentReconciliationCase {
        if ($subjectId <= 0) {
            throw new \DomainException('Le sujet de rapprochement est obligatoire.');
        }

        return DB::transaction(function () use ($subjectType, $subjectId, $caseType, $finding) {
            $case = PaymentReconciliationCase::where('subject_type', $subjectType)
                ->where('subject_id', $subjectId)
                ->where('case_type', $caseType)
                ->whereIn('status', ['open', 'investigating'])
                ->lockForUpdate()
                ->first();

            $attributes = [
                'severity' => $finding['severity'] ?? $this->defaultSeverity($caseType),
                'expected_amount' => $finding['expected_amount'] ?? null,
                'observed_amount' => $finding['observed_amount'] ?? null,
                'currency' => $finding['currency'] ?? 'XAF',
                'internal_status' => $finding['internal_status'] ?? null,
                'provider_status' => $finding['provider_status'] ?? null,
                'provider_reference' => $finding['provider_reference'] ?? null,
                'assigned_to' => $finding['assigned_to'] ?? null,
                'evidence' => array_merge($case?->evidence ?? [], $finding['evidence'] ?? []),
            ];

            if ($case) {
                $case->update($attributes);
                return $case->fresh();
            }

            return PaymentReconciliationCase::create(array_merge($attributes, [
                'subject_type' => $subjectType,
                'subject_id' => $subjectId,
                'case_type' => $caseType,
                'status' => 'open',
                'opened_at' => now(),
            ]));
        });
    }

    public function startInvestigation(PaymentReconciliationCase $case, int $assignedTo): PaymentReconciliationCase
    {
        if (!$case->isOpen()) {
            throw new \DomainException('Seul un dossier ouvert peut être pris en charge.');
        }

        $case->update([
            'status' => 'investigating',
            'assigned_to' => $assignedTo,
        ]);

        return $case->fresh();
    }

    public function resolve(PaymentReconciliationCase $case, string $note, ?int $actorId = null): PaymentReconciliationCase
    {
        $note = trim($note);

        if ($note === '') {
            throw new \DomainException('La note de résolution est obligatoire.');
        }

        if (!$case->isOpen()) {
            return $case;
        }

        $case->update([
            'status' => 'resolved',
            'resolved_at' => now(),
            'resolution_note' => $note,
            'evidence' => array_merge($case->evidence ?? [], [
                'resolved_by' => $actorId,
                'resolved_at' => now()->toIso8601String(),
            ]),
        ]);

        return $case->fresh();
    }

    private function defaultSeverity(string $caseType): string
    {
        return match ($caseType) {
            'duplicate', 'amount_mismatch', 'status_mismatch', 'reversed', 'missing_provider' => 'critical',
            'missing_internal', 'unknown' => 'warning',
            default => 'info',
        };
    }
}
