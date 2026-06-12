@extends('layouts.restaurant_app')
@section('title', 'Produits | ' . \App\Services\ConfigService::getCompanyName())
@section('topbar_title', 'Produits')
@section('product_nav', 'active')

@section('style')
<style>
/* ── Page produits ─────────────────────────────────────────── */
.prd { display: flex; flex-direction: column; gap: 20px; }

/* Barre outils */
.prd-toolbar {
    display: flex; align-items: center; justify-content: space-between;
    flex-wrap: wrap; gap: 12px;
}
.prd-toolbar__left  { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
.prd-toolbar__right { display: flex; align-items: center; gap: 8px; }

/* Filtre catégorie pills */
.prd-pill-nav { display: flex; gap: 4px; flex-wrap: wrap; }
.prd-pill {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 5px 14px; border-radius: 999px;
    border: 1px solid var(--bd-border);
    background: var(--bd-surface); color: var(--bd-text-2);
    font-size: 12px; font-weight: 600;
    text-decoration: none; transition: .12s;
}
.prd-pill:hover { border-color: var(--bd-green); color: var(--bd-green); }
.prd-pill.is-active { background: var(--bd-green); color: #fff; border-color: var(--bd-green); }

/* Boutons */
.prd-btn {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 7px 14px; border-radius: var(--bd-radius);
    font-size: 12px; font-weight: 600; cursor: pointer;
    font-family: var(--bd-font); border: none; transition: .12s;
    text-decoration: none;
}
.prd-btn--primary { background: var(--bd-green); color: #fff; }
.prd-btn--primary:hover { background: var(--bd-green-dark, #007836); color: #fff; }
.prd-btn--outline { background: var(--bd-surface); color: var(--bd-text-2); border: 1px solid var(--bd-border); }
.prd-btn--outline:hover { border-color: var(--bd-green); color: var(--bd-green); }
.prd-btn--danger  { background: rgba(239,68,68,.08); color: #dc2626; border: 1px solid rgba(239,68,68,.2); }
.prd-btn--danger:hover { background: rgba(239,68,68,.15); }

/* Champ recherche */
.prd-search {
    display: inline-flex; align-items: center; gap: 8px;
    padding: 7px 12px;
    border: 1px solid var(--bd-border); border-radius: var(--bd-radius);
    background: var(--bd-surface); font-size: 12px;
    transition: border-color .12s;
}
.prd-search:focus-within { border-color: var(--bd-green); }
.prd-search i { color: var(--bd-text-3); font-size: 12px; }
.prd-search input {
    border: none; background: transparent; outline: none;
    font-family: var(--bd-font); font-size: 12px; color: var(--bd-text);
    width: 180px;
}

/* Carte principale */
.prd-card {
    background: var(--bd-surface);
    border: 1px solid var(--bd-border);
    border-radius: var(--bd-radius);
    overflow: hidden;
}
.prd-card__head {
    display: flex; align-items: center; justify-content: space-between;
    gap: 12px; padding: 14px 20px;
    border-bottom: 1px solid var(--bd-border-2);
    flex-wrap: wrap;
}
.prd-card__title { font-size: 13px; font-weight: 600; color: var(--bd-text); }
.prd-card__count { font-size: 11px; color: var(--bd-text-3); margin-top: 2px; }

/* Tableau */
.prd-table-wrap { overflow-x: auto; }
.prd-table {
    width: 100%; border-collapse: collapse;
    font-size: 13px; font-family: var(--bd-font-body, 'Inter', sans-serif);
}
.prd-table thead th {
    padding: 10px 16px;
    font-size: 11px; font-weight: 700; letter-spacing: .06em;
    text-transform: uppercase; color: var(--bd-text-3);
    border-bottom: 1px solid var(--bd-border-2);
    background: var(--bd-surface-2); white-space: nowrap;
    text-align: left;
}
.prd-table tbody tr {
    border-bottom: 1px solid var(--bd-border-2);
    transition: background .1s;
}
.prd-table tbody tr:last-child { border-bottom: none; }
.prd-table tbody tr:hover { background: var(--bd-surface-2); }
.prd-table td { padding: 12px 16px; color: var(--bd-text-2); vertical-align: middle; }

/* Cellule image */
.prd-img {
    width: 48px; height: 48px; border-radius: 8px;
    object-fit: cover; background: var(--bd-surface-2);
    flex-shrink: 0;
}

/* Cellule nom + catégorie */
.prd-name { font-size: 13px; font-weight: 600; color: var(--bd-text); display: block; line-height: 1.3; }
.prd-cat  { font-size: 11px; color: var(--bd-text-3); margin-top: 2px; }

/* Cellule prix */
.prd-price {
    font-family: var(--bd-font-display, 'League Spartan', sans-serif);
    font-size: 14px; font-weight: 800; color: var(--bd-text); white-space: nowrap;
}
.prd-price-cur { font-size: 10px; font-weight: 600; color: var(--bd-text-3); font-family: var(--bd-font); }
.prd-price--discount { font-size: 11px; font-weight: 500; color: var(--bd-text-3); text-decoration: line-through; display: block; }

/* Badge vedette */
.prd-badge {
    display: inline-flex; align-items: center; gap: 4px;
    padding: 3px 9px; border-radius: 999px;
    font-size: 11px; font-weight: 700; white-space: nowrap;
    cursor: pointer; border: none; font-family: var(--bd-font);
    transition: .12s;
}
.prd-badge--on  { background: rgba(0,149,67,.1);    color: var(--bd-green); }
.prd-badge--on:hover  { background: rgba(0,149,67,.2); }
.prd-badge--off { background: rgba(239,68,68,.1);   color: #dc2626; }
.prd-badge--off:hover { background: rgba(239,68,68,.18); }
[data-theme="dark"] .prd-badge--on  { background: rgba(0,201,87,.15); color: #00c957; }
[data-theme="dark"] .prd-badge--off { background: rgba(248,113,113,.15); color: #f87171; }

/* Actions par ligne */
.prd-actions { display: flex; align-items: center; gap: 6px; }
.prd-action-btn {
    display: inline-flex; align-items: center; justify-content: center;
    width: 30px; height: 30px; border-radius: 7px;
    border: 1px solid var(--bd-border);
    background: var(--bd-surface); color: var(--bd-text-2);
    cursor: pointer; font-size: 12px; transition: .12s;
    text-decoration: none;
}
.prd-action-btn:hover { border-color: var(--bd-green); color: var(--bd-green); }
.prd-action-btn--delete { color: #dc2626; border-color: rgba(239,68,68,.2); }
.prd-action-btn--delete:hover { background: rgba(239,68,68,.06); border-color: #dc2626; color: #dc2626; }

/* État vide */
.prd-empty {
    padding: 56px 20px; text-align: center;
    color: var(--bd-text-3); font-size: 13px;
}
.prd-empty i { font-size: 32px; display: block; margin-bottom: 12px; color: var(--bd-border); }
.prd-empty p { margin: 0 0 16px; }

@media (max-width: 768px) {
    .prd-col-hide { display: none; }
    .prd-toolbar__left { width: 100%; }
}
</style>
@endsection

@section('content')
@php
    $productCount = $products->count();
    $activeCat    = request('category');
@endphp

<div class="prd">

    {{-- ── Alerte session ─────────────────────────────────── --}}
    @if(session()->has('alert'))
        <div class="alert alert-{{ session()->get('alert.type') }} alert-dismissible" role="alert">
            {{ session()->get('alert.message') }}
            <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
        </div>
    @endif

    {{-- ── Filtre catégorie + action globale ──────────────── --}}
    <div class="prd-toolbar">
        <div class="prd-toolbar__left">
            {{-- Filtre catégorie --}}
            @if($categories->count())
            <nav class="prd-pill-nav">
                <a href="{{ route('product.index') }}"
                   class="prd-pill {{ !$activeCat ? 'is-active' : '' }}">
                    Tous <span style="font-size:10px;font-weight:400;">({{ $productCount }})</span>
                </a>
                @foreach($categories as $cat)
                <a href="{{ route('product.index', ['category' => $cat->id]) }}"
                   class="prd-pill {{ $activeCat == $cat->id ? 'is-active' : '' }}">
                    {{ $cat->name }}
                </a>
                @endforeach
            </nav>
            @endif
        </div>
        <div class="prd-toolbar__right">
            <a href="{{ route('product.create') }}" class="prd-btn prd-btn--primary">
                <i class="fas fa-plus"></i> Ajouter un produit
            </a>
        </div>
    </div>

    {{-- ── Tableau produits ───────────────────────────────── --}}
    <div class="prd-card">
        <div class="prd-card__head">
            <div>
                <div class="prd-card__title">Catalogue produits</div>
                <div class="prd-card__count">{{ $productCount }} produit(s){{ $activeCat ? ' — catégorie filtrée' : '' }}</div>
            </div>
            <label class="prd-search">
                <i class="fas fa-search"></i>
                <input type="text" id="prdSearch" placeholder="Rechercher un produit…" autocomplete="off">
            </label>
        </div>

        <div class="prd-table-wrap">
            <table class="prd-table" id="prdTable">
                <thead>
                    <tr>
                        <th style="width:60px;">Image</th>
                        <th>Produit</th>
                        <th class="prd-col-hide">Prix</th>
                        <th class="prd-col-hide">Statut</th>
                        <th style="width:90px;">Actions</th>
                    </tr>
                </thead>
                <tbody id="prdTbody">
                @forelse($products as $product)
                @php
                    $img = $product->image ?? null;
                    $imgSrc = $img
                        ? (str_starts_with($img, 'http') ? $img : asset('images/product_images/' . $img))
                        : asset('images/product_images/default-food.jpg');
                    $hasDiscount = (float)($product->discount_price ?? 0) > 0
                        && (float)$product->discount_price < (float)$product->price;
                @endphp
                <tr data-name="{{ strtolower($product->name) }}" data-cat="{{ $product->category_id }}">
                    <td>
                        <img class="prd-img" src="{{ $imgSrc }}" alt="{{ $product->name }}"
                             onerror="this.src='{{ asset('images/product_images/default-food.jpg') }}'">
                    </td>
                    <td>
                        <span class="prd-name">{{ $product->name }}</span>
                        <span class="prd-cat">{{ $product->categories->name ?? '—' }}</span>
                        @if($product->description)
                        <span class="prd-cat" style="max-width:260px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;display:block;">
                            {{ \Illuminate\Support\Str::limit($product->description, 60) }}
                        </span>
                        @endif
                    </td>
                    <td class="prd-col-hide">
                        @if($hasDiscount)
                            <span class="prd-price--discount">
                                {{ number_format((float)$product->price, 0, ',', ' ') }} <span class="prd-price-cur">FCFA</span>
                            </span>
                            <span class="prd-price">
                                {{ number_format((float)$product->discount_price, 0, ',', ' ') }}
                                <span class="prd-price-cur">FCFA</span>
                            </span>
                        @else
                            <span class="prd-price">
                                {{ number_format((float)$product->price, 0, ',', ' ') }}
                                <span class="prd-price-cur">FCFA</span>
                            </span>
                        @endif
                    </td>
                    <td class="prd-col-hide">
                        <form method="POST" action="{{ route('restaurant.change_product_featured_status', $product->id) }}" style="display:inline;">
                            @csrf
                            <button type="submit" class="prd-badge {{ $product->featured ? 'prd-badge--on' : 'prd-badge--off' }}"
                                    title="{{ $product->featured ? 'Retirer de la vedette' : 'Mettre en vedette' }}">
                                <i class="fas {{ $product->featured ? 'fa-star' : 'fa-star' }}"
                                   style="font-size:9px;"></i>
                                {{ $product->featured ? 'En vedette' : 'Non mis en avant' }}
                            </button>
                        </form>
                    </td>
                    <td>
                        <div class="prd-actions">
                            <a href="{{ route('product.edit', $product->id) }}"
                               class="prd-action-btn" title="Modifier">
                                <i class="fas fa-pen"></i>
                            </a>
                            <form action="{{ route('product.destroy', $product->id) }}"
                                  method="post" style="display:inline;"
                                  onsubmit="return confirm('Supprimer « {{ addslashes($product->name) }} » ?');">
                                @csrf
                                @method('delete')
                                <button type="submit" class="prd-action-btn prd-action-btn--delete" title="Supprimer">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5">
                        <div class="prd-empty">
                            <i class="fas fa-utensils"></i>
                            <p>Aucun produit dans votre catalogue.</p>
                            <a href="{{ route('product.create') }}" class="prd-btn prd-btn--primary">
                                <i class="fas fa-plus"></i> Ajouter votre premier produit
                            </a>
                        </div>
                    </td>
                </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection

@section('script')
<script>
(function () {
    // Recherche client
    var searchInput = document.getElementById('prdSearch');
    var tbody = document.getElementById('prdTbody');
    if (!searchInput || !tbody) return;

    searchInput.addEventListener('input', function () {
        var q = this.value.toLowerCase().trim();
        var rows = tbody.querySelectorAll('tr[data-name]');
        rows.forEach(function (row) {
            var name = row.dataset.name || '';
            row.style.display = (!q || name.includes(q)) ? '' : 'none';
        });
    });

    // Filtre catégorie côté client (en complément du filtre URL)
    @if($activeCat)
    (function () {
        var catId = '{{ $activeCat }}';
        var rows = tbody.querySelectorAll('tr[data-cat]');
        rows.forEach(function (row) {
            if (row.dataset.cat !== catId) row.style.display = 'none';
        });
    })();
    @endif
})();
</script>
@endsection
