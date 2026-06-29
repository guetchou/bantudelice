<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Payment;
use App\Services\PaymentBusinessDashboardService;
use App\Services\PaymentDashboardService;
use App\Services\PaymentOperationsReconciliationService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PaymentDashboardController extends Controller
{
    public function index(
        Request $request,
        PaymentDashboardService $dashboard,
        PaymentBusinessDashboardService $businessDashboard
    ) {
        $hours = in_array((int) $request->query('hours', 12), [6, 12, 24], true)
            ? (int) $request->query('hours', 12)
            : 12;
        $filters = $request->only(['provider', 'status']);

        return view('admin.payments.dashboard', array_merge(
            $dashboard->build($hours, $filters),
            $businessDashboard->build($filters)
        ));
    }

    public function data(
        Request $request,
        PaymentDashboardService $dashboard,
        PaymentBusinessDashboardService $businessDashboard
    ) {
        $hours = in_array((int) $request->query('hours', 12), [6, 12, 24], true)
            ? (int) $request->query('hours', 12)
            : 12;
        $filters = $request->only(['provider', 'status']);

        return response()->json([
            'status' => true,
            'data' => array_merge(
                $dashboard->build($hours, $filters),
                $businessDashboard->build($filters)
            ),
        ]);
    }

    public function exportCsv(Request $request): StreamedResponse
    {
        $request->validate([
            'provider' => 'nullable|string|max:50',
            'status' => 'nullable|string|max:50',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
        ]);

        $query = Payment::with(['order', 'user'])
            ->orderByDesc('created_at');

        $provider = strtolower(trim((string) $request->query('provider', 'all')));
        if ($provider !== '' && $provider !== 'all') {
            $query->whereIn('provider', $this->rawProvidersForFilter($provider));
        }

        $status = strtolower(trim((string) $request->query('status', 'all')));
        if ($status !== '' && $status !== 'all') {
            $rawStatuses = $this->rawStatusesForFilter($status);

            $query->where(function ($statusQuery) use ($status, $rawStatuses) {
                $statusQuery->whereIn('status', $rawStatuses);

                if ($status === 'unknown') {
                    $statusQuery->orWhereNull('status');
                }
            });
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $filename = 'paiements-bantudelice-' . now()->format('Ymd-His') . '.csv';

        return response()->streamDownload(function () use ($query) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

            fputcsv($handle, [
                'ID', 'Commande', 'Utilisateur', 'Email', 'Téléphone',
                'Fournisseur', 'Référence fournisseur', 'Statut',
                'Montant', 'Devise', 'Date', 'Mis à jour',
            ], ';');

            $query->chunk(500, function ($payments) use ($handle) {
                foreach ($payments as $payment) {
                    fputcsv($handle, [
                        $payment->id,
                        optional($payment->order)->order_no ?? $payment->order_id,
                        optional($payment->user)->name ?? '',
                        optional($payment->user)->email ?? '',
                        optional($payment->user)->phone ?? data_get($payment->meta, 'phone', ''),
                        $payment->provider,
                        $payment->provider_reference ?? '',
                        $payment->status,
                        number_format((float) $payment->amount, 0, ',', ' '),
                        $payment->currency ?? 'XAF',
                        $payment->created_at?->format('d/m/Y H:i'),
                        $payment->updated_at?->format('d/m/Y H:i'),
                    ], ';');
                }
            });

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    public function reconcile(
        Request $request,
        Payment $payment,
        PaymentOperationsReconciliationService $reconciliationService
    ) {
        $result = $reconciliationService->reconcile($payment);
        $payment->refresh();

        return response()->json([
            'status' => (bool) ($result['reconciled'] ?? false),
            'message' => $result['message'] ?? 'Réconciliation terminée',
            'result' => $result,
            'payment' => [
                'id' => $payment->id,
                'status' => $payment->status,
                'provider' => $payment->provider,
                'provider_reference' => $payment->provider_reference,
                'updated_at' => $payment->updated_at?->toIso8601String(),
            ],
        ]);
    }

    private function rawProvidersForFilter(string $provider): array
    {
        return match ($provider) {
            'mtn' => ['momo', 'mtn_momo', 'mtn'],
            'airtel' => ['airtel', 'airtel_money'],
            'card' => ['card', 'stripe'],
            default => [$provider],
        };
    }

    private function rawStatusesForFilter(string $status): array
    {
        return match ($status) {
            'initiated' => ['INITIATED'],
            'pending' => ['PENDING'],
            'processing' => ['AUTHORIZED', 'PROCESSING'],
            'success' => ['SUCCESS', 'SUCCESSFUL'],
            'paid' => ['PAID'],
            'failed' => ['FAILED', 'REJECTED', 'DECLINED'],
            'cancelled' => ['CANCELLED', 'CANCELED'],
            'expired' => ['EXPIRED', 'TIMEOUT'],
            'refunded' => ['REFUNDED', 'PARTIALLY_REFUNDED'],
            'reversed' => ['REVERSED', 'REVERSAL', 'ROLLED_BACK'],
            'disputed' => ['DISPUTED', 'CHARGEBACK'],
            'unknown' => ['UNKNOWN', ''],
            default => [strtoupper($status)],
        };
    }
}
