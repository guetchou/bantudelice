<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin | BantuDelice')</title>

    <!-- Poppins font -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer">

    @yield('head_extra')
    @stack('head')

    <style>
    /* ── RESET & BASE ─────────────────────────────────────── */
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    :root {
        --adm-sidebar-w: 240px;
        --adm-topbar-h: 56px;
        --adm-bg: #f0f4f8;
        --adm-sidebar-bg: #0a1a0f;
        --adm-accent: #009543;
        --adm-green: #009543;
        --adm-text-muted: #6b7280;
        --adm-text-dim: #86a897;
        /* palette KPI */
        --kpi-blue:   #2563eb;
        --kpi-orange: #f97316;
        --kpi-red:    #ef4444;
        --kpi-purple: #8b5cf6;
    }

    html, body { height: 100%; }

    /* ── BODY ─────────────────────────────────────────────── */
    .adm-body {
        display: flex;
        background: var(--adm-bg);
        font-family: 'Poppins', system-ui, sans-serif;
        overflow-x: hidden;
        min-height: 100vh;
        color: #111827;
    }

    /* ── SIDEBAR ──────────────────────────────────────────── */
    .adm-sidebar {
        width: var(--adm-sidebar-w);
        background: var(--adm-sidebar-bg);
        color: #fff;
        display: flex;
        flex-direction: column;
        position: fixed;
        left: 0;
        top: 0;
        height: 100vh;
        z-index: 100;
        overflow-y: auto;
        overflow-x: hidden;
    }

    /* scrollbar sidebar */
    .adm-sidebar::-webkit-scrollbar { width: 4px; }
    .adm-sidebar::-webkit-scrollbar-track { background: transparent; }
    .adm-sidebar::-webkit-scrollbar-thumb { background: rgba(0,149,67,.3); border-radius: 4px; }

    /* Logo zone */
    .adm-logo {
        padding: 20px;
        display: flex;
        align-items: center;
        gap: 10px;
        border-bottom: 1px solid rgba(0,149,67,.15);
        flex-shrink: 0;
        text-decoration: none;
    }

    .adm-logo-circle {
        width: 36px;
        height: 36px;
        border-radius: 10px;
        background: #009543;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1rem;
        font-weight: 900;
        color: #fff;
        flex-shrink: 0;
        box-shadow: 0 0 0 3px rgba(0,149,67,.25);
    }

    .adm-logo-text {
        display: flex;
        flex-direction: column;
        gap: 1px;
        min-width: 0;
    }

    .adm-logo-name {
        font-size: .82rem;
        font-weight: 800;
        color: #fff;
        letter-spacing: -.01em;
        line-height: 1;
    }

    .adm-logo-badge {
        font-size: .6rem;
        font-weight: 700;
        color: var(--adm-text-dim);
        text-transform: uppercase;
        letter-spacing: .08em;
    }

    /* dot vert status */
    .adm-logo-dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background: var(--adm-green);
        box-shadow: 0 0 0 3px rgba(0,149,67,.25);
        margin-left: auto;
        flex-shrink: 0;
    }

    /* Nav section label */
    .adm-nav-section {
        padding: 8px 14px 2px;
        font-size: .58rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .12em;
        color: var(--adm-text-muted);
        white-space: nowrap;
    }

    /* Nav items container */
    .adm-nav {
        display: flex;
        flex-direction: column;
        padding: 4px 0;
        flex: 1;
        overflow-y: auto;
        overflow-x: hidden;
    }

    /* Nav item */
    .adm-nav-item {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 7px 14px;
        border-radius: 7px;
        margin: 1px 8px;
        cursor: pointer;
        font-size: .78rem;
        font-weight: 500;
        color: var(--adm-text-dim);
        text-decoration: none;
        transition: background .15s, color .15s;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        border-left: 2px solid transparent;
        flex-shrink: 0;
    }

    .adm-nav-item:hover {
        background: rgba(0,149,67,.12);
        color: #4ade80;
        border-left-color: rgba(0,149,67,.4);
    }

    .adm-nav-item.is-active {
        background: #009543;
        color: #fff;
        font-weight: 700;
        border-left-color: #009543;
    }

    .adm-nav-item .adm-nav-icon {
        width: 16px;
        text-align: center;
        color: inherit;
        font-size: .8rem;
        flex-shrink: 0;
    }

    /* Ecosystem accordion */
    .adm-eco-item { margin: 1px 0; }
    .adm-eco-toggle {
        display: flex; align-items: center; gap: 9px;
        width: calc(100% - 20px); margin: 0 10px;
        padding: 9px 10px; border: none; border-radius: 8px;
        background: transparent;
        font: 700 .62rem 'Poppins', system-ui, sans-serif;
        text-transform: uppercase; letter-spacing: .1em;
        color: var(--adm-text-muted); cursor: pointer; text-align: left;
        transition: background .15s, color .15s;
    }
    .adm-eco-toggle:hover { background: rgba(0,149,67,.08); color: #4ade80; }
    .adm-eco-toggle.is-active { color: #fff; }
    .adm-eco-dot  { width: 7px; height: 7px; border-radius: 50%; flex-shrink: 0; }
    .adm-eco-label { flex: 1; min-width: 0; }
    .adm-eco-arrow { font-size: .58rem; transition: transform .2s; flex-shrink: 0; }
    .adm-eco-toggle.is-active .adm-eco-arrow { transform: rotate(90deg); }
    .adm-eco-panel {
        display: none;
        padding: 2px 0 6px 10px;
        border-left: 1px solid rgba(0,149,67,.18);
        margin: 0 10px 4px 22px;
    }
    .adm-eco-panel.is-open { display: block; }

    /* Bottom user zone */
    .adm-sidebar-bottom {
        margin-top: auto;
        padding: 16px 20px;
        border-top: 1px solid rgba(0,149,67,.15);
        display: flex;
        align-items: center;
        gap: 10px;
        flex-shrink: 0;
    }

    .adm-avatar {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        background: #009543;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: .75rem;
        font-weight: 800;
        color: #fff;
        flex-shrink: 0;
        border: 2px solid rgba(0,149,67,.4);
    }

    .adm-user-info {
        flex: 1;
        min-width: 0;
    }

    .adm-user-name {
        font-size: .76rem;
        font-weight: 700;
        color: #e5e7eb;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        line-height: 1;
    }

    .adm-user-role {
        font-size: .62rem;
        color: var(--adm-text-muted);
        margin-top: 2px;
        line-height: 1;
    }

    .adm-logout {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 28px;
        height: 28px;
        border-radius: 8px;
        background: rgba(255,255,255,.06);
        color: var(--adm-text-muted);
        text-decoration: none;
        transition: background .15s, color .15s;
        flex-shrink: 0;
        font-size: .82rem;
    }

    .adm-logout:hover {
        background: rgba(239,68,68,.15);
        color: #f87171;
    }

    /* ── MAIN ─────────────────────────────────────────────── */
    .adm-main {
        margin-left: var(--adm-sidebar-w);
        display: flex;
        flex-direction: column;
        min-height: 100vh;
        width: calc(100% - var(--adm-sidebar-w));
    }

    /* ── TOPBAR ───────────────────────────────────────────── */
    .adm-topbar {
        background: #fff;
        border-bottom: 1px solid #e5e7eb;
        padding: 0 24px;
        height: var(--adm-topbar-h);
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        position: sticky;
        top: 0;
        z-index: 50;
    }

    .adm-topbar-left {
        display: flex;
        align-items: center;
        gap: 12px;
        min-width: 0;
    }

    .adm-page-title {
        font-size: .9rem;
        font-weight: 800;
        color: #111827;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .adm-topbar-right {
        display: flex;
        align-items: center;
        gap: 8px;
        flex-shrink: 0;
    }

    /* Workspace tabs */
    .adm-ws-tabs {
        display: flex;
        gap: 4px;
    }

    .adm-ws-tab {
        padding: 5px 12px;
        border-radius: 8px;
        font-size: .72rem;
        font-weight: 700;
        text-decoration: none;
        color: var(--adm-text-muted);
        background: transparent;
        border: none;
        transition: all .15s;
        white-space: nowrap;
    }

    .adm-ws-tab:hover { background: rgba(0,149,67,.08); color: #009543; }

    .adm-ws-tab.active {
        background: #009543;
        color: #fff;
        border-radius: 8px;
    }

    /* Compact workspace switcher (top of sidebar) */
    .adm-eco-switcher { display:flex; gap:3px; padding:10px 12px 8px; border-bottom:1px solid rgba(0,149,67,.12); flex-wrap:wrap; }
    .adm-eco-sw { display:inline-flex; align-items:center; gap:5px; padding:4px 9px; border-radius:6px; text-decoration:none; color:rgba(255,255,255,.4); font-size:.66rem; font-weight:700; text-transform:uppercase; letter-spacing:.05em; transition:all .15s; border:1px solid transparent; }
    .adm-eco-sw:hover { color:rgba(255,255,255,.75); background:rgba(255,255,255,.06); }
    .adm-eco-sw.active { color:#fff; background:rgba(0,149,67,.22); border-color:rgba(0,149,67,.35); }
    .adm-eco-sw .adm-eco-dot { width:6px; height:6px; border-radius:50%; flex-shrink:0; }

    /* Period selector */
    .adm-period-tabs {
        display: flex;
        gap: 2px;
        background: #f1f5f9;
        border-radius: 8px;
        padding: 3px;
    }

    .adm-period-tab {
        padding: 3px 10px;
        border-radius: 6px;
        font-size: .68rem;
        font-weight: 700;
        text-decoration: none;
        color: var(--adm-text-muted);
        background: transparent;
        border: none;
        transition: all .15s;
        white-space: nowrap;
    }

    .adm-period-tab.active {
        background: #fff;
        color: #111827;
        box-shadow: 0 1px 3px rgba(0,0,0,.08);
    }

    .adm-period-tab:hover:not(.active) { color: #374151; }

    /* Topbar avatar */
    .adm-topbar-avatar {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        background: #009543;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: .72rem;
        font-weight: 800;
        color: #fff;
        cursor: pointer;
        border: 2px solid rgba(0,149,67,.3);
        flex-shrink: 0;
        transition: opacity .15s;
    }
    .adm-topbar-avatar:hover { opacity: .85; }

    /* Divider topbar */
    .adm-topbar-divider {
        width: 1px;
        height: 20px;
        background: #e5e7eb;
        flex-shrink: 0;
    }

    /* ── CONTENT ──────────────────────────────────────────── */
    .adm-content {
        padding: 24px;
        flex: 1;
    }

    /* ── MOBILE OVERLAY ──────────────────────────────────── */
    .adm-sidebar-overlay {
        display: none;
        position: fixed;
        inset: 0;
        background: rgba(0,0,0,.5);
        z-index: 99;
    }
    .adm-sidebar-overlay.is-visible { display: block; }

    /* ── SIDEBAR COLLAPSED (desktop icon-only) ────────────── */
    @media (min-width: 1025px) {
        .adm-sidebar { transition: width .22s ease; }
        .adm-main    { transition: margin-left .22s ease, width .22s ease; }

        body.sidebar-collapsed .adm-sidebar { width: 64px; }
        body.sidebar-collapsed .adm-main {
            margin-left: 64px;
            width: calc(100% - 64px);
        }
        body.sidebar-collapsed .adm-logo-text,
        body.sidebar-collapsed .adm-logo-dot,
        body.sidebar-collapsed .adm-nav-section,
        body.sidebar-collapsed .adm-eco-label,
        body.sidebar-collapsed .adm-eco-arrow,
        body.sidebar-collapsed .adm-user-info,
        body.sidebar-collapsed .adm-logout { display: none; }

        body.sidebar-collapsed .adm-eco-panel { display: none !important; }
        body.sidebar-collapsed .adm-eco-toggle {
            font-size: 0;
            justify-content: center;
            padding: 10px 8px;
            margin: 2px 6px;
            width: calc(100% - 12px);
        }
        body.sidebar-collapsed .adm-eco-dot { width: 10px; height: 10px; }

        body.sidebar-collapsed .adm-nav-item {
            font-size: 0;
            justify-content: center;
            padding: 10px 8px;
            margin: 2px 6px;
        }
        body.sidebar-collapsed .adm-nav-item .adm-nav-icon {
            font-size: .9rem;
            width: auto;
        }
        body.sidebar-collapsed .adm-logo { justify-content: center; padding: 18px 12px; }
        body.sidebar-collapsed .adm-sidebar-bottom { justify-content: center; padding: 16px 6px; }
    }

    /* ── RESPONSIVE ───────────────────────────────────────── */
    @media (max-width: 1024px) {
        .adm-sidebar {
            transform: translateX(-100%);
            transition: transform .25s ease;
            z-index: 200;
        }
        .adm-sidebar.is-open {
            transform: translateX(0);
            box-shadow: 4px 0 24px rgba(0,0,0,.4);
        }
        .adm-main {
            margin-left: 0;
            width: 100%;
        }
    }

    @media (max-width: 768px) {
        .adm-topbar { padding: 0 16px; }
        .adm-content { padding: 16px; }
        .adm-ws-tabs, .adm-period-tabs { display: none; }
    }

    /* ── OPS SYSTEM ── shared operational components ────────── */
    .ops-page,.ops-shell,.bd-ops-page,.bd-ops-shell,.bd-orders-shell { display:flex; flex-direction:column; gap:1rem; }
    .ops-context { margin-bottom:1rem; }
    .ops-panel,.ops-card { background:#fff; border-radius:14px; border:1px solid #e5e7eb; padding:1rem 1.2rem; box-shadow:0 1px 3px rgba(0,0,0,.05); }
    .ops-card__header { display:flex; align-items:center; justify-content:space-between; padding-bottom:.75rem; margin-bottom:.75rem; border-bottom:1px solid #f3f4f6; font-size:.82rem; font-weight:700; color:#111827; }
    .ops-row { display:flex; align-items:flex-start; justify-content:space-between; gap:1rem; flex-wrap:wrap; }
    .ops-title { font-size:1rem; font-weight:700; color:#111827; margin-bottom:.2rem; }
    .ops-sub { font-size:.78rem; color:#6b7280; }
    .ops-meta { display:flex; align-items:center; gap:.5rem; flex-wrap:wrap; flex-shrink:0; }
    .ops-strip { display:flex; gap:1rem; align-items:center; flex-wrap:wrap; padding:.6rem 0; margin-top:.5rem; border-top:1px solid #f3f4f6; font-size:.75rem; color:#374151; }
    .ops-group { display:flex; align-items:center; gap:.35rem; flex-wrap:wrap; }
    .ops-chip { display:inline-flex; align-items:center; padding:2px 8px; border-radius:5px; font-size:.68rem; font-weight:600; background:#f3f4f6; color:#374151; border:1px solid #e5e7eb; white-space:nowrap; }
    .ops-chip--soft { background:rgba(0,149,67,.08); color:#009543; border-color:rgba(0,149,67,.2); }
    .ops-pill { display:inline-flex; align-items:center; padding:3px 10px; border-radius:20px; font-size:.7rem; font-weight:700; white-space:nowrap; }
    .ops-pill--ok { background:#d1fae5; color:#065f46; }
    .ops-pill--warn { background:#fef3c7; color:#92400e; }
    .ops-pill--danger { background:#fee2e2; color:#991b1b; }
    .ops-kpi-grid { display:grid; gap:.75rem; grid-template-columns:repeat(auto-fit,minmax(140px,1fr)); margin-top:.75rem; }
    .ops-kpi { background:#f9fafb; border-radius:10px; padding:.75rem 1rem; border:1px solid #f3f4f6; }
    .ops-kpi__label { font-size:.68rem; font-weight:600; color:#6b7280; text-transform:uppercase; letter-spacing:.04em; margin-bottom:.2rem; }
    .ops-kpi__value { font-size:1.3rem; font-weight:800; color:#111827; line-height:1.1; margin-bottom:.15rem; }
    .ops-kpi__sub { font-size:.68rem; color:#9ca3af; }
    .ops-queue { display:flex; flex-direction:column; gap:.4rem; margin-top:.5rem; }
    .ops-queue-item { display:flex; align-items:flex-start; gap:.6rem; padding:.5rem .75rem; background:#f9fafb; border-radius:8px; border-left:3px solid #e5e7eb; font-size:.78rem; }
    .ops-queue-dot { width:8px; height:8px; border-radius:50%; flex-shrink:0; margin-top:4px; }
    .ops-queue-dot--ok { background:#10b981; }
    .ops-queue-dot--warn { background:#f59e0b; }
    .ops-queue-dot--danger { background:#ef4444; }
    .ops-alerts { display:flex; flex-direction:column; gap:.5rem; }
    .ops-alert,.bd-ops-alert { padding:.6rem .9rem; border-radius:8px; font-size:.78rem; background:#f9fafb; border-left:3px solid #e5e7eb; }
    .ops-alert__top { font-weight:600; color:#111827; margin-bottom:.1rem; }
    .bd-ops-alert--danger,.bd-ops-alert--error { border-color:#ef4444; background:#fef2f2; color:#991b1b; }
    .bd-ops-alert--warn,.bd-ops-alert--warning { border-color:#f59e0b; background:#fffbeb; color:#92400e; }
    .bd-ops-alert--success,.bd-ops-alert--ok { border-color:#10b981; background:#ecfdf5; color:#065f46; }
    .ops-grid { display:grid; gap:1rem; }
    .ops-grid--2 { grid-template-columns:repeat(2,1fr); }
    @media (max-width:768px) { .ops-grid--2 { grid-template-columns:1fr; } }
    .ops-table-card,.bd-ops-table-card { background:#fff; border-radius:14px; border:1px solid #e5e7eb; overflow:hidden; margin-bottom:1rem; }
    .ops-table-card__header,.bd-ops-table-card__header { display:flex; align-items:center; justify-content:space-between; padding:.75rem 1rem; background:#f9fafb; border-bottom:1px solid #e5e7eb; font-size:.8rem; font-weight:700; color:#111827; }
    .ops-table-card__badge,.bd-ops-table-card__badge { font-size:.65rem; font-weight:700; background:#009543; color:#fff; padding:2px 7px; border-radius:10px; }
    .ops-table-wrap { overflow-x:auto; }
    .ops-table,.bd-ops-table { width:100%; border-collapse:collapse; font-size:.78rem; }
    .ops-table th,.bd-ops-table th { padding:.5rem .75rem; text-align:left; font-size:.65rem; font-weight:700; text-transform:uppercase; letter-spacing:.05em; color:#6b7280; background:#f9fafb; border-bottom:1px solid #e5e7eb; }
    .ops-table td,.bd-ops-table td { padding:.5rem .75rem; border-bottom:1px solid #f3f4f6; color:#374151; }
    .ops-table tr:last-child td,.bd-ops-table tr:last-child td { border-bottom:none; }
    .ops-filter-card,.bd-ops-filter-card { background:#fff; border-radius:10px; border:1px solid #e5e7eb; padding:.75rem 1rem; margin-bottom:1rem; }
    .ops-filter-row,.bd-ops-filter-row { display:flex; align-items:center; gap:.75rem; flex-wrap:wrap; }
    .ops-label,.bd-ops-label { font-size:.72rem; font-weight:600; color:#374151; white-space:nowrap; flex-shrink:0; }
    .ops-filter-input,.bd-ops-filter-input { padding:6px 10px; border:1px solid #d1d5db; border-radius:7px; font-size:.78rem; color:#374151; background:#f9fafb; font-family:inherit; }
    .ops-filter-input:focus,.bd-ops-filter-input:focus { outline:none; border-color:#009543; background:#fff; }
    .ops-status,.bd-ops-status { display:inline-flex; align-items:center; padding:2px 8px; border-radius:5px; font-size:.65rem; font-weight:700; white-space:nowrap; background:#f3f4f6; color:#374151; }
    .ops-tag { display:inline-flex; padding:2px 7px; border-radius:4px; font-size:.65rem; font-weight:700; background:#f3f4f6; color:#374151; }
    .ops-tag--danger { background:#fee2e2; color:#991b1b; }
    .ops-tag--warn { background:#fef3c7; color:#92400e; }
    .ops-trend { font-size:.7rem; font-weight:600; }
    .ops-stat-grid,.ops-stats-grid,.bd-ops-stat-grid { display:grid; gap:.75rem; grid-template-columns:repeat(auto-fit,minmax(160px,1fr)); }
    .ops-stat-card,.bd-ops-stat-card { background:#fff; border-radius:12px; border:1px solid #e5e7eb; padding:1rem 1.1rem; }
    .ops-stat { font-size:1.4rem; font-weight:800; color:#111827; }
    .ops-primary-btn,.bd-ops-primary-btn { display:inline-flex; align-items:center; gap:.4rem; padding:7px 16px; background:#009543; color:#fff; border:none; border-radius:8px; font-size:.78rem; font-weight:700; cursor:pointer; font-family:inherit; text-decoration:none; transition:background .15s; }
    .ops-primary-btn:hover,.bd-ops-primary-btn:hover { background:#007a38; color:#fff; }
    .ops-action-btn,.bd-ops-action-btn { display:inline-flex; align-items:center; justify-content:center; width:30px; height:30px; border-radius:7px; background:#f3f4f6; color:#374151; border:1px solid #e5e7eb; cursor:pointer; font-size:.78rem; text-decoration:none; transition:background .15s; }
    .ops-action-btn:hover,.bd-ops-action-btn:hover { background:#e5e7eb; }
    .ops-action-btn--danger,.bd-ops-action-btn--danger { background:#fef2f2; color:#ef4444; border-color:#fecaca; }
    .ops-action-btn--green,.bd-ops-action-btn--green { background:rgba(0,149,67,.08); color:#009543; border-color:rgba(0,149,67,.2); }
    .ops-hero,.bd-ops-hero { display:none; }
    .ops-hero__eyebrow,.bd-ops-hero__eyebrow,.ops-hero__actions,.bd-ops-hero__actions { display:none; }
    .ops-restaurant-link,.bd-ops-restaurant-link { color:#009543; text-decoration:none; font-weight:600; font-size:.78rem; }
    .ops-restaurant-link:hover,.bd-ops-restaurant-link:hover { text-decoration:underline; }
    /* ── ADM PAGE BAR (Odoo-style compact header) ── */
    .adm-page-bar { display:flex; align-items:center; justify-content:space-between; gap:12px; padding:14px 20px; background:#fff; border:1px solid #e5e7eb; border-radius:10px; margin-bottom:16px; flex-wrap:wrap; }
    .adm-page-bar__left { display:flex; flex-direction:column; gap:3px; min-width:0; }
    .adm-page-bar__breadcrumb { display:flex; align-items:center; gap:5px; font-size:.72rem; color:#9ca3af; }
    .adm-page-bar__breadcrumb a { color:#6b7280; text-decoration:none; }
    .adm-page-bar__breadcrumb a:hover { color:#009543; }
    .adm-page-bar__breadcrumb .sep { color:#d1d5db; }
    .adm-page-bar__title { font-size:1rem; font-weight:800; color:#111827; margin:0; line-height:1.2; }
    .adm-page-bar__right { display:flex; align-items:center; gap:8px; flex-shrink:0; flex-wrap:wrap; }
    .adm-page-bar__badge { display:inline-flex; align-items:center; padding:3px 10px; border-radius:20px; font-size:.7rem; font-weight:700; white-space:nowrap; }
    .adm-page-bar__badge--ok { background:#d1fae5; color:#065f46; }
    .adm-page-bar__badge--warn { background:#fef3c7; color:#92400e; }
    .adm-page-bar__badge--danger { background:#fee2e2; color:#991b1b; }
    /* ── END OPS SYSTEM ─────────────────────────────────────── */
    </style>

    @yield('style')
</head>
<body class="adm-body" data-nav-active="@yield('nav_active')">

    <!-- Mobile overlay -->
    <div class="adm-sidebar-overlay" id="admOverlay" onclick="admCloseSidebar()"></div>

    <!-- ── SIDEBAR ── -->
    <aside class="adm-sidebar" id="admSidebar">

        <!-- Logo -->
        <a href="/admin?workspace=bantudelice" class="adm-logo">
            <div class="adm-logo-circle">B</div>
            <div class="adm-logo-text">
                <span class="adm-logo-name">BantuDelice</span>
                <span class="adm-logo-badge">Admin</span>
            </div>
            <div class="adm-logo-dot"></div>
        </a>

        <!-- Nav -->
        <nav class="adm-nav">
        @php
            $activeWs = request('workspace', 'bantudelice');
            $urlPath  = request()->path();
            if (str_contains($urlPath, 'transport') || str_contains($urlPath, 'vehicle')) $activeWs = 'kende';
            elseif (str_contains($urlPath, 'colis') || str_contains($urlPath, 'relay')) $activeWs = 'mema';
            $navUserWorkspaces = auth()->user()->adminWorkspaces();
        @endphp

            {{-- ── Switcher compact : visible seulement si l'admin a plusieurs espaces ── --}}
            @if(count($navUserWorkspaces) > 1)
            <div class="adm-eco-switcher">
                @if(in_array('bantudelice', $navUserWorkspaces))
                <a href="/admin?workspace=bantudelice" class="adm-eco-sw {{ $activeWs === 'bantudelice' ? 'active' : '' }}">
                    <span class="adm-eco-dot" style="background:#009543;"></span>Food
                </a>
                @endif
                @if(in_array('kende', $navUserWorkspaces))
                <a href="/admin?workspace=kende" class="adm-eco-sw {{ $activeWs === 'kende' ? 'active' : '' }}">
                    <span class="adm-eco-dot" style="background:#f97316;"></span>Kende
                </a>
                @endif
                @if(in_array('mema', $navUserWorkspaces))
                <a href="/admin?workspace=mema" class="adm-eco-sw {{ $activeWs === 'mema' ? 'active' : '' }}">
                    <span class="adm-eco-dot" style="background:#2563eb;"></span>Mema
                </a>
                @endif
            </div>
            @endif

            {{-- ── Items propres à l'espace actif ── --}}
            @if($activeWs === 'bantudelice')
                <a href="/admin?workspace=bantudelice" class="adm-nav-item" data-nav="dashboard">
                    <span class="adm-nav-icon"><i class="fas fa-home"></i></span>Dashboard
                </a>
                <a href="/admin/all_orders" class="adm-nav-item" data-nav="orders">
                    <span class="adm-nav-icon"><i class="fas fa-shopping-bag"></i></span>Commandes
                </a>
                <a href="/admin/restaurant" class="adm-nav-item" data-nav="restaurants">
                    <span class="adm-nav-icon"><i class="fas fa-utensils"></i></span>Restaurants
                </a>
                <a href="/admin/driver" class="adm-nav-item" data-nav="drivers">
                    <span class="adm-nav-icon"><i class="fas fa-motorcycle"></i></span>Livreurs
                </a>
                <a href="/admin/user" class="adm-nav-item" data-nav="users">
                    <span class="adm-nav-icon"><i class="fas fa-users"></i></span>Utilisateurs
                </a>
                <div class="adm-nav-section">Catalogue</div>
                <a href="/admin/cuisine" class="adm-nav-item" data-nav="cuisine">
                    <span class="adm-nav-icon"><i class="fas fa-tag"></i></span>Cuisines
                </a>
                <a href="/admin/all-products" class="adm-nav-item" data-nav="products">
                    <span class="adm-nav-icon"><i class="fas fa-hamburger"></i></span>Produits
                </a>
                <a href="/admin/promotions" class="adm-nav-item" data-nav="promotions">
                    <span class="adm-nav-icon"><i class="fas fa-percent"></i></span>Promotions
                </a>
                <a href="/admin/news" class="adm-nav-item" data-nav="news">
                    <span class="adm-nav-icon"><i class="fas fa-bullhorn"></i></span>Actualités
                </a>
                <a href="{{ route('admin.cms.dashboard') }}" class="adm-nav-item" data-nav="cms">
                    <span class="adm-nav-icon"><i class="fas fa-newspaper"></i></span>CMS
                </a>

            @elseif($activeWs === 'kende')
                <a href="/admin?workspace=kende" class="adm-nav-item" data-nav="kende-dashboard">
                    <span class="adm-nav-icon"><i class="fas fa-home"></i></span>Dashboard
                </a>
                <a href="/admin/transport" class="adm-nav-item" data-nav="transport">
                    <span class="adm-nav-icon"><i class="fas fa-car"></i></span>Réservations
                </a>
                <a href="/admin/vehicle" class="adm-nav-item" data-nav="vehicles">
                    <span class="adm-nav-icon"><i class="fas fa-car-side"></i></span>Véhicules
                </a>
                <a href="{{ route('admin.cms.dashboard') }}" class="adm-nav-item" data-nav="cms">
                    <span class="adm-nav-icon"><i class="fas fa-newspaper"></i></span>CMS
                </a>

            @else
                <a href="/admin?workspace=mema" class="adm-nav-item" data-nav="mema-dashboard">
                    <span class="adm-nav-icon"><i class="fas fa-home"></i></span>Dashboard
                </a>
                <a href="/admin/colis" class="adm-nav-item" data-nav="colis">
                    <span class="adm-nav-icon"><i class="fas fa-box"></i></span>Colis
                </a>
                <a href="/admin/relay-points" class="adm-nav-item" data-nav="relay-points">
                    <span class="adm-nav-icon"><i class="fas fa-map-marker-alt"></i></span>Points relais
                </a>
                <a href="{{ route('admin.cms.dashboard') }}" class="adm-nav-item" data-nav="cms">
                    <span class="adm-nav-icon"><i class="fas fa-newspaper"></i></span>CMS
                </a>
            @endif

            {{-- ── Plateforme (transversal) ── --}}
            <div class="adm-nav-section" style="margin-top:10px;">Plateforme</div>
            <a href="/admin/payments/dashboard" class="adm-nav-item" data-nav="payments">
                <span class="adm-nav-icon"><i class="fas fa-wallet"></i></span>Paiements
            </a>
            <a href="/admin/restaurant_payout" class="adm-nav-item" data-nav="payouts-restaurants">
                <span class="adm-nav-icon"><i class="fas fa-store"></i></span>Virements restau.
            </a>
            <a href="/admin/driver_payout" class="adm-nav-item" data-nav="payouts-drivers">
                <span class="adm-nav-icon"><i class="fas fa-money-bill-wave"></i></span>Virements livreurs
            </a>
            <a href="/admin/commerce-analytics" class="adm-nav-item" data-nav="commerce-analytics">
                <span class="adm-nav-icon"><i class="fas fa-chart-line"></i></span>Analytique
            </a>
            <a href="/admin/support-tickets" class="adm-nav-item" data-nav="support">
                <span class="adm-nav-icon"><i class="fas fa-headset"></i></span>Support
            </a>

            {{-- ── Configuration ── --}}
            <div class="adm-nav-section">Configuration</div>
            <a href="/admin/charge" class="adm-nav-item" data-nav="settings">
                <span class="adm-nav-icon"><i class="fas fa-cog"></i></span>Paramètres
            </a>
            <a href="/admin/modules" class="adm-nav-item" data-nav="modules">
                <span class="adm-nav-icon"><i class="fas fa-puzzle-piece"></i></span>Modules
            </a>
            <a href="/admin/metrics" class="adm-nav-item" data-nav="metrics">
                <span class="adm-nav-icon"><i class="fas fa-chart-bar"></i></span>Métriques
            </a>

            @yield('sidebar_extra')

        </nav>

        <!-- Bottom user zone -->
        <div class="adm-sidebar-bottom">
            <div class="adm-avatar">
                {{ strtoupper(substr(auth()->user()->name ?? 'A', 0, 1)) }}
            </div>
            <div class="adm-user-info">
                <div class="adm-user-name">{{ auth()->user()->name ?? 'Administrateur' }}</div>
                <div class="adm-user-role">Admin</div>
            </div>
            <a href="{{ route('logout') }}"
               onclick="event.preventDefault(); document.getElementById('adm-logout-form').submit();"
               class="adm-logout"
               title="Déconnexion">
                <i class="fas fa-sign-out-alt"></i>
            </a>
        </div>

        <form id="adm-logout-form" action="{{ route('logout') }}" method="POST" style="display:none;">
            @csrf
        </form>
    </aside>

    <!-- ── MAIN ZONE ── -->
    <div class="adm-main">

        <!-- Top bar -->
        <header class="adm-topbar">
            <div class="adm-topbar-left">
                <!-- Mobile burger -->
                <button id="admSidebarToggle"
                    style="border:none;background:rgba(0,149,67,.1);cursor:pointer;padding:6px 10px;border-radius:8px;color:#009543;flex-shrink:0;"
                    title="Menu"
                    onclick="admToggleSidebar()">
                    <i class="fas fa-bars" style="font-size:1rem;"></i>
                </button>
                <span class="adm-page-title">@yield('page_title', 'Tableau de bord')</span>
            </div>

            <div class="adm-topbar-right">
                <!-- Avatar topbar -->
                <div class="adm-topbar-avatar" title="Mon profil" onclick="bdDrawerOpen()" role="button" aria-label="Ouvrir le profil" style="cursor:pointer;">
                    {{ strtoupper(substr(auth()->user()->name ?? 'A', 0, 1)) }}
                </div>
            </div>
        </header>

        <!-- Content -->
        <main class="adm-content">
            @yield('content')
        </main>

        @include('admin.partials._profile_drawer')

    </div>

    <!-- ── ACTIVE NAV JS ── -->
    <script>
    (function() {
        var active = document.body.dataset.navActive;
        if (!active) return;
        var items = document.querySelectorAll('.adm-nav-item[data-nav]');
        items.forEach(function(el) {
            if (el.dataset.nav === active) {
                el.classList.add('is-active');
            }
        });
    })();

    // Ecosystem accordion — un seul ouvert à la fois
    function admEcoToggle(btn) {
        var panel  = btn.nextElementSibling;
        var isOpen = panel.classList.contains('is-open');
        document.querySelectorAll('.adm-eco-panel').forEach(function(p) { p.classList.remove('is-open'); });
        document.querySelectorAll('.adm-eco-toggle').forEach(function(t) { t.classList.remove('is-active'); });
        if (!isOpen) {
            panel.classList.add('is-open');
            btn.classList.add('is-active');
        }
    }

    // Sidebar : desktop → collapse icon-only | mobile → slide in/out
    function admToggleSidebar() {
        if (window.innerWidth > 1024) {
            var c = document.body.classList.toggle('sidebar-collapsed');
            try { localStorage.setItem('adm-sc', c ? '1' : '0'); } catch(e) {}
        } else {
            var sidebar = document.getElementById('admSidebar');
            var overlay = document.getElementById('admOverlay');
            var isOpen  = sidebar.classList.toggle('is-open');
            overlay.classList.toggle('is-visible', isOpen);
        }
    }
    function admCloseSidebar() {
        document.getElementById('admSidebar').classList.remove('is-open');
        document.getElementById('admOverlay').classList.remove('is-visible');
    }
    (function() {
        // Restore collapsed state on desktop
        try {
            if (window.innerWidth > 1024 && localStorage.getItem('adm-sc') === '1') {
                document.body.classList.add('sidebar-collapsed');
            }
        } catch(e) {}
        // Close mobile drawer on resize
        window.addEventListener('resize', function() {
            if (window.innerWidth > 1024) admCloseSidebar();
        });
    })();
    </script>

    @yield('scripts')
    @yield('script')
    @stack('scripts')

</body>
</html>
