@extends('frontend.layouts.app-modern')
@section('title', 'Devenir Livreur | BantuDelice')
@section('description', 'Rejoignez l\'équipe BantuDelice en tant que livreur. Gagnez de l\'argent en livrant des commandes.')

@section('content')
<!-- Hero Section -->
<section style="background: linear-gradient(135deg, var(--secondary) 0%, var(--accent) 100%); padding: 150px 0 80px; text-align: center;">
    <div class="container">
        <span class="section-badge" style="background: rgba(255,255,255,0.1); color: white;">
            <i class="fas fa-motorcycle"></i> Livreur
        </span>
        <h1 style="color: white; font-size: clamp(2rem, 5vw, 3rem); margin-top: 1rem;">
            Devenez Livreur BantuDelice
        </h1>
        <p style="color: rgba(255,255,255,0.8); max-width: 600px; margin: 1rem auto 0; font-size: 1.125rem;">
            Rejoignez notre équipe de livreurs et gagnez de l'argent en toute flexibilité.
        </p>
    </div>
</section>

<!-- Registration Form -->
<section class="section">
    <div class="container">
        <div style="max-width: 700px; margin: 0 auto;">
            <!-- Alert Messages -->
            @if(session()->has('alert'))
                <div style="background: {{ session()->get('alert.type') == 'success' ? 'var(--success)' : 'var(--error)' }}; color: white; padding: 1rem; border-radius: var(--radius-lg); margin-bottom: 1.5rem; text-align: center;">
                    <strong>{{ session()->get('alert.message') }}</strong>
                </div>
            @endif
            
            <!-- Form Card -->
            <div style="background: white; padding: 2rem; border-radius: var(--radius-2xl); box-shadow: var(--shadow-lg);">
                <h2 style="font-size: 1.5rem; margin-bottom: 0.5rem; text-align: center;">Inscription Livreur</h2>
                <p style="color: var(--gray-500); text-align: center; margin-bottom: 2rem;">Remplissez le formulaire pour rejoindre notre équipe</p>
                
                <form action="{{ route('driver.register') }}" method="post" enctype="multipart/form-data">
                    @csrf
                    
                    <!-- Personal Info -->
                    <h4 style="font-size: 1rem; color: var(--primary); margin-bottom: 1rem; padding-bottom: 0.5rem; border-bottom: 2px solid var(--gray-100);">
                        <i class="fas fa-user"></i> Informations personnelles
                    </h4>
                    
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem; margin-bottom: 1.5rem;">
                        <div>
                            <label style="display: block; font-weight: 600; margin-bottom: 0.5rem; color: var(--gray-700);">Nom complet *</label>
                            <input type="text" name="name" value="{{ old('name') }}" placeholder="Votre nom complet"
                                   style="width: 100%; padding: 0.875rem 1rem; border: 2px solid var(--gray-200); border-radius: var(--radius-lg); font-size: 1rem;" required>
                        </div>
                        <div>
                            <label style="display: block; font-weight: 600; margin-bottom: 0.5rem; color: var(--gray-700);">Téléphone *</label>
                            <input type="tel" name="phone" value="{{ old('phone') }}" placeholder="+242 06 XXX XX XX"
                                   style="width: 100%; padding: 0.875rem 1rem; border: 2px solid var(--gray-200); border-radius: var(--radius-lg); font-size: 1rem;" required>
                        </div>
                    </div>
                    
                    <div style="margin-bottom: 1.5rem;">
                        <label style="display: block; font-weight: 600; margin-bottom: 0.5rem; color: var(--gray-700);">Email *</label>
                        <input type="email" name="email" value="{{ old('email') }}" placeholder="votre@email.com"
                               style="width: 100%; padding: 0.875rem 1rem; border: 2px solid var(--gray-200); border-radius: var(--radius-lg); font-size: 1rem;" required>
                    </div>
                    
                    <div style="margin-bottom: 1.5rem;">
                        <label style="display: block; font-weight: 600; margin-bottom: 0.5rem; color: var(--gray-700);">Adresse *</label>
                        <input type="text" name="address" value="{{ old('address') }}" placeholder="Votre adresse complète"
                               style="width: 100%; padding: 0.875rem 1rem; border: 2px solid var(--gray-200); border-radius: var(--radius-lg); font-size: 1rem;" required>
                    </div>
                    
                    <!-- Photos -->
                    <h4 style="font-size: 1rem; color: var(--primary); margin: 2rem 0 1rem; padding-bottom: 0.5rem; border-bottom: 2px solid var(--gray-100);">
                        <i class="fas fa-camera"></i> Photos
                    </h4>
                    
                    <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 1rem; margin-bottom: 1.5rem;">
                        <!-- Profile Image -->
                        <div>
                            <label style="display: block; font-weight: 600; margin-bottom: 0.5rem; color: var(--gray-700);">Photo de profil</label>
                            <div style="border: 2px dashed var(--gray-300); border-radius: var(--radius-lg); padding: 1.5rem; text-align: center; cursor: pointer;"
                                 onclick="document.getElementById('upload_file').click()">
                                <img src="{{ url('images/placeholder.png') }}" id="proflie_image" alt="Photo" 
                                     style="width: 80px; height: 80px; border-radius: 50%; object-fit: cover; margin: 0 auto 0.5rem;">
                                <p style="color: var(--gray-500); font-size: 0.8rem; margin: 0;">Photo de profil<br>90x90 pixels</p>
                            </div>
                            <input type="file" id="upload_file" name="image" accept="image/*" style="display: none;" onchange="profile(this);">
                        </div>
                        
                        <!-- License Image -->
                        <div>
                            <label style="display: block; font-weight: 600; margin-bottom: 0.5rem; color: var(--gray-700);">Permis de conduire</label>
                            <div style="border: 2px dashed var(--gray-300); border-radius: var(--radius-lg); padding: 1.5rem; text-align: center; cursor: pointer;"
                                 onclick="document.getElementById('file-input').click()">
                                <img src="{{ url('images/placeholder.png') }}" id="licence_image" alt="Permis" 
                                     style="width: 80px; height: 80px; border-radius: var(--radius-lg); object-fit: cover; margin: 0 auto 0.5rem;">
                                <p style="color: var(--gray-500); font-size: 0.8rem; margin: 0;">Photo du permis<br>90x90 pixels</p>
                            </div>
                            <input type="file" id="file-input" name="licence_image" accept="image/*" style="display: none;" onchange="licenceimage(this);">
                        </div>
                    </div>
                    
                    <!-- Bank Info -->
                    <h4 style="font-size: 1rem; color: var(--primary); margin: 2rem 0 1rem; padding-bottom: 0.5rem; border-bottom: 2px solid var(--gray-100);">
                        <i class="fas fa-university"></i> Informations bancaires
                    </h4>
                    
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem; margin-bottom: 1.5rem;">
                        <div>
                            <label style="display: block; font-weight: 600; margin-bottom: 0.5rem; color: var(--gray-700);">Nom du titulaire *</label>
                            <input type="text" name="account_name" value="{{ old('account_name') }}" placeholder="Nom sur le compte"
                                   style="width: 100%; padding: 0.875rem 1rem; border: 2px solid var(--gray-200); border-radius: var(--radius-lg); font-size: 1rem;" required>
                        </div>
                        <div>
                            <label style="display: block; font-weight: 600; margin-bottom: 0.5rem; color: var(--gray-700);">Numéro de compte *</label>
                            <input type="text" name="account_number" value="{{ old('account_number') }}" placeholder="Numéro de compte"
                                   style="width: 100%; padding: 0.875rem 1rem; border: 2px solid var(--gray-200); border-radius: var(--radius-lg); font-size: 1rem;" required>
                        </div>
                    </div>
                    
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem; margin-bottom: 1.5rem;">
                        <div>
                            <label style="display: block; font-weight: 600; margin-bottom: 0.5rem; color: var(--gray-700);">Nom de la banque *</label>
                            <input type="text" name="bank_name" value="{{ old('bank_name') }}" placeholder="Ex: BGFI, UBA, etc."
                                   style="width: 100%; padding: 0.875rem 1rem; border: 2px solid var(--gray-200); border-radius: var(--radius-lg); font-size: 1rem;" required>
                        </div>
                        <div>
                            <label style="display: block; font-weight: 600; margin-bottom: 0.5rem; color: var(--gray-700);">Nom de l'agence *</label>
                            <input type="text" name="branch_name" value="{{ old('branch_name') }}" placeholder="Agence"
                                   style="width: 100%; padding: 0.875rem 1rem; border: 2px solid var(--gray-200); border-radius: var(--radius-lg); font-size: 1rem;" required>
                        </div>
                    </div>
                    
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem; margin-bottom: 1.5rem;">
                        <div>
                            <label style="display: block; font-weight: 600; margin-bottom: 0.5rem; color: var(--gray-700);">Adresse de l'agence *</label>
                            <input type="text" name="branch_address" value="{{ old('branch_address') }}" placeholder="Adresse de l'agence"
                                   style="width: 100%; padding: 0.875rem 1rem; border: 2px solid var(--gray-200); border-radius: var(--radius-lg); font-size: 1rem;" required>
                        </div>
                        <div>
                            <label style="display: block; font-weight: 600; margin-bottom: 0.5rem; color: var(--gray-700);">Compte Mobile Money</label>
                            <input type="text" name="paypal_account_no" value="{{ old('paypal_account_no') }}" placeholder="Numéro Mobile Money"
                                   style="width: 100%; padding: 0.875rem 1rem; border: 2px solid var(--gray-200); border-radius: var(--radius-lg); font-size: 1rem;">
                        </div>
                    </div>
                    
                    <!-- Password -->
                    <h4 style="font-size: 1rem; color: var(--primary); margin: 2rem 0 1rem; padding-bottom: 0.5rem; border-bottom: 2px solid var(--gray-100);">
                        <i class="fas fa-lock"></i> Sécurité
                    </h4>
                    
                    <div style="margin-bottom: 1.5rem;">
                        <label style="display: block; font-weight: 600; margin-bottom: 0.5rem; color: var(--gray-700);">Mot de passe *</label>
                        <input type="password" name="password" placeholder="Créez un mot de passe sécurisé"
                               style="width: 100%; padding: 0.875rem 1rem; border: 2px solid var(--gray-200); border-radius: var(--radius-lg); font-size: 1rem;" required>
                    </div>
                    
                    <!-- Terms -->
                    <div style="margin-bottom: 1.5rem;">
                        <label style="display: flex; align-items: flex-start; gap: 0.5rem; cursor: pointer; font-size: 0.9375rem; color: var(--gray-600);">
                            <input type="checkbox" name="terms" style="width: 18px; height: 18px; accent-color: var(--primary); margin-top: 2px;" required>
                            <span>J'accepte les <a href="{{ route('terms.conditions') }}" style="color: var(--primary);">conditions générales</a> de BantuDelice</span>
                        </label>
                    </div>
                    
                    <!-- Submit -->
                    <button type="submit" class="btn btn-primary btn-lg" style="width: 100%;">
                        <i class="fas fa-paper-plane"></i> Soumettre ma candidature
                    </button>
                </form>
                
                <!-- Login Link -->
                <p style="text-align: center; color: var(--gray-600); margin-top: 1.5rem;">
                    Déjà inscrit ? 
                    <a href="{{ route('login') }}" style="color: var(--primary); font-weight: 600;">Connectez-vous</a>
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
