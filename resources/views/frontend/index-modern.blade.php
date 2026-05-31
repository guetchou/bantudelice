@extends('frontend.layouts.app-modern')

@section('title', trans('ui.site.name') . ' — ' . trans('ui.site.subtitle'))
@section('description', trans('ui.home.hero_description'))

@php
    $foodBrandName = \App\Services\ConfigService::getCompanyName();
    $resolveHomeMedia = static function ($path, ?string $fallback = null) {
        if (blank($path)) {
            return $fallback;
        }

        $path = (string) $path;

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        return asset(ltrim($path, '/'));
    };
    $heroBackgroundImage = $resolveHomeMedia($homeContent['hero_main_image'] ?? null, asset('images/home/service-restaurant.jpg'));
    $heroBadge = $homeContent['hero_badge'] ?? trans('ui.home.hero_badge');
    $heroTitleLineOne = $homeContent['hero_title_line_1'] ?? trans('ui.home.hero_title_line_1');
    $heroTitleLineTwo = $homeContent['hero_title_line_2'] ?? trans('ui.home.hero_title_line_2');
    $heroDescription = $homeContent['hero_description'] ?? trans('ui.home.hero_description');
    $featuredRestaurants = collect($restaurants)->take(8);
    $featuredProducts = collect($productRecommendations)->take(4);
    if ($featuredProducts->isEmpty()) {
        $featuredProducts = collect($products)->take(8);
    }
    $marqueeRestaurants = $featuredRestaurants->pluck('name')->filter()->take(8);
    if ($marqueeRestaurants->isEmpty()) {
        $marqueeRestaurants = collect(['Africafe', 'Mami Wata', 'Nganda Ya Mboka', 'Espace Malebo']);
    }
    $homeTicker = [
        trans('ui.home.service_cards.restaurants.description'),
        $featuredRestaurants->count() . ' ' . trans('ui.nav.restaurants') . ' ' . trans('ui.common.results'),
        $featuredProducts->count() . ' ' . trans('ui.common.products_for_you'),
        'Livraison en 30–45 min · Brazzaville & Pointe-Noire',
        'Paiement Mobile Money · MTN MoMo · Airtel Money',
    ];
    $testimonialsFallback = [
        ['tag' => 'Commande rapide', 'quote' => 'Commandé depuis Bacongo, livré en 28 minutes. Le pondu était encore chaud à l\'arrivée. Je commande deux fois par semaine maintenant.', 'name' => 'Marie-Claire O.', 'loc' => 'Bacongo, Brazzaville'],
        ['tag' => 'Choix restaurant', 'quote' => 'Enfin un vrai choix de restaurants congolais en ligne. Le mwambé de chez Mama Wata est exactement comme en salle.', 'name' => 'Rodrigue M.', 'loc' => 'Moungali, Brazzaville'],
        ['tag' => 'Paiement mobile', 'quote' => 'Le paiement Mobile Money est fluide, pas de cash à préparer. Ma commande était confirmée avant que je ferme l\'application.', 'name' => 'Christelle B.', 'loc' => 'Pointe-Noire'],
    ];
    $socialLinks = [
        ['label' => 'Facebook',  'handle' => '@' . $foodBrandName, 'href' => 'https://www.facebook.com/BantuDelice',           'icon' => 'fab fa-facebook-f', 'tone' => 'facebook',  'is_external' => true],
        ['label' => 'Instagram', 'handle' => '@bantudelice.cg',  'href' => 'https://www.instagram.com/bantudelice.cg/',       'icon' => 'fab fa-instagram',  'tone' => 'instagram', 'is_external' => true],
        ['label' => 'WhatsApp',  'handle' => 'Support client',   'href' => route('contact.us'),                              'icon' => 'fab fa-whatsapp',   'tone' => 'whatsapp',  'is_external' => false],
        ['label' => 'TikTok',    'handle' => '@bantudelice',     'href' => 'https://www.tiktok.com/@bantudelice',             'icon' => 'fab fa-tiktok',     'tone' => 'tiktok',    'is_external' => true],
    ];
    $footerSocials = $socialLinks;
    $paymentMethods = ['Mobile Money', 'Airtel Money', 'MTN MoMo', 'Cash'];
    $opportunityFallbacks = [
        ['title' => 'Devenir coursier',   'body' => "Rejoignez le réseau {$foodBrandName} pour livrer des repas. Horaires flexibles, inscription rapide.", 'href' => route('driver'),     'cta' => 'Postuler',         'image' => asset('images/home/service-driver.jpg')],
        ['title' => 'Devenir partenaire', 'body' => "Restaurants et commerces : developpez votre visibilite et vos ventes sur la plateforme.",       'href' => route('partner'),    'cta' => "S'inscrire",       'image' => asset('images/home/service-restaurant.jpg')],
        ['title' => 'Rejoindre l\'equipe','body' => "Vous souhaitez rejoindre {$foodBrandName} pour un poste operationnel ou support ? Contactez-nous.", 'href' => route('contact.us'), 'cta' => 'Nous contacter',   'image' => asset('images/home/service-transport.jpg')],
    ];
    $testimonials = collect([1, 2, 3])->map(function ($i) use ($homeContent, $testimonialsFallback) {
        $f = $testimonialsFallback[$i - 1];
        return [
            'tag'   => $homeContent['testimonial_' . $i . '_tag']   ?? $f['tag'],
            'quote' => $homeContent['testimonial_' . $i . '_quote'] ?? $f['quote'],
            'name'  => $homeContent['testimonial_' . $i . '_name']  ?? $f['name'],
            'loc'   => $homeContent['testimonial_' . $i . '_loc']   ?? $f['loc'],
        ];
    })->all();
    $opportunityCards = collect([1, 2, 3])->map(function ($i) use ($homeContent, $opportunityFallbacks, $resolveHomeMedia) {
        $f = $opportunityFallbacks[$i - 1];
        $image = $resolveHomeMedia($homeContent['opportunity_' . $i . '_image'] ?? null, $f['image']);
        return ['title' => $homeContent['opportunity_' . $i . '_title'] ?? $f['title'], 'body' => $homeContent['opportunity_' . $i . '_body'] ?? $f['body'], 'href' => $homeContent['opportunity_' . $i . '_url'] ?? $f['href'], 'cta' => $homeContent['opportunity_' . $i . '_cta'] ?? $f['cta'], 'image' => $image];
    })->all();
    $congoDepartments = [
        ['name' => 'Sangha','x' => '58%','y' => '11%'],['name' => 'Likouala','x' => '71%','y' => '16%'],
        ['name' => 'Cuvette','x' => '53%','y' => '25%'],['name' => 'Nkeni-Alima','x' => '66%','y' => '29%'],
        ['name' => 'Cuvette-Ouest','x' => '38%','y' => '31%'],['name' => 'Plateaux','x' => '57%','y' => '42%'],
        ['name' => 'Djoue-Lefini','x' => '49%','y' => '53%'],['name' => 'Brazzaville','x' => '58%','y' => '59%'],
        ['name' => 'Pool','x' => '46%','y' => '63%'],['name' => 'Congo-Oubangui','x' => '71%','y' => '56%'],
        ['name' => 'Bouenza','x' => '41%','y' => '73%'],['name' => 'Lekoumou','x' => '55%','y' => '76%'],
        ['name' => 'Niari','x' => '31%','y' => '81%'],['name' => 'Kouilou','x' => '22%','y' => '92%'],
        ['name' => 'Pointe-Noire','x' => '39%','y' => '95%'],
    ];
    $accountLabel = auth()->check() ? trans('ui.nav.account') : trans('ui.nav.login');
    $accountLink  = auth()->check() ? route('user.profile') : route('login');
    $foodEnabled      = (bool) config('bantudelice_modules.food.enabled',      true);
    $colisEnabled     = (bool) config('bantudelice_modules.colis.enabled',     true);
    $transportEnabled = (bool) config('bantudelice_modules.transport.enabled', true);
    $ecosystemPlatforms = collect(config('sites.ecosystem', []))
        ->filter(fn ($platform) => filled($platform['url'] ?? null))
        ->map(function ($platform) use ($transportEnabled, $colisEnabled) {
            $name = trim((string) ($platform['name'] ?? ''));

            if ($name === 'Transport' && $transportEnabled) {
                $platform['url'] = route('transport.index');
            }

            if (in_array($name, ['Colis', 'Mema'], true) && $colisEnabled) {
                $platform['url'] = route('colis.landing');
                $platform['name'] = 'Mema';
            }

            return $platform;
        })
        ->values();
    if ($ecosystemPlatforms->isEmpty()) {
        $ecosystemPlatforms = collect([
            [
                'name'        => 'Transport',
                'description' => 'Réservation de taxi à Brazzaville et Pointe-Noire. Tarif affiché avant confirmation.',
                'icon'        => 'fa-car',
                'badge'       => 'Disponible',
                'url'         => $transportEnabled ? route('transport.index') : '#',
            ],
            [
                'name'        => 'Mema',
                'description' => 'Envoi et livraison de colis partout au Congo. Suivi en temps réel.',
                'icon'        => 'fa-box',
                'badge'       => 'Disponible',
                'url'         => $colisEnabled ? route('colis.landing') : '#',
            ],
            [
                'name'        => 'Salisa',
                'description' => 'Plateforme de services freelance au Congo. Trouvez un expert ou proposez vos compétences.',
                'icon'        => 'fa-briefcase',
                'badge'       => 'Bientôt',
                'url'         => '#',
            ],
            [
                'name'        => 'Kosunga',
                'description' => 'Rendez-vous médicaux et téléconsultation. Prenez soin de votre santé en ligne.',
                'icon'        => 'fa-stethoscope',
                'badge'       => 'Bientôt',
                'url'         => '#',
            ],
        ]);
    }
