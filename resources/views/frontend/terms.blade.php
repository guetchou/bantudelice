@php
    $authBrand = app(\App\Services\AuthBrandingService::class)->resolve(request());
    $brandRouteParams = $authBrand['key'] !== 'bantudelice' ? ['brand' => $authBrand['key']] : [];
    $brandColor = $authBrand['primary'];
    $brandDark = $authBrand['primary_dark'];
    $brandSoft = $authBrand['primary_soft'] ?? 'rgba(0, 149, 67, 0.12)';
    $serviceSummary = [
        'bantudelice' => 'commande de repas, paiement, livraison et suivi de commande',
        'mema' => 'création d\'envois, suivi de colis, remise et réclamations',
        'kende' => 'réservation de trajets, suivi, paiement et gestion des réservations',
    ][$authBrand['key']] ?? 'utilisation du service';
@endphp
@extends('frontend.layouts.app-modern')
@section('title', 'Conditions générales | ' . $authBrand['name'])
@section('description', 'Consultez les conditions générales applicables à ' . $authBrand['name'] . '.')
@section('body_class', 'bd-support-page bd-terms-page')
@section('body_style', "--support-brand-color: {$brandColor}; --support-brand-dark: {$brandDark}; --support-brand-soft: {$brandSoft};")

@section('content')
<section class="support-hero">
    <div class="container">
        <span class="section-badge support-hero-badge">Conditions</span>
        <h1 class="support-hero-title">Conditions générales d'utilisation</h1>
        <p class="support-hero-copy">Règles principales applicables aux parcours {{ $authBrand['name'] }}.</p>
    </div>
</section>

<section class="section support-section">
    <div class="container">
        <div class="legal-shell">
            <article class="legal-card">
                <section class="legal-block">
                    <h2 class="legal-block-title">1. Objet</h2>
                    <p class="legal-block-copy">En utilisant {{ $authBrand['name'] }}, vous acceptez les règles applicables à {{ $serviceSummary }}.</p>
                </section>

                <section class="legal-block">
                    <h2 class="legal-block-title">2. Compte utilisateur</h2>
                    <p class="legal-block-copy">Vous êtes responsable des informations fournies, de la confidentialité de vos accès et de l'usage fait depuis votre compte.</p>
                </section>

                <section class="legal-block">
                    <h2 class="legal-block-title">3. Exécution du service</h2>
                    <p class="legal-block-copy">Les prix, délais, disponibilités et statuts affichés restent dépendants des partenaires, de la disponibilité opérationnelle et des validations requises par le service.</p>
                </section>

                <section class="legal-block">
                    <h2 class="legal-block-title">4. Paiement, annulation, réclamation</h2>
                    <p class="legal-block-copy">Les demandes d'annulation ou de réclamation sont traitées selon l'étape d'avancement du service. Certaines opérations déjà engagées peuvent ne plus être annulables.</p>
                </section>

                <section class="legal-block">
                    <h2 class="legal-block-title">5. Données et conformité</h2>
                    <p class="legal-block-copy">L'utilisation de {{ $authBrand['name'] }} implique l'acceptation des règles de confidentialité, de sécurité et de conservation des données nécessaires au fonctionnement du service.</p>
                </section>

                <aside class="legal-note">
                    <h3 class="legal-note-title">Besoin d'aide ?</h3>
                    <p class="legal-block-copy legal-block-copy--compact">Contact principal: <strong>{{ $authBrand['support_email'] }}</strong></p>
                    <div class="support-actions">
                        <a href="{{ route('contact.us', $brandRouteParams) }}" class="support-primary-link">Nous contacter</a>
                    </div>
                </aside>
            </article>
        </div>
    </div>
</section>
@endsection
