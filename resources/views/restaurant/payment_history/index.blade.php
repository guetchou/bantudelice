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

    {{-- ── Demande de reversement ───────────────────────── --}}
    <div class="fin-withdraw">
        <div class="fin-withdraw__title">Demander un reversement</div>
        <div class="fin-withdraw__sub">
            Montant déjà reversé : <strong>{{ number_format(round($withdrwan), 0, ',', ' ') }} FCFA</strong>
        </div>
        @if($withdrwan > 50)
            <form method="post" action="{{ route('r_earnings.store') }}">
                @csrf
                @php $rId = auth()->user()->restaurant()->value('id'); @endphp
                <input type="hidden" name="restaurant_id" value="{{ $rId }}">
                <div class="fin-withdraw__row">
                    <input type="number" class="fin-withdraw__input"
                           name="amount" placeholder="Montant demandé (FCFA)"
                           min="50" required>
                    <button type="submit" class="fin-btn fin-btn--primary">
                        <i class="fas fa-paper-plane"></i> Envoyer la demande
                    </button>
                </div>
                <div class="fin-withdraw__note">
                    Minimum : 50 FCFA. La demande sera traitée dans les meilleurs délais.
                </div>
            </form>
        @else
            <div style="font-size:12px;color:var(--bd-text-3);padding:12px 16px;background:var(--bd-surface-2);border-radius:var(--bd-radius);border:1px solid var(--bd-border-2);">
                <i class="fas fa-info-circle" style="margin-right:6px;color:var(--bd-text-3);"></i>
                Aucun montant disponible pour un reversement pour le moment.
            </div>
        @endif
    </div>

</div>
@endsection