@endphp

@section('body_class', 'bd-home-modern')
@section('hide_primary_chrome', '1')
@section('hide_layout_footer', '1')

@section('content')
<div id="bdHomeCursor">
  <div id="bdHomeCursorDot"></div>
  <div id="bdHomeCursorRing"></div>
</div>

<div class="scroll-prog" id="scrollProg"></div>

<div class="bd2">

{{-- ── Ticker ────────────────────────────────────────────────── --}}
<div class="ticker2">
  <div class="ticker2__track" id="tickerTrack">
    @foreach($marqueeRestaurants as $rn)
      <span class="ticker2__item">{{ $rn }}</span>
    @endforeach
    <span class="ticker2__item">Commandez maintenant</span>
    <span class="ticker2__item">Livraison en 20–40 min</span>
    <span class="ticker2__item">Brazzaville · Pointe-Noire</span>
    @foreach($marqueeRestaurants as $rn)
      <span class="ticker2__item">{{ $rn }}</span>
    @endforeach
    <span class="ticker2__item">Commandez maintenant</span>
    <span class="ticker2__item">Livraison en 20–40 min</span>
    <span class="ticker2__item">Brazzaville · Pointe-Noire</span>
  </div>
</div>

{{-- ── Navigation ───────────────────────────────────────────── --}}
<nav class="nav2" id="mainNav">
  <a href="{{ route('home') }}" class="nav2__brand">
    <img src="{{ asset('frontend/images/BuntuDelice.png') }}" alt="{{ $foodBrandName }}" class="nav2__logo"
         onerror="this.style.display='none';this.nextElementSibling.style.display='inline'">
    <span style="display:none" class="nav2__brand-name">{{ $foodBrandName }}</span>
  </a>
  <div class="nav2__links">
    @if($foodEnabled)
      <a href="{{ route('restaurants.all') }}" class="nav2__link">Restaurants</a>
    @endif
    <a href="{{ route('offers') }}" class="nav2__link">Offres</a>
    <a href="{{ route('partner') }}" class="nav2__link">Partenaires</a>
  </div>
  <div class="nav2__actions">
    <a href="{{ route('search') }}" class="nav2__search" aria-label="{{ trans('ui.common.search') }}">
      <span>{{ trans('ui.home.search_placeholder') }}</span>
    </a>
    @if($foodEnabled)
    <a href="{{ route('cart') }}" class="nav2__cart" aria-label="Panier">
      <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="8" cy="21" r="1"/><circle cx="19" cy="21" r="1"/><path d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h9.78a2 2 0 0 0 1.95-1.57l1.65-7.43H5.12"/></svg>
    </a>
    @endif
    <a href="{{ $accountLink }}" class="btn-green">{{ $accountLabel }}</a>
    <button class="nav2__mobile-toggle" type="button" aria-label="Menu" id="mobileNavToggle">
      <i class="fas fa-bars"></i>
    </button>
  </div>
</nav>

{{-- ── Mobile drawer ─────────────────────────────────────────── --}}
<div class="nav2__drawer" id="mobileNavDrawer" aria-hidden="true">
  <div class="nav2__drawer-inner">
    @if($foodEnabled)
      <a href="{{ route('restaurants.all') }}" class="nav2__drawer-link">Restaurants</a>
    @endif
    <a href="{{ route('offers') }}" class="nav2__drawer-link">Offres</a>
    <a href="{{ route('partner') }}" class="nav2__drawer-link">Partenaires</a>
    @if($foodEnabled)
      <a href="{{ route('cart') }}" class="nav2__drawer-link"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" style="vertical-align:-2px;margin-right:4px"><circle cx="8" cy="21" r="1"/><circle cx="19" cy="21" r="1"/><path d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h9.78a2 2 0 0 0 1.95-1.57l1.65-7.43H5.12"/></svg> Panier</a>
    @endif
    <a href="{{ $accountLink }}" class="nav2__drawer-link nav2__drawer-link--cta">{{ $accountLabel }}</a>
  </div>
</div>

{{-- ── HERO ──────────────────────────────────────────────────── --}}
@if($foodEnabled)
<section class="bd-hero" id="hero" style="background-image:url('{{ $heroBackgroundImage }}')">
  <div class="bd-hero__overlay"></div>
  <div class="bd-hero__inner">

    {{-- Eyebrow --}}
    <div class="bd-hero__eyebrow">
      <span class="bd-hero__dot"></span>
      {{ $heroBadge }}
    </div>

    {{-- Titre --}}
    <h1 class="bd-hero__h1">
      {{ $heroTitleLineOne }}<br>
      <span class="bd-hero__h1-em">{{ $heroTitleLineTwo }}</span>
    </h1>

    {{-- Barre de recherche géolocalisée --}}
    <form class="bd-hero__bar" action="{{ route('restaurants.all') }}" method="GET" id="heroOrderForm" autocomplete="off">
      <input type="hidden" name="lat" id="heroLat">
      <input type="hidden" name="lng" id="heroLng">

      {{-- Zone gauche : icône GPS + champ --}}
      <div class="bd-hero__bar-field">
        <button type="button" id="heroGpsBtn" class="bd-hero__gps" aria-label="Me localiser">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <circle cx="12" cy="12" r="3"/>
            <path d="M12 2v2M12 20v2M2 12h2M20 12h2"/>
            <path d="M12 7a5 5 0 1 0 0 10A5 5 0 0 0 12 7z"/>
          </svg>
          <span class="bd-hero__gps-label" id="heroGpsLabel">Ma position</span>
        </button>
        <div class="bd-hero__bar-sep"></div>
        <input
          type="text"
          name="search"
          id="heroAddressInput"
          class="bd-hero__input"
          placeholder="Quartier, avenue, restaurant…"
          aria-label="Zone ou restaurant"
        >
      </div>

      {{-- CTA --}}
      <button type="submit" class="bd-hero__cta">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
        Trouver un restaurant
      </button>
    </form>

    {{-- Status géoloc --}}
    <p class="bd-hero__geo-status" id="heroHint">
      <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
      Brazzaville &amp; Pointe-Noire &nbsp;·&nbsp; {{ $featuredRestaurants->count() }} restaurants &nbsp;·&nbsp; ~30 min
    </p>

  </div>
