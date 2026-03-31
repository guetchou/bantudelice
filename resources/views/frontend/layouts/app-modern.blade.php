<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>@yield('title', 'BantuDelice - Livraison à domicile')</title>
    <meta name="description" content="@yield('description', 'BantuDelice - Commande de repas et livraison food a Brazzaville.')">
    <meta name="keywords" content="livraison, restaurant, colis, taxi, Congo, Brazzaville">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    
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
    
    <!-- Styles pour le menu déroulant -->
    <style>
        :root {
            --bd-orange: #ff5a1f;
            --bd-blue: #2448ff;
            --bd-lime: #166534;
            --bd-ink: #081226;
            --bd-paper: #eef3ff;
            --bd-line: rgba(8, 18, 38, 0.12);
        }

        body.bd-future-shell {
            background: var(--bd-paper);
            color: var(--bd-ink);
            width: 100%;
            max-width: 100%;
            overflow-x: hidden;
        }

        body.bd-future-shell main {
            position: relative;
            z-index: 1;
            width: 100%;
            max-width: 100%;
            overflow-x: clip;
        }

        html {
            width: 100%;
            max-width: 100%;
            overflow-x: hidden;
        }

        img,
        video,
        iframe,
        canvas,
        svg {
            max-width: 100%;
        }

        .container,
        .container-fluid {
            width: 100%;
            max-width: 100%;
        }

        .modern-header {
            position: sticky;
            top: 12px;
            z-index: 80;
            padding: 0 12px 12px;
            background: transparent;
            backdrop-filter: none;
            border-bottom: 0;
        }

        .modern-header::before {
            display: none;
        }

        .modern-header .header-container {
            position: relative;
            z-index: 1;
            max-width: 1380px;
            margin: 0 auto;
            padding: 0 18px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            min-height: 82px;
            background: rgba(255,255,255,0.96);
            border: 1px solid var(--bd-line);
            border-radius: 30px;
            box-shadow: 0 20px 50px rgba(8,18,38,0.08);
            backdrop-filter: blur(18px);
            width: calc(100% - 24px);
        }

        .bd-brand-lockup {
            display: inline-flex;
            align-items: center;
            gap: 0;
            text-decoration: none;
            flex-shrink: 0;
        }

        .bd-brand-logo-wrap {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0;
            background: transparent;
            border: 0;
            box-shadow: none;
            line-height: 0;
        }

        .bd-brand-logo-wrap img {
            display: block;
            height: 48px;
            width: auto;
            max-width: 180px;
            object-fit: contain;
            image-rendering: -webkit-optimize-contrast;
        }

        .nav-menu {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex: 1 1 auto;
            min-width: 0;
            flex-wrap: nowrap;
            gap: 6px;
            padding: 7px;
            border-radius: 22px;
            background: #f7f9ff;
            border: 1px solid rgba(36,72,255,0.12);
            box-shadow: none;
            overflow: hidden;
        }

        .nav-menu .nav-link,
        .nav-menu .nav-dropdown-toggle {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 44px;
            padding: 0 16px;
            border-radius: 16px;
            color: var(--bd-ink);
            font-weight: 800;
            font-size: 0.93rem;
            text-decoration: none;
            transition: .22s ease;
        }

        .nav-menu .nav-link:hover,
        .nav-menu .nav-dropdown-toggle:hover {
            background: #ffffff;
            color: var(--bd-blue);
        }

        .nav-link--service {
            font-weight: 800 !important;
        }

        .nav-link--primary {
            background: #ffffff;
            color: var(--bd-ink) !important;
            border: 1px solid rgba(36,72,255,0.1);
            box-shadow: none;
        }

        .nav-link--primary:hover {
            background: #ffffff !important;
            color: var(--bd-blue) !important;
            opacity: 1;
        }

        .nav-link--utility {
            color: #5d6880 !important;
        }

        .nav-actions {
            display: inline-flex;
            align-items: center;
            gap: 12px;
            flex-shrink: 0;
            min-width: 0;
        }

        .bd-site-switcher {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 6px;
            border-radius: 16px;
            background: rgba(247,249,255,0.9);
            border: 1px solid rgba(36,72,255,0.1);
        }

        .bd-site-switcher__dropdown {
            position: relative;
        }

        .bd-site-switcher__trigger {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            min-height: 34px;
            padding: 0 10px 0 6px;
            border-radius: 999px;
            background: #ffffff;
            color: var(--bd-ink);
            border: 1px solid rgba(36,72,255,0.08);
            font-size: 0.72rem;
            font-weight: 800;
            letter-spacing: 0.08em;
        }

        .bd-site-switcher__icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 28px;
            height: 28px;
            border-radius: 999px;
            background: #ffffff;
            color: var(--bd-blue);
            border: 1px solid rgba(36,72,255,0.08);
            font-size: 0.82rem;
        }

        .bd-site-switcher__current {
            line-height: 1;
        }

        .bd-site-switcher__chevron {
            font-size: 0.62rem;
            color: #7a8799;
        }

        .bd-site-switcher__menu {
            position: absolute;
            top: calc(100% + 8px);
            right: 0;
            min-width: 150px;
            padding: 8px;
            border-radius: 18px;
            background: #ffffff;
            border: 1px solid rgba(8,18,38,0.08);
            box-shadow: 0 18px 40px rgba(8,18,38,0.12);
            opacity: 0;
            visibility: hidden;
            transform: translateY(-8px);
            pointer-events: none;
            transition: .22s ease;
            z-index: 1200;
        }

        .bd-site-switcher__dropdown.open .bd-site-switcher__menu {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
            pointer-events: auto;
        }

        .bd-site-switcher__option {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 10px 12px;
            border-radius: 12px;
            text-decoration: none;
            color: var(--bd-ink);
            font-size: 0.8rem;
            font-weight: 800;
        }

        .bd-site-switcher__option small {
            color: #7a8799;
            font-size: 0.72rem;
            font-weight: 700;
            letter-spacing: 0;
            text-transform: none;
        }

        .bd-site-switcher__option:hover,
        .bd-site-switcher__option.is-active {
            background: #eef3ff;
            color: var(--bd-blue);
        }

        .bd-site-switcher__locales,
        .bd-site-switcher__sites {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            flex-wrap: wrap;
        }

        .bd-site-switcher__pill {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 28px;
            padding: 0 10px;
            border-radius: 999px;
            background: #ffffff;
            color: var(--bd-ink);
            border: 1px solid rgba(36,72,255,0.08);
            text-decoration: none;
            font-size: 0.72rem;
            font-weight: 800;
            letter-spacing: 0.08em;
            transition: all .18s ease;
        }

        .bd-site-switcher__pill:hover,
        .bd-site-switcher__pill.is-active {
            background: var(--bd-blue);
            color: #ffffff;
            border-color: var(--bd-blue);
        }

        .cart-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 48px;
            height: 48px;
            border-radius: 18px;
            background: var(--bd-blue);
            color: #ffffff;
            border: 0;
            box-shadow: 0 14px 28px rgba(36,72,255,0.2);
        }

        .header-profile-chip {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            min-height: 52px;
            padding: 6px 10px 6px 8px;
            border-radius: 22px;
            background: #f7f9ff;
            border: 1px solid rgba(36,72,255,0.12);
            box-shadow: none;
            text-decoration: none;
        }

        .header-profile-avatar {
            width: 38px;
            height: 38px;
            border-radius: 999px;
            object-fit: cover;
            flex-shrink: 0;
        }

        .header-profile-copy {
            display: flex;
            flex-direction: column;
            line-height: 1.05;
        }

        .header-profile-copy strong {
            color: var(--bd-ink);
            font-size: 0.9rem;
            font-weight: 800;
        }

        .header-profile-copy small {
            color: var(--bd-blue);
            font-size: 0.72rem;
            text-transform: uppercase;
            letter-spacing: 0.12em;
            font-weight: 700;
        }

        .modern-header .btn.btn-primary.btn-sm,
        .modern-header .btn.btn-secondary.btn-sm {
            min-height: 46px;
            padding: 0 18px;
            font-weight: 800;
            border-radius: 18px !important;
        }

        .modern-header .btn.btn-commander.btn-sm {
            min-height: 48px;
            padding: 0 20px;
            font-weight: 800;
            border-radius: 18px !important;
            background: var(--bd-orange) !important;
            border: 0 !important;
            color: #ffffff !important;
            box-shadow: 0 14px 28px rgba(255,90,31,0.22) !important;
        }

        .modern-header .btn.btn-primary.btn-sm {
            background: var(--bd-blue) !important;
            border: 0 !important;
            box-shadow: 0 14px 28px rgba(36,72,255,0.2) !important;
        }

        .modern-header .btn.btn-secondary.btn-sm {
            background: #ffffff !important;
            border: 1px solid rgba(36,72,255,0.14) !important;
            color: var(--bd-blue) !important;
        }

        #toastContainer {
            max-width: calc(100vw - 24px);
            right: 12px !important;
            left: auto !important;
        }

        #toastContainer > div {
            min-width: 0 !important;
            width: min(360px, calc(100vw - 24px));
            max-width: calc(100vw - 24px) !important;
        }

        .flash-message {
            max-width: calc(100vw - 24px) !important;
            right: 12px !important;
            left: auto !important;
        }

        @media (max-width: 1279px) {
            .modern-header .header-container {
                padding: 0 14px;
                gap: 10px;
                min-height: 76px;
            }

            .nav-menu .nav-link,
            .nav-menu .nav-dropdown-toggle {
                min-height: 40px;
                padding: 0 12px;
                font-size: 0.86rem;
            }

            .nav-actions {
                gap: 8px;
            }

            .modern-header .btn.btn-commander.btn-sm,
            .modern-header .btn.btn-primary.btn-sm,
            .modern-header .btn.btn-secondary.btn-sm {
                min-height: 42px;
                padding: 0 14px;
                font-size: 0.82rem;
            }

            .header-profile-copy strong {
                max-width: 88px;
            }
        }

        /* Dropdown Menu - Cacher par défaut (spécificité élevée) */
        .modern-header .nav-dropdown {
            position: relative;
        }
        
        .modern-header .nav-dropdown-menu {
            position: absolute !important;
            top: 100% !important;
            left: 0 !important;
            min-width: 200px;
            background: white;
            border: 1px solid rgba(8,18,38,0.08);
            border-radius: 18px;
            box-shadow: 0 18px 40px rgba(8,18,38,0.12);
            padding: 8px 0;
            margin-top: 8px;
            opacity: 0 !important;
            visibility: hidden !important;
            transform: translateY(-10px);
            transition: all 0.3s ease;
            z-index: 1000;
            display: flex;
            flex-direction: column;
            pointer-events: none;
        }
        
        .modern-header .nav-dropdown-menu a {
            padding: 10px 16px;
            color: var(--bd-ink);
            text-decoration: none;
            font-size: 0.9375rem;
            transition: background 0.2s ease;
            white-space: nowrap;
            display: block;
        }
        
        .modern-header .nav-dropdown-menu a:hover {
            background: #eef3ff;
            color: var(--bd-blue);
        }
        
        /* Afficher le menu au hover */
        .modern-header .nav-dropdown:hover .nav-dropdown-menu,
        .modern-header .nav-dropdown.open .nav-dropdown-menu {
            opacity: 1 !important;
            visibility: visible !important;
            transform: translateY(0);
            pointer-events: auto;
        }
        
        /* Mobile : menu toujours visible si actif */
        @media (max-width: 1199px) {
            .modern-header {
                top: 0;
                padding: 0 0 10px;
            }

            .modern-header .header-container {
                min-height: 74px;
                margin: 0 12px;
                border-radius: 0 0 26px 26px;
                padding: 0 16px;
            }

            .nav-menu {
                top: 88px;
                left: 12px;
                right: 12px;
                background: rgba(255,255,255,0.98);
                border: 1px solid rgba(8,18,38,0.08);
                border-radius: 28px;
                padding: 18px 16px;
                box-shadow: 0 25px 60px rgba(8,18,38,0.12);
                overflow-y: auto;
                overflow-x: hidden;
            }

            .nav-menu .nav-link,
            .nav-menu .nav-dropdown-toggle {
                width: 100%;
                justify-content: space-between;
                background: #f7f9ff;
            }

            .modern-header .nav-dropdown-menu {
                position: static !important;
                opacity: 0 !important;
                visibility: hidden !important;
                transform: none !important;
                box-shadow: none;
                background: transparent;
                padding: 0;
                margin: 0;
                margin-left: 1rem;
                pointer-events: none;
                max-height: 0;
                overflow: hidden;
                transition: max-height 0.25s ease;
            }

            .modern-header .nav-dropdown.open .nav-dropdown-menu {
                opacity: 1 !important;
                visibility: visible !important;
                pointer-events: auto;
                max-height: 300px;
            }
            
            .modern-header .nav-dropdown-menu a {
                padding: 8px 0;
                font-size: 0.875rem;
            }

            .header-profile-chip {
                display: none !important;
            }

            .nav-actions .btn.btn-primary.btn-sm,
            .nav-actions .btn.btn-secondary.btn-sm {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                min-height: 36px;
                padding: 0.45rem 0.9rem;
                white-space: nowrap;
            }

            .bd-site-switcher {
                display: none;
            }

            #toastContainer {
                top: 12px !important;
            }
        }
    </style>
