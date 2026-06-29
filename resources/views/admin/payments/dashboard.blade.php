@extends('layouts.admin-modern')

@section('title', 'Centre financier des paiements')
@section('page_title', 'Centre financier des paiements')
@section('nav_active', 'payments')

@php
    $summary = array_merge([
        'confirmed_amount' => 0,
        'allocated_amount' => 0,
        'held_amount' => 0,
        'unallocated_amount' => 0,
        'allocation_count' => 0,
        'open_cases' => 0,
        'critical_cases' => 0,
        'open_case_amount' => 0,
        'reserved_withdrawals' => 0,
        'paid_withdrawals' => 0,
        'provider_clearing' => 0,
        'customer_funds' => 0,
    ], $businessSummary ?? []);

    $businessHealth = array_merge([
        'tone' => 'neutral',
        'label' => 'Position non calculée',
        'message' => 'Les données financières ne sont pas encore disponibles.',
    ], $businessHealth ?? []);

    $decisionQueue = collect($caseQueue ?? []);
    if ($decisionQueue->isEmpty()) {
        $decisionQueue = collect($workQueue ?? [])->map(fn ($item) => [
            'case_id' => null,
            'type' => $item['status'] ?? 'exception',
            'severity' => $item['severity'] ?? 'warning',
            'summary' => $item['reason'] ?? 'Paiement à vérifier.',
            'payment_id' => $item['raw_id'] ?? null,
            'payment_reference' => $item['reference'] ?? ($item['id'] ?? '—'),
            'provider' => $item['provider'] ?? '—',
            'internal_status' => $item['status_label'] ?? ($item['status'] ?? '—'),
            'expected_amount' => $item['amount'] ?? 0,
            'observed_amount' => $item['amount'] ?? 0,
            'currency' => 'XAF',
            'age_label' => $item['age_label'] ?? '—',
            'can_reconcile' => $item['can_reconcile'] ?? false,
        ]);
    }

    $chartLabels = collect($hourlySeries['labels'] ?? [])->values();
    $chartAmounts = collect($hourlySeries['amounts'] ?? [])->map(fn ($value) => (float) $value)->values();
    $chartMax = max(1, (float) $chartAmounts->max());
    $chartItems = $chartLabels->map(fn ($label, $index) => [
        'label' => $label,
        'amount' => (float) $chartAmounts->get($index, 0),
    ]);
@endphp

