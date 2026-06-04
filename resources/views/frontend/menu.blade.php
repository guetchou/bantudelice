@extends('frontend.layouts.app-modern')
@php
    $foodBrandName = \App\Services\ConfigService::getCompanyName();
@endphp
@section('title', $restaurant->name . ' | ' . $foodBrandName)
@section('description', $restaurant->description ?? 'Découvrez le menu de ' . $restaurant->name . ' et commandez vos plats préférés.')
@section('body_class', 'bd-restaurant-menu-page')

@php
    // Récupérer les valeurs par défaut depuis ConfigService (DB)
    $defaultRating = \App\Services\ConfigService::getDefaultRating();
@endphp

@section('content')
<div class="restaurant-page">
    <!-- Hero Section -->
    @php
        $restaurantCoverImage = method_exists($restaurant, 'publicCoverImageUrl')
            ? $restaurant->publicCoverImageUrl()
            : ($restaurant->cover_image
                ? (strpos($restaurant->cover_image, 'http') === 0 ? $restaurant->cover_image : asset('images/restaurant_images/' . $restaurant->cover_image))
                : ($restaurant->logo ? (strpos($restaurant->logo, 'http') === 0 ? $restaurant->logo : asset('images/restaurant_images/' . $restaurant->logo)) : asset('images/placeholder.png')));
        $restaurantIdentityImage = method_exists($restaurant, 'publicIdentityImageUrl')
            ? $restaurant->publicIdentityImageUrl()
            : ($restaurant->logo
                ? (strpos($restaurant->logo, 'http') === 0 ? $restaurant->logo : asset('images/restaurant_images/' . $restaurant->logo))
                : asset('images/placeholder.png'));
    @endphp
    <section class="restaurant-hero premium-restaurant-hero">
        <img src="{{ $restaurantCoverImage }}" 
             alt="{{ $restaurant->name }}"
             class="hero-bg"
             onerror="this.src='{{ asset('images/i1.jpg') }}'">
        
        <div class="hero-content">
            <div class="container">
                <img src="{{ $restaurantIdentityImage }}" 
                     alt="{{ $restaurant->name }}"
                     class="restaurant-logo"
                     onerror="this.src='{{ asset('images/placeholder.png') }}'">
                
                <div class="restaurant-info">
                    <h1 class="restaurant-name">{{ $restaurant->name }}</h1>
                    
                    @if($restaurant->slogan)
                    <p class="restaurant-slogan">{{ $restaurant->slogan }}</p>
                    @endif
                    
                    <div class="restaurant-meta">
                        <!-- Badge Ouvert/Fermé -->
                        @if(isset($status))
                        <div class="status-badge {{ $status['is_open'] ? 'is-open' : 'is-closed' }}">
                            <span>{{ $status['is_open'] ? 'Ouvert' : 'Fermé' }}</span>
                            @if(!$status['is_open'] && $status['next_opening'])
                            <span class="status-badge__hint">• Réouvre {{ $status['next_opening'] }}</span>
                            @endif
                        </div>
                        @endif
                        
                        <div class="rating-badge premium-rating-badge">
                            
                            {{ number_format((float)($restaurant->avg_rating ?? $defaultRating), 1) }}
                            @if($restaurant->rating_count ?? 0 > 0)
                            <span class="rating-badge__count">({{ $restaurant->rating_count }} avis)</span>
                            @endif
                        </div>
                        
                        @php
                            $defaultDeliveryTimeMin = \App\Services\ConfigService::getDefaultDeliveryTimeMin();
                            $defaultDeliveryTimeMax = \App\Services\ConfigService::getDefaultDeliveryTimeMax();
                            $etaMin = $defaultDeliveryTimeMin;
                            $etaMax = $defaultDeliveryTimeMax;
                            if ($restaurant->avg_delivery_time) {
                                try {
                                    $time = \Carbon\Carbon::parse($restaurant->avg_delivery_time);
                                    $minutes = $time->hour * 60 + $time->minute;
                                    if ($minutes > 0) {
                                        $etaMin = max(15, $minutes - 5);
                                        $etaMax = $minutes + 5;
                                    }
                                } catch (\Exception $e) {}
                            }
                        @endphp
                        <div class="meta-item premium-meta-item">
                            
                            {{ $etaMin }}-{{ $etaMax }} min
                        </div>
                        
                        @php
                            $deliveryFee = $restaurant->delivery_charges ?? $defaultDeliveryFee;
                        @endphp
                        <div class="meta-item premium-meta-item">
                            
                            Livraison : {{ number_format($deliveryFee, 0, ',', ' ') }} FCFA
                        </div>
                        
                        @if($restaurant->min_order)
                        <div class="meta-item premium-meta-item">
                            
                            Min. {{ number_format($restaurant->min_order, 0, ',', ' ') }} FCFA
                        </div>
                        @endif
                    </div>
                    
                    @if($restaurant->cuisines && $restaurant->cuisines->count() > 0)
                    <div class="cuisines-tags">
                        @foreach($restaurant->cuisines as $cuisine)
                        <span class="cuisine-tag premium-cuisine-tag">{{ $cuisine->name }}</span>
                        @endforeach
                    </div>
                    @endif

                    <div class="restaurant-hero-actions">
                        @auth
                            <form method="POST" action="{{ route('restaurants.favorite.toggle', $restaurant->id) }}">
                                @csrf
                                <button type="submit" class="restaurant-hero-action restaurant-hero-action--favorite{{ !empty($isFavorite) ? ' is-active' : '' }}">
                                    <i class="fas fa-heart"></i>
                                    {{ !empty($isFavorite) ? 'En favoris' : 'Ajouter aux favoris' }}
                                </button>
                            </form>
                        @endauth
                        <a href="{{ route('cart.detail') }}" class="restaurant-hero-action restaurant-hero-action--cart">
                            <i class="fas fa-shopping-bag"></i>
                            Voir le panier
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Menu Content -->
    <section class="menu-content premium-menu-content">
        <div class="container">
            <!-- Breadcrumb -->
            <nav class="breadcrumb-modern premium-breadcrumb">
                <a href="{{ route('home') }}">Accueil</a>
                <span class="separator">/</span>
                <span class="current">{{ $restaurant->name }}</span>
            </nav>

            @if(Session::has('message'))
            <div class="restaurant-flash-message">
                {{ Session::get('message') }}
            </div>
            @endif
            
            <!-- Promotions Actives -->
            @if(isset($activePromos) && $activePromos->count() > 0)
            <div class="restaurant-promos">
                <h3 class="restaurant-promos__title">Promotions actives</h3>
                <div class="restaurant-promos__grid">
                    @foreach($activePromos as $promo)
                    <div class="restaurant-promos__card">
                        <div class="restaurant-promos__value">-{{ $promo->discount }}%</div>
                        <div class="restaurant-promos__name">{{ $promo->name }}</div>
                        <div class="restaurant-promos__date">
                            Jusqu'au {{ \Carbon\Carbon::parse($promo->end_date)->format('d/m/Y') }}
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif
            
            @if($abc->count() > 1)
            <nav class="menu-catnav" id="menuCatNav">
                <div class="menu-catnav__inner">
                    @foreach($abc as $cat)
                    <a href="#cat-{{ $loop->index }}" class="menu-catnav__pill">{{ $cat->name }}</a>
                    @endforeach
                </div>
            </nav>
            @endif

            @foreach($abc as $category)
            <div class="category-section" id="cat-{{ $loop->index }}">
                <div class="category-header">
                    <h2 class="category-title">{{ $category->name }}</h2>
                    <span class="category-count">{{ $category->products->count() }} plat{{ $category->products->count() > 1 ? 's' : '' }}</span>
                </div>
                
                @if($category->products->count() > 0)
                <div class="products-grid">
                    @foreach($category->products as $pro)
                    @php
                        $productUrl = route('frontend.product.show', ['id' => $pro->id, 'slug' => \Illuminate\Support\Str::slug($pro->name)]);
                    @endphp
                    <div class="product-card{{ (isset($pro->is_available) && !$pro->is_available) ? ' is-unavailable' : '' }}">
                        <a href="{{ $productUrl }}" class="product-card-link">
                            <div class="product-image-wrapper">
                                @if($pro->featured)
                                <span class="product-badge">Populaire</span>
                                @endif
                                @if(isset($pro->is_available) && !$pro->is_available)
                                <span class="product-badge product-badge--unavailable">
                                    Indisponible
                                </span>
                                @endif
                                <img src="{{ method_exists($pro, 'publicImageUrl') ? $pro->publicImageUrl() : ($pro->image ? (strpos($pro->image, 'http') === 0 ? $pro->image : asset('images/product_images/' . $pro->image)) : asset('images/product_images/default-food.jpg')) }}"
                                     alt="{{ $pro->name }}"
                                     class="product-image"
                                     loading="lazy"
                                     onerror="this.src='{{ asset('images/product_images/default-food.jpg') }}'">
                            </div>
                            
                            <div class="product-info">
                                <h3 class="product-name">{{ $pro->name }}</h3>
                                <p class="product-description">
                                    {{ $pro->description ?: 'Délicieux plat préparé avec soin' }}
                                </p>
                            </div>
                        </a>
                        
                        <div class="product-info product-info--footer">
                            <div class="product-footer">
                                <span class="product-price">{{ number_format($pro->price, 0, ',', ' ') }} FCFA</span>
                                <div class="product-actions">
                                    <a href="{{ $productUrl }}" class="btn-view">Voir</a>
                                    @if(isset($pro->is_available) && !$pro->is_available)
                                    <button type="button" class="btn-quick-add" disabled>Indisponible</button>
                                    @else
                                    <div class="bd-add-wrap" data-product-id="{{ $pro->id }}">
                                        <button type="button" class="btn-quick-add bd-add-btn">+ Ajouter</button>
                                        <div class="bd-stepper" style="display:none">
                                            <button type="button" class="bd-stepper__btn bd-step-dec">−</button>
                                            <span class="bd-stepper__qty">1</span>
                                            <button type="button" class="bd-stepper__btn bd-step-inc">+</button>
                                        </div>
                                    </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
                @else
                <div class="empty-category"><p>Aucun plat disponible dans cette catégorie.</p></div>
                @endif
            </div>
            @endforeach
            
            @if($abc->count() == 0)
            <div class="empty-category empty-category--menu">
                <h3 class="empty-category__title">Menu en cours de préparation</h3>
                <p>Ce restaurant n'a pas encore ajouté de plats à son menu.</p>
                <div class="empty-category__actions">
                    <a href="{{ route('home') }}" class="empty-category__link empty-category__link--primary">Voir d'autres restaurants</a>
                    <a href="{{ url('restaurant') }}" class="empty-category__link empty-category__link--dark">Espace restaurant</a>
                </div>
            </div>
            @endif
            
            <!-- Section Avis Clients -->
            @if(isset($recentReviews) && $recentReviews->count() > 0)
            <div class="restaurant-reviews">
                <div class="restaurant-reviews__header">
                    <h2 class="restaurant-reviews__title">Avis clients</h2>
                    <a href="#all-reviews" class="restaurant-reviews__link">
                        Voir tous les avis ({{ $restaurant->rating_count ?? 0 }})
                    </a>
                </div>
                
                <div class="restaurant-reviews__grid" id="reviewsContainer">
                    @foreach($recentReviews as $review)
                    <div class="restaurant-review-card">
                        <div class="restaurant-review-card__head">
                            <div class="restaurant-review-card__user">
                                <div class="restaurant-review-card__avatar">
                                    {{ $review->user ? substr($review->user->name, 0, 2) : '??' }}
                                </div>
                                <div>
                                    <div class="restaurant-review-card__name">
                                        {{ $review->user->name ?? 'Anonyme' }}
                                    </div>
                                    <div class="restaurant-review-card__date">
                                        {{ $review->created_at->format('d/m/Y') }}
                                    </div>
                                </div>
                            </div>
                            <div class="restaurant-review-card__rating">{{ $review->rating }}</div>
                        </div>
                        @if($review->reviews)
                        <p class="restaurant-review-card__text">
                            {{ $review->reviews }}
                        </p>
                        @else
                        <p class="restaurant-review-card__empty">
                            Aucun commentaire
                        </p>
                        @endif
                    </div>
                    @endforeach
                </div>
                
                @if(($restaurant->rating_count ?? 0) > 10)
                <div class="restaurant-reviews__actions">
                    <button onclick="loadAllReviews({{ $restaurant->id }})" class="restaurant-reviews__button">Charger plus d'avis</button>
                </div>
                @endif
            </div>
            @endif
        </div>
    </section>
