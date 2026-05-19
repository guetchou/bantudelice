@extends('layouts.admin-modern')
@section('title', 'CMS - Type de contenu')
@section('page_title', 'Type de contenu')
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
                    <h1>{{ $type->exists ? 'Modifier le type' : 'Nouveau type' }}</h1>
                    <p>Définissez la structure, les revisions et les champs qui piloteront les contenus {{ $cmsWorkspace['label'] }}.</p>
                </div>
                <a href="{{ route('admin.cms.content-types.index', ['workspace' => $cmsWorkspace['key']]) }}" class="btn btn-light">Retour aux types</a>
            </section>
        </div>
    </div>
</div>

<section class="content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-5">
                <div class="card bd-admin-editor-card">
                    <div class="card-header border-0">
                        <div class="bd-admin-editor-card__header">
                            <div>
                                <h3>Configuration</h3>
                                <p>Nom, slug, description et capacités éditoriales du type.</p>
                            </div>
                        </div>
                    </div>
                    <form method="post" action="{{ $type->exists ? route('admin.cms.content-types.update', ['contentType' => $type, 'workspace' => $cmsWorkspace['key']]) : route('admin.cms.content-types.store', ['workspace' => $cmsWorkspace['key']]) }}">
                        @csrf
                        @if($type->exists)
                            @method('put')
                        @endif
                        <div class="card-body">
                            <div class="form-group">
                                <label>Nom</label>
                                <input type="text" name="name" class="form-control" value="{{ old('name', $type->name) }}" required>
                            </div>
                            <div class="form-group">
                                <label>Slug</label>
                                <input type="text" name="slug" class="form-control" value="{{ old('slug', $type->slug) }}">
                            </div>
                            <div class="form-group">
                                <label>Description</label>
                                <textarea name="description" rows="4" class="form-control">{{ old('description', $type->description) }}</textarea>
                            </div>
                            <div class="bd-admin-check-grid">
                                <label class="bd-admin-check-card">
                                    <input type="checkbox" name="is_active" value="1" {{ old('is_active', $type->is_active) ? 'checked' : '' }}>
                                    <span>
                                        <strong>Type actif</strong>
                                        <small>Disponible dans le back-office</small>
                                    </span>
                                </label>
                                <label class="bd-admin-check-card">
                                    <input type="checkbox" name="supports_revisions" value="1" {{ old('supports_revisions', $type->supports_revisions) ? 'checked' : '' }}>
                                    <span>
                                        <strong>Révisions</strong>
                                        <small>Historique des modifications</small>
                                    </span>
                                </label>
                            </div>
                        </div>
                        <div class="card-footer border-0 d-flex justify-content-end gap-2">
                            <a href="{{ route('admin.cms.content-types.index', ['workspace' => $cmsWorkspace['key']]) }}" class="btn btn-secondary">Retour</a>
                            <button type="submit" class="btn btn-primary">Enregistrer</button>
                        </div>
                    </form>
                </div>
            </div>

            @if($type->exists)
                <div class="col-lg-7">
                    <div class="card bd-admin-editor-card">
                        <div class="card-header border-0">
                            <div class="bd-admin-editor-card__header">
                                <div>
                                    <h3>Champs</h3>
                                    <p>Configurez les champs structurés disponibles pour ce type.</p>
                                </div>
                                <a href="{{ route('admin.cms.content-types.fields.create', ['contentType' => $type, 'workspace' => $cmsWorkspace['key']]) }}" class="btn btn-primary">Ajouter un champ</a>
                            </div>
                        </div>
                        <div class="card-body table-responsive p-0">
                            <table class="table table-hover">
                                <thead>
                                <tr>
                                    <th>Nom</th>
                                    <th>Clé</th>
                                    <th>Type</th>
                                    <th>Requis</th>
                                    <th>Ordre</th>
                                    <th></th>
                                </tr>
                                </thead>
                                <tbody>
                                @forelse($type->fields as $field)
                                    <tr>
                                        <td><strong>{{ $field->name }}</strong></td>
                                        <td><code>{{ $field->key }}</code></td>
                                        <td>{{ $field->field_type }}</td>
                                        <td>{{ $field->is_required ? 'Oui' : 'Non' }}</td>
                                        <td>{{ $field->sort_order }}</td>
                                        <td class="text-right">
                                            <a href="{{ route('admin.cms.content-types.fields.edit', ['contentType' => $type, 'field' => $field, 'workspace' => $cmsWorkspace['key']]) }}" class="btn btn-sm btn-outline-primary">Modifier</a>
                                            <form method="post" action="{{ route('admin.cms.content-types.fields.destroy', ['contentType' => $type, 'field' => $field, 'workspace' => $cmsWorkspace['key']]) }}" style="display:inline-block">
                                                @csrf
                                                @method('delete')
                                                <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Supprimer ce champ ?')">Supprimer</button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center text-muted py-5">Aucun champ défini.</td>
                                    </tr>
                                @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</section>

<style>
    .bd-admin-editor-shell { display:grid; gap:20px; }
    .bd-admin-editor-hero { display:flex; justify-content:space-between; align-items:flex-end; gap:20px; padding:28px 32px; border-radius:32px; background:linear-gradient(135deg,#020617 0%,#0f172a 60%,#155e75 100%); color:#fff; box-shadow:0 20px 60px rgba(15,23,42,.22); }
    .bd-admin-editor-hero__eyebrow { margin:0 0 8px; font-size:.78rem; letter-spacing:.18em; text-transform:uppercase; font-weight:800; color:#bae6fd; }
    .bd-admin-editor-hero h1 { margin:0; color:#fff !important; font-size:clamp(2rem,4vw,3rem); font-weight:900; line-height:1.04; }
    .bd-admin-editor-hero p { margin:14px 0 0; max-width:760px; color:rgba(255,255,255,.82); line-height:1.8; }
    .bd-admin-editor-card__header { display:flex; justify-content:space-between; align-items:flex-end; gap:18px; }
    .bd-admin-editor-card__header h3 { margin:0; color:#020617; font-size:1.2rem; font-weight:900; }
    .bd-admin-editor-card__header p { margin:8px 0 0; color:#64748b; line-height:1.7; }
    .bd-admin-check-grid { display:grid; gap:12px; margin-top:18px; }
    .bd-admin-check-card { display:flex; gap:14px; align-items:flex-start; padding:16px 18px; border-radius:20px; background:#f8fafc; border:1px solid #e2e8f0; cursor:pointer; }
    .bd-admin-check-card input { margin-top:4px; }
    .bd-admin-check-card strong { display:block; color:#020617; font-weight:800; }
    .bd-admin-check-card small { display:block; margin-top:4px; color:#64748b; }
    @media (max-width: 991.98px) { .bd-admin-editor-hero, .bd-admin-editor-card__header { flex-direction:column; align-items:flex-start; } }
</style>
@endsection
