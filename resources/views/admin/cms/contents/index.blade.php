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
<div style="padding:24px;">
    @include('admin.partials.control_hub_nav')
    <div class="bd-cms-shell">
        <div class="adm-page-bar">
            <div class="adm-page-bar__left">
                <nav class="adm-page-bar__breadcrumb">
                    <span>CMS</span><span class="sep">/</span><span>{{ $cmsWorkspace['label'] }}</span>
                </nav>
                <h1 class="adm-page-bar__title">Contenus {{ $cmsWorkspace['label'] }}</h1>
            </div>
            <div class="adm-page-bar__right">
                <a href="{{ route('admin.cms.contents.create', ['workspace' => $cmsWorkspace['key']]) }}" class="ops-primary-btn">Nouveau contenu</a>
            </div>
        </div>

        <section class="bd-cms-stats">
            <article class="bd-cms-stat-card"><span>Contenus totaux</span><strong>{{ $contents->total() }}</strong><small>Toutes pages confondues</small></article>
            <article class="bd-cms-stat-card"><span>Publies</span><strong>{{ $publishedCount }}</strong><small>Sur la page courante</small></article>
            <article class="bd-cms-stat-card"><span>Brouillons</span><strong>{{ $draftCount }}</strong><small>A completer ou relire</small></article>
            <article class="bd-cms-stat-card"><span>A revoir</span><strong>{{ $reviewCount }}</strong><small>En attente de validation</small></article>
            <article class="bd-cms-stat-card"><span>Planifies</span><strong>{{ $scheduledCount }}</strong><small>Publication differee</small></article>
        </section>
    </div>

    <div class="bd-cms-table-card" style="background:#fff;border:1px solid #e5e7eb;border-radius:10px;overflow:hidden;margin-top:20px;">
        <div style="padding:16px 20px;border-bottom:1px solid #f3f4f6;">
            <div class="bd-cms-table-card__header">
                <div>
                    <h3>Bibliotheque editoriale</h3>
                    <p>Filtrez par type, statut, auteur ou date pour couper la dette des anciens ecrans editoriaux.</p>
                </div>
                <form method="get" class="bd-cms-filter">
                    <input type="text" name="q" class="bd-cms-filter-input" placeholder="Titre, slug, resume" value="{{ request('q') }}">
                    <label for="cms-type-filter">Type</label>
                    <select id="cms-type-filter" name="type" class="bd-cms-filter-input">
                        <option value="">Tous</option>
                        @foreach($types as $type)
                            <option value="{{ $type->id }}" {{ request('type') == $type->id ? 'selected' : '' }}>{{ $type->name }}</option>
                        @endforeach
                    </select>
                    <select name="status" class="bd-cms-filter-input">
                        <option value="">Tous les statuts</option>
                        @foreach(['draft' => 'Brouillon', 'pending_review' => 'En revue', 'published' => 'Publie', 'scheduled' => 'Planifie', 'archived' => 'Archive'] as $value => $label)
                            <option value="{{ $value }}" {{ request('status') === $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                    <select name="author" class="bd-cms-filter-input">
                        <option value="">Tous les auteurs</option>
                        @foreach($authors as $author)
                            <option value="{{ $author->id }}" {{ (int) request('author') === (int) $author->id ? 'selected' : '' }}>{{ $author->name }}</option>
                        @endforeach
                    </select>
                    <input type="date" name="published_from" class="bd-cms-filter-input" value="{{ request('published_from') }}">
                    <input type="date" name="published_to" class="bd-cms-filter-input" value="{{ request('published_to') }}">
                    <button type="submit" style="padding:7px 14px;background:#1e3a5f;color:#fff;border:none;border-radius:6px;font-size:13px;font-weight:600;cursor:pointer;">Filtrer</button>
                    <a href="{{ route('admin.cms.contents.index', ['workspace' => $cmsWorkspace['key']]) }}" style="padding:7px 14px;border:1px solid #d1d5db;border-radius:6px;font-size:13px;font-weight:600;color:#374151;background:#fff;text-decoration:none;">Reset</a>
                </form>
            </div>
        </div>
        <div style="overflow-x:auto;">
            <table style="width:100%;border-collapse:collapse;font-size:13px;">
                <thead>
                <tr>
                    @foreach(['Contenu','Type','Statut','Auteur','Slug','Publication',''] as $h)
                    <th style="padding:9px 14px;font-size:10px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:#9ca3af;border-bottom:1px solid #f3f4f6;background:#f9fafb;text-align:{{ $loop->last ? 'right' : 'left' }};">{{ $h }}</th>
                    @endforeach
                </tr>
                </thead>
                <tbody>
                @forelse($contents as $content)
                    <tr>
                        <td style="padding:10px 14px;color:#374151;vertical-align:middle;border-bottom:1px solid #f3f4f6;">
                            <div class="bd-cms-table-primary">
                                <strong>{{ $content->title }}</strong>
                                <span>{{ $content->excerpt ?: 'Contenu editorial gere via le CMS.' }}</span>
                            </div>
                        </td>
                        <td style="padding:10px 14px;color:#374151;vertical-align:middle;border-bottom:1px solid #f3f4f6;">{{ $content->contentType->name ?? '-' }}</td>
                        <td style="padding:10px 14px;color:#374151;vertical-align:middle;border-bottom:1px solid #f3f4f6;">
                            <span class="bd-cms-status
                                @if($content->status === 'published') is-published
                                @elseif($content->status === 'pending_review') is-review
                                @elseif($content->status === 'archived') is-archived
                                @else is-draft
                                @endif">
                                {{ $content->status }}
                            </span>
                        </td>
                        <td style="padding:10px 14px;color:#374151;vertical-align:middle;border-bottom:1px solid #f3f4f6;">{{ $content->author->name ?? '-' }}</td>
                        <td style="padding:10px 14px;color:#374151;vertical-align:middle;border-bottom:1px solid #f3f4f6;"><code>{{ $content->slug }}</code></td>
                        <td style="padding:10px 14px;color:#374151;vertical-align:middle;border-bottom:1px solid #f3f4f6;">
                            {{ optional($content->published_at)->format('d/m/Y H:i') ?? '-' }}
                            @if($content->published_at && $content->published_at->isFuture())
                                <span class="bd-cms-status is-scheduled" style="display:block;margin-top:4px;">planifie</span>
                            @endif
                        </td>
                        <td style="padding:10px 14px;color:#374151;vertical-align:middle;border-bottom:1px solid #f3f4f6;text-align:right;">
                            <div class="bd-cms-table-actions">
                                <a href="{{ route('admin.cms.contents.edit', ['content' => $content, 'workspace' => $cmsWorkspace['key']]) }}" style="display:inline-flex;align-items:center;padding:4px 10px;border:1px solid #1e3a5f;color:#1e3a5f;border-radius:5px;font-size:12px;font-weight:600;text-decoration:none;">Modifier</a>
                                <form method="post" action="{{ route('admin.cms.contents.destroy', ['content' => $content, 'workspace' => $cmsWorkspace['key']]) }}" onsubmit="return confirm('Supprimer ce contenu CMS et son historique ?');" style="display:inline-block;">
                                    @csrf
                                    @method('delete')
                                    <button type="submit" style="display:inline-flex;align-items:center;padding:4px 10px;border:1px solid #dc2626;color:#dc2626;border-radius:5px;font-size:12px;font-weight:600;background:none;cursor:pointer;">Supprimer</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" style="text-align:center;color:#9ca3af;padding:40px;">Aucun contenu.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div style="padding:14px 20px;border-top:1px solid #f3f4f6;">
            {{ $contents->links() }}
        </div>
    </div>
</div>

<style>
    .bd-cms-shell { display: grid; gap: 14px; }
    .bd-cms-hero { display:none; }
    .bd-cms-stats { display:grid; grid-template-columns:repeat(5,minmax(0,1fr)); gap:12px; }
    .bd-cms-stat-card { padding:14px; border-radius:16px; border:1px solid rgba(148,163,184,.18); background:rgba(255,255,255,.96); box-shadow:0 12px 35px rgba(15,23,42,.05); }
    .bd-cms-stat-card span { display:block; color:#64748b; font-size:.88rem; font-weight:700; }
    .bd-cms-stat-card strong { display:block; margin-top:10px; color:#020617; font-size:1.45rem; line-height:1; font-weight:900; }
    .bd-cms-stat-card small { display:block; margin-top:8px; color:#94a3b8; }
    .bd-cms-table-card__header { display:flex; align-items:flex-end; justify-content:space-between; gap:14px; }
    .bd-cms-table-card__header h3 { margin:0; color:#020617; font-size:1.25rem; font-weight:900; }
    .bd-cms-table-card__header p { margin:6px 0 0; color:#64748b; line-height:1.5; }
    .bd-cms-filter { display:flex; align-items:center; gap:10px; flex-wrap:wrap; }
    .bd-cms-filter label { margin:0; color:#64748b; font-weight:700; font-size:13px; }
    .bd-cms-filter-input { min-width:130px; padding:7px 10px; border:1px solid #d1d5db; border-radius:6px; font-size:13px; }
    .bd-cms-table-primary { display:flex; flex-direction:column; gap:4px; }
    .bd-cms-table-primary strong { color:#020617; }
    .bd-cms-table-primary span { color:#94a3b8; font-size:.8rem; line-height:1.45; }
    .bd-cms-table-actions { display:inline-flex; align-items:center; justify-content:flex-end; gap:8px; }
    .bd-cms-table-actions form { margin:0; }
    .bd-cms-status { display:inline-flex; align-items:center; min-height:34px; padding:0 12px; border-radius:999px; font-size:.8rem; font-weight:800; text-transform:capitalize; }
    .bd-cms-status.is-scheduled { background:#fef3c7; color:#b45309; }
    .bd-cms-status.is-published { background:#ecfdf5; color:#047857; }
    .bd-cms-status.is-review    { background:#eff6ff; color:#1d4ed8; }
    .bd-cms-status.is-draft     { background:#fff7ed; color:#c2410c; }
    .bd-cms-status.is-archived  { background:#f8fafc; color:#64748b; }
    @media (max-width:991.98px) {
        .bd-cms-hero, .bd-cms-table-card__header { flex-direction:column; align-items:flex-start; }
        .bd-cms-stats { grid-template-columns:repeat(2,minmax(0,1fr)); }
    }
    @media (max-width:575.98px) {
        .bd-cms-stats { grid-template-columns:1fr; }
        .bd-cms-filter { width:100%; }
        .bd-cms-filter-input { width:100%; }
    }
</style>
@endsection
