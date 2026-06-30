@extends('layouts.admin-modern')

@section('title', 'Console GePay — Décaissements & Encaissements')
@section('page_title', 'Console GePay')
@section('nav_active', 'gepay')

@section('style')
<style>
:root {
    --gp-green:      #009543;
    --gp-green-bg:   #f0fdf4;
    --gp-green-bd:   #bbf7d0;
    --gp-teal:       #0284c7;
    --gp-teal-bg:    #f0f9ff;
    --gp-teal-bd:    #bae6fd;
    --gp-amber:      #d97706;
    --gp-amber-bg:   #fffbeb;
    --gp-amber-bd:   #fde68a;
    --gp-red:        #dc2626;
    --gp-red-bg:     #fef2f2;
    --gp-red-bd:     #fecaca;
    --gp-dark:       #0a1710;
    --gp-border:     #e5e7eb;
    --gp-muted:      #6b7280;
    --gp-text:       #111827;
    --gp-card:       #ffffff;
    --gp-bg2:        #f8fafc;
}

/* ── Wrap ── */
.gp-wrap { display: grid; gap: 1.25rem; }

/* ── KPI Strip — light ── */
.gp-kpi-strip {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: .75rem;
}
.gp-kpi {
    background: var(--gp-card);
    border: 1px solid var(--gp-border);
    border-radius: 14px;
    padding: 1rem 1.1rem;
    border-left-width: 4px;
}
.gp-kpi--green { border-left-color: var(--gp-green); background: var(--gp-green-bg); border-color: var(--gp-green-bd); border-left-color: var(--gp-green); }
.gp-kpi--teal  { border-left-color: var(--gp-teal);  background: var(--gp-teal-bg);  border-color: var(--gp-teal-bd);  border-left-color: var(--gp-teal); }
.gp-kpi--amber { border-left-color: var(--gp-amber); background: var(--gp-amber-bg); border-color: var(--gp-amber-bd); border-left-color: var(--gp-amber); }
.gp-kpi--red   { border-left-color: var(--gp-red);   background: var(--gp-red-bg);   border-color: var(--gp-red-bd);   border-left-color: var(--gp-red); }
.gp-kpi-label {
    font-size: .62rem; font-weight: 800; letter-spacing: .12em;
    text-transform: uppercase; display: flex; align-items: center; gap: .4rem;
}
.gp-kpi--green .gp-kpi-label { color: #15803d; }
.gp-kpi--teal  .gp-kpi-label { color: #0369a1; }
.gp-kpi--amber .gp-kpi-label { color: #92400e; }
.gp-kpi--red   .gp-kpi-label { color: #991b1b; }
.gp-kpi-value {
    margin-top: .45rem; font-size: 1.55rem; font-weight: 800;
    line-height: 1; letter-spacing: -.02em; color: var(--gp-text);
}
.gp-kpi-sub { margin-top: .25rem; font-size: .63rem; color: var(--gp-muted); font-weight: 600; }

/* ── Tabs ── */
.gp-tabs {
    display: flex; gap: .25rem;
    background: #f1f5f9; border: 1px solid var(--gp-border);
    border-radius: 10px; padding: .3rem; width: fit-content;
}
.gp-tab {
    padding: .45rem 1rem; border-radius: 7px;
    font-size: .73rem; font-weight: 700;
    color: var(--gp-muted); cursor: pointer;
    border: none; background: transparent; transition: .15s;
    display: flex; align-items: center; gap: .4rem;
}
.gp-tab:hover { color: var(--gp-text); }
.gp-tab.is-active { background: var(--gp-card); color: var(--gp-text); box-shadow: 0 1px 3px rgba(0,0,0,.1); }
.gp-tab-dot { width: 7px; height: 7px; border-radius: 50%; }
.gp-tab-dot--green { background: var(--gp-green); }
.gp-tab-dot--teal  { background: var(--gp-teal); }

/* ── Panels ── */
.gp-panel { display: none; }
.gp-panel.is-active { display: block; }

/* ── Body grid ── */
.gp-body { display: grid; grid-template-columns: 1fr 300px; gap: 1.25rem; align-items: start; }

/* ── Cards ── */
.gp-card {
    background: var(--gp-card); border: 1px solid var(--gp-border);
    border-radius: 14px; padding: 1.25rem;
}
.gp-card-title {
    font-size: .82rem; font-weight: 800; color: var(--gp-text);
    display: flex; align-items: center; gap: .5rem; margin-bottom: 1.1rem;
}

/* ── Flag banner ── */
.gp-flag {
    display: flex; align-items: flex-start; gap: .6rem;
    padding: .65rem .85rem; border-radius: 10px;
    font-size: .72rem; font-weight: 600; margin-bottom: 1rem;
    line-height: 1.5;
}
.gp-flag--warn { background: var(--gp-amber-bg); border: 1px solid var(--gp-amber-bd); color: #92400e; }
.gp-flag--ok   { background: var(--gp-green-bg); border: 1px solid var(--gp-green-bd); color: #15803d; }
.gp-flag i { margin-top: .1rem; flex-shrink: 0; }

/* ── Recipient cards ── */
.gp-recipients { display: flex; flex-direction: column; gap: 0; }

.gp-connector { display: flex; justify-content: center; align-items: center; height: 28px; position: relative; }
.gp-connector-line {
    width: 2px; height: 100%;
    background: repeating-linear-gradient(to bottom, var(--gp-teal) 0 4px, transparent 4px 8px);
    opacity: .4; animation: gp-pulse-line 1.8s ease-in-out infinite;
}
@keyframes gp-pulse-line { 0%,100%{opacity:.25}50%{opacity:.7} }
.gp-connector-badge {
    position: absolute;
    background: var(--gp-teal); color: #fff;
    font-size: .5rem; font-weight: 900; letter-spacing: .06em;
    padding: .15rem .4rem; border-radius: 999px; text-transform: uppercase;
}

.gp-recipient-card {
    background: var(--gp-bg2); border: 1.5px solid var(--gp-border);
    border-radius: 12px; padding: .9rem; transition: border-color .15s;
}
.gp-recipient-card.is-primary { border-color: var(--gp-teal); }
.gp-recipient-card.state-loading { opacity: .7; }
.gp-recipient-card.state-success { border-color: var(--gp-green); background: var(--gp-green-bg); }
.gp-recipient-card.state-error   { border-color: var(--gp-red);   background: var(--gp-red-bg); animation: gp-shake .45s ease-in-out; }

.gp-recipient-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: .75rem; }
.gp-recipient-num { display: flex; align-items: center; gap: .5rem; font-size: .68rem; font-weight: 800; color: var(--gp-teal); text-transform: uppercase; letter-spacing: .08em; }
.gp-recipient-num-dot {
    width: 22px; height: 22px; border-radius: 50%;
    background: rgba(2,132,199,.12); color: var(--gp-teal);
    display: flex; align-items: center; justify-content: center;
    font-size: .6rem; font-weight: 900;
    transition: .2s;
}
.state-loading .gp-recipient-num-dot { background: #dbeafe; color: var(--gp-teal); animation: gp-spin .8s linear infinite; }
.state-success .gp-recipient-num-dot { background: var(--gp-green); color: #fff; }
.state-error   .gp-recipient-num-dot { background: var(--gp-red);   color: #fff; }
.gp-recipient-remove {
    width: 26px; height: 26px; border-radius: 7px;
    border: 1px solid var(--gp-red-bd); background: var(--gp-red-bg);
    color: var(--gp-red); cursor: pointer; font-size: .7rem;
    display: flex; align-items: center; justify-content: center; transition: .12s;
}
.gp-recipient-remove:hover { background: var(--gp-red); color: #fff; }

.gp-recipient-fields { display: grid; grid-template-columns: 1fr 1fr; gap: .6rem; }
.gp-field--full { grid-column: 1 / -1; }
.gp-field { display: flex; flex-direction: column; gap: .25rem; }
.gp-field label { font-size: .6rem; font-weight: 800; letter-spacing: .1em; text-transform: uppercase; color: var(--gp-muted); }
.gp-field input {
    border: 1.5px solid var(--gp-border); border-radius: 8px;
    padding: .5rem .7rem; font-size: .78rem; font-family: inherit;
    color: var(--gp-text); background: var(--gp-card); transition: .15s;
}
.gp-field input:focus { outline: none; border-color: var(--gp-teal); box-shadow: 0 0 0 3px rgba(2,132,199,.1); }
.gp-field input::placeholder { color: #d1d5db; }
.gp-field input:disabled { opacity: .5; cursor: not-allowed; }

/* ── Add button ── */
.gp-add-btn {
    margin-top: .85rem; width: 100%;
    border: 1.5px dashed var(--gp-border); border-radius: 10px;
    padding: .7rem; background: transparent; color: var(--gp-teal);
    font-size: .72rem; font-weight: 700; font-family: inherit; cursor: pointer;
    display: flex; align-items: center; justify-content: center; gap: .4rem; transition: .15s;
}
.gp-add-btn:hover { border-color: var(--gp-teal); background: var(--gp-teal-bg); }
.gp-add-btn:disabled { opacity: .3; cursor: not-allowed; }

/* ── Summary panel (light) ── */
.gp-summary { position: sticky; top: 80px; display: flex; flex-direction: column; gap: .85rem; }
.gp-summary-card { background: var(--gp-card); border: 1px solid var(--gp-border); border-radius: 14px; padding: 1.1rem; }
.gp-summary-title { font-size: .62rem; font-weight: 800; letter-spacing: .12em; text-transform: uppercase; color: var(--gp-muted); margin-bottom: .85rem; }
.gp-summary-rows { display: flex; flex-direction: column; gap: .35rem; }
.gp-summary-row { display: flex; justify-content: space-between; align-items: center; padding: .4rem .5rem; border-radius: 7px; font-size: .72rem; background: var(--gp-bg2); }
.gp-summary-row-name { color: var(--gp-muted); font-weight: 600; }
.gp-summary-row-amount { font-weight: 800; color: var(--gp-text); }
.gp-summary-divider { border: none; border-top: 1px solid var(--gp-border); margin: .6rem 0; }
.gp-summary-total { display: flex; justify-content: space-between; align-items: baseline; padding: .3rem .5rem; }
.gp-summary-total-label { font-size: .65rem; font-weight: 800; letter-spacing: .08em; text-transform: uppercase; color: var(--gp-muted); }
.gp-summary-total-value { font-size: 1.35rem; font-weight: 800; color: var(--gp-teal); letter-spacing: -.02em; }

/* ── Submit button — state machine ── */
.gp-submit-btn {
    width: 100%; padding: .85rem;
    border-radius: 10px; border: none;
    background: var(--gp-teal); color: #fff;
    font-family: inherit; font-size: .8rem; font-weight: 800; cursor: pointer;
    display: flex; align-items: center; justify-content: center; gap: .55rem;
    transition: background .2s, transform .1s; letter-spacing: .02em;
}
.gp-submit-btn:hover:not(:disabled) { background: #0369a1; transform: translateY(-1px); }
.gp-submit-btn:active:not(:disabled) { transform: translateY(0); }
.gp-submit-btn:disabled { opacity: .45; cursor: not-allowed; transform: none; }
.gp-submit-btn.is-loading { background: #075985; cursor: not-allowed; }
.gp-submit-btn.is-success { background: var(--gp-green); }
.gp-submit-btn.is-error   { background: var(--gp-red); }

/* Spinner */
@keyframes gp-spin { to { transform: rotate(360deg); } }
.gp-spin { animation: gp-spin .75s linear infinite; }

/* Shake error */
@keyframes gp-shake {
    0%,100% { transform: translateX(0); }
    15%,55%  { transform: translateX(-7px); }
    35%,75%  { transform: translateX( 7px); }
}
.gp-shake { animation: gp-shake .45s ease-in-out; }

/* ── Success overlay ── */
.gp-success-overlay {
    display: none; position: fixed; inset: 0; z-index: 200;
    background: rgba(10,23,16,.65); backdrop-filter: blur(4px);
    align-items: center; justify-content: center;
}
.gp-success-overlay.is-visible { display: flex; }
.gp-success-modal {
    background: var(--gp-card); border-radius: 20px;
    padding: 2.5rem 2rem; max-width: 440px; width: 90%;
    text-align: center; box-shadow: 0 24px 64px rgba(0,0,0,.2);
    animation: gp-modal-in .35s cubic-bezier(.34,1.56,.64,1);
}
@keyframes gp-modal-in {
    from { transform: scale(.75); opacity: 0; }
    to   { transform: scale(1);   opacity: 1; }
}

/* SVG Checkmark animation */
.gp-check-wrap { width: 80px; height: 80px; margin: 0 auto 1.25rem; }
.gp-check-svg { width: 80px; height: 80px; }
.gp-check-circle {
    stroke: var(--gp-green); stroke-width: 3; fill: none;
    stroke-dasharray: 166; stroke-dashoffset: 166;
    stroke-linecap: round;
    animation: gp-circle-draw .6s cubic-bezier(.65,0,.45,1) forwards;
}
.gp-check-path {
    stroke: var(--gp-green); stroke-width: 3; fill: none;
    stroke-dasharray: 48; stroke-dashoffset: 48;
    stroke-linecap: round; stroke-linejoin: round;
    animation: gp-check-draw .4s .5s cubic-bezier(.65,0,.45,1) forwards;
}
@keyframes gp-circle-draw { to { stroke-dashoffset: 0; } }
@keyframes gp-check-draw  { to { stroke-dashoffset: 0; } }

/* SVG Error (X) */
.gp-error-svg { width: 80px; height: 80px; }
.gp-error-circle {
    stroke: var(--gp-red); stroke-width: 3; fill: none;
    stroke-dasharray: 166; stroke-dashoffset: 166;
    animation: gp-circle-draw .6s cubic-bezier(.65,0,.45,1) forwards;
}
.gp-error-line1, .gp-error-line2 {
    stroke: var(--gp-red); stroke-width: 3; stroke-linecap: round;
    stroke-dasharray: 30; stroke-dashoffset: 30;
}
.gp-error-line1 { animation: gp-check-draw .3s .5s ease forwards; }
.gp-error-line2 { animation: gp-check-draw .3s .7s ease forwards; }

.gp-success-title  { font-size: 1.2rem; font-weight: 800; color: var(--gp-text); margin-bottom: .35rem; }
.gp-success-sub    { font-size: .78rem; color: var(--gp-muted); margin-bottom: 1.25rem; }
.gp-success-lines  { text-align: left; background: var(--gp-bg2); border-radius: 10px; padding: .75rem; display: flex; flex-direction: column; gap: .4rem; margin-bottom: 1.25rem; }
.gp-success-line   { display: flex; align-items: center; gap: .5rem; font-size: .73rem; }
.gp-success-line i { width: 16px; text-align: center; }
.gp-success-line--ok  i { color: var(--gp-green); }
.gp-success-line--err i { color: var(--gp-red); }
.gp-success-line--ok .gp-sl-name { font-weight: 700; color: var(--gp-text); }
.gp-success-line--err .gp-sl-name { font-weight: 700; color: var(--gp-red); }
.gp-sl-amount { margin-left: auto; font-weight: 800; color: var(--gp-muted); font-size: .68rem; }

.gp-modal-close {
    width: 100%; padding: .75rem;
    border-radius: 9px; border: 1.5px solid var(--gp-border);
    background: transparent; color: var(--gp-text);
    font-family: inherit; font-size: .78rem; font-weight: 700;
    cursor: pointer; transition: .15s;
}
.gp-modal-close:hover { background: var(--gp-bg2); }

/* ── Error banner ── */
.gp-error-banner {
    display: none; border-radius: 10px; padding: .8rem 1rem;
    background: var(--gp-red-bg); border: 1px solid var(--gp-red-bd);
    color: #7f1d1d; font-size: .73rem; font-weight: 600; margin-top: .85rem;
}
.gp-error-banner.is-visible { display: flex; gap: .6rem; align-items: flex-start; }
.gp-error-banner ul { margin-top: .3rem; padding-left: 1rem; }
.gp-error-banner li { margin-top: .2rem; }

/* ── Transaction table ── */
.gp-table-wrap { overflow-x: auto; border-radius: 10px; border: 1px solid var(--gp-border); }
.gp-table { width: 100%; border-collapse: collapse; font-size: .72rem; }
.gp-table thead th {
    background: var(--gp-bg2); padding: .55rem .75rem; text-align: left;
    font-size: .6rem; font-weight: 800; letter-spacing: .1em;
    text-transform: uppercase; color: var(--gp-muted);
    border-bottom: 1px solid var(--gp-border); white-space: nowrap;
}
.gp-table tbody tr { border-bottom: 1px solid #f3f4f6; transition: background .1s; }
.gp-table tbody tr:last-child { border-bottom: none; }
.gp-table tbody tr:hover { background: var(--gp-bg2); }
.gp-table tbody td { padding: .6rem .75rem; color: #374151; vertical-align: middle; white-space: nowrap; }
.gp-type-badge {
    display: inline-flex; align-items: center; gap: .3rem;
    padding: .22rem .5rem; border-radius: 999px;
    font-size: .58rem; font-weight: 800; letter-spacing: .05em; text-transform: uppercase;
}
.gp-type-badge--collection  { background: var(--gp-green-bg); color: #065f46; border: 1px solid var(--gp-green-bd); }
.gp-type-badge--disbursement{ background: var(--gp-teal-bg);  color: #075985; border: 1px solid var(--gp-teal-bd); }
.gp-status-pill {
    display: inline-flex; align-items: center; gap: .3rem;
    padding: .2rem .55rem; border-radius: 999px;
    font-size: .63rem; font-weight: 700;
}
.gp-status-pill--successful { background: var(--gp-green-bg); color: #15803d; }
.gp-status-pill--pending,
.gp-status-pill--submitted,
.gp-status-pill--created  { background: var(--gp-amber-bg); color: #92400e; }
.gp-status-pill--failed,
.gp-status-pill--cancelled,
.gp-status-pill--expired  { background: var(--gp-red-bg); color: #991b1b; }
.gp-status-pill--unknown  { background: #f3f4f6; color: #374151; }
.gp-status-dot {
    width: 6px; height: 6px; border-radius: 50%; background: currentColor; flex-shrink: 0;
}
.gp-status-pill--pending .gp-status-dot,
.gp-status-pill--submitted .gp-status-dot,
.gp-status-pill--created .gp-status-dot { animation: gp-spin 1.8s linear infinite; }

.gp-mono { font-family: ui-monospace, monospace; font-size: .65rem; color: var(--gp-muted); }
.gp-amount-in  { font-weight: 800; color: var(--gp-green); }
.gp-amount-out { font-weight: 800; color: var(--gp-teal); }
.gp-empty { text-align: center; padding: 2.5rem; color: var(--gp-muted); font-size: .75rem; }
.gp-empty i { font-size: 1.5rem; opacity: .3; display: block; margin-bottom: .5rem; }

/* ── Filter tabs (table) ── */
.gp-filter-bar { display: flex; gap: .4rem; flex-wrap: wrap; margin-bottom: 1rem; }
.gp-filter-btn {
    padding: .35rem .75rem; border-radius: 999px; border: 1.5px solid var(--gp-border);
    background: transparent; color: var(--gp-muted);
    font-size: .68rem; font-weight: 700; cursor: pointer; transition: .15s; font-family: inherit;
}
.gp-filter-btn:hover { border-color: var(--gp-teal); color: var(--gp-teal); }
.gp-filter-btn.is-active { background: var(--gp-teal); border-color: var(--gp-teal); color: #fff; }

/* ── Responsive ── */
@media (max-width: 1024px) {
    .gp-body { grid-template-columns: 1fr; }
    .gp-summary { position: static; }
    .gp-kpi-strip { grid-template-columns: repeat(2, 1fr); }
}
@media (max-width: 640px) {
    .gp-kpi-strip { grid-template-columns: 1fr 1fr; }
    .gp-recipient-fields { grid-template-columns: 1fr; }
    .gp-field--full { grid-column: 1; }
}
</style>
@endsection

@section('content')

{{-- Success / partial overlay --}}
<div class="gp-success-overlay" id="gpOverlay" role="dialog" aria-modal="true">
    <div class="gp-success-modal" id="gpModal">
        <div class="gp-check-wrap" id="gpModalIcon">
            {{-- Injecté par JS --}}
        </div>
        <div class="gp-success-title" id="gpModalTitle"></div>
        <div class="gp-success-sub"   id="gpModalSub"></div>
        <div class="gp-success-lines" id="gpModalLines"></div>
        <button class="gp-modal-close" id="gpModalClose">
            <i class="fa fa-rotate-left" style="margin-right:.4rem"></i> Nouveau décaissement
        </button>
    </div>
</div>

<div class="gp-wrap">

    {{-- KPI Strip --}}
    <div class="gp-kpi-strip">
        <div class="gp-kpi gp-kpi--green">
            <div class="gp-kpi-label"><i class="fa fa-arrow-down-left"></i> Encaissé</div>
            <div class="gp-kpi-value">{{ number_format($kpis['collected'] / 100, 0, ',', "\u{202F}") }}</div>
            <div class="gp-kpi-sub">FCFA — collections réussies</div>
        </div>
        <div class="gp-kpi gp-kpi--teal">
            <div class="gp-kpi-label"><i class="fa fa-arrow-up-right"></i> Décaissé</div>
            <div class="gp-kpi-value">{{ number_format($kpis['disbursed'] / 100, 0, ',', "\u{202F}") }}</div>
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
                <div class="gp-card" id="gpFormCard">
                    <div class="gp-card-title">
                        <i class="fa fa-paper-plane" style="color:var(--gp-teal)"></i>
                        Nouveau lot de décaissement
                    </div>

                    @php $disbEnabled = config('gepay.bantudelice.withdrawals_enabled', false) @endphp
                    @if(! $disbEnabled)
                    <div class="gp-flag gp-flag--warn">
                        <i class="fa fa-triangle-exclamation"></i>
                        <div>Le décaissement GePay est désactivé (<code>GEPAY_BANTUDELICE_WITHDRAWALS_ENABLED=false</code>). Les appels passeront en mode démo.</div>
                    </div>
                    @else
                    <div class="gp-flag gp-flag--ok">
                        <i class="fa fa-circle-check"></i>
                        <div>Décaissement GePay <strong>actif</strong> — les transferts sont <strong>réels</strong>.</div>
                    </div>
                    @endif

                    <div class="gp-recipients" id="gpRecipients">
                        {{-- Bénéficiaire 1 --}}
                        <div class="gp-recipient-card is-primary" data-idx="0">
                            <div class="gp-recipient-header">
                                <div class="gp-recipient-num">
                                    <span class="gp-recipient-num-dot" id="gpDot0">1</span>
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
                                    <input type="number" name="recipients[0][amount]" placeholder="5 000" min="100" required data-amount>
                                </div>
                            </div>
                        </div>
                    </div>

                    <button type="button" class="gp-add-btn" id="gpAddBtn">
                        <i class="fa fa-plus"></i>
                        Ajouter un bénéficiaire <span style="opacity:.5;margin-left:.2rem">(max 3)</span>
                    </button>

                    <div class="gp-error-banner" id="gpErrorBanner">
                        <i class="fa fa-circle-xmark" style="flex-shrink:0;margin-top:.1rem"></i>
                        <div id="gpErrorText"></div>
                    </div>
                </div>
            </div>

            {{-- Right: summary --}}
            <div class="gp-summary">
                <div class="gp-summary-card">
                    <div class="gp-summary-title">Récapitulatif lot</div>
                    <div class="gp-summary-rows" id="gpSummaryRows">
                        <div class="gp-summary-row" id="gpSRow0">
                            <span class="gp-summary-row-name">Bénéficiaire 1</span>
                            <span class="gp-summary-row-amount" id="gpAmt0">—</span>
                        </div>
                    </div>
                    <hr class="gp-summary-divider">
                    <div class="gp-summary-total">
                        <span class="gp-summary-total-label">Total</span>
                        <span class="gp-summary-total-value" id="gpTotal">0 FCFA</span>
                    </div>
                </div>

                <button class="gp-submit-btn" id="gpSubmitBtn" disabled>
                    <i class="fa fa-paper-plane" id="gpBtnIcon"></i>
                    <span id="gpBtnLabel">Exécuter le décaissement</span>
                </button>
            </div>
        </div>
    </div>

    {{-- PANEL: Encaissements --}}
    <div class="gp-panel" id="panel-inbound">
        <div class="gp-card">
            <div class="gp-card-title">
                <i class="fa fa-list-ul" style="color:var(--gp-green)"></i>
                Journal des transactions
            </div>

            <div class="gp-filter-bar">
                <button class="gp-filter-btn is-active" data-filter="all">Tous</button>
                <button class="gp-filter-btn" data-filter="collection">Encaissements</button>
                <button class="gp-filter-btn" data-filter="disbursement">Décaissements</button>
            </div>

            <div class="gp-table-wrap">
                <table class="gp-table">
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
                        <tr data-type="{{ $tx->type->value }}">
                            <td>
                                <span class="gp-type-badge gp-type-badge--{{ $tx->type->value }}">
                                    <i class="fa fa-{{ $tx->type->value === 'collection' ? 'arrow-down-left' : 'arrow-up-right' }}" style="font-size:.55rem"></i>
                                    {{ $tx->type->value === 'collection' ? 'Encaissement' : 'Décaissement' }}
                                </span>
                            </td>
                            <td><span class="gp-mono">{{ Str::limit($tx->external_reference ?? '—', 22) }}</span></td>
                            <td><span class="gp-mono">{{ $tx->phone_masked ?? '—' }}</span></td>
                            <td class="{{ $tx->type->value === 'collection' ? 'gp-amount-in' : 'gp-amount-out' }}">
                                {{ number_format($tx->amount / 100, 0, ',', "\u{202F}") }} FCFA
                            </td>
                            <td>
                                <span class="gp-status-pill gp-status-pill--{{ $tx->status->value }}">
                                    <span class="gp-status-dot"></span>
                                    {{ ucfirst($tx->status->value) }}
                                </span>
                            </td>
                            <td class="gp-mono">{{ $tx->created_at?->format('d/m H:i') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="gp-empty"><i class="fa fa-inbox"></i>Aucune transaction</td></tr>
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

/* ─────────────────────────────────────────
   TABS
───────────────────────────────────────── */
document.querySelectorAll('.gp-tab[data-tab]').forEach(function (btn) {
    btn.addEventListener('click', function () {
        document.querySelectorAll('.gp-tab[data-tab]').forEach(function (b) { b.classList.remove('is-active'); });
        document.querySelectorAll('.gp-panel').forEach(function (p) { p.classList.remove('is-active'); });
        btn.classList.add('is-active');
        document.getElementById('panel-' + btn.dataset.tab).classList.add('is-active');
    });
});

/* ─────────────────────────────────────────
   FORMAT
───────────────────────────────────────── */
function fmt(n) {
    return Number(n || 0).toLocaleString('fr-FR') + ' FCFA';
}

/* ─────────────────────────────────────────
   RECIPIENT MANAGEMENT
───────────────────────────────────────── */
var MAX = 3;
var count = 1;
var container  = document.getElementById('gpRecipients');
var addBtn     = document.getElementById('gpAddBtn');
var submitBtn  = document.getElementById('gpSubmitBtn');
var totalEl    = document.getElementById('gpTotal');
var summaryEl  = document.getElementById('gpSummaryRows');
var errorBanner = document.getElementById('gpErrorBanner');
var errorText   = document.getElementById('gpErrorText');

function updateSummary() {
    var total = 0;
    document.querySelectorAll('[data-amount]').forEach(function (inp, i) {
        var v = parseInt(inp.value, 10) || 0;
        total += v;
        var el = document.getElementById('gpAmt' + i);
        if (el) el.textContent = v ? fmt(v) : '—';
    });
    totalEl.textContent = fmt(total);
    submitBtn.disabled = (total === 0);
}

function rebuildConnectors() {
    document.querySelectorAll('.gp-connector').forEach(function (c) { c.remove(); });
    var cards = Array.from(container.querySelectorAll('.gp-recipient-card'));
    if (cards.length < 2) return;
    cards.forEach(function (card, i) {
        if (i < cards.length - 1) {
            var wrap = document.createElement('div');
            wrap.innerHTML = '<div class="gp-connector"><div class="gp-connector-line"></div>'
                + '<span class="gp-connector-badge">LOT · ' + cards.length + ' DEST.</span></div>';
            card.after(wrap.firstChild);
        }
    });
}

function addCard() {
    if (count >= MAX) return;
    var idx = count;
    var card = document.createElement('div');
    card.className = 'gp-recipient-card';
    card.dataset.idx = idx;
    card.innerHTML =
        '<div class="gp-recipient-header">'
        + '<div class="gp-recipient-num">'
        + '<span class="gp-recipient-num-dot" id="gpDot' + idx + '">' + (idx + 1) + '</span>'
        + 'Bénéficiaire ' + (idx + 1)
        + '</div>'
        + '<button type="button" class="gp-recipient-remove" onclick="gpRemove(this)" aria-label="Supprimer">'
        + '<i class="fa fa-xmark"></i></button>'
        + '</div>'
        + '<div class="gp-recipient-fields">'
        + '<div class="gp-field gp-field--full"><label>Nom complet</label>'
        + '<input type="text" name="recipients[' + idx + '][name]" placeholder="Nom du bénéficiaire" required></div>'
        + '<div class="gp-field"><label>Téléphone MTN</label>'
        + '<input type="tel" name="recipients[' + idx + '][phone]" placeholder="068 000 000" required></div>'
        + '<div class="gp-field"><label>Montant (FCFA)</label>'
        + '<input type="number" name="recipients[' + idx + '][amount]" placeholder="5 000" min="100" required data-amount></div>'
        + '</div>';

    /* summary row */
    var srow = document.createElement('div');
    srow.className = 'gp-summary-row';
    srow.id = 'gpSRow' + idx;
    srow.innerHTML = '<span class="gp-summary-row-name">Bénéficiaire ' + (idx + 1) + '</span>'
        + '<span class="gp-summary-row-amount" id="gpAmt' + idx + '">—</span>';
    summaryEl.appendChild(srow);

    container.appendChild(card);
    count++;
    card.querySelector('[data-amount]').addEventListener('input', updateSummary);
    rebuildConnectors();
    addBtn.disabled = (count >= MAX);
    updateSummary();
}

window.gpRemove = function (btn) {
    var card = btn.closest('.gp-recipient-card');
    var idx  = parseInt(card.dataset.idx, 10);
    var srow = document.getElementById('gpSRow' + idx);
    if (srow) srow.remove();
    card.remove();
    count--;
    addBtn.disabled = false;
    rebuildConnectors();
    updateSummary();
};

addBtn.addEventListener('click', addCard);
document.querySelector('[data-amount]').addEventListener('input', updateSummary);

/* ─────────────────────────────────────────
   BUTTON STATE MACHINE
───────────────────────────────────────── */
var BTN_ICON  = document.getElementById('gpBtnIcon');
var BTN_LABEL = document.getElementById('gpBtnLabel');

function btnIdle() {
    submitBtn.disabled = false;
    submitBtn.className = 'gp-submit-btn';
    BTN_ICON.className  = 'fa fa-paper-plane';
    BTN_LABEL.textContent = 'Exécuter le décaissement';
    updateSummary();
}
function btnLoading() {
    submitBtn.disabled = true;
    submitBtn.className = 'gp-submit-btn is-loading';
    BTN_ICON.className  = 'fa fa-rotate gp-spin';
    BTN_LABEL.textContent = 'Traitement en cours…';
}
function btnSuccess() {
    submitBtn.className = 'gp-submit-btn is-success';
    BTN_ICON.className  = 'fa fa-check';
    BTN_LABEL.textContent = 'Décaissement envoyé';
}
function btnError() {
    submitBtn.className = 'gp-submit-btn is-error';
    BTN_ICON.className  = 'fa fa-xmark';
    BTN_LABEL.textContent = 'Réessayer';
    setTimeout(btnIdle, 2200);
}

/* ─────────────────────────────────────────
   PER-CARD STATES
───────────────────────────────────────── */
function setCardState(idx, state) {
    var card = container.querySelector('[data-idx="' + idx + '"]');
    if (!card) return;
    card.classList.remove('state-loading', 'state-success', 'state-error');
    if (state) card.classList.add('state-' + state);
    var dot = document.getElementById('gpDot' + idx);
    if (!dot) return;
    if (state === 'loading')  dot.innerHTML = '<i class="fa fa-rotate gp-spin" style="font-size:.55rem"></i>';
    if (state === 'success')  dot.innerHTML = '<i class="fa fa-check" style="font-size:.55rem"></i>';
    if (state === 'error')    dot.innerHTML = '<i class="fa fa-xmark" style="font-size:.55rem"></i>';
    if (!state)               dot.textContent = (idx + 1);
}

function setInputsDisabled(disabled) {
    container.querySelectorAll('input').forEach(function (inp) { inp.disabled = disabled; });
    addBtn.disabled = disabled;
}

/* ─────────────────────────────────────────
   SUCCESS OVERLAY
───────────────────────────────────────── */
var overlay = document.getElementById('gpOverlay');

function showSuccess(batchId, results) {
    var allOk = results.every(function (r) { return r.success; });
    var someOk = results.some(function (r) { return r.success; });

    document.getElementById('gpModalIcon').innerHTML = allOk || someOk
        ? '<svg class="gp-check-svg" viewBox="0 0 52 52"><circle class="gp-check-circle" cx="26" cy="26" r="23"/><path class="gp-check-path" fill="none" d="M14 27l7.5 7.5L38 18"/></svg>'
        : '<svg class="gp-error-svg" viewBox="0 0 52 52"><circle class="gp-error-circle" cx="26" cy="26" r="23"/><line class="gp-error-line1" x1="17" y1="17" x2="35" y2="35"/><line class="gp-error-line2" x1="35" y1="17" x2="17" y2="35"/></svg>';

    document.getElementById('gpModalTitle').textContent = allOk
        ? 'Décaissement réussi' : (someOk ? 'Lot partiellement envoyé' : 'Échec du décaissement');
    document.getElementById('gpModalSub').textContent = 'Lot #' + batchId
        + ' · ' + results.length + ' bénéficiaire' + (results.length > 1 ? 's' : '');

    var lines = '';
    results.forEach(function (r) {
        var cls = r.success ? 'ok' : 'err';
        var ico = r.success ? 'circle-check' : 'circle-xmark';
        lines += '<div class="gp-success-line gp-success-line--' + cls + '">'
            + '<i class="fa fa-' + ico + '"></i>'
            + '<span class="gp-sl-name">' + r.name + '</span>'
            + '<span class="gp-sl-amount">' + (r.amount ? fmt(r.amount / 100) : '') + '</span>'
            + '</div>';
        if (!r.success && r.message) {
            lines += '<div style="font-size:.63rem;color:var(--gp-red);margin:-.2rem 0 .25rem 1.4rem">'
                + r.message + '</div>';
        }
    });
    document.getElementById('gpModalLines').innerHTML = lines;
    overlay.classList.add('is-visible');
}

function showError(msg, results) {
    var html = msg || 'Erreur de traitement.';
    if (results && results.length) {
        html += '<ul>';
        results.forEach(function (r) {
            if (!r.success) html += '<li><strong>' + r.name + '</strong> — ' + (r.message || 'Échec') + '</li>';
        });
        html += '</ul>';
    }
    errorText.innerHTML = html;
    errorBanner.classList.add('is-visible');
    /* shake the form card */
    var card = document.getElementById('gpFormCard');
    card.classList.remove('gp-shake');
    void card.offsetWidth; /* reflow */
    card.classList.add('gp-shake');
    setTimeout(function () { card.classList.remove('gp-shake'); }, 600);
}

document.getElementById('gpModalClose').addEventListener('click', function () {
    overlay.classList.remove('is-visible');
    btnIdle();
    setInputsDisabled(false);
    /* reset cards */
    container.querySelectorAll('.gp-recipient-card').forEach(function (c, i) {
        c.classList.remove('state-loading', 'state-success', 'state-error');
        var dot = document.getElementById('gpDot' + c.dataset.idx);
        if (dot) dot.textContent = parseInt(c.dataset.idx, 10) + 1;
        c.querySelectorAll('input').forEach(function (inp) { inp.value = ''; });
    });
    updateSummary();
    errorBanner.classList.remove('is-visible');
});

/* ─────────────────────────────────────────
   SUBMIT
───────────────────────────────────────── */
submitBtn.addEventListener('click', function () {
    var inputs = container.querySelectorAll('input[required]');
    var valid = true;
    inputs.forEach(function (inp) { if (!inp.value.trim()) { inp.focus(); valid = false; } });
    if (!valid) return;

    errorBanner.classList.remove('is-visible');
    btnLoading();
    setInputsDisabled(true);

    /* mark all cards loading */
    container.querySelectorAll('.gp-recipient-card').forEach(function (c) {
        setCardState(parseInt(c.dataset.idx, 10), 'loading');
    });

    var payload = { recipients: [] };
    container.querySelectorAll('.gp-recipient-card').forEach(function (card) {
        var idx = card.dataset.idx;
        payload.recipients.push({
            name:   card.querySelector('[name="recipients[' + idx + '][name]"]').value.trim(),
            phone:  card.querySelector('[name="recipients[' + idx + '][phone]"]').value.trim(),
            amount: parseInt(card.querySelector('[name="recipients[' + idx + '][amount]"]').value, 10),
        });
    });

    fetch('{{ route("admin.gepay.disburse") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json',
        },
        body: JSON.stringify(payload),
    })
    .then(function (res) { return res.json(); })
    .then(function (data) {
        var results = data.results || [];

        /* per-card state */
        results.forEach(function (r, i) {
            var cards = container.querySelectorAll('.gp-recipient-card');
            if (cards[i]) setCardState(parseInt(cards[i].dataset.idx, 10), r.success ? 'success' : 'error');
        });

        var allOk = results.length > 0 && results.every(function (r) { return r.success; });
        var anyOk = results.some(function (r) { return r.success; });

        if (allOk || anyOk) {
            btnSuccess();
            showSuccess(data.batch_id || '???', results);
        } else {
            btnError();
            setInputsDisabled(false);
            showError(data.message || 'Aucun décaissement réussi.', results);
        }
    })
    .catch(function (err) {
        container.querySelectorAll('.gp-recipient-card').forEach(function (c) {
            c.classList.remove('state-loading');
        });
        btnError();
        setInputsDisabled(false);
        showError('Erreur réseau : ' + err.message, null);
    });
});

/* ─────────────────────────────────────────
   TABLE FILTER
───────────────────────────────────────── */
document.querySelectorAll('.gp-filter-btn').forEach(function (btn) {
    btn.addEventListener('click', function () {
        document.querySelectorAll('.gp-filter-btn').forEach(function (b) { b.classList.remove('is-active'); });
        btn.classList.add('is-active');
        var f = btn.dataset.filter;
        document.querySelectorAll('#gpTxBody tr[data-type]').forEach(function (row) {
            row.style.display = (f === 'all' || row.dataset.type === f) ? '' : 'none';
        });
    });
});

})();
</script>
@endpush
