@extends('frontend.layouts.app-modern')
@section('title', 'Votre Panier | BantuDelice')
@section('description', 'Consultez votre panier BantuDelice.')

@section('content')
<!-- Hero Section -->
<section style="background: linear-gradient(135deg, var(--secondary) 0%, var(--accent) 100%); padding: 120px 0 40px; text-align: center;">
    <div class="container">
        <h1 style="color: white; font-size: 2rem;">
            <i class="fas fa-shopping-cart"></i> Votre Panier
        </h1>
    </div>
</section>

<!-- Progress Steps -->
<section style="background: white; padding: 1.5rem 0; border-bottom: 1px solid var(--gray-100);">
    <div class="container">
        <div style="display: flex; justify-content: center; align-items: center; gap: 0;">
            <div style="display: flex; align-items: center;">
                <div style="width: 36px; height: 36px; background: var(--primary); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700;">1</div>
                <span style="margin-left: 0.5rem; font-weight: 600; color: var(--primary);">Panier</span>
            </div>
            <div style="width: 60px; height: 3px; background: var(--gray-200); margin: 0 0.5rem;"></div>
            <div style="display: flex; align-items: center;">
                <div style="width: 36px; height: 36px; background: var(--gray-200); color: var(--gray-500); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700;">2</div>
                <span style="margin-left: 0.5rem; font-weight: 500; color: var(--gray-400);">Paiement</span>
            </div>
            <div style="width: 60px; height: 3px; background: var(--gray-200); margin: 0 0.5rem;"></div>
            <div style="display: flex; align-items: center;">
                <div style="width: 36px; height: 36px; background: var(--gray-200); color: var(--gray-500); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700;">3</div>
                <span style="margin-left: 0.5rem; font-weight: 500; color: var(--gray-400);">Confirmeration</span>
            </div>
        </div>
    </div>
</section>

