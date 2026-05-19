<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>@yield('title')</title>
    <!-- Tell the browser to be responsive to screen width -->
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" type="image/x-icon" href="{{asset('favicon.ico')}}">
    <!-- Font Awesome -->
    <link 
  href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.3.0/css/font-awesome.css" 
  rel="stylesheet"  type='text/css'>
    <link rel="stylesheet" href="{{ asset('plugins/fontawesome-free/css/all.min.css')}}">
    <!-- Ionicons -->
    <link rel="stylesheet" href="https://code.ionicframework.com/ionicons/2.0.1/css/ionicons.min.css">
    <!-- Tempusdominus Bbootstrap 4 -->
    <link rel="stylesheet"
          href="{{ asset('plugins/tempusdominus-bootstrap-4/css/tempusdominus-bootstrap-4.min.css')}}">
    <!-- iCheck -->
    <link rel="stylesheet" href="{{ asset('plugins/icheck-bootstrap/icheck-bootstrap.min.css')}}">
    <!-- JQVMap -->
    <link rel="stylesheet" href="{{ asset('plugins/jqvmap/jqvmap.min.css')}}">
    <!-- Theme style -->
    <link rel="stylesheet" href="{{ asset('dist/css/adminlte.min.css')}}">

    <!-- overlayScrollbars -->
    <link rel="stylesheet"
          href="{{ asset('plugins/overlayScrollbars/css/OverlayScrollbars.min.css')}}">
    <!-- Daterange picker -->
    <link rel="stylesheet" href="{{ asset('plugins/daterangepicker/daterangepicker.css')}}">
    <!-- summernote -->
    <link rel="stylesheet" href="{{ asset('plugins/summernote/summernote-bs4.css')}}">
    <link rel="stylesheet" href="{{ asset('plugins/datatables-bs4/css/dataTables.bootstrap4.css')}}">
    <!-- Google Font: Source Sans Pro -->
    @yield('style')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Instrument+Sans:wght@400;500;600;700;800&family=Manrope:wght@700;800&display=swap" rel="stylesheet">
