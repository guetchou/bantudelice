@extends('layouts.admin-modern')
@section('title', 'CMS - Contenus')
@section('page_title', 'Contenus CMS')
@section('nav_active', 'cms')

@php
    $cmsWorkspace = $cmsWorkspace ?? [
        'key' => request('workspace', 'bantudelice'),
        'label' => request('workspace') === 'kende' ? 'Kende' : (request('workspace') === 'mema' ? 'Mema' : 'BantuDelice'),
        'eyebrow' => request('workspace') === 'kende' ? 'CMS Mobilite' : (request('workspace') === 'mema' ? 'CMS Colis' : 'CMS Food ops'),
        'description' => request('workspace') === 'kende'
            ? 'Contenus transport, trajets, flotte et mobilite.'
            : (request('workspace') === 'mema'
                ? 'Contenus logistiques, relais, suivi et parcours colis.'
                : 'Contenus food, restaurants, commandes et storefront.'),
    ];
    $visibleContents = collect($contents->items());
    $publishedCount = $visibleContents->where('status', 'published')->count();
    $draftCount = $visibleContents->where('status', 'draft')->count();
    $reviewCount = $visibleContents->where('status', 'pending_review')->count();
    $scheduledCount = $visibleContents->filter(function ($content) {
        return $content->status === 'published' && $content->published_at && $content->published_at->isFuture();
    })->count();
@endphp

