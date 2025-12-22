@extends('frontend.layouts.app-modern')
@section('title', 'Connexion | BantuDelice')
@section('description', 'Connectez-vous à votre compte BantuDelice pour commander vos plats préférés.')

@section('styles')
<style>
    .login-page {
        min-height: calc(100vh - 80px);
        display: flex;
        align-items: center;
        padding: 120px 0 60px;
        background: linear-gradient(135deg, #FFF5F0 0%, #FFFFFF 50%, #FFF9F5 100%);
        position: relative;
        overflow: hidden;
    }
    
    .login-page::before {
        content: '';
        position: absolute;
        top: -50%;
        right: -30%;
        width: 80%;
        height: 150%;
        background: radial-gradient(ellipse, rgba(232, 90, 36, 0.08) 0%, transparent 60%);
        pointer-events: none;
    }
    
    .login-page::after {
        content: '';
        position: absolute;
        bottom: -30%;
        left: -20%;
        width: 60%;
        height: 100%;
        background: radial-gradient(ellipse, rgba(232, 90, 36, 0.05) 0%, transparent 50%);
        pointer-events: none;
    }
    
    .login-container {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 4rem;
        max-width: 1100px;
        margin: 0 auto;
        padding: 0 1.5rem;
        position: relative;
        z-index: 1;
    }
    
    /* Left Side - Benefits */
    .login-benefits {
        display: flex;
        flex-direction: column;
        justify-content: center;
    }
    
    .benefits-header h1 {
        font-size: 2.5rem;
        font-weight: 800;
        color: var(--gray-900);
        line-height: 1.2;
        margin-bottom: 1rem;
    }
    
    .benefits-header h1 span {
        color: var(--primary);
        position: relative;
    }
    
    .benefits-header h1 span::after {
        content: '';
        position: absolute;
        bottom: 5px;
        left: 0;
        width: 100%;
        height: 8px;
        background: rgba(232, 90, 36, 0.2);
        z-index: -1;
        border-radius: 4px;
    }
    
    .benefits-header p {
        font-size: 1.125rem;
        color: var(--gray-600);
        line-height: 1.7;
        margin-bottom: 2.5rem;
    }
    
    .benefits-list {
        display: flex;
        flex-direction: column;
        gap: 1.25rem;
    }
    
    .benefit-item {
        display: flex;
        align-items: center;
        gap: 1rem;
        padding: 1rem 1.25rem;
        background: white;
        border-radius: 16px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.04);
        transition: all 0.3s ease;
    }
    
    .benefit-item:hover {
        transform: translateX(8px);
        box-shadow: 0 8px 30px rgba(232, 90, 36, 0.12);
    }
    
    .benefit-icon {
        width: 50px;
        height: 50px;
        background: linear-gradient(135deg, var(--primary), #FF7A44);
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 1.25rem;
        flex-shrink: 0;
    }
    
    .benefit-content h4 {
        font-size: 1rem;
        font-weight: 700;
        color: var(--gray-900);
        margin-bottom: 0.25rem;
    }
    
    .benefit-content p {
        font-size: 0.875rem;
        color: var(--gray-500);
        margin: 0;
    }
    
    /* Right Side - Form */
    .login-form-wrapper {
        display: flex;
        flex-direction: column;
        justify-content: center;
    }
    
    .login-card {
        background: white;
        border-radius: 24px;
        padding: 2.5rem;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.08);
        position: relative;
        overflow: hidden;
    }
    
    .login-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: linear-gradient(90deg, var(--primary), #FF7A44, #FFB347);
    }
    
    .form-header {
        text-align: center;
        margin-bottom: 2rem;
    }
    
    .form-header h2 {
        font-size: 1.75rem;
        font-weight: 700;
        color: var(--gray-900);
        margin-bottom: 0.5rem;
    }
    
    .form-header p {
        color: var(--gray-500);
        font-size: 0.95rem;
    }
    
    /* Social Login */
    .social-login {
        display: flex;
        gap: 1rem;
        margin-bottom: 1.5rem;
    }
    
    .btn-social {
        flex: 1;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.75rem;
        padding: 0.875rem 1rem;
        border: 2px solid var(--gray-200);
        border-radius: 12px;
        background: white;
        font-family: inherit;
        font-size: 0.9rem;
        font-weight: 600;
        color: var(--gray-700);
        cursor: pointer;
        transition: all 0.3s ease;
    }
    
    .btn-social:hover {
        border-color: var(--gray-300);
        background: var(--gray-50);
        transform: translateY(-2px);
    }
    
    .btn-social.google i { color: #DB4437; }
    .btn-social.facebook i { color: #4267B2; }
    .btn-social.apple i { color: #000; }
    
    .divider {
        display: flex;
        align-items: center;
        gap: 1rem;
        margin-bottom: 1.5rem;
    }
    
    .divider::before,
    .divider::after {
        content: '';
        flex: 1;
        height: 1px;
        background: var(--gray-200);
    }
    
    .divider span {
        color: var(--gray-400);
        font-size: 0.875rem;
        font-weight: 500;
    }
    
    /* Form Fields */
    .form-group {
        margin-bottom: 1.25rem;
    }
    
    .form-label {
        display: block;
        font-size: 0.875rem;
        font-weight: 600;
        color: var(--gray-700);
        margin-bottom: 0.5rem;
    }
    
    .input-group {
        position: relative;
    }
    
    .input-group i {
        position: absolute;
        left: 1rem;
        top: 50%;
        transform: translateY(-50%);
        color: var(--gray-400);
        font-size: 1rem;
        transition: color 0.3s ease;
        pointer-events: none;
    }
    
    .input-group input {
        width: 100%;
        padding: 0.875rem 1rem 0.875rem 2.75rem;
        border: 2px solid var(--gray-200);
        border-radius: 12px;
        font-family: inherit;
        font-size: 1rem;
        color: var(--gray-900);
        transition: all 0.3s ease;
        background: var(--gray-50);
    }
    
    .input-group input:focus {
        outline: none;
        border-color: var(--primary);
        background: white;
        box-shadow: 0 0 0 4px rgba(232, 90, 36, 0.1);
    }
    
    .input-group input:focus + i {
        color: var(--primary);
    }
    
    .input-group input::placeholder {
        color: var(--gray-400);
    }
    
    .password-toggle {
        position: absolute;
        right: 1rem;
        top: 50%;
        transform: translateY(-50%);
        background: none;
        border: none;
        color: var(--gray-400);
        cursor: pointer;
        padding: 0.25rem;
        transition: color 0.3s ease;
        z-index: 2;
    }
    
    .password-toggle:hover {
        color: var(--gray-600);
    }
    
    .form-error {
        color: #DC2626;
        font-size: 0.8rem;
        margin-top: 0.375rem;
        display: flex;
        align-items: center;
        gap: 0.375rem;
    }
    
    .form-error i {
        font-size: 0.75rem;
    }
    
    /* Form Options */
    .form-options {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1.5rem;
    }
    
    .checkbox-wrapper {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        cursor: pointer;
    }
    
    .checkbox-wrapper input[type="checkbox"] {
        display: none;
    }
    
    .checkbox-custom {
        width: 20px;
        height: 20px;
        border: 2px solid var(--gray-300);
        border-radius: 6px;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.3s ease;
    }
    
    .checkbox-wrapper input:checked + .checkbox-custom {
        background: var(--primary);
        border-color: var(--primary);
    }
    
    .checkbox-custom i {
        color: white;
        font-size: 0.625rem;
        opacity: 0;
        transform: scale(0);
        transition: all 0.2s ease;
    }
    
    .checkbox-wrapper input:checked + .checkbox-custom i {
        opacity: 1;
        transform: scale(1);
    }
    
    .checkbox-label {
        font-size: 0.9rem;
        color: var(--gray-600);
    }
    
    .forgot-link {
        font-size: 0.9rem;
        color: var(--primary);
        font-weight: 500;
        text-decoration: none;
        transition: all 0.3s ease;
    }
    
    .forgot-link:hover {
        color: var(--primary-dark);
        text-decoration: underline;
    }
    
    /* Submit Button */
    .btn-submit {
        width: 100%;
        padding: 1rem 1.5rem;
        background: linear-gradient(135deg, var(--primary), #FF7A44);
        border: none;
        border-radius: 12px;
        font-family: inherit;
        font-size: 1rem;
        font-weight: 600;
        color: white;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.75rem;
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
    }
    
    .btn-submit::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.25), transparent);
        transition: left 0.5s ease;
    }
    
    .btn-submit:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 30px rgba(232, 90, 36, 0.35);
    }
    
    .btn-submit:hover::before {
        left: 100%;
    }
    
    .btn-submit:active {
        transform: translateY(0);
    }
    
    .btn-submit.loading {
        pointer-events: none;
        opacity: 0.85;
    }
    
    .btn-submit.loading .btn-text,
    .btn-submit.loading i {
        visibility: hidden;
    }
    
    .btn-submit.loading::after {
        content: '';
        position: absolute;
        width: 24px;
        height: 24px;
        border: 3px solid white;
        border-top-color: transparent;
        border-radius: 50%;
        animation: spin 0.8s linear infinite;
    }
    
    @keyframes spin {
        to { transform: rotate(360deg); }
    }
    
    /* Form Footer */
    .form-footer {
        margin-top: 1.5rem;
        padding-top: 1.5rem;
        border-top: 1px solid var(--gray-200);
        text-align: center;
    }
    
    .form-footer p {
        font-size: 0.95rem;
        color: var(--gray-600);
    }
    
    .form-footer a {
        color: var(--primary);
        font-weight: 600;
        text-decoration: none;
        transition: all 0.3s ease;
    }
    
    .form-footer a:hover {
        color: var(--primary-dark);
        text-decoration: underline;
    }
    
    /* Alert Styles */
    .alert {
        padding: 1rem 1.25rem;
        border-radius: 12px;
        margin-bottom: 1.5rem;
        display: flex;
        align-items: center;
        gap: 0.75rem;
        font-size: 0.9rem;
        animation: slideIn 0.3s ease;
    }
    
    @keyframes slideIn {
        from {
            opacity: 0;
            transform: translateY(-10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    .alert-success {
        background: #D1FAE5;
        color: #065F46;
        border: 1px solid #A7F3D0;
    }
    
    .alert-error {
        background: #FEE2E2;
        color: #991B1B;
        border: 1px solid #FECACA;
    }
    
    .alert i {
        font-size: 1.25rem;
    }
    
    /* Trust Badges */
    .trust-badges {
        display: flex;
        justify-content: center;
        gap: 1.5rem;
        margin-top: 1.5rem;
    }
    
    .trust-badge {
        display: flex;
        align-items: center;
        gap: 0.375rem;
        font-size: 0.75rem;
        color: var(--gray-500);
    }
    
    .trust-badge i {
        color: var(--success);
    }
    
    /* Responsive */
    @media (max-width: 992px) {
        .login-container {
            grid-template-columns: 1fr;
            max-width: 480px;
        }
        
        .login-benefits {
            display: none;
        }
    }
    
    @media (max-width: 576px) {
        .login-page {
            padding: 100px 0 40px;
        }
        
        .login-card {
            padding: 1.5rem;
            border-radius: 20px;
        }
        
        .social-login {
            flex-direction: column;
        }
        
        .form-options {
            flex-direction: column;
            gap: 1rem;
            align-items: flex-start;
        }
    }
</style>
@endsection

@section('content')
<section class="login-page">
    <div class="login-container">
        <!-- Left Side - Benefits -->
        <div class="login-benefits">
            <div class="benefits-header">
                <h1>Commandez vos plats <span>préférés</span></h1>
                <p>Rejoignez des milliers de clients satisfaits et profitez de la livraison de vos repas favoris à domicile.</p>
            </div>
            
            <div class="benefits-list">
                <div class="benefit-item">
                    <div class="benefit-icon">
                        <i class="fas fa-bolt"></i>
                    </div>
                    <div class="benefit-content">
                        <h4>Livraison express</h4>
                        <p>Vos plats livrés en 30 min en moyenne</p>
                    </div>
                </div>
                
                <div class="benefit-item">
                    <div class="benefit-icon">
                        <i class="fas fa-map-marker-alt"></i>
                    </div>
                    <div class="benefit-content">
                        <h4>Suivi en temps réel</h4>
                        <p>Suivez votre commande sur la carte GPS</p>
                    </div>
                </div>
                
                <div class="benefit-item">
                    <div class="benefit-icon">
                        <i class="fas fa-percent"></i>
                    </div>
                    <div class="benefit-content">
                        <h4>Offres exclusives</h4>
                        <p>Profitez de réductions membres</p>
                    </div>
                </div>
                
                <div class="benefit-item">
                    <div class="benefit-icon">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <div class="benefit-content">
                        <h4>Paiement sécurisé</h4>
                        <p>Vos données sont protégées</p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Right Side - Form -->
        <div class="login-form-wrapper">
            <div class="login-card">
                <div class="form-header">
                    <h2>Connexion</h2>
                    <p>Bienvenue ! Connectez-vous pour continuer.</p>
                </div>
                
                <!-- Social Login -->
                <div class="social-login" style="display: flex !important; visibility: visible !important; opacity: 1 !important;">
                    <a class="btn-social google" href="{{ route('auth.social.redirect', ['provider' => 'google']) }}" style="text-decoration:none; display: flex !important; align-items: center; justify-content: center; gap: 10px;">
                        <i class="fab fa-google"></i>
                        Google
                    </a>
                    <a class="btn-social facebook" href="{{ route('auth.social.redirect', ['provider' => 'facebook']) }}" style="text-decoration:none; display: flex !important; align-items: center; justify-content: center; gap: 10px;">
                        <i class="fab fa-facebook-f"></i>
                        Facebook
                    </a>
                </div>
                
                <div class="divider">
                    <span>ou par email</span>
                </div>
                
                <!-- Alert Messages -->
                @if(Session::has('message'))
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i>
                        <span>{{ Session::get('message') }}</span>
                    </div>
                @endif
                
                @if($errors->any())
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-circle"></i>
                        <span>{{ $errors->first() }}</span>
                    </div>
                @endif
                
                <!-- Login Form -->
                <form method="post" action="{{ url('login') }}" id="loginForm">
                    @csrf
                    
                    <div class="form-group">
                        <label class="form-label">Adresse email</label>
                        <div class="input-group">
                            <input type="email" 
                                   name="email" 
                                   id="email"
                                   value="{{ old('email') }}" 
                                   placeholder="votre@email.com"
                                   required
                                   autocomplete="email">
                            <i class="fas fa-envelope"></i>
                        </div>
                        @if($errors->has('email'))
                            <div class="form-error">
                                <i class="fas fa-exclamation-circle"></i>
                                {{ $errors->first('email') }}
                            </div>
                        @endif
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Mot de passe</label>
                        <div class="input-group">
                            <input type="password" 
                                   name="password"
                                   id="password"
                                   placeholder="Votre mot de passe"
                                   required
                                   autocomplete="current-password">
                            <i class="fas fa-lock"></i>
                            <button type="button" class="password-toggle" onclick="togglePassword()">
                                <i class="fas fa-eye" id="toggleIcon"></i>
                            </button>
                        </div>
                        @if($errors->has('password'))
                            <div class="form-error">
                                <i class="fas fa-exclamation-circle"></i>
                                {{ $errors->first('password') }}
                            </div>
                        @endif
                    </div>
                    
                    <div class="form-options">
                        <label class="checkbox-wrapper">
                            <input type="checkbox" name="remember" id="remember">
                            <span class="checkbox-custom"><i class="fas fa-check"></i></span>
                            <span class="checkbox-label">Se souvenir de moi</span>
                        </label>
                        <a href="{{ route('user.forgot') }}" class="forgot-link">
                            Mot de passe oublié ?
                        </a>
                    </div>
                    
                    <button type="submit" class="btn-submit" id="submitBtn">
                        <span class="btn-text">Se connecter</span>
                        <i class="fas fa-arrow-right"></i>
                    </button>
                </form>
                
                <!-- Trust Badges -->
                <div class="trust-badges">
                    <div class="trust-badge">
                        <i class="fas fa-lock"></i>
                        SSL sécurisé
                    </div>
                    <div class="trust-badge">
                        <i class="fas fa-shield-alt"></i>
                        Données protégées
                    </div>
                </div>
                
                <div class="form-footer">
                    <p>Pas encore de compte ? <a href="{{ route('user.signup') }}">Créer un compte</a></p>
                    <p style="margin-top: 0.75rem; font-size: 0.85rem; color: var(--gray-500);">
                        Restaurant ou Livreur ? 
                        <a href="{{ url('/login') }}" style="color: var(--gray-600);">
                            Accès professionnel →
                        </a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection

@section('scripts')
<script>
    // Toggle password visibility
    function togglePassword() {
        const passwordInput = document.getElementById('password');
        const toggleIcon = document.getElementById('toggleIcon');
        
        if (passwordInput.type === 'password') {
            passwordInput.type = 'text';
            toggleIcon.classList.remove('fa-eye');
            toggleIcon.classList.add('fa-eye-slash');
        } else {
            passwordInput.type = 'password';
            toggleIcon.classList.remove('fa-eye-slash');
            toggleIcon.classList.add('fa-eye');
        }
    }
    
    // Form submission with loading state
    document.getElementById('loginForm').addEventListener('submit', function(e) {
        const btn = document.getElementById('submitBtn');
        btn.classList.add('loading');
    });
    
    // Social login: les boutons redirigent directement vers /auth/{provider}
    
    // Auto-hide alerts after 5 seconds
    setTimeout(() => {
        document.querySelectorAll('.alert').forEach(alert => {
            alert.style.opacity = '0';
            alert.style.transform = 'translateY(-10px)';
            alert.style.transition = 'all 0.3s ease';
            setTimeout(() => alert.remove(), 300);
        });
    }, 5000);
    
    // Email validation on input
    document.getElementById('email').addEventListener('input', function() {
        const email = this.value;
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        
        if (email && !emailRegex.test(email)) {
            this.style.borderColor = '#DC2626';
        } else if (email) {
            this.style.borderColor = '#10B981';
        } else {
            this.style.borderColor = '';
        }
    });
</script>
@endsection