</div>

<!-- Floating Cart Button -->
@if(Auth::check())
<a href="{{ route('cart.detail') }}" class="floating-cart" id="floatingCart">
    <span>Voir le panier</span>
    <span class="floating-cart__badge" id="floatingCartBadge" style="display:none">0</span>
</a>
@endif
@endsection

@section('style')
<style>
/* ── Category nav pills ────────────────────────── */
.menu-catnav{position:sticky;top:56px;z-index:90;background:#fff;border-bottom:1px solid #f0f0f0;box-shadow:0 2px 8px rgba(0,0,0,.06);}
.menu-catnav__inner{display:flex;gap:.5rem;overflow-x:auto;padding:.6rem 1rem;scrollbar-width:none;}
.menu-catnav__inner::-webkit-scrollbar{display:none}
.menu-catnav__pill{flex-shrink:0;padding:.35rem .85rem;border-radius:20px;font-size:.8rem;font-weight:600;color:#555;background:#f4f4f4;text-decoration:none;transition:background .18s,color .18s;white-space:nowrap;}
.menu-catnav__pill:hover,.menu-catnav__pill.is-active{background:#007836;color:#fff;}

/* ── Inline qty stepper on product card ─────────── */
.bd-add-wrap{display:flex;align-items:center;}
.bd-stepper{display:flex;align-items:center;gap:.25rem;background:#007836;border-radius:20px;padding:.2rem .25rem;}
.bd-stepper__btn{width:28px;height:28px;border-radius:50%;border:none;background:rgba(255,255,255,.25);color:#fff;font-size:1rem;line-height:1;cursor:pointer;display:flex;align-items:center;justify-content:center;transition:background .15s;}
.bd-stepper__btn:hover{background:rgba(255,255,255,.4);}
.bd-stepper__qty{color:#fff;font-weight:700;font-size:.9rem;min-width:18px;text-align:center;}
.btn-quick-add.bd-add-btn:disabled{opacity:.5;cursor:not-allowed;}

/* ── Floating cart badge ────────────────────────── */
.floating-cart{position:relative;}
.floating-cart__badge{position:absolute;top:-6px;right:-6px;background:#e53e3e;color:#fff;border-radius:50%;width:20px;height:20px;font-size:.68rem;font-weight:700;display:flex;align-items:center;justify-content:center;}
</style>
@endsection

@section('scripts')
<script>
(function() {
    const CART_URL      = @json(route('cart'));
    const CART_UPDATE   = @json(url('cart/update'));
    const CART_DELETE   = @json(url('cart/deleteItem'));
    const CSRF          = document.querySelector('meta[name="csrf-token"]')?.content || '';

    // { productId: { cartItemId, qty } }
    const state = {};

    function badgeEl() { return document.getElementById('floatingCartBadge'); }

    function setBadge(count) {
        const el = badgeEl();
        if (!el) return;
        el.textContent = count;
        el.style.display = count > 0 ? 'flex' : 'none';
        if (typeof updateCartBadge === 'function') updateCartBadge(count);
    }

    function renderStepper(wrap, qty) {
        wrap.querySelector('.bd-add-btn').style.display = 'none';
        const s = wrap.querySelector('.bd-stepper');
        s.style.display = 'flex';
        s.querySelector('.bd-stepper__qty').textContent = qty;
    }

    function showAddBtn(wrap) {
        wrap.querySelector('.bd-add-btn').style.display = '';
        wrap.querySelector('.bd-stepper').style.display = 'none';
    }

    async function postCart(productId) {
        const res = await fetch(CART_URL, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
            body: JSON.stringify({ product_id: productId, qty: 1 })
        });
        return res.json();
    }

    async function putCart(cartItemId, qty) {
        const body = new FormData();
        body.append('_method', 'PUT');
        body.append('qty', qty);
        body.append('_token', CSRF);
        const res = await fetch(`${CART_UPDATE}/${cartItemId}`, { method: 'POST', headers: { 'Accept': 'application/json' }, body });
        return res.ok;
    }

    async function deleteCartItem(cartItemId) {
        const body = new FormData();
        body.append('_token', CSRF);
        const res = await fetch(`${CART_DELETE}/${cartItemId}`, { method: 'POST', headers: { 'Accept': 'application/json' }, body });
        return res.ok;
    }

    document.querySelectorAll('.bd-add-wrap').forEach(function(wrap) {
        const productId = parseInt(wrap.dataset.productId);
        const addBtn    = wrap.querySelector('.bd-add-btn');
        const decBtn    = wrap.querySelector('.bd-step-dec');
        const incBtn    = wrap.querySelector('.bd-step-inc');

        addBtn.addEventListener('click', async function() {
            addBtn.disabled = true;
            try {
                const data = await postCart(productId);
                if (data.success) {
                    state[productId] = { cartItemId: data.cart_item_id, qty: 1 };
                    renderStepper(wrap, 1);
                    setBadge(data.total_items);
                    if (typeof showToast === 'function') showToast('Ajouté au panier', 'success');
                } else {
                    if (typeof showToast === 'function') showToast(data.message || 'Erreur', 'error');
                }
            } catch(e) {
                if (typeof showToast === 'function') showToast('Erreur réseau', 'error');
            } finally {
                addBtn.disabled = false;
            }
        });

        incBtn.addEventListener('click', async function() {
            incBtn.disabled = true;
            try {
                const data = await postCart(productId);
                if (data.success) {
                    state[productId].qty++;
                    if (data.cart_item_id) state[productId].cartItemId = data.cart_item_id;
                    wrap.querySelector('.bd-stepper__qty').textContent = state[productId].qty;
                    setBadge(data.total_items);
                }
            } finally { incBtn.disabled = false; }
        });

        decBtn.addEventListener('click', async function() {
            if (!state[productId]) return;
            decBtn.disabled = true;
            try {
                const { cartItemId, qty } = state[productId];
                if (qty > 1) {
                    const ok = await putCart(cartItemId, qty - 1);
                    if (ok) {
                        state[productId].qty--;
                        wrap.querySelector('.bd-stepper__qty').textContent = state[productId].qty;
                        const badge = badgeEl();
                        if (badge && parseInt(badge.textContent) > 0) setBadge(parseInt(badge.textContent) - 1);
                    }
                } else {
                    const ok = await deleteCartItem(cartItemId);
                    if (ok) {
                        delete state[productId];
                        showAddBtn(wrap);
                        const badge = badgeEl();
                        if (badge && parseInt(badge.textContent) > 0) setBadge(parseInt(badge.textContent) - 1);
                    }
                }
            } finally { decBtn.disabled = false; }
        });
    });

    // Smooth scroll + active pill highlight
    document.querySelectorAll('.menu-catnav__pill').forEach(function(link) {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) target.scrollIntoView({ behavior: 'smooth', block: 'start' });
        });
    });

    const sections = document.querySelectorAll('.category-section[id^="cat-"]');
    const pills    = document.querySelectorAll('.menu-catnav__pill');
    if (sections.length && pills.length) {
        const io = new IntersectionObserver(function(entries) {
            entries.forEach(function(entry) {
                if (entry.isIntersecting) {
                    const id = entry.target.id;
                    pills.forEach(function(p) {
                        p.classList.toggle('is-active', p.getAttribute('href') === '#' + id);
                    });
                }
            });
        }, { rootMargin: '-40% 0px -55% 0px' });
        sections.forEach(function(s) { io.observe(s); });
    }
})();
</script>
@endsection
