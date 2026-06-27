@extends('layouts.restaurant_app')
@section('title', 'Finances | ' . \App\Services\ConfigService::getCompanyName())
@section('topbar_title', 'Finances')
@section('earnings_nav', 'active')

@section('style')
<style>
.fin { display: flex; flex-direction: column; gap: 20px; }

/* ── KPIs financiers ──────────────────────────────────────── */
.fin-kpis {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 12px;
}
@media (max-width: 768px) { .fin-kpis { grid-template-columns: 1fr 1fr; } }
@media (max-width: 480px) { .fin-kpis { grid-template-columns: 1fr; } }

.fin-kpi {
    background: var(--bd-surface);
    border: 1px solid var(--bd-border);
    border-radius: var(--bd-radius);
    padding: 18px 20px 16px;
    display: flex; flex-direction: column; gap: 4px;
    position: relative; overflow: hidden;
    transition: border-color .12s, background .2s;
}
.fin-kpi__accent {
    position: absolute; top: 0; left: 0; right: 0; height: 3px;
    border-radius: var(--bd-radius) var(--bd-radius) 0 0;
}
.fin-kpi__accent--green  { background: var(--bd-green); }
.fin-kpi__accent--amber  { background: #f59e0b; }
.fin-kpi__accent--blue   { background: #3b82f6; }
.fin-kpi__label { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .05em; color: var(--bd-text-3); margin-top: 4px; }
.fin-kpi__value {
    font-family: var(--bd-font-display, 'League Spartan', sans-serif);
    font-size: 22px; font-weight: 800; color: var(--bd-text);
    line-height: 1.1;
}
.fin-kpi__cur { font-size: 12px; font-weight: 600; color: var(--bd-text-3); font-family: var(--bd-font); }
.fin-kpi__hint { font-size: 11px; color: var(--bd-text-3); margin-top: 2px; }

/* ── Finance cards (ledger) ───────────────────────────────── */
.fin-ledger {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 12px;
}
@media (max-width: 900px) { .fin-ledger { grid-template-columns: 1fr 1fr; } }
@media (max-width: 480px) { .fin-ledger { grid-template-columns: 1fr; } }

.fin-ledger-card {
    background: var(--bd-surface);
    border: 1px solid var(--bd-border);
    border-radius: var(--bd-radius);
    padding: 16px 18px;
    display: flex; flex-direction: column; gap: 6px;
    transition: background .2s;
}
.fin-ledger-card.is-success  { border-color: rgba(0,149,67,.25);   background: rgba(0,149,67,.04); }
.fin-ledger-card.is-warning  { border-color: rgba(245,158,11,.25); background: rgba(245,158,11,.04); }
.fin-ledger-card.is-orange   { border-color: rgba(234,88,12,.25);  background: rgba(234,88,12,.04); }
.fin-ledger-card.is-primary  { border-color: rgba(59,130,246,.25); background: rgba(59,130,246,.04); }
.fin-ledger-card.is-neutral  { border-color: var(--bd-border); }
[data-theme="dark"] .fin-ledger-card.is-success { background: rgba(0,201,87,.06); border-color: rgba(0,201,87,.25); }
[data-theme="dark"] .fin-ledger-card.is-warning { background: rgba(251,191,36,.06); border-color: rgba(251,191,36,.25); }
[data-theme="dark"] .fin-ledger-card.is-orange  { background: rgba(251,146,60,.06); border-color: rgba(251,146,60,.25); }
[data-theme="dark"] .fin-ledger-card.is-primary { background: rgba(96,165,250,.06); border-color: rgba(96,165,250,.25); }

.fin-ledger-card__label { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .05em; color: var(--bd-text-3); }
.fin-ledger-card__amount {
    font-family: var(--bd-font-display, 'League Spartan', sans-serif);
    font-size: 18px; font-weight: 800; color: var(--bd-text); line-height: 1;
}
.fin-ledger-card__cur  { font-size: 11px; font-weight: 600; color: var(--bd-text-3); font-family: var(--bd-font); }
.fin-ledger-card__desc { font-size: 11px; color: var(--bd-text-3); line-height: 1.5; }

/* ── Tableau reversements ─────────────────────────────────── */
.fin-card {
    background: var(--bd-surface);
    border: 1px solid var(--bd-border);
    border-radius: var(--bd-radius);
    overflow: hidden;
}
.fin-card__head {
    display: flex; align-items: center; justify-content: space-between;
    gap: 12px; padding: 14px 20px;
    border-bottom: 1px solid var(--bd-border-2);
    flex-wrap: wrap;
}
.fin-card__title { font-size: 13px; font-weight: 600; color: var(--bd-text); }
.fin-card__sub   { font-size: 11px; color: var(--bd-text-3); margin-top: 2px; }

.fin-btn {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 7px 14px; border-radius: var(--bd-radius);
    font-size: 12px; font-weight: 600; cursor: pointer;
    font-family: var(--bd-font); border: none; transition: .12s;
    text-decoration: none;
}
.fin-btn--primary { background: var(--bd-green); color: #fff; }
.fin-btn--primary:hover { background: var(--bd-green-dark, #007836); color: #fff; }

.fin-table-wrap { overflow-x: auto; }
.fin-table {
    width: 100%; border-collapse: collapse;
    font-size: 13px; font-family: var(--bd-font-body, 'Inter', sans-serif);
}
.fin-table thead th {
    padding: 9px 16px;
    font-size: 10px; font-weight: 700; letter-spacing: .06em;
    text-transform: uppercase; color: var(--bd-text-3);
    border-bottom: 1px solid var(--bd-border-2);
    background: var(--bd-surface-2); text-align: left; white-space: nowrap;
}
.fin-table tbody tr { border-bottom: 1px solid var(--bd-border-2); transition: background .1s; }
.fin-table tbody tr:last-child { border-bottom: none; }
.fin-table tbody tr:hover { background: var(--bd-surface-2); }
.fin-table td { padding: 11px 16px; color: var(--bd-text-2); vertical-align: middle; }

.fin-ref { font-family: monospace; font-size: 12px; color: var(--bd-text-2); }
.fin-amount {
    font-family: var(--bd-font-display, 'League Spartan', sans-serif);
    font-size: 15px; font-weight: 800; color: var(--bd-text); white-space: nowrap;
}
.fin-amount-cur { font-size: 10px; font-weight: 600; color: var(--bd-text-3); font-family: var(--bd-font); }

.fin-badge {
    display: inline-flex; align-items: center; gap: 4px;
    padding: 3px 9px; border-radius: 999px;
    font-size: 11px; font-weight: 700;
}
.fin-badge--paid    { background: rgba(0,149,67,.1);    color: var(--bd-green); }
.fin-badge--pending { background: rgba(245,158,11,.12); color: #d97706; }
.fin-badge--default { background: var(--bd-surface-2);  color: var(--bd-text-3); }
[data-theme="dark"] .fin-badge--paid    { background: rgba(0,201,87,.15);  color: #00c957; }
[data-theme="dark"] .fin-badge--pending { background: rgba(251,191,36,.15); color: #fbbf24; }

.fin-empty {
    padding: 40px 20px; text-align: center;
    color: var(--bd-text-3); font-size: 13px;
}
.fin-empty i { font-size: 28px; display: block; margin-bottom: 10px; color: var(--bd-border); }

/* ── Formulaire retrait ───────────────────────────────────── */
.fin-withdraw {
    background: var(--bd-surface);
    border: 1px solid var(--bd-border);
    border-radius: var(--bd-radius);
    padding: 24px 28px;
}
.fin-withdraw__title { font-size: 14px; font-weight: 700; color: var(--bd-text); margin-bottom: 4px; }
.fin-withdraw__sub   { font-size: 12px; color: var(--bd-text-3); margin-bottom: 18px; }
.fin-withdraw__row   { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
.fin-withdraw__input {
    padding: 8px 12px; border: 1px solid var(--bd-border); border-radius: var(--bd-radius);
    font-size: 13px; font-family: var(--bd-font); background: var(--bd-surface);
    color: var(--bd-text); min-width: 160px; outline: none;
    transition: border-color .12s;
}
.fin-withdraw__input:focus { border-color: var(--bd-green); }
.fin-withdraw__note { font-size: 11px; color: var(--bd-text-3); margin-top: 10px; }

/* ── Self-service withdraw UI ────────────────────────────────── */
.wd-btn-open{display:inline-flex;align-items:center;gap:8px;padding:12px 24px;background:#16a34a;color:#fff;border:none;border-radius:8px;font-size:14px;font-weight:700;cursor:pointer;transition:opacity .15s;}
.wd-btn-open:hover{opacity:.88;}
.wd-avail-hint{font-size:13px;color:#6b7280;margin-top:8px;}
.wd-empty-balance{display:flex;align-items:center;gap:8px;padding:14px 16px;background:#f9fafb;border:1px solid #e5e7eb;border-radius:8px;font-size:13px;color:#9ca3af;}
.wd-dialog{border:none;border-radius:14px;padding:0;max-width:480px;width:100%;box-shadow:0 24px 64px rgba(0,0,0,.18);}
.wd-dialog::backdrop{background:rgba(0,0,0,.45);}
.wd-dialog__header{display:flex;align-items:center;justify-content:space-between;padding:18px 22px;border-bottom:1px solid #f3f4f6;}
.wd-dialog__title{display:flex;align-items:center;gap:8px;font-size:15px;font-weight:700;color:#111827;}
.wd-dialog__close{background:none;border:none;cursor:pointer;padding:4px;border-radius:6px;color:#9ca3af;line-height:1;}
.wd-dialog__close:hover{background:#f3f4f6;color:#374151;}
.wd-dialog__body{padding:22px;}
.wd-dialog__footer{display:flex;justify-content:flex-end;gap:10px;padding:16px 22px;border-top:1px solid #f3f4f6;}
.wd-section-label{font-size:12px;font-weight:700;color:#6b7280;text-transform:uppercase;letter-spacing:.06em;margin-bottom:10px;}
.wd-operators{display:flex;flex-direction:column;gap:10px;}
.wd-op-card{display:flex;align-items:center;gap:14px;padding:14px 16px;border:2px solid #e5e7eb;border-radius:10px;cursor:pointer;background:#fff;transition:border-color .15s;width:100%;text-align:left;}
.wd-op-card--active{border-color:#16a34a;background:#f0fdf4;}
.wd-op-card--disabled{opacity:.55;cursor:not-allowed;background:#f9fafb;}
.wd-op-logo{width:44px;height:44px;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:10px;font-weight:800;flex-shrink:0;}
.wd-op-logo--mtn{background:#ffcb05;color:#1a1a1a;}
.wd-op-logo--airtel{background:#e4002b;color:#fff;}
.wd-op-name{font-size:14px;font-weight:700;color:#111827;}
.wd-op-limits{font-size:12px;color:#6b7280;margin-top:2px;}
.wd-op-check{margin-left:auto;flex-shrink:0;}
.wd-op-badge{margin-left:auto;font-size:11px;font-weight:600;color:#9ca3af;background:#f3f4f6;padding:3px 8px;border-radius:20px;flex-shrink:0;}
.wd-field{margin-bottom:16px;}
.wd-label{display:block;font-size:13px;font-weight:600;color:#374151;margin-bottom:6px;}
.wd-phone-display{display:flex;align-items:center;gap:8px;padding:10px 14px;background:#f9fafb;border:1px solid #e5e7eb;border-radius:8px;font-size:14px;color:#374151;}
.wd-amount-wrap{display:flex;gap:8px;}
.wd-input{flex:1;padding:10px 14px;border:1px solid #d1d5db;border-radius:8px;font-size:14px;color:#111827;}
.wd-input:focus{outline:none;border-color:#16a34a;box-shadow:0 0 0 3px rgba(22,163,74,.12);}
.wd-btn-max{padding:10px 14px;background:#f3f4f6;border:1px solid #e5e7eb;border-radius:8px;font-size:12px;font-weight:600;color:#374151;cursor:pointer;white-space:nowrap;}
.wd-btn-max:hover{background:#e5e7eb;}
.wd-hint{font-size:12px;color:#9ca3af;margin-top:6px;}
.wd-btn-cancel{padding:10px 18px;border:1px solid #d1d5db;border-radius:8px;font-size:13px;font-weight:600;color:#374151;background:#fff;cursor:pointer;}
.wd-btn-cancel:hover{background:#f9fafb;}
.wd-btn-confirm{display:inline-flex;align-items:center;gap:8px;padding:10px 20px;background:#16a34a;color:#fff;border:none;border-radius:8px;font-size:13px;font-weight:700;cursor:pointer;transition:opacity .15s;}
.wd-btn-confirm:hover{opacity:.88;}
.wd-btn-confirm:disabled{opacity:.5;cursor:not-allowed;}
.wd-feedback{padding:12px 16px;border-radius:8px;font-size:13px;font-weight:500;margin-top:14px;}
.wd-feedback--success{background:#f0fdf4;color:#166534;border:1px solid #bbf7d0;}
.wd-feedback--error{background:#fef2f2;color:#991b1b;border:1px solid #fecaca;}
.wd-feedback--info{background:#eff6ff;color:#1e40af;border:1px solid #bfdbfe;}
@keyframes wd-spin{to{transform:rotate(360deg);}}
.wd-spin{animation:wd-spin 1s linear infinite;}
@media(max-width:480px){.wd-dialog{max-width:100%;border-radius:14px 14px 0 0;}}

</style>
@endsection

@section('content')
<div class="fin">

    @if(session()->has('alert'))
        <div class="alert alert-{{ session()->get('alert.type') }} alert-dismissible" role="alert">
            {{ session()->get('alert.message') }}
            <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
        </div>
    @endif

    {{-- ── KPIs ────────────────────────────────────────── --}}
    <div class="fin-kpis">
        <div class="fin-kpi">
            <div class="fin-kpi__accent fin-kpi__accent--green"></div>
            <div class="fin-kpi__label">CA brut cumulé</div>
            <div class="fin-kpi__value">{{ number_format(round($Total_Earning), 0, ',', ' ') }} <span class="fin-kpi__cur">FCFA</span></div>
            <div class="fin-kpi__hint">Total des commandes terminées</div>
        </div>
        <div class="fin-kpi">
            <div class="fin-kpi__accent fin-kpi__accent--amber"></div>
            <div class="fin-kpi__label">Cette semaine</div>
            <div class="fin-kpi__value">{{ number_format(round($this_week_earning), 0, ',', ' ') }} <span class="fin-kpi__cur">FCFA</span></div>
            <div class="fin-kpi__hint">Semaine en cours</div>
        </div>
        <div class="fin-kpi">
            <div class="fin-kpi__accent fin-kpi__accent--blue"></div>
            <div class="fin-kpi__label">Aujourd'hui</div>
            <div class="fin-kpi__value">{{ number_format(round($today_earning), 0, ',', ' ') }} <span class="fin-kpi__cur">FCFA</span></div>
            <div class="fin-kpi__hint">{{ now()->format('d/m/Y') }}</div>
        </div>
    </div>

    {{-- ── Ledger financier (PartnerFinancialDashboardService) ── --}}
    @if(!empty($financialDashboard['rows'] ?? []))
    @foreach($financialDashboard['rows'] as $row)
    <div class="fin-ledger">
        @foreach($row as $card)
        <div class="fin-ledger-card is-{{ $card['tone'] ?? 'neutral' }}">
            <div class="fin-ledger-card__label">{{ $card['label'] }}</div>
            <div class="fin-ledger-card__amount">
                {{ number_format(round((float)($card['amount'] ?? 0)), 0, ',', ' ') }}
                <span class="fin-ledger-card__cur">FCFA</span>
            </div>
            <div class="fin-ledger-card__desc">{{ $card['description'] ?? '' }}</div>
        </div>
        @endforeach
    </div>
    @endforeach
    @endif

    {{-- ── Historique des reversements ─────────────────── --}}
    <div class="fin-card">
        <div class="fin-card__head">
            <div>
                <div class="fin-card__title">Historique des reversements</div>
                <div class="fin-card__sub">{{ $history->count() }} versement(s) enregistré(s)</div>
            </div>
        </div>
        @if($history->isEmpty())
            <div class="fin-empty">
                <i class="fas fa-money-bill-transfer"></i>
                <p>Aucun reversement enregistré.</p>
            </div>
        @else
            <div class="fin-table-wrap">
                <table class="fin-table">
                    <thead>
                        <tr>
                            <th>Référence</th>
                            <th>Montant</th>
                            <th>Date</th>
                            <th>Statut</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($history as $his)
                        @php
                            $statusRaw = strtolower($his->status ?? '');
                            $badgeCls = match(true) {
                                in_array($statusRaw, ['paid', 'payé', 'completed']) => 'fin-badge--paid',
                                in_array($statusRaw, ['pending', 'en attente', 'processing']) => 'fin-badge--pending',
                                default => 'fin-badge--default',
                            };
                        @endphp
                        <tr>
                            <td><span class="fin-ref">{{ $his->transaction_id ?? '—' }}</span></td>
                            <td>
                                <span class="fin-amount">
                                    {{ number_format(round($his->payout_amount ?? 0), 0, ',', ' ') }}
                                    <span class="fin-amount-cur">FCFA</span>
                                </span>
                            </td>
                            <td style="font-size:12px;white-space:nowrap;">
                                {{ $his->created_at ? \Carbon\Carbon::parse($his->created_at)->format('d/m/Y H:i') : '—' }}
                            </td>
                            <td>
                                <span class="fin-badge {{ $badgeCls }}">{{ $his->status ?? '—' }}</span>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    {{-- ── Retrait self-service ──────────────────────────────── --}}
    <div id="wd-trigger-wrap" style="margin-top:24px;">
        @php
            $availableCard = collect($financialDashboard['cards'])->firstWhere('label', 'Disponible au retrait');
            $availableAmount = (int) round((float) ($availableCard['amount'] ?? 0));
        @endphp
        @if($availableAmount >= 500)
            <button class="wd-btn-open" onclick="document.getElementById('wd-modal').showModal()">
                <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                Retirer des fonds
            </button>
            <p class="wd-avail-hint">Disponible : <strong>{{ number_format($availableAmount, 0, ',', ' ') }} FCFA</strong></p>
        @else
            <div class="wd-empty-balance">
                <svg width="20" height="20" fill="none" stroke="#9ca3af" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path stroke-linecap="round" d="M12 8v4m0 4h.01"/></svg>
                Aucun montant disponible pour un retrait (minimum 500 FCFA).
            </div>
        @endif
    </div>
</div>

{{-- ── Modal retrait ──────────────────────────────────────────── --}}
<dialog class="wd-dialog" id="wd-modal">
    <div class="wd-dialog__header">
        <div class="wd-dialog__title">
            <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
            Retirer des fonds
        </div>
        <button class="wd-dialog__close" onclick="document.getElementById('wd-modal').close()">
            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
    </div>
    <div class="wd-dialog__body">
        {{-- Operator selection --}}
        <p class="wd-section-label">Choisissez votre opérateur</p>
        <div class="wd-operators">
            <button class="wd-op-card wd-op-card--active" id="wd-op-mtn" type="button" onclick="wdSelectOp('mtn')">
                <div class="wd-op-logo wd-op-logo--mtn">MTN</div>
                <div class="wd-op-info">
                    <div class="wd-op-name">MTN MoMo</div>
                    <div class="wd-op-limits">Min : 500 FCFA &nbsp;·&nbsp; Max : solde dispo</div>
                </div>
                <svg class="wd-op-check" width="18" height="18" fill="none" stroke="#16a34a" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
            </button>
            <div class="wd-op-card wd-op-card--disabled">
                <div class="wd-op-logo wd-op-logo--airtel">Airtel</div>
                <div class="wd-op-info">
                    <div class="wd-op-name">Airtel Money</div>
                    <div class="wd-op-limits">Bientôt disponible</div>
                </div>
                <span class="wd-op-badge">Indisponible</span>
            </div>
        </div>

        {{-- Phone (read-only from profile) --}}
        <div class="wd-field" style="margin-top:16px;">
            <label class="wd-label">Numéro MTN MoMo</label>
            <div class="wd-phone-display">
                <svg width="16" height="16" fill="none" stroke="#6b7280" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                {{ $financialDashboard['cards'][0]['description'] ?? '' }}
                <span id="wd-phone-display">{{ auth()->user()->restaurant()->first()->phone ?? 'Non configuré' }}</span>
            </div>
            <p class="wd-hint">Le retrait est effectué vers le numéro enregistré sur votre profil restaurant.</p>
        </div>

        {{-- Amount --}}
        <div class="wd-field">
            <label class="wd-label" for="wd-amount">Montant à retirer (FCFA)</label>
            <div class="wd-amount-wrap">
                <input type="number" id="wd-amount" class="wd-input" placeholder="Ex : 5000"
                       min="500" max="{{ $availableAmount ?? 0 }}" step="100">
                <button class="wd-btn-max" type="button" onclick="document.getElementById('wd-amount').value={{ $availableAmount ?? 0 }}">
                    Tout retirer
                </button>
            </div>
            <p class="wd-hint">Disponible : <strong>{{ number_format($availableAmount ?? 0, 0, ',', ' ') }} FCFA</strong> — minimum 500 FCFA</p>
        </div>

        {{-- Feedback --}}
        <div id="wd-feedback" class="wd-feedback" style="display:none;"></div>
    </div>
    <div class="wd-dialog__footer">
        <button type="button" class="wd-btn-cancel" onclick="document.getElementById('wd-modal').close()">Annuler</button>
        <button type="button" class="wd-btn-confirm" id="wd-confirm-btn" onclick="wdSubmit('restaurant')">
            <span id="wd-confirm-text">Confirmer le retrait</span>
            <svg id="wd-spinner" class="wd-spin" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="display:none"><path stroke-linecap="round" d="M4 12a8 8 0 018-8"/></svg>
        </button>
    </div>
</dialog>

@endsection

@section('script')
<script>
var _wdOp = 'mtn';
var _wdStatusUrl = '{{ route("withdrawals.status", ["withdrawal" => "__ID__"]) }}';
var _wdWithdrawUrl = '{{ route("restaurant.withdrawals.store") }}';
var _wdCsrf = '{{ csrf_token() }}';

function wdSelectOp(op) { _wdOp = op; }

function wdSubmit(type) {
    var amount = parseInt(document.getElementById('wd-amount').value, 10);
    var fb = document.getElementById('wd-feedback');
    var btn = document.getElementById('wd-confirm-btn');
    var txt = document.getElementById('wd-confirm-text');
    var spin = document.getElementById('wd-spinner');
    var available = {{ $availableAmount ?? 0 }};

    fb.style.display = 'none';
    fb.className = 'wd-feedback';

    if (!amount || amount < 500) { wdShowFb('Le montant minimum est 500 FCFA.', 'error'); return; }
    if (amount > available)       { wdShowFb('Montant supérieur au solde disponible (' + available.toLocaleString('fr') + ' FCFA).', 'error'); return; }

    btn.disabled = true; txt.style.opacity = '.5'; spin.style.display = 'inline';

    var key = 'wd-' + Date.now() + '-' + Math.random().toString(36).slice(2);
    fetch(_wdWithdrawUrl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': _wdCsrf, 'Idempotency-Key': key, 'Accept': 'application/json' },
        body: JSON.stringify({ amount: amount })
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        btn.disabled = false; txt.style.opacity = '1'; spin.style.display = 'none';
        if (data.status === 'paid') {
            wdShowFb(data.message, 'success');
            setTimeout(function() { location.reload(); }, 2500);
        } else if (!data.success) {
            wdShowFb(data.message, 'error');
        } else {
            wdShowFb(data.message + ' Vérification en cours…', 'info');
            if (data.withdrawal && data.withdrawal.id) wdPollStatus(data.withdrawal.id);
        }
    })
    .catch(function() {
        btn.disabled = false; txt.style.opacity = '1'; spin.style.display = 'none';
        wdShowFb('Erreur réseau. Veuillez réessayer.', 'error');
    });
}

function wdPollStatus(id) {
    var url = _wdStatusUrl.replace('__ID__', id);
    var attempts = 0;
    var poll = setInterval(function() {
        attempts++;
        fetch(url, { headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': _wdCsrf } })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (!data.withdrawal) return;
            var s = data.withdrawal.status;
            if (s === 'paid') {
                clearInterval(poll);
                wdShowFb('Retrait confirmé ! ' + data.withdrawal.amount.toLocaleString('fr') + ' FCFA envoyés sur votre MTN MoMo.', 'success');
                setTimeout(function() { location.reload(); }, 2000);
            } else if (s === 'failed' || s === 'cancelled' || s === 'reversed') {
                clearInterval(poll);
                wdShowFb(data.withdrawal.failure_message || 'Le retrait a échoué. Solde non débité.', 'error');
            } else if (attempts >= 10) {
                clearInterval(poll);
                wdShowFb('Traitement en cours. Vérifiez l'historique dans quelques minutes.', 'info');
            }
        });
    }, 3000);
}

function wdShowFb(msg, type) {
    var fb = document.getElementById('wd-feedback');
    fb.className = 'wd-feedback wd-feedback--' + type;
    fb.textContent = msg;
    fb.style.display = 'block';
}
</script>
@endsection