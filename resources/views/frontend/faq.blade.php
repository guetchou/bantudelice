@extends('frontend.layouts.app-modern')
@php
    $faqBrand = app(\App\Services\AuthBrandingService::class)->resolve(request());
    $faqBrandName = $faqBrand['name'] ?? 'la plateforme';
    $faqBrandColor = $faqBrand['primary'] ?? '#009543';
    $faqBrandDark = $faqBrand['primary_dark'] ?? '#007836';
    $faqBrandSoft = $faqBrand['primary_soft'] ?? 'rgba(0, 149, 67, 0.12)';
    $faqItems = [
        ['category' => 'orders', 'question' => 'Comment passer une commande ?', 'answer' => 'C\'est simple. Choisissez un restaurant, sélectionnez vos plats, ajoutez-les au panier et procédez au paiement. Vous recevrez une confirmation par email et SMS.'],
        ['category' => 'orders', 'question' => 'Puis-je modifier ma commande après validation ?', 'answer' => 'Vous pouvez modifier votre commande dans les 5 minutes suivant la validation. Contactez-nous rapidement via le chat ou par téléphone pour toute modification.'],
        ['category' => 'orders', 'question' => 'Comment annuler une commande ?', 'answer' => 'Vous pouvez annuler votre commande depuis votre profil tant qu\'elle n\'a pas été prise en charge par le restaurant. Après cela, contactez notre service client.'],
        ['category' => 'delivery', 'question' => 'Quels sont les délais de livraison ?', 'answer' => 'Le délai moyen est de 30 à 45 minutes selon la distance et l\'affluence. Vous pouvez suivre votre commande en temps réel sur l\'application.'],
        ['category' => 'delivery', 'question' => 'Quelles sont les zones de livraison ?', 'answer' => 'Nous livrons actuellement à Brazzaville et ses environs. Entrez votre adresse pour vérifier si vous êtes dans notre zone de couverture.'],
        ['category' => 'delivery', 'question' => 'Comment suivre ma commande ?', 'answer' => 'Connectez-vous à votre compte et allez dans "Mes Commandes". Vous verrez le statut en temps réel : en préparation, en route, livrée.'],
        ['category' => 'payment', 'question' => 'Quels modes de paiement acceptez-vous ?', 'answer' => 'Nous acceptons le paiement à la livraison (espèces), Mobile Money (MTN, Airtel), et les cartes bancaires (Visa, Mastercard).'],
        ['category' => 'payment', 'question' => 'Le paiement est-il sécurisé ?', 'answer' => 'Oui, tous les paiements en ligne sont sécurisés par cryptage SSL. Vos données bancaires ne sont jamais stockées sur nos serveurs.'],
        ['category' => 'payment', 'question' => 'Comment utiliser un code promo ?', 'answer' => 'Lors du paiement, entrez votre code promo dans le champ prévu et cliquez sur "Appliquer". La réduction sera automatiquement appliquée.'],
        ['category' => 'account', 'question' => 'Comment créer un compte ?', 'answer' => 'Cliquez sur "Inscription" et remplissez le formulaire avec votre nom, email et téléphone. Vous recevrez un email de confirmation.'],
        ['category' => 'account', 'question' => 'J\'ai oublié mon mot de passe', 'answer' => 'Cliquez sur "Mot de passe oublié" sur la page de connexion. Entrez votre email et téléphone pour réinitialiser votre mot de passe.'],
        ['category' => 'account', 'question' => 'Comment supprimer mon compte ?', 'answer' => 'Connectez-vous à votre compte, allez dans Paramètres > Sécurité et cliquez sur "Supprimer mon compte". Cette action est irréversible.'],
    ];
@endphp
@section('title', 'Questions Fréquentes | ' . $faqBrandName)
@section('description', 'Trouvez les réponses à vos questions sur ' . $faqBrandName . ' : commandes, livraison, paiement et plus encore.')
@section('body_class', 'bd-faq-page')
@section('body_style', "--faq-brand-color: {$faqBrandColor}; --faq-brand-dark: {$faqBrandDark}; --faq-brand-soft: {$faqBrandSoft};")

