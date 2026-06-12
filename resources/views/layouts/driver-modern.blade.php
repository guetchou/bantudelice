<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Espace livreur | BantuDelice')</title>
    <script>(function(){var t=localStorage.getItem('bd-theme');if(t)document.documentElement.setAttribute('data-theme',t);})();</script>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Poppins:wght@400;500;600;700&family=League+Spartan:wght@600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer">

    @yield('head_extra')

    <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    /* ── Variables mode clair ───────────────────────── */
    :root {
        --bd-green:       #009543;
        --bd-green-dark:  #007836;
        --bd-green-pale:  #f0fdf4;
        --bd-green-mid:   rgba(0,149,67,.12);
        --bd-green-glow:  rgba(0,149,67,.20);

        --bd-bg:          #f4f5f7;
        --bd-surface:     #ffffff;
        --bd-surface-2:   #f9fafb;
        --bd-border:      #e5e7eb;
        --bd-border-2:    #f3f4f6;

        --bd-text:        #111827;
        --bd-text-2:      #4b5563;
        --bd-text-3:      #9ca3af;

        --bd-shadow-sm:   0 1px 2px rgba(0,0,0,.06);
        --bd-shadow:      0 1px 3px rgba(0,0,0,.10), 0 1px 2px rgba(0,0,0,.06);
        --bd-shadow-md:   0 4px 12px rgba(0,0,0,.08);

        --bd-radius:      8px;
        --bd-radius-lg:   12px;
        --bd-sidebar-w:   260px;
        --bd-topbar-h:    56px;
        --bd-font:        'Poppins', 'Inter', sans-serif;
        --bd-font-body:   'Inter', sans-serif;
        --bd-font-display:'League Spartan', 'Poppins', sans-serif;

        --bd-sb-bg:       #ffffff;
        --bd-sb-border:   #e5e7eb;
        --bd-sb-item-hover:#f4f5f7;
        --bd-sb-active-bg:#f0fdf4;
        --bd-sb-active-c: #009543;
        --bd-sb-text:     #4b5563;
        --bd-sb-label:    #9ca3af;

        --bd-tb-bg:       #ffffff;
        --bd-tb-border:   #e5e7eb;

        /* Aliases for child views */
        --c-primary:      #ff5a1f;
        --c-primary-dk:   #e04d15;
        --c-dark:         #1A1A2E;
        --c-dark-2:       #16213E;
        --c-green:        #009543;
        --c-green-lt:     #22c55e;
        --c-warn:         #F59E0B;
        --c-err:          #EF4444;
        --c-info:         #3B82F6;
        --c-bg:           #f4f5f7;
        --c-surface:      #ffffff;
        --c-border:       #e5e7eb;
        --c-text:         #111827;
        --c-text-2:       #374151;
        --c-text-muted:   #6B7280;
        --c-text-dim:     #9CA3AF;
        --font-body:      'Poppins', 'Inter', sans-serif;
        --font-display:   'League Spartan', 'Poppins', sans-serif;
    }

    /* ── Variables mode sombre ──────────────────────── */
    [data-theme="dark"] {
        --bd-bg:          #0f1117;
        --bd-surface:     #1a1d27;
        --bd-surface-2:   #22263a;
        --bd-border:      rgba(255,255,255,.09);
        --bd-border-2:    rgba(255,255,255,.05);
        --bd-text:        #f0f4f8;
        --bd-text-2:      #8892a4;
        --bd-text-3:      #4a5568;
        --bd-shadow-sm:   0 1px 4px rgba(0,0,0,.30);
        --bd-shadow:      0 2px 8px rgba(0,0,0,.40);
        --bd-shadow-md:   0 4px 20px rgba(0,0,0,.50);
        --bd-sb-bg:       #0d1016;
        --bd-sb-border:   rgba(255,255,255,.07);
        --bd-sb-item-hover:rgba(255,255,255,.05);
        --bd-sb-active-bg:rgba(0,149,67,.14);
        --bd-sb-active-c: #00c957;
        --bd-sb-text:     #8892a4;
        --bd-sb-label:    #4a5568;
        --bd-tb-bg:       #1a1d27;
        --bd-tb-border:   rgba(255,255,255,.07);
        /* Aliases */
        --c-bg:           #0f1117;
        --c-surface:      #1a1d27;
        --c-border:       rgba(255,255,255,.09);
        --c-text:         #f0f4f8;
        --c-text-2:       #8892a4;
        --c-text-muted:   #6b7280;
        --c-text-dim:     #4a5568;
    }

    html, body { height: 100%; }
    body {
        display: flex;
        background: var(--bd-bg);
        font-family: var(--bd-font);
        color: var(--bd-text);
        min-height: 100vh;
        font-size: 14px;
        line-height: 1.5;
        -webkit-font-smoothing: antialiased;
        transition: background .2s, color .2s;
    }
    a { text-decoration: none; color: inherit; }

    /* ── SIDEBAR ──────────────────────────────────────── */
    .bd-drv-sidebar {
        width: var(--bd-sidebar-w);
        flex-shrink: 0;
        position: sticky;
        top: 0; height: 100vh;
        overflow-y: auto;
        background: var(--bd-sb-bg);
        border-right: 1px solid var(--bd-sb-border);
        display: flex; flex-direction: column;
        transition: background .2s, transform .25s ease;
        z-index: 200;
    }
    .bd-drv-sidebar::-webkit-scrollbar { width: 4px; }
    .bd-drv-sidebar::-webkit-scrollbar-thumb { background: var(--bd-border); border-radius: 2px; }

    /* Brand */
    .bd-drv-brand {
        display: flex; align-items: center; gap: 10px;
        padding: 16px 16px 14px;
        border-bottom: 1px solid var(--bd-sb-border);
        flex-shrink: 0;
    }
    .bd-drv-brand-mark {
        width: 36px; height: 36px;
        border-radius: 8px; overflow: hidden;
        background: var(--bd-green);
        display: flex; align-items: center; justify-content: center;
        flex-shrink: 0;
    }
    .bd-drv-brand-mark img { width: 100%; height: 100%; object-fit: cover; }
    .bd-drv-brand-title {
        font-size: 13px; font-weight: 600;
        color: var(--bd-text);
        white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
        line-height: 1.3;
    }
    .bd-drv-brand-sub {
        font-size: 11px; font-weight: 400;
        color: var(--bd-text-3);
        line-height: 1.3; margin-top: 1px;
    }

    /* Nav */
    .bd-drv-nav { flex: 1; padding: 8px; overflow-y: auto; }
    .bd-drv-nav-label {
        display: block; padding: 8px 8px 4px;
        font-size: 10px; font-weight: 700;
        letter-spacing: .08em; text-transform: uppercase;
        color: var(--bd-sb-label);
    }
    .bd-drv-nav-item {
        display: flex; align-items: center; gap: 10px;
        padding: 9px 10px;
        border-radius: 7px;
        color: var(--bd-sb-text);
        font-size: 13px; font-weight: 500;
        transition: background .12s, color .12s;
        margin-bottom: 1px;
        border-left: 3px solid transparent;
    }
    .bd-drv-nav-item:hover {
        background: var(--bd-sb-item-hover);
        color: var(--bd-text);
    }
    .bd-drv-nav-item.is-active {
        background: var(--bd-sb-active-bg);
        color: var(--bd-sb-active-c);
        font-weight: 600;
        border-left-color: var(--bd-green);
    }
    .bd-drv-nav-item i {
        width: 16px; text-align: center;
        font-size: 13px; flex-shrink: 0;
    }
    .bd-drv-nav-badge {
        margin-left: auto;
        background: rgba(245,158,11,.15);
        color: #d97706;
        font-size: 10px; font-weight: 700;
        padding: 2px 7px; border-radius: 100px;
    }
    [data-theme="dark"] .bd-drv-nav-badge {
        background: rgba(251,191,36,.15);
        color: #fbbf24;
    }

    /* Sidebar footer */
    .bd-drv-sidebar-foot {
        border-top: 1px solid var(--bd-sb-border);
        padding: 10px 12px;
        flex-shrink: 0;
        display: flex; flex-direction: column; gap: 6px;
    }
    .bd-drv-user-row {
        display: flex; align-items: center; gap: 9px;
    }
    .bd-drv-avatar {
        width: 30px; height: 30px; border-radius: 50%;
        background: var(--bd-green);
        display: flex; align-items: center; justify-content: center;
        font-size: 11px; font-weight: 700; color: #fff;
        flex-shrink: 0;
    }
    .bd-drv-user-name {
        font-size: 12px; font-weight: 600; color: var(--bd-text);
        white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
        flex: 1;
    }
    .bd-drv-user-phone { font-size: 11px; color: var(--bd-text-3); margin-top: 1px; }
    .bd-drv-status {
        display: inline-flex; align-items: center; gap: 5px;
        padding: 3px 9px; border-radius: 99px;
        font-size: 11px; font-weight: 600;
    }
    .bd-drv-status.online  { background: rgba(34,197,94,.1); color: #16a34a; border: 1px solid rgba(34,197,94,.25); }
    .bd-drv-status.offline { background: var(--bd-surface-2); color: var(--bd-text-3); border: 1px solid var(--bd-border); }
    [data-theme="dark"] .bd-drv-status.online { background: rgba(0,201,87,.12); color: #00c957; border-color: rgba(0,201,87,.25); }
    .bd-drv-status-dot { width: 6px; height: 6px; border-radius: 50%; background: currentColor; }
    .bd-drv-foot-btn {
        display: flex; align-items: center; gap: 10px;
        width: 100%; padding: 7px 10px;
        border-radius: 6px; border: none;
        background: transparent;
        color: var(--bd-sb-text);
        font-size: 12px; font-weight: 500;
        font-family: var(--bd-font); cursor: pointer;
        transition: background .12s, color .12s;
    }
    .bd-drv-foot-btn:hover {
        background: var(--bd-sb-item-hover);
        color: var(--bd-text);
    }

    /* ── MAIN ─────────────────────────────────────────── */
    .bd-drv-main {
        flex: 1; min-width: 0;
        display: flex; flex-direction: column;
        background: var(--bd-bg);
        transition: background .2s;
    }

    /* Topbar */
    .bd-drv-topbar {
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
    .bd-drv-topbar-meta { display: flex; align-items: center; gap: 14px; }
    .bd-drv-topbar-title {
        font-size: 15px; font-weight: 600;
        color: var(--bd-text); letter-spacing: -.01em;
    }
    .bd-drv-topbar-date {
        font-size: 12px; color: var(--bd-text-3);
        border-left: 1px solid var(--bd-border);
        padding-left: 14px;
    }
    .bd-drv-topbar-actions { display: flex; align-items: center; gap: 8px; }

    /* Search bar */
    .bd-drv-topbar-search {
        display: flex; align-items: center; gap: 8px;
        background: var(--bd-surface-2);
        border: 1px solid var(--bd-border);
        border-radius: 7px;
        padding: 0 12px;
        height: 34px;
        min-width: 180px; max-width: 280px;
        flex: 1;
        transition: border-color .15s;
    }
    .bd-drv-topbar-search:focus-within { border-color: var(--bd-green); }
    .bd-drv-topbar-search i { font-size: 13px; color: var(--bd-text-3); flex-shrink: 0; }
    .bd-drv-topbar-search input {
        border: none; background: transparent;
        font-size: 12px; font-family: var(--bd-font);
        color: var(--bd-text); outline: none;
        width: 100%; height: 100%;
    }
    .bd-drv-topbar-search input::placeholder { color: var(--bd-text-3); }

    /* Theme toggle */
    .bd-theme-toggle {
        width: 34px; height: 34px;
        display: inline-flex; align-items: center; justify-content: center;
        border-radius: 7px;
        border: 1px solid var(--bd-border);
        background: var(--bd-surface);
        color: var(--bd-text-2);
        cursor: pointer; transition: all .15s;
        font-size: 14px;
    }
    .bd-theme-toggle:hover { border-color: var(--bd-green); color: var(--bd-green); }

    /* Burger */
    .bd-drv-burger {
        display: none;
        border: none; background: none; cursor: pointer;
        padding: 4px 6px; border-radius: 6px;
        color: var(--bd-text-2); font-size: .95rem;
    }
    .bd-drv-burger:hover { background: var(--bd-border); }

    /* Content */
    .bd-drv-content {
        flex: 1;
        padding: 24px 28px;
        min-width: 0;
    }
    .bd-drv-content-inner {
        max-width: 1100px;
        margin: 0 auto;
    }

    /* Footer */
    .bd-drv-footer {
        padding: 12px 28px;
        font-size: 12px;
        color: var(--bd-text-3);
        border-top: 1px solid var(--bd-border);
    }

    /* ── Overlay mobile ──────────────────────────────── */
    .bd-drv-overlay {
        display: none;
        position: fixed; inset: 0;
        background: rgba(0,0,0,.45);
        z-index: 199;
    }

    /* ── RESPONSIVE ───────────────────────────────────── */
    @media (max-width: 1024px) {
        .bd-drv-sidebar {
            position: fixed; left: 0; top: 0; bottom: 0;
            transform: translateX(-100%);
        }
        .bd-drv-sidebar.is-open {
            transform: translateX(0);
            box-shadow: 4px 0 30px rgba(0,0,0,.2);
        }
        .bd-drv-sidebar.is-open + .bd-drv-main .bd-drv-overlay { display: block; }
        .bd-drv-main { width: 100%; }
        .bd-drv-burger { display: flex; }
        .bd-drv-topbar-date { display: none; }
        .bd-drv-content { padding: 16px; }
    }
    </style>

    @yield('style')
</head>
<body>

<aside class="bd-drv-sidebar" id="drvSidebar">

    <a href="{{ route('driver.deliveries') }}" class="bd-drv-brand">
        <span class="bd-drv-brand-mark">
            <img src="{{ asset('frontend/images/BuntuDelice.png') }}" alt="{{ \App\Services\ConfigService::getCompanyName() }}">
        </span>
        <span style="min-width:0;">
            <span class="bd-drv-brand-title">{{ \App\Services\ConfigService::getCompanyName() }}</span>
            <span class="bd-drv-brand-sub">Espace livreur</span>
        </span>
    </a>

    <nav class="bd-drv-nav">

        <span class="bd-drv-nav-label">Missions</span>

        <a href="{{ route('driver.deliveries') }}" class="bd-drv-nav-item @yield('nav_deliveries')">
            <i class="fas fa-motorcycle"></i>
            <span>Mes livraisons</span>
            @yield('nav_deliveries_badge')
        </a>

        @php $txRoute = app('router')->has('driver.transport.dashboard') ? 'driver.transport.dashboard' : (app('router')->has('driver.transport.index') ? 'driver.transport.index' : null); @endphp
        @if($txRoute)
        <a href="{{ route($txRoute) }}" class="bd-drv-nav-item @yield('nav_transport')">
            <i class="fas fa-car"></i>
            <span>Transport</span>
        </a>
        @endif

        <span class="bd-drv-nav-label">Finances</span>

        @if(app('router')->has('driver.gains'))
        <a href="{{ route('driver.gains') }}" class="bd-drv-nav-item @yield('nav_gains')">
            <i class="fas fa-wallet"></i>
            <span>Gains</span>
        </a>
        @endif

        @if(app('router')->has('driver.historique'))
        <a href="{{ route('driver.historique') }}" class="bd-drv-nav-item @yield('nav_historique')">
            <i class="fas fa-clock-rotate-left"></i>
            <span>Historique</span>
        </a>
        @endif

        <span class="bd-drv-nav-label">Profil</span>

        @if(app('router')->has('driver.documents'))
        <a href="{{ route('driver.documents') }}" class="bd-drv-nav-item @yield('nav_documents')">
            <i class="fas fa-folder-open"></i>
            <span>Mes documents</span>
        </a>
        @endif

        @if(app('router')->has('driver.note'))
        <a href="{{ route('driver.note') }}" class="bd-drv-nav-item @yield('nav_note')">
            <i class="fas fa-star"></i>
            <span>Note & avis</span>
        </a>
        @endif

        @if(app('router')->has('driver.support'))
        <a href="{{ route('driver.support') }}" class="bd-drv-nav-item @yield('nav_support')">
            <i class="fas fa-headset"></i>
            <span>Support</span>
        </a>
        @endif

    </nav>

    <div class="bd-drv-sidebar-foot">
        <div class="bd-drv-user-row">
            <div class="bd-drv-avatar">@yield('driver_initials', 'LI')</div>
            <div style="flex:1;min-width:0;">
                <div class="bd-drv-user-name">@yield('driver_name', 'Livreur')</div>
                <div class="bd-drv-user-phone">@yield('driver_phone', '')</div>
            </div>
        </div>
        <form method="POST" action="{{ route('logout') }}" style="margin:0;">
            @csrf
            <button type="submit" class="bd-drv-foot-btn">
                <i class="fas fa-power-off"></i>
                <span>Déconnexion</span>
            </button>
        </form>
    </div>

</aside>

<div class="bd-drv-main">

    <div class="bd-drv-overlay" id="drvOverlay" onclick="drvToggleSidebar()"></div>

    <header class="bd-drv-topbar">
        <div class="bd-drv-topbar-meta">
            <button class="bd-drv-burger" id="drvBurger" onclick="drvToggleSidebar()">
                <i class="fas fa-bars"></i>
            </button>
            <span class="bd-drv-topbar-title">@yield('page_title', 'Dashboard')</span>
            <span class="bd-drv-topbar-date">{{ \Carbon\Carbon::now()->locale('fr')->isoFormat('dddd D MMMM YYYY') }}</span>
        </div>
        <div class="bd-drv-topbar-search">
            <i class="fas fa-search"></i>
            <input type="text" id="drvSearchInput" placeholder="Rechercher une commande..." autocomplete="off">
        </div>
        <div class="bd-drv-topbar-actions">
            @yield('topbar_right')
            <button class="bd-theme-toggle" id="bdThemeToggle" title="Mode sombre / clair">
                <i class="fas fa-moon" id="bdThemeIcon"></i>
            </button>
        </div>
    </header>

    <main class="bd-drv-content">
        <div class="bd-drv-content-inner">
            @yield('content')
        </div>
    </main>

    <footer class="bd-drv-footer">
        <strong>&copy; {{ date('Y') }} <a href="{{ route('home') }}">{{ \App\Services\ConfigService::getCompanyName() }}</a></strong> &mdash; Tous droits réservés.
    </footer>

</div>

<script>
function drvToggleSidebar() {
    var s = document.getElementById('drvSidebar');
    var o = document.getElementById('drvOverlay');
    var open = s.classList.toggle('is-open');
    o.style.display = open ? 'block' : 'none';
    document.body.style.overflow = open ? 'hidden' : '';
}
window.addEventListener('resize', function() {
    if (window.innerWidth > 1024) {
        document.getElementById('drvSidebar').classList.remove('is-open');
        document.getElementById('drvOverlay').style.display = 'none';
        document.body.style.overflow = '';
    }
});

/* Dark mode toggle */
(function () {
    var saved = localStorage.getItem('bd-theme') || 'light';
    document.documentElement.setAttribute('data-theme', saved);
    var icon = document.getElementById('bdThemeIcon');
    if (icon) icon.className = saved === 'dark' ? 'fas fa-sun' : 'fas fa-moon';
})();
document.getElementById('bdThemeToggle').addEventListener('click', function () {
    var html = document.documentElement;
    var isDark = html.getAttribute('data-theme') === 'dark';
    var next = isDark ? 'light' : 'dark';
    html.setAttribute('data-theme', next);
    localStorage.setItem('bd-theme', next);
    document.getElementById('bdThemeIcon').className = next === 'dark' ? 'fas fa-sun' : 'fas fa-moon';
});
</script>

@yield('script')
</body>
</html>