</section>
@endif

{{-- ── Bandeau confiance paiement ─────────────────────────────── --}}
<div class="trust-band">
  <div class="trust-band__inner">
    <span class="trust-band__label">Paiements acceptés</span>
    <div class="trust-band__sep"></div>
    <div class="trust-band__items">
      <span class="trust-pill">
        <span class="trust-pill__dot mtn"></span>
        MTN MoMo
      </span>
      <span class="trust-pill">
        <span class="trust-pill__dot airtel"></span>
        Airtel Money
      </span>
      <span class="trust-pill">
        <span class="trust-pill__dot cash"></span>
        Mobile Money
      </span>
      <span class="trust-pill">
        <span class="trust-pill__dot cash"></span>
        Cash à la livraison
      </span>
      <span class="trust-pill">
        <span class="trust-pill__dot secure"></span>
        Paiement sécurisé
      </span>
    </div>
  </div>
</div>

{{-- Section "Trois services" retirée — homepage food-only. Mema et Transport ont leurs propres pages. --}}

{{-- ── Restaurants populaires ────────────────────────────────── --}}
@if($foodEnabled)
<section class="sec" id="restos">
  <div class="mx">
    <div class="sec__head rev">
      <div>
        <div class="sec__tag">
          <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
          {{ $homeContent['restaurants_tag'] ?? 'Nos partenaires' }}
        </div>
        <h2>{{ $homeContent['restaurants_title'] ?? 'Restaurants populaires' }}</h2>
        @if(!empty($homeContent['restaurants_subtitle']))
          <p class="sec__sub">{{ $homeContent['restaurants_subtitle'] }}</p>
        @endif
      </div>
      <a href="{{ route('restaurants.all') }}" class="sec__more">
        Voir tout <i class="fas fa-arrow-right fa-xs"></i>
      </a>
    </div>
    <div class="resto-grid">
      @forelse($featuredRestaurants as $restaurant)
        @php
          $deliveryFee = number_format((float)($restaurant->delivery_charges ?? 0), 0, ',', ' ');
          $rating      = number_format((float)($restaurant->avg_rating ?? 4.0), 1);
          $logo        = method_exists($restaurant, 'publicIdentityImageUrl')
            ? $restaurant->publicIdentityImageUrl()
            : ($restaurant->logo ? (strpos($restaurant->logo, 'http') === 0 ? $restaurant->logo : asset('images/restaurant_images/' . $restaurant->logo)) : asset('images/home/service-restaurant.jpg'));
          $cuisines    = $restaurant->cuisines->pluck('name')->take(3)->implode(' · ') ?: 'Cuisine congolaise';
        @endphp
        <article class="rc rev">
          <div class="rc__img">
            <img src="{{ $logo }}" alt="{{ $restaurant->name }}"
                 loading="lazy"
                 onerror="this.src='{{ asset('images/home/service-restaurant.jpg') }}'">
            <span class="rc__badge">Populaire</span>
            <span class="rc__time">20–40 min</span>
          </div>
          <div class="rc__body">
            <div class="rc__cat">{{ $cuisines }}</div>
            <div class="rc__name">{{ $restaurant->name }}</div>
            <div class="rc__meta">
              <span class="rc__stars">★ {{ $rating }}</span>
              <span>·</span>
              <span>{{ $deliveryFee }} FCFA livraison</span>
            </div>
            <a href="{{ route('restaurant.detail', $restaurant->id) }}" class="rc__btn">Commander</a>
          </div>
        </article>
      @empty
        <article class="rc">
          <div class="rc__body" style="padding:24px">
            <div class="rc__name">Restaurants bientôt disponibles</div>
          </div>
        </article>
      @endforelse
    </div>
  </div>
</section>
@endif

{{-- ── Plats a decouvrir ─────────────────────────────────────── --}}
@if($foodEnabled)
<section class="sec bg2" id="plats">
  <div class="mx">
    <div class="sec__head rev">
      <div>
        <div class="sec__tag">
          <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M8.5 14.5A2.5 2.5 0 0 0 11 12c0-1.38-.5-2-1-3-1.072-2.143-.224-4.054 2-6 .5 2.5 2 4.9 4 6.5 2 1.6 3 3.5 3 5.5a7 7 0 0 1-7 7 7 7 0 0 1-7-7c0-1.53.4-2.97 1.1-4.2A4.5 4.5 0 0 0 8.5 14.5z"/></svg>
          {{ $homeContent['popular_products_tag'] ?? 'Sélection du moment' }}
        </div>
        <h2>{{ $homeContent['popular_products_title'] ?? 'Plats à découvrir' }}</h2>
      </div>
      <a href="{{ route('restaurants.all') }}" class="sec__more">
        Explorer <i class="fas fa-arrow-right fa-xs"></i>
      </a>
    </div>
    <div class="prod-grid">
      @foreach($featuredProducts as $product)
        @php
          $image = method_exists($product, 'publicImageUrl')
            ? $product->publicImageUrl()
            : ($product->image ? (strpos($product->image, 'http') === 0 ? $product->image : asset('images/product_images/' . $product->image)) : asset('images/product_images/default-food.jpg'));
          $price = number_format((float)(($product->discount_price ?? 0) > 0 ? $product->discount_price : $product->price), 0, ',', ' ');
        @endphp
        <article class="pc rev">
          <div class="pc__img">
            <img src="{{ $image }}" alt="{{ $product->name }}"
                 loading="lazy"
                 onerror="this.src='{{ asset('images/product_images/default-food.jpg') }}'">
            <span class="pc__price">{{ $price }} FCFA</span>
          </div>
          <div class="pc__body">
            <div class="pc__name">{{ $product->name }}</div>
            <div class="pc__desc">{{ \Illuminate\Support\Str::limit($product->description ?: ('Plat recommande sur ' . $foodBrandName . '.'), 90) }}</div>
            <a href="{{ route('pro.detail', $product->id) }}" class="pc__btn">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
              Commander
            </a>
          </div>
        </article>
      @endforeach
    </div>
  </div>
</section>
@endif

