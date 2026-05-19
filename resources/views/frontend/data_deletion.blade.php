@php
    $authBrand = app(\App\Services\AuthBrandingService::class)->resolve(request());
    $brandRouteParams = $authBrand['key'] !== 'bantudelice' ? ['brand' => $authBrand['key']] : [];
    $brandColor = $authBrand['primary'];
    $brandDark = $authBrand['primary_dark'];
    $brandSoft = $authBrand['primary_soft'] ?? 'rgba(0, 149, 67, 0.12)';
@endphp
@extends('frontend.layouts.app-modern')
@section('title', 'Suppression des données | ' . $authBrand['name'])
@section('description', $authBrand['data_deletion_intro'])
@section('body_class', 'bd-support-page bd-data-deletion-page')
@section('body_style', "--support-brand-color: {$brandColor}; --support-brand-dark: {$brandDark}; --support-brand-soft: {$brandSoft};")

@section('content')
<section class="support-hero support-hero--dark">
    <div class="container">
        <span class="section-badge support-hero-badge">Données</span>
        <h1 class="support-hero-title">Suppression des données</h1>
        <p class="support-hero-copy">{{ $authBrand['data_deletion_intro'] }}</p>
    </div>
</section>

<section class="section support-section">
    <div class="container">
        <div class="legal-shell">
            <article class="legal-card">
                <section class="legal-block">
                    <h2 class="legal-block-title">1. Depuis votre compte</h2>
                    <p class="legal-block-copy">
                        Si vous êtes connecté, vous pouvez demander la suppression via l'interface lorsque le parcours le permet. L'opération peut prendre la forme d'une anonymisation pour préserver l'intégrité technique de l'historique.
                    </p>
                </section>

                <section class="legal-block">
                    <h2 class="legal-block-title">2. Demande manuelle</h2>
                    <p class="legal-block-copy">
                        Si vous n'avez plus accès à votre compte, écrivez à <strong>{{ $authBrand['support_email'] }}</strong> en indiquant les éléments permettant d'identifier votre compte.
                    </p>
                </section>

                <section class="legal-block">
                    <h2 class="legal-block-title">3. Délai et statut</h2>
                    <p class="legal-block-copy">
                        Après traitement, un statut peut être communiqué selon le canal de demande. Les journaux purement techniques ou obligations réglementaires peuvent imposer une conservation limitée de certaines traces.
                    </p>
                </section>

                <aside class="legal-note">
                    <h3 class="legal-note-title">Liens utiles</h3>
                    <ul class="legal-list legal-list--tight">
                        <li><a href="{{ route('privacy.policy', $brandRouteParams) }}" class="legal-link">Politique de confidentialité</a></li>
                        <li><a href="{{ route('terms.conditions', $brandRouteParams) }}" class="legal-link">Conditions générales</a></li>
                        <li><a href="{{ route('contact.us', $brandRouteParams) }}" class="legal-link">Contacter le support</a></li>
                    </ul>
                </aside>
            </article>
        </div>
    </div>
</section>
@endsection
