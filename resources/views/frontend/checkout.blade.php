@extends('frontend.layouts.app-modern')
@section('title', 'Paiement | BantuDelice')
@section('description', 'Finalisez votre commande BantuDelice.')

@section('content')
<!-- Hero Section -->
<section style="background: linear-gradient(135deg, var(--secondary) 0%, var(--accent) 100%); padding: 120px 0 40px; text-align: center;">
    <div class="container">
        <h1 style="color: white; font-size: 2rem;">Finaliser la commande</h1>
    </div>
</section>

<!-- Progress Steps -->
<section style="background: white; padding: 1.5rem 0; border-bottom: 1px solid var(--gray-100);">
    <div class="container">
        <div style="display: flex; justify-content: center; align-items: center; gap: 0;">
            <div style="display: flex; align-items: center;">
                <div style="width: 36px; height: 36px; background: var(--success); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700;">
                    <i class="fas fa-check"></i>
                </div>
                <span style="margin-left: 0.5rem; font-weight: 600; color: var(--success);">Panier</span>
            </div>
            <div style="width: 60px; height: 3px; background: var(--success); margin: 0 0.5rem;"></div>
            <div style="display: flex; align-items: center;">
                <div style="width: 36px; height: 36px; background: var(--primary); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700;">2</div>
                <span style="margin-left: 0.5rem; font-weight: 600; color: var(--primary);">Paiement</span>
            </div>
            <div style="width: 60px; height: 3px; background: var(--gray-200); margin: 0 0.5rem;"></div>
            <div style="display: flex; align-items: center;">
                <div style="width: 36px; height: 36px; background: var(--gray-200); color: var(--gray-500); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700;">3</div>
                <span style="margin-left: 0.5rem; font-weight: 500; color: var(--gray-400);">Confirmeration</span>
            </div>
        </div>
    </div>
</section>

