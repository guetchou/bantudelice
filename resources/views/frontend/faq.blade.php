@extends('frontend.layouts.app-modern')
@section('title', 'Questions Fréquentes | BantuDelice')
@section('description', 'Trouvez les réponses à vos questions sur BantuDelice : commandes, livraison, paiement et plus encore.')

@section('style')
<style>
    .faq-hero {
        background: linear-gradient(135deg, var(--secondary) 0%, var(--accent) 100%);
        padding: 140px 0 80px;
        text-align: center;
        color: white;
    }
    .faq-search {
        max-width: 500px;
        margin: 2rem auto 0;
        position: relative;
    }
    .faq-search input {
        width: 100%;
        padding: 1rem 1.5rem 1rem 3rem;
        border: none;
        border-radius: var(--radius-full);
        font-size: 1rem;
        box-shadow: var(--shadow-lg);
    }
    .faq-search i {
        position: absolute;
        left: 1.25rem;
        top: 50%;
        transform: translateY(-50%);
        color: var(--gray-400);
    }
    .faq-categories {
        display: flex;
        justify-content: center;
        gap: 1rem;
        flex-wrap: wrap;
        margin-top: 2rem;
    }
    .faq-category-btn {
        padding: 0.75rem 1.5rem;
        background: rgba(255,255,255,0.15);
        border: 2px solid rgba(255,255,255,0.3);
        border-radius: var(--radius-full);
        color: white;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s;
    }
    .faq-category-btn:hover, .faq-category-btn.active {
        background: white;
        color: var(--secondary);
    }
    .faq-section {
        padding: 4rem 0;
        background: var(--gray-50);
    }
    .faq-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 1.5rem;
        margin-top: 2rem;
    }
    .faq-item {
        background: white;
        border-radius: var(--radius-xl);
        overflow: hidden;
        box-shadow: var(--shadow-sm);
        transition: all 0.3s;
    }
    .faq-item:hover {
        box-shadow: var(--shadow-lg);
        transform: translateY(-2px);
    }
    .faq-question {
        padding: 1.25rem 1.5rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
        cursor: pointer;
        font-weight: 600;
        color: var(--gray-800);
        background: none;
        border: none;
        width: 100%;
        text-align: left;
        transition: all 0.2s;
    }
    .faq-question:hover {
        color: var(--primary);
    }
    .faq-question i {
        transition: transform 0.3s;
        color: var(--primary);
    }
    .faq-item.open .faq-question i {
        transform: rotate(180deg);
    }
    .faq-answer {
        padding: 0 1.5rem;
        max-height: 0;
        overflow: hidden;
        transition: all 0.3s ease;
        color: var(--gray-600);
        line-height: 1.7;
    }
    .faq-item.open .faq-answer {
        padding: 0 1.5rem 1.5rem;
        max-height: 500px;
    }
    .faq-contact {
        background: linear-gradient(135deg, var(--primary) 0%, var(--warning) 100%);
        padding: 4rem 0;
        text-align: center;
        color: white;
    }
    .faq-contact h2 {
        font-size: 2rem;
        margin-bottom: 1rem;
    }
    .faq-contact-options {
        display: flex;
        justify-content: center;
        gap: 2rem;
        margin-top: 2rem;
        flex-wrap: wrap;
    }
    .faq-contact-card {
        background: rgba(255,255,255,0.15);
        backdrop-filter: blur(10px);
        padding: 2rem;
        border-radius: var(--radius-xl);
        min-width: 200px;
        transition: all 0.3s;
    }
    .faq-contact-card:hover {
        background: rgba(255,255,255,0.25);
        transform: translateY(-5px);
    }
    .faq-contact-card i {
        font-size: 2rem;
        margin-bottom: 1rem;
    }
    .faq-contact-card h3 {
        font-size: 1.125rem;
        margin-bottom: 0.5rem;
    }
    .faq-contact-card p {
        opacity: 0.9;
        font-size: 0.9375rem;
    }
</style>
@endsection

