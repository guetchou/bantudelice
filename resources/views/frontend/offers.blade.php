@extends('frontend.layouts.app-modern')
@section('title', 'Promotions & Offres | BantuDelice')
@section('description', 'Découvrez toutes les promotions et offres spéciales sur BantuDelice. Économisez sur vos livraisons de repas.')

@section('content')
<!-- Hero Section -->
<section style="background: linear-gradient(135deg, #FF6B35 0%, #F59E0B 50%, #10B981 100%); padding: 150px 0 80px; text-align: center; position: relative; overflow: hidden;">
    <!-- Animated Background -->
    <div style="position: absolute; inset: 0; opacity: 0.1;">
        <div style="position: absolute; width: 300px; height: 300px; background: white; border-radius: 50%; top: -100px; right: -100px;"></div>
        <div style="position: absolute; width: 200px; height: 200px; background: white; border-radius: 50%; bottom: -50px; left: -50px;"></div>
    </div>
    
    <div class="container" style="position: relative; z-index: 1;">
        <span class="section-badge" style="background: rgba(255,255,255,0.2); color: white; font-size: 1rem; padding: 0.75rem 1.5rem;">
            <i class="fas fa-gift"></i> Offres Exclusives
        </span>
        <h1 style="color: white; font-size: clamp(2.5rem, 6vw, 4rem); margin-top: 1rem; text-shadow: 0 2px 10px rgba(0,0,0,0.2);">
            Promotions & Offres
        </h1>
        <p style="color: rgba(255,255,255,0.9); max-width: 600px; margin: 1rem auto 0; font-size: 1.25rem;">
            Profitez de nos offres exceptionnelles pour commander vos plats préférés !
        </p>
    </div>
</section>

<!-- Featured Offers -->
<section class="section" style="margin-top: -60px;">
    <div class="container">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 2rem;">
            
            <!-- Offer 1: First Order -->
            <div style="background: linear-gradient(135deg, #FF6B35 0%, #F59E0B 100%); border-radius: var(--radius-2xl); padding: 2rem; color: white; position: relative; overflow: hidden; box-shadow: 0 20px 60px rgba(255, 107, 53, 0.3);">
                <div style="position: absolute; top: -20px; right: -20px; width: 100px; height: 100px; background: rgba(255,255,255,0.1); border-radius: 50%;"></div>
                <div style="position: absolute; bottom: -30px; left: -30px; width: 80px; height: 80px; background: rgba(255,255,255,0.1); border-radius: 50%;"></div>
                
                <span style="background: rgba(255,255,255,0.2); padding: 0.5rem 1rem; border-radius: var(--radius-lg); font-size: 0.875rem; font-weight: 600;">NOUVEAU CLIENT</span>
                
                <h2 style="font-size: 3rem; margin: 1rem 0 0.5rem; font-weight: 800;">-20%</h2>
                <h3 style="font-size: 1.25rem; margin-bottom: 1rem;">Sur votre première commande</h3>
                
                <p style="opacity: 0.9; margin-bottom: 1.5rem;">Inscrivez-vous et recevez automatiquement 20% de réduction sur votre première commande !</p>
                
                <div style="background: rgba(255,255,255,0.15); padding: 1rem; border-radius: var(--radius-lg); display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <small style="opacity: 0.8;">Code promo</small>
                        <p style="font-size: 1.25rem; font-weight: 700; margin: 0;">BIENVENUE20</p>
                    </div>
                    <button onclick="copyCode('BIENVENUE20')" style="background: white; color: var(--primary); border: none; padding: 0.5rem 1rem; border-radius: var(--radius-lg); font-weight: 600; cursor: pointer;">
                        <i class="fas fa-copy"></i> Copier
                    </button>
                </div>
            </div>
            
            <!-- Offer 2: Free Delivery -->
            <div style="background: linear-gradient(135deg, #10B981 0%, #059669 100%); border-radius: var(--radius-2xl); padding: 2rem; color: white; position: relative; overflow: hidden; box-shadow: 0 20px 60px rgba(16, 185, 129, 0.3);">
                <div style="position: absolute; top: -20px; right: -20px; width: 100px; height: 100px; background: rgba(255,255,255,0.1); border-radius: 50%;"></div>
                
                <span style="background: rgba(255,255,255,0.2); padding: 0.5rem 1rem; border-radius: var(--radius-lg); font-size: 0.875rem; font-weight: 600;">LIVRAISON</span>
                
                <h2 style="font-size: 2rem; margin: 1rem 0 0.5rem; font-weight: 800;">
                    <i class="fas fa-motorcycle"></i> LIVRAISON GRATUITE
                </h2>
                <h3 style="font-size: 1.25rem; margin-bottom: 1rem;">Dès 15 000 FCFA</h3>
                
                <p style="opacity: 0.9; margin-bottom: 1.5rem;">Profitez de la livraison gratuite sur toutes les commandes supérieures à 15 000 FCFA !</p>
                
                <a href="{{ route('home') }}" class="btn" style="background: white; color: #10B981; width: 100%;">
                    <i class="fas fa-shopping-bag"></i> Commander maintenant
                </a>
            </div>
            
            <!-- Offer 3: Weekend Special -->
            <div style="background: linear-gradient(135deg, #8B5CF6 0%, #7C3AED 100%); border-radius: var(--radius-2xl); padding: 2rem; color: white; position: relative; overflow: hidden; box-shadow: 0 20px 60px rgba(139, 92, 246, 0.3);">
                <div style="position: absolute; bottom: -30px; right: -30px; width: 120px; height: 120px; background: rgba(255,255,255,0.1); border-radius: 50%;"></div>
                
                <span style="background: rgba(255,255,255,0.2); padding: 0.5rem 1rem; border-radius: var(--radius-lg); font-size: 0.875rem; font-weight: 600;">WEEK-END</span>
                
                <h2 style="font-size: 3rem; margin: 1rem 0 0.5rem; font-weight: 800;">-15%</h2>
                <h3 style="font-size: 1.25rem; margin-bottom: 1rem;">Spécial Week-end</h3>
                
                <p style="opacity: 0.9; margin-bottom: 1.5rem;">Chaque samedi et dimanche, profitez de 15% de réduction sur toutes vos commandes !</p>
                
                <div style="background: rgba(255,255,255,0.15); padding: 1rem; border-radius: var(--radius-lg); display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <small style="opacity: 0.8;">Code promo</small>
                        <p style="font-size: 1.25rem; font-weight: 700; margin: 0;">WEEKEND15</p>
                    </div>
                    <button onclick="copyCode('WEEKEND15')" style="background: white; color: #8B5CF6; border: none; padding: 0.5rem 1rem; border-radius: var(--radius-lg); font-weight: 600; cursor: pointer;">
                        <i class="fas fa-copy"></i> Copier
                    </button>
                </div>
            </div>
            
        </div>
    </div>