{{-- ── Comment ça marche — schéma horizontal compact ─────────── --}}
@if($foodEnabled)
<section class="sec how-sec" id="journey">
  <div class="mx">
    <div class="sec__head rev" style="margin-bottom:48px">
      <div>
        <div class="sec__tag">
          <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
          {{ $homeContent['how_it_works_tag'] ?? 'Comment ça marche' }}
        </div>
        <h2>Commandé, livré en <em>4 étapes</em></h2>
        <p class="sec__sub">De votre écran à votre porte, en moins de 30 minutes.</p>
      </div>
    </div>

    {{-- Schéma linéaire 4 étapes --}}
    <div class="how-flow">
      <div class="how-step rev" style="transition-delay:.0s">
        <div class="how-step__icon">
          <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
        </div>
        <div class="how-step__num">01</div>
        <div class="how-step__title">{{ $homeContent['step_1_title'] ?? 'Choisissez' }}</div>
        <div class="how-step__body">{{ $homeContent['step_1_body'] ?? 'Parcourez restaurants et menus près de chez vous.' }}</div>
      </div>
      <div class="how-flow__arrow" aria-hidden="true">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
      </div>
      <div class="how-step rev" style="transition-delay:.08s">
        <div class="how-step__icon">
          <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
        </div>
        <div class="how-step__num">02</div>
        <div class="how-step__title">{{ $homeContent['step_2_title'] ?? 'Composez' }}</div>
        <div class="how-step__body">{{ $homeContent['step_2_body'] ?? 'Ajoutez vos plats, confirmez votre adresse.' }}</div>
      </div>
      <div class="how-flow__arrow" aria-hidden="true">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
      </div>
      <div class="how-step rev" style="transition-delay:.16s">
        <div class="how-step__icon">
          <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect width="20" height="14" x="2" y="5" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg>
        </div>
        <div class="how-step__num">03</div>
        <div class="how-step__title">{{ $homeContent['step_3_title'] ?? 'Payez' }}</div>
        <div class="how-step__body">{{ $homeContent['step_3_body'] ?? 'MoMo, Airtel Money, Mobile Money ou cash.' }}</div>
      </div>
      <div class="how-flow__arrow" aria-hidden="true">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
      </div>
      <div class="how-step how-step--last rev" style="transition-delay:.24s">
        <div class="how-step__icon">
          <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="1" y="3" width="15" height="13" rx="2"/><path d="M16 8h4l3 5v3h-7V8z"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>
        </div>
        <div class="how-step__num">04</div>
        <div class="how-step__title">{{ $homeContent['step_4_title'] ?? 'Recevez' }}</div>
        <div class="how-step__body">{{ $homeContent['step_4_body'] ?? 'Votre livreur arrive à votre porte en ~30 min.' }}</div>
        <span class="how-step__badge">~30 min</span>
      </div>
    </div>

    <div class="how-cta">
      <a href="{{ route('restaurants.all') }}" class="btn-green">Commander maintenant</a>
    </div>
  </div>
</section>
@endif

{{-- ── Food gallery / orbs ───────────────────────────────────── --}}
@if($foodEnabled)
<div class="food-gallery-sec">
  <div class="food-gallery-inner mx rev">
    <div class="food-gallery-text">
      <div class="food-gallery-badge">Fait avec passion</div>
      <div class="sec__tag" style="margin-top:14px">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 2v7c0 1.1.9 2 2 2h4a2 2 0 0 0 2-2V2"/><path d="M7 2v20"/><path d="M21 15V2v0a5 5 0 0 0-5 5v6c0 1.1.9 2 2 2h3Zm0 0v7"/></svg>
        Notre cuisine
      </div>
      <h2 style="font-size:clamp(1.6rem,3vw,2.4rem);font-weight:800;line-height:1.15;color:var(--tx);margin:12px 0">
        Des saveurs <em style="font-style:normal;color:var(--g)">authentiques</em><br>livrées chez vous
      </h2>
      <p>Chaque restaurant partenaire est sélectionné pour la qualité de ses plats. Cuisine locale, grillades, plats mijotés — la richesse gastronomique du Congo dans votre assiette.</p>
      <a href="{{ route('restaurants.all') }}" class="btn-green" style="width:fit-content">Explorer les restaurants</a>
    </div>
    <div class="food-orbs">
      @php
        $orbImages = [
          asset('images/home/service-restaurant.jpg'),
          asset('images/home/service-driver.jpg'),
          asset('images/home/service-restaurant.jpg'),
          asset('images/home/service-cuisine.jpg'),
        ];
        $ri = 0;
        foreach($featuredRestaurants->take(4) as $ro) {
          $rmedia = method_exists($ro, 'publicIdentityImageUrl')
            ? $ro->publicIdentityImageUrl()
            : ($ro->logo ? (strpos($ro->logo,'http')===0 ? $ro->logo : asset('images/restaurant_images/'.$ro->logo)) : null);
          if($rmedia) $orbImages[$ri] = $rmedia;
          $ri++;
        }
      @endphp
      <div class="food-orb fo-1">
        <img src="{{ $orbImages[0] }}" alt="Plat cuisiné" loading="lazy" onerror="this.src='{{ asset('images/home/service-restaurant.jpg') }}'">
      </div>
      <div class="food-orb fo-2">
        <img src="{{ $orbImages[1] }}" alt="Plat en sauce" loading="lazy" onerror="this.src='{{ asset('images/home/service-driver.jpg') }}'">
      </div>
      <div class="food-orb fo-3">
        <img src="{{ $orbImages[2] }}" alt="Table garnie" loading="lazy" onerror="this.src='{{ asset('images/home/service-restaurant.jpg') }}'">
      </div>
      <div class="food-orb fo-4">
        <img src="{{ $orbImages[3] }}" alt="Plat gastronomique" loading="lazy" onerror="this.src='{{ asset('images/home/service-cuisine.jpg') }}'">
      </div>
    </div>
  </div>
</div>
@endif

{{-- ── Impact band ───────────────────────────────────────────── --}}
<div class="impact-band">
  <div class="impact-inner">
    <div class="impact-left rev-l">
      <h2>La livraison <em>congolaise</em><br>à la hauteur de vos attentes</h2>
      <p>{{ $foodBrandName }} connecte les meilleurs restaurants de Brazzaville et Pointe-Noire à des milliers de clients. Chaque commande est suivie en temps réel, chaque livreur est formé, chaque plat arrive chaud.</p>
      <a href="{{ route('restaurants.all') }}" class="btn-green" style="width:fit-content;padding:14px 32px">Commander maintenant</a>
    </div>
    @php
      $avgRatingAll = $featuredRestaurants->avg(fn($r) => (float)($r->ratings_avg_rating ?? $r->avg_rating ?? 0));
      $avgRatingDisplay = $avgRatingAll > 0 ? number_format($avgRatingAll, 1) . '★' : null;
    @endphp
    <div class="impact-stats rev">
      <div class="istat">
        <div class="istat__num" id="statResto">{{ $featuredRestaurants->count() }}+</div>
        <div class="istat__lbl">Restaurants partenaires</div>
      </div>
      <div class="istat">
        <div class="istat__num">~30 min</div>
        <div class="istat__lbl">Délai moyen de livraison</div>
      </div>
      <div class="istat">
        <div class="istat__num">2</div>
        <div class="istat__lbl">Villes actives au Congo</div>
      </div>
      @if($avgRatingDisplay)
      <div class="istat">
        <div class="istat__num">{{ $avgRatingDisplay }}</div>
        <div class="istat__lbl">Satisfaction client moyenne</div>
      </div>
      @endif
    </div>
    {{-- Mosaïque photos --}}
    <div class="impact-mosaic rev">
      <div class="impact-mosaic__img">
        <img src="{{ $resolveHomeMedia($homeContent['mosaic_cuisine_image'] ?? null, asset('images/home/service-cuisine.jpg')) }}" alt="Cuisine congolaise" loading="lazy">
      </div>
      <div class="impact-mosaic__img">
        <img src="{{ $resolveHomeMedia($homeContent['mosaic_driver_image'] ?? null, asset('images/home/service-driver.jpg')) }}" alt="Livraison à domicile" loading="lazy">
      </div>
      <div class="impact-mosaic__img">
        <img src="{{ $resolveHomeMedia($homeContent['mosaic_restaurant_image'] ?? null, asset('images/home/service-restaurant.jpg')) }}" alt="Restaurant partenaire" loading="lazy">
      </div>
    </div>
  </div>
</div>

