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
<div class="content-header">
    <div class="container-fluid">
        @include('admin.partials.control_hub_nav')
        <div class="bd-cms-shell">
            <section class="bd-cms-hero">
                <div>
                    <p class="bd-cms-hero__eyebrow">{{ $cmsWorkspace['eyebrow'] }}</p>
                    <h1>Types de contenus {{ $cmsWorkspace['label'] }}</h1>
                    <p>{{ $cmsWorkspace['description'] }} Structurez des schemas reutilisables, des champs adaptes et une gouvernance claire.</p>
                </div>
                <a href="{{ route('admin.cms.content-types.create', ['workspace' => $cmsWorkspace['key']]) }}" class="btn btn-primary bd-cms-hero__action">Nouveau type</a>
            </section>

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
    </div>
</div>

<section class="content">
    <div class="container-fluid">
        <div class="card bd-cms-table-card">
            <div class="card-header border-0">
                <div class="bd-cms-table-card__header">
                    <div>
                        <h3>Bibliotheque des types</h3>
                        <p>Chaque type regroupe son slug, son volume de champs et le nombre de contenus deja produits.</p>
                    </div>
                    <span class="bd-cms-pill">{{ $types->count() }} type(s)</span>
                </div>
            </div>
            <div class="card-body table-responsive p-0">
                <table class="table table-hover">
                    <thead>
                    <tr>
                        <th>Type</th>
                        <th>Slug</th>
                        <th>Champs</th>
                        <th>Contenus</th>
                        <th>Etat</th>
                        <th></th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($types as $type)
                        <tr>
                            <td>
                                <div class="bd-cms-table-primary">
                                    <strong>{{ $type->name }}</strong>
                                    <span>Schema reutilisable</span>
                                </div>
                            </td>
                            <td><code>{{ $type->slug }}</code></td>
                            <td>{{ $type->fields_count }}</td>
                            <td>{{ $type->contents_count }}</td>
                            <td>
                                <span class="bd-cms-status {{ $type->is_active ? 'is-published' : 'is-archived' }}">
                                    {{ $type->is_active ? 'Actif' : 'Inactif' }}
                                </span>
                            </td>
                            <td class="text-right">
                                <a href="{{ route('admin.cms.content-types.edit', ['contentType' => $type, 'workspace' => $cmsWorkspace['key']]) }}" class="btn btn-sm btn-outline-primary">Configurer</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-5">Aucun type de contenu.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>

<style>
    .bd-cms-shell {
        display: grid;
        gap: 20px;
    }

    .bd-cms-hero {
        display: flex;
        align-items: flex-end;
        justify-content: space-between;
        gap: 20px;
        padding: 28px 32px;
        border-radius: 32px;
        background: linear-gradient(135deg, #020617 0%, #0f172a 55%, #155e75 100%);
        color: #fff;
        box-shadow: 0 20px 60px rgba(15,23,42,0.22);
    }

    .bd-cms-hero__eyebrow {
        margin: 0 0 8px;
        font-size: 0.78rem;
        letter-spacing: 0.18em;
        text-transform: uppercase;
        font-weight: 800;
        color: #bae6fd;
    }

    .bd-cms-hero h1 {
        margin: 0;
        font-size: clamp(2rem, 4vw, 3.1rem);
        font-weight: 900;
        line-height: 1.04;
        color: #fff;
    }

    .bd-cms-hero p {
        margin: 14px 0 0;
        max-width: 760px;
        color: rgba(255,255,255,0.82);
        line-height: 1.8;
    }

    .bd-cms-hero__action {
        min-width: 180px;
    }

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

    .bd-cms-stat-card span {
        display: block;
        color: #64748b;
        font-size: 0.88rem;
        font-weight: 700;
    }

    .bd-cms-stat-card strong {
        display: block;
        margin-top: 10px;
        color: #020617;
        font-size: 2rem;
        line-height: 1;
        font-weight: 900;
    }

    .bd-cms-stat-card small {
        display: block;
        margin-top: 8px;
        color: #94a3b8;
    }

    .bd-cms-table-card__header {
        display: flex;
        align-items: flex-end;
        justify-content: space-between;
        gap: 16px;
    }

    .bd-cms-table-card__header h3 {
        margin: 0;
        color: #020617;
        font-size: 1.25rem;
        font-weight: 900;
    }

    .bd-cms-table-card__header p {
        margin: 8px 0 0;
        color: #64748b;
        line-height: 1.7;
    }

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

    .bd-cms-table-primary {
        display: flex;
        flex-direction: column;
        gap: 4px;
    }

    .bd-cms-table-primary strong {
        color: #020617;
    }

    .bd-cms-table-primary span {
        color: #94a3b8;
        font-size: 0.84rem;
    }

    .bd-cms-status {
        display: inline-flex;
        align-items: center;
        min-height: 34px;
        padding: 0 12px;
        border-radius: 999px;
        font-size: 0.8rem;
        font-weight: 800;
    }

    .bd-cms-status.is-published {
        background: #ecfdf5;
        color: #047857;
    }

    .bd-cms-status.is-archived {
        background: #f8fafc;
        color: #64748b;
    }

    @media (max-width: 991.98px) {
        .bd-cms-hero,
        .bd-cms-table-card__header {
            flex-direction: column;
            align-items: flex-start;
        }

        .bd-cms-stats {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (max-width: 575.98px) {
        .bd-cms-stats {
            grid-template-columns: 1fr;
        }
    }
</style>
@endsection
