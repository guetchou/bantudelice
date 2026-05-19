@extends('frontend.layouts.app-modern')
@php
    $foodBrandName = \App\Services\ConfigService::getCompanyName();
@endphp
@section('title', 'Suivi de commande #' . $order->order_no . ' | ' . $foodBrandName)
@section('description', 'Suivez votre commande en temps réel sur ' . $foodBrandName . '.')
@section('body_class', 'bd-track-order-page')

@php
    $paymentExperience = $paymentExperience ?? null;
    $isPickup = method_exists($order, 'isPickup') ? $order->isPickup() : (($order->fulfillment_mode ?? 'delivery') === 'pickup');
    $businessStatus = $order->effective_business_status ?? $order->resolveEffectiveBusinessStatus();
    $trackingStatus = $order->tracking_status ?? $order->resolveTrackingStatus();
    $trackingProgress = $order->tracking_progress ?? $order->resolveTrackingProgress();
    $statusText = $isPickup ? [
        'pending_restaurant_acceptance' => ['label' => 'En attente du restaurant', 'description' => 'Le restaurant doit encore accepter votre commande retrait.'],
        'accepted' => ['label' => 'Commande acceptée', 'description' => 'Le restaurant confirme votre commande retrait.'],
        'in_kitchen' => ['label' => 'En préparation', 'description' => 'La cuisine prépare votre commande.'],
        'ready_for_pickup' => ['label' => 'Prête au retrait', 'description' => 'Votre commande est prête. Présentez votre code au restaurant.'],
        'customer_arrived' => ['label' => 'Client arrivé', 'description' => 'Le restaurant prépare la remise au comptoir.'],
        'picked_up_by_customer' => ['label' => 'Retirée', 'description' => 'La commande a été remise au client.'],
        'closed' => ['label' => 'Clôturée', 'description' => 'Le retrait est terminé.'],
        'no_show' => ['label' => 'Client absent', 'description' => 'Le retrait n’a pas été finalisé dans les délais.'],
        'cancelled' => ['label' => 'Annulée', 'description' => 'La commande a été annulée.'],
    ] : [
        'pending_restaurant_acceptance' => ['label' => 'En attente du restaurant', 'description' => 'Le restaurant doit encore accepter votre commande.'],
        'accepted' => ['label' => 'Commande acceptée', 'description' => 'Le restaurant confirme la prise en charge.'],
        'in_kitchen' => ['label' => 'En préparation', 'description' => 'La cuisine prépare votre commande.'],
        'ready_for_pickup' => ['label' => 'Prête au départ', 'description' => 'La commande est prête au restaurant.'],
        'driver_assigned' => ['label' => 'Livreur assigné', 'description' => 'Un livreur est assigné à votre commande.'],
        'picked_up' => ['label' => 'Commande récupérée', 'description' => 'Le livreur a pris en charge votre commande.'],
        'out_for_delivery' => ['label' => 'En route', 'description' => 'La livraison est en cours vers votre adresse.'],
        'delivered' => ['label' => 'Livrée', 'description' => 'Votre commande a été remise.'],
        'cancelled' => ['label' => 'Annulée', 'description' => 'La commande a été annulée.'],
    ];
    $currentStatus = $statusText[$businessStatus] ?? ['label' => 'Suivi en cours', 'description' => 'Le traitement de votre commande continue.'];
    $canEditOrder = auth()->check() && auth()->id() === $order->user_id && method_exists($order, 'canBeModified') && $order->canBeModified();
    $timelineSteps = $isPickup ? [
        ['key' => 'pending', 'label' => 'Commande confirmée', 'description' => 'Votre commande retrait est bien enregistrée.'],
        ['key' => 'prepairing', 'label' => 'Préparation cuisine', 'description' => 'Le restaurant prépare votre repas.'],
        ['key' => 'assign', 'label' => 'Prête au retrait', 'description' => 'Le restaurant attend votre passage.'],
        ['key' => 'completed', 'label' => 'Retirée', 'description' => 'La remise au client est confirmée.'],
    ] : [
        ['key' => 'pending', 'label' => 'Commande confirmée', 'description' => 'Votre commande est bien enregistrée.'],
        ['key' => 'prepairing', 'label' => 'Préparation cuisine', 'description' => 'Le restaurant prépare votre repas.'],
        ['key' => 'assign', 'label' => 'Livreur assigné', 'description' => 'Un livreur se prépare à récupérer la commande.'],
        ['key' => 'pickup', 'label' => 'Commande récupérée', 'description' => 'Le livreur a quitté le restaurant.'],
        ['key' => 'onway', 'label' => 'En livraison', 'description' => 'La commande se rapproche de votre adresse.'],
        ['key' => 'completed', 'label' => 'Livrée', 'description' => 'La remise est confirmée.'],
    ];
    $stepKeys = array_column($timelineSteps, 'key');
    $currentStepIndex = array_search($trackingStatus, $stepKeys, true);
    $currentStepIndex = $currentStepIndex === false ? 0 : $currentStepIndex;
    $otpVisible = !$isPickup && $delivery && !empty($delivery->delivery_otp_code) && in_array($businessStatus, ['driver_assigned', 'picked_up', 'out_for_delivery', 'delivered'], true);
    $pickupCodeVisible = $isPickup && !empty($order->pickup_code) && in_array($businessStatus, ['ready_for_pickup', 'customer_arrived', 'picked_up_by_customer', 'closed'], true);
    $receiptPanelInitiallyVisible = ($delivery && $delivery->status === 'DELIVERED') || ($isPickup && in_array($businessStatus, ['picked_up_by_customer', 'closed', 'ready_for_pickup', 'customer_arrived'], true));

    // Notation — visible une fois la commande terminée, une seule fois par commande
    $isOrderTerminal = in_array($businessStatus, ['delivered', 'closed', 'picked_up_by_customer'], true);
    $canRate = false;
    $existingRating = null;
    if ($isOrderTerminal && auth()->check() && auth()->id() === (int) $order->user_id) {
        $existingRating = \App\Rating::where('order_id', $order->id)
            ->where('user_id', auth()->id())
            ->first();
        $canRate = ! $existingRating;
    }
    $hasDriver = !$isPickup && $order->driver_id;
