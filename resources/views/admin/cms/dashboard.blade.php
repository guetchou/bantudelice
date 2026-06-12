@extends('layouts.admin-modern')
@section('title', 'CMS — Projets')
@section('page_title', 'CMS Projets')
@section('nav_active', 'cms')

@section('content')
<div class="cmsd-root">

    <div class="adm-page-bar">
        <div class="adm-page-bar__left">
            <nav class="adm-page-bar__breadcrumb">
                <span>Admin</span><span class="sep">/</span><span>CMS</span>
            </nav>
            <h1 class="adm-page-bar__title">Projets CMS</h1>
        </div>
        <div class="adm-page-bar__right">
            <a href="{{ route('admin.cms.contents.index') }}" class="cmsd-ghost-btn">Contenus</a>
            <a href="{{ route('admin.cms.media.index') }}" class="cmsd-ghost-btn">Mediatheque</a>
        </div>
    </div>

    <div class="cmsd-kpi-row">
        <div class="cmsd-kpi">
            <span>Applications</span>
            <strong>3</strong>
        </div>
        <div class="cmsd-kpi">
            <span>Medias renseignes</span>
            <strong>
                @php $totalFilled = collect($workspaces)->sum('media_filled'); $totalSlots = collect($workspaces)->sum('media_total'); @endphp
                {{ $totalFilled }}/{{ $totalSlots }}
            </strong>
        </div>
        <div class="cmsd-kpi">
            <span>Projets complets</span>
            <strong>{{ collect($workspaces)->where('media_status', 'ok')->count() }}</strong>
        </div>
        <div class="cmsd-kpi">
            <span>Projets incomplets</span>
            <strong>{{ collect($workspaces)->where('media_status', '!=', 'ok')->count() }}</strong>
        </div>
    </div>

    <div class="cmsd-projects">

        @foreach($workspaces as $ws)
        <div class="cmsd-project" style="--accent: {{ $ws['accent'] }};">
            <div class="cmsd-project__stripe"></div>

            <div class="cmsd-project__head">
                <div class="cmsd-project__identity">
                    <span class="cmsd-project__icon">
                        <i class="{{ $ws['icon'] }}"></i>
                    </span>
                    <div>
                        <h2 class="cmsd-project__name">{{ $ws['label'] }}</h2>
                        <p class="cmsd-project__tagline">{{ $ws['tagline'] }}</p>
                    </div>
                </div>

                <div class="cmsd-project__meta">
                    <span class="cmsd-project__domain">{{ $ws['domain'] }}</span>
                    <span class="cmsd-media-badge cmsd-media-badge--{{ $ws['media_status'] }}">
                        @if($ws['media_status'] === 'ok')
                            <i class="fas fa-check" style="font-size:.6rem;"></i>
                        @elseif($ws['media_status'] === 'warn')
                            <i class="fas fa-exclamation-triangle" style="font-size:.6rem;"></i>
                        @else
                            <i class="fas fa-times" style="font-size:.6rem;"></i>
                        @endif
                        {{ $ws['media_filled'] }}/{{ $ws['media_total'] }} medias
                    </span>
                </div>
            </div>

            <div class="cmsd-project__body">
                <div class="cmsd-project__sections">
                    <p class="cmsd-project__sections-label">Sections editables</p>
                    <div class="cmsd-project__chips">
                        @foreach($ws['sections'] as $section)
                            <span class="cmsd-chip">{{ $section }}</span>
                        @endforeach
                    </div>
                </div>

                <div class="cmsd-project__actions">
                    <a href="{{ $ws['edit_url'] }}" class="cmsd-btn-primary">
                        <i class="fas fa-pen" style="font-size:.72rem;"></i>
                        Editer l'accueil
                    </a>
                    <a href="{{ $ws['site_url'] }}" target="_blank" rel="noopener" class="cmsd-btn-ghost">
                        <i class="fas fa-external-link-alt" style="font-size:.72rem;"></i>
                        Voir le site
                    </a>
                </div>
            </div>
        </div>
        @endforeach

    </div>

    <div class="cmsd-advanced">
        <div class="cmsd-advanced__head">
            <h3>CMS avance</h3>
            <p>Types de contenu, mediatheque, entrees CMS structurees.</p>
        </div>
        <div class="cmsd-advanced__links">
            <a href="{{ route('admin.cms.contents.index') }}" class="cmsd-advanced__item">
                <span class="cmsd-advanced__item-icon"><i class="fas fa-file-alt"></i></span>
                <div>
                    <strong>Contenus</strong>
                    <small>Toutes les entrees CMS</small>
                </div>
                <i class="fas fa-chevron-right cmsd-advanced__item-arrow"></i>
            </a>
            <a href="{{ route('admin.cms.content-types.index') }}" class="cmsd-advanced__item">
                <span class="cmsd-advanced__item-icon"><i class="fas fa-layer-group"></i></span>
                <div>
                    <strong>Types de contenu</strong>
                    <small>Schemas et structures</small>
                </div>
                <i class="fas fa-chevron-right cmsd-advanced__item-arrow"></i>
            </a>
            <a href="{{ route('admin.cms.media.index') }}" class="cmsd-advanced__item">
                <span class="cmsd-advanced__item-icon"><i class="fas fa-photo-video"></i></span>
                <div>
                    <strong>Mediatheque</strong>
                    <small>Images et fichiers partages</small>
                </div>
                <i class="fas fa-chevron-right cmsd-advanced__item-arrow"></i>
            </a>
        </div>
    </div>

