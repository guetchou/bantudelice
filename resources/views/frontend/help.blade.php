@extends('frontend.layouts.app-modern')
@section('title', 'Centre d\'Aide | BantuDelice')
@section('description', 'Trouvez toutes les réponses à vos questions sur BantuDelice. Notre centre d\'aide est là pour vous accompagner.')

@section('content')
<!-- Hero Section -->
<section style="background: linear-gradient(135deg, var(--secondary) 0%, var(--accent) 100%); padding: 150px 0 80px; text-align: center;">
    <div class="container">
        <span class="section-badge" style="background: rgba(255,255,255,0.1); color: white;">
            <i class="fas fa-life-ring"></i> Aide
        </span>
        <h1 style="color: white; font-size: clamp(2rem, 5vw, 3rem); margin-top: 1rem;">
            Centre d'Aide
        </h1>
        <p style="color: rgba(255,255,255,0.8); max-width: 600px; margin: 1rem auto 0; font-size: 1.125rem;">
            Comment pouvons-nous vous aider ?
        </p>
        
        <!-- Search Box -->
        <div style="max-width: 500px; margin: 2rem auto 0;">
            <div style="position: relative;">
                <input type="text" id="helpSearch" placeholder="Rechercher dans l'aide..." 
                       style="width: 100%; padding: 1rem 1rem 1rem 3rem; border: none; border-radius: var(--radius-xl); font-size: 1rem; box-shadow: var(--shadow-xl);">
                <i class="fas fa-search" style="position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); color: var(--gray-400);"></i>
            </div>
        </div>
    </div>
</section>

<!-- Quick Links -->
<section class="section" style="margin-top: -40px;">
    <div class="container">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem; max-width: 1000px; margin: 0 auto;">
            <a href="#commandes" class="help-card" style="background: white; padding: 1.5rem; border-radius: var(--radius-xl); box-shadow: var(--shadow-lg); text-align: center; text-decoration: none; color: inherit; transition: transform 0.2s;">
                <div style="width: 60px; height: 60px; background: linear-gradient(135deg, rgba(255, 107, 53, 0.1) 0%, rgba(245, 158, 11, 0.1) 100%); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1rem;">
                    <i class="fas fa-shopping-bag" style="font-size: 1.5rem; color: var(--primary);"></i>
                </div>
                <h3 style="font-size: 1rem; margin-bottom: 0.25rem;">Commandes</h3>
                <p style="color: var(--gray-500); font-size: 0.875rem; margin: 0;">Suivi, modifications, annulations</p>
            </a>
            
            <a href="#livraison" class="help-card" style="background: white; padding: 1.5rem; border-radius: var(--radius-xl); box-shadow: var(--shadow-lg); text-align: center; text-decoration: none; color: inherit; transition: transform 0.2s;">
                <div style="width: 60px; height: 60px; background: linear-gradient(135deg, rgba(255, 107, 53, 0.1) 0%, rgba(245, 158, 11, 0.1) 100%); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1rem;">
                    <i class="fas fa-motorcycle" style="font-size: 1.5rem; color: var(--primary);"></i>
                </div>
                <h3 style="font-size: 1rem; margin-bottom: 0.25rem;">Livraison</h3>
                <p style="color: var(--gray-500); font-size: 0.875rem; margin: 0;">Délais, zones, suivi GPS</p>
            </a>
            
            <a href="#paiement" class="help-card" style="background: white; padding: 1.5rem; border-radius: var(--radius-xl); box-shadow: var(--shadow-lg); text-align: center; text-decoration: none; color: inherit; transition: transform 0.2s;">
                <div style="width: 60px; height: 60px; background: linear-gradient(135deg, rgba(255, 107, 53, 0.1) 0%, rgba(245, 158, 11, 0.1) 100%); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1rem;">
                    <i class="fas fa-credit-card" style="font-size: 1.5rem; color: var(--primary);"></i>
                </div>
                <h3 style="font-size: 1rem; margin-bottom: 0.25rem;">Paiement</h3>
                <p style="color: var(--gray-500); font-size: 0.875rem; margin: 0;">Modes de paiement, factures</p>
            </a>
            
            <a href="#compte" class="help-card" style="background: white; padding: 1.5rem; border-radius: var(--radius-xl); box-shadow: var(--shadow-lg); text-align: center; text-decoration: none; color: inherit; transition: transform 0.2s;">
                <div style="width: 60px; height: 60px; background: linear-gradient(135deg, rgba(255, 107, 53, 0.1) 0%, rgba(245, 158, 11, 0.1) 100%); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1rem;">
                    <i class="fas fa-user-cog" style="font-size: 1.5rem; color: var(--primary);"></i>
                </div>
                <h3 style="font-size: 1rem; margin-bottom: 0.25rem;">Mon Compte</h3>
                <p style="color: var(--gray-500); font-size: 0.875rem; margin: 0;">Profil, sécurité, préférences</p>
            </a>
        </div>
    </div>
