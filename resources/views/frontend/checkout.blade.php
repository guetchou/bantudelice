@extends('frontend.layouts.app-modern')
@section('title', trans('ui.checkout.title') . ' | ' . trans('ui.site.name'))
@php
    $foodBrandName = \App\Services\ConfigService::getCompanyName();
@endphp
@section('description', 'Finalisez votre commande ' . $foodBrandName . '.')
@section('hide_primary_chrome', '1')
@section('body_class', 'bd-checkout-page')

@section('style')
<style>
/* ── Checkout Wizard ── */
.co-step-section { display: none; }
.co-step-section.is-active { display: block; }

.co-step-nav {
  display: flex; align-items: center; justify-content: flex-end;
  gap: 12px; margin-top: 20px; padding-top: 16px;
  border-top: 1px solid #f1f5f9;
}
.co-step-nav--split { justify-content: space-between; }

.co-step-btn-back {
  display: inline-flex; align-items: center; gap: 6px;
  padding: 10px 18px;
  background: transparent; border: 1.5px solid #e2e8f0; border-radius: 10px;
  color: #64748b; font: 500 .85rem 'Poppins', sans-serif;
  cursor: pointer; text-decoration: none; transition: background .15s;
}
.co-step-btn-back:hover { background: #f8fafc; color: #334155; }

.co-step-btn-next {
  display: inline-flex; align-items: center; gap: 8px;
  padding: 12px 24px;
  background: #009543; border: none; border-radius: 10px;
  color: #fff; font: 600 .9rem 'Poppins', sans-serif;
  cursor: pointer; transition: background .15s, transform .1s;
}
.co-step-btn-next:hover { background: #007a38; }
.co-step-btn-next:active { transform: scale(.97); }
.co-step-btn-next svg, .co-step-btn-back svg { flex-shrink: 0; }

/* Step 4 mobile summary */
.co-step4-mobile { display: none; }

@media (max-width: 768px) {
  .co-sidebar { display: none; }
  .co-sidebar.is-step4-visible { display: block; }
  .co-step4-mobile { display: block; padding: 16px 0 4px; }
}
</style>
@endsection

@section('content')
@php
    $checkoutUi = trans('ui.checkout');
    $commonUi = trans('ui.common');
@endphp

{{-- styles migrated to frontend/css/modern.css --}}
{{-- ══════════════════════════════════════════════════
     NAV
══════════════════════════════════════════════════ --}}
<nav class="co-nav">
  <div class="co-nav__inner">
    <a href="{{ route('home') }}" class="co-nav__logo">
      <img src="{{ asset('frontend/images/BuntuDelice.png') }}" alt="{{ $foodBrandName }}" class="co-nav__logo-img"
           onerror="this.style.display='none';this.nextElementSibling.classList.add('is-fallback')">
      <span class="co-nav__logo-name">{{ $foodBrandName }}</span>
    </a>
    <div class="co-nav__right">
      <a href="{{ route('cart.detail') }}" class="co-nav__back">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5M12 5l-7 7 7 7"/></svg>
        Panier
      </a>
      <span class="co-nav__secure">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
        Paiement sécurisé
      </span>
    </div>
  </div>
</nav>

{{-- ══════════════════════════════════════════════════
     STEPPER
══════════════════════════════════════════════════ --}}
<div class="co-stepbar">
  <div class="co-stepbar__inner">
    <div class="co-step-item">
      <div class="co-step-node co-step-node--done" id="stepNode1">
        <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
      </div>
      <span class="co-step-text co-step-text--done" id="stepText1">{{ data_get($checkoutUi, 'step_cart', 'Panier') }}</span>
    </div>
    <div class="co-step-wire co-step-wire--done" id="stepWire1"></div>
    <div class="co-step-item">
      <div class="co-step-node co-step-node--active" id="stepNode2">
        <span class="co-step-check" style="display:none"><svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg></span>
        <span class="co-step-num">2</span>
      </div>
      <span class="co-step-text co-step-text--active" id="stepText2">Livraison</span>
    </div>
    <div class="co-step-wire co-step-wire--idle" id="stepWire2"></div>
    <div class="co-step-item">
      <div class="co-step-node co-step-node--idle" id="stepNode3">
        <span class="co-step-check" style="display:none"><svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg></span>
        <span class="co-step-num">3</span>
      </div>
      <span class="co-step-text co-step-text--idle" id="stepText3">{{ data_get($checkoutUi, 'step_payment', 'Paiement') }}</span>
    </div>
    <div class="co-step-wire co-step-wire--idle" id="stepWire3"></div>
    <div class="co-step-item">
      <div class="co-step-node co-step-node--idle" id="stepNode4">
        <span class="co-step-check" style="display:none"><svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg></span>
        <span class="co-step-num">4</span>
      </div>
      <span class="co-step-text co-step-text--idle" id="stepText4">{{ data_get($checkoutUi, 'step_confirm', 'Confirmation') }}</span>
    </div>
  </div>
</div>

{{-- ══════════════════════════════════════════════════
     PAGE
══════════════════════════════════════════════════ --}}
<section class="co-page">
  <div class="co-page__wrap">

    {{-- Flash message --}}
    @if(session()->has('message'))
      <div class="co-alert co-alert--warn">
        <strong>{{ session()->get('message') }}</strong>
      </div>
    @endif

    {{-- Validation errors --}}
    <?php $viewErrors = isset($errors) ? $errors : new \Illuminate\Support\ViewErrorBag; ?>
    @if($viewErrors->any())
      <div class="co-alert co-alert--error">
        <ul>
          @foreach($viewErrors->all() as $error)
            <li>{{ $error }}</li>
          @endforeach
        </ul>
      </div>
    @endif

    {{-- Stock issues --}}
    <?php $stockIssues = collect($stockIssues ?? []); ?>
    @if($stockIssues->isNotEmpty())
      <div class="co-stock-wrap">
        <div class="co-stock-hd">
          <div class="co-stock-icon"><i class="fas fa-exclamation-triangle"></i></div>
          <div>
            <div class="co-stock-title">Rupture détectée avant paiement</div>
            <div class="co-stock-sub">Certains produits ne sont plus disponibles. Choisissez un remplacement ou retirez l'article du panier.</div>
          </div>
        </div>
        @foreach($stockIssues as $issue)
            <div class="co-stock-card">
              <div class="co-stock-row">
              <div>
                <div class="co-stock-product">{{ $issue['product_name'] ?? 'Produit indisponible' }}</div>
                <div class="co-stock-meta">{{ $issue['restaurant_name'] ?? 'Restaurant' }} · Qté {{ $issue['qty'] ?? 1 }}</div>
              </div>
              <span class="co-rupture">Rupture</span>
            </div>
            @if(!empty($issue['suggestions']))
              <div class="co-suggests">
                @foreach($issue['suggestions'] as $suggestion)
                  <a href="{{ $suggestion['url'] ?? '#' }}" class="co-suggest-link">
                    Remplacer par <strong>{{ $suggestion['name'] ?? 'Suggestion' }}</strong>
                  </a>
                @endforeach
              </div>
            @endif
          </div>
        @endforeach
      </div>
    @endif

    <?php $checkoutGroups = collect($cartGroups ?? []); ?>

    <form class="form-horizontal" method="post" action="{{ route('place.order') }}" id="checkoutForm">
      @csrf

      <div class="co-layout">

        {{-- ════════════════════════
             LEFT COLUMN
        ════════════════════════ --}}
        <div class="co-main">

          {{-- ══ STEP 2 : Livraison & Horaire ══ --}}
          <div class="co-step-section is-active" id="coStep2">

          {{-- ── Mode de réception ── --}}
          <div class="co-card">
            <div class="co-card__hd">
              <div class="co-card__icon"><i class="fas fa-route"></i></div>
              <span class="co-card__title">{{ data_get($checkoutUi, 'reception_mode', 'Mode de réception') }}</span>
            </div>
            <div class="co-fulfill-grid">
              <label class="fulfillment-option is-active" data-mode="delivery">
                <input type="radio" name="fulfillment_mode" value="delivery" checked>
                <div class="co-ff-icon"><i class="fas fa-motorcycle"></i></div>
                <div class="co-ff-radio"></div>
                <div class="co-ff-title">{{ data_get($checkoutUi, 'delivery', 'Livraison') }}</div>
                <div class="co-ff-desc">{{ data_get($checkoutUi, 'delivery_description', 'Un livreur apporte la commande à votre adresse.') }}</div>
              </label>
              <label class="fulfillment-option" data-mode="pickup">
                <input type="radio" name="fulfillment_mode" value="pickup">
                <div class="co-ff-icon"><i class="fas fa-store"></i></div>
                <div class="co-ff-radio"></div>
                <div class="co-ff-title">{{ data_get($checkoutUi, 'pickup', 'Retrait') }}</div>
                <div class="co-ff-desc">{{ data_get($checkoutUi, 'pickup_description', 'Vous récupérez la commande au restaurant.') }}</div>
              </label>
            </div>
          </div>

          {{-- ── Planification ── --}}
          <div class="co-card">
            <div class="co-schedule-row">
              <div>
                <div class="co-schedule-label">{{ data_get($checkoutUi, 'schedule_title', 'Commander pour plus tard') }}</div>
                <div class="co-schedule-hint">Planifiez un créneau de livraison</div>
              </div>
              <label class="co-toggle">
                <input type="checkbox" id="scheduleOrderToggle">
                <span class="co-toggle__track"><span class="co-toggle__thumb"></span></span>
              </label>
            </div>
            <div id="scheduleOrderPanel">
              <div class="co-field">
                <label class="co-label" for="scheduledDate">{{ data_get($checkoutUi, 'scheduled_at', 'Date et heure souhaitées') }}</label>
                <input type="datetime-local" id="scheduledDate" name="scheduled_date" class="co-input">
                <p class="co-hint">{{ data_get($checkoutUi, 'scheduled_hint', 'La commande restera planifiée jusqu\'au créneau choisi.') }}</p>
              </div>
            </div>
          </div>

          {{-- ── Adresse de livraison ── --}}
          <div id="deliveryAddressPanel" class="co-card">
            <div class="co-card__hd">
              <div class="co-card__icon"><i class="fas fa-map-marker-alt"></i></div>
              <span class="co-card__title" id="addressPanelTitle">{{ data_get($checkoutUi, 'delivery_address', 'Adresse de livraison') }}</span>
            </div>
            <div class="co-card__body">

              <div class="co-field">
                <label class="co-label">{{ data_get($checkoutUi, 'name', 'Nom') }}</label>
                <input type="text" value="{{ optional(auth()->user())->name }}" class="co-input co-input--disabled" disabled>
              </div>

              <div class="co-field">
                <label class="co-label" for="searchMapInput">{{ data_get($checkoutUi, 'delivery_address', 'Adresse de livraison') }} *</label>
                <input type="text" id="searchMapInput" name="delivery_address"
                       placeholder="{{ data_get($checkoutUi, 'address_placeholder', 'Entrez votre adresse de livraison') }}"
                       value="{{ old('delivery_address') }}"
                       class="co-input" required>
                <div id="deliverySuggestions" class="checkout-suggestions"></div>
                <input type="hidden" id="latitude"  name="d_lat"       value="-4.2767">
                <input type="hidden" id="longitude" name="d_lng"       value="15.2832">
                <input type="hidden" id="savedAddressId" name="address_id" value="">
                <input type="hidden" id="deliveryCity" value="">
                <input type="hidden" id="deliveryDepartment" value="">
                <input type="hidden" id="deliveryAddressConfirmed" value="0">
                @if($viewErrors->has('delivery_address'))
                  <span class="co-field-error">{{ $viewErrors->first('delivery_address') }}</span>
                @endif
              </div>

              @if(isset($savedAddresses) && $savedAddresses->count() > 0)
              <div class="co-field">
                <label class="co-label" for="savedAddressSelect">{{ data_get($checkoutUi, 'saved_address', 'Adresse enregistrée') }}</label>
                <select id="savedAddressSelect" class="co-input">
                  <option value="">Choisir une adresse enregistrée</option>
                  @foreach($savedAddresses as $savedAddress)
                    <option
                      value="{{ $savedAddress->id }}"
                      data-title="{{ $savedAddress->title }}"
                      data-address="{{ $savedAddress->complete_address }}"
                      data-area="{{ $savedAddress->area }}"
                      data-building="{{ $savedAddress->building_no }}"
                      data-street="{{ $savedAddress->street_no }}"
                      data-floor="{{ $savedAddress->floor }}"
                      data-lat="{{ $savedAddress->latitude }}"
                      data-lng="{{ $savedAddress->longitude }}"
                      @if($savedAddress->is_default) selected @endif
                    >
                      {{ $savedAddress->title }} — {{ $savedAddress->complete_address }}
                      @if($savedAddress->is_default) (Par défaut) @endif
                    </option>
                  @endforeach
                </select>
                <p class="co-hint">{{ data_get($checkoutUi, 'saved_address_hint', 'Sélectionnez une adresse enregistrée ou gardez le repère carte.') }}</p>
              </div>
              @endif

              <div class="co-grid2">
                <div class="co-field">
                  <label class="co-label" for="deliveryDistrict">{{ data_get($checkoutUi, 'district', 'Quartier / zone') }}</label>
                  <input type="text" id="deliveryDistrict" placeholder="Ex: Poto-Poto" class="co-input">
                </div>
                <div class="co-field">
                  <label class="co-label" for="deliveryLandmark">{{ data_get($checkoutUi, 'landmark', 'Lieu connu / repère') }}</label>
                  <input type="text" id="deliveryLandmark" placeholder="Ex: Marché Total, pharmacie..." class="co-input">
                </div>
              </div>

              <div class="co-field">
                <label class="co-label" for="deliveryComplement">{{ data_get($checkoutUi, 'complement', 'Complément d\'adresse') }}</label>
                <textarea id="deliveryComplement" class="co-input co-input--area"
                          placeholder="Bâtiment, portail, étage, code d'accès, détail pour le livreur..."></textarea>
                <p id="deliveryMapStatus" class="co-map-status">{{ data_get($checkoutUi, 'map_hint', 'Placez un repère sur la carte pour une livraison plus précise.') }}</p>
                <div id="deliveryPrecisionAlert" class="co-precision-alert" aria-live="polite"></div>
              </div>

              <div class="co-map-helpers">
                <button type="button" id="locateDeliveryBtn" class="co-map-btn">
                  <i class="fas fa-location-arrow"></i> Me localiser
                </button>
                <button type="button" id="usePinBtn" class="co-map-btn">
                  <i class="fas fa-map-pin"></i> Placer repère
                </button>
                <button type="button" id="clearDeliveryBtn" class="co-map-btn">
                  <i class="fas fa-times"></i> Réinitialiser
                </button>
              </div>

              <div class="co-map-wrap">
                <div id="map" class="co-map-canvas"></div>
              </div>

            </div>
          </div>

          {{-- ── Retrait ── --}}
          <div id="pickupPanel" class="co-card co-hidden">
            <div class="co-card__hd">
              <div class="co-card__icon"><i class="fas fa-store"></i></div>
              <span class="co-card__title">{{ data_get($checkoutUi, 'pickup', 'Retrait au restaurant') }}</span>
            </div>
            <div class="co-card__body">
              <div class="co-pickup-info">
                <div class="co-pickup-info__lbl">{{ data_get($checkoutUi, 'restaurant', 'Restaurant') }}</div>
                <div class="co-pickup-info__name">{{ $restaurantModel->name ?? 'Restaurant partenaire' }}</div>
                <div class="co-pickup-info__addr">{{ $restaurantModel->address ?? 'Adresse non renseignée' }}</div>
              </div>
              <div class="co-field co-field--spaced">
                <label class="co-label" for="pickupNote">{{ data_get($checkoutUi, 'pickup_note', 'Note de retrait') }}</label>
                <textarea id="pickupNote" name="pickup_note" class="co-input co-input--area"
                          placeholder="Heure de passage, nom de la personne, repère utile..."></textarea>
                <p class="co-hint">{{ data_get($checkoutUi, 'pickup_note_hint', 'Un code de retrait sera généré après validation.') }}</p>
              </div>
            </div>
          </div>

          {{-- ── Step 2 navigation ── --}}
          <div class="co-step-nav">
            <a href="{{ route('cart.detail') }}" class="co-step-btn-back">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5M12 5l-7 7 7 7"/></svg>
              Modifier le panier
            </a>
            <button type="button" class="co-step-btn-next" onclick="coGoToStep(3)">
              Continuer vers le paiement
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
            </button>
          </div>

          </div>{{-- /coStep2 --}}

          {{-- ══ STEP 3 : Paiement & Options ══ --}}
          <div class="co-step-section" id="coStep3">

          {{-- ── Promo & Pourboire ── --}}
          <div class="co-card">
            <div class="co-card__hd">
              <div class="co-card__icon"><i class="fas fa-tag"></i></div>
              <span class="co-card__title">Code promo &amp; Pourboire</span>
            </div>
            <div class="co-card__body">
              <div class="co-tip-promo-grid">
                <div>
                  <label class="co-label" for="tip">Pourboire livreur (FCFA)</label>
                  <div class="co-inline">
                    <input type="number" min="0" id="tip" name="driver_tip" placeholder="0" value="0" class="co-input">
                    <button type="button" onclick="applyTip()" class="co-map-btn co-map-btn--fixed">
                      <i class="fas fa-plus"></i>
                    </button>
                  </div>
                </div>
                <div>
                  <label class="co-label" for="voucher">Code promo</label>
                  <div class="co-inline">
                    <input type="text" id="voucher" name="voucher_code" placeholder="Entrez votre code" class="co-input">
                    <button type="button" id="applyVoucher" class="co-voucher-btn">
                      Appliquer
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>

          {{-- ── Mode de paiement ── --}}
          <div class="co-card">
            <div class="co-card__hd">
              <div class="co-card__icon"><i class="fas fa-credit-card"></i></div>
              <span class="co-card__title">Mode de paiement</span>
            </div>
            <div class="co-card__body">
              <div class="payment-methods-card">
                <div class="payment-methods-list">

                  {{-- Cash --}}
                  <label class="payment-method-row" data-method="cash">
                    <input class="payment-method-input" type="radio" name="payment_method" value="cash">
                    <div class="payment-method-icon">
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" width="20" height="20">
                        <rect x="3" y="6" width="18" height="12" rx="2"></rect>
                        <path d="M16 12h4"></path>
                      </svg>
                    </div>
                    <div class="payment-method-copy">
                      <p class="payment-method-title">Paiement à la livraison</p>
                      <p class="payment-method-description">Payez en espèces à la réception</p>
                    </div>
                    <div class="payment-method-dot"></div>
                  </label>

                  {{-- Mobile money --}}
                  <label class="payment-method-row is-active" data-method="mobile_money">
                    <input class="payment-method-input" type="radio" name="payment_method" value="mobile_money" checked>
                    <div class="payment-method-copy">
                      <p class="payment-method-title">Paiement mobile</p>
                      <p class="payment-method-description">MTN MoMo · Airtel Money</p>
                      <div class="co-phone-wrap">
                        <label class="co-label" for="paymentPhone">Numéro à débiter</label>
                        <div class="co-phone-row">
                          <div id="paymentOperatorLogoWrap" class="co-hidden">
                            <img id="paymentOperatorLogo" src="" alt="">
                          </div>
                          <input
                            type="text"
                            id="paymentPhone"
                            name="phone"
                            value="{{ old('phone', '') }}"
                            autocomplete="off"
                            inputmode="tel"
                            class="co-input"
                            placeholder="06 xxx xxx ou 05 xxx xxx"
                          >
                        </div>
                        <div id="paymentOperatorHint" class="co-payment-operator-hint">
                          Entrez un numéro commençant par 06 ou 05.
                        </div>
                      </div>
                    </div>
                    <div class="payment-method-dot"></div>
                  </label>

                </div>
              </div>
            </div>
          </div>

          {{-- ── Step 3 navigation ── --}}
          <div class="co-step-nav co-step-nav--split">
            <button type="button" class="co-step-btn-back" onclick="coGoToStep(2)">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5M12 5l-7 7 7 7"/></svg>
              Livraison
            </button>
            <button type="button" class="co-step-btn-next" onclick="coGoToStep(4)">
              Vérifier la commande
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
            </button>
          </div>

          </div>{{-- /coStep3 --}}

          {{-- ══ STEP 4 : Confirmation (desktop = sidebar; mobile = this panel) ══ --}}
          <div class="co-step-section" id="coStep4">
            <div class="co-step4-mobile">
              <div class="co-card" style="border:2px solid #009543;">
                <div class="co-card__hd">
                  <div class="co-card__icon" style="background:#dcfce7;color:#009543;"><i class="fas fa-check-circle"></i></div>
                  <span class="co-card__title">Votre commande est prête</span>
                </div>
                <div class="co-card__body">
                  <p style="color:#64748b;font-size:.88rem;margin-bottom:16px;">Vérifiez le récapitulatif ci-dessous puis validez pour passer commande.</p>
                  <button type="submit" form="checkoutForm" class="co-step-btn-next" style="width:100%;justify-content:center;padding:14px 20px;font-size:.95rem;">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                    Commander
                  </button>
                </div>
              </div>
            </div>
            <div class="co-step-nav" style="padding-top:8px;margin-top:8px;">
              <button type="button" class="co-step-btn-back" onclick="coGoToStep(3)">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5M12 5l-7 7 7 7"/></svg>
                Modifier le paiement
              </button>
            </div>
          </div>{{-- /coStep4 --}}

        </div>{{-- /co-main --}}

        {{-- ════════════════════════
             RIGHT COLUMN — Summary
        ════════════════════════ --}}
        <div class="co-sidebar">
          <div class="co-summary-card">
            <div class="co-summary__hd">
              <span class="co-summary__title">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 17H5a2 2 0 0 0-2 2"/><path d="M15 17h4a2 2 0 0 1 2 2"/><path d="M12 3v14"/><path d="M5 3h14"/></svg>
                Récapitulatif
              </span>
              <a href="{{ route('cart.detail') }}" class="co-summary__edit">Modifier</a>
            </div>
            <div class="co-summary__body">

              {{-- Multi-restaurant banner --}}
              @if($hasMultipleRestaurants)
                <div class="co-multi-banner">
                  <div class="co-multi-icon"><i class="fas fa-layer-group"></i></div>
                  <div>
                    <div class="co-multi-title">Panier groupé</div>
                    <div class="co-multi-sub">{{ $checkoutGroups->count() }} restaurants · paiement unique</div>
                  </div>
                </div>
              @endif

              {{-- Items --}}
              <div class="co-items-scroll">
                @if($checkoutGroups->isNotEmpty())
                  @foreach($checkoutGroups as $group)
                  @php
                    $restaurant = $group->restaurant ?? null;
                    $restaurantLogo = $restaurant && method_exists($restaurant, 'publicIdentityImageUrl')
                      ? $restaurant->publicIdentityImageUrl()
                      : asset('images/home/service-restaurant.jpg');
                    $itemsCount = $group->items instanceof \Illuminate\Support\Collection ? $group->items->count() : count($group->items ?? []);
                  @endphp
                  <div class="co-rgroup">
                    <div class="co-rgroup__hd">
                      <img src="{{ $restaurantLogo }}" alt="{{ $restaurant->name ?? 'Restaurant' }}" class="co-rlogo"
                           onerror="this.src='{{ asset('images/home/service-restaurant.jpg') }}'">
                      <div class="co-rinfo">
                        <div class="co-rname">{{ $restaurant->name ?? 'Restaurant partenaire' }}</div>
                        <div class="co-rcount">{{ $itemsCount }} article{{ $itemsCount > 1 ? 's' : '' }}</div>
                      </div>
                      <div class="co-rsubtotal">
                        <div class="co-rsubtotal__val">{{ number_format($group->sub_total ?? 0, 0, ',', ' ') }} FCFA</div>
                      </div>
                    </div>
                    @foreach($group->items as $checkout)
                    <div class="co-item">
                      <img src="{{ method_exists($checkout, 'publicImageUrl') ? $checkout->publicImageUrl() : ($checkout->image ? (strpos($checkout->image, 'http') === 0 ? $checkout->image : asset('images/product_images/' . $checkout->image)) : asset('images/product_images/default-food.jpg')) }}"
                           class="co-item__img"
                           onerror="this.src='{{ asset('images/product_images/default-food.jpg') }}'">
                      <div class="co-item__body">
                        <p class="co-item__name">{{ $checkout->name }}</p>
                        @if(!empty($checkout->description))
                          <p class="co-item__desc">{{ \Illuminate\Support\Str::limit($checkout->description, 70) }}</p>
                        @endif
                        <div class="co-item__qty">Qté : {{ $checkout->qty }}</div>
                      </div>
                      <p class="co-item__price">{{ number_format((float)($checkout->cart_price ?? $checkout->price ?? 0), 0, ',', ' ') }} FCFA</p>
                    </div>
                    @endforeach
                  </div>
                  @endforeach
                @else
                  @foreach($checkoutData as $checkout)
                  <div class="co-item">
                    <img src="{{ method_exists($checkout, 'publicImageUrl') ? $checkout->publicImageUrl() : ($checkout->image ? (strpos($checkout->image, 'http') === 0 ? $checkout->image : asset('images/product_images/' . $checkout->image)) : asset('images/product_images/default-food.jpg')) }}"
                         class="co-item__img">
                    <div class="co-item__body">
                      <p class="co-item__name">{{ $checkout->name }}</p>
                      <div class="co-item__qty">Qté : {{ $checkout->qty }}</div>
                    </div>
                    <p class="co-item__price">{{ number_format($checkout->price, 0, ',', ' ') }} FCFA</p>
                  </div>
                  @endforeach
                @endif
              </div>

              {{-- Totals --}}
              @php
                $checkoutTax          = (float)($tax ?? 0);
                $checkoutServiceFee   = (float)($service_fee ?? 0);
                $checkoutLoyaltyDiscount = (float)($loyaltyDiscount ?? 0);
                $checkoutGrandTotal   = (float)($grandTotal ?? 0);
              @endphp

              <div class="co-totals">
                <div class="co-trow">
                  <span class="co-trow__lbl">Sous-total</span>
                  <span class="co-trow__val" id="checkoutSubtotal">{{ number_format($total, 0, ',', ' ') }} FCFA</span>
                </div>
                <div class="co-trow">
                  <span class="co-trow__lbl" id="deliveryFeeLabel">Frais de livraison</span>
                  <span class="co-trow__val" id="deliveryFee">{{ number_format($charges->delivery_fee, 0, ',', ' ') }} FCFA</span>
                </div>
                @if(!empty($weatherSurchargeActive) && $weatherSurcharge > 0)
                <div class="co-trow" style="color:#b45309;">
                  <span class="co-trow__lbl">
                      <i class="fas fa-cloud-rain" style="font-size:11px;margin-right:4px;"></i>
                      {{ $weatherSurchargeLabel ?? 'Majoration saison des pluies' }}
                  </span>
                  <span class="co-trow__val">+{{ number_format($weatherSurcharge, 0, ',', ' ') }} FCFA</span>
                </div>
                @endif
                <div class="co-trow">
                  <span class="co-trow__lbl">Frais de service</span>
                  <span class="co-trow__val" id="serviceFee">{{ number_format($checkoutServiceFee, 0, ',', ' ') }} FCFA</span>
                </div>
                <div class="co-trow">
                  <span class="co-trow__lbl">Taxes</span>
                  <span class="co-trow__val" id="taxAmount">{{ number_format($checkoutTax, 0, ',', ' ') }} FCFA</span>
                </div>

                @if(auth()->check() && $loyaltyPoints > 0)
                <div class="co-loyalty">
                  <div class="co-loyalty__hd">
                    <span class="co-loyalty__title">
                      <svg width="12" height="12" viewBox="0 0 24 24" fill="#009543"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                      Points fidélité
                    </span>
                    <label class="co-loyalty__cb">
                      <input type="checkbox" id="useLoyaltyPoints" name="use_loyalty_points" value="1" class="co-loyalty__input" onchange="updateLoyaltyDiscount()">
                      Utiliser
                    </label>
                  </div>
                  <div id="loyaltyDiscountRow">
                    <span class="co-ld-lbl">Réduction appliquée</span>
                    <span class="co-ld-val" id="loyaltyDiscountAmount">-{{ number_format($checkoutLoyaltyDiscount, 0, ',', ' ') }} FCFA</span>
                  </div>
                  <input type="hidden" name="loyalty_points_used" id="loyaltyPointsUsed" value="0">
                </div>
                @endif

                <div class="co-grand">
                  <span class="co-grand__lbl">Total</span>
                  <span class="co-grand__val" id="ttotal">{{ number_format($checkoutGrandTotal, 0, ',', ' ') }} FCFA</span>
                </div>
              </div>

              {{-- Hidden fields --}}
              <input type="hidden" name="qty"                       value="1">
              <input type="hidden" id="restaurant"                  value="{{ optional($resturant)->restaurant_id }}">
              <input type="hidden" id="checkoutRestaurantIds"       value="{{ $checkoutGroups->pluck('restaurant_id')->filter()->unique()->implode(',') }}">
              <input type="hidden" id="checkoutBaseSubtotal"        value="{{ $total }}">
              <input type="hidden" id="checkoutDeliveryFeeBase"     value="{{ (float)($charges->delivery_fee ?? 0) }}">
              <input type="hidden" id="checkoutPickupFeeBase"       value="{{ (float)($charges->pickup_fee ?? 0) }}">
              <input type="hidden" id="checkoutTaxRate"             value="{{ (float)($charges->tax ?? 0) }}">
              <input type="hidden" id="checkoutServiceFeeRate"      value="{{ (float)($charges->service_fee ?? 0) }}">
              <input type="hidden" id="checkoutLoyaltyDiscountBase" value="{{ $checkoutLoyaltyDiscount }}">
              <input type="hidden" name="sub_total"                 value="{{ $total }}">
              <input type="hidden" name="tax"                       value="{{ $charges->tax }}">
              <input type="hidden" name="delivery_charges"          value="{{ $charges->delivery_fee }}">
              <input type="hidden" id="sub_total"                   value="{{ $checkoutGrandTotal }}">
              <input type="hidden" id="total" name="amount"         value="{{ $checkoutGrandTotal }}">

              {{-- Submit --}}
              <button type="submit" id="checkoutSubmitBtn" class="btn">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                <span id="btnText">Commander</span>
              </button>

              <div class="co-ssl">
                <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                Paiement 100% sécurisé · SSL
              </div>

            </div>{{-- co-summary__body --}}
          </div>{{-- co-summary-card --}}
        </div>{{-- co-sidebar --}}

      </div>{{-- co-layout --}}
    </form>

    {{-- ── Mobile CTA sticky ── --}}
    <div class="co-mobile-cta">
      <div class="co-mcta-row">
        <span class="co-mcta-lbl">Total estimé</span>
        <span class="co-mcta-val" id="mobileTotalDisplay">{{ number_format($checkoutGrandTotal, 0, ',', ' ') }} FCFA</span>
      </div>
      <button type="button" class="co-mcta-btn" onclick="document.getElementById('checkoutSubmitBtn').click()">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
        <span id="mobileBtnText">Commander</span>
      </button>
    </div>

  </div>{{-- co-page__wrap --}}
</section>

@endsection

@section('scripts')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<script>
window.checkoutApiConfig = {
    createCheckoutUrl: @json(route('checkout.api')),
    paymentStatusBaseUrl: @json(url('/checkout/payments')),
    loginUrl: @json(route('user.login', ['redirect' => request()->getRequestUri()])),
};
</script>
<script src="{{ asset('js/checkout.js') }}?v={{ @filemtime(public_path('js/checkout.js')) ?: time() }}"></script>
<script>
const CHECKOUT_MAPBOX_TOKEN = @json(mapbox_public_token());
const CHECKOUT_DEFAULT_LOCATION = { lat: -4.2767, lng: 15.2832 };
const CHECKOUT_RESTAURANT_NAME = @json($restaurantModel->name ?? 'Restaurant partenaire');
let checkoutMap;
let checkoutMarker;
let checkoutSearchTimeout;
let checkoutVoucherDiscount = 0;
const savedAddressSelect = document.getElementById('savedAddressSelect');
const savedAddressIdField = document.getElementById('savedAddressId');
const checkoutAddressState = {
    precisionLevel: 'blind',
    confirmed: false,
    source: 'manual',
    city: 'Brazzaville',
    department: 'Brazzaville',
};

function getFulfillmentMode() {
    return document.querySelector('input[name="fulfillment_mode"]:checked')?.value || 'delivery';
}

function isCheckoutAddressTooBroad(level) {
    return ['district', 'area', 'blind'].includes(String(level || 'blind'));
}

function inferDepartmentFromCity(city) {
    const normalized = String(city || '').trim().toLowerCase();
    if (!normalized) return '';
    if (normalized.includes('pointe')) return 'Pointe-Noire';
    if (normalized.includes('brazzaville')) return 'Brazzaville';
    return city;
}

function syncCheckoutAddressState() {
    const cityField = document.getElementById('deliveryCity');
    const departmentField = document.getElementById('deliveryDepartment');
    const confirmedField = document.getElementById('deliveryAddressConfirmed');
    if (cityField) cityField.value = checkoutAddressState.city || '';
    if (departmentField) departmentField.value = checkoutAddressState.department || '';
    if (confirmedField) confirmedField.value = checkoutAddressState.confirmed ? '1' : '0';
}

function updateCheckoutPrecisionAlert(forceMessage = '', isError = false) {
    const box = document.getElementById('deliveryPrecisionAlert');
    if (!box) return;

    if (getFulfillmentMode() === 'pickup') {
        box.className = 'co-precision-alert';
        box.textContent = '';
        return;
    }

    const needsConfirmation = isCheckoutAddressTooBroad(checkoutAddressState.precisionLevel) && !checkoutAddressState.confirmed;
    const message = forceMessage || (needsConfirmation
        ? 'Adresse encore trop large pour une livraison fiable. Placez le repère exact sur la carte avant de continuer.'
        : '');

    if (!message) {
        box.className = 'co-precision-alert';
        box.textContent = '';
        return;
    }

    box.className = `co-precision-alert is-visible ${isError || needsConfirmation ? 'co-precision-alert--warn' : 'co-precision-alert--ok'}`;
    box.textContent = message;
}

function updateCheckoutActionState() {
    const button = document.getElementById('checkoutSubmitBtn');
    if (!button) return;
    button.disabled = getFulfillmentMode() === 'delivery'
        && isCheckoutAddressTooBroad(checkoutAddressState.precisionLevel)
        && !checkoutAddressState.confirmed;
}

function setCheckoutAddressState(partial = {}) {
    Object.assign(checkoutAddressState, partial);
    syncCheckoutAddressState();
    updateCheckoutPrecisionAlert();
    updateCheckoutActionState();
}

function getBaseSubtotal() {
    return parseFloat(document.getElementById('checkoutBaseSubtotal')?.value || '0');
}

function getBaseDeliveryFee() {
    return parseFloat(document.getElementById('checkoutDeliveryFeeBase')?.value || '0');
}

function getBasePickupFee() {
    return parseFloat(document.getElementById('checkoutPickupFeeBase')?.value || '0');
}

function getTaxRate() {
    return parseFloat(document.getElementById('checkoutTaxRate')?.value || '0');
}

function getServiceFeeRate() {
    return parseFloat(document.getElementById('checkoutServiceFeeRate')?.value || '0');
}

function getBaseLoyaltyDiscount() {
    return parseFloat(document.getElementById('checkoutLoyaltyDiscountBase')?.value || '0');
}

function getCurrentFee() {
    return getFulfillmentMode() === 'pickup' ? getBasePickupFee() : getBaseDeliveryFee();
}

function recalculateCheckoutTotals() {
    const mode = getFulfillmentMode();
    const subtotal = getBaseSubtotal();
    const fee = getCurrentFee();
    const tax = (getTaxRate() / 100) * subtotal;
    const serviceFee = ((fee + tax + subtotal) / 100) * getServiceFeeRate();
    const tipInput = document.getElementById('tip');
    const tip = mode === 'pickup' ? 0 : parseFloat(tipInput?.value || '0');
    const loyaltyApplied = document.getElementById('useLoyaltyPoints')?.checked ? getBaseLoyaltyDiscount() : 0;
    const total = Math.max(0, subtotal + fee + tax + serviceFee + tip - checkoutVoucherDiscount - loyaltyApplied);

    const deliveryLabel = document.getElementById('deliveryFeeLabel');
    const deliveryFee = document.getElementById('deliveryFee');
    const serviceFeeEl = document.getElementById('serviceFee');
    const taxAmount = document.getElementById('taxAmount');
    const totalText = document.getElementById('ttotal');
    const totalInput = document.getElementById('total');
    const feeInput = document.querySelector('input[name="delivery_charges"]');
    const subTotalInput = document.querySelector('input[name="sub_total"]');
    const hiddenSubTotal = document.getElementById('sub_total');
    const tipWrapper = document.getElementById('tip')?.closest('div')?.parentElement;
    const pickupPanel = document.getElementById('pickupPanel');
    const deliveryPanel = document.getElementById('deliveryAddressPanel');
    const addressTitle = document.getElementById('addressPanelTitle');
    const addressInput = document.getElementById('searchMapInput');
    const paymentMethod = document.querySelector('input[name="payment_method"]:checked')?.value || 'cash';
    const mobileMoneyPhonePanel = document.getElementById('mobileMoneyPhonePanel');
    const paymentPhoneInput = document.getElementById('paymentPhone');
    if (paymentPhoneInput) {
        updatePaymentOperatorHint(paymentPhoneInput.value);
    }

    if (deliveryLabel) deliveryLabel.textContent = mode === 'pickup' ? 'Frais de retrait' : 'Frais de livraison';
    if (deliveryFee) deliveryFee.textContent = formatFcfaAmount(fee);
    if (serviceFeeEl) serviceFeeEl.textContent = formatFcfaAmount(serviceFee);
    if (taxAmount) taxAmount.textContent = formatFcfaAmount(tax);
    if (totalText) totalText.textContent = formatFcfaAmount(total);
    if (totalInput) totalInput.value = total;
    if (feeInput) feeInput.value = fee;
    if (subTotalInput) subTotalInput.value = subtotal;
    if (hiddenSubTotal) hiddenSubTotal.value = total;
    if (tipInput && mode === 'pickup') tipInput.value = 0;
    if (tipWrapper) tipWrapper.classList.toggle('co-hidden', mode === 'pickup');
    if (pickupPanel) pickupPanel.classList.toggle('co-hidden', mode !== 'pickup');
    if (deliveryPanel) deliveryPanel.classList.toggle('co-hidden', mode === 'pickup');
    if (addressTitle) addressTitle.textContent = mode === 'pickup' ? 'Point de retrait' : 'Adresse de livraison';
    if (addressInput) addressInput.required = mode !== 'pickup';
    if (mobileMoneyPhonePanel) mobileMoneyPhonePanel.classList.toggle('co-hidden', paymentMethod !== 'mobile_money');
    if (paymentPhoneInput) paymentPhoneInput.required = paymentMethod === 'mobile_money';

    const btnText = document.getElementById('btnText');
    if (btnText) {
        if (paymentMethod === 'cash') {
            btnText.textContent = mode === 'pickup' ? 'Commander (Paiement au retrait)' : 'Commander (Paiement à la livraison)';
        } else if (paymentMethod === 'mobile_money') {
            btnText.textContent = mode === 'pickup' ? 'Payer puis retirer' : 'Commander (Paiement mobile)';
        } else {
            btnText.textContent = mode === 'pickup' ? 'Payer puis retirer' : 'Commander';
        }
    }

    updateCheckoutPrecisionAlert();
    updateCheckoutActionState();
}

// Mise à jour du total avec pourboire
function applyTip() {
    recalculateCheckoutTotals();
}

function formatFcfaAmount(value) {
    return `${Math.round(Number(value || 0)).toLocaleString('fr-FR')} FCFA`;
}

// Appliquer le code promo
document.getElementById('applyVoucher')?.addEventListener('click', function() {
    var voucherInput = document.getElementById('voucher');
    var voucher = voucherInput.value.trim();
    var restaurant = document.getElementById('restaurant').value;
    var subTotal = getBaseSubtotal();
    
    if(!voucher) {
        showToast('Veuillez entrer un code promo', 'error');
        return;
    }
    
    var btn = document.getElementById('applyVoucher');
    btn.disabled = true;
    btn.textContent = 'Vérification...';
    
    fetch("{{url('/voucher')}}", {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({voucher: voucher, restaurant: restaurant})
    })
    .then(async (response) => ({ ok: response.ok, data: await response.json().catch(() => ({})) }))
    .then(({ ok, data: response }) => {
        if(ok && response.status && response.data != null) {
            var discount = (response.data.discount / 100) * subTotal;
            checkoutVoucherDiscount = discount;
            
            // Afficher la réduction
            var discountRow = document.getElementById('voucherDiscountRow');
            if(!discountRow) {
                discountRow = document.createElement('div');
                discountRow.id = 'voucherDiscountRow';
                discountRow.className = 'co-voucher-row';
                var totalDiv = document.getElementById('ttotal').parentElement;
                totalDiv.parentElement.insertBefore(discountRow, totalDiv);
            }
            discountRow.innerHTML = '<span class="co-voucher-row__label"><i class="fas fa-tag"></i> Réduction code promo</span><span class="co-voucher-row__value">-' + Math.round(discount).toLocaleString('fr-FR') + ' FCFA</span>';
            discountRow.classList.remove('co-hidden');
            
            recalculateCheckoutTotals();
            
            btn.textContent = '✓ Appliqué';
            btn.classList.add('is-applied');
            voucherInput.disabled = true;
            
            // Afficher un message de succès
            var successMsg = document.createElement('div');
            successMsg.className = 'co-voucher-success';
            successMsg.textContent = '✓ Code promo appliqué ! Réduction de ' + Math.round(discount).toLocaleString('fr-FR') + ' FCFA';
            voucherInput.parentElement.appendChild(successMsg);
            setTimeout(() => successMsg.remove(), 3000);
        } else {
            btn.disabled = false;
            btn.textContent = 'Appliquer';
            showToast(response.message || 'Code promo invalide ou expiré.', 'error');
        }
    })
    .catch(error => {
        btn.disabled = false;
        btn.textContent = 'Appliquer';
        console.error('Erreur:', error);
        showToast('Erreur lors de la vérification du code promo', 'error');
    });
});

// Gestion des méthodes de paiement
document.querySelectorAll('.payment-method-row').forEach(function(option) {
    option.addEventListener('click', function() {
        if (this.classList.contains('is-disabled')) {
            return;
        }

        // Retirer la sélection de toutes les options
        document.querySelectorAll('.payment-method-row').forEach(function(opt) {
            opt.classList.remove('is-active');
        });
        // Ajouter la sélection à l'option cliquée
        this.classList.add('is-active');

        const input = this.querySelector('input[type="radio"]');
        if (input) {
            input.checked = true;
        }

        recalculateCheckoutTotals();
    });
});

// Gestion des points de fidélité
function updateLoyaltyDiscount() {
    const useLoyalty = document.getElementById('useLoyaltyPoints');
    const discountRow = document.getElementById('loyaltyDiscountRow');
    const discountAmount = document.getElementById('loyaltyDiscountAmount');
    const loyaltyPointsUsed = document.getElementById('loyaltyPointsUsed');
    const totalElement = document.getElementById('ttotal');
    const totalInput = document.getElementById('total');
    
    if (!useLoyalty || !discountRow) return;
    
    if (useLoyalty.checked) {
        discountRow.classList.remove('co-hidden');
        // Calculer les points utilisés
        const pointsPer1000 = 100;
        const pointsUsed = Math.floor((getBaseLoyaltyDiscount() / 1000) * pointsPer1000);
        loyaltyPointsUsed.value = pointsUsed;
    } else {
        discountRow.classList.add('co-hidden');
        loyaltyPointsUsed.value = 0;
    }
    recalculateCheckoutTotals();
}

// Mise à jour automatique du total avec pourboire
document.getElementById('tip')?.addEventListener('input', function() {
    applyTip();
    updateLoyaltyDiscount();
});

function detectMobileMoneyOperator(phone) {
    const digits = String(phone || '').replace(/\D+/g, '');
    if (!digits) {
        return { operator: 'unknown', label: 'Entrez un numéro commençant par 06 ou 05.' };
    }

    let local = digits;
    if (local.startsWith('242')) {
        local = local.slice(3);
    }
    if (local.startsWith('0')) {
        local = local.slice(1);
    }

    if (local.startsWith('6')) {
        return { operator: 'mtn', label: 'MTN détecté.' };
    }

    if (local.startsWith('5')) {
        return { operator: 'airtel', label: 'Airtel détecté.' };
    }

    return { operator: 'unknown', label: 'Numéro non reconnu. Utilisez 06 ou 05.' };
}

function updatePaymentOperatorHint(phone) {
    const hint = document.getElementById('paymentOperatorHint');
    const logoWrap = document.getElementById('paymentOperatorLogoWrap');
    const logo = document.getElementById('paymentOperatorLogo');
    const result = detectMobileMoneyOperator(phone);

    if (hint) {
        hint.textContent = result.label;
        hint.classList.toggle('is-detected', result.operator !== 'unknown');
    }

    if (!logoWrap || !logo) return;
    if (result.operator === 'mtn') {
        logo.src = "{{ asset('images/payments/mtn-momo-guideline.png') }}";
        logo.alt = 'MTN';
        logoWrap.classList.remove('co-hidden');
    } else if (result.operator === 'airtel') {
        logo.src = "{{ asset('images/payments/airtel-money-logo.svg') }}";
        logo.alt = 'Airtel';
        logoWrap.classList.remove('co-hidden');
    } else {
        logoWrap.classList.add('co-hidden');
        logo.removeAttribute('src');
        logo.removeAttribute('alt');
    }
}

document.getElementById('paymentPhone')?.addEventListener('input', function() {
    updatePaymentOperatorHint(this.value);
});

// Initialiser la sélection comme dans le composant fourni
document.querySelector('.payment-method-row[data-method="mobile_money"]')?.click();

document.querySelectorAll('.fulfillment-option').forEach(function(option) {
    option.addEventListener('click', function() {
        document.querySelectorAll('.fulfillment-option').forEach(function(opt) {
            opt.classList.remove('is-active');
        });
        this.classList.add('is-active');
        this.querySelector('input[type="radio"]').checked = true;
        recalculateCheckoutTotals();
    });
});

document.getElementById('scheduleOrderToggle')?.addEventListener('change', function() {
    const panel = document.getElementById('scheduleOrderPanel');
    if (panel) {
        panel.style.display = this.checked ? 'grid' : 'none';
    }
});

// Gestion de la soumission du formulaire via API
document.getElementById('checkoutForm')?.addEventListener('submit', async function(e) {
    e.preventDefault();
    
    var paymentMethod = document.querySelector('input[name="payment_method"]:checked')?.value;
    var deliveryAddress = document.getElementById('searchMapInput')?.value;
    var latitude = document.getElementById('latitude')?.value;
    var longitude = document.getElementById('longitude')?.value;
    var driverTip = document.getElementById('tip')?.value || 0;
    var voucherCode = document.getElementById('voucher')?.value || null;
    var deliveryDistrict = document.getElementById('deliveryDistrict')?.value.trim() || '';
    var deliveryLandmark = document.getElementById('deliveryLandmark')?.value.trim() || '';
    var deliveryComplement = document.getElementById('deliveryComplement')?.value.trim() || '';
    var pickupNote = document.getElementById('pickupNote')?.value.trim() || '';
    var paymentPhone = document.getElementById('paymentPhone')?.value.trim() || '';
    var fulfillmentMode = getFulfillmentMode();
    var scheduleEnabled = document.getElementById('scheduleOrderToggle')?.checked || false;
    var scheduledDate = scheduleEnabled ? document.getElementById('scheduledDate')?.value : null;
    var savedAddressId = document.getElementById('savedAddressId')?.value || '';
    
    // Validation de l'adresse
    if(fulfillmentMode === 'delivery' && (!deliveryAddress || deliveryAddress.trim() === '')) {
        showToast('Veuillez entrer une adresse de livraison', 'error');
        document.getElementById('searchMapInput').focus();
        return false;
    }

    if (fulfillmentMode === 'delivery' && isCheckoutAddressTooBroad(checkoutAddressState.precisionLevel) && !checkoutAddressState.confirmed) {
        const preciseMessage = 'Confirmez precisement l adresse de livraison sur la carte avant de continuer.';
        updateCheckoutStatus(preciseMessage, true);
        updateCheckoutPrecisionAlert(preciseMessage, true);
        document.getElementById('usePinBtn')?.focus();
        return false;
    }
    
    const hasDefaultCoordinates = String(latitude) === String(CHECKOUT_DEFAULT_LOCATION.lat)
        && String(longitude) === String(CHECKOUT_DEFAULT_LOCATION.lng);

    if (fulfillmentMode === 'delivery' && deliveryAddress && (!latitude || !longitude || hasDefaultCoordinates)) {
        const geocodedResults = await checkoutSearch(deliveryAddress, 1);
        if (geocodedResults[0]) {
            applyCheckoutAddress(geocodedResults[0], {
                confirmed: !isCheckoutAddressTooBroad(geocodedResults[0].precisionLevel),
                source: 'search',
            });
            latitude = geocodedResults[0].lat;
            longitude = geocodedResults[0].lng;
            updateCheckoutStatus(
                isCheckoutAddressTooBroad(geocodedResults[0].precisionLevel)
                    ? 'Adresse trouvée au niveau quartier. Confirmez le point exact sur la carte.'
                    : 'Adresse manuelle géolocalisée automatiquement.'
            );
        } else {
            latitude = null;
            longitude = null;
            updateCheckoutStatus('Adresse manuelle conservée sans géolocalisation précise.');
        }
    }

    if (scheduleEnabled && !scheduledDate) {
        showToast('Veuillez choisir une date et une heure de planification.', 'error');
        document.getElementById('scheduledDate').focus();
        return false;
    }

    var fullDeliveryAddress = fulfillmentMode === 'pickup'
        ? ['Retrait sur place', CHECKOUT_RESTAURANT_NAME, pickupNote ? 'Note: ' + pickupNote : ''].filter(Boolean).join(' | ')
        : [
            deliveryAddress,
            deliveryDistrict ? 'Quartier: ' + deliveryDistrict : '',
            deliveryLandmark ? 'Repère: ' + deliveryLandmark : '',
            deliveryComplement ? 'Complément: ' + deliveryComplement : ''
        ].filter(Boolean).join(' | ');
    
    // Normaliser le payment_method pour correspondre à l'API
    var apiPaymentMethod = paymentMethod;
    if (paymentMethod === 'mobile_money') {
        apiPaymentMethod = 'momo'; // Adapter selon votre backend
        if (!paymentPhone) {
            showToast('Saisissez un numéro valide commençant par 06 ou 05.', 'error');
            document.getElementById('paymentPhone')?.focus();
            return false;
        }

        const detectedOperator = detectMobileMoneyOperator(paymentPhone);
        if (detectedOperator.operator === 'unknown') {
            showToast(detectedOperator.label, 'error');
            document.getElementById('paymentPhone')?.focus();
            return false;
        }
    }
    
    // Préparer les données pour l'API
    const formData = {
        payment_method: apiPaymentMethod,
        fulfillment_mode: fulfillmentMode,
        delivery_address: fullDeliveryAddress,
        delivery_area: fulfillmentMode === 'pickup' ? null : (deliveryDistrict || null),
        delivery_city: fulfillmentMode === 'pickup' ? null : (checkoutAddressState.city || 'Brazzaville'),
        delivery_department: fulfillmentMode === 'pickup' ? null : (checkoutAddressState.department || inferDepartmentFromCity(checkoutAddressState.city || 'Brazzaville') || 'Brazzaville'),
        delivery_address_confirmed: fulfillmentMode === 'pickup' ? null : checkoutAddressState.confirmed,
        d_lat: fulfillmentMode === 'pickup' ? null : latitude,
        d_lng: fulfillmentMode === 'pickup' ? null : longitude,
        address_id: fulfillmentMode === 'pickup' ? null : savedAddressId || null,
        driver_tip: fulfillmentMode === 'pickup' ? 0 : driverTip,
        voucher_code: voucherCode,
        pickup_note: pickupNote,
        scheduled_date: scheduleEnabled ? scheduledDate : null,
        phone: apiPaymentMethod === 'momo' ? paymentPhone : null
    };
    
    // Appeler l'API via le gestionnaire de checkout
    if (typeof checkoutManager !== 'undefined') {
        checkoutManager.processCheckout(formData);
    } else {
        console.error('CheckoutManager non disponible');
        showToast('Le système de paiement est indisponible. Veuillez recharger la page.', 'error');
    }
    
    return false;
});

function buildCheckoutAddressDetails(lat, lng, feature) {
    const district = extractCheckoutContext(feature, ['neighborhood', 'locality', 'district']) || extractCheckoutContext(feature, ['place']) || 'Brazzaville';
    const landmark = extractCheckoutContext(feature, ['poi', 'address']) || '';
    const city = extractCheckoutContext(feature, ['place']) || 'Brazzaville';
    const department = extractCheckoutContext(feature, ['region']) || inferDepartmentFromCity(city) || 'Brazzaville';
    const placeTypes = Array.isArray(feature.place_type) ? feature.place_type : [];
    let precisionLevel = 'blind';
    if (placeTypes.includes('poi') || feature.address) {
        precisionLevel = 'exact';
    } else if (placeTypes.includes('address')) {
        precisionLevel = 'street';
    } else if (placeTypes.includes('neighborhood') || placeTypes.includes('locality') || placeTypes.includes('district')) {
        precisionLevel = 'district';
    } else if (placeTypes.includes('place') || placeTypes.includes('region')) {
        precisionLevel = 'area';
    }
    return {
        lat,
        lng,
        label: feature.place_name || `${lat.toFixed(6)}, ${lng.toFixed(6)}`,
        district,
        city,
        department,
        precisionLevel,
        landmark,
        addressLine: feature.text || feature.place_name || `${lat.toFixed(6)}, ${lng.toFixed(6)}`
    };
}

function extractCheckoutContext(feature, preferredTypes) {
    if (!feature) return '';
    const context = Array.isArray(feature.context) ? feature.context : [];
    for (const type of preferredTypes) {
        if ((feature.place_type || []).includes(type) && feature.text) {
            return feature.text;
        }
        const hit = context.find((entry) => (entry.id || '').startsWith(type + '.'));
        if (hit && hit.text) return hit.text;
    }
    return '';
}

var _checkoutMapboxOk = null;

async function checkoutReverseGeocodeNominatim(lat, lng) {
    try {
        const r = await fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}&accept-language=fr&zoom=18`);
        const d = await r.json();
        const addr = d.address || {};
        return {
            lat, lng,
            label: d.display_name || `${lat.toFixed(6)}, ${lng.toFixed(6)}`,
            district: addr.suburb || addr.neighbourhood || addr.city_district || '',
            city: addr.city || addr.town || 'Brazzaville',
            department: addr.state || 'Brazzaville',
            precisionLevel: addr.road ? 'address' : 'area',
            landmark: addr.road || '',
            addressLine: d.display_name || `${lat.toFixed(6)}, ${lng.toFixed(6)}`
        };
    } catch(e) {
        return { lat, lng, label: `${lat.toFixed(6)}, ${lng.toFixed(6)}`, district:'Brazzaville', city:'Brazzaville', department:'Brazzaville', precisionLevel:'area', landmark:'', addressLine:`${lat.toFixed(6)}, ${lng.toFixed(6)}` };
    }
}

async function checkoutReverseGeocode(lat, lng) {
    if (_checkoutMapboxOk === false) return checkoutReverseGeocodeNominatim(lat, lng);
    try {
        const url = `https://api.mapbox.com/geocoding/v5/mapbox.places/${lng},${lat}.json?access_token=${CHECKOUT_MAPBOX_TOKEN}&limit=1&language=fr&types=address,poi,neighborhood,locality,place`;
        const response = await fetch(url);
        if (!response.ok) { _checkoutMapboxOk = false; return checkoutReverseGeocodeNominatim(lat, lng); }
        _checkoutMapboxOk = true;
        const data = await response.json().catch(() => ({}));
        if (data.features && data.features[0]) {
            return buildCheckoutAddressDetails(lat, lng, data.features[0]);
        }
    } catch (error) {
        _checkoutMapboxOk = false;
        return checkoutReverseGeocodeNominatim(lat, lng);
    }
    return { lat, lng, label: `${lat.toFixed(6)}, ${lng.toFixed(6)}`, district:'Brazzaville', city:'Brazzaville', department:'Brazzaville', precisionLevel:'area', landmark:'', addressLine:`${lat.toFixed(6)}, ${lng.toFixed(6)}` };
}

async function checkoutSearchNominatim(query, limit = 5) {
    try {
        const r = await fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query)}&countrycodes=cg&limit=${limit}&accept-language=fr&addressdetails=1`);
        const results = await r.json();
        return (results || []).map(function(r) {
            const addr = r.address || {};
            return {
                lat: parseFloat(r.lat), lng: parseFloat(r.lon),
                label: r.display_name,
                district: addr.suburb || addr.neighbourhood || addr.city_district || '',
                city: addr.city || addr.town || 'Brazzaville',
                department: addr.state || 'Brazzaville',
                precisionLevel: addr.road ? 'address' : 'area',
                landmark: addr.road || '',
                addressLine: r.display_name
            };
        });
    } catch(e) { return []; }
}

async function checkoutSearch(query, limit = 5) {
    if (_checkoutMapboxOk === false) return checkoutSearchNominatim(query, limit);
    try {
        let url = `https://api.mapbox.com/geocoding/v5/mapbox.places/${encodeURIComponent(query)}.json?access_token=${CHECKOUT_MAPBOX_TOKEN}&autocomplete=true&limit=${limit}&language=fr&country=cg&types=address,poi,neighborhood,locality,place`;
        const latitude = document.getElementById('latitude')?.value;
        const longitude = document.getElementById('longitude')?.value;
        if (latitude && longitude) {
            url += `&proximity=${longitude},${latitude}`;
        }
        const response = await fetch(url);
        if (!response.ok) { _checkoutMapboxOk = false; return checkoutSearchNominatim(query, limit); }
        _checkoutMapboxOk = true;
        const data = await response.json().catch(() => ({}));
        if (!data.features) return [];
        return data.features.map((feature) => {
            const [lng, lat] = feature.center;
            return buildCheckoutAddressDetails(lat, lng, feature);
        });
    } catch (error) {
        _checkoutMapboxOk = false;
        return checkoutSearchNominatim(query, limit);
    }
}