<!-- Checkout Content -->
<section class="section" style="background: var(--gray-50);">
    <div class="container">
        @if(session()->has('message'))
            <div style="background: var(--warning); color: white; padding: 1rem; border-radius: var(--radius-lg); margin-bottom: 1.5rem; text-align: center;">
                <strong>{{ session()->get('message') }}</strong>
            </div>
        @endif
        
        @if($errors->any())
            <div style="background: var(--error); color: white; padding: 1rem; border-radius: var(--radius-lg); margin-bottom: 1.5rem;">
                <ul style="margin: 0; padding-left: 1.5rem;">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
        
        <form class="form-horizontal" method="post" action="{{ route('place.order') }}" id="checkoutForm">
            @csrf
            <div style="display: grid; grid-template-columns: 1fr 400px; gap: 2rem; align-items: start;">
                
                <!-- Left Column - Address & Tips -->
                <div>
                    <!-- Delivery Address -->
                    <div style="background: white; padding: 1.5rem; border-radius: var(--radius-xl); box-shadow: var(--shadow-sm); margin-bottom: 1.5rem;">
                        <h3 style="font-size: 1.125rem; margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem;">
                            <i class="fas fa-map-marker-alt" style="color: var(--primary);"></i>
                            Adresse de livraison
                        </h3>
                        
                        <div style="display: grid; gap: 1rem;">
                            <div>
                                <label style="display: block; font-weight: 600; margin-bottom: 0.5rem; color: var(--gray-700); font-size: 0.875rem;">Nom</label>
                                <input type="text" value="@if(Auth::check()) {{auth()->user()->name}} @endif" 
                                       style="width: 100%; padding: 0.75rem 1rem; border: 2px solid var(--gray-200); border-radius: var(--radius-lg); background: var(--gray-50);" disabled>
                            </div>
                            
                            <div>
                                <label style="display: block; font-weight: 600; margin-bottom: 0.5rem; color: var(--gray-700); font-size: 0.875rem;">Adresse de livraison *</label>
                                <input type="text" id="searchMapInput" name="delivery_address" placeholder="Entrez votre adresse de livraison"
                                       style="width: 100%; padding: 0.75rem 1rem; border: 2px solid var(--gray-200); border-radius: var(--radius-lg);" required>
                                <input type="hidden" id="latitude" name="d_lat" value="-4.2767">
                                <input type="hidden" id="longitude" name="d_lng" value="15.2832">
                                @if($errors->has('delivery_address'))
                                    <span style="color: var(--error); font-size: 0.875rem;">{{ $errors->first('delivery_address') }}</span>
                                @endif
                            </div>
                            
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                                <div>
                                    <label style="display: block; font-weight: 600; margin-bottom: 0.5rem; color: var(--gray-700); font-size: 0.875rem;">Téléphone</label>
                                    <input type="text" value="@if(Auth::check()) {{auth()->user()->phone}} @endif" 
                                           style="width: 100%; padding: 0.75rem 1rem; border: 2px solid var(--gray-200); border-radius: var(--radius-lg); background: var(--gray-50);" disabled>
                                </div>
                                <div>
                                    <label style="display: block; font-weight: 600; margin-bottom: 0.5rem; color: var(--gray-700); font-size: 0.875rem;">Email</label>
                                    <input type="text" value="@if(Auth::check()) {{auth()->user()->email}} @endif" 
                                           style="width: 100%; padding: 0.75rem 1rem; border: 2px solid var(--gray-200); border-radius: var(--radius-lg); background: var(--gray-50);" disabled>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Map -->
                        <div id="map" style="height: 250px; border-radius: var(--radius-lg); margin-top: 1rem; overflow: hidden;"></div>
                    </div>
                    
                    <!-- Tips & Voucher -->
                    <div style="background: white; padding: 1.5rem; border-radius: var(--radius-xl); box-shadow: var(--shadow-sm); margin-bottom: 1.5rem;">
                        <h3 style="font-size: 1.125rem; margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem;">
                            <i class="fas fa-gift" style="color: var(--primary);"></i>
                            Pourboire & Code promo
                        </h3>
                        
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                            <div>
                                <label style="display: block; font-weight: 600; margin-bottom: 0.5rem; color: var(--gray-700); font-size: 0.875rem;">Pourboire livreur (FCFA)</label>
                                <div style="display: flex; gap: 0.5rem;">
                                    <input type="number" min="0" id="tip" name="driver_tip" placeholder="0" value="0"
                                           style="flex: 1; padding: 0.75rem 1rem; border: 2px solid var(--gray-200); border-radius: var(--radius-lg);">
                                    <button type="button" onclick="myFunction()" class="btn btn-secondary" style="padding: 0.75rem 1rem;">
                                        <i class="fas fa-plus"></i>
                                    </button>
                                </div>
                            </div>
                            <div>
                                <label style="display: block; font-weight: 600; margin-bottom: 0.5rem; color: var(--gray-700); font-size: 0.875rem;">Code promo</label>
                                <div style="display: flex; gap: 0.5rem;">
                                    <input type="text" id="voucher" name="voucher_code" placeholder="Entrez votre code" 
                                           style="flex: 1; padding: 0.75rem 1rem; border: 2px solid var(--gray-200); border-radius: var(--radius-lg);">
                                    <button type="button" id="applyVoucher" class="btn btn-secondary" style="padding: 0.75rem 1rem;">
                                        Appliquer
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Méthode de Paiement -->
                    <div style="background: white; padding: 1.5rem; border-radius: var(--radius-xl); box-shadow: var(--shadow-sm);">
                        <h3 style="font-size: 1.125rem; margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem;">
                            <i class="fas fa-credit-card" style="color: var(--primary);"></i>
                            Mode de paiement
                        </h3>
                        
                        <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                            <!-- Cash -->
                            <label style="display: flex; align-items: center; padding: 1rem; border: 2px solid var(--gray-200); border-radius: var(--radius-lg); cursor: pointer; transition: all 0.2s;" class="payment-option" data-method="cash">
                                <input type="radio" name="payment_method" value="cash" checked style="margin-right: 1rem; width: 20px; height: 20px;">
                                <div style="flex: 1;">
                                    <span style="font-weight: 600; display: block;">💵 Paiement à la livraison</span>
                                    <span style="font-size: 0.8125rem; color: var(--gray-500);">Payez en espèces à la réception</span>
                                </div>
                            </label>
                            
                            <!-- Mobile Money -->
                            <label style="display: flex; align-items: center; padding: 1rem; border: 2px solid var(--gray-200); border-radius: var(--radius-lg); cursor: pointer; transition: all 0.2s;" class="payment-option" data-method="mobile_money">
                                <input type="radio" name="payment_method" value="mobile_money" style="margin-right: 1rem; width: 20px; height: 20px;">
                                <div style="flex: 1;">
                                    <span style="font-weight: 600; display: block;">📱 Mobile Money</span>
                                    <span style="font-size: 0.8125rem; color: var(--gray-500);">MTN Mobile Money, Airtel Money</span>
                                </div>
                            </label>
                            
                            <!-- PayPal -->
                            <label style="display: flex; align-items: center; padding: 1rem; border: 2px solid var(--gray-200); border-radius: var(--radius-lg); cursor: pointer; transition: all 0.2s;" class="payment-option" data-method="paypal">
                                <input type="radio" name="payment_method" value="paypal" style="margin-right: 1rem; width: 20px; height: 20px;">
                                <div style="flex: 1;">
                                    <span style="font-weight: 600; display: block;">💳 PayPal / Carte bancaire</span>
                                    <span style="font-size: 0.8125rem; color: var(--gray-500);">Visa, MasterCard, PayPal</span>
                                </div>
                            </label>
                        </div>
                    </div>
                </div>
                
                <!-- Right Column - Order Summary -->
                <div style="position: sticky; top: 100px;">
                    <div style="background: white; padding: 1.5rem; border-radius: var(--radius-xl); box-shadow: var(--shadow-lg);">
                        <h3 style="font-size: 1.125rem; margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem;">
                            <i class="fas fa-receipt" style="color: var(--primary);"></i>
                            Récapitulatif
                            <a href="{{ route('cart.detail') }}" style="margin-left: auto; font-size: 0.8125rem; color: var(--primary);">Modifier</a>
                        </h3>
                        
                        <!-- Items -->
                        <div style="max-height: 300px; overflow-y: auto; margin-bottom: 1rem;">
                            @foreach($checkoutData as $checkout)
                            <div style="display: flex; gap: 1rem; padding: 0.75rem 0; border-bottom: 1px solid var(--gray-100);">
                                <img src="{{ $checkout->image ? (strpos($checkout->image, 'http') === 0 ? $checkout->image : asset('images/product_images/' . $checkout->image)) : asset('images/product_images/default-food.jpg') }}" 
                                     style="width: 50px; height: 50px; border-radius: var(--radius-md); object-fit: cover;">
                                <div style="flex: 1;">
                                    <p style="font-weight: 600; font-size: 0.9375rem; margin: 0;">{{$checkout->name}}</p>
                                    <p style="color: var(--gray-500); font-size: 0.8125rem; margin: 0;">Qté: {{$checkout->qty}}</p>
                                </div>
                                <p style="font-weight: 700; color: var(--primary);">{{number_format($checkout->price, 0, ',', ' ')}} F</p>
                            </div>
                            @endforeach
                        </div>
                        
                        <!-- Totals -->
                        @php
                            $tax = $charges->tax/100 * $total;
                            $service_fee = ($charges->delivery_fee + $tax + $total)/100 * $charges->service_fee;
                            $loyaltyDiscount = 0;
                            $loyaltyPoints = 0;
                            if(auth()->check()) {
                                $loyaltyPoints = \App\Services\LoyaltyService::getBalance(auth()->user()->id);
                                $maxDiscount = \App\Services\LoyaltyService::calculateDiscount($loyaltyPoints);
                                // Limiter la réduction à 20% du total
                                $loyaltyDiscount = min($maxDiscount, ($total + $charges->delivery_fee + $tax + $service_fee) * 0.2);
                            }
                            $grandTotal = $total + $charges->delivery_fee + $tax + $service_fee - $loyaltyDiscount;
                        @endphp
                        
                        <div style="border-top: 2px solid var(--gray-100); padding-top: 1rem;">
                            <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                                <span style="color: var(--gray-600);">Sous-total</span>
                                <span style="font-weight: 600;" id="checkoutSubtotal">{{number_format($total, 0, ',', ' ')}} FCFA</span>
                            </div>
                            <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                                <span style="color: var(--gray-600);">Frais de livraison</span>
                                <span id="deliveryFee">{{number_format($charges->delivery_fee, 0, ',', ' ')}} FCFA</span>
                            </div>
                            <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                                <span style="color: var(--gray-600);">Frais de service</span>
                                <span id="serviceFee">{{number_format($service_fee, 0, ',', ' ')}} FCFA</span>
                            </div>
                            <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                                <span style="color: var(--gray-600);">Taxes</span>
                                <span id="taxAmount">{{number_format($tax, 0, ',', ' ')}} FCFA</span>
                            </div>
                            
                            @if(auth()->check() && $loyaltyPoints > 0)
                            <div style="background: linear-gradient(135deg, rgba(16, 185, 129, 0.1) 0%, rgba(5, 150, 105, 0.1) 100%); padding: 1rem; border-radius: 12px; margin: 1rem 0;">
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                                    <span style="color: #059669; font-weight: 600;">
                                        <i class="fas fa-star"></i> Réduction points fidélité
                                    </span>
                                    <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                                        <input type="checkbox" id="useLoyaltyPoints" name="use_loyalty_points" value="1" 
                                               style="width: 18px; height: 18px; accent-color: #10B981;"
                                               onchange="updateLoyaltyDiscount()">
                                        <span style="font-size: 0.875rem; color: #059669;">Utiliser mes points</span>
                                    </label>
                                </div>
                                <div id="loyaltyDiscountRow" style="display: none; margin-top: 0.5rem; padding-top: 0.5rem; border-top: 1px solid rgba(5, 150, 105, 0.2);">
                                    <div style="display: flex; justify-content: space-between;">
                                        <span style="color: #059669;">Réduction appliquée</span>
                                        <span style="font-weight: 700; color: #059669;" id="loyaltyDiscountAmount">-{{ number_format($loyaltyDiscount, 0, ',', ' ') }} FCFA</span>
                                    </div>
                                    <input type="hidden" name="loyalty_points_used" id="loyaltyPointsUsed" value="0">
                                </div>
                            </div>
                            @endif
                            
                            <div style="display: flex; justify-content: space-between; padding-top: 1rem; border-top: 2px solid var(--gray-800);">
                                <span style="font-size: 1.125rem; font-weight: 700;">Total</span>
                                <span style="font-size: 1.25rem; font-weight: 800; color: var(--primary);" id="ttotal">{{number_format($grandTotal, 0, ',', ' ')}} FCFA</span>
                            </div>
                        </div>
                        
                        <!-- Hidden fields -->
                        <input type="hidden" name="qty" value="1">
                        <input type="hidden" id="restaurant" value="{{$resturant->restaurant_id}}">
                        <input type="hidden" name="sub_total" value="{{$total}}">
                        <input type="hidden" name="tax" value="{{$charges->tax}}">
                        <input type="hidden" name="delivery_charges" value="{{$charges->delivery_fee}}">
                        <input type="hidden" id="sub_total" value="{{$grandTotal}}">
                        <input type="hidden" id="total" name="amount" value="{{$grandTotal}}">
                        
                        <!-- Payment Button -->
                        <button type="submit" id="checkoutSubmitBtn" class="btn btn-primary btn-lg" style="width: 100%; margin-top: 1.5rem;">
                            <i class="fas fa-lock"></i> <span id="btnText">Passer la commande</span>
                        </button>
                        
                        <p style="text-align: center; color: var(--gray-500); font-size: 0.8125rem; margin-top: 1rem;">
                            <i class="fas fa-shield-alt"></i> Paiement sécurisé
                        </p>
                    </div>
                </div>
            </div>
        </form>
        
        <!-- Formulaire PayPal séparé (caché) -->
        <form id="paypalForm" method="post" action="{!! URL::to('paypal') !!}" style="display: none;">
            @csrf
            <input type="hidden" name="delivery_address" id="paypal_delivery_address">
            <input type="hidden" name="driver_tip" id="paypal_driver_tip">
            <input type="hidden" name="d_lat" id="paypal_d_lat">
            <input type="hidden" name="d_lng" id="paypal_d_lng">
            <input type="hidden" name="sub_total" value="{{$total}}">
            <input type="hidden" name="tax" value="{{$charges->tax}}">
            <input type="hidden" name="delivery_charges" value="{{$charges->delivery_fee}}">
            <input type="hidden" name="amount" id="paypal_amount" value="{{$grandTotal}}">
        </form>
    </div>
