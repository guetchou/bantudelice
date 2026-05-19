@extends('frontend.layouts.app-modern')
@php
    $foodBrandName = \App\Services\ConfigService::getCompanyName();
@endphp
@section('title', 'Votre Panier | ' . $foodBrandName)
@section('description', 'Consultez et gérez votre panier ' . $foodBrandName . '.')
@php
    $hasCartItems = isset($cartData) && $cartData->count() > 0;
    $loyaltyPoints = 0;
    $loyaltyDiscount = 0;
    $showLoyaltyBlock = false;

    if (auth()->check()) {
        $loyaltyPoints = \App\Services\LoyaltyService::getBalance(auth()->user()->id);
        $loyaltyDiscount = \App\Services\LoyaltyService::calculateDiscount($loyaltyPoints);
        $showLoyaltyBlock = $loyaltyPoints > 0;
    }
@endphp

@section('hide_primary_chrome', '1')
@section('body_class', 'bd-cart-page')

@section('content')
{{-- ── Mini nav ──────────────────────────────────────────── --}}
<nav class="cart-mini-nav">
  <div class="cart-mini-nav__inner">
    <a href="{{ route('home') }}" class="cart-mini-nav__brand">
      <img src="{{ asset('frontend/images/BuntuDelice.png') }}" alt="{{ $foodBrandName }}" class="cart-mini-nav__brand-image" onerror="this.style.display='none';this.nextElementSibling.style.display='block'">
      <span class="cart-mini-nav__brand-name">{{ $foodBrandName }}</span>
    </a>
    <div class="cart-mini-nav__actions">
      <a href="{{ route('restaurants.all') }}" class="cart-mini-nav__continue">← Continuer mes achats</a>
      <a href="{{ auth()->check() ? route('user.profile') : route('login') }}" class="cart-mini-nav__account">
        <i class="fas fa-user fa-xs"></i> {{ auth()->check() ? auth()->user()->name : 'Connexion' }}
      </a>
    </div>
  </div>
</nav>

{{-- ── Page header + stepper ─────────────────────────────── --}}
<div class="cart-header">
  <div class="cart-header__inner">
    <div class="cart-header__top">
      <a href="{{ url('/') }}" class="cart-header__back" aria-label="Retour">
        <i class="fas fa-arrow-left"></i>
      </a>
      <h1 class="cart-header__title">Votre panier</h1>
      @if($hasCartItems)
        <span class="cart-header__count">{{ $cartData->count() }} article{{ $cartData->count() > 1 ? 's' : '' }}</span>
      @endif
    </div>
    <div class="cstepper">
      <div class="cstep">
        <span class="cstep__num active"><i class="fas fa-shopping-basket cstep__icon"></i></span>
        <span class="cstep__label active">Panier</span>
      </div>
      <div class="cstep__line"></div>
      <div class="cstep">
        <span class="cstep__num pending">2</span>
        <span class="cstep__label pending">Paiement</span>
      </div>
      <div class="cstep__line"></div>
      <div class="cstep">
        <span class="cstep__num pending">3</span>
        <span class="cstep__label pending">Confirmation</span>
      </div>
    </div>
  </div>
</div>