@section('content')
<section class="faq-hero">
    <div class="container">
        <span class="section-badge faq-hero-badge">FAQ</span>
        <h1 class="faq-hero-title">Questions fréquentes</h1>
        <p class="faq-hero-copy">Retrouvez les réponses essentielles sur les commandes, la livraison et le paiement.</p>

        <div class="faq-search">
            <input type="text" id="faqSearch" class="faq-search-input" placeholder="Rechercher une question...">
        </div>

        <div class="faq-categories">
            <button type="button" class="faq-category-btn active" data-category="all">Tout</button>
            <button type="button" class="faq-category-btn" data-category="orders">Commandes</button>
            <button type="button" class="faq-category-btn" data-category="delivery">Livraison</button>
            <button type="button" class="faq-category-btn" data-category="payment">Paiement</button>
            <button type="button" class="faq-category-btn" data-category="account">Compte</button>
        </div>
    </div>
</section>

<section class="faq-section">
    <div class="container">
        <div class="faq-grid">
            @foreach($faqItems as $item)
                <article class="faq-item" data-category="{{ $item['category'] }}">
                    <button type="button" class="faq-question" aria-expanded="false">
                        <span>{{ $item['question'] }}</span>
                        <i class="fas fa-chevron-down" aria-hidden="true"></i>
                    </button>
                    <div class="faq-answer">
                        <p>{{ $item['answer'] }}</p>
                    </div>
                </article>
            @endforeach
        </div>
    </div>
</section>

<section class="faq-contact">
    <div class="container">
        <h2 class="faq-contact-title">Vous n'avez pas trouvé votre réponse ?</h2>
        <p class="faq-contact-copy">Notre équipe est disponible pour vous aider.</p>

        <div class="faq-contact-options">
            <a href="tel:+242064000000" class="faq-contact-card">
                <h3>Téléphone</h3>
                <p>+242 06 400 00 00</p>
            </a>
            <a href="mailto:contact@bantudelice.cg" class="faq-contact-card">
                <h3>Email</h3>
                <p>contact@bantudelice.cg</p>
            </a>
            <a href="https://wa.me/242064000000" class="faq-contact-card" target="_blank" rel="noopener">
                <h3>WhatsApp</h3>
                <p>Chattez avec nous</p>
            </a>
        </div>
    </div>
</section>
@endsection

@section('script')
<script>
    const faqItems = document.querySelectorAll('.faq-item');

    document.querySelectorAll('.faq-question').forEach((button) => {
        button.addEventListener('click', function () {
            const item = this.closest('.faq-item');
            const isOpen = item.classList.contains('open');

            faqItems.forEach((entry) => {
                entry.classList.remove('open');
                const trigger = entry.querySelector('.faq-question');
                if (trigger) {
                    trigger.setAttribute('aria-expanded', 'false');
                }
            });

            if (!isOpen) {
                item.classList.add('open');
                this.setAttribute('aria-expanded', 'true');
            }
        });
    });

    function applyFaqFilters() {
        const activeCategory = document.querySelector('.faq-category-btn.active')?.dataset.category || 'all';
        const query = (document.getElementById('faqSearch')?.value || '').toLowerCase();

        faqItems.forEach((item) => {
            const matchesCategory = activeCategory === 'all' || item.dataset.category === activeCategory;
            const matchesQuery = item.textContent.toLowerCase().includes(query);
            item.hidden = !(matchesCategory && matchesQuery);
        });
    }

    document.querySelectorAll('.faq-category-btn').forEach((button) => {
        button.addEventListener('click', function () {
            document.querySelectorAll('.faq-category-btn').forEach((entry) => entry.classList.remove('active'));
            this.classList.add('active');
            applyFaqFilters();
        });
    });

    document.getElementById('faqSearch')?.addEventListener('input', applyFaqFilters);
</script>
@endsection