</section>
@endsection

@section('styles')
<style>
    @media (max-width: 991px) {
        .section > .container > form > div {
            grid-template-columns: 1fr !important;
        }
    }
</style>
@endsection

@section('scripts')
<script src="{{ asset('js/checkout.js') }}"></script>
<script>
// Mise à jour du total avec pourboire
function myFunction() {
    var total = document.getElementById("sub_total").value;
    var tip = document.getElementById("tip").value || 0;
    var newTotal = Number(total) + Number(tip);
    document.getElementById("ttotal").innerHTML = newTotal.toLocaleString('fr-FR') + ' FCFA';
    document.getElementById("total").value = newTotal;
}

// Appliquer le code promo
document.getElementById('applyVoucher')?.addEventListener('click', function() {
    var voucherInput = document.getElementById('voucher');
    var voucher = voucherInput.value.trim();
    var restaurant = document.getElementById('restaurant').value;
    var subTotal = parseFloat(document.getElementById("sub_total").value) || 0;
    var deliveryFee = parseFloat(document.getElementById("deliveryFee").textContent.replace(/[^\d]/g, '')) || 0;
    var taxAmount = parseFloat(document.getElementById("taxAmount").textContent.replace(/[^\d]/g, '')) || 0;
    var serviceFee = parseFloat(document.getElementById("serviceFee").textContent.replace(/[^\d]/g, '')) || 0;
    var totalBeforeDiscount = subTotal + deliveryFee + taxAmount + serviceFee;
    
    if(!voucher) {
        alert('Veuillez entrer un code promo');
        return;
    }
    
    var btn = document.getElementById('applyVoucher');
    btn.disabled = true;
    btn.textContent = 'Vérification...';
    
    fetch("{{url('/voucher')}}", {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({voucher: voucher, restaurant: restaurant})
    })
    .then(response => response.json())
    .then(response => {
        if(response.status && response.data != null) {
            var discount = (response.data.discount / 100) * subTotal;
            var newTotal = Math.max(0, totalBeforeDiscount - discount);
            
            // Afficher la réduction
            var discountRow = document.getElementById('voucherDiscountRow');
            if(!discountRow) {
                discountRow = document.createElement('div');
                discountRow.id = 'voucherDiscountRow';
                discountRow.style.cssText = 'display: flex; justify-content: space-between; margin-bottom: 0.5rem; padding: 0.75rem; background: linear-gradient(135deg, rgba(16, 185, 129, 0.1) 0%, rgba(5, 150, 105, 0.1) 100%); border-radius: 12px;';
                var totalDiv = document.getElementById('ttotal').parentElement;
                totalDiv.parentElement.insertBefore(discountRow, totalDiv);
            }
            discountRow.innerHTML = '<span style="color: #059669; font-weight: 600;"><i class="fas fa-tag"></i> Réduction code promo</span><span style="font-weight: 700; color: #059669;">-' + discount.toLocaleString('fr-FR') + ' FCFA</span>';
            discountRow.style.display = 'flex';
            
            document.getElementById("ttotal").innerHTML = newTotal.toLocaleString('fr-FR') + ' FCFA';
            document.getElementById("total").value = newTotal;
            document.getElementById("sub_total").value = newTotal;
            
            btn.textContent = '✓ Appliqué';
            btn.style.background = '#10B981';
            btn.style.color = 'white';
            voucherInput.disabled = true;
            
            // Afficher un message de succès
            var successMsg = document.createElement('div');
            successMsg.style.cssText = 'padding: 0.75rem; background: #10B981; color: white; border-radius: 8px; margin-top: 0.5rem; font-size: 0.875rem;';
            successMsg.textContent = '✓ Code promo appliqué ! Réduction de ' + discount.toLocaleString('fr-FR') + ' FCFA';
            voucherInput.parentElement.appendChild(successMsg);
            setTimeout(() => successMsg.remove(), 3000);
        } else {
            btn.disabled = false;
            btn.textContent = 'Appliquer';
            alert(response.message || 'Code promo invalide ou expiré');
        }
    })
    .catch(error => {
        btn.disabled = false;
        btn.textContent = 'Appliquer';
        console.error('Erreur:', error);
        alert('Erreur lors de la vérification du code promo');
    });
});

