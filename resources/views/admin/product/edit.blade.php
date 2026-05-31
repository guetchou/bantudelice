@extends('layouts.admin-modern')
@section('title', 'Modifier un plat')
@section('page_title', 'Modifier produit')
@section('nav_active', 'products')

@section('content')
@php
    $editContextQuery = array_filter([
        'media_status' => $backlogContext['media_status'] ?? null,
        'restaurant_id' => $backlogContext['restaurant_id'] ?? null,
    ]);
    $backlogIndexUrl = route('total.pro', $editContextQuery);
    $previousBacklogUrl = $previousBacklogProductId ? route('admin.product.edit', $previousBacklogProductId) . (!empty($editContextQuery) ? '?' . http_build_query($editContextQuery) : '') : null;
    $nextBacklogUrl = $nextBacklogProductId ? route('admin.product.edit', $nextBacklogProductId) . (!empty($editContextQuery) ? '?' . http_build_query($editContextQuery) : '') : null;
@endphp

<div style="padding:24px;">

    @if(!empty($backlogContext['media_status']))
    <div style="background:#fff;border:1px solid #d1fae5;border-radius:12px;padding:16px 20px;margin-bottom:20px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:14px;">
        <div>
            <div style="font-size:11px;font-weight:800;letter-spacing:.14em;text-transform:uppercase;color:#15803d;">Flux de traitement</div>
            <div style="margin-top:6px;font-size:15px;font-weight:800;color:#111827;">
                {{ ($backlogContext['media_status'] ?? '') === 'missing' ? 'Backlog produits sans media' : 'Retour au filtre courant' }}
            </div>
            <div style="margin-top:4px;font-size:13px;color:#6b7280;">
                Utilisez les actions ci-contre pour enchaîner les fiches sans revenir à la liste.
            </div>
        </div>
        <div style="display:flex;flex-wrap:wrap;gap:8px;">
            <a href="{{ $backlogIndexUrl }}" style="display:inline-flex;align-items:center;padding:6px 14px;border:1px solid #374151;border-radius:6px;font-size:12px;font-weight:600;color:#374151;text-decoration:none;">Retour au backlog</a>
            @if($previousBacklogUrl)
                <a href="{{ $previousBacklogUrl }}" style="display:inline-flex;align-items:center;padding:6px 14px;border:1px solid #9ca3af;border-radius:6px;font-size:12px;font-weight:600;color:#374151;text-decoration:none;">&#8592; Précédent</a>
            @endif
            @if($nextBacklogUrl)
                <a href="{{ $nextBacklogUrl }}" style="display:inline-flex;align-items:center;gap:4px;padding:6px 14px;background:#16a34a;border:none;border-radius:6px;font-size:12px;font-weight:600;color:#fff;text-decoration:none;">Suivant &#8594;</a>
            @endif
        </div>
    </div>
    @endif

    <form method="post" action="{{ url('/admin/product/' . $product->id) }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')
        @include('admin.product._form')
    </form>

</div>
@endsection
