<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Payment;
use App\Services\PaymentDashboardService;
use App\Services\PaymentReconciliationService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PaymentDashboardController extends Controller
{
    public function index(Request $request, PaymentDashboardService $dashboard)
    {
        $hours = in_array((int) $request->query('hours', 12), [6, 12, 24], true)
            ? (int) $request->query('hours', 12)
            : 12;

        $filters = $request->only(['provider', 'status']);

        return view('admin.payments.dashboard', $dashboard->build($hours, $filters));
    }

    public function data(Request $request, PaymentDashboardService $dashboard)
    {
        $hours = in_array((int) $request->query('hours', 12), [6, 12, 24], true)
            ? (int) $request->query('hours', 12)
            : 12;

        return response()->json([
            'status' => true,
            'data' => $dashboard->build($hours, $request->only(['provider', 'status'])),
        ]);
    }

    /**
     * S4.5 — Export CSV des paiements MoMo pour réconciliation comptable.
     * Filtres: provider, status, date_from, date_to.
     */
    public function exportCsv(Request $request): StreamedResponse
    {
        $request->validate([
            'provider'  => 'nullable|string|max:50',
            'status'    => 'nullable|string|max:50',
            'date_from' => 'nullable|date',
            'date_to'   => 'nullable|date|after_or_equal:date_from',
        ]);

        $query = Payment::with(['order', 'user'])
            ->orderBy('created_at', 'desc');

        if ($request->filled('provider')) {
            $query->where('provider', $request->provider);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
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
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF)); // UTF-8 BOM pour Excel

            fputcsv($handle, [
                'ID', 'Commande', 'Utilisateur', 'Email', 'Téléphone',
                'Fournisseur', 'Référence fournisseur', 'Statut',
                'Montant', 'Devise', 'Date', 'Mis à jour',
            ], ';');

            $query->chunk(500, function ($payments) use ($handle) {
                foreach ($payments as $p) {
                    fputcsv($handle, [
                        $p->id,
                        optional($p->order)->order_no ?? $p->order_id,
                        optional($p->user)->name ?? '',
                        optional($p->user)->email ?? '',
                        optional($p->user)->phone ?? data_get($p->meta, 'phone', ''),
                        $p->provider,
                        $p->provider_reference ?? '',
                        $p->status,
                        number_format((float) $p->amount, 0, ',', ' '),
                        $p->currency ?? 'FCFA',
                        $p->created_at?->format('d/m/Y H:i'),
                        $p->updated_at?->format('d/m/Y H:i'),
                    ], ';');
                }
            });

            fclose($handle);
        }, $filename, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    public function reconcile(Request $request, Payment $payment, PaymentReconciliationService $reconciliationService)
    {
        $result = $reconciliationService->reconcile($payment);
        $payment->refresh();

        return response()->json([
            'status' => true,
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
}
