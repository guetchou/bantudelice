@extends('layouts.admin-modern')

@section('title', 'Console GePay — Décaissements & Encaissements')
@section('page_title', 'Console GePay')
@section('nav_active', 'gepay')

@section('style')
<style>
:root {
    --gp-green:  #009543;
    --gp-teal:   #0284c7;
    --gp-amber:  #d97706;
    --gp-red:    #dc2626;
    --gp-dark:   #0a1710;
    --gp-card:   #ffffff;
    --gp-border: #e5e7eb;
    --gp-muted:  #6b7280;
    --gp-bg:     #f3f6f9;
}

/* ── Layout ── */
.gp-wrap { display: grid; gap: 1.25rem; }

/* ── KPI Strip ── */
.gp-kpi-strip {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: .75rem;
}
.gp-kpi {
    background: var(--gp-dark);
    border-radius: 14px;
    padding: 1rem 1.1rem;
    position: relative;
    overflow: hidden;
}
.gp-kpi::after {
    content: '';
    position: absolute;
    inset: auto -28px -28px auto;
    width: 90px; height: 90px;
    border-radius: 50%;
    background: radial-gradient(circle, rgba(255,255,255,.08), transparent 68%);
}
.gp-kpi-label {
    font-size: .62rem;
    font-weight: 800;
    letter-spacing: .12em;
    text-transform: uppercase;
    color: rgba(255,255,255,.45);
    display: flex;
    align-items: center;
    gap: .4rem;
}
.gp-kpi-label i { font-size: .7rem; }
.gp-kpi-value {
    margin-top: .5rem;
    font-size: 1.55rem;
    font-weight: 800;
    line-height: 1;
    color: #fff;
    letter-spacing: -.02em;
}
.gp-kpi-sub {
    margin-top: .3rem;
    font-size: .65rem;
    color: rgba(255,255,255,.35);
    font-weight: 600;
}
.gp-kpi--green .gp-kpi-label { color: rgba(0,200,100,.7); }
.gp-kpi--teal  .gp-kpi-label { color: rgba(56,189,248,.7); }
.gp-kpi--amber .gp-kpi-label { color: rgba(251,191,36,.7); }
.gp-kpi--red   .gp-kpi-label { color: rgba(248,113,113,.7); }

/* ── Body grid ── */
.gp-body {
    display: grid;
    grid-template-columns: 1fr 320px;
    gap: 1.25rem;
    align-items: start;
}