@endphp

@section('content')
<div class="to-page">
<section class="to-hero">
    <div class="container">
        <div class="to-shell-boundary">
            <div class="to-hero-row">
                <div>
                    <p class="to-hero-eyebrow">Suivi temps réel</p>
                    <h1 class="to-hero-title">Commande #{{ $order->order_no }}</h1>
                    <p class="to-hero-copy">{{ $currentStatus['label'] }}. {{ $currentStatus['description'] }}</p>
                </div>
                <div class="to-hero-meta">
                    <div class="to-hero-meta-label">Créée le</div>
                    <div class="to-hero-meta-value">{{ $order->created_at->format('d/m/Y à H:i') }}</div>
                    @if($canEditOrder)
                        <div class="to-hero-action">
                            <a href="{{ route('orders.edit', ['orderNo' => $order->order_no]) }}" class="to-btn-secondary">
                                <i class="fas fa-pen"></i> Modifier la commande
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</section>

<section class="to-shell">
    <div class="container">
        <div class="to-shell-boundary to-stack">
            <div class="to-kpi-grid">
                <div class="to-card to-kpi-card to-kpi-card--wide">
                    <div class="to-kpi-head">
                        <div>
                            <div class="to-kpi-label">Statut métier</div>
                            <div id="businessStatusLabel" class="to-kpi-value to-kpi-value--primary">{{ $currentStatus['label'] }}</div>
                        </div>
                        <div class="to-kpi-side">
                            <div class="to-kpi-label to-kpi-label--plain">Progression</div>
                            <div id="trackingProgressLabel" class="to-kpi-progress">{{ $trackingProgress }}%</div>
                        </div>
                    </div>
                    <div class="to-progress-track">
                        <div id="progressBar" class="to-progress-bar"></div>
                    </div>
                </div>
                <div class="to-card to-kpi-card">
                    <div class="to-kpi-label to-kpi-label--plain">Temps estimé</div>
                    <div class="to-kpi-value">{{ $estimatedTime }} min</div>
                    <div class="to-kpi-note">Reste env. {{ $remainingMinutes }} min</div>
                </div>
                <div class="to-card to-kpi-card">
                    <div class="to-kpi-label to-kpi-label--plain">Paiement</div>
                    <div class="to-kpi-value">{{ ucfirst($order->payment_method) }}</div>
                    <div class="to-kpi-note">{{ $paymentExperience['status'] ?? strtoupper($order->payment_status ?? 'pending') }}</div>
                    <div class="to-kpi-emphasis">{{ $paymentExperience['customer_message'] ?? 'Confirmation de paiement en attente.' }}</div>
                    @if(!empty($paymentExperience['support_action']))
                        <div class="to-kpi-subnote">{{ $paymentExperience['support_action'] }}</div>
                    @endif
                    @if(!empty($paymentExperience['failure_reason']))
                        <div class="to-kpi-error">Code provider: {{ $paymentExperience['failure_reason'] }}</div>
                    @endif
                </div>
            </div>

            @if($canEditOrder)
                <div class="to-card to-edit-banner">
                    <div>
                        <div class="to-edit-banner-label">Modification possible</div>
                        <div class="to-edit-banner-title">Vous pouvez encore ajuster votre commande avant préparation.</div>
                    </div>
                    <a href="{{ route('orders.edit', ['orderNo' => $order->order_no]) }}" class="to-btn-primary">Modifier maintenant</a>
                </div>
            @endif

            <div class="to-card">
                <div class="to-timeline">
                    @foreach($timelineSteps as $index => $step)
                        @php
                            $isDone = $index < $currentStepIndex;
                            $isActive = $index === $currentStepIndex;
                        @endphp
                        <div class="timeline-step{{ $isDone ? ' is-done' : '' }}{{ $isActive ? ' is-active' : '' }}" data-step="{{ $step['key'] }}">
                            <div class="timeline-step__index">
                                {{ $index + 1 }}
                            </div>
                            <div>
                                <div class="timeline-title">{{ $step['label'] }}</div>
                                <div class="timeline-copy">{{ $step['description'] }}</div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            @if($pickupCodeVisible)
                <div id="pickupCodePanel" class="to-card to-code-panel to-code-panel--pickup">
                    <div class="to-code-row">
                        <div>
                            <div class="to-code-label to-code-label--pickup">Code de retrait</div>
                            <div class="to-code-value to-code-value--pickup">{{ $order->pickup_code }}</div>
                            <div class="to-code-copy to-code-copy--pickup">Présentez ce code au restaurant pour récupérer votre commande.</div>
                        </div>
                        <div class="to-code-side to-code-side--pickup">
                            @if(in_array($businessStatus, ['picked_up_by_customer', 'closed'], true))
                                Le retrait a déjà été confirmé.
                            @else
                                Le restaurant pourra confirmer la remise avec ce code. Vous pouvez aussi confirmer le retrait ci-dessous si nécessaire.
                            @endif
                        </div>
                    </div>
                </div>
            @endif

            @if($otpVisible)
                <div id="otpPanel" class="to-card to-code-panel to-code-panel--otp">
                    <div class="to-code-row">
                        <div>
                            <div class="to-code-label">Code de remise</div>
                            <div class="to-code-value">{{ $delivery->delivery_otp_code }}</div>
                            <div class="to-code-copy">Communiquez ce code au livreur au moment de la remise.</div>
                        </div>
                        <div class="to-code-side">
                            @if($delivery->otp_verified_at)
                                OTP déjà vérifié le {{ $delivery->otp_verified_at->format('d/m à H:i') }}.
                            @else
                                Gardez ce code jusqu’à la remise. Il permet de confirmer la bonne réception.
                            @endif
                        </div>
                    </div>
                </div>
            @endif

            @if(($delivery && !$isPickup) || $isPickup)
                <div id="receiptPanel" class="to-card" @if(!$receiptPanelInitiallyVisible) style="display:none" @endif>
                    <div class="to-receipt-head">
                        <div>
                            <h3 class="to-section-heading">Confirmation de réception</h3>
                            <p class="to-receipt-copy">
                                @if($isPickup && in_array($businessStatus, ['picked_up_by_customer', 'closed'], true))
                                    Retrait confirmé pour la commande #{{ $order->order_no }}.
                                @elseif($delivery && $delivery->customer_confirmed_at)
                                    Réception confirmée le {{ $delivery->customer_confirmed_at->format('d/m/Y à H:i') }} via {{ $delivery->delivery_confirmation_method === 'otp' ? 'OTP' : 'confirmation client' }}.
                                @elseif($isPickup)
                                    Si le restaurant a déjà préparé votre commande, vous pouvez confirmer le retrait avec votre code.
                                @else
                                    Confirmez la réception pour clôturer proprement votre livraison.
                                @endif
                            </p>
                        </div>
                        @if(!$isPickup && $delivery && $delivery->delivery_proof_path)
                            <a href="{{ asset($delivery->delivery_proof_path) }}" target="_blank" class="to-btn-secondary">Voir la preuve de livraison</a>
                        @endif
                    </div>
                    @if($isPickup && auth()->check() && auth()->id() === $order->user_id && !in_array($businessStatus, ['picked_up_by_customer', 'closed'], true))
                        <form method="POST" action="{{ route('track.order.confirm', ['orderNo' => $order->order_no]) }}" class="to-verify-form">
                            @csrf
                            <div class="to-form-field">
                                <label class="to-form-label">Code de retrait</label>
                                <input type="text" name="delivery_otp" inputmode="numeric" placeholder="Saisissez le code de retrait" class="to-form-input">
                            </div>
                            <button type="submit" class="to-btn-primary">Confirmer le retrait</button>
                        </form>
                    @elseif(!$isPickup && auth()->check() && auth()->id() === $order->user_id && !$delivery->customer_confirmed_at)
                        <form method="POST" action="{{ route('track.order.confirm', ['orderNo' => $order->order_no]) }}" class="to-verify-form">
                            @csrf
                            @if($delivery->requiresOtp())
                                <div class="to-form-field">
                                    <label class="to-form-label">Code OTP</label>
                                    <input type="text" name="delivery_otp" inputmode="numeric" placeholder="Saisissez le code reçu" class="to-form-input">
                                </div>
                            @endif
                            <input type="hidden" name="customer_confirmed" value="1">
                            <button type="submit" class="to-btn-primary">Confirmer la réception</button>
                        </form>
                    @endif
                </div>
            @endif

            {{-- ── Formulaire de notation post-livraison ──────────────── --}}
            @if($canRate)
                <div id="ratingPanel" class="to-card to-rating-panel">
                    <h3 class="to-section-heading">Votre avis compte</h3>
                    <p class="to-receipt-copy">Comment s'est passée votre commande ? Une évaluation aide le restaurant et le livreur.</p>
                    <form id="ratingForm" class="to-rating-form">
                        @csrf
                        <input type="hidden" name="order_id" value="{{ $order->id }}">

                        <div class="to-form-field">
                            <label class="to-form-label">Note du restaurant</label>
                            <div class="to-stars" data-target="rating">
                                @for($i = 1; $i <= 5; $i++)
                                    <button type="button" class="to-star" data-value="{{ $i }}">★</button>
                                @endfor
                            </div>
                            <input type="hidden" name="rating" id="inputRating">
                            <input type="text" name="comment" placeholder="Votre commentaire (facultatif)" class="to-form-input" style="margin-top:8px">
                        </div>

                        @if($hasDriver)
                        <div class="to-form-field" style="margin-top:16px">
                            <label class="to-form-label">Note du livreur</label>
                            <div class="to-stars" data-target="driver_rating">
                                @for($i = 1; $i <= 5; $i++)
                                    <button type="button" class="to-star" data-value="{{ $i }}">★</button>
                                @endfor
                            </div>
                            <input type="hidden" name="driver_rating" id="inputDriverRating">
                            <input type="text" name="driver_comment" placeholder="Votre commentaire livreur (facultatif)" class="to-form-input" style="margin-top:8px">
                        </div>
                        @endif

                        <button type="submit" class="to-btn-primary" style="margin-top:16px" id="ratingSubmitBtn">
                            Envoyer mon avis
                        </button>
                        <div id="ratingFeedback" style="display:none;margin-top:10px;color:#009543;font-weight:600"></div>
                    </form>
                </div>
            @elseif($existingRating)
                <div class="to-card" style="text-align:center;padding:20px">
                    <div style="font-size:1.5rem">{{ str_repeat('★', $existingRating->rating) }}{{ str_repeat('☆', 5 - $existingRating->rating) }}</div>
                    <p style="margin:8px 0 0;color:#6b7280;font-size:.9rem">Vous avez déjà noté cette commande. Merci !</p>
                </div>
            @endif

            @if($isPickup && $businessStatus === 'no_show')
                <div class="to-card to-incident-panel">
                    <div class="to-incident-row">
                        <div>
                            <h3 class="to-incident-title">Retrait non finalisé</h3>
                            <p class="to-incident-copy">Le restaurant a marqué cette commande en client absent. Vous pouvez réactiver le retrait si vous repassez récupérer la commande.</p>
                        </div>
                        <form method="POST" action="{{ route('track.order.reopen_pickup', ['orderNo' => $order->order_no]) }}">
                            @csrf
                            <button type="submit" class="to-btn-primary">Réactiver le retrait</button>
                        </form>
                    </div>
                </div>
            @endif

            @if($delivery && !$isPickup)
                @php
                    $showIncidentPanel = in_array($businessStatus, ['delivery_attempt_failed', 'incident_open'], true) || $delivery->incident_status === 'open';
                @endphp
                @if($showIncidentPanel)
                    <div class="to-card to-incident-panel">
                        <div class="to-incident-row">
                            <div>
                                <h3 class="to-incident-title">Incident de livraison en cours</h3>
                                <p class="to-incident-copy">
                                    Motif: {{ str_replace('_', ' ', $delivery->incident_reason ?? 'incident') }}.
                                    @if(!empty($delivery->incident_notes))
                                        {{ $delivery->incident_notes }}
                                    @endif
                                </p>
                                <p class="to-incident-meta">
                                    Tentatives: {{ (int) ($delivery->failed_attempts ?? 0) }}.
                                    Support: {{ $delivery->support_status ?? 'ouvert' }}.
                                </p>
                            </div>
                            @if($delivery->driver && $delivery->driver->phone)
                                <a href="tel:{{ $delivery->driver->phone }}" class="to-btn-secondary">Appeler le livreur</a>
                            @endif
                        </div>
                        @if(auth()->check() && auth()->id() === $order->user_id)
                            <div class="to-incident-actions">
                                <form method="POST" action="{{ route('track.order.redelivery', ['orderNo' => $order->order_no]) }}" class="to-incident-form">
                                    @csrf
                                    <textarea name="notes" rows="3" placeholder="Précisez le meilleur repère ou horaire pour la nouvelle tentative." class="to-form-input to-form-textarea"></textarea>
                                    <button type="submit" class="to-btn-primary">Demander une re-livraison</button>
                                </form>
                                <form method="POST" action="{{ route('track.order.incident', ['orderNo' => $order->order_no]) }}" class="to-incident-form">
                                    @csrf
                                    <select name="reason" class="to-form-input">
                                        <option value="late_delivery">Retard important</option>
                                        <option value="missing_items">Articles manquants</option>
                                        <option value="courier_issue">Problème avec le livreur</option>
                                        <option value="delivery_issue">Commande non reçue</option>
                                    </select>
                                    <textarea name="notes" rows="3" placeholder="Décrivez précisément le problème pour le support." class="to-form-input to-form-textarea"></textarea>
                                    <button type="submit" class="to-btn-secondary">Ouvrir un litige</button>
                                </form>
                        </div>
                    @endif
                </div>
            @endif
            @endif

            @if(!empty($chatData) && ($chatData['can_view'] ?? false))
                @include('frontend.partials.order_chat', ['chatData' => $chatData])
            @endif

            <div class="to-content-grid">
                <div class="to-card">
                    <h3 class="to-section-heading">Détail de la commande</h3>
                    <div class="to-order-items">
                        @foreach($orderItems as $item)
                            <div class="to-order-item">
                                @if(optional($item->product)->image)
                                    <img src="{{ method_exists($item->product, 'publicImageUrl') ? $item->product->publicImageUrl() : asset('images/product_images/' . $item->product->image) }}" alt="{{ $item->product->name ?? 'Produit' }}" class="to-order-item-image">
                                @endif
                                <div class="to-order-item-copy">
                                    <div class="to-order-item-title">{{ $item->product ? $item->product->name : 'Produit' }}</div>
                                    <div class="to-order-item-meta">Qté: {{ $item->qty }}</div>
                                </div>
                                <div class="to-order-item-price">{{ number_format($item->price * $item->qty, 0, ',', ' ') }} FCFA</div>
                            </div>
                        @endforeach
                    </div>
                    <div class="to-order-summary">
                        <div class="to-order-summary-row"><span class="to-order-summary-label">Sous-total</span><strong>{{ number_format($order->sub_total, 0, ',', ' ') }} FCFA</strong></div>
                        <div class="to-order-summary-row"><span class="to-order-summary-label">{{ $isPickup ? 'Frais de retrait' : 'Livraison' }}</span><strong>{{ number_format($order->delivery_charges, 0, ',', ' ') }} FCFA</strong></div>
                        <div class="to-order-summary-row"><span class="to-order-summary-label">Taxes</span><strong>{{ number_format($order->tax, 0, ',', ' ') }} FCFA</strong></div>
                        @if($order->driver_tip > 0)
                            <div class="to-order-summary-row"><span class="to-order-summary-label">Pourboire</span><strong>{{ number_format($order->driver_tip, 0, ',', ' ') }} FCFA</strong></div>
                        @endif
                        <div class="to-order-summary-total"><span>Total</span><span>{{ number_format($order->total, 0, ',', ' ') }} FCFA</span></div>
                    </div>
                </div>

                <div class="to-side-stack">
                    @if($isPickup)
                        <div class="to-card">
                            <h3 class="to-section-heading to-section-heading--small">Retrait au restaurant</h3>
                            <div class="to-side-list">
                                <div>
                                    <div class="to-side-label">Restaurant</div>
                                    <div class="to-side-value">{{ $order->restaurant->name ?? 'Restaurant' }}</div>
                                </div>
                                <div>
                                    <div class="to-side-label">Adresse</div>
                                    <div class="to-side-copy">{{ $order->restaurant->address ?? 'Adresse non renseignée' }}</div>
                                </div>
                                <div>
                                    <div class="to-side-label">Consigne</div>
                                    <div class="to-side-copy">Présentez votre code de retrait au comptoir. Le restaurant confirmera ensuite la remise.</div>
                                </div>
                            </div>
                        </div>

                        <div class="to-card">
                            <h3 class="to-section-heading to-section-heading--small">Adresse et référence</h3>
                            <div class="to-address-box">
                                <div class="to-side-label">Référence de retrait</div>
                                <div class="to-address-value">{{ $order->delivery_address }}</div>
                            </div>
                        </div>
                    @else
                        <div class="to-card">
                            <h3 class="to-section-heading to-section-heading--small">Suivi de livraison</h3>
                            <div id="trackingMap" class="to-map"></div>
                            <div class="to-distance-grid">
                                <div class="to-distance-card to-distance-card--restaurant">
                                    <p class="to-distance-label">Restaurant vers client</p>
                                    <p id="restaurantDistance" class="to-distance-value">Calcul en cours</p>
                                </div>
                                <div class="to-distance-card to-distance-card--driver">
                                    <p class="to-distance-label">Livreur vers client</p>
                                    <p id="driverDistance" class="to-distance-value">En attente de position</p>
                                </div>
                            </div>
                        </div>

                        <div class="to-card">
                            <h3 class="to-section-heading to-section-heading--small">Livreur et adresse</h3>
                            <div id="driverInfoContainer">
                                @if($delivery && $delivery->driver)
                                    <div class="to-driver-row">
                                        <div class="to-driver-avatar">{{ strtoupper(substr($delivery->driver->name, 0, 2)) }}</div>
                                        <div class="to-driver-copy">
                                            <div class="to-driver-name">{{ $delivery->driver->name }}</div>
                                            <div class="to-driver-phone">{{ $delivery->driver->phone }}</div>
                                        </div>
                                        <a href="tel:{{ $delivery->driver->phone }}" class="to-btn-primary">Appeler</a>
                                    </div>
                                @else
                                    <div class="to-driver-empty">Aucun livreur assigné pour le moment.</div>
                                @endif
                            </div>
                            <div class="to-address-box">
                                <div class="to-side-label">Adresse de livraison</div>
                                <div class="to-address-value">{{ $order->delivery_address }}</div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <div class="to-actions">
                <a href="{{ route('user.profile') }}" class="to-btn-secondary">Mes commandes</a>
                <a href="{{ route('home') }}" class="to-btn-primary">Commander à nouveau</a>
                <a href="{{ route('order.receipt', $order->order_no) }}" class="to-btn-secondary">Voir le reçu</a>
                @if(!$isPickup && $delivery && $delivery->driver && $delivery->driver->phone)
                    <a href="https://wa.me/{{ str_replace('+', '', $delivery->driver->phone) }}" class="to-btn-whatsapp">Contacter le livreur</a>
                @endif
            </div>
        </div>
    </div>