function renderCheckoutSuggestions(items) {
    const box = document.getElementById('deliverySuggestions');
    if (!box) return;
    box.innerHTML = '';
    if (!items.length) {
        box.classList.remove('is-visible');
        return;
    }
    items.forEach((item) => {
        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'checkout-suggestion-item';
        button.textContent = item.label;
        button.addEventListener('click', () => {
            applyCheckoutAddress(item);
            box.classList.remove('is-visible');
        });
        box.appendChild(button);
    });
    box.classList.add('is-visible');
}

function updateCheckoutStatus(message, isError = false) {
    const status = document.getElementById('deliveryMapStatus');
    if (!status) return;
    status.textContent = message;
    status.classList.toggle('is-error', isError);
}

function applyCheckoutAddress(item, options = {}) {
    document.getElementById('searchMapInput').value = item.label || item.addressLine || '';
    document.getElementById('deliveryDistrict').value = item.district || '';
    if (!document.getElementById('deliveryLandmark').value) {
        document.getElementById('deliveryLandmark').value = item.landmark || '';
    }
    document.getElementById('latitude').value = item.lat;
    document.getElementById('longitude').value = item.lng;
    if (savedAddressSelect && !options.keepSavedSelection) {
        savedAddressSelect.value = '';
    }
    if (savedAddressIdField && !options.keepSavedSelection) {
        savedAddressIdField.value = '';
    }
    if (checkoutMarker) {
        checkoutMarker.setLatLng([item.lat, item.lng]);
    } else if (checkoutMap) {
        checkoutMarker = L.marker([item.lat, item.lng], { draggable: true }).addTo(checkoutMap);
        checkoutMarker.on('dragend', async function(event) {
            const pos = event.target.getLatLng();
            const details = await checkoutReverseGeocode(pos.lat, pos.lng);
            applyCheckoutAddress(details, { confirmed: true, source: 'map' });
        });
    }
    if (checkoutMap) {
        checkoutMap.setView([item.lat, item.lng], 16);
    }

    const precisionLevel = item.precisionLevel || 'blind';
    const confirmed = options.confirmed === true
        ? true
        : (options.confirmed === false ? false : !isCheckoutAddressTooBroad(precisionLevel));

    setCheckoutAddressState({
        precisionLevel,
        confirmed,
        source: options.source || 'search',
        city: item.city || 'Brazzaville',
        department: item.department || inferDepartmentFromCity(item.city || 'Brazzaville') || 'Brazzaville',
    });

    if (isCheckoutAddressTooBroad(precisionLevel) && !confirmed) {
        updateCheckoutStatus('Adresse trouvée au niveau quartier. Placez maintenant le repère exact sur la carte.', true);
    } else {
        updateCheckoutStatus(item.district ? `Repère confirmé pour ${item.district}.` : 'Repère confirmé sur la carte.');
    }
}

