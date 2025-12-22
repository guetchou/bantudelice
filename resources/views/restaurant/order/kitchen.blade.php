@extends('layouts.app')
@section('title','Kitchen Display | Restaurant')
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
                        <h1 class="m-0 text-dark">Kitchen Display</h1>
                        <div class="text-muted">Mise à jour auto (polling) • Actions 1-clic • Vue moderne</div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="{{ route('restaurant.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active">Kitchen</li>
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
                    <span>🟠 Nouvelles</span>
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
                    <span>🟢 Assignées</span>
                    <span class="badge badge-success" id="countAssign">0</span>
                </div>
                <div class="k-col-body" id="colAssign"></div>
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
        try { return new Intl.NumberFormat('fr-FR').format(v) + ' FCFA'; } catch(e) { return v + ' FCFA'; }
    }

    function beep() {
        try {
            const ctx = new (window.AudioContext || window.webkitAudioContext)();
            const o = ctx.createOscillator();
            const g = ctx.createGain();
            o.type = 'sine';
            o.frequency.value = 880;
            o.connect(g);
            g.connect(ctx.destination);
            g.gain.setValueAtTime(0.001, ctx.currentTime);
            g.gain.exponentialRampToValueAtTime(0.2, ctx.currentTime + 0.01);
            g.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.18);
            o.start();
            o.stop(ctx.currentTime + 0.2);
        } catch (e) {}
    }

    function renderOrderCard(o) {
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

        let actions = '';
        if (o.status === 'pending') {
            actions = `
                <button class="btn btn-sm btn-info pill" onclick="setStatus('${o.order_no}','prepairing')">Préparer</button>
                <button class="btn btn-sm btn-outline-danger pill" onclick="setStatus('${o.order_no}','cancelled')">Annuler</button>
            `;
        } else if (o.status === 'prepairing') {
            actions = `
                <button class="btn btn-sm btn-success pill" onclick="setStatus('${o.order_no}','assign')">Prêt / Assigner</button>
                <button class="btn btn-sm btn-outline-danger pill" onclick="setStatus('${o.order_no}','cancelled')">Annuler</button>
            `;
        } else if (o.status === 'assign') {
            actions = `
                <button class="btn btn-sm btn-success pill" onclick="setStatus('${o.order_no}','completed')">Terminer</button>
            `;
        }

        return `
            <div class="order-card">
                <div class="order-card-head">
                    <div>
                        <div class="order-no">#${o.order_no}</div>
                        <div class="order-meta">${customerLine}</div>
                        <div class="order-meta">${created}</div>
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
        url.searchParams.set('status[]', 'prepairing');
        url.searchParams.set('status[]', 'assign');
        if (lastUpdatedAfter) url.searchParams.set('updated_after', lastUpdatedAfter);

        const res = await fetch(url.toString(), { headers: { 'Accept': 'application/json' }});
        const json = await res.json();
        if (!res.ok) throw new Error(json.message || 'Erreur');
        lastUpdatedAfter = json.server_time;
        return json.data || [];
    }

    async function setStatus(orderNo, status) {
        if (status === 'cancelled' && !confirm('Annuler cette commande ?')) return;
        const url = statusUrlBase + orderNo + '/status';
        const res = await fetch(url, {
            method: 'PATCH',
            headers: { 'X-CSRF-TOKEN': csrf, 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify({ status })
        });
        const json = await res.json();
        if (!res.ok) return alert(json.message || 'Erreur');
        await refresh(true);
    }

    async function refresh(forceFull) {
        try {
            if (forceFull) lastUpdatedAfter = null;
            const data = await fetchOrders();

            const pending = data.filter(o => o.status === 'pending');
            const preparing = data.filter(o => o.status === 'prepairing');
            const assign = data.filter(o => o.status === 'assign');

            document.getElementById('countPending').innerText = pending.length;
            document.getElementById('countPreparing').innerText = preparing.length;
            document.getElementById('countAssign').innerText = assign.length;

            document.getElementById('colPending').innerHTML = pending.map(renderOrderCard).join('') || '<div class="muted">Aucune commande</div>';
            document.getElementById('colPreparing').innerHTML = preparing.map(renderOrderCard).join('') || '<div class="muted">Aucune commande</div>';
            document.getElementById('colAssign').innerHTML = assign.map(renderOrderCard).join('') || '<div class="muted">Aucune commande</div>';

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


