@extends('layouts.admin-modern')
@section('title', 'Configurer le 2FA | Admin')
@section('nav_active', 'profile')

@section('content')
<div style="max-width:560px;margin:0 auto;padding:24px 16px 80px;">

    <div style="margin-bottom:24px;">
        <h1 style="font-size:1.4rem;font-weight:700;color:#111827;margin-bottom:4px;">
            <i class="fas fa-shield-halved" style="color:#007836;margin-right:8px;"></i>
            Activer la double authentification
        </h1>
        <p style="color:#6b7280;font-size:.88rem;">Sécurisez votre compte admin avec une application TOTP.</p>
    </div>

    {{-- Étapes --}}
    <div style="background:#fff;border:1px solid #e5e7eb;border-radius:16px;padding:28px 24px;margin-bottom:20px;">

        <div style="margin-bottom:24px;">
            <div style="font-weight:700;color:#111827;margin-bottom:8px;">
                <span style="background:#007836;color:#fff;border-radius:50%;width:22px;height:22px;display:inline-flex;align-items:center;justify-content:center;font-size:.75rem;margin-right:8px;">1</span>
                Installez une application d'authentification
            </div>
            <p style="font-size:.84rem;color:#6b7280;margin-left:30px;">
                Google Authenticator (<a href="https://play.google.com/store/apps/details?id=com.google.android.apps.authenticator2" target="_blank" style="color:#007836;">Android</a> /
                <a href="https://apps.apple.com/app/google-authenticator/id388497605" target="_blank" style="color:#007836;">iOS</a>) ou Authy.
            </p>
        </div>

        <div style="margin-bottom:24px;">
            <div style="font-weight:700;color:#111827;margin-bottom:12px;">
                <span style="background:#007836;color:#fff;border-radius:50%;width:22px;height:22px;display:inline-flex;align-items:center;justify-content:center;font-size:.75rem;margin-right:8px;">2</span>
                Scannez ce QR code
            </div>
            <div style="display:flex;align-items:flex-start;gap:20px;margin-left:30px;flex-wrap:wrap;">
                {{-- QR généré côté serveur — aucune donnée envoyée à un tiers --}}
                <img
                    src="data:image/svg+xml;base64,{{ $qrSvg }}"
                    alt="QR Code 2FA"
                    style="border:6px solid #fff;box-shadow:0 2px 12px rgba(0,0,0,.1);border-radius:8px;flex-shrink:0;"
                    width="200" height="200"
                >
                <div>
                    <p style="font-size:.82rem;color:#6b7280;margin-bottom:8px;">
                        Impossible de scanner ? Saisissez ce code manuellement dans l'application :
                    </p>
                    <code style="display:block;background:#f1f5f9;border:1px solid #e2e8f0;border-radius:8px;padding:10px 14px;font-size:1rem;letter-spacing:.12em;font-family:monospace;word-break:break-all;color:#111827;">{{ $secret }}</code>
                    <button onclick="navigator.clipboard.writeText('{{ $secret }}').then(()=>this.textContent='✓ Copié')"
                        style="margin-top:6px;font-size:.75rem;color:#007836;background:none;border:none;cursor:pointer;padding:0;">
                        <i class="fas fa-copy"></i> Copier
                    </button>
                </div>
            </div>
        </div>

        <div>
            <div style="font-weight:700;color:#111827;margin-bottom:10px;">
                <span style="background:#007836;color:#fff;border-radius:50%;width:22px;height:22px;display:inline-flex;align-items:center;justify-content:center;font-size:.75rem;margin-right:8px;">3</span>
                Confirmez avec le code affiché
            </div>
            <form method="POST" action="{{ route('admin.2fa.enable') }}" style="margin-left:30px;">
                @csrf
                <div style="display:flex;gap:10px;align-items:flex-start;flex-wrap:wrap;">
                    <div style="flex:1;min-width:160px;">
                        <input
                            type="text"
                            name="code"
                            placeholder="000000"
                            maxlength="6"
                            inputmode="numeric"
                            autocomplete="one-time-code"
                            autofocus
                            required
                            style="width:100%;padding:11px 14px;border:2px solid {{ $errors->has('code') ? '#dc2626' : '#e5e7eb' }};border-radius:10px;font-size:1.1rem;font-weight:700;letter-spacing:.3em;text-align:center;outline:none;"
                        >
                        @error('code')
                            <p style="color:#dc2626;font-size:.78rem;margin-top:4px;">{{ $message }}</p>
                        @enderror
                    </div>
                    <button type="submit"
                        style="padding:11px 20px;background:#007836;color:#fff;border:none;border-radius:10px;font-size:.9rem;font-weight:600;cursor:pointer;white-space:nowrap;">
                        <i class="fas fa-lock"></i> Activer le 2FA
                    </button>
                </div>
            </form>
        </div>

    </div>

    <div style="text-align:center;">
        <a href="{{ route('admin.profile') }}" style="font-size:.82rem;color:#6b7280;text-decoration:none;">
            <i class="fas fa-arrow-left"></i> Annuler et retourner au profil
        </a>
    </div>

</div>
@endsection
