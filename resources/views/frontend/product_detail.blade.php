@extends('frontend.layouts.app-modern')
@php
    $foodBrandName = \App\Services\ConfigService::getCompanyName();
@endphp
@section('title', $proDetail->name . ' | ' . $foodBrandName)
@section('description', 'Commandez ' . $proDetail->name . ' chez ' . $restaurant->name . ' - Livraison rapide à domicile')
@section('body_class', 'bd-product-detail-page')

@section('content')
<section class="product-page">
    <div class="container">
        <!-- Breadcrumb -->
        <nav class="breadcrumb-modern premium-breadcrumb">
            <a href="{{ route('home') }}">Accueil</a>
            <span class="separator">/</span>
            <a href="{{ route('restaurant.detail', $restaurant->id) }}">
                {{ $restaurant->name }}
            </a>
            <span class="separator">/</span>
            <span class="current">{{ $proDetail->name }}</span>
        </nav>
        
        @if(Session::has('message'))
            <div class="alert-modern alert-success"><span>{{ Session::get('message') }}</span></div>
        @endif
        
        <!-- Product Detail Card -->
        <div class="product-detail-card">
            <div class="product-image-section">
                @if($proDetail->discount_price && $proDetail->discount_price < $proDetail->price)
                    <span class="product-badge">
                        -{{ round((($proDetail->price - $proDetail->discount_price) / $proDetail->price) * 100) }}%
                    </span>
                @endif
                <img src="{{ method_exists($proDetail, 'publicImageUrl') ? $proDetail->publicImageUrl() : ($proDetail->image ? (strpos($proDetail->image, 'http') === 0 ? $proDetail->image : asset('images/product_images/' . $proDetail->image)) : asset('images/product_images/default-food.jpg')) }}" 
                     alt="{{ $proDetail->name }}" 
                     class="product-image"
                     onerror="this.src='{{ asset('images/product_images/default-food.jpg') }}'">
            </div>
            
            <div class="product-info-section">
                <a href="{{ route('restaurant.detail', $restaurant->id) }}" class="restaurant-tag">{{ $restaurant->name }}</a>
                
                <h1 class="product-title">{{ $proDetail->name }}</h1>
                
            <div class="product-price-section">
                <span class="current-price">{{ number_format(($proDetail->discount_price && $proDetail->discount_price < $proDetail->price) ? $proDetail->discount_price : $proDetail->price, 0, ',', ' ') }} FCFA</span>
                @if($proDetail->discount_price && $proDetail->discount_price < $proDetail->price)
                    <span class="original-price">{{ number_format($proDetail->price, 0, ',', ' ') }} FCFA</span>
                        <span class="discount-badge">Promo</span>
                @endif
            </div>
                
                <p class="product-description">
                    @if($proDetail->description)
                        {{ $proDetail->description }}
                    @else
                        Découvrez ce délicieux plat préparé avec soin par {{ $restaurant->name }}. 
                        Commandez maintenant et faites-vous livrer à domicile !
                    @endif
                </p>
                
                <form action="{{ route('cart') }}" method="post" class="order-form" id="addToCartForm">
                    @csrf
                    <input type="hidden" name="restaurant_id" value="{{ $restaurant->id }}" />
                    <input type="hidden" name="user_id" value="@if(Auth::check()){{ auth()->user()->id }}@endif" />
                    <input type="hidden" name="product_id" value="{{ $proDetail->id }}" />
                    <input type="hidden" name="product_name" value="{{ $proDetail->name }}" />
                    <input type="hidden" name="price" value="{{ $proDetail->price }}" />
                    
                    <div class="form-group">
                        <label class="form-label">Quantité</label>
                        <div class="quantity-selector">
                            <button type="button" class="qty-btn" onclick="changeQty(-1)" {{ (isset($proDetail->is_available) && !$proDetail->is_available) ? 'disabled' : '' }}>-</button>
                            <input type="number" name="qty" id="qty" value="1" min="1" max="20" class="qty-input" readonly {{ (isset($proDetail->is_available) && !$proDetail->is_available) ? 'disabled' : '' }}>
                            <button type="button" class="qty-btn" onclick="changeQty(1)" {{ (isset($proDetail->is_available) && !$proDetail->is_available) ? 'disabled' : '' }}>+</button>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Instructions spéciales (optionnel)</label>
                        <textarea name="txtarea1" 
                                  id="txtarea1" 
                                  class="instructions-input"
                                  placeholder="Ex: Sans oignon, bien cuit, sauce à part..."></textarea>
                    </div>
                    
                    @if(isset($proDetail->is_available) && !$proDetail->is_available)
                        <button type="button" class="btn-add-cart is-disabled" id="submitCartBtn" disabled><span id="submitCartText">Indisponible</span></button>
                        <div class="product-availability-note">
                            Ce plat est temporairement indisponible.
                        </div>
                    @else
                    <button type="submit" class="btn-add-cart" id="submitCartBtn"><span id="submitCartText">Ajouter au panier</span></button>
                    @endif
                </form>
                
                <script>
                document.getElementById('addToCartForm').addEventListener('submit', function(e) {
                    e.preventDefault();
                    
                    const form = this;
                    const submitBtn = document.getElementById('submitCartBtn');
                    const submitText = document.getElementById('submitCartText');
                    const originalHTML = submitBtn.innerHTML;
                    
                    // ===== VALIDATION CÔTÉ CLIENT =====
                    const qtyInput = form.querySelector('input[name="qty"]');
                    const qty = parseInt(qtyInput.value) || 1;
                    
                    if (qty < 1 || qty > 20) {
                        showMessage('La quantité doit être entre 1 et 20', 'error');
                        return;
                    }
                    
                    const productId = form.querySelector('input[name="product_id"]').value;
                    if (!productId || productId <= 0) {
                        showMessage('ID produit invalide', 'error');
                        return;
                    }
                    
                    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
                    if (!csrfToken) {
                        showMessage('Erreur de sécurité : token CSRF manquant', 'error');
                        return;
                    }
                    
                    // Désactiver le bouton et afficher le chargement
                    submitBtn.disabled = true;
                    submitText.textContent = 'Ajout en cours...';
                    
                    const formData = new FormData(form);
                    
                    // ===== REQUÊTE AJAX AVEC TIMEOUT =====
                    const controller = new AbortController();
                    const timeoutId = setTimeout(() => controller.abort(), 10000); // Timeout de 10 secondes
                    
                    fetch(form.action, {
                        method: 'POST',
                        body: formData,
                        signal: controller.signal,
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json'
                        }
                    })
                    .then(async response => {
                        clearTimeout(timeoutId);
                        
                        // Gérer les différents codes de statut
                        if (response.status === 419) {
                            throw new Error('Session expirée. Veuillez rafraîchir la page.');
                        }
                        
                        if (response.status === 422) {
                            const errorData = await response.json().catch(() => ({}));
                            throw new Error(errorData.message || 'Erreur de validation');
                        }
                        
                        if (response.status >= 500) {
                            throw new Error('Erreur serveur. Veuillez réessayer plus tard.');
                        }
                        
                        // Si redirection, c'est une requête classique (non-AJAX)
                        if (response.redirected) {
                            // Recharger la page pour afficher le message flash
                            window.location.reload();
                            return;
                        }
                        
                        // Parser la réponse JSON
                        const data = await response.json();
                        return data;
                    })
                    .then(data => {
                        if (data && data.success) {
                            // Succès : afficher message et mettre à jour le compteur
                            showMessage(data.message || 'Produit ajouté au panier !', 'success');
                            
                            // Mettre à jour le compteur avec la valeur retournée par le serveur
                            if (data.total_items !== undefined) {
                                updateCartBadge(data.total_items);
                            } else {
                                // Fallback : appeler l'API pour récupérer le compteur
                                updateCartCount();
                            }
                            
                            // Réinitialiser le formulaire
                            form.reset();
                            qtyInput.value = 1;
                        } else if (data && data.message) {
                            // Erreur avec message
                            showMessage(data.message, 'error');
                        }
                    })
                    .catch(error => {
                        clearTimeout(timeoutId);
                        console.error('Erreur ajout au panier:', error);
                        
                        // Messages d'erreur spécifiques
                        let errorMessage = 'Erreur lors de l\'ajout au panier';
                        
                        if (error.name === 'AbortError') {
                            errorMessage = 'Délai d\'attente dépassé. Vérifiez votre connexion internet.';
                        } else if (error.message) {
                            errorMessage = error.message;
                        } else if (error instanceof TypeError && error.message.includes('fetch')) {
                            errorMessage = 'Erreur de connexion. Vérifiez votre connexion internet.';
                        }
                        
                        showMessage(errorMessage, 'error');
                    })
                    .finally(() => {
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = originalHTML;
                    });
                });
                
                // Fonction pour mettre à jour le badge du panier directement
                if (typeof updateCartBadge === 'undefined') {
                    function updateCartBadge(count) {
                        const cartBadge = document.getElementById('cartBadge') || document.querySelector('.cart-badge');
                        if (cartBadge) {
                            const countNum = parseInt(count) || 0;
                            if (countNum > 0) {
                                cartBadge.textContent = countNum;
                                cartBadge.style.display = 'inline-block';
                                cartBadge.style.transform = 'scale(1.2)';
                                cartBadge.style.transition = 'transform 0.2s ease';
                                setTimeout(() => {
                                    cartBadge.style.transform = 'scale(1)';
                                }, 200);
                            } else {
                                cartBadge.style.display = 'none';
                            }
                        }
                    }
                }
                
                // Fonction pour mettre à jour le compteur via API
                if (typeof updateCartCount === 'undefined') {
                    function updateCartCount() {
                        const controller = new AbortController();
                        const timeoutId = setTimeout(() => controller.abort(), 5000);
                        
                        fetch('{{ route("cart.count") }}', {
                            signal: controller.signal,
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'application/json'
                            }
                        })
                        .then(response => {
                            clearTimeout(timeoutId);
                            if (!response.ok) {
                                throw new Error('Erreur HTTP ' + response.status);
                            }
                            return response.json();
                        })
                        .then(data => {
                            updateCartBadge(data.count);
                        })
                        .catch(error => {
                            clearTimeout(timeoutId);
                            console.warn('Erreur mise à jour compteur:', error);
                        });
                    }
                }
                
                if (typeof showMessage === 'undefined') {
                    function showMessage(message, type = 'success') {
                        let notification = document.getElementById('cart-notification');
                        if (!notification) {
                            notification = document.createElement('div');
                            notification.id = 'cart-notification';
                            notification.style.cssText = 'position: fixed; top: 20px; right: 20px; padding: 1rem 1.5rem; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.15); z-index: 10000; font-weight: 500; transition: all 0.3s;';
                            document.body.appendChild(notification);
                        }
                        notification.style.background = type === 'success' ? '#05944F' : '#DC2626';
                        notification.style.color = 'white';
                        notification.textContent = message;
                        notification.style.display = 'block';
                        notification.style.opacity = '0';
                        setTimeout(() => {
                            notification.style.opacity = '1';
                        }, 10);
                        setTimeout(() => {
                            notification.style.opacity = '0';
                            setTimeout(() => {
                                notification.style.display = 'none';
                            }, 300);
                        }, 3000);
                    }
                }
                </script>
            </div>
        </div>
        
        <!-- Related Products -->
        @if($products && count($products) > 0)
        <div class="related-section">
            <div class="section-header">
                <h2 class="section-title">Découvrez aussi</h2>
                <a href="{{ route('restaurant.detail', $restaurant->id) }}" class="see-all-link">
                    Voir tout le menu
                </a>
            </div>
            
            <div class="products-grid">
                @foreach($products->take(4) as $product)
                <a href="{{ route('frontend.product.show', ['id' => $product->id, 'slug' => \Illuminate\Support\Str::slug($product->name)]) }}" class="product-card">
                    <img src="{{ $product->image ? (strpos($product->image, 'http') === 0 ? $product->image : asset('images/product_images/' . $product->image)) : asset('images/placeholder.png') }}" 
                         alt="{{ $product->name }}" 
                         class="product-card-image"
                         onerror="this.src='{{ asset('images/placeholder.png') }}'">
                    <div class="product-card-content">
                        <h3 class="product-card-name">{{ $product->name }}</h3>
                        <span class="product-card-price">{{ number_format($product->price, 0, ',', ' ') }} FCFA</span>
                    </div>
                </a>
                @endforeach
            </div>
        </div>
        @endif
    </div>
</section>
@endsection

@section('scripts')
<script>
    function changeQty(delta) {
        const input = document.getElementById('qty');
        let value = parseInt(input.value) + delta;
        if (value < 1) value = 1;
        if (value > 20) value = 20;
        input.value = value;
    }
    
    // Auto-hide alerts
    setTimeout(() => {
        document.querySelectorAll('.alert-modern').forEach(alert => {
            alert.style.opacity = '0';
            alert.style.transform = 'translateY(-10px)';
            alert.style.transition = 'all 0.3s ease';
            setTimeout(() => alert.remove(), 300);
        });
    }, 5000);
</script>
@endsection
