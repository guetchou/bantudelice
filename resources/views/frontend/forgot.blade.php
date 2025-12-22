@extends('frontend.layouts.app-modern')
@section('title', 'Mot de passe oublié | BantuDelice')
@section('description', 'Réinitialisez votre mot de passe BantuDelice.')

@section('content')
<section class="section" style="min-height: calc(100vh - 80px); display: flex; align-items: center; background: linear-gradient(135deg, var(--gray-50) 0%, var(--white) 100%); padding-top: 100px;">
    <div class="container">
        <div style="max-width: 450px; margin: 0 auto;">
            <!-- Header -->
            <div class="text-center" style="margin-bottom: 2rem;">
                <div style="width: 80px; height: 80px; background: linear-gradient(135deg, var(--primary) 0%, var(--warning) 100%); border-radius: 50%; margin: 0 auto 1.5rem; display: flex; align-items: center; justify-content: center;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" fill="white" viewBox="0 0 24 24">
                        <path d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zM9 6c0-1.66 1.34-3 3-3s3 1.34 3 3v2H9V6zm9 14H6V10h12v10zm-6-3c1.1 0 2-.9 2-2s-.9-2-2-2-2 .9-2 2 .9 2 2 2z"/>
                    </svg>
                </div>
                <h1 style="font-size: 2rem; margin-bottom: 0.5rem;">Mot de passe oublié ?</h1>
                <p style="color: var(--gray-500);">Pas de panique ! Entrez vos informations pour réinitialiser votre mot de passe.</p>
            </div>
            
            <!-- Alert Messages -->
            @if(Session::has('message'))
                <div style="background: var(--success); color: white; padding: 1rem; border-radius: var(--radius-lg); margin-bottom: 1.5rem; text-align: center; display: flex; align-items: center; justify-content: center; gap: 0.5rem;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/>
                    </svg>
                    <strong>{{ Session::get('message') }}</strong>
                </div>
            @endif
            
            @if(session()->has('error'))
                <div style="background: var(--error); color: white; padding: 1rem; border-radius: var(--radius-lg); margin-bottom: 1.5rem; text-align: center;">
                    <strong>{{ session()->get('error') }}</strong>
                </div>
            @endif
            
            <!-- Reset Form -->
            <div style="background: white; padding: 2rem; border-radius: var(--radius-2xl); box-shadow: var(--shadow-lg);">
                <form method="post" action="{{ route('forgot') }}">
                    @csrf
                    
                    <!-- Email -->
                    <div style="margin-bottom: 1.5rem;">
                        <label style="display: block; font-weight: 600; margin-bottom: 0.5rem; color: var(--gray-700);">
                            <i class="fas fa-envelope" style="color: var(--primary); margin-right: 0.5rem;"></i>
                            Adresse email
                        </label>
                        <input type="email" name="email" value="{{ old('email') }}" 
                               placeholder="votre@email.com"
                               style="width: 100%; padding: 0.875rem 1rem; border: 2px solid var(--gray-200); border-radius: var(--radius-lg); font-size: 1rem; transition: all 0.2s;"
                               onfocus="this.style.borderColor='var(--primary)'" 
                               onblur="this.style.borderColor='var(--gray-200)'"
                               required>
                        @if($errors->has('email'))
                            <span style="color: var(--error); font-size: 0.875rem; margin-top: 0.25rem; display: block;">
                                {{ $errors->first('email') }}
                            </span>
                        @endif
                    </div>
                    
                    <!-- Phone -->
                    <div style="margin-bottom: 1.5rem;">
                        <label style="display: block; font-weight: 600; margin-bottom: 0.5rem; color: var(--gray-700);">
                            <i class="fas fa-phone" style="color: var(--primary); margin-right: 0.5rem;"></i>
                            Numéro de téléphone
                        </label>
                        <input type="tel" name="phone" value="{{ old('phone') }}" 
                               placeholder="+242 06 XXX XX XX"
                               style="width: 100%; padding: 0.875rem 1rem; border: 2px solid var(--gray-200); border-radius: var(--radius-lg); font-size: 1rem; transition: all 0.2s;"
                               onfocus="this.style.borderColor='var(--primary)'" 
                               onblur="this.style.borderColor='var(--gray-200)'"
                               required>
                        @if($errors->has('phone'))
                            <span style="color: var(--error); font-size: 0.875rem; margin-top: 0.25rem; display: block;">
                                {{ $errors->first('phone') }}
                            </span>
                        @endif
                    </div>
                    
                    <!-- New Password -->
                    <div style="margin-bottom: 1.5rem;">
                        <label style="display: block; font-weight: 600; margin-bottom: 0.5rem; color: var(--gray-700);">
                            <i class="fas fa-lock" style="color: var(--primary); margin-right: 0.5rem;"></i>
                            Nouveau mot de passe
                        </label>
                        <input type="password" name="password" 
                               placeholder="Créez un nouveau mot de passe"
                               style="width: 100%; padding: 0.875rem 1rem; border: 2px solid var(--gray-200); border-radius: var(--radius-lg); font-size: 1rem; transition: all 0.2s;"
                               onfocus="this.style.borderColor='var(--primary)'" 
                               onblur="this.style.borderColor='var(--gray-200)'"
                               required>
                        @if($errors->has('password'))
                            <span style="color: var(--error); font-size: 0.875rem; margin-top: 0.25rem; display: block;">
                                {{ $errors->first('password') }}
                            </span>
                        @endif
                        <p style="color: var(--gray-500); font-size: 0.8125rem; margin-top: 0.5rem;">
                            <i class="fas fa-info-circle"></i> Minimum 6 caractères
                        </p>
                    </div>
                    
                    <!-- Submit Button -->
                    <button type="submit" class="btn btn-primary btn-lg" style="width: 100%;">
                        <i class="fas fa-key"></i> Réinitialiser mon mot de passe
                    </button>
                </form>
                
                <!-- Info Box -->
                <div style="background: var(--gray-50); padding: 1rem; border-radius: var(--radius-lg); margin-top: 1.5rem;">
                    <p style="color: var(--gray-600); font-size: 0.875rem; margin: 0; display: flex; align-items: flex-start; gap: 0.5rem;">
                        <i class="fas fa-shield-alt" style="color: var(--primary); margin-top: 2px;"></i>
                        <span>Pour votre sécurité, vous devez fournir l'email ET le numéro de téléphone associés à votre compte.</span>
                    </p>
                </div>
                
                <!-- Back to Login -->
                <p style="text-align: center; color: var(--gray-600); margin: 1.5rem 0 0;">
                    <a href="{{ route('user.login') }}" style="color: var(--primary); font-weight: 600; display: inline-flex; align-items: center; gap: 0.5rem;">
                        <i class="fas fa-arrow-left"></i> Retour à la connexion
                    </a>
                </p>
            </div>
        </div>
    </div>
</section>
@endsection