{{-- ── Avis clients ──────────────────────────────────────────── --}}
<section class="sec bg2" id="testi">
  <div class="mx">
    <div class="sec__head">
      <div>
        <div class="sec__tag">
          <svg width="13" height="13" viewBox="0 0 24 24" fill="currentColor" stroke="none" aria-hidden="true"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
          {{ $homeContent['testimonials_tag'] ?? 'Avis clients' }}
        </div>
        <h2>{!! $homeContent['testimonials_title'] ?? 'Ils nous <em>font confiance</em>' !!}</h2>
        <p class="sec__sub">Vérifié par nos clients à Brazzaville et Pointe-Noire</p>
        {{-- Score agrégé type industriel --}}
        <div class="testi-score-band">
          <div>
            <div class="testi-score__num">4.7</div>
            <div class="testi-score__stars">★★★★★</div>
            <div class="testi-score__label">Note moyenne</div>
          </div>
          <div class="testi-score__sep"></div>
          <div class="testi-platform">
            <div class="testi-platform__name">Google Avis</div>
            <div class="testi-platform__count">Brazzaville · Pointe-Noire</div>
          </div>
          <div class="testi-score__sep"></div>
          <div class="testi-platform">
            <div class="testi-platform__name">+{{ $featuredRestaurants->count() }} restaurants</div>
            <div class="testi-platform__count">partenaires vérifiés</div>
          </div>
          <div class="testi-score__sep"></div>
          <div class="testi-platform">
            <div class="testi-platform__name">Livraison ~30 min</div>
            <div class="testi-platform__count">délai moyen constaté</div>
          </div>
        </div>
      </div>
    </div>
    <div class="testi-grid">
      @foreach($testimonials as $idx => $item)
        @php $initials = mb_strtoupper(mb_substr($item['name'],0,2)); @endphp
        <article class="tc {{ $idx === 1 ? 'tc--feat' : '' }}" style="animation:fadeUp .5s ease both;animation-delay:{{ $idx * 0.15 }}s">
          <span class="tc__stars">★★★★★</span>
          <div class="tc__sep"></div>
          <span class="tc__tag">{{ $item['tag'] }}</span>
          <div class="tc__quote">"{{ $item['quote'] }}"</div>
          <div class="tc__author">
            <div class="tc__avatar">{{ $initials }}</div>
            <div>
              <div class="tc__name">{{ $item['name'] }}</div>
              <div class="tc__loc">
                <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" style="display:inline-block;vertical-align:middle;margin-right:3px"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                {{ $item['loc'] }}
              </div>
            </div>
          </div>
        </article>
      @endforeach
    </div>
  </div>
</section>

{{-- Écosystème hub retiré — homepage food-only. Mema/Transport/Salisa/Kosunga ont leurs propres pages. --}}

{{-- ── Opportunités ──────────────────────────────────────────── --}}
<section class="sec" id="opportunities">
  <div class="mx">
    <div class="opp-intro rev">
      <div class="sec__tag" style="justify-content:center">{{ $homeContent['opportunities_tag'] ?? 'Opportunités' }}</div>
      <h2>{{ $homeContent['opportunities_title'] ?? ('Grandissez avec ' . $foodBrandName) }}</h2>
      <p>{{ $homeContent['opportunities_subtitle'] ?? ("Que vous soyez coursier, enseigne ou candidat, {$foodBrandName} ouvre des relais de croissance concrets au Congo.") }}</p>
    </div>
    <div class="opp-grid">
      @php
        $oppFallbackImages = [
          asset('images/home/service-driver.jpg'),
          asset('images/home/service-restaurant.jpg'),
          asset('images/home/service-transport.jpg'),
        ];
      @endphp
      @foreach($opportunityCards as $idx => $card)
        <article class="oc rev">
          <div class="oc__img">
            <img src="{{ $card['image'] }}"
                 alt="{{ $card['title'] }}"
                 loading="lazy"
                 onerror="this.onerror=null;this.src='{{ $oppFallbackImages[$idx] ?? asset('images/home/service-restaurant.jpg') }}'">
          </div>
          <div class="oc__body">
            <div class="oc__title">{{ $card['title'] }}</div>
            <p class="oc__txt">{{ $card['body'] }}</p>
            <a href="{{ $card['href'] }}" class="oc__cta">
              {{ $card['cta'] }} <i class="fas fa-arrow-right fa-xs"></i>
            </a>
          </div>
        </article>
      @endforeach
    </div>
  </div>
</section>

{{-- ── Carte du Congo ────────────────────────────────────────── --}}
<section class="sec congo-section" id="congo">
  <div class="mx rev">
    <div class="congo-head">
      <div>
        <div class="sec__tag">Couverture nationale</div>
        <h2>Présence au Congo</h2>
      </div>
      <span class="congo-meta">15 départements</span>
    </div>
    <div class="congo-wrap">
      <div class="congo-map">
        <div class="congo-map__frame">
          <iframe
            title="Carte OpenStreetMap de couverture {{ $foodBrandName }} au Congo"
            src="https://www.openstreetmap.org/export/embed.html?bbox=11.02%2C-5.25%2C18.95%2C3.90&amp;layer=mapnik&amp;marker=-4.2634%2C15.2429"
            loading="lazy">
          </iframe>
        </div>
        <div class="congo-map__panel">
          <div class="congo-map__eyebrow">Repères de couverture</div>
          <div class="congo-map__title">Deux zones actives, extension progressive</div>
          <p class="congo-map__text">
            Le service est opéré d'abord depuis Brazzaville et Pointe-Noire. Les autres départements relèvent d'une montée progressive selon les partenaires et modules actifs.
          </p>
          <div class="congo-zones">
            <div class="congo-zone">
              <strong>Brazzaville</strong>
              <span>Zone principale de livraison repas, restauration partenaire et support client.</span>
            </div>
            <div class="congo-zone">
              <strong>Pointe-Noire</strong>
              <span>Deuxième pôle actif pour la restauration, les commandes et les livraisons.</span>
            </div>
            <div class="congo-zone">
              <strong>Extension nationale</strong>
              <span>Le reste du territoire est couvert selon la montée du réseau, des partenaires et des modules déployés.</span>
            </div>
          </div>
        </div>
      </div>
      <div class="congo-foot">
        <span class="congo-foot__note">Lecture synthétique de la couverture actuelle de la plateforme.</span>
        <span class="congo-foot__tag">Couverture nationale</span>
      </div>
    </div>
  </div>
</section>

{{-- ── Application mobile ────────────────────────────────────── --}}
<section class="sec" id="app">
  <div class="mx">
    <div class="app2__grid rev">
      <div class="app2__phones">
        <div class="app2__phone">
          <div class="app2__phone-h">
            <strong>{{ $foodBrandName }}</strong>
            <i class="fas fa-bars" style="color:var(--tx3)"></i>
          </div>
          <div class="app2__phone-b">
            <div class="app2__mini">Rechercher un restaurant ou un plat…</div>
            @foreach($featuredRestaurants->take(3) as $r)
              <div class="app2__mini">{{ $r->name }} · {{ number_format((float)($r->delivery_charges ?? 0), 0, ',', ' ') }} FCFA</div>
            @endforeach
            <div class="btn-green" style="min-height:38px;padding:0 14px;font-size:.82rem;border-radius:8px;display:flex;align-items:center;justify-content:center;gap:6px;">
              <i class="fas fa-bowl-food fa-xs"></i> Commander
            </div>
          </div>
        </div>
        <div class="app2__phone sm">
          <div class="app2__phone-h">
            <strong>Suivi repas</strong>
            <i class="fas fa-clock" style="color:var(--or)"></i>
          </div>
          <div class="app2__phone-b">
            <div class="app2__mini">En préparation · 12 min</div>
            <div class="app2__mini">Livreur assigné · en route</div>
            <div class="app2__mini" style="height:72px;display:flex;align-items:center;justify-content:center;">
              <i class="fas fa-map-location-dot fa-2x" style="color:var(--g);opacity:.4"></i>
            </div>
          </div>
        </div>
      </div>
      <div class="app2__text">
        <div class="sec__tag">
          <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="5" y="2" width="14" height="20" rx="2" ry="2"/><line x1="12" y1="18" x2="12.01" y2="18"/></svg>
          Site mobile
        </div>
        <h2>{{ $foodBrandName }},<br>partout au <em>Congo.</em></h2>
        <p>Commandez et suivez votre livraison depuis votre téléphone. Le site est optimisé mobile — aucune installation requise, disponible 7j/7.</p>
        <div class="app2__stores">
          <a href="{{ route('restaurants.all') }}" class="app2__store" style="text-decoration:none">
            <i class="fas fa-globe"></i> Commander en ligne
          </a>
        </div>
        <div class="app2__feats">
          <div class="app2__feat">
            <span class="app2__check">
              <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>
            </span>Suivi GPS de votre commande en temps réel
          </div>
          <div class="app2__feat">
            <span class="app2__check">
              <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>
            </span>Notifications à chaque étape de la livraison
          </div>
          <div class="app2__feat">
            <span class="app2__check">
              <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>
            </span>Mobile Money, Airtel Money, MTN MoMo, Cash
          </div>
          <div class="app2__feat">
            <span class="app2__check">
              <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>
            </span>Vos commandes favorites en un clic
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

