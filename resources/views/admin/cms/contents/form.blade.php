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

@section('style')
<style>
.cms-input { width:100%; padding:9px 12px; border:1px solid #d1d5db; border-radius:6px; font-size:14px; color:#111827; background:#fff; box-sizing:border-box; }
.cms-input:focus { outline:none; border-color:#1e3a5f; }
.cms-label { display:block; font-size:13px; font-weight:600; color:#374151; margin-bottom:5px; }
.cms-field { margin-bottom:14px; }
.cms-field:last-child { margin-bottom:0; }
.cms-hint { font-size:12px; color:#9ca3af; margin-top:4px; }
.cms-card { background:#fff; border:1px solid #e5e7eb; border-radius:10px; overflow:hidden; margin-bottom:16px; }
.cms-card__header { padding:16px 20px; border-bottom:1px solid #f3f4f6; }
.cms-card__body { padding:20px; }
.cms-card__footer { padding:14px 20px; border-top:1px solid #f3f4f6; display:flex; align-items:center; gap:10px; }
.cms-btn-primary { display:inline-flex; align-items:center; gap:6px; padding:8px 18px; background:#1e3a5f; color:#fff; border:none; border-radius:6px; font-size:13px; font-weight:600; cursor:pointer; }
.cms-btn-primary:hover { opacity:.85; }
.cms-btn-secondary { display:inline-flex; align-items:center; padding:8px 16px; border:1px solid #d1d5db; border-radius:6px; font-size:13px; font-weight:600; color:#374151; background:#fff; cursor:pointer; text-decoration:none; }
.cms-btn-secondary:hover { background:#f9fafb; color:#111827; text-decoration:none; }
.cms-btn-danger { display:inline-flex; align-items:center; padding:8px 16px; border:1px solid #dc2626; border-radius:6px; font-size:13px; font-weight:600; color:#dc2626; background:#fff; cursor:pointer; }
.cms-btn-danger:hover { background:#fef2f2; }
.cms-btn-workflow { display:block; width:100%; padding:9px; text-align:center; border-radius:6px; font-size:13px; font-weight:600; cursor:pointer; border:1px solid #d1d5db; background:#fff; color:#374151; margin-bottom:0; }
.cms-btn-workflow--primary { background:#1e3a5f; color:#fff; border-color:#1e3a5f; }
.cms-btn-workflow--outline-primary { border-color:#1e3a5f; color:#1e3a5f; }
.cms-btn-workflow--outline-secondary { border-color:#6b7280; color:#6b7280; }
.cms-btn-workflow--outline-dark { border-color:#111827; color:#111827; }
.cms-btn-workflow--warning { border-color:#f59e0b; color:#92400e; text-decoration:none; display:block; padding:9px; text-align:center; border-radius:6px; font-size:13px; font-weight:600; }
.cms-workflow-grid { display:grid; gap:10px; }
</style>
@endsection

@section('content')
<div style="padding:24px;">
    @include('admin.partials.control_hub_nav')
    <div class="bd-admin-editor-shell">
        <div class="adm-page-bar">
            <div class="adm-page-bar__left">
                <nav class="adm-page-bar__breadcrumb">
                    <span>CMS</span><span class="sep">/</span>
                    <a href="{{ route('admin.cms.contents.index', ['workspace' => $cmsWorkspace['key']]) }}">{{ $cmsWorkspace['label'] }}</a>
                    <span class="sep">/</span>
                    <span>{{ $content->exists ? 'Modifier' : 'Nouveau' }}</span>
                </nav>
                <h1 class="adm-page-bar__title">{{ $content->exists ? 'Modifier le contenu' : 'Nouveau contenu' }}</h1>
            </div>
            <div class="adm-page-bar__right">
                <a href="{{ route('admin.cms.contents.index', ['workspace' => $cmsWorkspace['key']]) }}" class="ops-action-btn ops-action-btn--green" title="Retour aux contenus"><i class="fas fa-arrow-left"></i></a>
            </div>
        </div>
    </div>

    <div style="display:grid;grid-template-columns:1fr 320px;gap:20px;margin-top:20px;align-items:start;">

        {{-- Main form --}}
        <div>
            <form method="post" enctype="multipart/form-data" action="{{ $content->exists ? route('admin.cms.contents.update', ['content' => $content, 'workspace' => $cmsWorkspace['key']]) : route('admin.cms.contents.store', ['workspace' => $cmsWorkspace['key']]) }}">
                @csrf
                @if($content->exists)
                    @method('put')
                @endif

                <div class="cms-card">
                    <div class="cms-card__header">
                        <div class="bd-admin-editor-card__header">
                            <div>
                                <h3 style="margin:0;color:#020617;font-size:1.1rem;font-weight:900;">Contenu principal</h3>
                                <p style="margin:6px 0 0;color:#64748b;">Le titre, le résumé et les champs structurés évoluent ensemble selon le type choisi.</p>
                            </div>
                        </div>
                    </div>
                    <div class="cms-card__body">
                        @if(!$content->exists)
                            <div class="cms-field">
                                <label class="cms-label">Type de contenu</label>
                                <select name="content_type_id" class="cms-input" onchange="window.location='?workspace={{ $cmsWorkspace['key'] }}&content_type_id=' + this.value">
                                    @foreach($types as $type)
                                        <option value="{{ $type->id }}" {{ optional($selectedType)->id === $type->id ? 'selected' : '' }}>{{ $type->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @endif

                        <div class="cms-field">
                            <label class="cms-label">Titre</label>
                            <input type="text" name="title" class="cms-input" value="{{ old('title', $content->title) }}" required>
                        </div>
                        <div class="cms-field">
                            <label class="cms-label">Slug</label>
                            <input type="text" name="slug" class="cms-input" value="{{ old('slug', $content->slug) }}">
                        </div>
                        <div class="cms-field" style="margin-bottom:0;">
                            <label class="cms-label">Résumé</label>
                            <textarea name="excerpt" rows="4" class="cms-input" style="resize:vertical;">{{ old('excerpt', $content->excerpt) }}</textarea>
                        </div>

                        @if($selectedType)
                            <div style="margin-top:28px;padding-top:28px;border-top:1px solid #e2e8f0;">
                                <h4 style="margin:0 0 18px;color:#020617;font-size:1.05rem;font-weight:900;">Champs structurés</h4>
                                @foreach($selectedType->fields as $field)
                                    @php($currentValue = old('fields.' . $field->id, $fieldValues[$field->id] ?? $field->default_value))
                                    <div class="cms-field">
                                        <label class="cms-label">{{ $field->name }} @if($field->is_required)<span style="color:#dc2626;">*</span>@endif</label>

                                        @if(in_array($field->field_type, ['text', 'url', 'date', 'datetime']))
                                            <input type="{{ $field->field_type === 'datetime' ? 'datetime-local' : ($field->field_type === 'url' ? 'url' : ($field->field_type === 'date' ? 'date' : 'text')) }}" name="fields[{{ $field->id }}]" class="cms-input" value="{{ $currentValue }}">
                                        @elseif(in_array($field->field_type, ['textarea', 'richtext', 'json']))
                                            <textarea name="fields[{{ $field->id }}]" rows="4" class="cms-input" style="resize:vertical;">{{ $currentValue }}</textarea>
                                        @elseif($field->field_type === 'number')
                                            <input type="number" step="any" name="fields[{{ $field->id }}]" class="cms-input" value="{{ $currentValue }}">
                                        @elseif($field->field_type === 'boolean')
                                            <input type="hidden" name="fields[{{ $field->id }}]" value="0">
                                            <label class="bd-admin-check-card" style="width:100%;margin:0;">
                                                <input type="checkbox" name="fields[{{ $field->id }}]" value="1" {{ (string)$currentValue === '1' ? 'checked' : '' }}>
                                                <span>
                                                    <strong>{{ $field->name }}</strong>
                                                    <small>{{ $field->help_text ?: 'Valeur booléenne activable pour ce contenu.' }}</small>
                                                </span>
                                            </label>
                                        @elseif($field->field_type === 'image')
                                            <input type="file" name="fields[{{ $field->id }}]" class="cms-input">
                                            @if(!empty($currentValue))
                                                <img src="{{ asset($currentValue) }}" alt="" style="display:block;width:100%;max-width:220px;border-radius:16px;box-shadow:0 12px 30px rgba(15,23,42,.08);margin-top:12px;">
                                            @endif
                                        @endif

                                        @if($field->help_text && $field->field_type !== 'boolean')
                                            <div class="cms-hint">{{ $field->help_text }}</div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            </form>
        </div>

        {{-- Sidebar --}}
        <div>
            {{-- Publication & SEO (separate form since it shares the outer form action, just included inline) --}}
            <div class="cms-card">
                <div class="cms-card__header">
                    <h3 style="margin:0;color:#020617;font-size:1rem;font-weight:900;">Publication &amp; SEO</h3>
                    <p style="margin:6px 0 0;color:#64748b;font-size:13px;">Définissez le statut, le layout et les métadonnées.</p>
                </div>
                <form method="post" enctype="multipart/form-data" action="{{ $content->exists ? route('admin.cms.contents.update', ['content' => $content, 'workspace' => $cmsWorkspace['key']]) : route('admin.cms.contents.store', ['workspace' => $cmsWorkspace['key']]) }}">
                    @csrf
                    @if($content->exists)
                        @method('put')
                    @endif
                    <div class="cms-card__body">
                        <div class="cms-field">
                            <label class="cms-label">Statut</label>
                            <select name="status" class="cms-input">
                                @foreach(['draft' => 'Brouillon', 'pending_review' => 'En attente', 'published' => 'Publié', 'archived' => 'Archivé'] as $value => $label)
                                    <option value="{{ $value }}" {{ old('status', $content->status ?: 'draft') === $value ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="cms-field">
                            <label class="cms-label">Layout</label>
                            <input type="text" name="layout" class="cms-input" value="{{ old('layout', $content->layout) }}">
                        </div>
                        <div class="cms-field">
                            <label class="cms-label">SEO Title</label>
                            <input type="text" name="seo_title" class="cms-input" value="{{ old('seo_title', $content->seo_title) }}">
                        </div>
                        <div class="cms-field">
                            <label class="cms-label">Publication planifiee</label>
                            <input type="datetime-local" name="published_at" class="cms-input" value="{{ old('published_at', optional($content->published_at)->format('Y-m-d\\TH:i')) }}">
                            <div class="cms-hint">Laissez vide pour une publication immediate lorsque le statut passe a publie.</div>
                        </div>
                        <div class="cms-field">
                            <label class="cms-label">SEO Description</label>
                            <textarea name="seo_description" rows="4" class="cms-input" style="resize:vertical;">{{ old('seo_description', $content->seo_description) }}</textarea>
                        </div>
                        <div class="cms-field" style="margin-bottom:0;">
                            <label class="cms-label">Note de revision</label>
                            <textarea name="revision_note" rows="3" class="cms-input" style="resize:vertical;" placeholder="Contexte de modification, validation, retour editorial...">{{ old('revision_note') }}</textarea>
                        </div>
                    </div>
                    <div class="cms-card__footer">
                        @if($content->exists)
                            <form method="post" action="{{ route('admin.cms.contents.destroy', ['content' => $content, 'workspace' => $cmsWorkspace['key']]) }}" onsubmit="return confirm('Supprimer ce contenu CMS et son historique ?');" style="margin-right:auto;">
                                @csrf
                                @method('delete')
                                <button type="submit" class="cms-btn-danger">Supprimer</button>
                            </form>
                        @endif
                        <a href="{{ route('admin.cms.contents.index', ['workspace' => $cmsWorkspace['key']]) }}" class="cms-btn-secondary">Retour</a>
                        <button type="submit" class="cms-btn-primary">Enregistrer</button>
                    </div>
                </form>
            </div>

            {{-- Workflow --}}
            <div class="cms-card">
                <div class="cms-card__header">
                    <h3 style="margin:0;color:#020617;font-size:1rem;font-weight:900;">Workflow editorial</h3>
                    <p style="margin:6px 0 0;color:#64748b;font-size:13px;">Poussez le contenu en revue, publiez-le ou archivez-le sans perdre l'historique.</p>
                </div>
                <div class="cms-card__body">
                    <div class="cms-workflow-grid">
                        @if($content->exists && in_array($content->status, ['draft', 'archived']))
                            <form method="post" action="{{ route('admin.cms.contents.transition', ['content' => $content, 'toStatus' => 'pending_review', 'workspace' => $cmsWorkspace['key']]) }}">
                                @csrf
                                <button type="submit" class="cms-btn-workflow cms-btn-workflow--outline-primary">Envoyer en revue</button>
                            </form>
                        @endif
                        @if($content->exists && $content->status !== 'published')
                            <form method="post" action="{{ route('admin.cms.contents.transition', ['content' => $content, 'toStatus' => 'published', 'workspace' => $cmsWorkspace['key']]) }}">
                                @csrf
                                <button type="submit" class="cms-btn-workflow cms-btn-workflow--primary">Publier</button>
                            </form>
                        @endif
                        @if($content->exists && $content->status !== 'draft')
                            <form method="post" action="{{ route('admin.cms.contents.transition', ['content' => $content, 'toStatus' => 'draft', 'workspace' => $cmsWorkspace['key']]) }}">
                                @csrf
                                <button type="submit" class="cms-btn-workflow cms-btn-workflow--outline-secondary">Repasser en brouillon</button>
                            </form>
                        @endif
                        @if($content->exists && $content->status !== 'archived')
                            <form method="post" action="{{ route('admin.cms.contents.transition', ['content' => $content, 'toStatus' => 'archived', 'workspace' => $cmsWorkspace['key']]) }}">
                                @csrf
                                <button type="submit" class="cms-btn-workflow cms-btn-workflow--outline-dark">Archiver</button>
                            </form>
                        @endif
                        <a href="{{ route('admin.cms.media.index', ['workspace' => $cmsWorkspace['key']]) }}" class="cms-btn-workflow--warning">Ouvrir la mediatheque</a>
                    </div>
                </div>
            </div>

            {{-- SEO Preview --}}
            <div class="cms-card">
                <div class="cms-card__header">
                    <h3 style="margin:0;color:#020617;font-size:1rem;font-weight:900;">SEO Preview</h3>
                    <p style="margin:6px 0 0;color:#64748b;font-size:13px;">Apercu rapide du rendu recherche avant publication.</p>
                </div>
                <div class="cms-card__body">
                    <div class="bd-admin-seo-preview">
                        <strong>{{ old('seo_title', $content->seo_title ?: $content->title ?: 'Titre SEO') }}</strong>
                        <span>{{ url('/').'/'.(old('slug', $content->slug ?: 'slug-contenu')) }}</span>
                        <p>{{ old('seo_description', $content->seo_description ?: $content->excerpt ?: 'Description SEO du contenu.') }}</p>
                    </div>
                </div>
            </div>

            {{-- Revisions --}}
            @if($content->exists)
                <div class="cms-card">
                    <div class="cms-card__header">
                        <h3 style="margin:0;color:#020617;font-size:1rem;font-weight:900;">Révisions</h3>
                        <p style="margin:6px 0 0;color:#64748b;font-size:13px;">Historique récent des sauvegardes du contenu.</p>
                    </div>
                    <div class="cms-card__body">
                        <div class="bd-admin-revision-stack">
                            @forelse($content->revisions as $revision)
                                <div class="bd-admin-revision-item">
                                    <strong>Révision #{{ $revision->revision_number }}</strong>
                                    <span>{{ $revision->created_at->format('d/m/Y H:i') }} @if($revision->note)• {{ $revision->note }}@endif</span>
                                </div>
                            @empty
                                <div style="color:#9ca3af;font-size:13px;">Aucune révision.</div>
                            @endforelse
                        </div>
                    </div>
                </div>

                <div class="cms-card">
                    <div class="cms-card__header">
                        <h3 style="margin:0;color:#020617;font-size:1rem;font-weight:900;">Journal editorial</h3>
                        <p style="margin:6px 0 0;color:#64748b;font-size:13px;">Transitions de statut les plus recentes.</p>
                    </div>
                    <div class="cms-card__body">
                        <div class="bd-admin-revision-stack">
                            @forelse($content->statusLogs as $log)
                                <div class="bd-admin-revision-item">
                                    <strong>{{ $log->from_status ?: 'creation' }} → {{ $log->to_status }}</strong>
                                    <span>{{ $log->created_at->format('d/m/Y H:i') }} @if($log->actor)• {{ $log->actor->name }}@endif @if($log->note)• {{ $log->note }}@endif</span>
                                </div>
                            @empty
                                <div style="color:#9ca3af;font-size:13px;">Aucune transition journalisee.</div>
                            @endforelse
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

<style>
    .bd-admin-editor-shell { display:grid; gap:20px; }
    .bd-admin-editor-hero { display:none; }
    .bd-admin-check-card { display:flex; gap:14px; align-items:flex-start; padding:16px 18px; border-radius:20px; background:#f8fafc; border:1px solid #e2e8f0; cursor:pointer; }
    .bd-admin-check-card input { margin-top:4px; }
    .bd-admin-check-card strong { display:block; color:#020617; font-weight:800; }
    .bd-admin-check-card small { display:block; margin-top:4px; color:#64748b; }
    .bd-admin-revision-stack { display:grid; gap:12px; }
    .bd-admin-revision-item { padding:14px 16px; border-radius:18px; background:#f8fafc; border:1px solid #e2e8f0; }
    .bd-admin-revision-item strong { display:block; color:#020617; }
    .bd-admin-revision-item span { display:block; margin-top:4px; color:#64748b; font-size:.9rem; }
    .bd-admin-seo-preview { padding:18px; border-radius:20px; background:#fffaf2; border:1px solid rgba(249,115,22,.14); }
    .bd-admin-seo-preview strong { display:block; color:#0f766e; font-size:1rem; }
    .bd-admin-seo-preview span { display:block; margin-top:6px; color:#c2410c; font-size:.88rem; }
    .bd-admin-seo-preview p { margin:10px 0 0; color:#64748b; line-height:1.7; }
    @media (max-width:900px) { .bd-admin-editor-hero { flex-direction:column; align-items:flex-start; } }
</style>
@endsection
