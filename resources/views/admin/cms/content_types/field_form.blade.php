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
<div style="padding:24px;">
    @include('admin.partials.control_hub_nav')
    <div class="bd-admin-editor-shell">
        <div class="adm-page-bar">
            <div class="adm-page-bar__left">
                <nav class="adm-page-bar__breadcrumb">
                    <span>CMS</span><span class="sep">/</span>
                    <a href="{{ route('admin.cms.content-types.index', ['workspace' => $cmsWorkspace['key']]) }}">Types</a>
                    <span class="sep">/</span>
                    <a href="{{ route('admin.cms.content-types.edit', ['contentType' => $contentType, 'workspace' => $cmsWorkspace['key']]) }}">{{ $contentType->name }}</a>
                    <span class="sep">/</span>
                    <span>{{ $field->exists ? 'Modifier' : 'Nouveau champ' }}</span>
                </nav>
                <h1 class="adm-page-bar__title">{{ $field->exists ? 'Modifier le champ' : 'Nouveau champ' }}</h1>
            </div>
            <div class="adm-page-bar__right">
                <a href="{{ route('admin.cms.content-types.edit', ['contentType' => $contentType, 'workspace' => $cmsWorkspace['key']]) }}" class="ops-action-btn ops-action-btn--green" title="Retour au type"><i class="fas fa-arrow-left"></i></a>
            </div>
        </div>
    </div>

    <div class="bd-admin-editor-card" style="background:#fff;border:1px solid #e5e7eb;border-radius:10px;overflow:hidden;margin-top:20px;">
        <div style="padding:16px 20px;border-bottom:1px solid #f3f4f6;">
            <div class="bd-admin-editor-card__header">
                <div>
                    <h3>Configuration du champ</h3>
                    <p>Définissez le type, l'ordre, la valeur par défaut et l'aide éditoriale associée.</p>
                </div>
            </div>
        </div>
        <form method="post" action="{{ $field->exists ? route('admin.cms.content-types.fields.update', ['contentType' => $contentType, 'field' => $field, 'workspace' => $cmsWorkspace['key']]) : route('admin.cms.content-types.fields.store', ['contentType' => $contentType, 'workspace' => $cmsWorkspace['key']]) }}">
            @csrf
            @if($field->exists)
                @method('put')
            @endif
            <div style="padding:20px;">
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:14px;">
                    <div>
                        <label style="display:block;font-size:13px;font-weight:600;color:#374151;margin-bottom:5px;">Nom</label>
                        <input type="text" name="name" style="width:100%;padding:9px 12px;border:1px solid #d1d5db;border-radius:6px;font-size:14px;box-sizing:border-box;" value="{{ old('name', $field->name) }}" required>
                    </div>
                    <div>
                        <label style="display:block;font-size:13px;font-weight:600;color:#374151;margin-bottom:5px;">Clé</label>
                        <input type="text" name="key" style="width:100%;padding:9px 12px;border:1px solid #d1d5db;border-radius:6px;font-size:14px;box-sizing:border-box;" value="{{ old('key', $field->key) }}">
                    </div>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:14px;margin-bottom:14px;">
                    <div>
                        <label style="display:block;font-size:13px;font-weight:600;color:#374151;margin-bottom:5px;">Type de champ</label>
                        <select name="field_type" style="width:100%;padding:9px 12px;border:1px solid #d1d5db;border-radius:6px;font-size:14px;box-sizing:border-box;" required>
                            @foreach(['text','textarea','richtext','image','number','boolean','date','datetime','url','json'] as $fieldType)
                                <option value="{{ $fieldType }}" {{ old('field_type', $field->field_type) === $fieldType ? 'selected' : '' }}>{{ $fieldType }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label style="display:block;font-size:13px;font-weight:600;color:#374151;margin-bottom:5px;">Ordre</label>
                        <input type="number" name="sort_order" style="width:100%;padding:9px 12px;border:1px solid #d1d5db;border-radius:6px;font-size:14px;box-sizing:border-box;" value="{{ old('sort_order', $field->sort_order) }}">
                    </div>
                    <div>
                        <label style="display:block;font-size:13px;font-weight:600;color:#374151;margin-bottom:5px;">Valeur par défaut</label>
                        <input type="text" name="default_value" style="width:100%;padding:9px 12px;border:1px solid #d1d5db;border-radius:6px;font-size:14px;box-sizing:border-box;" value="{{ old('default_value', $field->default_value) }}">
                    </div>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:14px;align-items:end;">
                    <div>
                        <label style="display:block;font-size:13px;font-weight:600;color:#374151;margin-bottom:5px;">Aide</label>
                        <input type="text" name="help_text" style="width:100%;padding:9px 12px;border:1px solid #d1d5db;border-radius:6px;font-size:14px;box-sizing:border-box;" value="{{ old('help_text', $field->help_text) }}">
                    </div>
                    <div>
                        <label class="bd-admin-check-card" style="width:100%;margin:0;">
                            <input type="checkbox" name="is_required" value="1" {{ old('is_required', $field->is_required) ? 'checked' : '' }}>
                            <span>
                                <strong>Champ requis</strong>
                                <small>Obligatoire pour l'enregistrement du contenu</small>
                            </span>
                        </label>
                    </div>
                </div>
                <div>
                    <label style="display:block;font-size:13px;font-weight:600;color:#374151;margin-bottom:5px;">Options</label>
                    <textarea name="options" rows="4" style="width:100%;padding:9px 12px;border:1px solid #d1d5db;border-radius:6px;font-size:14px;box-sizing:border-box;resize:vertical;">{{ old('options', $field->options) }}</textarea>
                    <div style="font-size:12px;color:#9ca3af;margin-top:4px;">JSON ou une option par ligne pour les sélections futures.</div>
                </div>
            </div>
            <div style="padding:14px 20px;border-top:1px solid #f3f4f6;display:flex;justify-content:flex-end;gap:10px;">
                <a href="{{ route('admin.cms.content-types.edit', ['contentType' => $contentType, 'workspace' => $cmsWorkspace['key']]) }}" style="display:inline-flex;align-items:center;padding:8px 16px;border:1px solid #d1d5db;border-radius:6px;font-size:13px;font-weight:600;color:#374151;background:#fff;text-decoration:none;">Retour</a>
                <button type="submit" style="display:inline-flex;align-items:center;padding:8px 18px;background:#1e3a5f;color:#fff;border:none;border-radius:6px;font-size:13px;font-weight:600;cursor:pointer;">Enregistrer</button>
            </div>
        </form>
    </div>
</div>

<style>
    .bd-admin-editor-shell { display:grid; gap:20px; }
    .bd-admin-editor-hero { display:none; }
    .bd-admin-editor-card__header h3 { margin:0; color:#020617; font-size:1.2rem; font-weight:900; }
    .bd-admin-editor-card__header p { margin:8px 0 0; color:#64748b; line-height:1.7; }
    .bd-admin-check-card { display:flex; gap:14px; align-items:flex-start; padding:16px 18px; border-radius:20px; background:#f8fafc; border:1px solid #e2e8f0; cursor:pointer; }
    .bd-admin-check-card input { margin-top:4px; }
    .bd-admin-check-card strong { display:block; color:#020617; font-weight:800; }
    .bd-admin-check-card small { display:block; margin-top:4px; color:#64748b; }
    @media (max-width: 991.98px) { .bd-admin-editor-hero { flex-direction:column; align-items:flex-start; } }
</style>
@endsection