@section('content')
<!-- Hero Section -->
<section class="faq-hero">
    <div class="container">
        <h1 style="font-size: 2.5rem; margin-bottom: 0.5rem;">Questions Fréquentes</h1>
        <p style="opacity: 0.9; font-size: 1.125rem;">Trouvez rapidement les réponses à vos questions</p>
        
        <div class="faq-search">
            <i class="fas fa-search"></i>
            <input type="text" id="faqSearch" placeholder="Rechercher une question...">
        </div>
        
        <div class="faq-categories">
            <button class="faq-category-btn active" data-category="all">Tout</button>
            <button class="faq-category-btn" data-category="orders">Commandes</button>
            <button class="faq-category-btn" data-category="delivery">Livraison</button>
            <button class="faq-category-btn" data-category="payment">Paiement</button>
            <button class="faq-category-btn" data-category="account">Compte</button>
        </div>
    </div>
</section>

<!-- FAQ Section -->
<section class="faq-section">
    <div class="container">
        <div class="faq-grid">
            <!-- Commandes -->
            <div class="faq-item" data-category="orders">
                <button class="faq-question">
                    Comment passer une commande ?
                    <i class="fas fa-chevron-down"></i>
                </button>
                <div class="faq-answer">
                    <p>C'est simple ! Choisissez un restaurant, sélectionnez vos plats, ajoutez-les au panier et procédez au paiement. Vous recevrez une confirmation par email et SMS.</p>
                </div>
            </div>
            
            <div class="faq-item" data-category="orders">
                <button class="faq-question">
                    Puis-je modifier ma commande après validation ?
                    <i class="fas fa-chevron-down"></i>
                </button>
                <div class="faq-answer">
                    <p>Vous pouvez modifier votre commande dans les 5 minutes suivant la validation. Contactez-nous rapidement via le chat ou par téléphone pour toute modification.</p>
                </div>
            </div>
            
            <div class="faq-item" data-category="orders">
                <button class="faq-question">
                    Comment annuler une commande ?
                    <i class="fas fa-chevron-down"></i>
                </button>
                <div class="faq-answer">
                    <p>Vous pouvez annuler votre commande depuis votre profil tant qu'elle n'a pas été prise en charge par le restaurant. Après cela, contactez notre service client.</p>
                </div>
            </div>
            
            <!-- Livraison -->
            <div class="faq-item" data-category="delivery">
                <button class="faq-question">
                    Quels sont les délais de livraison ?
                    <i class="fas fa-chevron-down"></i>
                </button>
                <div class="faq-answer">
                    <p>Le délai moyen est de 30 à 45 minutes selon la distance et l'affluence. Vous pouvez suivre votre commande en temps réel sur l'application.</p>
                </div>
            </div>
            
            <div class="faq-item" data-category="delivery">
                <button class="faq-question">
                    Quelles sont les zones de livraison ?
                    <i class="fas fa-chevron-down"></i>
                </button>
                <div class="faq-answer">
                    <p>Nous livrons actuellement à Brazzaville et ses environs. Entrez votre adresse pour vérifier si vous êtes dans notre zone de couverture.</p>
                </div>
            </div>
            
            <div class="faq-item" data-category="delivery">
                <button class="faq-question">
                    Comment suivre ma commande ?
                    <i class="fas fa-chevron-down"></i>
                </button>
                <div class="faq-answer">
                    <p>Connectez-vous à votre compte et allez dans "Mes Commandes". Vous verrez le statut en temps réel : en préparation, en route, livrée.</p>
                </div>
            </div>
            
            <!-- Paiement -->
            <div class="faq-item" data-category="payment">
                <button class="faq-question">
                    Quels modes de paiement acceptez-vous ?
                    <i class="fas fa-chevron-down"></i>
                </button>
                <div class="faq-answer">
                    <p>Nous acceptons le paiement à la livraison (espèces), Mobile Money (MTN, Airtel), et les cartes bancaires (Visa, Mastercard).</p>
                </div>
            </div>
            
            <div class="faq-item" data-category="payment">
                <button class="faq-question">
                    Le paiement est-il sécurisé ?
                    <i class="fas fa-chevron-down"></i>
                </button>
                <div class="faq-answer">
                    <p>Oui, tous les paiements en ligne sont sécurisés par cryptage SSL. Vos données bancaires ne sont jamais stockées sur nos serveurs.</p>
                </div>
            </div>
            
            <div class="faq-item" data-category="payment">
                <button class="faq-question">
                    Comment utiliser un code promo ?
                    <i class="fas fa-chevron-down"></i>
                </button>
                <div class="faq-answer">
                    <p>Lors du paiement, entrez votre code promo dans le champ prévu et cliquez sur "Appliquer". La réduction sera automatiquement appliquée.</p>
                </div>
            </div>
            
            <!-- Compte -->
            <div class="faq-item" data-category="account">
                <button class="faq-question">
                    Comment créer un compte ?
                    <i class="fas fa-chevron-down"></i>
                </button>
                <div class="faq-answer">
                    <p>Cliquez sur "Inscription" et remplissez le formulaire avec votre nom, email et téléphone. Vous recevrez un email de confirmation.</p>
                </div>
            </div>
            
            <div class="faq-item" data-category="account">
                <button class="faq-question">
                    J'ai oublié mon mot de passe
                    <i class="fas fa-chevron-down"></i>
                </button>
                <div class="faq-answer">
                    <p>Cliquez sur "Mot de passe oublié" sur la page de connexion. Entrez votre email et téléphone pour réinitialiser votre mot de passe.</p>
                </div>
            </div>
            
            <div class="faq-item" data-category="account">
                <button class="faq-question">
                    Comment supprimer mon compte ?
                    <i class="fas fa-chevron-down"></i>
                </button>
                <div class="faq-answer">
                    <p>Connectez-vous à votre compte, allez dans Paramètres > Sécurité et cliquez sur "Supprimer mon compte". Cette action est irréversible.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Contact Section -->
