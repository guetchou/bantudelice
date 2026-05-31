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
<div style="padding:24px;">
    @include('admin.partials.control_hub_nav')
    <div class="bd-admin-editor-shell">
        <div class="adm-page-bar">
            <div class="adm-page-bar__left">
                <nav class="adm-page-bar__breadcrumb">
                    <span>CMS</span><span class="sep">/</span>
                    <a href="{{ route('admin.cms.content-types.index', ['workspace' => $cmsWorkspace['key']]) }}">Types</a>
                    <span class="sep">/</span>
                    <span>{{ $type->exists ? 'Modifier' : 'Nouveau' }}</span>
                </nav>
                <h1 class="adm-page-bar__title">{{ $type->exists ? 'Modifier le type' : 'Nouveau type' }}</h1>
            </div>
            <div class="adm-page-bar__right">
                <a href="{{ route('admin.cms.content-types.index', ['workspace' => $cmsWorkspace['key']]) }}" class="ops-action-btn ops-action-btn--green" title="Retour aux types"><i class="fas fa-arrow-left"></i></a>
            </div>
        </div>
    </div>

    <div style="display:grid;grid-template-columns:{{ $type->exists ? '1fr 1.4fr' : '1fr' }};gap:20px;margin-top:20px;align-items:start;">
        {{-- Config card --}}
        <div class="bd-admin-editor-card" style="background:#fff;border:1px solid #e5e7eb;border-radius:10px;overflow:hidden;">
            <div style="padding:16px 20px;border-bottom:1px solid #f3f4f6;">
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
                <div style="padding:20px;">
                    <div style="margin-bottom:14px;">
                        <label style="display:block;font-size:13px;font-weight:600;color:#374151;margin-bottom:5px;">Nom</label>
                        <input type="text" name="name" style="width:100%;padding:9px 12px;border:1px solid #d1d5db;border-radius:6px;font-size:14px;box-sizing:border-box;" value="{{ old('name', $type->name) }}" required>
                    </div>
                    <div style="margin-bottom:14px;">
                        <label style="display:block;font-size:13px;font-weight:600;color:#374151;margin-bottom:5px;">Slug</label>
                        <input type="text" name="slug" style="width:100%;padding:9px 12px;border:1px solid #d1d5db;border-radius:6px;font-size:14px;box-sizing:border-box;" value="{{ old('slug', $type->slug) }}">
                    </div>
                    <div style="margin-bottom:14px;">
                        <label style="display:block;font-size:13px;font-weight:600;color:#374151;margin-bottom:5px;">Description</label>
                        <textarea name="description" rows="4" style="width:100%;padding:9px 12px;border:1px solid #d1d5db;border-radius:6px;font-size:14px;box-sizing:border-box;resize:vertical;">{{ old('description', $type->description) }}</textarea>
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
                <div style="padding:14px 20px;border-top:1px solid #f3f4f6;display:flex;justify-content:flex-end;gap:10px;">
                    <a href="{{ route('admin.cms.content-types.index', ['workspace' => $cmsWorkspace['key']]) }}" style="display:inline-flex;align-items:center;padding:8px 16px;border:1px solid #d1d5db;border-radius:6px;font-size:13px;font-weight:600;color:#374151;background:#fff;text-decoration:none;">Retour</a>
                    <button type="submit" style="display:inline-flex;align-items:center;padding:8px 18px;background:#1e3a5f;color:#fff;border:none;border-radius:6px;font-size:13px;font-weight:600;cursor:pointer;">Enregistrer</button>
                </div>
            </form>
        </div>

        {{-- Fields card (edit mode only) --}}
        @if($type->exists)
            <div class="bd-admin-editor-card" style="background:#fff;border:1px solid #e5e7eb;border-radius:10px;overflow:hidden;">
                <div style="padding:16px 20px;border-bottom:1px solid #f3f4f6;">
                    <div class="bd-admin-editor-card__header">
                        <div>
                            <h3>Champs</h3>
                            <p>Configurez les champs structurés disponibles pour ce type.</p>
                        </div>
                        <a href="{{ route('admin.cms.content-types.fields.create', ['contentType' => $type, 'workspace' => $cmsWorkspace['key']]) }}" style="display:inline-flex;align-items:center;padding:7px 14px;background:#1e3a5f;color:#fff;border-radius:6px;font-size:12px;font-weight:600;text-decoration:none;white-space:nowrap;">Ajouter un champ</a>
                    </div>
                </div>
                <div style="overflow-x:auto;">
                    <table style="width:100%;border-collapse:collapse;font-size:13px;">
                        <thead>
                        <tr>
                            @foreach(['Nom','Clé','Type','Requis','Ordre',''] as $h)
                            <th style="padding:9px 14px;font-size:10px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:#9ca3af;border-bottom:1px solid #f3f4f6;background:#f9fafb;text-align:{{ $loop->last ? 'right' : 'left' }};">{{ $h }}</th>
                            @endforeach
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($type->fields as $field)
                            <tr>
                                <td style="padding:10px 14px;color:#374151;vertical-align:middle;border-bottom:1px solid #f3f4f6;"><strong>{{ $field->name }}</strong></td>
                                <td style="padding:10px 14px;color:#374151;vertical-align:middle;border-bottom:1px solid #f3f4f6;"><code>{{ $field->key }}</code></td>
                                <td style="padding:10px 14px;color:#374151;vertical-align:middle;border-bottom:1px solid #f3f4f6;">{{ $field->field_type }}</td>
                                <td style="padding:10px 14px;color:#374151;vertical-align:middle;border-bottom:1px solid #f3f4f6;">{{ $field->is_required ? 'Oui' : 'Non' }}</td>
                                <td style="padding:10px 14px;color:#374151;vertical-align:middle;border-bottom:1px solid #f3f4f6;">{{ $field->sort_order }}</td>
                                <td style="padding:10px 14px;color:#374151;vertical-align:middle;border-bottom:1px solid #f3f4f6;text-align:right;">
                                    <a href="{{ route('admin.cms.content-types.fields.edit', ['contentType' => $type, 'field' => $field, 'workspace' => $cmsWorkspace['key']]) }}" style="display:inline-flex;align-items:center;padding:4px 10px;border:1px solid #1e3a5f;color:#1e3a5f;border-radius:5px;font-size:12px;font-weight:600;text-decoration:none;margin-right:4px;">Modifier</a>
                                    <form method="post" action="{{ route('admin.cms.content-types.fields.destroy', ['contentType' => $type, 'field' => $field, 'workspace' => $cmsWorkspace['key']]) }}" style="display:inline-block">
                                        @csrf
                                        @method('delete')
                                        <button type="submit" style="display:inline-flex;align-items:center;padding:4px 10px;border:1px solid #dc2626;color:#dc2626;border-radius:5px;font-size:12px;font-weight:600;background:none;cursor:pointer;" onclick="return confirm('Supprimer ce champ ?')">Supprimer</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" style="text-align:center;color:#9ca3af;padding:40px;">Aucun champ défini.</td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>
</div>

<style>
    .bd-admin-editor-shell { display:grid; gap:20px; }
    .bd-admin-editor-hero { display:none; }
    .bd-admin-editor-card__header { display:flex; justify-content:space-between; align-items:flex-end; gap:18px; }
    .bd-admin-editor-card__header h3 { margin:0; color:#020617; font-size:1.2rem; font-weight:900; }
    .bd-admin-editor-card__header p { margin:8px 0 0; color:#64748b; line-height:1.7; }
    .bd-admin-check-grid { display:grid; gap:12px; margin-top:18px; }
    .bd-admin-check-card { display:flex; gap:14px; align-items:flex-start; padding:16px 18px; border-radius:20px; background:#f8fafc; border:1px solid #e2e8f0; cursor:pointer; }
    .bd-admin-check-card input { margin-top:4px; }
    .bd-admin-check-card strong { display:block; color:#020617; font-weight:800; }
    .bd-admin-check-card small { display:block; margin-top:4px; color:#64748b; }
    @media (max-width: 991.98px) { .bd-admin-editor-hero { flex-direction:column; align-items:flex-start; } }
</style>
@endsection
