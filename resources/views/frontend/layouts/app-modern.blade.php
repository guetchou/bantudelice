<!DOCTYPE html>
@php
    $layoutBrand = app(\App\Services\AuthBrandingService::class)->resolve(request());
    $layoutBrandKey = $layoutBrand['key'] ?? 'bantudelice';
    $layoutBrandPrimary = $layoutBrand['primary'] ?? '#009543';
    $layoutBrandSoft = $layoutBrand['primary_soft'] ?? 'rgba(0, 149, 67, 0.12)';
    $layoutBrandName = $layoutBrand['name'] ?? \App\Services\ConfigService::getCompanyName();
    $layoutBrandRouteParams = $layoutBrandKey !== 'bantudelice' ? ['brand' => $layoutBrandKey] : [];
    $layoutHomeLink = match ($layoutBrandKey) {
        'mema' => route('colis.landing'),
        'kende' => route('transport.index'),
        default => route('home'),
    };
    $layoutFooterTagline = match ($layoutBrandKey) {
        'mema' => 'Votre service de confiance pour expedier, suivre et gerer vos colis au Congo.',
        'kende' => 'Votre espace de confiance pour reserver, suivre et gerer vos trajets au Congo.',
        default => 'Votre partenaire de confiance pour commander vos repas et decouvrir les meilleures tables locales.',
    };
    $layoutDefaultTitle = match ($layoutBrandKey) {
        'mema' => 'Mema - Livraison de colis au Congo',
        'kende' => 'Kende - Transport local au Congo',
        default => \App\Services\ConfigService::getCompanyName() . ' - Livraison a domicile',
    };
    $layoutDefaultDescription = match ($layoutBrandKey) {
        'mema' => 'Mema - Envoyez, suivez et gerez vos colis simplement a Brazzaville et au Congo.',
        'kende' => 'Kende - Reservez un trajet local, suivez votre chauffeur et gerez vos deplacements au Congo.',
        default => \App\Services\ConfigService::getCompanyName() . ' - Commande de repas et livraison food a Brazzaville.',
    };
    $layoutServiceLinks = match ($layoutBrandKey) {
        'mema' => [
            ['label' => 'Accueil Mema', 'url' => route('colis.landing')],
            ['label' => 'Suivre un envoi', 'url' => route('colis.track_public')],
            ['label' => 'Nouveau colis', 'url' => route('colis.create')],
            ['label' => 'Mes envois', 'url' => route('colis.mes-envois')],
            ['label' => 'Nous contacter', 'url' => route('contact.us', $layoutBrandRouteParams)],
        ],
        'kende' => [
            ['label' => 'Accueil Kende', 'url' => route('transport.index')],
            ['label' => 'Taxi', 'url' => route('transport.taxi')],
            ['label' => 'Covoiturage', 'url' => route('transport.carpool')],
            ['label' => 'Location', 'url' => route('transport.rental')],
            ['label' => 'Mes reservations', 'url' => route('transport.my_bookings')],
            ['label' => 'Nous contacter', 'url' => route('contact.us', $layoutBrandRouteParams)],
        ],
        default => [
            ['label' => 'Restaurants', 'url' => route('restaurants.all')],
            ['label' => 'Suivre une commande', 'url' => route('track.order')],
            ['label' => 'Offres du moment', 'url' => route('offers')],
            ['label' => 'À propos de nous', 'url' => route('about.us', $layoutBrandRouteParams)],
            ['label' => 'Devenir livreur', 'url' => route('driver')],
            ['label' => 'Devenir partenaire', 'url' => route('partenaires')],
            ['label' => 'Nous contacter', 'url' => route('contact.us', $layoutBrandRouteParams)],
        ],
    };
    $layoutBrandInitial = strtoupper(substr($layoutBrandName, 0, 1));
@endphp
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>@yield('title', $layoutDefaultTitle)</title>
    <meta name="description" content="@yield('description', $layoutDefaultDescription)">
    <meta name="keywords" content="livraison de repas, restaurant, food delivery, Congo, Brazzaville, Pointe-Noire">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">

    <!-- T1.3 — PWA Manifest -->
    <link rel="manifest" href="/manifest.webmanifest">
    <meta name="theme-color" content="#009543">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-title" content="BantuDelice">
    <link rel="apple-touch-icon" href="{{ asset('images/5-512.png') }}">
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Modern CSS -->
    <link href="{{ asset('frontend/css/modern.css') }}" rel="stylesheet">

    @yield('styles')
    @yield('style')
