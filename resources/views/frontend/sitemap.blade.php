@extends('frontend.layouts.app-modern')
@php
    $sitemapBrand = app(\App\Services\AuthBrandingService::class)->resolve(request());
    $sitemapBrandName = $sitemapBrand['name'] ?? 'la plateforme';
@endphp
@section('title', 'Plan du site | ' . $sitemapBrandName)
@section('description', 'Retrouvez rapidement toutes les pages importantes de ' . $sitemapBrandName . '.')

@php
    $foodEnabled = (bool) config('bantudelice_modules.food.enabled', true);
    $colisEnabled = (bool) config('bantudelice_modules.colis.enabled', true);
    $transportEnabled = (bool) config('bantudelice_modules.transport.enabled', true);
@endphp

@section('content')
<section style="background: linear-gradient(135deg, #111827 0%, #ff5a1f 100%); padding: 150px 0 80px; text-align: center;">
    <div class="container">
        <span class="section-badge" style="background: rgba(255,255,255,0.1); color: white;">
            <i class="fas fa-sitemap"></i> Navigation
        </span>
        <h1 style="color: white; font-size: clamp(2rem, 5vw, 3rem); margin-top: 1rem;">Plan du site</h1>
        <p style="color: rgba(255,255,255,0.85); max-width: 760px; margin: 1rem auto 0; font-size: 1.125rem;">
            Accédez rapidement aux pages principales, aux services et aux informations utiles de {{ $sitemapBrandName }}.
        </p>
    </div>
</section>

<section class="section">
    <div class="container">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 1.5rem; max-width: 1100px; margin: 0 auto;">
            <div style="background: #fff; padding: 1.5rem; border-radius: var(--radius-2xl); box-shadow: var(--shadow-lg);">
                <h2 style="font-size: 1.25rem; margin-bottom: 1rem; color: var(--primary);">Découvrir</h2>
                <div style="display: grid; gap: 0.75rem;">
                    <a href="{{ route('home') }}">Accueil</a>
                    @if($foodEnabled)
                        <a href="{{ route('restaurants.all') }}">Restaurants</a>
                    @endif
                    <a href="{{ route('offers') }}">Offres</a>
                    <a href="{{ route('about.us') }}">À propos</a>
                </div>
            </div>
            <div style="background: #fff; padding: 1.5rem; border-radius: var(--radius-2xl); box-shadow: var(--shadow-lg);">
                <h2 style="font-size: 1.25rem; margin-bottom: 1rem; color: var(--primary);">Services</h2>
                <div style="display: grid; gap: 0.75rem;">
                    @if($colisEnabled)
                        <a href="{{ route('colis.landing') }}">Livraison colis</a>
                        <a href="{{ route('colis.track_public') }}">Suivi colis</a>
                    @endif
                    @if($transportEnabled)
                        <a href="{{ route('transport.index') }}">Transport</a>
                    @endif
                    @if($foodEnabled)
                        <a href="{{ route('track.order') }}">Suivi de commande</a>
                    @endif
                </div>
            </div>
            <div style="background: #fff; padding: 1.5rem; border-radius: var(--radius-2xl); box-shadow: var(--shadow-lg);">
                <h2 style="font-size: 1.25rem; margin-bottom: 1rem; color: var(--primary);">Assistance</h2>
                <div style="display: grid; gap: 0.75rem;">
                    <a href="{{ route('help') }}">Centre d'aide</a>
                    <a href="{{ route('faq') }}">FAQ</a>
                    <a href="{{ route('contact.us') }}">Contact</a>
                    <a href="{{ route('driver') }}">Devenir livreur</a>
                </div>
            </div>
            <div style="background: #fff; padding: 1.5rem; border-radius: var(--radius-2xl); box-shadow: var(--shadow-lg);">
                <h2 style="font-size: 1.25rem; margin-bottom: 1rem; color: var(--primary);">Informations</h2>
                <div style="display: grid; gap: 0.75rem;">
                    <a href="{{ route('terms.conditions') }}">Conditions générales</a>
                    <a href="{{ route('privacy.policy') }}">Confidentialité</a>
                    <a href="{{ route('legal.notices') }}">Mentions légales</a>
                    <a href="{{ route('cookies.policy') }}">Cookies</a>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
