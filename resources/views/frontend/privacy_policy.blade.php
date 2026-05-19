@php
    $authBrand = app(\App\Services\AuthBrandingService::class)->resolve(request());
    $brandRouteParams = $authBrand['key'] !== 'bantudelice' ? ['brand' => $authBrand['key']] : [];
    $brandColor = $authBrand['primary'];
    $brandDark = $authBrand['primary_dark'];
    $brandSoft = $authBrand['primary_soft'] ?? 'rgba(0, 149, 67, 0.12)';
@endphp
@extends('frontend.layouts.app-modern')
@section('title', 'Politique de confidentialité | ' . $authBrand['name'])
@section('description', $authBrand['privacy_intro'])
@section('body_class', 'bd-support-page bd-privacy-policy-page')
@section('body_style', "--support-brand-color: {$brandColor}; --support-brand-dark: {$brandDark}; --support-brand-soft: {$brandSoft};")

@section('content')
<section class="support-hero">
    <div class="container">
        <span class="section-badge support-hero-badge">Confidentialité</span>
        <h1 class="support-hero-title">Politique de confidentialité</h1>
        <p class="support-hero-copy">{{ $authBrand['privacy_intro'] }}</p>
    </div>
</section>

<section class="section support-section">
    <div class="container">
        <div class="legal-shell">
            <article class="legal-card">
                <section class="legal-block">
                    <h2 class="legal-block-title">1. Périmètre</h2>
                    <p class="legal-block-copy">Cette politique couvre l'utilisation des données personnelles dans les parcours {{ $authBrand['name'] }}, y compris compte, opérations, support et sécurité.</p>
                </section>

                <section class="legal-block">
                    <h2 class="legal-block-title">2. Données collectées</h2>
                    <ul class="legal-list">
                        <li>Informations de compte: nom, email, téléphone, identifiants sociaux éventuels.</li>
                        <li>Données opérationnelles propres à {{ $authBrand['name'] }} selon le service utilisé.</li>
                        <li>Données techniques: session, sécurité, journaux applicatifs et cookies essentiels.</li>
                    </ul>
                </section>

                <section class="legal-block">
                    <h2 class="legal-block-title">3. Finalités</h2>
                    <p class="legal-block-copy">Les données servent à exécuter le service, assurer le support, limiter la fraude, tracer les opérations et améliorer la qualité du produit.</p>
                </section>

                <section class="legal-block">
                    <h2 class="legal-block-title">4. Partage et conservation</h2>
                    <p class="legal-block-copy">Seules les données strictement nécessaires sont partagées avec les partenaires opérationnels et prestataires techniques. Les journaux et historiques utiles à l'intégrité du service peuvent être conservés selon les besoins techniques et légaux.</p>
                </section>

                <section class="legal-block">
                    <h2 class="legal-block-title">5. Vos droits</h2>
                    <p class="legal-block-copy">Vous pouvez demander l'accès, la correction ou la suppression/anonymisation de vos données via le support {{ $authBrand['name'] }}.</p>
                </section>

                <aside class="legal-note">
                    <h3 class="legal-note-title">Contact confidentialité</h3>
                    <p class="legal-block-copy legal-block-copy--compact">Pour toute question liée à la confidentialité: <strong>{{ $authBrand['support_email'] }}</strong></p>
                    <div class="support-actions">
                        <a href="{{ route('data.deletion', $brandRouteParams) }}" class="support-primary-link">Suppression des données</a>
                        <a href="{{ route('contact.us', $brandRouteParams) }}" class="support-secondary-link">Contacter le support</a>
                    </div>
                </aside>
            </article>
        </div>
    </div>
</section>
@endsection