<!-- Cart Content -->
<section class="section" style="background: var(--gray-50);">
    <div class="container">
        @if(session()->has('alert'))
            <div style="background: {{ session()->get('alert.type') == 'success' ? 'var(--success)' : 'var(--error)' }}; color: white; padding: 1rem; border-radius: var(--radius-lg); margin-bottom: 1.5rem; text-align: center;">
                <strong>{{ session()->get('alert.message') }}</strong>
            </div>
        @endif
        
        @if($cartData->count() > 0)
        <div style="display: grid; grid-template-columns: 1fr 350px; gap: 2rem; align-items: start;">
            <!-- Cart Items -->
            <div style="background: white; border-radius: var(--radius-xl); box-shadow: var(--shadow-sm); overflow: hidden;">
                <div style="padding: 1.5rem; border-bottom: 1px solid var(--gray-100);">
                    <h2 style="font-size: 1.125rem; margin: 0;">Articles ({{ $cartData->count() }})</h2>
                </div>
                
                @foreach($cartData as $data)
                <div class="cart-item" data-cart-id="{{$data->id}}" style="padding: 1.5rem; border-bottom: 1px solid var(--gray-100); display: flex; gap: 1rem; align-items: center;">
                    <!-- Image -->
                    <img src="{{ $data->image ? (strpos($data->image, 'http') === 0 ? $data->image : asset('images/product_images/' . $data->image)) : asset('images/product_images/default-food.jpg') }}" 
                         style="width: 80px; height: 80px; border-radius: var(--radius-lg); object-fit: cover;">
                    
                    <!-- Info -->
                    <div style="flex: 1;">
                        <h4 style="font-size: 1rem; margin-bottom: 0.25rem;">{{$data->name}}</h4>
                        <p style="color: var(--gray-500); font-size: 0.875rem; margin: 0;">{!! Str::limit($data->description, 50) !!}</p>
                        <p class="item-price" data-price="{{$data->price}}" style="color: var(--primary); font-weight: 700; margin: 0.5rem 0 0;">{{number_format($data->price, 0, ',', ' ')}} FCFA</p>
                    </div>
                    
                    <!-- Quantity -->
                    <form class="cart-update-form" action="{{route('cart.update', $data->id)}}" method="post" style="display: flex; align-items: center; gap: 0.5rem;">
                        @csrf
                        @method('PUT')
                        <input type="number" name="qty" value="{{$data->qty}}" min="1" max="10" class="cart-qty-input"
                               style="width: 60px; padding: 0.5rem; border: 2px solid var(--gray-200); border-radius: var(--radius-md); text-align: center;">
                        <button type="submit" class="cart-update-btn" style="width: 36px; height: 36px; background: var(--gray-100); border: none; border-radius: var(--radius-md); cursor: pointer;">
                            <i class="fas fa-sync-alt" style="color: var(--gray-600);"></i>
                        </button>
                    </form>
                    
                    <!-- Subtotal -->
                    <div style="text-align: right; min-width: 100px;">
                        <p class="item-subtotal" style="font-weight: 700; font-size: 1rem; margin: 0;">{{number_format($data->price * $data->qty, 0, ',', ' ')}} F</p>
                    </div>
                    
                    <!-- Delete -->
                    <a href="{{route('cart.item', $data->id)}}" 
                       style="width: 36px; height: 36px; background: rgba(239, 68, 68, 0.1); border-radius: var(--radius-md); display: flex; align-items: center; justify-content: center;">
                        <i class="fas fa-trash" style="color: var(--error);"></i>
                    </a>
                </div>
                @endforeach
            </div>
            
            <!-- Order Summary -->
            <div style="position: sticky; top: 100px;">
                <div style="background: white; padding: 1.5rem; border-radius: var(--radius-xl); box-shadow: var(--shadow-lg);">
                    <h3 style="font-size: 1.125rem; margin-bottom: 1rem;">Résumé</h3>
                    
                    <div style="display: flex; justify-content: space-between; margin-bottom: 0.75rem;">
                        <span style="color: var(--gray-600);">Sous-total</span>
                        <span style="font-weight: 600;" id="cartSubtotal">{{number_format($total, 0, ',', ' ')}} FCFA</span>
                    </div>
                    
                    @if(auth()->check())
                        @php
                            $loyaltyPoints = \App\Services\LoyaltyService::getBalance(auth()->user()->id);
                            $loyaltyDiscount = \App\Services\LoyaltyService::calculateDiscount($loyaltyPoints);
                        @endphp
                        @if($loyaltyPoints > 0)
                        <div style="background: linear-gradient(135deg, rgba(255, 107, 53, 0.1) 0%, rgba(245, 158, 11, 0.1) 100%); padding: 1rem; border-radius: 12px; margin-bottom: 1rem;">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                                <span style="color: var(--gray-700); font-weight: 600;">
                                    <i class="fas fa-star" style="color: #FF6B35;"></i> Points de fidélité
                                </span>
                                <span style="font-weight: 700; color: #FF6B35;">{{ number_format($loyaltyPoints, 0, ',', ' ') }} pts</span>
                            </div>
                            <p style="color: var(--gray-600); font-size: 0.8125rem; margin: 0;">
                                Vous pouvez économiser jusqu'à <strong>{{ number_format($loyaltyDiscount, 0, ',', ' ') }} FCFA</strong> avec vos points
                            </p>
                        </div>
                        @endif
                    @endif
                    
                    <div style="display: flex; justify-content: space-between; margin-bottom: 1rem; padding-bottom: 1rem; border-bottom: 2px solid var(--gray-100);">
                        <span style="color: var(--gray-500); font-size: 0.875rem;">Frais de livraison calculés à l'étape suivante</span>
                    </div>
                    
                    <div style="display: flex; justify-content: space-between; margin-bottom: 1.5rem;">
                        <span style="font-size: 1.125rem; font-weight: 700;">Total estimé</span>
                        <span style="font-size: 1.25rem; font-weight: 800; color: var(--primary);" id="cartTotal">{{number_format($total, 0, ',', ' ')}} FCFA</span>
                    </div>
                    
                    @if($check != null)
                        @if($check->min_order <= $total)
                            <a href="{{route('checkout.detail')}}" class="btn btn-primary btn-lg" style="width: 100%;">
                                <i class="fas fa-lock"></i> Passer au paiement
                            </a>
                        @else
                            <div style="background: rgba(245, 158, 11, 0.1); padding: 1rem; border-radius: var(--radius-lg); text-align: center;">
                                <i class="fas fa-info-circle" style="color: var(--warning);"></i>
                                <p style="margin: 0.5rem 0 0; color: var(--gray-700); font-size: 0.9375rem;">
                                    Commande minimum : <strong>{{number_format($check->min_order, 0, ',', ' ')}} FCFA</strong>
                                </p>
                            </div>
                        @endif
                    @endif
                    
                    <a href="{{ url('/') }}" style="display: block; text-align: center; color: var(--primary); margin-top: 1rem; font-weight: 500;">
                        <i class="fas fa-arrow-left"></i> Continuer mes achats
                    </a>
                </div>
            </div>
        </div>
        @else
        <!-- Empty Cart -->
        <div style="text-align: center; padding: 4rem 2rem; background: white; border-radius: var(--radius-xl); max-width: 500px; margin: 0 auto;">
            <div style="font-size: 4rem; color: var(--gray-300); margin-bottom: 1rem;">
                <i class="fas fa-shopping-cart"></i>
            </div>
            <h2 style="font-size: 1.5rem; margin-bottom: 0.5rem;">Votre panier est vide</h2>
            <p style="color: var(--gray-500); margin-bottom: 1.5rem;">
                Découvrez nos délicieux plats et commencez à commander !
            </p>
            <a href="{{ url('/') }}" class="btn btn-primary btn-lg">
                <i class="fas fa-utensils"></i> Découvrir les restaurants
            </a>
        </div>
        @endif
    </div>
</section>
@endsection

@section('styles')
<style>
    @media (max-width: 991px) {
        .section > .container > div {
            grid-template-columns: 1fr !important;
        }
    }