// Gestion des méthodes de paiement
document.querySelectorAll('.payment-option').forEach(function(option) {
    option.addEventListener('click', function() {
        // Retirer la sélection de toutes les options
        document.querySelectorAll('.payment-option').forEach(function(opt) {
            opt.style.borderColor = 'var(--gray-200)';
            opt.style.background = 'white';
        });
        // Ajouter la sélection à l'option cliquée
        this.style.borderColor = 'var(--primary)';
        this.style.background = 'rgba(255, 107, 53, 0.05)';
        
        // Mettre à jour le texte du bouton
        var method = this.dataset.method;
        var btnText = document.getElementById('btnText');
        if(method === 'cash') {
            btnText.textContent = 'Commander (Paiement à la livraison)';
        } else if(method === 'mobile_money') {
            btnText.textContent = 'Commander (Mobile Money)';
        } else if(method === 'paypal') {
            btnText.textContent = 'Payer avec PayPal';
        }
    });
});

// Gestion des points de fidélité
function updateLoyaltyDiscount() {
    const useLoyalty = document.getElementById('useLoyaltyPoints');
    const discountRow = document.getElementById('loyaltyDiscountRow');
    const discountAmount = document.getElementById('loyaltyDiscountAmount');
    const loyaltyPointsUsed = document.getElementById('loyaltyPointsUsed');
    const totalElement = document.getElementById('ttotal');
    const totalInput = document.getElementById('total');
    
    if (!useLoyalty || !discountRow) return;
    
    if (useLoyalty.checked) {
        discountRow.style.display = 'block';
        const discount = parseFloat(discountAmount.textContent.replace(/[^\d]/g, '')) || 0;
        const currentTotal = parseFloat(totalInput.value) || 0;
        const newTotal = Math.max(0, currentTotal - discount);
        
        totalElement.textContent = newTotal.toLocaleString('fr-FR') + ' FCFA';
        totalInput.value = newTotal;
        
        // Calculer les points utilisés
        const pointsPer1000 = 100;
        const pointsUsed = Math.floor((discount / 1000) * pointsPer1000);
        loyaltyPointsUsed.value = pointsUsed;
    } else {
        discountRow.style.display = 'none';
        const discount = parseFloat(discountAmount.textContent.replace(/[^\d]/g, '')) || 0;
        const currentTotal = parseFloat(totalInput.value) || 0;
        const newTotal = currentTotal + discount;
        
        totalElement.textContent = newTotal.toLocaleString('fr-FR') + ' FCFA';
        totalInput.value = newTotal;
        loyaltyPointsUsed.value = 0;
    }
}

