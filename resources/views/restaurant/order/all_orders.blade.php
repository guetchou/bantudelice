@extends('layouts.restaurant_app')
@section('title', 'Commandes | ' . \App\Services\ConfigService::getCompanyName())
@section('topbar_title', 'Commandes')
@section('order_nav', 'active')

@section('style')
<style>
/* ── Page commandes ───────────────────────────────────────── */
.ord { display: flex; flex-direction: column; gap: 20px; }

/* Barre d'outils */
.ord-toolbar {
    display: flex; align-items: center; justify-content: space-between;
    flex-wrap: wrap; gap: 12px;
}
.ord-toolbar__left { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
.ord-toolbar__right { display: flex; align-items: center; gap: 8px; }

/* Champ date */
.ord-dateinput {
    display: inline-flex; align-items: center; gap: 8px;
    padding: 7px 12px;
    border: 1px solid var(--bd-border); border-radius: var(--bd-radius);
    background: var(--bd-surface); color: var(--bd-text);
    font-family: var(--bd-font); font-size: 12px; font-weight: 500;
    cursor: pointer; transition: border-color .12s;
}
.ord-dateinput i { color: var(--bd-text-3); font-size: 12px; }
.ord-dateinput input {
    border: none; background: transparent; outline: none;
    font-family: var(--bd-font); font-size: 12px; color: var(--bd-text);
    width: 200px;
}

/* Pill tabs statut */
.ord-pill-nav { display: flex; gap: 4px; flex-wrap: wrap; }
.ord-pill {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 5px 14px; border-radius: 999px;
    border: 1px solid var(--bd-border);
    background: var(--bd-surface); color: var(--bd-text-2);
    font-size: 12px; font-weight: 600;
    text-decoration: none; transition: .12s;
}
.ord-pill:hover { border-color: var(--bd-green); color: var(--bd-green); }
.ord-pill.is-active { background: var(--bd-green); color: #fff; border-color: var(--bd-green); }
.ord-pill__count {
    display: inline-flex; align-items: center; justify-content: center;
    min-width: 18px; height: 18px; border-radius: 999px;
    background: rgba(255,255,255,.3); font-size: 10px; font-weight: 800;
    padding: 0 4px;
}
.ord-pill:not(.is-active) .ord-pill__count { background: var(--bd-surface-2); color: var(--bd-text-3); }

/* Carte principale */
.ord-card {
    background: var(--bd-surface);
    border: 1px solid var(--bd-border);
    border-radius: var(--bd-radius);
    overflow: hidden;
    transition: background .2s;
}
.ord-card__head {
    display: flex; align-items: center; justify-content: space-between;
    gap: 12px; padding: 14px 20px;
    border-bottom: 1px solid var(--bd-border-2);
    flex-wrap: wrap;
}
.ord-card__title { font-size: 13px; font-weight: 600; color: var(--bd-text); }
.ord-total {
    font-family: var(--bd-font-display, 'League Spartan', sans-serif);
    font-size: 18px; font-weight: 800; color: var(--bd-green);
}
.ord-total small { font-size: 12px; font-weight: 600; font-family: var(--bd-font); }

/* Actions groupées */
.ord-bulk {
    display: flex; align-items: center; gap: 8px;
}
.ord-btn {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 7px 14px; border-radius: var(--bd-radius);
    font-size: 12px; font-weight: 600; cursor: pointer;
    font-family: var(--bd-font); border: none; transition: .12s;
    text-decoration: none;
}
.ord-btn--primary  { background: var(--bd-green); color: #fff; }
.ord-btn--primary:hover  { background: var(--bd-green-dark, #007836); color: #fff; }
.ord-btn--outline  { background: var(--bd-surface); color: var(--bd-text-2); border: 1px solid var(--bd-border); }
.ord-btn--outline:hover  { border-color: var(--bd-green); color: var(--bd-green); }
.ord-btn--danger   { background: rgba(239,68,68,.08); color: #dc2626; border: 1px solid rgba(239,68,68,.2); }
.ord-btn--danger:hover   { background: rgba(239,68,68,.15); }

/* Tableau */
.ord-table-wrap { overflow-x: auto; }
.ord-table {
    width: 100%; border-collapse: collapse;
    font-size: 13px; font-family: var(--bd-font-body, 'Inter', sans-serif);
}
.ord-table thead th {
    padding: 10px 16px;
    font-size: 11px; font-weight: 700; letter-spacing: .06em;
    text-transform: uppercase; color: var(--bd-text-3);
    border-bottom: 1px solid var(--bd-border-2);
    background: var(--bd-surface-2); white-space: nowrap;
    text-align: left;
}
.ord-table thead th:first-child { width: 40px; }
.ord-table tbody tr {
    border-bottom: 1px solid var(--bd-border-2);
    transition: background .1s;
}
.ord-table tbody tr:last-child { border-bottom: none; }
.ord-table tbody tr:hover { background: var(--bd-surface-2); }
.ord-table td {
    padding: 12px 16px; color: var(--bd-text-2);
    vertical-align: middle;
}
.ord-table td:first-child { padding: 12px 8px 12px 16px; }

/* Cellules spécifiques */
.ord-ref {
    font-family: var(--bd-font-display, 'League Spartan', sans-serif);
    font-size: 13px; font-weight: 700; color: var(--bd-text);
    display: block; line-height: 1.2;
}
.ord-ref-time { font-size: 11px; color: var(--bd-text-3); margin-top: 2px; }
.ord-customer { font-size: 13px; font-weight: 600; color: var(--bd-text); }
.ord-address  { font-size: 11px; color: var(--bd-text-3); max-width: 180px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.ord-amount {
    font-family: var(--bd-font-display, 'League Spartan', sans-serif);
    font-size: 15px; font-weight: 800; color: var(--bd-text); white-space: nowrap;
}
.ord-amount-cur { font-size: 10px; font-weight: 600; color: var(--bd-text-3); font-family: var(--bd-font); }

/* Badges statut */
.ord-badge {
    display: inline-flex; align-items: center; gap: 4px;
    padding: 3px 9px; border-radius: 999px;
    font-size: 11px; font-weight: 700; white-space: nowrap;
}
.ord-badge::before { content:''; width:6px; height:6px; border-radius:50%; background:currentColor; display:block; }
.ord-badge--new       { background: rgba(245,158,11,.12); color: #d97706; }
.ord-badge--preparing { background: rgba(59,130,246,.12);  color: #2563eb; }
.ord-badge--delivering{ background: rgba(99,102,241,.12);  color: #4f46e5; }
.ord-badge--done      { background: rgba(0,149,67,.1);     color: var(--bd-green); }
.ord-badge--cancelled { background: rgba(239,68,68,.1);    color: #dc2626; }
[data-theme="dark"] .ord-badge--new       { background:rgba(251,191,36,.15); color:#fbbf24; }
[data-theme="dark"] .ord-badge--preparing { background:rgba(96,165,250,.15); color:#60a5fa; }
[data-theme="dark"] .ord-badge--delivering{ background:rgba(129,140,248,.15);color:#818cf8; }
[data-theme="dark"] .ord-badge--done      { background:rgba(0,201,87,.15);   color:#00c957; }
[data-theme="dark"] .ord-badge--cancelled { background:rgba(248,113,113,.15);color:#f87171; }

/* Chat badge */
.ord-chat {
    display: inline-flex; align-items: center; gap: 4px;
    padding: 2px 8px; border-radius: 999px;
    background: #fff7ed; color: #c2410c;
    font-size: 10px; font-weight: 800; margin-top: 3px;
}
[data-theme="dark"] .ord-chat { background: rgba(194,65,12,.15); color: #fb923c; }

/* Actions par ligne */
.ord-actions { display: flex; align-items: center; gap: 6px; }
.ord-action-btn {
    display: inline-flex; align-items: center; justify-content: center;
    width: 30px; height: 30px; border-radius: 7px;
    border: 1px solid var(--bd-border);
    background: var(--bd-surface); color: var(--bd-text-2);
    cursor: pointer; font-size: 12px; transition: .12s;
    text-decoration: none;
}
.ord-action-btn:hover { border-color: var(--bd-green); color: var(--bd-green); }
.ord-action-btn--cancel { color: #dc2626; border-color: rgba(239,68,68,.2); }
.ord-action-btn--cancel:hover { background: rgba(239,68,68,.06); border-color: #dc2626; color: #dc2626; }

/* Vide */
.ord-empty {
    padding: 48px 20px; text-align: center;
    color: var(--bd-text-3); font-size: 13px;
}
.ord-empty i { font-size: 28px; display: block; margin-bottom: 10px; }

/* Checkbox */
.ord-check { width: 16px; height: 16px; accent-color: var(--bd-green); cursor: pointer; }

@media (max-width: 768px) {
    .ord-col-hide { display: none; }
    .ord-toolbar__left { width: 100%; }
}
</style>
@endsection

@section('content')
@php
    $totalAmount = $orders->sum('total');
    $orderCount  = $orders->count();
@endphp

<div class="ord">

    {{-- ── Alerte session ──────────────────────────────────── --}}
    @if(session()->has('alert'))
        <div class="alert alert-{{ session()->get('alert.type') }} alert-dismissible" role="alert">
            {{ session()->get('alert.message') }}
            <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
        </div>
    @endif

    {{-- ── Navigation inter-pages commandes ───────────────── --}}
    <div class="ord-pill-nav">
        <a href="{{ route('restaurant.all_orders') }}"
           class="ord-pill is-active">
            <i class="fas fa-inbox"></i> Nouvelles
            @if($orderCount > 0)<span class="ord-pill__count">{{ $orderCount }}</span>@endif
        </a>
        <a href="{{ route('restaurant.pending_orders') }}" class="ord-pill">
            <i class="fas fa-truck"></i> En livraison
        </a>
        <a href="{{ route('restaurant.complete_orders') }}" class="ord-pill">
            <i class="fas fa-check-circle"></i> Terminées
        </a>
        <a href="{{ route('restaurant.cancel_orders') }}" class="ord-pill">
            <i class="fas fa-ban"></i> Annulées
        </a>
        <a href="{{ route('restaurant.schedule_orders') }}" class="ord-pill">
            <i class="fas fa-calendar-clock"></i> Programmées
        </a>
    </div>

    {{-- ── Barre outils : filtre date + actions ────────────── --}}
    <div class="ord-toolbar">
        <div class="ord-toolbar__left">
            <form method="get" action="" style="display:contents;">
                <label class="ord-dateinput">
                    <i class="fas fa-calendar-range"></i>
                    <input type="text" name="date" id="ordDateRange"
                           value="{{ request('date','') }}"
                           placeholder="Filtrer par période…" autocomplete="off">
                </label>
                <button type="submit" class="ord-btn ord-btn--outline">
                    <i class="fas fa-filter"></i> Filtrer
                </button>
                @if(request('date'))
                    <a href="{{ route('restaurant.all_orders') }}" class="ord-btn ord-btn--outline">
                        <i class="fas fa-times"></i> Réinitialiser
                    </a>
                @endif
            </form>
        </div>
        <div class="ord-toolbar__right">
            <span style="font-size:12px;color:var(--bd-text-3);">{{ $orderCount }} commande(s)</span>
        </div>
    </div>

    {{-- ── Tableau principal ───────────────────────────────── --}}
    <div class="ord-card">
        <form action="{{ route('restaurant.prepaire_orders') }}" method="post" id="ordBulkForm">
            @csrf
            <div class="ord-card__head">
                <div>
                    <div class="ord-card__title">Nouvelles commandes</div>
                    <div style="font-size:11px;color:var(--bd-text-3);">Sélectionner et passer en préparation</div>
                </div>
                <div style="display:flex;align-items:center;gap:16px;flex-wrap:wrap;">
                    <div>
                        <div style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.05em;color:var(--bd-text-3);">Total sélection</div>
                        <div class="ord-total" id="ordSelTotal">
                            {{ number_format($totalAmount, 0, ',', ' ') }} <small>FCFA</small>
                        </div>
                    </div>
                    <button type="submit" class="ord-btn ord-btn--primary" {{ $orderCount === 0 ? 'disabled' : '' }}>
                        <i class="fas fa-fire-burner"></i> Passer en préparation
                    </button>
                </div>
            </div>

            @if($errors->has('id'))
                <div style="padding:10px 20px;">
                    <div class="alert alert-danger" style="margin:0;font-size:12px;">{{ $errors->first('id') }}</div>
                </div>
            @endif

            <div class="ord-table-wrap">
                @if($orderCount > 0)
                    <table class="ord-table">
                        <thead>
                            <tr>
                                <th>
                                    <input type="checkbox" class="ord-check" id="ordCheckAll"
                                           title="Tout sélectionner">
                                </th>
                                <th>Référence</th>
                                <th class="ord-col-hide">Client</th>
                                <th>Montant</th>
                                <th class="ord-col-hide">Adresse</th>
                                <th>Statut</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($orders as $order)
                                <tr>
                                    <td>
                                        <input type="checkbox" class="ord-check ord-row-check"
                                               name="id[]" value="{{ $order->order_no }}"
                                               data-amount="{{ (float) $order->total }}">
                                    </td>
                                    <td>
                                        <span class="ord-ref">{{ $order->order_no }}</span>
                                        <span class="ord-ref-time">
                                            {{ \Carbon\Carbon::parse($order->created_at)->format('d/m · H:i') }}
                                        </span>
                                        @if(!empty($order->chatBadge['has_unread']))
                                            <a href="{{ route('restaurant.show_orders', $order->order_no) }}#chat" class="ord-chat" title="Ouvrir la conversation">
                                                <i class="fas fa-comment-dots" style="font-size:9px;"></i>
                                                {{ $order->chatBadge['label'] }}
                                            </a>
                                        @endif
                                    </td>
                                    <td class="ord-col-hide">
                                        <div class="ord-customer">
                                            {{ $order->user->name ?? $order->customer_name ?? 'Client' }}
                                        </div>
                                    </td>
                                    <td>
                                        <span class="ord-amount">
                                            {{ number_format((float) $order->total, 0, ',', ' ') }}
                                        </span>
                                        <span class="ord-amount-cur">FCFA</span>
                                    </td>
                                    <td class="ord-col-hide">
                                        <div class="ord-address" title="{{ $order->delivery_address }}">
                                            {{ $order->delivery_address ?? '—' }}
                                        </div>
                                    </td>
                                    <td>
                                        @php
                                            $statusMap = [
                                                'pending'    => ['label' => 'Nouvelle',    'cls' => 'new'],
                                                'new'        => ['label' => 'Nouvelle',    'cls' => 'new'],
                                                'received'   => ['label' => 'Reçue',       'cls' => 'new'],
                                                'preparing'  => ['label' => 'Préparation', 'cls' => 'preparing'],
                                                'accepted'   => ['label' => 'Acceptée',    'cls' => 'preparing'],
                                                'delivering' => ['label' => 'Livraison',   'cls' => 'delivering'],
                                                'completed'  => ['label' => 'Terminée',    'cls' => 'done'],
                                                'delivered'  => ['label' => 'Livrée',      'cls' => 'done'],
                                                'cancelled'  => ['label' => 'Annulée',     'cls' => 'cancelled'],
                                                'rejected'   => ['label' => 'Refusée',     'cls' => 'cancelled'],
                                            ];
                                            $st = $statusMap[strtolower($order->status)] ?? ['label' => ucfirst($order->status), 'cls' => 'new'];
                                        @endphp
                                        <span class="ord-badge ord-badge--{{ $st['cls'] }}">{{ $st['label'] }}</span>
                                    </td>
                                    <td>
                                        <div class="ord-actions">
                                            <a href="{{ route('restaurant.show_order', $order->order_no) }}"
                                               class="ord-action-btn" title="Voir le détail">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            {{-- Accepter (préparer) --}}
                                            <form method="POST"
                                                  action="{{ route('restaurant.prepaire_orders') }}"
                                                  style="display:inline">
                                                @csrf
                                                <input type="hidden" name="id[]" value="{{ $order->order_no }}">
                                                <button type="submit" class="ord-action-btn ord-action-btn--accept" title="Accepter et préparer"
                                                        style="background:#dcfce7;color:#16a34a;border:1px solid #bbf7d0">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                            </form>
                                            {{-- Refuser --}}
                                            <form method="POST"
                                                  action="{{ route('restaurant.cancel_order', $order->order_no) }}"
                                                  onsubmit="return confirm('Refuser cette commande ? Un motif sera requis.')">
                                                @csrf
                                                <input type="hidden" name="reason" value="restaurant_refused">
                                                <button type="submit" class="ord-action-btn ord-action-btn--cancel" title="Refuser la commande">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <div class="ord-empty">
                        <i class="fas fa-inbox" style="color:var(--bd-green);"></i>
                        Aucune nouvelle commande pour le moment
                    </div>
                @endif
            </div>
        </form>
    </div>

</div>
@endsection

@section('script')
<script src="{{ asset('plugins/daterangepicker/daterangepicker.js') }}" defer></script>
<script>
$(function () {
    // Date range picker
    if ($.fn.daterangepicker) {
        $('#ordDateRange').daterangepicker({
            autoUpdateInput: false,
            locale: { cancelLabel: 'Vider', applyLabel: 'Appliquer', format: 'DD/MM/YYYY',
                      daysOfWeek: ['Di','Lu','Ma','Me','Je','Ve','Sa'],
                      monthNames: ['Janvier','Février','Mars','Avril','Mai','Juin','Juillet','Août','Septembre','Octobre','Novembre','Décembre'],
                      firstDay: 1 }
        });
        $('#ordDateRange').on('apply.daterangepicker', function (ev, picker) {
            $(this).val(picker.startDate.format('DD/MM/YYYY') + ' - ' + picker.endDate.format('DD/MM/YYYY'));
        });
        $('#ordDateRange').on('cancel.daterangepicker', function () { $(this).val(''); });
    }

    // Tout cocher
    $('#ordCheckAll').on('change', function () {
        $('.ord-row-check').prop('checked', this.checked);
        updateSelTotal();
    });
    $(document).on('change', '.ord-row-check', updateSelTotal);

    function updateSelTotal() {
        var total = 0;
        var allChecked = true;
        $('.ord-row-check').each(function () {
            if (this.checked) total += parseFloat(this.getAttribute('data-amount') || 0);
            else allChecked = false;
        });
        var fmt = new Intl.NumberFormat('fr-FR', { maximumFractionDigits: 0 }).format(total);
        $('#ordSelTotal').html(fmt + ' <small>FCFA</small>');
        $('#ordCheckAll').prop('indeterminate', !allChecked && $('.ord-row-check:checked').length > 0);
    }
});
</script>
@endsection