<style>
    :root {
        --bg: #f4f6f9;
        --bg-2: #ffffff;
        --bg-3: #f8fafc;
        --bg-4: #f1f5f9;
        --surface: #ffffff;
        --surface-2: #f8fafc;
        --border: rgba(15,23,42,.08);
        --border-2: rgba(15,23,42,.14);
        --text: #0f172a;
        --text-2: #334155;
        --text-3: #64748b;
        --green: #009543;
        --green-dark: #007836;
        --green-pale: rgba(0,149,67,.10);
        --gold: #b45309;
        --gold-pale: rgba(180,83,9,.10);
        --amber: #d97706;
        --amber-pale: rgba(217,119,6,.10);
        --red: #dc2626;
        --red-pale: rgba(220,38,38,.10);
        --sidebar-w: 250px;
        --topbar-h: 60px;
        --r-s: 6px;
        --r-m: 10px;
        --r-l: 16px;
        --r-xl: 22px;
        --f-d: 'Manrope', sans-serif;
        --f-u: 'Instrument Sans', sans-serif;
        --f-b: 'Instrument Sans', sans-serif;
    }

    body {
        font-family: var(--f-b), sans-serif !important;
        font-size: .9rem !important;
        text-rendering: optimizeLegibility !important;
        -webkit-font-smoothing: antialiased !important;
        -moz-osx-font-smoothing: grayscale !important;
    }

    .wrapper {
        min-height: 100vh;
    }

    html,
    body {
        width: 100%;
        max-width: 100%;
        overflow-x: hidden;
    }

    img,
    video,
    iframe,
    canvas,
    svg {
        max-width: 100%;
        height: auto;
    }

    .table-responsive,
    .card-body.table-responsive,
    .content-wrapper .table-responsive {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }

    .content-wrapper .container-fluid,
    .content-wrapper .row,
    .content-wrapper .col,
    .content-wrapper [class*="col-"] {
        min-width: 0;
    }

    .content-wrapper .btn,
    .content-wrapper .nav-link,
    .content-wrapper .badge,
    .content-wrapper p,
    .content-wrapper h1,
    .content-wrapper h2,
    .content-wrapper h3,
    .content-wrapper h4,
    .content-wrapper h5,
    .content-wrapper h6,
    .content-wrapper td,
    .content-wrapper th {
        overflow-wrap: anywhere;
    }

    .bd-role-admin,
    .bd-role-admin .content-wrapper,
    .bd-role-admin .main-footer {
        background: var(--bg) !important;
        color: var(--text) !important;
    }

    .bd-role-admin .content-wrapper {
        margin-left: var(--sidebar-w);
        padding: 1.5rem;
        min-height: calc(100vh - var(--topbar-h)) !important;
    }

    .bd-role-admin .main-footer {
        margin-left: var(--sidebar-w);
        border-top: 1px solid var(--border);
        color: var(--text-3);
        background: var(--bg-2) !important;
    }

    .bd-role-admin .main-footer a {
        color: var(--green);
    }

    .bd-role-admin .content-header {
        padding: 0 0 .75rem !important;
    }

    .bd-role-admin .content-header h1,
    .bd-role-admin .content-header .m-0 {
        color: var(--text) !important;
        font-family: var(--f-u);
        font-size: 1.35rem;
        font-weight: 700;
        letter-spacing: -.025em;
    }

    .bd-role-admin .card,
    .bd-admin-ref-shell .panel {
        background: var(--bg-3) !important;
        border: 1px solid var(--border) !important;
        border-radius: var(--r-l) !important;
        box-shadow: none !important;
    }

    .bd-role-admin .card-header,
    .bd-admin-ref-shell .panel-header {
        border-bottom: 1px solid var(--border);
        background: transparent !important;
    }

    .bd-role-admin .card-body,
    .bd-role-admin .card-footer,
    .bd-admin-ref-shell .panel-body {
        background: transparent !important;
    }

    .bd-role-admin .table thead th {
        border-bottom: 1px solid var(--border) !important;
        color: var(--text-3);
        font-size: 0.66rem;
        text-transform: uppercase;
        letter-spacing: 0.12em;
        font-weight: 800;
    }

    .bd-role-admin .table td {
        vertical-align: middle !important;
        border-top: 1px solid var(--border) !important;
        color: var(--text-2);
    }

    .bd-role-admin .btn-primary {
        background: var(--green) !important;
        border-color: var(--green) !important;
        color: #fff !important;
        box-shadow: none !important;
    }

    .bd-role-admin .btn-outline-primary,
    .bd-role-admin .btn-secondary,
    .bd-role-admin .btn-light,
    .bd-role-admin .btn-outline-light {
        background: var(--bg-4) !important;
        border-color: var(--border) !important;
        color: var(--text) !important;
        box-shadow: none !important;
    }

    .bd-role-admin .form-control,
    .bd-role-admin .custom-select,
    .bd-role-admin select,
    .bd-role-admin textarea {
        min-height: 44px;
        border-radius: var(--r-m) !important;
        border: 1px solid var(--border) !important;
        background: var(--bg-4) !important;
        color: var(--text) !important;
        box-shadow: none !important;
    }

    .bd-role-admin .form-control:focus,
    .bd-role-admin .custom-select:focus,
    .bd-role-admin .btn:focus {
        border-color: rgba(0,149,67,.35) !important;
        box-shadow: 0 0 0 0.18rem rgba(0,149,67,.12) !important;
    }

    .bd-role-admin .form-control::placeholder {
        color: var(--text-3) !important;
    }

    .ops-context {
        display: grid;
        grid-template-columns: minmax(0,1.22fr) minmax(320px,.78fr);
        gap: 14px;
    }

    .ops-panel,
    .ops-card {
        background: #fff;
        border: 1px solid rgba(15,23,42,.08);
        border-radius: 18px;
        box-shadow: 0 18px 60px rgba(15,23,42,.06);
    }

    .ops-panel {
        padding: 16px 18px;
    }

    .ops-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        flex-wrap: wrap;
    }

    .ops-title {
        font: 800 1.18rem 'Manrope', var(--f-d), serif;
        letter-spacing: -.05em;
        color: #111827;
    }

    .ops-sub {
        margin-top: 2px;
        color: #98a2b3;
        font-size: .72rem;
    }

    .ops-meta {
        display: flex;
        align-items: center;
        gap: 10px;
        flex-wrap: wrap;
    }

    .ops-strip {
        margin-top: 12px;
        display: grid;
        gap: 8px;
    }

    .ops-group {
        display: flex;
        align-items: center;
        gap: 6px;
        flex-wrap: wrap;
    }

    .ops-group strong {
        color: #667085;
        font-size: .72rem;
        min-width: 38px;
    }

    .ops-chip {
        min-height: 24px;
        padding: 0 8px;
        border-radius: 999px;
        display: inline-flex;
        align-items: center;
        background: rgba(15,23,42,.05);
        color: #344054;
        font-size: .7rem;
        font-weight: 700;
    }

    .ops-chip--soft {
        background: var(--green-pale);
        color: var(--green);
    }

    .ops-tag,
    .ops-pill {
        min-height: 22px;
        padding: 0 7px;
        border-radius: 999px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: .66rem;
        font-weight: 800;
    }

    .ops-tag--danger,
    .ops-pill--danger {
        background: rgba(217,45,32,.1);
        color: #d92d20;
    }

    .ops-tag--warn,
    .ops-pill--warn {
        background: rgba(240,140,0,.11);
        color: #f08c00;
    }

    .ops-pill--ok {
        background: rgba(22,163,74,.1);
        color: #16a34a;
    }

    .ops-kpi-grid {
        margin-top: 12px;
        display: grid;
        grid-template-columns: repeat(4,minmax(0,1fr));
        gap: 10px;
    }

    .ops-kpi {
        background: #f8fafc;
        border: 1px solid rgba(15,23,42,.05);
        border-radius: 16px;
        padding: 12px;
    }

    .ops-kpi__label {
        color: #667085;
        font-size: .74rem;
        font-weight: 700;
    }

    .ops-kpi__value {
        margin-top: 6px;
        font: 800 1.22rem 'Manrope', var(--f-d), serif;
        letter-spacing: -.05em;
        color: #111827;
    }

    .ops-kpi__sub {
        margin-top: 4px;
        color: #667085;
        font-size: .76rem;
    }

    .ops-alerts {
        padding: 14px;
        display: grid;
        gap: 10px;
    }

    .ops-alert {
        background: #f8fafc;
        border: 1px solid rgba(15,23,42,.05);
        padding: 12px;
        border-radius: 14px;
    }

    .ops-alert__top {
        display: flex;
        justify-content: space-between;
        gap: 10px;
    }

    .ops-alert h3 {
        margin: 0;
        font-size: .88rem;
    }

    .ops-alert p {
        margin: 6px 0 0;
        color: #667085;
        font-size: .77rem;
        line-height: 1.5;
    }

    .ops-grid {
        display: grid;
        gap: 14px;
    }

    .ops-grid--2 {
        grid-template-columns: minmax(0,1fr) minmax(0,1fr);
    }

    .ops-card {
        padding: 16px 18px;
    }

    .ops-card__header {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 10px;
        margin-bottom: 12px;
    }

    .ops-card__header h2 {
        margin: 0;
        font-size: .94rem;
        letter-spacing: -.03em;
    }

    .ops-card__header p {
        margin: 4px 0 0;
        color: #667085;
        font-size: .77rem;
    }

    .ops-queue {
        display: grid;
        gap: 10px;
    }

    .ops-queue-item {
        display: grid;
        grid-template-columns: auto 1fr auto;
        gap: 10px;
        align-items: start;
        padding: 12px;
        border-radius: 14px;
        background: #f8fafc;
        border: 1px solid rgba(15,23,42,.05);
    }

    .ops-queue-dot {
        width: 9px;
        height: 9px;
        margin-top: 4px;
        border-radius: 999px;
    }

    .ops-queue-dot--danger { background: #d92d20; }
    .ops-queue-dot--warn { background: #f08c00; }
    .ops-queue-dot--ok { background: #16a34a; }

    .ops-queue-item h3 {
        margin: 0;
        font-size: .85rem;
        line-height: 1.35;
    }

    .ops-queue-item p {
        margin: 4px 0 0;
        color: #667085;
        font-size: .76rem;
        line-height: 1.48;
    }

    .ops-queue-item a {
        min-height: 30px;
        padding: 0 10px;
        display: inline-flex;
        align-items: center;
        border-radius: 12px;
        background: #fff;
        border: 1px solid rgba(15,23,42,.08);
        font-size: .75rem;
        font-weight: 700;
        color: #0f172a;
    }

    .ops-stats-grid {
        display: grid;
        grid-template-columns: repeat(2,minmax(0,1fr));
        gap: 10px;
    }

    .ops-stat {
        background: #f8fafc;
        border: 1px solid rgba(15,23,42,.05);
        padding: 12px;
        border-radius: 14px;
    }

    .ops-stat strong {
        display: block;
        font: 800 1.12rem 'Manrope', var(--f-d), serif;
        letter-spacing: -.05em;
    }

    .ops-stat span {
        display: block;
        margin-top: 4px;
        color: #667085;
        font-size: .75rem;
    }

    .ops-trend {
        margin-top: 12px;
        height: 96px;
        border-radius: 14px;
        background:
            repeating-linear-gradient(to right, transparent 0 52px, rgba(15,23,42,.04) 52px 53px),
            repeating-linear-gradient(to top, transparent 0 30px, rgba(15,23,42,.04) 30px 31px),
            linear-gradient(180deg, rgba(255,255,255,.35), rgba(255,255,255,.9)),
            rgba(0,149,67,.10);
        position: relative;
        overflow: hidden;
    }

    .ops-trend svg {
        position: absolute;
        inset: 12px;
        width: calc(100% - 24px);
        height: calc(100% - 24px);
    }

    .ops-table-wrap {
        overflow-x: auto;
    }

    .ops-table {
        width: 100%;
        border-collapse: collapse;
    }

    .ops-table th {
        font-size: .62rem;
        font-weight: 800;
        letter-spacing: .14em;
        text-transform: uppercase;
        color: #667085;
        padding: .62rem .78rem;
        text-align: left;
        border-bottom: 1px solid rgba(15,23,42,.08);
    }

    .ops-table td {
        padding: .72rem .78rem;
        font-size: .78rem;
        color: #334155;
        border-bottom: 1px solid rgba(15,23,42,.07);
        vertical-align: top;
    }

    .ops-table tr:last-child td {
        border-bottom: none;
    }

    .ops-table strong {
        color: #0f172a;
    }

    .bd-role-admin .main-sidebar,
    .bd-role-restaurant .main-sidebar {
        background: var(--bg-2) !important;
        border-right: 1px solid var(--border);
        box-shadow: none !important;
    }

    .bd-role-admin .main-sidebar .brand-image.p-0,
    .bd-role-restaurant .main-sidebar .brand-image.p-0 {
        display: flex;
        align-items: center;
        gap: .7rem;
        min-height: 82px;
        padding: 1.2rem !important;
        background: transparent !important;
        border-bottom: 1px solid var(--border);
        justify-content: flex-start;
    }

    .bd-role-admin .bd-admin-brand__dot {
        width: 28px;
        height: 28px;
        border-radius: 7px;
        background: var(--green);
        color: #fff;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    .bd-role-admin .bd-admin-brand__name {
        font-family: var(--f-u);
        font-size: 1.05rem;
        font-weight: 800;
        color: var(--text);
        letter-spacing: -.03em;
    }

    .bd-role-admin .bd-admin-brand__stack {
        display: grid;
        gap: .12rem;
        min-width: 0;
        flex: 1;
    }

    .bd-role-admin .bd-admin-brand__meta { display: none; }

    .bd-role-admin .bd-admin-brand__badge {
        margin-left: auto;
        font-size: .58rem;
        font-weight: 800;
        letter-spacing: .1em;
        text-transform: uppercase;
        background: var(--green-pale);
        color: var(--green);
        padding: 2px 7px;
        border-radius: 4px;
    }
    .bd-role-restaurant,
    .bd-role-restaurant .content-wrapper,
    .bd-role-restaurant .main-footer {
        background:
            radial-gradient(circle at top left, rgba(254, 215, 170, .24), transparent 24%),
            linear-gradient(180deg, #fffaf5 0%, #fff 46%, #f8fafc 100%) !important;
        color: var(--text) !important;
    }
    .bd-role-restaurant .content-wrapper {
        margin-left: var(--sidebar-w);
        padding: 1.4rem 1.5rem 2rem;
        min-height: calc(100vh - var(--topbar-h)) !important;
    }
    .bd-role-restaurant .main-footer {
        margin-left: var(--sidebar-w);
        border-top: 1px solid rgba(251, 146, 60, .14);
        color: #7c2d12;
        background: rgba(255,255,255,.9) !important;
    }
    .bd-role-restaurant .main-footer a {
        color: #c2410c;
    }

    .bd-role-admin .user-panel {
        border-bottom: 1px solid var(--border) !important;
        margin: 0 1rem 1rem !important;
        padding: 1rem 0 1rem !important;
    }

    .bd-role-admin .user-panel .image img {
        width: 38px;
        height: 38px;
        border-radius: 50%;
        border: 1px solid var(--border);
        object-fit: cover;
    }

    .bd-role-admin .user-panel .info a,
    .bd-role-admin .user-panel .info {
        color: var(--text) !important;
        font-weight: 700;
    }

    .bd-role-admin .bd-sidebar-group {
        font-family: var(--f-u);
        font-size: .58rem;
        font-weight: 700;
        letter-spacing: .22em;
        text-transform: uppercase;
        color: var(--text-3);
        padding: .9rem 1.2rem .35rem;
        margin-top: .25rem;
    }
    .bd-role-restaurant .bd-sidebar-group {
        font-family: var(--f-u);
        font-size: .58rem;
        font-weight: 800;
        letter-spacing: .24em;
        text-transform: uppercase;
        color: #9a3412;
        padding: 1rem 1.2rem .4rem;
        margin-top: .35rem;
    }

    .bd-role-admin .nav-sidebar .nav-link,
    .bd-role-admin .nav-sidebar .nav-treeview .nav-link,
    .bd-role-admin .sidebar .nav-link,
    .bd-role-admin .nav-item .nav-link {
        display: flex;
        font-family: var(--f-u) !important;
        align-items: center;
        gap: .72rem;
        border-radius: var(--r-m);
        color: var(--text-2) !important;
        white-space: normal !important;
        margin: 1px .6rem;
        padding: .62rem .9rem;
        transition: background .15s, color .15s;
        font-size: .82rem;
        font-weight: 500;
        background: transparent !important;
    }
    .bd-role-restaurant .nav-sidebar .nav-link,
    .bd-role-restaurant .nav-sidebar .nav-treeview .nav-link,
    .bd-role-restaurant .sidebar .nav-link,
    .bd-role-restaurant .nav-item .nav-link {
        display: flex;
        align-items: center;
        gap: .78rem;
        border-radius: 14px;
        color: #334155 !important;
        white-space: normal !important;
        margin: 3px .65rem;
        padding: .75rem .95rem;
        transition: background .15s, color .15s, transform .15s;
        font-family: var(--f-u) !important;
        font-size: .83rem;
        font-weight: 600;
        background: transparent !important;
    }

    .bd-role-admin .nav-sidebar .nav-link:hover,
    .bd-role-admin .nav-sidebar .nav-treeview .nav-link:hover {
        background: var(--bg-3) !important;
        color: var(--text) !important;
    }
    .bd-role-restaurant .nav-sidebar .nav-link:hover,
    .bd-role-restaurant .nav-sidebar .nav-treeview .nav-link:hover {
        background: #fff7ed !important;
        color: #9a3412 !important;
        transform: translateX(1px);
    }

    .bd-role-admin .nav-sidebar .nav-link.active {
        background: var(--green-pale) !important;
        color: var(--green) !important;
        font-weight: 700;
    }
    .bd-role-restaurant .nav-sidebar .nav-link.active {
        background: linear-gradient(135deg, #fff7ed 0%, #ffedd5 100%) !important;
        color: #9a3412 !important;
        font-weight: 800;
        box-shadow: inset 0 0 0 1px rgba(251, 146, 60, .16);
    }

    .bd-role-admin .nav-sidebar .nav-link .nav-icon,
    .bd-role-admin .nav-sidebar .nav-link .far,
    .bd-role-admin .nav-sidebar .nav-link .fas {
        color: currentColor !important;
        opacity: .9;
    }
    .bd-role-restaurant .nav-sidebar .nav-link .nav-icon,
    .bd-role-restaurant .nav-sidebar .nav-link .far,
    .bd-role-restaurant .nav-sidebar .nav-link .fas {
        color: currentColor !important;
        opacity: .92;
    }

    .bd-role-admin .nav-sidebar .nav-link p,
    .bd-role-admin .nav-sidebar .nav-treeview .nav-link p {
        white-space: normal !important;
        margin: 0 !important;
    }
    .bd-role-restaurant .nav-sidebar .nav-link p,
    .bd-role-restaurant .nav-sidebar .nav-treeview .nav-link p {
        white-space: normal !important;
        margin: 0 !important;
        font-family: var(--f-u) !important;
        font-size: .82rem;
        font-weight: 600;
    }

    .bd-role-admin .nav-sidebar .nav-treeview {
        margin: .1rem .35rem .45rem .2rem;
        padding: .2rem 0 .35rem;
        border-left: 1px solid rgba(15,23,42,.05);
    }
    .bd-role-restaurant .nav-sidebar .nav-treeview {
        margin-top: .15rem;
        padding-left: .35rem;
    }

    .bd-role-admin .nav-sidebar .nav-treeview .nav-link {
        font-size: .7rem;
        color: var(--text-3) !important;
        margin: 1px .35rem 1px .55rem;
        padding: .38rem .6rem;
    }
    .bd-role-restaurant .nav-sidebar .nav-treeview .nav-link {
        font-size: .76rem;
        color: #64748b !important;
    }

    .bd-role-admin .nav-sidebar > .nav-item > .nav-link > .right {
        top: 1rem;
    }

    .bd-role-admin .bd-nav-badge {
        margin-left: auto;
        font-size: .62rem;
        font-weight: 800;
        min-width: 18px;
        height: 18px;
        border-radius: 9px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 0 5px;
        background: var(--gold-pale);
        color: var(--gold);
    }

    .bd-role-admin .bd-nav-badge.bd-nav-badge--green {
        background: var(--green-pale);
        color: var(--green);
    }

    .bd-role-admin .bd-nav-badge.bd-nav-badge--red {
        background: var(--red-pale);
        color: var(--red);
    }

    .bd-role-admin .bd-sidebar-group--nested {
        padding: .42rem .95rem .16rem 1.15rem;
        margin-top: .18rem;
        font-size: .5rem;
        letter-spacing: .14em;
        color: rgba(100,116,139,.9);
    }

    .bd-role-admin .nav-item.has-treeview.menu-open > .nav-treeview {
        background: rgba(255,255,255,.36);
        border-radius: 0 0 14px 14px;
    }

    .bd-role-admin .bd-admin-topbar,
    .bd-role-restaurant .bd-admin-topbar {
        height: var(--topbar-h);
        background: rgba(255,255,255,.9) !important;
        border-bottom: 1px solid var(--border);
        display: flex;
        align-items: center;
        gap: 1rem;
        padding: 0 1.5rem;
        position: sticky;
        top: 0;
        z-index: 40;
        box-shadow: none;
    }
    .bd-role-restaurant .bd-admin-topbar {
        border-bottom-color: rgba(251, 146, 60, .14);
        backdrop-filter: blur(14px);
    }

    .bd-role-admin .bd-admin-topbar .nav-link,
    .bd-role-admin .bd-admin-topbar .navbar-text,
    .bd-role-admin .bd-admin-topbar .dropdown-toggle,
    .bd-role-admin .bd-admin-topbar .text-white,
    .bd-role-restaurant .bd-admin-topbar .nav-link,
    .bd-role-restaurant .bd-admin-topbar .navbar-text,
    .bd-role-restaurant .bd-admin-topbar .dropdown-toggle,
    .bd-role-restaurant .bd-admin-topbar .text-white {
        color: var(--text) !important;
    }

    .bd-role-admin .bd-admin-topbar__title,
    .bd-role-restaurant .bd-admin-topbar__title {
        font-family: var(--f-u);
        font-size: 1rem;
        color: var(--text);
        font-weight: 700;
        letter-spacing: -.02em;
    }

    .bd-role-admin .bd-admin-topbar__meta,
    .bd-role-restaurant .bd-admin-topbar__meta {
        display: flex;
        align-items: center;
        gap: .75rem;
    }

    .bd-role-admin .bd-admin-topbar__meta--stack {
        display: grid;
        gap: .08rem;
    }

    .bd-role-admin .bd-admin-topbar__date,
    .bd-role-restaurant .bd-admin-topbar__date {
        font-size: .68rem;
        color: var(--text-3);
    }

    .bd-admin-topbar__home {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        min-height: 36px;
        padding: 0 14px;
        border-radius: var(--r-m);
        border: 1px solid var(--border);
        background: var(--bg-3);
        color: var(--text) !important;
        text-decoration: none !important;
    }

    .bd-role-admin .bd-admin-topbar__spacer {
        flex: 1;
    }

    .bd-role-admin .bd-admin-periods {
        display: inline-flex;
        background: var(--bg-3);
        border: 1px solid var(--border);
        border-radius: var(--r-m);
        overflow: hidden;
    }

    .bd-role-admin .bd-admin-periods a,
    .bd-role-admin .bd-admin-periods button {
        padding: .38rem .85rem;
        font-size: .74rem;
        font-weight: 600;
        color: var(--text-3) !important;
        background: transparent;
        border: 0;
        text-decoration: none !important;
    }

    .bd-role-admin .bd-admin-periods .active {
        background: var(--surface);
        color: var(--text) !important;
    }

    .bd-role-admin .bd-admin-search {
        display: flex;
        align-items: center;
        gap: .6rem;
        background: var(--bg-3);
        border: 1px solid var(--border);
        border-radius: var(--r-m);
        padding: .38rem .9rem;
        font-size: .78rem;
        color: var(--text-3);
        min-width: 260px;
    }

    .bd-role-admin .bd-admin-search input {
        background: none;
        border: none;
        outline: none;
        color: var(--text);
        width: 100%;
        font-size: .78rem;
    }

    .bd-role-admin .bd-admin-icon-btn,
    .bd-role-admin .bd-admin-user-pill,
    .bd-role-restaurant .bd-admin-user-pill {
        background: var(--bg-3);
        border: 1px solid var(--border);
        border-radius: var(--r-m);
    }

    .bd-role-admin .bd-admin-icon-btn {
        width: 36px;
        height: 36px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        position: relative;
    }

    .bd-role-admin .bd-admin-icon-btn__dot {
        position: absolute;
        top: 7px;
        right: 7px;
        width: 7px;
        height: 7px;
        border-radius: 50%;
        background: var(--red);
        border: 1px solid var(--bg-2);
    }

    .bd-role-admin .bd-admin-user-pill,
    .bd-role-restaurant .bd-admin-user-pill {
        display: inline-flex;
        align-items: center;
        gap: .75rem;
        padding: .35rem .65rem;
    }

    .bd-role-admin .bd-admin-user-pill__avatar,
    .bd-role-restaurant .bd-admin-user-pill__avatar {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        object-fit: cover;
    }

    .bd-role-admin .bd-admin-user-pill__name,
    .bd-role-restaurant .bd-admin-user-pill__name {
        font-size: .8rem;
        font-weight: 700;
        color: var(--text);
        line-height: 1.2;
    }

    .bd-role-admin .bd-admin-user-pill__role,
    .bd-role-restaurant .bd-admin-user-pill__role {
        font-size: .68rem;
        color: var(--text-3);
    }
    .bd-role-restaurant .bd-admin-user-pill {
        background: linear-gradient(135deg, #fff7ed 0%, #ffffff 100%);
        border-color: rgba(251, 146, 60, .18);
    }
    .bd-role-restaurant .bd-admin-user-pill__avatar {
        border: 2px solid rgba(251, 146, 60, .18);
    }
    .bd-role-restaurant .bd-admin-topbar__title {
        color: #9a3412;
    }
    .bd-restaurant-brand {
        display: inline-grid;
        grid-template-columns: 54px minmax(0, 1fr);
        gap: .8rem;
        align-items: center;
        width: 100%;
    }
    .bd-restaurant-brand__mark {
        width: 54px;
        height: 54px;
        border-radius: 16px;
        overflow: hidden;
        background: linear-gradient(135deg, #fb923c, #ff5a1f);
        box-shadow: 0 16px 28px rgba(249, 115, 22, .22);
    }
    .bd-restaurant-brand__mark img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
    }
    .bd-restaurant-brand__copy {
        display: grid;
        gap: .12rem;
        min-width: 0;
    }
    .bd-restaurant-brand__name {
        color: #111827;
        font-weight: 800;
        line-height: 1.15;
        letter-spacing: -.02em;
    }
    .bd-restaurant-brand__badge {
        color: #9a3412;
        font-size: .68rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: .08em;
    }
    .bd-role-restaurant .sidebar {
        padding-bottom: 1.25rem;
    }
    .bd-role-restaurant .main-sidebar {
        background:
            radial-gradient(circle at top left, rgba(254, 215, 170, .18), transparent 26%),
            linear-gradient(180deg, #fffdfb 0%, #ffffff 100%) !important;
        border-right: 1px solid rgba(251, 146, 60, .14);
    }
    .bd-role-restaurant .main-sidebar .brand-image.p-0 {
        border-bottom: 1px solid rgba(251, 146, 60, .14);
        min-height: 94px;
    }

    .bd-role-admin .kpi-card {
        background: var(--bg-3);
        border: 1px solid var(--border);
        border-radius: var(--r-l);
        padding: 1.4rem 1.6rem;
        position: relative;
        overflow: hidden;
        transition: border-color .2s;
    }

    .bd-role-admin .kpi-card:hover {
        border-color: var(--border-2);
    }

    .bd-role-admin .kpi-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 2px;
        background: rgba(15,23,42,.08);
    }

    .bd-role-admin .kpi-card.green::before {
        background: rgba(15,23,42,.08);
    }

    .bd-role-admin .kpi-card.gold::before {
        background: rgba(15,23,42,.08);
    }

    .bd-role-admin .kpi-card.blue::before,
    .bd-role-admin .kpi-card.purple::before {
        background: rgba(15,23,42,.08);
    }

    .bd-role-admin .kpi-icon.green {
        background: rgba(15,23,42,.05);
    }

    .bd-role-admin .kpi-icon.gold {
        background: rgba(15,23,42,.05);
    }

    .bd-role-admin .kpi-icon.blue,
    .bd-role-admin .kpi-icon.purple {
        background: rgba(15,23,42,.05);
    }

    .bd-role-admin .status-pill.sp-transit,
    .bd-role-admin .service-tag.st-taxi {
        background: var(--amber-pale);
        color: var(--amber);
    }

    .bd-role-admin .status-pill.sp-transit::before {
        background: var(--amber);
    }

    .modal.right .modal-dialog {
        position: fixed;
        margin-top: 56px;
        width: 360px;
        height: 100%;
        transform: translate3d(0%, 0, 0);
    }

    .modal.right .modal-content {
        height: 100%;
        overflow-y: auto;
        border-radius: 24px 0 0 24px;
        border: 0;
    }

    .modal.right .modal-body {
        padding: 0;
    }

    .modal.right.fade .modal-dialog {
        right: 0;
        transition: opacity 0.3s linear, right 0.3s ease-out;
    }

    .bd-role-admin .modal-header {
        border-bottom-color: var(--border);
        background-color: var(--bg-3);
    }

    .bd-role-admin .badge-warning {
        background: var(--gold) !important;
        color: #fff !important;
    }

    .bd-role-admin .bd-cms-hero,
    .bd-role-admin .bd-admin-editor-hero,
    .bd-role-admin .bd-admin-dashboard__hero {
        background: var(--bg-3) !important;
        color: var(--text) !important;
        border: 1px solid var(--border) !important;
        border-radius: var(--r-l) !important;
        box-shadow: none !important;
    }

    .bd-role-admin .bd-cms-hero__eyebrow,
    .bd-role-admin .bd-admin-editor-hero__eyebrow,
    .bd-role-admin .bd-admin-dashboard__eyebrow {
        color: var(--green) !important;
    }

    .bd-role-admin .bd-cms-hero h1,
    .bd-role-admin .bd-admin-editor-hero h1,
    .bd-role-admin .bd-admin-dashboard__hero h1,
    .bd-role-admin .bd-cms-hero p,
    .bd-role-admin .bd-admin-editor-hero p,
    .bd-role-admin .bd-admin-dashboard__hero p,
    .bd-role-admin .bd-admin-editor-hero__badges span {
        color: var(--text) !important;
    }

    .bd-role-admin .bd-admin-editor-hero__badges span {
        background: var(--bg-4) !important;
        border: 1px solid var(--border) !important;
    }

    .bd-role-admin .bd-cms-table-card,
    .bd-role-admin .bd-admin-editor-card {
        border-radius: var(--r-l) !important;
    }

    .bd-role-admin .bg-gradient-primary,
    .bd-role-admin .bg-gradient-info,
    .bd-role-admin .bg-gradient-success,
    .bd-role-admin .bg-gradient-warning,
    .bd-role-admin .bg-gradient-danger,
    .bd-role-admin .bg-gradient-secondary,
    .bd-role-admin .bg-gradient-dark {
        background-image: none !important;
        box-shadow: none !important;
    }

    .bd-role-admin .bg-gradient-primary,
    .bd-role-admin .bg-gradient-success {
        background-color: var(--green) !important;
        color: #fff !important;
    }

    .bd-role-admin .bg-gradient-warning {
        background-color: var(--gold) !important;
        color: #fff !important;
    }

    .bd-role-admin .bg-gradient-danger {
        background-color: var(--red) !important;
        color: #fff !important;
    }

    .bd-role-admin .bg-gradient-info,
    .bd-role-admin .bg-gradient-secondary,
    .bd-role-admin .bg-gradient-dark {
        background-color: var(--bg-4) !important;
        color: var(--text) !important;
        border: 1px solid var(--border) !important;
    }

    .bd-role-admin .content-header [style*="linear-gradient"],
    .bd-role-admin .content [style*="linear-gradient"] {
        background: var(--bg-3) !important;
        background-image: none !important;
        color: var(--text) !important;
        border: 1px solid var(--border) !important;
        box-shadow: none !important;
    }

    .bd-role-admin .dt-buttons {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        margin-bottom: 16px;
    }

    .bd-role-admin button.dt-button,
    .bd-role-admin div.dt-button,
    .bd-role-admin a.dt-button {
        min-height: 40px;
        padding: 0 14px !important;
        border-radius: 10px !important;
        border: 1px solid var(--border) !important;
        background: var(--bg-4) !important;
        background-image: none !important;
        color: var(--text) !important;
        box-shadow: none !important;
    }

    .bd-role-admin button.dt-button:hover,
    .bd-role-admin div.dt-button:hover,
    .bd-role-admin a.dt-button:hover,
    .bd-role-admin button.dt-button:focus,
    .bd-role-admin div.dt-button:focus,
    .bd-role-admin a.dt-button:focus {
        background: var(--surface-2) !important;
        border-color: rgba(0,149,67,.35) !important;
        color: #fff !important;
    }

    .bd-role-admin .dataTables_wrapper .dataTables_length,
    .bd-role-admin .dataTables_wrapper .dataTables_filter,
    .bd-role-admin .dataTables_wrapper .dataTables_info,
    .bd-role-admin .dataTables_wrapper .dataTables_paginate {
        color: var(--text-2) !important;
    }

    .bd-role-admin .dataTables_wrapper .dataTables_filter input,
    .bd-role-admin .dataTables_wrapper .dataTables_length select {
        background: var(--bg-4) !important;
        color: var(--text) !important;
        border: 1px solid var(--border) !important;
        border-radius: 10px !important;
        min-height: 38px;
        padding: 6px 10px;
    }

    .bd-role-admin .dataTables_wrapper .dataTables_paginate .paginate_button {
        border-radius: 10px !important;
        border: 1px solid transparent !important;
        color: var(--text) !important;
    }

    .bd-role-admin .dataTables_wrapper .dataTables_paginate .paginate_button.current,
    .bd-role-admin .dataTables_wrapper .dataTables_paginate .paginate_button.current:hover {
        background: var(--green) !important;
        color: #fff !important;
        border-color: var(--green) !important;
    }

    .bd-role-admin .dataTables_wrapper .dataTables_paginate .paginate_button:hover {
        background: var(--bg-4) !important;
        color: #fff !important;
        border-color: var(--border) !important;
    }

    .bd-role-admin .bd-admin-subcard,
    .bd-role-admin .bd-admin-check-card,
    .bd-role-admin .bd-admin-revision-item,
    .bd-role-admin .bd-admin-seo-preview,
    .bd-role-admin .bd-cms-media-card {
        background: var(--bg-4) !important;
        border: 1px solid var(--border) !important;
        border-radius: var(--r-m) !important;
        box-shadow: none !important;
    }

    @media (max-width: 991.98px) {
        .bd-role-admin .content-wrapper,
        .bd-role-restaurant .content-wrapper,
        .bd-role-admin .main-footer {
            margin-left: 0 !important;
        }
        .bd-role-restaurant .main-footer {
            margin-left: 0 !important;
        }

        .content-wrapper {
            margin-left: 0 !important;
            padding: 12px 12px 24px !important;
        }

        .main-footer {
            margin-left: 0 !important;
        }

        .content-wrapper .card-body,
        .content-wrapper .card-header,
        .content-wrapper .card-footer {
            padding-left: 1rem !important;
            padding-right: 1rem !important;
        }
    }

    @media (max-width: 1199px) {
        .content-wrapper .container-fluid.main-content {
            padding-left: 0.5rem;
            padding-right: 0.5rem;
        }
    }

    /* ── Syne typography system ───────────────────────────────────── */
    .bd-role-admin h1,
    .bd-role-admin h2,
    .bd-role-admin h3,
    .bd-role-admin h4,
    .bd-role-admin h5 {
        font-family: var(--f-u) !important;
        letter-spacing: -.02em;
        font-weight: 700;
        color: var(--text);
    }

    .bd-role-admin .card-title,
    .bd-role-admin .card-header .card-title {
        font-family: var(--f-u) !important;
        font-weight: 700;
        letter-spacing: -.02em;
        font-size: .88rem !important;
        color: var(--text) !important;
    }

    .bd-role-admin .card-header {
        font-family: var(--f-u) !important;
    }

    .bd-role-admin .btn,
    .bd-role-admin button[type="submit"] {
        font-family: var(--f-u) !important;
        font-weight: 600;
        letter-spacing: -.01em;
    }

    .bd-role-admin .small-box h3,
    .bd-role-admin .small-box .inner h3 {
        font-family: var(--f-u) !important;
        font-weight: 800;
        letter-spacing: -.035em;
    }

    .bd-role-admin .small-box p,
    .bd-role-admin .small-box .inner p {
        font-family: var(--f-u) !important;
        font-weight: 600;
        font-size: .78rem;
        letter-spacing: .02em;
        text-transform: uppercase;
    }

    .bd-role-admin .small-box-footer {
        font-family: var(--f-u) !important;
        font-size: .76rem;
        font-weight: 600;
        letter-spacing: .01em;
    }

    .bd-role-admin .nav-sidebar .nav-link p,
    .bd-role-admin .nav-sidebar .nav-treeview .nav-link p {
        font-family: var(--f-u) !important;
        font-size: .82rem;
        font-weight: 500;
    }

    .bd-role-admin .user-panel .info a,
    .bd-role-admin .user-panel .info {
        font-family: var(--f-u) !important;
    }

    .bd-role-admin .breadcrumb,
    .bd-role-admin .breadcrumb-item,
    .bd-role-admin .breadcrumb-item a {
        font-family: var(--f-u) !important;
        font-size: .76rem;
        font-weight: 500;
    }

    .bd-role-admin label {
        font-family: var(--f-u) !important;
        font-weight: 600;
        font-size: .8rem;
        letter-spacing: -.01em;
    }

    .bd-role-admin .badge {
        font-family: var(--f-u) !important;
        font-weight: 700;
        letter-spacing: .02em;
    }

    .bd-role-admin .dropdown-item {
        font-family: var(--f-u) !important;
        font-size: .84rem;
        font-weight: 500;
    }

    .bd-role-admin .bd-partner-finance__label {
        font-family: var(--f-u) !important;
    }

    .bd-role-admin .bd-partner-finance__amount {
        font-family: var(--f-u) !important;
        font-weight: 800;
        letter-spacing: -.035em;
    }

    /* ══════════════════════════════════════════════════════
       KLEON DESIGN SYSTEM — Admin Shell Edition
       ══════════════════════════════════════════════════════ */

    /* ── Tokens Kleon ─────────────────────────────────────── */
    :root {
        --kl-sidebar-w: 210px;
        --kl-topbar-h: 60px;
        --kl-radius: 12px;
        --kl-radius-sm: 7px;
        --kl-radius-pill: 999px;
        --kl-shadow: 0 2px 12px rgba(15,23,42,.07);
        --kl-shadow-md: 0 4px 24px rgba(15,23,42,.10);
        --kl-shadow-lg: 0 8px 40px rgba(15,23,42,.13);
        --kl-green: #009543;
        --kl-green-2: #22c55e;
        --kl-green-pale: rgba(0,149,67,.10);
        --kl-green-mid: rgba(0,149,67,.18);
        --kl-surface: #ffffff;
        --kl-bg: #f4f6f8;
        --kl-border: rgba(15,23,42,.08);
        --kl-text: #1a2035;
        --kl-text-2: #64748b;
        --kl-text-3: #94a3b8;
    }

    /* ── Layout base ──────────────────────────────────────── */
    .bd-admin-body {
        background: var(--kl-bg) !important;
    }
    .bd-role-admin .content-wrapper,
    .bd-role-admin .main-footer {
        background: var(--kl-bg) !important;
    }

    /* ── Sidebar Kleon ────────────────────────────────────── */
    .bd-role-admin .main-sidebar {
        width: var(--kl-sidebar-w) !important;
        background: var(--kl-surface) !important;
        border-right: 1px solid var(--kl-border) !important;
        box-shadow: 2px 0 20px rgba(15,23,42,.04) !important;
    }
    .bd-role-admin .content-wrapper,
    .bd-role-admin .main-footer {
        margin-left: var(--kl-sidebar-w) !important;
    }

    /* Brand area */
    .bd-role-admin .main-sidebar .brand-image.p-0 {
        min-height: 56px;
        padding: 10px 12px !important;
        border-bottom: 1px solid var(--kl-border) !important;
        background: var(--kl-surface) !important;
    }
    .bd-role-admin .bd-admin-brand__dot {
        width: 32px; height: 32px; border-radius: 10px;
        background: linear-gradient(135deg, var(--kl-green), var(--kl-green-2));
        color: #fff; font-size: .9rem;
        box-shadow: 0 4px 12px rgba(0,149,67,.35);
    }
    .bd-role-admin .bd-admin-brand__name {
        font-size: .9rem; font-weight: 800; letter-spacing: -.025em;
        color: var(--kl-text);
    }
    .bd-role-admin .bd-admin-brand__badge {
        font-size: .52rem; font-weight: 800; letter-spacing: .1em;
        text-transform: uppercase;
        background: var(--kl-green-pale);
        color: var(--kl-green);
        padding: 2px 6px; border-radius: 4px;
    }

    /* Restaurant/Driver brand */
    .bd-restaurant-brand {
        display: flex; align-items: center; gap: .7rem;
        min-height: 72px; padding: 14px 20px;
    }
    .bd-restaurant-brand__mark {
        width: 40px; height: 40px; border-radius: 12px;
        overflow: hidden; flex-shrink: 0;
        border: 1.5px solid var(--kl-border);
    }
    .bd-restaurant-brand__mark img {
        width: 100%; height: 100%; object-fit: cover;
    }
    .bd-restaurant-brand__name {
        font-family: var(--f-u); font-weight: 800; font-size: .9rem;
        color: var(--kl-text); line-height: 1.2; letter-spacing: -.02em;
    }
    .bd-restaurant-brand__badge {
        font-size: .6rem; font-weight: 700; letter-spacing: .08em;
        text-transform: uppercase; color: var(--kl-green);
    }

    /* User panel */
    .bd-role-admin .user-panel {
        border-bottom: 1px solid var(--kl-border) !important;
        margin: 0 !important; padding: 14px 20px !important;
        background: var(--kl-bg) !important;
    }
    .bd-role-admin .user-panel .image img {
        width: 36px; height: 36px; border-radius: 10px;
        border: 2px solid var(--kl-border); object-fit: cover;
    }
    .bd-role-admin .user-panel .info a {
        font-size: .82rem; font-weight: 700; color: var(--kl-text) !important;
    }

    /* Sidebar group labels */
    .bd-role-admin .bd-sidebar-group {
        font-size: .6rem; font-weight: 700; letter-spacing: .18em;
        text-transform: uppercase; color: var(--kl-text-3);
        padding: 11px 14px 4px;
    }

    /* Nav links — Kleon style */
    .bd-role-admin .nav-sidebar .nav-link,
    .bd-role-admin .nav-sidebar .nav-treeview .nav-link,
    .bd-role-admin .sidebar .nav-link,
    .bd-role-admin .nav-item .nav-link {
        font-family: var(--f-u) !important;
        display: flex; align-items: center; gap: .65rem;
        border-radius: var(--kl-radius-sm);
        color: var(--kl-text-2) !important;
        margin: 1px 6px;
        padding: .44rem .68rem;
        font-size: .74rem; font-weight: 500;
        background: transparent !important;
        position: relative;
        transition: background .15s, color .15s;
        border-left: 3px solid transparent;
    }
    .bd-role-admin .nav-sidebar .nav-link:hover {
        background: var(--kl-bg) !important;
        color: var(--kl-text) !important;
        border-left-color: transparent !important;
    }
    .bd-role-admin .nav-sidebar .nav-link.active {
        background: rgba(15,23,42,.05) !important;
        color: var(--kl-text) !important;
        font-weight: 700 !important;
        border-left-color: rgba(15,23,42,.18) !important;
    }
    .bd-role-admin .nav-sidebar .nav-link .nav-icon,
    .bd-role-admin .nav-sidebar .nav-link .fas,
    .bd-role-admin .nav-sidebar .nav-link .far {
        width: 18px; text-align: center;
        color: currentColor !important; opacity: .9;
    }
    .bd-role-admin .nav-sidebar .nav-treeview {
        padding-left: .2rem; margin-top: .05rem;
    }
    .bd-role-admin .nav-sidebar .nav-treeview .nav-link {
        font-size: .7rem; color: var(--kl-text-3) !important;
        border-left-color: transparent !important;
    }
    .bd-role-admin .nav-sidebar .nav-treeview .nav-link.active {
        color: var(--kl-text) !important;
        border-left-color: rgba(15,23,42,.18) !important;
        background: rgba(15,23,42,.05) !important;
    }

    /* Badge nav */
    .bd-role-admin .bd-nav-badge {
        margin-left: auto; font-size: .6rem; font-weight: 800;
        min-width: 17px; height: 17px; border-radius: 9px;
        display: inline-flex; align-items: center; justify-content: center;
        padding: 0 4px;
        background: rgba(15,23,42,.06); color: #475467;
    }

    /* Sidebar promo card (Kleon "upgrade" card) */
    .bd-kl-promo {
        margin: 14px 12px 12px;
        background: linear-gradient(135deg, #009543 0%, #0d9948 60%, #007836 100%);
        border-radius: var(--kl-radius);
        padding: 14px 14px;
        color: #fff;
        box-shadow: 0 8px 24px rgba(0,149,67,.30);
    }
    .bd-kl-promo__icon {
        width: 36px; height: 36px; border-radius: 10px;
        background: rgba(255,255,255,.2);
        display: flex; align-items: center; justify-content: center;
        font-size: 1rem; margin-bottom: 10px;
    }
    .bd-kl-promo__title {
        font-family: var(--f-u); font-weight: 800; font-size: .9rem;
        line-height: 1.3; margin-bottom: 6px;
    }
    .bd-kl-promo__sub {
        font-size: .74rem; opacity: .8; line-height: 1.5; margin-bottom: 12px;
    }
    .bd-kl-promo__link {
        display: inline-flex; align-items: center; gap: 6px;
        font-size: .76rem; font-weight: 700;
        background: rgba(255,255,255,.22);
        border: 1px solid rgba(255,255,255,.3);
        color: #fff; padding: 6px 14px; border-radius: var(--kl-radius-pill);
        text-decoration: none; transition: background .15s;
    }
    .bd-kl-promo__link:hover { background: rgba(255,255,255,.32); color: #fff; }

    /* ── Topbar Kleon ─────────────────────────────────────── */
    .bd-role-admin .bd-admin-topbar {
        height: var(--kl-topbar-h) !important;
        background: var(--kl-surface) !important;
        border-bottom: 1px solid var(--kl-border) !important;
        box-shadow: 0 1px 8px rgba(15,23,42,.05) !important;
        padding: 0 14px !important;
    }
    .bd-role-admin .bd-admin-topbar__meta {
        gap: .5rem;
    }
    .bd-role-admin .bd-admin-topbar__title {
        font-size: .9rem; font-weight: 700; color: var(--kl-text);
        letter-spacing: -.02em;
    }
    .bd-role-admin .bd-admin-topbar__date {
        font-size: .72rem; color: var(--kl-text-3);
    }

    /* Kleon search bar */
    .bd-kl-search {
        display: flex; align-items: center; gap: 8px;
        background: var(--kl-bg);
        border: 1.5px solid var(--kl-border);
        border-radius: var(--kl-radius-pill);
        padding: 0 16px; height: 40px;
        min-width: 260px; max-width: 380px;
        transition: border-color .2s, box-shadow .2s;
    }
    .bd-kl-search:focus-within {
        border-color: rgba(0,149,67,.35);
        box-shadow: 0 0 0 3px rgba(0,149,67,.08);
        background: #fff;
    }
    .bd-kl-search i { color: var(--kl-text-3); font-size: .8rem; }
    .bd-kl-search input {
        border: none; background: transparent; outline: none;
        font-family: var(--f-b); font-size: .84rem; color: var(--kl-text);
        width: 100%;
    }
    .bd-kl-search input::placeholder { color: var(--kl-text-3); }

    /* Topbar icon buttons */
    .bd-admin-icon-btn {
        width: 38px; height: 38px; border-radius: 10px;
        display: inline-flex; align-items: center; justify-content: center;
        background: var(--kl-bg) !important;
        border: 1px solid var(--kl-border) !important;
        color: var(--kl-text-2) !important;
        position: relative; transition: background .15s;
    }
    .bd-admin-icon-btn:hover { background: var(--kl-green-pale) !important; color: var(--kl-green) !important; }
    .bd-admin-icon-btn__dot {
        position: absolute; top: 6px; right: 6px;
        width: 7px; height: 7px; border-radius: 50%;
        background: #ef4444; border: 1.5px solid #fff;
    }

    /* User pill */
    .bd-admin-user-pill {
        display: flex; align-items: center; gap: 9px;
        padding: 5px 12px 5px 5px;
        background: var(--kl-bg);
        border: 1px solid var(--kl-border);
        border-radius: var(--kl-radius-pill);
        cursor: pointer; transition: background .15s;
    }
    .bd-admin-user-pill:hover { background: var(--kl-green-pale); }
    .bd-admin-user-pill__avatar {
        width: 30px; height: 30px; border-radius: 50%;
        object-fit: cover; border: 2px solid var(--kl-border);
    }
    .bd-admin-user-pill__name {
        font-family: var(--f-u); font-size: .8rem; font-weight: 700;
        color: var(--kl-text); line-height: 1.2;
    }
    .bd-admin-user-pill__role {
        font-size: .68rem; color: var(--kl-text-3);
    }

    .bd-admin-workspaces {
        display: flex;
        align-items: center;
        gap: .85rem;
        min-width: 0;
        margin-left: 1rem;
    }
    .bd-admin-workspaces__label {
        flex-shrink: 0;
        font-size: .68rem;
        font-weight: 700;
        letter-spacing: .16em;
        text-transform: uppercase;
        color: var(--kl-text-3);
    }
    .bd-admin-workspaces__list {
        display: flex;
        align-items: center;
        gap: .5rem;
        overflow-x: auto;
        padding-bottom: 2px;
    }
    .bd-admin-workspaces__list::-webkit-scrollbar {
        display: none;
    }
    .bd-admin-workspaces__link {
        display: inline-flex;
        align-items: center;
        gap: .45rem;
        padding: .4rem .72rem;
        border-radius: var(--kl-radius-pill);
        border: 1px solid var(--kl-border);
        background: var(--kl-bg);
        color: var(--kl-text-2);
        text-decoration: none;
        white-space: nowrap;
        font-family: var(--f-u);
        font-size: .74rem;
        font-weight: 700;
        transition: background .15s, color .15s, border-color .15s, box-shadow .15s;
    }
    .bd-admin-workspaces__link:hover {
        color: var(--kl-text);
        background: #fff;
        border-color: rgba(0,149,67,.22);
    }
    .bd-admin-workspaces__link.is-active {
        background: var(--kl-green-pale);
        border-color: rgba(0,149,67,.22);
        color: var(--kl-green);
        box-shadow: 0 4px 16px rgba(0,149,67,.12);
    }
    .bd-admin-workspaces__link i {
        font-size: .8rem;
    }
    .bd-role-admin .bd-ecosystem-link {
        margin-top: .15rem;
        font-weight: 800 !important;
    }
    .bd-role-admin .bd-ecosystem-link p {
        display: flex;
        align-items: center;
        gap: .45rem;
    }
    .bd-role-admin .bd-ecosystem-link .menu-title {
        flex: 1;
        min-width: 0;
    }
    .bd-role-admin .bd-ecosystem-link .menu-meta {
        display: inline-flex;
        align-items: center;
        min-height: 20px;
        padding: 0 6px;
        border-radius: 999px;
        background: rgba(15,23,42,.06);
        color: var(--text-3);
        font-size: .58rem;
        font-weight: 800;
        letter-spacing: .08em;
        text-transform: uppercase;
        white-space: nowrap;
    }
    .bd-role-admin .bd-ecosystem-link.active {
        background: linear-gradient(135deg, rgba(0,149,67,.14) 0%, rgba(255,255,255,.96) 100%) !important;
        color: var(--green) !important;
        box-shadow: inset 0 0 0 1px rgba(0,149,67,.12);
    }
    .bd-role-admin .bd-ecosystem-link .right {
        top: 50% !important;
        transform: translateY(-50%);
    }
    .bd-role-admin .bd-sidebar-group--nested {
        padding-top: .7rem;
        padding-bottom: .25rem;
        padding-left: 1.55rem;
        font-size: .54rem;
    }

    /* ── Cards Kleon ──────────────────────────────────────── */
    .bd-role-admin .card {
        background: var(--kl-surface) !important;
        border: 1px solid var(--kl-border) !important;
        border-radius: var(--kl-radius) !important;
        box-shadow: var(--kl-shadow) !important;
    }
    .bd-role-admin .card-header {
        border-bottom: 1px solid var(--kl-border) !important;
        background: transparent !important;
        padding: 12px 16px !important;
    }
    .bd-role-admin .card-body { padding: 14px 16px !important; }
    .bd-role-admin .card-footer {
        background: var(--kl-bg) !important;
        border-top: 1px solid var(--kl-border) !important;
        padding: 10px 16px !important;
    }

    /* ── Small stat boxes Kleon ───────────────────────────── */
    .bd-role-admin .small-box {
        border-radius: var(--kl-radius) !important;
        box-shadow: var(--kl-shadow) !important;
        border: 1px solid var(--kl-border) !important;
        overflow: hidden;
    }
    .bd-role-admin .small-box .inner { padding: 18px 20px 12px; }
    .bd-role-admin .small-box h3 {
        font-size: 2rem !important; margin-bottom: 2px;
    }
    .bd-role-admin .small-box p {
        font-size: .72rem !important; margin: 0;
    }
    .bd-role-admin .small-box-footer {
        padding: 8px 14px !important; font-size: .76rem !important;
    }
    .bd-role-admin .small-box .icon i {
        font-size: 3.5rem !important; top: 10px !important; right: 10px !important;
    }

    /* ── Tables Kleon ─────────────────────────────────────── */
    .bd-role-admin .table thead th {
        background: var(--kl-bg) !important;
        border-bottom: 1px solid var(--kl-border) !important;
        border-top: none !important;
        color: var(--kl-text-3) !important;
        font-size: .65rem !important;
        text-transform: uppercase; letter-spacing: .12em; font-weight: 800;
        padding: 8px 10px !important;
        white-space: normal !important;
        line-height: 1.35;
    }
    .bd-role-admin .table td {
        border-top: 1px solid var(--kl-border) !important;
        color: var(--kl-text-2) !important;
        padding: 9px 10px !important;
        vertical-align: middle !important;
        white-space: normal !important;
        word-break: break-word;
        line-height: 1.45;
    }
    .bd-role-admin .table tbody tr:hover td {
        background: var(--kl-bg) !important;
    }

    /* ── Status badges Kleon ──────────────────────────────── */
    .bd-role-admin .badge-success, .bd-role-admin .badge-pill.badge-success,
    .bd-role-admin .status-pill.sp-delivered {
        background: #dcfce7 !important; color: #009543 !important;
        border-radius: var(--kl-radius-pill) !important;
        padding: 4px 10px !important; font-size: .7rem !important; font-weight: 700 !important;
    }
    .bd-role-admin .badge-warning, .bd-role-admin .badge-pill.badge-warning,
    .bd-role-admin .status-pill.sp-pending {
        background: #fef9c3 !important; color: #b45309 !important;
        border-radius: var(--kl-radius-pill) !important;
        padding: 4px 10px !important; font-size: .7rem !important; font-weight: 700 !important;
    }
    .bd-role-admin .badge-danger, .bd-role-admin .badge-pill.badge-danger,
    .bd-role-admin .status-pill.sp-cancelled {
        background: #fee2e2 !important; color: #dc2626 !important;
        border-radius: var(--kl-radius-pill) !important;
        padding: 4px 10px !important; font-size: .7rem !important; font-weight: 700 !important;
    }
    .bd-role-admin .badge-info, .bd-role-admin .badge-pill.badge-info,
    .bd-role-admin .status-pill.sp-transit {
        background: #dbeafe !important; color: #1d4ed8 !important;
        border-radius: var(--kl-radius-pill) !important;
        padding: 4px 10px !important; font-size: .7rem !important; font-weight: 700 !important;
    }
    .bd-role-admin .badge-primary, .bd-role-admin .badge-pill.badge-primary {
        background: var(--kl-green-pale) !important; color: var(--kl-green) !important;
        border-radius: var(--kl-radius-pill) !important;
        padding: 4px 10px !important; font-size: .7rem !important; font-weight: 700 !important;
    }

    /* ── Buttons Kleon ────────────────────────────────────── */
    .bd-role-admin .btn-primary {
        background: linear-gradient(135deg, var(--kl-green), #007836) !important;
        border: none !important; color: #fff !important;
        border-radius: var(--kl-radius-sm) !important;
        font-weight: 700 !important; padding: 8px 18px !important;
        box-shadow: 0 4px 12px rgba(0,149,67,.30) !important;
        transition: box-shadow .2s, transform .1s !important;
    }
    .bd-role-admin .btn-primary:hover {
        box-shadow: 0 6px 18px rgba(0,149,67,.40) !important;
        transform: translateY(-1px) !important;
    }
    .bd-role-admin .btn-secondary, .bd-role-admin .btn-light,
    .bd-role-admin .btn-outline-primary, .bd-role-admin .btn-outline-secondary {
        background: var(--kl-surface) !important;
        border: 1px solid var(--kl-border) !important;
        color: var(--kl-text) !important;
        border-radius: var(--kl-radius-sm) !important;
        font-weight: 600 !important;
    }
    .bd-role-admin .btn-secondary:hover, .bd-role-admin .btn-light:hover {
        background: var(--kl-bg) !important;
        border-color: rgba(0,149,67,.3) !important;
    }

    /* ── Forms Kleon ──────────────────────────────────────── */
    .bd-role-admin .form-control,
    .bd-role-admin .custom-select,
    .bd-role-admin select,
    .bd-role-admin textarea {
        min-height: 42px !important;
        border-radius: var(--kl-radius-sm) !important;
        border: 1.5px solid var(--kl-border) !important;
        background: var(--kl-surface) !important;
        color: var(--kl-text) !important;
        font-size: .88rem !important;
        padding: 8px 14px !important;
        box-shadow: none !important;
        transition: border-color .2s, box-shadow .2s !important;
    }
    .bd-role-admin .form-control:focus,
    .bd-role-admin .custom-select:focus {
        border-color: rgba(0,149,67,.4) !important;
        box-shadow: 0 0 0 3px rgba(0,149,67,.10) !important;
        background: #fff !important;
    }

    /* ── Page header Kleon ────────────────────────────────── */
    .bd-role-admin .content-header {
        background: var(--kl-surface);
        border-bottom: 1px solid var(--kl-border);
        margin: -1.5rem -1.5rem 1.5rem !important;
        padding: 12px 18px !important;
        display: flex; align-items: center;
        justify-content: space-between;
    }
    .bd-role-admin .content-header h1,
    .bd-role-admin .content-header .m-0 {
        font-size: 1.2rem !important; font-weight: 700 !important;
        letter-spacing: -.025em !important; color: var(--kl-text) !important;
    }
    .bd-role-admin .content-header .breadcrumb {
        background: transparent !important; margin: 0 !important; padding: 0 !important;
        font-size: .74rem !important; color: var(--kl-text-3) !important;
    }
    .bd-role-admin .content-header .breadcrumb-item + .breadcrumb-item::before {
        color: var(--kl-text-3) !important;
    }
    .bd-role-admin .content-header .breadcrumb-item a {
        color: var(--kl-green) !important;
    }
    .bd-role-admin .content-header .breadcrumb-item.active {
        color: var(--kl-text-3) !important;
    }

    /* ── Dropdown Kleon ───────────────────────────────────── */
    .bd-role-admin .dropdown-menu {
        border: 1px solid var(--kl-border) !important;
        border-radius: var(--kl-radius) !important;
        box-shadow: var(--kl-shadow-lg) !important;
        padding: 6px !important;
    }
    .bd-role-admin .dropdown-item {
        border-radius: var(--kl-radius-sm) !important;
        font-size: .84rem !important; padding: 8px 14px !important;
        color: var(--kl-text-2) !important;
    }
    .bd-role-admin .dropdown-item:hover {
        background: var(--kl-green-pale) !important; color: var(--kl-green) !important;
    }
    .bd-role-admin .dropdown-divider {
        border-color: var(--kl-border) !important; margin: 4px 0 !important;
    }

    /* ── Pagination Kleon ─────────────────────────────────── */
    .bd-role-admin .page-link {
        border: 1px solid var(--kl-border) !important;
        color: var(--kl-text-2) !important;
        border-radius: var(--kl-radius-sm) !important;
        margin: 0 2px !important; min-width: 36px;
        text-align: center; font-weight: 600;
        background: var(--kl-surface) !important;
    }
    .bd-role-admin .page-item.active .page-link {
        background: linear-gradient(135deg, var(--kl-green), #007836) !important;
        border-color: var(--kl-green) !important;
        color: #fff !important;
        box-shadow: 0 4px 10px rgba(0,149,67,.30) !important;
    }
    .bd-role-admin .page-link:hover {
        background: var(--kl-green-pale) !important;
        border-color: rgba(0,149,67,.3) !important;
        color: var(--kl-green) !important;
    }

    /* ── DataTables Kleon ─────────────────────────────────── */
    .bd-role-admin .dataTables_wrapper .dataTables_filter input,
    .bd-role-admin .dataTables_wrapper .dataTables_length select {
        border: 1.5px solid var(--kl-border) !important;
        border-radius: var(--kl-radius-sm) !important;
        background: var(--kl-surface) !important;
        color: var(--kl-text) !important;
        min-height: 38px; padding: 5px 10px;
    }
    .bd-role-admin .dataTables_wrapper .dataTables_paginate .paginate_button {
        border-radius: var(--kl-radius-sm) !important;
        border: 1px solid var(--kl-border) !important;
        color: var(--kl-text) !important;
        margin: 0 2px !important; font-weight: 600;
    }
    .bd-role-admin .dataTables_wrapper .dataTables_paginate .paginate_button.current,
    .bd-role-admin .dataTables_wrapper .dataTables_paginate .paginate_button.current:hover {
        background: var(--kl-green) !important;
        color: #fff !important; border-color: var(--kl-green) !important;
        box-shadow: 0 4px 10px rgba(0,149,67,.30) !important;
    }
    .bd-role-admin .dataTables_wrapper .dataTables_paginate .paginate_button:hover {
        background: var(--kl-green-pale) !important;
        border-color: rgba(0,149,67,.3) !important; color: var(--kl-green) !important;
    }
    button.dt-button, div.dt-button, a.dt-button {
        border-radius: var(--kl-radius-sm) !important;
        border: 1px solid var(--kl-border) !important;
        background: var(--kl-surface) !important;
        color: var(--kl-text) !important;
        font-family: var(--f-u) !important;
        font-weight: 600 !important; font-size: .82rem !important;
    }

    /* ── Finance cards Kleon ──────────────────────────────── */
    .bd-partner-finance__card {
        border-radius: var(--kl-radius) !important;
        box-shadow: var(--kl-shadow) !important;
        border: 1px solid var(--kl-border) !important;
    }

    /* ── Modals Kleon ─────────────────────────────────────── */
    .bd-role-admin .modal-content {
        border-radius: var(--kl-radius) !important;
        border: none !important;
        box-shadow: var(--kl-shadow-lg) !important;
    }
    .bd-role-admin .modal-header {
        border-bottom: 1px solid var(--kl-border) !important;
        padding: 16px 20px !important;
    }
    .bd-role-admin .modal-footer {
        border-top: 1px solid var(--kl-border) !important;
        padding: 12px 20px !important;
    }

    /* ── Alerts Kleon ─────────────────────────────────────── */
    .bd-role-admin .alert {
        border-radius: var(--kl-radius-sm) !important;
        border: none !important; font-size: .875rem !important;
    }
    .bd-role-admin .alert-success {
        background: #dcfce7 !important; color: #007836 !important;
    }
    .bd-role-admin .alert-warning {
        background: #fef9c3 !important; color: #854d0e !important;
    }
    .bd-role-admin .alert-danger {
        background: #fee2e2 !important; color: #991b1b !important;
    }
    .bd-role-admin .alert-info {
        background: #dbeafe !important; color: #1e40af !important;
    }

    /* ── Admin search bar ─────────────────────────────────── */
    .bd-admin-search {
        display: flex; align-items: center; gap: 8px;
        background: var(--kl-bg);
        border: 1.5px solid var(--kl-border);
        border-radius: var(--kl-radius-pill);
        padding: 0 14px; height: 36px; min-width: 220px;
        transition: border-color .2s, box-shadow .2s;
    }
    .bd-admin-search:focus-within {
        border-color: rgba(0,149,67,.35);
        box-shadow: 0 0 0 3px rgba(0,149,67,.08);
        background: #fff;
    }
    .bd-admin-search i { color: var(--kl-text-3); font-size: .8rem; }
    .bd-admin-search input {
        border: none; background: transparent; outline: none;
        font-size: .84rem; color: var(--kl-text); width: 100%;
    }
    .bd-admin-search input::placeholder { color: var(--kl-text-3); }

    /* ── Period switcher ──────────────────────────────────── */
    .bd-admin-periods {
        display: inline-flex;
        background: var(--kl-bg) !important;
        border: 1px solid var(--kl-border) !important;
        border-radius: var(--kl-radius-pill) !important;
        overflow: hidden; padding: 3px;
    }
    .bd-admin-periods a, .bd-admin-periods button {
        padding: 4px 11px !important; font-size: .72rem !important;
        font-weight: 600 !important; border-radius: var(--kl-radius-pill) !important;
        color: var(--kl-text-3) !important; background: transparent !important;
        border: none !important; text-decoration: none !important;
        transition: background .15s, color .15s !important;
    }
    .bd-admin-periods a.active, .bd-admin-periods a:hover {
        background: rgba(15,23,42,.08) !important;
        color: var(--kl-text) !important;
    }

    /* ── Content wrapper padding ──────────────────────────── */
    .bd-role-admin .content-wrapper {
        padding: 1rem !important;
    }
    .bd-role-admin .content { padding: 0 !important; }

    /* ── Admin shell override: green refit aligned with home ─────── */
    .bd-role-admin,
    .bd-role-admin .content-wrapper,
    .bd-role-admin .main-footer {
        background:
            radial-gradient(circle at top left, rgba(0,149,67,.08), transparent 24%),
            radial-gradient(circle at top right, rgba(251,191,36,.07), transparent 18%),
            linear-gradient(180deg, #f6f4ed 0%, #fbfaf6 40%, #f3f7f2 100%) !important;
    }

    .bd-role-admin .main-sidebar {
        position: fixed !important;
        top: 0;
        left: 0;
        bottom: 0;
        width: 248px !important;
        overflow-y: auto;
        overflow-x: hidden;
        z-index: 1040;
        padding: 14px 10px 12px !important;
        border-right: 0 !important;
        background:
            radial-gradient(circle at top right, rgba(255,255,255,.08), transparent 22%),
            linear-gradient(180deg, #08160e 0%, #0d2d19 44%, #114e2e 100%) !important;
        box-shadow: 22px 0 42px rgba(15,23,42,.12) !important;
    }

    .bd-role-admin .main-sidebar .brand-image.p-0 {
        min-height: auto;
        display: flex !important;
        align-items: center;
        gap: 12px;
        margin-bottom: 16px !important;
        padding: 12px 14px !important;
        border: 1px solid rgba(255,255,255,.08) !important;
        border-radius: 18px;
        background: rgba(255,255,255,.06) !important;
        box-shadow: inset 0 1px 0 rgba(255,255,255,.04);
    }

    .bd-role-admin .sidebar {
        padding: 0 0 1rem;
    }

    .bd-role-admin .bd-admin-brand__dot {
        width: 38px;
        height: 38px;
        border-radius: 14px;
        background: #f6f4ed;
        color: #0d2d19;
        box-shadow: none;
        font-family: var(--f-d);
        font-size: .95rem;
        font-weight: 800;
    }

    .bd-role-admin .bd-admin-brand__stack {
        gap: 2px;
    }

    .bd-role-admin .bd-admin-brand__name {
        font-size: .94rem;
        font-weight: 800;
        letter-spacing: -.03em;
        line-height: 1.1;
        color: #f8fafc;
    }

    .bd-role-admin .bd-admin-brand__meta {
        display: block;
        color: rgba(226,232,240,.72);
        font-size: .68rem;
        font-weight: 600;
    }

    .bd-role-admin .bd-admin-brand__badge,
    .bd-role-admin .bd-admin-shell-card,
    .bd-role-admin .bd-kl-promo,
    .bd-role-admin .bd-admin-topbar__meta,
    .bd-role-admin .nav-sidebar {
        display: none !important;
    }

    .bd-role-admin .bd-admin-topbar {
        height: auto;
        margin: 18px 22px 14px 270px;
        padding: 0 !important;
        border: 0;
        background: transparent !important;
        box-shadow: none;
        z-index: 60;
    }

    .bd-role-admin .bd-admin-topbar__shell {
        display: flex;
        align-items: center;
        gap: 10px;
        width: 100%;
        padding: 10px 12px;
        border-radius: 20px;
        border: 1px solid rgba(15,23,42,.08);
        background: #fcfaf6;
        backdrop-filter: none;
        box-shadow: 0 18px 36px rgba(15,23,42,.1);
    }

    .bd-role-admin .bd-admin-topbar__left,
    .bd-role-admin .bd-admin-topbar__hub,
    .bd-role-admin .bd-admin-topbar__right {
        display: flex;
        align-items: center;
        gap: 10px;
        min-width: 0;
    }

    .bd-role-admin .bd-admin-topbar__left {
        flex: 0 1 340px;
        min-width: 200px;
    }

    .bd-role-admin .bd-admin-topbar__right {
        margin-left: auto;
        justify-content: flex-end;
        flex-wrap: nowrap;
        flex: 0 0 auto;
    }

    .bd-role-admin .bd-admin-topbar__hub {
        flex: 1 1 auto;
        overflow: hidden;
    }

    .bd-role-admin .bd-admin-topbar__context,
    .bd-role-admin .bd-admin-topbar__account {
        display: flex;
        align-items: center;
        gap: 10px;
        min-width: 0;
    }

    .bd-role-admin .bd-admin-topbar__context {
        justify-content: flex-end;
        flex-wrap: nowrap;
    }

    .bd-role-admin .bd-admin-topbar__account {
        flex-wrap: nowrap;
    }

    .bd-role-admin .bd-admin-topbar__toggle {
        width: 42px;
        height: 42px;
        border-radius: 14px;
        border: 1px solid rgba(15,23,42,.08);
        background: rgba(255,255,255,.72);
        color: #0f172a !important;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        text-decoration: none !important;
    }

    .bd-role-admin .bd-admin-search,
    .bd-role-admin .bd-admin-chip,
    .bd-role-admin .bd-admin-icon-btn,
    .bd-role-admin .bd-admin-user-pill {
        min-height: 42px;
        border-radius: 14px !important;
        border: 1px solid rgba(15,23,42,.08) !important;
        background: rgba(255,255,255,.76) !important;
        box-shadow: none !important;
    }

    .bd-role-admin .bd-admin-search {
        width: min(300px, 100%);
        max-width: 100%;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 0 12px;
        color: #64748b;
    }

    .bd-role-admin .bd-admin-search:focus-within {
        border-color: rgba(0,149,67,.22) !important;
        box-shadow: 0 0 0 4px rgba(0,149,67,.08) !important;
        background: #ffffff !important;
    }

    .bd-role-admin .bd-admin-search i {
        color: #64748b;
    }

    .bd-role-admin .bd-admin-search input {
        min-width: 0;
        width: 100%;
        font-size: .78rem;
        font-weight: 500;
    }

    .bd-role-admin .bd-admin-chip {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        padding: 0 10px;
        color: #0f172a;
        white-space: nowrap;
    }

    .bd-role-admin .bd-admin-chip strong {
        font-size: .62rem;
        color: #64748b;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: .08em;
    }

    .bd-role-admin .bd-admin-chip span {
        font-size: .72rem;
        font-weight: 700;
        color: #0f172a;
    }

    .bd-role-admin .bd-admin-periods {
        display: inline-flex;
        gap: 6px;
        padding: 4px;
        border-radius: 999px;
        background: rgba(255,255,255,.76);
        border: 1px solid rgba(15,23,42,.08);
    }

    .bd-role-admin .bd-admin-periods a,
    .bd-role-admin .bd-admin-periods button {
        min-height: 30px;
        padding: 0 9px;
        border-radius: 999px;
        font-size: .68rem;
        font-weight: 800;
        color: #475569;
    }

    .bd-role-admin .bd-admin-periods .active {
        background: #0d2d19 !important;
        color: #f8fafc !important;
    }

    .bd-role-admin .bd-admin-icon-btn {
        width: 42px;
        justify-content: center;
        color: #0f172a !important;
    }

    .bd-role-admin .bd-admin-user-pill {
        min-width: 0;
        max-width: min(100%, 210px);
        padding: 6px 10px 6px 6px;
        gap: 7px;
    }

    .bd-role-admin .bd-admin-user-pill__avatar {
        width: 28px;
        height: 28px;
        border-radius: 10px;
        border-width: 0;
        background: linear-gradient(135deg, #e8f7ef, #f5efe1);
    }

    .bd-role-admin .bd-admin-user-pill__name {
        max-width: 140px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        font-size: .72rem;
        font-weight: 800;
        color: #0f172a;
    }

    .bd-role-admin .bd-admin-user-pill__role {
        font-size: .58rem;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: .06em;
    }

    @media (max-width: 1400px) {
        .bd-role-admin .bd-admin-topbar__right {
            justify-content: flex-end;
            flex-wrap: nowrap;
        }
    }

    @media (max-width: 1180px) {
        .bd-role-admin .bd-admin-topbar__shell { gap: 8px; }

        .bd-role-admin .bd-admin-topbar__right {
            width: auto;
            justify-content: flex-end;
            flex-wrap: nowrap;
        }

        .bd-role-admin .bd-admin-topbar__context,
        .bd-role-admin .bd-admin-topbar__account {
            width: auto;
            justify-content: flex-end;
            flex-wrap: nowrap;
        }

        .bd-role-admin .bd-admin-periods {
            flex-wrap: nowrap;
        }

        .bd-role-admin .bd-admin-user-pill {
            max-width: 100%;
        }
    }

    @media (max-width: 980px) {
        .bd-role-admin .bd-admin-topbar__shell {
            display: grid;
            grid-template-columns: 1fr;
        }

        .bd-role-admin .bd-admin-topbar__right,
        .bd-role-admin .bd-admin-topbar__context,
        .bd-role-admin .bd-admin-topbar__account {
            width: 100%;
            justify-content: flex-start;
            flex-wrap: wrap;
        }

        .bd-role-admin .bd-admin-periods {
            flex-wrap: wrap;
        }
    }

    .bd-role-admin .content-wrapper {
        margin-left: 248px !important;
        padding: .25rem 1.4rem 2rem !important;
    }

    .bd-role-admin .content-header {
        margin: .1rem 0 1rem !important;
        padding: 0 !important;
        background: transparent !important;
        border: 0 !important;
    }

    .bd-role-admin .content-header h1,
    .bd-role-admin .content-header .m-0 {
        font-size: 1.38rem !important;
        font-weight: 800 !important;
        letter-spacing: -.04em !important;
    }

    .bd-role-admin .bd-sidebar-group {
        display: none;
    }

    .bd-role-admin .bd-ovh-sidebar {
        display: grid;
        gap: 10px;
        padding: 2px 2px 0;
    }

    .bd-role-admin .bd-ovh-sidebar__title {
        padding: 0 10px 2px;
        color: rgba(248,250,252,.92);
        font-size: .84rem;
        font-weight: 800;
    }

    .bd-role-admin .bd-ovh-sidebar__group {
        padding: 8px 10px 2px;
        color: rgba(226,232,240,.42);
        font-size: .58rem;
        font-weight: 800;
        letter-spacing: .1em;
        text-transform: uppercase;
    }

    .bd-role-admin .bd-ovh-sidebar__section,
    .bd-role-admin .bd-ovh-sidebar__section-links,
    .bd-role-admin .bd-ovh-sidebar__support {
        display: grid;
        gap: 5px;
    }

    .bd-role-admin .bd-ovh-sidebar__section-summary,
    .bd-role-admin .bd-ovh-sidebar__workspace,
    .bd-role-admin .bd-ovh-sidebar__link {
        display: flex;
        align-items: center;
        justify-content: flex-start;
        min-height: 36px;
        padding: 0 12px;
        border-radius: 12px;
        color: rgba(248,250,252,.88) !important;
        text-decoration: none !important;
        font-size: .74rem;
        font-weight: 700;
        transition: background-color .15s ease, color .15s ease, transform .15s ease;
    }

    .bd-role-admin .bd-ovh-sidebar__section-summary {
        min-height: 30px;
        color: rgba(226,232,240,.6) !important;
        font-size: .68rem;
    }

    .bd-role-admin .bd-ovh-sidebar__workspace:hover,
    .bd-role-admin .bd-ovh-sidebar__link:hover,
    .bd-role-admin .bd-ovh-sidebar__section-summary:hover {
        background: rgba(255,255,255,.06);
        color: #ffffff !important;
        transform: translateX(2px);
    }

    .bd-role-admin .bd-ovh-sidebar__workspace.active,
    .bd-role-admin .bd-ovh-sidebar__link.active {
        background: rgba(248,250,252,.96);
        color: #0d2d19 !important;
        box-shadow: 0 8px 22px rgba(8,22,14,.22);
    }

    .bd-role-admin .bd-ovh-sidebar__cta {
        display: flex;
        align-items: center;
        justify-content: center;
        min-height: 40px;
        margin: 12px 0 6px;
        border-radius: 14px;
        background: #f6f4ed;
        color: #0d2d19 !important;
        font-size: .76rem;
        font-weight: 800;
        text-decoration: none !important;
    }

    .bd-role-admin .bd-ovh-sidebar__support {
        margin-top: 4px;
        padding: 12px 10px 10px;
        border-top: 1px solid rgba(255,255,255,.08);
    }

    .bd-role-admin .bd-ovh-sidebar__support a,
    .bd-role-admin .bd-ovh-sidebar__support button {
        display: block;
        padding: 0;
        background: transparent !important;
        border: 0 !important;
        color: rgba(226,232,240,.72) !important;
        font-size: .66rem;
        line-height: 1.4;
        text-align: left;
        text-decoration: none !important;
    }

    .bd-role-admin .card,
    .bd-admin-ref-shell .panel {
        border-radius: 20px !important;
        border: 1px solid rgba(15,23,42,.07) !important;
        background: rgba(255,255,255,.82) !important;
        box-shadow: 0 14px 32px rgba(15,23,42,.05) !important;
        backdrop-filter: blur(10px);
    }

    .bd-role-admin .card-header,
    .bd-admin-ref-shell .panel-header {
        padding: 14px 18px !important;
    }

    .bd-role-admin .card-body,
    .bd-admin-ref-shell .panel-body {
        padding: 16px 18px !important;
    }

    .bd-role-admin .kpi-card {
        border-radius: 20px;
        padding: 1.05rem 1.05rem .95rem;
        background: linear-gradient(180deg, rgba(255,255,255,.97) 0%, rgba(247,245,240,.97) 100%);
        box-shadow: 0 16px 28px rgba(15,23,42,.05);
    }

    .bd-role-admin .kpi-value {
        font-size: 1.48rem;
    }

    .bd-role-admin .main-footer {
        margin-left: 248px !important;
        background: transparent !important;
        border-top: 0;
        padding-top: 1rem;
    }

    @media (max-width: 991.98px) {
        .bd-role-admin .main-sidebar {
            width: 248px !important;
        }

        .bd-role-admin .bd-admin-topbar {
            margin: 12px 12px 0 !important;
        }

        .bd-role-admin .bd-admin-topbar__shell {
            padding: 10px 12px;
        }

        .bd-role-admin .content-wrapper,
        .bd-role-admin .main-footer {
            margin-left: 0 !important;
        }

        .bd-role-admin .content-wrapper {
            padding: 10px 12px 12px !important;
        }

        .bd-role-admin .content-header {
            margin: -12px -12px 12px !important;
        }
    }
</style>
</head>
<body class="hold-transition sidebar-mini layout-fixed bd-admin-body bd-role-{{ auth()->check() ? auth()->user()->type : 'guest' }}">
<div class="wrapper">
    @php
        $currentUser = auth()->user();
        $currentRestaurant = auth()->check() && ($currentUser->type ?? null) === 'restaurant' ? optional($currentUser)->restaurant : null;
        $restaurantLogo = null;
        if ($currentRestaurant && !empty($currentRestaurant->logo)) {
            $restaurantLogo = \Illuminate\Support\Str::startsWith($currentRestaurant->logo, ['http://', 'https://'])
                ? $currentRestaurant->logo
                : asset('images/restaurant_images/' . $currentRestaurant->logo);
        }
        $genericAvatar = asset('assets/images/user-avatar.png');
        $platformMark = asset('frontend/images/Logo2.png');
        $isDriverRole = auth()->check() && ($currentUser->type ?? null) === 'driver';
        $driverSpaceLabel = request()->is('driver/transport*') ? 'Espace chauffeur' : 'Espace livreur';
        $driverAvatar = $genericAvatar;
        if ($isDriverRole) {
            $driverPhotoCandidate = $currentUser->image ?? $currentUser->photo ?? null;
            if (!empty($driverPhotoCandidate)) {
                $driverAvatar = \Illuminate\Support\Str::startsWith($driverPhotoCandidate, ['http://', 'https://'])
                    ? $driverPhotoCandidate
                    : asset('images/driver_images/' . ltrim($driverPhotoCandidate, '/'));
            }
        }
        $brandHref = auth()->check() && ($currentUser->type ?? null) === 'restaurant'
            ? route('restaurant.dashboard')
            : ($isDriverRole ? route('driver.deliveries') : url('/admin'));
        $isAdminRole = auth()->check() && ($currentUser->type ?? null) === 'admin';
        $renderAdminHubInTopbar = $isAdminRole;
        $currentRouteName = optional(request()->route())->getName() ?? '';
        $adminRouteIs = function (array $patterns) {
            foreach ($patterns as $pattern) {
                if (request()->routeIs($pattern)) {
                    return true;
                }
            }

            return false;
        };
        $adminPathIs = function (array $patterns) {
            foreach ($patterns as $pattern) {
                if (request()->is($pattern)) {
                    return true;
                }
            }

            return false;
        };
        $adminWorkspaceDefinitions = [
            'bantudelice' => [
                'label' => 'BantuDelice',
                'eyebrow' => 'Food ops',
                'description' => 'Restaurants, commandes, catalogue, clients et terrain food.',
                'icon' => 'fas fa-utensils',
                'landing_route' => 'admin.all_orders',
                'route_patterns' => ['admin.dashboard', 'admin.architecture.*', 'admin.all_orders', 'admin.pending_orders', 'admin.complete_orders', 'admin.cancel_orders', 'admin.prepaire_orders', 'admin.schedule_orders', 'admin.show_order', 'admin.show_completed_order', 'restaurant.*', 'user.*', 'news.*', 'cuisine.*', 'total.pro', 'driver.*', 'vehicle.*'],
            ],
            'kende' => [
                'label' => 'Kende',
                'eyebrow' => 'Mobilite',
                'description' => 'Trajets, reservations, flotte et tarification.',
                'icon' => 'fas fa-car-side',
                'landing_route' => 'admin.transport.dashboard',
                'route_patterns' => ['admin.transport.*'],
            ],
            'mema' => [
                'label' => 'Mema',
                'eyebrow' => 'Colis',
                'description' => 'Expeditions, relais, suivi et reconciliation COD.',
                'icon' => 'fas fa-box-open',
                'landing_route' => 'admin.colis.index',
                'route_patterns' => ['admin.colis.*', 'admin.relay-points.*'],
            ],
        ];
        $resolvedWorkspace = 'bantudelice';
        foreach ($adminWorkspaceDefinitions as $workspaceKey => $workspaceDefinition) {
            if ($adminRouteIs($workspaceDefinition['route_patterns'])) {
                $resolvedWorkspace = $workspaceKey;
                break;
            }
        }
        $requestedWorkspace = request('workspace');
        $selectedAdminWorkspace = array_key_exists($requestedWorkspace, $adminWorkspaceDefinitions) ? $requestedWorkspace : $resolvedWorkspace;
        $selectedAdminWorkspaceMeta = $adminWorkspaceDefinitions[$selectedAdminWorkspace] ?? $adminWorkspaceDefinitions['bantudelice'];
        $adminShellTitle = ($selectedAdminWorkspaceMeta['label'] ?? 'Control hub') . ' admin';
        $adminZoneLabel = request('zone', 'Brazzaville Centre');
        $adminPeriodValue = (int) request('period', 30);
        $adminPeriodLabel = $adminPeriodValue === 7 ? "7 jours" : ($adminPeriodValue === 90 ? "90 jours" : "30 jours");
        $adminWorkspaceMark = strtoupper(substr($selectedAdminWorkspaceMeta['label'] ?? 'B', 0, 1));
        $adminRoute = function ($routeName, array $params = [], $workspace = null) use ($selectedAdminWorkspace) {
            return route($routeName, array_merge($params, ['workspace' => $workspace ?: $selectedAdminWorkspace]));
        };
        $adminSidebarTrees = [];
        if ($isAdminRole) {
            $pendingOrdersCount = \App\Order::whereStatus('pending')->get()->unique('order_no')->count();
            $activeShipmentsCount = \App\Domain\Colis\Models\Shipment::query()
                ->whereNull('deleted_at')
                ->whereNotIn('status', ['delivered', 'cancelled', 'completed'])
                ->count();
            $openTransportBookingsCount = \App\Domain\Transport\Models\TransportBooking::query()
                ->whereNull('deleted_at')
                ->whereNotIn('status', ['completed', 'cancelled', 'closed'])
                ->count();
            $driverCount = \App\Driver::count();

            $adminCoreSidebarGroups = [
                [
                    'label' => 'Super admin',
                    'items' => [
                        [
                            'label' => 'Tableau de bord',
                            'icon' => 'fas fa-tachometer-alt',
                            'route' => 'admin.dashboard',
                            'patterns' => ['admin.dashboard'],
                        ],
                        [
                            'label' => 'Architecture admin',
                            'icon' => 'fas fa-project-diagram',
                            'route' => 'admin.architecture.show',
                            'patterns' => ['admin.architecture.show', 'admin.architecture.preview'],
                        ],
                        [
                            'label' => 'Metriques',
                            'icon' => 'fas fa-wave-square',
                            'route' => 'admin.metrics',
                            'patterns' => ['admin.metrics', 'admin.metrics.*'],
                        ],
                    ],
                ],
                [
                    'label' => 'Support',
                    'items' => [
                        [
                            'label' => 'Tickets support',
                            'icon' => 'fas fa-life-ring',
                            'route' => 'admin.support-tickets.index',
                            'patterns' => ['admin.support-tickets.*'],
                        ],
                        [
                            'label' => 'Commerce analytics',
                            'icon' => 'fas fa-chart-line',
                            'route' => 'admin.commerce-analytics.index',
                            'patterns' => ['admin.commerce-analytics.*'],
                        ],
                    ],
                ],
                [
                    'label' => 'Finance',
                    'items' => [
                        [
                            'label' => 'Paiements',
                            'icon' => 'fas fa-money-check-alt',
                            'route' => 'admin.payments.dashboard',
                            'patterns' => ['admin.payments.dashboard', 'admin.payments.dashboard.*'],
                        ],
                        [
                            'label' => 'Reversements restaurants',
                            'icon' => 'fas fa-store',
                            'route' => 'restaurant_payout',
                            'patterns' => ['restaurant_payout', 'restaurant_payout.*'],
                        ],
                        [
                            'label' => 'Reversements livreurs',
                            'icon' => 'fas fa-motorcycle',
                            'route' => 'driver_payout',
                            'patterns' => ['driver_payout', 'driver_payout.*'],
                        ],
                        [
                            'label' => 'Charges',
                            'icon' => 'fas fa-file-invoice-dollar',
                            'route' => 'charge.index',
                            'patterns' => ['charge.*'],
                        ],
                    ],
                ],
                [
                    'label' => 'Parametre',
                    'items' => [
                        [
                            'label' => 'Modules et sante',
                            'icon' => 'fas fa-cogs',
                            'route' => 'admin.modules.index',
                            'patterns' => ['admin.modules.*'],
                        ],
                        [
                            'label' => 'Contenu accueil',
                            'icon' => 'fas fa-home',
                            'route' => 'admin.home-content.edit',
                            'patterns' => ['admin.home-content.*'],
                        ],
                        [
                            'label' => 'Contenus CMS',
                            'icon' => 'fas fa-copy',
                            'route' => 'admin.cms.contents.index',
                            'patterns' => ['admin.cms.contents.*'],
                        ],
                        [
                            'label' => 'Types de contenus',
                            'icon' => 'fas fa-sitemap',
                            'route' => 'admin.cms.content-types.index',
                            'patterns' => ['admin.cms.content-types.*'],
                        ],
                        [
                            'label' => 'Mediatheque',
                            'icon' => 'fas fa-photo-video',
                            'route' => 'admin.cms.media.index',
                            'patterns' => ['admin.cms.media.*'],
                        ],
                        [
                            'label' => 'Actualites',
                            'icon' => 'fas fa-newspaper',
                            'route' => 'news.index',
                            'patterns' => ['news.*'],
                        ],
                        [
                            'label' => 'Configuration API',
                            'icon' => 'fas fa-plug',
                            'route' => 'admin.api.configuration',
                            'patterns' => ['admin.api.*'],
                        ],
                        [
                            'label' => 'Profil',
                            'icon' => 'fas fa-user-cog',
                            'route' => 'admin.profile',
                            'patterns' => ['admin.profile', 'admin.profile_update'],
                        ],
                    ],
                ],
            ];

            $adminOperationsGroups = [
                'bantudelice' => [
                    'label' => 'Operations',
                    'items' => [
                        [
                            'label' => 'Commandes',
                            'icon' => 'fas fa-shopping-cart',
                            'route' => 'admin.all_orders',
                            'patterns' => ['admin.all_orders', 'admin.pending_orders', 'admin.complete_orders', 'admin.cancel_orders', 'admin.prepaire_orders', 'admin.schedule_orders', 'admin.show_order', 'admin.show_completed_order'],
                            'badge' => $pendingOrdersCount,
                            'badge_variant' => 'red',
                        ],
                        [
                            'label' => 'Plats',
                            'icon' => 'fas fa-utensils',
                            'route' => 'total.pro',
                            'patterns' => ['total.pro', 'admin.product.*'],
                        ],
                        [
                            'label' => 'Restaurants',
                            'icon' => 'fas fa-building',
                            'route' => 'restaurant.index',
                            'patterns' => ['restaurant.*', 'admin.pending'],
                        ],
                        [
                            'label' => 'Clients',
                            'icon' => 'fas fa-users',
                            'route' => 'user.index',
                            'patterns' => ['user.*'],
                        ],
                        [
                            'label' => 'Livreurs',
                            'icon' => 'fas fa-motorcycle',
                            'route' => 'driver.index',
                            'patterns' => ['driver.*'],
                            'badge' => $driverCount,
                            'badge_variant' => 'green',
                        ],
                        [
                            'label' => 'Parc vehicules',
                            'icon' => 'fas fa-truck-moving',
                            'route' => 'vehicle.index',
                            'patterns' => ['vehicle.*'],
                        ],
                    ],
                ],
                'kende' => [
                    'label' => 'Operations',
                    'items' => [
                        [
                            'label' => 'Tableau transport',
                            'icon' => 'fas fa-route',
                            'route' => 'admin.transport.dashboard',
                            'patterns' => ['admin.transport.dashboard'],
                            'badge' => $openTransportBookingsCount,
                            'badge_variant' => 'green',
                        ],
                        [
                            'label' => 'Reservations',
                            'icon' => 'fas fa-calendar-check',
                            'route' => 'admin.transport.bookings.index',
                            'patterns' => ['admin.transport.bookings.*'],
                        ],
                        [
                            'label' => 'Flotte',
                            'icon' => 'fas fa-shuttle-van',
                            'route' => 'admin.transport.vehicles.index',
                            'patterns' => ['admin.transport.vehicles.*'],
                        ],
                        [
                            'label' => 'Tarification',
                            'icon' => 'fas fa-tags',
                            'route' => 'admin.transport.pricing.index',
                            'patterns' => ['admin.transport.pricing.*'],
                        ],
                    ],
                ],
                'mema' => [
                    'label' => 'Operations',
                    'items' => [
                        [
                            'label' => 'Expeditions',
                            'icon' => 'fas fa-box-open',
                            'route' => 'admin.colis.index',
                            'patterns' => ['admin.colis.index', 'admin.colis.show', 'admin.colis.print', 'admin.colis.export-csv'],
                            'badge' => $activeShipmentsCount,
                        ],
                        [
                            'label' => 'Nouveaux colis',
                            'icon' => 'fas fa-inbox',
                            'route' => 'admin.colis.index',
                            'params' => ['status' => 'created'],
                            'patterns' => [],
                            'active_when' => $currentRouteName === 'admin.colis.index' && request('status') === 'created',
                        ],
                        [
                            'label' => 'Points relais',
                            'icon' => 'fas fa-map-marker-alt',
                            'route' => 'admin.relay-points.index',
                            'patterns' => ['admin.relay-points.*'],
                        ],
                        [
                            'label' => 'Reconciliation COD',
                            'icon' => 'fas fa-wallet',
                            'route' => 'admin.colis.finance',
                            'patterns' => ['admin.colis.finance', 'admin.colis.reconcile'],
                        ],
                    ],
                ],
            ];

            foreach ($adminWorkspaceDefinitions as $workspaceKey => $workspaceDefinition) {
                $groups = [];

                foreach ($adminCoreSidebarGroups as $group) {
                    $items = [];
                    foreach ($group['items'] as $item) {
                        $items[] = [
                            'label' => $item['label'],
                            'icon' => $item['icon'],
                            'url' => $adminRoute($item['route'], $item['params'] ?? [], $workspaceKey),
                            'active' => !empty($item['active_when']) ? (bool) $item['active_when'] && $selectedAdminWorkspace === $workspaceKey : ($selectedAdminWorkspace === $workspaceKey && $adminRouteIs($item['patterns'] ?? [])),
                            'badge' => $item['badge'] ?? null,
                            'badge_variant' => $item['badge_variant'] ?? null,
                        ];
                    }
                    $groups[] = [
                        'label' => $group['label'],
                        'active' => collect($items)->contains(function ($item) {
                            return !empty($item['active']);
                        }),
                        'items' => $items,
                    ];
                }

                if (isset($adminOperationsGroups[$workspaceKey])) {
                    $operationGroup = $adminOperationsGroups[$workspaceKey];
                    $operationItems = [];
                    foreach ($operationGroup['items'] as $item) {
                        $operationItems[] = [
                            'label' => $item['label'],
                            'icon' => $item['icon'],
                            'url' => $adminRoute($item['route'], $item['params'] ?? [], $workspaceKey),
                            'active' => !empty($item['active_when']) ? (bool) $item['active_when'] && $selectedAdminWorkspace === $workspaceKey : ($selectedAdminWorkspace === $workspaceKey && ($adminRouteIs($item['patterns'] ?? []) || (!empty($item['patterns']) && $adminPathIs($item['patterns'])))),
                            'badge' => $item['badge'] ?? null,
                            'badge_variant' => $item['badge_variant'] ?? null,
                        ];
                    }
                    $groups[] = [
                        'label' => $operationGroup['label'],
                        'active' => collect($operationItems)->contains(function ($item) {
                            return !empty($item['active']);
                        }),
                        'items' => $operationItems,
                    ];
                }

                $adminSidebarTrees[] = [
                    'key' => $workspaceKey,
                    'label' => $workspaceDefinition['label'],
                    'eyebrow' => $workspaceDefinition['eyebrow'],
                    'description' => $workspaceDefinition['description'],
                    'icon' => $workspaceDefinition['icon'],
                    'landing_url' => $adminRoute($workspaceDefinition['landing_route'], [], $workspaceKey),
                    'active' => $selectedAdminWorkspace === $workspaceKey,
                    'summary_badge' => $workspaceKey === 'bantudelice'
                        ? $pendingOrdersCount
                        : ($workspaceKey === 'kende' ? $openTransportBookingsCount : $activeShipmentsCount),
                    'summary_badge_variant' => $workspaceKey === 'bantudelice'
                        ? 'red'
                        : ($workspaceKey === 'kende' ? 'green' : 'gold'),
                    'groups' => $groups,
                ];
            }
        }
    @endphp

    <!-- Navbar -->
    <nav class="main-header navbar navbar-expand navbar-light bd-admin-topbar">
        @if(auth()->check() && auth()->user()->type === 'admin')
            <div class="bd-admin-topbar__shell">
                <div class="bd-admin-topbar__left">
                    <a class="bd-admin-topbar__toggle" data-widget="pushmenu" href="#"><i class="fas fa-bars"></i></a>
                    <label class="bd-admin-search mb-0">
                        <i class="fas fa-search"></i>
                        <input type="text" placeholder="Recherche rapide..." aria-label="Recherche admin">
                    </label>
                </div>
                <div class="bd-admin-topbar__hub">
                    @include('admin.partials.control_hub_nav', ['embeddedInTopbar' => true])
                </div>
                <div class="bd-admin-topbar__right">
                    <div class="bd-admin-topbar__context">
                        <span class="bd-admin-chip">
                            <strong>Zone</strong>
                            <span>{{ $adminZoneLabel }}</span>
                        </span>
                        <div class="bd-admin-periods" aria-label="Periode active">
                            <a href="{{ request()->fullUrlWithQuery(['period' => 7]) }}" class="{{ $adminPeriodValue === 7 ? 'active' : '' }}">7 jours</a>
                            <a href="{{ request()->fullUrlWithQuery(['period' => 30]) }}" class="{{ $adminPeriodValue === 30 ? 'active' : '' }}">30 jours</a>
                            <a href="{{ request()->fullUrlWithQuery(['period' => 90]) }}" class="{{ $adminPeriodValue === 90 ? 'active' : '' }}">90 jours</a>
                        </div>
                    </div>
                    <div class="bd-admin-topbar__account">
                        <a class="nav-link bd-admin-icon-btn" data-toggle="modal" data-target="#myModal2" href="#">
                            <i class="fas fa-bell"></i>
                            <span class="bd-admin-icon-btn__dot"></span>
                        </a>
                        <a class="nav-link p-0" href="{{ route('admin.profile') }}">
                            <span class="bd-admin-user-pill">
                                <img src="{{ auth()->user()->avatarUrl() }}" data-fallback-src="{{ $genericAvatar }}" alt="User" class="bd-admin-user-pill__avatar">
                                <span>
                                    <span class="bd-admin-user-pill__name">{{ auth()->user()->name }}</span>
                                    <span class="d-block bd-admin-user-pill__role">{{ auth()->user()->type ?? 'admin' }}</span>
                                </span>
                            </span>
                        </a>
                    </div>
                </div>
            </div>
        @else
        <!-- Left navbar links -->
        <ul class="navbar-nav">
            <li class="nav-item">
                <a class="nav-link" data-widget="pushmenu" href="#"><i class="fas fa-bars"></i></a>
            </li>
            @if(auth()->check() && auth()->user()->type === 'restaurant')
                <li class="nav-item d-flex align-items-center">
                    <div class="bd-admin-topbar__meta">
                        <span class="bd-admin-topbar__title">Espace restaurant</span>
                        <span class="bd-admin-topbar__date">{{ $currentRestaurant->name ?? auth()->user()->name }}</span>
                    </div>
                </li>
            @elseif($isDriverRole)
                <li class="nav-item d-flex align-items-center">
                    <div class="bd-admin-topbar__meta">
                        <span class="bd-admin-topbar__title">{{ $driverSpaceLabel }}</span>
                        <span class="bd-admin-topbar__date">{{ auth()->user()->name }}</span>
                    </div>
                </li>
            @else
                <li class="nav-item d-flex align-items-center">
                    <div class="bd-admin-topbar__meta">
                        <span class="bd-admin-topbar__title">{{ $adminShellTitle }}</span>
                    </div>
                </li>
            @endif
        </ul>
        <!-- Right navbar links -->
        <ul class="navbar-nav ml-auto align-items-center">
            @php
                $dashboardProfileRoute = auth()->check() && (auth()->user()->type ?? null) === 'restaurant'
                    ? route('restaurant.profile')
                    : ($isDriverRole ? route('driver.deliveries') : route('admin.profile'));
                $impersonationContext = session('admin_impersonation_context');
            @endphp
            @if(auth()->check() and auth()->user()->type === 'restaurant')
                <li class="nav-item dropdown">
                   <a class="nav-link" data-toggle="modal" data-target="#myModal2" href="#">
                       <i class="fas fa-bell"></i>
                       <span class="badge badge-warning navbar-badge" id="notiBell"></span>
                    </a>
                </li>
            @endif
                @if(!auth()->check() || auth()->user()->type !== 'admin')
                    @if($impersonationContext)
                        <li class="nav-item d-none d-md-flex mr-2">
                            <form method="POST" action="{{ route('admin.impersonate.stop') }}" style="margin:0;">
                                @csrf
                                <button type="submit" class="bd-admin-topbar__home" style="border:0;">
                                    <i class="fas fa-user-shield"></i>
                                    <span>Revenir à l'admin</span>
                                </button>
                            </form>
                        </li>
                        <li class="nav-item d-none d-md-flex mr-2">
                            <span class="bd-admin-topbar__date">Session déléguée : {{ $impersonationContext['target_name'] ?? auth()->user()->name }}</span>
                        </li>
                    @endif
                    <li class="nav-item d-none d-md-flex mr-2">
                        <a href="{{ route('home') }}" class="bd-admin-topbar__home">
                            <i class="fas fa-home"></i>
                            <span>Accueil</span>
                        </a>
                    </li>
                    <li class="nav-item d-none d-md-flex mr-2">
                        <span class="bd-admin-topbar__date">{{date('d M Y')}}</span>
                    </li>
                @endif
                <li class="nav-item dropdown">
                    <a class="nav-link" data-toggle="dropdown" href="#">
                        <span class="bd-admin-user-pill">
                            <img src="{{ auth()->check() && auth()->user()->type === 'restaurant' ? ($restaurantLogo ?? $platformMark) : ($isDriverRole ? $driverAvatar : auth()->user()->avatarUrl()) }}" data-fallback-src="{{ $genericAvatar }}" alt="User" class="bd-admin-user-pill__avatar">
                            <span>
                                <span class="bd-admin-user-pill__name">{{ auth()->check() && auth()->user()->type === 'restaurant' ? ($currentRestaurant->name ?? auth()->user()->name) : auth()->user()->name }}</span>
                                <span class="d-block bd-admin-user-pill__role">{{ auth()->check() && auth()->user()->type === 'restaurant' ? 'Restaurant' : ($isDriverRole ? (request()->is('driver/transport*') ? 'Chauffeur' : 'Livreur') : (auth()->user()->type ?? 'admin')) }}</span>
                            </span>
                        </span>
                    </a>
                    <div class="dropdown-menu dropdown-menu-right mr-3">
                   <a href="{{ $dashboardProfileRoute }}" class="dropdown-item">
                       <i class="fas fa-user mr-2"></i>Profil

                   </a>
                   <div class="dropdown-divider"></div>
                   <form method="POST" action="{{ route('logout') }}">
                       @csrf
                       <button type="submit" class="dropdown-item" style="background:none;border:0;width:100%;text-align:left;">
                           <i class="fas fa-envelope mr-2"></i> Déconnexion
                       </button>
                   </form>

                    </div>
                </li>

       </ul>
       @endif
    </nav>
    <!-- /.navbar -->

    <!-- Main Sidebar Container -->
    <aside class="main-sidebar sidebar-light-primary elevation-4"@if($isAdminRole) style="position:fixed;top:0;left:0;bottom:0;width:248px;overflow-y:auto;overflow-x:hidden;z-index:1040;" @endif>
        <!-- Brand Logo -->
        <a href="{{ $brandHref }}" class="brand-image p-0"@unless($isAdminRole) style="background-color:#fff"@endunless>
            @if(auth()->check() && auth()->user()->type === 'admin')
                <span class="bd-admin-brand__dot">{{ $adminWorkspaceMark }}</span>
                <span class="bd-admin-brand__stack">
                    <span class="bd-admin-brand__name">{{ $selectedAdminWorkspaceMeta['label'] ?? 'Control hub' }}</span>
                    <span class="bd-admin-brand__meta">Hub admin</span>
                </span>
            @elseif(auth()->check() && auth()->user()->type === 'restaurant')
                <span class="bd-restaurant-brand">
                    <span class="bd-restaurant-brand__mark">
                        <img src="{{ $restaurantLogo ?? $platformMark }}" alt="{{ $currentRestaurant->name ?? 'Restaurant' }}">
                    </span>
                    <span class="bd-restaurant-brand__copy">
                        <span class="bd-restaurant-brand__name">{{ $currentRestaurant->name ?? 'Restaurant' }}</span>
                        <span class="bd-restaurant-brand__badge">Espace restaurant</span>
                    </span>
                </span>
            @elseif($isDriverRole)
                    <span class="bd-restaurant-brand">
                        <span class="bd-restaurant-brand__mark">
                        <img src="{{ $driverAvatar }}" alt="{{ auth()->user()->name }}">
                        </span>
                        <span class="bd-restaurant-brand__copy">
                            <span class="bd-restaurant-brand__name">{{ auth()->user()->name }}</span>
                            <span class="bd-restaurant-brand__badge">{{ $driverSpaceLabel }}</span>
                    </span>
                </span>
            @else
                <img src="{{ $platformMark }}" alt="Plateforme"
                     class="brand-image"
                     style="opacity: .8; width:250px; height:50px;">
            @endif
        </a>

        <!-- Sidebar -->
        <div class="sidebar">
            <!-- Sidebar Menu -->
            <nav class="mt-2">
                @if(auth()->check() and auth()->user()->type === 'admin')
                    <div class="bd-ovh-sidebar">
                        <div class="bd-ovh-sidebar__title">Hub de services</div>
                        <div class="bd-ovh-sidebar__group">Ecosystemes</div>
                        @foreach($adminSidebarTrees as $workspaceTree)
                            <a href="{{ $workspaceTree['landing_url'] }}" class="bd-ovh-sidebar__workspace{{ $workspaceTree['active'] ? ' active' : '' }}">
                                <span>{{ $workspaceTree['label'] }}</span>
                            </a>
                            @if($workspaceTree['active'])
                                @foreach($workspaceTree['groups'] as $sidebarGroup)
                                    @if(!empty($sidebarGroup['active']))
                                        <div class="bd-ovh-sidebar__section">
                                            <div class="bd-ovh-sidebar__group">{{ $sidebarGroup['label'] }}</div>
                                            <div class="bd-ovh-sidebar__section-links">
                                                @foreach($sidebarGroup['items'] as $sidebarItem)
                                                    <a href="{{ $sidebarItem['url'] }}" class="bd-ovh-sidebar__link{{ $sidebarItem['active'] ? ' active' : '' }}">
                                                        <span>{{ $sidebarItem['label'] }}</span>
                                                    </a>
                                                @endforeach
                                            </div>
                                        </div>
                                    @elseif(!empty($sidebarGroup['items'][0]))
                                        <a href="{{ $sidebarGroup['items'][0]['url'] }}" class="bd-ovh-sidebar__section-summary">
                                            <span>{{ $sidebarGroup['label'] }}</span>
                                        </a>
                                    @endif
                                @endforeach
                            @endif
                        @endforeach

                        <a href="{{ route('home') }}" class="bd-ovh-sidebar__cta" target="_blank" rel="noopener">Ajouter un service</a>

                        <div class="bd-ovh-sidebar__support">
                            <a href="{{ route('contact.us', ['brand' => $selectedAdminWorkspace]) }}" target="_blank" rel="noopener">Centre d'aide</a>
                            <a href="{{ route('admin.support-tickets.index', ['workspace' => $selectedAdminWorkspace]) }}">Mes demandes d'assistance</a>
                            <a href="{{ route('admin.modules.index', ['workspace' => $selectedAdminWorkspace]) }}">Etat du reseau et incidents</a>
                            <button type="button" data-toggle="modal" data-target="#myModal2">Notifications</button>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit">Deconnexion</button>
                            </form>
                        </div>
                    </div>
                @elseif(auth()->check() and auth()->user()->type === 'restaurant' and auth()->user()->restaurant()->first()->services === 'both')
                <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu"
                    data-accordion="false">
                        <li class="nav-item">
                            <a class="nav-link @yield('dashboard_nav')" href="{{url('restaurant')}}">
                                <i class="nav-icon fas fa-tachometer-alt "></i>
                                <p>
                                    Tableau de bord
                                </p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link @yield('media_nav')" href="{{ route('restaurant.media.index') }}">
                                <i class="nav-icon fas fa-images"></i>
                                <p>Médias (galerie)</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link @yield('menu_nav')" href="{{ route('restaurant.menu.index') }}">
                                <i class="nav-icon fas fa-utensils"></i>
                                <p>Menu (moderne)</p>
                            </a>
                        </li>
                        <li class="nav-item has-treeview">
                            <a href="{{ route('category.index') }}" class="nav-link @yield('category_nav')">
                                <i class="nav-icon fas fa-list-alt"></i>
                                <p>
                                    Catégories
                                    <i class="right fas fa-angle-left"></i>
                                </p>
                            </a>
                            <ul class="nav nav-treeview">
                                <li class="nav-item">
                                    <a href="{{route('category.index')}}" class="nav-link">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Toutes les catégories</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{route('category.create')}}" class="nav-link">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Ajouter une catégorie</p>
                                    </a>
                                </li>
                            </ul>
                        </li>
                        <!--<li class="nav-item has-treeview">-->
                        <!--    <a href="{{route('add-on.index')}}" class="nav-link @yield('add_on_nav')">-->
                        <!--         <i class="nav-icon fas fa-puzzle-piece"></i>-->
                        <!--        <p>Add ons</p>-->
                        <!--    </a>-->
                        <!--</li>-->
                        <li class="nav-item has-treeview @yield('vouchers_nav_open')">
                            <a href="{{ route('voucher.index') }}" class="nav-link @yield('vouchers_nav')">
                                 <i class="nav-icon fas fa-tags"></i>
                                <p>Bons de réduction<i class="fas fa-angle-left right"></i></p>
                            </a>
                            <ul class="nav nav-treeview">
                                <li class="nav-item">
                                    <a href="{{route('voucher.index')}}" class="nav-link @yield('vouchers_nav_index')">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Tous les bons</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{route('voucher.create')}}" class="nav-link @yield('vouchers_nav_create')">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Ajouter un bon</p>
                                    </a>
                                </li>
                            </ul>
                        </li>
                        <li class="nav-item has-treeview @yield('working_hour_nav_open')">
                            <a href="{{ route('working_hour.index') }}" class="nav-link @yield('working_hour_nav')">
                                <i class="nav-icon fas fa-calendar-alt"></i>
                                <p>
                                    Horaires
                                    <i class="right fas fa-angle-left"></i>
                                </p>
                            </a>
                            <ul class="nav nav-treeview">
                                <li class="nav-item">
                                    <a href="{{route('working_hour.index')}}" class="nav-link">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Voir les horaires</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{route('working_hour.create')}}" class="nav-link">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Ajouter un horaire</p>
                                    </a>
                                </li>
                            </ul>
                        </li>
                        <li class="nav-item has-treeview">
                            <a href="{{ route('product.index') }}" class="nav-link @yield('product_nav')">
                                <i class="nav-icon fas fa-list-alt"></i>
                                <p>
                                    Produits
                                    <i class="right fas fa-angle-left"></i>
                                </p>
                            </a>
                            <ul class="nav nav-treeview">
                                <li class="nav-item">
                                    <a href="{{route('product.index')}}" class="nav-link">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Tous les produits</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{route('product.create')}}" class="nav-link">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Ajouter un produit</p>
                                    </a>
                                </li>
                            </ul>
                        </li>
                        <li class="nav-item has-treeview @yield('order_nav_open')">
                            <a href="{{ route('restaurant.all_orders') }}" class="nav-link @yield('order_nav')">
                                <i class="nav-icon fas fa-shopping-cart"></i>
                                <p>
                                    Commandes
                                    <i class="right fas fa-angle-left"></i>
                                </p>
                            </a>
                            <ul class="nav nav-treeview">
                                <li class="nav-item">
                                    <a href="{{ route('restaurant.kitchen') }}" class="nav-link">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Écran cuisine</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{route('restaurant.all_orders')}}" class="nav-link @yield('order_nav_all')">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Nouvelles commandes ({{ \App\Order::where('restaurant_id', auth()->user()->restaurant()->first()->id ?? 0)->whereStatus('pending')->get()->unique('order_no')->count() }})</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{route('restaurant.getpreparing')}}" class="nav-link @yield('order_nav_preparing')">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>En préparation ({{ \App\Order::where('restaurant_id', auth()->user()->restaurant()->first()->id ?? 0)->whereStatus('prepairing')->get()->unique('order_no')->count() }})</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{route('restaurant.pending_orders')}}" class="nav-link @yield('order_nav_pending')">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Assignées ({{ \App\Order::where('restaurant_id', auth()->user()->restaurant()->first()->id ?? 0)->whereStatus('assign')->get()->unique('order_no')->count() }})</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{route('restaurant.complete_orders')}}" class="nav-link @yield('order_nav_complete')">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Terminées ({{ \Schema::hasTable('completed_orders') ? \App\CompletedOrder::where('restaurant_id', auth()->user()->restaurant()->first()->id ?? 0)->whereStatus('completed')->get()->unique('order_no')->count() : 0 }})</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{route('restaurant.cancel_orders')}}" class="nav-link @yield('order_nav_cancel')">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Annulées ({{ \App\Order::where('restaurant_id', auth()->user()->restaurant()->first()->id ?? 0)->whereStatus('cancelled')->get()->unique('order_no')->count() }})</p>
                                    </a>
                                </li>
                            </ul>
                        </li>
                        <li class="nav-item">
                             <a class=" nav-link @yield('earnings_nav')" href="{{route('r_earnings.index')}}">
                                <i class="nav-icon fas fa-history"></i>
                                <p>Historique des paiements</p>
                            </a>
                        </li>
                        <!--<li class="nav-item has-treeview">-->
                        <!--    <a href="#" class="nav-link @yield('working_hour_nav')">-->
                        <!--        <i class="nav-icon fas fa-calendar-times"></i>-->
                        <!--        <p>-->
                        <!--            Working Hours-->
                        <!--            <i class="right fas fa-angle-left"></i>-->
                        <!--        </p>-->
                        <!--    </a>-->
                        <!--    <ul class="nav nav-treeview">-->
                        <!--        <li class="nav-item">-->
                        <!--            <a href="{{route('working_hour.index')}}" class="nav-link">-->
                        <!--                <i class="far fa-circle nav-icon"></i>-->
                        <!--                <p>List</p>-->
                        <!--            </a>-->
                        <!--        </li>-->
                        <!--        <li class="nav-item">-->
                        <!--            <a href="{{route('working_hour.create')}}" class="nav-link">-->
                        <!--                <i class="far fa-circle nav-icon"></i>-->
                        <!--                <p>Add</p>-->
                        <!--            </a>-->
                        <!--        </li>-->
                        <!--    </ul>-->
                        <!--</li>-->
{{--                        <li class=" nav-link">--}}
{{--                            <a href="{{ route('profile') }}">--}}
{{--                                <i class="nav-icon fas fa-user"></i>--}}
{{--                                <p>--}}
{{--                                    Profile--}}
{{--                                    --}}{{-- <i class="right fas fa-angle-left"></i>--}}
{{--                                </p>--}}
{{--                            </a>--}}
{{--                        </li>--}}
                        <!--<li class="nav-item ">-->
                        <!--    <a class="nav-link " href="{{ route('delivery_boundary') }}">-->
                        <!--        <i class="nav-icon fas fa-map-marker"></i>-->
                        <!--        <p>-->
                        <!--            Delivery Boundary-->
                        <!--        </p>-->
                        <!--    </a>-->
                        <!--</li>-->
                        <li class=" nav-item">
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="nav-link" style="background:none;border:0;width:100%;text-align:left;">
                                    <i class="nav-icon fas fa-power-off"></i>
                                    <p>
                                        Déconnexion
                                    </p>
                                </button>
                            </form>
                        </li>
                    </ul>

                     @elseif(auth()->check() and auth()->user()->type === 'restaurant' and auth()->user()->restaurant()->first()->services === 'delivery')
                     <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu"
                    data-accordion="false">
                     <!-- Here starts the delivery nav-bar -->
                        <li class="nav-item">
                            <a class="nav-link @yield('colis_nav')" href="{{ route('admin.colis.index') }}" style="background-color: #ff0000 !important; color: #fff !important; margin-bottom: 10px;">
                                <i class="nav-icon fas fa-box"></i>
                                <p>
                                    LIVRAISON COLIS
                                </p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link @yield('dashboard_nav')" href="{{ route('delivery.dashboard') }}">
                                <i class="nav-icon fas fa-tachometer-alt "></i>
                                <p>
                                    Tableau de bord
                                </p>
                            </a>
                        </li>
                       <li class="nav-item has-treeview">
                            <a href="{{ route('delivery.all_orders') }}" class="nav-link @yield('order_nav')">
                                <i class="nav-icon fas fa-shopping-cart"></i>
                                <p>
                                    Commandes
                                    <i class="right fas fa-angle-left"></i>
                                </p>
                            </a>
                            <ul class="nav nav-treeview">
                                <li class="nav-item">
                                    <a href="{{route('delivery.all_orders')}}" class="nav-link">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Toutes les commandes</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{route('delivery.complete_orders')}}" class="nav-link">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Complétées</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{route('delivery.cancel_orders')}}" class="nav-link">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Annulées</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{route('delivery.pending_orders')}}" class="nav-link">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>En attente</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{route('delivery.schedule_orders')}}" class="nav-link">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Programmées</p>
                                    </a>
                                </li>
                            </ul>
                        </li>
                        <li class="nav-item has-treeview">
                             <a class=" nav-link @yield('earnings_nav')" href="{{ route('d_earnings.index') }}">
                                <i class="nav-icon fas fa-history"></i>
                                <p>
                                    Historique des paiements
                                    <i class="right fas fa-angle-left"></i>
                                </p>
                            </a>
                            <ul class="nav nav-treeview">
                                <li class="nav-item">
                                    <a href="{{route('d_earnings.index')}}" class="nav-link ">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Revenus totaux</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{route('d_earnings.create')}}" class="nav-link ">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Historique des paiements</p>
                                    </a>
                                </li>
                            </ul>
                        </li>
                        <li class=" nav-item">
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="nav-link" style="background:none;border:0;width:100%;text-align:left;">
                                    <i class="nav-icon fas fa-power-off"></i>
                                    <p>
                                        Déconnexion
                                    </p>
                                </button>
                            </form>
                        </li>
                    </ul>
                    @elseif($isDriverRole)
                        <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu"
                    data-accordion="false">
                        <li class="bd-sidebar-group">Mon espace</li>
                        <li class="nav-item">
                            <a class="nav-link @yield('deliveries_nav')" href="{{ route('driver.deliveries') }}">
                                <i class="nav-icon fas fa-motorcycle"></i>
                                <p>Mes livraisons</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link @yield('transport_driver_nav')" href="{{ route('driver.transport.dashboard') }}">
                                <i class="nav-icon fas fa-car-side"></i>
                                <p>Courses transport</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="nav-link" style="background:none;border:0;width:100%;text-align:left;">
                                    <i class="nav-icon fas fa-power-off"></i>
                                    <p>Déconnexion</p>
                                </button>
                            </form>
                        </li>
                    </ul>
                    @endif
            </nav>
            <!-- /.sidebar-menu -->
        </div>
        <!-- Kleon promo card -->
        <div class="bd-kl-promo">
            <div class="bd-kl-promo__icon"><i class="fas fa-bolt"></i></div>
            <div class="bd-kl-promo__title">{{ auth()->check() && auth()->user()->type === 'restaurant' ? 'Restaurant' : ($isAdminRole ? (($selectedAdminWorkspaceMeta['label'] ?? 'Control hub') . ' admin') : 'Espace pro') }}</div>
            <div class="bd-kl-promo__sub">{{ $isAdminRole ? 'Pilotez les flux, incidents et performances du contexte actif.' : 'Gérez vos commandes, ventes et performances en temps réel.' }}</div>
            <a href="{{ route('home') }}" class="bd-kl-promo__link" target="_blank">
                Voir le site <i class="fas fa-arrow-right fa-xs"></i>
            </a>
        </div>
        <!-- /.sidebar -->
    </aside>
    <div class="content-wrapper"@if($isAdminRole) style="margin-left:248px;min-height:calc(100vh - 72px);" @endif>
        <!-- Content Header (Page header) -->
        @yield('content')
    </div>
</div>
<footer class="main-footer">
    <strong>Copyright &copy; {{date('Y')}} <a href="{{ route('home') }}">Buntu Delice</a>.</strong>
    Tous droits réservés.
</footer>
</div><!-- container -->

<!-- Notification panel -->

<div class="modal right fade" id="myModal2" tabindex="-1" role="dialog" aria-labelledby="myModalLabel2">
    <div class="modal-dialog" role="document">
        <div class="modal-content pb-5">

            <div class="modal-header p-3">
                <h4 class="modal-title" id="notiTitle"></h4>
                <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true" style="float:left"><i class="fas fa-arrow-right" style="margin-top:5px;"></i></span></button>
            </div>
            <div class="modal-body p-0 mb-5" id="notiBody">                   
            </div>

        </div><!-- modal-content -->
    </div><!-- modal-dialog -->
</div><!-- modal -->

<!-- ./wrapper -->

<!-- jQuery -->
<script src="{{ asset('plugins/jquery/jquery.min.js')}}"></script>
<!-- jQuery UI 1.11.4 -->
<script src="{{ asset('plugins/jquery-ui/jquery-ui.min.js')}}"></script>
<!-- Resolve conflict in jQuery UI tooltip with Bootstrap tooltip -->
<script>
    $.widget.bridge('uibutton', $.ui.button)
</script>
<!-- Bootstrap 4 -->
<script src="{{ asset('plugins/bootstrap/js/bootstrap.bundle.min.js')}}"></script>
<!-- ChartJS -->
<script src="{{ asset('plugins/chart.js/Chart.min.js')}}"></script>
<!-- Sparkline -->
<script src="{{ asset('plugins/sparklines/sparkline.js')}}"></script>
<!-- JQVMap -->
<script src="{{ asset('plugins/jqvmap/jquery.vmap.min.js')}}"></script>
<script src="{{ asset('plugins/jqvmap/maps/jquery.vmap.usa.js')}}"></script>
<!-- jQuery Knob Chart -->
<script src="{{ asset('plugins/jquery-knob/jquery.knob.min.js')}}"></script>
<!-- daterangepicker -->
<script src="{{ asset('plugins/moment/moment.min.js')}}"></script>
<script src="{{ asset('plugins/daterangepicker/daterangepicker.js')}}"></script>
<!-- Tempusdominus Bootstrap 4 -->
<script
    src="{{ asset('plugins/tempusdominus-bootstrap-4/js/tempusdominus-bootstrap-4.min.js')}}"></script>
<!-- Summernote -->
<script src="{{ asset('plugins/summernote/summernote-bs4.min.js')}}"></script>
<!-- overlayScrollbars -->
<script src="{{ asset('plugins/overlayScrollbars/js/jquery.overlayScrollbars.min.js')}}"></script>
<!-- AdminLTE App -->
<script src="{{ asset('dist/js/adminlte.js')}}"></script>
<script src="{{ asset('plugins/datatables/jquery.dataTables.js')}}"></script>
<script src="{{ asset('plugins/datatables-bs4/js/dataTables.bootstrap4.js')}}"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const role = document.querySelector('.bd-admin-user-pill__role')?.textContent?.trim().toLowerCase();
    const avatar = document.querySelector('.bd-admin-user-pill__avatar');
    const fallback = avatar?.dataset?.fallbackSrc;

    if (role === 'restaurant' && avatar && fallback && avatar.getAttribute('src')?.startsWith('data:image/svg+xml')) {
        avatar.setAttribute('src', fallback);
    }
});

if (window.jQuery && $.fn.dataTable) {
    window.bdAdminDataTableLanguage = {
        decimal: "",
        emptyTable: "Aucune donnee disponible",
        info: "Affichage de _START_ a _END_ sur _TOTAL_ elements",
        infoEmpty: "Affichage de 0 a 0 sur 0 element",
        infoFiltered: "(filtres depuis _MAX_ elements au total)",
        thousands: " ",
        lengthMenu: "Afficher _MENU_ elements",
        loadingRecords: "Chargement...",
        processing: "Traitement...",
        search: "Rechercher :",
        zeroRecords: "Aucun resultat trouve",
        paginate: {
            first: "Premier",
            last: "Dernier",
            next: "Suivant",
            previous: "Precedent"
        },
        buttons: {
            copy: "Copier",
            colvis: "Colonnes"
        }
    };

    window.bdAdminExportButtons = function (includeColumnVisibility) {
        const buttons = [];
        if (includeColumnVisibility) {
            buttons.push({ extend: 'colvis', text: 'Colonnes' });
        }

        buttons.push(
            { extend: 'excelHtml5', text: 'Excel', footer: true },
            { extend: 'csvHtml5', text: 'CSV', footer: true },
            { extend: 'pdfHtml5', text: 'PDF', footer: true }
        );

        return buttons;
    };

    window.bdAdminParseMoney = function (value) {
        if (typeof value === 'number') {
            return value;
        }

        if (typeof value !== 'string') {
            return 0;
        }

        const normalized = value.replace(/&nbsp;/g, ' ').replace(/<[^>]*>/g, ' ');
        const digits = normalized.replace(/[^0-9,-]/g, '').replace(/,/g, '.');
        const parsed = parseFloat(digits);

        return Number.isFinite(parsed) ? parsed : 0;
    };

    window.bdAdminMoneyFooterText = function (pageTotal, total) {
        return Math.round(pageTotal) + ' FCFA ( ' + Math.round(total) + ' FCFA total)';
    };

    $.extend(true, $.fn.dataTable.defaults, {
        language: window.bdAdminDataTableLanguage
    });
}
</script>
@if(auth()->check() && in_array(auth()->user()->type, ['restaurant', 'admin'], true))
@php($notificationAudioPath = public_path('notification.mp3'))
@if(file_exists($notificationAudioPath))
<audio id="myAudio" preload="auto" src="{{ asset('notification.mp3') }}"></audio>
@endif
<script>
const notificationConfig = {
    pollUrl: @json(
        auth()->user()->type === 'admin'
            ? route('admin.notifications')
            : (auth()->user()->restaurant ? url('/restaurant/notifications/' . auth()->user()->restaurant->id) : null)
    ),
    orderBaseUrl: @json(auth()->user()->type === 'admin' ? url('/admin/show_order') : url('/restaurant/show_order')),
    userType: @json(auth()->user()->type),
};

let notificationSoundUnlocked = false;
let lastNotificationSoundAt = 0;

function unlockNotificationSound() {
    notificationSoundUnlocked = true;
    const audio = document.getElementById('myAudio');
    if (!audio) {
        return;
    }

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

var _adminAudioCtx = null;

function playNotificationSound() {
    const now = Date.now();
    if (now - lastNotificationSoundAt < 4000) return;
    lastNotificationSoundAt = now;

    // mp3 en priorité, oscillateur en fallback si mp3 absent
    const audio = document.getElementById('myAudio');
    if (audio && notificationSoundUnlocked) {
        audio.currentTime = 0;
        const p = audio.play();
        if (p && typeof p.catch === 'function') {
            p.catch(function () { _playAdminOscillator(); });
        }
    } else {
        _playAdminOscillator();
    }
}

function _playAdminOscillator() {
    if (!notificationSoundUnlocked) return;
    try {
        var C = window.AudioContext || window.webkitAudioContext;
        if (!C) return;
        if (!_adminAudioCtx || _adminAudioCtx.state === 'closed') _adminAudioCtx = new C();
        if (_adminAudioCtx.state === 'suspended') _adminAudioCtx.resume();
        var ctx = _adminAudioCtx;
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
    if (!notificationConfig.pollUrl) {
        return;
    }

    $.ajax({
        type: "GET",
        url: notificationConfig.pollUrl,
        dataType: 'json',
        success: function(data) {
            var value = '';
            var orders = Array.isArray(data.orders) ? data.orders : [];

            if (data.count > 0) {
                orders.forEach(function(element) {
                    var label = '#' + element.order_no;
                    if (element.restaurant_name) {
                        label += ' - ' + element.restaurant_name;
                    }

                    value += ` <a href="` + notificationConfig.orderBaseUrl + `/` + element.order_no + `" class="dropdown-item">
                            <i class="fas fa-envelope mr-2"></i>` + label + `<span class="float-right text-muted text-sm">
                            ` + (element.time || '') + `</span> </a> <div class="dropdown-divider"></div>`;
                });
            } else {
                value += `<a class="dropdown-item text-center">
                            <b>Aucune nouvelle notification</b>
                          </a> <div class="dropdown-divider"></div>`;
            }

            if (document.getElementById('notiBody')) {
                document.getElementById('notiBody').innerHTML = value;
            }
            if (document.getElementById('notiTitle')) {
                document.getElementById('notiTitle').innerHTML = data.count + ' Notifications';
            }
            if (document.getElementById('notiBell')) {
                document.getElementById('notiBell').innerHTML = data.count;
            }
            if (data.new) {
                playNotificationSound();
            }
        },
        error: function(xhr, status, error) {
            console.error('Erreur lors de la récupération des notifications:', error);
        }
    });
}

get_notification();
setInterval(get_notification, 5000);
</script>
@endif
@yield('script')
<script>
    $(function () {
        $('[data-toggle="tooltip"]').tooltip()
        var liveTime = document.getElementById('bd-live-time');
        if (liveTime) {
            var updateLiveTime = function () {
                var now = new Date();
                liveTime.textContent = now.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' });
            };
            updateLiveTime();
            setInterval(updateLiveTime, 1000);
        }
    })
</script>
</body>
</html>
