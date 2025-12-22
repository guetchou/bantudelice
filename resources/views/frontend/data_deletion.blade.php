@extends('frontend.layouts.app-modern')
@section('title', 'Suppression des données utilisateur | BantuDelice')
@section('description', 'Instructions pour demander la suppression/anonymisation de vos données sur BantuDelice.')

@section('content')
<section style="background: linear-gradient(135deg, #111827 0%, #0F766E 100%); padding: 150px 0 80px; text-align: center;">
    <div class="container">
        <span class="section-badge" style="background: rgba(255,255,255,0.1); color: white;">
            <i class="fas fa-user-shield"></i> Données personnelles
        </span>
        <h1 style="color: white; font-size: clamp(2rem, 5vw, 3rem); margin-top: 1rem;">
            Suppression des données utilisateur
        </h1>
        <p style="color: rgba(255,255,255,0.85); max-width: 760px; margin: 1rem auto 0; font-size: 1.125rem;">
            Cette page décrit comment demander la suppression/anonymisation de vos données personnelles.
        </p>
    </div>
</section>

<section class="section">
    <div class="container">
        <div style="max-width: 900px; margin: 0 auto; background: #fff; padding: 2rem; border-radius: var(--radius-2xl); box-shadow: var(--shadow-lg);">
            <div style="margin-bottom: 1.5rem;">
                <h2 style="font-size: 1.5rem; color: var(--primary); margin-bottom: 0.75rem;">1. Suppression via votre compte</h2>
                <p style="color: var(--gray-600); line-height: 1.8; margin: 0;">
                    Si vous êtes connecté, vous pouvez demander la suppression via l’interface (action “Supprimer mon compte”).
                    La suppression est effectuée par <strong>anonymisation</strong> afin de préserver l’historique des commandes (intégrité technique).
                </p>
            </div>

            <div style="margin-bottom: 1.5rem;">
                <h2 style="font-size: 1.5rem; color: var(--primary); margin-bottom: 0.75rem;">2. Suppression via Facebook Login</h2>
                <p style="color: var(--gray-600); line-height: 1.8; margin: 0;">
                    Si vous utilisez Facebook Login, Facebook peut nous transmettre automatiquement une demande de suppression (User Data Deletion).
                    Notre endpoint traite la demande et renvoie une URL de statut.
                </p>
            </div>

            <div style="margin-bottom: 1.5rem;">
                <h2 style="font-size: 1.5rem; color: var(--primary); margin-bottom: 0.75rem;">3. Demande par email (alternative)</h2>
                <p style="color: var(--gray-600); line-height: 1.8; margin: 0;">
                    Si vous ne pouvez pas accéder à votre compte, vous pouvez envoyer une demande à :
                    <strong>{{ \App\Services\ConfigService::getContactEmail() }}</strong>
                    en précisant votre email/téléphone et “Suppression de compte”.
                </p>
            </div>

            <div style="background: var(--gray-50); padding: 1.5rem; border-radius: var(--radius-xl); margin-top: 2rem;">
                <h3 style="font-size: 1.125rem; margin-bottom: 0.5rem;">Liens utiles</h3>
                <ul style="color: var(--gray-600); line-height: 1.9; margin: 0; padding-left: 1.25rem; list-style: disc;">
                    <li><a href="{{ route('privacy.policy') }}" style="color: var(--primary); font-weight: 600;">Politique de confidentialité</a></li>
                    <li><a href="{{ route('terms.conditions') }}" style="color: var(--primary); font-weight: 600;">Conditions générales</a></li>
                </ul>
            </div>
        </div>
    </div>
</section>
@endsection


