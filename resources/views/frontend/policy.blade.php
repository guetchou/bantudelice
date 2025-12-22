@extends('frontend.layouts.app-modern')
@section('title', 'Politique de Remboursement | BantuDelice')
@section('description', 'Consultez notre politique de remboursement et d\'annulation de commandes.')

@section('content')
<!-- Hero Section -->
<section style="background: linear-gradient(135deg, var(--secondary) 0%, var(--accent) 100%); padding: 150px 0 80px; text-align: center;">
    <div class="container">
        <span class="section-badge" style="background: rgba(255,255,255,0.1); color: white;">
            <i class="fas fa-undo-alt"></i> Remboursement
        </span>
        <h1 style="color: white; font-size: clamp(2rem, 5vw, 3rem); margin-top: 1rem;">
            Politique de Remboursement
        </h1>
        <p style="color: rgba(255,255,255,0.8); max-width: 600px; margin: 1rem auto 0; font-size: 1.125rem;">
            Tout ce que vous devez savoir sur les annulations et remboursements.
        </p>
    </div>
</section>

<!-- Content Section -->
<section class="section">
    <div class="container">
        <div style="max-width: 800px; margin: 0 auto; background: white; padding: 2rem; border-radius: var(--radius-2xl); box-shadow: var(--shadow-lg);">
            
            <!-- Important Notice -->
            <div style="background: linear-gradient(135deg, rgba(255, 107, 53, 0.1) 0%, rgba(245, 158, 11, 0.1) 100%); padding: 1.5rem; border-radius: var(--radius-xl); margin-bottom: 2rem; border-left: 4px solid var(--primary);">
                <h3 style="font-size: 1rem; color: var(--primary); margin-bottom: 0.5rem;">
                    <i class="fas fa-info-circle"></i> À retenir
                </h3>
                <p style="color: var(--gray-700); margin: 0; line-height: 1.6;">
                    Une commande peut être remboursée uniquement si elle n'a pas encore été acceptée par le restaurant ou le marchand. Une fois la préparation commencée, l'annulation n'est plus possible.
                </p>
            </div>
            
            <!-- When can you get a refund -->
            <div style="margin-bottom: 2rem;">
                <h2 style="font-size: 1.5rem; color: var(--primary); margin-bottom: 1rem;">
                    <i class="fas fa-check-circle" style="color: var(--success);"></i> Quand pouvez-vous être remboursé ?
                </h2>
                <div style="background: var(--gray-50); padding: 1.5rem; border-radius: var(--radius-lg);">
                    <ul style="color: var(--gray-600); line-height: 2; margin: 0; padding-left: 1.5rem;">
                        <li><strong>Commande non acceptée :</strong> Remboursement intégral si la commande n'a pas été acceptée par le restaurant</li>
                        <li><strong>Produit indisponible :</strong> Remboursement du produit manquant pour les courses alimentaires</li>
                        <li><strong>Erreur de commande :</strong> Remboursement si l'erreur provient de notre part ou du restaurant</li>
                        <li><strong>Problème de qualité :</strong> Remboursement après vérification par notre équipe</li>
                    </ul>
                </div>
            </div>
            
            <!-- When you cannot get a refund -->
            <div style="margin-bottom: 2rem;">
                <h2 style="font-size: 1.5rem; color: var(--primary); margin-bottom: 1rem;">
                    <i class="fas fa-times-circle" style="color: var(--error);"></i> Quand le remboursement n'est pas possible ?
                </h2>
                <div style="background: rgba(239, 68, 68, 0.05); padding: 1.5rem; border-radius: var(--radius-lg); border: 1px solid rgba(239, 68, 68, 0.2);">
                    <ul style="color: var(--gray-600); line-height: 2; margin: 0; padding-left: 1.5rem;">
                        <li><strong>Commande en préparation :</strong> Une fois que le restaurant a commencé la préparation</li>
                        <li><strong>Commande livrée :</strong> Les commandes livrées et réceptionnées sont finales</li>
                        <li><strong>Annulation tardive :</strong> Annulation après le départ du livreur</li>
                        <li><strong>Changement d'avis :</strong> Simple changement d'avis après acceptation</li>
                    </ul>
                </div>
            </div>
            
            <!-- Process -->
            <div style="margin-bottom: 2rem;">
                <h2 style="font-size: 1.5rem; color: var(--primary); margin-bottom: 1rem;">
                    <i class="fas fa-clock"></i> Délais de traitement
                </h2>
                <p style="color: var(--gray-600); line-height: 1.8; margin-bottom: 1rem;">
                    Les remboursements sont traités selon les délais suivants :
                </p>
                
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                    <div style="background: var(--gray-50); padding: 1.5rem; border-radius: var(--radius-lg); text-align: center;">
                        <div style="font-size: 2rem; color: var(--primary); margin-bottom: 0.5rem;">
                            <i class="fas fa-mobile-alt"></i>
                        </div>
                        <h4 style="font-size: 1rem; margin-bottom: 0.25rem;">Mobile Money</h4>
                        <p style="color: var(--gray-500); font-size: 0.875rem; margin: 0;">24 à 48 heures</p>
                    </div>
                    <div style="background: var(--gray-50); padding: 1.5rem; border-radius: var(--radius-lg); text-align: center;">
                        <div style="font-size: 2rem; color: var(--primary); margin-bottom: 0.5rem;">
                            <i class="fas fa-credit-card"></i>
                        </div>
                        <h4 style="font-size: 1rem; margin-bottom: 0.25rem;">Carte bancaire</h4>
                        <p style="color: var(--gray-500); font-size: 0.875rem; margin: 0;">3 à 5 jours ouvrés</p>
                    </div>
                    <div style="background: var(--gray-50); padding: 1.5rem; border-radius: var(--radius-lg); text-align: center;">
                        <div style="font-size: 2rem; color: var(--primary); margin-bottom: 0.5rem;">
                            <i class="fas fa-wallet"></i>
                        </div>
                        <h4 style="font-size: 1rem; margin-bottom: 0.25rem;">Crédit BantuDelice</h4>
                        <p style="color: var(--gray-500); font-size: 0.875rem; margin: 0;">Immédiat</p>
                    </div>
                </div>
            </div>
            
            <!-- How to request -->
            <div style="margin-bottom: 2rem;">
                <h2 style="font-size: 1.5rem; color: var(--primary); margin-bottom: 1rem;">
                    <i class="fas fa-question-circle"></i> Comment demander un remboursement ?
                </h2>
                <p style="color: var(--gray-600); line-height: 1.8; margin-bottom: 1rem;">
                    Pour demander un remboursement, suivez ces étapes :
                </p>
                
                <div style="display: flex; flex-direction: column; gap: 1rem;">
                    <div style="display: flex; gap: 1rem; align-items: flex-start;">
                        <div style="width: 32px; height: 32px; background: var(--primary); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; flex-shrink: 0;">1</div>
                        <div>
                            <h4 style="font-size: 1rem; margin-bottom: 0.25rem;">Accédez à votre historique de commandes</h4>
                            <p style="color: var(--gray-500); font-size: 0.9375rem; margin: 0;">Connectez-vous et allez dans "Mes commandes"</p>
                        </div>
                    </div>
                    <div style="display: flex; gap: 1rem; align-items: flex-start;">
                        <div style="width: 32px; height: 32px; background: var(--primary); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; flex-shrink: 0;">2</div>
                        <div>
                            <h4 style="font-size: 1rem; margin-bottom: 0.25rem;">Sélectionnez la commande concernée</h4>
                            <p style="color: var(--gray-500); font-size: 0.9375rem; margin: 0;">Cliquez sur "Signaler un problème"</p>
                        </div>
                    </div>
                    <div style="display: flex; gap: 1rem; align-items: flex-start;">
                        <div style="width: 32px; height: 32px; background: var(--primary); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; flex-shrink: 0;">3</div>
                        <div>
                            <h4 style="font-size: 1rem; margin-bottom: 0.25rem;">Décrivez le problème</h4>
                            <p style="color: var(--gray-500); font-size: 0.9375rem; margin: 0;">Fournissez les détails et photos si nécessaire</p>
                        </div>
                    </div>
                    <div style="display: flex; gap: 1rem; align-items: flex-start;">
                        <div style="width: 32px; height: 32px; background: var(--success); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; flex-shrink: 0;"><i class="fas fa-check"></i></div>
                        <div>
                            <h4 style="font-size: 1rem; margin-bottom: 0.25rem;">Attendez notre réponse</h4>
                            <p style="color: var(--gray-500); font-size: 0.9375rem; margin: 0;">Notre équipe vous répondra sous 24h</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Contact -->
            <div style="background: var(--gray-50); padding: 1.5rem; border-radius: var(--radius-xl); margin-top: 2rem;">
                <h3 style="font-size: 1.125rem; margin-bottom: 0.5rem;">Besoin d'aide ?</h3>
                <p style="color: var(--gray-600); margin-bottom: 1rem;">
                    Notre équipe de support est disponible pour vous aider avec votre demande de remboursement.
                </p>
                <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
                    <a href="{{ route('contact.us') }}" class="btn btn-primary">
                        <i class="fas fa-envelope"></i> Nous contacter
                    </a>
                    <a href="https://wa.me/242064000000" class="btn btn-secondary" style="background: #25D366; color: white; border-color: #25D366;">
                        <i class="fab fa-whatsapp"></i> WhatsApp
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