// Mise à jour automatique du total avec pourboire
document.getElementById('tip')?.addEventListener('input', function() {
    myFunction();
    updateLoyaltyDiscount();
});

// Initialiser la première option comme sélectionnée
document.querySelector('.payment-option[data-method="cash"]').click();

// Gestion de la soumission du formulaire via API
document.getElementById('checkoutForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    
    var paymentMethod = document.querySelector('input[name="payment_method"]:checked')?.value;
    var deliveryAddress = document.getElementById('searchMapInput')?.value;
    var latitude = document.getElementById('latitude')?.value;
    var longitude = document.getElementById('longitude')?.value;
    var driverTip = document.getElementById('tip')?.value || 0;
    var voucherCode = document.getElementById('voucher')?.value || null;
    
    // Validation de l'adresse
    if(!deliveryAddress || deliveryAddress.trim() === '') {
        alert('Veuillez entrer une adresse de livraison');
        document.getElementById('searchMapInput').focus();
        return false;
    }
    
    // Validation des coordonnées
    if(!latitude || !longitude || (latitude === '-4.2767' && longitude === '15.2832')) {
        alert('Veuillez sélectionner une adresse précise sur la carte en cliquant ou en recherchant une adresse');
        document.getElementById('searchMapInput').focus();
        return false;
    }
    
    // Normaliser le payment_method pour correspondre à l'API
    var apiPaymentMethod = paymentMethod;
    if (paymentMethod === 'mobile_money') {
        apiPaymentMethod = 'momo'; // Adapter selon votre backend
    }
    
    // Préparer les données pour l'API
    const formData = {
        payment_method: apiPaymentMethod,
        delivery_address: deliveryAddress,
        d_lat: latitude,
        d_lng: longitude,
        driver_tip: driverTip,
        voucher_code: voucherCode
    };
    
    // Appeler l'API via le gestionnaire de checkout
    if (typeof checkoutManager !== 'undefined') {
        checkoutManager.processCheckout(formData);
    } else {
        console.error('CheckoutManager non disponible');
        alert('Erreur: Le système de checkout n\'est pas disponible. Veuillez recharger la page.');
    }
    
    return false;
});

