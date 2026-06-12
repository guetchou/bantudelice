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

@section('style')
<style>
/* ── BantuDelice Auth Shell ─────────────────────────────────── */
* { box-sizing: border-box; margin: 0; padding: 0; }

.bdl-auth {
    display: flex;
    min-height: 100vh;
    font-family: 'Plus Jakarta Sans', 'Outfit', sans-serif;
}

/* ── LEFT PANEL ─────────────────────────────────────────────── */
.bdl-left {
    width: 44%;
    flex-shrink: 0;
    background: #F5A41B;
    position: relative;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    padding: 32px 28px 28px;
}

/* Wavy edge right side */
.bdl-left::after {
    content: '';
    position: absolute;
    top: 0; right: -44px; bottom: 0;
    width: 88px;
    background: #FAF8F4;
    border-radius: 60% 0 0 60%;
    z-index: 5;
    pointer-events: none;
}

/* Brand logo */
.bdl-brand {
    display: flex;
    align-items: center;
    gap: 10px;
    position: relative;
    z-index: 6;
}
.bdl-brand img {
    height: 36px;
    width: auto;
    filter: brightness(0) invert(1);
}
.bdl-brand-name {
    font-size: 14px;
    font-weight: 700;
    color: #fff;
    letter-spacing: 0.5px;
}

/* Food orbs — same as home page */
.bdl-orbs {
    position: absolute;
    inset: 0;
    z-index: 2;
}
.bdl-orb {
    position: absolute;
    border-radius: 50%;
    overflow: hidden;
    border: 4px solid rgba(255,255,255,0.85);
    box-shadow: 0 8px 32px rgba(0,0,0,0.18);
    transition: transform 0.4s ease;
}
.bdl-orb:hover { transform: scale(1.04); }
.bdl-orb img  { width: 100%; height: 100%; object-fit: cover; display: block; }

/* Positions identiques à .fo-* de modern.css, adaptées à la hauteur 100vh */
.bdl-o1 { width: 230px; height: 230px; top: 15%;  left: 14%; }
.bdl-o2 { width: 165px; height: 165px; top: 12%;  right: 8%; }
.bdl-o3 { width: 190px; height: 190px; bottom: 14%; left: 4%; }
.bdl-o4 { width: 148px; height: 148px; bottom: 18%; right: 10%; }

/* Tagline bas */
.bdl-tagline {
    position: absolute;
    bottom: 36px;
    left: 32px;
    right: 60px;
    z-index: 6;
    color: rgba(255,255,255,0.92);
    font-size: 13px;
    font-weight: 600;
    letter-spacing: 0.2px;
    line-height: 1.5;
}

/* Zigzag decorative marks */
.bdl-zap {
    position: absolute;
    z-index: 6;
}
.bdl-zap svg { display: block; }

/* ── RIGHT PANEL ────────────────────────────────────────────── */
.bdl-right {
    flex: 1;
    background: #FAF8F4;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 40px 32px;
    position: relative;
    overflow: hidden;
}

/* Zigzags on right */
.bdl-zap-r { position: absolute; }

.bdl-card {
    width: 100%;
    max-width: 420px;
    position: relative;
    z-index: 2;
}

.bdl-card h2 {
    font-size: 28px;
    font-weight: 800;
    color: #1a1a1a;
    letter-spacing: -0.5px;
    margin-bottom: 28px;
    text-transform: uppercase;
}

