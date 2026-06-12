@extends('frontend.layouts.app-modern')
@php
    $foodBrandName = \App\Services\ConfigService::getCompanyName();
@endphp
@section('title', 'Politique de Remboursement | ' . $foodBrandName)
@section('description', 'Consultez notre politique de remboursement et d\'annulation de commandes.')
@section('body_class', 'bd-refund-policy-page')

@section('content')
<section class="refund-hero">
    <div class="container">
        <span class="section-badge refund-hero-badge">Remboursement</span>
        <h1 class="refund-hero-title">Politique de remboursement</h1>
        <p class="refund-hero-copy">Tout ce que vous devez savoir sur les annulations et remboursements.</p>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="refund-shell">
            <article class="refund-card">
                <aside class="refund-highlight">
                    <h3 class="refund-highlight-title">À retenir</h3>
                    <p class="refund-highlight-copy">Une commande peut être remboursée uniquement si elle n'a pas encore été acceptée par le restaurant ou le marchand. Une fois la préparation commencée, l'annulation n'est plus possible.</p>
                </aside>

                <section class="refund-section">
                    <h2 class="refund-section-title">Quand pouvez-vous être remboursé ?</h2>
                    <div class="refund-surface">
                        <ul class="refund-list">
                            <li><strong>Commande non acceptée :</strong> Remboursement intégral si la commande n'a pas été acceptée par le restaurant.</li>
                            <li><strong>Produit indisponible :</strong> Remboursement du produit manquant pour les courses alimentaires.</li>
                            <li><strong>Erreur de commande :</strong> Remboursement si l'erreur provient de notre part ou du restaurant.</li>
                            <li><strong>Problème de qualité :</strong> Remboursement après vérification par notre équipe.</li>
                        </ul>
                    </div>
                </section>

                <section class="refund-section">
                    <h2 class="refund-section-title">Quand le remboursement n'est pas possible ?</h2>
                    <div class="refund-surface refund-surface--warning">
                        <ul class="refund-list">
                            <li><strong>Commande en préparation :</strong> Une fois que le restaurant a commencé la préparation.</li>
                            <li><strong>Commande livrée :</strong> Les commandes livrées et réceptionnées sont finales.</li>
                            <li><strong>Annulation tardive :</strong> Annulation après le départ du livreur.</li>
                            <li><strong>Changement d'avis :</strong> Simple changement d'avis après acceptation.</li>
                        </ul>
                    </div>
                </section>

                <section class="refund-section">
                    <h2 class="refund-section-title">Délais de traitement</h2>
                    <p class="refund-copy">Les remboursements sont traités selon les délais suivants :</p>
                    <div class="refund-delay-grid">
                        <article class="refund-delay-card">
                            <h4 class="refund-delay-title">Mobile Money</h4>
                            <p class="refund-delay-copy">24 à 48 heures</p>
                        </article>
                        <article class="refund-delay-card">
                            <h4 class="refund-delay-title">Carte bancaire</h4>
                            <p class="refund-delay-copy">3 à 5 jours ouvrés</p>
                        </article>
                        <article class="refund-delay-card">
                            <h4 class="refund-delay-title">Crédit {{ $foodBrandName }}</h4>
                            <p class="refund-delay-copy">Immédiat</p>
                        </article>
                    </div>
                </section>

                <section class="refund-section">
                    <h2 class="refund-section-title">Comment demander un remboursement ?</h2>
                    <p class="refund-copy">Pour demander un remboursement, suivez ces étapes :</p>
                    <div class="refund-steps">
                        <article class="refund-step">
                            <div class="refund-step-index">1</div>
                            <div>
                                <h4 class="refund-step-title">Accédez à votre historique de commandes</h4>
                                <p class="refund-step-copy">Connectez-vous et allez dans "Mes commandes".</p>
                            </div>
                        </article>
                        <article class="refund-step">
                            <div class="refund-step-index">2</div>
                            <div>
                                <h4 class="refund-step-title">Sélectionnez la commande concernée</h4>
                                <p class="refund-step-copy">Cliquez sur "Signaler un problème".</p>
                            </div>
                        </article>
                        <article class="refund-step">
                            <div class="refund-step-index">3</div>
                            <div>
                                <h4 class="refund-step-title">Décrivez le problème</h4>
                                <p class="refund-step-copy">Fournissez les détails et photos si nécessaire.</p>
                            </div>
                        </article>
                        <article class="refund-step">
                            <div class="refund-step-index">4</div>
                            <div>
                                <h4 class="refund-step-title">Attendez notre réponse</h4>
                                <p class="refund-step-copy">Notre équipe vous répondra sous 24h.</p>
                            </div>
                        </article>
                    </div>
                </section>

                <aside class="refund-help">
                    <h3 class="refund-help-title">Besoin d'aide ?</h3>
                    <p class="refund-help-copy">Notre équipe de support est disponible pour vous aider avec votre demande de remboursement.</p>
                    <div class="refund-help-actions">
                        <a href="{{ route('contact.us') }}" class="refund-btn-primary">Nous contacter</a>
                        <a href="https://wa.me/242064000000" class="refund-btn-secondary refund-btn-secondary--whatsapp">WhatsApp</a>
                    </div>
                </aside>
            </article>
        </div>
    </div>
</section>
@endsection
