<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>BantuDelice — Connexion</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        html, body {
            height: 100%;
            font-family: 'Plus Jakarta Sans', Arial, sans-serif;
        }

        body {
            display: flex;
            min-height: 100vh;
            overflow: hidden;
        }

        /* ══════════════════════════════════════
           LEFT PANEL
        ══════════════════════════════════════ */
        .bl-left {
            width: 42%;
            flex-shrink: 0;
            background: #F5A41B;
            position: relative;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            padding: 28px 24px;
        }

        /* Wavy edge droite */
        .bl-left::after {
            content: '';
            position: absolute;
            top: 0; right: -44px; bottom: 0;
            width: 88px;
            background: #FAF8F4;
            border-radius: 60% 0 0 60%;
            z-index: 5;
            pointer-events: none;
        }

        /* Food orbs — cercles superposés comme la home */
        .bl-orbs {
            position: absolute;
            inset: 0;
            z-index: 2;
            pointer-events: none;
        }
        .bl-orb {
            position: absolute;
            border-radius: 50%;
            overflow: hidden;
            border: 4px solid rgba(255,255,255,0.85);
            box-shadow: 0 8px 32px rgba(0,0,0,0.18);
        }
        .bl-orb img { width: 100%; height: 100%; object-fit: cover; display: block; }
        .bl-o1 { width: 230px; height: 230px; top: 15%;  left: 10%; }
        .bl-o2 { width: 170px; height: 170px; top:  9%;  right: 10%; }
        .bl-o3 { width: 195px; height: 195px; bottom: 14%; left: 4%; }
        .bl-o4 { width: 150px; height: 150px; bottom: 20%; right:  8%; }

        /* Brand */
        .bl-brand {
            display: flex;
            align-items: center;
            gap: 9px;
            position: relative;
            z-index: 6;
        }
        .bl-brand img {
            height: 34px;
            width: auto;
            filter: brightness(0) invert(1);
        }
        .bl-brand-name {
            font-size: 14px;
            font-weight: 800;
            color: #fff;
            letter-spacing: 0.3px;
        }

        /* Tagline bas */
        .bl-tagline {
            position: absolute;
            bottom: 32px; left: 24px; right: 56px;
            z-index: 6;
            color: rgba(255,255,255,0.92);
            font-size: 12.5px;
            font-weight: 600;
            line-height: 1.55;
        }

        /* Zigzags left */
        .bl-zap { position: absolute; z-index: 6; line-height: 0; }

        /* ══════════════════════════════════════
           RIGHT PANEL
        ══════════════════════════════════════ */
        .bl-right {
            flex: 1;
            background: #FAF8F4;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 48px;
            position: relative;
            overflow: hidden;
        }

        /* Photos décoratives coins */
        .bl-deco {
            position: absolute;
            width: 180px;
            height: 180px;
            object-fit: cover;
            filter: grayscale(80%) opacity(0.35);
            pointer-events: none;
        }
        .bl-deco-tr {
            top: -20px; right: -20px;
            border-radius: 0 0 0 60%;
        }
        .bl-deco-br {
            bottom: -20px; right: -20px;
            border-radius: 60% 0 0 0;
        }

        /* Zigzags right */
        .bl-zap-r { position: absolute; line-height: 0; }

        /* Card */
        .bl-card {
            width: 100%;
            max-width: 390px;
            position: relative;
            z-index: 2;
        }

        .bl-card h2 {
            font-size: 27px;
            font-weight: 800;
            color: #1a1a1a;
            text-transform: uppercase;
            letter-spacing: -0.3px;
            margin-bottom: 24px;
        }

        /* Alerts */
        .bl-alert {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 11px 15px;
            border-radius: 10px;
            font-size: 13px;
            margin-bottom: 14px;
        }
        .bl-alert.error   { background: #fdecea; color: #c0392b; }
        .bl-alert.success { background: #e8f7ee; color: #1a7a40; }

        /* Inputs */
        .bl-field { margin-bottom: 13px; }
        .bl-field input {
            width: 100%;
            background: #EDECEB;
            border: none;
            border-radius: 12px;
            padding: 14px 18px;
            font-size: 14px;
            color: #1a1a1a;
            outline: none;
            font-family: inherit;
            transition: background .2s, box-shadow .2s;
        }
        .bl-field input::placeholder { color: #aaa; }
        .bl-field input:focus {
            background: #e5e4e2;
            box-shadow: 0 0 0 3px rgba(0,149,67,.18);
        }
        .bl-field-err { font-size: 12px; color: #c0392b; margin-top: 4px; padding-left: 4px; }

        /* Password */
        .bl-pw { position: relative; }
        .bl-pw input { padding-right: 46px; }
        .bl-pw-btn {
            position: absolute; right: 13px; top: 50%;
            transform: translateY(-50%);
            background: none; border: none;
            cursor: pointer; color: #bbb; font-size: 14px;
            transition: color .2s;
        }
        .bl-pw-btn:hover { color: #666; }

        /* Options */
        .bl-options {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 18px;
        }
        .bl-remember { display: flex; align-items: center; gap: 7px; }
        .bl-remember input { width: 15px; height: 15px; accent-color: #009543; cursor: pointer; }
        .bl-remember label { font-size: 13px; color: #555; cursor: pointer; }
        .bl-forgot { font-size: 13px; color: #009543; text-decoration: none; font-weight: 500; }
        .bl-forgot:hover { text-decoration: underline; }

        /* Bouton — centré, pas full-width (écart #4) */
        .bl-btn-wrap { text-align: center; margin-bottom: 0; }
        .bl-btn-submit {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 13px 44px;
            background: #009543;
            color: #fff;
            border: none;
            border-radius: 12px;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
            font-family: inherit;
            transition: background .2s, transform .15s, box-shadow .2s;
            position: relative;
            overflow: hidden;
        }
        .bl-btn-submit:hover {
            background: #007836;
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(0,149,67,.28);
        }
        .bl-btn-submit:active { transform: translateY(0); }
        .bl-btn-submit.loading { opacity: .75; pointer-events: none; }
        .bl-btn-submit.loading .btn-text { visibility: hidden; }
        .bl-btn-submit.loading::after {
            content: '';
            position: absolute;
            width: 18px; height: 18px;
            border: 2px solid #fff;
            border-top-color: transparent;
            border-radius: 50%;
            animation: bl-spin .7s linear infinite;
        }
        @keyframes bl-spin { to { transform: rotate(360deg); } }

        /* Diviseur lignes vertes (écart #5) */
        .bl-divider {
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 18px 0;
            color: #aaa;
            font-size: 13px;
        }
        .bl-divider::before, .bl-divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: #009543;
            opacity: 0.4;
        }

        /* Social */
        .bl-social { display: flex; gap: 12px; margin-bottom: 18px; }
        .bl-btn-soc {
            flex: 1;
            display: flex; align-items: center; justify-content: center; gap: 8px;
            padding: 12px; border-radius: 12px;
            border: 1.5px solid #d9d8d6; background: #fff;
            color: #1a1a1a; font-size: 13px; font-weight: 600;
            text-decoration: none; font-family: inherit; cursor: pointer;
            transition: border-color .2s, box-shadow .2s;
        }
        .bl-btn-soc:hover { border-color: #bbb; box-shadow: 0 2px 8px rgba(0,0,0,.08); }
        .bl-btn-soc .fab { font-size: 16px; }
        .bl-btn-soc.google   .fab { color: #DB4437; }
        .bl-btn-soc.facebook .fab { color: #1877F2; }
        .bl-soc-off { opacity: .5; cursor: not-allowed; pointer-events: none; }

        /* Footer */
        .bl-footer {
            text-align: center;
            font-size: 12.5px;
            color: #888;
            padding-top: 14px;
            border-top: 1px solid #e8e6e3;
            line-height: 1.9;
        }
        .bl-footer a { color: #009543; font-weight: 600; text-decoration: none; }
        .bl-footer a:hover { text-decoration: underline; }

        /* ══════════════════════════════════════
           RESPONSIVE
        ══════════════════════════════════════ */
        @media (max-width: 900px) {
            .bl-left { display: none; }
            .bl-right { padding: 32px 20px; }
            .bl-deco { display: none; }
        }
        @media (max-width: 480px) {
            .bl-social { flex-direction: column; }
            .bl-card h2 { font-size: 22px; }
        }
    </style>
</head>
<body>

{{-- ══════════════ LEFT PANEL ══════════════ --}}
<div class="bl-left">

    {{-- Food orbs — 4 cercles superposés, pattern identique à la section home --}}
    <div class="bl-orbs">
        <div class="bl-orb bl-o1">
            <img src="{{ asset('frontend/images/g4.jpg') }}" alt=""
                 onerror="this.src='{{ asset('images/home/service-restaurant.jpg') }}'">
        </div>
        <div class="bl-orb bl-o2">
            <img src="{{ asset('frontend/images/g5.jpg') }}" alt=""
                 onerror="this.src='{{ asset('images/home/service-driver.jpg') }}'">
        </div>
        <div class="bl-orb bl-o3">
            <img src="{{ asset('frontend/images/g1.jpg') }}" alt=""
                 onerror="this.src='{{ asset('images/home/service-cuisine.jpg') }}'">
        </div>
        <div class="bl-orb bl-o4">
            <img src="{{ asset('frontend/images/g3.jpg') }}" alt=""
                 onerror="this.src='{{ asset('images/home/service-restaurant.jpg') }}'">
        </div>
    </div>

    <div class="bl-brand">
        <img src="{{ asset('frontend/images/BuntuDelice.png') }}" alt="BantuDelice">
        <span class="bl-brand-name">BantuDelice</span>
    </div>

    <div class="bl-tagline">La cuisine congolaise<br>livrée chez vous</div>

    {{-- Zigzag top-right --}}
    <div class="bl-zap" style="top:14px; right:58px;">
        <svg width="20" height="20" viewBox="0 0 20 20" fill="none" aria-hidden="true">
            <polyline points="3,2 10,10 3,18" stroke="rgba(0,0,0,0.28)" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/>
            <polyline points="10,2 17,10 10,18" stroke="rgba(0,0,0,0.28)" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
    </div>
    {{-- Zigzag bas-gauche --}}
    <div class="bl-zap" style="bottom:70px; left:14px;">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <polyline points="4,3 12,12 4,21" stroke="rgba(0,0,0,0.22)" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/>
            <polyline points="12,3 20,12 12,21" stroke="rgba(0,0,0,0.22)" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
    </div>

</div>

{{-- ══════════════ RIGHT PANEL ══════════════ --}}
<div class="bl-right">

    {{-- Photos décoratives coins (écart #3) --}}
    <img class="bl-deco bl-deco-tr"
         src="{{ asset('frontend/images/g6.jpg') }}" alt=""
         onerror="this.src='{{ asset('images/home/service-restaurant.jpg') }}'">
    <img class="bl-deco bl-deco-br"
         src="{{ asset('frontend/images/g7.jpg') }}" alt=""
         onerror="this.src='{{ asset('images/home/service-cuisine.jpg') }}'">

    {{-- Zigzags panel droit --}}
    <div class="bl-zap-r" style="top:18px; right:210px;">
        <svg width="18" height="18" viewBox="0 0 20 20" fill="none" aria-hidden="true">
            <polyline points="3,2 10,10 3,18" stroke="#1a1a1a" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/>
            <polyline points="10,2 17,10 10,18" stroke="#1a1a1a" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
    </div>
    <div class="bl-zap-r" style="top:130px; left:52px;">
        <svg width="16" height="16" viewBox="0 0 20 20" fill="none" aria-hidden="true">
            <polyline points="4,2 10,10 4,18" stroke="#1a1a1a" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
    </div>
    <div class="bl-zap-r" style="bottom:30px; left:48px;">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <polyline points="4,3 12,12 4,21" stroke="#1a1a1a" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/>
            <polyline points="12,3 20,12 12,21" stroke="#1a1a1a" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
    </div>
    {{-- Zigzag orange milieu-droit --}}
    <div class="bl-zap-r" style="top:45%; right:32px;">
        <svg width="20" height="20" viewBox="0 0 20 20" fill="none" aria-hidden="true">
            <polyline points="3,2 10,10 3,18" stroke="#F5A41B" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
    </div>
    <div class="bl-zap-r" style="bottom:80px; right:54px;">
        <svg width="26" height="18" viewBox="0 0 26 18" fill="none" aria-hidden="true">
            <polyline points="2,2 9,9 2,16"  stroke="#F5A41B" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/>
            <polyline points="11,2 18,9 11,16" stroke="#F5A41B" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
    </div>

    <div class="bl-card">
        <h2>Bienvenue !</h2>

        {{-- Alertes session --}}
        @if(isset($errors) && $errors->any())
            <div class="bl-alert error">
                <i class="fas fa-exclamation-circle"></i>
                <span>{{ $errors->first() }}</span>
            </div>
        @endif

        @if(session('alert'))
            @php
                $alert = session('alert');
                $alertType    = is_array($alert) && isset($alert['type'])    ? $alert['type']    : 'danger';
                $alertMessage = is_array($alert) && isset($alert['message']) ? $alert['message'] : (is_array($alert) && isset($alert['heading']) ? $alert['heading'] : 'Une erreur est survenue');
            @endphp
            <div class="bl-alert {{ $alertType === 'danger' ? 'error' : 'success' }}">
                <i class="fas {{ $alertType === 'danger' ? 'fa-exclamation-circle' : 'fa-check-circle' }}"></i>
                <span>{{ $alertMessage }}</span>
            </div>
        @endif

        @if(session('success'))
            <div class="bl-alert success">
                <i class="fas fa-check-circle"></i>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        {{-- Formulaire — action/csrf/champs inchangés --}}
        <form method="post" action="{{ url('login') }}" id="loginForm">
            @csrf

            <div class="bl-field">
                <input type="text"
                       id="identifier"
                       name="identifier"
                       value="{{ old('identifier') }}"
                       placeholder="Email, +242 06… ou nom d'utilisateur"
                       required
                       autocomplete="username"
                       autofocus>
                @error('identifier')
                    <div class="bl-field-err"><i class="fas fa-exclamation-circle"></i> {{ $message }}</div>
                @enderror
            </div>

            <div class="bl-field">
                <div class="bl-pw">
                    <input type="password"
                           id="password"
                           name="password"
                           placeholder="Mot de passe"
                           required
                           autocomplete="current-password">
                    <button type="button" class="bl-pw-btn" onclick="togglePassword()" aria-label="Afficher le mot de passe">
                        <i class="fas fa-eye" id="toggleIcon"></i>
                    </button>
                </div>
            </div>

            <div class="bl-options">
                <div class="bl-remember">
                    <input type="checkbox" name="remember" id="remember">
                    <label for="remember">Se souvenir de moi</label>
                </div>
                <a href="{{ url('password/reset') }}" class="bl-forgot">Mot de passe oublié ?</a>
            </div>

            {{-- Bouton centré, pas full-width (écart #4) --}}
            <div class="bl-btn-wrap">
                <button type="submit" class="bl-btn-submit" id="btnLogin">
                    <span class="btn-text">Se connecter</span>
                    <i class="fas fa-arrow-right"></i>
                </button>
            </div>
        </form>

        {{-- Diviseur lignes vertes (écart #5) --}}
        <div class="bl-divider"><span>Ou</span></div>

        {{-- Social --}}
        <div class="bl-social">
            <a class="bl-btn-soc google bl-soc-off" href="#" title="Bientôt disponible">
                <i class="fab fa-google"></i> Google
            </a>
            <a class="bl-btn-soc facebook bl-soc-off" href="#" title="Bientôt disponible">
                <i class="fab fa-facebook-f"></i> Facebook
            </a>
        </div>

        {{-- Footer --}}
        <div class="bl-footer">
            <p>Pas encore de compte ?
                <a href="{{ url('/partner/registration') }}">Devenir partenaire</a> —
                <a href="{{ route('user.signup') }}">Créer un compte client</a>
            </p>
            <p><a href="{{ url('/') }}">← Retour au site</a></p>
        </div>
    </div>

</div>

<script>
    function togglePassword() {
        const input = document.getElementById('password');
        const icon  = document.getElementById('toggleIcon');
        const show  = input.type === 'password';
        input.type  = show ? 'text' : 'password';
        icon.classList.toggle('fa-eye',       !show);
        icon.classList.toggle('fa-eye-slash',  show);
    }

    document.getElementById('loginForm').addEventListener('submit', function() {
        document.getElementById('btnLogin').classList.add('loading');
    });

    setTimeout(() => {
        document.querySelectorAll('.bl-alert').forEach(el => {
            el.style.transition = 'opacity .3s, transform .3s';
            el.style.opacity    = '0';
            el.style.transform  = 'translateY(-8px)';
            setTimeout(() => el.remove(), 300);
        });
    }, 5000);
</script>
</body>
</html>
