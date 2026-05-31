@php
    $authBrand = app(\App\Services\AuthBrandingService::class)->resolve(request());
    $googleAuthEnabled = (bool) config('external-services.social_auth.google.enabled');
    $facebookAuthEnabled = (bool) config('external-services.social_auth.facebook.enabled');
    $hasSocialAuth = $googleAuthEnabled || $facebookAuthEnabled;
    $socialRedirect = request()->query('redirect');
@endphp
@extends('frontend.layouts.app-modern')
@section('hide_primary_chrome', '1')
@section('title', $authBrand['login_title'])
@section('description', $authBrand['login_description'])
@section('body_class', 'bd-auth-page bd-auth-login')
@section('body_style', '--auth-brand-primary: ' . $authBrand['primary'] . '; --auth-brand-primary-dark: ' . $authBrand['primary_dark'] . '; --auth-brand-primary-soft: ' . $authBrand['primary_soft'] . '; --auth-brand-secondary: ' . $authBrand['secondary'] . '; --auth-brand-surface: ' . $authBrand['surface'] . ';')

@section('content')
<section class="login-page">
    <div class="login-container">
        <!-- Left Side - Benefits -->
        <div class="login-benefits">
            <div class="benefits-header">
                <div class="auth-brand-pill">
                    <span>{{ $authBrand['name'] }}</span>
                    <span class="auth-brand-pill__dot"></span>
                    <span>{{ $authBrand['label'] }}</span>
                </div>
                <h1>{{ trim(str_replace($authBrand['hero_title_emphasis'], '', $authBrand['hero_title'])) }} <span>{{ $authBrand['hero_title_emphasis'] }}</span></h1>
                <p>{{ $authBrand['hero_description'] }}</p>
            </div>
            
            <div class="benefits-list">
                @foreach($authBrand['features'] as $feature)
                    <div class="benefit-item">
                        <div class="benefit-icon">
                            <i class="fas {{ $feature['icon'] }}"></i>
                        </div>
                        <div class="benefit-content">
                            <h4>{{ $feature['title'] }}</h4>
                            <p>{{ $feature['description'] }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
        
        <!-- Right Side - Form -->
        <div class="login-form-wrapper">
            <div class="login-card">
                <div class="form-header">
                    <h2>Connexion</h2>
                    <p>Bienvenue sur {{ $authBrand['name'] }}. Connectez-vous pour continuer.</p>
                </div>
                
                @if($hasSocialAuth)
                    <div class="social-login auth-social-row">
                        @if($googleAuthEnabled)
                            <a class="btn-social auth-social-link google" href="{{ route('auth.social.redirect', ['provider' => 'google', 'redirect' => $socialRedirect]) }}">
                                <i class="fab fa-google"></i>
                                Google
                            </a>
                        @endif
                        @if($facebookAuthEnabled)
                            <a class="btn-social auth-social-link facebook" href="{{ route('auth.social.redirect', ['provider' => 'facebook', 'redirect' => $socialRedirect]) }}">
                                <i class="fab fa-facebook-f"></i>
                                Facebook
                            </a>
                        @endif
                    </div>
                    
                    <div class="divider">
                        <span>ou par email</span>
                    </div>
                @endif
                
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
                    @if($socialRedirect)
                        <input type="hidden" name="redirect" value="{{ $socialRedirect }}">
                    @endif
                    
                    <div class="form-group">
                        <label class="form-label">Email ou téléphone</label>
                        <div class="input-group">
                            <input type="text"
                                   name="identifier"
                                   id="identifier"
                                   value="{{ old('identifier') }}"
                                   placeholder="votre@email.com ou +242 06..."
                                   required
                                   autocomplete="username">
                            <i class="fas fa-user"></i>
                        </div>
                        @if($errors->has('identifier'))
                            <div class="form-error">
                                <i class="fas fa-exclamation-circle"></i>
                                {{ $errors->first('identifier') }}
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
                        <a href="{{ route('user.forgot', array_filter(['redirect' => $socialRedirect])) }}" class="forgot-link">
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
                    <p>Pas encore de compte ? <a href="{{ route('user.signup', array_filter(['redirect' => $socialRedirect])) }}">Créer un compte</a></p>
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
    
    document.getElementById('identifier').addEventListener('input', function() {
        this.style.borderColor = this.value.trim() ? '#009543' : '';
    });
</script>
@endsection
