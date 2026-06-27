@extends('layouts.admin-modern')
@section('title', 'Reversements livreurs | Finance')
@section('page_title', 'Reversements livreurs')
@section('nav_active', 'payouts-drivers')

@section('style')
<style>
.pay-page { padding:24px; }
.pay-alert { padding:12px 16px; border-radius:8px; font-size:13px; font-weight:500; border:1px solid transparent; margin-bottom:16px; }
.pay-alert--success { background:#f0fdf4; color:#166534; border-color:#bbf7d0; }
.pay-alert--danger  { background:#fef2f2; color:#991b1b; border-color:#fecaca; }
.pay-alert--info    { background:#eff6ff; color:#1e3a8a; border-color:#bfdbfe; border-radius:12px; }
.pay-card { background:#fff; border:1px solid #e5e7eb; border-radius:10px; overflow:hidden; margin-bottom:20px; }
.pay-card__header { padding:14px 20px; border-bottom:1px solid #f3f4f6; }
.pay-card__title { font-size:14px; font-weight:700; color:#111827; margin:0; }
.pay-card__body { padding:20px; }
/* Tabs */
.pay-tabs { display:flex; gap:0; border-bottom:2px solid #e5e7eb; margin-bottom:20px; }
.pay-tab { padding:10px 22px; font-size:13px; font-weight:600; color:#6b7280; background:none; border:none; border-bottom:2px solid transparent; margin-bottom:-2px; cursor:pointer; transition:color .15s, border-color .15s; }
.pay-tab.active { color:#1e3a5f; border-bottom-color:#1e3a5f; }
.pay-tab-panel { display:none; }
.pay-tab-panel.active { display:block; }
/* Fallback banner */
.pay-fallback { display:flex; flex-wrap:wrap; justify-content:space-between; align-items:flex-start; gap:14px; padding:16px 20px; background:#f9fafb; border:1px solid #e5e7eb; border-radius:8px; margin-bottom:20px; }
.pay-fallback__text h5 { font-size:14px; font-weight:700; color:#111827; margin:0 0 4px; }
.pay-fallback__text p  { font-size:13px; color:#6b7280; margin:0 0 4px; }
.pay-fallback__text small { font-size:12px; color:#9ca3af; }
.pay-btn-csv { display:inline-flex; align-items:center; gap:6px; padding:8px 16px; background:#16a34a; color:#fff; border:none; border-radius:6px; font-size:13px; font-weight:600; text-decoration:none; transition:opacity .15s; white-space:nowrap; }
.pay-btn-csv:hover { opacity:.85; color:#fff; text-decoration:none; }
.pay-btn-csv.disabled { opacity:.5; pointer-events:none; }
/* Table */
.pay-table-wrap { overflow-x:auto; }
.pay-table { width:100%; border-collapse:collapse; font-size:13px; }
.pay-table thead th { padding:9px 14px; font-size:10px; font-weight:700; letter-spacing:.06em; text-transform:uppercase; color:#9ca3af; border-bottom:1px solid #f3f4f6; background:#f9fafb; text-align:left; white-space:nowrap; }
.pay-table tbody td { padding:10px 14px; color:#374151; vertical-align:middle; border-bottom:1px solid #f3f4f6; white-space:nowrap; }
.pay-table tbody tr:last-child td { border-bottom:none; }
/* Pills */
.pay-pill { display:inline-flex; align-items:center; padding:3px 10px; border-radius:20px; font-size:11px; font-weight:600; }
.pay-pill--info    { background:#dbeafe; color:#1e40af; }
.pay-pill--warn    { background:#fef3c7; color:#92400e; }
.pay-pill--success { background:#d1fae5; color:#065f46; }
.pay-pill__ref { font-size:11px; color:#9ca3af; margin-top:3px; }
/* Action buttons */
.pay-btn-launch { display:inline-flex; align-items:center; gap:5px; padding:5px 12px; background:#16a34a; color:#fff; border:none; border-radius:5px; font-size:12px; font-weight:600; cursor:pointer; transition:opacity .15s; }
.pay-btn-launch:hover { opacity:.85; }
.pay-btn-detail { display:inline-flex; align-items:center; gap:5px; padding:5px 12px; background:#0284c7; color:#fff; border:none; border-radius:5px; font-size:12px; font-weight:600; cursor:pointer; transition:opacity .15s; }
.pay-btn-detail:hover { opacity:.85; }
/* Native dialog / modal */
.pay-dialog { border:none; border-radius:12px; padding:0; max-width:440px; width:100%; box-shadow:0 20px 60px rgba(0,0,0,.18); }
.pay-dialog::backdrop { background:rgba(0,0,0,.45); }
.pay-dialog__header { display:flex; align-items:center; justify-content:space-between; padding:16px 20px; border-bottom:1px solid #f3f4f6; }
.pay-dialog__title { font-size:14px; font-weight:700; color:#111827; margin:0; }
.pay-dialog__close { background:none; border:none; font-size:18px; color:#9ca3af; cursor:pointer; line-height:1; padding:2px 6px; border-radius:4px; }
.pay-dialog__close:hover { background:#f3f4f6; color:#374151; }
.pay-dialog__body { padding:20px; }
.pay-dialog__footer { display:flex; justify-content:space-between; gap:10px; padding:14px 20px; border-top:1px solid #f3f4f6; }
.pay-field { display:flex; flex-direction:column; gap:5px; margin-bottom:14px; }
.pay-field:last-child { margin-bottom:0; }
.pay-label { font-size:13px; font-weight:600; color:#374151; }
.pay-input { width:100%; padding:9px 12px; border:1px solid #d1d5db; border-radius:6px; font-size:14px; color:#111827; background:#fff; box-sizing:border-box; }
.pay-input:focus { outline:none; border-color:#1e3a5f; box-shadow:0 0 0 3px rgba(30,58,95,.12); }
.pay-hint { font-size:12px; color:#9ca3af; margin-top:3px; }
.pay-btn-primary { display:inline-flex; align-items:center; gap:6px; padding:8px 18px; background:#1e3a5f; color:#fff; border:none; border-radius:6px; font-size:13px; font-weight:600; cursor:pointer; }
.pay-btn-primary:hover { opacity:.85; }
.pay-btn-secondary { display:inline-flex; align-items:center; padding:8px 16px; border:1px solid #d1d5db; border-radius:6px; font-size:13px; font-weight:600; color:#374151; background:#fff; cursor:pointer; }
.pay-btn-secondary:hover { background:#f9fafb; }
/* Detail dialog (wider) */
.pay-dialog--lg { max-width:640px; }
.pay-invoice-grid { display:grid; grid-template-columns:1fr 1fr 1fr; gap:20px; }
.pay-invoice-block strong { display:block; font-size:12px; font-weight:700; color:#111827; margin-bottom:6px; text-transform:uppercase; letter-spacing:.04em; }
.pay-invoice-block address { font-size:13px; color:#374151; font-style:normal; line-height:1.6; }
.pay-invoice-block p { font-size:13px; color:#374151; margin:0; line-height:1.8; }
@media (max-width:640px) { .pay-invoice-grid { grid-template-columns:1fr; } }
.pay-balance-widget{display:flex;align-items:center;gap:16px;padding:16px 20px;background:linear-gradient(135deg,#1e3a5f 0%,#2563eb 100%);border-radius:10px;margin-bottom:20px;color:#fff;}
.pay-balance-widget__icon{font-size:28px;opacity:.85;}
.pay-balance-widget__label{font-size:11px;font-weight:600;letter-spacing:.08em;text-transform:uppercase;opacity:.75;margin-bottom:4px;}
.pay-balance-widget__amount{font-size:24px;font-weight:700;}
.pay-balance-widget__status{font-size:11px;margin-top:4px;}
.pay-balance-widget__status--ok{color:#86efac;}
.pay-balance-widget__status--warn{color:#fcd34d;}
</style>
@endsection

@section('content')
<div class="pay-page">

    @if(session()->has('alert'))
        <div class="pay-alert pay-alert--{{ session()->get('alert.type') }}">
            {{ session()->get('alert.message') }}
        </div>
    @endif

        <div class="pay-balance-widget">
        <div class="pay-balance-widget__icon"><i class="fas fa-wallet"></i></div>
        <div class="pay-balance-widget__content">
            <div class="pay-balance-widget__label">Solde MTN disbursement</div>
            @if($mtnBalance && isset($mtnBalance['availableBalance']))
                <div class="pay-balance-widget__amount">{{ number_format((float)$mtnBalance['availableBalance'], 0, ',', ' ') }} {{ $mtnBalance['currency'] ?? 'XAF' }}</div>
                <div class="pay-balance-widget__status pay-balance-widget__status--ok"><i class="fas fa-circle" style="font-size:7px;vertical-align:middle;"></i> API temps r&#233;el active</div>
            @else
                <div class="pay-balance-widget__amount" style="color:#bfdbfe;">Indisponible</div>
                <div class="pay-balance-widget__status pay-balance-widget__status--warn"><i class="fas fa-exclamation-circle"></i> V&#233;rifier la connexion MTN</div>
            @endif
        </div>
    </div>
<div class="pay-card">
        <div class="pay-card__body">

            <div class="pay-alert pay-alert--info" style="margin-bottom:20px;">
                Cet écran pilote les <strong>reversements livreurs</strong>. Il est distinct du cockpit des paiements entrants clients. Trois cas existent ici : <strong>lancer l'API MTN</strong>, <strong>suivre une référence MTN déjà lancée</strong>, ou <strong>marquer un reversement manuel</strong>.
            </div>

            {{-- Tabs --}}
            <div class="pay-tabs">
                <button class="pay-tab active" onclick="payShowTab('requests', this)">Demandes de reversement</button>
                <button class="pay-tab" onclick="payShowTab('history', this)">Historique des reversements</button>
            </div>

            {{-- Tab: Requests --}}
            <div id="pay-panel-requests" class="pay-tab-panel active">
                <div class="pay-fallback">
                    <div class="pay-fallback__text">
                        <h5><i class="fas fa-file-csv" style="color:#16a34a;margin-right:6px;"></i>Backup bulk MTN</h5>
                        <p>Backup : exportez les demandes <code>pending</code> en CSV pour le portail bulk payment MTN si l'API temps réel est indisponible.</p>
                        <small>Colonnes exportées : <code>Payee Name</code>, <code>MSISDN</code>, <code>Amount (FCFA)</code>.</small>
                    </div>
                    <a href="{{ route('driver_payout.export_csv') }}"
                       class="pay-btn-csv {{ $requests->isEmpty() ? 'disabled' : '' }}"
                       @if($requests->isEmpty()) aria-disabled="true" @endif>
                        <i class="fas fa-file-export"></i>Export CSV bulk MTN
                    </a>
                </div>
                <div class="pay-table-wrap">
                    <table class="pay-table" id="dt-driver-requests">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>N°</th>
                                <th>Livreur</th>
                                <th>Téléphone</th>
                                <th>Montant à payer</th>
                                <th>Statut</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                        @forelse($requests as $index => $request)
                            @php $hasAutoRef = \Illuminate\Support\Str::isUuid($request->transaction_id); @endphp
                            <tr>
                                <td>{{ \Carbon\Carbon::parse($request->date)->diffForHumans() }}</td>
                                <td>{{ ++$index }}</td>
                                <td>{{ $request->name }}</td>
                                <td>{{ $request->phone }}</td>
                                <td>{{ number_format($request->payout_amount, 0, ',', ' ') }} FCFA</td>
                                <td>
                                    @if($hasAutoRef)
                                        <span class="pay-pill pay-pill--info">Décaissement API en cours</span>
                                        <div class="pay-pill__ref">Réf. MTN : {{ $request->transaction_id }}</div>
                                    @else
                                        <span class="pay-pill pay-pill--warn">Prêt à lancer</span>
                                    @endif
                                </td>
                                <td>
                                    <button class="pay-btn-launch"
                                            onclick="document.getElementById('dlg-drv-req-{{ $request->request_id }}').showModal()">
                                        <i class="fas {{ $hasAutoRef ? 'fa-sync-alt' : 'fa-paper-plane' }}"></i>
                                        {{ $hasAutoRef ? 'Vérifier le statut' : 'Lancer le reversement' }}
                                    </button>
                                </td>
                            </tr>

                            {{-- Dialog reversement --}}
                            <dialog class="pay-dialog" id="dlg-drv-req-{{ $request->request_id }}">
                                <div class="pay-dialog__header">
                                    <h4 class="pay-dialog__title"><i class="fas fa-money-bill-wave" style="margin-right:6px;"></i>Reversement livreur</h4>
                                    <button class="pay-dialog__close" onclick="this.closest('dialog').close()">&times;</button>
                                </div>
                                <form action="{{ route('driver_pay') }}" method="post">
                                    @csrf
                                    <input type="hidden" name="request_id" value="{{ $request->request_id }}">
                                    <div class="pay-dialog__body">
                                        <div class="pay-field">
                                            <label class="pay-label">Référence manuelle <span style="font-weight:400;color:#9ca3af;">(optionnel)</span></label>
                                            <input type="text" class="pay-input" name="transaction_id" placeholder="Ex : TXN123456789">
                                            <div class="pay-hint">
                                                Laissez vide pour {{ $hasAutoRef ? 'vérifier le décaissement MTN API déjà lancé' : 'lancer un reversement MTN MoMo automatique' }}.
                                            </div>
                                            @if($hasAutoRef)
                                                <div class="pay-hint">Référence MTN en cours : {{ $request->transaction_id }}</div>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="pay-dialog__footer">
                                        <button type="button" class="pay-btn-secondary" onclick="this.closest('dialog').close()">
                                            <i class="fas fa-times" style="margin-right:4px;"></i>Fermer
                                        </button>
                                        <button type="submit" class="pay-btn-primary">
                                            <i class="fas fa-check"></i>Confirmer le traitement
                                        </button>
                                    </div>
                                </form>
                            </dialog>
                        @empty
                            <tr>
                                <td colspan="7" style="text-align:center;color:#9ca3af;padding:32px;">Aucune demande de paiement en attente.</td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Tab: History --}}
            <div id="pay-panel-history" class="pay-tab-panel">
                <div class="pay-table-wrap">
                    <table class="pay-table" id="dt-driver-history">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>N°</th>
                                <th>Livreur</th>
                                <th>Téléphone</th>
                                <th>Montant reversé</th>
                                <th>Statut</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                        @foreach($history as $index => $request)
                            <tr>
                                <td>{{ \Carbon\Carbon::parse($request->date)->diffForHumans() }}</td>
                                <td>{{ ++$index }}</td>
                                <td>{{ $request->name }}</td>
                                <td>{{ $request->phone }}</td>
                                <td>{{ number_format($request->payout_amount, 0, ',', ' ') }} FCFA</td>
                                <td><span class="pay-pill pay-pill--success">Reversement confirmé</span></td>
                                <td>
                                    <button class="pay-btn-detail"
                                            onclick="document.getElementById('dlg-drv-hist-{{ $request->request_id }}').showModal()">
                                        <i class="fas fa-eye"></i>Détails
                                    </button>
                                </td>
                            </tr>

                            {{-- Dialog détail --}}
                            <dialog class="pay-dialog pay-dialog--lg" id="dlg-drv-hist-{{ $request->request_id }}">
                                <div class="pay-dialog__header">
                                    <h4 class="pay-dialog__title"><i class="fas fa-receipt" style="margin-right:6px;"></i>Détail du reversement</h4>
                                    <button class="pay-dialog__close" onclick="this.closest('dialog').close()">&times;</button>
                                </div>
                                <div class="pay-dialog__body">
                                    <div class="pay-invoice-grid">
                                        <div class="pay-invoice-block">
                                            <strong>De</strong>
                                            <address>
                                                <strong>{{ auth()->user()->name }}</strong><br>
                                                {{ auth()->user()->address ?? 'N/A' }}<br>
                                                Téléphone : {{ auth()->user()->phone ?? 'N/A' }}<br>
                                                Email : {{ auth()->user()->email }}
                                            </address>
                                        </div>
                                        <div class="pay-invoice-block">
                                            <strong>À</strong>
                                            <address>
                                                <strong>{{ $request->name }}</strong><br>
                                                {{ $request->address ?? 'N/A' }}<br>
                                                Téléphone : {{ $request->phone }}<br>
                                                Email : {{ $request->email ?? 'N/A' }}
                                            </address>
                                        </div>
                                        <div class="pay-invoice-block">
                                            <strong>Référence</strong>
                                            <p>
                                                #{{ $request->transaction_id }}<br>
                                                <b>Date :</b> {{ $request->date }}<br>
                                                <b>Montant :</b> {{ number_format($request->payout_amount, 0, ',', ' ') }} FCFA
                                            </p>
                                        </div>
                                    </div>
                                </div>
                                <div class="pay-dialog__footer" style="justify-content:flex-end;">
                                    <button type="button" class="pay-btn-secondary" onclick="this.closest('dialog').close()">Fermer</button>
                                </div>
                            </dialog>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>

</div>
@endsection

@section('script')
<script src="{{ asset('plugins/datatables/jquery.dataTables.js') }}"></script>
<script>
function payShowTab(panel, btn) {
    document.querySelectorAll('.pay-tab-panel').forEach(function(p) { p.classList.remove('active'); });
    document.querySelectorAll('.pay-tab').forEach(function(b) { b.classList.remove('active'); });
    document.getElementById('pay-panel-' + panel).classList.add('active');
    btn.classList.add('active');
}
$(function () {
    $('#dt-driver-requests').DataTable({ language: window.bdAdminDataTableLanguage });
    $('#dt-driver-history').DataTable({ language: window.bdAdminDataTableLanguage });
});
</script>
@endsection
