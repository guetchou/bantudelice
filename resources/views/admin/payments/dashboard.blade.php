@extends('layouts.admin-modern')

@section('title', 'Contrôle financier')
@section('page_title', 'Contrôle financier')
@section('nav_active', 'payments')

@php
    $positionCards = [
        $financialPosition['confirmed_collections'],
        $financialPosition['unresolved_collections'],
        $financialPosition['reserved_withdrawals'],
        $financialPosition['paid_withdrawals'],
    ];
    $chartLabels = collect($hourlySeries['labels'] ?? [])->values();
    $chartAmounts = collect($hourlySeries['amounts'] ?? [])->map(fn ($value) => (float) $value)->values();
    $chartMax = max(1, (float) $chartAmounts->max());
@endphp

@section('style')
<style>
.finops{max-width:1600px;margin:0 auto;display:flex;flex-direction:column;gap:16px}.finops-panel,.finops-kpi,.finops-health,.finops-filterbar{background:#fff;border:1px solid #e2e8f0;border-radius:12px;box-shadow:0 1px 2px rgba(15,23,42,.04)}.finops-header{display:flex;align-items:flex-start;justify-content:space-between;gap:16px;flex-wrap:wrap}.finops-eyebrow{font-size:.62rem;font-weight:800;letter-spacing:.08em;text-transform:uppercase;color:#64748b}.finops-title{margin-top:5px;font-size:1.35rem;font-weight:900;color:#0f172a}.finops-subtitle{max-width:760px;margin-top:6px;font-size:.74rem;line-height:1.55;color:#64748b}.finops-actions{display:flex;gap:8px;align-items:center;flex-wrap:wrap}.finops-btn{height:34px;display:inline-flex;align-items:center;justify-content:center;gap:7px;padding:0 12px;border:1px solid #cbd5e1;border-radius:8px;background:#fff;color:#334155;text-decoration:none;font:750 .67rem 'Poppins',sans-serif;cursor:pointer;white-space:nowrap}.finops-btn:hover{border-color:#94a3b8;color:#0f172a}.finops-btn--primary{background:#0f766e;border-color:#0f766e;color:#fff}.finops-btn--primary:hover{background:#115e59;border-color:#115e59;color:#fff}.finops-btn:disabled{opacity:.55;cursor:wait}.finops-health{display:grid;grid-template-columns:10px minmax(0,1fr) auto;gap:12px;align-items:center;padding:12px 14px}.finops-health__rail{width:10px;height:38px;border-radius:999px;background:#16a34a}.finops-health--warning .finops-health__rail{background:#d97706}.finops-health--danger .finops-health__rail{background:#dc2626}.finops-health__title{font-size:.75rem;font-weight:850;color:#0f172a}.finops-health__message{margin-top:2px;font-size:.65rem;color:#64748b}.finops-health__time{font-size:.61rem;color:#94a3b8;white-space:nowrap}.finops-filterbar{display:flex;align-items:end;gap:10px;padding:11px;flex-wrap:wrap}.finops-field{display:flex;flex-direction:column;gap:4px}.finops-field label{font-size:.58rem;font-weight:800;text-transform:uppercase;letter-spacing:.05em;color:#64748b}.finops-select{height:34px;min-width:165px;padding:0 28px 0 9px;border:1px solid #cbd5e1;border-radius:8px;background:#fff;color:#334155;font:650 .66rem 'Poppins',sans-serif}.finops-periods{display:inline-flex;gap:2px;padding:3px;border:1px solid #cbd5e1;border-radius:8px;background:#f8fafc}.finops-period{height:26px;min-width:44px;display:grid;place-items:center;border-radius:6px;color:#64748b;text-decoration:none;font-size:.63rem;font-weight:800}.finops-period.is-active{background:#fff;color:#0f766e;box-shadow:0 1px 2px rgba(15,23,42,.12)}.finops-spacer{flex:1}.finops-kpis{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px}.finops-kpi{padding:15px;min-width:0}.finops-kpi__label{font-size:.62rem;font-weight:800;color:#64748b}.finops-kpi__amount{margin-top:9px;font-size:1.28rem;font-weight:900;color:#0f172a;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}.finops-kpi__meta{margin-top:5px;font-size:.61rem;line-height:1.45;color:#94a3b8}.finops-kpi__count{display:inline-flex;margin-top:9px;padding:3px 7px;border-radius:999px;background:#f1f5f9;color:#475569;font-size:.58rem;font-weight:800}.finops-grid{display:grid;grid-template-columns:minmax(0,1.4fr) minmax(320px,.6fr);gap:14px}.finops-grid--equal{grid-template-columns:repeat(2,minmax(0,1fr))}.finops-panel{overflow:hidden}.finops-panel__head{min-height:54px;display:flex;align-items:center;justify-content:space-between;gap:12px;padding:12px 15px;border-bottom:1px solid #e2e8f0}.finops-panel__title{font-size:.76rem;font-weight:850;color:#0f172a}.finops-panel__sub{margin-top:2px;font-size:.6rem;color:#94a3b8}.finops-panel__body{padding:14px 15px}.finops-table-wrap{overflow-x:auto}.finops-table{width:100%;border-collapse:collapse}.finops-table th{padding:8px 10px;background:#f8fafc;border-bottom:1px solid #e2e8f0;text-align:left;font-size:.56rem;font-weight:850;letter-spacing:.04em;text-transform:uppercase;color:#64748b;white-space:nowrap}.finops-table td{padding:10px;border-bottom:1px solid #edf2f7;font-size:.66rem;color:#475569;vertical-align:middle}.finops-table tr:last-child td{border-bottom:0}.finops-table strong{color:#0f172a;font-weight:800}.finops-table small{display:block;margin-top:2px;font-size:.57rem;line-height:1.4;color:#94a3b8}.finops-badge{display:inline-flex;align-items:center;justify-content:center;padding:4px 8px;border-radius:999px;background:#e2e8f0;color:#475569;font-size:.57rem;font-weight:850;white-space:nowrap}.finops-badge--active,.finops-badge--paid,.finops-badge--success{background:#dcfce7;color:#166534}.finops-badge--missing,.finops-badge--warning,.finops-badge--pending,.finops-badge--processing,.finops-badge--initiated{background:#fef3c7;color:#92400e}.finops-badge--critical,.finops-badge--danger,.finops-badge--failed,.finops-badge--unknown,.finops-badge--reversed,.finops-badge--disputed,.finops-badge--duplicate{background:#fee2e2;color:#991b1b}.finops-queue-priority{display:inline-flex;align-items:center;gap:6px;font-size:.59rem;font-weight:850;white-space:nowrap}.finops-queue-priority::before{content:'';width:7px;height:7px;border-radius:50%;background:#d97706}.finops-queue-priority--critical{color:#b91c1c}.finops-queue-priority--critical::before{background:#dc2626}.finops-list{display:flex;flex-direction:column}.finops-list__item{display:grid;grid-template-columns:minmax(0,1fr) auto;gap:12px;padding:11px 0;border-bottom:1px solid #edf2f7}.finops-list__item:last-child{border-bottom:0}.finops-list__title{font-size:.67rem;font-weight:800;color:#0f172a}.finops-list__text{margin-top:2px;font-size:.58rem;line-height:1.45;color:#64748b}.finops-list__value{text-align:right;font-size:.7rem;font-weight:900;color:#0f172a;white-space:nowrap}.finops-progress{height:5px;margin-top:8px;border-radius:999px;background:#e2e8f0;overflow:hidden}.finops-progress span{display:block;height:100%;border-radius:inherit;background:#0f766e}.finops-coverage{display:flex;flex-direction:column}.finops-coverage__row{display:grid;grid-template-columns:minmax(0,1fr) auto;gap:10px;padding:10px 0;border-bottom:1px solid #edf2f7}.finops-coverage__row:last-child{border-bottom:0}.finops-rule{padding:10px 0;border-bottom:1px solid #edf2f7}.finops-rule:last-child{border-bottom:0}.finops-rule strong{font-size:.66rem;color:#0f172a}.finops-rule p{margin-top:3px;font-size:.59rem;line-height:1.45;color:#64748b}.finops-chart{height:165px;display:flex;align-items:flex-end;gap:5px}.finops-bar{flex:1;min-width:3px;display:flex;flex-direction:column;align-items:center;justify-content:flex-end;gap:5px;height:100%}.finops-bar span{width:100%;min-height:3px;border-radius:4px 4px 1px 1px;background:#0f766e}.finops-bar small{width:100%;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;text-align:center;font-size:.48rem;color:#94a3b8}.finops-search{height:32px;width:min(230px,100%);padding:0 10px;border:1px solid #cbd5e1;border-radius:8px;color:#334155;font:650 .64rem 'Poppins',sans-serif}.finops-empty{padding:24px;text-align:center;font-size:.65rem;color:#94a3b8}.finops-toast{position:fixed;right:18px;bottom:18px;z-index:500;display:none;max-width:340px;padding:10px 13px;border-radius:9px;background:#0f172a;color:#fff;font-size:.65rem;box-shadow:0 12px 30px rgba(15,23,42,.25)}.finops-toast.is-visible{display:block}.finops-toast.is-error{background:#991b1b}@media(max-width:1180px){.finops-kpis{grid-template-columns:repeat(2,minmax(0,1fr))}.finops-grid,.finops-grid--equal{grid-template-columns:1fr}}@media(max-width:680px){.finops-kpis{grid-template-columns:1fr}.finops-health{grid-template-columns:8px minmax(0,1fr)}.finops-health__time{display:none}.finops-field{width:100%}.finops-select{width:100%}.finops-spacer{display:none}.finops-filterbar .finops-btn{flex:1}.finops-actions{width:100%}.finops-actions .finops-btn{flex:1}}
</style>
@endsection

@section('content')
<div class="finops">
    <header class="finops-header">
        <div>
            <div class="finops-eyebrow">Finance · Encaissements · Reversements</div>
            <h1 class="finops-title">Centre de contrôle financier</h1>
            <p class="finops-subtitle">Une vue métier unique pour distinguer l’argent confirmé, les paiements non affectés, les fonds partenaires réservés et les dossiers qui exigent une décision.</p>
        </div>
        <div class="finops-actions">
            <a class="finops-btn" href="{{ route('admin.payments.export-csv', array_filter(['provider' => $filters['provider'] !== 'all' ? $filters['provider'] : null, 'status' => $filters['status'] !== 'all' ? $filters['status'] : null])) }}"><i class="fas fa-file-export"></i> Exporter</a>
            <a class="finops-btn finops-btn--primary" href="{{ request()->fullUrl() }}"><i class="fas fa-rotate"></i> Actualiser</a>
        </div>
    </header>

    <section class="finops-health finops-health--{{ $industrialHealth['tone'] ?? 'neutral' }}">
        <span class="finops-health__rail"></span>
        <div><div class="finops-health__title">{{ $industrialHealth['label'] ?? 'État indisponible' }}</div><div class="finops-health__message">{{ $industrialHealth['message'] ?? '' }}</div></div>
        <div class="finops-health__time">Dernier calcul {{ $generatedAt->format('H:i:s') }}</div>
    </section>

    <form class="finops-filterbar" method="GET" action="{{ route('admin.payments.dashboard') }}">
        <div class="finops-field"><label>Période</label><div class="finops-periods">@foreach([6,12,24] as $period)<a class="finops-period {{ (int)$hours === $period ? 'is-active' : '' }}" href="{{ route('admin.payments.dashboard',['hours'=>$period,'provider'=>$filters['provider'],'status'=>$filters['status']]) }}">{{ $period }} h</a>@endforeach</div></div>
        <input type="hidden" name="hours" value="{{ $hours }}">
        <div class="finops-field"><label for="provider">Canal</label><select id="provider" name="provider" class="finops-select">@foreach($filterOptions['providers'] as $option)<option value="{{ $option['value'] }}" {{ $filters['provider'] === $option['value'] ? 'selected' : '' }}>{{ $option['label'] }}</option>@endforeach</select></div>
        <div class="finops-field"><label for="status">Statut</label><select id="status" name="status" class="finops-select">@foreach($filterOptions['statuses'] as $option)<option value="{{ $option['value'] }}" {{ $filters['status'] === $option['value'] ? 'selected' : '' }}>{{ $option['label'] }}</option>@endforeach</select></div>
        <div class="finops-spacer"></div>
        <a class="finops-btn" href="{{ route('admin.payments.dashboard') }}">Réinitialiser</a>
        <button class="finops-btn finops-btn--primary" type="submit">Appliquer</button>
    </form>

    <section class="finops-kpis">
        @foreach($positionCards as $card)
            <article class="finops-kpi"><div class="finops-kpi__label">{{ $card['label'] }}</div><div class="finops-kpi__amount">{{ number_format($card['amount'],0,',',' ') }} FCFA</div><span class="finops-kpi__count">{{ $card['count'] }} opération(s)</span><div class="finops-kpi__meta">{{ $card['definition'] }}</div></article>
        @endforeach
    </section>

    <section class="finops-grid">
        <article class="finops-panel">
            <div class="finops-panel__head"><div><div class="finops-panel__title">File de contrôle financier</div><div class="finops-panel__sub">Paiements, affectations et retraits classés par risque métier.</div></div><span class="finops-badge {{ $industrialQueue->isEmpty() ? 'finops-badge--active' : 'finops-badge--danger' }}">{{ $industrialQueue->count() }} dossier(s)</span></div>
            <div class="finops-table-wrap"><table class="finops-table"><thead><tr><th>Priorité</th><th>Type</th><th>Référence</th><th>Partie</th><th>Montant</th><th>État</th><th>Action</th></tr></thead><tbody>
            @forelse($industrialQueue as $item)
                <tr><td><span class="finops-queue-priority finops-queue-priority--{{ $item['priority'] }}">{{ $item['priority'] === 'critical' ? 'Critique' : 'À vérifier' }}</span></td><td><strong>{{ $item['source'] === 'withdrawal' ? 'Retrait' : 'Encaissement' }}</strong><small>{{ str_replace('_',' ',$item['control_type']) }}</small></td><td><strong>{{ $item['reference'] }}</strong><small>{{ $item['age_label'] }}</small></td><td>{{ $item['party'] }}</td><td><strong>{{ number_format($item['amount'],0,',',' ') }} FCFA</strong></td><td><span class="finops-badge finops-badge--{{ $item['status'] }}">{{ $item['status_label'] }}</span><small>{{ $item['reason'] }}</small></td><td>@if($item['source']==='collection' && $item['can_reconcile'])<button class="finops-btn finops-reconcile" type="button" data-url="{{ route('admin.payments.reconcile',['payment'=>$item['raw_id']]) }}">Rapprocher</button>@else<span class="finops-badge">Revue requise</span>@endif</td></tr>
            @empty<tr><td colspan="7"><div class="finops-empty">Aucun dossier prioritaire.</div></td></tr>@endforelse
            </tbody></table></div>
        </article>

        <article class="finops-panel">
            <div class="finops-panel__head"><div><div class="finops-panel__title">Couverture des contrôles métier</div><div class="finops-panel__sub">Ce qui est réellement industrialisé et ce qui reste à construire.</div></div></div>
            <div class="finops-panel__body"><div class="finops-coverage">@foreach($controlCoverage as $control)<div class="finops-coverage__row"><div><div class="finops-list__title">{{ $control['label'] }}</div><div class="finops-list__text">{{ $control['definition'] }}</div></div><span class="finops-badge finops-badge--{{ $control['status'] }}">{{ $control['status']==='active' ? 'Actif' : 'Manquant' }}</span></div>@endforeach</div></div>
        </article>
    </section>

    <section class="finops-grid finops-grid--equal">
        <article class="finops-panel">
            <div class="finops-panel__head"><div><div class="finops-panel__title">Position financière</div><div class="finops-panel__sub">Les flux sont séparés selon leur réalité comptable.</div></div></div>
            <div class="finops-table-wrap"><table class="finops-table"><thead><tr><th>Position</th><th>Montant</th><th>Volume</th><th>Définition</th></tr></thead><tbody>@foreach($financialPosition as $position)<tr><td><strong>{{ $position['label'] }}</strong></td><td><strong>{{ number_format($position['amount'],0,',',' ') }} FCFA</strong></td><td>{{ $position['count'] }}</td><td>{{ $position['definition'] }}</td></tr>@endforeach</tbody></table></div>
        </article>

        <article class="finops-panel">
            <div class="finops-panel__head"><div><div class="finops-panel__title">Obligations partenaires</div><div class="finops-panel__sub">Reversements programmés et retraits déjà réservés.</div></div></div>
            <div class="finops-panel__body"><div class="finops-list">
                <div class="finops-list__item"><div><div class="finops-list__title">Restaurants programmés</div><div class="finops-list__text">Ordres de reversement en attente.</div></div><div class="finops-list__value">{{ number_format($partnerObligations['restaurant_scheduled_amount'],0,',',' ') }} FCFA<small>{{ $partnerObligations['restaurant_scheduled_count'] }} dossier(s)</small></div></div>
                <div class="finops-list__item"><div><div class="finops-list__title">Livreurs programmés</div><div class="finops-list__text">Ordres de reversement en attente.</div></div><div class="finops-list__value">{{ number_format($partnerObligations['driver_scheduled_amount'],0,',',' ') }} FCFA<small>{{ $partnerObligations['driver_scheduled_count'] }} dossier(s)</small></div></div>
                <div class="finops-list__item"><div><div class="finops-list__title">Retraits restaurants réservés</div><div class="finops-list__text">Sommes indisponibles jusqu’à clôture fournisseur.</div></div><div class="finops-list__value">{{ number_format($withdrawalControl['restaurant_reserved_amount'],0,',',' ') }} FCFA<small>{{ $withdrawalControl['restaurant_reserved_count'] }} retrait(s)</small></div></div>
                <div class="finops-list__item"><div><div class="finops-list__title">Retraits livreurs réservés</div><div class="finops-list__text">Sommes indisponibles jusqu’à clôture fournisseur.</div></div><div class="finops-list__value">{{ number_format($withdrawalControl['driver_reserved_amount'],0,',',' ') }} FCFA<small>{{ $withdrawalControl['driver_reserved_count'] }} retrait(s)</small></div></div>
            </div></div>
        </article>
    </section>

    <section class="finops-grid finops-grid--equal">
        <article class="finops-panel">
            <div class="finops-panel__head"><div><div class="finops-panel__title">Encaissements confirmés</div><div class="finops-panel__sub">Montants confirmés sur les {{ $hours }} dernières heures.</div></div></div>
            <div class="finops-panel__body">@if($chartAmounts->count())<div class="finops-chart">@foreach($chartAmounts as $index=>$value)<div class="finops-bar" title="{{ $chartLabels->get($index) }} · {{ number_format($value,0,',',' ') }} FCFA"><span style="height:{{ max(3,round(($value/$chartMax)*100)) }}%"></span><small>{{ $chartLabels->get($index) }}</small></div>@endforeach</div>@else<div class="finops-empty">Aucune donnée sur cette période.</div>@endif</div>
        </article>
        <article class="finops-panel">
            <div class="finops-panel__head"><div><div class="finops-panel__title">Règles de vérité financière</div><div class="finops-panel__sub">Les principes qui empêchent les faux soldes.</div></div></div>
            <div class="finops-panel__body">@foreach($accountingRules as $rule)<div class="finops-rule"><strong>{{ $rule['title'] }}</strong><p>{{ $rule['formula'] }}</p></div>@endforeach</div>
        </article>
    </section>

    <section class="finops-panel">
        <div class="finops-panel__head"><div><div class="finops-panel__title">Journal des encaissements</div><div class="finops-panel__sub">Dernières transactions correspondant aux filtres.</div></div><input id="finopsSearch" class="finops-search" type="search" placeholder="Référence, téléphone, canal…"></div>
        <div class="finops-table-wrap"><table class="finops-table" id="finopsJournal"><thead><tr><th>Transaction</th><th>Commande / payeur</th><th>Canal / référence</th><th>Montant</th><th>Statut</th><th>Activité</th><th>Action</th></tr></thead><tbody>
        @forelse($tablePayments as $payment)
            <tr data-search="{{ strtolower($payment['id'].' '.$payment['phone'].' '.$payment['provider'].' '.$payment['reference'].' '.$payment['order_reference']) }}"><td><strong>{{ $payment['id'] }}</strong></td><td><strong>{{ $payment['order_reference'] }}</strong><small>{{ $payment['phone'] }}</small></td><td><strong>{{ $payment['provider'] }}</strong><small>{{ $payment['reference'] }}</small></td><td><strong>{{ number_format($payment['amount'],0,',',' ') }} FCFA</strong></td><td><span class="finops-badge finops-badge--{{ $payment['status'] }}">{{ $payment['status_label'] }}</span></td><td>{{ $payment['updated_at_human'] }}<small>{{ $payment['age_label'] }}</small></td><td>@if($payment['can_reconcile'])<button class="finops-btn finops-reconcile" type="button" data-url="{{ route('admin.payments.reconcile',['payment'=>$payment['raw_id']]) }}">Vérifier</button>@else<span class="finops-badge">Lecture seule</span>@endif</td></tr>
        @empty<tr><td colspan="7"><div class="finops-empty">Aucune transaction.</div></td></tr>@endforelse
        </tbody></table></div>
    </section>
</div>
<div id="finopsToast" class="finops-toast" role="status" aria-live="polite"></div>
@endsection

@section('script')
<script>
(function(){const token=document.querySelector('meta[name="csrf-token"]')?.content||'';const toast=document.getElementById('finopsToast');function notify(message,error){if(!toast)return;toast.textContent=message;toast.classList.toggle('is-error',!!error);toast.classList.add('is-visible');setTimeout(()=>toast.classList.remove('is-visible'),3500)}document.addEventListener('click',async function(event){const button=event.target.closest('.finops-reconcile');if(!button)return;const original=button.innerHTML;button.disabled=true;button.innerHTML='<i class="fas fa-spinner fa-spin"></i>';try{const response=await fetch(button.dataset.url,{method:'POST',headers:{Accept:'application/json','X-CSRF-TOKEN':token,'X-Requested-With':'XMLHttpRequest'}});const payload=await response.json().catch(()=>({}));if(!response.ok||payload.status!==true)throw new Error(payload.message||'Rapprochement impossible.');notify(payload.message||'Rapprochement terminé.',false);setTimeout(()=>location.reload(),700)}catch(error){notify(error.message||'Erreur de rapprochement.',true);button.disabled=false;button.innerHTML=original}});const search=document.getElementById('finopsSearch');const rows=[...document.querySelectorAll('#finopsJournal tbody tr[data-search]')];search?.addEventListener('input',()=>{const query=search.value.trim().toLocaleLowerCase('fr');rows.forEach(row=>row.hidden=query!==''&&!row.dataset.search.includes(query))})})();
</script>
@endsection
