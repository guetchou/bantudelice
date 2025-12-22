@extends('frontend.layouts.app-modern')
@section('title', 'Commande Confirmée | BantuDelice')
@section('description', 'Votre commande a été confirmée avec succès. Merci de votre confiance !')

@section('content')
<!-- Success Section -->
<section style="background: linear-gradient(135deg, var(--success) 0%, #059669 100%); padding: 150px 0 80px; text-align: center; position: relative; overflow: hidden;">
    <!-- Animated confetti effect -->
    <div style="position: absolute; inset: 0; pointer-events: none;">
        <div class="confetti"></div>
    </div>
    
    <div class="container" style="position: relative; z-index: 1;">
        <div style="width: 120px; height: 120px; background: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 2rem; animation: scaleIn 0.5s ease;">
            <i class="fas fa-check" style="font-size: 4rem; color: var(--success);"></i>
        </div>
        
        <h1 style="color: white; font-size: clamp(2rem, 5vw, 3rem); margin-bottom: 1rem;">
            Merci pour votre commande !
        </h1>
        <p style="color: rgba(255,255,255,0.9); max-width: 600px; margin: 0 auto; font-size: 1.25rem;">
            Votre commande a été confirmée et est en cours de préparation.
        </p>
    </div>
</section>

<!-- Order Details -->
<section class="section" style="margin-top: -40px;">
    <div class="container">
        <div style="max-width: 700px; margin: 0 auto;">
            
            <!-- Order Card -->
            <div style="background: white; border-radius: var(--radius-2xl); box-shadow: var(--shadow-xl); overflow: hidden;">
                <!-- Header -->
                <div style="background: var(--gray-50); padding: 1.5rem 2rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
                    <div>
                        <p style="color: var(--gray-500); font-size: 0.875rem; margin-bottom: 0.25rem;">Numéro de commande</p>
                        <p style="font-size: 1.25rem; font-weight: 700; color: var(--primary); margin: 0;">#{{ session('order_no', 'BD' . rand(100000, 999999)) }}</p>
                    </div>
                    <div style="text-align: right;">
                        <p style="color: var(--gray-500); font-size: 0.875rem; margin-bottom: 0.25rem;">Date</p>
                        <p style="font-weight: 600; margin: 0;">{{ now()->format('d/m/Y H:i') }}</p>
                    </div>
                </div>
                
                <!-- Progress Steps -->
                <div style="padding: 2rem;">
                    <h3 style="font-size: 1rem; color: var(--gray-700); margin-bottom: 1.5rem;">Suivi de commande</h3>
                    
                    <div style="display: flex; justify-content: space-between; position: relative;">
                        <!-- Progress Line -->
                        <div style="position: absolute; top: 20px; left: 0; right: 0; height: 4px; background: var(--gray-200); z-index: 0;">
                            <div style="width: 33%; height: 100%; background: var(--success);"></div>
                        </div>
                        
                        <!-- Step 1 -->
                        <div style="text-align: center; position: relative; z-index: 1; flex: 1;">
                            <div style="width: 44px; height: 44px; background: var(--success); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 0.5rem; color: white;">
                                <i class="fas fa-check"></i>
                            </div>
                            <p style="font-size: 0.875rem; font-weight: 600; margin: 0;">Confirmée</p>
                        </div>
                        
                        <!-- Step 2 -->
                        <div style="text-align: center; position: relative; z-index: 1; flex: 1;">
                            <div style="width: 44px; height: 44px; background: var(--warning); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 0.5rem; color: white; animation: pulse 2s infinite;">
                                <i class="fas fa-utensils"></i>
                            </div>
                            <p style="font-size: 0.875rem; font-weight: 600; margin: 0;">En préparation</p>
                        </div>
                        
                        <!-- Step 3 -->
                        <div style="text-align: center; position: relative; z-index: 1; flex: 1;">
                            <div style="width: 44px; height: 44px; background: var(--gray-300); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 0.5rem; color: white;">
                                <i class="fas fa-motorcycle"></i>
                            </div>
                            <p style="font-size: 0.875rem; color: var(--gray-500); margin: 0;">En livraison</p>
                        </div>
                        
                        <!-- Step 4 -->
                        <div style="text-align: center; position: relative; z-index: 1; flex: 1;">
                            <div style="width: 44px; height: 44px; background: var(--gray-300); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 0.5rem; color: white;">
                                <i class="fas fa-home"></i>
                            </div>
                            <p style="font-size: 0.875rem; color: var(--gray-500); margin: 0;">Livrée</p>
                        </div>
                    </div>
                </div>
                
                <!-- Estimated Time -->
                <div style="background: linear-gradient(135deg, rgba(255, 107, 53, 0.1) 0%, rgba(245, 158, 11, 0.1) 100%); padding: 1.5rem 2rem; margin: 0 2rem 2rem; border-radius: var(--radius-xl); display: flex; align-items: center; gap: 1rem;">
                    <div style="width: 50px; height: 50px; background: var(--primary); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; flex-shrink: 0;">
                        <i class="fas fa-clock" style="font-size: 1.25rem;"></i>
                    </div>
                    <div>
                        <p style="font-size: 0.875rem; color: var(--gray-600); margin-bottom: 0.25rem;">Temps de livraison estimé</p>
                        <p style="font-size: 1.5rem; font-weight: 700; color: var(--primary); margin: 0;">30 - 45 min</p>
                    </div>
                </div>
                
                <!-- Actions -->
                <div style="padding: 0 2rem 2rem; display: flex; gap: 1rem; flex-wrap: wrap;">
                    @if(isset($order) && $order->order_no)
                        <a href="{{ route('track.order', $order->order_no) }}" class="btn btn-primary" style="flex: 1; min-width: 200px;">
                            <i class="fas fa-truck"></i> Suivre ma commande
                        </a>
                    @else
                        <a href="{{ route('user.profile') }}" class="btn btn-primary" style="flex: 1; min-width: 200px;">
                            <i class="fas fa-truck"></i> Suivre ma commande
                        </a>
                    @endif
                    <a href="{{ route('home') }}" class="btn btn-secondary" style="flex: 1; min-width: 200px;">
                        <i class="fas fa-home"></i> Retour à l'accueil
                    </a>
                </div>
            </div>
            
            <!-- Notification Info -->
            <div style="background: white; border-radius: var(--radius-xl); padding: 1.5rem; margin-top: 1.5rem; box-shadow: var(--shadow-md); display: flex; align-items: flex-start; gap: 1rem;">
                <div style="width: 44px; height: 44px; background: #3B82F6; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; flex-shrink: 0;">
                    <i class="fas fa-bell"></i>
                </div>
                <div>
                    <h4 style="font-size: 1rem; margin-bottom: 0.25rem;">Notifications activées</h4>
                    <p style="color: var(--gray-600); font-size: 0.9375rem; margin: 0;">
                        Vous recevrez des SMS et notifications à chaque étape de votre commande. Restez informé en temps réel !
                    </p>
                </div>
            </div>
            
            <!-- Help Section -->
            <div style="text-align: center; margin-top: 2rem; color: var(--gray-600);">
                <p style="margin-bottom: 1rem;">Un problème avec votre commande ?</p>
                <div style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
                    <a href="{{ route('help') }}" style="color: var(--primary); text-decoration: none; font-weight: 600;">
                        <i class="fas fa-question-circle"></i> Centre d'aide
                    </a>
                    <a href="https://wa.me/242064000000" style="color: #25D366; text-decoration: none; font-weight: 600;">
                        <i class="fab fa-whatsapp"></i> WhatsApp
                    </a>
                    <a href="{{ route('contact.us') }}" style="color: var(--primary); text-decoration: none; font-weight: 600;">
                        <i class="fas fa-envelope"></i> Contact
                    </a>
                </div>
            </div>
            
        </div>
    </div>
