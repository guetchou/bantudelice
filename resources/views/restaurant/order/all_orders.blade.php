@extends('layouts.restaurant_app')
@section('title', 'Commandes | ' . \App\Services\ConfigService::getCompanyName())
@section('topbar_title', 'Commandes')
@section('order_nav', 'active')

@section('style')
<style>
.ord { display:flex; flex-direction:column; gap:20px; }
.ord-toolbar,.ord-card__head { display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:12px; }
.ord-toolbar__left,.ord-toolbar__right,.ord-actions,.ord-bulk { display:flex; align-items:center; gap:8px; flex-wrap:wrap; }
.ord-dateinput { display:inline-flex; align-items:center; gap:8px; padding:7px 12px; border:1px solid var(--bd-border); border-radius:var(--bd-radius); background:var(--bd-surface); }
.ord-dateinput input { border:0; background:transparent; outline:0; width:200px; color:var(--bd-text); }
.ord-pill-nav { display:flex; gap:4px; flex-wrap:wrap; }
.ord-pill { display:inline-flex; align-items:center; gap:5px; padding:5px 14px; border-radius:999px; border:1px solid var(--bd-border); background:var(--bd-surface); color:var(--bd-text-2); font-size:12px; font-weight:600; text-decoration:none; }
.ord-pill.is-active { background:var(--bd-green); color:#fff; border-color:var(--bd-green); }
.ord-pill__count { min-width:18px; height:18px; display:inline-flex; align-items:center; justify-content:center; border-radius:999px; background:rgba(255,255,255,.3); padding:0 4px; font-size:10px; }
.ord-card { background:var(--bd-surface); border:1px solid var(--bd-border); border-radius:var(--bd-radius); overflow:hidden; }
.ord-card__head { padding:14px 20px; border-bottom:1px solid var(--bd-border-2); }
.ord-card__title { font-size:13px; font-weight:700; color:var(--bd-text); }
.ord-card__subtitle { font-size:11px; color:var(--bd-text-3); margin-top:2px; }
.ord-total { font-size:18px; font-weight:800; color:var(--bd-green); }
.ord-total small { font-size:11px; }
.ord-btn { display:inline-flex; align-items:center; gap:6px; padding:7px 14px; border-radius:var(--bd-radius); font-size:12px; font-weight:600; cursor:pointer; border:0; text-decoration:none; }
.ord-btn--primary { background:var(--bd-green); color:#fff; }
.ord-btn--outline { background:var(--bd-surface); color:var(--bd-text-2); border:1px solid var(--bd-border); }
.ord-table-wrap { overflow-x:auto; }
.ord-table { width:100%; border-collapse:collapse; font-size:13px; }
.ord-table th { padding:10px 16px; font-size:11px; text-transform:uppercase; letter-spacing:.05em; color:var(--bd-text-3); background:var(--bd-surface-2); text-align:left; white-space:nowrap; }
.ord-table td { padding:12px 16px; color:var(--bd-text-2); border-top:1px solid var(--bd-border-2); vertical-align:middle; }
.ord-ref { display:block; font-weight:800; color:var(--bd-text); }
.ord-ref-time,.ord-address { font-size:11px; color:var(--bd-text-3); }
.ord-address { max-width:190px; overflow:hidden; white-space:nowrap; text-overflow:ellipsis; }
.ord-customer { font-weight:600; color:var(--bd-text); }
.ord-amount { font-size:15px; font-weight:800; color:var(--bd-text); white-space:nowrap; }
.ord-badge { display:inline-flex; align-items:center; gap:5px; padding:3px 9px; border-radius:999px; font-size:11px; font-weight:700; white-space:nowrap; }
.ord-badge::before { content:''; width:6px; height:6px; border-radius:50%; background:currentColor; }
.ord-badge--new { background:rgba(245,158,11,.12); color:#d97706; }
.ord-badge--payment { background:rgba(139,92,246,.12); color:#7c3aed; }
.ord-badge--preparing { background:rgba(59,130,246,.12); color:#2563eb; }
.ord-action-btn { display:inline-flex; align-items:center; justify-content:center; width:30px; height:30px; border-radius:7px; border:1px solid var(--bd-border); background:var(--bd-surface); color:var(--bd-text-2); cursor:pointer; text-decoration:none; }
.ord-action-btn--accept { background:#dcfce7; color:#16a34a; border-color:#bbf7d0; }
.ord-action-btn--cancel { background:#fef2f2; color:#dc2626; border-color:#fecaca; }
.ord-empty { padding:42px 20px; text-align:center; color:var(--bd-text-3); font-size:13px; }
.ord-check { width:16px; height:16px; accent-color:var(--bd-green); }
.ord-notice { padding:10px 14px; border-radius:10px; font-size:12px; background:rgba(139,92,246,.08); color:#6d28d9; }
@media(max-width:768px){ .ord-col-hide{display:none}.ord-toolbar__left{width:100%} }
</style>
@endsection

@section('content')
@php
    $actionableOrders = $orders->filter(fn($order) => ($order->business_status ?? '') === 'pending_restaurant_acceptance')->values();
    $awaitingPaymentOrders = $orders->filter(fn($order) => ($order->business_status ?? '') === 'accepted_awaiting_payment')->values();
    $actionableTotal = $actionableOrders->sum('total');
@endphp

<div class="ord">
    @if(session()->has('alert'))
        <div class="alert alert-{{ session()->get('alert.type') }} alert-dismissible" role="alert">
            {{ session()->get('alert.message') }}
            <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger">
            @foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach
        </div>
    @endif

    <div class="ord-pill-nav">
        <a href="{{ route('restaurant.all_orders') }}" class="ord-pill is-active">
            <i class="fas fa-inbox"></i> À traiter
            @if($actionableOrders->isNotEmpty())<span class="ord-pill__count">{{ $actionableOrders->count() }}</span>@endif
        </a>
        <a href="{{ route('restaurant.kitchen') }}" class="ord-pill"><i class="fas fa-fire-burner"></i> Cuisine</a>
        <a href="{{ route('restaurant.pending_orders') }}" class="ord-pill"><i class="fas fa-truck"></i> Livraison</a>
        <a href="{{ route('restaurant.complete_orders') }}" class="ord-pill"><i class="fas fa-check-circle"></i> Terminées</a>
        <a href="{{ route('restaurant.cancel_orders') }}" class="ord-pill"><i class="fas fa-ban"></i> Annulées</a>
        <a href="{{ route('restaurant.schedule_orders') }}" class="ord-pill"><i class="fas fa-calendar"></i> Programmées</a>
    </div>

    <div class="ord-toolbar">
        <div class="ord-toolbar__left">
            <form method="get" action="{{ route('restaurant.all_orders') }}" class="ord-toolbar__left">
                <label class="ord-dateinput">
                    <i class="fas fa-calendar-range"></i>
                    <input type="text" name="date" id="ordDateRange" value="{{ request('date','') }}" placeholder="Filtrer par période…" autocomplete="off">
                </label>
                <button type="submit" class="ord-btn ord-btn--outline"><i class="fas fa-filter"></i> Filtrer</button>
                @if(request('date'))
                    <a href="{{ route('restaurant.all_orders') }}" class="ord-btn ord-btn--outline"><i class="fas fa-times"></i> Réinitialiser</a>
                @endif
            </form>
        </div>
        <div class="ord-toolbar__right">
            <span style="font-size:12px;color:var(--bd-text-3);">{{ $actionableOrders->count() }} action(s) requise(s)</span>
        </div>
    </div>

    {{-- Formulaire groupé autonome : les contrôles du tableau utilisent l'attribut form. --}}
    <form action="{{ route('restaurant.prepaire_orders') }}" method="post" id="ordBulkForm">
        @csrf
    </form>

    <div class="ord-card">
        <div class="ord-card__head">
            <div>
                <div class="ord-card__title">Nouvelles commandes</div>
                <div class="ord-card__subtitle">Acceptez ou refusez chaque commande avant expiration du délai.</div>
            </div>
            <div class="ord-bulk">
                <div>
                    <div style="font-size:10px;text-transform:uppercase;color:var(--bd-text-3);">Total sélection</div>
                    <div class="ord-total" id="ordSelTotal">0 <small>FCFA</small></div>
                </div>
                <button type="submit" form="ordBulkForm" class="ord-btn ord-btn--primary" {{ $actionableOrders->isEmpty() ? 'disabled' : '' }}>
                    <i class="fas fa-check"></i> Accepter la sélection
                </button>
            </div>
        </div>

        <div class="ord-table-wrap">
            @if($actionableOrders->isNotEmpty())
                <table class="ord-table">
                    <thead><tr><th><input type="checkbox" class="ord-check" id="ordCheckAll"></th><th>Référence</th><th class="ord-col-hide">Client</th><th>Montant</th><th class="ord-col-hide">Adresse</th><th>Statut</th><th></th></tr></thead>
                    <tbody>
                    @foreach($actionableOrders as $order)
                        @php $cancelFormId = 'cancel-order-' . preg_replace('/[^A-Za-z0-9_-]/', '-', $order->order_no); @endphp
                        <tr>
                            <td><input type="checkbox" form="ordBulkForm" class="ord-check ord-row-check" name="id[]" value="{{ $order->order_no }}" data-amount="{{ (float)$order->total }}"></td>
                            <td><span class="ord-ref">{{ $order->order_no }}</span><span class="ord-ref-time">{{ optional($order->created_at)->format('d/m · H:i') }}</span></td>
                            <td class="ord-col-hide"><div class="ord-customer">{{ $order->user->name ?? 'Client' }}</div></td>
                            <td><span class="ord-amount">{{ number_format((float)$order->total,0,',',' ') }} FCFA</span></td>
                            <td class="ord-col-hide"><div class="ord-address" title="{{ $order->delivery_address }}">{{ $order->delivery_address ?? '—' }}</div></td>
                            <td><span class="ord-badge ord-badge--new">Action requise</span></td>
                            <td>
                                <div class="ord-actions">
                                    <a href="{{ route('restaurant.show_order', $order->order_no) }}" class="ord-action-btn" title="Voir"><i class="fas fa-eye"></i></a>
                                    <button type="submit" form="ordBulkForm" name="id[]" value="{{ $order->order_no }}" class="ord-action-btn ord-action-btn--accept" title="Accepter"><i class="fas fa-check"></i></button>
                                    <button type="button" class="ord-action-btn ord-action-btn--cancel js-cancel-order" data-form="{{ $cancelFormId }}" title="Refuser"><i class="fas fa-times"></i></button>
                                </div>
                                <form id="{{ $cancelFormId }}" method="POST" action="{{ route('restaurant.cancel_order', $order->order_no) }}" hidden>
                                    @csrf
                                    <input type="hidden" name="reason" value="">
                                    <input type="hidden" name="cancel_note" value="">
                                </form>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            @else
                <div class="ord-empty"><i class="fas fa-inbox"></i><br>Aucune commande ne nécessite une action.</div>
            @endif
        </div>
    </div>

    <div class="ord-card">
        <div class="ord-card__head">
            <div>
                <div class="ord-card__title">Acceptées — paiement client attendu</div>
                <div class="ord-card__subtitle">Ces commandes sont déjà acceptées. Ne les acceptez pas une seconde fois.</div>
            </div>
            <span class="ord-badge ord-badge--payment">{{ $awaitingPaymentOrders->count() }} en attente</span>
        </div>
        @if($awaitingPaymentOrders->isNotEmpty())
            <div class="ord-notice" style="margin:12px 20px 0;">La cuisine démarrera automatiquement après confirmation du paiement.</div>
            <div class="ord-table-wrap">
                <table class="ord-table">
                    <thead><tr><th>Référence</th><th class="ord-col-hide">Client</th><th>Montant</th><th>Statut</th><th></th></tr></thead>
                    <tbody>
                    @foreach($awaitingPaymentOrders as $order)
                        <tr>
                            <td><span class="ord-ref">{{ $order->order_no }}</span><span class="ord-ref-time">{{ optional($order->created_at)->format('d/m · H:i') }}</span></td>
                            <td class="ord-col-hide"><div class="ord-customer">{{ $order->user->name ?? 'Client' }}</div></td>
                            <td><span class="ord-amount">{{ number_format((float)$order->total,0,',',' ') }} FCFA</span></td>
                            <td><span class="ord-badge ord-badge--payment">Paiement attendu</span></td>
                            <td><a href="{{ route('restaurant.show_order', $order->order_no) }}" class="ord-action-btn" title="Voir"><i class="fas fa-eye"></i></a></td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="ord-empty">Aucune commande en attente de paiement.</div>
        @endif
    </div>
</div>
@endsection

@section('script')
<script src="{{ asset('plugins/daterangepicker/daterangepicker.js') }}" defer></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    if (window.jQuery && $.fn.daterangepicker) {
        $('#ordDateRange').daterangepicker({
            autoUpdateInput:false,
            locale:{cancelLabel:'Vider',applyLabel:'Appliquer',format:'DD/MM/YYYY',firstDay:1}
        });
        $('#ordDateRange').on('apply.daterangepicker', function (event, picker) {
            this.value = picker.startDate.format('DD/MM/YYYY') + ' - ' + picker.endDate.format('DD/MM/YYYY');
        }).on('cancel.daterangepicker', function () { this.value = ''; });
    }

    const checkAll = document.getElementById('ordCheckAll');
    const rowChecks = Array.from(document.querySelectorAll('.ord-row-check'));
    const totalEl = document.getElementById('ordSelTotal');

    function updateSelection() {
        const selected = rowChecks.filter(input => input.checked);
        const total = selected.reduce((sum, input) => sum + Number(input.dataset.amount || 0), 0);
        totalEl.innerHTML = new Intl.NumberFormat('fr-FR').format(total) + ' <small>FCFA</small>';
        if (checkAll) {
            checkAll.checked = rowChecks.length > 0 && selected.length === rowChecks.length;
            checkAll.indeterminate = selected.length > 0 && selected.length < rowChecks.length;
        }
    }

    checkAll?.addEventListener('change', function () {
        rowChecks.forEach(input => input.checked = this.checked);
        updateSelection();
    });
    rowChecks.forEach(input => input.addEventListener('change', updateSelection));
    updateSelection();

    const reasons = {
        '1':'restaurant_closed',
        '2':'product_unavailable',
        '3':'too_many_orders',
        '4':'delivery_zone_issue',
        '5':'other'
    };

    document.querySelectorAll('.js-cancel-order').forEach(button => {
        button.addEventListener('click', function () {
            const choice = window.prompt(
                'Motif obligatoire :\n1. Restaurant fermé\n2. Produit indisponible\n3. Trop de commandes\n4. Zone non couverte\n5. Autre\n\nSaisissez 1 à 5.'
            );
            const reason = reasons[String(choice || '').trim()];
            if (!reason) return;

            let note = '';
            if (reason === 'other') {
                note = String(window.prompt('Précisez le motif du refus :') || '').trim();
                if (!note) return;
            }

            const form = document.getElementById(this.dataset.form);
            form.querySelector('[name="reason"]').value = reason;
            form.querySelector('[name="cancel_note"]').value = note;
            if (window.confirm('Confirmer le refus de cette commande ?')) form.submit();
        });
    });
});
</script>
@endsection
