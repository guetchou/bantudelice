@extends('layouts.admin-modern')
@section('title', 'CMS - Contenu')
@section('page_title', 'Contenu CMS')
@section('nav_active', 'cms')

@php
    $selectedType = $selectedType ?? null;
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
@endphp

@section('content')
<div class="content-header">
    <div class="container-fluid">
        @include('admin.partials.control_hub_nav')
        <div class="bd-admin-editor-shell">
            <section class="bd-admin-editor-hero">
                <div>
                    <p class="bd-admin-editor-hero__eyebrow">{{ $cmsWorkspace['eyebrow'] }}</p>
                    <h1>{{ $content->exists ? 'Modifier le contenu' : 'Nouveau contenu' }}</h1>
                    <p>{{ $cmsWorkspace['description'] }} Structurez le titre, les champs, le SEO et le statut editorial depuis un seul ecran de production.</p>
                </div>
                <a href="{{ route('admin.cms.contents.index', ['workspace' => $cmsWorkspace['key']]) }}" class="btn btn-light">Retour aux contenus</a>
            </section>
        </div>
    </div>
</div>

<section class="content">
    <div class="container-fluid">
        <form method="post" enctype="multipart/form-data" action="{{ $content->exists ? route('admin.cms.contents.update', ['content' => $content, 'workspace' => $cmsWorkspace['key']]) : route('admin.cms.contents.store', ['workspace' => $cmsWorkspace['key']]) }}">
            @csrf
            @if($content->exists)
                @method('put')
            @endif

            <div class="row">
                <div class="col-lg-8">
                    <div class="card bd-admin-editor-card">
                        <div class="card-header border-0">
                            <div class="bd-admin-editor-card__header">
                                <div>
                                    <h3>Contenu principal</h3>
                                    <p>Le titre, le résumé et les champs structurés évoluent ensemble selon le type choisi.</p>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            @if(!$content->exists)
                                <div class="form-group">
                                    <label>Type de contenu</label>
                                    <select name="content_type_id" class="form-control" onchange="window.location='?workspace={{ $cmsWorkspace['key'] }}&content_type_id=' + this.value">
                                        @foreach($types as $type)
                                            <option value="{{ $type->id }}" {{ optional($selectedType)->id === $type->id ? 'selected' : '' }}>{{ $type->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            @endif

                            <div class="form-group">
                                <label>Titre</label>
                                <input type="text" name="title" class="form-control" value="{{ old('title', $content->title) }}" required>
                            </div>
                            <div class="form-group">
                                <label>Slug</label>
                                <input type="text" name="slug" class="form-control" value="{{ old('slug', $content->slug) }}">
                            </div>
                            <div class="form-group mb-0">
                                <label>Résumé</label>
                                <textarea name="excerpt" rows="4" class="form-control">{{ old('excerpt', $content->excerpt) }}</textarea>
                            </div>

                            @if($selectedType)
                                <div class="bd-admin-form-group">
                                    <h4>Champs structurés</h4>
                                    @foreach($selectedType->fields as $field)
                                        @php($currentValue = old('fields.' . $field->id, $fieldValues[$field->id] ?? $field->default_value))
                                        <div class="form-group">
                                            <label>{{ $field->name }} @if($field->is_required)<span class="text-danger">*</span>@endif</label>

                                            @if(in_array($field->field_type, ['text', 'url', 'date', 'datetime']))
                                                <input type="{{ $field->field_type === 'datetime' ? 'datetime-local' : ($field->field_type === 'url' ? 'url' : ($field->field_type === 'date' ? 'date' : 'text')) }}" name="fields[{{ $field->id }}]" class="form-control" value="{{ $currentValue }}">
                                            @elseif(in_array($field->field_type, ['textarea', 'richtext', 'json']))
                                                <textarea name="fields[{{ $field->id }}]" rows="4" class="form-control">{{ $currentValue }}</textarea>
                                            @elseif($field->field_type === 'number')
                                                <input type="number" step="any" name="fields[{{ $field->id }}]" class="form-control" value="{{ $currentValue }}">
                                            @elseif($field->field_type === 'boolean')
                                                <input type="hidden" name="fields[{{ $field->id }}]" value="0">
                                                <label class="bd-admin-check-card w-100 mb-0">
                                                    <input type="checkbox" name="fields[{{ $field->id }}]" value="1" {{ (string)$currentValue === '1' ? 'checked' : '' }}>
                                                    <span>
                                                        <strong>{{ $field->name }}</strong>
                                                        <small>{{ $field->help_text ?: 'Valeur booléenne activable pour ce contenu.' }}</small>
                                                    </span>
                                                </label>
                                            @elseif($field->field_type === 'image')
                                                <input type="file" name="fields[{{ $field->id }}]" class="form-control">
                                                @if(!empty($currentValue))
                                                    <img src="{{ asset($currentValue) }}" alt="" class="bd-admin-media-thumb mt-3">
                                                @endif
                                            @endif

                                            @if($field->help_text && $field->field_type !== 'boolean')
                                                <small class="form-text text-muted">{{ $field->help_text }}</small>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="card bd-admin-editor-card">
                        <div class="card-header border-0">
                            <div class="bd-admin-editor-card__header">
                                <div>
                                    <h3>Publication & SEO</h3>
                                    <p>Définissez le statut, le layout et les métadonnées de publication.</p>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="form-group">
                                <label>Statut</label>
                                <select name="status" class="form-control">
                                    @foreach(['draft' => 'Brouillon', 'pending_review' => 'En attente', 'published' => 'Publié', 'archived' => 'Archivé'] as $value => $label)
                                        <option value="{{ $value }}" {{ old('status', $content->status ?: 'draft') === $value ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Layout</label>
                                <input type="text" name="layout" class="form-control" value="{{ old('layout', $content->layout) }}">
                            </div>
                            <div class="form-group">
                                <label>SEO Title</label>
                                <input type="text" name="seo_title" class="form-control" value="{{ old('seo_title', $content->seo_title) }}">
                            </div>
                            <div class="form-group">
                                <label>Publication planifiee</label>
                                <input type="datetime-local" name="published_at" class="form-control" value="{{ old('published_at', optional($content->published_at)->format('Y-m-d\\TH:i')) }}">
                                <small class="form-text text-muted">Laissez vide pour une publication immediate lorsque le statut passe a publie.</small>
                            </div>
                            <div class="form-group mb-0">
                                <label>SEO Description</label>
                                <textarea name="seo_description" rows="4" class="form-control">{{ old('seo_description', $content->seo_description) }}</textarea>
                            </div>
                            <div class="form-group mt-3 mb-0">
                                <label>Note de revision</label>
                                <textarea name="revision_note" rows="3" class="form-control" placeholder="Contexte de modification, validation, retour editorial...">{{ old('revision_note') }}</textarea>
                            </div>
                        </div>
                        <div class="card-footer border-0 d-flex justify-content-end gap-2">
                            @if($content->exists)
                                    <form method="post" action="{{ route('admin.cms.contents.destroy', ['content' => $content, 'workspace' => $cmsWorkspace['key']]) }}" onsubmit="return confirm('Supprimer ce contenu CMS et son historique ?');" class="mr-auto">
                                    @csrf
                                    @method('delete')
                                    <button type="submit" class="btn btn-outline-danger">Supprimer</button>
                                </form>
                            @endif
                            <a href="{{ route('admin.cms.contents.index', ['workspace' => $cmsWorkspace['key']]) }}" class="btn btn-secondary">Retour</a>
                            <button type="submit" class="btn btn-primary">Enregistrer</button>
                        </div>
                    </div>

                    <div class="card bd-admin-editor-card mt-4">
                        <div class="card-header border-0">
                            <div class="bd-admin-editor-card__header">
                                <div>
                                    <h3>Workflow editorial</h3>
                                    <p>Poussez le contenu en revue, publiez-le ou archivez-le sans perdre l'historique.</p>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="bd-admin-workflow-actions">
                                @if($content->exists && in_array($content->status, ['draft', 'archived']))
                                    <form method="post" action="{{ route('admin.cms.contents.transition', ['content' => $content, 'toStatus' => 'pending_review', 'workspace' => $cmsWorkspace['key']]) }}">
                                        @csrf
                                        <button type="submit" class="btn btn-outline-primary btn-block">Envoyer en revue</button>
                                    </form>
                                @endif
                                @if($content->exists && $content->status !== 'published')
                                    <form method="post" action="{{ route('admin.cms.contents.transition', ['content' => $content, 'toStatus' => 'published', 'workspace' => $cmsWorkspace['key']]) }}">
                                        @csrf
                                        <button type="submit" class="btn btn-primary btn-block">Publier</button>
                                    </form>
                                @endif
                                @if($content->exists && $content->status !== 'draft')
                                    <form method="post" action="{{ route('admin.cms.contents.transition', ['content' => $content, 'toStatus' => 'draft', 'workspace' => $cmsWorkspace['key']]) }}">
                                        @csrf
                                        <button type="submit" class="btn btn-outline-secondary btn-block">Repasser en brouillon</button>
                                    </form>
                                @endif
                                @if($content->exists && $content->status !== 'archived')
                                    <form method="post" action="{{ route('admin.cms.contents.transition', ['content' => $content, 'toStatus' => 'archived', 'workspace' => $cmsWorkspace['key']]) }}">
                                        @csrf
                                        <button type="submit" class="btn btn-outline-dark btn-block">Archiver</button>
                                    </form>
                                @endif
                                <a href="{{ route('admin.cms.media.index', ['workspace' => $cmsWorkspace['key']]) }}" class="btn btn-outline-warning btn-block">Ouvrir la mediatheque</a>
                            </div>
                        </div>
                    </div>

                    <div class="card bd-admin-editor-card mt-4">
                        <div class="card-header border-0">
                            <div class="bd-admin-editor-card__header">
                                <div>
                                    <h3>SEO Preview</h3>
                                    <p>Apercu rapide du rendu recherche avant publication.</p>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="bd-admin-seo-preview">
                                <strong>{{ old('seo_title', $content->seo_title ?: $content->title ?: 'Titre SEO') }}</strong>
                                <span>{{ url('/').'/'.(old('slug', $content->slug ?: 'slug-contenu')) }}</span>
                                <p>{{ old('seo_description', $content->seo_description ?: $content->excerpt ?: 'Description SEO du contenu.') }}</p>
                            </div>
                        </div>
                    </div>

                    @if($content->exists)
                        <div class="card bd-admin-editor-card mt-4">
                            <div class="card-header border-0">
                                <div class="bd-admin-editor-card__header">
                                    <div>
                                        <h3>Révisions</h3>
                                        <p>Historique récent des sauvegardes du contenu.</p>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div class="bd-admin-revision-stack">
                                    @forelse($content->revisions as $revision)
                                        <div class="bd-admin-revision-item">
                                            <strong>Révision #{{ $revision->revision_number }}</strong>
                                            <span>{{ $revision->created_at->format('d/m/Y H:i') }} @if($revision->note)• {{ $revision->note }}@endif</span>
                                        </div>
                                    @empty
                                        <div class="text-muted">Aucune révision.</div>
                                    @endforelse
                                </div>
                            </div>
                        </div>

                        <div class="card bd-admin-editor-card mt-4">
                            <div class="card-header border-0">
                                <div class="bd-admin-editor-card__header">
                                    <div>
                                        <h3>Journal editorial</h3>
                                        <p>Transitions de statut les plus recentes.</p>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div class="bd-admin-revision-stack">
                                    @forelse($content->statusLogs as $log)
                                        <div class="bd-admin-revision-item">
                                            <strong>{{ $log->from_status ?: 'creation' }} → {{ $log->to_status }}</strong>
                                            <span>{{ $log->created_at->format('d/m/Y H:i') }} @if($log->actor)• {{ $log->actor->name }}@endif @if($log->note)• {{ $log->note }}@endif</span>
                                        </div>
                                    @empty
                                        <div class="text-muted">Aucune transition journalisee.</div>
                                    @endforelse
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </form>
    </div>
</section>

<style>
    .bd-admin-editor-shell { display:grid; gap:20px; }
    .bd-admin-editor-hero { display:flex; justify-content:space-between; align-items:flex-end; gap:20px; padding:28px 32px; border-radius:32px; background:linear-gradient(135deg,#431407 0%,#c2410c 55%,#a3e635 100%); color:#fff; box-shadow:0 20px 60px rgba(154,52,18,.22); }
    .bd-admin-editor-hero__eyebrow { margin:0 0 8px; font-size:.78rem; letter-spacing:.18em; text-transform:uppercase; font-weight:800; color:#fef9c3; }
    .bd-admin-editor-hero h1 { margin:0; color:#fff !important; font-size:clamp(2rem,4vw,3rem); font-weight:900; line-height:1.04; }
    .bd-admin-editor-hero p { margin:14px 0 0; max-width:760px; color:rgba(255,255,255,.82); line-height:1.8; }
    .bd-admin-editor-card__header h3 { margin:0; color:#020617; font-size:1.2rem; font-weight:900; }
    .bd-admin-editor-card__header p { margin:8px 0 0; color:#64748b; line-height:1.7; }
    .bd-admin-form-group { margin-top:28px; padding-top:28px; border-top:1px solid #e2e8f0; }
    .bd-admin-form-group h4 { margin:0 0 18px; color:#020617; font-size:1.05rem; font-weight:900; }
    .bd-admin-check-card { display:flex; gap:14px; align-items:flex-start; padding:16px 18px; border-radius:20px; background:#f8fafc; border:1px solid #e2e8f0; cursor:pointer; }
    .bd-admin-check-card input { margin-top:4px; }
    .bd-admin-check-card strong { display:block; color:#020617; font-weight:800; }
    .bd-admin-check-card small { display:block; margin-top:4px; color:#64748b; }
    .bd-admin-media-thumb { display:block; width:100%; max-width:220px; border-radius:16px; box-shadow:0 12px 30px rgba(15,23,42,.08); }
    .bd-admin-revision-stack { display:grid; gap:12px; }
    .bd-admin-revision-item { padding:14px 16px; border-radius:18px; background:#f8fafc; border:1px solid #e2e8f0; }
    .bd-admin-revision-item strong { display:block; color:#020617; }
    .bd-admin-revision-item span { display:block; margin-top:4px; color:#64748b; font-size:.9rem; }
    .bd-admin-workflow-actions { display:grid; gap:12px; }
    .bd-admin-seo-preview { padding:18px; border-radius:20px; background:#fffaf2; border:1px solid rgba(249,115,22,.14); }
    .bd-admin-seo-preview strong { display:block; color:#0f766e; font-size:1rem; }
    .bd-admin-seo-preview span { display:block; margin-top:6px; color:#c2410c; font-size:.88rem; }
    .bd-admin-seo-preview p { margin:10px 0 0; color:#64748b; line-height:1.7; }
    @media (max-width: 991.98px) { .bd-admin-editor-hero { flex-direction:column; align-items:flex-start; } }
</style>
@endsection