</section>
</div>{{-- .to-page --}}
@endsection

@section('scripts')
@if(!$isPickup)
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV/XN/WPeE=" crossorigin=""></script>
@endif
<script>
    const API_BASE = '{{ url('/api') }}';
    const ORDER_ID = {{ $order->id }};
    const INITIAL_TRACKING_STATUS = '{{ $trackingStatus }}';
    const IS_PICKUP_ORDER = {{ $isPickup ? 'true' : 'false' }};
    const RESTAURANT_COORDS = {
        lat: {{ (float) optional($order->restaurant)->latitude ?: -4.2767 }},
        lng: {{ (float) optional($order->restaurant)->longitude ?: 15.2832 }}
    };
    const DELIVERY_COORDS = {
        lat: {{ (float) ($order->d_lat ?: -4.2767) }},
        lng: {{ (float) ($order->d_lng ?: 15.2832) }}
    };
    let latestDriverCoords = {!! json_encode(
        isset($delivery) && $delivery && $delivery->driver
            ? ['lat' => (float) ($delivery->driver->latitude ?: 0), 'lng' => (float) ($delivery->driver->longitude ?: 0)]
            : null
    ) !!};
    let map;
    let restaurantMarker;
    let deliveryMarker;
    let driverMarker;

    const trackingOrder = IS_PICKUP_ORDER
        ? ['pending', 'prepairing', 'assign', 'completed']
        : ['pending', 'prepairing', 'assign', 'pickup', 'onway', 'completed'];
    const businessLabels = @json($statusText);

    // ── Notifications audio parcours client ──────────────────────
    let _lastKnownTrackingStatus = INITIAL_TRACKING_STATUS;
    let _lastKnownBusinessStatus = '{{ $businessStatus }}';

    const _statusToastMessages = {
        prepairing:  { text: 'Votre commande est en préparation 🍽️',  sound: 'status' },
        assign:      { text: 'Un livreur a été assigné 🛵',            sound: 'status' },
        pickup:      { text: 'Le livreur a récupéré votre commande',   sound: 'status' },
        onway:       { text: 'Votre commande est en route ! 🚀',       sound: 'status' },
        completed:   { text: 'Commande livrée ! Bon appétit 🎉',       sound: 'delivered' },
        // pickup mode
        ready_for_pickup:    { text: 'Votre commande est prête au retrait ✅', sound: 'delivered' },
        picked_up_by_customer: { text: 'Commande retirée. Bon appétit ! 🎉',   sound: 'delivered' },
    };

    function _notifyStatusChange(newTracking, newBusiness) {
        const trackingChanged  = newTracking !== _lastKnownTrackingStatus;
        const businessChanged  = newBusiness !== _lastKnownBusinessStatus;
        if (!trackingChanged && !businessChanged) return;

        _lastKnownTrackingStatus = newTracking;
        _lastKnownBusinessStatus = newBusiness;

        const key = _statusToastMessages[newBusiness] ? newBusiness : newTracking;
        const notif = _statusToastMessages[key];
        if (!notif) return;

        if (window.BdAudio) window.BdAudio.play(notif.sound);
        if (window.showToast) window.showToast(notif.text, 'success');
    }
    // ─────────────────────────────────────────────────────────────

    function haversineKm(lat1, lng1, lat2, lng2) {
        const R = 6371;
        const dLat = (lat2 - lat1) * Math.PI / 180;
        const dLng = (lng2 - lng1) * Math.PI / 180;
        const a = Math.sin(dLat / 2) ** 2 + Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) * Math.sin(dLng / 2) ** 2;
        return 2 * R * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
    }

    function formatDistance(km) {
        if (!Number.isFinite(km) || km <= 0) return 'Position non disponible';
        return km < 1 ? `${Math.round(km * 1000)} m` : `${km.toFixed(1)} km`;
    }

    function updateDistanceSummaries() {
        if (IS_PICKUP_ORDER) {
            return;
        }
        const restaurantDistance = document.getElementById('restaurantDistance');
        const driverDistance = document.getElementById('driverDistance');
        if (restaurantDistance) {
            restaurantDistance.textContent = formatDistance(haversineKm(RESTAURANT_COORDS.lat, RESTAURANT_COORDS.lng, DELIVERY_COORDS.lat, DELIVERY_COORDS.lng));
        }
        if (driverDistance) {
            if (latestDriverCoords && latestDriverCoords.lat && latestDriverCoords.lng) {
                driverDistance.textContent = formatDistance(haversineKm(latestDriverCoords.lat, latestDriverCoords.lng, DELIVERY_COORDS.lat, DELIVERY_COORDS.lng));
            } else {
                driverDistance.textContent = 'Livreur en attente de position';
            }
        }
    }

    function updateTimeline(trackingStatus, progress, businessStatus) {
        const currentIndex = Math.max(trackingOrder.indexOf(trackingStatus), 0);
        document.querySelectorAll('.timeline-step').forEach((step, index) => {
            const isDone = index < currentIndex;
            const isActive = index === currentIndex;
            step.classList.toggle('is-done', isDone);
            step.classList.toggle('is-active', isActive);
        });

        const progressBar = document.getElementById('progressBar');
        const progressLabel = document.getElementById('trackingProgressLabel');
        if (progressBar) progressBar.style.width = `${progress || 0}%`;
        if (progressLabel) progressLabel.textContent = `${progress || 0}%`;

        const businessLabel = document.getElementById('businessStatusLabel');
        if (businessLabel && businessLabels[businessStatus]) {
            businessLabel.textContent = businessLabels[businessStatus].label;
        }

        const receiptPanel = document.getElementById('receiptPanel');
        if (receiptPanel) {
            const shouldShowReceipt = IS_PICKUP_ORDER
                ? ['ready_for_pickup', 'customer_arrived', 'picked_up_by_customer', 'closed'].includes(businessStatus)
                : ['completed'].includes(trackingStatus) || ['delivered'].includes(businessStatus);

            receiptPanel.style.display = shouldShowReceipt ? '' : 'none';
        }
    }

    function updateDriverInfo(driver) {
        const container = document.getElementById('driverInfoContainer');
        if (!container || !driver) return;
        container.innerHTML = `
            <div class="to-driver-row">
                <div class="to-driver-avatar">${driver.name ? driver.name.substring(0,2).toUpperCase() : 'DR'}</div>
                <div class="to-driver-copy">
                    <div class="to-driver-name">${driver.name || 'Livreur'}</div>
                    <div class="to-driver-phone">${driver.phone || ''}</div>
                </div>
                ${driver.phone ? `<a href="tel:${driver.phone}" class="to-btn-primary">Appeler</a>` : ''}
            </div>
            <div class="to-address-box">
                <div class="to-side-label">Adresse de livraison</div>
                <div class="to-address-value">{{ addslashes($order->delivery_address) }}</div>
            </div>
        `;
    }

    function orderTrackingUrl() {
        const url = new URL(`${API_BASE}/orders/${ORDER_ID}/tracking`, window.location.origin);
        url.searchParams.set('_ts', Date.now().toString());
        return url.toString();
    }

    async function updateTracking() {
        try {
            const response = await fetch(orderTrackingUrl(), {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin',
                cache: 'no-store'
            });
            const payload = await response.json().catch(() => ({}));
            if (!response.ok || !payload.status || !payload.data?.tracking_status || !payload.data?.business_status) return;

            const data = payload.data;
            _notifyStatusChange(data.tracking_status || INITIAL_TRACKING_STATUS, data.business_status);
            updateTimeline(data.tracking_status || INITIAL_TRACKING_STATUS, {
                pending: 0,
                prepairing: 30,
                assign: IS_PICKUP_ORDER ? 75 : 50,
                pickup: 75,
                onway: 90,
                completed: 100
            }[data.tracking_status] || 0, data.business_status);

            if (!IS_PICKUP_ORDER && data.driver) {
                updateDriverInfo(data.driver);
                if (data.driver.location) {
                    latestDriverCoords = {
                        lat: parseFloat(data.driver.location.latitude),
                        lng: parseFloat(data.driver.location.longitude),
                    };
                    if (driverMarker && latestDriverCoords.lat && latestDriverCoords.lng) {
                        driverMarker.setLatLng([latestDriverCoords.lat, latestDriverCoords.lng]);
                    } else if (!driverMarker && map && latestDriverCoords && latestDriverCoords.lat) {
                        driverMarker = L.marker([latestDriverCoords.lat, latestDriverCoords.lng], { icon: _makeIcon('#009543','🛵'), title: 'Livreur' }).addTo(map);
                    }
                    updateDistanceSummaries();
                }
            }
        } catch (error) {
            console.error(error);
        }
    }

    function refreshTrackingOnResume() {
        if (document.visibilityState && document.visibilityState !== 'visible') {
            return;
        }

        updateTracking();
    }

    const TRACKING_MAPBOX_TOKEN = @json(mapbox_public_token());

    function _makeIcon(color, emoji) {
        var el = document.createElement('div');
        el.style.cssText = 'width:32px;height:32px;border-radius:50%;background:' + color + ';display:flex;align-items:center;justify-content:center;font-size:16px;box-shadow:0 2px 8px rgba(0,0,0,.35);border:2px solid #fff;';
        el.textContent = emoji;
        return L.divIcon({ html: el.outerHTML, iconSize: [32, 32], iconAnchor: [16, 16], className: '' });
    }

    function initTrackingMap() {
        if (IS_PICKUP_ORDER) return;

        const box = document.getElementById('trackingMap');
        if (!box) return;

        if (!TRACKING_MAPBOX_TOKEN) {
            box.innerHTML = '<div class="to-map-fallback">Carte indisponible — token manquant.</div>';
            return;
        }

        if (typeof L === 'undefined') {
            box.innerHTML = '<div class="to-map-fallback">Chargement de la carte…</div>';
            return;
        }

        map = L.map(box, { zoomControl: true }).setView([DELIVERY_COORDS.lat, DELIVERY_COORDS.lng], 13);

        L.tileLayer('https://api.mapbox.com/styles/v1/mapbox/streets-v12/tiles/{z}/{x}/{y}?access_token=' + TRACKING_MAPBOX_TOKEN, {
            tileSize: 512,
            zoomOffset: -1,
            attribution: '© Mapbox © OpenStreetMap',
            maxZoom: 18,
        }).addTo(map);

        deliveryMarker = L.marker([DELIVERY_COORDS.lat, DELIVERY_COORDS.lng], { icon: _makeIcon('#ef4444','📍'), title: 'Adresse de livraison' }).addTo(map);
        restaurantMarker = L.marker([RESTAURANT_COORDS.lat, RESTAURANT_COORDS.lng], { icon: _makeIcon('#f97316','🍽️'), title: 'Restaurant' }).addTo(map);

        if (latestDriverCoords && latestDriverCoords.lat && latestDriverCoords.lng) {
            driverMarker = L.marker([latestDriverCoords.lat, latestDriverCoords.lng], { icon: _makeIcon('#009543','🛵'), title: 'Livreur' }).addTo(map);
        }

        updateDistanceSummaries();
    }

    document.addEventListener('DOMContentLoaded', function () {
        updateTimeline(INITIAL_TRACKING_STATUS, {{ $trackingProgress }}, '{{ $businessStatus }}');
        updateDistanceSummaries();
        initTrackingMap();
        updateTracking();
        setInterval(updateTracking, 10000);
        window.addEventListener('focus', refreshTrackingOnResume);
        window.addEventListener('pageshow', refreshTrackingOnResume);
        document.addEventListener('visibilitychange', refreshTrackingOnResume);
    });

    window.initTrackingMap = initTrackingMap;
    window.updateTracking = updateTracking;
