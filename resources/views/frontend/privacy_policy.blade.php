@extends('frontend.layouts.app-modern')
@section('title', 'Politique de confidentialité | BantuDelice')
@section('description', 'Politique de confidentialité et protection des données personnelles sur BantuDelice.')

@section('content')
<section style="background: linear-gradient(135deg, var(--secondary) 0%, var(--accent) 100%); padding: 150px 0 80px; text-align: center;">
    <div class="container">
        <span class="section-badge" style="background: rgba(255,255,255,0.1); color: white;">
            <i class="fas fa-shield-alt"></i> Confidentialité
        </span>
        <h1 style="color: white; font-size: clamp(2rem, 5vw, 3rem); margin-top: 1rem;">
            Politique de confidentialité
        </h1>
        <p style="color: rgba(255,255,255,0.8); max-width: 720px; margin: 1rem auto 0; font-size: 1.125rem;">
            Dernière mise à jour : {{ date('d/m/Y') }}
        </p>
    </div>
</section>

<section class="section">
    <div class="container">
        <div style="max-width: 900px; margin: 0 auto; background: #fff; padding: 2rem; border-radius: var(--radius-2xl); box-shadow: var(--shadow-lg);">
            <div style="margin-bottom: 1.75rem;">
                <h2 style="font-size: 1.5rem; color: var(--primary); margin-bottom: 0.75rem;">1. Qui sommes-nous ?</h2>
                <p style="color: var(--gray-600); line-height: 1.8; margin: 0;">
                    BantuDelice est une plateforme de commande et de livraison (restaurants, courses et services) opérant à Brazzaville (République du Congo).
                </p>
            </div>

            <div style="margin-bottom: 1.75rem;">
                <h2 style="font-size: 1.5rem; color: var(--primary); margin-bottom: 0.75rem;">2. Données que nous collectons</h2>
                <ul style="color: var(--gray-600); line-height: 1.9; margin-left: 1.25rem; list-style: disc;">
                    <li>Informations de compte : nom, email, téléphone, photo de profil (optionnelle).</li>
                    <li>Informations de commande : produits, montants, adresse de livraison, historique.</li>
                    <li>Données techniques : logs, cookies essentiels, informations de session.</li>
                    <li>Authentification sociale : identifiant du fournisseur (Google/Facebook), avatar (si fourni).</li>
                </ul>
            </div>

            <div style="margin-bottom: 1.75rem;">
                <h2 style="font-size: 1.5rem; color: var(--primary); margin-bottom: 0.75rem;">3. Finalités</h2>
                <ul style="color: var(--gray-600); line-height: 1.9; margin-left: 1.25rem; list-style: disc;">
                    <li>Créer et gérer votre compte utilisateur.</li>
                    <li>Traiter vos commandes et assurer la livraison.</li>
                    <li>Assurer le support client et la prévention de la fraude.</li>
                    <li>Améliorer la qualité de service (statistiques agrégées).</li>
                </ul>
            </div>

            <div style="margin-bottom: 1.75rem;">
                <h2 style="font-size: 1.5rem; color: var(--primary); margin-bottom: 0.75rem;">4. Partage des données</h2>
                <p style="color: var(--gray-600); line-height: 1.8; margin: 0;">
                    Nous partageons uniquement les informations nécessaires à l’exécution du service avec nos partenaires (restaurants, livreurs) et prestataires techniques (paiement, hébergement).
                    Nous ne vendons pas vos données.
                </p>
            </div>

            <div style="margin-bottom: 1.75rem;">
                <h2 style="font-size: 1.5rem; color: var(--primary); margin-bottom: 0.75rem;">5. Cookies</h2>
                <p style="color: var(--gray-600); line-height: 1.8; margin: 0;">
                    Nous utilisons des cookies essentiels au fonctionnement (session, sécurité). Des cookies analytiques peuvent être ajoutés si activés.
                </p>
            </div>

            <div style="margin-bottom: 1.75rem;">
                <h2 style="font-size: 1.5rem; color: var(--primary); margin-bottom: 0.75rem;">6. Vos droits</h2>
                <ul style="color: var(--gray-600); line-height: 1.9; margin-left: 1.25rem; list-style: disc;">
                    <li>Accès et rectification.</li>
                    <li>Suppression/anonymisation de votre compte (voir page suppression des données).</li>
                </ul>
                <p style="color: var(--gray-600); line-height: 1.8; margin-top: 0.75rem;">
                    Pour demander la suppression de vos données : <a href="{{ route('data.deletion') }}" style="color: var(--primary); font-weight: 600;">voir les instructions</a>.
                </p>
            </div>

            <div style="background: var(--gray-50); padding: 1.5rem; border-radius: var(--radius-xl); margin-top: 2rem;">
                <h3 style="font-size: 1.125rem; margin-bottom: 0.5rem;">Contact</h3>
                <p style="color: var(--gray-600); margin-bottom: 0;">
                    Pour toute question liée à la confidentialité : <strong>{{ \App\Services\ConfigService::getContactEmail() }}</strong>
                </p>
            </div>
        </div>
    </div>
</section>
@endsection