function applySavedCheckoutAddress(option) {
    const lat = parseFloat(option.dataset.lat || CHECKOUT_DEFAULT_LOCATION.lat);
    const lng = parseFloat(option.dataset.lng || CHECKOUT_DEFAULT_LOCATION.lng);
    const addressLine = option.dataset.address || '';
    const district = option.dataset.area || '';
    const landmark = [option.dataset.building || '', option.dataset.street || '', option.dataset.floor || ''].filter(Boolean).join(' · ');
    const city = /pointe-noire/i.test(addressLine) ? 'Pointe-Noire' : 'Brazzaville';

    if (savedAddressIdField) {
        savedAddressIdField.value = option.value || '';
    }

    applyCheckoutAddress({
        label: [option.dataset.title || '', addressLine].filter(Boolean).join(' - '),
        addressLine: addressLine,
        district: district,
        landmark: landmark,
        city: city,
        department: inferDepartmentFromCity(city),
        precisionLevel: landmark || /(\d{1,5}|av\.|avenue|rue|bd\.|boulevard)/i.test(addressLine) ? 'street' : 'district',
        lat: Number.isFinite(lat) ? lat : CHECKOUT_DEFAULT_LOCATION.lat,
        lng: Number.isFinite(lng) ? lng : CHECKOUT_DEFAULT_LOCATION.lng,
    }, { confirmed: true, source: 'saved_address', keepSavedSelection: true });

    if (savedAddressSelect) {
        savedAddressSelect.value = option.value || '';
    }
    updateCheckoutStatus('Adresse enregistrée appliquée.');
}

