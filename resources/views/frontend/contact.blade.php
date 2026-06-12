@php
    $authBrand = app(\App\Services\AuthBrandingService::class)->resolve(request());
    $brandRouteParams = $authBrand['key'] !== 'bantudelice' ? ['brand' => $authBrand['key']] : [];
    $brandColor = $authBrand['primary'];
    $brandDark = $authBrand['primary_dark'];
    $brandSoft = $authBrand['primary_soft'];
@endphp
@extends('frontend.layouts.app-modern')
@section('title', 'Contactez-nous | ' . $authBrand['name'])
@section('description', $authBrand['contact_intro'])
@section('body_class', 'bd-contact-page')
@section('body_style', "--contact-brand-color: {$brandColor}; --contact-brand-dark: {$brandDark}; --contact-brand-soft: {$brandSoft};")

@section('content')
<section class="contact-hero">
    <div class="container">
        <span class="section-badge contact-hero-badge">Contact</span>
        <h1 class="contact-hero-title">Contactez {{ $authBrand['name'] }}</h1>
        <p class="contact-hero-copy">
            {{ $authBrand['contact_intro'] }}
        </p>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="contact-grid">
            <div class="contact-form-card">
                <h2 class="contact-heading">Envoyez-nous un message</h2>
                <p class="contact-subtitle">Décrivez votre besoin et l'équipe {{ $authBrand['name'] }} vous répondra dans les meilleurs délais.</p>

                @if(Session::has('success'))
                    <div class="contact-success">
                        <strong>{{ Session::get('success') }}</strong>
                    </div>
                @endif

                <form action="{{ route('contact', $brandRouteParams) }}" method="post">
                    @csrf
                    @if(isset($brandRouteParams['brand']))
                        <input type="hidden" name="brand" value="{{ $brandRouteParams['brand'] }}">
                    @endif

                    <div class="contact-field">
                        <label class="contact-label">Nom complet</label>
                        <input type="text" name="name" value="{{ old('name') }}" placeholder="Votre nom"
                               class="contact-input"
                               required>
                        @if($errors->has('name'))
                            <span class="contact-error">{{ $errors->first('name') }}</span>
                        @endif
                    </div>

                    <div class="contact-field">
                        <label class="contact-label">Adresse email</label>
                        <input type="email" name="email" value="{{ old('email') }}" placeholder="votre@email.com"
                               class="contact-input"
                               required>
                        @if($errors->has('email'))
                            <span class="contact-error">{{ $errors->first('email') }}</span>
                        @endif
                    </div>

                    <div class="contact-field">
                        <label class="contact-label">Numéro de téléphone</label>
                        <input type="tel" name="phone" value="{{ old('phone') }}" placeholder="{{ $authBrand['support_phone'] }}"
                               class="contact-input"
                               required>
                        @if($errors->has('phone'))
                            <span class="contact-error">{{ $errors->first('phone') }}</span>
                        @endif
                    </div>

                    <div class="contact-field">
                        <label class="contact-label">Sujet</label>
                        <select name="subject" class="contact-input">
                            <option value="" disabled {{ old('subject') ? '' : 'selected' }}>Sélectionnez un sujet</option>
                            <option value="Commande en cours" {{ old('subject') === 'Commande en cours' ? 'selected' : '' }}>Commande en cours</option>
                            <option value="Problème de livraison" {{ old('subject') === 'Problème de livraison' ? 'selected' : '' }}>Problème de livraison</option>
                            <option value="Paiement" {{ old('subject') === 'Paiement' ? 'selected' : '' }}>Paiement</option>
                            <option value="Mon compte" {{ old('subject') === 'Mon compte' ? 'selected' : '' }}>Mon compte</option>
                            <option value="Autre" {{ old('subject') === 'Autre' ? 'selected' : '' }}>Autre</option>
                        </select>
                        @if($errors->has('subject'))
                            <span class="contact-error">{{ $errors->first('subject') }}</span>
                        @endif
                    </div>

                    <div class="contact-field contact-field--last">
                        <label class="contact-label">Votre message</label>
                        <textarea name="message" rows="5" placeholder="Comment pouvons-nous vous aider ?"
                                  class="contact-input contact-input--textarea"
                                  required>{{ old('message') }}</textarea>
                        @if($errors->has('message'))
                            <span class="contact-error">{{ $errors->first('message') }}</span>
                        @endif
                    </div>

                    <button type="submit" class="contact-submit">
                        <img src="{{ asset('images/icons/food-delivery.svg') }}" alt="" class="contact-submit-icon">
                        Envoyer le message
                    </button>
                </form>
            </div>

            <div>
                <h2 class="contact-heading contact-heading--side">Informations de contact</h2>

                <div class="contact-info-card">
                    <div class="contact-info-icon">
                        <img src="{{ asset('images/icons/package-box.svg') }}" alt="Adresse" class="contact-info-icon-image">
                    </div>
                    <div>
                        <h4 class="contact-info-title">Adresse</h4>
                        <p class="contact-info-copy">Brazzaville, République du Congo</p>
                    </div>
                </div>

                <div class="contact-info-card">
                    <div>
                        <h4 class="contact-info-title">Email</h4>
                        <a href="mailto:{{ $authBrand['support_email'] }}" class="contact-brand-link">{{ $authBrand['support_email'] }}</a>
                    </div>
                </div>

                <div class="contact-info-card">
                    <div>
                        <h4 class="contact-info-title">Téléphone</h4>
                        <a href="tel:{{ preg_replace('/[^0-9+]/', '', $authBrand['support_phone']) }}" class="contact-brand-link">{{ $authBrand['support_phone'] }}</a>
                    </div>
                </div>

                <div class="contact-info-card">
                    <div>
                        <h4 class="contact-info-title">WhatsApp</h4>
                        <a href="{{ $authBrand['support_whatsapp'] }}" class="contact-whatsapp-link">Discuter sur WhatsApp</a>
                    </div>
                </div>

                <div class="contact-links-card">
                    <h4 class="contact-info-title">Liens utiles</h4>
                    <div class="contact-links-row">
                        <a href="{{ route('help', $brandRouteParams) }}" class="contact-primary-link">Support</a>
                        <a href="{{ route('privacy.policy', $brandRouteParams) }}" class="contact-secondary-link">Confidentialité</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="contact-map-section">
    <iframe
        src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d127535.45561509853!2d15.178615799999999!3d-4.2633597!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x1a6a3130f8e1f2c7%3A0x8e1f3e1f3e1f3e1f!2sBrazzaville%2C%20Republic%20of%20the%20Congo!5e0!3m2!1sen!2s!4v1234567890"
        width="100%"
        height="100%"
        class="contact-map-frame"
        allowfullscreen=""
        loading="lazy"
        referrerpolicy="no-referrer-when-downgrade">
    </iframe>
</section>
@endsection
