@php
    $authBrand = app(\App\Services\AuthBrandingService::class)->resolve(request());
    $brandRouteParams = $authBrand['key'] !== 'bantudelice' ? ['brand' => $authBrand['key']] : [];
    $brandColor = $authBrand['primary'];
    $brandDark = $authBrand['primary_dark'];
    $brandSoft = $authBrand['primary_soft'] ?? 'rgba(0, 149, 67, 0.12)';
    $faqBlocks = [
        'bantudelice' => [
            'Parcours de commande' => [
                'Comment passer une commande ?' => 'Parcourez les restaurants, ajoutez vos plats au panier, confirmez votre adresse puis choisissez votre mode de paiement.',
                'Comment suivre ma commande ?' => 'Le suivi est disponible depuis votre profil et la page de suivi de commande avec mises à jour en temps réel.',
            ],
            'Livraison et paiement' => [
                'Quels sont les délais ?' => 'Le délai dépend du restaurant, de la distance et de la circulation. Une estimation est affichée avant validation.',
                'Quels paiements sont acceptés ?' => 'Espèces, Mobile Money et selon disponibilité les autres moyens activés sur la plateforme.',
            ],
        ],
        'mema' => [
            'Expédition et suivi' => [
                'Comment créer un envoi ?' => 'Accédez à la création de colis, saisissez expéditeur, destinataire, poids et instructions, puis validez le paiement.',
                'Comment suivre un colis ?' => 'Utilisez le numéro de suivi sur la page publique Mema ou connectez-vous pour voir vos envois dans votre espace.',
            ],
            'Réclamations et paiement' => [
                'Que faire en cas d\'incident ?' => 'Ouvrez une réclamation depuis le support ou le détail du colis avec la référence de l\'envoi.',
                'Comment se passe la remise ?' => 'Le suivi garde l\'historique de prise en charge, transit, remise et preuves utiles lorsqu\'elles sont disponibles.',
            ],
        ],
        'kende' => [
            'Réservations et trajets' => [
                'Comment réserver un trajet ?' => 'Choisissez votre point de départ, la destination, le mode de transport et confirmez la réservation depuis Kende.',
                'Comment retrouver mes réservations ?' => 'Une fois connecté, votre espace Kende affiche les réservations actives, passées et leur statut.',
            ],
            'Paiement et assistance' => [
                'Comment signaler un problème de trajet ?' => 'Passez par la page de contact ou l\'espace de suivi du trajet en précisant la référence de réservation.',
                'Les estimations sont-elles garanties ?' => 'Les prix et horaires affichés restent des estimations opérationnelles pouvant varier selon trafic et disponibilité.',
            ],
        ],
    ][$authBrand['key']] ?? [];
@endphp
@extends('frontend.layouts.app-modern')
@section('title', $authBrand['help_label'] . ' | ' . $authBrand['name'])
@section('description', $authBrand['support_intro'])
@section('body_class', 'bd-support-page bd-help-page')
@section('body_style', "--support-brand-color: {$brandColor}; --support-brand-dark: {$brandDark}; --support-brand-soft: {$brandSoft};")

@section('content')
<section class="support-hero">
    <div class="container">
        <span class="section-badge support-hero-badge">Support</span>
        <h1 class="support-hero-title">{{ $authBrand['help_label'] }}</h1>
        <p class="support-hero-copy">{{ $authBrand['support_intro'] }}</p>
    </div>
</section>

<section class="section support-section">
    <div class="container">
        <div class="support-shell">
            <div class="support-stack">
                @foreach($faqBlocks as $groupTitle => $questions)
                    <section class="support-card">
                        <h2 class="support-card-title">{{ $groupTitle }}</h2>
                        <div class="support-faq-grid">
                            @foreach($questions as $question => $answer)
                                <article class="support-faq-item">
                                    <h3 class="support-faq-question">{{ $question }}</h3>
                                    <p class="support-faq-answer">{{ $answer }}</p>
                                </article>
                            @endforeach
                        </div>
                    </section>
                @endforeach

                <section class="support-card support-card--contact">
                    <h2 class="support-card-heading">Contacter {{ $authBrand['name'] }}</h2>
                    <p class="support-contact-copy">
                        Email: <strong>{{ $authBrand['support_email'] }}</strong><br>
                        Téléphone: <strong>{{ $authBrand['support_phone'] }}</strong>
                    </p>
                    <div class="support-actions">
                        <a href="{{ route('contact.us', $brandRouteParams) }}" class="support-primary-link">Nous contacter</a>
                        <a href="{{ route('privacy.policy', $brandRouteParams) }}" class="support-secondary-link">Confidentialité</a>
                        <a href="{{ route('terms.conditions', $brandRouteParams) }}" class="support-secondary-link">Conditions</a>
                    </div>
                </section>
            </div>
        </div>
    </div>
</section>
@endsection
