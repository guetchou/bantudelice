@extends('layouts.app')
@section('title','Menu | Restaurant')
@section('menu_nav', 'active')

@section('style')
<style>
    .menu-wrap { display:flex; gap: 16px; }
    .menu-left { flex: 1; min-width: 320px; }
    .menu-right { width: 360px; }
    .category-card { border:1px solid #e5e7eb; border-radius:14px; overflow:hidden; background:#fff; box-shadow:0 2px 10px rgba(0,0,0,0.05); }
    .category-header { display:flex; align-items:center; justify-content:space-between; gap:12px; padding:14px 16px; background:#f8fafc; border-bottom:1px solid #eef2f7; }
    .category-title { margin:0; font-weight:700; font-size: 1rem; }
    .badge-pill { border-radius:999px; padding:4px 10px; font-size: 12px; }
    .products-list { padding: 12px 12px 2px; display:flex; flex-direction:column; gap:10px; }
    .product-row { display:flex; gap:10px; align-items:center; border:1px solid #eef2f7; border-radius:12px; padding:10px; background:#fff; }
    .product-img { width:44px; height:44px; border-radius:10px; object-fit:cover; background:#f3f4f6; }
    .drag-handle { cursor: grab; color:#6b7280; }
    .drag-handle:active { cursor: grabbing; }
    .pill-btn { border-radius:999px; }
    .muted { color:#6b7280; font-size: 12px; }
    .category-card[aria-disabled="true"] { opacity: .75; }
</style>
@endsection

@section('content')
<div class="content-header">
    @if(session()->has('alert'))
        <div class="alert alert-{{ session()->get('alert.type') }}">
            {{ session()->get('alert.message') }}
        </div>
    @endif
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0 text-dark">Menu (sections & disponibilité)</h1>
                <div class="text-muted">Réordonner par drag&drop • activer/désactiver catégories et produits • impact immédiat côté page client</div>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="{{ route('restaurant.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active">Menu</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<section class="content">
    <div class="container-fluid">
        <div class="menu-wrap">
            <div class="menu-left">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Catégories</h3>
                    </div>
                    <div class="card-body">
                        <div id="categoriesSortable" style="display:flex; flex-direction:column; gap: 14px;">
                            @foreach($categories as $category)
                                <div class="category-card" data-id="{{ $category->id }}" aria-disabled="{{ $category->is_available ? 'false' : 'true' }}">
                                    <div class="category-header">
                                        <div style="display:flex; align-items:center; gap:10px;">
                                            <i class="fas fa-grip-vertical drag-handle"></i>
                                            <div>
                                                <p class="category-title">{{ $category->name }}</p>
                                                <div class="muted">{{ $category->products->count() }} produit(s)</div>
                                            </div>
                                        </div>
                                        <div style="display:flex; align-items:center; gap:8px;">
                                            <button class="btn btn-sm pill-btn btnToggleCategory {{ $category->is_available ? 'btn-success' : 'btn-outline-secondary' }}" data-id="{{ $category->id }}">
                                                <i class="fas {{ $category->is_available ? 'fa-check' : 'fa-ban' }}"></i>
                                                {{ $category->is_available ? 'Disponible' : 'Indispo' }}
                                            </button>
                                            <a href="{{ route('category.edit', $category->id) }}" class="btn btn-sm btn-outline-info pill-btn" title="Éditer catégorie">
                                                <i class="fas fa-pen"></i>
                                            </a>
                                        </div>
                                    </div>

                                    <div class="products-list" id="productsSortable-{{ $category->id }}" data-category-id="{{ $category->id }}">
                                        @foreach($category->products as $product)
                                            @php
                                                $img = $product->image ?? null;
                                                $imgSrc = $img
                                                    ? (strpos($img, 'http') === 0 ? $img : asset('images/product_images/' . $img))
                                                    : asset('images/product_images/default-food.jpg');
                                            @endphp
                                            <div class="product-row" data-id="{{ $product->id }}">
                                                <i class="fas fa-grip-vertical drag-handle"></i>
                                                <img class="product-img" src="{{ $imgSrc }}" onerror="this.src='{{ asset('images/product_images/default-food.jpg') }}'">
                                                <div style="flex:1;">
                                                    <div style="font-weight:700; line-height:1.2;">{{ $product->name }}</div>
                                                    <div class="muted">{{ number_format($product->discount_price > 0 ? $product->discount_price : $product->price, 0, ',', ' ') }} FCFA</div>
                                                </div>
                                                <button class="btn btn-sm pill-btn btnToggleProduct {{ $product->is_available ? 'btn-success' : 'btn-outline-secondary' }}" data-id="{{ $product->id }}">
                                                    <i class="fas {{ $product->is_available ? 'fa-check' : 'fa-ban' }}"></i>
                                                </button>
                                                <a href="{{ route('product.edit', $product->id) }}" class="btn btn-sm btn-outline-info pill-btn" title="Éditer produit">
                                                    <i class="fas fa-pen"></i>
                                                </a>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            <div class="menu-right">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Actions rapides</h3>
                    </div>
                    <div class="card-body">
                        <a href="{{ route('category.create') }}" class="btn btn-primary btn-block">
                            <i class="fas fa-plus"></i> Nouvelle catégorie
                        </a>
                        <a href="{{ route('product.create') }}" class="btn btn-outline-primary btn-block">
                            <i class="fas fa-plus"></i> Nouveau produit
                        </a>
                        <a href="{{ route('restaurant.media.index') }}" class="btn btn-outline-secondary btn-block">
                            <i class="fas fa-images"></i> Galerie médias
                        </a>
                        <div class="mt-3 muted">
                            Conseil: désactive un produit pour le masquer/désactiver côté client (panier bloqué).
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection

@section('script')
<script src="https://cdnjs.cloudflare.com/ajax/libs/Sortable/1.15.2/Sortable.min.js"></script>
<script>
    const csrf = '{{ csrf_token() }}';
    const reorderCategoriesUrl = '{{ route('restaurant.menu.categories.reorder') }}';
    const toggleCategoryUrlBase = '{{ url('/') }}/restaurant/menu/categories/';
    const toggleProductUrlBase = '{{ url('/') }}/restaurant/menu/products/';
    const reorderProductsUrlBase = '{{ url('/') }}/restaurant/menu/categories/';

    async function postJson(url, method, payload) {
        const res = await fetch(url, {
            method,
            headers: { 'X-CSRF-TOKEN': csrf, 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: payload ? JSON.stringify(payload) : null
        });
        const json = await res.json();
        if (!res.ok) throw new Error(json.message || 'Erreur');
        return json;
    }

    // Sort categories
    new Sortable(document.getElementById('categoriesSortable'), {
        handle: '.drag-handle',
        animation: 150,
        onEnd: async function () {
            const ids = Array.from(document.querySelectorAll('#categoriesSortable .category-card')).map(el => parseInt(el.dataset.id, 10));
            try {
                await postJson(reorderCategoriesUrl, 'PATCH', { ids });
            } catch (e) {
                alert(e.message);
            }
        }
    });

    // Sort products per category
    document.querySelectorAll('[id^="productsSortable-"]').forEach(list => {
        const categoryId = list.dataset.categoryId;
        new Sortable(list, {
            handle: '.drag-handle',
            animation: 150,
            onEnd: async function () {
                const ids = Array.from(list.querySelectorAll('.product-row')).map(el => parseInt(el.dataset.id, 10));
                const url = reorderProductsUrlBase + categoryId + '/products/reorder';
                try {
                    await postJson(url, 'PATCH', { ids });
                } catch (e) {
                    alert(e.message);
                }
            }
        });
    });

    // Toggle category
    document.querySelectorAll('.btnToggleCategory').forEach(btn => {
        btn.addEventListener('click', async () => {
            const id = btn.dataset.id;
            try {
                const json = await postJson(toggleCategoryUrlBase + id + '/availability', 'PATCH', {});
                window.location.reload();
            } catch (e) {
                alert(e.message);
            }
        });
    });

    // Toggle product
    document.querySelectorAll('.btnToggleProduct').forEach(btn => {
        btn.addEventListener('click', async () => {
            const id = btn.dataset.id;
            try {
                const json = await postJson(toggleProductUrlBase + id + '/availability', 'PATCH', {});
                window.location.reload();
            } catch (e) {
                alert(e.message);
            }
        });
    });
</script>
@endsection


