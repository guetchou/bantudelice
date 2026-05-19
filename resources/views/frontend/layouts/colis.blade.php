<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>@yield('title', 'Mema')</title>
    <meta name="description" content="@yield('description', 'Mema au Congo : expédition, suivi et gestion de colis.')">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="{{ asset('frontend/css/modern.css') }}" rel="stylesheet">
    @yield('styles')
    @yield('style')
    <style>
        :root{
            --module-primary:#2448ff;
            --module-secondary:#0f172a;
            --module-accent:#00a86b;
            --module-muted:#64748b;
            --module-line:rgba(15,23,42,.08);
            --module-bg:#f8fafc;
        }
        body.bd-colis-shell{
            margin:0;
            background:var(--module-bg);
            color:var(--module-secondary);
            font-family:'Plus Jakarta Sans',sans-serif;
        }
        .module-shell__header{
            position:sticky;
            top:0;
            z-index:90;
            background:rgba(255,255,255,.95);
            backdrop-filter:blur(18px);
            border-bottom:1px solid var(--module-line);
        }
        .module-shell__header-inner,
        .module-shell__footer-inner{
            max-width:1280px;
            margin:0 auto;
            padding:0 24px;
        }
        .module-shell__header-inner{
            min-height:84px;
            display:flex;
            align-items:center;
            justify-content:space-between;
            gap:18px;
        }
        .module-shell__brand{
            display:inline-flex;
            align-items:center;
            gap:12px;
            color:var(--module-secondary);
            text-decoration:none;
            font-family:'Outfit',sans-serif;
            font-weight:800;
            font-size:1.1rem;
        }
        .module-shell__brand-mark{
            display:inline-flex;
            align-items:center;
            justify-content:center;
            width:42px;
            height:42px;
            border-radius:14px;
            background:linear-gradient(135deg,var(--module-primary),var(--module-accent));
            color:#fff;
            font-size:.9rem;
            font-weight:900;
            letter-spacing:.08em;
        }
        .module-shell__brand span{color:var(--module-primary)}
        .module-shell__nav{
            display:flex;
            align-items:center;
            gap:10px;
            flex-wrap:wrap;
        }
        .module-shell__nav a{
            display:inline-flex;
            align-items:center;
            min-height:42px;
            padding:0 16px;
            border-radius:999px;
            color:var(--module-secondary);
            text-decoration:none;
            font-weight:700;
            font-size:.92rem;
        }
        .module-shell__nav a.is-active,
        .module-shell__nav a:hover{
            background:#eaf0ff;
            color:var(--module-primary);
        }
        .module-shell__actions{
            display:flex;
            align-items:center;
            gap:10px;
            flex-wrap:wrap;
        }
        .module-shell__button{
            display:inline-flex;
            align-items:center;
            justify-content:center;
            min-height:44px;
            padding:0 18px;
            border-radius:999px;
            text-decoration:none;
            font-weight:800;
            font-size:.9rem;
        }
        .module-shell__button--ghost{
            color:var(--module-secondary);
            border:1px solid var(--module-line);
            background:#fff;
        }
        .module-shell__button--primary{
            color:#fff;
            background:var(--module-primary);
            box-shadow:0 14px 28px rgba(36,72,255,.16);
        }
        .module-shell__main{min-height:calc(100vh - 220px)}
        .module-shell__footer{
            margin-top:48px;
            background:#fff;
            border-top:1px solid var(--module-line);
        }
        .module-shell__footer-inner{
            padding-top:28px;
            padding-bottom:28px;
        }
        .module-shell__footer-grid{
            display:grid;
            grid-template-columns:1.2fr 1fr 1fr;
            gap:24px;
        }
        .module-shell__footer h4{
            margin:0 0 12px;
            font-size:1rem;
            font-weight:800;
        }
        .module-shell__footer p,
        .module-shell__footer a{
            color:var(--module-muted);
            font-size:.92rem;
            line-height:1.7;
            text-decoration:none;
        }
        .module-shell__footer-links{
            display:flex;
            flex-direction:column;
            gap:6px;
        }
        .module-shell__footer-bottom{
            margin-top:22px;
            padding-top:18px;
            border-top:1px solid var(--module-line);
            display:flex;
            align-items:center;
            justify-content:space-between;
            gap:16px;
            flex-wrap:wrap;
            color:var(--module-muted);
            font-size:.88rem;
        }
        @media (max-width: 992px){
            .module-shell__header-inner{padding-top:14px;padding-bottom:14px;align-items:flex-start;flex-direction:column}
            .module-shell__footer-grid{grid-template-columns:1fr}
        }
    </style>
