@extends('frontend.layouts.app-modern')
@section('title', 'Devenir Partenaire | BantuDelice')
@section('description', 'Rejoignez le réseau BantuDelice en tant que restaurant ou marchand partenaire.')

@section('content')
<!-- Hero Section -->
<section style="background: linear-gradient(135deg, var(--secondary) 0%, var(--accent) 100%); padding: 150px 0 80px; text-align: center;">
    <div class="container">
        <span class="section-badge" style="background: rgba(255,255,255,0.1); color: white;">
            <i class="fas fa-store"></i> Partenaire
        </span>
        <h1 style="color: white; font-size: clamp(2rem, 5vw, 3rem); margin-top: 1rem;">
            Devenez Partenaire BantuDelice
        </h1>
        <p style="color: rgba(255,255,255,0.8); max-width: 600px; margin: 1rem auto 0; font-size: 1.125rem;">
            Augmentez votre visibilité et vos ventes en rejoignant notre plateforme de livraison.
        </p>
    </div>
</section>

<!-- Registration Form -->
<section class="section">
    <div class="container">
        <div style="max-width: 700px; margin: 0 auto;">
            <!-- Benefits -->
            <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem; margin-bottom: 2rem;">
                <div style="background: linear-gradient(135deg, rgba(255, 107, 53, 0.1) 0%, rgba(245, 158, 11, 0.1) 100%); padding: 1.5rem; border-radius: var(--radius-xl); text-align: center;">
                    <div style="width: 48px; height: 48px; background: var(--primary); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 0.75rem;">
                        <i class="fas fa-chart-line" style="color: white; font-size: 1.25rem;"></i>
                    </div>
                    <h4 style="font-size: 0.9375rem; margin: 0;">Plus de clients</h4>
                </div>
                <div style="background: linear-gradient(135deg, rgba(255, 107, 53, 0.1) 0%, rgba(245, 158, 11, 0.1) 100%); padding: 1.5rem; border-radius: var(--radius-xl); text-align: center;">
                    <div style="width: 48px; height: 48px; background: var(--primary); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 0.75rem;">
                        <i class="fas fa-shipping-fast" style="color: white; font-size: 1.25rem;"></i>
                    </div>
                    <h4 style="font-size: 0.9375rem; margin: 0;">Livraison rapide</h4>
                </div>
                <div style="background: linear-gradient(135deg, rgba(255, 107, 53, 0.1) 0%, rgba(245, 158, 11, 0.1) 100%); padding: 1.5rem; border-radius: var(--radius-xl); text-align: center;">
                    <div style="width: 48px; height: 48px; background: var(--primary); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 0.75rem;">
                        <i class="fas fa-hand-holding-usd" style="color: white; font-size: 1.25rem;"></i>
                    </div>
                    <h4 style="font-size: 0.9375rem; margin: 0;">Revenus garantis</h4>
                </div>
            </div>
            
            <!-- Alert Messages -->
            @if(session()->has('alert'))
                <div style="background: {{ session()->get('alert.type') == 'success' ? 'var(--success)' : 'var(--error)' }}; color: white; padding: 1rem; border-radius: var(--radius-lg); margin-bottom: 1.5rem; text-align: center;">
                    <strong>{{ session()->get('alert.message') }}</strong>
                </div>
            @endif
            
            <!-- Form Card -->
            <div style="background: white; padding: 2rem; border-radius: var(--radius-2xl); box-shadow: var(--shadow-lg);">
                <h2 style="font-size: 1.5rem; margin-bottom: 0.5rem; text-align: center;">Inscription Restaurant/Marchand</h2>
                <p style="color: var(--gray-500); text-align: center; margin-bottom: 2rem;">Complétez le formulaire pour rejoindre notre réseau</p>
                
                <form action="{{ route('partner.register') }}" method="post" enctype="multipart/form-data">
                    @csrf
                    
                    <!-- Business Info -->
                    <h4 style="font-size: 1rem; color: var(--primary); margin-bottom: 1rem; padding-bottom: 0.5rem; border-bottom: 2px solid var(--gray-100);">
                        <i class="fas fa-store"></i> Informations de l'établissement
                    </h4>
                    
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem; margin-bottom: 1.5rem;">
                        <div>
                            <label style="display: block; font-weight: 600; margin-bottom: 0.5rem; color: var(--gray-700);">Nom de l'établissement *</label>
                            <input type="text" name="name" value="{{ old('name') }}" placeholder="Ex: Restaurant Le Délice"
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
                        <input type="email" name="email" value="{{ old('email') }}" placeholder="contact@votrerestaurant.com"
                               style="width: 100%; padding: 0.875rem 1rem; border: 2px solid var(--gray-200); border-radius: var(--radius-lg); font-size: 1rem;" required>
                    </div>
                    
                    <div style="margin-bottom: 1.5rem;">
                        <label style="display: block; font-weight: 600; margin-bottom: 0.5rem; color: var(--gray-700);">Slogan</label>
                        <input type="text" name="slogan" value="{{ old('slogan') }}" placeholder="Ex: Les meilleurs plats de la ville"
                               style="width: 100%; padding: 0.875rem 1rem; border: 2px solid var(--gray-200); border-radius: var(--radius-lg); font-size: 1rem;">
                    </div>
                    
                    <!-- Images -->
                    <h4 style="font-size: 1rem; color: var(--primary); margin: 2rem 0 1rem; padding-bottom: 0.5rem; border-bottom: 2px solid var(--gray-100);">
                        <i class="fas fa-image"></i> Images
                    </h4>
                    
                    <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 1rem; margin-bottom: 1.5rem;">
                        <!-- Logo -->
                        <div>
                            <label style="display: block; font-weight: 600; margin-bottom: 0.5rem; color: var(--gray-700);">Logo</label>
                            <div style="border: 2px dashed var(--gray-300); border-radius: var(--radius-lg); padding: 1.5rem; text-align: center; cursor: pointer;"
                                 onclick="document.getElementById('upload_file').click()">
                                <img src="{{ url('images/placeholder.png') }}" id="proflie_image" alt="Logo" 
                                     style="width: 80px; height: 80px; border-radius: var(--radius-lg); object-fit: cover; margin: 0 auto 0.5rem;">
                                <p style="color: var(--gray-500); font-size: 0.8rem; margin: 0;">Logo de l'établissement<br>90x90 pixels</p>
                            </div>
                            <input type="file" id="upload_file" name="logo" accept="image/*" style="display: none;" onchange="profile(this);">
                        </div>
                        
                        <!-- Cover Image -->
                        <div>
                            <label style="display: block; font-weight: 600; margin-bottom: 0.5rem; color: var(--gray-700);">Image de couverture</label>
                            <div style="border: 2px dashed var(--gray-300); border-radius: var(--radius-lg); padding: 1.5rem; text-align: center; cursor: pointer;"
                                 onclick="document.getElementById('file-input').click()">
                                <img src="{{ url('images/placeholder.png') }}" id="licence_image" alt="Couverture" 
                                     style="width: 80px; height: 80px; border-radius: var(--radius-lg); object-fit: cover; margin: 0 auto 0.5rem;">
                                <p style="color: var(--gray-500); font-size: 0.8rem; margin: 0;">Photo de couverture<br>Format paysage</p>
                            </div>
                            <input type="file" id="file-input" name="cover_image" accept="image/*" style="display: none;" onchange="licenceimage(this);">
                        </div>
                    </div>
                    
                    <!-- Location -->
                    <h4 style="font-size: 1rem; color: var(--primary); margin: 2rem 0 1rem; padding-bottom: 0.5rem; border-bottom: 2px solid var(--gray-100);">
                        <i class="fas fa-map-marker-alt"></i> Localisation
                    </h4>
                    
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem; margin-bottom: 1.5rem;">
                        <div>
                            <label style="display: block; font-weight: 600; margin-bottom: 0.5rem; color: var(--gray-700);">Ville *</label>
                            <input type="text" name="city" value="{{ old('city') }}" placeholder="Ex: Brazzaville"
                                   style="width: 100%; padding: 0.875rem 1rem; border: 2px solid var(--gray-200); border-radius: var(--radius-lg); font-size: 1rem;" required>
                        </div>
                        <div>
                            <label style="display: block; font-weight: 600; margin-bottom: 0.5rem; color: var(--gray-700);">Adresse complète *</label>
                            <input type="text" name="address" value="{{ old('address') }}" placeholder="Quartier, avenue, numéro"
                                   style="width: 100%; padding: 0.875rem 1rem; border: 2px solid var(--gray-200); border-radius: var(--radius-lg); font-size: 1rem;" required>
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
                            <label style="display: block; font-weight: 600; margin-bottom: 0.5rem; color: var(--gray-700);">Code agence *</label>
                            <input type="text" name="branch_number" value="{{ old('branch_number') }}" placeholder="Code agence"
                                   style="width: 100%; padding: 0.875rem 1rem; border: 2px solid var(--gray-200); border-radius: var(--radius-lg); font-size: 1rem;" required>
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
                            <span>J'accepte les <a href="{{ route('terms.conditions') }}" style="color: var(--primary);">conditions générales</a> et la <a href="{{ route('refund.policy') }}" style="color: var(--primary);">politique de remboursement</a> de BantuDelice</span>
                        </label>
                    </div>
                    
                    <!-- Submit -->
                    <button type="submit" class="btn btn-primary btn-lg" style="width: 100%;">
                        <i class="fas fa-paper-plane"></i> Soumettre ma candidature
                    </button>
                </form>
                
                <!-- Login Link -->
                <p style="text-align: center; color: var(--gray-600); margin-top: 1.5rem;">
                    Déjà partenaire ? 
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
