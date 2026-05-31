@extends('layouts.restaurant_app')
@section('title', 'Historique des paiements | ' . \App\Services\ConfigService::getCompanyName())
@section('topbar_title', 'Historique des paiements')
@section('earnings_nav', 'active')

@section('style')
<style>
.phist { display: flex; flex-direction: column; gap: 20px; }

.phist-kpis { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; }
@media (max-width: 768px) { .phist-kpis { grid-template-columns: 1fr 1fr; } }
@media (max-width: 480px) { .phist-kpis { grid-template-columns: 1fr; } }

.phist-kpi {
    background: var(--bd-surface); border: 1px solid var(--bd-border);
    border-radius: var(--bd-radius); padding: 16px 18px;
    position: relative; overflow: hidden;
}
.phist-kpi__accent { position: absolute; top: 0; left: 0; right: 0; height: 3px; border-radius: var(--bd-radius) var(--bd-radius) 0 0; }
.phist-kpi__accent--green  { background: var(--bd-green); }
.phist-kpi__accent--amber  { background: #f59e0b; }
.phist-kpi__accent--blue   { background: #3b82f6; }
.phist-kpi__label { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .05em; color: var(--bd-text-3); margin-top: 6px; }
.phist-kpi__value { font-family: var(--bd-font-display,'League Spartan',sans-serif); font-size: 20px; font-weight: 800; color: var(--bd-text); line-height: 1.1; margin-top: 4px; }
.phist-kpi__cur { font-size: 11px; font-weight: 600; color: var(--bd-text-3); font-family: var(--bd-font); }

.phist-tabs { display: flex; gap: 2px; background: var(--bd-surface-2); border: 1px solid var(--bd-border); border-radius: var(--bd-radius); padding: 3px; width: fit-content; }
.phist-tab {
    padding: 7px 16px; border-radius: calc(var(--bd-radius) - 2px);
    font-size: 12px; font-weight: 600; cursor: pointer;
    color: var(--bd-text-3); border: none; background: transparent;
    font-family: var(--bd-font); transition: .12s;
}
.phist-tab.active { background: var(--bd-surface); color: var(--bd-text); box-shadow: 0 1px 3px rgba(0,0,0,.08); }

.phist-panel { display: none; }
.phist-panel.active { display: block; }

.phist-card { background: var(--bd-surface); border: 1px solid var(--bd-border); border-radius: var(--bd-radius); overflow: hidden; }
.phist-card__head { padding: 14px 18px; border-bottom: 1px solid var(--bd-border-2); font-size: 13px; font-weight: 700; color: var(--bd-text); }
.phist-card__sub { font-size: 11px; color: var(--bd-text-3); font-weight: 400; margin-top: 1px; }

.phist-table-wrap { overflow-x: auto; }
.phist-table { width: 100%; border-collapse: collapse; font-size: 13px; }
.phist-table thead th {
    padding: 8px 14px; font-size: 10px; font-weight: 700;
    letter-spacing: .06em; text-transform: uppercase;
    color: var(--bd-text-3); border-bottom: 1px solid var(--bd-border-2);
    background: var(--bd-surface-2); text-align: left; white-space: nowrap;
}
.phist-table tbody tr { border-bottom: 1px solid var(--bd-border-2); transition: background .1s; }
.phist-table tbody tr:last-child { border-bottom: none; }
.phist-table tbody tr:hover { background: var(--bd-surface-2); }
.phist-table td { padding: 10px 14px; color: var(--bd-text-2); vertical-align: middle; }
.phist-amount { font-family: var(--bd-font-display,'League Spartan',sans-serif); font-size: 14px; font-weight: 800; color: var(--bd-text); white-space: nowrap; }
.phist-amount-cur { font-size: 10px; font-weight: 600; color: var(--bd-text-3); font-family: var(--bd-font); }
.phist-ref { font-family: monospace; font-size: 12px; }
.phist-badge {
    display: inline-flex; align-items: center; gap: 4px;
    padding: 2px 8px; border-radius: 999px;
    font-size: 11px; font-weight: 700;
    background: var(--bd-surface-2); color: var(--bd-text-3);
}
.phist-action-btn {
    display: inline-flex; align-items: center; justify-content: center;
    width: 28px; height: 28px; border-radius: 6px;
    border: 1px solid var(--bd-border); background: var(--bd-surface);
    color: var(--bd-text-2); cursor: pointer; font-size: 11px;
    transition: .12s; text-decoration: none;
}
.phist-action-btn:hover { border-color: var(--bd-green); color: var(--bd-green); }
.phist-empty { padding: 32px 20px; text-align: center; color: var(--bd-text-3); font-size: 13px; }
.phist-empty i { font-size: 24px; display: block; margin-bottom: 8px; color: var(--bd-border); }
</style>
@endsection

@section('content')
<div class="phist">

    @if(session()->has('alert'))
        <div class="alert alert-{{ session()->get('alert.type') }} alert-dismissible" role="alert">
            {{ session()->get('alert.message') }}
            <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
        </div>
    @endif

    {{-- ── KPIs ──────────────────────────────────────── --}}
    <div class="phist-kpis">
        <div class="phist-kpi">
            <div class="phist-kpi__accent phist-kpi__accent--green"></div>
            <div class="phist-kpi__label">Revenus totaux</div>
            <div class="phist-kpi__value">{{ number_format(round($Total_Earning ?? 0), 0, ',', ' ') }} <span class="phist-kpi__cur">FCFA</span></div>
        </div>
        <div class="phist-kpi">
            <div class="phist-kpi__accent phist-kpi__accent--amber"></div>
            <div class="phist-kpi__label">Cette semaine</div>
            <div class="phist-kpi__value">{{ number_format(round($this_week_earning ?? 0), 0, ',', ' ') }} <span class="phist-kpi__cur">FCFA</span></div>
        </div>
        <div class="phist-kpi">
            <div class="phist-kpi__accent phist-kpi__accent--blue"></div>
            <div class="phist-kpi__label">Aujourd'hui</div>
            <div class="phist-kpi__value">{{ number_format(round($today_earning ?? 0), 0, ',', ' ') }} <span class="phist-kpi__cur">FCFA</span></div>
        </div>
    </div>

    {{-- ── Onglets ────────────────────────────────────── --}}
    <div class="phist-tabs">
        <button class="phist-tab active" onclick="phistTab('hist', this)">Historique</button>
        <button class="phist-tab" onclick="phistTab('received', this)">Reçus</button>
        <button class="phist-tab" onclick="phistTab('sent', this)">Envoyés</button>
    </div>

    {{-- ── Historique ─────────────────────────────────── --}}
    <div class="phist-panel active" id="phist-panel-hist">
        <div class="phist-card">
            <div class="phist-card__head">
                Tous les paiements
                <div class="phist-card__sub">{{ count($payment_history ?? []) }} transaction(s)</div>
            </div>
            @if(empty($payment_history) || collect($payment_history)->isEmpty())
                <div class="phist-empty"><i class="fas fa-receipt"></i><p>Aucun paiement enregistré.</p></div>
            @else
                <div class="phist-table-wrap">
                    <table class="phist-table">
                        <thead><tr><th>#</th><th>N° commande</th><th>Type</th><th>Montant</th><th>Statut</th><th style="text-align:right;">Action</th></tr></thead>
                        <tbody>
                            @foreach($payment_history as $i => $payment)
                            <tr>
                                <td style="color:var(--bd-text-3);font-size:12px;">{{ $i + 1 }}</td>
                                <td><span class="phist-ref">{{ $payment->order_id ?? '—' }}</span></td>
                                <td>{{ $payment->payment_type ?? '—' }}</td>
                                <td><span class="phist-amount">{{ number_format((float)($payment->amount ?? 0), 0, ',', ' ') }}<span class="phist-amount-cur"> FCFA</span></span></td>
                                <td><span class="phist-badge">{{ $payment->status ?? '—' }}</span></td>
                                <td style="text-align:right;">
                                    <a href="{{ route('restaurant.show_order', $payment->order_id ?? '#') }}" class="phist-action-btn" title="Voir la commande">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    {{-- ── Paiements reçus ─────────────────────────────── --}}
    <div class="phist-panel" id="phist-panel-received">
        <div class="phist-card">
            <div class="phist-card__head">
                Paiements reçus
                <div class="phist-card__sub">{{ count($received_payments ?? []) }} transaction(s)</div>
            </div>
            @if(empty($received_payments) || collect($received_payments)->isEmpty())
                <div class="phist-empty"><i class="fas fa-arrow-down-to-line"></i><p>Aucun paiement reçu.</p></div>
            @else
                <div class="phist-table-wrap">
                    <table class="phist-table">
                        <thead><tr><th>#</th><th>N° commande</th><th>Type</th><th>Montant</th><th>Statut</th><th style="text-align:right;">Action</th></tr></thead>
                        <tbody>
                            @foreach($received_payments as $i => $payment)
                            <tr>
                                <td style="color:var(--bd-text-3);font-size:12px;">{{ $i + 1 }}</td>
                                <td><span class="phist-ref">{{ $payment->order_id ?? '—' }}</span></td>
                                <td>{{ $payment->payment_type ?? '—' }}</td>
                                <td><span class="phist-amount">{{ number_format((float)($payment->amount ?? 0), 0, ',', ' ') }}<span class="phist-amount-cur"> FCFA</span></span></td>
                                <td><span class="phist-badge">{{ $payment->status ?? '—' }}</span></td>
                                <td style="text-align:right;">
                                    <a href="{{ route('restaurant.show_order', $payment->order_id ?? '#') }}" class="phist-action-btn" title="Voir la commande">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    {{-- ── Paiements envoyés ───────────────────────────── --}}
    <div class="phist-panel" id="phist-panel-sent">
        <div class="phist-card">
            <div class="phist-card__head">
                Paiements envoyés
                <div class="phist-card__sub">{{ count($sent_payments ?? []) }} transaction(s)</div>
            </div>
            @if(empty($sent_payments) || collect($sent_payments)->isEmpty())
                <div class="phist-empty"><i class="fas fa-arrow-up-from-line"></i><p>Aucun paiement envoyé.</p></div>
            @else
                <div class="phist-table-wrap">
                    <table class="phist-table">
                        <thead><tr><th>#</th><th>N° commande</th><th>Type</th><th>Montant</th><th>Statut</th><th style="text-align:right;">Action</th></tr></thead>
                        <tbody>
                            @foreach($sent_payments as $i => $payment)
                            <tr>
                                <td style="color:var(--bd-text-3);font-size:12px;">{{ $i + 1 }}</td>
                                <td><span class="phist-ref">{{ $payment->order_id ?? '—' }}</span></td>
                                <td>{{ $payment->payment_type ?? '—' }}</td>
                                <td><span class="phist-amount">{{ number_format((float)($payment->amount ?? 0), 0, ',', ' ') }}<span class="phist-amount-cur"> FCFA</span></span></td>
                                <td><span class="phist-badge">{{ $payment->status ?? '—' }}</span></td>
                                <td style="text-align:right;">
                                    <a href="{{ route('restaurant.show_order', $payment->order_id ?? '#') }}" class="phist-action-btn" title="Voir la commande">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

</div>

<script>
function phistTab(name, btn) {
    document.querySelectorAll('.phist-tab').forEach(b => b.classList.remove('active'));
    document.querySelectorAll('.phist-panel').forEach(p => p.classList.remove('active'));
    btn.classList.add('active');
    document.getElementById('phist-panel-' + name).classList.add('active');
}
</script>
@endsection
