<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Réconcilie les reversements restaurants/livreurs avec le statut réel MTN.
 *
 * Les tables historiques ne disposent pas encore d'un ledger de tentatives ;
 * ce service sécurise leur fonctionnement actuel en utilisant transaction_id
 * comme référence provider et en ne faisant jamais confiance au statut brut
 * reçu dans un callback.
 */
class DisbursementReconciliationService
{
    private const TABLES = [
        'restaurant_payments',
        'driver_payments',
    ];

    public function reconcilePending(int $limitPerTable = 50): array
    {
        $result = [
            'processed' => 0,
            'paid' => 0,
            'failed' => 0,
            'pending' => 0,
            'errors' => 0,
        ];

        foreach (self::TABLES as $table) {
            if (!Schema::hasTable($table)) {
                continue;
            }

            $rows = DB::table($table)
                ->where('status', 'pending')
                ->whereNotNull('transaction_id')
                ->where('transaction_id', '!=', '')
                ->orderBy('updated_at')
                ->limit(max(1, $limitPerTable))
                ->get();

            foreach ($rows as $row) {
                $reference = trim((string) $row->transaction_id);

                // Les références manuelles ou les identifiants de demande REQ-* ne
                // doivent pas être envoyés à l'endpoint de statut MTN.
                if (!Str::isUuid($reference)) {
                    continue;
                }

                $result['processed']++;

                try {
                    $status = DisbursementService::checkDisbursementStatus('mtn_momo', $reference);
                    $normalized = strtoupper((string) ($status['status'] ?? 'UNKNOWN'));

                    if ($this->isSuccessful($normalized)) {
                        $this->updateStatus($table, (int) $row->id, 'paid');
                        $result['paid']++;
                        continue;
                    }

                    if ($this->isFailed($normalized)) {
                        $this->updateStatus($table, (int) $row->id, 'failed');
                        $result['failed']++;
                        continue;
                    }

                    $result['pending']++;
                } catch (\Throwable $e) {
                    $result['errors']++;
                    Log::error('Erreur de réconciliation d’un décaissement', [
                        'table' => $table,
                        'payout_id' => $row->id,
                        'reference' => $reference,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        return $result;
    }

    /**
     * Traite un callback comme un simple signal : le statut est toujours relu
     * auprès de MTN avant toute mise à jour financière.
     */
    public function handleCallback(array $references): ?array
    {
        $references = array_values(array_unique(array_filter(array_map(
            static fn($value) => trim((string) $value),
            $references
        ), static fn($value) => $value !== '')));

        if ($references === []) {
            return null;
        }

        foreach (self::TABLES as $table) {
            if (!Schema::hasTable($table)) {
                continue;
            }

            $row = DB::table($table)
                ->whereIn('transaction_id', $references)
                ->latest('id')
                ->first();

            if (!$row) {
                continue;
            }

            $reference = trim((string) $row->transaction_id);
            if (!Str::isUuid($reference)) {
                return [
                    'handled' => false,
                    'status' => 'INVALID_REFERENCE',
                    'table' => $table,
                    'payout_id' => (int) $row->id,
                ];
            }

            $providerStatus = DisbursementService::checkDisbursementStatus('mtn_momo', $reference);
            $normalized = strtoupper((string) ($providerStatus['status'] ?? 'UNKNOWN'));

            if ($this->isSuccessful($normalized)) {
                $this->updateStatus($table, (int) $row->id, 'paid');
            } elseif ($this->isFailed($normalized)) {
                $this->updateStatus($table, (int) $row->id, 'failed');
            }

            Log::info('Callback de décaissement réconcilié', [
                'table' => $table,
                'payout_id' => $row->id,
                'reference' => $reference,
                'provider_status' => $normalized,
            ]);

            return [
                'handled' => true,
                'status' => $normalized,
                'table' => $table,
                'payout_id' => (int) $row->id,
                'reference' => $reference,
                'provider' => $providerStatus,
            ];
        }

        return null;
    }

    private function updateStatus(string $table, int $id, string $status): void
    {
        $updates = [
            'status' => $status,
            'updated_at' => now(),
        ];

        if ($status === 'paid' && Schema::hasColumn($table, 'paid_at')) {
            $updates['paid_at'] = now();
        }

        DB::table($table)
            ->where('id', $id)
            ->whereIn('status', ['pending', 'processing', 'failed'])
            ->update($updates);
    }

    private function isSuccessful(string $status): bool
    {
        return in_array($status, [
            'SUCCESSFUL', 'SUCCESS', 'PAID', 'COMPLETED', 'APPROVED',
        ], true);
    }

    private function isFailed(string $status): bool
    {
        return in_array($status, [
            'FAILED', 'REJECTED', 'DECLINED', 'CANCELLED', 'EXPIRED',
        ], true);
    }
}