<section class="faq-contact">
    <div class="container">
        <h2>Vous n'avez pas trouvé votre réponse ?</h2>
        <p style="opacity: 0.9;">Notre équipe est disponible pour vous aider</p>
        
        <div class="faq-contact-options">
            <a href="tel:+242064000000" class="faq-contact-card">
                <i class="fas fa-phone"></i>
                <h3>Téléphone</h3>
                <p>+242 06 400 00 00</p>
            </a>
            
            <a href="mailto:contact@bantudelice.cg" class="faq-contact-card">
                <i class="fas fa-envelope"></i>
                <h3>Email</h3>
                <p>contact@bantudelice.cg</p>
            </a>
            
            <a href="https://wa.me/242064000000" class="faq-contact-card" target="_blank">
                <i class="fab fa-whatsapp"></i>
                <h3>WhatsApp</h3>
                <p>Chattez avec nous</p>
            </a>
        </div>
    </div>
</section>
@endsection

@section('script')
<script>
    // Toggle FAQ
    document.querySelectorAll('.faq-question').forEach(btn => {
        btn.addEventListener('click', function() {
            const item = this.closest('.faq-item');
            const isOpen = item.classList.contains('open');
            
            // Close all
            document.querySelectorAll('.faq-item').forEach(i => i.classList.remove('open'));
            
            // Toggle current
            if (!isOpen) {
                item.classList.add('open');
            }
        });
    });
    
    // Category filter
    document.querySelectorAll('.faq-category-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.faq-category-btn').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            
            const category = this.dataset.category;
            document.querySelectorAll('.faq-item').forEach(item => {
                if (category === 'all' || item.dataset.category === category) {
                    item.style.display = 'block';
                } else {
                    item.style.display = 'none';
                }
            });
        });
    });
    
    // Search
    document.getElementById('faqSearch').addEventListener('input', function() {
        const query = this.value.toLowerCase();
        document.querySelectorAll('.faq-item').forEach(item => {
            const text = item.textContent.toLowerCase();
            item.style.display = text.includes(query) ? 'block' : 'none';
        });
    });
</script>
@endsection