</script>

<style>
.to-rating-panel { border-top: 3px solid #009543; }
.to-stars { display:flex; gap:6px; margin-top:4px; }
.to-star {
    background:none; border:none; font-size:2rem; color:#d1d5db;
    cursor:pointer; padding:0; line-height:1; transition:color .15s;
}
.to-star.active, .to-star:hover { color:#f59e0b; }
</style>

<script>
(function () {
    // ── Étoiles interactives ───────────────────────────────────────────
    document.querySelectorAll('.to-stars').forEach(function (group) {
        var stars  = group.querySelectorAll('.to-star');
        var target = group.dataset.target;
        var hidden = document.getElementById(target === 'driver_rating' ? 'inputDriverRating' : 'inputRating');

        stars.forEach(function (star, idx) {
            star.addEventListener('mouseenter', function () {
                stars.forEach(function (s, i) {
                    s.classList.toggle('active', i <= idx);
                });
            });
            star.addEventListener('click', function () {
                hidden.value = star.dataset.value;
                stars.forEach(function (s, i) {
                    s.classList.toggle('active', i <= idx);
                });
            });
        });
        group.addEventListener('mouseleave', function () {
            var val = parseInt(hidden ? hidden.value : 0);
            stars.forEach(function (s, i) {
                s.classList.toggle('active', val > 0 && i < val);
            });
        });
    });

    // ── Soumission AJAX ────────────────────────────────────────────────
    var form = document.getElementById('ratingForm');
    if (!form) return;

    form.addEventListener('submit', function (e) {
        e.preventDefault();
        var btn      = document.getElementById('ratingSubmitBtn');
        var feedback = document.getElementById('ratingFeedback');
        var orderId  = form.querySelector('[name="order_id"]').value;
        var rating   = document.getElementById('inputRating').value;
        var comment  = form.querySelector('[name="comment"]').value;
        var dRating  = document.getElementById('inputDriverRating');
        var dComment = form.querySelector('[name="driver_comment"]');

        if (!rating) {
            feedback.style.color = '#ef4444';
            feedback.textContent = 'Veuillez sélectionner une note pour le restaurant.';
            feedback.style.display = 'block';
            return;
        }

        btn.disabled = true;
        btn.textContent = 'Envoi…';

        var payload = {
            _token: document.querySelector('[name="_token"]').value,
            rating: parseInt(rating),
            comment: comment || '',
        };
        if (dRating && dRating.value) {
            payload.driver_rating = parseInt(dRating.value);
            payload.driver_comment = dComment ? dComment.value : '';
        }

        fetch('/api/orders/' + orderId + '/rating', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': payload._token,
            },
            credentials: 'same-origin',
            body: JSON.stringify(payload),
        })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            if (data.status) {
                feedback.style.color = '#009543';
                feedback.textContent = '✓ Merci pour votre avis !';
                feedback.style.display = 'block';
                form.querySelectorAll('button, input, textarea').forEach(function (el) {
                    el.disabled = true;
                });
                btn.style.display = 'none';
            } else {
                feedback.style.color = '#ef4444';
                feedback.textContent = data.message || 'Une erreur est survenue.';
                feedback.style.display = 'block';
                btn.disabled = false;
                btn.textContent = 'Envoyer mon avis';
            }
        })
        .catch(function () {
            feedback.style.color = '#ef4444';
            feedback.textContent = 'Erreur réseau. Veuillez réessayer.';
            feedback.style.display = 'block';
            btn.disabled = false;
            btn.textContent = 'Envoyer mon avis';
        });
    });
})();
</script>
@endsection
