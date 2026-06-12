@php
    $authBrand = app(\App\Services\AuthBrandingService::class)->resolve(request());
    $googleAuthEnabled = (bool) config('external-services.social_auth.google.enabled');
    $facebookAuthEnabled = (bool) config('external-services.social_auth.facebook.enabled');
    $hasSocialAuth = $googleAuthEnabled || $facebookAuthEnabled;
    $socialRedirect = request()->query('redirect');
@endphp
@extends('frontend.layouts.app-modern')
@section('hide_primary_chrome', '1')
@section('title', $authBrand['signup_title'])
@section('description', $authBrand['signup_description'])
@section('body_class', 'bd-auth-page bd-auth-signup')
@section('body_style', '--auth-brand-primary: ' . $authBrand['primary'] . '; --auth-brand-primary-dark: ' . $authBrand['primary_dark'] . '; --auth-brand-primary-soft: ' . $authBrand['primary_soft'] . '; --auth-brand-secondary: ' . $authBrand['secondary'] . '; --auth-brand-surface: ' . $authBrand['surface'] . ';')

@section('content')
<section class="signup-page">
    <div class="signup-container">
        <!-- Left Side - Benefits -->
        <div class="signup-benefits">
            <div class="benefits-header">
                <div class="auth-brand-pill">
                    <span>{{ $authBrand['name'] }}</span>
                    <span class="auth-brand-pill__dot"></span>
                    <span>{{ $authBrand['label'] }}</span>
                </div>
                <h1>Rejoignez <span>{{ $authBrand['name'] }}</span></h1>
                <p>{{ $authBrand['hero_description'] }}</p>
            </div>
            
            <div class="benefits-list">
                @foreach($authBrand['signup_features'] as $feature)
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
        <div class="signup-form-wrapper">
            <div class="signup-card">
                <div class="form-header">
                    <h2>Créer un compte</h2>
                    <p>Remplissez les informations ci-dessous</p>
                </div>
                
                <!-- Progress Steps -->
                <div class="progress-steps">
                    <div class="progress-step active" id="step1"></div>
                    <div class="progress-step" id="step2"></div>
                    <div class="progress-step" id="step3"></div>
                </div>
                
                <!-- Social Signup -->
                @if($hasSocialAuth)
                    <div class="social-signup auth-social-row">
                        @if($googleAuthEnabled)
                            <a class="btn-social auth-social-link google" href="{{ route('auth.social.redirect', array_filter(['provider' => 'google', 'redirect' => $socialRedirect])) }}">
                                <i class="fab fa-google"></i>
                                Google
                            </a>
                        @endif
                        @if($facebookAuthEnabled)
                            <a class="btn-social auth-social-link facebook" href="{{ route('auth.social.redirect', array_filter(['provider' => 'facebook', 'redirect' => $socialRedirect])) }}">
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
                @if(session()->has('alert'))
                    <div class="alert {{ session()->get('alert.type') == 'success' ? 'alert-success' : 'alert-error' }}">
                        <i class="fas {{ session()->get('alert.type') == 'success' ? 'fa-check-circle' : 'fa-exclamation-circle' }}"></i>
                        <span>{{ session()->get('alert.message') }}</span>
                    </div>
                @endif
                
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
                
                <!-- Signup Form -->
                <form action="{{ route('new.signup') }}" method="post" enctype="multipart/form-data" id="signupForm">
                    @csrf
                    @if($socialRedirect)
                        <input type="hidden" name="redirect" value="{{ $socialRedirect }}">
                    @endif
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Nom complet *</label>
                            <div class="input-group" id="nameGroup">
                                <input type="text" 
                                       name="name"
                                       id="name"
                                       value="{{ old('name') }}" 
                                       placeholder="Jean Dupont"
                                       required>
                                <i class="fas fa-user"></i>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Téléphone *</label>
                            <div class="input-group" id="phoneGroup">
                                <input type="tel" 
                                       name="phone"
                                       id="phone"
                                       value="{{ old('phone') }}" 
                                       placeholder="+242 06 XXX XX XX"
                                       required>
                                <i class="fas fa-phone"></i>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Adresse email *</label>
                        <div class="input-group" id="emailGroup">
                            <input type="email" 
                                   name="email"
                                   id="email"
                                   value="{{ old('email') }}" 
                                   placeholder="votre@email.com"
                                   required>
                            <i class="fas fa-envelope"></i>
                        </div>
                        @if($errors->has('email'))
                            <div class="form-error">
                                <i class="fas fa-exclamation-circle"></i>
                                {{ $errors->first('email') }}
                            </div>
                        @endif
                    </div>
                    
                    <!-- Profile Upload -->
                    <div class="profile-upload">
                        <div class="upload-area" onclick="document.getElementById('upload_file').click()">
                            <img src="{{ url('images/placeholder.png') }}" id="profile_image" alt="Photo de profil" class="auth-hidden">
                            <i class="fas fa-camera upload-icon" id="upload_icon"></i>
                        </div>
                        <p class="upload-text"><span>Cliquez</span> pour ajouter une photo (optionnel)</p>
                        <input type="file" id="upload_file" name="image" accept="image/*" class="auth-hidden" onchange="previewImage(this);">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Mot de passe *</label>
                        <div class="input-group" id="passwordGroup">
                            <input type="password"
                                   name="password"
                                   id="password"
                                   placeholder="Créez un mot de passe sécurisé"
                                   autocomplete="new-password"
                                   required
                                   minlength="8">
                            <i class="fas fa-lock"></i>
                            <button type="button" class="password-toggle" onclick="togglePassword('password', 'toggleIcon1')">
                                <i class="fas fa-eye" id="toggleIcon1"></i>
                            </button>
                        </div>
                        <div class="password-strength auth-hidden" id="passwordStrength">
                            <div class="strength-bar">
                                <div class="strength-segment" id="seg1"></div>
                                <div class="strength-segment" id="seg2"></div>
                                <div class="strength-segment" id="seg3"></div>
                                <div class="strength-segment" id="seg4"></div>
                            </div>
                            <span class="strength-text" id="strengthText">Force du mot de passe</span>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Confirmer le mot de passe *</label>
                        <div class="input-group" id="confirmGroup">
                            <input type="password"
                                   name="password_confirmation"
                                   id="password_confirmation"
                                   placeholder="Confirmez votre mot de passe"
                                   autocomplete="new-password"
                                   required>
                            <i class="fas fa-lock"></i>
                            <button type="button" class="password-toggle" onclick="togglePassword('password_confirmation', 'toggleIcon2')">
                                <i class="fas fa-eye" id="toggleIcon2"></i>
                            </button>
                        </div>
                        <div class="form-error auth-hidden" id="confirmError">
                            <i class="fas fa-exclamation-circle"></i>
                            Les mots de passe ne correspondent pas
                        </div>
                    </div>
                    
                    <label class="checkbox-wrapper">
                        <input type="checkbox" name="terms" id="terms" required>
                        <span class="checkbox-custom"><i class="fas fa-check"></i></span>
                        <span class="checkbox-label">
                            J'accepte les <a href="{{ route('terms.conditions') }}" target="_blank">conditions générales</a> 
                            et la <a href="{{ route('privacy.policy') }}" target="_blank">politique de confidentialité</a> de {{ $authBrand['name'] }}
                        </span>
                    </label>
                    
                    <button type="submit" class="btn-submit" id="submitBtn">
                        <span class="btn-text">Créer mon compte</span>
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
                    <div class="trust-badge">
                        <i class="fas fa-user-shield"></i>
                        Inscription gratuite
                    </div>
                </div>
                
                <div class="form-footer">
                    <p>Déjà un compte ? <a href="{{ route('user.login', array_filter(['redirect' => $socialRedirect])) }}">Connectez-vous</a></p>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection

