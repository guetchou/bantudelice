@php
    $authBrand = app(\App\Services\AuthBrandingService::class)->resolve(request());
    $brandRouteParams = $authBrand['key'] !== 'bantudelice' ? ['brand' => $authBrand['key']] : [];
    $brandColor = $authBrand['primary'];
    $brandDark = $authBrand['primary_dark'];
    $brandSoft = $authBrand['primary_soft'] ?? 'rgba(0, 149, 67, 0.12)';
@endphp
@extends('frontend.layouts.app-modern')
@section('title', 'Politique cookies | ' . $authBrand['name'])
@section('description', $authBrand['cookies_intro'])
@section('body_class', 'bd-support-page bd-cookies-page')
@section('body_style', "--support-brand-color: {$brandColor}; --support-brand-dark: {$brandDark}; --support-brand-soft: {$brandSoft};")

@section('content')
<section class="support-hero">
    <div class="container">
        <span class="section-badge support-hero-badge">Cookies</span>
        <h1 class="support-hero-title">Politique cookies</h1>
        <p class="support-hero-copy">{{ $authBrand['cookies_intro'] }}</p>
    </div>
</section>

<section class="section support-section">
    <div class="container">
        <div class="legal-shell">
            <article class="legal-card">
                <section class="legal-block">
                    <h2 class="legal-block-title">1. Usage des cookies</h2>
                    <p class="legal-block-copy">
                        Les cookies servent à maintenir la session, sécuriser la connexion, mémoriser certaines préférences et stabiliser les parcours {{ $authBrand['name'] }}.
                    </p>
                </section>

                <section class="legal-block">
                    <h2 class="legal-block-title">2. Catégories utilisées</h2>
                    <ul class="legal-list">
                        <li>Cookies essentiels de session et d'authentification.</li>
                        <li>Cookies fonctionnels liés à l'expérience utilisateur.</li>
                        <li>Cookies techniques de sécurité et de prévention des abus.</li>
                    </ul>
                </section>

                <section class="legal-block">
                    <h2 class="legal-block-title">3. Gestion de vos préférences</h2>
                    <p class="legal-block-copy">
                        Vous pouvez bloquer ou supprimer les cookies depuis votre navigateur. Certaines fonctionnalités de {{ $authBrand['name'] }} peuvent alors être limitées.
                    </p>
                </section>

                <div class="support-actions">
                    <a href="{{ route('privacy.policy', $brandRouteParams) }}" class="support-primary-link">Confidentialité</a>
                    <a href="{{ route('data.deletion', $brandRouteParams) }}" class="support-secondary-link">Suppression des données</a>
                </div>
            </article>
        </div>
    </div>
</section>
@endsection