</head>
<body class="bd-future-shell">
    <!-- Header Moderne -->
    <header class="modern-header float-nav-shell" id="header">
        <div class="header-container float-nav-inner">
            <!-- Logo -->
            <a href="{{ route('home') }}" class="logo float-brand bd-brand-lockup">
                <span class="bd-brand-logo-wrap">
                    <img src="{{ asset('frontend/images/BuntuDelice.png') }}" alt="BantuDelice">
                </span>
            </a>
            
            @php
                $foodEnabled = (bool) config('bantudelice_modules.food.enabled', true);
                $colisEnabled = (bool) config('bantudelice_modules.colis.enabled', true);
                $transportEnabled = (bool) config('bantudelice_modules.transport.enabled', true);
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
                @if($colisEnabled || $transportEnabled)
                    <div class="nav-dropdown">
                        <button type="button" class="nav-link nav-dropdown-toggle nav-link--utility" aria-expanded="false" style="background:none;border:0;padding:0;">
                            Services <i class="fas fa-chevron-down" style="font-size: 0.7rem; margin-left: 4px;"></i>
                        </button>
                        <div class="nav-dropdown-menu">
                            @if($colisEnabled)
                                <a href="{{ route('colis.landing') }}">{{ trans('ui.nav.colis') }}</a>
                                <a href="{{ route('colis.track_public') }}">Suivre un colis</a>
                            @endif
                            @if($transportEnabled)
                                <a href="{{ route('transport.taxi') }}">Transport</a>
                            @endif
                        </div>
                    </div>
                @endif
                @if($foodEnabled || $colisEnabled)
                    <div class="nav-dropdown">
                        <button type="button" class="nav-link nav-dropdown-toggle nav-link--utility" aria-expanded="false" style="background:none;border:0;padding:0;">
                            {{ trans('ui.nav.suivi') }} <i class="fas fa-chevron-down" style="font-size: 0.7rem; margin-left: 4px;"></i>
                        </button>
                        <div class="nav-dropdown-menu">
                            @if($foodEnabled)
                                <a href="{{ route('track.order') }}">{{ trans('ui.nav.suivi') }} commande</a>
                            @endif
                            @if($colisEnabled)
                                <a href="{{ route('colis.track_public') }}">{{ trans('ui.nav.suivi') }} colis</a>
                                @if(Auth::check())
                                    <a href="{{ route('colis.mes-envois') }}">Mes envois</a>
                                @endif
                            @endif
                        </div>
                    </div>
                @endif
                <a href="{{ route('help') }}" class="nav-link nav-link--utility">{{ trans('ui.nav.help') }}</a>
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
                    <a href="{{ route('restaurants.all') }}" style="display:inline-flex;align-items:center;background:#16a34a;color:#fff;font-weight:700;font-size:.82rem;padding:.5rem 1rem;border-radius:999px;border:none;cursor:pointer;" style="border-radius: 999px;">{{ trans('ui.home.service_cards.restaurants.cta') }}</a>
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
    
    <!-- Messages Flash (Notifications) -->
    @if(session('message'))
        <div id="flash-message" class="flash-message flash-success" style="position: fixed; top: 20px; right: 20px; padding: 1rem 1.5rem; background: #05944F; color: white; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.15); z-index: 10000; font-weight: 500; animation: slideIn 0.3s ease-out;">
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
        <style>
            @keyframes slideIn {
                from {
                    transform: translateX(100%);
                    opacity: 0;
                }
                to {
                    transform: translateX(0);
                    opacity: 1;
                }
            }
            @keyframes slideOut {
                from {
                    transform: translateX(0);
                    opacity: 1;
                }
                to {
                    transform: translateX(100%);
                    opacity: 0;
                }
            }
        </style>
    @endif
    
    @if(session('error'))
        <div id="flash-error" class="flash-message flash-error" style="position: fixed; top: 20px; right: 20px; padding: 1rem 1.5rem; background: #DC2626; color: white; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.15); z-index: 10000; font-weight: 500; animation: slideIn 0.3s ease-out;">
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
    <footer class="modern-footer">
        <div class="container">
            <div class="footer-grid">
                <!-- Brand -->
                <div class="footer-brand">
                    <div class="footer-logo">
                        <img src="{{ asset('frontend/images/BuntuDelice.png') }}" alt="BantuDelice" style="height: 45px; margin-bottom: 1rem;">
                    </div>
                    <p>Votre partenaire de confiance pour commander vos repas et decouvrir les meilleures tables locales.</p>
                </div>
                
                <!-- Liens Rapides -->
                <div class="footer-column">
                    <h4>Food</h4>
                    <div class="footer-links">
                        @if($foodEnabled)
                            <a href="{{ route('restaurants.all') }}">Restaurants</a>
                            <a href="{{ route('track.order') }}">Suivre une commande</a>
                        @endif
                        <a href="{{ route('offers') }}">Offres du moment</a>
                        <a href="{{ route('about.us') }}">À propos de nous</a>
                        <a href="{{ route('driver') }}">Devenir livreur</a>
                        <a href="{{ route('partner') }}">Devenir partenaire</a>
                        <a href="{{ route('contact.us') }}">Nous contacter</a>
                    </div>
                </div>
                
                <!-- Informations -->
                <div class="footer-column">
                    <h4>Informations</h4>
                    <div class="footer-links">
                        <a href="{{ route('terms.conditions') }}">Conditions générales</a>
                        <a href="{{ route('privacy.policy') }}">Politique de confidentialité</a>
                        <a href="{{ route('refund.policy') }}">Politique de remboursement</a>
                        <a href="{{ route('faq') }}">FAQ</a>
                        <a href="{{ route('help') }}">Centre d'aide</a>
                        <a href="{{ route('offers') }}">Offres et promotions</a>
                        <a href="{{ route('data.deletion') }}">Suppression des données</a>
                        <a href="{{ route('guidance.execution') }}">Guidance execution</a>
                    </div>
                </div>
                
                <!-- Ressources -->
                <div class="footer-column">
                    <h4>Autres services</h4>
                    <p style="color: rgba(255,255,255,0.6); font-size: 0.875rem; margin-bottom: 1rem;">
                        Gardez un acces simple aux autres parcours sans les melanger au tunnel food.
                    </p>
                    <div class="footer-links">
                        @if($colisEnabled)
                            <a href="{{ route('colis.landing') }}">Service colis</a>
                            <a href="{{ route('colis.track_public') }}">Suivre un colis</a>
                        @endif
                        @if($transportEnabled)
                            <a href="{{ route('transport.taxi') }}">Transport</a>
                        @endif
                        <a href="{{ route('help') }}">Centre d'aide</a>
                        <a href="{{ route('site.map') }}">Plan du site</a>
                    </div>
                </div>
            </div>
            
            <!-- Bottom -->
            <div class="footer-bottom">
                <p class="footer-copyright">
                    &copy; {{ date('Y') }} BantuDelice. Tous droits réservés.
                </p>
                <div class="footer-bottom-links">
                    <a href="{{ route('legal.notices') }}">Mentions légales</a>
                    <a href="{{ route('cookies.policy') }}">Cookies</a>
                    <a href="{{ route('site.map') }}">Plan du site</a>
                </div>
            </div>
        </div>
    </footer>
    
    <!-- Back to Top -->
    <button type="button" id="backToTop" aria-label="Retour en haut" style="
        position: fixed;
        bottom: 30px;
        right: 30px;
        width: 50px;
        height: 50px;
        background: var(--primary);
        color: white;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        opacity: 0;
        visibility: hidden;
        transition: all 0.3s ease;
        z-index: 999;
        box-shadow: 0 4px 15px rgba(255, 107, 53, 0.4);
    ">
        <i class="fas fa-arrow-up"></i>
    </button>
    
    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Header scroll effect
        const header = document.getElementById('header');
        window.addEventListener('scroll', () => {
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
    <div id="toastContainer" style="position: fixed; top: 20px; right: 20px; z-index: 10000; display: flex; flex-direction: column; gap: 0.75rem; pointer-events: none;"></div>
    
    <script>
        // Fonction globale pour afficher un toast (utilisable partout)
        function showToast(message, type = 'success') {
            const container = document.getElementById('toastContainer');
            if (!container) {
                // Créer le container s'il n'existe pas
                const newContainer = document.createElement('div');
                newContainer.id = 'toastContainer';
                newContainer.style.cssText = 'position: fixed; top: 20px; right: 20px; z-index: 10000; display: flex; flex-direction: column; gap: 0.75rem; pointer-events: none;';
                document.body.appendChild(newContainer);
                return showToast(message, type);
            }
            
            const toast = document.createElement('div');
            toast.style.cssText = `
                background: ${type === 'success' ? '#05944F' : '#EF4444'};
                color: white;
                padding: 1rem 1.5rem;
                border-radius: 12px;
                box-shadow: 0 8px 24px rgba(0,0,0,0.15);
                font-weight: 500;
                display: flex;
                align-items: center;
                gap: 0.75rem;
                min-width: 300px;
                max-width: 400px;
                animation: slideInRight 0.3s ease-out;
                pointer-events: auto;
            `;
            
            const icon = type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle';
            const iconEl = document.createElement('i');
            iconEl.className = `fas ${icon}`;
            iconEl.style.fontSize = '1.25rem';

            const textEl = document.createElement('span');
            textEl.textContent = message;

            toast.appendChild(iconEl);
            toast.appendChild(textEl);
            
            container.appendChild(toast);
            
            setTimeout(() => {
                toast.style.animation = 'slideOutRight 0.3s ease-out';
                setTimeout(() => toast.remove(), 300);
            }, 3000);
        }
        
        // Alias pour compatibilité avec l'ancien code
        function showMessage(message, type = 'success') {
            showToast(message, type);
        }
        
        // Ajouter les styles d'animation si pas déjà présents
        if (!document.getElementById('toast-animations-style')) {
            const style = document.createElement('style');
            style.id = 'toast-animations-style';
            style.textContent = `
                @keyframes slideInRight {
                    from {
                        transform: translateX(100%);
                        opacity: 0;
                    }
                    to {
                        transform: translateX(0);
                        opacity: 1;
                    }
                }
                @keyframes slideOutRight {
                    from {
                        transform: translateX(0);
                        opacity: 1;
                    }
                    to {
                        transform: translateX(100%);
                        opacity: 0;
                    }
                }
            `;
            document.head.appendChild(style);
        }
        
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
</body>
</html>
