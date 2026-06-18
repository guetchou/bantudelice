@extends('frontend.layouts.app-modern')
@php
    $foodBrandName = \App\Services\ConfigService::getCompanyName();
@endphp
@section('title', 'Devenir Livreur | ' . $foodBrandName)
@section('description', 'Rejoignez l\'équipe ' . $foodBrandName . ' en tant que livreur. Gagnez de l\'argent en livrant des commandes.')
@section('body_class', 'bd-driver-page')

@section('content')
<section class="driver-hero">
    <div class="container">
        <span class="section-badge driver-hero-badge">
            <i class="fas fa-motorcycle"></i> Livreur
        </span>
        <h1 class="driver-hero-title">Devenez Livreur {{ $foodBrandName }}</h1>
        <p class="driver-hero-copy">
            Rejoignez notre équipe de livreurs et gagnez de l'argent en toute flexibilité.
        </p>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="driver-shell">
            @if(session()->has('alert'))
                <div class="driver-alert {{ session()->get('alert.type') == 'success' ? 'is-success' : 'is-error' }}">
                    <strong>{{ session()->get('alert.message') }}</strong>
                </div>
            @endif

            <div class="driver-card">
                <div class="driver-head">
                    <h2 class="driver-head-title">Inscription Livreur</h2>
                    <p class="driver-head-copy">Remplissez le formulaire pour rejoindre notre équipe</p>
                </div>

                <form action="{{ route('driver.register') }}" method="post" enctype="multipart/form-data" class="driver-form">
                    @csrf

                    <section class="driver-form-section">
                        <h4 class="driver-section-title">
                            <i class="fas fa-user"></i> Informations personnelles
                        </h4>

                        <div class="driver-grid driver-grid--two">
                            <div class="driver-field">
                                <label class="driver-label" for="driverName">Nom complet *</label>
                                <input
                                    id="driverName"
                                    class="driver-input"
                                    type="text"
                                    name="name"
                                    value="{{ old('name') }}"
                                    placeholder="Votre nom complet"
                                    required
                                >
                            </div>
                            <div class="driver-field">
                                <label class="driver-label" for="driverPhone">Téléphone *</label>
                                <input
                                    id="driverPhone"
                                    class="driver-input"
                                    type="tel"
                                    name="phone"
                                    value="{{ old('phone') }}"
                                    placeholder="+242 06 XXX XX XX"
                                    required
                                >
                            </div>
                        </div>

                        <div class="driver-field">
                            <label class="driver-label" for="driverEmail">Email *</label>
                            <input
                                id="driverEmail"
                                class="driver-input"
                                type="email"
                                name="email"
                                value="{{ old('email') }}"
                                placeholder="votre@email.com"
                                required
                            >
                        </div>

                        <div class="driver-field">
                            <label class="driver-label" for="driverAddress">Adresse *</label>
                            <input
                                id="driverAddress"
                                class="driver-input"
                                type="text"
                                name="address"
                                value="{{ old('address') }}"
                                placeholder="Votre adresse complète"
                                required
                            >
                        </div>
                    </section>

                    <section class="driver-form-section">
                        <h4 class="driver-section-title">
                            <i class="fas fa-camera"></i> Photos
                        </h4>

                        <div class="driver-grid driver-grid--two driver-grid--uploads">
                            <div class="driver-upload-field">
                                <label class="driver-label" for="upload_file">Photo de profil</label>
                                <button type="button" class="driver-upload-card" onclick="document.getElementById('upload_file').click()">
                                    <img src="{{ url('images/placeholder.png') }}" id="proflie_image" alt="Photo de profil" class="driver-upload-image driver-upload-image--round">
                                    <span class="driver-upload-copy">Photo de profil<br>90x90 pixels</span>
                                </button>
                                <input class="driver-file-input" type="file" id="upload_file" name="image" accept="image/*" onchange="profile(this);">
                            </div>

                            <div class="driver-upload-field">
                                <label class="driver-label" for="file-input">Permis de conduire</label>
                                <button type="button" class="driver-upload-card" onclick="document.getElementById('file-input').click()">
                                    <img src="{{ url('images/placeholder.png') }}" id="licence_image" alt="Permis de conduire" class="driver-upload-image">
                                    <span class="driver-upload-copy">Photo du permis<br>90x90 pixels</span>
                                </button>
                                <input class="driver-file-input" type="file" id="file-input" name="licence_image" accept="image/*" onchange="licenceimage(this);">
                            </div>
                        </div>
                    </section>

                    {{-- Mobile Money — principal au Congo --}}
                    <section class="driver-form-section">
                        <h4 class="driver-section-title">
                            <i class="fas fa-mobile-screen-button"></i> Mobile Money <span style="font-size:.75rem;font-weight:400;color:#6b7280;">(MTN / Airtel)</span>
                        </h4>

                        <div class="driver-field">
                            <label class="driver-label" for="driverMobileMoney">Numéro Mobile Money *</label>
                            <input
                                id="driverMobileMoney"
                                class="driver-input"
                                type="tel"
                                name="paypal_account_no"
                                value="{{ old('paypal_account_no') }}"
                                placeholder="Ex : +242 06 XXX XX XX"
                                inputmode="tel"
                                required
                            >
                            @error('paypal_account_no')
                                <span class="driver-error">{{ $message }}</span>
                            @enderror
                        </div>
                    </section>

                    {{-- Banque — optionnel --}}
                    <section class="driver-form-section">
                        <h4 class="driver-section-title" style="cursor:pointer;" onclick="this.nextElementSibling.style.display=this.nextElementSibling.style.display==='none'?'block':'none'">
                            <i class="fas fa-university"></i> Coordonnées bancaires
                            <span style="font-size:.75rem;font-weight:400;color:#6b7280;margin-left:6px;">(optionnel) <i class="fas fa-chevron-down" style="font-size:.65rem;"></i></span>
                        </h4>
                        <div style="display:none;">
                            <div class="driver-grid driver-grid--two">
                                <div class="driver-field">
                                    <label class="driver-label" for="driverAccountName">Nom du titulaire</label>
                                    <input id="driverAccountName" class="driver-input" type="text" name="account_name"
                                        value="{{ old('account_name') }}" placeholder="Nom sur le compte">
                                </div>
                                <div class="driver-field">
                                    <label class="driver-label" for="driverAccountNumber">Numéro de compte</label>
                                    <input id="driverAccountNumber" class="driver-input" type="text" name="account_number"
                                        value="{{ old('account_number') }}" placeholder="Numéro de compte">
                                </div>
                            </div>
                            <div class="driver-grid driver-grid--two">
                                <div class="driver-field">
                                    <label class="driver-label" for="driverBankName">Nom de la banque</label>
                                    <input id="driverBankName" class="driver-input" type="text" name="bank_name"
                                        value="{{ old('bank_name') }}" placeholder="Ex: BGFI, UBA, etc.">
                                </div>
                                <div class="driver-field">
                                    <label class="driver-label" for="driverBranchName">Nom de l'agence</label>
                                    <input id="driverBranchName" class="driver-input" type="text" name="branch_name"
                                        value="{{ old('branch_name') }}" placeholder="Agence">
                                </div>
                            </div>
                            <div class="driver-field">
                                <label class="driver-label" for="driverBranchAddress">Adresse de l'agence</label>
                                <input id="driverBranchAddress" class="driver-input" type="text" name="branch_address"
                                    value="{{ old('branch_address') }}" placeholder="Adresse de l'agence">
                            </div>
                        </div>
                    </section>

                    <section class="driver-form-section">
                        <h4 class="driver-section-title">
                            <i class="fas fa-lock"></i> Sécurité
                        </h4>

                        <div class="driver-field">
                            <label class="driver-label" for="driverPassword">Mot de passe *</label>
                            <input
                                id="driverPassword"
                                class="driver-input"
                                type="password"
                                name="password"
                                placeholder="Créez un mot de passe sécurisé"
                                autocomplete="new-password"
                                required
                            >
                        </div>
                    </section>

                    <div class="driver-terms">
                        <label class="driver-terms-label">
                            <input class="driver-terms-input" type="checkbox" name="terms" required>
                            <span>J'accepte les <a href="{{ route('terms.conditions') }}" class="driver-inline-link">conditions générales</a> de {{ $foodBrandName }}</span>
                        </label>
                    </div>

                    <button type="submit" class="driver-submit">
                        <i class="fas fa-paper-plane"></i> Soumettre ma candidature
                    </button>
                </form>

                <p class="driver-login-copy">
                    Déjà inscrit ?
                    <a href="{{ route('login') }}" class="driver-inline-link driver-inline-link--strong">Connectez-vous</a>
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
