@extends('frontend.layouts.app-modern')
@section('title', 'Conditions Générales | BantuDelice')
@section('description', 'Consultez les conditions générales d\'utilisation de BantuDelice.')

@section('content')
<!-- Hero Section -->
<section style="background: linear-gradient(135deg, var(--secondary) 0%, var(--accent) 100%); padding: 150px 0 80px; text-align: center;">
    <div class="container">
        <span class="section-badge" style="background: rgba(255,255,255,0.1); color: white;">
            <i class="fas fa-file-contract"></i> Légal
        </span>
        <h1 style="color: white; font-size: clamp(2rem, 5vw, 3rem); margin-top: 1rem;">
            Conditions Générales d'Utilisation
        </h1>
        <p style="color: rgba(255,255,255,0.8); max-width: 600px; margin: 1rem auto 0; font-size: 1.125rem;">
            Dernière mise à jour : {{ date('d/m/Y') }}
        </p>
    </div>
</section>

<!-- Content Section -->
<section class="section">
    <div class="container">
        <div style="max-width: 800px; margin: 0 auto; background: white; padding: 2rem; border-radius: var(--radius-2xl); box-shadow: var(--shadow-lg);">
            
            <!-- Introduction -->
            <div style="margin-bottom: 2rem;">
                <h2 style="font-size: 1.5rem; color: var(--primary); margin-bottom: 1rem;">1. Introduction</h2>
                <p style="color: var(--gray-600); line-height: 1.8;">
                    Bienvenue sur BantuDelice. En accédant à notre plateforme et en utilisant nos services, vous acceptez d'être lié par les présentes conditions générales d'utilisation. Veuillez les lire attentivement avant d'utiliser notre service.
                </p>
                <p style="color: var(--gray-600); line-height: 1.8;">
                    BantuDelice est une plateforme de commande et de livraison en ligne qui met en relation les clients avec des restaurants et des marchands locaux. Notre objectif est de vous offrir une expérience de commande simple, rapide et agréable.
                </p>
            </div>
            
            <!-- Services -->
            <div style="margin-bottom: 2rem;">
                <h2 style="font-size: 1.5rem; color: var(--primary); margin-bottom: 1rem;">2. Description des Services</h2>
                <p style="color: var(--gray-600); line-height: 1.8;">
                    BantuDelice propose les services suivants :
                </p>
                <ul style="color: var(--gray-600); line-height: 1.8; margin-left: 1.5rem; list-style: disc;">
                    <li>Commande de repas auprès de restaurants partenaires</li>
                    <li>Livraison de courses et produits d'épicerie</li>
                    <li>Livraison de fleurs et cadeaux</li>
                    <li>Service de coursier pour colis</li>
                    <li>Réservation de tables dans des restaurants</li>
                    <li>Service traiteur pour événements</li>
                </ul>
            </div>
            
            <!-- Prix et Paiement -->
            <div style="margin-bottom: 2rem;">
                <h2 style="font-size: 1.5rem; color: var(--primary); margin-bottom: 1rem;">3. Prix et Paiement</h2>
                <p style="color: var(--gray-600); line-height: 1.8;">
                    Les prix affichés sur notre plateforme sont en Francs CFA (FCFA) et incluent toutes les taxes applicables. Les frais de livraison sont calculés en fonction de la distance et sont clairement indiqués avant la validation de votre commande.
                </p>
                <p style="color: var(--gray-600); line-height: 1.8;">
                    Nous acceptons les modes de paiement suivants :
                </p>
                <ul style="color: var(--gray-600); line-height: 1.8; margin-left: 1.5rem; list-style: disc;">
                    <li>Paiement à la livraison (espèces)</li>
                    <li>Mobile Money (MTN, Airtel)</li>
                    <li>Carte bancaire (Visa, Mastercard)</li>
                </ul>
            </div>
            
            <!-- Commandes -->
            <div style="margin-bottom: 2rem;">
                <h2 style="font-size: 1.5rem; color: var(--primary); margin-bottom: 1rem;">4. Commandes et Livraison</h2>
                <p style="color: var(--gray-600); line-height: 1.8;">
                    Une fois votre commande passée et confirmée, elle est transmise au restaurant ou marchand partenaire. Le délai de livraison estimé vous est communiqué lors de la commande et peut varier en fonction de la distance, des conditions de circulation et de la disponibilité des livreurs.
                </p>
                <p style="color: var(--gray-600); line-height: 1.8;">
                    Vous pouvez suivre l'état de votre commande en temps réel via notre application ou site web. En cas de retard significatif, notre service client vous contactera.
                </p>
            </div>
            
            <!-- Annulation -->
            <div style="margin-bottom: 2rem;">
                <h2 style="font-size: 1.5rem; color: var(--primary); margin-bottom: 1rem;">5. Annulation et Remboursement</h2>
                <p style="color: var(--gray-600); line-height: 1.8;">
                    Vous pouvez annuler votre commande tant qu'elle n'a pas été acceptée par le restaurant ou le marchand. Une fois la commande en préparation, l'annulation n'est plus possible.
                </p>
                <p style="color: var(--gray-600); line-height: 1.8;">
                    Pour plus de détails sur notre politique de remboursement, veuillez consulter notre <a href="{{ route('refund.policy') }}" style="color: var(--primary);">Politique de Remboursement</a>.
                </p>
            </div>
            
            <!-- Responsabilité -->
            <div style="margin-bottom: 2rem;">
                <h2 style="font-size: 1.5rem; color: var(--primary); margin-bottom: 1rem;">6. Responsabilité</h2>
                <p style="color: var(--gray-600); line-height: 1.8;">
                    BantuDelice agit en tant qu'intermédiaire entre les clients et les restaurants/marchands partenaires. Nous nous efforçons de garantir la qualité de notre service, mais nous ne pouvons être tenus responsables des produits fournis par nos partenaires.
                </p>
                <p style="color: var(--gray-600); line-height: 1.8;">
                    En cas de problème avec votre commande, veuillez contacter notre service client dans les plus brefs délais.
                </p>
            </div>
            
            <!-- Protection des données -->
            <div style="margin-bottom: 2rem;">
                <h2 style="font-size: 1.5rem; color: var(--primary); margin-bottom: 1rem;">7. Protection des Données</h2>
                <p style="color: var(--gray-600); line-height: 1.8;">
                    Nous prenons la protection de vos données personnelles très au sérieux. Les informations que vous nous fournissez sont utilisées uniquement pour traiter vos commandes et améliorer nos services. Nous ne partageons pas vos données avec des tiers sans votre consentement.
                </p>
            </div>
            
            <!-- Contact -->
            <div style="background: var(--gray-50); padding: 1.5rem; border-radius: var(--radius-xl); margin-top: 2rem;">
                <h3 style="font-size: 1.125rem; margin-bottom: 0.5rem;">Des questions ?</h3>
                <p style="color: var(--gray-600); margin-bottom: 1rem;">
                    Si vous avez des questions concernant ces conditions générales, n'hésitez pas à nous contacter.
                </p>
                <a href="{{ route('contact.us') }}" class="btn btn-primary">
                    <i class="fas fa-envelope"></i> Nous contacter
                </a>
            </div>
        </div>
    </div>
</section>
@endsection
