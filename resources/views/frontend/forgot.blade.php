@php
    $authBrand = app(\App\Services\AuthBrandingService::class)->resolve(request());
    $redirectTarget = request()->query('redirect');
@endphp
@extends('frontend.layouts.app-modern')
@section('hide_primary_chrome', '1')
@section('title', $authBrand['forgot_title'])
@section('description', $authBrand['forgot_description'])
@section('body_class', 'bd-auth-page bd-auth-forgot')
@section('body_style', '--auth-brand-primary: ' . $authBrand['primary'] . '; --auth-brand-primary-dark: ' . $authBrand['primary_dark'] . '; --auth-brand-primary-soft: ' . $authBrand['primary_soft'] . '; --auth-brand-secondary: ' . $authBrand['secondary'] . '; --auth-brand-surface: ' . $authBrand['surface'] . ';')

@section('content')
<section class="section forgot-page-shell">
    <div class="container">
        <div class="forgot-shell">
            <!-- Header -->
            <div class="text-center forgot-header">
                <div class="auth-brand-pill auth-brand-pill--center">
                    <span>{{ $authBrand['name'] }}</span>
                    <span class="auth-brand-pill__dot"></span>
                    <span>{{ $authBrand['label'] }}</span>
                </div>
                <div class="forgot-icon-wrap">
                    <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" fill="white" viewBox="0 0 24 24">
                        <path d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zM9 6c0-1.66 1.34-3 3-3s3 1.34 3 3v2H9V6zm9 14H6V10h12v10zm-6-3c1.1 0 2-.9 2-2s-.9-2-2-2-2 .9-2 2 .9 2 2 2z"/>
                    </svg>
                </div>
                <h1 class="forgot-title">Mot de passe oublié ?</h1>
                <p class="forgot-subtitle">Pas de panique. Renseignez vos informations pour réinitialiser votre accès à {{ $authBrand['name'] }}.</p>
            </div>
            
            <!-- Alert Messages -->
            @if(Session::has('message'))
                <div class="forgot-alert forgot-alert--success">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/>
                    </svg>
                    <strong>{{ Session::get('message') }}</strong>
                </div>
            @endif
            
            @if(session()->has('error'))
                <div class="forgot-alert forgot-alert--error">
                    <strong>{{ session()->get('error') }}</strong>
                </div>
            @endif
            
            <!-- Reset Form -->
            <div class="forgot-panel">
                <form method="post" action="{{ route('forgot') }}">
                    @csrf
                    
                    <!-- Email -->
                    <div class="forgot-field">
                        <label class="forgot-label">
                            <i class="fas fa-envelope forgot-label__icon"></i>
                            Adresse email
                        </label>
                        <input type="email" name="email" value="{{ old('email') }}" 
                               placeholder="votre@email.com"
                               class="forgot-input"
                               required>
                        @if($errors->has('email'))
                            <span class="forgot-error">
                                {{ $errors->first('email') }}
                            </span>
                        @endif
                    </div>
                    
                    <!-- Phone -->
                    <div class="forgot-field">
                        <label class="forgot-label">
                            <i class="fas fa-phone forgot-label__icon"></i>
                            Numéro de téléphone
                        </label>
                        <input type="tel" name="phone" value="{{ old('phone') }}" 
                               placeholder="+242 06 XXX XX XX"
                               class="forgot-input"
                               required>
                        @if($errors->has('phone'))
                            <span class="forgot-error">
                                {{ $errors->first('phone') }}
                            </span>
                        @endif
                    </div>
                    
                    <!-- New Password -->
                    <div class="forgot-field">
                        <label class="forgot-label">
                            <i class="fas fa-lock forgot-label__icon"></i>
                            Nouveau mot de passe
                        </label>
                        <input type="password" name="password"
                               placeholder="Créez un nouveau mot de passe"
                               class="forgot-input"
                               autocomplete="new-password"
                               required>
                        @if($errors->has('password'))
                            <span class="forgot-error">
                                {{ $errors->first('password') }}
                            </span>
                        @endif
                        <p class="forgot-help">
                            <i class="fas fa-info-circle"></i> Minimum 6 caractères
                        </p>
                    </div>
                    
                    <!-- Submit Button -->
                    <button type="submit" class="forgot-submit">
                        <i class="fas fa-key"></i> Réinitialiser mon mot de passe
                    </button>
                </form>
                
                <!-- Info Box -->
                <div class="forgot-info-box">
                    <p class="forgot-info-box__text">
                        <i class="fas fa-shield-alt forgot-info-box__icon"></i>
                        <span>{{ $authBrand['forgot_hint'] }}</span>
                    </p>
                </div>
                
                <!-- Back to Login -->
                <p class="forgot-back-wrap">
                    <a href="{{ route('user.login', array_filter(['redirect' => $redirectTarget])) }}" class="forgot-back-link">
                        <i class="fas fa-arrow-left"></i> Retour à la connexion
                    </a>
                </p>
            </div>
        </div>
    </div>
</section>
@endsection
