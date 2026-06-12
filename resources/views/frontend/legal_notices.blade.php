@php
    $authBrand = app(\App\Services\AuthBrandingService::class)->resolve(request());
    $brandRouteParams = $authBrand['key'] !== 'bantudelice' ? ['brand' => $authBrand['key']] : [];
    $brandColor = $authBrand['primary'];
    $brandDark = $authBrand['primary_dark'];
    $brandSoft = $authBrand['primary_soft'] ?? 'rgba(0, 149, 67, 0.12)';
@endphp
@extends('frontend.layouts.app-modern')
@section('title', 'Mentions légales | ' . $authBrand['name'])
@section('description', $authBrand['legal_intro'])
@section('body_class', 'bd-support-page bd-legal-notices-page')
@section('body_style', "--support-brand-color: {$brandColor}; --support-brand-dark: {$brandDark}; --support-brand-soft: {$brandSoft};")

@section('content')
<section class="support-hero support-hero--dark">
    <div class="container">
        <span class="section-badge support-hero-badge">Légal</span>
        <h1 class="support-hero-title">Mentions légales</h1>
        <p class="support-hero-copy">{{ $authBrand['legal_intro'] }}</p>
    </div>
</section>

<section class="section support-section">
    <div class="container">
        <div class="legal-shell">
            <article class="legal-card">
                <section class="legal-block">
                    <h2 class="legal-block-title">1. Service concerné</h2>
                    <p class="legal-block-copy">Ces mentions couvrent l'espace {{ $authBrand['name'] }} et les parcours qui lui sont associés sur la plateforme.</p>
                </section>

                <section class="legal-block">
                    <h2 class="legal-block-title">2. Hébergement et exploitation</h2>
                    <p class="legal-block-copy">Le service est exploité sur une infrastructure cloud sécurisée. Les opérations techniques, journaux et sauvegardes sont administrés par l'équipe en charge de {{ $authBrand['name'] }}.</p>
                </section>

                <section class="legal-block">
                    <h2 class="legal-block-title">3. Contact</h2>
                    <p class="legal-block-copy">Contact principal: <strong>{{ $authBrand['support_email'] }}</strong> ou <strong>{{ $authBrand['support_phone'] }}</strong>.</p>
                </section>

                <section class="legal-block">
                    <h2 class="legal-block-title">4. Propriété intellectuelle</h2>
                    <p class="legal-block-copy">Les contenus, textes, éléments graphiques, interfaces et signes distinctifs liés à {{ $authBrand['name'] }} restent protégés. Toute reproduction non autorisée est interdite.</p>
                </section>

                <div class="support-actions">
                    <a href="{{ route('terms.conditions', $brandRouteParams) }}" class="support-primary-link">Conditions générales</a>
                    <a href="{{ route('privacy.policy', $brandRouteParams) }}" class="support-secondary-link">Confidentialité</a>
                    <a href="{{ route('cookies.policy', $brandRouteParams) }}" class="support-secondary-link">Cookies</a>
                </div>
            </article>
        </div>
    </div>
</section>
@endsection