</div>

<style>
    .cmsd-root { padding: 24px; display: grid; gap: 20px; }

    /* KPI ROW */
    .cmsd-kpi-row { display: grid; grid-template-columns: repeat(4, minmax(0,1fr)); gap: 12px; }
    .cmsd-kpi { padding: 16px 18px; background: #fff; border: 1px solid #e5e7eb; border-radius: 10px; }
    .cmsd-kpi span { display: block; font-size: .7rem; font-weight: 700; color: #9ca3af; text-transform: uppercase; letter-spacing: .08em; }
    .cmsd-kpi strong { display: block; margin-top: 6px; font-size: 1.5rem; font-weight: 900; color: #111827; line-height: 1; }

    /* PROJECT CARDS */
    .cmsd-projects { display: grid; gap: 16px; }

    .cmsd-project { position: relative; background: #fff; border: 1px solid #e5e7eb; border-radius: 14px; overflow: hidden; display: grid; gap: 0; }
    .cmsd-project__stripe { height: 4px; background: var(--accent); }
    .cmsd-project__head { display: flex; align-items: center; justify-content: space-between; gap: 16px; padding: 20px 24px 16px; flex-wrap: wrap; }
    .cmsd-project__identity { display: flex; align-items: center; gap: 14px; }
    .cmsd-project__icon { width: 44px; height: 44px; border-radius: 12px; display: inline-flex; align-items: center; justify-content: center; font-size: 1.05rem; color: #fff; background: var(--accent); flex-shrink: 0; }
    .cmsd-project__name { margin: 0; font-size: 1.1rem; font-weight: 900; color: #111827; line-height: 1.1; }
    .cmsd-project__tagline { margin: 3px 0 0; font-size: .8rem; color: #6b7280; font-weight: 500; }
    .cmsd-project__meta { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
    .cmsd-project__domain { font-size: .76rem; font-weight: 600; color: #9ca3af; font-family: monospace; }

    .cmsd-media-badge { display: inline-flex; align-items: center; gap: 5px; padding: 4px 10px; border-radius: 20px; font-size: .72rem; font-weight: 700; white-space: nowrap; }
    .cmsd-media-badge--ok { background: #d1fae5; color: #065f46; }
    .cmsd-media-badge--warn { background: #fef3c7; color: #92400e; }
    .cmsd-media-badge--missing { background: #fee2e2; color: #991b1b; }

    .cmsd-project__body { display: flex; align-items: center; justify-content: space-between; gap: 20px; padding: 16px 24px 20px; border-top: 1px solid #f3f4f6; flex-wrap: wrap; }
    .cmsd-project__sections-label { margin: 0 0 8px; font-size: .65rem; font-weight: 800; color: #9ca3af; text-transform: uppercase; letter-spacing: .1em; }
    .cmsd-project__chips { display: flex; flex-wrap: wrap; gap: 6px; }
    .cmsd-chip { display: inline-flex; align-items: center; padding: 3px 10px; border-radius: 6px; background: #f3f4f6; border: 1px solid #e5e7eb; font-size: .74rem; font-weight: 600; color: #374151; }

    .cmsd-project__actions { display: flex; align-items: center; gap: 8px; flex-shrink: 0; }
    .cmsd-btn-primary { display: inline-flex; align-items: center; gap: 7px; height: 36px; padding: 0 16px; background: var(--accent); color: #fff; border-radius: 8px; font-size: .82rem; font-weight: 700; text-decoration: none; white-space: nowrap; }
    .cmsd-btn-primary:hover { opacity: .88; }
    .cmsd-btn-ghost { display: inline-flex; align-items: center; gap: 7px; height: 36px; padding: 0 14px; background: #fff; border: 1px solid #d1d5db; color: #374151; border-radius: 8px; font-size: .82rem; font-weight: 600; text-decoration: none; white-space: nowrap; }
    .cmsd-btn-ghost:hover { border-color: #9ca3af; color: #111827; }

    /* ADVANCED */
    .cmsd-advanced { background: #fff; border: 1px solid #e5e7eb; border-radius: 14px; overflow: hidden; }
    .cmsd-advanced__head { padding: 18px 24px 14px; border-bottom: 1px solid #f3f4f6; }
    .cmsd-advanced__head h3 { margin: 0; font-size: .95rem; font-weight: 800; color: #111827; }
    .cmsd-advanced__head p { margin: 4px 0 0; font-size: .8rem; color: #6b7280; }
    .cmsd-advanced__links { display: grid; }
    .cmsd-advanced__item { display: flex; align-items: center; gap: 14px; padding: 14px 24px; border-bottom: 1px solid #f9fafb; text-decoration: none; color: #111827; transition: background .1s; }
    .cmsd-advanced__item:last-child { border-bottom: none; }
    .cmsd-advanced__item:hover { background: #f9fafb; }
    .cmsd-advanced__item-icon { width: 36px; height: 36px; border-radius: 9px; display: inline-flex; align-items: center; justify-content: center; background: #f3f4f6; color: #374151; font-size: .85rem; flex-shrink: 0; }
    .cmsd-advanced__item strong { display: block; font-size: .85rem; font-weight: 700; }
    .cmsd-advanced__item small { display: block; font-size: .74rem; color: #6b7280; margin-top: 2px; }
    .cmsd-advanced__item-arrow { margin-left: auto; color: #d1d5db; font-size: .75rem; }

    /* GHOST BTN (top bar) */
    .cmsd-ghost-btn { display: inline-flex; align-items: center; height: 34px; padding: 0 14px; border: 1px solid #d1d5db; border-radius: 8px; color: #374151; text-decoration: none; font-size: .8rem; font-weight: 600; background: #fff; }
    .cmsd-ghost-btn:hover { border-color: #009543; color: #009543; }

    /* RESPONSIVE */
    @media (max-width: 900px) {
        .cmsd-kpi-row { grid-template-columns: repeat(2, minmax(0,1fr)); }
        .cmsd-project__body { flex-direction: column; align-items: flex-start; }
    }
    @media (max-width: 600px) {
        .cmsd-root { padding: 16px; }
        .cmsd-kpi-row { grid-template-columns: 1fr 1fr; }
        .cmsd-project__head { flex-direction: column; align-items: flex-start; }
    }
</style>
@endsection
