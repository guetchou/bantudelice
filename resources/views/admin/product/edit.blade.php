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
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0 text-dark">Modifier un plat</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ url('/admin') }}">Accueil</a></li>
                        <li class="breadcrumb-item"><a href="{{ url('/admin/all-products') }}">Plats</a></li>
                        <li class="breadcrumb-item active">{{ $product->name }}</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <section class="content">
        <div class="container-fluid">
            @if(!empty($backlogContext['media_status']))
                <div class="card mb-3" style="border-radius:24px; border:1px solid rgba(20,83,45,.12); box-shadow:0 14px 36px rgba(21,128,61,.08);">
                    <div class="card-body d-flex justify-content-between align-items-center flex-wrap" style="gap:14px;">
                        <div>
                            <div style="font-size:.72rem; font-weight:800; letter-spacing:.14em; text-transform:uppercase; color:#15803d;">Flux de traitement</div>
                            <div style="margin-top:8px; font-size:1.05rem; font-weight:800; color:#111827;">
                                {{ ($backlogContext['media_status'] ?? '') === 'missing' ? 'Backlog produits sans media' : 'Retour au filtre courant' }}
                            </div>
                            <div style="margin-top:6px; color:#6b7280;">
                                Utilisez les actions ci-contre pour enchainer les fiches sans revenir a la liste.
                            </div>
                        </div>
                        <div class="d-flex flex-wrap" style="gap:10px;">
                            <a href="{{ $backlogIndexUrl }}" class="btn btn-outline-dark btn-sm">Retour au backlog</a>
                            @if($previousBacklogUrl)
                                <a href="{{ $previousBacklogUrl }}" class="btn btn-outline-secondary btn-sm">Produit precedent</a>
                            @endif
                            @if($nextBacklogUrl)
                                <a href="{{ $nextBacklogUrl }}" class="btn btn-success btn-sm">Produit suivant</a>
                            @endif
                        </div>
                    </div>
                </div>
            @endif
            <form method="post" action="{{ url('/admin/product/' . $product->id) }}" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                @include('admin.product._form')
            </form>
        </div>
    </section>
@endsection
