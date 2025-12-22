@extends('frontend.layouts.app-modern')
@section('title', $proDetail->name . ' | BantuDelice')
@section('description', 'Commandez ' . $proDetail->name . ' chez ' . $restaurant->name . ' - Livraison rapide à domicile')

@section('styles')
<style>
    .product-page {
        padding: 120px 0 60px;
        background: linear-gradient(135deg, #FAFAFA 0%, #FFFFFF 100%);
        min-height: calc(100vh - 80px);
    }
    
    /* Breadcrumb */
    .breadcrumb-modern {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-size: 0.9rem;
        margin-bottom: 2rem;
        flex-wrap: wrap;
    }
    
    .breadcrumb-modern a {
        color: var(--gray-500);
        text-decoration: none;
        transition: color 0.3s ease;
        display: flex;
        align-items: center;
        gap: 0.25rem;
    }
    
    .breadcrumb-modern a:hover {
        color: var(--primary);
    }
    
    .breadcrumb-modern .separator {
        color: var(--gray-300);
    }
    
    .breadcrumb-modern .current {
        color: var(--gray-900);
        font-weight: 600;
    }
    
    /* Product Detail Card */
    .product-detail-card {
        background: white;
        border-radius: 24px;
        overflow: hidden;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.08);
        display: grid;
        grid-template-columns: 1fr 1.2fr;
        gap: 0;
    }
    
    .product-image-section {
        position: relative;
        background: var(--gray-100);
        min-height: 400px;
    }
    
    .product-image {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    
    .product-badge {
        position: absolute;
        top: 1rem;
        left: 1rem;
        background: var(--primary);
        color: white;
        padding: 0.5rem 1rem;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: 600;
    }
    
    .product-info-section {
        padding: 2.5rem;
        display: flex;
        flex-direction: column;
    }
    
    .restaurant-tag {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        background: var(--gray-100);
        padding: 0.5rem 1rem;
        border-radius: 20px;
        font-size: 0.85rem;
        color: var(--gray-600);
        margin-bottom: 1rem;
        width: fit-content;
    }
    
    .restaurant-tag i {
        color: var(--primary);
    }
    
    .product-title {
        font-size: 2rem;
        font-weight: 800;
        color: var(--gray-900);
        margin-bottom: 1rem;
        line-height: 1.2;
    }
    
    .product-price-section {
        display: flex;
        align-items: baseline;
        gap: 1rem;
        margin-bottom: 1.5rem;
    }
    
    .current-price {
        font-size: 2rem;
        font-weight: 700;
        color: var(--primary);
    }
    
    .original-price {
        font-size: 1.25rem;
        color: var(--gray-400);
        text-decoration: line-through;
    }
    
    .discount-badge {
        background: #FEE2E2;
        color: #DC2626;
        padding: 0.25rem 0.75rem;
        border-radius: 12px;
        font-size: 0.8rem;
        font-weight: 600;
    }
    
    .product-description {
        color: var(--gray-600);
        line-height: 1.7;
        margin-bottom: 2rem;
        font-size: 1rem;
    }
    
    /* Form Styling */
    .order-form {
        margin-top: auto;
    }
    
    .form-group {
        margin-bottom: 1.5rem;
    }
    
    .form-label {
        display: block;
        font-size: 0.9rem;
        font-weight: 600;
        color: var(--gray-700);
        margin-bottom: 0.5rem;
    }
    
    .quantity-selector {
        display: flex;
        align-items: center;
        gap: 0;
        border: 2px solid var(--gray-200);
        border-radius: 12px;
        width: fit-content;
        overflow: hidden;
    }
    
    .qty-btn {
        width: 48px;
        height: 48px;
        border: none;
        background: var(--gray-100);
        color: var(--gray-700);
        font-size: 1.25rem;
        cursor: pointer;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .qty-btn:hover {
        background: var(--primary);
        color: white;
    }
    
    .qty-input {
        width: 60px;
        height: 48px;
        border: none;
        text-align: center;
        font-size: 1.1rem;
        font-weight: 600;
        color: var(--gray-900);
        background: white;
    }
    
    .qty-input:focus {
        outline: none;
    }
    
    .instructions-input {
        width: 100%;
        padding: 1rem;
        border: 2px solid var(--gray-200);
        border-radius: 12px;
        font-family: inherit;
        font-size: 1rem;
        color: var(--gray-900);
        resize: vertical;
        min-height: 100px;
        transition: all 0.3s ease;
    }
    
    .instructions-input:focus {
        outline: none;
        border-color: var(--primary);
        box-shadow: 0 0 0 4px rgba(232, 90, 36, 0.1);
    }
    
    .instructions-input::placeholder {
        color: var(--gray-400);
    }
    
    .btn-add-cart {
        width: 100%;
        padding: 1rem 2rem;
        background: linear-gradient(135deg, var(--primary), #FF7A44);
        border: none;
        border-radius: 12px;
        color: white;
        font-family: inherit;
        font-size: 1.1rem;
        font-weight: 600;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.75rem;
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
    }
    
    .btn-add-cart::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
        transition: left 0.5s ease;
    }
    
    .btn-add-cart:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 30px rgba(232, 90, 36, 0.35);
    }
    
    .btn-add-cart:hover::before {
        left: 100%;
    }
    
    /* Related Products Section */
    .related-section {
        margin-top: 4rem;
    }
    
    .section-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 2rem;
    }
    
    .section-title {
        font-size: 1.75rem;
        font-weight: 700;
        color: var(--gray-900);
    }
    
    .see-all-link {
        color: var(--primary);
        text-decoration: none;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        transition: gap 0.3s ease;
    }
    
    .see-all-link:hover {
        gap: 0.75rem;
    }
    
    .products-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 1.5rem;
    }
    
    .product-card {
        background: white;
        border-radius: 16px;
        overflow: hidden;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.06);
        transition: all 0.3s ease;
        text-decoration: none;
    }
    
    .product-card:hover {
        transform: translateY(-8px);
        box-shadow: 0 12px 40px rgba(0, 0, 0, 0.12);
    }
    
    .product-card-image {
        width: 100%;
        height: 160px;
        object-fit: cover;
    }
    
    .product-card-content {
        padding: 1rem;
    }
    
    .product-card-name {
        font-size: 1rem;
        font-weight: 600;
        color: var(--gray-900);
        margin-bottom: 0.5rem;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
    
    .product-card-price {
        font-size: 1.1rem;
        font-weight: 700;
        color: var(--primary);
    }
    
    /* Alert Styles */
    .alert-modern {
        padding: 1rem 1.5rem;
        border-radius: 12px;
        margin-bottom: 1.5rem;
        display: flex;
        align-items: center;
        gap: 0.75rem;
        animation: slideIn 0.3s ease;
    }
    
    @keyframes slideIn {
        from { opacity: 0; transform: translateY(-10px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    .alert-success {
        background: #D1FAE5;
        color: #065F46;
        border: 1px solid #A7F3D0;
    }
    
    .alert-success i {
        color: #10B981;
        font-size: 1.25rem;
    }
    
    /* Responsive */
    @media (max-width: 992px) {
        .product-detail-card {
            grid-template-columns: 1fr;
        }
        
        .product-image-section {
            min-height: 300px;
        }
        
        .products-grid {
            grid-template-columns: repeat(2, 1fr);
        }
    }
    
    @media (max-width: 576px) {
        .product-page {
            padding: 100px 0 40px;
        }
        
        .product-info-section {
            padding: 1.5rem;
        }
        
        .product-title {
            font-size: 1.5rem;
        }
        
        .current-price {
            font-size: 1.5rem;
        }
        
        .products-grid {
            grid-template-columns: 1fr;
        }
    }
</style>
@endsection

@section('content')
<section class="product-page">
    <div class="container">
        <!-- Breadcrumb -->
        <nav class="breadcrumb-modern">
            <a href="{{ route('home') }}">
                <i class="fas fa-home"></i> Accueil
            </a>
            <span class="separator">/</span>
            <a href="{{ route('resturant.detail', $restaurant->id) }}">
                {{ $restaurant->name }}
            </a>
            <span class="separator">/</span>
            <span class="current">{{ $proDetail->name }}</span>
        </nav>
        
        @if(Session::has('message'))
            <div class="alert-modern alert-success">
                <i class="fas fa-check-circle"></i>
                <span>{{ Session::get('message') }}</span>
            </div>
        @endif
        
        <!-- Product Detail Card -->
        <div class="product-detail-card">
            <div class="product-image-section">
                @if($proDetail->discount_price && $proDetail->discount_price < $proDetail->price)
                    <span class="product-badge">
                        -{{ round((($proDetail->price - $proDetail->discount_price) / $proDetail->price) * 100) }}%
                    </span>
                @endif
                <img src="{{ $proDetail->image ? (strpos($proDetail->image, 'http') === 0 ? $proDetail->image : asset('images/product_images/' . $proDetail->image)) : asset('images/placeholder.png') }}" 
                     alt="{{ $proDetail->name }}" 
                     class="product-image"
                     onerror="this.src='{{ asset('images/placeholder.png') }}'">
            </div>
            
            <div class="product-info-section">
                <a href="{{ route('resturant.detail', $restaurant->id) }}" class="restaurant-tag">
                    <i class="fas fa-store"></i>
                    {{ $restaurant->name }}
                </a>
                
                <h1 class="product-title">{{ $proDetail->name }}</h1>
                
                <div class="product-price-section">
                    <span class="current-price">{{ number_format($proDetail->price, 0, ',', ' ') }} FCFA</span>
                    @if($proDetail->discount_price && $proDetail->discount_price < $proDetail->price)
                        <span class="original-price">{{ number_format($proDetail->discount_price, 0, ',', ' ') }} FCFA</span>
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
                            <button type="button" class="qty-btn" onclick="changeQty(-1)" {{ (isset($proDetail->is_available) && !$proDetail->is_available) ? 'disabled' : '' }}>
                                <i class="fas fa-minus"></i>
                            </button>
                            <input type="number" name="qty" id="qty" value="1" min="1" max="20" class="qty-input" readonly {{ (isset($proDetail->is_available) && !$proDetail->is_available) ? 'disabled' : '' }}>
                            <button type="button" class="qty-btn" onclick="changeQty(1)" {{ (isset($proDetail->is_available) && !$proDetail->is_available) ? 'disabled' : '' }}>
                                <i class="fas fa-plus"></i>
                            </button>
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
                        <button type="button" class="btn-add-cart" id="submitCartBtn" disabled style="opacity:0.7; cursor:not-allowed;">
                            <i class="fas fa-ban"></i>
                            <span id="submitCartText">Indisponible</span>
                        </button>
                        <div style="margin-top: 10px; color: #b45309; font-weight: 600;">
                            Ce plat est temporairement indisponible.
                        </div>
                    @else
                    <button type="submit" class="btn-add-cart" id="submitCartBtn">
                        <i class="fas fa-shopping-cart"></i>
                        <span id="submitCartText">Ajouter au panier</span>
                    </button>
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
                <a href="{{ route('resturant.detail', $restaurant->id) }}" class="see-all-link">
                    Voir tout le menu <i class="fas fa-arrow-right"></i>
                </a>
            </div>
            
            <div class="products-grid">
                @foreach($products->take(4) as $product)
                <a href="{{ route('pro.detail', $product->id) }}" class="product-card">
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
