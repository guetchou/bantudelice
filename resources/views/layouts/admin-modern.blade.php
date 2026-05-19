<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin | BantuDelice')</title>

    <!-- Manrope font -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

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
        --adm-sidebar-bg: #111827;
        --adm-accent: #1e3a5f;
        --adm-green: #22c55e;
        --adm-text-muted: #6b7280;
        --adm-text-dim: #9ca3af;
    }

    html, body { height: 100%; }

    /* ── BODY ─────────────────────────────────────────────── */
    .adm-body {
        display: flex;
        background: var(--adm-bg);
        font-family: 'Manrope', system-ui, sans-serif;
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
    .adm-sidebar::-webkit-scrollbar-thumb { background: rgba(255,255,255,.12); border-radius: 4px; }

    /* Logo zone */
    .adm-logo {
        padding: 20px;
        display: flex;
        align-items: center;
        gap: 10px;
        border-bottom: 1px solid #1f2937;
        flex-shrink: 0;
        text-decoration: none;
    }

    .adm-logo-circle {
        width: 36px;
        height: 36px;
        border-radius: 10px;
        background: linear-gradient(135deg, #f97316, #fb923c);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1rem;
        font-weight: 900;
        color: #fff;
        flex-shrink: 0;
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
        box-shadow: 0 0 0 3px rgba(34,197,94,.2);
        margin-left: auto;
        flex-shrink: 0;
    }

    /* Nav section label */
    .adm-nav-section {
        padding: 14px 20px 4px;
        font-size: .62rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .12em;
        color: var(--adm-text-muted);
    }

    /* Nav items container */
    .adm-nav {
        display: flex;
        flex-direction: column;
        padding: 8px 0;
        flex: 1;
    }

    /* Nav item */
    .adm-nav-item {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 10px 20px;
        border-radius: 8px;
        margin: 2px 10px;
        cursor: pointer;
        font-size: .82rem;
        font-weight: 600;
        color: var(--adm-text-dim);
        text-decoration: none;
        transition: background .15s, color .15s;
        white-space: nowrap;
        overflow: hidden;
    }

    .adm-nav-item:hover {
        background: #1e3a5f;
        color: #fff;
    }

    .adm-nav-item.is-active {
        background: #1e3a5f;
        color: #fff;
    }

    .adm-nav-item .adm-nav-icon {
        width: 18px;
        text-align: center;
        color: inherit;
        font-size: .9rem;
        flex-shrink: 0;
    }

    /* Bottom user zone */
    .adm-sidebar-bottom {
        margin-top: auto;
        padding: 16px 20px;
        border-top: 1px solid #1f2937;
        display: flex;
        align-items: center;
        gap: 10px;
        flex-shrink: 0;
    }

    .adm-avatar {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        background: #1e3a5f;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: .75rem;
        font-weight: 800;
        color: #fff;
        flex-shrink: 0;
        border: 2px solid rgba(255,255,255,.1);
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
        background: #f1f5f9;
        border: 1px solid #e5e7eb;
        transition: all .15s;
        white-space: nowrap;
    }

    .adm-ws-tab.active,
    .adm-ws-tab:hover {
        background: #1e3a5f;
        color: #fff;
        border-color: #1e3a5f;
    }

    /* Period selector */
    .adm-period-tabs {
        display: flex;
        gap: 3px;
    }

    .adm-period-tab {
        padding: 4px 10px;
        border-radius: 6px;
        font-size: .68rem;
        font-weight: 700;
        text-decoration: none;
        color: var(--adm-text-muted);
        background: #f8fafc;
        border: 1px solid #e5e7eb;
        transition: all .15s;
        white-space: nowrap;
    }

    .adm-period-tab.active {
        background: #e0f2fe;
        color: #0369a1;
        border-color: #bae6fd;
    }

    .adm-period-tab:hover:not(.active) {
        background: #f1f5f9;
        color: #374151;
    }

    /* Topbar avatar */
    .adm-topbar-avatar {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        background: linear-gradient(135deg, #3b82f6, #6366f1);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: .72rem;
        font-weight: 800;
        color: #fff;
        cursor: pointer;
        border: 2px solid #e5e7eb;
        flex-shrink: 0;
    }

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

    /* ── RESPONSIVE ───────────────────────────────────────── */
    @media (max-width: 1024px) {
        .adm-sidebar {
            transform: translateX(-100%);
            transition: transform .25s;
        }
        .adm-sidebar.is-open {
            transform: translateX(0);
        }
        .adm-main {
            margin-left: 0;
            width: 100%;
        }
    }

    @media (max-width: 768px) {
        .adm-topbar {
            padding: 0 16px;
        }
        .adm-content {
            padding: 16px;
        }
        .adm-ws-tabs,
        .adm-period-tabs {
            display: none;
        }
    }
    </style>

    @yield('style')
</head>
<body class="adm-body" data-nav-active="@yield('nav_active')">

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

            <!-- Section Menu principal -->
            <div class="adm-nav-section">Menu</div>

            <a href="/admin?workspace=bantudelice" class="adm-nav-item" data-nav="dashboard">
                <span class="adm-nav-icon"><i class="fas fa-home"></i></span>
                Dashboard
            </a>
            <a href="/admin/all_orders" class="adm-nav-item" data-nav="orders">
                <span class="adm-nav-icon"><i class="fas fa-shopping-bag"></i></span>
                Commandes
            </a>
            <a href="/admin/restaurant" class="adm-nav-item" data-nav="restaurants">
                <span class="adm-nav-icon"><i class="fas fa-utensils"></i></span>
                Restaurants
            </a>
            <a href="/admin/restaurants/paused" class="adm-nav-item" data-nav="restaurants-paused">
                <span class="adm-nav-icon"><i class="fas fa-pause-circle"></i></span>
                Restaurants pausés
            </a>
            <a href="/admin/driver" class="adm-nav-item" data-nav="drivers">
                <span class="adm-nav-icon"><i class="fas fa-motorcycle"></i></span>
                Livreurs
            </a>
            <a href="/admin/user" class="adm-nav-item" data-nav="users">
                <span class="adm-nav-icon"><i class="fas fa-users"></i></span>
                Utilisateurs
            </a>

            <!-- Section Catalogue -->
            <div class="adm-nav-section">Catalogue</div>

            <a href="/admin/cuisine" class="adm-nav-item" data-nav="cuisine">
                <span class="adm-nav-icon"><i class="fas fa-tag"></i></span>
                Cuisines
            </a>
            <a href="/admin/all-products" class="adm-nav-item" data-nav="products">
                <span class="adm-nav-icon"><i class="fas fa-hamburger"></i></span>
                Produits
            </a>
            <a href="/admin/extras" class="adm-nav-item" data-nav="extras">
                <span class="adm-nav-icon"><i class="fas fa-plus-square"></i></span>
                Extras
            </a>
            <a href="/admin/promotions" class="adm-nav-item" data-nav="promotions">
                <span class="adm-nav-icon"><i class="fas fa-percent"></i></span>
                Promotions
            </a>
            <a href="/admin/news" class="adm-nav-item" data-nav="news">
                <span class="adm-nav-icon"><i class="fas fa-bullhorn"></i></span>
                Actualités
            </a>

            <!-- Section Modules -->
            <div class="adm-nav-section">Modules</div>

            <a href="/admin/transport" class="adm-nav-item" data-nav="transport">
                <span class="adm-nav-icon"><i class="fas fa-car"></i></span>
                Transport
            </a>
            <a href="/admin/colis" class="adm-nav-item" data-nav="colis">
                <span class="adm-nav-icon"><i class="fas fa-box"></i></span>
                Colis / Mema
            </a>
            <a href="/admin/vehicle" class="adm-nav-item" data-nav="vehicles">
                <span class="adm-nav-icon"><i class="fas fa-car-side"></i></span>
                Véhicules
            </a>
            <a href="/admin/relay-points" class="adm-nav-item" data-nav="relay-points">
                <span class="adm-nav-icon"><i class="fas fa-map-marker-alt"></i></span>
                Points relais
            </a>

            <!-- Section Finance -->
            <div class="adm-nav-section">Finance</div>

            <a href="/admin/payments/dashboard" class="adm-nav-item" data-nav="payments">
                <span class="adm-nav-icon"><i class="fas fa-wallet"></i></span>
                Paiements
            </a>
            <a href="/admin/restaurant_payout" class="adm-nav-item" data-nav="payouts-restaurants">
                <span class="adm-nav-icon"><i class="fas fa-store"></i></span>
                Virements restaurants
            </a>
            <a href="/admin/driver_payout" class="adm-nav-item" data-nav="payouts-drivers">
                <span class="adm-nav-icon"><i class="fas fa-money-bill-wave"></i></span>
                Virements livreurs
            </a>
            <a href="/admin/commerce-analytics" class="adm-nav-item" data-nav="commerce-analytics">
                <span class="adm-nav-icon"><i class="fas fa-chart-line"></i></span>
                Analytique
            </a>

            <!-- Section Support -->
            <div class="adm-nav-section">Support</div>

            <a href="/admin/support-tickets" class="adm-nav-item" data-nav="support">
                <span class="adm-nav-icon"><i class="fas fa-headset"></i></span>
                Support
            </a>

            <!-- Section Configuration -->
            <div class="adm-nav-section">Configuration</div>

            <a href="/admin/charge" class="adm-nav-item" data-nav="settings">
                <span class="adm-nav-icon"><i class="fas fa-cog"></i></span>
                Paramètres
            </a>
            <a href="/admin/weather-surcharge" class="adm-nav-item" data-nav="weather-surcharge">
                <span class="adm-nav-icon"><i class="fas fa-cloud-rain"></i></span>
                Surcharge météo
            </a>
            <a href="/admin/home-content" class="adm-nav-item" data-nav="cms">
                <span class="adm-nav-icon"><i class="fas fa-newspaper"></i></span>
                CMS
            </a>
            <a href="/admin/modules" class="adm-nav-item" data-nav="modules">
                <span class="adm-nav-icon"><i class="fas fa-puzzle-piece"></i></span>
                Modules
            </a>
            <a href="/admin/api-configuration" class="adm-nav-item" data-nav="api-config">
                <span class="adm-nav-icon"><i class="fas fa-key"></i></span>
                API / Intégrations
            </a>
            <a href="/admin/metrics" class="adm-nav-item" data-nav="metrics">
                <span class="adm-nav-icon"><i class="fas fa-chart-bar"></i></span>
                Métriques
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
                    style="display:none;border:none;background:none;cursor:pointer;padding:4px 8px;border-radius:6px;color:#6b7280;"
                    onclick="document.getElementById('admSidebar').classList.toggle('is-open')">
                    <i class="fas fa-bars" style="font-size:1rem;"></i>
                </button>
                <span class="adm-page-title">@yield('page_title', 'Tableau de bord')</span>
            </div>

            <div class="adm-topbar-right">
                <!-- Workspace tabs -->
                <div class="adm-ws-tabs">
                    <a href="/admin?workspace=bantudelice" class="adm-ws-tab {{ request('workspace','bantudelice') === 'bantudelice' ? 'active' : '' }}">BantuDelice</a>
                    <a href="/admin?workspace=kende" class="adm-ws-tab {{ request('workspace') === 'kende' ? 'active' : '' }}">Kende</a>
                    <a href="/admin?workspace=mema" class="adm-ws-tab {{ request('workspace') === 'mema' ? 'active' : '' }}">Mema</a>
                </div>

                <div class="adm-topbar-divider"></div>

                <!-- Period selector -->
                <div class="adm-period-tabs">
                    @foreach([7, 30, 90] as $p)
                        <a href="{{ request()->fullUrlWithQuery(['period' => $p]) }}"
                           class="adm-period-tab {{ (int)request('period', 30) === $p ? 'active' : '' }}">
                            {{ $p }}j
                        </a>
                    @endforeach
                </div>

                <div class="adm-topbar-divider"></div>

                <!-- Avatar topbar -->
                <div class="adm-topbar-avatar" title="{{ auth()->user()->name ?? 'Admin' }}">
                    {{ strtoupper(substr(auth()->user()->name ?? 'A', 0, 1)) }}
                </div>
            </div>
        </header>

        <!-- Content -->
        <main class="adm-content">
            @yield('content')
        </main>

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

    // Mobile sidebar toggle visibility
    (function() {
        function checkWidth() {
            var toggle = document.getElementById('admSidebarToggle');
            if (!toggle) return;
            toggle.style.display = window.innerWidth <= 1024 ? 'inline-flex' : 'none';
        }
        checkWidth();
        window.addEventListener('resize', checkWidth);
    })();
    </script>

    @yield('scripts')
    @yield('script')
    @stack('scripts')

</body>
</html>
