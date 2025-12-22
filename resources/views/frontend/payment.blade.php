@extends('frontend.layouts.app-modern')
@section('title', 'Paiement | BantuDelice')
@section('description', 'Finalisez votre paiement en toute sécurité sur BantuDelice.')

@section('styles')
<style>
    .payment-container {
        max-width: 600px;
        margin: 0 auto;
    }
    
    .payment-card {
        background: white;
        border-radius: 24px;
        box-shadow: 0 10px 40px rgba(0,0,0,0.08);
        overflow: hidden;
    }
    
    .payment-header {
        background: linear-gradient(135deg, #FF6B35 0%, #F59E0B 100%);
        padding: 2rem;
        text-align: center;
        color: white;
    }
    
    .payment-header h2 {
        font-size: 1.5rem;
        font-weight: 700;
        margin: 0;
    }
    
    .payment-header p {
        opacity: 0.9;
        margin: 0.5rem 0 0;
        font-size: 0.9375rem;
    }
    
    .payment-body {
        padding: 2rem;
    }
    
    .payment-methods {
        display: grid;
        gap: 1rem;
        margin-bottom: 2rem;
    }
    
    .payment-method {
        display: flex;
        align-items: center;
        gap: 1rem;
        padding: 1rem 1.25rem;
        border: 2px solid #E5E7EB;
        border-radius: 12px;
        cursor: pointer;
        transition: all 0.2s;
    }
    
    .payment-method:hover {
        border-color: #FF6B35;
        background: rgba(255, 107, 53, 0.03);
    }
    
    .payment-method.active {
        border-color: #FF6B35;
        background: rgba(255, 107, 53, 0.05);
    }
    
    .payment-method-icon {
        width: 48px;
        height: 48px;
        background: #F3F4F6;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
    }
    
    .payment-method.active .payment-method-icon {
        background: #FF6B35;
        color: white;
    }
    
    .payment-method-info {
        flex: 1;
    }
    
    .payment-method-info h4 {
        font-size: 1rem;
        font-weight: 600;
        margin: 0 0 0.25rem;
        color: #1F2937;
    }
    
    .payment-method-info p {
        font-size: 0.8125rem;
        color: #6B7280;
        margin: 0;
    }
    
    .payment-method-check {
        width: 24px;
        height: 24px;
        border: 2px solid #E5E7EB;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.2s;
    }
    
    .payment-method.active .payment-method-check {
        background: #FF6B35;
        border-color: #FF6B35;
        color: white;
    }
    
    .card-form {
        display: none;
        margin-top: 1.5rem;
        padding: 1.5rem;
        background: #F9FAFB;
        border-radius: 16px;
    }
    
    .card-form.active {
        display: block;
    }
    
    .form-group {
        margin-bottom: 1rem;
    }
    
    .form-group label {
        display: block;
        font-weight: 600;
        margin-bottom: 0.5rem;
        color: #374151;
        font-size: 0.875rem;
    }
    
    .form-control {
        width: 100%;
        padding: 0.875rem 1rem;
        border: 2px solid #E5E7EB;
        border-radius: 12px;
        font-size: 1rem;
        transition: border-color 0.2s;
    }
    
    .form-control:focus {
        outline: none;
        border-color: #FF6B35;
    }
    
    .form-row {
        display: grid;
        grid-template-columns: 2fr 1fr 1fr;
        gap: 1rem;
    }
    
    .momo-form {
        display: none;
        margin-top: 1.5rem;
        padding: 1.5rem;
        background: #F9FAFB;
        border-radius: 16px;
    }
    
    .momo-form.active {
        display: block;
    }
    
    .order-summary {
        background: #F9FAFB;
        border-radius: 16px;
        padding: 1.5rem;
        margin-bottom: 2rem;
    }
    
    .order-summary h4 {
        font-size: 1rem;
        font-weight: 600;
        margin: 0 0 1rem;
        color: #1F2937;
    }
    
    .summary-row {
        display: flex;
        justify-content: space-between;
        margin-bottom: 0.5rem;
        font-size: 0.9375rem;
    }
    
    .summary-row.total {
        font-weight: 700;
        font-size: 1.125rem;
        padding-top: 1rem;
        margin-top: 1rem;
        border-top: 2px solid #E5E7EB;
        color: #1F2937;
    }
    
    .summary-row.total .amount {
        color: #FF6B35;
    }
    
    .secure-badge {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        margin-top: 1.5rem;
        color: #6B7280;
        font-size: 0.8125rem;
    }
    
    .secure-badge i {
        color: #10B981;
    }
    
    .payment-logos {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 1.5rem;
        margin-top: 1rem;
        padding-top: 1rem;
        border-top: 1px solid #E5E7EB;
    }
    
    .payment-logos img {
        height: 24px;
        opacity: 0.6;
    }
    
    .alert {
        padding: 1rem 1.25rem;
        border-radius: 12px;
        margin-bottom: 1.5rem;
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }
    
    .alert-success {
        background: rgba(16, 185, 129, 0.1);
        color: #047857;
    }
    
    .alert-danger {
        background: rgba(239, 68, 68, 0.1);
        color: #B91C1C;
    }
    
    .error-message {
        color: #EF4444;
        font-size: 0.8125rem;
        margin-top: 0.5rem;
        display: none;
    }
    
    .form-control.error {
        border-color: #EF4444;
    }
    
    @media (max-width: 640px) {
        .form-row {
            grid-template-columns: 1fr;
        }
    }
</style>
@endsection

@section('content')
<!-- Hero Section -->
<section style="background: linear-gradient(135deg, #FF6B35 0%, #F59E0B 100%); padding: 120px 0 60px;">
    <div class="container" style="text-align: center;">
        <h1 style="color: white; font-size: 2rem; font-weight: 700; margin: 0;">
            <i class="fas fa-lock"></i> Paiement Sécurisé
        </h1>
        <p style="color: rgba(255,255,255,0.9); margin-top: 0.5rem; font-size: 1rem;">
            Finalisez votre commande en toute sécurité
        </p>
    </div>
</section>

<!-- Payment Section -->
<section class="section" style="background: #F9FAFB; padding: 3rem 0;">
    <div class="container">
        <div class="payment-container">
            
            <!-- Stepper -->
            <div style="display: flex; justify-content: center; gap: 0.5rem; margin-bottom: 2rem;">
                <div style="display: flex; align-items: center;">
                    <div style="width: 32px; height: 32px; background: #10B981; color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 600; font-size: 0.875rem;">
                        <i class="fas fa-check"></i>
                    </div>
                    <span style="margin-left: 0.5rem; font-size: 0.875rem; color: #10B981; font-weight: 600;">Panier</span>
                </div>
                <div style="width: 40px; height: 2px; background: #10B981; margin: 0 0.5rem; align-self: center;"></div>
                <div style="display: flex; align-items: center;">
                    <div style="width: 32px; height: 32px; background: #10B981; color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 600; font-size: 0.875rem;">
                        <i class="fas fa-check"></i>
                    </div>
                    <span style="margin-left: 0.5rem; font-size: 0.875rem; color: #10B981; font-weight: 600;">Livraison</span>
                </div>
                <div style="width: 40px; height: 2px; background: #FF6B35; margin: 0 0.5rem; align-self: center;"></div>
                <div style="display: flex; align-items: center;">
                    <div style="width: 32px; height: 32px; background: #FF6B35; color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 600; font-size: 0.875rem;">3</div>
                    <span style="margin-left: 0.5rem; font-size: 0.875rem; color: #FF6B35; font-weight: 600;">Paiement</span>
                </div>
            </div>
            
            <div class="payment-card">
                <div class="payment-body">
                    
                    <!-- Alerts -->
                    @if (Session::has('success'))
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle"></i>
                            <span>{{ Session::get('success') }}</span>
                        </div>
                    @endif
                    
                    @if (Session::has('error'))
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle"></i>
                            <span>{{ Session::get('error') }}</span>
                        </div>
                    @endif
                    
                    <!-- Order Summary -->
                    <div class="order-summary">
                        <h4><i class="fas fa-receipt" style="color: #FF6B35; margin-right: 0.5rem;"></i> Récapitulatif de votre commande</h4>
                        <div class="summary-row">
                            <span style="color: #6B7280;">Sous-total</span>
                            <span>{{ number_format($subTotal ?? 0, 0, ',', ' ') }} FCFA</span>
                        </div>
                        <div class="summary-row">
                            <span style="color: #6B7280;">Frais de livraison</span>
                            <span>{{ number_format($deliveryFee ?? 1500, 0, ',', ' ') }} FCFA</span>
                        </div>
                        <div class="summary-row">
                            <span style="color: #6B7280;">Taxes</span>
                            <span>{{ number_format($tax ?? 0, 0, ',', ' ') }} FCFA</span>
                        </div>
                        @if(isset($discount) && $discount > 0)
                        <div class="summary-row" style="color: #10B981;">
                            <span>Réduction</span>
                            <span>-{{ number_format($discount, 0, ',', ' ') }} FCFA</span>
                        </div>
                        @endif
                        <div class="summary-row total">
                            <span>Total à payer</span>
                            <span class="amount">{{ number_format($total ?? 0, 0, ',', ' ') }} FCFA</span>
                        </div>
                    </div>
                    
                    <!-- Payment Methods -->
                    <h4 style="font-size: 1rem; font-weight: 600; margin: 0 0 1rem; color: #1F2937;">
                        <i class="fas fa-credit-card" style="color: #FF6B35; margin-right: 0.5rem;"></i> Choisissez votre mode de paiement
                    </h4>
                    
                    <div class="payment-methods">
                        <!-- Cash -->
                        <div class="payment-method active" data-method="cash" onclick="selectPaymentMethod('cash')">
                            <div class="payment-method-icon">
                                <i class="fas fa-money-bill-wave"></i>
                            </div>
                            <div class="payment-method-info">
                                <h4>Paiement à la livraison</h4>
                                <p>Payez en espèces à la réception de votre commande</p>
                            </div>
                            <div class="payment-method-check">
                                <i class="fas fa-check" style="font-size: 0.75rem;"></i>
                            </div>
                        </div>
                        
                        <!-- Mobile Money -->
                        <div class="payment-method" data-method="momo" onclick="selectPaymentMethod('momo')">
                            <div class="payment-method-icon">
                                <i class="fas fa-mobile-alt"></i>
                            </div>
                            <div class="payment-method-info">
                                <h4>Mobile Money</h4>
                                <p>MTN Mobile Money, Airtel Money</p>
                            </div>
                            <div class="payment-method-check">
                                <i class="fas fa-check" style="font-size: 0.75rem;"></i>
                            </div>
                        </div>
                        
                        <!-- Card -->
                        <div class="payment-method" data-method="card" onclick="selectPaymentMethod('card')">
                            <div class="payment-method-icon">
                                <i class="fas fa-credit-card"></i>
                            </div>
                            <div class="payment-method-info">
                                <h4>Carte bancaire</h4>
                                <p>Visa, Mastercard, autres cartes</p>
                            </div>
                            <div class="payment-method-check">
                                <i class="fas fa-check" style="font-size: 0.75rem;"></i>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Mobile Money Form -->
                    <div class="momo-form" id="momoForm">
                        <h4 style="font-size: 0.9375rem; font-weight: 600; margin: 0 0 1rem; color: #1F2937;">Détails Mobile Money</h4>
                        <div class="form-group">
                            <label>Numéro de téléphone</label>
                            <input type="tel" class="form-control" id="momoPhone" placeholder="+242 06 XXX XX XX" autocomplete="tel">
                        </div>
                        <div class="form-group">
                            <label>Opérateur</label>
                            <select class="form-control" id="momoOperator">
                                <option value="">Sélectionnez votre opérateur</option>
                                <option value="mtn">MTN Mobile Money</option>
                                <option value="airtel">Airtel Money</option>
                            </select>
                        </div>
                        <p style="font-size: 0.8125rem; color: #6B7280; margin: 0;">
                            <i class="fas fa-info-circle" style="color: #3B82F6;"></i>
                            Vous recevrez une notification sur votre téléphone pour confirmer le paiement.
                        </p>
                    </div>
                    
                    <!-- Card Form -->
                    <div class="card-form" id="cardForm">
                        <h4 style="font-size: 0.9375rem; font-weight: 600; margin: 0 0 1rem; color: #1F2937;">Informations de carte</h4>
                        <form id="paymentForm" role="form" action="{{ route('stripe.post') }}" method="post" class="require-validation"
                              data-cc-on-file="false"
                              data-stripe-publishable-key="{{ env('STRIPE_KEY') }}">
                            @csrf
                            
                            <div class="form-group">
                                <label>Nom sur la carte</label>
                                <input type="text" class="form-control" id="cardName" placeholder="JEAN DUPONT" autocomplete="cc-name">
                            </div>
                            
                            <div class="form-group">
                                <label>Numéro de carte</label>
                                <input type="text" class="form-control card-number" id="cardNumber" placeholder="1234 5678 9012 3456" autocomplete="cc-number" maxlength="19">
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Date d'expiration</label>
                                    <div style="display: flex; gap: 0.5rem;">
                                        <input type="text" class="form-control card-expiry-month" id="cardMonth" placeholder="MM" maxlength="2" style="flex: 1;">
                                        <input type="text" class="form-control card-expiry-year" id="cardYear" placeholder="AA" maxlength="2" style="flex: 1;">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label>CVV</label>
                                    <input type="text" class="form-control card-cvc" id="cardCvv" placeholder="123" maxlength="4" autocomplete="cc-csc">
                                </div>
                            </div>
                            
                            <div class="error-message" id="cardError">
                                <i class="fas fa-exclamation-circle"></i> <span></span>
                            </div>
                        </form>
                    </div>
                    
                    <!-- Submit Button -->
                    <button type="button" id="payButton" class="btn btn-primary btn-lg" style="width: 100%; margin-top: 1.5rem; padding: 1rem 2rem;" onclick="processPayment()">
                        <i class="fas fa-lock" style="margin-right: 0.5rem;"></i>
                        <span id="payButtonText">Confirmer et payer</span>
                        <span id="payButtonAmount" style="font-weight: 700; margin-left: 0.5rem;">{{ number_format($total ?? 0, 0, ',', ' ') }} FCFA</span>
                    </button>
                    
                    <!-- Secure Badge -->
                    <div class="secure-badge">
                        <i class="fas fa-shield-alt"></i>
                        <span>Paiement 100% sécurisé</span>
                    </div>
                    
                    <div class="payment-logos">
                        <i class="fab fa-cc-visa" style="font-size: 2rem; color: #1A1F71;"></i>
                        <i class="fab fa-cc-mastercard" style="font-size: 2rem; color: #EB001B;"></i>
                        <img src="{{ asset('images/icons/mtn-momo.png') }}" alt="MTN MoMo" style="height: 24px;" onerror="this.style.display='none'">
                        <img src="{{ asset('images/icons/airtel-money.png') }}" alt="Airtel Money" style="height: 24px;" onerror="this.style.display='none'">
                    </div>
                </div>
            </div>
            
            <!-- Back Link -->
            <div style="text-align: center; margin-top: 1.5rem;">
                <a href="{{ route('checkout.detail') }}" style="color: #6B7280; text-decoration: none; font-size: 0.9375rem;">
                    <i class="fas fa-arrow-left"></i> Retour au panier
                </a>
            </div>
            
        </div>
    </div>
</section>
@endsection

@section('scripts')
<script src="https://js.stripe.com/v2/"></script>
<script>
    let selectedPaymentMethod = 'cash';
    
    function selectPaymentMethod(method) {
        selectedPaymentMethod = method;
        
        // Mise à jour visuelle
        document.querySelectorAll('.payment-method').forEach(el => {
            el.classList.remove('active');
        });
        document.querySelector(`[data-method="${method}"]`).classList.add('active');
        
        // Afficher/masquer les formulaires
        document.getElementById('cardForm').classList.remove('active');
        document.getElementById('momoForm').classList.remove('active');
        
        if (method === 'card') {
            document.getElementById('cardForm').classList.add('active');
        } else if (method === 'momo') {
            document.getElementById('momoForm').classList.add('active');
        }
        
        // Mettre à jour le bouton
        const payButtonText = document.getElementById('payButtonText');
        if (method === 'cash') {
            payButtonText.textContent = 'Confirmer la commande';
        } else {
            payButtonText.textContent = 'Payer maintenant';
        }
    }
    
    function processPayment() {
        const payButton = document.getElementById('payButton');
        payButton.disabled = true;
        payButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Traitement en cours...';
        
        if (selectedPaymentMethod === 'cash') {
            // Soumettre le formulaire de commande pour paiement à la livraison
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '{{ route("place.order") }}';
            
            const csrfInput = document.createElement('input');
            csrfInput.type = 'hidden';
            csrfInput.name = '_token';
            csrfInput.value = '{{ csrf_token() }}';
            form.appendChild(csrfInput);
            
            const methodInput = document.createElement('input');
            methodInput.type = 'hidden';
            methodInput.name = 'payment_method';
            methodInput.value = 'cash';
            form.appendChild(methodInput);
            
            document.body.appendChild(form);
            form.submit();
        } else if (selectedPaymentMethod === 'momo') {
            // Traitement Mobile Money
            const phone = document.getElementById('momoPhone').value;
            const operator = document.getElementById('momoOperator').value;
            
            if (!phone || !operator) {
                showError('Veuillez remplir tous les champs Mobile Money');
                payButton.disabled = false;
                payButton.innerHTML = '<i class="fas fa-lock" style="margin-right: 0.5rem;"></i><span id="payButtonText">Payer maintenant</span><span id="payButtonAmount" style="font-weight: 700; margin-left: 0.5rem;">{{ number_format($total ?? 0, 0, ",", " ") }} FCFA</span>';
                return;
            }
            
            // Soumettre le formulaire de commande avec Mobile Money
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '{{ route("place.order") }}';
            
            const csrfInput = document.createElement('input');
            csrfInput.type = 'hidden';
            csrfInput.name = '_token';
            csrfInput.value = '{{ csrf_token() }}';
            form.appendChild(csrfInput);
            
            const methodInput = document.createElement('input');
            methodInput.type = 'hidden';
            methodInput.name = 'payment_method';
            methodInput.value = 'momo';
            form.appendChild(methodInput);
            
            const phoneInput = document.createElement('input');
            phoneInput.type = 'hidden';
            phoneInput.name = 'momo_phone';
            phoneInput.value = phone;
            form.appendChild(phoneInput);
            
            const operatorInput = document.createElement('input');
            operatorInput.type = 'hidden';
            operatorInput.name = 'momo_operator';
            operatorInput.value = operator;
            form.appendChild(operatorInput);
            
            document.body.appendChild(form);
            form.submit();
        } else if (selectedPaymentMethod === 'card') {
            // Traitement carte (Stripe)
            processCardPayment();
        }
    }
    
    function processCardPayment() {
        const form = document.getElementById('paymentForm');
        const payButton = document.getElementById('payButton');
        const cardError = document.getElementById('cardError');
        
        // Validation basique
        const cardNumber = document.getElementById('cardNumber').value.replace(/\s/g, '');
        const cardMonth = document.getElementById('cardMonth').value;
        const cardYear = document.getElementById('cardYear').value;
        const cardCvv = document.getElementById('cardCvv').value;
        
        if (!cardNumber || cardNumber.length < 14) {
            showError('Numéro de carte invalide');
            payButton.disabled = false;
            payButton.innerHTML = '<i class="fas fa-lock" style="margin-right: 0.5rem;"></i><span>Payer maintenant</span><span style="font-weight: 700; margin-left: 0.5rem;">{{ number_format($total ?? 0, 0, ",", " ") }} FCFA</span>';
            return;
        }
        
        if (!cardMonth || !cardYear) {
            showError('Date d\'expiration invalide');
            payButton.disabled = false;
            payButton.innerHTML = '<i class="fas fa-lock" style="margin-right: 0.5rem;"></i><span>Payer maintenant</span><span style="font-weight: 700; margin-left: 0.5rem;">{{ number_format($total ?? 0, 0, ",", " ") }} FCFA</span>';
            return;
        }
        
        if (!cardCvv || cardCvv.length < 3) {
            showError('CVV invalide');
            payButton.disabled = false;
            payButton.innerHTML = '<i class="fas fa-lock" style="margin-right: 0.5rem;"></i><span>Payer maintenant</span><span style="font-weight: 700; margin-left: 0.5rem;">{{ number_format($total ?? 0, 0, ",", " ") }} FCFA</span>';
            return;
        }
        
        // Créer un token Stripe
        Stripe.setPublishableKey(form.dataset.stripePublishableKey);
        Stripe.createToken({
            number: cardNumber,
            cvc: cardCvv,
            exp_month: cardMonth,
            exp_year: '20' + cardYear
        }, function(status, response) {
            if (response.error) {
                showError(response.error.message);
                payButton.disabled = false;
                payButton.innerHTML = '<i class="fas fa-lock" style="margin-right: 0.5rem;"></i><span>Payer maintenant</span><span style="font-weight: 700; margin-left: 0.5rem;">{{ number_format($total ?? 0, 0, ",", " ") }} FCFA</span>';
            } else {
                // Token créé avec succès, envoyer au serveur
                const hiddenInput = document.createElement('input');
                hiddenInput.type = 'hidden';
                hiddenInput.name = 'stripeToken';
                hiddenInput.value = response.id;
                form.appendChild(hiddenInput);
                form.submit();
            }
        });
    }
    
    function showError(message) {
        const cardError = document.getElementById('cardError');
        cardError.querySelector('span').textContent = message;
        cardError.style.display = 'block';
        
        // Afficher une notification
        const notification = document.createElement('div');
        notification.style.cssText = 'position: fixed; top: 20px; right: 20px; background: #EF4444; color: white; padding: 1rem 1.5rem; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.15); z-index: 10000; max-width: 300px;';
        notification.innerHTML = `<i class="fas fa-exclamation-circle"></i> ${message}`;
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.style.opacity = '0';
            notification.style.transition = 'opacity 0.3s';
            setTimeout(() => notification.remove(), 300);
        }, 5000);
    }
    
    // Formatage du numéro de carte
    document.getElementById('cardNumber').addEventListener('input', function(e) {
        let value = e.target.value.replace(/\s/g, '').replace(/\D/g, '');
        let formatted = '';
        for (let i = 0; i < value.length; i++) {
            if (i > 0 && i % 4 === 0) {
                formatted += ' ';
            }
            formatted += value[i];
        }
        e.target.value = formatted;
    });
    
    // Limitez les champs numériques
    ['cardMonth', 'cardYear', 'cardCvv'].forEach(id => {
        document.getElementById(id).addEventListener('input', function(e) {
            e.target.value = e.target.value.replace(/\D/g, '');
        });
    });
</script>
@endsection
