@extends('frontend.layouts.app-modern')
@section('title', 'Contactez-nous | BantuDelice')
@section('description', 'Contactez l\'équipe BantuDelice. Nous sommes là pour répondre à toutes vos questions.')

@section('content')
<!-- Hero Section -->
<section style="background: linear-gradient(135deg, var(--secondary) 0%, var(--accent) 100%); padding: 150px 0 80px; text-align: center;">
    <div class="container">
        <span class="section-badge" style="background: rgba(255,255,255,0.1); color: white;">
            <img src="{{ asset('images/icons/happy-customer.svg') }}" alt="Contact" style="width: 20px; height: 20px; vertical-align: middle; margin-right: 6px;">
            Contact
        </span>
        <h1 style="color: white; font-size: clamp(2rem, 5vw, 3rem); margin-top: 1rem;">
            Contactez-nous
        </h1>
        <p style="color: rgba(255,255,255,0.8); max-width: 600px; margin: 1rem auto 0; font-size: 1.125rem;">
            Une question ? Une suggestion ? Nous sommes là pour vous aider !
        </p>
    </div>
</section>

<!-- Contact Section -->
<section class="section">
    <div class="container">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(350px, 1fr)); gap: 3rem; align-items: start;">
            
            <!-- Contact Form -->
            <div style="background: white; padding: 2rem; border-radius: var(--radius-2xl); box-shadow: var(--shadow-lg);">
                <h2 style="font-size: 1.5rem; margin-bottom: 0.5rem;">Envoyez-nous un message</h2>
                <p style="color: var(--gray-500); margin-bottom: 1.5rem;">Remplissez le formulaire ci-dessous et nous vous répondrons rapidement.</p>
                
                @if(Session::has('message'))
                    <div style="background: var(--success); color: white; padding: 1rem; border-radius: var(--radius-lg); margin-bottom: 1.5rem; text-align: center;">
                        <strong>{{ Session::get('message') }}</strong>
                    </div>
                @endif
                
                <form action="{{ route('contact') }}" method="post">
                    @csrf
                    
                    <!-- Name -->
                    <div style="margin-bottom: 1.25rem;">
                        <label style="display: block; font-weight: 600; margin-bottom: 0.5rem; color: var(--gray-700);">
                            Nom complet
                        </label>
                        <input type="text" name="name" placeholder="Votre nom"
                               style="width: 100%; padding: 0.875rem 1rem; border: 2px solid var(--gray-200); border-radius: var(--radius-lg); font-size: 1rem; transition: all 0.2s;"
                               required>
                        @if($errors->has('name'))
                            <span style="color: var(--error); font-size: 0.875rem;">{{ $errors->first('name') }}</span>
                        @endif
                    </div>
                    
                    <!-- Email -->
                    <div style="margin-bottom: 1.25rem;">
                        <label style="display: block; font-weight: 600; margin-bottom: 0.5rem; color: var(--gray-700);">
                            Adresse email
                        </label>
                        <input type="email" name="email" placeholder="votre@email.com"
                               style="width: 100%; padding: 0.875rem 1rem; border: 2px solid var(--gray-200); border-radius: var(--radius-lg); font-size: 1rem; transition: all 0.2s;"
                               required>
                    </div>
                    
                    <!-- Phone -->
                    <div style="margin-bottom: 1.25rem;">
                        <label style="display: block; font-weight: 600; margin-bottom: 0.5rem; color: var(--gray-700);">
                            Numéro de téléphone
                        </label>
                        <input type="tel" name="phone" placeholder="+242 06 XXX XX XX"
                               style="width: 100%; padding: 0.875rem 1rem; border: 2px solid var(--gray-200); border-radius: var(--radius-lg); font-size: 1rem; transition: all 0.2s;"
                               required>
                    </div>
                    
                    <!-- Message -->
                    <div style="margin-bottom: 1.5rem;">
                        <label style="display: block; font-weight: 600; margin-bottom: 0.5rem; color: var(--gray-700);">
                            Votre message
                        </label>
                        <textarea name="message" rows="5" placeholder="Comment pouvons-nous vous aider ?"
                                  style="width: 100%; padding: 0.875rem 1rem; border: 2px solid var(--gray-200); border-radius: var(--radius-lg); font-size: 1rem; transition: all 0.2s; resize: vertical;"
                                  required></textarea>
                    </div>
                    
                    <!-- Submit -->
                    <button type="submit" class="btn btn-primary btn-lg" style="width: 100%;">
                        <img src="{{ asset('images/icons/food-delivery.svg') }}" alt="" style="width: 20px; height: 20px; vertical-align: middle; margin-right: 8px; filter: brightness(10);">
                        Envoyer le message
                    </button>
                </form>
            </div>
            
            <!-- Contact Info -->
            <div>
                <h2 style="font-size: 1.5rem; margin-bottom: 1.5rem;">Informations de contact</h2>
                
                <!-- Address -->
                <div style="display: flex; gap: 1rem; margin-bottom: 1.5rem; padding: 1.5rem; background: var(--gray-50); border-radius: var(--radius-xl);">
                    <div style="width: 50px; height: 50px; background: var(--primary); border-radius: var(--radius-lg); display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                        <img src="{{ asset('images/icons/package-box.svg') }}" alt="Adresse" style="width: 32px; height: 32px;">
                    </div>
                    <div>
                        <h4 style="font-size: 1rem; margin-bottom: 0.25rem;">Adresse</h4>
                        <p style="color: var(--gray-600); margin: 0;">Brazzaville, République du Congo</p>
                    </div>
                </div>
                
                <!-- Email -->
                <div style="display: flex; gap: 1rem; margin-bottom: 1.5rem; padding: 1.5rem; background: var(--gray-50); border-radius: var(--radius-xl);">
                    <div style="width: 50px; height: 50px; background: var(--primary); border-radius: var(--radius-lg); display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                        <i class="fas fa-envelope" style="color: white; font-size: 1.25rem;"></i>
                    </div>
                    <div>
                        <h4 style="font-size: 1rem; margin-bottom: 0.25rem;">Email</h4>
                        <a href="mailto:contact@bantudelice.cg" style="color: var(--primary);">contact@bantudelice.cg</a>
                    </div>
                </div>
                
                <!-- Phone -->
                <div style="display: flex; gap: 1rem; margin-bottom: 1.5rem; padding: 1.5rem; background: var(--gray-50); border-radius: var(--radius-xl);">
                    <div style="width: 50px; height: 50px; background: var(--primary); border-radius: var(--radius-lg); display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                        <i class="fas fa-phone-alt" style="color: white; font-size: 1.25rem;"></i>
                    </div>
                    <div>
                        <h4 style="font-size: 1rem; margin-bottom: 0.25rem;">Téléphone</h4>
                        <a href="tel:+242064000000" style="color: var(--primary);">+242 06 400 00 00</a>
                    </div>
                </div>
                
                <!-- WhatsApp -->
                <div style="display: flex; gap: 1rem; margin-bottom: 2rem; padding: 1.5rem; background: var(--gray-50); border-radius: var(--radius-xl);">
                    <div style="width: 50px; height: 50px; background: #25D366; border-radius: var(--radius-lg); display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                        <i class="fab fa-whatsapp" style="color: white; font-size: 1.5rem;"></i>
                    </div>
                    <div>
                        <h4 style="font-size: 1rem; margin-bottom: 0.25rem;">WhatsApp</h4>
                        <a href="https://wa.me/242064000000" style="color: #25D366;">Discuter sur WhatsApp</a>
                    </div>
                </div>
                
                <!-- Social Links -->
                <h4 style="margin-bottom: 1rem;">Suivez-nous</h4>
                <div style="display: flex; gap: 1rem;">
                    <a href="https://www.facebook.com/bantudelice/" target="_blank" 
                       style="width: 44px; height: 44px; background: #1877F2; color: white; border-radius: var(--radius-lg); display: flex; align-items: center; justify-content: center; transition: transform 0.2s;">
                        <i class="fab fa-facebook-f"></i>
                    </a>
                    <a href="https://twitter.com/bantudelice" target="_blank"
                       style="width: 44px; height: 44px; background: #1DA1F2; color: white; border-radius: var(--radius-lg); display: flex; align-items: center; justify-content: center; transition: transform 0.2s;">
                        <i class="fab fa-twitter"></i>
                    </a>
                    <a href="https://www.instagram.com/bantudelice/" target="_blank"
                       style="width: 44px; height: 44px; background: linear-gradient(45deg, #f09433 0%,#e6683c 25%,#dc2743 50%,#cc2366 75%,#bc1888 100%); color: white; border-radius: var(--radius-lg); display: flex; align-items: center; justify-content: center; transition: transform 0.2s;">
                        <i class="fab fa-instagram"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Map Section -->
<section style="height: 400px;">
    <iframe 
        src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d127535.45561509853!2d15.178615799999999!3d-4.2633597!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x1a6a3130f8e1f2c7%3A0x8e1f3e1f3e1f3e1f!2sBrazzaville%2C%20Republic%20of%20the%20Congo!5e0!3m2!1sen!2s!4v1234567890" 
        width="100%" 
        height="100%" 
        style="border:0;" 
        allowfullscreen="" 
        loading="lazy" 
        referrerpolicy="no-referrer-when-downgrade">
    </iframe>
</section>
@endsection
