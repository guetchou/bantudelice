@extends('frontend.layouts.app-modern')
@php
    $foodBrandName = \App\Services\ConfigService::getCompanyName();
@endphp
@section('title', 'Devenir Partenaire | ' . $foodBrandName)
@section('description', 'Rejoignez le réseau ' . $foodBrandName . ' en tant que restaurant ou marchand partenaire.')
@section('body_class', 'bd-partner-page')

@section('content')
<section class="partner-hero">
    <div class="container">
        <span class="section-badge partner-hero-badge">
            <i class="fas fa-store"></i> Partenaire
        </span>
        <h1 class="partner-hero-title">
            Devenez Partenaire {{ $foodBrandName }}
        </h1>
        <p class="partner-hero-description">
            Déposez votre dossier pour référencer votre établissement et centraliser vos opérations de commande sur la plateforme.
        </p>
    </div>
</section>

<section class="section partner-shell">
    <div class="container">
        <div class="partner-form-wrap">
            <div class="partner-benefits">
                <div class="partner-benefit-card">
                    <div class="partner-benefit-icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <h4 class="partner-benefit-title">Nouveaux canaux de vente</h4>
                </div>
                <div class="partner-benefit-card">
                    <div class="partner-benefit-icon">
                        <i class="fas fa-shipping-fast"></i>
                    </div>
                    <h4 class="partner-benefit-title">Opérations de livraison</h4>
                </div>
                <div class="partner-benefit-card">
                    <div class="partner-benefit-icon">
                        <i class="fas fa-hand-holding-usd"></i>
                    </div>
                    <h4 class="partner-benefit-title">Versements structurés</h4>
                </div>
            </div>

            @if(session()->has('alert'))
                <div class="partner-alert {{ session()->get('alert.type') == 'success' ? 'is-success' : 'is-error' }}">
                    <strong>{{ session()->get('alert.message') }}</strong>
                </div>
            @endif

            <div class="partner-card">
                <h2 class="partner-heading">Ouverture de compte partenaire</h2>
                <p class="partner-subtitle">Complétez le formulaire pour soumettre votre dossier.</p>

                <form action="{{ route('partner.register') }}" method="post" enctype="multipart/form-data" class="partner-form">
                    @csrf

                    <h4 class="partner-section-title">
                        <i class="fas fa-store"></i> Informations de l'établissement
                    </h4>

                    <div class="partner-grid">
                        <div class="partner-field">
                            <label class="partner-label">Nom de l'établissement *</label>
                            <input type="text" name="name" value="{{ old('name') }}" placeholder="Ex: Restaurant Le Délice"
                                   class="partner-input" required>
                        </div>
                        <div class="partner-field">
                            <label class="partner-label">Téléphone *</label>
                            <input type="tel" name="phone" value="{{ old('phone') }}" placeholder="+242 06 XXX XX XX"
                                   class="partner-input" required>
                        </div>
                    </div>

                    <div class="partner-field">
                        <label class="partner-label">Email *</label>
                        <input type="email" name="email" value="{{ old('email') }}" placeholder="contact@votrerestaurant.com"
                               class="partner-input" required>
                    </div>

                    <div class="partner-field">
                        <label class="partner-label">Slogan</label>
                        <input type="text" name="slogan" value="{{ old('slogan') }}" placeholder="Ex: Les meilleurs plats de la ville"
                               class="partner-input">
                    </div>

                    <h4 class="partner-section-title">
                        <i class="fas fa-image"></i> Images
                    </h4>

                    <div class="partner-upload-grid">
                        <div class="partner-field">
                            <label class="partner-label">Logo</label>
                            <div class="partner-upload-trigger" onclick="document.getElementById('upload_file').click()">
                                <img src="{{ url('images/placeholder.png') }}" id="proflie_image" alt="Logo"
                                     class="partner-upload-preview">
                                <p class="partner-upload-copy">Logo de l'établissement<br>90x90 pixels</p>
                            </div>
                            <input type="file" id="upload_file" name="logo" accept="image/*" class="partner-file-input" onchange="profile(this);">
                        </div>

                        <div class="partner-field">
                            <label class="partner-label">Image de couverture</label>
                            <div class="partner-upload-trigger" onclick="document.getElementById('file-input').click()">
                                <img src="{{ url('images/placeholder.png') }}" id="licence_image" alt="Couverture"
                                     class="partner-upload-preview">
                                <p class="partner-upload-copy">Photo de couverture<br>Format paysage</p>
                            </div>
                            <input type="file" id="file-input" name="cover_image" accept="image/*" class="partner-file-input" onchange="licenceimage(this);">
                        </div>
                    </div>

                    <h4 class="partner-section-title">
                        <i class="fas fa-map-marker-alt"></i> Localisation
                    </h4>

                    <div class="partner-grid">
                        <div class="partner-field">
                            <label class="partner-label">Ville *</label>
                            <input type="text" name="city" value="{{ old('city') }}" placeholder="Ex: Brazzaville"
                                   class="partner-input" required>
                        </div>
                        <div class="partner-field">
                            <label class="partner-label">Adresse complète *</label>
                            <input type="text" name="address" value="{{ old('address') }}" placeholder="Quartier, avenue, numéro"
                                   class="partner-input" required>
                        </div>
                    </div>

                    {{-- Mobile Money — principal --}}
                    <h4 class="partner-section-title">
                        <i class="fas fa-mobile-screen-button"></i> Mobile Money
                        <span style="font-size:.75rem;font-weight:400;color:#6b7280;margin-left:4px;">(MTN / Airtel)</span>
                    </h4>

                    <div class="partner-field">
                        <label class="partner-label">Numéro Mobile Money *</label>
                        <input type="tel" name="paypal_account_no" value="{{ old('paypal_account_no') }}"
                               placeholder="Ex : +242 06 XXX XX XX" inputmode="tel"
                               class="partner-input" required>
                        @error('paypal_account_no')
                            <span style="color:#dc2626;font-size:.78rem;">{{ $message }}</span>
                        @enderror
                    </div>

                    {{-- Banque — optionnel --}}
                    <h4 class="partner-section-title" style="cursor:pointer;margin-top:20px;"
                        onclick="this.nextElementSibling.style.display=this.nextElementSibling.style.display==='none'?'block':'none'">
                        <i class="fas fa-university"></i> Coordonnées bancaires
                        <span style="font-size:.75rem;font-weight:400;color:#6b7280;margin-left:4px;">(optionnel) <i class="fas fa-chevron-down" style="font-size:.65rem;"></i></span>
                    </h4>

                    <div style="display:none;">
                        <div class="partner-grid">
                            <div class="partner-field">
                                <label class="partner-label">Nom du titulaire</label>
                                <input type="text" name="account_name" value="{{ old('account_name') }}"
                                       placeholder="Nom sur le compte" class="partner-input">
                            </div>
                            <div class="partner-field">
                                <label class="partner-label">Numéro de compte</label>
                                <input type="text" name="account_number" value="{{ old('account_number') }}"
                                       placeholder="Numéro de compte" class="partner-input">
                            </div>
                        </div>
                        <div class="partner-grid">
                            <div class="partner-field">
                                <label class="partner-label">Nom de la banque</label>
                                <input type="text" name="bank_name" value="{{ old('bank_name') }}"
                                       placeholder="Ex: BGFI, UBA, etc." class="partner-input">
                            </div>
                            <div class="partner-field">
                                <label class="partner-label">Code agence</label>
                                <input type="text" name="branch_number" value="{{ old('branch_number') }}"
                                       placeholder="Code agence" class="partner-input">
                            </div>
                        </div>
                    </div>

                    <h4 class="partner-section-title" style="margin-top:20px;">
                        <i class="fas fa-lock"></i> Sécurité
                    </h4>

                    <div class="partner-field">
                        <label class="partner-label">Mot de passe *</label>
                        <input type="password" name="password" placeholder="Créez un mot de passe sécurisé"
                               class="partner-input" autocomplete="new-password" required>
                    </div>

                    <div class="partner-terms">
                        <label class="partner-terms-label">
                            <input type="checkbox" name="terms" class="partner-terms-checkbox" required>
                            <span>J'accepte les <a href="{{ route('terms.conditions') }}" class="partner-terms-link">conditions générales</a> et la <a href="{{ route('refund.policy') }}" class="partner-terms-link">politique de remboursement</a> de {{ $foodBrandName }}</span>
                        </label>
                    </div>

                    <button type="submit" class="partner-submit">
                        <i class="fas fa-paper-plane"></i> Soumettre ma candidature
                    </button>
                </form>

                <p class="partner-login-note">
                    Déjà partenaire ?
                    <a href="{{ route('login') }}" class="partner-login-link">Connectez-vous</a>
                </p>
            </div>
        </div>
    </div>
</section>
@endsection

@section('scripts')
<script>
    function licenceimage(input) {
        if (input.files && input.files[0]) {
            var reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('licence_image').src = e.target.result;
            };
            reader.readAsDataURL(input.files[0]);
        }
    }

    function profile(input) {
        if (input.files && input.files[0]) {
            var reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('proflie_image').src = e.target.result;
            };
            reader.readAsDataURL(input.files[0]);
        }
    }
</script>
@endsection
