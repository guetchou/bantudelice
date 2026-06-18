@extends('layouts.restaurant_app')
@section('title', 'Écran cuisine | ' . \App\Services\ConfigService::getCompanyName())
@section('topbar_title', 'Écran cuisine')
@section('order_nav', 'active')
@section('order_nav_open', 'menu-open')

@section('style')
<style>
.kitch { display: flex; flex-direction: column; gap: 16px; }

.kitch-toolbar {
    background: var(--bd-surface); border: 1px solid var(--bd-border);
    border-radius: var(--bd-radius); padding: 12px 18px;
    display: flex; align-items: center; gap: 12px; flex-wrap: wrap;
}
.kitch-refresh-btn {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 7px 14px; border-radius: var(--bd-radius);
    font-size: 12px; font-weight: 700; cursor: pointer;
    background: var(--bd-surface); color: var(--bd-text-2);
    border: 1px solid var(--bd-border); font-family: var(--bd-font);
    transition: .12s;
}
.kitch-refresh-btn:hover { border-color: var(--bd-green); color: var(--bd-green); }
.kitch-sync-info { font-size: 11px; color: var(--bd-text-3); }

.kitch-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 14px;
}
@media (max-width: 1100px) { .kitch-grid { grid-template-columns: 1fr; } }
@media (min-width: 600px) and (max-width: 1100px) { .kitch-grid { grid-template-columns: 1fr 1fr; } }