function initMap() {
    // Coordonnées par défaut (Centre de Brazzaville, Congo)
    var defaultLat = -4.2767;
    var defaultLng = 15.2832;
    var lati = defaultLat;
    var long = defaultLng;
    
    var geocoder = new google.maps.Geocoder();
    var map = new google.maps.Map(document.getElementById('map'), {
        center: {lat: lati, lng: long},
        zoom: 13,
        styles: [
            {"featureType": "poi", "stylers": [{"visibility": "off"}]}
        ]
    });
    
    var input = document.getElementById('searchMapInput');
    var autocomplete = new google.maps.places.Autocomplete(input, {
        componentRestrictions: {country: ['cg', 'cd']}, // Congo et RDC
        fields: ['geometry', 'name', 'formatted_address']
    });
    autocomplete.bindTo('bounds', map);
    
    var infowindow = new google.maps.InfoWindow();
    var marker = new google.maps.Marker({
        position: {lat: lati, lng: long},
        map: map,
        draggable: true,
        icon: {
            url: 'https://maps.google.com/mapfiles/ms/icons/red-dot.png'
        }
    });

    // Essayer d'obtenir la position de l'utilisateur
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(function(position) {
            var userLat = position.coords.latitude;
            var userLng = position.coords.longitude;
            
            map.setCenter({lat: userLat, lng: userLng});
            map.setZoom(15);
            marker.setPosition({lat: userLat, lng: userLng});
            document.getElementById('latitude').value = userLat;
            document.getElementById('longitude').value = userLng;
            
            // Obtenir l'adresse depuis les coordonnées (éviter Djoué-Léfini)
            geocoder.geocode({'location': {lat: userLat, lng: userLng}}, function(results, status) {
                if (status === 'OK' && results[0]) {
                    var address = results[0].formatted_address;
                    // Filtrer les adresses contenant "djoue" ou "lefini" pour éviter l'affichage incorrect
                    if (address && !address.toLowerCase().includes('djoue') && !address.toLowerCase().includes('lefini')) {
                        document.getElementById('searchMapInput').value = address;
                    } else {
                        // Si l'adresse contient djoue/lefini, laisser l'utilisateur saisir manuellement
                        document.getElementById('searchMapInput').placeholder = 'Entrez votre adresse de livraison';
                    }
                }
            });
        }, function(error) {
            console.log('Erreur de géolocalisation:', error);
            // Utiliser les coordonnées par défaut
        });
    }

    autocomplete.addListener('place_changed', function() {
        marker.setVisible(true);
        var place = autocomplete.getPlace();
        
        if (!place.geometry) {
            console.log("Aucun détail disponible pour: " + place.name);
            return;
        }
        
        if (place.geometry.viewport) {
            map.fitBounds(place.geometry.viewport);
        } else {
            map.setCenter(place.geometry.location);
            map.setZoom(17);
        }

        marker.setPosition(place.geometry.location);
        var lat = place.geometry.location.lat();
        var lng = place.geometry.location.lng();
        
        document.getElementById('latitude').value = lat;
        document.getElementById('longitude').value = lng;
        
        // Mettre à jour l'adresse complète
        if (place.formatted_address) {
            document.getElementById('searchMapInput').value = place.formatted_address;
        }
        
        infowindow.setContent('<div style="padding: 0.5rem;"><strong>' + (place.name || place.formatted_address) + '</strong></div>');
        infowindow.open(map, marker);
    });

    google.maps.event.addListener(marker, 'dragend', function(event) {
        var latLng = event.latLng;
        var lat = latLng.lat();
        var lng = latLng.lng();
        
        document.getElementById('latitude').value = lat;
        document.getElementById('longitude').value = lng;
        
        geocoder.geocode({'location': latLng}, function(results, status) {
            if (status === 'OK' && results[0]) {
                var address = results[0].formatted_address;
                // Filtrer les adresses contenant "djoue" ou "lefini"
                if (address && !address.toLowerCase().includes('djoue') && !address.toLowerCase().includes('lefini')) {
                    document.getElementById('searchMapInput').value = address;
                    infowindow.setContent('<div style="padding: 0.5rem;"><strong>' + address + '</strong></div>');
                } else {
                    // Si l'adresse contient djoue/lefini, afficher un message générique
                    infowindow.setContent('<div style="padding: 0.5rem;"><strong>Position sélectionnée</strong><br>Veuillez entrer votre adresse manuellement</div>');
                }
                infowindow.open(map, marker);
            }
        });
    });
    
    // Clic sur la carte pour placer le marqueur
    map.addListener('click', function(event) {
        var lat = event.latLng.lat();
        var lng = event.latLng.lng();
        
        marker.setPosition({lat: lat, lng: lng});
        document.getElementById('latitude').value = lat;
        document.getElementById('longitude').value = lng;
        
        geocoder.geocode({'location': {lat: lat, lng: lng}}, function(results, status) {
            if (status === 'OK' && results[0]) {
                var address = results[0].formatted_address;
                // Filtrer les adresses contenant "djoue" ou "lefini"
                if (address && !address.toLowerCase().includes('djoue') && !address.toLowerCase().includes('lefini')) {
                    document.getElementById('searchMapInput').value = address;
                    infowindow.setContent('<div style="padding: 0.5rem;"><strong>' + address + '</strong></div>');
                } else {
                    // Si l'adresse contient djoue/lefini, afficher un message générique
                    infowindow.setContent('<div style="padding: 0.5rem;"><strong>Position sélectionnée</strong><br>Veuillez entrer votre adresse manuellement</div>');
                }
                infowindow.open(map, marker);
            }
        });
    });
}
</script>
<script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyCkXFIvxvN0M1Chg644bLwAnXEQUG_RKUI&libraries=places&callback=initMap" async defer></script>
@endsection