</section>

<!-- Help Topics -->
<section class="section" style="background: var(--gray-50);">
    <div class="container">
        <div style="max-width: 800px; margin: 0 auto;">
            
            <!-- Commandes -->
            <div id="commandes" style="margin-bottom: 3rem;">
                <h2 style="font-size: 1.5rem; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.75rem;">
                    <i class="fas fa-shopping-bag" style="color: var(--primary);"></i>
                    Commandes
                </h2>
                
                <div class="help-accordion" style="background: white; border-radius: var(--radius-xl); overflow: hidden; box-shadow: var(--shadow-sm);">
                    <div class="accordion-item" style="border-bottom: 1px solid var(--gray-100);">
                        <button class="accordion-btn" onclick="toggleAccordion(this)" style="width: 100%; padding: 1.25rem 1.5rem; display: flex; justify-content: space-between; align-items: center; background: none; border: none; cursor: pointer; text-align: left;">
                            <span style="font-weight: 600;">Comment passer une commande ?</span>
                            <i class="fas fa-chevron-down" style="transition: transform 0.3s;"></i>
                        </button>
                        <div class="accordion-content" style="max-height: 0; overflow: hidden; transition: max-height 0.3s ease;">
                            <div style="padding: 0 1.5rem 1.5rem; color: var(--gray-600); line-height: 1.8;">
                                <ol style="margin: 0; padding-left: 1.5rem;">
                                    <li>Parcourez les restaurants ou utilisez la barre de recherche</li>
                                    <li>Sélectionnez les plats que vous souhaitez commander</li>
                                    <li>Ajoutez-les à votre panier</li>
                                    <li>Validez votre panier et choisissez votre adresse de livraison</li>
                                    <li>Sélectionnez votre mode de paiement et confirmez</li>
                                </ol>
                            </div>
                        </div>
                    </div>
                    
                    <div class="accordion-item" style="border-bottom: 1px solid var(--gray-100);">
                        <button class="accordion-btn" onclick="toggleAccordion(this)" style="width: 100%; padding: 1.25rem 1.5rem; display: flex; justify-content: space-between; align-items: center; background: none; border: none; cursor: pointer; text-align: left;">
                            <span style="font-weight: 600;">Comment suivre ma commande ?</span>
                            <i class="fas fa-chevron-down" style="transition: transform 0.3s;"></i>
                        </button>
                        <div class="accordion-content" style="max-height: 0; overflow: hidden; transition: max-height 0.3s ease;">
                            <div style="padding: 0 1.5rem 1.5rem; color: var(--gray-600); line-height: 1.8;">
                                <p>Une fois votre commande passée, vous pouvez la suivre en temps réel :</p>
                                <ul style="margin-top: 0.5rem; padding-left: 1.5rem;">
                                    <li>Depuis votre profil > Commandes actives</li>
                                    <li>Via les notifications de l'application</li>
                                    <li>Par SMS pour les étapes clés</li>
                                </ul>
                                <p style="margin-top: 0.5rem;">Vous verrez l'état de préparation, le départ du livreur et sa position en temps réel.</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="accordion-item">
                        <button class="accordion-btn" onclick="toggleAccordion(this)" style="width: 100%; padding: 1.25rem 1.5rem; display: flex; justify-content: space-between; align-items: center; background: none; border: none; cursor: pointer; text-align: left;">
                            <span style="font-weight: 600;">Comment annuler une commande ?</span>
                            <i class="fas fa-chevron-down" style="transition: transform 0.3s;"></i>
                        </button>
                        <div class="accordion-content" style="max-height: 0; overflow: hidden; transition: max-height 0.3s ease;">
                            <div style="padding: 0 1.5rem 1.5rem; color: var(--gray-600); line-height: 1.8;">
                                <p>Vous pouvez annuler votre commande <strong>uniquement si elle n'a pas encore été acceptée</strong> par le restaurant.</p>
                                <p style="margin-top: 0.5rem;">Pour annuler : Profil > Commandes > Sélectionnez la commande > Annuler</p>
                                <p style="margin-top: 0.5rem; color: var(--warning);"><strong>Important:</strong> Une fois la commande en preparation, l'annulation n'est plus possible.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Livraison -->
            <div id="livraison" style="margin-bottom: 3rem;">
                <h2 style="font-size: 1.5rem; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.75rem;">
                    <i class="fas fa-motorcycle" style="color: var(--primary);"></i>
                    Livraison
                </h2>
                
                <div class="help-accordion" style="background: white; border-radius: var(--radius-xl); overflow: hidden; box-shadow: var(--shadow-sm);">
                    <div class="accordion-item" style="border-bottom: 1px solid var(--gray-100);">
                        <button class="accordion-btn" onclick="toggleAccordion(this)" style="width: 100%; padding: 1.25rem 1.5rem; display: flex; justify-content: space-between; align-items: center; background: none; border: none; cursor: pointer; text-align: left;">
                            <span style="font-weight: 600;">Quelles sont les zones de livraison ?</span>
                            <i class="fas fa-chevron-down" style="transition: transform 0.3s;"></i>
                        </button>
                        <div class="accordion-content" style="max-height: 0; overflow: hidden; transition: max-height 0.3s ease;">
                            <div style="padding: 0 1.5rem 1.5rem; color: var(--gray-600); line-height: 1.8;">
                                <p>Nous livrons actuellement dans les zones suivantes :</p>
                                <ul style="margin-top: 0.5rem; padding-left: 1.5rem;">
                                    <li><strong>Brazzaville :</strong> Centre-ville, Bacongo, Poto-Poto, Moungali, Ouenzé, Talangaï, Mfilou</li>
                                    <li><strong>Pointe-Noire :</strong> Centre-ville et environs</li>
                                </ul>
                                <p style="margin-top: 0.5rem;">Le rayon de livraison est de 8 km autour de chaque restaurant.</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="accordion-item" style="border-bottom: 1px solid var(--gray-100);">
                        <button class="accordion-btn" onclick="toggleAccordion(this)" style="width: 100%; padding: 1.25rem 1.5rem; display: flex; justify-content: space-between; align-items: center; background: none; border: none; cursor: pointer; text-align: left;">
                            <span style="font-weight: 600;">Quels sont les frais de livraison ?</span>
                            <i class="fas fa-chevron-down" style="transition: transform 0.3s;"></i>
                        </button>
                        <div class="accordion-content" style="max-height: 0; overflow: hidden; transition: max-height 0.3s ease;">
                            <div style="padding: 0 1.5rem 1.5rem; color: var(--gray-600); line-height: 1.8;">
                                <p>Les frais de livraison varient selon la distance :</p>
                                <ul style="margin-top: 0.5rem; padding-left: 1.5rem;">
                                    <li><strong>0-3 km :</strong> 1 000 FCFA</li>
                                    <li><strong>3-5 km :</strong> 1 500 FCFA</li>
                                    <li><strong>5-8 km :</strong> 2 000 FCFA</li>
                                </ul>
                                <p style="margin-top: 0.5rem; color: var(--success);"><strong>Offre speciale:</strong> Livraison gratuite pour les commandes superieures a 15 000 FCFA</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="accordion-item">
                        <button class="accordion-btn" onclick="toggleAccordion(this)" style="width: 100%; padding: 1.25rem 1.5rem; display: flex; justify-content: space-between; align-items: center; background: none; border: none; cursor: pointer; text-align: left;">
                            <span style="font-weight: 600;">Quel est le délai de livraison ?</span>
                            <i class="fas fa-chevron-down" style="transition: transform 0.3s;"></i>
                        </button>
                        <div class="accordion-content" style="max-height: 0; overflow: hidden; transition: max-height 0.3s ease;">
                            <div style="padding: 0 1.5rem 1.5rem; color: var(--gray-600); line-height: 1.8;">
                                <p>Le délai de livraison estimé est généralement de <strong>30 à 45 minutes</strong> selon :</p>
                                <ul style="margin-top: 0.5rem; padding-left: 1.5rem;">
                                    <li>Le temps de préparation du restaurant</li>
                                    <li>La distance entre le restaurant et votre adresse</li>
                                    <li>Les conditions de circulation</li>
                                </ul>
                                <p style="margin-top: 0.5rem;">Le délai exact est affiché lors de la commande et peut être suivi en temps réel.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Paiement -->
            <div id="paiement" style="margin-bottom: 3rem;">
                <h2 style="font-size: 1.5rem; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.75rem;">
                    <i class="fas fa-credit-card" style="color: var(--primary);"></i>
                    Paiement
                </h2>
                
                <div class="help-accordion" style="background: white; border-radius: var(--radius-xl); overflow: hidden; box-shadow: var(--shadow-sm);">
                    <div class="accordion-item" style="border-bottom: 1px solid var(--gray-100);">
                        <button class="accordion-btn" onclick="toggleAccordion(this)" style="width: 100%; padding: 1.25rem 1.5rem; display: flex; justify-content: space-between; align-items: center; background: none; border: none; cursor: pointer; text-align: left;">
                            <span style="font-weight: 600;">Quels modes de paiement acceptez-vous ?</span>
                            <i class="fas fa-chevron-down" style="transition: transform 0.3s;"></i>
                        </button>
                        <div class="accordion-content" style="max-height: 0; overflow: hidden; transition: max-height 0.3s ease;">
                            <div style="padding: 0 1.5rem 1.5rem; color: var(--gray-600); line-height: 1.8;">
                                <p>Nous acceptons plusieurs modes de paiement :</p>
                                <ul style="margin-top: 0.5rem; padding-left: 1.5rem;">
                                    <li><strong>Mobile Money :</strong> MTN Mobile Money, Airtel Money</li>
                                    <li><strong>Carte bancaire :</strong> Visa, Mastercard</li>
                                    <li><strong>Espèces :</strong> Paiement à la livraison</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    
                    <div class="accordion-item">
                        <button class="accordion-btn" onclick="toggleAccordion(this)" style="width: 100%; padding: 1.25rem 1.5rem; display: flex; justify-content: space-between; align-items: center; background: none; border: none; cursor: pointer; text-align: left;">
                            <span style="font-weight: 600;">Comment obtenir un remboursement ?</span>
                            <i class="fas fa-chevron-down" style="transition: transform 0.3s;"></i>
                        </button>
                        <div class="accordion-content" style="max-height: 0; overflow: hidden; transition: max-height 0.3s ease;">
                            <div style="padding: 0 1.5rem 1.5rem; color: var(--gray-600); line-height: 1.8;">
                                <p>Pour demander un remboursement :</p>
                                <ol style="margin-top: 0.5rem; padding-left: 1.5rem;">
                                    <li>Accédez à votre historique de commandes</li>
                                    <li>Sélectionnez la commande concernée</li>
                                    <li>Cliquez sur "Signaler un problème"</li>
                                    <li>Décrivez le problème avec des preuves si nécessaire</li>
                                </ol>
                                <p style="margin-top: 0.5rem;">Consultez notre <a href="{{ route('refund.policy') }}" style="color: var(--primary);">politique de remboursement</a> pour plus de détails.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Compte -->
            <div id="compte" style="margin-bottom: 3rem;">
                <h2 style="font-size: 1.5rem; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.75rem;">
                    <i class="fas fa-user-cog" style="color: var(--primary);"></i>
                    Mon Compte
                </h2>
                
                <div class="help-accordion" style="background: white; border-radius: var(--radius-xl); overflow: hidden; box-shadow: var(--shadow-sm);">
                    <div class="accordion-item" style="border-bottom: 1px solid var(--gray-100);">
                        <button class="accordion-btn" onclick="toggleAccordion(this)" style="width: 100%; padding: 1.25rem 1.5rem; display: flex; justify-content: space-between; align-items: center; background: none; border: none; cursor: pointer; text-align: left;">
                            <span style="font-weight: 600;">Comment créer un compte ?</span>
                            <i class="fas fa-chevron-down" style="transition: transform 0.3s;"></i>
                        </button>
                        <div class="accordion-content" style="max-height: 0; overflow: hidden; transition: max-height 0.3s ease;">
                            <div style="padding: 0 1.5rem 1.5rem; color: var(--gray-600); line-height: 1.8;">
                                <p>Créer un compte est simple et gratuit :</p>
                                <ul style="margin-top: 0.5rem; padding-left: 1.5rem;">
                                    <li>Cliquez sur "Inscription" en haut de la page</li>
                                    <li>Remplissez vos informations (nom, email, téléphone)</li>
                                    <li>Créez un mot de passe sécurisé</li>
                                    <li>Ou inscrivez-vous via Google/Facebook</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    
                    <div class="accordion-item">
                        <button class="accordion-btn" onclick="toggleAccordion(this)" style="width: 100%; padding: 1.25rem 1.5rem; display: flex; justify-content: space-between; align-items: center; background: none; border: none; cursor: pointer; text-align: left;">
                            <span style="font-weight: 600;">J'ai oublié mon mot de passe</span>
                            <i class="fas fa-chevron-down" style="transition: transform 0.3s;"></i>
                        </button>
                        <div class="accordion-content" style="max-height: 0; overflow: hidden; transition: max-height 0.3s ease;">
                            <div style="padding: 0 1.5rem 1.5rem; color: var(--gray-600); line-height: 1.8;">
                                <p>Pour réinitialiser votre mot de passe :</p>
                                <ol style="margin-top: 0.5rem; padding-left: 1.5rem;">
                                    <li>Cliquez sur "Connexion"</li>
                                    <li>Cliquez sur "Mot de passe oublié ?"</li>
                                    <li>Entrez votre adresse email</li>
                                    <li>Suivez le lien envoyé par email</li>
                                    <li>Créez un nouveau mot de passe</li>
                                </ol>
                                <p style="margin-top: 0.5rem;"><a href="{{ route('forgot.password') }}" class="btn btn-secondary" style="padding: 0.5rem 1rem; font-size: 0.875rem;">Réinitialiser mon mot de passe</a></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
        </div>
    </div>
