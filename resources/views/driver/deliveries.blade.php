@extends('layouts.app')
@section('title', 'Espace livreur | ' . \App\Services\ConfigService::getCompanyName())
@section('deliveries_nav', 'active')

@section('style')
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
<style>
/* ── RESET AdminLTE ─────────────────────────────────── */
.main-sidebar, .main-header { display:none!important; }
.content-wrapper {
    margin-left:0!important;
    background:#f5f6fa!important;
    font-family:'Manrope',sans-serif;
    padding:0!important;
}
/* Neutraliser les règles bd-role-admin sur content-wrapper */
.bd-role-admin .content-wrapper,
.bd-role-driver .content-wrapper {
    margin-left:0!important;
    padding:0!important;
}

/* ── LAYOUT 2 COLONNES ──────────────────────────────── */
.fd-shell {
    display:flex;
    min-height:100vh;
    width:100%;
}

/* ── SIDEBAR ────────────────────────────────────────── */
.fd-sidebar {
    width:220px;
    flex-shrink:0;
    background:#fff;
    border-right:1px solid #f0f0f0;
    display:flex;
    flex-direction:column;
    position:sticky;
    top:0;
    height:100vh;
    overflow-y:auto;
    z-index:200;
}
.fd-sidebar::-webkit-scrollbar { width:3px; }
.fd-sidebar::-webkit-scrollbar-thumb { background:#e0e0e0; }

.fd-logo {
    padding:20px 20px 16px;
    display:flex;
    align-items:center;
    gap:10px;
    border-bottom:1px solid #f5f5f5;
    text-decoration:none;
}
.fd-logo-circle {
    width:36px; height:36px;
    border-radius:10px;
    background:linear-gradient(135deg,#ff8c00,#e85d04);
    display:flex; align-items:center; justify-content:center;
    font-weight:900; font-size:.9rem; color:#fff;
    flex-shrink:0;
}
.fd-logo-name { font-size:.9rem; font-weight:900; color:#1a1a1a; line-height:1.2; }
.fd-logo-role { font-size:.65rem; color:#aaa; font-weight:600; text-transform:uppercase; letter-spacing:.06em; }

.fd-nav { padding:12px 0; flex:1; }
.fd-nav-section {
    padding:10px 20px 4px;
    font-size:.62rem;
    font-weight:800;
    text-transform:uppercase;
    letter-spacing:.1em;
    color:#bbb;
}
.fd-nav-item {
    display:flex;
    align-items:center;
    gap:10px;
    padding:10px 20px;
    margin:1px 10px;
    border-radius:10px;
    text-decoration:none;
    font-size:.82rem;
    font-weight:600;
    color:#888;
    transition:all .15s;
    cursor:pointer;
}
.fd-nav-item:hover { background:#fff7f0; color:#e85d04; }
.fd-nav-item.active {
    background:linear-gradient(135deg,#ff8c00,#e85d04);
    color:#fff;
    font-weight:700;
    box-shadow:0 4px 14px rgba(232,93,4,.3);
}
.fd-nav-icon { width:18px; text-align:center; font-size:.85rem; flex-shrink:0; }
.fd-nav-badge {
    margin-left:auto;
    background:rgba(255,255,255,.25);
    color:#fff;
    border-radius:99px;
    padding:1px 7px;
    font-size:.65rem;
    font-weight:800;
}
.fd-nav-item:not(.active) .fd-nav-badge {
    background:#fff3e8;
    color:#e85d04;
}

.fd-sidebar-user {
    border-top:1px solid #f5f5f5;
    padding:14px 20px;
    display:flex;
    align-items:center;
    gap:10px;
}
.fd-sidebar-avatar {
    width:34px; height:34px;
    border-radius:50%;
    background:linear-gradient(135deg,#ff8c00,#e85d04);
    display:flex; align-items:center; justify-content:center;
    font-weight:900; font-size:.85rem; color:#fff;
    flex-shrink:0;
}
.fd-sidebar-uname { font-size:.8rem; font-weight:700; color:#1a1a1a; }
.fd-sidebar-urole { font-size:.65rem; color:#aaa; }

/* ── COLONNE DROITE ─────────────────────────────────── */
.fd-right-col {
    flex:1 1 0;
    min-width:0;
    display:flex;
    flex-direction:column;
    min-height:100vh;
}

/* ── TOPBAR ─────────────────────────────────────────── */
.fd-topbar {
    background:#fff;
    border-bottom:1px solid #f0f0f0;
    height:56px;
    display:flex;
    align-items:center;
    justify-content:space-between;
    padding:0 24px;
    position:sticky;
    top:0;
    z-index:100;
}
.fd-topbar-title {
    font-size:1rem;
    font-weight:800;
    color:#1a1a1a;
}
.fd-topbar-right {
    display:flex;
    align-items:center;
    gap:12px;
}
.fd-status-pill {
    display:inline-flex;
    align-items:center;
    gap:6px;
    padding:6px 14px;
    border-radius:99px;
    font-size:.75rem;
    font-weight:700;
    border:2px solid;
    cursor:pointer;
    transition:all .18s;
    background:#fff;
}
.fd-status-pill.is-online  { border-color:#ef4444; color:#ef4444; }
.fd-status-pill.is-online:hover  { background:#ef4444; color:#fff; }
.fd-status-pill.is-offline { border-color:#22c55e; color:#22c55e; }
.fd-status-pill.is-offline:hover { background:#22c55e; color:#fff; }
.fd-status-pill:disabled { opacity:.5; cursor:wait; }
.fd-status-dot { width:7px; height:7px; border-radius:50%; background:currentColor; flex-shrink:0; }

/* ── MAIN BODY ──────────────────────────────────────── */
.fd-main {
    flex:1;
    min-width:0;
    padding:20px 24px 40px;
}
.fd-grid {
    display:grid;
    gap:20px;
}

/* ── SECTION HEADER ─────────────────────────────────── */
.fd-section-header {
    display:flex;
    align-items:center;
    justify-content:space-between;
    margin-bottom:4px;
}
.fd-section-title {
    font-size:.82rem;
    font-weight:800;
    text-transform:uppercase;
    letter-spacing:.08em;
    color:#aaa;
}

/* ── TOP ROW : Profil + Stats + Perf ────────────────── */
.fd-top-row {
    display:grid;
    grid-template-columns:300px 1fr;
    gap:16px;
    min-width:0;
}
.fd-top-row > * { min-width:0; }

/* ── PROFIL CARD ────────────────────────────────────── */
.fd-card {
    background:#fff;
    border-radius:16px;
    border:1px solid #f0f0f0;
    overflow:hidden;
}
.fd-profile-card {
    padding:20px;
}
.fd-profile-head {
    display:flex;
    align-items:center;
    gap:14px;
    margin-bottom:16px;
    padding-bottom:16px;
    border-bottom:1px solid #f5f5f5;
}
.fd-profile-avatar {
    width:52px; height:52px;
    border-radius:14px;
    background:linear-gradient(135deg,#ff8c00,#e85d04);
    display:flex; align-items:center; justify-content:center;
    font-size:1.3rem; font-weight:900; color:#fff;
    flex-shrink:0;
}
.fd-profile-name { font-size:1rem; font-weight:800; color:#1a1a1a; }
.fd-profile-phone { font-size:.75rem; color:#aaa; margin-top:2px; }
.fd-profile-status {
    display:inline-flex; align-items:center; gap:5px;
    margin-top:4px;
    font-size:.72rem; font-weight:700;
}
.fd-profile-status.online  { color:#22c55e; }
.fd-profile-status.offline { color:#94a3b8; }
.fd-profile-status-dot {
    width:7px; height:7px; border-radius:50%;
    background:currentColor;
}

/* Stat counters */
.fd-stat-row {
    display:grid;
    grid-template-columns:repeat(3,1fr);
    gap:8px;
    margin-bottom:14px;
}
.fd-stat-cell {
    background:#f9f9f9;
    border-radius:12px;
    padding:12px 8px;
    text-align:center;
}
.fd-stat-val {
    font-size:1.2rem;
    font-weight:900;
    color:#1a1a1a;
    line-height:1;
}
.fd-stat-lbl {
    font-size:.65rem;
    font-weight:700;
    color:#aaa;
    text-transform:uppercase;
    letter-spacing:.04em;
    margin-top:3px;
}
.fd-stat-cell.green .fd-stat-val { color:#22c55e; }
.fd-stat-cell.orange .fd-stat-val { color:#f97316; }
.fd-stat-cell.red .fd-stat-val { color:#ef4444; }

/* Earnings mini cards */
.fd-earnings-row {
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:8px;
    margin-bottom:14px;
}
.fd-earning-card {
    border-radius:12px;
    padding:12px 14px;
    display:flex;
    align-items:center;
    gap:10px;
}
.fd-earning-card.today { background:#f0fdf4; }
.fd-earning-card.week  { background:#fff7ed; }
.fd-earning-icon {
    width:36px; height:36px;
    border-radius:10px;
    display:flex; align-items:center; justify-content:center;
    font-size:14px; color:#fff; flex-shrink:0;
}
.fd-earning-card.today .fd-earning-icon { background:linear-gradient(135deg,#22c55e,#16a34a); }
.fd-earning-card.week  .fd-earning-icon { background:linear-gradient(135deg,#f97316,#ea580c); }
.fd-earning-lbl { font-size:.65rem; font-weight:700; color:#aaa; text-transform:uppercase; letter-spacing:.05em; }
.fd-earning-val { font-size:.95rem; font-weight:900; color:#1a1a1a; line-height:1.2; }

/* Trip summary strip */
.fd-trip-strip {
    display:grid;
    grid-template-columns:repeat(3,1fr);
    gap:0;
    border:1px solid #f0f0f0;
    border-radius:12px;
    overflow:hidden;
}
.fd-trip-cell {
    padding:10px 8px;
    text-align:center;
    border-right:1px solid #f0f0f0;
}
.fd-trip-cell:last-child { border-right:none; }
.fd-trip-val { font-size:.88rem; font-weight:800; color:#1a1a1a; }
.fd-trip-lbl { font-size:.62rem; color:#aaa; font-weight:600; text-transform:uppercase; letter-spacing:.04em; }

/* ── PERF CHART CARD ────────────────────────────────── */
.fd-perf-card {
    padding:20px;
    display:flex;
    flex-direction:column;
    gap:14px;
}
.fd-perf-head {
    display:flex;
    align-items:flex-start;
    justify-content:space-between;
}
.fd-perf-title { font-size:.88rem; font-weight:800; color:#1a1a1a; }
.fd-perf-meta  { font-size:.72rem; color:#aaa; margin-top:2px; }
.fd-perf-pill  {
    padding:4px 12px; border-radius:99px;
    background:#f5f5f5; font-size:.72rem; font-weight:700; color:#555;
}
.fd-perf-legend {
    display:flex;
    align-items:center;
    gap:16px;
}
.fd-perf-legend-item {
    display:flex; align-items:center; gap:5px;
    font-size:.72rem; font-weight:600; color:#555;
}
.fd-perf-legend-dot {
    width:8px; height:8px; border-radius:50%;
}
.fd-perf-chart-area {
    position:relative;
    height:140px;
    background:linear-gradient(180deg,#fff7f0 0%,#fff 100%);
    border-radius:12px;
    overflow:hidden;
    padding:10px 8px 0;
}
.fd-perf-avg {
    display:flex;
    align-items:center;
    gap:10px;
    font-size:.75rem;
    font-weight:600;
    color:#555;
}
.fd-perf-avg-bar {
    flex:1;
    height:4px;
    border-radius:99px;
    background:#f0f0f0;
    overflow:hidden;
}
.fd-perf-avg-fill {
    height:100%;
    border-radius:99px;
    background:linear-gradient(90deg,#ff8c00,#e85d04);
}

/* ── MISSIONS ROW ───────────────────────────────────── */
.fd-missions-row {
    display:grid;
    grid-template-columns:repeat(auto-fill,minmax(340px,1fr));
    gap:16px;
}

/* ── MISSION CARD ───────────────────────────────────── */
.fd-mission {
    background:#fff;
    border-radius:16px;
    border:1px solid #f0f0f0;
    overflow:hidden;
}
.fd-mission-stripe { height:3px; }
.fd-mission-stripe.assigned { background:linear-gradient(90deg,#f97316,#fb923c); }
.fd-mission-stripe.picked   { background:linear-gradient(90deg,#3b82f6,#60a5fa); }
.fd-mission-stripe.onway    { background:linear-gradient(90deg,#8b5cf6,#a78bfa); }

.fd-mission-head {
    padding:14px 16px 0;
    display:flex;
    align-items:flex-start;
    justify-content:space-between;
    gap:8px;
}
.fd-mission-ref {
    font-size:.95rem;
    font-weight:900;
    color:#1a1a1a;
    font-family:monospace;
}
.fd-mission-fee {
    text-align:right;
}
.fd-mission-fee-val {
    font-size:1rem;
    font-weight:900;
    color:#e85d04;
    line-height:1;
}
.fd-mission-fee-lbl {
    font-size:.6rem;
    font-weight:700;
    color:#aaa;
    text-transform:uppercase;
}

.fd-badge {
    display:inline-flex;
    align-items:center;
    gap:4px;
    padding:3px 8px;
    border-radius:6px;
    font-size:.65rem;
    font-weight:800;
    text-transform:uppercase;
    letter-spacing:.04em;
}
.fd-badge.assigned { background:#fff7ed; color:#c2410c; }
.fd-badge.picked   { background:#eff6ff; color:#1d4ed8; }
.fd-badge.onway    { background:#f5f3ff; color:#6d28d9; }
.fd-badge.warn     { background:#fef3c7; color:#92400e; }
.fd-badge.green    { background:#dcfce7; color:#15803d; }

.fd-mission-badges {
    padding:8px 16px 0;
    display:flex;
    flex-wrap:wrap;
    gap:4px;
}

/* Progress steps */
.fd-steps {
    padding:12px 16px;
    display:flex;
    align-items:center;
    gap:0;
    position:relative;
}
.fd-steps::before {
    content:'';
    position:absolute;
    top:17px; left:28px; right:28px;
    height:2px;
    background:#f0f0f0;
    z-index:0;
}
.fd-step {
    flex:1; text-align:center; position:relative; z-index:1;
}
.fd-step-dot {
    width:16px; height:16px;
    border-radius:50%;
    border:2px solid #e0e0e0;
    background:#fff;
    margin:0 auto 4px;
    transition:all .2s;
}
.fd-step.done  .fd-step-dot { background:#22c55e; border-color:#22c55e; }
.fd-step.active .fd-step-dot { background:#e85d04; border-color:#e85d04; box-shadow:0 0 0 3px rgba(232,93,4,.18); }
.fd-step-lbl { font-size:.6rem; font-weight:700; color:#bbb; }
.fd-step.done  .fd-step-lbl  { color:#22c55e; }
.fd-step.active .fd-step-lbl { color:#e85d04; }

/* Info collapsible */
.fd-info-toggle {
    width:100%;
    border:none; background:none; cursor:pointer;
    padding:8px 16px;
    display:flex; align-items:center; justify-content:space-between;
    font-size:.75rem; font-weight:700; color:#aaa;
    border-top:1px solid #f5f5f5;
    text-align:left;
}
.fd-info-toggle svg { transition:transform .2s; }
.fd-info-toggle.open svg { transform:rotate(180deg); }
.fd-info-body {
    max-height:0; overflow:hidden;
    transition:max-height .25s ease;
    padding:0 16px;
}
.fd-info-body.open { max-height:300px; padding-bottom:12px; }
.fd-info-row {
    display:flex; align-items:flex-start; gap:8px;
    margin-bottom:8px;
}
.fd-info-icon { font-size:.85rem; flex-shrink:0; margin-top:1px; }
.fd-info-lbl  { font-size:.65rem; font-weight:700; color:#aaa; text-transform:uppercase; letter-spacing:.04em; }
.fd-info-val  { font-size:.8rem; font-weight:700; color:#1a1a1a; }
.fd-info-sub  { font-size:.72rem; color:#aaa; }
.fd-call-btn  {
    display:inline-flex; align-items:center; gap:4px;
    margin-top:4px;
    background:#f0fdf4; color:#15803d;
    border:1px solid #bbf7d0;
    border-radius:7px; padding:3px 8px;
    font-size:.7rem; font-weight:700; text-decoration:none;
}

/* Action zone */
.fd-action-zone {
    padding:12px 16px 14px;
    border-top:1px solid #f5f5f5;
}
.fd-main-btn {
    display:block; width:100%;
    border:none; border-radius:12px;
    padding:13px;
    font-size:.88rem; font-weight:800;
    cursor:pointer; text-align:center;
    transition:all .18s;
    letter-spacing:.01em;
    margin-bottom:8px;
}
.fd-main-btn.green { background:linear-gradient(135deg,#22c55e,#16a34a); color:#fff; box-shadow:0 4px 14px rgba(34,197,94,.3); }
.fd-main-btn.blue  { background:linear-gradient(135deg,#3b82f6,#2563eb); color:#fff; box-shadow:0 4px 14px rgba(59,130,246,.3); }
.fd-main-btn.dark  { background:linear-gradient(135deg,#111827,#1e293b); color:#fff; box-shadow:0 4px 14px rgba(17,24,39,.3); }
.fd-main-btn:disabled { opacity:.5; cursor:wait; }
.fd-incident-btn {
    display:block; width:100%;
    border:1px solid #fee2e2; background:#fef2f2;
    border-radius:10px; padding:9px;
    font-size:.78rem; font-weight:700; color:#b91c1c;
    cursor:pointer; text-align:center;
    transition:all .15s;
}
.fd-incident-btn:hover { background:#fee2e2; }

/* Pickup panel */
.fd-pickup-panel { display:none; margin-top:8px; }
.fd-pickup-panel.open { display:block; }
.fd-field { margin-bottom:8px; }
.fd-field label { display:block; font-size:.72rem; font-weight:700; color:#555; margin-bottom:3px; }
.fd-field input, .fd-field textarea, .fd-field select {
    width:100%; border:1px solid #e5e7eb; border-radius:9px;
    padding:8px 10px; font-size:.8rem; background:#fff;
    font-family:'Manrope',sans-serif;
}
.fd-field textarea { min-height:72px; resize:vertical; }
.fd-hint { font-size:.7rem; color:#aaa; margin-bottom:8px; }
.fd-deliver-submit {
    width:100%; border:none; border-radius:10px;
    background:#e85d04; color:#fff; padding:11px;
    font-size:.85rem; font-weight:800; cursor:pointer;
}

/* Deliver panel */
.fd-deliver-panel { display:none; margin-top:8px; }
.fd-deliver-panel.open { display:block; }

/* Bottom sheet incident */
.fd-bs-overlay {
    display:none; position:fixed; inset:0;
    background:rgba(0,0,0,.5); z-index:9000;
    backdrop-filter:blur(2px);
}
.fd-bs-overlay.open { display:block; }
.fd-bs-panel {
    position:fixed; bottom:0; left:0; right:0;
    background:#fff; border-radius:20px 20px 0 0;
    padding:0 20px 24px;
    z-index:9001;
    transform:translateY(100%);
    transition:transform .3s cubic-bezier(.32,.72,0,1);
    max-height:85vh; overflow-y:auto;
}
.fd-bs-panel.open { transform:translateY(0); }
.fd-bs-handle {
    width:36px; height:4px; border-radius:99px;
    background:#e0e0e0; margin:12px auto 16px;
    cursor:pointer;
}
.fd-bs-title {
    display:flex; align-items:center; justify-content:space-between;
    margin-bottom:16px;
}
.fd-bs-title span { font-size:.95rem; font-weight:800; color:#1a1a1a; }
.fd-bs-close {
    width:28px; height:28px; border-radius:50%;
    background:#f5f5f5; border:none; cursor:pointer;
    font-size:1rem; font-weight:700; color:#555;
    display:flex; align-items:center; justify-content:center;
}
.fd-bs-field { margin-bottom:12px; }
.fd-bs-field label { display:block; font-size:.75rem; font-weight:700; color:#555; margin-bottom:4px; }
.fd-bs-field select, .fd-bs-field textarea {
    width:100%; border:1px solid #e5e7eb; border-radius:10px;
    padding:10px 12px; font-size:.82rem; background:#fff;
    font-family:'Manrope',sans-serif;
}
.fd-bs-field textarea { min-height:80px; resize:vertical; }
.fd-bs-submit {
    width:100%; border:none; border-radius:12px;
    background:#e85d04; color:#fff; padding:13px;
    font-size:.88rem; font-weight:800; cursor:pointer;
    margin-top:4px;
}
.fd-bs-zone-alert {
    display:none;
    background:#fef2f2; border:1px solid #fca5a5;
    border-radius:10px; padding:10px 12px;
    margin-bottom:10px; font-size:.78rem; color:#991b1b; font-weight:600;
}

/* Empty state */
.fd-empty {
    text-align:center; padding:40px 20px;
    background:#fff; border-radius:16px;
    border:1px dashed #e0e0e0;
}
.fd-empty-icon { font-size:2.5rem; margin-bottom:12px; }
@keyframes pulse {
    0%,100% { opacity:1; transform:scale(1); }
    50% { opacity:.4; transform:scale(1.3); }
}

/* Proofs */
.fd-proofs { padding:0 16px 10px; display:flex; flex-wrap:wrap; gap:6px; }
.fd-proof-link {
    display:inline-flex; align-items:center; gap:4px;
    background:#f8fafc; border:1px solid #e2e8f0;
    border-radius:8px; padding:4px 10px;
    font-size:.72rem; font-weight:700; color:#334155;
    text-decoration:none;
}

/* Chat badge */
.fd-chat-badge {
    display:inline-flex; align-items:center; gap:4px;
    border-radius:99px; padding:4px 10px;
    background:linear-gradient(135deg,#ff5a1f,#f59e0b);
    color:#fff; font-size:.7rem; font-weight:800;
}

/* Responsive */
@media (max-width:900px) {
    .fd-sidebar { display:none; }
    .fd-top-row { grid-template-columns:1fr; }
    .fd-missions-row { grid-template-columns:1fr; }
}
</style>
@endsection

@section('content')
@php
    $stepOrder   = ['ASSIGNED','PICKED_UP','ON_THE_WAY'];
    $statusLabels = ['ASSIGNED'=>'À récupérer','PICKED_UP'=>'Récupérée','ON_THE_WAY'=>'En route','DELIVERED'=>'Livrée'];
    $businessLabels = ['driver_assigned'=>'Livreur assigné','picked_up'=>'Récupérée','out_for_delivery'=>'En livraison','delivery_attempt_failed'=>'Tentative échouée','incident_open'=>'Incident ouvert','delivered'=>'Livrée'];

    $driverIsOnline = ($driver->status ?? 'offline') === 'online';
    $driverInitials = strtoupper(substr($driver->name ?? 'L', 0, 2));

    // Compteurs stats (today from DB)
    $todayCompleted  = \App\Delivery::where('driver_id', $driver->id)->where('status','DELIVERED')->whereDate('delivered_at', today())->count();
    $todayAll        = \App\Delivery::where('driver_id', $driver->id)->whereDate('created_at', today())->count();
    $todayCancelled  = \App\Delivery::where('driver_id', $driver->id)->where('status','CANCELLED')->whereDate('created_at', today())->count();
    $activeCount     = $deliveries->count();

    // Finance
    $finNet       = 0; $finAvailable = 0; $finGross = 0;
    if (!empty($financialDashboard['rows'])) {
        foreach ($financialDashboard['rows'] as $row) {
            foreach ((array)$row as $card) {
                if (!is_array($card)) continue;
                $lbl = strtolower($card['label'] ?? '');
                if (str_contains($lbl,'brut'))       $finGross     = $card['amount'] ?? 0;
                if (str_contains($lbl,'net partenaire')) $finNet   = $card['amount'] ?? 0;
                if (str_contains($lbl,'disponible')) $finAvailable = $card['amount'] ?? 0;
            }
        }
    }

    // Performance chart (7 derniers jours)
    $perfDays = [];
    for ($i = 6; $i >= 0; $i--) {
        $d = now()->subDays($i);
        $cnt = \App\Delivery::where('driver_id', $driver->id)->where('status','DELIVERED')->whereDate('delivered_at', $d)->count();
        $perfDays[] = ['label' => $d->format('D'), 'count' => $cnt];
    }
    $perfMax = max(max(array_column($perfDays, 'count')), 1);

    // Trip distance & time (approx)
    $totalTrips = \App\Delivery::where('driver_id', $driver->id)->where('status','DELIVERED')->count();
@endphp

<div class="fd-shell">

    {{-- ── SIDEBAR ─────────────────────────────────────── --}}
    <aside class="fd-sidebar">
        <a href="{{ route('driver.deliveries') }}" class="fd-logo">
            <div class="fd-logo-circle">B</div>
            <div>
                <div class="fd-logo-name">BantuDelice</div>
                <div class="fd-logo-role">Espace livreur</div>
            </div>
        </a>

        <nav class="fd-nav">
            <div class="fd-nav-section">Mon espace</div>
            <a href="{{ route('driver.deliveries') }}" class="fd-nav-item active">
                <span class="fd-nav-icon"><i class="fas fa-motorcycle"></i></span>
                Mes livraisons
                @if($activeCount > 0)
                    <span class="fd-nav-badge">{{ $activeCount }}</span>
                @endif
            </a>
            @if(app('router')->has('driver.transport.index'))
            <a href="{{ route('driver.transport.index') }}" class="fd-nav-item">
                <span class="fd-nav-icon"><i class="fas fa-car"></i></span>
                Courses transport
            </a>
            @endif
            <div class="fd-nav-section">Compte</div>
            <a href="{{ route('logout') }}"
               onclick="event.preventDefault(); document.getElementById('fd-logout-form').submit();"
               class="fd-nav-item">
                <span class="fd-nav-icon"><i class="fas fa-sign-out-alt"></i></span>
                Déconnexion
            </a>
        </nav>

        <div class="fd-sidebar-user">
            <div class="fd-sidebar-avatar">{{ $driverInitials }}</div>
            <div>
                <div class="fd-sidebar-uname">{{ $driver->name ?? 'Livreur' }}</div>
                <div class="fd-sidebar-urole">{{ $driver->phone ?? '' }}</div>
            </div>
        </div>
    </aside>
    <form id="fd-logout-form" action="{{ route('logout') }}" method="POST" style="display:none;">@csrf</form>

    {{-- ── COLONNE DROITE ──────────────────────────────── --}}
    <div class="fd-right-col">

    {{-- ── TOPBAR ──────────────────────────────────────── --}}
    <header class="fd-topbar">
        <div class="fd-topbar-title">Tableau de bord livreur</div>
        <div class="fd-topbar-right">
            <span id="driverStatusLabel" style="font-size:.75rem;font-weight:600;color:{{ $driverIsOnline ? '#22c55e' : '#94a3b8' }};">
                {{ $driverIsOnline ? 'En ligne' : 'Hors ligne' }}
            </span>
            <button type="button"
                    id="driverToggleBtn"
                    class="fd-status-pill {{ $driverIsOnline ? 'is-online' : 'is-offline' }}"
                    data-driver-id="{{ $driver->id }}"
                    data-is-online="{{ $driverIsOnline ? '1' : '0' }}"
                    data-url-online="{{ url('api/set_driver_online/' . $driver->id) }}"
                    data-url-offline="{{ url('api/set_driver_offline/' . $driver->id) }}"
                    data-csrf="{{ csrf_token() }}">
                <span class="fd-status-dot" id="driverStatusDot"></span>
                <span id="driverToggleBtnLabel">{{ $driverIsOnline ? 'Passer hors ligne' : 'Passer en ligne' }}</span>
            </button>
        </div>
    </header>

    {{-- ── MAIN ────────────────────────────────────────── --}}
    <main class="fd-main">
    <div class="fd-grid">

        {{-- TOP ROW : Profil + Chart Perf --}}
        <div class="fd-top-row">

            {{-- Profil Card --}}
            <div class="fd-card fd-profile-card">
                <div class="fd-profile-head">
                    <div class="fd-profile-avatar">{{ $driverInitials }}</div>
                    <div>
                        <div class="fd-profile-name">{{ $driver->name ?? 'Livreur' }}</div>
                        <div class="fd-profile-phone">{{ $driver->phone ?? 'Téléphone non renseigné' }}</div>
                        <div class="fd-profile-status {{ $driverIsOnline ? 'online' : 'offline' }}">
                            <span class="fd-profile-status-dot"></span>
                            {{ $driverIsOnline ? 'En ligne' : 'Hors ligne' }}
                        </div>
                    </div>
                </div>

                {{-- Stat counters --}}
                <div class="fd-stat-row">
                    <div class="fd-stat-cell green">
                        <div class="fd-stat-val">{{ $todayCompleted }}</div>
                        <div class="fd-stat-lbl">Livrées</div>
                    </div>
                    <div class="fd-stat-cell orange">
                        <div class="fd-stat-val">{{ $activeCount }}</div>
                        <div class="fd-stat-lbl">Actives</div>
                    </div>
                    <div class="fd-stat-cell red">
                        <div class="fd-stat-val">{{ $todayCancelled }}</div>
                        <div class="fd-stat-lbl">Annulées</div>
                    </div>
                </div>

                {{-- Earnings --}}
                <div class="fd-earnings-row">
                    <div class="fd-earning-card today">
                        <div class="fd-earning-icon"><i class="fas fa-coins"></i></div>
                        <div>
                            <div class="fd-earning-lbl">Disponible</div>
                            <div class="fd-earning-val">{{ number_format(round($finAvailable),0,',',' ') }} FCFA</div>
                        </div>
                    </div>
                    <div class="fd-earning-card week">
                        <div class="fd-earning-icon"><i class="fas fa-chart-line"></i></div>
                        <div>
                            <div class="fd-earning-lbl">Net total</div>
                            <div class="fd-earning-val">{{ number_format(round($finNet),0,',',' ') }} FCFA</div>
                        </div>
                    </div>
                </div>

                {{-- Trip summary --}}
                <div class="fd-trip-strip">
                    <div class="fd-trip-cell">
                        <div class="fd-trip-val">{{ $totalTrips }}</div>
                        <div class="fd-trip-lbl">Courses total</div>
                    </div>
                    <div class="fd-trip-cell">
                        <div class="fd-trip-val">{{ $todayAll }}</div>
                        <div class="fd-trip-lbl">Aujourd'hui</div>
                    </div>
                    <div class="fd-trip-cell">
                        <div class="fd-trip-val">{{ number_format(round($finGross),0,',',' ') }}</div>
                        <div class="fd-trip-lbl">CA Brut FCFA</div>
                    </div>
                </div>
            </div>

            {{-- Performance Chart --}}
            <div class="fd-card fd-perf-card">
                <div class="fd-perf-head">
                    <div>
                        <div class="fd-perf-title">Performance — 7 derniers jours</div>
                        <div class="fd-perf-meta">Livraisons complétées par jour</div>
                    </div>
                    <span class="fd-perf-pill">Cette semaine</span>
                </div>

                <div class="fd-perf-legend">
                    <div class="fd-perf-legend-item">
                        <span class="fd-perf-legend-dot" style="background:#e85d04;"></span> Livraisons
                    </div>
                    <div style="margin-left:auto;font-size:.75rem;font-weight:600;color:#aaa;">
                        Moy. : <strong style="color:#1a1a1a;">{{ $perfMax > 0 ? number_format(collect($perfDays)->avg('count'),1) : '0' }}/j</strong>
                    </div>
                </div>

                {{-- SVG Line Chart --}}
                @php
                    $svgPts = collect($perfDays)->map(function($d, $i) use ($perfMax) {
                        $x = round($i / 6 * 100, 2);
                        $y = round(100 - ($d['count'] / $perfMax * 85), 2);
                        return $x.','.$y;
                    })->implode(' ');
                @endphp
                <div class="fd-perf-chart-area">
                    <svg viewBox="0 0 100 100" preserveAspectRatio="none" style="width:100%;height:110px;" aria-hidden="true">
                        <defs>
                            <linearGradient id="perfGrad" x1="0%" y1="0%" x2="0%" y2="100%">
                                <stop offset="0%" stop-color="rgba(232,93,4,.18)"/>
                                <stop offset="100%" stop-color="rgba(232,93,4,.02)"/>
                            </linearGradient>
                        </defs>
                        <polyline fill="url(#perfGrad)" stroke="none"
                            points="0,100 {{ $svgPts }} 100,100"/>
                        <polyline fill="none" stroke="#e85d04" stroke-width="2.5"
                            stroke-linecap="round" stroke-linejoin="round"
                            points="{{ $svgPts }}"/>
                        @foreach($perfDays as $i => $pd)
                            @php $px = round($i/6*100,1); $py = round(100-($pd['count']/$perfMax*85),1); @endphp
                            <circle cx="{{ $px }}" cy="{{ $py }}" r="3" fill="#fff" stroke="#e85d04" stroke-width="2"/>
                        @endforeach
                    </svg>
                </div>

                {{-- Axis labels --}}
                <div style="display:flex;justify-content:space-between;margin-top:-4px;">
                    @foreach($perfDays as $pd)
                        <span style="font-size:.62rem;color:#bbb;text-align:center;flex:1;">{{ $pd['label'] }}</span>
                    @endforeach
                </div>

                {{-- Avg bar --}}
                @php $avgPct = $perfMax > 0 ? min(100, round(collect($perfDays)->avg('count') / $perfMax * 100)) : 0; @endphp
                <div class="fd-perf-avg">
                    <span>Perf. moy.</span>
                    <div class="fd-perf-avg-bar">
                        <div class="fd-perf-avg-fill" style="width:{{ $avgPct }}%;"></div>
                    </div>
                    <strong>{{ $avgPct }}%</strong>
                </div>
            </div>
        </div>

        {{-- MISSIONS ACTIVES --}}
        @if(session()->has('alert'))
            <div class="alert alert-{{ session('alert.type','info') }} alert-dismissible fade show">
                {{ session('alert.message') }}
                <button type="button" class="close" data-dismiss="alert">&times;</button>
            </div>
        @endif

        <div class="fd-section-header">
            <div class="fd-section-title">Missions en cours ({{ $activeCount }})</div>
        </div>

        <div class="fd-missions-row">
        @forelse($deliveries as $delivery)
        @php
            $businessStatus  = optional($delivery->order)->resolveEffectiveBusinessStatus();
            $currentStepIdx  = array_search($delivery->status, $stepOrder, true);
            $currentStepIdx  = $currentStepIdx === false ? 0 : $currentStepIdx;
            $requiresCash    = ($delivery->order->payment_method ?? null) === 'cash' && empty($delivery->cash_collected_at);
            $infoOpen        = $delivery->status === 'ASSIGNED';
            $stripeClass     = ['ASSIGNED'=>'assigned','PICKED_UP'=>'picked','ON_THE_WAY'=>'onway'][$delivery->status] ?? 'assigned';
            $badgeClass      = ['ASSIGNED'=>'assigned','PICKED_UP'=>'picked','ON_THE_WAY'=>'onway'][$delivery->status] ?? 'assigned';
            $restPhone       = $delivery->restaurant->phone ?? null;
            $clientPhone     = $delivery->order->user->phone ?? null;
            $incidentPhase   = ['ASSIGNED'=>'pre','PICKED_UP'=>'pickup','ON_THE_WAY'=>'otw'][$delivery->status] ?? 'pre';
        @endphp

        <div class="fd-mission">
            <div class="fd-mission-stripe {{ $stripeClass }}"></div>

            {{-- Head --}}
            <div class="fd-mission-head">
                <div>
                    <div class="fd-mission-ref">#{{ $delivery->order->order_no ?? $delivery->order_id }}</div>
                </div>
                <div class="fd-mission-fee">
                    <div class="fd-mission-fee-val">{{ number_format($delivery->delivery_fee??0,0,',',' ') }}</div>
                    <div class="fd-mission-fee-lbl">FCFA</div>
                </div>
            </div>

            {{-- Badges --}}
            <div class="fd-mission-badges">
                <span class="fd-badge {{ $badgeClass }}">{{ $statusLabels[$delivery->status] ?? $delivery->status }}</span>
                @if($delivery->requiresOtp()) <span class="fd-badge warn">OTP requis</span> @endif
                @if($requiresCash)            <span class="fd-badge warn">Cash à encaisser</span> @endif
                @if($delivery->incident_status === 'open') <span class="fd-badge" style="background:#fee2e2;color:#b91c1c;">Incident</span> @endif
                @if(!empty($delivery->chatBadge['has_unread']))
                    <span class="fd-chat-badge"><i class="fas fa-comments"></i> {{ $delivery->chatBadge['label'] }}</span>
                @endif
            </div>

            {{-- Progress --}}
            <div class="fd-steps">
                @foreach(['ASSIGNED'=>'Récupérer','PICKED_UP'=>'Ramassage','ON_THE_WAY'=>'En route'] as $sc => $sl)
                @php
                    $si = array_search($sc, $stepOrder, true);
                    $cls = $si < $currentStepIdx ? 'done' : ($sc === $delivery->status ? 'active' : '');
                @endphp
                <div class="fd-step {{ $cls }}">
                    <div class="fd-step-dot"></div>
                    <div class="fd-step-lbl">{{ $sl }}</div>
                </div>
                @endforeach
            </div>

            {{-- Preuves --}}
            @if($delivery->pickup_proof_path || $delivery->delivery_proof_path || $delivery->customer_confirmed_at)
            <div class="fd-proofs">
                @if($delivery->pickup_proof_path)
                    <a class="fd-proof-link" href="{{ asset($delivery->pickup_proof_path) }}" target="_blank">📷 Ramassage</a>
                @endif
                @if($delivery->delivery_proof_path)
                    <a class="fd-proof-link" href="{{ asset($delivery->delivery_proof_path) }}" target="_blank">📷 Remise</a>
                @endif
                @if($delivery->customer_confirmed_at)
                    <span class="fd-proof-link">✓ Client confirmé {{ $delivery->customer_confirmed_at->format('d/m H:i') }}</span>
                @endif
            </div>
            @endif

            {{-- Infos collapsible --}}
            <button class="fd-info-toggle {{ $infoOpen ? 'open' : '' }}" type="button" onclick="fdToggleInfos(this)">
                <span>Détails commande</span>
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg>
            </button>
            <div class="fd-info-body {{ $infoOpen ? 'open' : '' }}">
                <div class="fd-info-row">
                    <span class="fd-info-icon">🍽</span>
                    <div>
                        <div class="fd-info-lbl">Restaurant</div>
                        <div class="fd-info-val">{{ $delivery->restaurant->name ?? 'N/A' }}</div>
                        <div class="fd-info-sub">{{ $delivery->restaurant->address ?? '' }}</div>
                        @if($restPhone) <a class="fd-call-btn" href="tel:{{ $restPhone }}">📞 Appeler</a> @endif
                    </div>
                </div>
                <div class="fd-info-row">
                    <span class="fd-info-icon">👤</span>
                    <div>
                        <div class="fd-info-lbl">Client</div>
                        <div class="fd-info-val">{{ $delivery->order->user->name ?? 'N/A' }}</div>
                        @if($clientPhone)
                            <div class="fd-info-sub">{{ $clientPhone }}</div>
                            <a class="fd-call-btn" href="tel:{{ $clientPhone }}">📞 Appeler</a>
                        @endif
                    </div>
                </div>
                <div class="fd-info-row">
                    <span class="fd-info-icon">📍</span>
                    <div>
                        <div class="fd-info-lbl">Adresse de livraison</div>
                        <div class="fd-info-val">{{ $delivery->order->delivery_address ?? 'N/A' }}</div>
                    </div>
                </div>
                <div class="fd-info-row">
                    <span class="fd-info-icon">💰</span>
                    <div>
                        <div class="fd-info-lbl">Commande</div>
                        <div class="fd-info-val">{{ number_format($delivery->order->total??0,0,',',' ') }} FCFA</div>
                        <div class="fd-info-sub">
                            @if($requiresCash) Cash à encaisser à la remise
                            @elseif(($delivery->order->payment_status??null)==='paid') Paiement confirmé
                            @else Paiement en cours @endif
                            @if($delivery->assigned_at) · Assignée le {{ $delivery->assigned_at->format('d/m à H:i') }} @endif
                        </div>
                    </div>
                </div>
            </div>

            {{-- Action principale --}}
            <div class="fd-action-zone">
                @if($delivery->status === 'ASSIGNED')
                    <button type="button" class="fd-main-btn green"
                            onclick="fdOpenPanel('pickup-panel-{{ $delivery->id }}', this)">
                        ✓ Récupérer la commande
                    </button>
                    <div class="fd-pickup-panel" id="pickup-panel-{{ $delivery->id }}">
                        <form method="POST" action="{{ route('driver.deliveries.update', $delivery->id) }}" enctype="multipart/form-data" class="delivery-action-form" data-lat-prefix="pickup">
                            @csrf
                            <input type="hidden" name="status" value="PICKED_UP">
                            <input type="hidden" name="pickup_latitude">
                            <input type="hidden" name="pickup_longitude">
                            <div class="fd-field"><label>Note de ramassage</label><textarea name="pickup_notes" placeholder="Commande récupérée, vérifiée…"></textarea></div>
                            <div class="fd-field"><label>Photo de prise en charge</label><input type="file" name="pickup_proof" accept="image/*"></div>
                            <div class="fd-hint">Géolocalisation ajoutée automatiquement.</div>
                            <button type="submit" class="fd-deliver-submit">Confirmer le ramassage</button>
                        </form>
                    </div>

                @elseif($delivery->status === 'PICKED_UP')
                    <button type="button" class="fd-main-btn blue"
                            onclick="fdOpenPanel('onway-panel-{{ $delivery->id }}', this)">
                        🛵 Démarrer la livraison
                    </button>
                    <div class="fd-pickup-panel" id="onway-panel-{{ $delivery->id }}">
                        <form method="POST" action="{{ route('driver.deliveries.update', $delivery->id) }}" class="delivery-action-form" data-lat-prefix="delivery">
                            @csrf
                            <input type="hidden" name="status" value="ON_THE_WAY">
                            <input type="hidden" name="delivery_latitude">
                            <input type="hidden" name="delivery_longitude">
                            <div class="fd-field"><label>Note de livraison</label><textarea name="delivery_notes" placeholder="Départ du restaurant…"></textarea></div>
                            <div class="fd-hint">Position de départ capturée automatiquement.</div>
                            <button type="submit" class="fd-deliver-submit">Passer en route</button>
                        </form>
                    </div>

                @elseif($delivery->status === 'ON_THE_WAY')
                    <button type="button" class="fd-main-btn dark"
                            onclick="fdTogglePanel('deliver-panel-{{ $delivery->id }}', this)">
                        📦 Confirmer la remise
                    </button>
                    <div class="fd-deliver-panel" id="deliver-panel-{{ $delivery->id }}">
                        <form method="POST" action="{{ route('driver.deliveries.update', $delivery->id) }}" enctype="multipart/form-data" class="delivery-action-form" data-lat-prefix="delivery">
                            @csrf
                            <input type="hidden" name="status" value="DELIVERED">
                            <input type="hidden" name="delivery_latitude">
                            <input type="hidden" name="delivery_longitude">
                            <div class="fd-field"><label>Code OTP client</label><input type="text" name="delivery_otp" inputmode="numeric" placeholder="Code communiqué par le client"></div>
                            <div class="fd-field"><label>Preuve photo</label><input type="file" name="delivery_proof" accept="image/*"></div>
                            <div class="fd-field"><label>Note de remise</label><textarea name="delivery_notes" placeholder="Remise au client…"></textarea></div>
                            <div class="fd-field">
                                <label style="display:flex;align-items:center;gap:6px;">
                                    <input type="checkbox" name="customer_confirmed" value="1" style="width:auto;">
                                    Client confirme la réception sur place
                                </label>
                            </div>
                            <div class="fd-hint">
                                @if($requiresCash) Encaisse {{ number_format($delivery->order->total??0,0,',',' ') }} FCFA avant de confirmer. @endif
                            </div>
                            <button type="submit" class="fd-deliver-submit">Marquer comme livrée</button>
                        </form>
                    </div>
                @endif

                <button type="button" class="fd-incident-btn" style="margin-top:6px;"
                        onclick="fdOpenIncident('{{ $delivery->id }}')">
                    ⚠ Signaler un problème
                </button>
            </div>

            {{-- Chat --}}
            @if(!empty($delivery->chatData) && ($delivery->chatData['can_view'] ?? false))
                @include('frontend.partials.order_chat', ['chatData' => $delivery->chatData])
            @endif
        </div>

        {{-- Bottom sheet incident --}}
        <div class="fd-bs-overlay" id="bs-overlay-{{ $delivery->id }}" onclick="fdCloseIncident('{{ $delivery->id }}')"></div>
        <div class="fd-bs-panel" id="bs-panel-{{ $delivery->id }}">
            <div class="fd-bs-handle" onclick="fdCloseIncident('{{ $delivery->id }}')"></div>
            <div class="fd-bs-title">
                <span>⚠ Signaler un incident</span>
                <button type="button" class="fd-bs-close" onclick="fdCloseIncident('{{ $delivery->id }}')">×</button>
            </div>
            <form method="POST" action="{{ route('driver.deliveries.incident', $delivery->id) }}" id="incident-form-{{ $delivery->id }}-{{ $incidentPhase }}">
                @csrf
                <div id="zone-alert-{{ $delivery->id }}-{{ $incidentPhase }}" class="fd-bs-zone-alert">
                    🚧 Zone inaccessible — le support et le dispatch seront notifiés immédiatement.
                </div>
                <div class="fd-bs-field">
                    <label>Motif</label>
                    <select name="reason" onchange="bdShowZoneAlert(this, '{{ $delivery->id }}-{{ $incidentPhase }}')">
                        @if($delivery->status === 'ASSIGNED')
                            <option value="restaurant_issue">Restaurant indisponible</option>
                            <option value="order_missing">Commande introuvable</option>
                            <option value="address_issue">Adresse problématique</option>
                            <option value="zone_inaccessible">🚧 Zone inaccessible (route coupée / pluies)</option>
                        @elseif($delivery->status === 'PICKED_UP')
                            <option value="packaging_issue">Produit endommagé</option>
                            <option value="restaurant_issue">Restaurant bloqué</option>
                            <option value="zone_inaccessible">🚧 Zone inaccessible (route coupée / pluies)</option>
                            <option value="incident_open">Autre incident</option>
                        @elseif($delivery->status === 'ON_THE_WAY')
                            <option value="customer_absent">Client absent</option>
                            <option value="recipient_unreachable">Client injoignable</option>
                            <option value="address_issue">Adresse introuvable</option>
                            <option value="zone_inaccessible">🚧 Zone inaccessible (route coupée / pluies)</option>
                            <option value="delivery_failed">Tentative échouée</option>
                            <option value="courier_issue">Problème livreur</option>
                        @endif
                    </select>
                </div>
                <div class="fd-bs-field">
                    <label>Détails</label>
                    <textarea name="notes" placeholder="Décrivez le blocage pour le support."></textarea>
                </div>
                <button type="submit" class="fd-bs-submit">Soumettre l'incident</button>
            </form>
        </div>

        @empty
        @if(!$driverIsOnline)
        <div class="fd-empty" style="border-color:#fca5a5;background:#fff7f7;">
            <div class="fd-empty-icon">🔴</div>
            <div style="font-size:.95rem;font-weight:800;color:#1a1a1a;margin-bottom:6px;">Vous êtes hors ligne</div>
            <div style="font-size:.82rem;color:#aaa;margin-bottom:16px;">Passez en ligne pour recevoir des missions de livraison.</div>
            <button type="button"
                    id="emptyStateToggleBtn"
                    class="fd-main-btn green"
                    style="width:auto;padding:10px 24px;font-size:.85rem;"
                    onclick="document.getElementById('driverToggleBtn').click()">
                Passer en ligne
            </button>
        </div>
        @else
        <div class="fd-empty">
            <div class="fd-empty-icon">🛵</div>
            <div style="font-size:.95rem;font-weight:800;color:#1a1a1a;margin-bottom:6px;">En attente de missions</div>
            <div style="font-size:.82rem;color:#aaa;">Vous êtes en ligne. Les nouvelles missions apparaîtront ici automatiquement.</div>
            <div style="margin-top:12px;display:flex;align-items:center;justify-content:center;gap:6px;">
                <span style="width:6px;height:6px;border-radius:50%;background:#22c55e;display:inline-block;animation:pulse 1.4s infinite;"></span>
                <span style="font-size:.75rem;color:#22c55e;font-weight:700;">Actif — en attente</span>
            </div>
        </div>
        @endif
        @endforelse
        </div>

    </div>
    </main>

    </div>{{-- fd-right-col --}}
</div>{{-- fd-shell --}}
@endsection

@section('scripts')
<script>
// ── T1.4 Zone inaccessible ────────────────────────────────────
function bdShowZoneAlert(select, key) {
    var el = document.getElementById('zone-alert-' + key);
    if (!el) return;
    el.style.display = select.value === 'zone_inaccessible' ? 'block' : 'none';
}

// ── Infos collapsible ─────────────────────────────────────────
function fdToggleInfos(btn) {
    btn.classList.toggle('open');
    btn.nextElementSibling.classList.toggle('open');
}

// ── Panneaux action (pickup / on-the-way) ─────────────────────
function fdOpenPanel(panelId, btn) {
    var panel = document.getElementById(panelId);
    if (!panel) return;
    var open = panel.classList.toggle('open');
    btn.style.opacity = open ? '.75' : '1';
}
function fdTogglePanel(panelId, btn) {
    var panel = document.getElementById(panelId);
    if (!panel) return;
    var open = panel.classList.toggle('open');
    btn.style.opacity = open ? '.75' : '1';
}

// ── Bottom sheet incident ──────────────────────────────────────
function fdOpenIncident(id) {
    document.getElementById('bs-overlay-' + id).classList.add('open');
    document.getElementById('bs-panel-'   + id).classList.add('open');
    document.body.style.overflow = 'hidden';
}
function fdCloseIncident(id) {
    document.getElementById('bs-overlay-' + id).classList.remove('open');
    document.getElementById('bs-panel-'   + id).classList.remove('open');
    document.body.style.overflow = '';
}
</script>

<script>
// ── Toggle online / offline ───────────────────────────────────
(function() {
    var btn = document.getElementById('driverToggleBtn');
    if (!btn) return;
    btn.addEventListener('click', function() {
        var isOnline = btn.getAttribute('data-is-online') === '1';
        var url = isOnline ? btn.getAttribute('data-url-offline') : btn.getAttribute('data-url-online');
        btn.disabled = true;
        fetch(url, {
            method: isOnline ? 'GET' : 'POST',
            headers: { 'X-CSRF-TOKEN': btn.getAttribute('data-csrf'), 'Accept': 'application/json', 'Content-Type': 'application/json' }
        })
        .then(function(r){ return r.json(); })
        .then(function() {
            var nowOnline = !isOnline;
            btn.setAttribute('data-is-online', nowOnline ? '1' : '0');
            btn.className = 'fd-status-pill ' + (nowOnline ? 'is-online' : 'is-offline');
            document.getElementById('driverToggleBtnLabel').textContent = nowOnline ? 'Passer hors ligne' : 'Passer en ligne';
            var dot = document.getElementById('driverStatusDot');
            if (dot) dot.className = 'fd-status-dot';
            var lbl = document.getElementById('driverStatusLabel');
            if (lbl) { lbl.textContent = nowOnline ? 'En ligne' : 'Hors ligne'; lbl.style.color = nowOnline ? '#22c55e' : '#94a3b8'; }
            btn.disabled = false;
        })
        .catch(function() { btn.disabled = false; });
    });
})();
</script>

<script>
// ── Polling + Offre entrante ──────────────────────────────────
(function () {
    var POLL_URL  = "{{ route('driver.deliveries.poll') }}";
    var POLL_MS   = 10000;
    var lastCount = -1;
    var _driverAudioCtx = null;
    var _driverAudioUnlocked = false;

    (function () {
        function unlock() { _driverAudioUnlocked = true; }
        document.addEventListener('click',    unlock, { once: true });
        document.addEventListener('keydown',  unlock, { once: true });
        document.addEventListener('touchstart', unlock, { once: true, passive: true });
    })();

    function playNotifSound() {
        if (!_driverAudioUnlocked) return;
        try {
            var C = window.AudioContext || window.webkitAudioContext;
            if (!C) return;
            if (!_driverAudioCtx || _driverAudioCtx.state === 'closed') _driverAudioCtx = new C();
            if (_driverAudioCtx.state === 'suspended') _driverAudioCtx.resume();
            var ctx = _driverAudioCtx;
            var osc = ctx.createOscillator(); var gain = ctx.createGain();
            osc.connect(gain); gain.connect(ctx.destination);
            osc.frequency.value = 660;
            var t = ctx.currentTime;
            gain.gain.setValueAtTime(0.001, t);
            gain.gain.exponentialRampToValueAtTime(0.22, t + 0.01);
            gain.gain.exponentialRampToValueAtTime(0.001, t + 0.4);
            osc.start(t); osc.stop(t + 0.41);
        } catch (e) {}
    }

    function updateDriverBadge(count) {
        var badge = document.getElementById('driverAssignedBadge');
        if (!badge) {
            badge = document.createElement('span');
            badge.id = 'driverAssignedBadge';
            badge.style.cssText = 'display:inline-block;background:#e85d04;color:#fff;border-radius:50%;padding:2px 7px;font-size:12px;margin-left:8px;font-weight:700;';
            var title = document.querySelector('.fd-topbar-title');
            if (title) title.appendChild(badge);
        }
        badge.textContent = count;
        badge.style.display = count > 0 ? 'inline-block' : 'none';
    }

    var _offerPanel = null; var _offerTimer = null; var _currentOfferId = null;

    function showOfferPanel(offer) {
        if (_currentOfferId === offer.delivery_id) return;
        _currentOfferId = offer.delivery_id;
        if (_offerPanel) _offerPanel.remove();
        _offerPanel = document.createElement('div');
        _offerPanel.id = 'offerPanel';
        _offerPanel.style.cssText = [
            'position:fixed','bottom:24px','left:50%','transform:translateX(-50%)',
            'z-index:99999','width:min(420px,95vw)',
            'background:#fff','border-radius:20px',
            'box-shadow:0 20px 60px rgba(0,0,0,.22)','border:2px solid #e85d04',
            'padding:20px 22px','font-family:Manrope,sans-serif',
        ].join(';');

        var expiresAt = new Date(offer.expires_at).getTime();
        _offerPanel.innerHTML = [
            '<div style="display:flex;align-items:center;gap:10px;margin-bottom:12px;">',
            '  <span style="font-size:28px;">🛵</span>',
            '  <div>',
            '    <div style="font-weight:900;font-size:1.05rem;color:#1a1a1a;">Nouvelle mission</div>',
            '    <div style="font-size:.82rem;color:#888;">' + (offer.restaurant_name||'Restaurant') + ' · Commande #' + offer.order_no + '</div>',
            '  </div>',
            '</div>',
            offer.distance_km ? '<div style="font-size:.85rem;color:#555;margin-bottom:10px;">📍 ' + parseFloat(offer.distance_km).toFixed(1) + ' km du restaurant</div>' : '',
            '<div style="background:#fff7ed;border-radius:10px;padding:10px 14px;margin-bottom:14px;display:flex;align-items:center;gap:8px;">',
            '  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#e85d04" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>',
            '  <span style="font-size:.85rem;color:#c2410c;font-weight:700;">Expire dans <span id="offerCountdown">--</span>s</span>',
            '</div>',
            '<div style="display:flex;gap:10px;">',
            '  <button id="offerAcceptBtn" style="flex:2;background:linear-gradient(135deg,#ff8c00,#e85d04);color:#fff;border:none;border-radius:12px;padding:13px;font-weight:800;font-size:.95rem;cursor:pointer;">✓ Accepter</button>',
            '  <button id="offerDeclineBtn" style="flex:1;background:#f8fafc;color:#888;border:1.5px solid #e0e0e0;border-radius:12px;padding:13px;font-weight:700;font-size:.9rem;cursor:pointer;">Refuser</button>',
            '</div>',
        ].join('');

        document.body.appendChild(_offerPanel);
        clearInterval(_offerTimer);
        _offerTimer = setInterval(function() {
            var remaining = Math.max(0, Math.round((expiresAt - Date.now()) / 1000));
            var el = document.getElementById('offerCountdown');
            if (el) el.textContent = remaining;
            if (remaining <= 0) { clearInterval(_offerTimer); hideOfferPanel(); }
        }, 500);

        setTimeout(function() {
            if (_offerPanel) { _offerPanel.style.borderColor = '#ef4444'; if (window.BdAudio) window.BdAudio.play('alert'); }
        }, (expiresAt - Date.now() - 10000));

        playNotifSound();
        if (window.BdAudio) window.BdAudio.play('new_order');

        document.getElementById('offerAcceptBtn').addEventListener('click', function() {
            this.disabled = true; this.textContent = '⏳ Acceptation…';
            fetch('{{ url('/driver/deliveries') }}/' + offer.delivery_id + '/offer/accept', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json', 'Content-Type': 'application/json' },
                credentials: 'same-origin',
            })
            .then(function(r){ return r.json(); })
            .then(function(d) {
                hideOfferPanel();
                if (d.status) { if (window.BdAudio) window.BdAudio.play('confirm'); setTimeout(function(){ window.location.reload(); }, 800); }
                else { alert(d.message || 'Mission non disponible.'); }
            })
            .catch(function(){ window.location.reload(); });
        });

        document.getElementById('offerDeclineBtn').addEventListener('click', function() {
            fetch('{{ url('/driver/deliveries') }}/' + offer.delivery_id + '/offer/decline', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json', 'Content-Type': 'application/json' },
                credentials: 'same-origin',
            }).catch(function(){});
            hideOfferPanel();
        });
    }

    function hideOfferPanel() {
        clearInterval(_offerTimer); _currentOfferId = null;
        if (_offerPanel) { _offerPanel.remove(); _offerPanel = null; }
    }

    function poll() {
        fetch(POLL_URL, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin', cache: 'no-store' })
        .then(function(r) { return r.ok ? r.json() : null; })
        .then(function(data) {
            if (!data || !data.status) return;
            var count = parseInt(data.count) || 0;
            updateDriverBadge(data.new_assigned || 0);
            if (data.pending_offer) { showOfferPanel(data.pending_offer); }
            else if (!data.pending_offer && _currentOfferId) { hideOfferPanel(); }
            if (lastCount !== -1 && count > lastCount) { playNotifSound(); setTimeout(function(){ window.location.reload(); }, 1500); }
            lastCount = count;
        })
        .catch(function () {});
    }

    poll();
    setInterval(poll, POLL_MS);

    @if(config('broadcasting.default') !== 'log')
    if (window.Echo) {
        @php $driverModel = auth()->user() ? \App\Driver::where('email', auth()->user()->email)->orWhere('phone', auth()->user()->phone ?? '')->first() : null; @endphp
        @if($driverModel)
        window.Echo.private('food.driver.{{ $driverModel->id }}.deliveries')
            .listen('.food.delivery.assigned', function (e) {
                playNotifSound();
                if (typeof toastr !== 'undefined') toastr.info('Nouvelle livraison assignée !');
                setTimeout(function () { window.location.reload(); }, 1500);
            });
        @endif
    }
    @endif
})();
</script>

<script>
// ── Géolocalisation avant submit ──────────────────────────────
function withGeoBeforeSubmit(form) {
    const prefix = form.dataset.latPrefix;
    if (!prefix || !navigator.geolocation) { form.submit(); return; }
    navigator.geolocation.getCurrentPosition(
        (position) => {
            const latField = form.querySelector(`input[name="${prefix}_latitude"]`);
            const lngField = form.querySelector(`input[name="${prefix}_longitude"]`);
            if (latField) latField.value = position.coords.latitude;
            if (lngField) lngField.value = position.coords.longitude;
            form.submit();
        },
        () => form.submit(),
        { enableHighAccuracy: true, timeout: 5000, maximumAge: 30000 }
    );
}
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.delivery-action-form').forEach((form) => {
        form.addEventListener('submit', function (event) {
            if (form.dataset.submitting === '1') return;
            event.preventDefault();
            form.dataset.submitting = '1';
            withGeoBeforeSubmit(form);
        });
    });
});
</script>

<script>
// ── GPS continu livreur ───────────────────────────────────────
(function () {
    var GPS_URL    = @json(route('driver.location.update'));
    var MIN_DIST_M = 30;
    var CSRF       = @json(csrf_token());
    var _watchId   = null; var _lastLat = null; var _lastLng = null;
    var _isOnline  = {{ $driverIsOnline ? 'true' : 'false' }};
    var _sending   = false;

    function haversineM(lat1,lng1,lat2,lng2) {
        var dLat=(lat2-lat1)*Math.PI/180, dLng=(lng2-lng1)*Math.PI/180;
        var a=Math.sin(dLat/2)*Math.sin(dLat/2)+Math.cos(lat1*Math.PI/180)*Math.cos(lat2*Math.PI/180)*Math.sin(dLng/2)*Math.sin(dLng/2);
        return 2*6371000*Math.atan2(Math.sqrt(a),Math.sqrt(1-a));
    }

    function sendPosition(pos) {
        var lat=pos.coords.latitude, lng=pos.coords.longitude;
        if (_lastLat!==null && haversineM(_lastLat,_lastLng,lat,lng)<MIN_DIST_M) return;
        if (_sending) return;
        _sending = true;
        fetch(GPS_URL, {
            method:'POST',
            headers:{'X-CSRF-TOKEN':CSRF,'Content-Type':'application/json','Accept':'application/json'},
            credentials:'same-origin',
            body:JSON.stringify({latitude:lat,longitude:lng,accuracy:pos.coords.accuracy||null,heading:pos.coords.heading||null,speed:pos.coords.speed||null}),
        })
        .then(function(r){return r.ok?r.json():null;})
        .then(function(d){if(d&&d.status){_lastLat=lat;_lastLng=lng;}})
        .catch(function(){})
        .finally(function(){_sending=false;});
    }

    function startWatching() {
        if (_watchId!==null||!navigator.geolocation) return;
        _watchId = navigator.geolocation.watchPosition(sendPosition, function(err){if(err.code!==err.TIMEOUT)stopWatching();},{enableHighAccuracy:true,maximumAge:10000,timeout:20000});
    }
    function stopWatching() {
        if (_watchId!==null){navigator.geolocation.clearWatch(_watchId);_watchId=null;}
    }

    if (_isOnline) startWatching();

    var toggleBtn = document.getElementById('driverToggleBtn');
    if (toggleBtn) {
        toggleBtn.addEventListener('click', function(){
            setTimeout(function(){
                var nowOnline = toggleBtn.getAttribute('data-is-online')==='1';
                if (nowOnline){startWatching();}else{stopWatching();}
            }, 300);
        });
    }

    window.addEventListener('beforeunload', stopWatching);
    document.addEventListener('visibilitychange', function(){
        if (document.hidden){stopWatching();}else if(_isOnline){startWatching();}
    });
})();
</script>
@endsection