</section>

<!-- Recommended Section -->
<section class="section" style="background: var(--gray-50);">
    <div class="container">
        <div class="section-header">
            <span class="section-badge">
                <i class="fas fa-heart"></i> Pour vous
            </span>
            <h2 class="section-title">Vous aimerez aussi</h2>
        </div>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem;">
            @php
                $recommendations = [
                    ['name' => 'Poulet Braisé', 'price' => '8 500', 'rating' => 4.8],
                    ['name' => 'Saka Saka', 'price' => '6 000', 'rating' => 4.6],
                    ['name' => 'Poisson Braisé', 'price' => '9 500', 'rating' => 4.9],
                ];
            @endphp
            
            @foreach($recommendations as $item)
            <div style="background: white; border-radius: var(--radius-xl); overflow: hidden; box-shadow: var(--shadow-md);">
                <div style="height: 150px; background: linear-gradient(135deg, var(--gray-100) 0%, var(--gray-200) 100%); display: flex; align-items: center; justify-content: center;">
                    <i class="fas fa-utensils" style="font-size: 3rem; color: var(--gray-400);"></i>
                </div>
                <div style="padding: 1rem;">
                    <h4 style="font-size: 1rem; margin-bottom: 0.25rem;">{{ $item['name'] }}</h4>
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <span style="font-weight: 700; color: var(--primary);">{{ $item['price'] }} FCFA</span>
                        <span style="color: var(--warning);"><i class="fas fa-star"></i> {{ $item['rating'] }}</span>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</section>
@endsection

@section('styles')
<style>
    @keyframes scaleIn {
        0% { transform: scale(0); }
        50% { transform: scale(1.1); }
        100% { transform: scale(1); }
    }
    
    @keyframes pulse {
        0%, 100% { transform: scale(1); }
        50% { transform: scale(1.1); }
    }
    
    /* Confetti animation */
    .confetti {
        position: absolute;
        inset: 0;
        overflow: hidden;
    }
    
    .confetti::before,
    .confetti::after {
        content: '';
        position: absolute;
        width: 10px;
        height: 10px;
        background: #FFD700;
        animation: fall 3s linear infinite;
    }
    
    .confetti::before {
        left: 10%;
        animation-delay: 0s;
        background: #FF6B35;
    }
    
    .confetti::after {
        left: 80%;
        animation-delay: 1s;
        background: #10B981;
    }
    
    @keyframes fall {
        0% {
            transform: translateY(-100px) rotate(0deg);
            opacity: 1;
        }
        100% {
            transform: translateY(100vh) rotate(720deg);
            opacity: 0;
        }
    }
</style>
@endsection