function initMap() {
    const mapBox = document.getElementById('map');
    if (!mapBox) return;
    if (!CHECKOUT_MAPBOX_TOKEN) {
        mapBox.innerHTML = '<div class="co-map-fallback">Carte indisponible. Ajoutez MAPBOX_PUBLIC_TOKEN.</div>';
        return;
    }

    checkoutMap = L.map('map', { zoomControl: true }).setView([CHECKOUT_DEFAULT_LOCATION.lat, CHECKOUT_DEFAULT_LOCATION.lng], 13);
    L.tileLayer('https://api.mapbox.com/styles/v1/mapbox/streets-v12/tiles/{z}/{x}/{y}?access_token=' + CHECKOUT_MAPBOX_TOKEN, {
        tileSize: 512,
        zoomOffset: -1,
        attribution: '&copy; OpenStreetMap contributors &copy; Mapbox',
        maxZoom: 19
    }).addTo(checkoutMap);

    checkoutMarker = L.marker([CHECKOUT_DEFAULT_LOCATION.lat, CHECKOUT_DEFAULT_LOCATION.lng], { draggable: true }).addTo(checkoutMap);
    checkoutMarker.on('dragend', async function(event) {
        const pos = event.target.getLatLng();
        const details = await checkoutReverseGeocode(pos.lat, pos.lng);
        applyCheckoutAddress(details, { confirmed: true, source: 'map' });
    });

    checkoutMap.on('click', async function(event) {
        const details = await checkoutReverseGeocode(event.latlng.lat, event.latlng.lng);
        applyCheckoutAddress(details, { confirmed: true, source: 'map' });
    });

    const searchInput = document.getElementById('searchMapInput');
    searchInput?.addEventListener('input', function() {
        const query = this.value.trim();
        clearTimeout(checkoutSearchTimeout);
        if (query.length < 3) {
            document.getElementById('deliverySuggestions')?.classList.remove('is-visible');
            return;
        }
        checkoutSearchTimeout = setTimeout(async () => {
            const suggestions = await checkoutSearch(query);
            renderCheckoutSuggestions(suggestions);
        }, 250);
    });

    searchInput?.addEventListener('keydown', async function(event) {
        if (event.key !== 'Enter') return;
        event.preventDefault();
        const query = this.value.trim();
        if (query.length < 3) return;
        const result = await checkoutSearch(query, 1);
        if (result[0]) {
            applyCheckoutAddress(result[0], {
                confirmed: !isCheckoutAddressTooBroad(result[0].precisionLevel),
                source: 'search',
            });
            document.getElementById('deliverySuggestions')?.classList.remove('is-visible');
        }
    });

    document.getElementById('locateDeliveryBtn')?.addEventListener('click', function() {
        if (!navigator.geolocation) {
            updateCheckoutStatus('La géolocalisation n’est pas disponible sur cet appareil.', true);
            return;
        }
        updateCheckoutStatus('Localisation en cours...');
        navigator.geolocation.getCurrentPosition(async function(position) {
            const details = await checkoutReverseGeocode(position.coords.latitude, position.coords.longitude);
            applyCheckoutAddress(details, { confirmed: true, source: 'gps' });
        }, function() {
            updateCheckoutStatus('Impossible de récupérer votre position actuelle.', true);
        }, {
            enableHighAccuracy: true,
            timeout: 12000,
            maximumAge: 0
        });
    });

    document.getElementById('usePinBtn')?.addEventListener('click', function() {
        updateCheckoutStatus('Cliquez sur la carte pour placer le repère de livraison.');
    });

    if (savedAddressSelect) {
        savedAddressSelect.addEventListener('change', function() {
            const selected = this.selectedOptions && this.selectedOptions[0];
            if (!selected || !selected.value) {
                if (savedAddressIdField) {
                    savedAddressIdField.value = '';
                }
                return;
            }
            applySavedCheckoutAddress(selected);
        });
    }

    ['searchMapInput', 'deliveryDistrict', 'deliveryLandmark', 'deliveryComplement'].forEach((fieldId) => {
        const field = document.getElementById(fieldId);
        field?.addEventListener('input', function() {
            if (savedAddressSelect) {
                savedAddressSelect.value = '';
            }
            if (savedAddressIdField) {
                savedAddressIdField.value = '';
            }
            if (fieldId === 'searchMapInput' || fieldId === 'deliveryDistrict') {
                setCheckoutAddressState({
                    confirmed: false,
                    source: 'manual',
                    precisionLevel: fieldId === 'deliveryDistrict' ? 'district' : checkoutAddressState.precisionLevel,
                });
            }
        });
    });

    document.getElementById('clearDeliveryBtn')?.addEventListener('click', function() {
        document.getElementById('searchMapInput').value = '';
        document.getElementById('deliveryDistrict').value = '';
        document.getElementById('deliveryLandmark').value = '';
        document.getElementById('deliveryComplement').value = '';
        if (savedAddressSelect) {
            savedAddressSelect.value = '';
        }
        if (savedAddressIdField) {
            savedAddressIdField.value = '';
        }
        document.getElementById('latitude').value = CHECKOUT_DEFAULT_LOCATION.lat;
        document.getElementById('longitude').value = CHECKOUT_DEFAULT_LOCATION.lng;
        if (checkoutMarker) {
            checkoutMarker.setLatLng([CHECKOUT_DEFAULT_LOCATION.lat, CHECKOUT_DEFAULT_LOCATION.lng]);
        }
        checkoutMap.setView([CHECKOUT_DEFAULT_LOCATION.lat, CHECKOUT_DEFAULT_LOCATION.lng], 13);
        setCheckoutAddressState({
            precisionLevel: 'blind',
            confirmed: false,
            source: 'manual',
            city: 'Brazzaville',
            department: 'Brazzaville',
        });
        updateCheckoutStatus('Repère réinitialisé. Repositionnez la livraison sur la carte.');
    });

    document.addEventListener('click', function(event) {
        if (!event.target.closest('#searchMapInput') && !event.target.closest('#deliverySuggestions')) {
            document.getElementById('deliverySuggestions')?.classList.remove('is-visible');
        }
    });
}