@section('style')
<style>
.finpay{display:flex;flex-direction:column;gap:16px;max-width:1600px;margin:0 auto}.finpay-card,.finpay-filter,.finpay-health{background:#fff;border:1px solid #e5e7eb;border-radius:14px;box-shadow:0 1px 3px rgba(15,23,42,.04)}.finpay-head{display:flex;align-items:flex-start;justify-content:space-between;gap:16px;flex-wrap:wrap}.finpay-kicker{font-size:.62rem;font-weight:850;text-transform:uppercase;letter-spacing:.08em;color:#009543}.finpay-title{margin-top:4px;font-size:1.3rem;font-weight:900;color:#0f172a}.finpay-sub{max-width:780px;margin-top:5px;font-size:.72rem;line-height:1.55;color:#64748b}.finpay-actions{display:flex;gap:8px;flex-wrap:wrap}.finpay-btn{min-height:35px;display:inline-flex;align-items:center;justify-content:center;gap:7px;padding:0 12px;border:1px solid #dbe3ea;border-radius:9px;background:#fff;color:#334155;text-decoration:none;font:750 .68rem 'Poppins',sans-serif;cursor:pointer}.finpay-btn:hover{border-color:#94a3b8;color:#0f172a}.finpay-btn--primary{background:#009543;border-color:#009543;color:#fff}.finpay-btn--primary:hover{background:#007f39;color:#fff}.finpay-btn:disabled{opacity:.55;cursor:wait}.finpay-health{display:grid;grid-template-columns:40px minmax(0,1fr) auto;gap:12px;align-items:center;padding:13px 15px;border-left-width:4px}.finpay-health--success{border-left-color:#16a34a}.finpay-health--warning{border-left-color:#f59e0b}.finpay-health--danger{border-left-color:#dc2626}.finpay-health--neutral{border-left-color:#94a3b8}.finpay-health__icon{width:38px;height:38px;display:grid;place-items:center;border-radius:10px;background:#f8fafc;color:#475569}.finpay-health__title{font-size:.76rem;font-weight:850;color:#0f172a}.finpay-health__message{margin-top:2px;font-size:.65rem;color:#64748b}.finpay-health__time{font-size:.62rem;color:#94a3b8;white-space:nowrap}.finpay-filter{display:flex;align-items:end;gap:10px;padding:12px;flex-wrap:wrap}.finpay-field{display:flex;flex-direction:column;gap:5px}.finpay-field label{font-size:.58rem;font-weight:850;text-transform:uppercase;letter-spacing:.05em;color:#64748b}.finpay-select{min-width:165px;height:35px;padding:0 10px;border:1px solid #dbe3ea;border-radius:8px;background:#fff;color:#334155;font:650 .68rem 'Poppins',sans-serif}.finpay-periods{display:flex;padding:3px;border:1px solid #dbe3ea;border-radius:8px;background:#f8fafc}.finpay-period{min-width:45px;height:27px;display:grid;place-items:center;border-radius:6px;text-decoration:none;font-size:.64rem;font-weight:850;color:#64748b}.finpay-period.is-active{background:#fff;color:#009543;box-shadow:0 1px 3px rgba(15,23,42,.1)}.finpay-spacer{flex:1}.finpay-kpis{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px}.finpay-kpi{padding:16px}.finpay-kpi__label{font-size:.63rem;font-weight:850;color:#64748b}.finpay-kpi__value{margin-top:9px;font-size:1.3rem;font-weight:900;color:#0f172a;line-height:1.05}.finpay-kpi__meta{margin-top:6px;font-size:.61rem;line-height:1.45;color:#94a3b8}.finpay-kpi--danger .finpay-kpi__value{color:#b91c1c}.finpay-kpi--warning .finpay-kpi__value{color:#b45309}.finpay-position{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));border-top:1px solid #edf2f7}.finpay-position__item{padding:12px 15px;border-right:1px solid #edf2f7}.finpay-position__item:last-child{border-right:0}.finpay-position__label{font-size:.58rem;font-weight:850;color:#94a3b8;text-transform:uppercase;letter-spacing:.04em}.finpay-position__value{margin-top:4px;font-size:.82rem;font-weight:900;color:#0f172a}.finpay-grid{display:grid;grid-template-columns:minmax(0,1.45fr) minmax(320px,.75fr);gap:14px}.finpay-card{overflow:hidden}.finpay-card__head{min-height:56px;display:flex;align-items:center;justify-content:space-between;gap:12px;padding:12px 15px;border-bottom:1px solid #edf2f7}.finpay-card__title{font-size:.77rem;font-weight:850;color:#0f172a}.finpay-card__sub{margin-top:2px;font-size:.61rem;color:#94a3b8}.finpay-card__body{padding:15px}.finpay-badge{display:inline-flex;align-items:center;justify-content:center;gap:5px;padding:4px 8px;border-radius:999px;background:#f1f5f9;color:#475569;font-size:.58rem;font-weight:850;white-space:nowrap}.finpay-badge--critical,.finpay-badge--failed,.finpay-badge--unknown,.finpay-badge--reversed,.finpay-badge--disputed{background:#fee2e2;color:#991b1b}.finpay-badge--warning,.finpay-badge--pending,.finpay-badge--initiated,.finpay-badge--processing{background:#fef3c7;color:#92400e}.finpay-badge--paid,.finpay-badge--success,.finpay-badge--resolved{background:#dcfce7;color:#166534}.finpay-table-wrap{overflow-x:auto}.finpay-table{width:100%;border-collapse:collapse}.finpay-table th{padding:9px 11px;background:#f8fafc;border-bottom:1px solid #e5e7eb;color:#64748b;font-size:.56rem;font-weight:850;text-align:left;text-transform:uppercase;letter-spacing:.04em;white-space:nowrap}.finpay-table td{padding:10px 11px;border-bottom:1px solid #edf2f7;color:#475569;font-size:.66rem;vertical-align:middle}.finpay-table tr:last-child td{border-bottom:0}.finpay-table tbody tr:hover{background:#fbfdfc}.finpay-table strong{color:#0f172a;font-weight:850}.finpay-table small{display:block;margin-top:2px;font-size:.56rem;color:#94a3b8;line-height:1.35}.finpay-bars{height:180px;display:flex;align-items:flex-end;gap:5px}.finpay-bar-wrap{height:100%;min-width:0;flex:1;display:flex;flex-direction:column;justify-content:flex-end;align-items:center;gap:5px}.finpay-bar{width:100%;min-height:3px;border-radius:5px 5px 2px 2px;background:#009543}.finpay-bar-label{width:100%;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;text-align:center;font-size:.5rem;color:#94a3b8}.finpay-provider{padding:11px 0;border-bottom:1px solid #edf2f7}.finpay-provider:first-child{padding-top:0}.finpay-provider:last-child{padding-bottom:0;border-bottom:0}.finpay-provider__top{display:flex;justify-content:space-between;gap:10px}.finpay-provider__name{font-size:.69rem;font-weight:850;color:#0f172a}.finpay-provider__meta{margin-top:2px;font-size:.57rem;color:#94a3b8}.finpay-provider__amount{font-size:.68rem;font-weight:850;color:#0f172a;white-space:nowrap}.finpay-progress{height:5px;margin-top:8px;border-radius:999px;background:#edf2f7;overflow:hidden}.finpay-progress span{display:block;height:100%;border-radius:inherit;background:#009543}.finpay-alert{display:grid;grid-template-columns:7px minmax(0,1fr) auto;gap:9px;align-items:center;padding:9px 10px;border:1px solid #edf2f7;border-radius:9px;background:#fbfcfd}.finpay-alert+.finpay-alert{margin-top:7px}.finpay-alert__rail{width:7px;height:30px;border-radius:999px;background:#16a34a}.finpay-alert--warning .finpay-alert__rail{background:#f59e0b}.finpay-alert--danger .finpay-alert__rail{background:#dc2626}.finpay-alert__label{font-size:.65rem;font-weight:850;color:#0f172a}.finpay-alert__message{margin-top:2px;font-size:.57rem;color:#64748b;line-height:1.4}.finpay-alert__value{font-size:.82rem;font-weight:900;color:#0f172a}.finpay-search{width:min(100%,245px);height:33px;padding:0 10px 0 31px;border:1px solid #dbe3ea;border-radius:8px;background:#fff;color:#334155;font:650 .64rem 'Poppins',sans-serif}.finpay-search-wrap{position:relative}.finpay-search-wrap i{position:absolute;left:10px;top:50%;transform:translateY(-50%);font-size:.65rem;color:#94a3b8}.finpay-empty{padding:26px 15px;text-align:center;font-size:.67rem;color:#94a3b8}.finpay-empty i{display:block;margin-bottom:6px;font-size:1.2rem}.finpay-toast{position:fixed;right:18px;bottom:18px;z-index:500;display:none;max-width:360px;padding:11px 14px;border-radius:10px;background:#0f172a;color:#fff;font-size:.66rem;box-shadow:0 12px 30px rgba(15,23,42,.25)}.finpay-toast.is-visible{display:block}.finpay-toast.is-warning{background:#92400e}.finpay-toast.is-error{background:#991b1b}@media(max-width:1160px){.finpay-kpis,.finpay-position{grid-template-columns:repeat(2,minmax(0,1fr))}.finpay-grid{grid-template-columns:1fr}.finpay-position__item:nth-child(2){border-right:0}.finpay-position__item:nth-child(-n+2){border-bottom:1px solid #edf2f7}}@media(max-width:720px){.finpay-kpis{grid-template-columns:1fr}.finpay-field{width:100%}.finpay-select{width:100%}.finpay-spacer{display:none}.finpay-actions{width:100%}.finpay-actions .finpay-btn,.finpay-filter>.finpay-btn{flex:1}.finpay-health{grid-template-columns:38px minmax(0,1fr)}.finpay-health__time{display:none}}@media(max-width:480px){.finpay-position{grid-template-columns:1fr}.finpay-position__item{border-right:0!important;border-bottom:1px solid #edf2f7}.finpay-position__item:last-child{border-bottom:0}}
</style>
@endsection

@section('content')
<div class="finpay">
    <header class="finpay-head">
        <div>
            <div class="finpay-kicker">Finance · Encaissement · Affectation · Reversement</div>
            <h1 class="finpay-title">Centre financier des paiements</h1>
            <p class="finpay-sub">Un paiement confirmé n’est libéré vers la commande qu’après contrôle du montant, de la cible et de l’absence de double financement.</p>
        </div>
        <div class="finpay-actions">
            <a class="finpay-btn" href="{{ route('admin.payments.export-csv', array_filter(['provider' => ($filters['provider'] ?? 'all') !== 'all' ? $filters['provider'] : null, 'status' => ($filters['status'] ?? 'all') !== 'all' ? $filters['status'] : null])) }}"><i class="fas fa-file-export"></i> Exporter</a>
            <a class="finpay-btn finpay-btn--primary" href="{{ request()->fullUrl() }}"><i class="fas fa-rotate"></i> Actualiser</a>
        </div>
    </header>

    <section class="finpay-health finpay-health--{{ $businessHealth['tone'] }}">
        <div class="finpay-health__icon"><i class="fas fa-scale-balanced"></i></div>
        <div><div class="finpay-health__title">{{ $businessHealth['label'] }}</div><div class="finpay-health__message">{{ $businessHealth['message'] }}</div></div>
        <div class="finpay-health__time">Calculé à {{ ($generatedAt ?? now())->format('H:i:s') }}</div>
    </section>

    <form class="finpay-filter" method="GET" action="{{ route('admin.payments.dashboard') }}">
        <div class="finpay-field"><label>Période opérationnelle</label><div class="finpay-periods">@foreach([6,12,24] as $period)<a class="finpay-period {{ (int)($hours ?? 12)===$period?'is-active':'' }}" href="{{ route('admin.payments.dashboard',['hours'=>$period,'provider'=>$filters['provider'] ?? 'all','status'=>$filters['status'] ?? 'all']) }}">{{ $period }} h</a>@endforeach</div></div>
        <input type="hidden" name="hours" value="{{ $hours ?? 12 }}">
        <div class="finpay-field"><label for="providerFilter">Canal</label><select id="providerFilter" name="provider" class="finpay-select">@foreach(($filterOptions['providers'] ?? []) as $option)<option value="{{ $option['value'] }}" {{ ($filters['provider'] ?? 'all')===$option['value']?'selected':'' }}>{{ $option['label'] }}</option>@endforeach</select></div>
        <div class="finpay-field"><label for="statusFilter">Statut technique</label><select id="statusFilter" name="status" class="finpay-select">@foreach(($filterOptions['statuses'] ?? []) as $option)<option value="{{ $option['value'] }}" {{ ($filters['status'] ?? 'all')===$option['value']?'selected':'' }}>{{ $option['label'] }}</option>@endforeach</select></div>
        <div class="finpay-spacer"></div>
        <a class="finpay-btn" href="{{ route('admin.payments.dashboard') }}">Réinitialiser</a><button class="finpay-btn finpay-btn--primary" type="submit">Appliquer</button>
    </form>

    <section class="finpay-kpis">
        <article class="finpay-card finpay-kpi"><div class="finpay-kpi__label">Encaissements confirmés</div><div class="finpay-kpi__value">{{ number_format($summary['confirmed_amount'],0,',',' ') }} FCFA</div><div class="finpay-kpi__meta">Sommes confirmées par les fournisseurs aujourd’hui.</div></article>
        <article class="finpay-card finpay-kpi"><div class="finpay-kpi__label">Affecté aux opérations</div><div class="finpay-kpi__value">{{ number_format($summary['allocated_amount'],0,',',' ') }} FCFA</div><div class="finpay-kpi__meta">{{ $summary['allocation_count'] }} affectation(s) valide(s), sans compter les lignes produit plusieurs fois.</div></article>
        <article class="finpay-card finpay-kpi {{ $summary['unallocated_amount']>0?'finpay-kpi--warning':'' }}"><div class="finpay-kpi__label">Non affecté ou bloqué</div><div class="finpay-kpi__value">{{ number_format($summary['unallocated_amount'],0,',',' ') }} FCFA</div><div class="finpay-kpi__meta">Dont {{ number_format($summary['held_amount'],0,',',' ') }} FCFA en attente de décision.</div></article>
        <article class="finpay-card finpay-kpi {{ $summary['critical_cases']>0?'finpay-kpi--danger':'' }}"><div class="finpay-kpi__label">Dossiers ouverts</div><div class="finpay-kpi__value">{{ $summary['open_cases'] }}</div><div class="finpay-kpi__meta">{{ $summary['critical_cases'] }} critique(s) · {{ number_format($summary['open_case_amount'],0,',',' ') }} FCFA concernés.</div></article>
    </section>

    <section class="finpay-card">
        <div class="finpay-position">
            <div class="finpay-position__item"><div class="finpay-position__label">Clearing fournisseur</div><div class="finpay-position__value">{{ number_format($summary['provider_clearing'],0,',',' ') }} FCFA</div></div>
            <div class="finpay-position__item"><div class="finpay-position__label">Fonds clients en registre</div><div class="finpay-position__value">{{ number_format($summary['customer_funds'],0,',',' ') }} FCFA</div></div>
            <div class="finpay-position__item"><div class="finpay-position__label">Retraits réservés</div><div class="finpay-position__value">{{ number_format($summary['reserved_withdrawals'],0,',',' ') }} FCFA</div></div>
            <div class="finpay-position__item"><div class="finpay-position__label">Retraits payés aujourd’hui</div><div class="finpay-position__value">{{ number_format($summary['paid_withdrawals'],0,',',' ') }} FCFA</div></div>
        </div>
    </section>

    <section class="finpay-card">
        <div class="finpay-card__head"><div><div class="finpay-card__title">File de décision financière</div><div class="finpay-card__sub">Sous-paiements, doubles paiements, statuts inconnus, inversions et incohérences fournisseur.</div></div><span class="finpay-badge {{ $summary['critical_cases']>0?'finpay-badge--critical':'finpay-badge--resolved' }}">{{ $decisionQueue->count() }} dossier(s)</span></div>
        <div class="finpay-table-wrap"><table class="finpay-table"><thead><tr><th>Risque</th><th>Dossier</th><th>Paiement</th><th>Canal</th><th>Attendu</th><th>Observé</th><th>Ancienneté</th><th>Action</th></tr></thead><tbody>
        @forelse($decisionQueue as $case)
            <tr><td><span class="finpay-badge finpay-badge--{{ $case['severity'] }}">{{ $case['severity']==='critical'?'Critique':'À vérifier' }}</span></td><td><strong>{{ str_replace('_',' ',ucfirst($case['type'])) }}</strong><small>{{ $case['summary'] }}</small></td><td><strong>{{ $case['payment_reference'] }}</strong><small>Statut interne : {{ $case['internal_status'] }}</small></td><td>{{ $case['provider'] }}</td><td>{{ number_format($case['expected_amount'],0,',',' ') }} {{ $case['currency'] }}</td><td>{{ number_format($case['observed_amount'],0,',',' ') }} {{ $case['currency'] }}</td><td>{{ $case['age_label'] }}</td><td>@if($case['can_reconcile'] && $case['payment_id'])<button type="button" class="finpay-btn finpay-reconcile" data-reconcile-url="{{ route('admin.payments.reconcile',['payment'=>$case['payment_id']]) }}">Rapprocher</button>@else<span class="finpay-badge">Analyse manuelle</span>@endif</td></tr>
        @empty<tr><td colspan="8"><div class="finpay-empty"><i class="fas fa-circle-check"></i>Aucun dossier financier ouvert.</div></td></tr>@endforelse
        </tbody></table></div>
    </section>

    <section class="finpay-grid">
        <article class="finpay-card"><div class="finpay-card__head"><div><div class="finpay-card__title">Encaissements confirmés par tranche</div><div class="finpay-card__sub">Montants réellement confirmés sur les {{ $hours ?? 12 }} dernières heures.</div></div></div><div class="finpay-card__body">@if($chartItems->isNotEmpty())<div class="finpay-bars">@foreach($chartItems as $item)<div class="finpay-bar-wrap" title="{{ $item['label'] }} : {{ number_format($item['amount'],0,',',' ') }} FCFA"><div class="finpay-bar" style="height:{{ max(3,round(($item['amount']/$chartMax)*100)) }}%"></div><div class="finpay-bar-label">{{ $item['label'] }}</div></div>@endforeach</div>@else<div class="finpay-empty"><i class="fas fa-chart-column"></i>Aucune donnée sur la période.</div>@endif</div></article>
        <article class="finpay-card"><div class="finpay-card__head"><div><div class="finpay-card__title">Santé des canaux</div><div class="finpay-card__sub">Montants confirmés, réussite et exceptions.</div></div></div><div class="finpay-card__body">@forelse(($providerBreakdown ?? collect()) as $provider)<div class="finpay-provider"><div class="finpay-provider__top"><div><div class="finpay-provider__name">{{ $provider['provider'] }}</div><div class="finpay-provider__meta">{{ $provider['count'] }} tentative(s) · {{ number_format($provider['success_rate'],1,',',' ') }} % réussies · {{ $provider['exceptions'] }} exception(s)</div></div><div class="finpay-provider__amount">{{ number_format($provider['amount'],0,',',' ') }} FCFA</div></div><div class="finpay-progress"><span style="width:{{ min(100,max(3,$provider['share_percent'])) }}%"></span></div></div>@empty<div class="finpay-empty"><i class="fas fa-signal"></i>Aucune activité opérateur.</div>@endforelse</div></article>
    </section>

    <section class="finpay-grid">
        <article class="finpay-card"><div class="finpay-card__head"><div><div class="finpay-card__title">Alertes opérationnelles</div><div class="finpay-card__sub">Les alertes expliquent les risques sans dupliquer la file de décision.</div></div></div><div class="finpay-card__body">@foreach(($alerts ?? []) as $alert)<div class="finpay-alert finpay-alert--{{ $alert['tone'] }}"><span class="finpay-alert__rail"></span><div><div class="finpay-alert__label">{{ $alert['label'] }}</div><div class="finpay-alert__message">{{ $alert['message'] }}</div></div><div class="finpay-alert__value">{{ $alert['value'] }}</div></div>@endforeach</div></article>
        <article class="finpay-card"><div class="finpay-card__head"><div><div class="finpay-card__title">Règles métier actives</div><div class="finpay-card__sub">Le dashboard représente les mouvements réels, pas seulement des statuts.</div></div></div><div class="finpay-card__body"><div class="finpay-alert"><span class="finpay-alert__rail"></span><div><div class="finpay-alert__label">Encaissement ≠ affectation</div><div class="finpay-alert__message">Un paiement confirmé sans cible fiable reste bloqué et visible.</div></div></div><div class="finpay-alert finpay-alert--warning"><span class="finpay-alert__rail"></span><div><div class="finpay-alert__label">Commande multi-article = une opération</div><div class="finpay-alert__message">Le total du groupe de commande n’est jamais multiplié par le nombre de lignes produit.</div></div></div><div class="finpay-alert finpay-alert--danger"><span class="finpay-alert__rail"></span><div><div class="finpay-alert__label">Inversion = contre-écriture</div><div class="finpay-alert__message">Aucune suppression : l’historique et la contre-écriture restent auditables.</div></div></div></div></article>
    </section>

    <section class="finpay-card">
        <div class="finpay-card__head"><div><div class="finpay-card__title">Journal des transactions</div><div class="finpay-card__sub">Trente dernières opérations correspondant aux filtres.</div></div><div class="finpay-search-wrap"><i class="fas fa-search"></i><input id="paymentSearch" class="finpay-search" type="search" placeholder="Référence, canal, téléphone…" autocomplete="off"></div></div>
        <div class="finpay-table-wrap"><table class="finpay-table" id="paymentJournalTable"><thead><tr><th>Transaction</th><th>Payeur / commande</th><th>Canal / référence</th><th>Montant</th><th>Statut</th><th>Activité</th><th>Action</th></tr></thead><tbody>
        @forelse(($tablePayments ?? collect()) as $payment)
            <tr data-search="{{ strtolower(($payment['id'] ?? '').' '.($payment['phone'] ?? '').' '.($payment['provider'] ?? '').' '.($payment['reference'] ?? '').' '.($payment['order_reference'] ?? '')) }}"><td><strong>{{ $payment['id'] }}</strong><small>{{ $payment['order_reference'] }}</small></td><td><strong>{{ $payment['phone'] }}</strong>@if($payment['reason'])<small>{{ $payment['reason'] }}</small>@endif</td><td><strong>{{ $payment['provider'] }}</strong><small>{{ $payment['reference'] }}</small></td><td><strong>{{ number_format($payment['amount'],0,',',' ') }} FCFA</strong></td><td><span class="finpay-badge finpay-badge--{{ $payment['status'] }}">{{ $payment['status_label'] }}</span></td><td>{{ $payment['updated_at_human'] }}<small>{{ $payment['age_label'] }}</small></td><td>@if($payment['can_reconcile'])<button type="button" class="finpay-btn finpay-reconcile" data-reconcile-url="{{ route('admin.payments.reconcile',['payment'=>$payment['raw_id']]) }}">Vérifier</button>@else<span class="finpay-badge">Clôturé</span>@endif</td></tr>
        @empty<tr><td colspan="7"><div class="finpay-empty"><i class="fas fa-receipt"></i>Aucune transaction.</div></td></tr>@endforelse
        </tbody></table></div>
    </section>
</div>
<div id="finpayToast" class="finpay-toast" role="status" aria-live="polite"></div>
@endsection

@section('script')
<script>
(function(){const csrf=document.querySelector('meta[name="csrf-token"]')?.content||'';const toast=document.getElementById('finpayToast');const search=document.getElementById('paymentSearch');const rows=Array.from(document.querySelectorAll('#paymentJournalTable tbody tr[data-search]'));function notify(message,tone){if(!toast)return;toast.textContent=message;toast.classList.remove('is-error','is-warning');if(tone)toast.classList.add('is-'+tone);toast.classList.add('is-visible');setTimeout(()=>toast.classList.remove('is-visible'),3800)}async function reconcile(button){const url=button.dataset.reconcileUrl;if(!url)return;const original=button.innerHTML;button.disabled=true;button.innerHTML='<i class="fas fa-spinner fa-spin"></i> Contrôle';try{const response=await fetch(url,{method:'POST',headers:{Accept:'application/json','X-CSRF-TOKEN':csrf,'X-Requested-With':'XMLHttpRequest'}});const payload=await response.json().catch(()=>({}));if(!response.ok)throw new Error(payload.message||'Erreur de rapprochement.');notify(payload.message||'Rapprochement terminé.',payload.status?'':'warning');setTimeout(()=>location.reload(),900)}catch(error){notify(error.message||'Erreur de rapprochement.','error');button.disabled=false;button.innerHTML=original}}document.addEventListener('click',event=>{const button=event.target.closest('.finpay-reconcile');if(button)reconcile(button)});search?.addEventListener('input',()=>{const query=search.value.trim().toLocaleLowerCase('fr');rows.forEach(row=>row.hidden=query!==''&&!row.dataset.search.includes(query))})})();
</script>
@endsection
