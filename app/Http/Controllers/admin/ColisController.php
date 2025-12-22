<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Domain\Colis\Models\Shipment;
use App\Domain\Colis\Enums\ShipmentStatus;
use Illuminate\Http\Request;

class ColisController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->only(['search', 'status', 'courier_id', 'date_from', 'date_to']);
        
        $shipments = Shipment::with(['customer', 'courier'])
            ->filter($filters)
            ->latest()
            ->paginate(20);

        // Statistiques pour le dashboard
        $stats = [
            'total' => Shipment::count(),
            'pending' => Shipment::whereIn('status', ['created', 'priced', 'paid'])->count(),
            'in_transit' => Shipment::whereIn('status', ['picked_up', 'in_transit', 'out_for_delivery'])->count(),
            'delivered_today' => Shipment::where('status', 'delivered')->whereDate('delivered_at', now())->count(),
        ];

        $couriers = \App\Driver::where('active', 1)->get();

        return view('admin.colis.index', compact('shipments', 'stats', 'couriers'));
    }

    public function show(Shipment $shipment)
    {
        $shipment->load(['addresses', 'events', 'proofs', 'incidents.reporter']);
        return view('admin.colis.show', compact('shipment'));
    }

    /**
     * Déclarer un incident sur un colis
     */
    public function reportIncident(Request $request, Shipment $shipment)
    {
        $request->validate([
            'type' => 'required|string',
            'description' => 'required|string',
            'status' => 'nullable|string'
        ]);

        return \Illuminate\Support\Facades\DB::transaction(function () use ($request, $shipment) {
            // Créer l'incident
            \App\Domain\Colis\Models\ShipmentIncident::create([
                'shipment_id' => $shipment->id,
                'reported_by' => auth()->id(),
                'type' => $request->type,
                'description' => $request->description,
            ]);

            // Si un nouveau statut est demandé (ex: marqué comme endommagé)
            if ($request->status) {
                $newStatus = ShipmentStatus::from($request->status);
                app(ShipmentStateMachine::class)->transitionTo($shipment, $newStatus, [
                    'actor_type' => 'admin',
                    'actor_id' => auth()->id(),
                    'notes' => "Incident signalé: " . $request->description
                ]);
            }

            return redirect()->back()->with('success', 'Incident enregistré avec succès.');
        });
    }

    /**
     * Dashboard financier / Réconciliation
     */
    public function financialIndex()
    {
        $paymentService = app(\App\Domain\Colis\Services\ShipmentPaymentService::class);
        
        // Liste des coursiers ayant du cash en main (COD non réconciliés)
        $couriersWithCash = \App\Driver::where('active', 1)
            ->whereHas('shipments', function($q) {
                $q->where('status', ShipmentStatus::DELIVERED)
                  ->where('payment_status', 'cod_pending')
                  ->where('cod_amount', '>', 0);
            })
            ->get()
            ->map(function($courier) use ($paymentService) {
                $pending = $paymentService->getPendingCourierCOD($courier->id);
                $courier->pending_cod_count = $pending['count'];
                $courier->pending_cod_amount = $pending['total_amount'];
                $courier->pending_shipment_ids = $pending['shipment_ids'];
                return $courier;
            });

        // Historique des dernières réconciliations
        $recentReconciliations = \App\Domain\Colis\Models\ShipmentReconciliation::with(['courier', 'admin'])
            ->latest()
            ->limit(10)
            ->get();

        return view('admin.colis.reconciliation', compact('couriersWithCash', 'recentReconciliations'));
    }

    /**
     * Valider la réconciliation d'un coursier
     */
    public function reconcile(Request $request, $courierId)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0',
            'shipment_ids' => 'required|array',
        ]);

        $paymentService = app(\App\Domain\Colis\Services\ShipmentPaymentService::class);
        
        $paymentService->reconcileCourier(
            $courierId,
            auth()->id(),
            $request->shipment_ids,
            $request->amount
        );

        return redirect()->back()->with('success', 'Réconciliation effectuée avec succès.');
    }

    /**
     * Exporter les colis au format CSV
     */
    public function exportCsv(Request $request)
    {
        $filters = $request->only(['search', 'status', 'courier_id', 'date_from', 'date_to']);
        $shipments = Shipment::with(['customer', 'courier'])->filter($filters)->get();

        $fileName = 'colis_export_' . now()->format('Y-m-d_H-i') . '.csv';

        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $columns = [
            'Tracking', 'Client', 'Coursier', 'Statut', 'Poids (kg)', 'Prix (FCFA)', 
            'COD (FCFA)', 'Paiement', 'Créé le', 'Livré le'
        ];

        $callback = function() use($shipments, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns, ';');

            foreach ($shipments as $s) {
                fputcsv($file, [
                    $s->tracking_number,
                    $s->customer->name ?? 'N/A',
                    $s->courier->name ?? 'N/A',
                    $s->status->label(),
                    $s->weight_kg,
                    $s->total_price,
                    $s->cod_amount,
                    $s->payment_status,
                    $s->created_at->format('d/m/Y H:i'),
                    $s->delivered_at ? $s->delivered_at->format('d/m/Y H:i') : ''
                ], ';');
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Vue d'impression pour l'étiquette / Facture
     */
    public function print(Shipment $shipment)
    {
        $shipment->load(['customer', 'addresses']);
        return view('admin.colis.print', compact('shipment'));
    }
}