@section('scripts')
<script>
    // Preview profile image
    function previewImage(input) {
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                const img = document.getElementById('profile_image');
                img.src = e.target.result;
                img.style.display = 'block';
                document.getElementById('upload_icon').style.display = 'none';
            };
            reader.readAsDataURL(input.files[0]);
            updateProgress(1);
        }
    }
    
    // Toggle password visibility
    function togglePassword(inputId, iconId) {
        const input = document.getElementById(inputId);
        const icon = document.getElementById(iconId);
        
        if (input.type === 'password') {
            input.type = 'text';
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        } else {
            input.type = 'password';
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        }
    }
    
    // Password strength checker
    document.getElementById('password').addEventListener('input', function() {
        const password = this.value;
        const strengthDiv = document.getElementById('passwordStrength');
        const strengthText = document.getElementById('strengthText');
        
        if (password.length > 0) {
            strengthDiv.style.display = 'block';
            
            let strength = 0;
            if (password.length >= 8) strength++;
            if (/[a-z]/.test(password) && /[A-Z]/.test(password)) strength++;
            if (/\d/.test(password)) strength++;
            if (/[^a-zA-Z0-9]/.test(password)) strength++;
            
            const segments = ['seg1', 'seg2', 'seg3', 'seg4'];
            segments.forEach((seg, i) => {
                const el = document.getElementById(seg);
                el.className = 'strength-segment';
                if (i < strength) {
                    if (strength <= 1) el.classList.add('weak');
                    else if (strength <= 2) el.classList.add('medium');
                    else el.classList.add('strong');
                }
            });
            
            const texts = ['Très faible', 'Faible', 'Moyen', 'Fort', 'Très fort'];
            strengthText.textContent = texts[strength];
            
            updateProgress(2);
        } else {
            strengthDiv.style.display = 'none';
        }
    });
    
    // Password confirmation check
    document.getElementById('password_confirmation').addEventListener('input', function() {
        const password = document.getElementById('password').value;
        const confirm = this.value;
        const errorDiv = document.getElementById('confirmError');
        const group = document.getElementById('confirmGroup');
        
        if (confirm.length > 0) {
            if (password !== confirm) {
                errorDiv.style.display = 'flex';
                group.classList.add('invalid');
                group.classList.remove('valid');
            } else {
                errorDiv.style.display = 'none';
                group.classList.remove('invalid');
                group.classList.add('valid');
                updateProgress(3);
            }
        }
    });
    
    // Update progress steps
    function updateProgress(step) {
        for (let i = 1; i <= step; i++) {
            document.getElementById('step' + i).classList.add('active');
            if (i < step) {
                document.getElementById('step' + i).classList.add('completed');
            }
        }
    }
    
    // Email validation
    document.getElementById('email').addEventListener('input', function() {
        const email = this.value;
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        const group = document.getElementById('emailGroup');
        
        if (email && !emailRegex.test(email)) {
            group.classList.add('invalid');
            group.classList.remove('valid');
        } else if (email) {
            group.classList.remove('invalid');
            group.classList.add('valid');
        } else {
            group.classList.remove('invalid', 'valid');
        }
    });
    
    // Form submission
    document.getElementById('signupForm').addEventListener('submit', function(e) {
        const password = document.getElementById('password').value;
        const confirm = document.getElementById('password_confirmation').value;
        
        if (password !== confirm) {
            e.preventDefault();
            document.getElementById('confirmError').style.display = 'flex';
            return;
        }
        
        const btn = document.getElementById('submitBtn');
        btn.classList.add('loading');
    });
    
    // Auto-hide alerts
    setTimeout(() => {
        document.querySelectorAll('.alert').forEach(alert => {
            alert.style.opacity = '0';
            alert.style.transform = 'translateY(-10px)';
            alert.style.transition = 'all 0.3s ease';
            setTimeout(() => alert.remove(), 300);
        });
    }, 5000);
</script>
@endsection