/* ── Tabs ── */
.gp-tabs {
    display: flex;
    gap: .25rem;
    background: var(--gp-dark);
    border-radius: 10px;
    padding: .3rem;
    margin-bottom: 1rem;
    width: fit-content;
}
.gp-tab {
    padding: .45rem 1rem;
    border-radius: 7px;
    font-size: .73rem;
    font-weight: 700;
    color: rgba(255,255,255,.45);
    cursor: pointer;
    border: none;
    background: transparent;
    transition: .15s;
    display: flex;
    align-items: center;
    gap: .4rem;
}
.gp-tab:hover { color: #fff; }
.gp-tab.is-active {
    background: #fff;
    color: var(--gp-dark);
}
.gp-tab.is-active .gp-tab-dot--green { background: var(--gp-green); }
.gp-tab.is-active .gp-tab-dot--teal  { background: var(--gp-teal); }
.gp-tab-dot {
    width: 7px; height: 7px;
    border-radius: 50%;
    background: currentColor;
    opacity: .6;
}

/* ── Panels ── */
.gp-panel { display: none; }
.gp-panel.is-active { display: block; }

/* ── Card ── */
.gp-card {
    background: var(--gp-card);
    border: 1px solid var(--gp-border);
    border-radius: 14px;
    padding: 1.25rem;
}
.gp-card-title {
    font-size: .8rem;
    font-weight: 800;
    color: #111827;
    display: flex;
    align-items: center;
    gap: .5rem;
    margin-bottom: 1rem;
}
.gp-card-title i { color: var(--gp-teal); }

/* ── Recipient cards ── */
.gp-recipients { display: flex; flex-direction: column; gap: 0; }

/* Batch connector between cards */
.gp-connector {
    display: flex;
    justify-content: center;
    align-items: center;
    height: 24px;
    position: relative;
}
.gp-connector-line {
    width: 2px;
    height: 100%;
    background: repeating-linear-gradient(
        to bottom,
        var(--gp-teal) 0px, var(--gp-teal) 4px,
        transparent 4px, transparent 8px
    );
    opacity: 0.5;
    animation: gp-pulse-line 1.8s ease-in-out infinite;
}
@keyframes gp-pulse-line {
    0%, 100% { opacity: .3; }
    50% { opacity: .8; }
}
.gp-connector-badge {
    position: absolute;
    background: var(--gp-teal);
    color: #fff;
    font-size: .5rem;
    font-weight: 900;
    letter-spacing: .06em;
    padding: .15rem .35rem;
    border-radius: 999px;
    text-transform: uppercase;
    white-space: nowrap;
}

.gp-recipient-card {
    background: #f8fafc;
    border: 1px solid var(--gp-border);
    border-radius: 12px;
    padding: .9rem;
    position: relative;
}
.gp-recipient-card.is-first { border-color: var(--gp-teal); box-shadow: 0 0 0 3px rgba(2,132,199,.07); }

.gp-recipient-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: .75rem;
}
.gp-recipient-num {
    display: flex;
    align-items: center;
    gap: .5rem;
    font-size: .68rem;
    font-weight: 800;
    color: var(--gp-teal);
    text-transform: uppercase;
    letter-spacing: .08em;
}
.gp-recipient-num-dot {
    width: 22px; height: 22px;
    border-radius: 50%;
    background: rgba(2,132,199,.12);
    display: flex; align-items: center; justify-content: center;
    font-size: .6rem; font-weight: 900;
    color: var(--gp-teal);
}
.gp-recipient-remove {
    width: 24px; height: 24px;
    border-radius: 6px;
    border: none;
    background: rgba(220,38,38,.08);
    color: var(--gp-red);
    cursor: pointer;
    font-size: .65rem;
    display: flex; align-items: center; justify-content: center;
}
.gp-recipient-remove:hover { background: rgba(220,38,38,.15); }

.gp-recipient-fields {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: .6rem;
}
.gp-recipient-fields .gp-field--full { grid-column: 1 / -1; }

