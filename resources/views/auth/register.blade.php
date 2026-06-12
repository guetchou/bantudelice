<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Plateforme | Inscription Administration</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" type="image/x-icon" href="{{asset('favicon.ico')}}">

    <!-- Poppins font -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        :root {
            --primary: #009543;
            --primary-dark: #007836;
            --primary-light: #22c55e;
            --sidebar: #0a1a0f;
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

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Poppins', sans-serif;
            min-height: 100vh;
            display: flex;
            background: var(--sidebar);
            overflow-x: hidden;
        }

        /* ── Left Side ── */
        .reg-branding {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 3rem;
            position: relative;
            overflow: hidden;
        }

        .reg-branding::before {
            content: '';
            position: absolute;
            bottom: -80px;
            right: -80px;
            width: 320px;
            height: 320px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(0,149,67,.14) 0%, transparent 70%);
            pointer-events: none;
        }

        .branding-content {
            position: relative;
            z-index: 1;
            text-align: center;
            max-width: 500px;
        }

        .brand-logo {
            width: 140px;
            height: auto;
            margin-bottom: 2rem;
            filter: drop-shadow(0 4px 16px rgba(0,149,67,.25));
        }

        .branding-content h1 {
            font-size: 2.25rem;
            font-weight: 800;
            color: white;
            margin-bottom: 1rem;
            line-height: 1.2;
        }

        .branding-content h1 span { color: var(--primary); }

        .branding-content p {
            font-size: 1rem;
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
            font-size: 0.9rem;
        }

        .feature-item i {
            width: 38px;
            height: 38px;
            background: #009543;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 0.95rem;
            flex-shrink: 0;
        }

        /* ── Right Side ── */
        .reg-form-container {
            width: 520px;
            min-height: 100vh;
            background: white;
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 2.5rem 3rem;
            position: relative;
            overflow-y: auto;
        }

        .reg-form-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: #009543;
        }

        .form-header { margin-bottom: 1.5rem; }

        .form-header h2 {
            font-size: 1.6rem;
            font-weight: 700;
            color: var(--gray-900);
            margin-bottom: 0.4rem;
        }

        .form-header p {
            color: var(--gray-500);
            font-size: 0.9rem;
        }

        .reg-form {
            display: flex;
            flex-direction: column;
            gap: 1.25rem;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        .form-group { position: relative; }

        .form-group label {
            display: block;
            font-size: 0.8rem;
            font-weight: 600;
            color: var(--gray-700);
            margin-bottom: 0.4rem;
        }

        .input-wrapper { position: relative; }

        .input-wrapper i.input-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--gray-400);
            font-size: 0.9rem;
            transition: color 0.3s;
            pointer-events: none;
        }

        .input-wrapper input {
            width: 100%;
            padding: 0.8rem 1rem 0.8rem 2.75rem;
            border: 2px solid var(--gray-200);
            border-radius: 12px;
            font-family: inherit;
            font-size: 0.95rem;
            color: var(--gray-900);
            background: var(--gray-50);
            transition: all 0.3s;
        }

        .input-wrapper input:focus {
            outline: none;
            border-color: var(--primary);
            background: white;
            box-shadow: 0 0 0 4px rgba(0,149,67,.15);
        }

        .input-wrapper input:focus ~ i.input-icon { color: var(--primary); }

        .input-wrapper input::placeholder { color: var(--gray-400); }

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
            transition: color 0.3s;
        }

        .password-toggle:hover { color: var(--gray-600); }

        /* Password strength */
        .password-strength {
            margin-top: 0.5rem;
        }

        .strength-bar {
            display: flex;
            gap: 4px;
            margin-bottom: 0.3rem;
        }

        .strength-segment {
            flex: 1;
            height: 4px;
            border-radius: 2px;
            background: var(--gray-200);
            transition: background 0.3s;
        }

        .strength-segment.weak   { background: #ef4444; }
        .strength-segment.medium { background: #f97316; }
        .strength-segment.strong { background: #009543; }

        .strength-text {
            font-size: 0.75rem;
            color: var(--gray-500);
        }

        /* Terms checkbox */
        .checkbox-wrapper {
            display: flex;
            align-items: flex-start;
            gap: 0.75rem;
            cursor: pointer;
        }

        .checkbox-wrapper input[type="checkbox"] {
            width: 18px;
            height: 18px;
            accent-color: var(--primary);
            cursor: pointer;
            flex-shrink: 0;
            margin-top: 2px;
        }

        .checkbox-label {
            font-size: 0.85rem;
            color: var(--gray-600);
            line-height: 1.5;
        }

        .checkbox-label a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 500;
        }

        .checkbox-label a:hover { text-decoration: underline; }

        /* Submit button */
        .btn-register {
            width: 100%;
            padding: 0.95rem;
            background: #009543;
            border: none;
            border-radius: 12px;
            color: white;
            font-family: inherit;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            position: relative;
            overflow: hidden;
        }

        .btn-register::before {
            content: '';
            position: absolute;
            top: 0; left: -100%;
            width: 100%; height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,.2), transparent);
            transition: left 0.5s;
        }

        .btn-register:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(0,149,67,.3);
        }

        .btn-register:hover::before { left: 100%; }
        .btn-register:active { transform: translateY(0); }

        /* Loading state */
        .btn-register.loading { pointer-events: none; opacity: 0.8; }
        .btn-register.loading .btn-text { visibility: hidden; }
        .btn-register.loading::after {
            content: '';
            position: absolute;
            width: 20px; height: 20px;
            border: 2px solid white;
            border-top-color: transparent;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }

        @keyframes spin { to { transform: rotate(360deg); } }

        /* Alerts */
        .alert {
            padding: 0.875rem 1rem;
            border-radius: 10px;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-size: 0.875rem;
        }

        .alert-danger  { background: #FEE2E2; color: #991B1B; border: 1px solid #FECACA; }
        .alert-success { background: #D1FAE5; color: #065F46; border: 1px solid #A7F3D0; }

        .field-error {
            color: #dc2626;
            font-size: 0.78rem;
            margin-top: 0.3rem;
            display: flex;
            align-items: center;
            gap: 0.3rem;
        }

        /* Footer */
        .form-footer {
            text-align: center;
            margin-top: 1.25rem;
            padding-top: 1.25rem;
            border-top: 1px solid var(--gray-200);
        }

        .form-footer p { color: var(--gray-500); font-size: 0.875rem; }

        .form-footer a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s;
        }

        .form-footer a:hover {
            color: var(--primary-dark);
            text-decoration: underline;
        }

        /* Responsive */
        @media (max-width: 1024px) {
            .reg-branding { display: none; }

            .reg-form-container {
                width: 100%;
                max-width: 520px;
                margin: 0 auto;
                min-height: auto;
                padding: 2rem;
                border-radius: 20px;
                margin-top: 2rem;
                margin-bottom: 2rem;
            }

            .reg-form-container::before { display: none; }

            body {
                padding: 1rem;
                align-items: center;
                justify-content: center;
            }
        }

        @media (max-width: 540px) {
            .form-row { grid-template-columns: 1fr; }
            .reg-form-container { padding: 1.5rem; }
        }
    </style>
</head>
<body>
    <!-- Left Side - Branding -->
    <div class="reg-branding">
        <div class="branding-content">
            <img src="{{asset('frontend/images/BuntuDelice.png')}}" alt="BantuDelice" class="brand-logo">
            <h1>Rejoignez <span>BantuDelice</span></h1>
            <p>Créez votre compte administrateur pour piloter les restaurants, livreurs et commandes sur la plateforme.</p>

            <div class="features-list">
                <div class="feature-item">
                    <i class="fas fa-shield-alt"></i>
                    <span>Accès sécurisé à l'espace admin</span>
                </div>
                <div class="feature-item">
                    <i class="fas fa-utensils"></i>
                    <span>Gestion complète des restaurants</span>
                </div>
                <div class="feature-item">
                    <i class="fas fa-chart-line"></i>
                    <span>Tableau de bord des performances</span>
                </div>
                <div class="feature-item">
                    <i class="fas fa-bell"></i>
                    <span>Alertes opérationnelles en temps réel</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Right Side - Register Form -->
    <div class="reg-form-container">
        <div class="form-header">
            <h2>Créer un compte</h2>
            <p>Remplissez les informations pour créer votre accès</p>
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
                $alertMessage = is_array($alert) && isset($alert['message']) ? $alert['message'] : 'Une erreur est survenue';
            @endphp
            <div class="alert {{ $alertType == 'danger' ? 'alert-danger' : 'alert-success' }}">
                <i class="fas {{ $alertType == 'danger' ? 'fa-exclamation-circle' : 'fa-check-circle' }}"></i>
                <span>{{ $alertMessage }}</span>
            </div>
        @endif

        <form method="post" action="{{ route('register') }}" class="reg-form" id="registerForm" enctype="multipart/form-data">
            @csrf

            <div class="form-row">
                <div class="form-group">
                    <label for="name">Nom complet</label>
                    <div class="input-wrapper">
                        <input type="text"
                               id="name"
                               name="name"
                               value="{{ old('name') }}"
                               placeholder="Jean Dupont"
                               required
                               autocomplete="name"
                               autofocus>
                        <i class="fas fa-user input-icon"></i>
                    </div>
                    @error('name')
                        <div class="field-error"><i class="fas fa-exclamation-circle"></i> {{ $message }}</div>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="phone">Téléphone</label>
                    <div class="input-wrapper">
                        <input type="tel"
                               id="phone"
                               name="phone"
                               value="{{ old('phone') }}"
                               placeholder="+242 06 XXX XX XX"
                               required
                               autocomplete="tel">
                        <i class="fas fa-phone input-icon"></i>
                    </div>
                    @error('phone')
                        <div class="field-error"><i class="fas fa-exclamation-circle"></i> {{ $message }}</div>
                    @enderror
                </div>
            </div>

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
                    <i class="fas fa-envelope input-icon"></i>
                </div>
                @error('email')
                    <div class="field-error"><i class="fas fa-exclamation-circle"></i> {{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label for="password">Mot de passe</label>
                <div class="input-wrapper">
                    <input type="password"
                           id="password"
                           name="password"
                           placeholder="Créez un mot de passe sécurisé"
                           required
                           minlength="8"
                           autocomplete="new-password">
                    <i class="fas fa-lock input-icon"></i>
                    <button type="button" class="password-toggle" onclick="togglePassword('password', 'toggleIcon1')">
                        <i class="fas fa-eye" id="toggleIcon1"></i>
                    </button>
                </div>
                <div class="password-strength" id="passwordStrength" style="display:none;">
                    <div class="strength-bar">
                        <div class="strength-segment" id="seg1"></div>
                        <div class="strength-segment" id="seg2"></div>
                        <div class="strength-segment" id="seg3"></div>
                        <div class="strength-segment" id="seg4"></div>
                    </div>
                    <span class="strength-text" id="strengthText">Force du mot de passe</span>
                </div>
                @error('password')
                    <div class="field-error"><i class="fas fa-exclamation-circle"></i> {{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label for="password_confirmation">Confirmer le mot de passe</label>
                <div class="input-wrapper">
                    <input type="password"
                           id="password_confirmation"
                           name="password_confirmation"
                           placeholder="Confirmez votre mot de passe"
                           required
                           autocomplete="new-password">
                    <i class="fas fa-lock input-icon"></i>
                    <button type="button" class="password-toggle" onclick="togglePassword('password_confirmation', 'toggleIcon2')">
                        <i class="fas fa-eye" id="toggleIcon2"></i>
                    </button>
                </div>
                <div class="field-error" id="confirmError" style="display:none;">
                    <i class="fas fa-exclamation-circle"></i> Les mots de passe ne correspondent pas
                </div>
            </div>

            <label class="checkbox-wrapper">
                <input type="checkbox" name="terms" id="terms" required>
                <span class="checkbox-label">
                    J'accepte les <a href="{{ route('terms.conditions') }}" target="_blank">conditions générales</a>
                    et la <a href="{{ route('privacy.policy') }}" target="_blank">politique de confidentialité</a>
                </span>
            </label>

            <button type="submit" class="btn-register" id="btnRegister">
                <span class="btn-text">Créer mon compte</span>
                <i class="fas fa-arrow-right"></i>
            </button>
        </form>

        <div class="form-footer">
            <p>Vous avez déjà un compte ? <a href="{{ route('login') }}">Se connecter</a></p>
            <p style="margin-top: 0.5rem;"><a href="{{ url('/') }}">← Retour au site</a></p>
        </div>
    </div>

    <script>
        function togglePassword(inputId, iconId) {
            const input = document.getElementById(inputId);
            const icon  = document.getElementById(iconId);
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.replace('fa-eye', 'fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.replace('fa-eye-slash', 'fa-eye');
            }
        }

        // Password strength
        document.getElementById('password').addEventListener('input', function() {
            const pw = this.value;
            const bar = document.getElementById('passwordStrength');
            const text = document.getElementById('strengthText');

            if (!pw.length) { bar.style.display = 'none'; return; }
            bar.style.display = 'block';

            let score = 0;
            if (pw.length >= 8) score++;
            if (/[a-z]/.test(pw) && /[A-Z]/.test(pw)) score++;
            if (/\d/.test(pw)) score++;
            if (/[^a-zA-Z0-9]/.test(pw)) score++;

            ['seg1','seg2','seg3','seg4'].forEach((id, i) => {
                const el = document.getElementById(id);
                el.className = 'strength-segment';
                if (i < score) {
                    el.classList.add(score <= 1 ? 'weak' : score <= 2 ? 'medium' : 'strong');
                }
            });

            text.textContent = ['Très faible', 'Faible', 'Moyen', 'Fort', 'Très fort'][score];
        });

        // Password confirmation
        document.getElementById('password_confirmation').addEventListener('input', function() {
            const err = document.getElementById('confirmError');
            err.style.display = (this.value && this.value !== document.getElementById('password').value)
                ? 'flex' : 'none';
        });

        // Form submit
        document.getElementById('registerForm').addEventListener('submit', function(e) {
            const pw  = document.getElementById('password').value;
            const pwc = document.getElementById('password_confirmation').value;
            if (pw !== pwc) {
                e.preventDefault();
                document.getElementById('confirmError').style.display = 'flex';
                return;
            }
            document.getElementById('btnRegister').classList.add('loading');
        });

        // Auto-hide alerts
        setTimeout(() => {
            document.querySelectorAll('.alert').forEach(a => {
                a.style.transition = 'all 0.3s ease';
                a.style.opacity = '0';
                a.style.transform = 'translateY(-10px)';
                setTimeout(() => a.remove(), 300);
            });
        }, 5000);
    </script>
</body>
</html>
