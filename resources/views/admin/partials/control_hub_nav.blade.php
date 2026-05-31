@php
    $cmsWorkspaceQuery = request('workspace') ? ['workspace' => request('workspace')] : [];
    $controlWorkspace = request('workspace', 'bantudelice');
    $controlWorkspaceLabel = $controlWorkspace === 'kende' ? 'Kende' : ($controlWorkspace === 'mema' ? 'Mema' : 'BantuDelice');
    $embeddedInTopbar = $embeddedInTopbar ?? false;
    $isDashboardRoute = request()->routeIs('admin.dashboard');
    $topbarSectionLabel = 'Pilotage';
    if (request()->routeIs('admin.cms.contents.*')) {
        $topbarSectionLabel = 'Contenus';
    } elseif (request()->routeIs('admin.cms.content-types.*')) {
        $topbarSectionLabel = 'Types';
    } elseif (request()->routeIs('admin.cms.media.*')) {
        $topbarSectionLabel = 'Mediatheque';
    } elseif (request()->routeIs('admin.modules.*')) {
        $topbarSectionLabel = 'Modules';
    } elseif (request()->routeIs('admin.home-content.*')) {
        $topbarSectionLabel = 'Accueil';
    } elseif (request()->routeIs('admin.metrics*')) {
        $topbarSectionLabel = 'Observabilite';
    } elseif (request()->routeIs('admin.payments.dashboard')) {
        $topbarSectionLabel = 'Paiements';
    }
@endphp

@if($embeddedInTopbar && !$isDashboardRoute)
    <div class="bd-control-hub-nav bd-control-hub-nav--topbar">
        <span class="bd-control-hub-nav__badge">{{ $controlWorkspaceLabel }}</span>
        <span class="bd-control-hub-nav__separator" aria-hidden="true">/</span>
        <span class="bd-control-hub-nav__current">{{ $topbarSectionLabel }}</span>
    </div>
@elseif(empty($renderAdminHubInTopbar) && !$isDashboardRoute)
    <div class="bd-control-hub-nav {{ $embeddedInTopbar ? 'bd-control-hub-nav--topbar' : '' }}">
        <span class="bd-control-hub-nav__badge">{{ $controlWorkspaceLabel }}</span>
        <a href="{{ route('admin.dashboard', $cmsWorkspaceQuery) }}" class="bd-control-hub-nav__link {{ request()->routeIs('admin.dashboard') ? 'is-active' : '' }}">
            Pilotage
        </a>
        <a href="{{ route('admin.cms.contents.index', $cmsWorkspaceQuery) }}" class="bd-control-hub-nav__link {{ request()->routeIs('admin.cms.contents.*') ? 'is-active' : '' }}">
            Contenus
        </a>
        <a href="{{ route('admin.cms.content-types.index', $cmsWorkspaceQuery) }}" class="bd-control-hub-nav__link {{ request()->routeIs('admin.cms.content-types.*') ? 'is-active' : '' }}">
            Types
        </a>
        <a href="{{ route('admin.cms.media.index', $cmsWorkspaceQuery) }}" class="bd-control-hub-nav__link {{ request()->routeIs('admin.cms.media.*') ? 'is-active' : '' }}">
            Mediatheque
        </a>
        <a href="{{ route('admin.modules.index', $cmsWorkspaceQuery) }}" class="bd-control-hub-nav__link {{ request()->routeIs('admin.modules.*') ? 'is-active' : '' }}">
            Modules
        </a>
        <a href="{{ route('admin.home-content.edit', $cmsWorkspaceQuery) }}" class="bd-control-hub-nav__link {{ request()->routeIs('admin.home-content.*') ? 'is-active' : '' }}">
            Accueil
        </a>
        <a href="{{ route('admin.metrics', $cmsWorkspaceQuery) }}" class="bd-control-hub-nav__link {{ request()->routeIs('admin.metrics*') ? 'is-active' : '' }}">
            Observabilite
        </a>
        <a href="{{ route('admin.payments.dashboard', $cmsWorkspaceQuery) }}" class="bd-control-hub-nav__link {{ request()->routeIs('admin.payments.dashboard') ? 'is-active' : '' }}">
            Paiements
        </a>
    </div>
@endif

<style>
    .bd-control-hub-nav {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 10px;
        margin: 0 0 20px;
        padding: 12px 14px;
        border-radius: 18px;
        border: 1px solid rgba(15,23,42,.08);
        background: #ffffff;
        box-shadow: 0 12px 28px rgba(15,23,42,.06);
        position: relative;
        z-index: 1;
    }
    .bd-control-hub-nav__badge {
        display: inline-flex;
        align-items: center;
        min-height: 34px;
        padding: 0 13px;
        border-radius: 999px;
        background: #0f172a;
        color: #fff;
        font-size: .74rem;
        font-weight: 800;
        letter-spacing: .08em;
        text-transform: uppercase;
    }
    .bd-control-hub-nav__link {
        display: inline-flex;
        align-items: center;
        min-height: 34px;
        padding: 0 12px;
        border-radius: 999px;
        font-size: .74rem;
        font-weight: 800;
        text-decoration: none;
        border: 1px solid rgba(15,23,42,.1);
        color: #334155;
        background: #f8fafc;
    }
    .bd-control-hub-nav__link.is-active,
    .bd-control-hub-nav__link:hover {
        background: #0d2d19;
        border-color: #0d2d19;
        color: #f8fafc;
    }
    .bd-control-hub-nav--topbar {
        flex-wrap: nowrap;
        gap: 8px;
        margin: 0;
        padding: 0;
        border: 0;
        background: transparent;
        box-shadow: none;
        overflow-x: auto;
        scrollbar-width: none;
    }
    .bd-control-hub-nav--topbar::-webkit-scrollbar {
        display: none;
    }
    .bd-control-hub-nav--topbar .bd-control-hub-nav__badge {
        min-height: 32px;
        padding: 0 10px;
        font-size: .68rem;
        letter-spacing: .06em;
        flex: 0 0 auto;
    }
    .bd-control-hub-nav--topbar .bd-control-hub-nav__link,
    .bd-control-hub-nav--topbar .bd-control-hub-nav__current {
        min-height: 32px;
        padding: 0;
        font-size: .74rem;
        flex: 0 0 auto;
        white-space: nowrap;
    }
    .bd-control-hub-nav--topbar .bd-control-hub-nav__separator {
        color: #64748b;
        font-size: .78rem;
        font-weight: 800;
        flex: 0 0 auto;
    }
    .bd-control-hub-nav--topbar .bd-control-hub-nav__current {
        color: #0f172a;
        font-weight: 800;
        letter-spacing: -.01em;
    }
    @media (max-width: 780px) {
        .bd-control-hub-nav {
            padding: 10px 12px;
            border-radius: 16px;
            gap: 8px;
        }
        .bd-control-hub-nav--topbar {
            padding: 0;
        }
    }
</style>