</head>
<body class="bd-colis-shell @yield('body_class')">
@php
    $colisAccountUrl = auth()->check() ? route('user.profile') : route('user.login', ['redirect' => url()->current()]);
    $colisAccountLabel = auth()->check() ? 'Mon compte' : 'Connexion';
    $hideModuleHeader = trim($__env->yieldContent('hide_module_header')) !== '';
    $hideModuleFooter = trim($__env->yieldContent('hide_module_footer')) !== '';
@endphp

@unless($hideModuleHeader)
<header class="module-shell__header">
    <div class="module-shell__header-inner">
        <a href="{{ route('colis.landing') }}" class="module-shell__brand">
            <span class="module-shell__brand-mark" aria-hidden="true">M</span>
            <strong>Mema</strong>
        </a>
        <nav class="module-shell__nav" aria-label="Navigation colis">
            <a href="{{ route('colis.landing') }}" class="{{ request()->routeIs('colis.landing') ? 'is-active' : '' }}">Accueil Mema</a>
            <a href="{{ route('colis.create') }}" class="{{ request()->routeIs('colis.create') ? 'is-active' : '' }}">Expédier</a>
            <a href="{{ route('colis.track_public') }}" class="{{ request()->routeIs('colis.track_public') ? 'is-active' : '' }}">Suivi</a>
            @auth
                <a href="{{ route('colis.mes-envois') }}" class="{{ request()->routeIs('colis.mes-envois', 'colis.show') ? 'is-active' : '' }}">Mes envois</a>
            @endauth
        </nav>
        <div class="module-shell__actions">
            <a href="{{ route('help', ['brand' => 'mema']) }}" class="module-shell__button module-shell__button--ghost">Aide</a>
            <a href="{{ $colisAccountUrl }}" class="module-shell__button module-shell__button--ghost">{{ $colisAccountLabel }}</a>
            <a href="{{ route('colis.create') }}" class="module-shell__button module-shell__button--primary">Nouveau colis</a>
        </div>
    </div>
</header>
@endunless

<main class="module-shell__main">
    @yield('content')
</main>

@unless($hideModuleFooter)
<footer class="module-shell__footer">
    <div class="module-shell__footer-inner">
        <div class="module-shell__footer-grid">
            <div class="module-shell__footer">
                <h4>Mema</h4>
                <p>Mema simplifie vos expéditions avec un suivi clair, une prise en charge rapide et une remise confirmée.</p>
            </div>
            <div class="module-shell__footer">
                <h4>Mema</h4>
                <div class="module-shell__footer-links">
                    <a href="{{ route('colis.landing') }}">Accueil Mema</a>
                    <a href="{{ route('colis.create') }}">Expédier un colis</a>
                    <a href="{{ route('colis.track_public') }}">Suivre un envoi</a>
                    @auth
                        <a href="{{ route('colis.mes-envois') }}">Mes envois</a>
                    @endauth
                </div>
            </div>
            <div class="module-shell__footer">
                <h4>Support</h4>
                <div class="module-shell__footer-links">
                    <a href="{{ route('help', ['brand' => 'mema']) }}">Centre d'aide</a>
                    <a href="{{ route('faq') }}">FAQ</a>
                    <a href="{{ route('contact.us', ['brand' => 'mema']) }}">Nous contacter</a>
                    <a href="{{ route('terms.conditions', ['brand' => 'mema']) }}">Conditions générales</a>
                    <a href="{{ route('privacy.policy', ['brand' => 'mema']) }}">Confidentialité</a>
                    <a href="{{ route('cookies.policy', ['brand' => 'mema']) }}">Cookies</a>
                </div>
            </div>
        </div>
        <div class="module-shell__footer-bottom">
            <span>© {{ date('Y') }} Mema</span>
            <span>Suivi · Expédition · Réclamation</span>
        </div>
    </div>
</footer>
@endunless

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
@yield('scripts')
</body>
</html>