@section('content')
<div class="content-header">
    <div class="container-fluid">
        @include('admin.partials.control_hub_nav')
        <div class="bd-cms-shell">
            <section class="bd-cms-hero">
                <div>
                    <p class="bd-cms-hero__eyebrow">{{ $cmsWorkspace['eyebrow'] }}</p>
                    <h1>Contenus {{ $cmsWorkspace['label'] }}</h1>
                    <p>{{ $cmsWorkspace['description'] }} Pilotez pages, actualites, sections d'accueil et contenus editoriaux depuis un espace unique.</p>
                </div>
                <a href="{{ route('admin.cms.contents.create', ['workspace' => $cmsWorkspace['key']]) }}" class="btn btn-primary bd-cms-hero__action">Nouveau contenu</a>
            </section>

            <section class="bd-cms-stats">
                <article class="bd-cms-stat-card">
                    <span>Contenus totaux</span>
                    <strong>{{ $contents->total() }}</strong>
                    <small>Toutes pages confondues</small>
                </article>
                <article class="bd-cms-stat-card">
                    <span>Publies</span>
                    <strong>{{ $publishedCount }}</strong>
                    <small>Sur la page courante</small>
                </article>
                <article class="bd-cms-stat-card">
                    <span>Brouillons</span>
                    <strong>{{ $draftCount }}</strong>
                    <small>A completer ou relire</small>
                </article>
                <article class="bd-cms-stat-card">
                    <span>A revoir</span>
                    <strong>{{ $reviewCount }}</strong>
                    <small>En attente de validation</small>
                </article>
                <article class="bd-cms-stat-card">
                    <span>Planifies</span>
                    <strong>{{ $scheduledCount }}</strong>
                    <small>Publication differee</small>
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
                        <h3>Bibliotheque editoriale</h3>
                        <p>Filtrez par type, statut, auteur ou date pour couper la dette des anciens ecrans editoriaux.</p>
                    </div>
                    <form method="get" class="bd-cms-filter">
                        <input type="text" name="q" class="form-control" placeholder="Titre, slug, resume" value="{{ request('q') }}">
                        <label for="cms-type-filter">Type</label>
                        <select id="cms-type-filter" name="type" class="form-control">
                            <option value="">Tous</option>
                            @foreach($types as $type)
                                <option value="{{ $type->id }}" {{ request('type') == $type->id ? 'selected' : '' }}>{{ $type->name }}</option>
                            @endforeach
                        </select>
                        <select name="status" class="form-control">
                            <option value="">Tous les statuts</option>
                            @foreach(['draft' => 'Brouillon', 'pending_review' => 'En revue', 'published' => 'Publie', 'scheduled' => 'Planifie', 'archived' => 'Archive'] as $value => $label)
                                <option value="{{ $value }}" {{ request('status') === $value ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                        <select name="author" class="form-control">
                            <option value="">Tous les auteurs</option>
                            @foreach($authors as $author)
                                <option value="{{ $author->id }}" {{ (int) request('author') === (int) $author->id ? 'selected' : '' }}>{{ $author->name }}</option>
                            @endforeach
                        </select>
                        <input type="date" name="published_from" class="form-control" value="{{ request('published_from') }}">
                        <input type="date" name="published_to" class="form-control" value="{{ request('published_to') }}">
                        <button type="submit" class="btn btn-secondary">Filtrer</button>
                        <a href="{{ route('admin.cms.contents.index', ['workspace' => $cmsWorkspace['key']]) }}" class="btn btn-light">Reset</a>
                    </form>
                </div>
            </div>
            <div class="card-body table-responsive p-0">
                <table class="table table-hover">
                    <thead>
                    <tr>
                        <th>Contenu</th>
                        <th>Type</th>
                        <th>Statut</th>
                        <th>Auteur</th>
                        <th>Slug</th>
                        <th>Publication</th>
                        <th></th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($contents as $content)
                        <tr>
                            <td>
                                <div class="bd-cms-table-primary">
                                    <strong>{{ $content->title }}</strong>
                                    <span>{{ $content->excerpt ?: 'Contenu editorial gere via le CMS.' }}</span>
                                </div>
                            </td>
                            <td>{{ $content->contentType->name ?? '-' }}</td>
                            <td>
                                <span class="bd-cms-status
                                    @if($content->status === 'published') is-published
                                    @elseif($content->status === 'pending_review') is-review
                                    @elseif($content->status === 'archived') is-archived
                                    @else is-draft
                                    @endif">
                                    {{ $content->status }}
                                </span>
                            </td>
                            <td>{{ $content->author->name ?? '-' }}</td>
                            <td><code>{{ $content->slug }}</code></td>
                            <td>
                                {{ optional($content->published_at)->format('d/m/Y H:i') ?? '-' }}
                                @if($content->published_at && $content->published_at->isFuture())
                                    <span class="bd-cms-status is-scheduled mt-1">planifie</span>
                                @endif
                            </td>
                            <td class="text-right">
                                <div class="bd-cms-table-actions">
                                    <a href="{{ route('admin.cms.contents.edit', ['content' => $content, 'workspace' => $cmsWorkspace['key']]) }}" class="btn btn-sm btn-outline-primary">Modifier</a>
                                    <form method="post" action="{{ route('admin.cms.contents.destroy', ['content' => $content, 'workspace' => $cmsWorkspace['key']]) }}" onsubmit="return confirm('Supprimer ce contenu CMS et son historique ?');">
                                        @csrf
                                        @method('delete')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">Supprimer</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-5">Aucun contenu.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            <div class="card-footer clearfix">
                {{ $contents->links() }}
            </div>
        </div>
    </div>
</section>

<style>
    .bd-cms-shell {
        display: grid;
        gap: 14px;
    }

    .bd-cms-hero {
        display: flex;
        align-items: flex-end;
        justify-content: space-between;
        gap: 16px;
        padding: 18px 20px;
        border-radius: 18px;
        background: linear-gradient(135deg, #020617 0%, #0f172a 55%, #155e75 100%);
        color: #fff;
        box-shadow: 0 12px 30px rgba(15,23,42,0.16);
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
        font-size: clamp(1.45rem, 3vw, 2rem);
        font-weight: 900;
        line-height: 1.04;
        color: #fff;
    }

    .bd-cms-hero p {
        margin: 8px 0 0;
        max-width: 980px;
        color: rgba(255,255,255,0.82);
        line-height: 1.55;
    }

    .bd-cms-hero__action {
        min-width: 160px;
    }

    .bd-cms-stats {
        display: grid;
        grid-template-columns: repeat(5, minmax(0, 1fr));
        gap: 12px;
    }

    .bd-cms-stat-card {
        padding: 14px;
        border-radius: 16px;
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
        font-size: 1.45rem;
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
        gap: 14px;
    }

    .bd-cms-table-card__header h3 {
        margin: 0;
        color: #020617;
        font-size: 1.25rem;
        font-weight: 900;
    }

    .bd-cms-table-card__header p {
        margin: 6px 0 0;
        color: #64748b;
        line-height: 1.5;
    }

    .bd-cms-filter {
        display: flex;
        align-items: center;
        gap: 10px;
        flex-wrap: wrap;
    }

    .bd-cms-filter label {
        margin: 0;
        color: #64748b;
        font-weight: 700;
    }

    .bd-cms-filter .form-control {
        min-width: 150px;
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
        font-size: 0.8rem;
        line-height: 1.45;
    }

    .bd-cms-table-actions {
        display: inline-flex;
        align-items: center;
        justify-content: flex-end;
        gap: 8px;
    }

    .bd-cms-table-actions form {
        margin: 0;
    }

    .bd-cms-status {
        display: inline-flex;
        align-items: center;
        min-height: 34px;
        padding: 0 12px;
        border-radius: 999px;
        font-size: 0.8rem;
        font-weight: 800;
        text-transform: capitalize;
    }

    .bd-cms-status.is-scheduled {
        background: #fef3c7;
        color: #b45309;
    }

    .bd-cms-status.is-published {
        background: #ecfdf5;
        color: #047857;
    }

    .bd-cms-status.is-review {
        background: #eff6ff;
        color: #1d4ed8;
    }

    .bd-cms-status.is-draft {
        background: #fff7ed;
        color: #c2410c;
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

        .bd-cms-filter {
            width: 100%;
        }

        .bd-cms-filter .form-control,
        .bd-cms-filter .btn {
            width: 100%;
        }
    }
</style>
@endsection