{{-- ── Cart shell ────────────────────────────────────────── --}}
<div class="cart-shell">
  <div class="cart-inner">

    {{-- Alert --}}
    @if(session()->has('alert'))
      <div class="cart-alert cart-alert--full {{ session()->get('alert.type') == 'success' ? 'success' : 'error' }}">
        <i class="fas fa-{{ session()->get('alert.type') == 'success' ? 'check-circle' : 'exclamation-circle' }}"></i>
        {{ session()->get('alert.message') }}
      </div>
    @endif

    @if($hasCartItems)

    {{-- ── Items panel ──────────────────────────────────── --}}
    <div class="cart-panel">
      <div class="cart-panel__head">
        <h2><i class="fas fa-bowl-food cart-panel__title-icon"></i>Articles ({{ $cartData->count() }})</h2>
        <a href="{{ route('restaurants.all') }}">+ Ajouter des plats</a>
      </div>

      @foreach($cartData as $data)
      <div class="cart-item" data-cart-id="{{ $data->id }}">
        <img class="cart-item__img"
             src="{{ method_exists($data, 'publicImageUrl') ? $data->publicImageUrl() : ($data->image ? (strpos($data->image,'http')===0 ? $data->image : asset('images/product_images/'.$data->image)) : asset('images/product_images/default-food.jpg')) }}"
             alt="{{ $data->name }}"
             onerror="this.src='{{ asset('images/product_images/default-food.jpg') }}'">

        <div class="cart-item__info">
          <div class="cart-item__name">{{ $data->name }}</div>
          <div class="cart-item__desc">{!! \Illuminate\Support\Str::limit($data->description, 60) !!}</div>
          <div class="cart-item__price item-price" data-price="{{ $data->price }}">
            {{ number_format($data->price, 0, ',', ' ') }} FCFA / unité
          </div>
          <div class="cart-item__actions">
            <form class="cart-update-form" action="{{ route('cart.update', $data->id) }}" method="post">
              @csrf
              @method('PUT')
              <div class="cart-qty-stepper">
                <button type="button" class="cart-qty-btn qty-dec" aria-label="Diminuer">−</button>
                <input type="number" name="qty" value="{{ $data->qty }}" min="1" max="20" class="cart-qty-input">
                <button type="button" class="cart-qty-btn qty-inc" aria-label="Augmenter">+</button>
              </div>
              <button type="submit" class="cart-update-btn cart-update-btn--spaced">
                <i class="fas fa-rotate fa-xs"></i> Mettre à jour
              </button>
            </form>
            <form method="POST" action="{{ route('cart.item', $data->id) }}" class="cart-delete-form">
              @csrf
              <button type="submit" class="cart-delete-btn">
                <i class="fas fa-trash-can fa-xs"></i> Supprimer
              </button>
            </form>
          </div>
        </div>

        <div class="cart-item__right">
          <div class="cart-item-subtotal">{{ number_format($data->price * $data->qty, 0, ',', ' ') }} FCFA</div>
        </div>
      </div>
      @endforeach
    </div>

    {{-- ── Summary card ─────────────────────────────────── --}}
    <div class="cart-summary">
      <div class="cart-summary__head">
        <i class="fas fa-receipt cart-summary__icon"></i>Récapitulatif
      </div>
      <div class="cart-summary__body">

        <div class="cart-summary-row">
          <span>Sous-total</span>
          <strong id="cartSubtotal">{{ number_format($total, 0, ',', ' ') }} FCFA</strong>
        </div>

        <div class="cart-summary-delivery-note">
          <i class="fas fa-circle-info"></i>
          Frais de livraison calculés à l'étape suivante
        </div>

        @if($showLoyaltyBlock)
          <div class="loyalty-block">
            <div class="loyalty-block__head">
              <span class="loyalty-block__title"><i class="fas fa-star fa-xs"></i> Points fidélité</span>
              <span class="loyalty-block__pts">{{ number_format($loyaltyPoints, 0, ',', ' ') }} pts</span>
            </div>
            <p>Économisez jusqu'à <strong>{{ number_format($loyaltyDiscount, 0, ',', ' ') }} FCFA</strong> à l'étape paiement</p>
          </div>
        @endif

        <div class="cart-summary-row total">
          <span>Total estimé</span>
          <span id="cartTotal">{{ number_format($total, 0, ',', ' ') }} FCFA</span>
        </div>

        @if($check != null)
          @if($check->min_order <= $total)
            <a href="{{ route('checkout.detail') }}" class="cart-btn-checkout cart-btn-checkout--spaced">
              <i class="fas fa-lock fa-sm"></i> Passer au paiement
            </a>
          @else
            <div class="min-order-warn min-order-warn--spaced">
              <p>
                <i class="fas fa-triangle-exclamation fa-xs"></i>
                Commande minimum : <strong>{{ number_format($check->min_order, 0, ',', ' ') }} FCFA</strong>
              </p>
            </div>
          @endif
        @endif

        <a href="{{ url('/') }}" class="cart-btn-continue">
          <i class="fas fa-arrow-left fa-xs"></i> Continuer mes achats
        </a>
      </div>
    </div>

    @else
    {{-- ── Empty state ──────────────────────────────────── --}}
    <div class="cart-empty-wrap">
      <div class="cart-empty">
        <div class="cart-empty__icon"><i class="fas fa-basket-shopping"></i></div>
        <h2>Votre panier est vide</h2>
        <p>Découvrez nos restaurants partenaires et commencez à remplir votre panier avec vos plats favoris.</p>
        <a href="{{ url('/') }}" class="cart-btn-explore">
          <i class="fas fa-bowl-food"></i> Découvrir les restaurants
        </a>
      </div>
    </div>
    @endif

  </div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
    const cartForms = document.querySelectorAll('.cart-update-form');

    function formatNumber(num) {
        return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ' ');
    }

    function updateCartTotals() {
        let subtotal = 0;
        document.querySelectorAll('.cart-item').forEach(function(item) {
            const priceEl = item.querySelector('.item-price');
            const qtyEl   = item.querySelector('.cart-qty-input');
            if (!priceEl || !qtyEl) return;
            const price = parseFloat(priceEl.dataset.price);
            const qty   = parseInt(qtyEl.value);
            const sub   = price * qty;
            const subEl = item.querySelector('.cart-item-subtotal');
            if (subEl) subEl.textContent = formatNumber(sub) + ' FCFA';
            subtotal += sub;
        });
        const subEl = document.getElementById('cartSubtotal');
        const totEl = document.getElementById('cartTotal');
        if (subEl) subEl.textContent = formatNumber(subtotal) + ' FCFA';
        if (totEl) totEl.textContent = formatNumber(subtotal) + ' FCFA';
    }

    // +/- stepper buttons
    document.querySelectorAll('.qty-dec').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const input = btn.closest('.cart-qty-stepper').querySelector('.cart-qty-input');
            if (parseInt(input.value) > 1) { input.value = parseInt(input.value) - 1; updateCartTotals(); }
        });
    });
    document.querySelectorAll('.qty-inc').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const input = btn.closest('.cart-qty-stepper').querySelector('.cart-qty-input');
            if (parseInt(input.value) < 10) { input.value = parseInt(input.value) + 1; updateCartTotals(); }
        });
    });

    cartForms.forEach(function(form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(form);
            const qty      = parseInt(formData.get('qty'));
            const submitBtn = form.querySelector('.cart-update-btn');
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin fa-xs"></i> …';

            fetch(form.action, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                },
                body: formData
            })
            .then(r => r.ok ? r.json().catch(() => ({status:true})) : Promise.reject())
            .then(() => {
                if (qty <= 0) {
                    const item = form.closest('.cart-item');
                    item.style.transition = 'opacity .3s,transform .3s';
                    item.style.opacity = '0';
                    item.style.transform = 'translateX(-12px)';
                    setTimeout(() => { item.remove(); updateCartTotals(); }, 300);
                } else {
                    updateCartTotals();
                    showToast('Quantité mise à jour', 'success');
                }
            })
            .catch(() => showToast('Erreur lors de la mise à jour', 'error'))
            .finally(() => {
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-rotate fa-xs"></i> Mettre à jour';
            });
        });

        form.querySelector('.cart-qty-input')?.addEventListener('change', function() {
            const qty = parseInt(this.value);
            if (qty > 0 && qty <= 10) updateCartTotals();
        });
    });

    function showToast(message, type) {
        if (typeof window.showToast === 'function') { window.showToast(message, type); return; }
        const t = document.createElement('div');
        t.style.cssText = 'position:fixed;top:20px;right:20px;padding:12px 20px;border-radius:10px;z-index:10000;font-weight:600;font-size:.875rem;box-shadow:0 4px 16px rgba(0,0,0,.15);';
        t.style.background = type === 'success' ? '#009543' : '#dc2626';
        t.style.color = '#fff';
        t.textContent = message;
        document.body.appendChild(t);
        setTimeout(() => { t.style.opacity = '0'; t.style.transition = 'opacity .3s'; setTimeout(() => t.remove(), 300); }, 2800);
    }
});
</script>
@endsection