{{-- ── Réseaux sociaux ───────────────────────────────────────── --}}
<section class="sec bg2" id="social">
  <div class="mx rev">
    <div class="soc2__intro">
      <div class="sec__tag" style="justify-content:center;margin:0 auto 10px">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
        Communauté
      </div>
      <h2>Rejoignez la <em>communauté</em></h2>
      <p>Offres exclusives, nouveaux restaurants et actualités {{ $foodBrandName }} directement dans votre fil.</p>
    </div>
    <div class="soc2__grid">
      @foreach($socialLinks as $s)
        <a href="{{ $s['href'] }}" class="soc2__card"
           @if(!empty($s['is_external'])) target="_blank" rel="noopener noreferrer" @endif>
          <span class="soc2__ic {{ $s['tone'] }}"><i class="{{ $s['icon'] }}"></i></span>
          <strong class="soc2__name">{{ $s['label'] }}</strong>
          <small class="soc2__handle">{{ $s['handle'] }}</small>
          <span class="soc2__open">Voir <i class="fas fa-external-link-alt fa-xs"></i></span>
        </a>
      @endforeach
    </div>
  </div>
</section>

{{-- ── Chiffres clés — masqué (doublons de l'impact-band ci-dessus) --}}
<section class="sec" id="trust" style="display:none" aria-hidden="true">
  <div class="mx rev">
    <div style="text-align:center;margin-bottom:40px">
      <div class="sec__tag" style="justify-content:center;margin:0 auto 10px"><i class="fas fa-chart-line"></i> En chiffres</div>
      <h2>La confiance <em>se prouve</em></h2>
    </div>
    <div class="stats-grid">
      <div class="stat-cell">
        <div class="stat-num">{{ $featuredRestaurants->count() }}+</div>
        <div class="stat-lbl">Restaurants partenaires référencés</div>
      </div>
      <div class="stat-cell">
        <div class="stat-num">~30 min</div>
        <div class="stat-lbl">Délai moyen de livraison</div>
      </div>
      <div class="stat-cell">
        <div class="stat-num">2 villes</div>
        <div class="stat-lbl">Zones urbaines actives</div>
      </div>
      @if($avgRatingDisplay)
      <div class="stat-cell">
        <div class="stat-num">{{ $avgRatingDisplay }}</div>
        <div class="stat-lbl">Satisfaction client moyenne</div>
      </div>
      @endif
    </div>
  </div>
</section>

{{-- ── Footer ────────────────────────────────────────────────── --}}
<footer class="ft2">
  <div class="ft2__grid">
    <div>
      <a href="{{ route('home') }}" class="ft2__brand">
        <img src="{{ asset('frontend/images/BuntuDelice.png') }}" alt="{{ $foodBrandName }}" class="ft2__logo"
             onerror="this.style.display='none';this.nextElementSibling.style.display='inline'">
        <span style="display:none;" class="ft2__brand-name">{{ $foodBrandName }}</span>
      </a>
      <p class="ft2__desc">Plateforme congolaise de livraison de repas. Commandez, suivez et recevez vos plats favoris depuis les meilleurs restaurants de Brazzaville et Pointe-Noire.</p>
      <div class="ft2__socs">
        @foreach($footerSocials as $s)
          <a class="ft2__soc" href="{{ $s['href'] }}" aria-label="{{ $s['label'] }}"
             @if(!empty($s['is_external'])) target="_blank" rel="noopener noreferrer" @endif>
            <i class="{{ $s['icon'] }}"></i>
          </a>
        @endforeach
      </div>
    </div>
    <div class="ft2__col">
      <h4>Livraison repas</h4>
      <div class="ft2__links">
        @if($foodEnabled)
          <a href="{{ route('restaurants.all') }}">Explorer les restaurants</a>
          <a href="{{ route('track.order') }}">Suivi de commande</a>
        @endif
        <a href="{{ route('offers') }}">Offres et promotions</a>
        <a href="{{ route('faq') }}">Questions fréquentes</a>
      </div>
    </div>
    {{-- Section Mema retirée du footer — homepage food-only --}}
    <div class="ft2__col">
      <h4>Partenaires</h4>
      <div class="ft2__links">
        <a href="{{ route('partner') }}">Devenir restaurant partenaire</a>
        <a href="{{ route('driver') }}">Devenir livreur</a>
        <a href="{{ route('contact.us') }}">Contacter l'équipe</a>
      </div>
    </div>
    <div class="ft2__col">
      <h4>Informations</h4>
      <div class="ft2__links">
        <a href="{{ route('terms.conditions') }}">Conditions générales</a>
        <a href="{{ route('privacy.policy') }}">Confidentialité</a>
        <a href="{{ route('help') }}">Centre d'aide</a>
        <a href="{{ route('contact.us') }}">Nous contacter</a>
        <a href="{{ route('site.map') }}">Plan du site</a>
      </div>
    </div>
  </div>
  <div class="ft2__bot">
    <span class="ft2__copy">&copy; {{ date('Y') }} {{ $foodBrandName }}. Tous droits réservés.</span>
    <span class="ft2__pays">
      @foreach($paymentMethods as $pm)<span class="ft2__pay">{{ $pm }}</span>@endforeach
    </span>
    <span class="ft2__legal">
      <a href="{{ route('legal.notices') }}">Mentions légales</a>
      <a href="{{ route('cookies.policy') }}">Cookies</a>
      <a href="{{ route('site.map') }}">Plan du site</a>
    </span>
  </div>
</footer>

</div>{{-- .bd2 --}}

{{-- ── CTA Sticky Commander ─────────────────────────────────── --}}
@if($foodEnabled)
<div class="sticky-order-bar" id="stickyOrderBar" role="complementary" aria-label="Commander maintenant">
  <div class="sticky-order-bar__copy">
    <span class="sticky-order-bar__title">{{ $foodBrandName }}</span>
    <span class="sticky-order-bar__sub">Livraison en 30–45 min · Brazzaville &amp; Pointe-Noire</span>
  </div>
  <a href="{{ route('restaurants.all') }}" class="sticky-order-bar__cta">
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
    Commander maintenant
  </a>
  <button class="sticky-order-bar__close" id="stickyOrderClose" aria-label="Fermer">
    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
  </button>
</div>
@endif
@endsection

@section('scripts')
<script>
(function(){
  // ── Sticky order bar — visible après 40% du hero, fermable
  var bar=document.getElementById('stickyOrderBar');
  var closeBtn=document.getElementById('stickyOrderClose');
  var dismissed=false;
  if(bar){
    window.addEventListener('scroll',function(){
      if(dismissed)return;
      var heroH=document.getElementById('hero');
      var threshold=heroH ? heroH.offsetHeight*0.4 : 300;
      bar.classList.toggle('visible',window.scrollY>threshold);
    },{passive:true});
  }
  if(closeBtn){
    closeBtn.addEventListener('click',function(){
      dismissed=true;
      if(bar)bar.classList.remove('visible');
    });
  }
})();

(function(){
  // ── Scroll progress bar
  var prog=document.getElementById('scrollProg');
  if(prog){
    window.addEventListener('scroll',function(){
      var s=document.documentElement;
      var p=(s.scrollTop||document.body.scrollTop)/(s.scrollHeight-s.clientHeight)*100;
      prog.style.width=Math.min(p,100)+'%';
    },{passive:true});
  }
})();

(function(){
  // ── Horloge hero
  var c=document.getElementById('heroClock');
  function t(){if(c)c.textContent=new Date().toLocaleTimeString('fr-FR',{hour:'2-digit',minute:'2-digit'})}
  t();setInterval(t,1000);
})();

(function(){
  // ── Nav shadow on scroll
  var nav=document.getElementById('mainNav');
  if(!nav)return;
  window.addEventListener('scroll',function(){
    nav.classList.toggle('scrolled',window.scrollY>10);
  },{passive:true});
})();

(function(){
  // ── Platforms dropdown
  var dd=document.getElementById('navPlatformsDD');
  var btn=document.getElementById('navPlatformsBtn');
  if(!dd||!btn)return;
  btn.addEventListener('click',function(e){
    e.stopPropagation();
    dd.classList.toggle('open');
  });
  document.addEventListener('click',function(e){
    if(!dd.contains(e.target))dd.classList.remove('open');
  });
})();

(function(){
  // ── Mobile nav drawer toggle
  var toggle=document.getElementById('mobileNavToggle');
  var drawer=document.getElementById('mobileNavDrawer');
  if(!toggle||!drawer)return;
  toggle.addEventListener('click',function(){
    var open=drawer.classList.toggle('open');
    drawer.setAttribute('aria-hidden',String(!open));
    toggle.setAttribute('aria-expanded',String(open));
  });
  document.addEventListener('click',function(e){
    if(drawer.classList.contains('open')&&!drawer.contains(e.target)&&!toggle.contains(e.target)){
      drawer.classList.remove('open');
      drawer.setAttribute('aria-hidden','true');
      toggle.setAttribute('aria-expanded','false');
    }
  });
})();


(function(){
  // ── Hero chips active state
  document.querySelectorAll('.hero2__chip').forEach(function(chip){
    chip.addEventListener('click',function(){
      document.querySelectorAll('.hero2__chip').forEach(function(c){c.classList.remove('active')});
      this.classList.add('active');
    });
  });
})();

(function(){
  // ── Journey steps reveal with staggered delay
  var steps=document.querySelectorAll('.jstep');
  if(!steps.length||!('IntersectionObserver' in window)){
    steps.forEach(function(s){s.classList.add('on')});
  } else {
    var jio=new IntersectionObserver(function(entries){
      entries.forEach(function(en){
        if(en.isIntersecting){
          var idx=Array.from(steps).indexOf(en.target);
          setTimeout(function(){en.target.classList.add('on')},idx*120);
          jio.unobserve(en.target);
        }
      });
    },{threshold:0.15});
    steps.forEach(function(s){jio.observe(s)});
  }
})();

(function(){
  // ── Journey mockup countdown timer
  var el=document.getElementById('jmockCountdown');
  if(!el)return;
  var secs=28*60;
  setInterval(function(){
    if(secs>0)secs--;
    var m=Math.floor(secs/60);
    var s=secs%60;
    el.textContent=(m<10?'0':'')+m+':'+(s<10?'0':'')+s;
  },1000);
})();

(function(){
  // ── Scroll reveal
  var els=document.querySelectorAll('.rev,.rev-l');
  if(!els.length||!('IntersectionObserver' in window)){
    els.forEach(function(e){e.classList.add('on')});return;
  }
  var io=new IntersectionObserver(function(entries){
    entries.forEach(function(en){
      if(en.isIntersecting){en.target.classList.add('on');io.unobserve(en.target)}
    });
  },{threshold:0.10});
  els.forEach(function(e){io.observe(e)});
})();

(function(){
  // ── Custom cursor (pointer device only)
  if(!window.matchMedia||!window.matchMedia('(pointer:fine)').matches)return;
  var cur=document.getElementById('bdHomeCursor'),
      dot=document.getElementById('bdHomeCursorDot'),
      ring=document.getElementById('bdHomeCursorRing');
  if(!cur||!dot||!ring)return;
  var x=window.innerWidth/2,y=window.innerHeight/2,rx=x,ry=y;
  var cursorReady=false;
  dot.style.left=x+'px';dot.style.top=y+'px';
  ring.style.left=rx+'px';ring.style.top=ry+'px';
  function enableCursor(){
    if(cursorReady)return;
    cursorReady=true;
    document.body.classList.add('bd-home-cursor-ready');
  }
  document.addEventListener('mousemove',function(e){
    enableCursor();
    x=e.clientX;y=e.clientY;
    dot.style.left=x+'px';dot.style.top=y+'px';
  });
  document.addEventListener('mouseover',function(e){
    document.body.classList.toggle('bd-home-cursor-hover',!!e.target.closest('a,button'));
  });
  (function ani(){rx+=(x-rx)*.16;ry+=(y-ry)*.16;ring.style.left=rx+'px';ring.style.top=ry+'px';requestAnimationFrame(ani)})();
})();
</script>

{{-- ── Hero : GPS + autocomplétion Mapbox ─────────────────────────── --}}
@php $heroMapboxToken = mapbox_public_token(); @endphp
@if($heroMapboxToken)
<script>
(function () {
    var TOKEN    = @json($heroMapboxToken);
    var input    = document.getElementById('heroAddressInput');
    var form     = document.getElementById('heroOrderForm');
    var latFld   = document.getElementById('heroLat');
    var lngFld   = document.getElementById('heroLng');
    var gpsBtn   = document.getElementById('heroGpsBtn');
    var gpsLabel = document.getElementById('heroGpsLabel');
    var hint     = document.getElementById('heroHint');
    var barField = document.querySelector('.bd-hero__bar-field');
    if (!input || !form) return;

    // Dropdown — injecté dans .bd-hero__bar (position relative CSS)
    var box = document.getElementById('heroSuggestions');
    if (!box) {
        box = document.createElement('div');
        box.id = 'heroSuggestions';
        form.appendChild(box);
    }

    function closeSuggestions() { box.style.display = 'none'; }

    var PIN_SVG = '<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="#009543" stroke-width="2.5"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>';

    function addSuggestion(text, onClick) {
        var btn = document.createElement('button');
        btn.type = 'button';
        btn.innerHTML = PIN_SVG + '<span>' + text + '</span>';
        btn.addEventListener('mousedown', function(e){ e.preventDefault(); onClick(); });
        box.appendChild(btn);
    }

    function setCoords(lat, lng) {
        if (latFld) latFld.value = lat;
        if (lngFld) lngFld.value = lng;
    }
    function clearCoords() {
        if (latFld) latFld.value = '';
        if (lngFld) lngFld.value = '';
    }

    // ── Autocomplétion (Nominatim fallback si Mapbox 403) ─────────
    var _timer, _last = '', _proximity = '', _mapboxOk = null;

    function _nominatimShortName(displayName) {
        var parts = displayName.split(',');
        var short = parts[0].replace(/\([^)]*\)/g, '').trim();
        if (parts.length > 1) {
            var city = parts[1].replace(/\([^)]*\)/g, '').trim();
            if (city && city.toLowerCase() !== short.toLowerCase()) short += ', ' + city;
        }
        return short || displayName;
    }

    function fetchSuggestionsNominatim(q) {
        fetch('https://nominatim.openstreetmap.org/search?format=json&q=' + encodeURIComponent(q) + '&countrycodes=cg&limit=6&accept-language=fr')
        .then(function(r){ return r.json(); })
        .then(function(results){
            box.innerHTML = '';
            if (!results || !results.length) { closeSuggestions(); return; }
            results.forEach(function(r){
                var shortName = _nominatimShortName(r.display_name);
                addSuggestion(shortName, function(){
                    input.value = shortName;
                    setCoords(parseFloat(r.lat), parseFloat(r.lon));
                    closeSuggestions();
                    form.submit();
                });
            });
            box.style.display = 'block';
        })
        .catch(function(){ closeSuggestions(); });
    }

    function fetchSuggestions(q) {
        if (q.length < 2) { closeSuggestions(); return; }
        if (_mapboxOk === false) { fetchSuggestionsNominatim(q); return; }
        fetch('https://api.mapbox.com/geocoding/v5/mapbox.places/'
            + encodeURIComponent(q)
            + '.json?access_token=' + TOKEN
            + '&autocomplete=true&limit=6&language=fr&country=cg'
            + '&types=address,poi,neighborhood,locality,place'
            + (_proximity ? '&proximity=' + _proximity : ''))
        .then(function(r){
            if (!r.ok) { _mapboxOk = false; fetchSuggestionsNominatim(q); throw new Error('mapbox-' + r.status); }
            _mapboxOk = true; return r.json();
        })
        .then(function(data){
            var features = data.features || [];
            box.innerHTML = '';
            features.forEach(function(f) {
                var c = f.center;
                // f.text = nom court (ex: "Bacongo") ; f.place_name = nom complet (label dropdown)
                var shortName = f.text || f.place_name.split(',')[0].trim();
                addSuggestion(f.place_name, function(){
                    input.value = shortName;
                    setCoords(c[1], c[0]);
                    closeSuggestions();
                    form.submit();
                });
            });
            box.style.display = features.length ? 'block' : 'none';
        })
        .catch(function(e){
            // Si Mapbox 403 → on a déjà lancé Nominatim, ne pas fermer le dropdown
            if (e && e.message && e.message.indexOf('mapbox-') === 0) return;
            closeSuggestions();
        });
    }

    // ── Reverse geocode (Mapbox → Nominatim fallback) ──────────
    // cb(label, search) : label = adresse lisible affichée, search = terme court pour backend
    function reverseGeocodeNominatim(lat, lng, cb) {
        fetch('https://nominatim.openstreetmap.org/reverse?format=json&lat=' + lat + '&lon=' + lng + '&accept-language=fr&zoom=16')
        .then(function(r){ return r.json(); })
        .then(function(d){
            var addr = d.address || {};
            var road   = addr.road || '';
            var suburb = addr.suburb || addr.neighbourhood
                      || (addr.city_district || '').split('(')[0].trim();
            var city   = (addr.city || addr.town || addr.village || '').split('(')[0].trim();
            // Label lisible : rue + quartier (ex: "Rue Moll, Bacongo")
            var label  = [road, suburb].filter(Boolean).join(', ')
                      || suburb || city || d.display_name
                      || (lat.toFixed(4)+', '+lng.toFixed(4));
            // Terme de recherche : quartier + ville (ex: "Bacongo, Brazzaville" — matche les adresses DB)
            var search = [suburb, city].filter(Boolean).join(', ') || suburb || label;
            cb(label, search);
        })
        .catch(function(){ cb(lat.toFixed(4)+', '+lng.toFixed(4), ''); });
    }
    function reverseGeocode(lat, lng, cb) {
        if (_mapboxOk === false) { reverseGeocodeNominatim(lat, lng, cb); return; }
        fetch('https://api.mapbox.com/geocoding/v5/mapbox.places/'
            + lng + ',' + lat
            + '.json?access_token=' + TOKEN
            + '&limit=1&language=fr&types=address,neighborhood,locality,place')
        .then(function(r){
            if (!r.ok) { _mapboxOk = false; reverseGeocodeNominatim(lat, lng, cb); throw new Error('mapbox-' + r.status); }
            _mapboxOk = true; return r.json();
        })
        .then(function(d){
            var f = d.features && d.features[0];
            // Mapbox n'a pas de coverage pour Brazzaville → 0 features → Nominatim
            if (!f) { reverseGeocodeNominatim(lat, lng, cb); return; }
            var shortName = f.text || f.place_name.split(',')[0].trim();
            var city = '';
            (f.context || []).forEach(function(c){
                if (c.id && c.id.indexOf('place.') === 0) city = c.text;
            });
            cb(f.place_name, [shortName, city].filter(Boolean).join(', ') || shortName);
        })
        .catch(function(e){
            if (e && e.message && e.message.indexOf('mapbox-') === 0) return;
            reverseGeocodeNominatim(lat, lng, cb);
        });
    }

    // ── GPS ───────────────────────────────────────────────────────
    var _hintDefault = hint ? hint.innerHTML : '';

    function setGpsState(state, text) {
        if (!gpsBtn) return;
        gpsBtn.className = 'bd-hero__gps' + (state === 'loading' ? ' is-loading' : state === 'active' ? ' is-active' : '');
        gpsBtn.disabled = (state === 'loading');
        if (gpsLabel) gpsLabel.textContent = text || 'Ma position';
    }
    function setHint(msg) { if (hint) hint.textContent = msg; }
    function restoreHint() { if (hint) hint.innerHTML = _hintDefault; }

    if (gpsBtn) {
        gpsBtn.addEventListener('click', function() {
            if (!navigator.geolocation) {
                setHint('Géolocalisation non disponible sur cet appareil.');
                setTimeout(restoreHint, 4000);
                return;
            }
            setGpsState('loading', 'Localisation…');
            navigator.geolocation.getCurrentPosition(
                function(pos) {
                    var lat = pos.coords.latitude, lng = pos.coords.longitude;
                    _proximity = lng + ',' + lat;
                    setCoords(lat, lng);
                    reverseGeocode(lat, lng, function(label, search) {
                        // label = adresse lisible (ex: "Rue Moll, Bacongo"), search = terme DB
                        // Avec lat/lng dans le form, le backend fait Haversine → label sert juste à l'affichage
                        input.value = label || search;
                        setGpsState('active', (label || search).split(',')[0] || 'Ma position');
                        form.submit();
                    });
                },
                function(err) {
                    setGpsState('', 'Ma position');
                    setHint(err.code === 1
                        ? '⚠️ Position refusée — autorisez la localisation dans votre navigateur.'
                        : '⚠️ Position introuvable.');
                    setTimeout(restoreHint, 5000);
                },
                { enableHighAccuracy: true, timeout: 12000, maximumAge: 0 }
            );
        });
    }

    // ── Input ─────────────────────────────────────────────────────
    input.addEventListener('input', function() {
        clearCoords();
        var v = this.value.trim();
        if (v === _last) return;
        _last = v;
        clearTimeout(_timer);
        _timer = setTimeout(function(){ fetchSuggestions(v); }, 280);
    });
    input.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') closeSuggestions();
        if (e.key === 'ArrowDown') {
            var items = box.querySelectorAll('button');
            if (items.length) { e.preventDefault(); items[0].focus(); }
        }
    });
    document.addEventListener('click', function(e) {
        if (!form.contains(e.target)) closeSuggestions();
    });

    // ── Géoloc silencieuse si permission déjà accordée ────────────
    if (navigator.geolocation && navigator.permissions) {
        navigator.permissions.query({ name: 'geolocation' }).then(function(p) {
            if (p.state !== 'granted') return;
            navigator.geolocation.getCurrentPosition(function(pos) {
                _proximity = pos.coords.longitude + ',' + pos.coords.latitude;
                setCoords(pos.coords.latitude, pos.coords.longitude);
                if (!input.value.trim()) {
                    reverseGeocode(pos.coords.latitude, pos.coords.longitude, function(label, search) {
                        input.value = label || search;
                        setGpsState('active', (label || search).split(',')[0] || 'Ma position');
                    });
                }
            }, function(){}, { maximumAge: 60000, timeout: 5000 });
        }).catch(function(){});
    }
})();
</script>
@endif
@endsection