window.handleCheckoutClientError = function(message, payload = null) {
    const normalized = String(message || '').toLowerCase();
    if (
        normalized.includes('confirmez precisement l adresse de livraison')
        || Boolean(payload?.errors?.delivery_address_confirmed)
    ) {
        updateCheckoutStatus('Confirmez precisement l adresse de livraison sur la carte avant de continuer.', true);
        updateCheckoutPrecisionAlert('Confirmez precisement l adresse de livraison sur la carte avant de continuer.', true);
        document.getElementById('usePinBtn')?.focus();
        return true;
    }
    return false;
};
</script>
<script>
window.addEventListener('load', function () {
    initMap();
    syncCheckoutAddressState();
    if (savedAddressSelect && savedAddressSelect.value) {
        savedAddressSelect.dispatchEvent(new Event('change'));
    }
    recalculateCheckoutTotals();
}, { once: true });
</script>
<script>
/* ── Checkout Wizard ── */
(function () {
    'use strict';
    var _step = 2;

    function validateStep(step) {
        if (step === 2) {
            if (typeof getFulfillmentMode === 'function' && getFulfillmentMode() === 'delivery') {
                var addr = (document.getElementById('searchMapInput') || {}).value || '';
                if (!addr.trim()) {
                    if (typeof showToast === 'function') showToast('Veuillez entrer une adresse de livraison.', 'error');
                    var f = document.getElementById('searchMapInput');
                    if (f) f.focus();
                    return false;
                }
                if (typeof isCheckoutAddressTooBroad === 'function'
                    && typeof checkoutAddressState !== 'undefined'
                    && isCheckoutAddressTooBroad(checkoutAddressState.precisionLevel)
                    && !checkoutAddressState.confirmed) {
                    if (typeof showToast === 'function') showToast('Précisez le repère sur la carte avant de continuer.', 'error');
                    return false;
                }
            }
        }
        if (step === 3) {
            var method = (document.querySelector('input[name="payment_method"]:checked') || {}).value;
            if (method === 'mobile_money') {
                var phone = ((document.getElementById('paymentPhone') || {}).value || '').trim();
                if (!phone) {
                    if (typeof showToast === 'function') showToast('Saisissez le numéro pour le paiement mobile.', 'error');
                    var pf = document.getElementById('paymentPhone');
                    if (pf) pf.focus();
                    return false;
                }
                if (typeof detectMobileMoneyOperator === 'function') {
                    var op = detectMobileMoneyOperator(phone);
                    if (op.operator === 'unknown') {
                        if (typeof showToast === 'function') showToast(op.label, 'error');
                        return false;
                    }
                }
            }
        }
        return true;
    }

    function updateStepper(step) {
        for (var i = 2; i <= 4; i++) {
            var node = document.getElementById('stepNode' + i);
            var text = document.getElementById('stepText' + i);
            if (!node) continue;
            var state = i < step ? 'done' : (i === step ? 'active' : 'idle');
            node.className = 'co-step-node co-step-node--' + state;
            if (text) text.className = 'co-step-text co-step-text--' + state;
            var checkEl = node.querySelector('.co-step-check');
            var numEl = node.querySelector('.co-step-num');
            if (checkEl) checkEl.style.display = i < step ? '' : 'none';
            if (numEl) numEl.style.display = i < step ? 'none' : '';
        }
        for (var j = 1; j <= 3; j++) {
            var wire = document.getElementById('stepWire' + j);
            if (wire) wire.className = 'co-step-wire co-step-wire--' + (j < step ? 'done' : 'idle');
        }
    }

    function updateMobileCta(step) {
        var btn = document.querySelector('.co-mcta-btn');
        if (!btn) return;
        btn.onclick = null;
        if (step === 4) {
            btn.textContent = 'Commander';
            btn.onclick = function () {
                var sb = document.getElementById('checkoutSubmitBtn');
                if (sb) sb.click();
            };
        } else if (step === 3) {
            btn.textContent = 'Vérifier la commande →';
            btn.onclick = function () { coGoToStep(4); };
        } else {
            btn.textContent = 'Continuer →';
            btn.onclick = function () { coGoToStep(_step + 1); };
        }
    }

    function goToStep(n) {
        if (n > _step && !validateStep(_step)) return;
        _step = n;
        document.querySelectorAll('.co-step-section').forEach(function (s) { s.classList.remove('is-active'); });
        var section = document.getElementById('coStep' + n);
        if (section) section.classList.add('is-active');
        var sidebar = document.querySelector('.co-sidebar');
        if (sidebar) sidebar.classList.toggle('is-step4-visible', n === 4);
        updateStepper(n);
        updateMobileCta(n);
        if (n === 2 && typeof checkoutMap !== 'undefined' && checkoutMap) {
            setTimeout(function () { checkoutMap.invalidateSize(); }, 250);
        }
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    window.coGoToStep = goToStep;

    /* Init after DOM ready */
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () { goToStep(2); });
    } else {
        goToStep(2);
    }
})();
</script>
@endsection
