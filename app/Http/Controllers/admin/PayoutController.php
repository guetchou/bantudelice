<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Services\DisbursementService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PayoutController extends Controller
{
    private const AUTO_SETTLEMENT_ATTEMPTS = 3;
    private const AUTO_SETTLEMENT_DELAY_MS = 1500;
    private const PAYOUT_LOCK_SECONDS = 180;

    public function restaurant_payout()
    {
        $config = [
            'table' => 'restaurant_payments',
            'owner_table' => 'restaurants',
            'owner_key' => 'restaurant_id',
            'file_prefix' => 'restaurant_payouts_bulk_mtn',
        ];

        $requests = $this->getPendingPayouts($config);
        $history = $this->getPaidPayouts($config);

        return view('admin.payouts.restaurant_payout', compact('requests', 'history'));
    }

    public function driver_payout()
    {
        $config = [
            'table' => 'driver_payments',
            'owner_table' => 'drivers',
            'owner_key' => 'driver_id',
            'file_prefix' => 'driver_payouts_bulk_mtn',
        ];

        $requests = $this->getPendingPayouts($config);
        $history = $this->getPaidPayouts($config);

        return view('admin.payouts.driver_payout', compact('requests', 'history'));
    }

    public function exportRestaurantBulkCsv()
    {
        return $this->exportPendingBulkCsv([
            'table' => 'restaurant_payments',
            'owner_table' => 'restaurants',
            'owner_key' => 'restaurant_id',
            'file_prefix' => 'restaurant_payouts_bulk_mtn',
        ]);
    }

    public function exportDriverBulkCsv()
    {
        return $this->exportPendingBulkCsv([
            'table' => 'driver_payments',
            'owner_table' => 'drivers',
            'owner_key' => 'driver_id',
            'file_prefix' => 'driver_payouts_bulk_mtn',
        ]);
    }

    public function restaurant_pay(Request $request)
    {
        return $this->handlePayout($request, [
            'table' => 'restaurant_payments',
            'owner_table' => 'restaurants',
            'owner_key' => 'restaurant_id',
            'route' => 'restaurant_payout',
            'external_reference_prefix' => 'RESTAURANT-PAYOUT',
            'payer_message' => 'Versement restaurant',
            'payee_note' => 'Paiement restaurant',
        ]);
    }

    public function driver_pay(Request $request)
    {
        return $this->handlePayout($request, [
            'table' => 'driver_payments',
            'owner_table' => 'drivers',
            'owner_key' => 'driver_id',
            'route' => 'driver_payout',
            'external_reference_prefix' => 'DRIVER-PAYOUT',
            'payer_message' => 'Versement livreur',
            'payee_note' => 'Paiement livreur',
        ]);
    }

    private function handlePayout(Request $request, array $config)
    {
        $payload = $request->validate([
            'request_id' => 'required|integer|exists:' . $config['table'] . ',id',
            'transaction_id' => 'nullable|string|max:255',
        ]);

        $requestId = (int) $payload['request_id'];
        $lock = Cache::lock(
            'payout-execution:' . $config['table'] . ':' . $requestId,
            self::PAYOUT_LOCK_SECONDS
        );

        if (!$lock->get()) {
            return redirect()
                ->route($config['route'])
                ->with('alert', $this->buildAlert(
                    'info',
                    'Cette demande de paiement est déjà en cours de traitement.'
                ));
        }

        try {
            return $this->handleLockedPayout($payload, $config, $requestId);
        } finally {
            $lock->release();
        }
    }

    private function handleLockedPayout(array $payload, array $config, int $requestId)
    {
        $manualTransactionId = trim((string) ($payload['transaction_id'] ?? ''));

        if ($manualTransactionId !== '') {
            return $this->markPayoutAsPaid(
                $config['table'],
                $requestId,
                $manualTransactionId,
                $config['route']
            );
        }

        $payout = $this->findPayoutRecord($config, $requestId);

        if (!$payout) {
            return redirect()
                ->route($config['route'])
                ->with('alert', $this->buildAlert('danger', 'Cette demande de paiement est introuvable.'));
        }

        if ($payout->status !== 'pending') {
            return redirect()
                ->route($config['route'])
                ->with('alert', $this->buildAlert('danger', 'Cette demande de paiement a déjà été traitée.'));
        }

        $existingReference = $this->extractProviderReference($payout->transaction_id);

        if ($existingReference) {
            $existingStatus = DisbursementService::waitForDisbursementFinalStatus(
                'mtn_momo',
                $existingReference,
                $this->autoSettlementAttempts(),
                $this->autoSettlementDelayMs()
            );
            $normalizedExistingStatus = strtoupper((string) ($existingStatus['status'] ?? 'UNKNOWN'));

            if ($this->isSuccessfulProviderStatus($normalizedExistingStatus)) {
                return $this->markPayoutAsPaid(
                    $config['table'],
                    $requestId,
                    $existingReference,
                    $config['route'],
                    'Décaissement MTN confirmé avec succès.'
                );
            }

            if ($this->isPendingProviderStatus($normalizedExistingStatus)) {
                return redirect()
                    ->route($config['route'])
                    ->with('alert', $this->buildAlert(
                        'info',
                        'Le décaissement MTN est toujours en cours pour ' . $payout->name . ' (réf. ' . $existingReference . ').'
                    ));
            }

            if ($normalizedExistingStatus === 'ERROR') {
                return redirect()
                    ->route($config['route'])
                    ->with('alert', $this->buildAlert(
                        'danger',
                        $this->formatProviderMessage($existingStatus, 'Impossible de vérifier le décaissement en cours.')
                    ));
            }

            // Une nouvelle tentative n'est permise que si MTN a explicitement
            // déclaré la tentative précédente terminalement échouée.
            if (!$this->isFailedProviderStatus($normalizedExistingStatus)) {
                return redirect()
                    ->route($config['route'])
                    ->with('alert', $this->buildAlert(
                        'danger',
                        'Le statut MTN de la tentative précédente est indéterminé. Aucun nouveau transfert n’a été lancé.'
                    ));
            }
        }

        $disbursement = DisbursementService::initiateDisbursement(
            (string) $payout->phone,
            (int) $payout->payout_amount,
            [
                'external_reference' => $config['external_reference_prefix'] . '-' . $requestId . '-' . time(),
                'payer_message' => $config['payer_message'] . ' ' . $requestId,
                'payee_note' => $config['payee_note'],
            ]
        );

        if (!($disbursement['success'] ?? false)) {
            return redirect()
                ->route($config['route'])
                ->with('alert', $this->buildAlert(
                    'danger',
                    $this->formatProviderMessage($disbursement, 'Le décaissement automatique a échoué.')
                ));
        }

        $providerReference = trim((string) ($disbursement['provider_reference'] ?? ''));

        if ($providerReference === '' || !Str::isUuid($providerReference)) {
            return redirect()
                ->route($config['route'])
                ->with('alert', $this->buildAlert(
                    'danger',
                    'Le provider n\'a pas renvoyé de référence de décaissement exploitable.'
                ));
        }

        // Persister immédiatement la référence avant toute attente réseau. En cas
        // de timeout HTTP ou de redémarrage du processus, le prochain passage
        // réconciliera cette même tentative au lieu d'en créer une seconde.
        $stored = DB::table($config['table'])
            ->where('id', $requestId)
            ->where('status', 'pending')
            ->update([
                'transaction_id' => $providerReference,
                'updated_at' => now(),
            ]);

        if ($stored < 1) {
            return redirect()
                ->route($config['route'])
                ->with('alert', $this->buildAlert(
                    'danger',
                    'La demande a changé pendant le traitement. Le statut MTN doit être réconcilié avant toute nouvelle action.'
                ));
        }

        $settlement = DisbursementService::waitForDisbursementFinalStatus(
            (string) ($disbursement['provider'] ?? 'mtn_momo'),
            $providerReference,
            $this->autoSettlementAttempts(),
            $this->autoSettlementDelayMs()
        );
        $normalizedSettlementStatus = strtoupper((string) ($settlement['status'] ?? 'UNKNOWN'));

        if ($this->isSuccessfulProviderStatus($normalizedSettlementStatus)) {
            return $this->markPayoutAsPaid(
                $config['table'],
                $requestId,
                $providerReference,
                $config['route'],
                'Décaissement MTN confirmé avec succès.'
            );
        }

        if ($this->isPendingProviderStatus($normalizedSettlementStatus)) {
            return redirect()
                ->route($config['route'])
                ->with('alert', $this->buildAlert(
                    'info',
                    'Décaissement MTN lancé pour ' . $payout->name . ' (réf. ' . $providerReference . '). La réconciliation automatique suivra son statut.'
                ));
        }

        return redirect()
            ->route($config['route'])
            ->with('alert', $this->buildAlert(
                'danger',
                $this->formatProviderMessage($settlement, 'Le décaissement MTN a échoué.') . ' Réf. ' . $providerReference . '.'
            ));
    }

    private function findPayoutRecord(array $config, int $requestId)
    {
        return $this->basePayoutQuery($config)
            ->select(
                $config['table'] . '.id as request_id',
                $config['table'] . '.transaction_id',
                $config['table'] . '.status',
                $config['table'] . '.payout_amount',
                $config['owner_table'] . '.phone',
                $config['owner_table'] . '.name'
            )
            ->where($config['table'] . '.id', $requestId)
            ->first();
    }

    private function getPendingPayouts(array $config)
    {
        return $this->basePayoutQuery($config)
            ->select(
                $config['table'] . '.id as request_id',
                $config['table'] . '.status',
                $config['table'] . '.payout_amount',
                $config['table'] . '.transaction_id',
                $config['table'] . '.created_at as date',
                $config['owner_table'] . '.phone',
                $config['owner_table'] . '.email',
                $config['owner_table'] . '.address',
                $config['owner_table'] . '.name'
            )
            ->where($config['table'] . '.status', 'pending')
            ->orderByDesc($config['table'] . '.created_at')
            ->get();
    }

    private function getPaidPayouts(array $config)
    {
        return $this->basePayoutQuery($config)
            ->select(
                $config['table'] . '.id as request_id',
                $config['table'] . '.transaction_id',
                $config['table'] . '.status',
                $config['table'] . '.payout_amount',
                $config['table'] . '.created_at as date',
                $config['owner_table'] . '.phone',
                $config['owner_table'] . '.name',
                $config['owner_table'] . '.address',
                $config['owner_table'] . '.email'
            )
            ->where($config['table'] . '.status', 'paid')
            ->orderByDesc($config['table'] . '.created_at')
            ->get();
    }

    private function basePayoutQuery(array $config)
    {
        return DB::table($config['owner_table'])
            ->join($config['table'], $config['owner_table'] . '.id', '=', $config['table'] . '.' . $config['owner_key']);
    }

    private function exportPendingBulkCsv(array $config)
    {
        $rows = $this->getPendingPayouts($config);
        $fileName = $config['file_prefix'] . '_' . now()->format('Y-m-d_H-i') . '.csv';
        $headers = [
            'Content-type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename=' . $fileName,
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];
        $columns = ['Payee Name', 'MSISDN', 'Amount (FCFA)'];

        $callback = function () use ($rows, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            foreach ($rows as $row) {
                fputcsv($file, [
                    $this->formatBulkPayeeName($row->name ?? ''),
                    $this->formatBulkMsisdn($row->phone ?? ''),
                    $this->formatBulkAmount($row->payout_amount ?? 0),
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    private function markPayoutAsPaid(
        string $table,
        int $requestId,
        string $transactionId,
        string $route,
        string $successMessage = 'Demande envoyée avec succès !'
    ) {
        $affected = DB::table($table)
            ->where('id', $requestId)
            ->where('status', 'pending')
            ->update([
                'transaction_id' => $transactionId,
                'status' => 'paid',
                'updated_at' => now(),
            ]);

        return redirect()
            ->route($route)
            ->with('alert', $this->buildAlert(
                $affected > 0 ? 'success' : 'danger',
                $affected > 0
                    ? $successMessage
                    : 'Cette demande de paiement est introuvable ou déjà traitée.'
            ));
    }

    private function buildAlert(string $type, string $message): array
    {
        return [
            'type' => $type,
            'message' => $message,
        ];
    }

    private function extractProviderReference(?string $value): ?string
    {
        $value = trim((string) $value);

        return Str::isUuid($value) ? $value : null;
    }

    private function isSuccessfulProviderStatus(?string $status): bool
    {
        return in_array(strtoupper((string) $status), [
            'SUCCESSFUL', 'SUCCESS', 'PAID', 'COMPLETED', 'APPROVED',
        ], true);
    }

    private function isPendingProviderStatus(?string $status): bool
    {
        return in_array(strtoupper((string) $status), [
            'PENDING', 'INITIATED', 'PROCESSING',
        ], true);
    }

    private function isFailedProviderStatus(?string $status): bool
    {
        return in_array(strtoupper((string) $status), [
            'FAILED', 'REJECTED', 'DECLINED', 'CANCELLED', 'EXPIRED',
        ], true);
    }

    private function formatProviderMessage(array $payload, string $fallback): string
    {
        $message = trim((string) ($payload['error'] ?? $payload['message'] ?? $fallback));
        $action = trim((string) ($payload['action'] ?? ''));

        if ($action === '') {
            return $message;
        }

        return $message . ' ' . $action;
    }

    private function autoSettlementAttempts(): int
    {
        return app()->environment('testing') ? 1 : self::AUTO_SETTLEMENT_ATTEMPTS;
    }

    private function autoSettlementDelayMs(): int
    {
        return app()->environment('testing') ? 0 : self::AUTO_SETTLEMENT_DELAY_MS;
    }

    private function formatBulkPayeeName(?string $name): string
    {
        $name = Str::ascii(trim((string) $name));
        $name = preg_replace('/\s+/', ' ', $name) ?? '';

        return $name !== '' ? $name : 'Platform Payee';
    }

    private function formatBulkMsisdn(?string $phone): string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone) ?? '';

        if ($digits === '') {
            return '';
        }

        if (str_starts_with($digits, '242')) {
            return $digits;
        }

        if (str_starts_with($digits, '0')) {
            return '242' . substr($digits, 1);
        }

        return '242' . $digits;
    }

    private function formatBulkAmount($amount): string
    {
        if (!is_numeric($amount)) {
            return '0';
        }

        return (string) max(0, (int) round((float) $amount));
    }
}