</style>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
    const cartForms = document.querySelectorAll('.cart-update-form');
    
    // Fonction pour formater un nombre avec espaces
    function formatNumber(num) {
        return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ' ');
    }
    
    // Fonction pour calculer et mettre à jour le total du panier
    function updateCartTotals() {
        let subtotal = 0;
        
        document.querySelectorAll('.cart-item').forEach(function(item) {
            const price = parseFloat(item.querySelector('.item-price').dataset.price);
            const qty = parseInt(item.querySelector('.cart-qty-input').value);
            const itemSubtotal = price * qty;
            
            // Mettre à jour le sous-total de l'article
            const subtotalElement = item.querySelector('.item-subtotal');
            if (subtotalElement) {
                subtotalElement.textContent = formatNumber(itemSubtotal) + ' F';
            }
            
            subtotal += itemSubtotal;
        });
        
        // Mettre à jour le sous-total global
        const cartSubtotalEl = document.getElementById('cartSubtotal');
        if (cartSubtotalEl) {
            cartSubtotalEl.textContent = formatNumber(subtotal) + ' FCFA';
        }
        
        // Mettre à jour le total estimé
        const cartTotalEl = document.getElementById('cartTotal');
        if (cartTotalEl) {
            cartTotalEl.textContent = formatNumber(subtotal) + ' FCFA';
        }
    }
    
    // Intercepter la soumission des formulaires de mise à jour
    cartForms.forEach(function(form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(form);
            const cartId = form.closest('.cart-item').dataset.cartId;
            const qty = parseInt(formData.get('qty'));
            const submitBtn = form.querySelector('.cart-update-btn');
            const originalIcon = submitBtn.innerHTML;
            
            // Désactiver le bouton et afficher un loader
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin" style="color: var(--gray-600);"></i>';
            
            // Appel AJAX
            fetch(form.action, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                },
                body: formData
            })
            .then(response => {
                if (response.ok) {
                    return response.json().catch(() => ({ status: true }));
                }
                throw new Error('Erreur lors de la mise à jour');
            })
            .then(data => {
                // Si la quantité est 0 ou moins, supprimer l'article du DOM
                if (qty <= 0) {
                    const cartItem = form.closest('.cart-item');
                    cartItem.style.transition = 'opacity 0.3s, transform 0.3s';
                    cartItem.style.opacity = '0';
                    cartItem.style.transform = 'translateX(-20px)';
                    setTimeout(() => {
                        cartItem.remove();
                        updateCartTotals();
                        // Mettre à jour le badge du panier si la fonction existe
                        if (typeof updateCartCount === 'function') {
                            updateCartCount();
                        }
                    }, 300);
                } else {
                    // Mettre à jour les totaux
                    updateCartTotals();
                    
                    // Afficher un message de succès (optionnel)
                    showToast('Quantité mise à jour', 'success');
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                showToast('Erreur lors de la mise à jour', 'error');
            })
            .finally(() => {
                // Réactiver le bouton
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalIcon;
            });
        });
        
        // Mettre à jour en temps réel lors du changement de valeur (optionnel)
        const qtyInput = form.querySelector('.cart-qty-input');
        qtyInput.addEventListener('change', function() {
            const qty = parseInt(this.value);
            if (qty > 0 && qty <= 10) {
                // Mettre à jour le sous-total de l'article immédiatement (sans sauvegarder)
                const cartItem = form.closest('.cart-item');
                const price = parseFloat(cartItem.querySelector('.item-price').dataset.price);
                const itemSubtotal = price * qty;
                const subtotalElement = cartItem.querySelector('.item-subtotal');
                if (subtotalElement) {
                    subtotalElement.textContent = formatNumber(itemSubtotal) + ' F';
                }
                // Mettre à jour le total global
                updateCartTotals();
            }
        });
    });
    
    // Fonction pour afficher des toasts (réutiliser celle existante ou créer une simple)
    function showToast(message, type = 'success') {
        // Vérifier si une fonction toast existe déjà
        if (typeof window.showToast === 'function') {
            window.showToast(message, type);
            return;
        }
        
        // Sinon, créer un toast simple
        const toast = document.createElement('div');
        toast.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 1rem 1.5rem;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            z-index: 10000;
            font-weight: 500;
            background: ${type === 'success' ? '#05944F' : '#DC2626'};
            color: white;
            animation: slideIn 0.3s ease-out;
        `;
        toast.textContent = message;
        document.body.appendChild(toast);
        
        setTimeout(() => {
            toast.style.animation = 'slideOut 0.3s ease-out';
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    }
    
    // Ajouter les animations CSS si elles n'existent pas
    if (!document.getElementById('cart-toast-styles')) {
        const style = document.createElement('style');
        style.id = 'cart-toast-styles';
        style.textContent = `
            @keyframes slideIn {
                from {
                    opacity: 0;
                    transform: translateY(-20px);
                }
                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }
            @keyframes slideOut {
                from {
                    opacity: 1;
                    transform: translateY(0);
                }
                to {
                    opacity: 0;
                    transform: translateY(-20px);
                }
            }
        `;
        document.head.appendChild(style);
    }
});
</script>
@endsection
