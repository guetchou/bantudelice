{{--
    Partial partagé — toutes les pages commandes restaurant
    Variables attendues : $orders (Collection), $pageTitle (string), $activeTab (string)
    activeTab : 'new' | 'delivering' | 'complete' | 'cancelled' | 'scheduled'
--}}
@once
<style>
/* ── Commandes partagé ────────────────────────────────────── */
.ord { display: flex; flex-direction: column; gap: 20px; }

/* Navigation onglets */
.ord-pill-nav { display: flex; gap: 4px; flex-wrap: wrap; }
.ord-pill {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 6px 14px; border-radius: 999px;
    border: 1px solid var(--bd-border);
    background: var(--bd-surface); color: var(--bd-text-2);
    font-size: 12px; font-weight: 600; text-decoration: none;
    transition: border-color .12s, color .12s, background .12s;
    white-space: nowrap;
}
.ord-pill:hover { border-color: var(--bd-green); color: var(--bd-green); text-decoration: none; }
.ord-pill.is-active { background: var(--bd-green); color: #fff; border-color: var(--bd-green); }
.ord-pill__count {
    display: inline-flex; align-items: center; justify-content: center;
    min-width: 18px; height: 18px; border-radius: 999px;
    font-size: 10px; font-weight: 800; padding: 0 4px;
    background: rgba(255,255,255,.28);
}
.ord-pill:not(.is-active) .ord-pill__count {
    background: var(--bd-surface-2); color: var(--bd-text-3);
}

/* Barre filtre */
.ord-toolbar {
    display: flex; align-items: center; justify-content: space-between;
    flex-wrap: wrap; gap: 10px;
}
.ord-dateinput {
    display: inline-flex; align-items: center; gap: 8px;
    padding: 7px 12px;
    border: 1px solid var(--bd-border); border-radius: var(--bd-radius);
    background: var(--bd-surface); color: var(--bd-text);
    font-family: var(--bd-font); font-size: 12px; font-weight: 500;
    cursor: pointer; transition: border-color .12s;
}
.ord-dateinput:focus-within { border-color: var(--bd-green); }
.ord-dateinput i { color: var(--bd-text-3); font-size: 11px; }
.ord-dateinput input {
    border: none; background: transparent; outline: none;
    font-family: var(--bd-font); font-size: 12px; color: var(--bd-text);
    width: 210px; min-width: 0;
}
.ord-dateinput input::placeholder { color: var(--bd-text-3); }

/* Carte */
.ord-card {
    background: var(--bd-surface); border: 1px solid var(--bd-border);
    border-radius: var(--bd-radius); overflow: hidden; transition: background .2s;
}
.ord-card__head {
    display: flex; align-items: center; justify-content: space-between;
    gap: 12px; padding: 14px 20px;
    border-bottom: 1px solid var(--bd-border-2); flex-wrap: wrap;
}
.ord-card__title { font-size: 13px; font-weight: 600; color: var(--bd-text); }
.ord-card__meta  { font-size: 11px; color: var(--bd-text-3); margin-top: 1px; }
.ord-total {
    font-family: var(--bd-font-display, 'League Spartan', sans-serif);
    font-size: 20px; font-weight: 800; color: var(--bd-green); line-height: 1;
}
.ord-total small { font-size: 12px; font-weight: 600; font-family: var(--bd-font); color: var(--bd-text-3); margin-left: 2px; }

/* Bouton */
.ord-btn {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 7px 14px; border-radius: var(--bd-radius);
    font-size: 12px; font-weight: 600; cursor: pointer;
    font-family: var(--bd-font); border: none; transition: .12s;
    text-decoration: none; white-space: nowrap;
}
.ord-btn--primary { background: var(--bd-green); color: #fff; }
.ord-btn--primary:hover { background: var(--bd-green-dark, #007836); color: #fff; }
.ord-btn--outline { background: var(--bd-surface); color: var(--bd-text-2); border: 1px solid var(--bd-border); }
.ord-btn--outline:hover { border-color: var(--bd-green); color: var(--bd-green); }

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
    background: var(--bd-surface-2); white-space: nowrap; text-align: left;
}
.ord-table tbody tr {
    border-bottom: 1px solid var(--bd-border-2); transition: background .1s;
}
.ord-table tbody tr:last-child { border-bottom: none; }
.ord-table tbody tr:hover { background: var(--bd-surface-2); }
.ord-table td { padding: 11px 16px; color: var(--bd-text-2); vertical-align: middle; }

/* Cellules */
.ord-ref {
    font-family: var(--bd-font-display, 'League Spartan', sans-serif);
    font-size: 13px; font-weight: 700; color: var(--bd-text); display: block; line-height: 1.2;
}
.ord-ref-time { font-size: 11px; color: var(--bd-text-3); margin-top: 2px; }
.ord-amount {
    font-family: var(--bd-font-display, 'League Spartan', sans-serif);
    font-size: 14px; font-weight: 800; color: var(--bd-text); white-space: nowrap;
}
.ord-amount-cur { font-size: 10px; color: var(--bd-text-3); font-family: var(--bd-font); margin-left: 2px; }
.ord-address { font-size: 11px; color: var(--bd-text-3); max-width: 160px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.ord-sched   { font-size: 12px; font-weight: 600; color: var(--bd-text-2); white-space: nowrap; }

/* Chat badge */
.ord-chat {
    display: inline-flex; align-items: center; gap: 3px; margin-top: 3px;
    padding: 2px 8px; border-radius: 999px;
    background: #fff7ed; color: #c2410c; font-size: 10px; font-weight: 800;
}
[data-theme="dark"] .ord-chat { background: rgba(194,65,12,.15); color: #fb923c; }

/* Badges statut */
.ord-badge {
    display: inline-flex; align-items: center; gap: 4px;
    padding: 3px 9px; border-radius: 999px;
    font-size: 11px; font-weight: 700; white-space: nowrap;
}
.ord-badge::before { content:''; width:6px; height:6px; border-radius:50%; background:currentColor; display:block; }
.ord-badge--new        { background: rgba(245,158,11,.12); color: #d97706; }
.ord-badge--preparing  { background: rgba(59,130,246,.12);  color: #2563eb; }
.ord-badge--delivering { background: rgba(99,102,241,.12);  color: #4f46e5; }
.ord-badge--done       { background: rgba(0,149,67,.1);     color: var(--bd-green); }
.ord-badge--cancelled  { background: rgba(239,68,68,.1);    color: #dc2626; }
.ord-badge--scheduled  { background: rgba(139,92,246,.1);   color: #7c3aed; }
[data-theme="dark"] .ord-badge--new        { background:rgba(251,191,36,.15);  color:#fbbf24; }
[data-theme="dark"] .ord-badge--preparing  { background:rgba(96,165,250,.15);  color:#60a5fa; }
[data-theme="dark"] .ord-badge--delivering { background:rgba(129,140,248,.15); color:#818cf8; }
[data-theme="dark"] .ord-badge--done       { background:rgba(0,201,87,.15);    color:#00c957; }
[data-theme="dark"] .ord-badge--cancelled  { background:rgba(248,113,113,.15); color:#f87171; }
[data-theme="dark"] .ord-badge--scheduled  { background:rgba(167,139,250,.15); color:#a78bfa; }

/* Actions ligne */
.ord-actions { display: flex; align-items: center; gap: 6px; }
.ord-action-btn {
    display: inline-flex; align-items: center; justify-content: center;
    width: 30px; height: 30px; border-radius: 7px;
    border: 1px solid var(--bd-border);
    background: var(--bd-surface); color: var(--bd-text-2);
    cursor: pointer; font-size: 11px; transition: .12s; text-decoration: none;
}
.ord-action-btn:hover { border-color: var(--bd-green); color: var(--bd-green); text-decoration: none; }

/* Vide */
.ord-empty {
    padding: 52px 20px; text-align: center;
    color: var(--bd-text-3); font-size: 13px;
}
.ord-empty i { font-size: 28px; display: block; margin-bottom: 10px; }

/* Checkbox */
.ord-check { width: 16px; height: 16px; accent-color: var(--bd-green); cursor: pointer; }

@media (max-width: 768px) {
    .ord-col-hide { display: none; }
    .ord-pill-nav { gap: 3px; }
    .ord-pill { padding: 5px 10px; font-size: 11px; }
}
</style>
@endonce

{{-- ── Navigation pills ────────────────────────────────────── --}}
<div class="ord-pill-nav">
    <a href="{{ route('restaurant.all_orders') }}"
       class="ord-pill {{ $activeTab === 'new' ? 'is-active' : '' }}">
        <i class="fas fa-inbox"></i> Nouvelles
    </a>
    <a href="{{ route('restaurant.pending_orders') }}"
       class="ord-pill {{ $activeTab === 'delivering' ? 'is-active' : '' }}">
        <i class="fas fa-truck"></i> En livraison
    </a>
    <a href="{{ route('restaurant.complete_orders') }}"
       class="ord-pill {{ $activeTab === 'complete' ? 'is-active' : '' }}">
        <i class="fas fa-check-circle"></i> Terminées
    </a>
    <a href="{{ route('restaurant.cancel_orders') }}"
       class="ord-pill {{ $activeTab === 'cancelled' ? 'is-active' : '' }}">
        <i class="fas fa-ban"></i> Annulées
    </a>
    <a href="{{ route('restaurant.schedule_orders') }}"
       class="ord-pill {{ $activeTab === 'scheduled' ? 'is-active' : '' }}">
        <i class="fas fa-calendar-clock"></i> Programmées
    </a>
</div>
