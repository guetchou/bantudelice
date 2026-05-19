<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>@yield('title')</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <link rel="stylesheet" href="{{ asset('plugins/fontawesome-free/css/all.min.css') }}">
    <link rel="stylesheet" href="{{ asset('dist/css/adminlte.min.css') }}">
    <link rel="stylesheet" href="{{ asset('plugins/datatables-bs4/css/dataTables.bootstrap4.css') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=Syne:wght@400;500;600;700;800&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    @yield('style')
    <style>
        /* ═══════════════════════════════════════════════════
           Food Platform — Restaurant App Layout
           Design system : Inter · vert #009543 · neutre
           ═══════════════════════════════════════════════════ */
        :root {
            --r-bg:          #f3f4f6;
            --r-surface:     #ffffff;
            --r-border:      #e5e7eb;
            --r-text:        #111827;
            --r-text-2:      #6b7280;
            --r-text-3:      #9ca3af;
            --r-green:       #009543;
            --r-green-pale:  #f0fdf4;
            --r-green-mid:   rgba(0,149,67,.12);
            --r-shadow-sm:   0 1px 2px rgba(0,0,0,.06);
            --r-shadow:      0 1px 3px rgba(0,0,0,.10), 0 1px 2px rgba(0,0,0,.06);
            --r-radius:      8px;
            --r-radius-lg:   12px;
            --r-sidebar-w:   240px;
            --r-topbar-h:    60px;
            --r-font:        'Inter', sans-serif;
        }

        *, *::before, *::after { box-sizing: border-box; margin: 0; }

        html, body {
            height: 100%;
            background: var(--r-bg);
            color: var(--r-text);
            font-family: var(--r-font);
            font-size: 14px;
            line-height: 1.5;
            -webkit-font-smoothing: antialiased;
        }

        a { text-decoration: none; color: inherit; }

        /* ── App shell ───────────────────────────────────── */
        .bd-restaurant-app {
            display: flex;
            min-height: 100vh;
        }

        /* ── Sidebar ─────────────────────────────────────── */
        .bd-restaurant-sidebar {
            width: var(--r-sidebar-w);
            flex-shrink: 0;
            position: sticky;
            top: 0;
            height: 100vh;
            overflow-y: auto;
            background: var(--r-surface);
            border-right: 1px solid var(--r-border);
            display: flex;
            flex-direction: column;
            padding: 0;
        }

        .bd-restaurant-sidebar__brand {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 16px 16px 14px;
            border-bottom: 1px solid var(--r-border);
            text-decoration: none;
        }

        .bd-restaurant-sidebar__mark {
            width: 36px;
            height: 36px;
            border-radius: 8px;
            overflow: hidden;
            background: var(--r-green);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .bd-restaurant-sidebar__mark img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        .bd-restaurant-sidebar__copy {
            min-width: 0;
        }

        .bd-restaurant-sidebar__title {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: var(--r-text);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            line-height: 1.3;
        }

        .bd-restaurant-sidebar__eyebrow {
            display: block;
            font-size: 11px;
            font-weight: 400;
            color: var(--r-text-3);
            line-height: 1.3;
            margin-top: 1px;
        }

        .bd-restaurant-sidebar__meta { display: none; }

        .bd-restaurant-sidebar__status {
            margin: 12px 12px 0;
            padding: 10px 12px;
            border-radius: var(--r-radius);
            background: var(--r-green-pale);
            border: 1px solid rgba(0,149,67,.15);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
        }

        .bd-restaurant-sidebar__status span { display: block; }

        .bd-restaurant-sidebar__status-label {
            font-size: 10px;
            font-weight: 600;
            letter-spacing: .06em;
            text-transform: uppercase;
            color: var(--r-text-3);
        }

        .bd-restaurant-sidebar__status-value {
            font-size: 12px;
            font-weight: 500;
            color: var(--r-text);
            margin-top: 1px;
        }

        .bd-restaurant-sidebar__status-pill {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 2px 8px;
            border-radius: 999px;
            background: var(--r-green);
            color: #fff;
            font-size: 11px;
            font-weight: 600;
            white-space: nowrap;
        }

        .bd-restaurant-sidebar__status-pill::before {
            content: '';
            width: 5px;
            height: 5px;
            border-radius: 50%;
            background: rgba(255,255,255,.7);
            display: block;
        }

        /* ── Nav ─────────────────────────────────────────── */
        .bd-restaurant-nav {
            flex: 1;
            padding: 12px 8px;
            overflow-y: auto;
        }

        .bd-restaurant-nav__group {
            margin-bottom: 16px;
        }

        .bd-restaurant-nav__group-label {
            display: block;
            padding: 0 8px;
            margin-bottom: 4px;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: .08em;
            text-transform: uppercase;
            color: var(--r-text-3);
        }

        .bd-restaurant-nav__item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 8px 10px;
            border-radius: 6px;
            color: var(--r-text-2);
            font-size: 13px;
            font-weight: 500;
            transition: background .12s, color .12s;
            margin-bottom: 1px;
        }

        .bd-restaurant-nav__item:hover {
            background: var(--r-bg);
            color: var(--r-text);
        }

        .bd-restaurant-nav__item.is-active {
            background: var(--r-green-pale);
            color: var(--r-green);
            font-weight: 600;
        }

        .bd-restaurant-nav__item i {
            width: 16px;
            text-align: center;
            font-size: 13px;
            flex-shrink: 0;
        }

        /* ── Sidebar footer ──────────────────────────────── */
        .bd-restaurant-sidebar__footer {
            padding: 8px;
            border-top: 1px solid var(--r-border);
            display: flex;
            flex-direction: column;
            gap: 2px;
        }

        .bd-restaurant-sidebar__button,
        .bd-restaurant-sidebar__button button {
            display: flex;
            align-items: center;
            gap: 10px;
            width: 100%;
            padding: 8px 10px;
            border-radius: 6px;
            border: none;
            background: transparent;
            color: var(--r-text-2);
            font-size: 13px;
            font-weight: 500;
            font-family: var(--r-font);
            cursor: pointer;
            transition: background .12s, color .12s;
        }

        .bd-restaurant-sidebar__button:hover,
        .bd-restaurant-sidebar__button button:hover {
            background: var(--r-bg);
            color: var(--r-text);
        }

        /* ── Main ────────────────────────────────────────── */
        .bd-restaurant-main {
            flex: 1;
            min-width: 0;
            display: flex;
            flex-direction: column;
        }

        /* ── Topbar ──────────────────────────────────────── */
        .bd-restaurant-topbar {
            position: sticky;
            top: 0;
            z-index: 30;
            height: var(--r-topbar-h);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            padding: 0 24px;
            background: var(--r-surface);
            border-bottom: 1px solid var(--r-border);
        }

        .bd-restaurant-topbar__meta { display: flex; align-items: center; gap: 16px; }

        .bd-restaurant-topbar__eyebrow { display: none; }

        .bd-restaurant-topbar__title {
            font-size: 15px;
            font-weight: 600;
            color: var(--r-text);
            letter-spacing: -.01em;
        }

        .bd-restaurant-topbar__date {
            font-size: 12px;
            color: var(--r-text-3);
            border-left: 1px solid var(--r-border);
            padding-left: 16px;
        }

        .bd-restaurant-topbar__actions {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .bd-restaurant-topbar__home,
        .bd-restaurant-topbar__session {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 14px;
            border-radius: 6px;
            border: 1px solid var(--r-border);
            background: var(--r-surface);
            color: var(--r-text-2);
            font-size: 13px;
            font-weight: 500;
            font-family: var(--r-font);
            cursor: pointer;
            min-height: 36px;
            transition: border-color .12s, color .12s;
        }

        .bd-restaurant-topbar__home:hover,
        .bd-restaurant-topbar__session:hover { border-color: var(--r-green); color: var(--r-green); }

        .bd-restaurant-topbar__notif {
            position: relative;
            width: 36px;
            height: 36px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 6px;
            border: 1px solid var(--r-border);
            background: var(--r-surface);
            color: var(--r-text-2);
            transition: border-color .12s, color .12s;
        }

        .bd-restaurant-topbar__notif:hover { border-color: var(--r-green); color: var(--r-green); }

        .bd-restaurant-topbar__notif .badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background: var(--r-green);
            font-size: 10px;
            border-radius: 999px;
            padding: 1px 5px;
            color: #fff;
        }

        .bd-restaurant-user {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 4px 10px 4px 4px;
            border-radius: 6px;
            border: 1px solid var(--r-border);
            background: var(--r-surface);
            min-height: 36px;
            transition: border-color .12s;
        }

        .bd-restaurant-user:hover { border-color: var(--r-green); }

        .bd-restaurant-user__avatar {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            object-fit: cover;
        }

        .bd-restaurant-user__copy { display: grid; gap: 0; }

        .bd-restaurant-user__name {
            font-size: 12px;
            font-weight: 600;
            color: var(--r-text);
            line-height: 1.2;
        }

        .bd-restaurant-user__role {
            font-size: 11px;
            color: var(--r-text-3);
            line-height: 1.2;
        }

        /* ── Content ─────────────────────────────────────── */
        .bd-restaurant-content {
            flex: 1;
            padding: 24px;
            min-width: 0;
        }

        .bd-restaurant-footer {
            padding: 12px 24px;
            font-size: 12px;
            color: var(--r-text-3);
            border-top: 1px solid var(--r-border);
        }

        /* ── Modal ───────────────────────────────────────── */
        .modal.right .modal-dialog {
            position: fixed;
            right: 0;
            margin: 0;
            width: 360px;
            height: 100%;
        }

        .modal.right .modal-content {
            height: 100%;
            border-radius: 12px 0 0 12px;
            border: 0;
        }

        /* ── Responsive ──────────────────────────────────── */
        @media (max-width: 1024px) {
            .bd-restaurant-app { flex-direction: column; }
            .bd-restaurant-sidebar { width: 100%; height: auto; position: relative; flex-direction: row; flex-wrap: wrap; }
        }

        @media (max-width: 768px) {
            .bd-restaurant-content { padding: 16px; }
            .bd-restaurant-topbar { padding: 0 16px; }
            .bd-restaurant-topbar__date { display: none; }
        }

        /* ═══════════════════════════════════════════════════
           DESIGN SYSTEM GLOBAL — Override AdminLTE
           S'applique à toutes les vues restaurant
           ═══════════════════════════════════════════════════ */

        /* ── Fond & section ────────────────────────────── */
        .content-wrapper { background: transparent !important; }
        section.content { padding: 0 !important; }

        /* ── Content header (breadcrumb) ────────────────── */
        .content-header {
            padding: 0 0 16px !important;
        }
        .content-header h1 {
            font-size: 18px !important;
            font-weight: 700 !important;
            color: #111827 !important;
            letter-spacing: -.02em !important;
        }
        .content-header .breadcrumb {
            background: transparent !important;
            padding: 0 !important;
            font-size: 12px !important;
        }
        .content-header .breadcrumb-item,
        .content-header .breadcrumb-item a { color: #9ca3af !important; }
        .content-header .breadcrumb-item.active { color: #6b7280 !important; }
        .content-header .breadcrumb-item + .breadcrumb-item::before { color: #d1d5db !important; }

        /* ── Card ───────────────────────────────────────── */
        .card {
            border: 1px solid #e5e7eb !important;
            border-radius: 8px !important;
            box-shadow: 0 1px 3px rgba(0,0,0,.08) !important;
            background: #fff !important;
            margin-bottom: 16px !important;
        }
        .card-header {
            border-bottom: 1px solid #f3f4f6 !important;
            background: #fff !important;
            padding: 14px 18px !important;
            border-radius: 8px 8px 0 0 !important;
        }
        .card-header:first-child { border-radius: 8px 8px 0 0 !important; }
        .card-title {
            font-size: 14px !important;
            font-weight: 600 !important;
            color: #111827 !important;
            letter-spacing: -.01em !important;
            font-family: 'Inter', sans-serif !important;
        }
        .card-body { padding: 18px !important; }
        .card-footer {
            background: #f9fafb !important;
            border-top: 1px solid #f3f4f6 !important;
            padding: 12px 18px !important;
            border-radius: 0 0 8px 8px !important;
        }
        /* Supprimer les couleurs de bordure AdminLTE */
        .card-primary, .card-success, .card-info,
        .card-warning, .card-danger, .card-default {
            border-top: none !important;
        }
        .card-outline.card-primary,
        .card-outline.card-success,
        .card-outline.card-info,
        .card-outline.card-warning,
        .card-outline.card-danger { border: 1px solid #e5e7eb !important; }

        /* ── Buttons ────────────────────────────────────── */
        .btn {
            font-family: 'Inter', sans-serif !important;
            font-size: 13px !important;
            font-weight: 500 !important;
            border-radius: 6px !important;
            padding: 7px 14px !important;
            transition: all .12s !important;
            line-height: 1.4 !important;
        }
        .btn-sm { padding: 5px 10px !important; font-size: 12px !important; }
        .btn-lg { padding: 10px 20px !important; font-size: 14px !important; }

        /* Primary → vert */
        .btn-primary, .btn-success {
            background: #009543 !important;
            border-color: #009543 !important;
            color: #fff !important;
        }
        .btn-primary:hover, .btn-success:hover {
            background: #007836 !important;
            border-color: #007836 !important;
            color: #fff !important;
        }
        /* Outline info → neutral outline */
        .btn-outline-primary, .btn-outline-success {
            background: transparent !important;
            border: 1px solid #009543 !important;
            color: #009543 !important;
        }
        .btn-outline-primary:hover, .btn-outline-success:hover {
            background: #f0fdf4 !important;
            color: #007836 !important;
        }
        .btn-outline-info {
            background: transparent !important;
            border: 1px solid #e5e7eb !important;
            color: #374151 !important;
        }
        .btn-outline-info:hover {
            background: #f9fafb !important;
            border-color: #009543 !important;
            color: #009543 !important;
        }
        .btn-outline-warning {
            background: transparent !important;
            border: 1px solid #e5e7eb !important;
            color: #374151 !important;
        }
        .btn-outline-warning:hover {
            background: #fffbeb !important;
            border-color: #f59e0b !important;
            color: #d97706 !important;
        }
        .btn-outline-danger {
            background: transparent !important;
            border: 1px solid #e5e7eb !important;
            color: #374151 !important;
        }
        .btn-outline-danger:hover {
            background: #fef2f2 !important;
            border-color: #ef4444 !important;
            color: #dc2626 !important;
        }
        .btn-default {
            background: #fff !important;
            border: 1px solid #e5e7eb !important;
            color: #374151 !important;
        }
        .btn-default:hover { background: #f9fafb !important; }
        .btn-danger {
            background: #dc2626 !important;
            border-color: #dc2626 !important;
            color: #fff !important;
        }
        .btn-warning {
            background: #f59e0b !important;
            border-color: #f59e0b !important;
            color: #fff !important;
        }
        .btn-info {
            background: #3b82f6 !important;
            border-color: #3b82f6 !important;
            color: #fff !important;
        }

        /* ── Tables ─────────────────────────────────────── */
        .table {
            font-size: 13px !important;
            color: #374151 !important;
            font-family: 'Inter', sans-serif !important;
        }
        .table thead th {
            font-size: 11px !important;
            font-weight: 700 !important;
            letter-spacing: .06em !important;
            text-transform: uppercase !important;
            color: #9ca3af !important;
            border-bottom: 1px solid #f3f4f6 !important;
            border-top: none !important;
            padding: 10px 12px !important;
            background: #f9fafb !important;
            white-space: nowrap !important;
        }
        .table tbody td {
            padding: 12px 12px !important;
            border-top: 1px solid #f9fafb !important;
            vertical-align: middle !important;
            color: #374151 !important;
        }
        .table tbody tr:hover td { background: #f9fafb !important; }
        .table-bordered { border: 1px solid #f3f4f6 !important; }
        .table-bordered td, .table-bordered th { border: 1px solid #f3f4f6 !important; }
        .table-striped tbody tr:nth-of-type(odd) td { background: #fafafa !important; }
        .table-hover tbody tr:hover td { background: #f9fafb !important; }
        .table-responsive { border-radius: 0 0 8px 8px !important; }

        /* ── Badges ─────────────────────────────────────── */
        .badge {
            font-size: 11px !important;
            font-weight: 600 !important;
            padding: 3px 8px !important;
            border-radius: 999px !important;
            font-family: 'Inter', sans-serif !important;
        }
        .badge-success, .badge-primary { background: #dcfce7 !important; color: #007836 !important; }
        .badge-warning  { background: #fef3c7 !important; color: #d97706 !important; }
        .badge-danger   { background: #fee2e2 !important; color: #b91c1c !important; }
        .badge-info     { background: #dbeafe !important; color: #1d4ed8 !important; }
        .badge-secondary, .badge-default { background: #f3f4f6 !important; color: #6b7280 !important; }
        .badge-dark     { background: #f3f4f6 !important; color: #111827 !important; }

        /* ── Forms ──────────────────────────────────────── */
        .form-control {
            border: 1px solid #e5e7eb !important;
            border-radius: 6px !important;
            font-size: 13px !important;
            font-family: 'Inter', sans-serif !important;
            color: #111827 !important;
            padding: 8px 12px !important;
            height: auto !important;
            transition: border-color .12s, box-shadow .12s !important;
        }
        .form-control:focus {
            border-color: #009543 !important;
            box-shadow: 0 0 0 3px rgba(0,149,67,.1) !important;
            outline: none !important;
        }
        label {
            font-size: 12px !important;
            font-weight: 600 !important;
            color: #374151 !important;
            margin-bottom: 4px !important;
            font-family: 'Inter', sans-serif !important;
        }
        .form-group { margin-bottom: 16px !important; }
        select.form-control { cursor: pointer !important; }

        /* ── Nav tabs ───────────────────────────────────── */
        .nav-tabs {
            border-bottom: 1px solid #e5e7eb !important;
        }
        .nav-tabs .nav-link {
            font-size: 13px !important;
            font-weight: 500 !important;
            color: #6b7280 !important;
            border: none !important;
            border-bottom: 2px solid transparent !important;
            border-radius: 0 !important;
            padding: 8px 16px !important;
            font-family: 'Inter', sans-serif !important;
            transition: color .12s !important;
        }
        .nav-tabs .nav-link:hover { color: #111827 !important; border-bottom-color: #e5e7eb !important; }
        .nav-tabs .nav-link.active {
            color: #009543 !important;
            border-bottom-color: #009543 !important;
            background: transparent !important;
            font-weight: 600 !important;
        }

        /* ── Pagination ─────────────────────────────────── */
        .pagination {
            font-size: 13px !important;
            font-family: 'Inter', sans-serif !important;
        }
        .page-link {
            border: 1px solid #e5e7eb !important;
            color: #374151 !important;
            padding: 6px 12px !important;
            border-radius: 6px !important;
            margin: 0 2px !important;
        }
        .page-link:hover { background: #f9fafb !important; border-color: #009543 !important; color: #009543 !important; }
        .page-item.active .page-link {
            background: #009543 !important;
            border-color: #009543 !important;
            color: #fff !important;
        }
        .page-item.disabled .page-link { color: #9ca3af !important; }

        /* ── Alerts ─────────────────────────────────────── */
        .alert {
            border-radius: 8px !important;
            font-size: 13px !important;
            font-family: 'Inter', sans-serif !important;
            border: 1px solid transparent !important;
            padding: 12px 16px !important;
        }
        .alert-success { background: #f0fdf4 !important; border-color: rgba(0,149,67,.2) !important; color: #007836 !important; }
        .alert-danger, .alert-error { background: #fef2f2 !important; border-color: rgba(239,68,68,.2) !important; color: #b91c1c !important; }
        .alert-warning { background: #fffbeb !important; border-color: rgba(245,158,11,.2) !important; color: #d97706 !important; }
        .alert-info { background: #eff6ff !important; border-color: rgba(59,130,246,.2) !important; color: #1d4ed8 !important; }

        /* ── Modal ──────────────────────────────────────── */
        .modal-content {
            border-radius: 12px !important;
            border: none !important;
            box-shadow: 0 20px 60px rgba(0,0,0,.15) !important;
        }
        .modal-header {
            padding: 16px 20px !important;
            border-bottom: 1px solid #f3f4f6 !important;
            border-radius: 12px 12px 0 0 !important;
        }
        .modal-title { font-size: 15px !important; font-weight: 600 !important; color: #111827 !important; }
        .modal-body { padding: 20px !important; font-size: 13px !important; }
        .modal-footer { padding: 12px 20px !important; border-top: 1px solid #f3f4f6 !important; border-radius: 0 0 12px 12px !important; }
        .close { color: #9ca3af !important; opacity: 1 !important; font-size: 18px !important; }
        .close:hover { color: #374151 !important; }

        /* ── DataTables ─────────────────────────────────── */
        .dataTables_wrapper .dataTables_length select,
        .dataTables_wrapper .dataTables_filter input {
            border: 1px solid #e5e7eb !important;
            border-radius: 6px !important;
            font-size: 12px !important;
            padding: 5px 8px !important;
            font-family: 'Inter', sans-serif !important;
        }
        .dataTables_wrapper .dataTables_filter input:focus {
            border-color: #009543 !important;
            outline: none !important;
        }
        .dataTables_wrapper .dataTables_info,
        .dataTables_wrapper .dataTables_length label,
        .dataTables_wrapper .dataTables_filter label { font-size: 12px !important; color: #6b7280 !important; }

        /* ── Select2 ────────────────────────────────────── */
        .select2-container--default .select2-selection--single,
        .select2-container--default .select2-selection--multiple {
            border: 1px solid #e5e7eb !important;
            border-radius: 6px !important;
            min-height: 36px !important;
        }
        .select2-container--default.select2-container--focus .select2-selection--single,
        .select2-container--default.select2-container--focus .select2-selection--multiple {
            border-color: #009543 !important;
            box-shadow: 0 0 0 3px rgba(0,149,67,.1) !important;
        }
        .select2-container--default .select2-results__option--highlighted[aria-selected] {
            background: #009543 !important;
        }
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
    $sidebarGroups = [
        [
            'label' => 'Pilotage',
            'items' => [
                ['label' => 'Tableau de bord', 'icon' => 'fas fa-chart-line', 'route' => route('restaurant.dashboard'), 'active' => trim($__env->yieldContent('dashboard_nav')) === 'active'],
                ['label' => 'Commandes', 'icon' => 'fas fa-receipt', 'route' => route('restaurant.all_orders'), 'active' => trim($__env->yieldContent('order_nav')) === 'active'],
                ['label' => 'Écran cuisine', 'icon' => 'fas fa-fire-burner', 'route' => route('restaurant.kitchen'), 'active' => trim($__env->yieldContent('kitchen_nav')) === 'active'],
                ['label' => 'Historique des paiements', 'icon' => 'fas fa-wallet', 'route' => route('r_earnings.index'), 'active' => trim($__env->yieldContent('earnings_nav')) === 'active'],
            ],
        ],
        [
            'label' => 'Catalogue',
            'items' => [
                ['label' => 'Menu moderne', 'icon' => 'fas fa-utensils', 'route' => route('restaurant.menu.index'), 'active' => trim($__env->yieldContent('menu_nav')) === 'active'],
                ['label' => 'Médias', 'icon' => 'fas fa-images', 'route' => route('restaurant.media.index'), 'active' => trim($__env->yieldContent('media_nav')) === 'active'],
                ['label' => 'Catégories', 'icon' => 'fas fa-layer-group', 'route' => route('category.index'), 'active' => trim($__env->yieldContent('category_nav')) === 'active'],
                ['label' => 'Produits', 'icon' => 'fas fa-bowl-food', 'route' => route('product.index'), 'active' => trim($__env->yieldContent('product_nav')) === 'active'],
            ],
        ],
        [
            'label' => 'Configuration',
            'items' => [
                ['label' => 'Horaires', 'icon' => 'fas fa-clock', 'route' => route('working_hour.index'), 'active' => trim($__env->yieldContent('working_hour_nav')) === 'active'],
                ['label' => 'Bons de réduction', 'icon' => 'fas fa-tags', 'route' => route('voucher.index'), 'active' => trim($__env->yieldContent('vouchers_nav')) === 'active'],
                ['label' => 'Profil', 'icon' => 'fas fa-store', 'route' => route('restaurant.profile'), 'active' => trim($__env->yieldContent('profile_nav')) === 'active'],
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

        <nav class="bd-restaurant-nav">
            @foreach($sidebarGroups as $group)
                <div class="bd-restaurant-nav__group">
                    <span class="bd-restaurant-nav__group-label">{{ $group['label'] }}</span>
                    @foreach($group['items'] as $item)
                        <a href="{{ $item['route'] }}" class="bd-restaurant-nav__item{{ $item['active'] ? ' is-active' : '' }}">
                            <i class="{{ $item['icon'] }}"></i>
                            <span>{{ $item['label'] }}</span>
                        </a>
                    @endforeach
                </div>
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
                        <button type="submit" class="bd-restaurant-topbar__session" style="border:1px solid var(--restaurant-border);">
                            <i class="fas fa-user-shield"></i>
                            <span>Revenir à l’admin</span>
                        </button>
                    </form>
                @endif
                <a href="#" class="bd-restaurant-topbar__notif" data-toggle="modal" data-target="#myModal2" aria-label="Notifications">
                    <i class="fas fa-bell"></i>
                    <span class="badge badge-warning" id="notiBell"></span>
                </a>
                <a href="{{ route('restaurant.profile') }}" class="bd-restaurant-user" title="Mon profil">
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
                </a>
            </div>
        </header>

        <main class="bd-restaurant-content">
            @yield('content')
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

{{-- ── Laravel Echo + Soketi WebSocket ─────────────────────── --}}
<script src="https://cdn.jsdelivr.net/npm/pusher-js@8/dist/web/pusher.min.js"></script>
<script>
(function () {
    var PUSHER_KEY     = @json(env('PUSHER_APP_KEY', 'bantudelice-key'));
    var PUSHER_HOST    = @json(env('PUSHER_HOST', '127.0.0.1'));
    var PUSHER_PORT    = @json((int) env('PUSHER_PORT', 6001));
    var PUSHER_CLUSTER = @json(env('PUSHER_APP_CLUSTER', 'mt1'));

    if (!PUSHER_KEY || typeof Pusher === 'undefined') return;

    var pusher = new Pusher(PUSHER_KEY, {
        wsHost:           window.location.hostname,
        wsPort:           PUSHER_PORT,
        wssPort:          PUSHER_PORT,
        forceTLS:         false,
        disableStats:     true,
        enabledTransports:['ws', 'wss'],
        cluster:          PUSHER_CLUSTER,
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
@yield('script')
</body>
</html>