/* Inputs */
.bdl-field {
    margin-bottom: 14px;
}
.bdl-field input {
    width: 100%;
    background: #EDECEB;
    border: none;
    border-radius: 12px;
    padding: 15px 18px;
    font-size: 14px;
    color: #1a1a1a;
    outline: none;
    font-family: inherit;
    transition: background 0.2s, box-shadow 0.2s;
}
.bdl-field input::placeholder { color: #999; }
.bdl-field input:focus {
    background: #e5e4e2;
    box-shadow: 0 0 0 3px rgba(125, 196, 67, 0.2);
}
.bdl-field-pw {
    position: relative;
}
.bdl-field-pw input { padding-right: 48px; }
.bdl-pw-toggle {
    position: absolute;
    right: 14px;
    top: 50%;
    transform: translateY(-50%);
    background: none;
    border: none;
    cursor: pointer;
    color: #999;
    padding: 4px;
    font-size: 15px;
    line-height: 1;
    transition: color 0.2s;
}
.bdl-pw-toggle:hover { color: #555; }

/* Forgot link */
.bdl-forgot-row {
    display: flex;
    justify-content: flex-end;
    margin-bottom: 20px;
}
.bdl-forgot {
    font-size: 13px;
    color: #7DC443;
    text-decoration: none;
    font-weight: 500;
    transition: color 0.2s;
}
.bdl-forgot:hover { color: #5a9e2e; text-decoration: underline; }

/* Remember row */
.bdl-remember {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 18px;
}
.bdl-remember input[type="checkbox"] {
    width: 16px; height: 16px;
    accent-color: #7DC443;
    cursor: pointer;
}
.bdl-remember label {
    font-size: 13px;
    color: #555;
    cursor: pointer;
}

/* Submit button */
.bdl-btn-submit {
    width: 100%;
    background: #7DC443;
    color: #fff;
    border: none;
    border-radius: 12px;
    padding: 15px;
    font-size: 15px;
    font-weight: 700;
    cursor: pointer;
    font-family: inherit;
    transition: background 0.2s, transform 0.15s, box-shadow 0.2s;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    letter-spacing: 0.3px;
}
.bdl-btn-submit:hover {
    background: #5a9e2e;
    transform: translateY(-1px);
    box-shadow: 0 6px 20px rgba(125,196,67,0.35);
}
.bdl-btn-submit:active { transform: translateY(0); }
.bdl-btn-submit.loading { opacity: 0.7; pointer-events: none; }

/* Divider */
.bdl-divider {
    display: flex;
    align-items: center;
    gap: 12px;
    margin: 20px 0;
    color: #aaa;
    font-size: 13px;
}
.bdl-divider::before, .bdl-divider::after {
    content: '';
    flex: 1;
    height: 1px;
    background: #d9d8d6;
}

/* Social buttons */
.bdl-social-row {
    display: flex;
    gap: 12px;
    margin-bottom: 24px;
}
.bdl-btn-social {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 12px;
    border-radius: 12px;
    border: 1.5px solid #d9d8d6;
    background: #fff;
    color: #1a1a1a;
    font-size: 13px;
    font-weight: 600;
    text-decoration: none;
    font-family: inherit;
    cursor: pointer;
    transition: border-color 0.2s, box-shadow 0.2s;
}
.bdl-btn-social:hover { border-color: #bbb; box-shadow: 0 2px 8px rgba(0,0,0,0.08); }
.bdl-btn-social .fab { font-size: 16px; }
.bdl-btn-social.google .fab { color: #DB4437; }
.bdl-btn-social.facebook .fab { color: #1877F2; }
.bdl-social-disabled { opacity: 0.55; cursor: not-allowed; pointer-events: none; }

/* Alerts */
.bdl-alert {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 12px 16px;
    border-radius: 10px;
    font-size: 13px;
    margin-bottom: 16px;
}
.bdl-alert.success { background: #e8f7ee; color: #1a7a40; }
.bdl-alert.error   { background: #fdecea; color: #c0392b; }
.bdl-field-error {
    font-size: 12px;
    color: #c0392b;
    margin-top: 5px;
    padding-left: 4px;
    display: flex;
    align-items: center;
    gap: 4px;
}

/* Footer */
.bdl-footer {
    text-align: center;
    font-size: 13px;
    color: #888;
}
.bdl-footer a {
    color: #7DC443;
    font-weight: 600;
    text-decoration: none;
}
.bdl-footer a:hover { text-decoration: underline; }

/* Trust badges */
.bdl-trust {
    display: flex;
    gap: 16px;
    justify-content: center;
    margin-bottom: 16px;
}
.bdl-trust-item {
    display: flex;
    align-items: center;
    gap: 5px;
    font-size: 11px;
    color: #aaa;
}
.bdl-trust-item i { font-size: 12px; color: #7DC443; }

/* ── RESPONSIVE ─────────────────────────────────────────────── */
@media (max-width: 900px) {
    .bdl-left { display: none; }
    .bdl-right { padding: 32px 20px; }
    .bdl-card { max-width: 100%; }
}
@media (max-width: 480px) {
    .bdl-card h2 { font-size: 22px; }
    .bdl-social-row { flex-direction: column; }
}
</style>
@endsection

@section('content')
<div class="bdl-auth">

    {{-- ══════════════ LEFT PANEL ══════════════ --}}
    <div class="bdl-left">

        {{-- Logo --}}
        <div class="bdl-brand" style="position:relative;z-index:6;">
            <img src="{{ asset('frontend/images/BuntuDelice.png') }}" alt="{{ $authBrand['name'] }}">
            <span class="bdl-brand-name">{{ $authBrand['name'] }}</span>
        </div>

        {{-- Food orbs — mêmes images que la section "Notre Cuisine" de la home --}}
        <div class="bdl-orbs" aria-hidden="true">
            <div class="bdl-orb bdl-o1">
                <img src="{{ asset('frontend/images/g4.jpg') }}" alt="Plat cuisiné"
                     onerror="this.src='{{ asset('images/home/service-restaurant.jpg') }}'">
            </div>
            <div class="bdl-orb bdl-o2">
                <img src="{{ asset('frontend/images/g5.jpg') }}" alt="Sélection du jour"
                     onerror="this.src='{{ asset('images/home/service-driver.jpg') }}'">
            </div>
            <div class="bdl-orb bdl-o3">
                <img src="{{ asset('frontend/images/g1.jpg') }}" alt="Table garnie"
                     onerror="this.src='{{ asset('images/home/service-restaurant.jpg') }}'">
            </div>
            <div class="bdl-orb bdl-o4">
                <img src="{{ asset('frontend/images/g3.jpg') }}" alt="Pain traditionnel"
                     onerror="this.src='{{ asset('images/home/service-cuisine.jpg') }}'">
            </div>
        </div>

        {{-- Tagline --}}
        <div class="bdl-tagline">
            La cuisine congolaise<br>livrée chez vous
        </div>

        {{-- Zigzag top-right --}}
        <div class="bdl-zap" style="top:16px; right:60px;">
            <svg width="22" height="22" viewBox="0 0 22 22" fill="none" aria-hidden="true">
                <polyline points="4,2 12,11 4,20" stroke="rgba(0,0,0,0.35)" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
                <polyline points="11,2 19,11 11,20" stroke="rgba(0,0,0,0.35)" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </div>

        {{-- Zigzag bottom-left --}}
        <div class="bdl-zap" style="bottom:80px; left:20px;">
            <svg width="28" height="28" viewBox="0 0 28 28" fill="none" aria-hidden="true">
                <polyline points="5,3 14,14 5,25" stroke="rgba(0,0,0,0.3)" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
                <polyline points="14,3 23,14 14,25" stroke="rgba(0,0,0,0.3)" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </div>

    </div>

    {{-- ══════════════ RIGHT PANEL ══════════════ --}}
    <div class="bdl-right">

        {{-- Zigzag decoration top --}}
        <div class="bdl-zap-r" style="top:20px; right:60px;">
            <svg width="20" height="20" viewBox="0 0 22 22" fill="none" aria-hidden="true">
                <polyline points="4,2 12,11 4,20" stroke="#1a1a1a" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
                <polyline points="11,2 19,11 11,20" stroke="#1a1a1a" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </div>

        {{-- Zigzag decoration bottom-right --}}
        <div class="bdl-zap-r" style="bottom:28px; right:48px;">
            <svg width="34" height="22" viewBox="0 0 34 22" fill="none" aria-hidden="true">
                <polyline points="2,2 10,11 2,20" stroke="#F5A41B" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
                <polyline points="13,2 21,11 13,20" stroke="#F5A41B" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </div>

        {{-- Zigzag bottom-left --}}
        <div class="bdl-zap-r" style="bottom:60px; left:32px;">
            <svg width="22" height="22" viewBox="0 0 22 22" fill="none" aria-hidden="true">
                <polyline points="4,2 12,11 4,20" stroke="#1a1a1a" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </div>

        <div class="bdl-card">
            <h2>Bienvenue !</h2>

            {{-- Alert Messages --}}
            @if(Session::has('message'))
                <div class="bdl-alert success">
                    <i class="fas fa-check-circle"></i>
                    <span>{{ Session::get('message') }}</span>
                </div>
            @endif

            @if($errors->any())
                <div class="bdl-alert error">
                    <i class="fas fa-exclamation-circle"></i>
                    <span>{{ $errors->first() }}</span>
                </div>
            @endif

            {{-- Login Form --}}
            <form method="post" action="{{ url('login') }}" id="loginForm">
                @csrf
                @if($socialRedirect)
                    <input type="hidden" name="redirect" value="{{ $socialRedirect }}">
                @endif

                <div class="bdl-field">
                    <input type="text"
                           name="identifier"
                           id="identifier"
                           value="{{ old('identifier') }}"
                           placeholder="E-mail ou +242 06..."
                           required
                           autocomplete="username">
                    @if($errors->has('identifier'))
                        <div class="bdl-field-error"><i class="fas fa-exclamation-circle"></i>{{ $errors->first('identifier') }}</div>
                    @endif
                </div>

                <div class="bdl-field">
                    <div class="bdl-field-pw">
                        <input type="password"
                               name="password"
                               id="password"
                               placeholder="Mot de passe"
                               required
                               autocomplete="current-password">
                        <button type="button" class="bdl-pw-toggle" onclick="togglePassword()" aria-label="Afficher le mot de passe">
                            <i class="fas fa-eye" id="toggleIcon"></i>
                        </button>
                    </div>
                    @if($errors->has('password'))
                        <div class="bdl-field-error"><i class="fas fa-exclamation-circle"></i>{{ $errors->first('password') }}</div>
                    @endif
                </div>

                <div class="bdl-forgot-row">
                    <a href="{{ route('user.forgot', array_filter(['redirect' => $socialRedirect])) }}" class="bdl-forgot">
                        Mot de passe oublié ?
                    </a>
                </div>

                <div class="bdl-remember">
                    <input type="checkbox" name="remember" id="remember">
                    <label for="remember">Se souvenir de moi</label>
                </div>

                <button type="submit" class="bdl-btn-submit" id="submitBtn">
                    Se connecter
                </button>
            </form>

            {{-- Social Auth — toujours affiché, fonctionnel quand credentials configurés --}}
            <div class="bdl-divider"><span>Ou</span></div>
            <div class="bdl-social-row">
                <a class="bdl-btn-social google{{ $googleAuthEnabled ? '' : ' bdl-social-disabled' }}"
                   href="{{ $googleAuthEnabled ? route('auth.social.redirect', ['provider' => 'google', 'redirect' => $socialRedirect]) : '#' }}"
                   @if(!$googleAuthEnabled) title="Connexion Google bientôt disponible" @endif>
                    <i class="fab fa-google"></i> Google
                </a>
                <a class="bdl-btn-social facebook{{ $facebookAuthEnabled ? '' : ' bdl-social-disabled' }}"
                   href="{{ $facebookAuthEnabled ? route('auth.social.redirect', ['provider' => 'facebook', 'redirect' => $socialRedirect]) : '#' }}"
                   @if(!$facebookAuthEnabled) title="Connexion Facebook bientôt disponible" @endif>
                    <i class="fab fa-facebook-f"></i> Facebook
                </a>
            </div>

            <div class="bdl-trust">
                <div class="bdl-trust-item"><i class="fas fa-lock"></i> SSL sécurisé</div>
                <div class="bdl-trust-item"><i class="fas fa-shield-alt"></i> Données protégées</div>
            </div>

            <div class="bdl-footer">
                <p>Pas encore de compte ? <a href="{{ route('user.signup', array_filter(['redirect' => $socialRedirect])) }}">Créer un compte</a></p>
            </div>
        </div>
    </div>

</div>
@endsection

@section('script')
<script>
function togglePassword() {
    const input = document.getElementById('password');
    const icon  = document.getElementById('toggleIcon');
    const show  = input.type === 'password';
    input.type  = show ? 'text' : 'password';
    icon.classList.toggle('fa-eye',      !show);
    icon.classList.toggle('fa-eye-slash', show);
}

document.getElementById('loginForm').addEventListener('submit', function() {
    document.getElementById('submitBtn').classList.add('loading');
});

// Auto-dismiss alerts
setTimeout(() => {
    document.querySelectorAll('.bdl-alert').forEach(el => {
        el.style.transition = 'opacity 0.3s, transform 0.3s';
        el.style.opacity    = '0';
        el.style.transform  = 'translateY(-8px)';
        setTimeout(() => el.remove(), 300);
    });
}, 5000);

// Green border on identifier focus
document.getElementById('identifier').addEventListener('input', function() {
    this.style.boxShadow = this.value.trim()
        ? '0 0 0 3px rgba(125,196,67,0.25)'
        : '';
});
</script>
@endsection
