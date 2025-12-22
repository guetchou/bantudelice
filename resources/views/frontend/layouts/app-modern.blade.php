<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>@yield('title', 'BantuDelice - Livraison à domicile')</title>
    <meta name="description" content="@yield('description', 'BantuDelice - Votre service de livraison à domicile. Restaurants, courses, fleurs et plus encore.')">
    <meta name="keywords" content="livraison, restaurant, courses, fleurs, Congo, Brazzaville">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Modern CSS -->
    <link href="{{ asset('frontend/css/modern.css') }}" rel="stylesheet">
    
    @yield('styles')
    @yield('style')
    
    <!-- Styles pour le menu déroulant -->
    <style>
        /* Dropdown Menu - Cacher par défaut (spécificité élevée) */
        .modern-header .nav-dropdown {
            position: relative;
        }
        
        .modern-header .nav-dropdown-menu {
            position: absolute !important;
            top: 100% !important;
            left: 0 !important;
            min-width: 200px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            padding: 8px 0;
            margin-top: 8px;
            opacity: 0 !important;
            visibility: hidden !important;
            transform: translateY(-10px);
            transition: all 0.3s ease;
            z-index: 1000;
            display: flex;
            flex-direction: column;
            pointer-events: none;
        }
        
        .modern-header .nav-dropdown-menu a {
            padding: 10px 16px;
            color: #191919;
            text-decoration: none;
            font-size: 0.9375rem;
            transition: background 0.2s ease;
            white-space: nowrap;
            display: block;
        }
        
        .modern-header .nav-dropdown-menu a:hover {
            background: #F6F6F6;
            color: #05944F;
        }
        
        /* Afficher le menu au hover */
        .modern-header .nav-dropdown:hover .nav-dropdown-menu {
            opacity: 1 !important;
            visibility: visible !important;
            transform: translateY(0);
            pointer-events: auto;
        }
        
        /* Mobile : menu toujours visible si actif */
        @media (max-width: 767px) {
            .modern-header .nav-dropdown-menu {
                position: static !important;
                opacity: 1 !important;
                visibility: visible !important;
                transform: none !important;
                box-shadow: none;
                background: transparent;
                padding: 0;
                margin: 0;
                margin-left: 1rem;
                pointer-events: auto;
            }
            
            .modern-header .nav-dropdown-menu a {
                padding: 8px 0;
                font-size: 0.875rem;
            }
        }
    </style>
