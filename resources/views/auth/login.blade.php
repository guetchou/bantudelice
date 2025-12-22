<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>BantuDelice | Connexion Administration</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" type="image/x-icon" href="{{asset('favicon.ico')}}">
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --primary: #E85A24;
            --primary-dark: #C74A1A;
            --primary-light: #FF7A44;
            --secondary: #1A1A2E;
            --accent: #16213E;
            --success: #10B981;
            --warning: #F59E0B;
            --danger: #EF4444;
            --light: #F8FAFC;
            --dark: #0F172A;
            --gray-50: #F9FAFB;
            --gray-100: #F3F4F6;
            --gray-200: #E5E7EB;
            --gray-300: #D1D5DB;
            --gray-400: #9CA3AF;
            --gray-500: #6B7280;
            --gray-600: #4B5563;
            --gray-700: #374151;
            --gray-800: #1F2937;
            --gray-900: #111827;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            min-height: 100vh;
            display: flex;
            background: linear-gradient(135deg, var(--secondary) 0%, var(--accent) 50%, var(--dark) 100%);
            overflow: hidden;
        }
        
        /* Left Side - Branding */
        .login-branding {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 3rem;
            position: relative;
            overflow: hidden;
        }
        
        .login-branding::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(232, 90, 36, 0.15) 0%, transparent 60%);
            animation: pulse-bg 8s ease-in-out infinite;
        }
        
        @keyframes pulse-bg {
            0%, 100% { transform: scale(1) rotate(0deg); opacity: 0.5; }
            50% { transform: scale(1.1) rotate(180deg); opacity: 0.8; }
        }
        
        .branding-content {
            position: relative;
            z-index: 1;
            text-align: center;
            max-width: 500px;
        }
        
        .brand-logo {
            width: 180px;
            height: auto;
            margin-bottom: 2rem;
            filter: drop-shadow(0 10px 30px rgba(232, 90, 36, 0.3));
            animation: float 6s ease-in-out infinite;
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }
        
        .branding-content h1 {
            font-size: 2.5rem;
            font-weight: 800;
            color: white;
            margin-bottom: 1rem;
            line-height: 1.2;
        }
        
        .branding-content h1 span {
            color: var(--primary);
        }
        
        .branding-content p {
            font-size: 1.1rem;
            color: var(--gray-400);
            line-height: 1.7;
            margin-bottom: 2rem;
        }
        
        .features-list {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        
        .feature-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            color: var(--gray-300);
            font-size: 0.95rem;
        }
        
        .feature-item i {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1rem;
        }
        
        /* Right Side - Login Form */
        .login-form-container {
            width: 500px;
            min-height: 100vh;
            background: white;
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 3rem;
            position: relative;
        }
        
        .login-form-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: linear-gradient(to bottom, var(--primary), var(--primary-light), var(--warning));
        }
        
        .form-header {
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
        
        .login-form {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }
        
        .form-group {
            position: relative;
        }
        
        .form-group label {
            display: block;
            font-size: 0.875rem;
            font-weight: 600;
            color: var(--gray-700);
            margin-bottom: 0.5rem;
        }
        
        .input-wrapper {
            position: relative;
        }
        
        .input-wrapper i {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--gray-400);
            font-size: 1rem;
            transition: color 0.3s ease;
        }
        
        .input-wrapper input {
            width: 100%;
            padding: 0.875rem 1rem 0.875rem 3rem;
            border: 2px solid var(--gray-200);
            border-radius: 12px;
            font-family: inherit;
            font-size: 1rem;
            color: var(--gray-900);
            transition: all 0.3s ease;
            background: var(--gray-50);
        }
        
        .input-wrapper input:focus {
            outline: none;
            border-color: var(--primary);
            background: white;
            box-shadow: 0 0 0 4px rgba(232, 90, 36, 0.1);
        }
        
        .input-wrapper input:focus + i,
        .input-wrapper input:focus ~ i {
            color: var(--primary);
        }
        
        .input-wrapper input::placeholder {
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
        }
        
        .password-toggle:hover {
            color: var(--gray-600);
        }
        
        .form-options {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .remember-me {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            cursor: pointer;
        }
        
        .remember-me input[type="checkbox"] {
            width: 18px;
            height: 18px;
            accent-color: var(--primary);
            cursor: pointer;
        }
        
        .remember-me span {
            font-size: 0.9rem;
            color: var(--gray-600);
        }
        
        .forgot-password {
            font-size: 0.9rem;
            color: var(--primary);
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
        }
        
        .forgot-password:hover {
            color: var(--primary-dark);
            text-decoration: underline;
        }
        
        .btn-login {
            width: 100%;
            padding: 1rem;
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            border: none;
            border-radius: 12px;
            color: white;
            font-family: inherit;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            position: relative;
            overflow: hidden;
        }
        
        .btn-login::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s ease;
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(232, 90, 36, 0.3);
        }
        
        .btn-login:hover::before {
            left: 100%;
        }
        
        .btn-login:active {
            transform: translateY(0);
        }
        
        .divider {
            display: flex;
            align-items: center;
            gap: 1rem;
            color: var(--gray-400);
            font-size: 0.875rem;
        }
        
        .divider::before,
        .divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: var(--gray-200);
        }
        
        .social-login {
            display: flex;
            gap: 1rem;
        }
        
        .btn-social {
            flex: 1;
            padding: 0.875rem;
            border: 2px solid var(--gray-200);
            border-radius: 12px;
            background: white;
            font-family: inherit;
            font-size: 0.9rem;
            font-weight: 500;
            color: var(--gray-700);
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }
        
        .btn-social:hover {
            border-color: var(--gray-300);
            background: var(--gray-50);
        }
        
        .btn-social.google i { color: #DB4437; }
        .btn-social.facebook i { color: #4267B2; }
        
        .form-footer {
            text-align: center;
            margin-top: 1.5rem;
            padding-top: 1.5rem;
            border-top: 1px solid var(--gray-200);
        }
        
        .form-footer p {
            color: var(--gray-500);
            font-size: 0.9rem;
        }
        
        .form-footer a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s ease;
        }
        
        .form-footer a:hover {
            color: var(--primary-dark);
            text-decoration: underline;
        }
        
        /* Alert Messages */
        .alert {
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-size: 0.9rem;
        }
        
        .alert-danger {
            background: #FEE2E2;
            color: #991B1B;
            border: 1px solid #FECACA;
        }
        
        .alert-success {
            background: #D1FAE5;
            color: #065F46;
            border: 1px solid #A7F3D0;
        }
        
        .alert i {
            font-size: 1.25rem;
        }
        
        /* Responsive */
        @media (max-width: 1024px) {
            .login-branding {
                display: none;
            }
            
            .login-form-container {
                width: 100%;
                max-width: 480px;
                margin: 0 auto;
                min-height: auto;
                padding: 2rem;
                border-radius: 20px;
                margin-top: 2rem;
                margin-bottom: 2rem;
            }
            
            .login-form-container::before {
                display: none;
            }
            
            body {
                padding: 1rem;
                align-items: center;
                justify-content: center;
            }
        }
        
        @media (max-width: 480px) {
            .login-form-container {
                padding: 1.5rem;
            }
            
            .social-login {
                flex-direction: column;
            }
        }
        
        /* Loading State */
        .btn-login.loading {
            pointer-events: none;
            opacity: 0.8;
        }
        
        .btn-login.loading .btn-text {
            visibility: hidden;
        }
        
        .btn-login.loading::after {
            content: '';
            position: absolute;
            width: 20px;
            height: 20px;
            border: 2px solid white;
            border-top-color: transparent;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <!-- Left Side - Branding -->
    <div class="login-branding">
        <div class="branding-content">
            <img src="{{asset('frontend/images/BuntuDelice.png')}}" alt="BantuDelice" class="brand-logo">
            <h1>Bienvenue sur <span>BantuDelice</span></h1>
            <p>La plateforme de livraison de repas et services à domicile au Congo. Gérez votre restaurant, vos livraisons et vos commandes en toute simplicité.</p>
            
            <div class="features-list">
                <div class="feature-item">
                    <i class="fas fa-chart-line"></i>
                    <span>Tableau de bord analytique en temps réel</span>
                </div>
                <div class="feature-item">
                    <i class="fas fa-bell"></i>
                    <span>Notifications instantanées des commandes</span>
                </div>
                <div class="feature-item">
                    <i class="fas fa-truck"></i>
                    <span>Suivi GPS des livraisons en direct</span>
                </div>
                <div class="feature-item">
                    <i class="fas fa-shield-alt"></i>
                    <span>Sécurité renforcée et paiements sécurisés</span>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Right Side - Login Form -->
    <div class="login-form-container">
        <div class="form-header">
            <h2>Connexion</h2>
            <p>Connectez-vous pour accéder à votre espace professionnel</p>
        </div>
        
        @if(isset($errors) && $errors->any())
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i>
                <span>{{ $errors->first() }}</span>
            </div>
        @endif
        
        @if(session('alert'))
            @php
                $alert = session('alert');
                $alertType = is_array($alert) && isset($alert['type']) ? $alert['type'] : 'danger';
                $alertMessage = is_array($alert) && isset($alert['message']) ? $alert['message'] : (is_array($alert) && isset($alert['heading']) ? $alert['heading'] : 'Une erreur est survenue');
            @endphp
            <div class="alert {{ $alertType == 'danger' ? 'alert-danger' : 'alert-success' }}">
                <i class="fas {{ $alertType == 'danger' ? 'fa-exclamation-circle' : 'fa-check-circle' }}"></i>
                <span>{{ $alertMessage }}</span>
            </div>
        @endif
        
        @if(session('success'))
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        <div class="social-login" style="margin-bottom: 1.5rem;">
            <a class="btn-social google" href="{{ route('auth.social.redirect', ['provider' => 'google']) }}" style="text-decoration:none;">
                <i class="fab fa-google"></i>
                Google
            </a>
            <a class="btn-social facebook" href="{{ route('auth.social.redirect', ['provider' => 'facebook']) }}" style="text-decoration:none;">
                <i class="fab fa-facebook-f"></i>
                Facebook
            </a>
        </div>

        <div class="divider">
            <span>ou par email</span>
        </div>
        
        <form method="post" action="{{ url('login') }}" class="login-form" id="loginForm">
            @csrf
            
            <div class="form-group">
                <label for="email">Adresse email</label>
                <div class="input-wrapper">
                    <input type="email" 
                           id="email" 
                           name="email" 
                           value="{{ old('email') }}" 
                           placeholder="votre@email.com"
                           required
                           autocomplete="email">
                    <i class="fas fa-envelope"></i>
                </div>
            </div>
            
            <div class="form-group">
                <label for="password">Mot de passe</label>
                <div class="input-wrapper">
                    <input type="password" 
                           id="password" 
                           name="password" 
                           placeholder="Votre mot de passe"
                           required
                           autocomplete="current-password">
                    <i class="fas fa-lock"></i>
                    <button type="button" class="password-toggle" onclick="togglePassword()">
                        <i class="fas fa-eye" id="toggleIcon"></i>
                    </button>
                </div>
            </div>
            
            <div class="form-options">
                <label class="remember-me">
                    <input type="checkbox" name="remember" id="remember">
                    <span>Se souvenir de moi</span>
                </label>
                <a href="{{ url('password/reset') }}" class="forgot-password">Mot de passe oublié ?</a>
            </div>
            
            <button type="submit" class="btn-login" id="btnLogin">
                <span class="btn-text">Se connecter</span>
                <i class="fas fa-arrow-right"></i>
            </button>
        </form>
        
        <div class="form-footer">
            <p>Vous n'avez pas de compte ? 
                <a href="{{ url('/partner/registration') }}">Devenir partenaire restaurant</a> ou 
                <a href="{{ route('user.signup') }}">Créer un compte client</a>
            </p>
            <p style="margin-top: 0.75rem; padding-top: 0.75rem; border-top: 1px solid var(--gray-200);">
                <strong>Vous êtes un client ?</strong> 
                <a href="{{ url('/user/login') }}" style="display: inline-flex; align-items: center; gap: 0.25rem;">
                    Connectez-vous ici <i class="fas fa-arrow-right" style="font-size: 0.75rem;"></i>
                </a>
            </p>
            <p style="margin-top: 0.5rem;"><a href="{{ url('/') }}">← Retour au site</a></p>
        </div>
    </div>
    
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
            const btn = document.getElementById('btnLogin');
            btn.classList.add('loading');
        });
        
        // Connexion sociale: disponible côté clients (page /user/login)
        
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
</body>
</html>
