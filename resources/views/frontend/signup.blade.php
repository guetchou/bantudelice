@extends('frontend.layouts.app-modern')
@section('title', 'Inscription | BantuDelice')
@section('description', 'Créez votre compte BantuDelice et commencez à commander vos plats préférés.')

@section('styles')
<style>
    .signup-page {
        min-height: calc(100vh - 80px);
        display: flex;
        align-items: center;
        padding: 120px 0 60px;
        background: linear-gradient(135deg, #FFF5F0 0%, #FFFFFF 50%, #FFF9F5 100%);
        position: relative;
        overflow: hidden;
    }
    
    .signup-page::before {
        content: '';
        position: absolute;
        top: -50%;
        left: -30%;
        width: 80%;
        height: 150%;
        background: radial-gradient(ellipse, rgba(232, 90, 36, 0.08) 0%, transparent 60%);
        pointer-events: none;
    }
    
    .signup-container {
        display: grid;
        grid-template-columns: 1fr 1.2fr;
        gap: 4rem;
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 1.5rem;
        position: relative;
        z-index: 1;
    }
    
    /* Left Side - Benefits */
    .signup-benefits {
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
    .signup-form-wrapper {
        display: flex;
        flex-direction: column;
        justify-content: center;
    }
    
    .signup-card {
        background: white;
        border-radius: 24px;
        padding: 2.5rem;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.08);
        position: relative;
        overflow: hidden;
    }
    
    .signup-card::before {
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
    
    /* Progress Steps */
    .progress-steps {
        display: flex;
        justify-content: center;
        gap: 0.5rem;
        margin-bottom: 2rem;
    }
    
    .progress-step {
        width: 40px;
        height: 4px;
        background: var(--gray-200);
        border-radius: 2px;
        transition: all 0.3s ease;
    }
    
    .progress-step.active {
        background: var(--primary);
    }
    
    .progress-step.completed {
        background: var(--success);
    }
    
    /* Social Signup */
    .social-signup {
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
    .form-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1rem;
    }
    
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
    
    .input-group.valid input {
        border-color: var(--success);
    }
    
    .input-group.invalid input {
        border-color: #DC2626;
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
    
    /* Password Strength */
    .password-strength {
        margin-top: 0.5rem;
    }
    
    .strength-bar {
        display: flex;
        gap: 4px;
        margin-bottom: 0.375rem;
    }
    
    .strength-segment {
        flex: 1;
        height: 4px;
        background: var(--gray-200);
        border-radius: 2px;
        transition: all 0.3s ease;
    }
    
    .strength-segment.weak { background: #EF4444; }
    .strength-segment.medium { background: #F59E0B; }
    .strength-segment.strong { background: #10B981; }
    
    .strength-text {
        font-size: 0.75rem;
        color: var(--gray-500);
    }
    
    /* Profile Upload */
    .profile-upload {
        text-align: center;
        margin-bottom: 1.5rem;
    }
    
    .upload-area {
        width: 100px;
        height: 100px;
        border: 3px dashed var(--gray-300);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 0.75rem;
        cursor: pointer;
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
    }
    
    .upload-area:hover {
        border-color: var(--primary);
        background: rgba(232, 90, 36, 0.05);
    }
    
    .upload-area img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    
    .upload-area .upload-icon {
        font-size: 1.5rem;
        color: var(--gray-400);
    }
    
    .upload-text {
        font-size: 0.875rem;
        color: var(--gray-500);
    }
    
    .upload-text span {
        color: var(--primary);
        font-weight: 600;
    }
    
    /* Checkbox */
    .checkbox-wrapper {
        display: flex;
        align-items: flex-start;
        gap: 0.75rem;
        cursor: pointer;
        margin-bottom: 1.5rem;
    }
    
    .checkbox-wrapper input[type="checkbox"] {
        display: none;
    }
    
    .checkbox-custom {
        width: 22px;
        height: 22px;
        border: 2px solid var(--gray-300);
        border-radius: 6px;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.3s ease;
        flex-shrink: 0;
        margin-top: 2px;
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
        line-height: 1.5;
    }
    
    .checkbox-label a {
        color: var(--primary);
        font-weight: 500;
        text-decoration: none;
    }
    
    .checkbox-label a:hover {
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
    
    .btn-submit:disabled {
        opacity: 0.7;
        cursor: not-allowed;
        transform: none;
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
        .signup-container {
            grid-template-columns: 1fr;
            max-width: 520px;
        }
        
        .signup-benefits {
            display: none;
        }
    }
    
    @media (max-width: 576px) {
        .signup-page {
            padding: 100px 0 40px;
        }
        
        .signup-card {
            padding: 1.5rem;
            border-radius: 20px;
        }
        
        .form-row {
            grid-template-columns: 1fr;
        }
        
        .social-signup {
            flex-direction: column;
        }
    }
</style>
@endsection

@section('content')
<section class="signup-page">
    <div class="signup-container">
        <!-- Left Side - Benefits -->
        <div class="signup-benefits">
            <div class="benefits-header">
                <h1>Rejoignez <span>BantuDelice</span></h1>
                <p>Créez votre compte gratuit et profitez de tous les avantages : commandes simplifiées, suivi en temps réel, et offres exclusives.</p>
            </div>
            
            <div class="benefits-list">
                <div class="benefit-item">
                    <div class="benefit-icon">
                        <i class="fas fa-gift"></i>
                    </div>
                    <div class="benefit-content">
                        <h4>Bonus de bienvenue</h4>
                        <p>10% de réduction sur votre première commande</p>
                    </div>
                </div>
                
                <div class="benefit-item">
                    <div class="benefit-icon">
                        <i class="fas fa-history"></i>
                    </div>
                    <div class="benefit-content">
                        <h4>Historique des commandes</h4>
                        <p>Retrouvez et recommandez facilement</p>
                    </div>
                </div>
                
                <div class="benefit-item">
                    <div class="benefit-icon">
                        <i class="fas fa-heart"></i>
                    </div>
                    <div class="benefit-content">
                        <h4>Restaurants favoris</h4>
                        <p>Sauvegardez vos adresses préférées</p>
                    </div>
                </div>
                
                <div class="benefit-item">
                    <div class="benefit-icon">
                        <i class="fas fa-bell"></i>
                    </div>
                    <div class="benefit-content">
                        <h4>Notifications</h4>
                        <p>Restez informé des offres et promotions</p>
                    </div>
                </div>
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
                <div class="social-signup">
                    <button type="button" class="btn-social google" onclick="socialSignup('google')">
                        <i class="fab fa-google"></i>
                        Google
                    </button>
                    <button type="button" class="btn-social facebook" onclick="socialSignup('facebook')">
                        <i class="fab fa-facebook-f"></i>
                        Facebook
                    </button>
                </div>
                
                <div class="divider">
                    <span>ou par email</span>
                </div>
                
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
                            <img src="{{ url('images/placeholder.png') }}" id="profile_image" alt="Photo de profil" style="display: none;">
                            <i class="fas fa-camera upload-icon" id="upload_icon"></i>
                        </div>
                        <p class="upload-text"><span>Cliquez</span> pour ajouter une photo (optionnel)</p>
                        <input type="file" id="upload_file" name="image" accept="image/*" style="display: none;" onchange="previewImage(this);">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Mot de passe *</label>
                        <div class="input-group" id="passwordGroup">
                            <input type="password" 
                                   name="password"
                                   id="password"
                                   placeholder="Créez un mot de passe sécurisé"
                                   required
                                   minlength="8">
                            <i class="fas fa-lock"></i>
                            <button type="button" class="password-toggle" onclick="togglePassword('password', 'toggleIcon1')">
                                <i class="fas fa-eye" id="toggleIcon1"></i>
                            </button>
                        </div>
                        <div class="password-strength" id="passwordStrength" style="display: none;">
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
                                   required>
                            <i class="fas fa-lock"></i>
                            <button type="button" class="password-toggle" onclick="togglePassword('password_confirmation', 'toggleIcon2')">
                                <i class="fas fa-eye" id="toggleIcon2"></i>
                            </button>
                        </div>
                        <div class="form-error" id="confirmError" style="display: none;">
                            <i class="fas fa-exclamation-circle"></i>
                            Les mots de passe ne correspondent pas
                        </div>
                    </div>
                    
                    <label class="checkbox-wrapper">
                        <input type="checkbox" name="terms" id="terms" required>
                        <span class="checkbox-custom"><i class="fas fa-check"></i></span>
                        <span class="checkbox-label">
                            J'accepte les <a href="{{ route('terms.conditions') }}" target="_blank">conditions générales</a> 
                            et la <a href="#" target="_blank">politique de confidentialité</a> de BantuDelice
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
                    <p>Déjà un compte ? <a href="{{ route('user.login') }}">Connectez-vous</a></p>
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
    
    // Social signup placeholder
    function socialSignup(provider) {
        const providerName = provider.charAt(0).toUpperCase() + provider.slice(1);
        alert('L\'inscription ' + providerName + ' sera bientôt disponible !');
    }
    
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
