@extends('layouts.admin-modern')
@section('title', 'CMS - Types de contenus')
@section('page_title', 'Types de contenu')
@section('nav_active', 'cms')

@php
    $cmsWorkspace = $cmsWorkspace ?? [
        'key' => request('workspace', 'bantudelice'),
        'label' => request('workspace') === 'kende' ? 'Kende' : (request('workspace') === 'mema' ? 'Mema' : 'BantuDelice'),
        'eyebrow' => request('workspace') === 'kende' ? 'CMS Mobilite' : (request('workspace') === 'mema' ? 'CMS Colis' : 'CMS Food ops'),
        'description' => request('workspace') === 'kende'
            ? 'Schemas editoriaux pour transport et mobilite.'
            : (request('workspace') === 'mema'
                ? 'Schemas editoriaux pour logistique et expedition.'
                : 'Schemas editoriaux pour food et storefront.'),
    ];
    $activeTypes = $types->where('is_active', true)->count();
    $inactiveTypes = $types->count() - $activeTypes;
    $totalFields = $types->sum('fields_count');
    $totalContents = $types->sum('contents_count');
@endphp

@section('content')
<div style="padding:24px;">
    @include('admin.partials.control_hub_nav')
    <div class="bd-cms-shell">
        <div class="adm-page-bar">
            <div class="adm-page-bar__left">
                <nav class="adm-page-bar__breadcrumb">
                    <span>CMS</span><span class="sep">/</span><span>{{ $cmsWorkspace['label'] }}</span>
                </nav>
                <h1 class="adm-page-bar__title">Types de contenus {{ $cmsWorkspace['label'] }}</h1>
            </div>
            <div class="adm-page-bar__right">
                <a href="{{ route('admin.cms.content-types.create', ['workspace' => $cmsWorkspace['key']]) }}" class="ops-primary-btn">Nouveau type</a>
            </div>
        </div>

        <section class="bd-cms-stats">
            <article class="bd-cms-stat-card">
                <span>Types declares</span>
                <strong>{{ $types->count() }}</strong>
                <small>Base CMS disponible</small>
            </article>
            <article class="bd-cms-stat-card">
                <span>Types actifs</span>
                <strong>{{ $activeTypes }}</strong>
                <small>{{ $inactiveTypes }} inactifs</small>
            </article>
            <article class="bd-cms-stat-card">
                <span>Champs structures</span>
                <strong>{{ $totalFields }}</strong>
                <small>Tous schemas confondus</small>
            </article>
            <article class="bd-cms-stat-card">
                <span>Contenus relies</span>
                <strong>{{ $totalContents }}</strong>
                <small>Instances deja creees</small>
            </article>
        </section>
    </div>

    <div class="bd-cms-table-card" style="background:#fff;border:1px solid #e5e7eb;border-radius:10px;overflow:hidden;margin-top:20px;">
        <div style="padding:16px 20px;border-bottom:1px solid #f3f4f6;">
            <div class="bd-cms-table-card__header">
                <div>
                    <h3>Bibliotheque des types</h3>
                    <p>Chaque type regroupe son slug, son volume de champs et le nombre de contenus deja produits.</p>
                </div>
                <span class="bd-cms-pill">{{ $types->count() }} type(s)</span>
            </div>
        </div>
        <div style="overflow-x:auto;">
            <table style="width:100%;border-collapse:collapse;font-size:13px;">
                <thead>
                <tr>
                    <th style="padding:9px 14px;font-size:10px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:#9ca3af;border-bottom:1px solid #f3f4f6;background:#f9fafb;text-align:left;">Type</th>
                    <th style="padding:9px 14px;font-size:10px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:#9ca3af;border-bottom:1px solid #f3f4f6;background:#f9fafb;text-align:left;">Slug</th>
                    <th style="padding:9px 14px;font-size:10px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:#9ca3af;border-bottom:1px solid #f3f4f6;background:#f9fafb;text-align:left;">Champs</th>
                    <th style="padding:9px 14px;font-size:10px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:#9ca3af;border-bottom:1px solid #f3f4f6;background:#f9fafb;text-align:left;">Contenus</th>
                    <th style="padding:9px 14px;font-size:10px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:#9ca3af;border-bottom:1px solid #f3f4f6;background:#f9fafb;text-align:left;">Etat</th>
                    <th style="padding:9px 14px;font-size:10px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:#9ca3af;border-bottom:1px solid #f3f4f6;background:#f9fafb;text-align:right;"></th>
                </tr>
                </thead>
                <tbody>
                @forelse($types as $type)
                    <tr>
                        <td style="padding:10px 14px;color:#374151;vertical-align:middle;border-bottom:1px solid #f3f4f6;">
                            <div class="bd-cms-table-primary">
                                <strong>{{ $type->name }}</strong>
                                <span>Schema reutilisable</span>
                            </div>
                        </td>
                        <td style="padding:10px 14px;color:#374151;vertical-align:middle;border-bottom:1px solid #f3f4f6;"><code>{{ $type->slug }}</code></td>
                        <td style="padding:10px 14px;color:#374151;vertical-align:middle;border-bottom:1px solid #f3f4f6;">{{ $type->fields_count }}</td>
                        <td style="padding:10px 14px;color:#374151;vertical-align:middle;border-bottom:1px solid #f3f4f6;">{{ $type->contents_count }}</td>
                        <td style="padding:10px 14px;color:#374151;vertical-align:middle;border-bottom:1px solid #f3f4f6;">
                            <span class="bd-cms-status {{ $type->is_active ? 'is-published' : 'is-archived' }}">
                                {{ $type->is_active ? 'Actif' : 'Inactif' }}
                            </span>
                        </td>
                        <td style="padding:10px 14px;color:#374151;vertical-align:middle;border-bottom:1px solid #f3f4f6;text-align:right;">
                            <a href="{{ route('admin.cms.content-types.edit', ['contentType' => $type, 'workspace' => $cmsWorkspace['key']]) }}" style="display:inline-flex;align-items:center;padding:5px 12px;border:1px solid #1e3a5f;color:#1e3a5f;border-radius:5px;font-size:12px;font-weight:600;text-decoration:none;">Configurer</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" style="text-align:center;color:#9ca3af;padding:40px;">Aucun type de contenu.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
    .bd-cms-shell {
        display: grid;
        gap: 20px;
    }

    .bd-cms-hero { display: none; }

    .bd-cms-stats {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 18px;
    }

    .bd-cms-stat-card {
        padding: 22px;
        border-radius: 26px;
        border: 1px solid rgba(148,163,184,0.18);
        background: rgba(255,255,255,0.96);
        box-shadow: 0 12px 35px rgba(15,23,42,0.05);
    }

    .bd-cms-stat-card span { display: block; color: #64748b; font-size: 0.88rem; font-weight: 700; }
    .bd-cms-stat-card strong { display: block; margin-top: 10px; color: #020617; font-size: 2rem; line-height: 1; font-weight: 900; }
    .bd-cms-stat-card small { display: block; margin-top: 8px; color: #94a3b8; }

    .bd-cms-table-card__header {
        display: flex;
        align-items: flex-end;
        justify-content: space-between;
        gap: 16px;
    }

    .bd-cms-table-card__header h3 { margin: 0; color: #020617; font-size: 1.25rem; font-weight: 900; }
    .bd-cms-table-card__header p { margin: 8px 0 0; color: #64748b; line-height: 1.7; }

    .bd-cms-pill {
        display: inline-flex;
        align-items: center;
        min-height: 38px;
        padding: 0 14px;
        border-radius: 999px;
        background: #eff6ff;
        color: #1d4ed8;
        font-weight: 800;
    }

    .bd-cms-table-primary { display: flex; flex-direction: column; gap: 4px; }
    .bd-cms-table-primary strong { color: #020617; }
    .bd-cms-table-primary span { color: #94a3b8; font-size: 0.84rem; }

    .bd-cms-status {
        display: inline-flex;
        align-items: center;
        min-height: 34px;
        padding: 0 12px;
        border-radius: 999px;
        font-size: 0.8rem;
        font-weight: 800;
    }

    .bd-cms-status.is-published { background: #ecfdf5; color: #047857; }
    .bd-cms-status.is-archived  { background: #f8fafc; color: #64748b; }

    @media (max-width: 991.98px) {
        .bd-cms-hero, .bd-cms-table-card__header { flex-direction: column; align-items: flex-start; }
        .bd-cms-stats { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    }
    @media (max-width: 575.98px) { .bd-cms-stats { grid-template-columns: 1fr; } }
</style>
@endsection