</section>

<!-- More Offers -->
<section class="section" style="background: var(--gray-50);">
    <div class="container">
        <div class="section-header">
            <span class="section-badge">
                <i class="fas fa-percent"></i> Plus d'offres
            </span>
            <h2 class="section-title">Offres en cours</h2>
        </div>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.5rem;">
            
            <!-- Offer Card 1 -->
            <div style="background: white; border-radius: var(--radius-xl); overflow: hidden; box-shadow: var(--shadow-lg);">
                <div style="background: linear-gradient(135deg, #FF6B35 0%, #F59E0B 100%); padding: 1.5rem; text-align: center; color: white;">
                    <i class="fas fa-birthday-cake" style="font-size: 2rem; margin-bottom: 0.5rem;"></i>
                    <h3 style="font-size: 1.25rem; margin: 0;">Anniversaire</h3>
                </div>
                <div style="padding: 1.5rem;">
                    <h4 style="font-size: 1.5rem; color: var(--primary); margin-bottom: 0.5rem;">-25% Offert</h4>
                    <p style="color: var(--gray-600); margin-bottom: 1rem;">Recevez 25% de réduction le jour de votre anniversaire ! Ajoutez votre date de naissance dans votre profil.</p>
                    <a href="{{ route('user.profile') }}" style="color: var(--primary); font-weight: 600; text-decoration: none;">
                        Mettre à jour mon profil <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
            </div>
            
            <!-- Offer Card 2 -->
            <div style="background: white; border-radius: var(--radius-xl); overflow: hidden; box-shadow: var(--shadow-lg);">
                <div style="background: linear-gradient(135deg, #3B82F6 0%, #1D4ED8 100%); padding: 1.5rem; text-align: center; color: white;">
                    <i class="fas fa-users" style="font-size: 2rem; margin-bottom: 0.5rem;"></i>
                    <h3 style="font-size: 1.25rem; margin: 0;">Parrainage</h3>
                </div>
                <div style="padding: 1.5rem;">
                    <h4 style="font-size: 1.5rem; color: var(--primary); margin-bottom: 0.5rem;">5 000 FCFA Chacun</h4>
                    <p style="color: var(--gray-600); margin-bottom: 1rem;">Parrainez un ami et recevez chacun 5 000 FCFA de crédit sur votre prochaine commande !</p>
                    <a href="{{ route('user.profile') }}" style="color: var(--primary); font-weight: 600; text-decoration: none;">
                        Parrainer un ami <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
            </div>
            
            <!-- Offer Card 3 -->
            <div style="background: white; border-radius: var(--radius-xl); overflow: hidden; box-shadow: var(--shadow-lg);">
                <div style="background: linear-gradient(135deg, #EC4899 0%, #BE185D 100%); padding: 1.5rem; text-align: center; color: white;">
                    <i class="fas fa-heart" style="font-size: 2rem; margin-bottom: 0.5rem;"></i>
                    <h3 style="font-size: 1.25rem; margin: 0;">Fidélité</h3>
                </div>
                <div style="padding: 1.5rem;">
                    <h4 style="font-size: 1.5rem; color: var(--primary); margin-bottom: 0.5rem;">1 Commande = 1 Point</h4>
                    <p style="color: var(--gray-600); margin-bottom: 1rem;">Cumulez des points à chaque commande et échangez-les contre des réductions et cadeaux !</p>
                    <a href="{{ route('user.profile') }}" style="color: var(--primary); font-weight: 600; text-decoration: none;">
                        Voir mes points <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
            </div>
            
            <!-- Offer Card 4 -->
            <div style="background: white; border-radius: var(--radius-xl); overflow: hidden; box-shadow: var(--shadow-lg);">
                <div style="background: linear-gradient(135deg, #F59E0B 0%, #D97706 100%); padding: 1.5rem; text-align: center; color: white;">
                    <i class="fas fa-clock" style="font-size: 2rem; margin-bottom: 0.5rem;"></i>
                    <h3 style="font-size: 1.25rem; margin: 0;">Happy Hour</h3>
                </div>
                <div style="padding: 1.5rem;">
                    <h4 style="font-size: 1.5rem; color: var(--primary); margin-bottom: 0.5rem;">-10% de 14h à 17h</h4>
                    <p style="color: var(--gray-600); margin-bottom: 1rem;">Profitez de 10% de réduction sur vos commandes passées entre 14h et 17h en semaine !</p>
                    <a href="{{ route('home') }}" style="color: var(--primary); font-weight: 600; text-decoration: none;">
                        Commander <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
            </div>
            
        </div>
    </div>