</head>
<body>
    <!-- Header Moderne -->
    <header class="modern-header" id="header">
        <div class="header-container">
            <!-- Logo -->
            <a href="{{ route('home') }}" class="logo">
                <img src="{{ asset('frontend/images/BuntuDelice.png') }}" alt="BantuDelice" style="height: 50px;">
            </a>
            
            <!-- Navigation -->
            <nav class="nav-menu" id="navMenu">
                <a href="{{ route('home') }}" class="nav-link">Accueil</a>
                <div class="nav-dropdown">
                    <a href="#" class="nav-link">
                        Menu <i class="fas fa-chevron-down" style="font-size: 0.7rem; margin-left: 4px;"></i>
                    </a>
                    <div class="nav-dropdown-menu">
                        @php $cuisines = DB::table('cuisines')->limit(8)->get(); @endphp
                        @foreach($cuisines as $cuisine)
                            <a href="{{ route('restaurant.cuisine', $cuisine->id) }}">{{ $cuisine->name }}</a>
                        @endforeach
                    </div>
                </div>
                <a href="#services" class="nav-link">Services</a>
                <div class="nav-dropdown">
                    <a href="{{ route('colis.landing') }}" class="nav-link">
                        Colis <i class="fas fa-chevron-down" style="font-size: 0.7rem; margin-left: 4px;"></i>
                    </a>
                    <div class="nav-dropdown-menu">
                        <a href="{{ route('colis.landing') }}">Présentation</a>
                        <a href="{{ route('colis.track_public') }}">Suivi colis</a>
                        @if(Auth::check())
                            <a href="{{ route('colis.mes-envois') }}">Mes envois</a>
                            <a href="{{ route('colis.create') }}">Nouvel envoi</a>
                        @endif
                    </div>
                </div>
                <a href="{{ route('about.us') }}" class="nav-link">À propos</a>
                <a href="{{ route('contact.us') }}" class="nav-link">Contact</a>
            </nav>
            
            <!-- Actions -->
            <div class="nav-actions">
                @php
                    $count_items = 0;
                    if(Auth::check() && auth()->user()) {
                        // Utilisateur connecté : lire depuis la base de données
                        $count_items = DB::table('carts')->where('user_id', auth()->user()->id)->sum('qty');
                    } else {
                        // Invité : lire depuis la session
                        $cart = session()->get('cart', []);
                        if (is_array($cart)) {
                            foreach($cart as $item) {
                                $count_items += ($item['qty'] ?? 0);
                            }
                        }
                    }
                @endphp
                
                <a href="{{ route('cart.detail') }}" class="cart-icon">
                    <i class="fas fa-shopping-bag"></i>
                    @if($count_items > 0)
                        <span class="cart-badge" id="cartBadge">{{ $count_items }}</span>
                    @else
                        <span class="cart-badge" id="cartBadge" style="display: none;">0</span>
                    @endif
                </a>
                
                @if(Auth::check())
                    <a href="{{ route('user.profile') }}" class="btn btn-secondary btn-sm">
                        <i class="fas fa-user"></i> Profil
                    </a>
                    <a href="{{ route('user.logout') }}" class="btn btn-primary btn-sm">
                        <i class="fas fa-sign-out-alt"></i> Déconnexion
                    </a>
                @else
                    <a href="{{ route('user.login') }}" class="btn btn-secondary btn-sm">Connexion</a>
                    <a href="{{ route('user.signup') }}" class="btn btn-primary btn-sm">Inscription</a>
                @endif
                
                <!-- Mobile Menu Toggle -->
                <button class="menu-toggle" id="menuToggle" aria-label="Menu">
                    <span></span>
                    <span></span>
                    <span></span>
                </button>
            </div>
        </div>
    </header>
    
    <!-- Messages Flash (Notifications) -->
    @if(session('message'))
        <div id="flash-message" class="flash-message flash-success" style="position: fixed; top: 20px; right: 20px; padding: 1rem 1.5rem; background: #05944F; color: white; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.15); z-index: 10000; font-weight: 500; animation: slideIn 0.3s ease-out;">
            <i class="fas fa-check-circle"></i> {{ session('message') }}
        </div>
        <script>
            // Masquer le message après 3 secondes
            setTimeout(function() {
                const msg = document.getElementById('flash-message');
                if (msg) {
                    msg.style.animation = 'slideOut 0.3s ease-out';
                    setTimeout(() => msg.remove(), 300);
                }
            }, 3000);
        </script>
        <style>
            @keyframes slideIn {
                from {
                    transform: translateX(100%);
                    opacity: 0;
                }
                to {
                    transform: translateX(0);
                    opacity: 1;
                }
            }
            @keyframes slideOut {
                from {
                    transform: translateX(0);
                    opacity: 1;
                }
                to {
                    transform: translateX(100%);
                    opacity: 0;
                }
            }
        </style>
    @endif
    
    @if(session('error'))
        <div id="flash-error" class="flash-message flash-error" style="position: fixed; top: 20px; right: 20px; padding: 1rem 1.5rem; background: #DC2626; color: white; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.15); z-index: 10000; font-weight: 500; animation: slideIn 0.3s ease-out;">
            <i class="fas fa-exclamation-circle"></i> {{ session('error') }}
        </div>
        <script>
            setTimeout(function() {
                const msg = document.getElementById('flash-error');
                if (msg) {
                    msg.style.animation = 'slideOut 0.3s ease-out';
                    setTimeout(() => msg.remove(), 300);
                }
            }, 3000);
        </script>
    @endif
    
    <!-- Contenu Principal -->
    <main>
        @yield('content')
    </main>
    
    <!-- Footer Moderne -->
    <footer class="modern-footer">
        <div class="container">
            <div class="footer-grid">
                <!-- Brand -->
                <div class="footer-brand">
                    <div class="footer-logo">
                        <img src="{{ asset('frontend/images/BuntuDelice.png') }}" alt="BantuDelice" style="height: 45px; margin-bottom: 1rem;">
                    </div>
                    <p>Votre partenaire de confiance pour la livraison à domicile. Restaurants, courses, fleurs et bien plus encore, livrés directement chez vous.</p>
                    <div class="footer-social">
                        <a href="#" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
                        <a href="#" aria-label="Twitter"><i class="fab fa-twitter"></i></a>
                        <a href="#" aria-label="WhatsApp"><i class="fab fa-whatsapp"></i></a>
                    </div>
                </div>
                
                <!-- Liens Rapides -->
                <div class="footer-column">
                    <h4>Liens Rapides</h4>
                    <div class="footer-links">
                        <a href="{{ route('colis.landing') }}" style="color: #ff0000; font-weight: bold;">Livraison Colis</a>
                        <a href="{{ route('colis.track_public') }}">Suivre un colis</a>
                        <a href="{{ route('about.us') }}">À propos de nous</a>
                        <a href="{{ route('driver') }}">Devenir livreur</a>
                        <a href="{{ route('partner') }}">Devenir partenaire</a>
                        <a href="{{ route('contact.us') }}">Nous contacter</a>
                    </div>
                </div>
                
                <!-- Informations -->
                <div class="footer-column">
                    <h4>Informations</h4>
                    <div class="footer-links">
                        <a href="{{ route('terms.conditions') }}">Conditions générales</a>
                        <a href="{{ route('privacy.policy') }}">Politique de confidentialité</a>
                        <a href="{{ route('refund.policy') }}">Politique de remboursement</a>
                        <a href="{{ route('faq') }}">FAQ</a>
                        <a href="{{ route('data.deletion') }}">Suppression des données</a>
                    </div>
                </div>
                
                <!-- Applications -->
                <div class="footer-column">
                    <h4>Téléchargez l'App</h4>
                    <p style="color: rgba(255,255,255,0.6); font-size: 0.875rem; margin-bottom: 1rem;">
                        Commandez encore plus facilement avec notre application mobile.
                    </p>
                    <div class="footer-app-buttons">
                        <a href="#">
                            <img src="{{ asset('images/playstore.png') }}" alt="Google Play">
                        </a>
                        <a href="#">
                            <img src="{{ asset('images/applestore.png') }}" alt="App Store">
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Bottom -->
            <div class="footer-bottom">
                <p class="footer-copyright">
                    &copy; {{ date('Y') }} BantuDelice. Tous droits réservés.
                </p>
                <div class="footer-bottom-links">
                    <a href="#">Mentions légales</a>
                    <a href="#">Cookies</a>
                    <a href="#">Plan du site</a>
                </div>
            </div>
        </div>
    </footer>
    
    <!-- Back to Top -->
    <a href="#" id="backToTop" style="
        position: fixed;
        bottom: 30px;
        right: 30px;
        width: 50px;
        height: 50px;
        background: var(--primary);
        color: white;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        opacity: 0;
        visibility: hidden;
        transition: all 0.3s ease;
        z-index: 999;
        box-shadow: 0 4px 15px rgba(255, 107, 53, 0.4);
    ">
        <i class="fas fa-arrow-up"></i>
    </a>
    
    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Header scroll effect
        const header = document.getElementById('header');
        window.addEventListener('scroll', () => {
            if (window.scrollY > 50) {
                header.classList.add('scrolled');
            } else {
                header.classList.remove('scrolled');
            }
        });
        
        // Mobile menu toggle
        const menuToggle = document.getElementById('menuToggle');
        const navMenu = document.getElementById('navMenu');
        
        menuToggle?.addEventListener('click', () => {
            navMenu.classList.toggle('active');
        });
        
        // Back to top button
        const backToTop = document.getElementById('backToTop');
        window.addEventListener('scroll', () => {
            if (window.scrollY > 300) {
                backToTop.style.opacity = '1';
                backToTop.style.visibility = 'visible';
            } else {
                backToTop.style.opacity = '0';
                backToTop.style.visibility = 'hidden';
            }
        });
        
        backToTop?.addEventListener('click', (e) => {
            e.preventDefault();
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
        
        // Smooth scroll for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                const href = this.getAttribute('href');
                if (href !== '#') {
                    e.preventDefault();
                    const target = document.querySelector(href);
                    if (target) {
                        target.scrollIntoView({ behavior: 'smooth' });
                    }
                }
            });
        });
    </script>
    
    <!-- Toast Container -->
    <div id="toastContainer" style="position: fixed; top: 20px; right: 20px; z-index: 10000; display: flex; flex-direction: column; gap: 0.75rem; pointer-events: none;"></div>
    
    <script>
        // Fonction globale pour afficher un toast (utilisable partout)
        function showToast(message, type = 'success') {
            const container = document.getElementById('toastContainer');
            if (!container) {
                // Créer le container s'il n'existe pas
                const newContainer = document.createElement('div');
                newContainer.id = 'toastContainer';
                newContainer.style.cssText = 'position: fixed; top: 20px; right: 20px; z-index: 10000; display: flex; flex-direction: column; gap: 0.75rem; pointer-events: none;';
                document.body.appendChild(newContainer);
                return showToast(message, type);
            }
            
            const toast = document.createElement('div');
            toast.style.cssText = `
                background: ${type === 'success' ? '#05944F' : '#EF4444'};
                color: white;
                padding: 1rem 1.5rem;
                border-radius: 12px;
                box-shadow: 0 8px 24px rgba(0,0,0,0.15);
                font-weight: 500;
                display: flex;
                align-items: center;
                gap: 0.75rem;
                min-width: 300px;
                max-width: 400px;
                animation: slideInRight 0.3s ease-out;
                pointer-events: auto;
            `;
            
            const icon = type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle';
            toast.innerHTML = `
                <i class="fas ${icon}" style="font-size: 1.25rem;"></i>
                <span>${message}</span>
            `;
            
            container.appendChild(toast);
            
            setTimeout(() => {
                toast.style.animation = 'slideOutRight 0.3s ease-out';
                setTimeout(() => toast.remove(), 300);
            }, 3000);
        }
        
        // Alias pour compatibilité avec l'ancien code
        function showMessage(message, type = 'success') {
            showToast(message, type);
        }
        
        // Ajouter les styles d'animation si pas déjà présents
        if (!document.getElementById('toast-animations-style')) {
            const style = document.createElement('style');
            style.id = 'toast-animations-style';
            style.textContent = `
                @keyframes slideInRight {
                    from {
                        transform: translateX(100%);
                        opacity: 0;
                    }
                    to {
                        transform: translateX(0);
                        opacity: 1;
                    }
                }
                @keyframes slideOutRight {
                    from {
                        transform: translateX(0);
                        opacity: 1;
                    }
                    to {
                        transform: translateX(100%);
                        opacity: 0;
                    }
                }
            `;
            document.head.appendChild(style);
        }
        
        // Mettre à jour le badge du panier au chargement
        document.addEventListener('DOMContentLoaded', function() {
            updateCartCount();
        });
        
        // Fonction pour mettre à jour le compteur du panier
        function updateCartCount() {
            fetch('{{ route("cart.count") }}', {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                const badge = document.getElementById('cartBadge');
                if (badge) {
                    const count = data.count || 0;
                    badge.textContent = count;
                    badge.style.display = count > 0 ? 'inline-block' : 'none';
                }
                
                // Mettre à jour aussi le badge flottant si présent
                const floatingBadge = document.getElementById('floatingCartBadge');
                if (floatingBadge) {
                    floatingBadge.textContent = count;
                    floatingBadge.style.display = count > 0 ? 'inline-block' : 'none';
                }
            })
            .catch(error => console.error('Error updating cart count:', error));
        }
    </script>
    
    @yield('scripts')
</body>
</html>