</head>
@php
    $hidePrimaryChrome = trim($__env->yieldContent('hide_primary_chrome')) !== '';
    $pageBodyClass = trim($__env->yieldContent('body_class'));
    $pageBodyStyle = trim($__env->yieldContent('body_style'));
@endphp
<body class="bd-future-shell{{ $pageBodyClass ? ' ' . $pageBodyClass : '' }}" style="--layout-brand-primary: {{ $layoutBrandPrimary }}; --layout-brand-soft: {{ $layoutBrandSoft }};{{ $pageBodyStyle ? ' ' . $pageBodyStyle : '' }}">
    <!-- Header Moderne -->
    @unless($hidePrimaryChrome)
    <header class="modern-header float-nav-shell" id="header">
        <div class="header-container float-nav-inner">
            <!-- Logo -->
            <a href="{{ $layoutHomeLink }}" class="logo float-brand bd-brand-lockup">
                <span class="bd-brand-logo-wrap">
                    @if($layoutBrandKey === 'bantudelice')
                        <img src="{{ asset('frontend/images/BuntuDelice.png') }}" alt="{{ $layoutBrandName }}">
                    @else
                        <span class="bd-brand-chip">
                            <span class="bd-brand-chip__badge" style="background: {{ $layoutBrandPrimary }};">{{ $layoutBrandInitial }}</span>
                            <span class="bd-brand-chip__copy">
                                <span class="bd-brand-chip__name">{{ $layoutBrandName }}</span>
                                <span class="bd-brand-chip__label">{{ $layoutBrand['label'] ?? 'Service' }}</span>
                            </span>
                        </span>
                    @endif
                </span>
            </a>
            
            @php
                $foodEnabled = (bool) config('bantudelice_modules.food.enabled', true);
                $colisEnabled = (bool) config('bantudelice_modules.colis.enabled', true);
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
                $brandQuery = request()->query('brand');
                $brandRouteParams = in_array($brandQuery, ['mema', 'kende', 'bantudelice'], true) ? ['brand' => $brandQuery] : [];
                $accountLink = route('user.login');
                $accountLabel = trans('ui.nav.account');

                if (Auth::check() && auth()->user()) {
                    $userType = auth()->user()->type ?? 'user';

                    if ($userType === 'admin') {
                        $accountLink = route('admin.dashboard');
                        $accountLabel = 'Administration';
                    } elseif ($userType === 'restaurant') {
                        $accountLink = route('restaurant.dashboard');
                        $accountLabel = 'Espace restaurant';
                    } elseif ($userType === 'delivery') {
                        $accountLink = route('delivery.dashboard');
                        $accountLabel = 'Espace livraison';
                    } elseif ($userType === 'driver') {
                        $accountLink = route('driver.deliveries');
                        $accountLabel = 'Mes livraisons';
                    }
                }
            @endphp

            <!-- Navigation -->
            <nav class="nav-menu" id="navMenu">
                @if(Auth::check())
                    <div class="mobile-account-panel">
                        <a href="{{ $accountLink }}" class="mobile-account-summary">
                            <img src="{{ auth()->user()->avatarUrl() }}" alt="{{ auth()->user()->name }}" class="mobile-account-avatar">
                            <span class="mobile-account-copy">
                                <strong>{{ auth()->user()->name }}</strong>
                                <small>{{ ucfirst(auth()->user()->type ?? trans('ui.nav.profile')) }}</small>
                            </span>
                        </a>
                        <div class="mobile-account-links">
                            <a href="{{ $accountLink }}">{{ $accountLabel }}</a>
                            <a href="{{ route('cart.detail') }}">{{ trans('ui.nav.cart') }}</a>
                            <a href="{{ route('restaurants.favorites') }}">{{ trans('ui.nav.favorites') }}</a>
                            <form method="POST" action="{{ route('user.logout') }}" style="display:block;">
                                @csrf
                                <button type="submit" style="background:none;border:0;padding:0;color:inherit;font:inherit;cursor:pointer;">{{ trans('ui.nav.logout') }}</button>
                            </form>
                        </div>
                    </div>
                @endif
                @if($foodEnabled)
                    <a href="{{ route('restaurants.all') }}" class="nav-link nav-link--service nav-link--primary">{{ trans('ui.nav.repas') }}</a>
                @endif
                @if($ecosystemPlatforms->isNotEmpty())
                    <div class="nav-dropdown">
                        <button type="button" class="nav-link nav-dropdown-toggle nav-link--utility" aria-expanded="false">
                            Nos autres plateformes <i class="fas fa-chevron-down" style="font-size: 0.7rem; margin-left: 4px;"></i>
                        </button>
                        <div class="nav-dropdown-menu">
                            @foreach($ecosystemPlatforms as $platform)
                                @php
                                    $platformHost = parse_url((string) $platform['url'], PHP_URL_HOST);
                                    $isExternalPlatform = filled($platformHost) && $platformHost !== request()->getHost();
                                @endphp
                                <a href="{{ $platform['url'] }}" @if($isExternalPlatform) target="_blank" rel="noopener" @endif>{{ $platform['name'] }}</a>
                            @endforeach
                        </div>
                    </div>
                @endif
                @if($foodEnabled)
                    <a href="{{ route('track.order') }}" class="nav-link nav-link--utility">{{ trans('ui.nav.suivi') }}</a>
                @endif
                <a href="{{ route('help', $brandRouteParams) }}" class="nav-link nav-link--utility">{{ trans('ui.nav.help') }}</a>
            </nav>
            
            <!-- Actions -->
            <div class="nav-actions">
                @include('frontend.partials.site_switcher')
                @php
                    $count_items = 0;

                    if (Auth::check() && auth()->user()) {
                        try {
                            $count_items = DB::table('carts')->where('user_id', auth()->user()->id)->sum('qty');
                        } catch (\Throwable $e) {
                            $count_items = 0;
                        }
                    } else {
                        $cart = session()->get('cart', []);
                        if (is_array($cart)) {
                            foreach($cart as $item) {
                                $count_items += ($item['qty'] ?? 0);
                            }
                        }
                    }
                @endphp

                @auth
                    @php
                        $headerFirstName = trim(explode(' ', (string) auth()->user()->name)[0] ?? '');
                        $headerFirstName = $headerFirstName !== '' ? $headerFirstName : 'Compte';
                    @endphp
                    <a href="{{ $accountLink }}" class="header-profile-chip" aria-label="Mon compte">
                        <img src="{{ auth()->user()->avatarUrl() }}" alt="{{ auth()->user()->name }}" class="header-profile-avatar">
                        <span class="header-profile-copy">
                            <strong>{{ $headerFirstName }}</strong>
                            <small>{{ $accountLabel }}</small>
                        </span>
                    </a>
                @endauth

                @auth
                <a href="{{ route('user.notifications') }}" class="cart-icon ntf-bell-nav" id="notifBellNav" aria-label="Notifications" style="position:relative;">
                    <i class="fas fa-bell" style="font-size:18px;"></i>
                    <span id="notifBadge" class="cart-badge" style="display:none;min-width:16px;height:16px;font-size:.65rem;padding:0 4px;line-height:16px;text-align:center;border-radius:8px;">0</span>
                </a>
                @endauth

                <a href="{{ route('cart.detail') }}" class="cart-icon" aria-label="Panier">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                        <path d="M3 5H5L7.4 14.2C7.63 15.08 8.42 15.7 9.33 15.7H17.8C18.67 15.7 19.43 15.13 19.69 14.3L21 10" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M8.5 19.2C8.5 19.86 7.96 20.4 7.3 20.4C6.64 20.4 6.1 19.86 6.1 19.2C6.1 18.54 6.64 18 7.3 18C7.96 18 8.5 18.54 8.5 19.2Z" fill="currentColor"/>
                        <path d="M19.1 19.2C19.1 19.86 18.56 20.4 17.9 20.4C17.24 20.4 16.7 19.86 16.7 19.2C16.7 18.54 17.24 18 17.9 18C18.56 18 19.1 18.54 19.1 19.2Z" fill="currentColor"/>
                        <path d="M9 8.2H20" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                    </svg>
                    @if($count_items > 0)
                        <span class="cart-badge" data-cart-count>{{ $count_items }}</span>
                    @else
                        <span class="cart-badge" data-cart-count style="display: none;">0</span>
                    @endif
                </a>
                
                @if($foodEnabled)
                    <a href="{{ route('restaurants.all') }}" class="nav-cta-pill">{{ trans('ui.home.service_cards.restaurants.cta') }}</a>
                @endif
                
                <!-- Mobile Menu Toggle -->
                <button class="menu-toggle" id="menuToggle" aria-label="Menu">
                    <span></span>
                    <span></span>
                    <span></span>
                </button>
            </div>
        </div>
    </header>
    @endunless
    
    <!-- Messages Flash (Notifications) -->
    @if(session('message'))
        <div id="flash-message" class="flash-message flash-success">
            {{ session('message') }}
        </div>
        <script>
            // Masquer le message après 3 secondes
            setTimeout(function() {
                const msg = document.getElementById('flash-message');
                if (msg) {
                    msg.style.animation = 'slideOut 0.3s ease-out';
                    setTimeout(() => msg.remove(), 300);
                }
            }, 3000);
        </script>
    @endif
    
    @if(session('error'))
        <div id="flash-error" class="flash-message flash-error">
            {{ session('error') }}
        </div>
        <script>
            setTimeout(function() {
                const msg = document.getElementById('flash-error');
                if (msg) {
                    msg.style.animation = 'slideOut 0.3s ease-out';
                    setTimeout(() => msg.remove(), 300);
                }
            }, 3000);
        </script>
    @endif
    
    <!-- Contenu Principal -->
    <main>
        @yield('content')
    </main>
    
    <!-- Footer Moderne -->
    @unless($hidePrimaryChrome || trim($__env->yieldContent('hide_layout_footer')) !== '')
    <footer class="modern-footer">
        <div class="container">
            <div class="footer-grid">
                <!-- Brand -->
                <div class="footer-brand">
                    <div class="footer-logo">
                        @if($layoutBrandKey === 'bantudelice')
                            <img src="{{ asset('frontend/images/BuntuDelice.png') }}" alt="{{ $layoutBrandName }}" class="footer-brand-image">
                        @else
                            <span class="bd-brand-chip bd-brand-chip--footer">
                                <span class="bd-brand-chip__badge" style="background: {{ $layoutBrandPrimary }};">{{ $layoutBrandInitial }}</span>
                                <span class="bd-brand-chip__copy">
                                    <span class="bd-brand-chip__name">{{ $layoutBrandName }}</span>
                                    <span class="bd-brand-chip__label">{{ $layoutBrand['label'] ?? 'Service' }}</span>
                                </span>
                            </span>
                        @endif
                    </div>
                    <p>{{ $layoutFooterTagline }}</p>
                </div>
                
                <!-- Liens Rapides -->
                <div class="footer-column">
                    <h4>Services</h4>
                    <div class="footer-links">
                        @foreach($layoutServiceLinks as $serviceLink)
                            <a href="{{ $serviceLink['url'] }}">{{ $serviceLink['label'] }}</a>
                        @endforeach
                    </div>
                </div>
                
                <!-- Informations -->
                <div class="footer-column">
                    <h4>Informations</h4>
                    <div class="footer-links">
                        <a href="{{ route('terms.conditions', $brandRouteParams) }}">Conditions générales</a>
                        <a href="{{ route('privacy.policy', $brandRouteParams) }}">Politique de confidentialité</a>
                        <a href="{{ route('refund.policy') }}">Politique de remboursement</a>
                        <a href="{{ route('faq') }}">FAQ</a>
                        <a href="{{ route('help', $brandRouteParams) }}">Centre d'aide</a>
                        <a href="{{ route('offers') }}">Offres et promotions</a>
                        <a href="{{ route('data.deletion', $brandRouteParams) }}">Suppression des données</a>
                    </div>
                </div>
                
                <!-- Ressources -->
                @if($ecosystemPlatforms->isNotEmpty())
                    <div class="footer-column">
                        <h4>Nos autres plateformes</h4>
                        <p class="footer-platform-copy">
                            Decouvrez aussi les autres marques de l'ecosysteme sur leurs domaines dedies.
                        </p>
                        <div class="footer-links">
                            @foreach($ecosystemPlatforms as $platform)
                                @php
                                    $platformHost = parse_url((string) $platform['url'], PHP_URL_HOST);
                                    $isExternalPlatform = filled($platformHost) && $platformHost !== request()->getHost();
                                @endphp
                                <a href="{{ $platform['url'] }}" @if($isExternalPlatform) target="_blank" rel="noopener" @endif>{{ $platform['name'] }}</a>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
            
            <!-- Bottom -->
            <div class="footer-bottom">
                <p class="footer-copyright">
                    &copy; {{ date('Y') }} {{ $layoutBrandName }}. Tous droits réservés.
                </p>
                <div class="footer-bottom-links">
                    <a href="{{ route('legal.notices', $brandRouteParams) }}">Mentions légales</a>
                    <a href="{{ route('cookies.policy', $brandRouteParams) }}">Cookies</a>
                    <a href="{{ route('site.map') }}">Plan du site</a>
                </div>
            </div>
        </div>
    </footer>
    @endunless
    
    <!-- Back to Top -->
    <button type="button" id="backToTop" class="back-to-top" aria-label="Retour en haut">
        <i class="fas fa-arrow-up"></i>
    </button>
    
    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Header scroll effect
        const header = document.getElementById('header');
        window.addEventListener('scroll', () => {
            if (!header) {
                return;
            }

            if (window.scrollY > 50) {
                header.classList.add('scrolled');
            } else {
                header.classList.remove('scrolled');
            }
        });
        
        // Mobile menu toggle
        const menuToggle = document.getElementById('menuToggle');
        const navMenu = document.getElementById('navMenu');
        const dropdownToggles = document.querySelectorAll('.nav-dropdown-toggle');
        const localeDropdown = document.querySelector('.bd-site-switcher__dropdown');
        const localeTrigger = document.querySelector('.bd-site-switcher__trigger');

        menuToggle?.addEventListener('click', () => {
            if (!navMenu) {
                return;
            }

            navMenu.classList.toggle('active');
            if (!navMenu.classList.contains('active')) {
                document.querySelectorAll('.nav-dropdown.open').forEach(el => el.classList.remove('open'));
            }
        });

        dropdownToggles.forEach(toggle => {
            toggle.addEventListener('click', (e) => {
                if (window.innerWidth <= 991) {
                    e.preventDefault();
                    const dropdown = toggle.closest('.nav-dropdown');
                    if (!dropdown) return;

                    const isOpen = dropdown.classList.contains('open');
                    document.querySelectorAll('.nav-dropdown.open').forEach(el => el.classList.remove('open'));
                    if (!isOpen) dropdown.classList.add('open');
                }
            });
        });

        localeTrigger?.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            localeDropdown?.classList.toggle('open');
            localeTrigger.setAttribute('aria-expanded', localeDropdown?.classList.contains('open') ? 'true' : 'false');
        });

        document.addEventListener('click', (e) => {
            if (!e.target.closest('.nav-dropdown')) {
                document.querySelectorAll('.nav-dropdown.open').forEach(el => el.classList.remove('open'));
            }

            if (!e.target.closest('.bd-site-switcher__dropdown')) {
                localeDropdown?.classList.remove('open');
                localeTrigger?.setAttribute('aria-expanded', 'false');
            }
        });
        
        // Back to top button
        const backToTop = document.getElementById('backToTop');
        window.addEventListener('scroll', () => {
            if (!backToTop) {
                return;
            }

            if (window.scrollY > 300) {
                backToTop.style.opacity = '1';
                backToTop.style.visibility = 'visible';
            } else {
                backToTop.style.opacity = '0';
                backToTop.style.visibility = 'hidden';
            }
        });
        
        backToTop?.addEventListener('click', (e) => {
            e.preventDefault();
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
        
        // Smooth scroll for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                const href = this.getAttribute('href');
                if (href !== '#') {
                    e.preventDefault();
                    const target = document.querySelector(href);
                    if (target) {
                        target.scrollIntoView({ behavior: 'smooth' });
                    }
                }
            });
        });
    </script>
    
    <!-- Toast Container -->
    <div id="toastContainer" style="position:fixed;bottom:80px;left:50%;transform:translateX(-50%);z-index:10000;display:flex;flex-direction:column;align-items:center;gap:0.5rem;pointer-events:none;"></div>

    <script>
        function showToast(message, type = 'success') {
            let container = document.getElementById('toastContainer');
            if (!container) {
                container = document.createElement('div');
                container.id = 'toastContainer';
                container.style.cssText = 'position:fixed;bottom:80px;left:50%;transform:translateX(-50%);z-index:10000;display:flex;flex-direction:column;align-items:center;gap:0.5rem;pointer-events:none;';
                document.body.appendChild(container);
            }

            const toast = document.createElement('div');
            toast.style.cssText = `
                background:${type === 'success' ? '#05944F' : type === 'warning' ? '#D97706' : '#EF4444'};
                color:#fff;padding:0.6rem 1.1rem;border-radius:20px;
                box-shadow:0 4px 16px rgba(0,0,0,0.18);font-weight:500;font-size:0.875rem;
                display:flex;align-items:center;gap:0.5rem;white-space:nowrap;
                animation:bdToastIn 0.25s ease-out;pointer-events:auto;
            `;
            const icon = type === 'success' ? 'fa-check-circle' : type === 'warning' ? 'fa-triangle-exclamation' : 'fa-exclamation-circle';
            toast.innerHTML = `<i class="fas ${icon}" style="font-size:.9rem"></i><span>${message}</span>`;
            container.appendChild(toast);

            setTimeout(() => {
                toast.style.animation = 'bdToastOut 0.25s ease-in forwards';
                setTimeout(() => toast.remove(), 260);
            }, 2400);
        }

        function showMessage(message, type = 'success') { showToast(message, type); }

        if (!document.getElementById('toast-animations-style')) {
            const style = document.createElement('style');
            style.id = 'toast-animations-style';
            style.textContent = `
                @keyframes bdToastIn  { from { opacity:0; transform:translateY(10px); } to { opacity:1; transform:translateY(0); } }
                @keyframes bdToastOut { from { opacity:1; transform:translateY(0);    } to { opacity:0; transform:translateY(8px); } }
            `;
            document.head.appendChild(style);
        }

        function updateCartBadge(count) {
            document.querySelectorAll('[data-cart-count]').forEach(function(el) {
                el.textContent = count;
                el.style.display = count > 0 ? 'inline-block' : 'none';
            });
        }
        
        // ── BdAudio : utilitaire audio partagé pour tout le frontend ──────
        window.BdAudio = (function () {
            var _ctx = null;
            var _unlocked = false;
            var _lastAt = 0;

            function _getCtx() {
                if (!_ctx || _ctx.state === 'closed') {
                    var C = window.AudioContext || window.webkitAudioContext;
                    if (!C) return null;
                    _ctx = new C();
                }
                if (_ctx.state === 'suspended') _ctx.resume();
                return _ctx;
            }

            function unlock() {
                _unlocked = true;
                _getCtx();
            }

            // tone(freq, duration, volume)  — synthèse Web Audio, pas de fichier
            function tone(freq, dur, vol) {
                if (!_unlocked) return;
                var ctx = _getCtx();
                if (!ctx) return;
                try {
                    var osc = ctx.createOscillator();
                    var gain = ctx.createGain();
                    osc.type = 'sine';
                    osc.frequency.value = freq || 880;
                    osc.connect(gain);
                    gain.connect(ctx.destination);
                    var t = ctx.currentTime;
                    var d = dur || 0.22;
                    var v = vol || 0.18;
                    gain.gain.setValueAtTime(0.001, t);
                    gain.gain.exponentialRampToValueAtTime(v, t + 0.01);
                    gain.gain.exponentialRampToValueAtTime(0.001, t + d);
                    osc.start(t);
                    osc.stop(t + d + 0.01);
                } catch (e) {}
            }

            // Sonneries nommées — throttle 3 s pour éviter le spam
            function play(name) {
                var now = Date.now();
                if (now - _lastAt < 3000) return;
                _lastAt = now;
                switch (name) {
                    case 'confirm':   tone(660, 0.15, 0.20); setTimeout(function(){ tone(880, 0.20, 0.22); }, 160); break;
                    case 'status':    tone(520, 0.12, 0.14); setTimeout(function(){ tone(660, 0.18, 0.16); }, 130); break;
                    case 'delivered': tone(660, 0.12, 0.18); setTimeout(function(){ tone(880, 0.12, 0.20); }, 120); setTimeout(function(){ tone(1100, 0.22, 0.22); }, 250); break;
                    case 'alert':     tone(880, 0.30, 0.22); break;
                    case 'new_order': tone(880, 0.22, 0.22); setTimeout(function(){ tone(660, 0.22, 0.18); }, 250); break;
                    default:          tone(880, 0.22, 0.18);
                }
            }

            document.addEventListener('click',   unlock, { once: true });
            document.addEventListener('keydown',  unlock, { once: true });
            document.addEventListener('touchstart', unlock, { once: true, passive: true });

            return { play: play, unlock: unlock };
        })();
        // ────────────────────────────────────────────────────────────────

        // Fonction pour mettre à jour le compteur du panier
        function updateCartCount() {
            fetch('{{ route("cart.count") }}', {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                const count = data.count || 0;
                document.querySelectorAll('[data-cart-count], #floatingCartBadge').forEach((badge) => {
                    badge.textContent = count;
                    badge.style.display = count > 0 ? 'inline-block' : 'none';
                });
            })
            .catch(error => console.error('Error updating cart count:', error));
        }

        window.BD_PUSH_CONFIG = {
            webEndpoint: @json(route('push.devices.store.web')),
            deleteEndpoint: @json(route('push.devices.destroy.web')),
            apiEndpoint: @json(url('api/push/devices')),
            siteKey: @json(app(\App\Services\SiteContextService::class)->currentSiteKey(request())),
            locale: @json(app()->getLocale()),
            authenticated: @json(auth()->check()),
            csrfToken: @json(csrf_token())
        };

        window.bdRegisterPushDevice = async function(deviceToken, extra = {}) {
            if (!deviceToken) {
                return null;
            }

            const payload = {
                device_token: deviceToken,
                platform: extra.platform || window.BD_PUSH_PLATFORM || 'webview',
                locale: extra.locale || window.BD_PUSH_CONFIG.locale,
                site_key: extra.siteKey || window.BD_PUSH_CONFIG.siteKey,
                metadata: extra.metadata || {}
            };

            const response = await fetch(window.BD_PUSH_CONFIG.webEndpoint, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': window.BD_PUSH_CONFIG.csrfToken
                },
                body: JSON.stringify(payload)
            });

            if (!response.ok) {
                throw new Error(`Push device registration failed (${response.status})`);
            }

            return response.json();
        };

        window.bdProvidePushToken = async function(deviceToken, extra = {}) {
            if (!deviceToken) {
                return null;
            }

            try {
                if (window.localStorage) {
                    window.localStorage.setItem('bd_push_token', deviceToken);
                    if (extra.platform) {
                        window.localStorage.setItem('bd_push_platform', extra.platform);
                    }
                    if (extra.locale) {
                        window.localStorage.setItem('bd_push_locale', extra.locale);
                    }
                }
                if (window.sessionStorage) {
                    window.sessionStorage.setItem('bd_push_token', deviceToken);
                }
            } catch (storageError) {
                console.warn('Push token storage unavailable', storageError);
            }

            return window.bdRegisterPushDevice(deviceToken, {
                platform: extra.platform || window.BD_PUSH_PLATFORM || 'webview',
                locale: extra.locale || window.BD_PUSH_CONFIG.locale,
                siteKey: extra.siteKey || window.BD_PUSH_CONFIG.siteKey,
                metadata: extra.metadata || {}
            });
        };

        window.bdRequestPushTokenFromBridge = function() {
            const request = {
                type: 'REQUEST_PUSH_TOKEN',
                siteKey: window.BD_PUSH_CONFIG.siteKey,
                locale: window.BD_PUSH_CONFIG.locale
            };

            try {
                if (window.ReactNativeWebView && typeof window.ReactNativeWebView.postMessage === 'function') {
                    window.ReactNativeWebView.postMessage(JSON.stringify(request));
                }
            } catch (error) {
                console.warn('ReactNativeWebView push bridge error', error);
            }

            try {
                if (window.webkit?.messageHandlers?.bdPush) {
                    window.webkit.messageHandlers.bdPush.postMessage(request);
                }
            } catch (error) {
                console.warn('iOS push bridge error', error);
            }

            try {
                if (window.Capacitor?.Plugins?.PushNotifications?.requestPermissions) {
                    window.Capacitor.Plugins.PushNotifications.requestPermissions();
                }
            } catch (error) {
                console.warn('Capacitor push bridge error', error);
            }

            return request;
        };

        window.bdConsumePushTokenFromQuery = async function() {
            const params = new URLSearchParams(window.location.search);
            const token = params.get('push_token') || params.get('bd_push_token') || params.get('device_token');
            if (!token) {
                return null;
            }

            const metadata = {};
            ['platform', 'locale', 'site_key'].forEach((key) => {
                const value = params.get(key);
                if (value) {
                    metadata[key] = value;
                }
            });

            const result = await window.bdProvidePushToken(token, metadata);
            params.delete('push_token');
            params.delete('bd_push_token');
            params.delete('device_token');

            const cleanUrl = `${window.location.pathname}${params.toString() ? `?${params.toString()}` : ''}${window.location.hash || ''}`;
            window.history.replaceState({}, document.title, cleanUrl);

            return result;
        };

        window.bdSyncPushDevice = async function(options = {}) {
            if (!window.BD_PUSH_CONFIG.authenticated) {
                return null;
            }

            const tokenCandidate = options.token
                || window.BD_PUSH_TOKEN
                || window.localStorage?.getItem('bd_push_token')
                || window.sessionStorage?.getItem('bd_push_token');

            const token = typeof tokenCandidate === 'string'
                ? tokenCandidate
                : tokenCandidate?.token || tokenCandidate?.device_token || null;

            if (!token || window.__bdRegisteredPushToken === token) {
                return null;
            }

            window.__bdRegisteredPushToken = token;

            try {
                return await window.bdRegisterPushDevice(token, {
                    platform: options.platform || window.BD_PUSH_PLATFORM || 'webview',
                    locale: options.locale || window.BD_PUSH_CONFIG.locale,
                    siteKey: options.siteKey || window.BD_PUSH_CONFIG.siteKey,
                    metadata: options.metadata || {}
                });
            } catch (error) {
                console.warn('Push device registration failed', error);
                return null;
            }
        };

        document.addEventListener('DOMContentLoaded', function() {
            updateCartCount();
            window.bdConsumePushTokenFromQuery();
            window.bdRequestPushTokenFromBridge();
            window.bdSyncPushDevice();
        });

        window.addEventListener('bd:push-token', function(event) {
            const detail = event && event.detail ? event.detail : {};
            if (detail.token) {
                window.bdProvidePushToken(detail.token, detail);
            }
        });

        window.addEventListener('message', function(event) {
            const data = event && event.data ? event.data : {};
            if (!data) {
                return;
            }

            if (data.type === 'bd:push-token' || data.type === 'REQUEST_PUSH_TOKEN_REPLY') {
                const token = data.token || data.device_token || null;
                if (token) {
                    window.bdProvidePushToken(token, data);
                }
            }
        });
    </script>
    
    @yield('scripts')

    @auth
    <script>
    (function() {
        fetch('/notifications/unread-count', {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin'
        }).then(function(r) { return r.json(); }).then(function(d) {
            if (d.count > 0) {
                var b = document.getElementById('notifBadge');
                if (b) { b.textContent = d.count > 99 ? '99+' : d.count; b.style.display = ''; }
            }
        }).catch(function() {});
    })();
    </script>
    @endauth

    {{-- T1.3 — PWA Service Worker + bannière offline --}}
    <div id="bd-offline-banner" role="alert" style="
        display:none;position:fixed;bottom:0;left:0;right:0;z-index:9999;
        background:#92400e;color:#fff;text-align:center;padding:10px 16px;
        font-size:14px;font-weight:600;font-family:inherit;
        box-shadow:0 -2px 8px rgba(0,0,0,.2);
    ">
        ⚠️ Hors ligne — votre commande sera envoyée dès le retour du réseau
    </div>
    <div id="bd-checkout-queued-banner" role="status" style="
        display:none;position:fixed;bottom:0;left:0;right:0;z-index:9999;
        background:#15803d;color:#fff;text-align:center;padding:10px 16px;
        font-size:14px;font-weight:600;font-family:inherit;cursor:pointer;
        box-shadow:0 -2px 8px rgba(0,0,0,.2);
    " onclick="this.style.display='none'">
        ✅ Réseau de retour — commande envoyée avec succès !
    </div>

    <script>
    // ── Service Worker registration ───────────────────────────────────────────
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', function() {
            // Force update du SW — purge l'ancien cache
            navigator.serviceWorker.getRegistrations().then(function(regs) {
                regs.forEach(function(r) { r.update(); });
            });
            navigator.serviceWorker.register('/sw.js', { scope: '/' })
                .then(function(reg) {
                    reg.addEventListener('updatefound', function() {
                        var nw = reg.installing;
                        if (nw) nw.addEventListener('statechange', function() {
                            if (nw.state === 'activated' && navigator.serviceWorker.controller) {
                                window.location.reload();
                            }
                        });
                    });
                    navigator.serviceWorker.addEventListener('message', function(event) {
                        if (event.data && event.data.type === 'bd:checkout-replayed') {
                            var banner = document.getElementById('bd-checkout-queued-banner');
                            if (banner) {
                                banner.style.display = 'block';
                                setTimeout(function() { banner.style.display = 'none'; }, 6000);
                            }
                        }
                    });
                })
                .catch(function() {});
        });
        // Purger le cache manuellement via l'API Cache
        if ('caches' in window) {
            caches.keys().then(function(names) {
                names.forEach(function(name) {
                    if (name.indexOf('20260527-5') === -1) caches.delete(name);
                });
            });
        }
    }

    // ── Bannière offline ─────────────────────────────────────────────────────
    (function() {
        var banner = document.getElementById('bd-offline-banner');
        function update() {
            if (banner) banner.style.display = navigator.onLine ? 'none' : 'block';
        }
        window.addEventListener('online',  update);
        window.addEventListener('offline', update);
        update();
    })();

    // ── Intercepter fetch checkout.api pour afficher le message "en attente" ─
    (function() {
        var _origFetch = window.fetch;
        window.fetch = function(input, init) {
            var url = (typeof input === 'string') ? input : (input && input.url) ? input.url : '';
            return _origFetch.apply(this, arguments).then(function(response) {
                if (url.indexOf('/checkout/api') !== -1 && response.status === 202) {
                    response.clone().json().then(function(data) {
                        if (data && data.queued) {
                            var banner = document.getElementById('bd-offline-banner');
                            if (banner) banner.textContent = '⏳ Commande sauvegardée — envoi automatique dès le retour du réseau';
                        }
                    }).catch(function() {});
                }
                return response;
            });
        };
    })();
    </script>
</body>
</html>
