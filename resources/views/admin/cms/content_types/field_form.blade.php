@extends('layouts.admin-modern')
@section('title', 'CMS - Champ')
@section('page_title', 'Champ de contenu')
@section('nav_active', 'cms')

@php
    $cmsWorkspace = $cmsWorkspace ?? [
        'key' => request('workspace', 'bantudelice'),
        'label' => request('workspace') === 'kende' ? 'Kende' : (request('workspace') === 'mema' ? 'Mema' : 'BantuDelice'),
        'eyebrow' => request('workspace') === 'kende' ? 'CMS Mobilite' : (request('workspace') === 'mema' ? 'CMS Colis' : 'CMS Food ops'),
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
                    <h1>{{ $field->exists ? 'Modifier le champ' : 'Nouveau champ' }}</h1>
                    <p>Ajoutez un champ structure au type <strong>{{ $contentType->name }}</strong> pour enrichir les contenus {{ $cmsWorkspace['label'] }} sans retomber dans du texte libre partout.</p>
                </div>
                <a href="{{ route('admin.cms.content-types.edit', ['contentType' => $contentType, 'workspace' => $cmsWorkspace['key']]) }}" class="btn btn-light">Retour au type</a>
            </section>
        </div>
    </div>
</div>

<section class="content">
    <div class="container-fluid">
        <div class="card bd-admin-editor-card">
            <div class="card-header border-0">
                <div class="bd-admin-editor-card__header">
                    <div>
                        <h3>Configuration du champ</h3>
                        <p>Définissez le type, l’ordre, la valeur par défaut et l’aide éditoriale associée.</p>
                    </div>
                </div>
            </div>
            <form method="post" action="{{ $field->exists ? route('admin.cms.content-types.fields.update', ['contentType' => $contentType, 'field' => $field, 'workspace' => $cmsWorkspace['key']]) : route('admin.cms.content-types.fields.store', ['contentType' => $contentType, 'workspace' => $cmsWorkspace['key']]) }}">
                @csrf
                @if($field->exists)
                    @method('put')
                @endif
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label>Nom</label>
                            <input type="text" name="name" class="form-control" value="{{ old('name', $field->name) }}" required>
                        </div>
                        <div class="col-md-6 form-group">
                            <label>Clé</label>
                            <input type="text" name="key" class="form-control" value="{{ old('key', $field->key) }}">
                        </div>
                        <div class="col-md-4 form-group">
                            <label>Type de champ</label>
                            <select name="field_type" class="form-control" required>
                                @foreach(['text','textarea','richtext','image','number','boolean','date','datetime','url','json'] as $fieldType)
                                    <option value="{{ $fieldType }}" {{ old('field_type', $field->field_type) === $fieldType ? 'selected' : '' }}>{{ $fieldType }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4 form-group">
                            <label>Ordre</label>
                            <input type="number" name="sort_order" class="form-control" value="{{ old('sort_order', $field->sort_order) }}">
                        </div>
                        <div class="col-md-4 form-group">
                            <label>Valeur par défaut</label>
                            <input type="text" name="default_value" class="form-control" value="{{ old('default_value', $field->default_value) }}">
                        </div>
                        <div class="col-md-6 form-group">
                            <label>Aide</label>
                            <input type="text" name="help_text" class="form-control" value="{{ old('help_text', $field->help_text) }}">
                        </div>
                        <div class="col-md-6 form-group d-flex align-items-end">
                            <label class="bd-admin-check-card w-100 mb-0">
                                <input type="checkbox" name="is_required" value="1" {{ old('is_required', $field->is_required) ? 'checked' : '' }}>
                                <span>
                                    <strong>Champ requis</strong>
                                    <small>Obligatoire pour l’enregistrement du contenu</small>
                                </span>
                            </label>
                        </div>
                        <div class="col-12 form-group mb-0">
                            <label>Options</label>
                            <textarea name="options" rows="4" class="form-control">{{ old('options', $field->options) }}</textarea>
                            <small class="form-text text-muted">JSON ou une option par ligne pour les sélections futures.</small>
                        </div>
                    </div>
                </div>
                <div class="card-footer border-0 d-flex justify-content-end gap-2">
                    <a href="{{ route('admin.cms.content-types.edit', ['contentType' => $contentType, 'workspace' => $cmsWorkspace['key']]) }}" class="btn btn-secondary">Retour</a>
                    <button type="submit" class="btn btn-primary">Enregistrer</button>
                </div>
            </form>
        </div>
    </div>
</section>

<style>
    .bd-admin-editor-shell { display:grid; gap:20px; }
    .bd-admin-editor-hero { display:flex; justify-content:space-between; align-items:flex-end; gap:20px; padding:28px 32px; border-radius:32px; background:linear-gradient(135deg,#020617 0%,#0f172a 60%,#155e75 100%); color:#fff; box-shadow:0 20px 60px rgba(15,23,42,.22); }
    .bd-admin-editor-hero__eyebrow { margin:0 0 8px; font-size:.78rem; letter-spacing:.18em; text-transform:uppercase; font-weight:800; color:#bae6fd; }
    .bd-admin-editor-hero h1 { margin:0; color:#fff !important; font-size:clamp(2rem,4vw,3rem); font-weight:900; line-height:1.04; }
    .bd-admin-editor-hero p { margin:14px 0 0; max-width:760px; color:rgba(255,255,255,.82); line-height:1.8; }
    .bd-admin-editor-card__header h3 { margin:0; color:#020617; font-size:1.2rem; font-weight:900; }
    .bd-admin-editor-card__header p { margin:8px 0 0; color:#64748b; line-height:1.7; }
    .bd-admin-check-card { display:flex; gap:14px; align-items:flex-start; padding:16px 18px; border-radius:20px; background:#f8fafc; border:1px solid #e2e8f0; cursor:pointer; }
    .bd-admin-check-card input { margin-top:4px; }
    .bd-admin-check-card strong { display:block; color:#020617; font-weight:800; }
    .bd-admin-check-card small { display:block; margin-top:4px; color:#64748b; }
    @media (max-width: 991.98px) { .bd-admin-editor-hero { flex-direction:column; align-items:flex-start; } }
</style>
@endsection