</section>

<!-- Contact Section -->
<section class="section">
    <div class="container">
        <div style="max-width: 600px; margin: 0 auto; text-align: center;">
            <h2 style="font-size: 1.75rem; margin-bottom: 1rem;">Vous n'avez pas trouvé votre réponse ?</h2>
            <p style="color: var(--gray-600); margin-bottom: 2rem;">Notre équipe de support est disponible pour vous aider.</p>
            
            <div style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
                <a href="{{ route('contact.us') }}" class="btn btn-primary btn-lg">
                    <i class="fas fa-envelope"></i> Nous contacter
                </a>
                <a href="https://wa.me/242064000000" class="btn btn-lg" style="background: #25D366; color: white;">
                    <i class="fab fa-whatsapp"></i> WhatsApp
                </a>
                <a href="{{ route('faq') }}" class="btn btn-secondary btn-lg">
                    <i class="fas fa-question-circle"></i> FAQ
                </a>
            </div>
        </div>
    </div>
</section>
@endsection

@section('styles')
<style>
    .help-card:hover {
        transform: translateY(-5px);
    }
    
    .accordion-btn:hover {
        background: var(--gray-50) !important;
    }
    
    .accordion-btn.active .fa-chevron-down {
        transform: rotate(180deg);
    }
    
    .accordion-content.active {
        max-height: 500px !important;
    }
</style>
@endsection

@section('scripts')
<script>
    function toggleAccordion(btn) {
        const content = btn.nextElementSibling;
        const isActive = btn.classList.contains('active');
        
        // Close all
        document.querySelectorAll('.accordion-btn').forEach(b => b.classList.remove('active'));
        document.querySelectorAll('.accordion-content').forEach(c => c.classList.remove('active'));
        
        // Toggle current
        if (!isActive) {
            btn.classList.add('active');
            content.classList.add('active');
        }
    }
    
    // Search functionality
    document.getElementById('helpSearch').addEventListener('input', function(e) {
        const query = e.target.value.toLowerCase();
        const accordionItems = document.querySelectorAll('.accordion-item');
        
        accordionItems.forEach(item => {
            const text = item.textContent.toLowerCase();
            if (query && text.includes(query)) {
                item.style.display = 'block';
                item.style.backgroundColor = 'rgba(255, 107, 53, 0.05)';
            } else if (query) {
                item.style.display = 'none';
            } else {
                item.style.display = 'block';
                item.style.backgroundColor = '';
            }
        });
    });
</script>
@endsection
