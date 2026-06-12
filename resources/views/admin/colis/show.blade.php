@extends('layouts.admin-modern')
@section('title', 'Détail Colis | Mema')
@section('page_title', 'Détail colis')
@section('nav_active', 'colis')

@php
    $paymentExperience = $paymentExperience ?? null;
    $paymentPillClass = match($paymentExperience['status'] ?? null) {
        'PAID'              => 'background:#d1fae5;color:#065f46;',
        'FAILED','CANCELLED'=> 'background:#fee2e2;color:#991b1b;',
        'PENDING'           => 'background:#fef3c7;color:#92400e;',
        default             => 'background:#dbeafe;color:#1e40af;',
    };
@endphp

@section('style')
<style>
.col-page { padding:24px; }
.col-topbar { display:flex; justify-content:flex-end; margin-bottom:16px; }
.col-layout { display:grid; grid-template-columns:320px 1fr; gap:20px; }
.col-card { background:#fff; border:1px solid #e5e7eb; border-radius:10px; overflow:hidden; margin-bottom:16px; }
.col-card:last-child { margin-bottom:0; }
.col-card__header { padding:12px 18px; border-bottom:1px solid #f3f4f6; }
.col-card__title { font-size:13px; font-weight:700; color:#111827; margin:0; }
.col-card__body { padding:18px; }
.col-id { font-size:18px; font-weight:700; color:#111827; text-align:center; margin-bottom:4px; }
.col-status-sub { font-size:13px; color:#9ca3af; text-align:center; margin-bottom:14px; }
.col-list { list-style:none; padding:0; margin:0; border-top:1px solid #f3f4f6; }
.col-list li { display:flex; justify-content:space-between; padding:8px 0; font-size:13px; border-bottom:1px solid #f3f4f6; gap:8px; }
.col-list li:last-child { border-bottom:none; }
.col-list li b { color:#374151; flex-shrink:0; }
.col-list li span, .col-list li a { color:#6b7280; text-align:right; overflow-wrap:anywhere; }
.col-pill { display:inline-flex; align-items:center; padding:2px 8px; border-radius:20px; font-size:11px; font-weight:700; }
.col-payment-detail { background:#f9fafb; border:1px solid #e5e7eb; border-radius:8px; padding:12px 14px; margin-top:12px; font-size:13px; }
.col-payment-detail strong { display:block; color:#111827; margin-bottom:4px; }
.col-address-section { margin-bottom:12px; }
.col-address-section:last-child { margin-bottom:0; }
.col-address-label { font-size:12px; font-weight:700; color:#374151; margin-bottom:4px; }
.col-address-text { font-size:13px; color:#6b7280; line-height:1.6; }
.col-tabs { display:flex; gap:0; border-bottom:2px solid #e5e7eb; margin-bottom:16px; }
.col-tab { padding:8px 18px; font-size:13px; font-weight:600; color:#6b7280; background:none; border:none; border-bottom:2px solid transparent; margin-bottom:-2px; cursor:pointer; }
.col-tab.active { color:#1e3a5f; border-bottom-color:#1e3a5f; }
.col-tab-panel { display:none; }
.col-tab-panel.active { display:block; }
.col-btn-danger { display:inline-flex; align-items:center; gap:5px; padding:7px 14px; background:#dc2626; color:#fff; border:none; border-radius:6px; font-size:13px; font-weight:600; cursor:pointer; }
.col-print-btn { display:inline-flex; align-items:center; gap:6px; padding:8px 16px; border:1px solid #d1d5db; border-radius:6px; font-size:13px; font-weight:600; color:#374151; background:#fff; text-decoration:none; }
.col-print-btn:hover { background:#f9fafb; color:#111827; text-decoration:none; }
.col-incident-item { padding:12px 14px; border:1px solid #e5e7eb; border-radius:8px; margin-bottom:10px; }
.col-incident-date { font-size:11px; font-weight:700; color:#dc2626; margin-bottom:4px; }
.col-incident-type { font-size:13px; font-weight:700; color:#111827; margin-bottom:4px; }
.col-incident-desc { font-size:13px; color:#6b7280; }
.col-incident-resolution { margin-top:8px; padding:8px 12px; background:#f0fdf4; border-radius:6px; font-size:12px; color:#166534; }
.col-field { margin-bottom:14px; }
.col-label { font-size:13px; font-weight:600; color:#374151; margin-bottom:5px; display:block; }
.col-input { width:100%; padding:9px 12px; border:1px solid #d1d5db; border-radius:6px; font-size:14px; box-sizing:border-box; }
.col-hint { font-size:12px; color:#9ca3af; margin-top:4px; }
.col-dialog { border:none; border-radius:12px; padding:0; max-width:460px; width:100%; box-shadow:0 20px 60px rgba(0,0,0,.18); }
.col-dialog::backdrop { background:rgba(0,0,0,.45); }
.col-dialog__header { display:flex; align-items:center; justify-content:space-between; padding:14px 20px; border-bottom:1px solid #f3f4f6; background:#dc2626; }
.col-dialog__title { font-size:14px; font-weight:700; color:#fff; margin:0; }
.col-dialog__close { background:none; border:none; font-size:18px; color:#fff; cursor:pointer; }
.col-dialog__body { padding:20px; }
.col-dialog__footer { display:flex; justify-content:space-between; gap:10px; padding:14px 20px; border-top:1px solid #f3f4f6; }
.col-btn-secondary { display:inline-flex; align-items:center; padding:8px 16px; border:1px solid #d1d5db; border-radius:6px; font-size:13px; font-weight:600; color:#374151; background:#fff; cursor:pointer; }
@media (max-width:900px) { .col-layout { grid-template-columns:1fr; } }
</style>
@endsection

@section('content')
<div class="col-page">
    <div class="col-topbar">
        <a href="{{ route('admin.colis.print', $shipment->id) }}" target="_blank" class="col-print-btn">
            <i class="fas fa-print"></i> Imprimer l'étiquette
        </a>
    </div>

    <div class="col-layout">
        {{-- Sidebar info --}}
        <div>
            <div class="col-card">
                <div class="col-card__body">
                    <div class="col-id">{{ $shipment->tracking_number }}</div>
                    <div class="col-status-sub">{{ $shipment->status->label() }}</div>
                    <ul class="col-list">
                        <li><b>Client</b><span>{{ $shipment->customer->name ?? 'N/A' }}</span></li>
                        <li><b>Poids</b><span>{{ $shipment->weight_kg }} kg</span></li>
                        <li><b>Prix Total</b><span>{{ number_format($shipment->total_price, 0, ',', ' ') }} FCFA</span></li>
                        <li>
                            <b>Paiement</b>
                            <span class="col-pill" style="{{ $paymentPillClass }}">
                                {{ $paymentExperience['status'] ?? strtoupper($shipment->payment_status) }}
                            </span>
                        </li>
                    </ul>
                    @if($paymentExperience)
                        <div class="col-payment-detail">
                            <strong>{{ $paymentExperience['customer_message'] }}</strong>
                            @if(!empty($paymentExperience['failure_reason']))
                                <div style="color:#dc2626;font-size:12px;margin-top:4px;">Code provider : {{ $paymentExperience['failure_reason'] }}</div>
                            @endif
                            @if(!empty($paymentExperience['support_action']))
                                <div style="color:#9ca3af;font-size:12px;margin-top:4px;">Action back-office : {{ $paymentExperience['support_action'] }}</div>
                            @endif
                        </div>
                    @endif
                </div>
            </div>

            <div class="col-card">
                <div class="col-card__header">
                    <h3 class="col-card__title">Adresses</h3>
                </div>
                <div class="col-card__body">
                    <div class="col-address-section">
                        <div class="col-address-label"><i class="fas fa-map-marker-alt" style="margin-right:4px;"></i>Ramassage (Pickup)</div>
                        @php $p = $shipment->pickupAddress(); @endphp
                        <div class="col-address-text">
                            {{ $p->full_name }} ({{ $p->phone }})<br>
                            {{ $p->address_line }}, {{ $p->district }}, {{ $p->city }}<br>
                            <span style="font-size:12px;">Repère : {{ $p->landmark ?? 'N/A' }}</span>
                        </div>
                    </div>
                    <hr style="margin:12px 0;border:none;border-top:1px solid #f3f4f6;">
                    <div class="col-address-section">
                        <div class="col-address-label"><i class="fas fa-map-marker-alt" style="margin-right:4px;color:#16a34a;"></i>Livraison (Dropoff)</div>
                        @php $d = $shipment->dropoffAddress(); @endphp
                        <div class="col-address-text">
                            {{ $d->full_name }} ({{ $d->phone }})<br>
                            {{ $d->address_line }}, {{ $d->district }}, {{ $d->city }}<br>
                            <span style="font-size:12px;">Repère : {{ $d->landmark ?? 'N/A' }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Main content --}}
        <div>
            <div class="col-card">
                <div class="col-card__body">
                    <div class="col-tabs">
                        <button class="col-tab active" onclick="colShowTab('timeline',this)">Timeline (Suivi)</button>
                        <button class="col-tab" onclick="colShowTab('details',this)">Plus de détails</button>
                        <button class="col-tab" onclick="colShowTab('incidents',this)">
                            Incidents &amp; Litiges
                            @if($shipment->incidents->count() > 0)
                                <span style="display:inline-flex;align-items:center;padding:1px 7px;background:#fee2e2;color:#991b1b;border-radius:20px;font-size:11px;font-weight:700;margin-left:5px;">{{ $shipment->incidents->count() }}</span>
                            @endif
                        </button>
                    </div>

                    <div id="col-panel-timeline" class="col-tab-panel active">
                        {{-- timeline content --}}
                    </div>
                    <div id="col-panel-details" class="col-tab-panel">
                        {{-- details content --}}
                    </div>
                    <div id="col-panel-incidents" class="col-tab-panel">
                        <div style="display:flex;justify-content:flex-end;margin-bottom:12px;">
                            <button type="button" class="col-btn-danger"
                                    onclick="document.getElementById('dlg-incident').showModal()">
                                <i class="fas fa-exclamation-triangle"></i> Signaler un incident
                            </button>
                        </div>

                        @forelse($shipment->incidents->sortByDesc('created_at') as $incident)
                            <div class="col-incident-item">
                                <div class="col-incident-date">{{ $incident->created_at->format('d M. Y H:i') }}</div>
                                <div class="col-incident-type">Type : {{ strtoupper($incident->type) }} — Signalé par {{ $incident->reporter->name ?? 'Système' }}</div>
                                <div class="col-incident-desc">{{ $incident->description }}</div>
                                @if($incident->resolution_notes)
                                    <div class="col-incident-resolution">
                                        <strong>Résolution :</strong> {{ $incident->resolution_notes }}<br>
                                        <span style="font-size:11px;">Résolu le : {{ $incident->resolved_at->format('d/m/Y H:i') }}</span>
                                    </div>
                                @endif
                            </div>
                        @empty
                            <div style="text-align:center;color:#9ca3af;padding:32px;font-size:13px;">Aucun incident signalé pour ce colis.</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Dialog Signaler Incident --}}
<dialog class="col-dialog" id="dlg-incident">
    <div class="col-dialog__header">
        <h4 class="col-dialog__title">Signaler un incident / litige</h4>
        <button class="col-dialog__close" onclick="this.closest('dialog').close()">&times;</button>
    </div>
    <form action="{{ route('admin.colis.report-incident', $shipment->id) }}" method="POST">
        @csrf
        <div class="col-dialog__body">
            <div class="col-field">
                <label class="col-label">Type d'incident</label>
                <select name="type" class="col-input" required>
                    <option value="damage">Colis endommagé</option>
                    <option value="loss">Colis perdu / volé</option>
                    <option value="delay">Retard important</option>
                    <option value="customer_complain">Plainte client</option>
                    <option value="other">Autre</option>
                </select>
            </div>
            <div class="col-field">
                <label class="col-label">Description du problème</label>
                <textarea name="description" class="col-input" rows="3" style="resize:vertical;" placeholder="Détaillez le problème rencontré..." required></textarea>
            </div>
            <div class="col-field" style="margin-bottom:0;">
                <label class="col-label">Action immédiate (Changement de statut)</label>
                <select name="status" class="col-input">
                    <option value="">Ne pas changer le statut</option>
                    <option value="damaged">Marquer comme ENDOMMAGÉ</option>
                    <option value="lost">Marquer comme PERDU</option>
                    <option value="returned">Lancer le RETOUR à l'expéditeur</option>
                </select>
                <div class="col-hint">Cela mettra à jour le tracking du client.</div>
            </div>
        </div>
        <div class="col-dialog__footer">
            <button type="button" class="col-btn-secondary" onclick="this.closest('dialog').close()">Fermer</button>
            <button type="submit" class="col-btn-danger">Enregistrer le signalement</button>
        </div>
    </form>
</dialog>
@endsection

@section('script')
<script>
function colShowTab(panel, btn) {
    document.querySelectorAll('.col-tab-panel').forEach(function(p) { p.classList.remove('active'); });
    document.querySelectorAll('.col-tab').forEach(function(b) { b.classList.remove('active'); });
    document.getElementById('col-panel-' + panel).classList.add('active');
    btn.classList.add('active');
}
</script>
@endsection
