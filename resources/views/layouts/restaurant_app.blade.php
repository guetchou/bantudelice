<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>@yield('title')</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <script>(function(){var t=localStorage.getItem('bd-theme');if(t)document.documentElement.setAttribute('data-theme',t);})();</script>
    <link rel="stylesheet" href="{{ asset('plugins/fontawesome-free/css/all.min.css') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Poppins:wght@400;500;600;700&family=League+Spartan:wght@600;700;800;900&display=swap" rel="stylesheet">
    @yield('style')
    <style>
        /* ═══════════════════════════════════════════════════
           BantuDelice Restaurant Dashboard — Design System
           Structure : Option B (sidebar stats + live scroll)
           Modes : clair (défaut) + sombre (data-theme="dark")
           ═══════════════════════════════════════════════════ */

        /* ── Variables mode clair ────────────────────────── */
        :root {
            --bd-green:        #009543;
            --bd-green-dark:   #007836;
            --bd-green-pale:   #f0fdf4;
            --bd-green-mid:    rgba(0,149,67,.12);
            --bd-green-glow:   rgba(0,149,67,.20);

            --bd-bg:           #f4f5f7;
            --bd-surface:      #ffffff;
            --bd-surface-2:    #f9fafb;
            --bd-border:       #e5e7eb;
            --bd-border-2:     #f3f4f6;

            --bd-text:         #111827;
            --bd-text-2:       #4b5563;
            --bd-text-3:       #9ca3af;

            --bd-shadow-sm:    0 1px 2px rgba(0,0,0,.06);
            --bd-shadow:       0 1px 3px rgba(0,0,0,.10), 0 1px 2px rgba(0,0,0,.06);
            --bd-shadow-md:    0 4px 12px rgba(0,0,0,.08);

            --bd-radius:       8px;
            --bd-radius-lg:    12px;
            --bd-sidebar-w:    280px;
            --bd-topbar-h:     60px;
            --bd-font:         'Poppins', 'Inter', sans-serif;
            --bd-font-body:    'Inter', sans-serif;
            --bd-font-display: 'League Spartan', 'Poppins', sans-serif;

            /* Sidebar : fond blanc en mode clair */
            --bd-sb-bg:        #ffffff;
            --bd-sb-border:    #e5e7eb;
            --bd-sb-item-hover:#f4f5f7;
            --bd-sb-active-bg: #f0fdf4;
            --bd-sb-active-c:  #009543;
            --bd-sb-text:      #4b5563;
            --bd-sb-label:     #9ca3af;
            --bd-sb-stat-bg:   #f4f5f7;

            /* Topbar */
            --bd-tb-bg:        #ffffff;
            --bd-tb-border:    #e5e7eb;
        }

        /* ── Variables mode sombre ───────────────────────── */
        [data-theme="dark"] {
            --bd-bg:           #0f1117;
            --bd-surface:      #1a1d27;
            --bd-surface-2:    #22263a;
            --bd-border:       rgba(255,255,255,.09);
            --bd-border-2:     rgba(255,255,255,.05);

            --bd-text:         #f0f4f8;
            --bd-text-2:       #8892a4;
            --bd-text-3:       #4a5568;

            --bd-shadow-sm:    0 1px 4px rgba(0,0,0,.30);
            --bd-shadow:       0 2px 8px rgba(0,0,0,.40);
            --bd-shadow-md:    0 4px 20px rgba(0,0,0,.50);

            --bd-sb-bg:        #0d1016;
            --bd-sb-border:    rgba(255,255,255,.07);
            --bd-sb-item-hover:rgba(255,255,255,.05);
            --bd-sb-active-bg: rgba(0,149,67,.14);
            --bd-sb-active-c:  #00c957;
            --bd-sb-text:      #8892a4;
            --bd-sb-label:     #4a5568;
            --bd-sb-stat-bg:   #22263a;

            --bd-tb-bg:        #1a1d27;
            --bd-tb-border:    rgba(255,255,255,.07);
        }

        *, *::before, *::after { box-sizing: border-box; margin: 0; }
        html, body {
            height: 100%;
            background: var(--bd-bg);
            color: var(--bd-text);
            font-family: var(--bd-font);
            font-size: 14px;
            line-height: 1.5;
            -webkit-font-smoothing: antialiased;
            transition: background .2s, color .2s;
        }
        a { text-decoration: none; color: inherit; }

        /* ── App shell ───────────────────────────────────── */
        .bd-restaurant-app { display: flex; min-height: 100vh; }

        /* ══════════════════════════════════════════════════
           SIDEBAR — 280px, stats intégrées + toggle statut
           ══════════════════════════════════════════════════ */
        .bd-restaurant-sidebar {
            width: var(--bd-sidebar-w);
            flex-shrink: 0;
            position: sticky;
            top: 0;
            height: 100vh;
            overflow-y: auto;
            background: var(--bd-sb-bg);
            border-right: 1px solid var(--bd-sb-border);
            display: flex;
            flex-direction: column;
            transition: background .2s;
        }
        .bd-restaurant-sidebar::-webkit-scrollbar { width: 4px; }
        .bd-restaurant-sidebar::-webkit-scrollbar-track { background: transparent; }
        .bd-restaurant-sidebar::-webkit-scrollbar-thumb { background: var(--bd-border); border-radius: 2px; }

        /* Brand */
        .bd-restaurant-sidebar__brand {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 16px 16px 14px;
            border-bottom: 1px solid var(--bd-sb-border);
            text-decoration: none;
            flex-shrink: 0;
        }
        .bd-restaurant-sidebar__mark {
            width: 36px; height: 36px;
            border-radius: 8px; overflow: hidden;
            background: var(--bd-green);
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0;
        }
        .bd-restaurant-sidebar__mark img { width: 100%; height: 100%; object-fit: cover; display: block; }
        .bd-restaurant-sidebar__title {
            display: block; font-size: 13px; font-weight: 600;
            color: var(--bd-text); white-space: nowrap;
            overflow: hidden; text-overflow: ellipsis; line-height: 1.3;
        }
        .bd-restaurant-sidebar__eyebrow {
            display: block; font-size: 11px; font-weight: 400;
            color: var(--bd-text-3); line-height: 1.3; margin-top: 1px;
        }
        .bd-restaurant-sidebar__copy { min-width: 0; }


        /* Nav */
        .bd-restaurant-nav { flex: 1; padding: 8px; overflow-y: auto; }
        .bd-restaurant-nav__group { margin-bottom: 4px; }
        .bd-restaurant-nav__group-label {
            display: block; padding: 8px 8px 4px;
            font-size: 10px; font-weight: 700;
            letter-spacing: .08em; text-transform: uppercase;
            color: var(--bd-sb-label);
        }
        .bd-restaurant-nav__item {
            display: flex; align-items: center; gap: 10px;
            padding: 9px 10px;
            border-radius: 7px;
            color: var(--bd-sb-text);
            font-size: 13px; font-weight: 500;
            transition: background .12s, color .12s;
            margin-bottom: 1px;
            border-left: 3px solid transparent;
        }
        .bd-restaurant-nav__item:hover {
            background: var(--bd-sb-item-hover);
            color: var(--bd-text);
        }
        .bd-restaurant-nav__item.is-active {
            background: var(--bd-sb-active-bg);
            color: var(--bd-sb-active-c);
            font-weight: 600;
            border-left-color: var(--bd-green);
        }
        .bd-restaurant-nav__item i {
            width: 16px; text-align: center;
            font-size: 13px; flex-shrink: 0;
        }
        .bd-restaurant-nav__item .nav-badge {
            margin-left: auto;
            background: rgba(245,158,11,.15);
            color: #d97706;
            font-size: 10px; font-weight: 700;
            padding: 2px 7px; border-radius: 100px;
        }
        [data-theme="dark"] .bd-restaurant-nav__item .nav-badge {
            background: rgba(251,191,36,.15);
            color: #fbbf24;
        }

        /* Sous-navigation */
        .bd-restaurant-nav__sub {
            display: none;
            flex-direction: column;
            margin: 1px 0 2px 18px;
            border-left: 1px solid var(--bd-sb-border);
            padding-left: 8px;
        }
        .bd-restaurant-nav__sub.is-open { display: flex; }
        .bd-restaurant-nav__sub-item {
            display: flex; align-items: center; gap: 8px;
            padding: 6px 8px; border-radius: 6px;
            color: var(--bd-sb-text); font-size: 12px; font-weight: 500;
            text-decoration: none; transition: background .1s, color .1s;
            margin-bottom: 1px;
        }
        .bd-restaurant-nav__sub-item:hover { background: var(--bd-sb-item-hover); color: var(--bd-text); }
        .bd-restaurant-nav__sub-item.is-active { color: var(--bd-green); font-weight: 600; }
        .bd-restaurant-nav__sub-item i { width: 13px; text-align: center; font-size: 11px; flex-shrink: 0; }
        .bd-restaurant-nav__chevron {
            margin-left: auto; font-size: 10px;
            transition: transform .18s;
        }
        .bd-restaurant-nav__item.has-sub.is-open .bd-restaurant-nav__chevron { transform: rotate(90deg); }

        /* Sidebar footer */
        .bd-restaurant-sidebar__footer {
            padding: 8px;
            border-top: 1px solid var(--bd-sb-border);
            display: flex; flex-direction: column; gap: 2px;
            flex-shrink: 0;
        }
        .bd-restaurant-sidebar__button,
        .bd-restaurant-sidebar__button button {
            display: flex; align-items: center; gap: 10px;
            width: 100%; padding: 8px 10px;
            border-radius: 6px; border: none;
            background: transparent;
            color: var(--bd-sb-text);
            font-size: 13px; font-weight: 500;
            font-family: var(--bd-font); cursor: pointer;
            transition: background .12s, color .12s;
        }
        .bd-restaurant-sidebar__button:hover,
        .bd-restaurant-sidebar__button button:hover {
            background: var(--bd-sb-item-hover);
            color: var(--bd-text);
        }

        /* ══════════════════════════════════════════════════
           MAIN
           ══════════════════════════════════════════════════ */
        .bd-restaurant-main {
            flex: 1; min-width: 0;
            display: flex; flex-direction: column;
            background: var(--bd-bg);
            transition: background .2s;
        }

        /* ── Topbar ──────────────────────────────────────── */
        .bd-restaurant-topbar {
            position: sticky; top: 0; z-index: 30;
            height: var(--bd-topbar-h);
            display: flex; align-items: center;
            justify-content: space-between; gap: 12px;
            padding: 0 24px;
            background: var(--bd-tb-bg);
            border-bottom: 1px solid var(--bd-tb-border);
            box-shadow: var(--bd-shadow-sm);
            transition: background .2s;
        }
        .bd-restaurant-topbar__meta { display: flex; align-items: center; gap: 14px; }
        .bd-restaurant-topbar__eyebrow { display: none; }
        .bd-restaurant-topbar__title {
            font-size: 16px; font-weight: 600;
            color: var(--bd-text); letter-spacing: -.01em;
        }
        .bd-restaurant-topbar__date {
            font-size: 12px; color: var(--bd-text-3);
            border-left: 1px solid var(--bd-border);
            padding-left: 14px;
        }
        .bd-restaurant-topbar__actions {
            display: flex; align-items: center; gap: 8px;
        }

        /* Chip statut en ligne */
        .bd-topbar-status {
            display: flex; align-items: center; gap: 6px;
            background: var(--bd-surface-2);
            border: 1px solid var(--bd-border);
            padding: 5px 12px; border-radius: 7px;
            font-size: 12px; font-weight: 600; color: var(--bd-text-2);
        }
        .bd-topbar-status .dot {
            width: 7px; height: 7px; border-radius: 50%;
            background: #22c55e;
            animation: bd-pulse 2s infinite;
        }
        @keyframes bd-pulse { 0%,100%{opacity:1} 50%{opacity:.4} }

        /* Bouton bascule mode sombre */
        .bd-theme-toggle {
            width: 36px; height: 36px;
            display: inline-flex; align-items: center; justify-content: center;
            border-radius: 7px;
            border: 1px solid var(--bd-border);
            background: var(--bd-surface);
            color: var(--bd-text-2);
            cursor: pointer; transition: all .15s;
            font-size: 15px;
        }
        .bd-theme-toggle:hover { border-color: var(--bd-green); color: var(--bd-green); }

        /* Cloche notifications */
        .bd-restaurant-topbar__notif {
            position: relative; width: 36px; height: 36px;
            display: inline-flex; align-items: center; justify-content: center;
            border-radius: 7px; border: 1px solid var(--bd-border);
            background: var(--bd-surface); color: var(--bd-text-2);
            transition: border-color .12s, color .12s;
        }
        .bd-restaurant-topbar__notif:hover { border-color: var(--bd-green); color: var(--bd-green); }
        .bd-restaurant-topbar__notif .badge {
            position: absolute; top: -5px; right: -5px;
            background: var(--bd-green);
            font-size: 10px; border-radius: 999px;
            padding: 1px 5px; color: #fff;
        }

        /* Bouton session / impersonation */
        .bd-restaurant-topbar__home,
        .bd-restaurant-topbar__session {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 6px 14px; border-radius: 7px;
            border: 1px solid var(--bd-border);
            background: var(--bd-surface);
            color: var(--bd-text-2);
            font-size: 13px; font-weight: 500;
            font-family: var(--bd-font); cursor: pointer;
            min-height: 36px; transition: border-color .12s, color .12s;
        }
        .bd-restaurant-topbar__home:hover,
        .bd-restaurant-topbar__session:hover { border-color: var(--bd-green); color: var(--bd-green); }

        /* Avatar utilisateur */
        .bd-restaurant-user {
            display: inline-flex; align-items: center; gap: 8px;
            padding: 4px 10px 4px 4px;
            border-radius: 7px; border: 1px solid var(--bd-border);
            background: var(--bd-surface); min-height: 36px;
            transition: border-color .12s; cursor: pointer;
        }
        .bd-restaurant-user:hover { border-color: var(--bd-green); }
        .bd-restaurant-user__avatar {
            width: 28px; height: 28px; border-radius: 50%; object-fit: cover;
        }
        .bd-restaurant-user__name {
            font-size: 12px; font-weight: 600;
            color: var(--bd-text); line-height: 1.2;
        }
        .bd-restaurant-user__copy { display: grid; gap: 0; }

        /* ── Content ─────────────────────────────────────── */
        .bd-restaurant-content { flex: 1; padding: 28px 32px; min-width: 0; }
        .bd-restaurant-content__inner {
            max-width: 1180px;
            margin: 0 auto;
        }
        .bd-restaurant-footer {
            padding: 12px 32px; font-size: 12px;
            color: var(--bd-text-3);
            border-top: 1px solid var(--bd-border);
        }

        /* ── Modal notifications (slide depuis droite) ───── */
        .modal.right .modal-dialog { position: fixed; right: 0; margin: 0; width: 360px; height: 100%; }
        .modal.right .modal-content { height: 100%; border-radius: 12px 0 0 12px; border: 0; }

        /* ── Responsive ──────────────────────────────────── */
        @media (max-width: 1024px) {
            :root { --bd-sidebar-w: 220px; }
        }
        @media (max-width: 768px) {
            .bd-restaurant-app { flex-direction: column; }
            .bd-restaurant-sidebar { width: 100%; height: auto; position: relative; }
            .bd-restaurant-content { padding: 16px; }
            .bd-restaurant-topbar { padding: 0 16px; }
            .bd-restaurant-topbar__date { display: none; }
            .bd-topbar-status { display: none; }
        }
        @media (min-width: 769px) and (max-width: 1300px) {
            .bd-restaurant-content { padding: 24px; }
        }

        /* ══════════════════════════════════════════════════
           DESIGN SYSTEM — Composants (Bootstrap 4 overrides)
           S'applique à toutes les vues restaurant
           ══════════════════════════════════════════════════ */

        .content-wrapper { background: transparent !important; }
        section.content { padding: 0 !important; }

        .content-header { padding: 0 0 16px !important; }
        .content-header h1 { font-size: 18px !important; font-weight: 700 !important; color: var(--bd-text) !important; letter-spacing: -.02em !important; }
        .content-header .breadcrumb { background: transparent !important; padding: 0 !important; font-size: 12px !important; }
        .content-header .breadcrumb-item, .content-header .breadcrumb-item a { color: var(--bd-text-3) !important; }
        .content-header .breadcrumb-item.active { color: var(--bd-text-2) !important; }
        .content-header .breadcrumb-item + .breadcrumb-item::before { color: var(--bd-border) !important; }

        /* Card */
        .card { border: 1px solid var(--bd-border) !important; border-radius: var(--bd-radius) !important; box-shadow: var(--bd-shadow) !important; background: var(--bd-surface) !important; margin-bottom: 16px !important; transition: background .2s, border-color .2s !important; }
        .card-header { border-bottom: 1px solid var(--bd-border-2) !important; background: var(--bd-surface) !important; padding: 14px 18px !important; border-radius: var(--bd-radius) var(--bd-radius) 0 0 !important; }
        .card-header:first-child { border-radius: var(--bd-radius) var(--bd-radius) 0 0 !important; }
        .card-title { font-size: 14px !important; font-weight: 600 !important; color: var(--bd-text) !important; letter-spacing: -.01em !important; font-family: var(--bd-font) !important; }
        .card-body { padding: 18px !important; }
        .card-footer { background: var(--bd-surface-2) !important; border-top: 1px solid var(--bd-border-2) !important; padding: 12px 18px !important; border-radius: 0 0 var(--bd-radius) var(--bd-radius) !important; }
        .card-primary,.card-success,.card-info,.card-warning,.card-danger,.card-default { border-top: none !important; }
        .card-outline.card-primary,.card-outline.card-success,.card-outline.card-info,.card-outline.card-warning,.card-outline.card-danger { border: 1px solid var(--bd-border) !important; }

        /* Buttons */
        .btn { font-family: var(--bd-font) !important; font-size: 13px !important; font-weight: 500 !important; border-radius: 7px !important; padding: 7px 14px !important; transition: all .12s !important; line-height: 1.4 !important; }
        .btn-sm { padding: 5px 10px !important; font-size: 12px !important; }
        .btn-lg { padding: 10px 20px !important; font-size: 14px !important; }
        .btn-primary,.btn-success { background: var(--bd-green) !important; border-color: var(--bd-green) !important; color: #fff !important; }
        .btn-primary:hover,.btn-success:hover { background: var(--bd-green-dark) !important; border-color: var(--bd-green-dark) !important; color: #fff !important; }
        .btn-outline-primary,.btn-outline-success { background: transparent !important; border: 1px solid var(--bd-green) !important; color: var(--bd-green) !important; }
        .btn-outline-primary:hover,.btn-outline-success:hover { background: var(--bd-green-pale) !important; }
        .btn-outline-info { background: transparent !important; border: 1px solid var(--bd-border) !important; color: var(--bd-text-2) !important; }
        .btn-outline-info:hover { background: var(--bd-surface-2) !important; border-color: var(--bd-green) !important; color: var(--bd-green) !important; }
        .btn-outline-warning { background: transparent !important; border: 1px solid var(--bd-border) !important; color: var(--bd-text-2) !important; }
        .btn-outline-warning:hover { background: #fffbeb !important; border-color: #f59e0b !important; color: #d97706 !important; }
        .btn-outline-danger { background: transparent !important; border: 1px solid var(--bd-border) !important; color: var(--bd-text-2) !important; }
        .btn-outline-danger:hover { background: #fef2f2 !important; border-color: #ef4444 !important; color: #dc2626 !important; }
        .btn-default { background: var(--bd-surface) !important; border: 1px solid var(--bd-border) !important; color: var(--bd-text-2) !important; }
        .btn-default:hover { background: var(--bd-surface-2) !important; }
        .btn-danger { background: #dc2626 !important; border-color: #dc2626 !important; color: #fff !important; }
        .btn-warning { background: #f59e0b !important; border-color: #f59e0b !important; color: #fff !important; }
        .btn-info { background: #3b82f6 !important; border-color: #3b82f6 !important; color: #fff !important; }

        /* Tables */
        .table { font-size: 13px !important; color: var(--bd-text-2) !important; font-family: var(--bd-font) !important; }
        .table thead th { font-size: 11px !important; font-weight: 700 !important; letter-spacing: .06em !important; text-transform: uppercase !important; color: var(--bd-text-3) !important; border-bottom: 1px solid var(--bd-border-2) !important; border-top: none !important; padding: 10px 12px !important; background: var(--bd-surface-2) !important; white-space: nowrap !important; }
        .table tbody td { padding: 12px 12px !important; border-top: 1px solid var(--bd-border-2) !important; vertical-align: middle !important; color: var(--bd-text) !important; }
        .table tbody tr:hover td { background: var(--bd-surface-2) !important; }
        .table-bordered { border: 1px solid var(--bd-border) !important; }
        .table-bordered td,.table-bordered th { border: 1px solid var(--bd-border) !important; }
        .table-striped tbody tr:nth-of-type(odd) td { background: var(--bd-surface-2) !important; }
        .table-hover tbody tr:hover td { background: var(--bd-surface-2) !important; }
        .table-responsive { border-radius: 0 0 var(--bd-radius) var(--bd-radius) !important; }

        /* Badges */
        .badge { font-size: 11px !important; font-weight: 600 !important; padding: 3px 8px !important; border-radius: 999px !important; font-family: var(--bd-font) !important; }
        .badge-success,.badge-primary { background: #dcfce7 !important; color: #007836 !important; }
        .badge-warning { background: #fef3c7 !important; color: #d97706 !important; }
        .badge-danger { background: #fee2e2 !important; color: #b91c1c !important; }
        .badge-info { background: #dbeafe !important; color: #1d4ed8 !important; }
        .badge-secondary,.badge-default { background: var(--bd-surface-2) !important; color: var(--bd-text-2) !important; }
        .badge-dark { background: var(--bd-surface-2) !important; color: var(--bd-text) !important; }
        [data-theme="dark"] .badge-success,[data-theme="dark"] .badge-primary { background: rgba(0,149,67,.15) !important; color: #00c957 !important; }
        [data-theme="dark"] .badge-warning { background: rgba(251,191,36,.15) !important; color: #fbbf24 !important; }
        [data-theme="dark"] .badge-danger { background: rgba(239,68,68,.15) !important; color: #f87171 !important; }
        [data-theme="dark"] .badge-info { background: rgba(96,165,250,.15) !important; color: #60a5fa !important; }

        /* Forms */
        .form-control { border: 1px solid var(--bd-border) !important; border-radius: 6px !important; font-size: 13px !important; font-family: var(--bd-font) !important; color: var(--bd-text) !important; background: var(--bd-surface) !important; padding: 8px 12px !important; height: auto !important; transition: border-color .12s, box-shadow .12s, background .2s !important; }
        .form-control:focus { border-color: var(--bd-green) !important; box-shadow: 0 0 0 3px var(--bd-green-glow) !important; outline: none !important; }
        .form-control::placeholder { color: var(--bd-text-3) !important; }
        label { font-size: 12px !important; font-weight: 600 !important; color: var(--bd-text-2) !important; margin-bottom: 4px !important; font-family: var(--bd-font) !important; }
        .form-group { margin-bottom: 16px !important; }
        select.form-control { cursor: pointer !important; }

        /* Nav tabs */
        .nav-tabs { border-bottom: 1px solid var(--bd-border) !important; }
        .nav-tabs .nav-link { font-size: 13px !important; font-weight: 500 !important; color: var(--bd-text-2) !important; border: none !important; border-bottom: 2px solid transparent !important; border-radius: 0 !important; padding: 8px 16px !important; font-family: var(--bd-font) !important; transition: color .12s !important; }
        .nav-tabs .nav-link:hover { color: var(--bd-text) !important; border-bottom-color: var(--bd-border) !important; }
        .nav-tabs .nav-link.active { color: var(--bd-green) !important; border-bottom-color: var(--bd-green) !important; background: transparent !important; font-weight: 600 !important; }

        /* Pagination */
        .pagination { font-size: 13px !important; font-family: var(--bd-font) !important; }
        .page-link { border: 1px solid var(--bd-border) !important; color: var(--bd-text-2) !important; background: var(--bd-surface) !important; padding: 6px 12px !important; border-radius: 6px !important; margin: 0 2px !important; }
        .page-link:hover { background: var(--bd-surface-2) !important; border-color: var(--bd-green) !important; color: var(--bd-green) !important; }
        .page-item.active .page-link { background: var(--bd-green) !important; border-color: var(--bd-green) !important; color: #fff !important; }
        .page-item.disabled .page-link { color: var(--bd-text-3) !important; }

        /* Alerts */
        .alert { border-radius: var(--bd-radius) !important; font-size: 13px !important; font-family: var(--bd-font) !important; border: 1px solid transparent !important; padding: 12px 16px !important; }
        .alert-success { background: #f0fdf4 !important; border-color: rgba(0,149,67,.2) !important; color: #007836 !important; }
        .alert-danger,.alert-error { background: #fef2f2 !important; border-color: rgba(239,68,68,.2) !important; color: #b91c1c !important; }
        .alert-warning { background: #fffbeb !important; border-color: rgba(245,158,11,.2) !important; color: #d97706 !important; }
        .alert-info { background: #eff6ff !important; border-color: rgba(59,130,246,.2) !important; color: #1d4ed8 !important; }

        /* Modal */
        .modal-content { border-radius: var(--bd-radius-lg) !important; border: none !important; background: var(--bd-surface) !important; box-shadow: 0 20px 60px rgba(0,0,0,.15) !important; }
        .modal-header { padding: 16px 20px !important; border-bottom: 1px solid var(--bd-border) !important; border-radius: var(--bd-radius-lg) var(--bd-radius-lg) 0 0 !important; }
        .modal-title { font-size: 15px !important; font-weight: 600 !important; color: var(--bd-text) !important; }
        .modal-body { padding: 20px !important; font-size: 13px !important; color: var(--bd-text) !important; }
        .modal-footer { padding: 12px 20px !important; border-top: 1px solid var(--bd-border) !important; border-radius: 0 0 var(--bd-radius-lg) var(--bd-radius-lg) !important; }
        .close { color: var(--bd-text-3) !important; opacity: 1 !important; font-size: 18px !important; }
        .close:hover { color: var(--bd-text) !important; }

        /* DataTables */
        .dataTables_wrapper .dataTables_length select,
        .dataTables_wrapper .dataTables_filter input { border: 1px solid var(--bd-border) !important; border-radius: 6px !important; font-size: 12px !important; padding: 5px 8px !important; font-family: var(--bd-font) !important; background: var(--bd-surface) !important; color: var(--bd-text) !important; }
        .dataTables_wrapper .dataTables_filter input:focus { border-color: var(--bd-green) !important; outline: none !important; }
        .dataTables_wrapper .dataTables_info,
        .dataTables_wrapper .dataTables_length label,
        .dataTables_wrapper .dataTables_filter label { font-size: 12px !important; color: var(--bd-text-2) !important; }

        /* Select2 */
        .select2-container--default .select2-selection--single,
        .select2-container--default .select2-selection--multiple { border: 1px solid var(--bd-border) !important; border-radius: 6px !important; min-height: 36px !important; background: var(--bd-surface) !important; }
        .select2-container--default.select2-container--focus .select2-selection--single,
        .select2-container--default.select2-container--focus .select2-selection--multiple { border-color: var(--bd-green) !important; box-shadow: 0 0 0 3px var(--bd-green-glow) !important; }
        .select2-container--default .select2-results__option--highlighted[aria-selected] { background: var(--bd-green) !important; }
        .select2-dropdown { background: var(--bd-surface) !important; border: 1px solid var(--bd-border) !important; }
        .select2-container--default .select2-results__option { color: var(--bd-text) !important; }
        .select2-container--default .select2-selection--single .select2-selection__rendered { color: var(--bd-text) !important; }

        /* ── Aide visuelle : fond body en mode sombre ─────── */
        [data-theme="dark"] body { background: var(--bd-bg); }
        [data-theme="dark"] .modal-backdrop { background: rgba(0,0,0,.7) !important; }
    </style>
</head>
<body>
@php
    $restaurantUser = auth()->user();
    $restaurant = optional($restaurantUser)->restaurant;
    $restaurantLogo = null;
    if ($restaurant && !empty($restaurant->logo)) {
        $restaurantLogo = \Illuminate\Support\Str::startsWith($restaurant->logo, ['http://', 'https://'])
            ? $restaurant->logo
            : asset('images/restaurant_images/' . $restaurant->logo);
    }
    // ── Navigation principale avec sous-menus ─────────────
    $navCurrent = [
        'dashboard'    => trim($__env->yieldContent('dashboard_nav'))    === 'active',
        'order'        => trim($__env->yieldContent('order_nav'))        === 'active',
        'kitchen'      => trim($__env->yieldContent('kitchen_nav'))      === 'active',
        'menu'         => trim($__env->yieldContent('menu_nav'))         === 'active',
        'product'      => trim($__env->yieldContent('product_nav'))      === 'active',
        'category'     => trim($__env->yieldContent('category_nav'))     === 'active',
        'media'        => trim($__env->yieldContent('media_nav'))        === 'active',
        'add_on'       => trim($__env->yieldContent('add_on_nav'))       === 'active',
        'earnings'     => trim($__env->yieldContent('earnings_nav'))     === 'active',
        'profile'      => trim($__env->yieldContent('profile_nav'))      === 'active',
        'working_hour' => trim($__env->yieldContent('working_hour_nav')) === 'active',
        'vouchers'     => trim($__env->yieldContent('vouchers_nav'))     === 'active',
        'employee'     => trim($__env->yieldContent('employee_nav'))     === 'active',
    ];

    $isOrderActive   = $navCurrent['order'] || $navCurrent['kitchen'];
    $isCatalogActive = $navCurrent['menu'] || $navCurrent['product'] || $navCurrent['category'] || $navCurrent['media'] || $navCurrent['add_on'];
    $isSettingsActive= $navCurrent['profile'] || $navCurrent['working_hour'] || $navCurrent['vouchers'] || $navCurrent['employee'];

    $sidebarItems = [
        [
            'label'  => 'Tableau de bord',
            'icon'   => 'fas fa-house',
            'route'  => route('restaurant.dashboard'),
            'active' => $navCurrent['dashboard'],
            'sub'    => [],
        ],
        [
            'label'  => 'Commandes',
            'icon'   => 'fas fa-receipt',
            'route'  => route('restaurant.all_orders'),
            'active' => $isOrderActive,
            'sub'    => [
                ['label' => 'Toutes les commandes', 'icon' => 'fas fa-list',         'route' => route('restaurant.all_orders'),      'active' => $navCurrent['order']],
                ['label' => 'Cuisine',              'icon' => 'fas fa-fire-burner',   'route' => route('restaurant.kitchen'),         'active' => $navCurrent['kitchen']],
            ],
        ],
        [
            'label'  => 'Menu',
            'icon'   => 'fas fa-utensils',
            'route'  => route('restaurant.menu.index'),
            'active' => $isCatalogActive,
            'sub'    => [
                ['label' => 'Vue d\'ensemble', 'icon' => 'fas fa-grip',          'route' => route('restaurant.menu.index'), 'active' => $navCurrent['menu']],
                ['label' => 'Produits',         'icon' => 'fas fa-bowl-food',     'route' => route('product.index'),         'active' => $navCurrent['product']],
                ['label' => 'Catégories',       'icon' => 'fas fa-layer-group',   'route' => route('category.index'),        'active' => $navCurrent['category']],
                ['label' => 'Suppléments',      'icon' => 'fas fa-circle-plus',   'route' => route('add-on.index'),              'active' => $navCurrent['add_on']],
                ['label' => 'Médias',           'icon' => 'fas fa-images',        'route' => route('restaurant.media.index'),    'active' => $navCurrent['media']],
            ],
        ],
        [
            'label'  => 'Finances',
            'icon'   => 'fas fa-wallet',
            'route'  => route('r_earnings.index'),
            'active' => $navCurrent['earnings'],
            'sub'    => [],
        ],
        [
            'label'  => 'Paramètres',
            'icon'   => 'fas fa-gear',
            'route'  => route('restaurant.profile'),
            'active' => $isSettingsActive,
            'sub'    => [
                ['label' => 'Restaurant',      'icon' => 'fas fa-store',        'route' => route('restaurant.profile') . '?tab=restaurant', 'active' => $navCurrent['profile']],
                ['label' => 'Horaires',        'icon' => 'fas fa-clock',        'route' => route('working_hour.index'),                      'active' => $navCurrent['working_hour']],
                ['label' => 'Livraison',       'icon' => 'fas fa-motorcycle',   'route' => route('restaurant.profile') . '?tab=livraison',   'active' => false],
                ['label' => 'Promotions',      'icon' => 'fas fa-tags',         'route' => route('voucher.index'),                           'active' => $navCurrent['vouchers']],
                ['label' => 'Équipe',          'icon' => 'fas fa-users',        'route' => route('employee.index'),                          'active' => $navCurrent['employee']],
            ],
        ],
    ];
    $impersonationContext = session('admin_impersonation_context');
@endphp
<div class="bd-restaurant-app">
    <aside class="bd-restaurant-sidebar">
        <a href="{{ route('restaurant.dashboard') }}" class="bd-restaurant-sidebar__brand">
            <span class="bd-restaurant-sidebar__mark">
                @if($restaurantLogo)
                    <img src="{{ $restaurantLogo }}" alt="{{ $restaurant->name ?? 'Restaurant' }}">
                @else
                    <img src="{{ asset('frontend/images/BuntuDelice.png') }}" alt="{{ \App\Services\ConfigService::getCompanyName() }}">
                @endif
            </span>
            <span class="bd-restaurant-sidebar__copy">
                <span class="bd-restaurant-sidebar__title">{{ $restaurant->name ?? ($restaurantUser->name ?? 'Restaurant') }}</span>
                <span class="bd-restaurant-sidebar__eyebrow">{{ $restaurant->city ?? 'Brazzaville' }}</span>
            </span>
        </a>

        @php
            $isOpen = $restaurant ? !($restaurant->is_paused ?? false) : true;
        @endphp
        <nav class="bd-restaurant-nav" style="padding-top:8px;">
            @foreach($sidebarItems as $item)
                @if(!empty($item['sub']))
                    {{-- Entrée avec sous-menu --}}
                    <a href="{{ $item['route'] }}"
                       class="bd-restaurant-nav__item has-sub{{ $item['active'] ? ' is-active is-open' : '' }}"
                       onclick="bdNavToggle(event,this)">
                        <i class="{{ $item['icon'] }}"></i>
                        <span>{{ $item['label'] }}</span>
                        <i class="fas fa-chevron-right bd-restaurant-nav__chevron"></i>
                    </a>
                    <div class="bd-restaurant-nav__sub{{ $item['active'] ? ' is-open' : '' }}">
                        @foreach($item['sub'] as $sub)
                            <a href="{{ $sub['route'] }}"
                               class="bd-restaurant-nav__sub-item{{ $sub['active'] ? ' is-active' : '' }}">
                                <i class="{{ $sub['icon'] }}"></i>
                                <span>{{ $sub['label'] }}</span>
                            </a>
                        @endforeach
                    </div>
                @else
                    {{-- Entrée simple --}}
                    <a href="{{ $item['route'] }}" class="bd-restaurant-nav__item{{ $item['active'] ? ' is-active' : '' }}">
                        <i class="{{ $item['icon'] }}"></i>
                        <span>{{ $item['label'] }}</span>
                        @if(isset($item['badge']) && $item['badge'])
                            <span class="nav-badge">{{ $item['badge'] }}</span>
                        @endif
                    </a>
                @endif
            @endforeach
        </nav>

        <div class="bd-restaurant-sidebar__footer">
            <a href="{{ route('home') }}" class="bd-restaurant-sidebar__button">
                <i class="fas fa-home"></i>
                <span>Retour au site</span>
            </a>
            <form method="POST" action="{{ route('logout') }}" class="bd-restaurant-sidebar__button" style="margin:0;">
                @csrf
                <button type="submit">
                    <i class="fas fa-power-off"></i>
                    <span>Déconnexion</span>
                </button>
            </form>
        </div>
    </aside>

    <div class="bd-restaurant-main">
        <header class="bd-restaurant-topbar">
            <div class="bd-restaurant-topbar__meta">
                <span class="bd-restaurant-topbar__eyebrow">Pilotage restaurant</span>
                <span class="bd-restaurant-topbar__title">@yield('topbar_title', 'Tableau de bord restaurant')</span>
                <span class="bd-restaurant-topbar__date">{{ \Carbon\Carbon::now()->locale('fr')->isoFormat('dddd D MMMM YYYY') }}</span>
            </div>
            <div class="bd-restaurant-topbar__actions">
                @if($impersonationContext)
                    <form method="POST" action="{{ route('admin.impersonate.stop') }}" style="margin:0;">
                        @csrf
                        <button type="submit" class="bd-restaurant-topbar__session" style="border:1px solid var(--bd-border);">
                            <i class="fas fa-user-shield"></i>
                            <span>Revenir à l’admin</span>
                        </button>
                    </form>
                @endif
                <button class="bd-theme-toggle" id="bdThemeToggle" title="Mode sombre / clair" aria-label="Basculer le thème">
                    <i class="fas fa-moon" id="bdThemeIcon"></i>
                </button>
                <a href="#" class="bd-restaurant-topbar__notif" data-toggle="modal" data-target="#myModal2" aria-label="Notifications">
                    <i class="fas fa-bell"></i>
                    <span class="badge badge-warning" id="notiBell"></span>
                </a>
                <button onclick="bdDrawerOpen()" class="bd-restaurant-user" title="Mon profil" style="border:none;background:none;padding:0;cursor:pointer;" aria-label="Ouvrir le profil">
                    @php
                        $initials = strtoupper(substr($restaurantUser->name ?? $restaurant->name ?? 'R', 0, 1));
                    @endphp
                    <span class="bd-restaurant-user__avatar" style="display:inline-flex;align-items:center;justify-content:center;background:var(--r-green);color:#fff;font-size:12px;font-weight:700;border-radius:50%;overflow:hidden;">
                        @if($restaurantLogo)
                            <img src="{{ $restaurantLogo }}" alt="{{ $restaurant->name ?? 'Restaurant' }}" style="width:100%;height:100%;object-fit:cover;">
                        @else
                            {{ $initials }}
                        @endif
                    </span>
                    <span class="bd-restaurant-user__copy">
                        <span class="bd-restaurant-user__name">{{ $restaurant->name ?? $restaurantUser->name ?? 'Mon profil' }}</span>
                    </span>
                </button>
            </div>
        </header>

        <main class="bd-restaurant-content">
            <div class="bd-restaurant-content__inner">
                @yield('content')
            </div>
        </main>

        <footer class="bd-restaurant-footer">
            <strong>Copyright &copy; {{ date('Y') }} <a href="{{ route('home') }}">{{ \App\Services\ConfigService::getCompanyName() }}</a>.</strong>
            Tous droits réservés.
        </footer>
    </div>
</div>

<div class="modal right fade" id="myModal2" tabindex="-1" role="dialog" aria-labelledby="myModalLabel2">
    <div class="modal-dialog" role="document">
        <div class="modal-content pb-5">
            <div class="modal-header p-3">
                <h4 class="modal-title" id="notiTitle"></h4>
                <button type="button" class="close" data-dismiss="modal">
                    <span aria-hidden="true" style="float:left"><i class="fas fa-arrow-right" style="margin-top:5px;"></i></span>
                </button>
            </div>
            <div class="modal-body p-0 mb-5" id="notiBody"></div>
        </div>
    </div>
</div>

<script src="{{ asset('plugins/jquery/jquery.min.js') }}"></script>
<script src="{{ asset('plugins/jquery-ui/jquery-ui.min.js') }}"></script>
<script>$.widget.bridge('uibutton', $.ui.button)</script>
<script src="{{ asset('plugins/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
<script src="{{ asset('plugins/chart.js/Chart.min.js') }}"></script>
<script src="{{ asset('plugins/datatables/jquery.dataTables.js') }}"></script>
<script src="{{ asset('plugins/datatables-bs4/js/dataTables.bootstrap4.js') }}"></script>
@php($notificationAudioPath = public_path('notification.mp3'))
@if(file_exists($notificationAudioPath))
<audio id="myAudio" preload="auto" src="{{ asset('notification.mp3') }}"></audio>
@endif
<script>
const notificationConfig = {
    pollUrl: @json($restaurant ? url('/restaurant/notifications/' . $restaurant->id) : null),
    orderBaseUrl: @json(url('/restaurant/show_order')),
};

let notificationSoundUnlocked = false;
let lastNotificationSoundAt = 0;

function unlockNotificationSound() {
    notificationSoundUnlocked = true;
    const audio = document.getElementById('myAudio');
    if (!audio) return;
    audio.muted = true;
    const playPromise = audio.play();
    if (playPromise && typeof playPromise.then === 'function') {
        playPromise.then(function () {
            audio.pause();
            audio.currentTime = 0;
            audio.muted = false;
        }).catch(function () {
            audio.muted = false;
        });
    } else {
        audio.muted = false;
    }
}

document.addEventListener('click', unlockNotificationSound, { once: true });
document.addEventListener('keydown', unlockNotificationSound, { once: true });

var _restaurantAudioCtx = null;

function playNotificationSound() {
    const now = Date.now();
    if (now - lastNotificationSoundAt < 4000) return;
    lastNotificationSoundAt = now;
    const audio = document.getElementById('myAudio');
    if (audio && notificationSoundUnlocked) {
        audio.currentTime = 0;
        const p = audio.play();
        if (p && typeof p.catch === 'function') {
            p.catch(function () { _playRestaurantOscillator(); });
        }
    } else {
        _playRestaurantOscillator();
    }
}

function _playRestaurantOscillator() {
    if (!notificationSoundUnlocked) return;
    try {
        var C = window.AudioContext || window.webkitAudioContext;
        if (!C) return;
        if (!_restaurantAudioCtx || _restaurantAudioCtx.state === 'closed') _restaurantAudioCtx = new C();
        if (_restaurantAudioCtx.state === 'suspended') _restaurantAudioCtx.resume();
        var ctx = _restaurantAudioCtx;
        var osc = ctx.createOscillator();
        var gain = ctx.createGain();
        osc.type = 'sine'; osc.frequency.value = 880;
        osc.connect(gain); gain.connect(ctx.destination);
        var t = ctx.currentTime;
        gain.gain.setValueAtTime(0.001, t);
        gain.gain.exponentialRampToValueAtTime(0.18, t + 0.01);
        gain.gain.exponentialRampToValueAtTime(0.001, t + 0.25);
        osc.start(t); osc.stop(t + 0.26);
    } catch (e) {}
}

function get_notification() {
    if (!notificationConfig.pollUrl) return;
    $.ajax({
        type: 'GET',
        url: notificationConfig.pollUrl,
        dataType: 'json',
        success: function(data) {
            let value = '';
            const orders = Array.isArray(data.orders) ? data.orders : [];
            if (data.count > 0) {
                orders.forEach(function(element) {
                    value += `<a href="${notificationConfig.orderBaseUrl}/${element.order_no}" class="dropdown-item">
                        <i class="fas fa-envelope mr-2"></i>#${element.order_no}
                        <span class="float-right text-muted text-sm">${element.time || ''}</span>
                    </a><div class="dropdown-divider"></div>`;
                });
            } else {
                value += `<a class="dropdown-item text-center"><b>Aucune nouvelle notification</b></a><div class="dropdown-divider"></div>`;
            }
            if (document.getElementById('notiBody')) document.getElementById('notiBody').innerHTML = value;
            if (document.getElementById('notiTitle')) document.getElementById('notiTitle').innerHTML = data.count + ' notifications';
            if (document.getElementById('notiBell')) document.getElementById('notiBell').innerHTML = data.count;
            if (data.new) playNotificationSound();
        }
    });
}

get_notification();
setInterval(get_notification, 5000);
</script>

{{-- ── Laravel Echo + Soketi WebSocket (via nginx WSS proxy /app → soketi:6001) --}}
<script src="https://cdn.jsdelivr.net/npm/pusher-js@8.5.0/dist/web/pusher.min.js"
        integrity="sha384-uL9egdFHAuMuLFCiNbD2ihk8UhS3m64kqg17C2aI6lwuJ3psgm2Dj20y9QgXyzWw"
        crossorigin="anonymous"></script>
<script>
(function () {
    var PUSHER_KEY     = @json(config('broadcasting.connections.pusher.key', ''));
    var PUSHER_CLUSTER = @json(config('broadcasting.connections.pusher.options.cluster', 'mt1'));
    var CSRF_TOKEN     = @json(csrf_token());

    if (!PUSHER_KEY || typeof Pusher === 'undefined') return;

    // Soketi exposé via nginx sur le port 443 (WSS) au path /app
    // nginx proxie /app → http://127.0.0.1:6001
    var pusher = new Pusher(PUSHER_KEY, {
        wsHost:            window.location.hostname,
        wsPort:            443,
        wssPort:           443,
        forceTLS:          true,
        disableStats:      true,
        enabledTransports: ['wss'],
        cluster:           PUSHER_CLUSTER,
        // Auth des canaux privés via Laravel broadcasting/auth
        authEndpoint:      '/broadcasting/auth',
        auth: {
            headers: {
                'X-CSRF-TOKEN': CSRF_TOKEN,
            },
        },
    });

    @if($restaurant)
    var channel = pusher.subscribe('private-food.restaurant.{{ $restaurant->id }}.orders');

    channel.bind('food.restaurant.order.updated', function (data) {
        // Son + notification OS
        if (window.BdAudio) window.BdAudio.play('new_order');

        if ('Notification' in window && Notification.permission === 'granted') {
            var orderNo = data.order_no || '';
            new Notification('Nouvelle commande BantuDelice', {
                body: orderNo ? 'Commande #' + orderNo + ' en attente.' : 'Une nouvelle commande est arrivee.',
                icon: '{{ asset("frontend/images/BuntuDelice.png") }}',
                tag:  'order-' + orderNo,
            });
        }

        // Actualiser les badges de notification
        get_notification();
    });

    // Demander la permission OS au premier clic
    document.addEventListener('click', function () {
        if ('Notification' in window && Notification.permission === 'default') {
            Notification.requestPermission();
        }
    }, { once: true });
    @endif
})();
</script>
<script>
/* ── Dark mode toggle ─────────────────────────────── */
(function () {
    var saved = localStorage.getItem('bd-theme') || 'light';
    document.documentElement.setAttribute('data-theme', saved);
    var icon = document.getElementById('bdThemeIcon');
    if (icon) icon.className = saved === 'dark' ? 'fas fa-sun' : 'fas fa-moon';
})();

document.getElementById('bdThemeToggle')?.addEventListener('click', function () {
    var html = document.documentElement;
    var isDark = html.getAttribute('data-theme') === 'dark';
    var next = isDark ? 'light' : 'dark';
    html.setAttribute('data-theme', next);
    localStorage.setItem('bd-theme', next);
    document.getElementById('bdThemeIcon').className = next === 'dark' ? 'fas fa-sun' : 'fas fa-moon';
});

</script>
<script>
function bdNavToggle(e, el) {
    var sub = el.nextElementSibling;
    if (!sub || !sub.classList.contains('bd-restaurant-nav__sub')) return;
    var isOpen = sub.classList.contains('is-open');
    if (isOpen) {
        // Refermer : naviguer vers la route principale
        return; // laisser le lien fonctionner
    }
    // Ouvrir sans naviguer
    e.preventDefault();
    sub.classList.toggle('is-open');
    el.classList.toggle('is-open');
}
</script>
@yield('script')

@include('admin.partials._profile_drawer')
</body>
</html>