.k-col {
    background: var(--bd-surface-2); border: 1px solid var(--bd-border);
    border-radius: var(--bd-radius); overflow: hidden;
}
.k-col-head {
    padding: 11px 14px; font-weight: 700; font-size: 13px;
    display: flex; align-items: center; justify-content: space-between;
    border-bottom: 1px solid var(--bd-border-2);
    background: var(--bd-surface); color: var(--bd-text);
}
.k-count {
    display: inline-flex; align-items: center; justify-content: center;
    min-width: 20px; height: 20px; padding: 0 5px;
    border-radius: 999px; font-size: 11px; font-weight: 700;
    background: var(--bd-surface-2); color: var(--bd-text-3);
    border: 1px solid var(--bd-border);
}
.k-count--orange { background: rgba(245,158,11,.15); color: #d97706; border-color: rgba(245,158,11,.3); }
.k-count--blue   { background: rgba(59,130,246,.15); color: #2563eb; border-color: rgba(59,130,246,.3); }
.k-count--green  { background: rgba(0,149,67,.15); color: var(--bd-green); border-color: rgba(0,149,67,.3); }

.k-col-body { padding: 12px; display: flex; flex-direction: column; gap: 10px; min-height: 60vh; }
@media (max-width: 1100px) { .k-col-body { min-height: auto; } }

.k-empty { padding: 24px 12px; text-align: center; color: var(--bd-text-3); font-size: 12px; }
.k-empty i { font-size: 20px; display: block; margin-bottom: 8px; color: var(--bd-border); }

.order-card {
    border-radius: calc(var(--bd-radius) - 2px);
    border: 1px solid var(--bd-border);
    background: var(--bd-surface);
    overflow: hidden;
}
.order-card-head {
    padding: 11px 14px;
    display: flex; align-items: flex-start; justify-content: space-between; gap: 10px;
    background: #111827;
    color: #fff;
}
.order-no { font-weight: 900; font-size: 14px; letter-spacing: .3px; }
.order-meta { font-size: 11px; opacity: .85; line-height: 1.5; }
.order-card-body { padding: 10px 14px; }
.item-row {
    display: flex; gap: 10px; align-items: center;
    padding: 7px 0; border-bottom: 1px dashed var(--bd-border-2);
}
.item-row:last-child { border-bottom: none; }
.item-img {
    width: 36px; height: 36px; border-radius: 8px;
    object-fit: cover; background: var(--bd-surface-2);
    border: 1px solid var(--bd-border-2); flex-shrink: 0;
}
.item-name { font-weight: 700; font-size: 12px; color: var(--bd-text); line-height: 1.2; }
.item-sub { font-size: 11px; color: var(--bd-text-3); }
.order-actions { padding: 10px 14px; display: flex; gap: 6px; flex-wrap: wrap; border-top: 1px solid var(--bd-border-2); }

.k-btn {
    display: inline-flex; align-items: center; gap: 4px;
    padding: 5px 12px; border-radius: 999px;
    font-size: 11px; font-weight: 700; cursor: pointer;
    border: none; font-family: var(--bd-font); transition: .12s;
}
.k-btn--blue   { background: #3b82f6; color: #fff; }
.k-btn--blue:hover   { background: #2563eb; }
.k-btn--cyan   { background: #06b6d4; color: #fff; }
.k-btn--cyan:hover   { background: #0891b2; }
.k-btn--green  { background: var(--bd-green); color: #fff; }
.k-btn--green:hover  { background: var(--bd-green-dark, #007836); }
.k-btn--red    { background: var(--bd-surface); color: #dc2626; border: 1px solid rgba(220,38,38,.3); }
.k-btn--red:hover    { background: rgba(220,38,38,.08); }
.k-badge {
    display: inline-flex; align-items: center; gap: 4px;
    padding: 3px 9px; border-radius: 999px;
    font-size: 11px; font-weight: 700;
    background: var(--bd-surface-2); color: var(--bd-text-3);
    border: 1px solid var(--bd-border);
}
.k-badge--green { background: rgba(0,149,67,.1); color: var(--bd-green); border-color: rgba(0,149,67,.3); }
.k-badge--blue  { background: rgba(59,130,246,.1); color: #2563eb; border-color: rgba(59,130,246,.3); }
</style>
@endsection

@section('content')
<div class="kitch">

    <div class="kitch-toolbar">
        <button class="kitch-refresh-btn" id="btnRefresh">
            <i class="fas fa-rotate"></i> Rafraîchir
        </button>
        <span class="kitch-sync-info">Dernière synchro : <b id="lastSync">—</b></span>
        <span class="kitch-sync-info">Intervalle : <b id="pollInterval">5s</b></span>
    </div>

    <div class="kitch-grid">
        <div class="k-col">
            <div class="k-col-head">
                <span>Nouvelles commandes</span>
                <span class="k-count k-count--orange" id="countPending">0</span>
            </div>
            <div class="k-col-body" id="colPending">
                <div class="k-empty"><i class="fas fa-inbox"></i>Chargement…</div>
            </div>
        </div>

        <div class="k-col">
            <div class="k-col-head">
                <span>En préparation</span>
                <span class="k-count k-count--blue" id="countPreparing">0</span>
            </div>
            <div class="k-col-body" id="colPreparing">
                <div class="k-empty"><i class="fas fa-inbox"></i>Chargement…</div>
            </div>
        </div>

        <div class="k-col">
            <div class="k-col-head">
                <span>Prêtes / dispatch</span>
                <span class="k-count k-count--green" id="countReady">0</span>
            </div>
            <div class="k-col-body" id="colReady">
                <div class="k-empty"><i class="fas fa-inbox"></i>Chargement…</div>
            </div>
        </div>
    </div>

</div>
@endsection

@section('script')
<script>
    let csrf = '{{ csrf_token() }}';
    const ordersUrl = '{{ route('restaurant.kitchen.orders') }}';
    const statusUrlBase = '{{ url('/') }}/restaurant/kitchen/orders/';
    const keepaliveUrl = '{{ route('session.keepalive') }}';
    const pollMs = 60000; // fallback si WebSocket indisponible
    let lastUpdatedAfter = null;
    let lastPendingCount = 0;
    let sessionLost = false;

    // Keepalive : rafraîchit la session et le token CSRF toutes les 5 min
    async function sessionKeepalive() {
        try {
            const res = await fetch(keepaliveUrl, { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' });
            if (res.status === 401 || res.status === 419) {
                if (!sessionLost) {
                    sessionLost = true;
                    document.getElementById('lastSync').textContent = 'Session expirée — rechargement…';
                    setTimeout(() => window.location.reload(), 3000);
                }
                return;
            }
            const data = await res.json();
            if (data.csrf) csrf = data.csrf;
            sessionLost = false;
        } catch (e) {}
    }
    setInterval(sessionKeepalive, 5 * 60 * 1000);
    sessionKeepalive();

    function formatMoney(v) {
        const rounded = Math.round(Number(v || 0));
        try {
            return new Intl.NumberFormat('fr-FR', { minimumFractionDigits: 0, maximumFractionDigits: 0 }).format(rounded) + ' FCFA';
        } catch(e) { return rounded + ' FCFA'; }
    }

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
            var o = ctx.createOscillator(), g = ctx.createGain();
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
        const bs = o.business_status || o.status;
        const bsLabel = {
            pending_restaurant_acceptance: 'En attente',
            accepted: 'Acceptée', in_kitchen: 'En préparation',
            ready_for_pickup: 'Prête', customer_arrived: 'Client arrivé',
            driver_assigned: 'Livreur assigné', picked_up: 'Récupérée',
            picked_up_by_customer: 'Retirée', out_for_delivery: 'En livraison',
            delivered: 'Livrée', no_show: 'Client absent', cancelled: 'Annulée'
        }[bs] || bs;

        const itemsHtml = (o.items || []).map(it => {
            const img = it.product_image
                ? `<img class="item-img" src="${it.product_image}" onerror="this.style.display='none'" alt="">`
                : `<div class="item-img"></div>`;
            return `<div class="item-row">${img}<div style="flex:1;"><div class="item-name">${it.product_name}</div><div class="item-sub">Qté : ${it.qty} · ${formatMoney(it.price)}</div></div><div style="font-size:12px;font-weight:800;color:var(--bd-text);">${formatMoney(it.line_total)}</div></div>`;
        }).join('');

        const customerLine = o.customer ? `${o.customer.name || 'Client'}${o.customer.phone ? ' · ' + o.customer.phone : ''}` : 'Client';
        const created = o.created_at ? new Date(o.created_at).toLocaleString('fr-FR') : '';
        const isPickup = (o.fulfillment_mode || 'delivery') === 'pickup';

        let actions = '';
        if (['pending_restaurant_acceptance', 'pending'].includes(bs)) {
            actions = `
                <button class="k-btn k-btn--blue" onclick="setStatus('${o.order_no}','accepted')"><i class="fas fa-check"></i> Accepter</button>
                <button class="k-btn k-btn--cyan" onclick="setStatus('${o.order_no}','in_kitchen')"><i class="fas fa-fire"></i> Lancer cuisine</button>
                <button class="k-btn k-btn--red" onclick="setStatus('${o.order_no}','cancelled')"><i class="fas fa-ban"></i> Annuler</button>
            `;
        } else if (bs === 'accepted') {
            actions = `
                <button class="k-btn k-btn--cyan" onclick="setStatus('${o.order_no}','in_kitchen')"><i class="fas fa-fire"></i> Démarrer préparation</button>
                <button class="k-btn k-btn--red" onclick="setStatus('${o.order_no}','cancelled')"><i class="fas fa-ban"></i> Annuler</button>
            `;
        } else if (['in_kitchen', 'prepairing'].includes(bs)) {
            actions = `
                <button class="k-btn k-btn--green" onclick="setStatus('${o.order_no}','ready_for_pickup')"><i class="fas fa-bell"></i> Marquer prête</button>
                <button class="k-btn k-btn--red" onclick="setStatus('${o.order_no}','cancelled')"><i class="fas fa-ban"></i> Annuler</button>
            `;
        } else if (isPickup && bs === 'ready_for_pickup') {
            actions = `
                <span class="k-badge k-badge--blue"><i class="fas fa-key"></i> Code : ${o.pickup_code || '----'}</span>
                <button class="k-btn k-btn--blue" onclick="setStatus('${o.order_no}','customer_arrived')">Client arrivé</button>
                <button class="k-btn k-btn--red" onclick="setStatus('${o.order_no}','no_show')">Client absent</button>
            `;
        } else if (isPickup && bs === 'customer_arrived') {
            actions = `
                <span class="k-badge k-badge--blue"><i class="fas fa-key"></i> Code : ${o.pickup_code || '----'}</span>
                <button class="k-btn k-btn--green" onclick="confirmPickup('${o.order_no}','${o.pickup_code || ''}')">Confirmer retrait</button>
            `;
        } else if (isPickup && bs === 'no_show') {
            actions = `
                <span class="k-badge">Client absent</span>
                <button class="k-btn k-btn--blue" onclick="setStatus('${o.order_no}','ready_for_pickup')">Réactiver</button>
                <button class="k-btn k-btn--red" onclick="setStatus('${o.order_no}','cancelled')">Annuler</button>
            `;
        } else if (['ready_for_pickup', 'driver_assigned', 'assign'].includes(bs)) {
            actions = `<span class="k-badge k-badge--green"><i class="fas fa-motorcycle"></i> Attente livreur</span>`;
        }

        return `<div class="order-card">
            <div class="order-card-head">
                <div>
                    <div class="order-no">#${o.order_no}</div>
                    <div class="order-meta">${customerLine}</div>
                    <div class="order-meta">${created}</div>
                    <div class="order-meta" style="font-weight:700;">${bsLabel}${isPickup ? ' · retrait' : ''}</div>
                </div>
                <div style="text-align:right;">
                    <div style="font-weight:900;font-size:15px;">${formatMoney(o.total || 0)}</div>
                    <div class="order-meta">${o.items_count || 0} article(s)</div>
                </div>
            </div>
            <div class="order-card-body">${itemsHtml}</div>
            <div class="order-actions">${actions}</div>
        </div>`;
    }

    async function fetchOrders() {
        const url = new URL(ordersUrl, window.location.origin);
        ['pending','accepted','prepairing','in_kitchen','assign','ready_for_pickup','driver_assigned','customer_arrived','no_show']
            .forEach(s => url.searchParams.append('status[]', s));
        if (lastUpdatedAfter) url.searchParams.set('updated_after', lastUpdatedAfter);
        const res = await fetch(url.toString(), { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' });
        if (res.status === 401 || res.status === 419) {
            await sessionKeepalive();
            return null;
        }
        const json = await res.json();
        if (!res.ok) throw new Error(json.message || 'Erreur');
        lastUpdatedAfter = json.server_time;
        return json.data || [];
    }

    function confirmPickup(orderNo, expectedCode) {
        const entered = window.prompt(`Code de retrait pour #${orderNo}`, expectedCode || '');
        if (entered === null) return;
        setStatus(orderNo, 'picked_up_by_customer', entered.trim());
    }

    async function setStatus(orderNo, status, pickupCode) {
        if (status === 'cancelled' && !confirm('Annuler cette commande ?')) return;
        const res = await fetch(statusUrlBase + orderNo + '/status', {
            method: 'PATCH',
            headers: { 'X-CSRF-TOKEN': csrf, 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify({ status, pickup_code: pickupCode || '' })
        });
        const json = await res.json();
        if (!res.ok) { alert(json.message || 'Erreur'); return; }
        await refresh(true);
    }

    function emptyMsg() { return '<div class="k-empty"><i class="fas fa-inbox"></i>Aucune commande</div>'; }

    async function refresh(forceFull) {
        try {
            if (forceFull) lastUpdatedAfter = null;
            const data = await fetchOrders();
            if (data === null) return;
            const pending   = data.filter(o => ['pending_restaurant_acceptance','accepted','pending'].includes(o.business_status || o.status));
            const preparing = data.filter(o => ['in_kitchen','prepairing'].includes(o.business_status || o.status));
            const ready     = data.filter(o => ['ready_for_pickup','driver_assigned','assign','customer_arrived','no_show'].includes(o.business_status || o.status));

            document.getElementById('countPending').textContent   = pending.length;
            document.getElementById('countPreparing').textContent = preparing.length;
            document.getElementById('countReady').textContent     = ready.length;

            document.getElementById('colPending').innerHTML   = pending.map(renderOrderCard).join('') || emptyMsg();
            document.getElementById('colPreparing').innerHTML = preparing.map(renderOrderCard).join('') || emptyMsg();
            document.getElementById('colReady').innerHTML     = ready.map(renderOrderCard).join('') || emptyMsg();

            document.getElementById('lastSync').textContent = new Date().toLocaleTimeString('fr-FR');
            if (pending.length > lastPendingCount) beep();
            lastPendingCount = pending.length;
        } catch (e) {
            console.error('Kitchen refresh error:', e);
        }
    }

    document.getElementById('btnRefresh').addEventListener('click', () => refresh(true));
    refresh(true);
    setInterval(() => refresh(false), pollMs);

    // Mise à jour immédiate via WebSocket (événement émis par restaurant_app layout)
    window.addEventListener('bd:restaurant-order-updated', function() { refresh(true); });
</script>
@endsection
