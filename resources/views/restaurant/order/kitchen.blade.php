@extends('layouts.restaurant_app')
@section('title','Écran cuisine | Restaurant')
@section('topbar_title', 'Écran cuisine')
@section('order_nav', 'active')
@section('order_nav_open', 'menu-open')

@section('style')
<style>
    .kitchen-header {
        display:flex; align-items:center; justify-content:space-between; gap:12px;
    }
    .kitchen-grid {
        display:grid;
        grid-template-columns: 1fr 1fr 1fr;
        gap: 14px;
    }
    .k-col {
        background: #f8fafc;
        border: 1px solid #e5e7eb;
        border-radius: 16px;
        overflow: hidden;
    }
    .k-col-head {
        padding: 12px 14px;
        font-weight: 800;
        display:flex; align-items:center; justify-content:space-between;
        border-bottom: 1px solid #e5e7eb;
        background: #fff;
    }
    .k-col-body { padding: 12px; display:flex; flex-direction:column; gap: 12px; min-height: 65vh; }
    .order-card {
        border-radius: 14px;
        border: 1px solid #e5e7eb;
        background: #fff;
        box-shadow: 0 2px 10px rgba(0,0,0,0.06);
        overflow: hidden;
    }
    .order-card-head {
        padding: 12px 14px;
        display:flex; align-items:flex-start; justify-content:space-between; gap: 10px;
        background: #111827;
        color: #fff;
    }
    .order-no { font-weight: 900; letter-spacing: .3px; }
    .order-meta { font-size: 12px; opacity: .9; }
    .order-card-body { padding: 12px 14px; }
    .item-row { display:flex; gap: 10px; align-items:center; padding: 8px 0; border-bottom: 1px dashed #e5e7eb; }
    .item-row:last-child { border-bottom: none; }
    .item-img { width: 38px; height: 38px; border-radius: 10px; object-fit: cover; background: #f3f4f6; }
    .item-name { font-weight: 700; line-height: 1.2; }
    .item-sub { font-size: 12px; color: #6b7280; }
    .order-actions { padding: 12px 14px; display:flex; gap: 8px; flex-wrap: wrap; border-top: 1px solid #eef2f7; }
    .pill { border-radius: 999px; }
    .muted { color:#6b7280; font-size: 12px; }
    @media (max-width: 1200px) {
        .kitchen-grid { grid-template-columns: 1fr; }
        .k-col-body { min-height: auto; }
    }
</style>
@endsection

@section('content')
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <div class="kitchen-header">
                    <div>
                        <h1 class="m-0 text-dark">Écran cuisine</h1>
                        <div class="text-muted">Mise à jour auto (polling) • Actions 1-clic • Vue moderne</div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="{{ route('restaurant.dashboard') }}">Accueil</a></li>
                    <li class="breadcrumb-item active">Cuisine</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<section class="content">
    <div class="container-fluid">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center" style="gap: 12px; flex-wrap: wrap;">
                    <button class="btn btn-outline-primary pill" id="btnRefresh">
                        <i class="fas fa-sync"></i> Rafraîchir
                    </button>
                    <div class="muted">Dernière synchro: <span id="lastSync">—</span></div>
                    <div class="muted">Intervalle: <span id="pollInterval">5s</span></div>
                </div>
            </div>
        </div>

        <div class="kitchen-grid">
            <div class="k-col">
                <div class="k-col-head">
                    <span>🟠 Nouvelles commandes</span>
                    <span class="badge badge-warning" id="countPending">0</span>
                </div>
                <div class="k-col-body" id="colPending"></div>
            </div>

            <div class="k-col">
                <div class="k-col-head">
                    <span>🟣 En préparation</span>
                    <span class="badge badge-info" id="countPreparing">0</span>
                </div>
                <div class="k-col-body" id="colPreparing"></div>
            </div>

            <div class="k-col">
                <div class="k-col-head">
                    <span>🟢 Prêtes / dispatch</span>
                    <span class="badge badge-success" id="countReady">0</span>
                </div>
                <div class="k-col-body" id="colReady"></div>
            </div>
        </div>
    </div>
</section>
@endsection

@section('script')
<script>
    const csrf = '{{ csrf_token() }}';
    const ordersUrl = '{{ route('restaurant.kitchen.orders') }}';
    const statusUrlBase = '{{ url('/') }}/restaurant/kitchen/orders/';
    const pollMs = 5000;
    let lastUpdatedAfter = null;
    let lastPendingCount = 0;

    function formatMoney(v) {
        const rounded = Math.round(Number(v || 0));
        try { return new Intl.NumberFormat('fr-FR', { minimumFractionDigits: 0, maximumFractionDigits: 0 }).format(rounded) + ' FCFA'; } catch(e) { return rounded + ' FCFA'; }
    }

    // Contexte audio partagé — évite la fuite mémoire à chaque bip
    var _kitchenAudioCtx = null;
    var _kitchenAudioUnlocked = false;
    (function () {
        function unlock() { _kitchenAudioUnlocked = true; }
        document.addEventListener('click',    unlock, { once: true });
        document.addEventListener('keydown',  unlock, { once: true });
        document.addEventListener('touchstart', unlock, { once: true, passive: true });
    })();

    function beep() {
        if (!_kitchenAudioUnlocked) return;
        try {
            var C = window.AudioContext || window.webkitAudioContext;
            if (!C) return;
            if (!_kitchenAudioCtx || _kitchenAudioCtx.state === 'closed') _kitchenAudioCtx = new C();
            if (_kitchenAudioCtx.state === 'suspended') _kitchenAudioCtx.resume();
            var ctx = _kitchenAudioCtx;
            var o = ctx.createOscillator();
            var g = ctx.createGain();
            o.type = 'sine'; o.frequency.value = 880;
            o.connect(g); g.connect(ctx.destination);
            var t = ctx.currentTime;
            g.gain.setValueAtTime(0.001, t);
            g.gain.exponentialRampToValueAtTime(0.2, t + 0.01);
            g.gain.exponentialRampToValueAtTime(0.001, t + 0.18);
            o.start(t); o.stop(t + 0.2);
        } catch (e) {}
    }

    function renderOrderCard(o) {
        const businessStatus = o.business_status || o.status;
        const businessStatusLabel = {
            pending_restaurant_acceptance: 'En attente',
            accepted: 'Acceptée',
            in_kitchen: 'En préparation',
            ready_for_pickup: 'Prête',
            customer_arrived: 'Client arrivé',
            driver_assigned: 'Livreur assigné',
            picked_up: 'Récupérée',
            picked_up_by_customer: 'Retirée',
            out_for_delivery: 'En livraison',
            delivered: 'Livrée',
            no_show: 'Client absent',
            cancelled: 'Annulée'
        }[businessStatus] || businessStatus;
        const itemsHtml = (o.items || []).map(it => {
            const img = it.product_image ? `<img class="item-img" src="${it.product_image}" onerror="this.style.display='none'">` : `<div class="item-img"></div>`;
            return `
                <div class="item-row">
                    ${img}
                    <div style="flex:1;">
                        <div class="item-name">${it.product_name}</div>
                        <div class="item-sub">Qté: ${it.qty} • ${formatMoney(it.price)}</div>
                    </div>
                    <div style="font-weight:800;">${formatMoney(it.line_total)}</div>
                </div>
            `;
        }).join('');

        const customerLine = o.customer ? `${o.customer.name || 'Client'}${o.customer.phone ? ' • ' + o.customer.phone : ''}` : 'Client';
        const created = o.created_at ? new Date(o.created_at).toLocaleString('fr-FR') : '';

        const isPickupOrder = (o.fulfillment_mode || 'delivery') === 'pickup';
        let actions = '';
        if (['pending_restaurant_acceptance', 'pending'].includes(businessStatus)) {
            actions = `
                <button class="btn btn-sm btn-primary pill" onclick="setStatus('${o.order_no}','accepted')">Accepter</button>
                <button class="btn btn-sm btn-info pill" onclick="setStatus('${o.order_no}','in_kitchen')">Lancer cuisine</button>
                <button class="btn btn-sm btn-outline-danger pill" onclick="setStatus('${o.order_no}','cancelled')">Annuler</button>
            `;
        } else if (businessStatus === 'accepted') {
            actions = `
                <button class="btn btn-sm btn-info pill" onclick="setStatus('${o.order_no}','in_kitchen')">Démarrer préparation</button>
                <button class="btn btn-sm btn-outline-danger pill" onclick="setStatus('${o.order_no}','cancelled')">Annuler</button>
            `;
        } else if (['in_kitchen', 'prepairing'].includes(businessStatus)) {
            actions = `
                <button class="btn btn-sm btn-success pill" onclick="setStatus('${o.order_no}','ready_for_pickup')">Marquer prête</button>
                <button class="btn btn-sm btn-outline-danger pill" onclick="setStatus('${o.order_no}','cancelled')">Annuler</button>
            `;
        } else if (isPickupOrder && businessStatus === 'ready_for_pickup') {
            actions = `
                <span class="badge badge-primary">Code retrait: ${o.pickup_code || '----'}</span>
                <button class="btn btn-sm btn-primary pill" onclick="setStatus('${o.order_no}','customer_arrived')">Client arrivé</button>
                <button class="btn btn-sm btn-outline-danger pill" onclick="setStatus('${o.order_no}','no_show')">Client absent</button>
            `;
        } else if (isPickupOrder && businessStatus === 'customer_arrived') {
            actions = `
                <span class="badge badge-primary">Code retrait: ${o.pickup_code || '----'}</span>
                <button class="btn btn-sm btn-success pill" onclick="confirmPickup('${o.order_no}', '${o.pickup_code || ''}')">Confirmer retrait</button>
            `;
        } else if (isPickupOrder && businessStatus === 'no_show') {
            actions = `
                <span class="badge badge-warning">Client absent</span>
                <button class="btn btn-sm btn-primary pill" onclick="setStatus('${o.order_no}','ready_for_pickup')">Réactiver</button>
                <button class="btn btn-sm btn-outline-danger pill" onclick="setStatus('${o.order_no}','cancelled')">Annuler</button>
            `;
        } else if (['ready_for_pickup', 'driver_assigned', 'assign'].includes(businessStatus)) {
            actions = `<span class="badge badge-success">Attente livreur / remise</span>`;
        }

        return `
            <div class="order-card">
                <div class="order-card-head">
                    <div>
                        <div class="order-no">#${o.order_no}</div>
                        <div class="order-meta">${customerLine}</div>
                        <div class="order-meta">${created}</div>
                        <div class="order-meta" style="font-weight:700;">${businessStatusLabel}${isPickupOrder ? ' • retrait' : ''}</div>
                    </div>
                    <div style="text-align:right;">
                        <div style="font-weight:900; font-size: 16px;">${formatMoney(o.total || 0)}</div>
                        <div class="order-meta">${o.items_count || 0} item(s)</div>
                    </div>
                </div>
                <div class="order-card-body">${itemsHtml}</div>
                <div class="order-actions">${actions}</div>
            </div>
        `;
    }

    async function fetchOrders() {
        const url = new URL(ordersUrl, window.location.origin);
        url.searchParams.set('status[]', 'pending');
        url.searchParams.set('status[]', 'accepted');
        url.searchParams.set('status[]', 'prepairing');
        url.searchParams.set('status[]', 'in_kitchen');
        url.searchParams.set('status[]', 'assign');
        url.searchParams.set('status[]', 'ready_for_pickup');
        url.searchParams.set('status[]', 'driver_assigned');
        url.searchParams.set('status[]', 'customer_arrived');
        url.searchParams.set('status[]', 'no_show');
        if (lastUpdatedAfter) url.searchParams.set('updated_after', lastUpdatedAfter);

        const res = await fetch(url.toString(), { headers: { 'Accept': 'application/json' }});
        const json = await res.json();
        if (!res.ok) throw new Error(json.message || 'Erreur');
        lastUpdatedAfter = json.server_time;
        return json.data || [];
    }

    function confirmPickup(orderNo, expectedCode) {
        const entered = window.prompt(`Code de retrait pour ${orderNo}`, expectedCode || '');
        if (entered === null) return;
        setStatus(orderNo, 'picked_up_by_customer', entered.trim());
    }

    async function setStatus(orderNo, status, pickupCode = '') {
        if (status === 'cancelled' && !confirm('Annuler cette commande ?')) return;
        const url = statusUrlBase + orderNo + '/status';
        const res = await fetch(url, {
            method: 'PATCH',
            headers: { 'X-CSRF-TOKEN': csrf, 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify({ status, pickup_code: pickupCode })
        });
        const json = await res.json();
        if (!res.ok) return alert(json.message || 'Erreur');
        await refresh(true);
    }

    async function refresh(forceFull) {
        try {
            if (forceFull) lastUpdatedAfter = null;
            const data = await fetchOrders();

            const pending = data.filter(o => ['pending_restaurant_acceptance', 'accepted', 'pending'].includes(o.business_status || o.status));
            const preparing = data.filter(o => ['in_kitchen', 'prepairing'].includes(o.business_status || o.status));
            const ready = data.filter(o => ['ready_for_pickup', 'driver_assigned', 'assign', 'customer_arrived', 'no_show'].includes(o.business_status || o.status));

            document.getElementById('countPending').innerText = pending.length;
            document.getElementById('countPreparing').innerText = preparing.length;
            document.getElementById('countReady').innerText = ready.length;

            document.getElementById('colPending').innerHTML = pending.map(renderOrderCard).join('') || '<div class="muted">Aucune commande</div>';
            document.getElementById('colPreparing').innerHTML = preparing.map(renderOrderCard).join('') || '<div class="muted">Aucune commande</div>';
            document.getElementById('colReady').innerHTML = ready.map(renderOrderCard).join('') || '<div class="muted">Aucune commande</div>';

            const now = new Date().toLocaleTimeString('fr-FR');
            document.getElementById('lastSync').innerText = now;

            if (pending.length > lastPendingCount) {
                beep();
            }
            lastPendingCount = pending.length;
        } catch (e) {
            console.error(e);
        }
    }

    document.getElementById('btnRefresh').addEventListener('click', () => refresh(true));
    refresh(true);
    setInterval(() => refresh(false), pollMs);
</script>
@endsection
