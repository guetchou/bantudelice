<?php

namespace App\Services;

use App\FinancialStateTransition;
use Illuminate\Support\Facades\DB;

class FinancialStateTransitionService
{
    public function record(
        string $subjectType,
        int $subjectId,
        ?string $fromStatus,
        string $toStatus,
        array $context = [],
    ): FinancialStateTransition {
        if ($subjectId <= 0 || trim($subjectType) === '' || trim($toStatus) === '') {
            throw new \DomainException('Le sujet et le statut cible sont obligatoires.');
        }

        $idempotencyKey = $context['idempotency_key'] ?? null;
        $attributes = [
            'subject_type' => trim($subjectType),
            'subject_id' => $subjectId,
            'from_status' => $fromStatus,
            'to_status' => trim($toStatus),
            'source' => $context['source'] ?? 'system',
            'reason' => $context['reason'] ?? null,
            'actor_id' => $context['actor_id'] ?? null,
            'idempotency_key' => $idempotencyKey,
            'occurred_at' => $context['occurred_at'] ?? now(),
            'context' => $context['context'] ?? [],
        ];

        if (!$idempotencyKey) {
            return FinancialStateTransition::create($attributes);
        }

        return DB::transaction(function () use ($idempotencyKey, $attributes) {
            $existing = FinancialStateTransition::where('idempotency_key', $idempotencyKey)
                ->lockForUpdate()
                ->first();

            return $existing ?: FinancialStateTransition::create($attributes);
        });
    }
}
