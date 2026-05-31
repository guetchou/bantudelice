@extends('layouts.admin-modern')
@section('title', 'CMS - Mediatheque')
@section('page_title', 'Médiathèque')
@section('nav_active', 'cms')

@php
    $cmsWorkspace = $cmsWorkspace ?? [
        'key' => request('workspace', 'bantudelice'),
        'label' => request('workspace') === 'kende' ? 'Kende' : (request('workspace') === 'mema' ? 'Mema' : 'BantuDelice'),
        'eyebrow' => request('workspace') === 'kende' ? 'CMS Mobilite' : (request('workspace') === 'mema' ? 'CMS Colis' : 'CMS Food ops'),
        'description' => request('workspace') === 'kende'
            ? 'Assets transport, flotte et pages de mobilite.'
            : (request('workspace') === 'mema'
                ? 'Assets logistiques, relais et parcours colis.'
                : 'Assets food, menus et storefront.'),
    ];
    $usageGuide = $usageGuide ?? ['title' => null, 'description' => null, 'actions' => [], 'slots' => []];
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
                <h1 class="adm-page-bar__title">Mediatheque {{ $cmsWorkspace['label'] }}</h1>
            </div>
            <div class="adm-page-bar__right"></div>
        </div>
    </div>

    @if(session()->has('alert'))
        <div style="padding:12px 16px;border-radius:8px;font-size:13px;font-weight:500;margin:16px 0;background:#f0fdf4;color:#166534;border:1px solid #bbf7d0;">{{ session()->get('alert.message') }}</div>
    @endif

    @if(!empty($usageGuide['actions']))
        <div class="bd-cms-usage">
            <div class="bd-cms-usage__head">
                <div>
                    <p class="bd-cms-usage__eyebrow">Utilisation rapide</p>
                    <h2>{{ $usageGuide['title'] }}</h2>
                    <p>{{ $usageGuide['description'] }}</p>
                </div>
            </div>
            <div class="bd-cms-usage__actions">
                @foreach($usageGuide['actions'] as $action)
                    <a href="{{ $action['href'] }}" class="bd-cms-usage__action">
                        <strong>{{ $action['label'] }}</strong>
                        <span>{{ $action['meta'] }}</span>
                    </a>
                @endforeach
            </div>
            @if(!empty($usageGuide['slots']))
                <div class="bd-cms-usage__slots">
                    @foreach($usageGuide['slots'] as $slot)
                        <div class="bd-cms-usage__slot {{ $slot['is_ready'] ? 'is-ready' : 'is-missing' }}">
                            <span>{{ $slot['label'] }}</span>
                            <strong>{{ $slot['is_ready'] ? 'Pret' : 'A renseigner' }}</strong>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    @endif

    <div style="display:grid;grid-template-columns:1fr 2fr;gap:20px;margin-top:20px;align-items:start;">
        {{-- Upload form --}}
        <div style="background:#fff;border:1px solid #e5e7eb;border-radius:10px;overflow:hidden;">
            <div style="padding:16px 20px;border-bottom:1px solid #f3f4f6;">
                <h3 style="margin:0;font-size:1.1rem;font-weight:900;color:#111827;">Ajouter un media</h3>
                <p style="margin:6px 0 0;color:#78716c;font-size:13px;">Images et PDF reutilisables pour {{ $cmsWorkspace['label'] }}.</p>
            </div>
            <div style="padding:20px;">
                <form method="post" enctype="multipart/form-data" action="{{ route('admin.cms.media.store', ['workspace' => $cmsWorkspace['key']]) }}">
                    @csrf
                    <div style="margin-bottom:14px;">
                        <label style="display:block;font-size:13px;font-weight:600;color:#374151;margin-bottom:5px;">Fichier</label>
                        <input type="file" name="file" style="width:100%;padding:8px;border:1px solid #d1d5db;border-radius:6px;font-size:13px;box-sizing:border-box;" required>
                    </div>
                    <div style="margin-bottom:14px;">
                        <label style="display:block;font-size:13px;font-weight:600;color:#374151;margin-bottom:5px;">Titre</label>
                        <input type="text" name="title" style="width:100%;padding:9px 12px;border:1px solid #d1d5db;border-radius:6px;font-size:14px;box-sizing:border-box;">
                    </div>
                    <div style="margin-bottom:14px;">
                        <label style="display:block;font-size:13px;font-weight:600;color:#374151;margin-bottom:5px;">Texte alternatif</label>
                        <input type="text" name="alt_text" style="width:100%;padding:9px 12px;border:1px solid #d1d5db;border-radius:6px;font-size:14px;box-sizing:border-box;">
                    </div>
                    <button type="submit" style="display:inline-flex;align-items:center;gap:6px;padding:9px 20px;background:#1e3a5f;color:#fff;border:none;border-radius:6px;font-size:13px;font-weight:600;cursor:pointer;">Televerser</button>
                </form>
            </div>
        </div>

        {{-- Media library --}}
        <div style="background:#fff;border:1px solid #e5e7eb;border-radius:10px;overflow:hidden;">
            <div style="padding:16px 20px;border-bottom:1px solid #f3f4f6;">
                <h3 style="margin:0;font-size:1.1rem;font-weight:900;color:#111827;">Bibliotheque</h3>
                <p style="margin:6px 0 0;color:#78716c;font-size:13px;">Copiez le chemin d'un asset pour l'utiliser dans les champs image du CMS ou dans le backlog media du workspace.</p>
            </div>
            <div style="padding:20px;">
                <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:16px;">
                    @forelse($assets as $asset)
                        @php
                            $assetMime  = is_array($asset) ? ($asset['mime_type'] ?? '') : $asset->mime_type;
                            $assetUrl   = is_array($asset) ? ($asset['url'] ?? asset($asset['path'] ?? '')) : asset($asset->file_path);
                            $assetTitle = is_array($asset) ? ($asset['title'] ?? '') : $asset->title;
                            $assetName  = is_array($asset) ? ($asset['file_name'] ?? '') : $asset->file_name;
                            $assetPath  = is_array($asset) ? ($asset['path'] ?? '') : $asset->file_path;
                            $assetSource = is_array($asset) ? ($asset['source'] ?? 'CMS') : 'CMS';
                            $assetAlt   = is_array($asset) ? ($asset['title'] ?? '') : ($asset->alt_text ?: $asset->title);
                        @endphp
                        <div class="bd-cms-media-card">
                            @if(str_starts_with((string) $assetMime, 'image/'))
                                <img src="{{ $assetUrl }}" alt="{{ $assetAlt }}">
                            @else
                                <div class="bd-cms-media-card__file"><i class="fas fa-file-pdf"></i></div>
                            @endif
                            <strong>{{ $assetTitle }}</strong>
                            <span>{{ $assetName }}</span>
                            <em class="bd-cms-media-card__source">{{ $assetSource }}</em>
                            <div class="bd-cms-media-card__path">
                                <code>{{ $assetPath }}</code>
                                <button type="button" class="bd-cms-media-card__copy" data-copy-value="{{ $assetPath }}">Copier le chemin</button>
                            </div>
                            <a href="{{ $assetUrl }}" target="_blank" rel="noopener" class="bd-cms-media-card__open">Ouvrir l'asset</a>
                        </div>
                    @empty
                        <div style="color:#9ca3af;font-size:13px;grid-column:1/-1;">Aucun media pour le moment.</div>
                    @endforelse
                </div>
                <div style="margin-top:16px;">
                    {{ $assets->appends(['workspace' => $cmsWorkspace['key']])->links() }}
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .bd-cms-shell { display:grid; gap:20px; }
    .bd-cms-hero { display:none; }
    .bd-cms-usage { margin-bottom:22px; padding:22px 24px; border-radius:28px; background:linear-gradient(135deg,#eff6ff 0%,#f8fafc 100%); border:1px solid #dbeafe; box-shadow:0 20px 44px rgba(15,23,42,.06); }
    .bd-cms-usage__head h2 { margin:0; color:#0f172a; font-size:1.3rem; font-weight:900; }
    .bd-cms-usage__head p { margin:10px 0 0; color:#475569; line-height:1.75; max-width:780px; }
    .bd-cms-usage__eyebrow { margin:0 0 8px; color:#1d4ed8; font-size:.72rem; font-weight:800; letter-spacing:.12em; text-transform:uppercase; }
    .bd-cms-usage__actions { display:grid; grid-template-columns:repeat(auto-fit,minmax(220px,1fr)); gap:12px; margin-top:16px; }
    .bd-cms-usage__action { display:grid; gap:6px; padding:16px 18px; border-radius:20px; background:#fff; border:1px solid #dbeafe; text-decoration:none; box-shadow:0 12px 28px rgba(15,23,42,.05); }
    .bd-cms-usage__action strong { color:#0f172a; font-size:.92rem; font-weight:900; }
    .bd-cms-usage__action span { color:#64748b; font-size:.78rem; font-weight:700; text-transform:uppercase; letter-spacing:.08em; }
    .bd-cms-usage__slots { display:grid; grid-template-columns:repeat(auto-fit,minmax(160px,1fr)); gap:12px; margin-top:14px; }
    .bd-cms-usage__slot { display:grid; gap:4px; padding:14px 16px; border-radius:18px; background:#fff; border:1px solid #dbeafe; }
    .bd-cms-usage__slot span { color:#334155; font-size:.83rem; font-weight:800; }
    .bd-cms-usage__slot strong { font-size:.76rem; letter-spacing:.08em; text-transform:uppercase; }
    .bd-cms-usage__slot.is-ready strong { color:#0f766e; }
    .bd-cms-usage__slot.is-missing strong { color:#b91c1c; }
    .bd-cms-media-card { display:grid; gap:10px; padding:16px; border:1px solid rgba(249,115,22,.12); border-radius:20px; background:#fffaf2; }
    .bd-cms-media-card img { width:100%; height:180px; object-fit:cover; border-radius:14px; }
    .bd-cms-media-card__file { height:180px; display:flex; align-items:center; justify-content:center; border-radius:14px; background:#fff7ed; color:#c2410c; font-size:2rem; }
    .bd-cms-media-card strong { color:#111827; }
    .bd-cms-media-card span { color:#78716c; font-size:.88rem; }
    .bd-cms-media-card__source { font-style:normal; color:#059669; font-size:.78rem; font-weight:700; }
    .bd-cms-media-card__path { display:grid; gap:8px; }
    .bd-cms-media-card code { white-space:normal; font-size:.75rem; }
    .bd-cms-media-card__copy, .bd-cms-media-card__open {
        display:inline-flex; align-items:center; justify-content:center; min-height:38px; padding:0 12px;
        border-radius:12px; border:1px solid rgba(249,115,22,.18); background:#fff; color:#9a3412;
        font-size:.78rem; font-weight:800; text-decoration:none; cursor:pointer;
    }
    .bd-cms-media-card__copy.is-done { border-color:#16a34a; color:#166534; background:#f0fdf4; }
    @media (max-width:991.98px) {
        .bd-cms-hero { padding:22px; border-radius:24px; }
        .bd-cms-usage { padding:18px; border-radius:22px; }
    }
</style>
<script>
document.addEventListener('click', async function (event) {
    const trigger = event.target.closest('[data-copy-value]');
    if (!trigger) return;
    const copyValue = trigger.getAttribute('data-copy-value') || '';
    if (!copyValue) return;
    const originalLabel = trigger.textContent;
    try {
        if (navigator.clipboard && navigator.clipboard.writeText) {
            await navigator.clipboard.writeText(copyValue);
        } else {
            const input = document.createElement('input');
            input.value = copyValue;
            document.body.appendChild(input);
            input.select();
            document.execCommand('copy');
            document.body.removeChild(input);
        }
        trigger.textContent = 'Chemin copie';
        trigger.classList.add('is-done');
    } catch (error) {
        trigger.textContent = 'Copie impossible';
    }
    window.setTimeout(function () {
        trigger.textContent = originalLabel;
        trigger.classList.remove('is-done');
    }, 1800);
});
</script>
@endsection
