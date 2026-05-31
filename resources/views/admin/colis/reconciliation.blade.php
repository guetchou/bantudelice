@extends('layouts.admin-modern')
@section('title', 'Réconciliation COD Colis | Mema')
@section('page_title', 'Réconciliation colis')
@section('nav_active', 'colis')

@section('style')
<style>
.rec-page { padding:24px; }
.rec-alert { padding:12px 16px; border-radius:8px; font-size:13px; font-weight:500; border:1px solid transparent; margin-bottom:16px; }
.rec-alert--warning { background:#fffbeb; color:#92400e; border-color:#fde68a; }
.rec-alert--success { background:#f0fdf4; color:#166534; border-color:#bbf7d0; }
.rec-grid { display:grid; grid-template-columns:7fr 5fr; gap:20px; }
.rec-card { background:#fff; border:1px solid #e5e7eb; border-radius:10px; overflow:hidden; }
.rec-card__header { display:flex; align-items:center; gap:8px; padding:14px 20px; border-bottom:1px solid #f3f4f6; }
.rec-card__title { font-size:14px; font-weight:700; color:#111827; margin:0; }
.rec-table-wrap { overflow-x:auto; }
.rec-table { width:100%; border-collapse:collapse; font-size:13px; }
.rec-table thead th { padding:9px 14px; font-size:10px; font-weight:700; letter-spacing:.06em; text-transform:uppercase; color:#9ca3af; border-bottom:1px solid #f3f4f6; background:#f9fafb; text-align:left; }
.rec-table tbody td { padding:10px 14px; color:#374151; vertical-align:middle; border-bottom:1px solid #f3f4f6; }
.rec-table tbody tr:last-child td { border-bottom:none; }
.rec-amount { color:#dc2626; font-weight:700; }
.rec-btn { display:inline-flex; align-items:center; gap:5px; padding:5px 12px; background:#16a34a; color:#fff; border:none; border-radius:5px; font-size:12px; font-weight:600; cursor:pointer; }
@media (max-width:900px) { .rec-grid { grid-template-columns:1fr; } }
</style>
@endsection

@section('content')
<div class="rec-page">
    <div class="rec-alert rec-alert--warning">
        <strong>Important :</strong> cet écran n'est pas un dashboard financier partenaire colis.
        Il couvre uniquement la <strong>réconciliation du cash collecté à la livraison (COD)</strong>.
        Aucun ledger séparé de reversement partenaire colis n'est encore implémenté, donc aucun montant
        "disponible au retrait" ou "net partenaire" n'est affiché ici.
    </div>

    @if(session('success'))
        <div class="rec-alert rec-alert--success">{{ session('success') }}</div>
    @endif

    <div class="rec-grid">
        {{-- Cash COD en attente --}}
        <div class="rec-card">
            <div class="rec-card__header">
                <i class="fas fa-money-bill-wave" style="color:#f59e0b;"></i>
                <h3 class="rec-card__title">Cash COD en attente de collecte</h3>
            </div>
            <div class="rec-table-wrap">
                <table class="rec-table">
                    <thead>
                        <tr>
                            <th>Coursier</th>
                            <th>Nombre de Colis</th>
                            <th>Montant COD à collecter</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($couriersWithCash as $courier)
                        <tr>
                            <td><strong>{{ $courier->name }}</strong></td>
                            <td>{{ $courier->pending_cod_count }} colis livrés</td>
                            <td><span class="rec-amount">{{ number_format($courier->pending_cod_amount, 0, ',', ' ') }} FCFA</span></td>
                            <td>
                                <form action="{{ route('admin.colis.reconcile', $courier->id) }}" method="POST"
                                      onsubmit="return confirm('Confirmer la réception de {{ number_format($courier->pending_cod_amount, 0, ',', ' ') }} FCFA ?')">
                                    @csrf
                                    <input type="hidden" name="amount" value="{{ $courier->pending_cod_amount }}">
                                    @foreach($courier->pending_shipment_ids as $id)
                                        <input type="hidden" name="shipment_ids[]" value="{{ $id }}">
                                    @endforeach
                                    <button type="submit" class="rec-btn">
                                        <i class="fas fa-check-double"></i> Réconcilier
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" style="text-align:center;color:#9ca3af;padding:32px;">Aucune collecte COD en attente.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Historique réconciliations --}}
        <div class="rec-card">
            <div class="rec-card__header">
                <i class="fas fa-history" style="color:#0284c7;"></i>
                <h3 class="rec-card__title">Dernières réconciliations COD</h3>
            </div>
            <div class="rec-table-wrap">
                <table class="rec-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Coursier</th>
                            <th>Montant</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($recentReconciliations as $log)
                        <tr>
                            <td>{{ $log->created_at->format('d/m/Y H:i') }}</td>
                            <td>{{ $log->courier->name }}</td>
                            <td>{{ number_format($log->amount_reconciled, 0, ',', ' ') }} FCFA</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