</section>

<!-- Newsletter Section -->
<section class="section">
    <div class="container">
        <div style="background: linear-gradient(135deg, var(--secondary) 0%, var(--accent) 100%); border-radius: var(--radius-2xl); padding: 3rem; text-align: center; color: white;">
            <i class="fas fa-bell" style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.9;"></i>
            <h2 style="font-size: 2rem; margin-bottom: 0.5rem;">Ne ratez aucune offre !</h2>
            <p style="opacity: 0.9; max-width: 500px; margin: 0 auto 2rem;">Inscrivez-vous à notre newsletter pour recevoir nos offres exclusives directement dans votre boîte mail.</p>
            
            <form style="display: flex; gap: 1rem; max-width: 500px; margin: 0 auto; flex-wrap: wrap; justify-content: center;">
                <input type="email" placeholder="Votre adresse email" 
                       style="flex: 1; min-width: 250px; padding: 1rem 1.5rem; border: none; border-radius: var(--radius-xl); font-size: 1rem;">
                <button type="submit" class="btn" style="background: white; color: var(--primary); padding: 1rem 2rem;">
                    <i class="fas fa-paper-plane"></i> S'abonner
                </button>
            </form>
        </div>
    </div>
</section>

<!-- Toast for copy notification -->
<div id="copyToast" style="position: fixed; bottom: 2rem; left: 50%; transform: translateX(-50%) translateY(100px); background: var(--secondary); color: white; padding: 1rem 2rem; border-radius: var(--radius-xl); box-shadow: var(--shadow-xl); transition: transform 0.3s ease; z-index: 1000;">
    <i class="fas fa-check-circle"></i> Code copié !
</div>
@endsection

@section('scripts')
<script>
    function copyCode(code) {
        navigator.clipboard.writeText(code).then(() => {
            const toast = document.getElementById('copyToast');
            toast.style.transform = 'translateX(-50%) translateY(0)';
            setTimeout(() => {
                toast.style.transform = 'translateX(-50%) translateY(100px)';
            }, 2000);
        });
    }
</script>
@endsection