.gp-field { display: flex; flex-direction: column; gap: .25rem; }
.gp-field label {
    font-size: .6rem;
    font-weight: 800;
    letter-spacing: .1em;
    text-transform: uppercase;
    color: var(--gp-muted);
}
.gp-field input {
    border: 1px solid var(--gp-border);
    border-radius: 8px;
    padding: .5rem .65rem;
    font-size: .78rem;
    font-family: inherit;
    color: #111827;
    background: #fff;
    transition: border-color .15s, box-shadow .15s;
}
.gp-field input:focus {
    outline: none;
    border-color: var(--gp-teal);
    box-shadow: 0 0 0 3px rgba(2,132,199,.1);
}
.gp-field input::placeholder { color: #d1d5db; }

/* ── Add recipient button ── */
.gp-add-btn {
    margin-top: .75rem;
    width: 100%;
    border: 1.5px dashed var(--gp-border);
    border-radius: 10px;
    padding: .65rem;
    background: transparent;
    color: var(--gp-teal);
    font-size: .72rem;
    font-weight: 700;
    font-family: inherit;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: .4rem;
    transition: .15s;
}
.gp-add-btn:hover {
    border-color: var(--gp-teal);
    background: rgba(2,132,199,.04);
}
.gp-add-btn:disabled { opacity: .3; cursor: not-allowed; }

/* ── Right panel ── */
.gp-summary {
    position: sticky;
    top: 80px;
    display: flex;
    flex-direction: column;
    gap: .75rem;
}

.gp-summary-card {
    background: var(--gp-dark);
    border-radius: 14px;
    padding: 1.1rem;
    color: #fff;
}
.gp-summary-title {
    font-size: .62rem;
    font-weight: 800;
    letter-spacing: .12em;
    text-transform: uppercase;
    color: rgba(255,255,255,.4);
    margin-bottom: .85rem;
}
.gp-summary-rows { display: flex; flex-direction: column; gap: .4rem; }
.gp-summary-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: .4rem .5rem;
    border-radius: 7px;
    font-size: .72rem;
}
.gp-summary-row-name { color: rgba(255,255,255,.6); font-weight: 600; }
.gp-summary-row-amount { font-weight: 800; color: #fff; }
.gp-summary-divider { border: none; border-top: 1px solid rgba(255,255,255,.08); margin: .5rem 0; }
.gp-summary-total {
    display: flex;
    justify-content: space-between;
    align-items: baseline;
    padding: .3rem .5rem;
}
.gp-summary-total-label {
    font-size: .65rem;
    font-weight: 800;
    letter-spacing: .08em;
    text-transform: uppercase;
    color: rgba(255,255,255,.45);
}
.gp-summary-total-value {
    font-size: 1.3rem;
    font-weight: 800;
    color: #fff;
    letter-spacing: -.02em;
}

.gp-submit-btn {
    width: 100%;
    padding: .8rem;
    border-radius: 10px;
    border: none;
    background: var(--gp-teal);
    color: #fff;
    font-family: inherit;
    font-size: .78rem;
    font-weight: 800;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: .5rem;
    transition: .15s;
    letter-spacing: .02em;
}
.gp-submit-btn:hover:not(:disabled) { background: #0369a1; }
.gp-submit-btn:disabled { opacity: .4; cursor: not-allowed; }
.gp-submit-btn.is-loading i { animation: gp-spin .7s linear infinite; }
@keyframes gp-spin { to { transform: rotate(360deg); } }

.gp-flag {
    display: flex;
    align-items: center;
    gap: .5rem;
    padding: .55rem .75rem;
    border-radius: 8px;
    font-size: .68rem;
    font-weight: 700;
}
.gp-flag--warn {
    background: rgba(217,119,6,.1);
    border: 1px solid rgba(217,119,6,.25);
    color: #92400e;
}
.gp-flag--ok {
    background: rgba(0,149,67,.1);
    border: 1px solid rgba(0,149,67,.25);
    color: #14532d;
}

/* ── Result toast ── */
.gp-result {
    display: none;
    border-radius: 10px;
    padding: .8rem 1rem;
    font-size: .75rem;
    font-weight: 700;
}
.gp-result.is-ok { background: rgba(0,149,67,.1); border: 1px solid rgba(0,149,67,.3); color: #14532d; }
.gp-result.is-err { background: rgba(220,38,38,.1); border: 1px solid rgba(220,38,38,.3); color: #7f1d1d; }
.gp-result.is-partial { background: rgba(217,119,6,.1); border: 1px solid rgba(217,119,6,.3); color: #78350f; }
.gp-result ul { margin-top: .4rem; padding-left: 1.1rem; }
.gp-result li { margin-top: .2rem; font-weight: 600; }

/* ── Transaction table ── */
.gp-table-wrap {
    overflow-x: auto;
    border-radius: 10px;
    border: 1px solid var(--gp-border);
}
.gp-table {
    width: 100%;
    border-collapse: collapse;
    font-size: .72rem;
}
.gp-table thead th {
    background: #f9fafb;
    padding: .55rem .75rem;
    text-align: left;
    font-size: .6rem;
    font-weight: 800;
    letter-spacing: .1em;
    text-transform: uppercase;
    color: var(--gp-muted);
    border-bottom: 1px solid var(--gp-border);
    white-space: nowrap;
}
.gp-table tbody tr { border-bottom: 1px solid #f3f4f6; }
.gp-table tbody tr:last-child { border-bottom: none; }
.gp-table tbody td {
    padding: .6rem .75rem;
    color: #374151;
    vertical-align: middle;
    white-space: nowrap;
}
.gp-type-badge {
    display: inline-flex;
    align-items: center;
    gap: .3rem;
    padding: .2rem .5rem;
    border-radius: 999px;
    font-size: .58rem;
    font-weight: 800;
    letter-spacing: .06em;
    text-transform: uppercase;
}
.gp-type-badge--collection { background: rgba(0,149,67,.1); color: #065f46; }
.gp-type-badge--disbursement { background: rgba(2,132,199,.1); color: #075985; }
.gp-status-dot {
    display: inline-flex;
    align-items: center;
    gap: .3rem;
    font-size: .65rem;
    font-weight: 700;
}
.gp-status-dot::before {
    content: '';
    width: 7px; height: 7px;
    border-radius: 50%;
    display: inline-block;
    flex-shrink: 0;
}
.gp-status-dot--successful::before  { background: var(--gp-green); }
.gp-status-dot--pending::before,
.gp-status-dot--submitted::before,
.gp-status-dot--created::before   { background: var(--gp-amber); animation: gp-blink 1.4s ease-in-out infinite; }
.gp-status-dot--failed::before,
.gp-status-dot--cancelled::before,
.gp-status-dot--expired::before    { background: var(--gp-red); }
.gp-status-dot--unknown::before    { background: #9ca3af; }
@keyframes gp-blink { 0%,100%{opacity:1}50%{opacity:.3} }

.gp-mono { font-family: ui-monospace, monospace; font-size: .65rem; color: var(--gp-muted); }
.gp-amount-col { font-weight: 800; letter-spacing: -.01em; }
.gp-amount-col--in  { color: var(--gp-green); }
.gp-amount-col--out { color: var(--gp-teal); }

.gp-empty {
    text-align: center;
    padding: 2.5rem;
    color: var(--gp-muted);
    font-size: .75rem;
}
.gp-empty i { font-size: 1.5rem; opacity: .3; display: block; margin-bottom: .5rem; }

/* ── Responsive ── */
@media (max-width: 1024px) {
    .gp-body { grid-template-columns: 1fr; }
    .gp-summary { position: static; }
    .gp-kpi-strip { grid-template-columns: repeat(2, 1fr); }
}
@media (max-width: 640px) {
    .gp-kpi-strip { grid-template-columns: 1fr 1fr; }
    .gp-recipient-fields { grid-template-columns: 1fr; }
    .gp-recipient-fields .gp-field--full { grid-column: 1; }
}
</style>
@endsection

@section('content')
<div class="gp-wrap">

    {{-- KPI Strip --}}
    <div class="gp-kpi-strip">
        <div class="gp-kpi gp-kpi--green">
            <div class="gp-kpi-label"><i class="fa fa-arrow-down-left"></i> Encaissé</div>
            <div class="gp-kpi-value">{{ number_format($kpis['collected'] / 100, 0, ',', ' ') }}</div>
            <div class="gp-kpi-sub">FCFA — collections réussies</div>
        </div>
        <div class="gp-kpi gp-kpi--teal">
            <div class="gp-kpi-label"><i class="fa fa-arrow-up-right"></i> Décaissé</div>
            <div class="gp-kpi-value">{{ number_format($kpis['disbursed'] / 100, 0, ',', ' ') }}</div>
            <div class="gp-kpi-sub">FCFA — décaissements réussis</div>
        </div>
        <div class="gp-kpi gp-kpi--amber">
            <div class="gp-kpi-label"><i class="fa fa-clock"></i> En cours</div>
            <div class="gp-kpi-value">{{ $kpis['pending'] }}</div>
            <div class="gp-kpi-sub">transactions en attente</div>
        </div>
        <div class="gp-kpi gp-kpi--red">
            <div class="gp-kpi-label"><i class="fa fa-xmark"></i> Échecs</div>
            <div class="gp-kpi-value">{{ $kpis['failed'] }}</div>
            <div class="gp-kpi-sub">annulés / expirés / échoués</div>
        </div>
    </div>

    {{-- Tabs --}}
    <div class="gp-tabs">
        <button class="gp-tab is-active" data-tab="disburse">
            <span class="gp-tab-dot gp-tab-dot--teal"></span>
            Décaissement
        </button>
        <button class="gp-tab" data-tab="inbound">
            <span class="gp-tab-dot gp-tab-dot--green"></span>
            Encaissements
        </button>
    </div>

    {{-- PANEL: Décaissement --}}
    <div class="gp-panel is-active" id="panel-disburse">
        <div class="gp-body">

            {{-- Left: form --}}
            <div>
                <div class="gp-card">
                    <div class="gp-card-title">
                        <i class="fa fa-paper-plane"></i>
                        Nouveau lot de décaissement
                    </div>

                    @php $disbEnabled = config('gepay.bantudelice.withdrawals_enabled', false) @endphp
                    @if(! $disbEnabled)
                    <div class="gp-flag gp-flag--warn" style="margin-bottom:.85rem">
                        <i class="fa fa-triangle-exclamation"></i>
                        Le décaissement GePay est désactivé (<code>GEPAY_BANTUDELICE_WITHDRAWALS_ENABLED=false</code>). Les appels passeront en mode démo.
                    </div>
                    @else
                    <div class="gp-flag gp-flag--ok" style="margin-bottom:.85rem">
                        <i class="fa fa-circle-check"></i>
                        Décaissement GePay actif — les transferts sont réels.
                    </div>
                    @endif

                    <form id="gpDisbForm" onsubmit="return false">
                        <div class="gp-recipients" id="gpRecipients">
                            {{-- Recipient 1 (always present) --}}
                            <div class="gp-recipient-card is-first" data-idx="0">
                                <div class="gp-recipient-header">
                                    <div class="gp-recipient-num">
                                        <span class="gp-recipient-num-dot">1</span>
                                        Bénéficiaire 1
                                    </div>
                                </div>
                                <div class="gp-recipient-fields">
                                    <div class="gp-field gp-field--full">
                                        <label>Nom complet</label>
                                        <input type="text" name="recipients[0][name]" placeholder="Jean-Baptiste Moussoki" required>
                                    </div>
                                    <div class="gp-field">
                                        <label>Téléphone MTN</label>
                                        <input type="tel" name="recipients[0][phone]" placeholder="068 000 000" required>
                                    </div>
                                    <div class="gp-field">
                                        <label>Montant (FCFA)</label>
                                        <input type="number" name="recipients[0][amount]" placeholder="5000" min="100" required data-amount>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <button type="button" class="gp-add-btn" id="gpAddRecipient">
                            <i class="fa fa-plus"></i>
                            Ajouter un bénéficiaire <span style="opacity:.5;font-weight:600">(max 3)</span>
                        </button>

                        <div class="gp-result" id="gpResult"></div>
                    </form>
                </div>
            </div>

            {{-- Right: summary --}}
            <div class="gp-summary">
                <div class="gp-summary-card">
                    <div class="gp-summary-title">Récapitulatif du lot</div>
                    <div class="gp-summary-rows" id="gpSummaryRows">
                        <div class="gp-summary-row">
                            <span class="gp-summary-row-name">Bénéficiaire 1</span>
                            <span class="gp-summary-row-amount" id="gpAmt0">— FCFA</span>
                        </div>
                    </div>
                    <hr class="gp-summary-divider">
                    <div class="gp-summary-total">
                        <span class="gp-summary-total-label">Total lot</span>
                        <span class="gp-summary-total-value" id="gpTotal">0 FCFA</span>
                    </div>
                </div>

                <button class="gp-submit-btn" id="gpSubmitBtn" disabled>
                    <i class="fa fa-paper-plane"></i>
                    Exécuter le décaissement
                </button>
            </div>
        </div>
    </div>

    {{-- PANEL: Encaissements --}}
    <div class="gp-panel" id="panel-inbound">
        <div class="gp-card">
            <div class="gp-card-title">
                <i class="fa fa-arrow-down-left" style="color:var(--gp-green)"></i>
                Journal des transactions
            </div>

            <div style="display:flex;gap:.5rem;margin-bottom:1rem;flex-wrap:wrap">
                <button class="gp-tab" style="background:#f3f4f6;color:#374151;font-size:.67rem" data-filter="all">Tous</button>
                <button class="gp-tab" style="background:#f3f4f6;color:#374151;font-size:.67rem" data-filter="collection">Encaissements</button>
                <button class="gp-tab" style="background:#f3f4f6;color:#374151;font-size:.67rem" data-filter="disbursement">Décaissements</button>
            </div>

            <div class="gp-table-wrap">
                <table class="gp-table" id="gpTxTable">
                    <thead>
                        <tr>
                            <th>Type</th>
                            <th>Référence</th>
                            <th>Téléphone</th>
                            <th>Montant</th>
                            <th>Statut</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody id="gpTxBody">
                        @forelse($recent as $tx)
                        <tr>
                            <td>
                                <span class="gp-type-badge gp-type-badge--{{ $tx->type->value }}">
                                    @if($tx->type->value === 'collection')
                                        <i class="fa fa-arrow-down-left" style="font-size:.55rem"></i> Encaissement
                                    @else
                                        <i class="fa fa-arrow-up-right" style="font-size:.55rem"></i> Décaissement
                                    @endif
                                </span>
                            </td>
                            <td><span class="gp-mono">{{ Str::limit($tx->external_reference, 20) }}</span></td>
                            <td><span class="gp-mono">{{ $tx->phone_masked ?? '—' }}</span></td>
                            <td class="gp-amount-col gp-amount-col--{{ $tx->type->value === 'collection' ? 'in' : 'out' }}">
                                {{ number_format($tx->amount / 100, 0, ',', ' ') }} FCFA
                            </td>
                            <td>
                                <span class="gp-status-dot gp-status-dot--{{ $tx->status->value }}">
                                    {{ ucfirst($tx->status->value) }}
                                </span>
                            </td>
                            <td class="gp-mono">{{ $tx->created_at?->format('d/m H:i') }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="6" class="gp-empty"><i class="fa fa-inbox"></i> Aucune transaction</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
(function () {
'use strict';

/* ── Tabs ── */
document.querySelectorAll('.gp-tab[data-tab]').forEach(function(btn) {
    btn.addEventListener('click', function() {
        document.querySelectorAll('.gp-tab[data-tab]').forEach(function(b) { b.classList.remove('is-active'); });
        document.querySelectorAll('.gp-panel').forEach(function(p) { p.classList.remove('is-active'); });
        btn.classList.add('is-active');
        document.getElementById('panel-' + btn.dataset.tab).classList.add('is-active');
    });
});

/* ── Recipient management ── */
var maxRecipients = 3;
var recipientCount = 1;
var container = document.getElementById('gpRecipients');
var addBtn     = document.getElementById('gpAddRecipient');
var submitBtn  = document.getElementById('gpSubmitBtn');
var totalEl    = document.getElementById('gpTotal');
var summaryRows = document.getElementById('gpSummaryRows');
var resultEl   = document.getElementById('gpResult');

function fmt(n) {
    return Number(n || 0).toLocaleString('fr-FR') + ' FCFA';
}

function updateSummary() {
    var inputs = document.querySelectorAll('[data-amount]');
    var total = 0;
    inputs.forEach(function(inp, i) {
        var v = parseInt(inp.value, 10) || 0;
        total += v;
        var el = document.getElementById('gpAmt' + i);
        if (el) el.textContent = v ? fmt(v) : '— FCFA';
    });
    totalEl.textContent = fmt(total);
    submitBtn.disabled = (total === 0);
}

function buildConnector(batchSize) {
    return '<div class="gp-connector">' +
        '<div class="gp-connector-line"></div>' +
        (batchSize > 1 ? '<span class="gp-connector-badge">LOT · ' + batchSize + ' DEST.</span>' : '') +
        '</div>';
}

function rebuildConnectors() {
    document.querySelectorAll('.gp-connector').forEach(function(c) { c.remove(); });
    var cards = container.querySelectorAll('.gp-recipient-card');
    if (cards.length < 2) return;
    cards.forEach(function(card, i) {
        if (i < cards.length - 1) {
            var conn = document.createElement('div');
            conn.innerHTML = buildConnector(cards.length);
            card.after(conn.firstChild);
        }
    });
}

function addRecipientCard() {
    if (recipientCount >= maxRecipients) return;
    var idx = recipientCount;
    var card = document.createElement('div');
    card.className = 'gp-recipient-card';
    card.dataset.idx = idx;
    card.innerHTML =
        '<div class="gp-recipient-header">' +
            '<div class="gp-recipient-num">' +
                '<span class="gp-recipient-num-dot">' + (idx + 1) + '</span>' +
                'Bénéficiaire ' + (idx + 1) +
            '</div>' +
            '<button type="button" class="gp-recipient-remove" onclick="gpRemove(this)"><i class="fa fa-xmark"></i></button>' +
        '</div>' +
        '<div class="gp-recipient-fields">' +
            '<div class="gp-field gp-field--full">' +
                '<label>Nom complet</label>' +
                '<input type="text" name="recipients[' + idx + '][name]" placeholder="Nom du bénéficiaire" required>' +
            '</div>' +
            '<div class="gp-field">' +
                '<label>Téléphone MTN</label>' +
                '<input type="tel" name="recipients[' + idx + '][phone]" placeholder="068 000 000" required>' +
            '</div>' +
            '<div class="gp-field">' +
                '<label>Montant (FCFA)</label>' +
                '<input type="number" name="recipients[' + idx + '][amount]" placeholder="5000" min="100" required data-amount>' +
            '</div>' +
        '</div>';

    /* summary row */
    var srow = document.createElement('div');
    srow.className = 'gp-summary-row';
    srow.id = 'gpSummaryRow' + idx;
    srow.innerHTML = '<span class="gp-summary-row-name">Bénéficiaire ' + (idx + 1) + '</span><span class="gp-summary-row-amount" id="gpAmt' + idx + '">— FCFA</span>';
    summaryRows.appendChild(srow);

    container.appendChild(card);
    recipientCount++;

    card.querySelectorAll('[data-amount]').forEach(function(inp) {
        inp.addEventListener('input', updateSummary);
    });

    rebuildConnectors();
    addBtn.disabled = (recipientCount >= maxRecipients);
    updateSummary();
}

window.gpRemove = function(btn) {
    var card = btn.closest('.gp-recipient-card');
    var idx  = parseInt(card.dataset.idx, 10);
    var srow = document.getElementById('gpSummaryRow' + idx);
    if (srow) srow.remove();
    card.remove();
    recipientCount--;
    addBtn.disabled = false;
    rebuildConnectors();
    updateSummary();
};

addBtn.addEventListener('click', addRecipientCard);

document.querySelectorAll('[data-amount]').forEach(function(inp) {
    inp.addEventListener('input', updateSummary);
});

/* ── Submit disbursement ── */
submitBtn.addEventListener('click', function() {
    var form    = document.getElementById('gpDisbForm');
    var inputs  = form.querySelectorAll('input[required]');
    var ok = true;
    inputs.forEach(function(inp) { if (!inp.value.trim()) { inp.focus(); ok = false; } });
    if (!ok) return;

    submitBtn.classList.add('is-loading');
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fa fa-rotate"></i> Envoi en cours…';
    resultEl.style.display = 'none';

    var data = { recipients: [] };
    var cards = container.querySelectorAll('.gp-recipient-card');
    cards.forEach(function(card) {
        var idx = card.dataset.idx;
        data.recipients.push({
            name:   card.querySelector('[name="recipients[' + idx + '][name]"]').value.trim(),
            phone:  card.querySelector('[name="recipients[' + idx + '][phone]"]').value.trim(),
            amount: parseInt(card.querySelector('[name="recipients[' + idx + '][amount]"]').value, 10),
        });
    });

    fetch('{{ route("admin.gepay.disburse") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN':  document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json',
        },
        body: JSON.stringify(data),
    })
    .then(function(r) { return r.json(); })
    .then(function(res) {
        var cls = res.success ? 'is-ok' : (res.results ? 'is-partial' : 'is-err');
        var icon = res.success ? '<i class="fa fa-circle-check"></i>' : '<i class="fa fa-triangle-exclamation"></i>';
        var html = icon + ' <strong>Lot ' + (res.batch_id || '') + '</strong><ul>';
        (res.results || []).forEach(function(r) {
            html += '<li>' + r.name + ' — ' +
                (r.success
                    ? '<span style="color:var(--gp-green)">' + (r.status || 'soumis') + ' ✓</span>'
                    : '<span style="color:var(--gp-red)">' + (r.message || 'Erreur') + '</span>')
                + '</li>';
        });
        if (res.message) html += '<li>' + res.message + '</li>';
        html += '</ul>';
        resultEl.className = 'gp-result ' + cls;
        resultEl.innerHTML = html;
        resultEl.style.display = 'block';
    })
    .catch(function(e) {
        resultEl.className = 'gp-result is-err';
        resultEl.innerHTML = '<i class="fa fa-xmark"></i> Erreur réseau : ' + e.message;
        resultEl.style.display = 'block';
    })
    .finally(function() {
        submitBtn.classList.remove('is-loading');
        submitBtn.innerHTML = '<i class="fa fa-paper-plane"></i> Exécuter le décaissement';
        submitBtn.disabled = false;
    });
});

/* ── Table filter ── */
document.querySelectorAll('[data-filter]').forEach(function(btn) {
    btn.addEventListener('click', function() {
        var rows = document.querySelectorAll('#gpTxBody tr[data-type]');
        var f = btn.dataset.filter;
        rows.forEach(function(row) {
            row.style.display = (f === 'all' || row.dataset.type === f) ? '' : 'none';
        });
    });
});

/* tag rows with type for JS filter */
document.querySelectorAll('#gpTxBody tr').forEach(function(row) {
    var badge = row.querySelector('.gp-type-badge');
    if (badge) {
        row.dataset.type = badge.classList.contains('gp-type-badge--collection') ? 'collection' : 'disbursement';
    }
});

})();
</script>
@endpush
