@extends('layouts.restaurant_app')
@section('title', 'Menu | ' . \App\Services\ConfigService::getCompanyName())
@section('topbar_title', 'Menu')
@section('menu_nav', 'active')

@section('style')
<style>
/* ── Page menu ────────────────────────────────────────────── */
.mnu { display: flex; flex-direction: column; gap: 20px; }

/* En-tête page */
.mnu-head {
    display: flex; align-items: center; justify-content: space-between;
    flex-wrap: wrap; gap: 12px;
}
.mnu-head__title { font-size: 16px; font-weight: 700; color: var(--bd-text); }
.mnu-head__sub   { font-size: 12px; color: var(--bd-text-3); margin-top: 2px; }
.mnu-head__actions { display: flex; gap: 8px; }

/* Bouton */
.mnu-btn {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 8px 16px; border-radius: var(--bd-radius);
    font-size: 12px; font-weight: 600; cursor: pointer;
    font-family: var(--bd-font); border: none; transition: .12s;
    text-decoration: none; white-space: nowrap;
}
.mnu-btn--primary { background: var(--bd-green); color: #fff; }
.mnu-btn--primary:hover { background: var(--bd-green-dark, #007836); color: #fff; }
.mnu-btn--outline { background: var(--bd-surface); color: var(--bd-text-2); border: 1px solid var(--bd-border); }
.mnu-btn--outline:hover { border-color: var(--bd-green); color: var(--bd-green); }

/* Carte catégorie */
.mnu-cat {
    background: var(--bd-surface);
    border: 1px solid var(--bd-border);
    border-radius: var(--bd-radius-lg, 12px);
    overflow: hidden;
    transition: background .2s, border-color .12s, opacity .2s;
}
.mnu-cat.is-unavailable { opacity: .65; }
.mnu-cat.is-unavailable .mnu-cat__head { background: var(--bd-surface-2); }

.mnu-cat__head {
    display: flex; align-items: center; gap: 12px;
    padding: 13px 16px;
    border-bottom: 1px solid var(--bd-border-2);
    background: var(--bd-surface-2);
}
.mnu-cat__grip { color: var(--bd-text-3); cursor: grab; font-size: 12px; flex-shrink: 0; }
.mnu-cat__grip:active { cursor: grabbing; }
.mnu-cat__name {
    flex: 1; font-size: 14px; font-weight: 700; color: var(--bd-text);
    display: flex; align-items: center; gap: 8px;
}
.mnu-cat__count {
    display: inline-flex; align-items: center;
    padding: 2px 8px; border-radius: 999px;
    background: var(--bd-surface); border: 1px solid var(--bd-border-2);
    font-size: 10px; font-weight: 700; color: var(--bd-text-3);
}
.mnu-cat__controls { display: flex; align-items: center; gap: 6px; }

/* Toggle disponibilité catégorie */
.mnu-toggle {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 5px 12px; border-radius: 999px;
    font-size: 11px; font-weight: 700; cursor: pointer;
    border: none; font-family: var(--bd-font); transition: .12s;
}
.mnu-toggle--on  { background: rgba(0,149,67,.12); color: var(--bd-green); }
.mnu-toggle--off { background: var(--bd-surface-2); color: var(--bd-text-3); border: 1px solid var(--bd-border); }
.mnu-toggle--on:hover  { background: rgba(0,149,67,.2); }
.mnu-toggle--off:hover { border-color: var(--bd-text-2); color: var(--bd-text-2); }
[data-theme="dark"] .mnu-toggle--on { background: rgba(0,201,87,.15); color: #00c957; }

/* Bouton icon */
.mnu-icon-btn {
    display: inline-flex; align-items: center; justify-content: center;
    width: 30px; height: 30px; border-radius: 7px;
    border: 1px solid var(--bd-border);
    background: var(--bd-surface); color: var(--bd-text-2);
    text-decoration: none; font-size: 11px; cursor: pointer;
    transition: .12s;
}
.mnu-icon-btn:hover { border-color: var(--bd-green); color: var(--bd-green); }

/* Liste produits */
.mnu-products { display: flex; flex-direction: column; }

/* Ligne produit */
.mnu-product {
    display: flex; align-items: center; gap: 12px;
    padding: 11px 16px;
    border-bottom: 1px solid var(--bd-border-2);
    transition: background .1s;
}
.mnu-product:last-child { border-bottom: none; }
.mnu-product:hover { background: var(--bd-surface-2); }
.mnu-product.is-unavailable { opacity: .6; }

.mnu-product__grip { color: var(--bd-text-3); cursor: grab; font-size: 11px; flex-shrink: 0; }
.mnu-product__grip:active { cursor: grabbing; }

.mnu-product__img {
    width: 42px; height: 42px; border-radius: 8px;
    object-fit: cover; flex-shrink: 0;
    border: 1px solid var(--bd-border);
    background: var(--bd-surface-2);
}
.mnu-product__info { flex: 1; min-width: 0; }
.mnu-product__name {
    font-size: 13px; font-weight: 600; color: var(--bd-text);
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}
.mnu-product__price {
    font-family: var(--bd-font-display, 'League Spartan', sans-serif);
    font-size: 13px; font-weight: 700; color: var(--bd-text-2);
    margin-top: 2px;
}
.mnu-product__price .orig {
    font-size: 11px; color: var(--bd-text-3);
    text-decoration: line-through; margin-left: 4px;
    font-family: var(--bd-font);
}

/* Toggle produit (icône uniquement) */
.mnu-avail-btn {
    width: 30px; height: 30px; border-radius: 999px;
    display: inline-flex; align-items: center; justify-content: center;
    cursor: pointer; border: none; flex-shrink: 0;
    font-size: 11px; transition: .12s;
}
.mnu-avail-btn--on  { background: rgba(0,149,67,.12); color: var(--bd-green); }
.mnu-avail-btn--off { background: var(--bd-surface-2); color: var(--bd-text-3); border: 1px solid var(--bd-border); }
.mnu-avail-btn--on:hover  { background: rgba(0,149,67,.2); }
.mnu-avail-btn--off:hover { border-color: var(--bd-text-2); }
[data-theme="dark"] .mnu-avail-btn--on { background: rgba(0,201,87,.15); color: #00c957; }

/* Vide */
.mnu-empty {
    padding: 32px 16px; text-align: center;
    color: var(--bd-text-3); font-size: 12px;
}
.mnu-empty i { display: block; font-size: 22px; margin-bottom: 8px; }

/* Carte vide (aucune catégorie) */
.mnu-no-data {
    background: var(--bd-surface); border: 1px solid var(--bd-border);
    border-radius: var(--bd-radius); padding: 56px 32px;
    text-align: center; color: var(--bd-text-3);
}
.mnu-no-data i { font-size: 36px; display: block; margin-bottom: 12px; color: var(--bd-text-3); }
.mnu-no-data h3 { font-size: 16px; font-weight: 700; color: var(--bd-text-2); margin-bottom: 8px; }

@media (max-width: 600px) {
    .mnu-head { flex-direction: column; align-items: flex-start; }
    .mnu-head__actions { width: 100%; }
    .mnu-btn { flex: 1; justify-content: center; }
    .mnu-product__img { width: 36px; height: 36px; }
}
</style>
@endsection

@section('content')
<div class="mnu">

    {{-- ── Alerte ─────────────────────────────────────────── --}}
    @if(session()->has('alert'))
        <div class="alert alert-{{ session()->get('alert.type') }} alert-dismissible" role="alert">
            {{ session()->get('alert.message') }}
            <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
        </div>
    @endif

    {{-- ── En-tête ─────────────────────────────────────────── --}}
    <div class="mnu-head">
        <div>
            <div class="mnu-head__title">Catalogue</div>
            <div class="mnu-head__sub">
                {{ $categories->count() }} catégorie(s) ·
                {{ $categories->sum(fn($c) => $c->products->count()) }} produit(s) —
                Réordonner par glisser-déposer · activer/désactiver en temps réel
            </div>
        </div>
        <div class="mnu-head__actions">
            <a href="{{ route('category.create') }}" class="mnu-btn mnu-btn--outline">
                <i class="fas fa-folder-plus"></i> Catégorie
            </a>
            <a href="{{ route('product.create') }}" class="mnu-btn mnu-btn--primary">
                <i class="fas fa-plus"></i> Ajouter un plat
            </a>
        </div>
    </div>

    {{-- ── Catégories ──────────────────────────────────────── --}}
    @if($categories->isNotEmpty())
        <div id="categoriesSortable" style="display:flex; flex-direction:column; gap:12px;">
            @foreach($categories as $category)
                <div class="mnu-cat {{ $category->is_available ? '' : 'is-unavailable' }}"
                     data-id="{{ $category->id }}">

                    <div class="mnu-cat__head">
                        <i class="fas fa-grip-vertical mnu-cat__grip"></i>
                        <div class="mnu-cat__name">
                            {{ $category->name }}
                            <span class="mnu-cat__count">{{ $category->products->count() }}</span>
                            @if(!$category->is_available)
                                <span style="font-size:10px;font-weight:600;color:var(--bd-text-3);">· Masquée</span>
                            @endif
                        </div>
                        <div class="mnu-cat__controls">
                            <button class="mnu-toggle {{ $category->is_available ? 'mnu-toggle--on' : 'mnu-toggle--off' }} btnToggleCategory"
                                    data-id="{{ $category->id }}" type="button"
                                    title="{{ $category->is_available ? 'Désactiver cette catégorie' : 'Activer cette catégorie' }}">
                                <i class="fas {{ $category->is_available ? 'fa-eye' : 'fa-eye-slash' }}"></i>
                                {{ $category->is_available ? 'Visible' : 'Masquée' }}
                            </button>
                            <a href="{{ route('category.edit', $category->id) }}"
                               class="mnu-icon-btn" title="Modifier la catégorie">
                                <i class="fas fa-pen"></i>
                            </a>
                        </div>
                    </div>

                    <div class="mnu-products"
                         id="productsSortable-{{ $category->id }}"
                         data-category-id="{{ $category->id }}">
                        @forelse($category->products as $product)
                            @php
                                $img = $product->image ?? null;
                                $imgSrc = $img
                                    ? (str_starts_with($img, 'http') ? $img : asset('images/product_images/' . $img))
                                    : null;
                                $price = (float) ($product->discount_price > 0 ? $product->discount_price : $product->price);
                                $origPrice = $product->discount_price > 0 ? (float) $product->price : null;
                            @endphp
                            <div class="mnu-product {{ $product->is_available ? '' : 'is-unavailable' }}"
                                 data-id="{{ $product->id }}">
                                <i class="fas fa-grip-vertical mnu-product__grip"></i>
                                @if($imgSrc)
                                    <img class="mnu-product__img" src="{{ $imgSrc }}"
                                         onerror="this.style.display='none'"
                                         alt="{{ $product->name }}">
                                @else
                                    <div class="mnu-product__img"
                                         style="display:flex;align-items:center;justify-content:center;font-size:18px;">🍽</div>
                                @endif
                                <div class="mnu-product__info">
                                    <div class="mnu-product__name">{{ $product->name }}</div>
                                    <div class="mnu-product__price">
                                        {{ number_format($price, 0, ',', ' ') }} FCFA
                                        @if($origPrice)
                                            <span class="orig">{{ number_format($origPrice, 0, ',', ' ') }}</span>
                                        @endif
                                    </div>
                                </div>
                                <button class="mnu-avail-btn {{ $product->is_available ? 'mnu-avail-btn--on' : 'mnu-avail-btn--off' }} btnToggleProduct"
                                        data-id="{{ $product->id }}" type="button"
                                        title="{{ $product->is_available ? 'Désactiver ce produit' : 'Activer ce produit' }}">
                                    <i class="fas {{ $product->is_available ? 'fa-check' : 'fa-ban' }}"></i>
                                </button>
                                <a href="{{ route('product.edit', $product->id) }}"
                                   class="mnu-icon-btn" title="Modifier le produit">
                                    <i class="fas fa-pen"></i>
                                </a>
                            </div>
                        @empty
                            <div class="mnu-empty">
                                <i class="fas fa-utensils"></i>
                                Aucun produit dans cette catégorie —
                                <a href="{{ route('product.create') }}" style="color:var(--bd-green);font-weight:600;">Ajouter</a>
                            </div>
                        @endforelse
                    </div>

                </div>
            @endforeach
        </div>
    @else
        <div class="mnu-no-data">
            <i class="fas fa-utensils"></i>
            <h3>Catalogue vide</h3>
            <p style="margin-bottom:20px;">Créez votre première catégorie pour organiser votre menu.</p>
            <a href="{{ route('category.create') }}" class="mnu-btn mnu-btn--primary" style="display:inline-flex;">
                <i class="fas fa-folder-plus"></i> Créer une catégorie
            </a>
        </div>
    @endif

</div>
@endsection

@section('script')
<script src="https://cdnjs.cloudflare.com/ajax/libs/Sortable/1.15.2/Sortable.min.js"></script>
<script>
(function () {
    var csrf = '{{ csrf_token() }}';
    var reorderCatUrl   = '{{ route("restaurant.menu.categories.reorder") }}';
    var toggleCatBase   = '{{ url("/") }}/restaurant/menu/categories/';
    var toggleProdBase  = '{{ url("/") }}/restaurant/menu/products/';
    var reorderProdBase = '{{ url("/") }}/restaurant/menu/categories/';

    async function patch(url, payload) {
        var r = await fetch(url, {
            method: 'PATCH',
            headers: { 'X-CSRF-TOKEN': csrf, 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify(payload)
        });
        if (!r.ok) throw new Error((await r.json()).message || 'Erreur');
        return r.json();
    }

    // Tri catégories
    new Sortable(document.getElementById('categoriesSortable'), {
        handle: '.mnu-cat__grip', animation: 150,
        onEnd: function () {
            var ids = [...document.querySelectorAll('#categoriesSortable .mnu-cat')].map(el => +el.dataset.id);
            patch(reorderCatUrl, { ids }).catch(function(e){ alert(e.message); });
        }
    });

    // Tri produits par catégorie
    document.querySelectorAll('[id^="productsSortable-"]').forEach(function (list) {
        new Sortable(list, {
            handle: '.mnu-product__grip', animation: 150,
            onEnd: function () {
                var ids = [...list.querySelectorAll('.mnu-product')].map(el => +el.dataset.id);
                var catId = list.dataset.categoryId;
                patch(reorderProdBase + catId + '/products/reorder', { ids })
                    .catch(function(e){ alert(e.message); });
            }
        });
    });

    // Toggle catégorie
    document.querySelectorAll('.btnToggleCategory').forEach(function (btn) {
        btn.addEventListener('click', async function () {
            try {
                await patch(toggleCatBase + btn.dataset.id + '/availability', {});
                location.reload();
            } catch (e) { alert(e.message); }
        });
    });

    // Toggle produit
    document.querySelectorAll('.btnToggleProduct').forEach(function (btn) {
        btn.addEventListener('click', async function () {
            try {
                await patch(toggleProdBase + btn.dataset.id + '/availability', {});
                location.reload();
            } catch (e) { alert(e.message); }
        });
    });
}());
</script>
@endsection
