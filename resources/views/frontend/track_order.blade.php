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
        'no_show' => ['label' => 'Client absent', 'description' => "Le retrait n'a pas été finalisé dans les délais."],
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
        ['key' => 'pending', 'label' => 'Confirmée', 'description' => 'Votre commande retrait est bien enregistrée.'],
        ['key' => 'prepairing', 'label' => 'En prépa', 'description' => 'Le restaurant prépare votre repas.'],
        ['key' => 'assign', 'label' => 'Prête', 'description' => 'Le restaurant attend votre passage.'],
        ['key' => 'completed', 'label' => 'Retirée', 'description' => 'La remise au client est confirmée.'],
    ] : [
        ['key' => 'pending', 'label' => 'Confirmée', 'description' => 'Votre commande est bien enregistrée.'],
        ['key' => 'prepairing', 'label' => 'En prépa', 'description' => 'Le restaurant prépare votre repas.'],
        ['key' => 'assign', 'label' => 'Livreur', 'description' => 'Un livreur se prépare à récupérer la commande.'],
        ['key' => 'pickup', 'label' => 'Récupérée', 'description' => 'Le livreur a quitté le restaurant.'],
        ['key' => 'onway', 'label' => 'En route', 'description' => 'La commande se rapproche de votre adresse.'],
        ['key' => 'completed', 'label' => 'Livrée', 'description' => 'La remise est confirmée.'],
    ];
    $stepIcons = $isPickup
        ? ['pending'=>'fa-paper-plane','prepairing'=>'fa-fire-burner','assign'=>'fa-bell-concierge','completed'=>'fa-circle-check']
        : ['pending'=>'fa-paper-plane','prepairing'=>'fa-fire-burner','assign'=>'fa-motorcycle','pickup'=>'fa-box','onway'=>'fa-route','completed'=>'fa-circle-check'];
    $stepKeys = array_column($timelineSteps, 'key');
    $currentStepIndex = array_search($trackingStatus, $stepKeys, true);
    $currentStepIndex = $currentStepIndex === false ? 0 : $currentStepIndex;
    $otpVisible = !$isPickup && $delivery && !empty($delivery->delivery_otp_code) && in_array($businessStatus, ['driver_assigned', 'picked_up', 'out_for_delivery', 'delivered'], true);
    $pickupCodeVisible = $isPickup && !empty($order->pickup_code) && in_array($businessStatus, ['ready_for_pickup', 'customer_arrived', 'picked_up_by_customer', 'closed'], true);
    $receiptPanelInitiallyVisible = ($delivery && $delivery->status === 'DELIVERED') || ($isPickup && in_array($businessStatus, ['picked_up_by_customer', 'closed', 'ready_for_pickup', 'customer_arrived'], true));
    $isOrderTerminal = in_array($businessStatus, ['delivered', 'closed', 'picked_up_by_customer'], true);
    $canRate = false;
    $existingRating = null;
    if ($isOrderTerminal && auth()->check() && auth()->id() === (int) $order->user_id) {
        $existingRating = \App\Rating::where('order_id', $order->id)->where('user_id', auth()->id())->first();
        $canRate = ! $existingRating;
    }
    $hasDriver = !$isPickup && $order->driver_id;
    $statusBadgeClass = match(true) {
        in_array($businessStatus, ['cancelled', 'no_show']) => 'trk-badge--cancelled',
        in_array($businessStatus, ['delivered', 'closed', 'picked_up_by_customer']) => 'trk-badge--done',
        in_array($businessStatus, ['out_for_delivery', 'picked_up', 'onway']) => 'trk-badge--moving',
        default => 'trk-badge--active',
    };
    $statusPulseClass = match(true) {
        in_array($businessStatus, ['cancelled', 'no_show']) => 'trk-pulse--cancelled',
        in_array($businessStatus, ['delivered', 'closed', 'picked_up_by_customer']) => 'trk-pulse--done',
        in_array($businessStatus, ['out_for_delivery', 'picked_up', 'onway']) => 'trk-pulse--moving',
        default => '',
    };
    $restLogo = optional($order->restaurant)->logo;
    $restLogoUrl = $restLogo ? (\Str::startsWith($restLogo, ['http://', 'https://']) ? $restLogo : asset('images/restaurant_images/' . $restLogo)) : null;
    $restPhone = optional($order->restaurant)->phone;
    $restWa = $restPhone ? 'https://wa.me/' . preg_replace('/[^0-9]/', '', $restPhone) : null;
@endphp

@section('styles')
<style>
/* ── Layout ────────────────────────────────────────────────── */
.trk { max-width:720px; margin:0 auto; padding:0 0 5rem; font-family:inherit; }

/* ── Topbar ────────────────────────────────────────────────── */
.trk-topbar { display:flex; align-items:center; gap:10px; padding:14px 16px 0; }
.trk-back { display:inline-flex; align-items:center; gap:5px; color:#009543; font-weight:700; font-size:.85rem; text-decoration:none; flex-shrink:0; }
.trk-back:hover { color:#007a37; }
.trk-order-ref { flex:1; font-size:.95rem; font-weight:800; color:#111; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; text-align:center; }
.trk-badge { display:inline-flex; align-items:center; gap:5px; padding:4px 11px; border-radius:99px; font-size:.72rem; font-weight:800; letter-spacing:.04em; flex-shrink:0; }
.trk-badge--active { background:#fef3c7; color:#92400e; }
.trk-badge--moving { background:#dcfce7; color:#15803d; }
.trk-badge--done { background:#f0fdf4; color:#16a34a; }
.trk-badge--cancelled { background:#fee2e2; color:#dc2626; }
.trk-badge-dot { width:6px; height:6px; border-radius:50%; background:currentColor; flex-shrink:0; animation:trk-blink 1.2s ease-in-out infinite; }
.trk-badge--done .trk-badge-dot, .trk-badge--cancelled .trk-badge-dot { animation:none; }

/* ── Map ────────────────────────────────────────────────────── */
.trk-map-wrap { position:relative; width:100%; margin-top:14px; }
#trackingMap { height:500px; width:100%; background:#e5e7eb; }
.trk-map-fallback { height:280px; display:flex; flex-direction:column; align-items:center; justify-content:center; background:#f9fafb; color:#9ca3af; font-size:.9rem; gap:8px; }
.trk-map-overlay { position:absolute; bottom:14px; left:12px; right:12px; display:flex; gap:8px; pointer-events:none; z-index:500; flex-wrap:wrap; }
.trk-map-pill { background:rgba(255,255,255,.95); backdrop-filter:blur(8px); border-radius:99px; padding:7px 16px; font-size:.8rem; font-weight:700; color:#111; box-shadow:0 2px 12px rgba(0,0,0,.2); display:flex; align-items:center; gap:6px; }
.trk-map-pill i { color:#009543; font-size:.78rem; }
.trk-map-pill--driver i { color:#f97316; }

/* ── ETA hero (Uber Eats style) ─────────────────────────────── */
.trk-eta-hero { text-align:center; padding:4px 0 18px; border-bottom:1px solid #f3f4f6; margin-bottom:16px; }
.trk-eta-eyebrow { font-size:.68rem; text-transform:uppercase; letter-spacing:.09em; color:#9ca3af; font-weight:700; margin-bottom:6px; }
.trk-eta-value { font-size:3rem; font-weight:900; color:#009543; line-height:1; font-variant-numeric:tabular-nums; letter-spacing:-.02em; transition:color .3s; }
.trk-eta-value--late { color:#f59e0b; }
.trk-eta-route { font-size:.78rem; color:#9ca3af; margin-top:6px; display:flex; align-items:center; justify-content:center; gap:5px; }

/* ── Status card ────────────────────────────────────────────── */
.trk-section { padding:0 14px; }
.trk-status-card { background:#fff; border-radius:18px; box-shadow:0 4px 24px rgba(0,0,0,.13); padding:20px; margin:14px 0 8px; position:relative; z-index:600; }
.trk-section--float { padding:0 14px; margin-top:-32px; }
.trk-status-main { display:flex; align-items:flex-start; gap:14px; margin-bottom:16px; }
.trk-pulse { width:52px; height:52px; border-radius:50%; background:#009543; display:flex; align-items:center; justify-content:center; color:#fff; font-size:22px; flex-shrink:0; box-shadow:0 0 0 6px rgba(0,149,67,.12); animation:trk-pulse-ring 2.5s infinite; }
.trk-pulse--moving { background:#009543; box-shadow:0 0 0 6px rgba(0,149,67,.15); }
.trk-pulse--done { background:#16a34a; box-shadow:none; animation:none; }
.trk-pulse--cancelled { background:#ef4444; box-shadow:none; animation:none; }
@keyframes trk-pulse-ring { 0%,100%{box-shadow:0 0 0 6px rgba(0,149,67,.12)} 50%{box-shadow:0 0 0 12px rgba(0,149,67,.06)} }
.trk-status-texts { flex:1; min-width:0; }
.trk-status-label { font-size:1.15rem; font-weight:900; color:#111; line-height:1.2; margin-bottom:4px; }
.trk-status-desc { font-size:.86rem; color:#6b7280; line-height:1.45; }
.trk-status-meta { display:grid; grid-template-columns:repeat(3,1fr); gap:10px; padding-top:14px; border-top:1px solid #f3f4f6; margin-top:4px; }
.trk-meta-item { text-align:center; }
.trk-meta-label { font-size:.65rem; text-transform:uppercase; letter-spacing:.07em; color:#9ca3af; font-weight:700; }
.trk-meta-val { font-size:1.05rem; font-weight:900; color:#111; margin-top:3px; }
.trk-meta-val--sm { font-size:.85rem; }
.trk-progress-wrap { margin-top:16px; }
.trk-progress-header { display:flex; justify-content:space-between; align-items:center; font-size:.75rem; font-weight:700; color:#9ca3af; margin-bottom:7px; }
.trk-progress-track { height:7px; background:#f3f4f6; border-radius:99px; overflow:hidden; }
.trk-progress-bar { height:100%; background:linear-gradient(90deg,#009543,#00c957); border-radius:99px; transition:width .9s cubic-bezier(.4,0,.2,1); min-width:4px; }

/* ── Timeline horizontal ────────────────────────────────────── */
.trk-timeline-wrap { padding:0 14px 4px; overflow-x:auto; -webkit-overflow-scrolling:touch; scrollbar-width:none; margin-top:4px; }
.trk-timeline-wrap::-webkit-scrollbar { display:none; }
.trk-timeline { display:flex; align-items:flex-start; padding:16px 4px 8px; min-width:max-content; }
.trk-step { display:flex; flex-direction:column; align-items:center; position:relative; min-width:72px; padding:0 4px; }
.trk-step:not(:last-child)::after { content:''; position:absolute; top:20px; left:calc(50% + 20px); right:calc(-50% + 20px); height:2px; background:#e5e7eb; z-index:0; transition:background .5s; }
.trk-step.is-done::after { background:#009543; }
.trk-step__dot { width:40px; height:40px; border-radius:50%; background:#f3f4f6; border:2px solid #e5e7eb; display:flex; align-items:center; justify-content:center; font-size:14px; color:#9ca3af; position:relative; z-index:1; transition:all .4s; flex-shrink:0; }
.trk-step.is-done .trk-step__dot { background:#009543; border-color:#009543; color:#fff; }
.trk-step.is-active .trk-step__dot { background:#fff; border-color:#009543; border-width:2.5px; color:#009543; box-shadow:0 0 0 5px rgba(0,149,67,.14); }
.trk-step__label { font-size:.67rem; font-weight:600; color:#9ca3af; text-align:center; margin-top:7px; line-height:1.3; max-width:68px; }
.trk-step.is-done .trk-step__label { color:#009543; }
.trk-step.is-active .trk-step__label { color:#009543; font-weight:800; }
@keyframes trk-blink { 0%,100%{opacity:1} 50%{opacity:.45} }

/* ── Contact cards ──────────────────────────────────────────── */
.trk-contacts { display:grid; grid-template-columns:1fr 1fr; gap:12px; padding:8px 14px; }
@media(max-width:380px){ .trk-contacts { grid-template-columns:1fr; } }
.trk-contact { background:#fff; border-radius:18px; box-shadow:0 2px 14px rgba(0,0,0,.09); padding:16px; }
.trk-contact-top { display:flex; align-items:center; gap:10px; margin-bottom:12px; }
.trk-contact-avatar { width:46px; height:46px; border-radius:50%; object-fit:cover; flex-shrink:0; border:2px solid #f3f4f6; }
.trk-contact-avatar-ph { width:46px; height:46px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-weight:800; font-size:1rem; color:#fff; flex-shrink:0; }
.trk-contact-avatar-ph--green { background:#009543; }
.trk-contact-avatar-ph--orange { background:#f97316; }
.trk-contact-role { font-size:.65rem; text-transform:uppercase; letter-spacing:.08em; color:#9ca3af; font-weight:700; margin-bottom:3px; }
.trk-contact-name { font-size:.9rem; font-weight:800; color:#111; line-height:1.2; }
.trk-contact-sub { font-size:.75rem; color:#9ca3af; margin-top:1px; }
.trk-contact-btns { display:flex; gap:7px; flex-wrap:wrap; }
.trk-btn-call { display:inline-flex; align-items:center; justify-content:center; gap:5px; padding:9px 12px; border-radius:12px; background:#009543; color:#fff; font-weight:700; font-size:.78rem; text-decoration:none; border:none; cursor:pointer; flex:1; min-width:0; transition:background .15s; }
.trk-btn-call:hover { background:#007a37; color:#fff; }
.trk-btn-wa { display:inline-flex; align-items:center; justify-content:center; gap:5px; padding:9px 12px; border-radius:12px; background:#25D366; color:#fff; font-weight:700; font-size:.78rem; text-decoration:none; flex:1; min-width:0; transition:background .15s; }
.trk-btn-wa:hover { background:#1da851; color:#fff; }
.trk-btn-outline { display:inline-flex; align-items:center; justify-content:center; gap:5px; padding:9px 12px; border-radius:12px; background:#f9fafb; color:#374151; font-weight:700; font-size:.78rem; text-decoration:none; border:1.5px solid #e5e7eb; flex:1; min-width:0; transition:background .15s; }
.trk-btn-outline:hover { background:#f3f4f6; }
.trk-no-driver { text-align:center; padding:14px 0 4px; color:#9ca3af; font-size:.82rem; }
.trk-no-driver i { display:block; font-size:1.5rem; margin-bottom:5px; color:#d1d5db; }

/* ── Collapsible cards ─────────────────────────────────────── */
.trk-card { background:#fff; border-radius:18px; box-shadow:0 2px 14px rgba(0,0,0,.09); margin:0 14px 12px; overflow:hidden; }
.trk-card-head { display:flex; align-items:center; gap:10px; padding:16px 18px; cursor:pointer; user-select:none; }
.trk-card-head h3 { flex:1; font-size:.92rem; font-weight:800; color:#111; margin:0; display:flex; align-items:center; gap:8px; }
.trk-card-head h3 i { color:#009543; font-size:.85rem; }
.trk-card-chevron { color:#9ca3af; transition:transform .25s; font-size:.75rem; }
.trk-card-body { padding:0 18px 18px; }
.trk-card--collapsed .trk-card-chevron { transform:rotate(-90deg); }
.trk-card--collapsed .trk-card-body { display:none; }

/* ── Code cards ─────────────────────────────────────────────── */
.trk-code-card { border-radius:18px; margin:0 14px 12px; padding:22px; color:#fff; }
.trk-code-card--pickup { background:linear-gradient(135deg,#009543 0%,#00b84a 100%); }
.trk-code-card--otp { background:linear-gradient(135deg,#1d4ed8 0%,#3b82f6 100%); }
.trk-code-eyebrow { font-size:.68rem; font-weight:800; text-transform:uppercase; letter-spacing:.1em; opacity:.75; margin-bottom:8px; display:flex; align-items:center; gap:6px; }
.trk-code-value { font-size:3rem; font-weight:900; letter-spacing:.2em; line-height:1; margin:6px 0 10px; font-variant-numeric:tabular-nums; }
.trk-code-hint { font-size:.84rem; opacity:.85; line-height:1.45; }

/* ── Confirmation form ──────────────────────────────────────── */
.trk-confirm { background:#fff; border-radius:18px; box-shadow:0 2px 14px rgba(0,0,0,.09); margin:0 14px 12px; padding:20px; }
.trk-confirm h3 { font-size:.95rem; font-weight:800; color:#111; margin:0 0 5px; display:flex; align-items:center; gap:8px; }
.trk-confirm h3 i { color:#009543; }
.trk-confirm p { font-size:.84rem; color:#6b7280; margin:0 0 16px; line-height:1.45; }
.trk-input { width:100%; padding:12px 14px; border:1.5px solid #e5e7eb; border-radius:12px; font-size:.95rem; outline:none; transition:border-color .15s; box-sizing:border-box; font-family:inherit; }
.trk-input:focus { border-color:#009543; }
.trk-textarea { min-height:80px; resize:vertical; }
.trk-btn-primary { display:inline-flex; align-items:center; justify-content:center; gap:8px; padding:13px 22px; border-radius:12px; background:#009543; color:#fff; font-weight:800; font-size:.9rem; text-decoration:none; border:none; cursor:pointer; width:100%; margin-top:12px; transition:background .15s; }
.trk-btn-primary:hover { background:#007a37; color:#fff; }
.trk-btn-primary-sm { display:inline-flex; align-items:center; justify-content:center; gap:6px; padding:10px 18px; border-radius:10px; background:#009543; color:#fff; font-weight:700; font-size:.84rem; text-decoration:none; border:none; cursor:pointer; transition:background .15s; }
.trk-btn-primary-sm:hover { background:#007a37; color:#fff; }

/* ── Distance pills in card ─────────────────────────────────── */
.trk-dist-grid { display:grid; grid-template-columns:1fr 1fr; gap:10px; margin-top:14px; }
.trk-dist-card { background:#f9fafb; border-radius:12px; padding:12px 14px; }
.trk-dist-label { font-size:.68rem; text-transform:uppercase; letter-spacing:.07em; color:#9ca3af; font-weight:700; margin-bottom:4px; }
.trk-dist-val { font-size:1rem; font-weight:800; color:#111; }

/* ── Order items ─────────────────────────────────────────────── */
.trk-item { display:flex; align-items:center; gap:12px; padding:10px 0; border-bottom:1px solid #f9fafb; }
.trk-item:last-child { border-bottom:none; }
.trk-item-img { width:46px; height:46px; border-radius:10px; object-fit:cover; flex-shrink:0; background:#f3f4f6; }
.trk-item-img-ph { width:46px; height:46px; border-radius:10px; background:#f3f4f6; flex-shrink:0; display:flex; align-items:center; justify-content:center; color:#d1d5db; font-size:18px; }
.trk-item-name { font-size:.88rem; font-weight:700; color:#111; flex:1; }
.trk-item-qty { font-size:.75rem; color:#9ca3af; margin-top:2px; }
.trk-item-price { font-size:.9rem; font-weight:800; color:#009543; white-space:nowrap; }
.trk-summary { border-top:1px solid #f3f4f6; padding-top:12px; margin-top:6px; }
.trk-summary-row { display:flex; justify-content:space-between; font-size:.85rem; color:#6b7280; padding:3px 0; }
.trk-summary-total { display:flex; justify-content:space-between; font-size:1.05rem; font-weight:900; color:#111; padding:10px 0 0; border-top:1.5px solid #e5e7eb; margin-top:8px; }

/* ── Incident ───────────────────────────────────────────────── */
.trk-incident { background:#fff5f5; border:1.5px solid #fecaca; border-radius:18px; margin:0 14px 12px; padding:18px 20px; }
.trk-incident-title { font-size:.95rem; font-weight:800; color:#dc2626; margin:0 0 6px; }
.trk-incident-copy { font-size:.84rem; color:#6b7280; margin:0 0 14px; line-height:1.45; }
.trk-incident-meta { font-size:.78rem; color:#9ca3af; margin:0 0 14px; }
.trk-incident-actions { display:flex; flex-direction:column; gap:10px; }
.trk-incident-form { display:flex; flex-direction:column; gap:8px; }

/* ── Edit banner ────────────────────────────────────────────── */
.trk-edit-banner { background:#fffbeb; border:1.5px solid #fde68a; border-radius:16px; margin:0 14px 12px; padding:14px 18px; display:flex; align-items:center; justify-content:space-between; gap:12px; flex-wrap:wrap; }
.trk-edit-text strong { display:block; font-size:.88rem; font-weight:800; color:#92400e; }
.trk-edit-text span { font-size:.8rem; color:#b45309; }
.trk-btn-edit { padding:9px 16px; border-radius:10px; background:#f59e0b; color:#fff; font-weight:700; font-size:.82rem; text-decoration:none; white-space:nowrap; transition:background .15s; }
.trk-btn-edit:hover { background:#d97706; color:#fff; }

/* ── Rating ─────────────────────────────────────────────────── */
.trk-rating { background:#fff; border-radius:18px; box-shadow:0 2px 14px rgba(0,0,0,.09); margin:0 14px 12px; padding:20px; border-top:3px solid #009543; }
.trk-rating h3 { font-size:.95rem; font-weight:800; color:#111; margin:0 0 4px; }
.trk-rating p { font-size:.84rem; color:#6b7280; margin:0 0 14px; }
.trk-rating-group { margin-bottom:16px; }
.trk-rating-group label { font-size:.78rem; font-weight:700; color:#374151; text-transform:uppercase; letter-spacing:.06em; display:block; margin-bottom:6px; }
.trk-stars { display:flex; gap:5px; }
.trk-star { background:none; border:none; font-size:2.2rem; color:#e5e7eb; cursor:pointer; padding:0; line-height:1; transition:color .15s; }
.trk-star.active, .trk-star:hover { color:#f59e0b; }
.trk-rating-note { font-size:.75rem; color:#9ca3af; margin-top:4px; }

/* ── JS-generated driver info (.to-* compat) ────────────────── */
.to-driver-row { display:flex; align-items:center; gap:10px; }
.to-driver-avatar { width:46px; height:46px; border-radius:50%; background:#009543; color:#fff; font-weight:800; font-size:1rem; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
.to-driver-copy { flex:1; min-width:0; }
.to-driver-name { font-size:.9rem; font-weight:800; color:#111; }
.to-driver-phone { font-size:.78rem; color:#6b7280; margin-top:1px; }
.to-driver-empty { text-align:center; color:#9ca3af; font-size:.83rem; padding:10px 0; }
.to-address-box { margin-top:12px; padding:12px 14px; background:#f9fafb; border-radius:12px; }
.to-side-label { font-size:.68rem; text-transform:uppercase; letter-spacing:.07em; color:#9ca3af; font-weight:700; margin-bottom:3px; }
.to-address-value { font-size:.88rem; color:#374151; font-weight:600; }
.to-btn-primary { display:inline-flex; align-items:center; justify-content:center; padding:8px 14px; border-radius:10px; background:#009543; color:#fff; font-weight:700; font-size:.8rem; text-decoration:none; border:none; cursor:pointer; transition:background .15s; }
.to-btn-primary:hover { background:#007a37; color:#fff; }
.to-btn-secondary { display:inline-flex; align-items:center; justify-content:center; padding:8px 14px; border-radius:10px; background:#f3f4f6; color:#374151; font-weight:700; font-size:.8rem; text-decoration:none; border:1px solid #e5e7eb; cursor:pointer; }

/* ── Chat embed ─────────────────────────────────────────────── */
.trk-chat { margin:0 14px 12px; }
.trk-chat .order-chat-widget { background:#fff; border-radius:18px; box-shadow:0 2px 14px rgba(0,0,0,.09); overflow:hidden; }
.trk-chat .order-chat-widget__header { padding:16px 18px 12px; border-bottom:1px solid #f3f4f6; display:flex; align-items:flex-start; justify-content:space-between; gap:10px; }
.trk-chat .order-chat-widget__eyebrow { font-size:.65rem; text-transform:uppercase; letter-spacing:.08em; color:#009543; font-weight:800; margin-bottom:3px; }
.trk-chat .order-chat-widget__title { font-size:.92rem; font-weight:800; color:#111; margin:0; }
.trk-chat .order-chat-widget__subtitle { font-size:.78rem; color:#9ca3af; margin-top:2px; }
.trk-chat .order-chat-widget__badge { background:#f0fdf4; border-radius:99px; padding:4px 12px; font-size:.75rem; font-weight:700; color:#009543; display:inline-flex; align-items:center; gap:6px; white-space:nowrap; flex-shrink:0; }
.trk-chat .order-chat-widget__participants { padding:8px 18px; display:flex; gap:12px; background:#f9fafb; border-bottom:1px solid #f3f4f6; flex-wrap:wrap; }
.trk-chat .order-chat-participant { font-size:.72rem; color:#6b7280; }
.trk-chat .order-chat-participant__label { color:#9ca3af; }
.trk-chat .order-chat-widget__messages { max-height:260px; overflow-y:auto; padding:12px 18px; display:flex; flex-direction:column; gap:8px; scroll-behavior:smooth; }
.trk-chat .order-chat-widget__form { padding:12px 18px 16px; border-top:1px solid #f3f4f6; }
.trk-chat .order-chat-widget__form textarea { width:100%; border:1.5px solid #e5e7eb; border-radius:12px; padding:10px 14px; font-size:.88rem; resize:none; outline:none; box-sizing:border-box; font-family:inherit; transition:border-color .15s; }
.trk-chat .order-chat-widget__form textarea:focus { border-color:#009543; }
.trk-chat .order-chat-widget__label { font-size:.72rem; font-weight:700; color:#6b7280; text-transform:uppercase; letter-spacing:.06em; display:block; margin-bottom:5px; }
.trk-chat .order-chat-widget__actions { display:flex; align-items:center; justify-content:space-between; margin-top:8px; gap:10px; }
.trk-chat .order-chat-widget__actions small { font-size:.7rem; color:#9ca3af; flex:1; }

/* ── Actions footer ─────────────────────────────────────────── */
.trk-footer-actions { display:flex; gap:10px; padding:12px 14px 20px; flex-wrap:wrap; }
.trk-footer-actions a, .trk-footer-actions button { flex:1; min-width:130px; text-align:center; padding:13px 14px; border-radius:14px; font-weight:800; font-size:.88rem; text-decoration:none; display:inline-flex; align-items:center; justify-content:center; gap:7px; transition:all .15s; }
.trk-action-primary { background:#009543; color:#fff; border:none; cursor:pointer; }
.trk-action-primary:hover { background:#007a37; color:#fff; }
.trk-action-secondary { background:#f3f4f6; color:#374151; border:1px solid #e5e7eb; }
.trk-action-secondary:hover { background:#e5e7eb; }
.trk-action-whatsapp { background:#25D366; color:#fff; }
.trk-action-whatsapp:hover { background:#1da851; color:#fff; }

/* ── Pickup info ─────────────────────────────────────────────── */
.trk-pickup-info { display:flex; flex-direction:column; gap:10px; }
.trk-info-row { display:flex; flex-direction:column; gap:2px; padding:10px 0; border-bottom:1px solid #f9fafb; }
.trk-info-row:last-child { border-bottom:none; }
.trk-info-label { font-size:.68rem; text-transform:uppercase; letter-spacing:.07em; color:#9ca3af; font-weight:700; }
.trk-info-val { font-size:.9rem; font-weight:700; color:#111; }
.trk-info-hint { font-size:.8rem; color:#6b7280; margin-top:2px; line-height:1.4; }

/* ── Commande annulée ───────────────────────────────────────── */
.trk-cancelled-screen { margin:16px 14px 0; background:#fff; border-radius:18px; box-shadow:0 2px 14px rgba(0,0,0,.09); padding:32px 24px; text-align:center; }
.trk-cancelled-icon { font-size:3.5rem; color:#ef4444; margin-bottom:12px; line-height:1; }
.trk-cancelled-title { font-size:1.3rem; font-weight:900; color:#111; margin-bottom:6px; }
.trk-cancelled-sub { font-size:.9rem; color:#6b7280; line-height:1.5; margin-bottom:16px; }
.trk-cancelled-payment { display:inline-flex; align-items:center; gap:7px; background:#f9fafb; border:1px solid #e5e7eb; border-radius:99px; padding:7px 16px; font-size:.83rem; color:#374151; font-weight:600; margin-bottom:20px; }
.trk-cancelled-actions { display:flex; justify-content:center; }

/* ── Global page BG ─────────────────────────────────────────── */
.bd-track-order-page { background:#f6f7f9; min-height:100vh; }
</style>
@endsection

@section('content')
<div class="trk">

    {{-- ── Topbar ─────────────────────────────────────────────────── --}}
    <div class="trk-topbar">
        <a href="{{ route('user.profile') }}" class="trk-back">
            <i class="fas fa-arrow-left"></i> Retour
        </a>
        <span class="trk-order-ref">Commande #{{ $order->order_no }}</span>
        <span class="trk-badge {{ $statusBadgeClass }}">
            <span class="trk-badge-dot"></span>
            {{ $currentStatus['label'] }}
        </span>
    </div>

    {{-- ── Carte interactive (livraison active uniquement) ─────────── --}}
    @php $isCancelled = in_array($businessStatus, ['cancelled', 'no_show']); @endphp
    @if(!$isPickup && !$isCancelled)
    <div class="trk-map-wrap">
        <div id="trackingMap"></div>
        <div class="trk-map-overlay">
            <div class="trk-map-pill">
                <i class="fas fa-store"></i>
                <span id="restaurantDistance">Calcul…</span>
            </div>
            <div class="trk-map-pill trk-map-pill--driver">
                <i class="fas fa-motorcycle"></i>
                <span id="driverDistance">En attente</span>
            </div>
        </div>
    </div>
    @elseif($isCancelled)
    <div class="trk-cancelled-screen">
        <div class="trk-cancelled-icon"><i class="fas fa-circle-xmark"></i></div>
        <div class="trk-cancelled-title">Commande annulée</div>
        <div class="trk-cancelled-sub">{{ $currentStatus['description'] }}</div>
        @if(!empty($paymentExperience['customer_message']))
            <div class="trk-cancelled-payment">
                <i class="fas fa-credit-card"></i>
                {{ $paymentExperience['customer_message'] }}
            </div>
        @endif
        <div class="trk-cancelled-actions">
            <a href="{{ route('home') }}" class="trk-btn-call" style="text-decoration:none;flex:none;padding:11px 24px">
                <i class="fas fa-plus"></i> Commander à nouveau
            </a>
        </div>
    </div>
    @endif

    {{-- ── Statut & progression (masqué si annulée — trk-cancelled-screen suffit) --}}
    @if(!$isCancelled)
    @php
        $showEtaHero = !$isPickup && $remainingMinutes > 0
            && in_array($businessStatus, ['driver_assigned','picked_up','out_for_delivery','accepted','in_kitchen','ready_for_pickup','pending_restaurant_acceptance']);
        $floatCard = !$isPickup;
    @endphp
    <div class="{{ $floatCard ? 'trk-section--float' : 'trk-section' }}">
        <div class="trk-status-card">

            {{-- ETA hero — visible quand livraison active --}}
            @if($showEtaHero)
            <div class="trk-eta-hero">
                <div class="trk-eta-eyebrow">Arrivée estimée</div>
                <div id="etaCountdown" class="trk-eta-value">{{ $remainingMinutes }} min</div>
                <div id="etaRoute" class="trk-eta-route">
                    <i class="fas fa-route" style="font-size:.75rem;color:#009543"></i>
                    <span>Calcul de la route en cours…</span>
                </div>
            </div>
            @endif

            <div class="trk-status-main">
                <div class="trk-pulse {{ $statusPulseClass }}">
                    @if(in_array($businessStatus, ['delivered','closed','picked_up_by_customer']))
                        <i class="fas fa-circle-check"></i>
                    @elseif(in_array($businessStatus, ['cancelled','no_show']))
                        <i class="fas fa-circle-xmark"></i>
                    @elseif(in_array($businessStatus, ['out_for_delivery','picked_up']))
                        <i class="fas fa-motorcycle" style="animation:trk-blink 1.4s ease-in-out infinite"></i>
                    @elseif(in_array($businessStatus, ['in_kitchen','prepairing']))
                        <i class="fas fa-fire-burner" style="animation:trk-blink 1.4s ease-in-out infinite"></i>
                    @else
                        <i class="fas fa-clock" style="animation:trk-blink 1.6s ease-in-out infinite"></i>
                    @endif
                </div>
                <div class="trk-status-texts">
                    <div id="businessStatusLabel" class="trk-status-label">{{ $currentStatus['label'] }}</div>
                    <div class="trk-status-desc">{{ $currentStatus['description'] }}</div>
                </div>
            </div>

            <div class="trk-progress-wrap">
                <div class="trk-progress-header">
                    <span>Avancement</span>
                    <span id="trackingProgressLabel">{{ $trackingProgress }}%</span>
                </div>
                <div class="trk-progress-track">
                    <div id="progressBar" class="trk-progress-bar" style="width:{{ $trackingProgress }}%"></div>
                </div>
            </div>

            <div class="trk-status-meta">
                <div class="trk-meta-item">
                    <div class="trk-meta-label">Estimé total</div>
                    <div class="trk-meta-val">{{ $estimatedTime }}<small style="font-size:.65rem;font-weight:600;color:#9ca3af"> min</small></div>
                </div>
                <div class="trk-meta-item">
                    <div class="trk-meta-label">Méthode</div>
                    <div class="trk-meta-val trk-meta-val--sm">{{ ucfirst($order->payment_method ?? '—') }}</div>
                </div>
                <div class="trk-meta-item">
                    <div class="trk-meta-label">Paiement</div>
                    <div class="trk-meta-val trk-meta-val--sm">{{ payment_status_label($order->payment_status ?? 'pending') }}</div>
                </div>
            </div>
        </div>
    </div>
    @endif {{-- !$isCancelled status card --}}

    {{-- ── Timeline horizontale ────────────────────────────────────── --}}
    @if(!$isCancelled)
    <div class="trk-timeline-wrap">
        <div class="trk-timeline">
            @foreach($timelineSteps as $index => $step)
                @php
                    $isDone   = $index < $currentStepIndex;
                    $isActive = $index === $currentStepIndex;
                    $icon     = $stepIcons[$step['key']] ?? 'fa-circle';
                @endphp
                <div class="trk-step timeline-step{{ $isDone ? ' is-done' : '' }}{{ $isActive ? ' is-active' : '' }}" data-step="{{ $step['key'] }}">
                    <div class="trk-step__dot timeline-step__index">
                        @if($isDone)
                            <i class="fas fa-check"></i>
                        @elseif($isActive)
                            <i class="fas {{ $icon }}" style="animation:trk-blink 1.2s ease-in-out infinite"></i>
                        @else
                            <i class="fas {{ $icon }}"></i>
                        @endif
                    </div>
                    <div class="trk-step__label timeline-copy">{{ $step['label'] }}</div>
                </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- ── Modification possible ───────────────────────────────────── --}}
    @if($canEditOrder)
    <div class="trk-edit-banner">
        <div class="trk-edit-text">
            <strong>Modification possible</strong>
            <span>Vous pouvez encore ajuster votre commande avant la préparation.</span>
        </div>
        <a href="{{ route('orders.edit', ['orderNo' => $order->order_no]) }}" class="trk-btn-edit">
            <i class="fas fa-pen"></i> Modifier
        </a>
    </div>
    @endif

    {{-- ── Contacts : livreur + restaurant ───────────────────────────── --}}
    <div class="trk-contacts">

        {{-- Livreur --}}
        @if(!$isPickup)
        <div class="trk-contact" id="driverInfoContainer">
            @if($delivery && $delivery->driver)
                @php
                    $drvImg = $delivery->driver->image ?? null;
                    $drvImgUrl = $drvImg ? (\Str::startsWith($drvImg, ['http://','https://']) ? $drvImg : asset('images/driver_images/' . $drvImg)) : null;
                    $drvPhone = $delivery->driver->phone ?? null;
                    $drvWaUrl = $drvPhone ? 'https://wa.me/' . preg_replace('/[^0-9]/', '', $drvPhone) : null;
                @endphp
                <div class="trk-contact-top">
                    @if($drvImgUrl)
                        <img src="{{ $drvImgUrl }}" alt="{{ $delivery->driver->name }}" class="trk-contact-avatar">
                    @else
                        <div class="trk-contact-avatar-ph trk-contact-avatar-ph--green">{{ strtoupper(substr($delivery->driver->name, 0, 2)) }}</div>
                    @endif
                    <div>
                        <div class="trk-contact-role"><i class="fas fa-motorcycle"></i> Livreur</div>
                        <div class="trk-contact-name">{{ $delivery->driver->name }}</div>
                        @if($drvPhone)<div class="trk-contact-sub">{{ $drvPhone }}</div>@endif
                    </div>
                </div>
                <div class="trk-contact-btns">
                    @if($drvPhone)
                        <a href="tel:{{ $drvPhone }}" class="trk-btn-call">
                            <i class="fas fa-phone"></i> Appeler
                        </a>
                    @endif
                    @if($drvWaUrl)
                        <a href="{{ $drvWaUrl }}" class="trk-btn-wa" target="_blank" rel="noopener">
                            <i class="fab fa-whatsapp"></i>
                        </a>
                    @endif
                </div>
            @else
                <div class="trk-no-driver">
                    <i class="fas fa-motorcycle"></i>
                    <div class="trk-contact-role" style="text-align:center;margin:0 0 4px"><i class="fas fa-motorcycle"></i> Livreur</div>
                    Aucun livreur assigné
                </div>
            @endif
        </div>
        @endif

        {{-- Restaurant --}}
        <div class="trk-contact">
            <div class="trk-contact-top">
                @if($restLogoUrl)
                    <img src="{{ $restLogoUrl }}" alt="{{ optional($order->restaurant)->name ?? 'Restaurant' }}" class="trk-contact-avatar">
                @else
                    <div class="trk-contact-avatar-ph trk-contact-avatar-ph--orange">{{ strtoupper(substr(optional($order->restaurant)->name ?? 'R', 0, 2)) }}</div>
                @endif
                <div>
                    <div class="trk-contact-role"><i class="fas fa-store"></i> Restaurant</div>
                    <div class="trk-contact-name">{{ optional($order->restaurant)->name ?? 'Restaurant' }}</div>
                    @if($restPhone)<div class="trk-contact-sub">{{ $restPhone }}</div>@endif
                </div>
            </div>
            <div class="trk-contact-btns">
                @if($restPhone)
                    <a href="tel:{{ $restPhone }}" class="trk-btn-call">
                        <i class="fas fa-phone"></i> Appeler
                    </a>
                @endif
                @if($restWa)
                    <a href="{{ $restWa }}" class="trk-btn-wa" target="_blank" rel="noopener">
                        <i class="fab fa-whatsapp"></i>
                    </a>
                @endif
            </div>
        </div>
    </div>

    {{-- ── Chat de commande ────────────────────────────────────────── --}}
    @if(!empty($chatData) && ($chatData['can_view'] ?? false))
    <div class="trk-chat">
        @include('frontend.partials.order_chat', ['chatData' => $chatData])
    </div>
    @endif

    {{-- ── Code de retrait ─────────────────────────────────────────── --}}
    @if($pickupCodeVisible)
    <div id="pickupCodePanel" class="trk-code-card trk-code-card--pickup">
        <div class="trk-code-eyebrow"><i class="fas fa-qrcode"></i> Code de retrait</div>
        <div class="trk-code-value">{{ $order->pickup_code }}</div>
        <div class="trk-code-hint">
            @if(in_array($businessStatus, ['picked_up_by_customer', 'closed'], true))
                Le retrait a déjà été confirmé. Bon appétit !
            @else
                Présentez ce code au restaurant pour récupérer votre commande.
            @endif
        </div>
    </div>
    @endif

    {{-- ── Code OTP ─────────────────────────────────────────────────── --}}
    @if($otpVisible)
    <div id="otpPanel" class="trk-code-card trk-code-card--otp">
        <div class="trk-code-eyebrow"><i class="fas fa-shield-halved"></i> Code de remise (OTP)</div>
        <div class="trk-code-value">{{ $delivery->delivery_otp_code }}</div>
        <div class="trk-code-hint">
            @if($delivery->otp_verified_at)
                OTP vérifié le {{ $delivery->otp_verified_at->format('d/m à H:i') }}.
            @else
                Donnez ce code au livreur au moment de la remise pour confirmer la réception.
            @endif
        </div>
    </div>
    @endif

    {{-- ── Confirmation de réception ───────────────────────────────── --}}
    @if(($delivery && !$isPickup) || $isPickup)
    <div id="receiptPanel" class="trk-confirm" @if(!$receiptPanelInitiallyVisible) style="display:none" @endif>
        <h3><i class="fas fa-circle-check"></i> Confirmation de réception</h3>
        <p>
            @if($isPickup && in_array($businessStatus, ['picked_up_by_customer', 'closed'], true))
                Retrait confirmé pour la commande #{{ $order->order_no }}.
            @elseif($delivery && $delivery->customer_confirmed_at)
                Réception confirmée le {{ $delivery->customer_confirmed_at->format('d/m/Y à H:i') }}
                via {{ $delivery->delivery_confirmation_method === 'otp' ? 'code OTP' : 'confirmation client' }}.
            @elseif($isPickup)
                Si vous avez récupéré votre commande, entrez le code de retrait pour confirmer.
            @else
                Confirmez la réception pour clôturer votre livraison.
            @endif
        </p>
        @if(!$isPickup && $delivery && $delivery->delivery_proof_path)
            <a href="{{ asset($delivery->delivery_proof_path) }}" target="_blank" class="trk-btn-outline" style="margin-bottom:12px;display:inline-flex">
                <i class="fas fa-image"></i> Preuve de livraison
            </a>
        @endif
        @if($isPickup && auth()->check() && auth()->id() === $order->user_id && !in_array($businessStatus, ['picked_up_by_customer', 'closed'], true))
            <form method="POST" action="{{ route('track.order.confirm', ['orderNo' => $order->order_no]) }}">
                @csrf
                <input type="text" name="delivery_otp" inputmode="numeric" placeholder="Code de retrait" class="trk-input">
                <button type="submit" class="trk-btn-primary"><i class="fas fa-check"></i> Confirmer le retrait</button>
            </form>
        @elseif(!$isPickup && auth()->check() && auth()->id() === $order->user_id && !$delivery->customer_confirmed_at)
            <form method="POST" action="{{ route('track.order.confirm', ['orderNo' => $order->order_no]) }}">
                @csrf
                @if($delivery->requiresOtp())
                    <input type="text" name="delivery_otp" inputmode="numeric" placeholder="Code OTP" class="trk-input" style="margin-bottom:10px">
                @endif
                <input type="hidden" name="customer_confirmed" value="1">
                <button type="submit" class="trk-btn-primary"><i class="fas fa-check"></i> Confirmer la réception</button>
            </form>
        @endif
    </div>
    @endif

    {{-- ── Incident de livraison ───────────────────────────────────── --}}
    @if($delivery && !$isPickup)
        @php
            $showIncidentPanel = in_array($businessStatus, ['delivery_attempt_failed', 'incident_open'], true) || ($delivery->incident_status ?? '') === 'open';
        @endphp
        @if($showIncidentPanel)
        <div class="trk-incident">
            <div class="trk-incident-title"><i class="fas fa-triangle-exclamation"></i> Incident de livraison</div>
            <p class="trk-incident-copy">
                {{ str_replace('_', ' ', ucfirst($delivery->incident_reason ?? 'incident')) }}.
                @if(!empty($delivery->incident_notes)) {{ $delivery->incident_notes }}@endif
            </p>
            <p class="trk-incident-meta">Tentatives : {{ (int)($delivery->failed_attempts ?? 0) }} · Support : {{ $delivery->support_status ?? 'ouvert' }}</p>
            <div class="trk-incident-actions">
                @if(auth()->check() && auth()->id() === $order->user_id)
                    <form method="POST" action="{{ route('track.order.redelivery', ['orderNo' => $order->order_no]) }}" class="trk-incident-form">
                        @csrf
                        <textarea name="notes" rows="2" placeholder="Précisez le meilleur repère ou horaire pour une nouvelle tentative." class="trk-input trk-textarea"></textarea>
                        <button type="submit" class="trk-btn-primary-sm"><i class="fas fa-rotate-right"></i> Demander une re-livraison</button>
                    </form>
                    <form method="POST" action="{{ route('track.order.incident', ['orderNo' => $order->order_no]) }}" class="trk-incident-form">
                        @csrf
                        <select name="reason" class="trk-input">
                            <option value="late_delivery">Retard important</option>
                            <option value="missing_items">Articles manquants</option>
                            <option value="courier_issue">Problème avec le livreur</option>
                            <option value="delivery_issue">Commande non reçue</option>
                        </select>
                        <textarea name="notes" rows="2" placeholder="Décrivez le problème." class="trk-input trk-textarea"></textarea>
                        <button type="submit" class="trk-btn-outline" style="margin-top:6px"><i class="fas fa-gavel"></i> Ouvrir un litige</button>
                    </form>
                @else
                    @if($delivery->driver && $delivery->driver->phone)
                        <a href="tel:{{ $delivery->driver->phone }}" class="trk-btn-call"><i class="fas fa-phone"></i> Appeler le livreur</a>
                    @elseif(optional($order->restaurant)->phone)
                        <a href="tel:{{ $order->restaurant->phone }}" class="trk-btn-outline"><i class="fas fa-phone"></i> Appeler le restaurant</a>
                    @endif
                @endif
            </div>
        </div>
        @endif
    @endif

    {{-- ── Retrait non finalisé ────────────────────────────────────── --}}
    @if($isPickup && $businessStatus === 'no_show')
    <div class="trk-incident">
        <div class="trk-incident-title"><i class="fas fa-user-clock"></i> Retrait non finalisé</div>
        <p class="trk-incident-copy">Le restaurant vous a marqué absent. Vous pouvez réactiver le retrait si vous repassez récupérer la commande.</p>
        <form method="POST" action="{{ route('track.order.reopen_pickup', ['orderNo' => $order->order_no]) }}">
            @csrf
            <button type="submit" class="trk-btn-primary-sm"><i class="fas fa-rotate-right"></i> Réactiver le retrait</button>
        </form>
    </div>
    @endif

    {{-- ── Détail de la commande (collapsible) ────────────────────── --}}
    <div class="trk-card" id="orderDetailCard">
        <div class="trk-card-head" onclick="toggleCard('orderDetailCard')">
            <h3><i class="fas fa-receipt"></i> Détail de la commande</h3>
            <i class="fas fa-chevron-down trk-card-chevron"></i>
        </div>
        <div class="trk-card-body">
            <div>
                @foreach($orderItems as $item)
                <div class="trk-item">
                    @if(optional($item->product)->image)
                        <img src="{{ method_exists($item->product, 'publicImageUrl') ? $item->product->publicImageUrl() : asset('images/product_images/' . $item->product->image) }}" alt="{{ $item->product->name ?? '' }}" class="trk-item-img">
                    @else
                        <div class="trk-item-img-ph"><i class="fas fa-utensils"></i></div>
                    @endif
                    <div style="flex:1;min-width:0">
                        <div class="trk-item-name">{{ $item->product ? $item->product->name : 'Produit' }}</div>
                        <div class="trk-item-qty">Qté : {{ $item->qty }}</div>
                    </div>
                    <div class="trk-item-price">{{ number_format($item->price * $item->qty, 0, ',', ' ') }} FCFA</div>
                </div>
                @endforeach
            </div>
            <div class="trk-summary">
                <div class="trk-summary-row"><span>Sous-total</span><strong>{{ number_format($order->sub_total, 0, ',', ' ') }} FCFA</strong></div>
                <div class="trk-summary-row"><span>{{ $isPickup ? 'Frais de retrait' : 'Livraison' }}</span><strong>{{ number_format($order->delivery_charges, 0, ',', ' ') }} FCFA</strong></div>
                <div class="trk-summary-row"><span>Taxes</span><strong>{{ number_format($order->tax, 0, ',', ' ') }} FCFA</strong></div>
                @if($order->driver_tip > 0)
                    <div class="trk-summary-row"><span>Pourboire livreur</span><strong>{{ number_format($order->driver_tip, 0, ',', ' ') }} FCFA</strong></div>
                @endif
                <div class="trk-summary-total"><span>Total</span><span>{{ number_format($order->total, 0, ',', ' ') }} FCFA</span></div>
            </div>
            @if($isPickup)
            <div style="margin-top:14px">
                <div class="trk-pickup-info">
                    <div class="trk-info-row">
                        <span class="trk-info-label">Restaurant</span>
                        <span class="trk-info-val">{{ optional($order->restaurant)->name ?? 'Restaurant' }}</span>
                    </div>
                    <div class="trk-info-row">
                        <span class="trk-info-label">Adresse</span>
                        <span class="trk-info-val">{{ optional($order->restaurant)->address ?? 'Non renseignée' }}</span>
                    </div>
                    <div class="trk-info-row">
                        <span class="trk-info-label">Référence</span>
                        <span class="trk-info-val">{{ $order->delivery_address }}</span>
                        <span class="trk-info-hint">Présentez votre code de retrait au comptoir.</span>
                    </div>
                </div>
            </div>
            @else
            <div style="margin-top:14px;padding:12px 14px;background:#f9fafb;border-radius:12px">
                <div class="trk-info-label">Adresse de livraison</div>
                <div class="trk-info-val" style="margin-top:4px">{{ $order->delivery_address }}</div>
            </div>
            @endif
        </div>
    </div>

    {{-- ── Paiement (détails étendus) ──────────────────────────────── --}}
    @if($paymentExperience && (!empty($paymentExperience['customer_message']) || !empty($paymentExperience['failure_reason'])))
    <div class="trk-card" id="paymentCard">
        <div class="trk-card-head trk-card--collapsed" onclick="toggleCard('paymentCard')">
            <h3><i class="fas fa-credit-card"></i> Paiement</h3>
            <i class="fas fa-chevron-down trk-card-chevron"></i>
        </div>
        <div class="trk-card-body" style="display:none">
            <div class="trk-pickup-info">
                <div class="trk-info-row">
                    <span class="trk-info-label">Méthode</span>
                    <span class="trk-info-val">{{ ucfirst($order->payment_method) }}</span>
                </div>
                @if(!empty($paymentExperience['customer_message']))
                <div class="trk-info-row">
                    <span class="trk-info-label">Statut</span>
                    <span class="trk-info-val">{{ $paymentExperience['customer_message'] }}</span>
                </div>
                @endif
                @if(!empty($paymentExperience['support_action']))
                <div class="trk-info-row">
                    <span class="trk-info-label">Action requise</span>
                    <span class="trk-info-val" style="color:#f59e0b">{{ $paymentExperience['support_action'] }}</span>
                </div>
                @endif
                @if(!empty($paymentExperience['failure_reason']))
                <div class="trk-info-row">
                    <span class="trk-info-label">Code erreur</span>
                    <span class="trk-info-val" style="color:#ef4444;font-size:.8rem">{{ $paymentExperience['failure_reason'] }}</span>
                </div>
                @endif
            </div>
        </div>
    </div>
    @endif

    {{-- ── Notation ─────────────────────────────────────────────────── --}}
    @if($canRate)
    <div id="ratingPanel" class="trk-rating">
        <h3>Votre avis compte</h3>
        <p>Comment s'est passée votre commande ? Une évaluation aide le restaurant et le livreur.</p>
        <form id="ratingForm">
            @csrf
            <input type="hidden" name="order_id" value="{{ $order->id }}">
            <div class="trk-rating-group">
                <label>Restaurant</label>
                <div class="trk-stars" data-target="rating">
                    @for($i = 1; $i <= 5; $i++)
                        <button type="button" class="trk-star to-star" data-value="{{ $i }}">★</button>
                    @endfor
                </div>
                <input type="hidden" name="rating" id="inputRating">
                <input type="text" name="comment" placeholder="Votre commentaire (facultatif)" class="trk-input" style="margin-top:10px">
            </div>
            @if($hasDriver)
            <div class="trk-rating-group">
                <label>Livreur</label>
                <div class="trk-stars" data-target="driver_rating">
                    @for($i = 1; $i <= 5; $i++)
                        <button type="button" class="trk-star to-star" data-value="{{ $i }}">★</button>
                    @endfor
                </div>
                <input type="hidden" name="driver_rating" id="inputDriverRating">
                <input type="text" name="driver_comment" placeholder="Votre commentaire livreur (facultatif)" class="trk-input" style="margin-top:10px">
            </div>
            @endif
            <button type="submit" class="trk-btn-primary" id="ratingSubmitBtn">
                <i class="fas fa-paper-plane"></i> Envoyer mon avis
            </button>
            <div id="ratingFeedback" style="display:none;margin-top:10px;font-weight:700;font-size:.88rem"></div>
        </form>
    </div>
    @elseif($existingRating)
    <div class="trk-rating" style="text-align:center;border-top-color:#f59e0b">
        <div style="font-size:1.8rem;letter-spacing:2px;margin-bottom:8px">
            {{ str_repeat('★', $existingRating->rating) }}{{ str_repeat('☆', 5 - $existingRating->rating) }}
        </div>
        <p style="margin:0;color:#6b7280;font-size:.88rem">Vous avez déjà noté cette commande. Merci !</p>
    </div>
    @endif

    {{-- ── Actions footer ──────────────────────────────────────────── --}}
    <div class="trk-footer-actions">
        <a href="{{ route('home') }}" class="trk-action-primary">
            <i class="fas fa-plus"></i> Nouvelle commande
        </a>
        <a href="{{ route('order.receipt', $order->order_no) }}" class="trk-action-secondary">
            <i class="fas fa-file-invoice"></i> Reçu
        </a>
        @if(!$isPickup && $delivery && $delivery->driver && $delivery->driver->phone)
            <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $delivery->driver->phone) }}" class="trk-action-whatsapp" target="_blank" rel="noopener">
                <i class="fab fa-whatsapp"></i> Livreur
            </a>
        @endif
    </div>

</div>{{-- .trk --}}

<script>
function toggleCard(id) {
    var card = document.getElementById(id);
    if (!card) return;
    card.classList.toggle('trk-card--collapsed');
    var body = card.querySelector('.trk-card-body');
    if (body) body.style.display = card.classList.contains('trk-card--collapsed') ? 'none' : '';
}
</script>
@endsection

@section('scripts')
@if(!$isPickup)
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css" integrity="sha384-c6Rcwz4e4CITMbu/NBmnNS8yN2sC3cUElMEMfP3vqqKFp7GOYaaBBCqmaWBjmkjb" crossorigin="anonymous"/>
<script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js" integrity="sha384-NElt3Op+9NBMCYaef5HxeJmU4Xeard/Lku8ek6hoPTvYkQPh3zLIrJP7KiRocsxO" crossorigin="anonymous"></script>
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
    let _routeBg = null;
    let _routeDriver = null;
    let _etaSec = {{ max(0, (int)$remainingMinutes) }} * 60;
    let _etaInterval = null;

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

    // ── ETA countdown ─────────────────────────────────────────────
    function _fmtEta(s) {
        if (s <= 0) return 'Imminent';
        const m = Math.floor(s / 60), sec = s % 60;
        return m === 0 ? `${sec}s` : `${m} min ${sec < 10 ? '0'+sec : sec}`;
    }
    function _startEta() {
        const el = document.getElementById('etaCountdown');
        if (!el) return;
        clearInterval(_etaInterval);
        _etaInterval = setInterval(() => {
            _etaSec = Math.max(0, _etaSec - 1);
            el.textContent = _fmtEta(_etaSec);
            el.classList.toggle('trk-eta-value--late', _etaSec < 120 && _etaSec > 0);
            if (_etaSec === 0) clearInterval(_etaInterval);
        }, 1000);
    }
    function _setEtaFromRoute(durationSec, distKm) {
        _etaSec = Math.max(0, Math.round(durationSec));
        const el = document.getElementById('etaCountdown');
        const routeEl = document.getElementById('etaRoute');
        if (el) el.textContent = _fmtEta(_etaSec);
        if (routeEl && distKm) routeEl.innerHTML = `<i class="fas fa-route" style="font-size:.75rem;color:#009543"></i> <span>${distKm} km · route optimisée</span>`;
        _startEta();
    }

    // ── Route polyline (Mapbox Directions) ────────────────────────
    async function _fetchRoute(fromLng, fromLat, toLng, toLat) {
        const url = `https://api.mapbox.com/directions/v5/mapbox/driving/${fromLng},${fromLat};${toLng},${toLat}.json?access_token=${TRACKING_MAPBOX_TOKEN}&geometries=geojson&overview=full&steps=false&language=fr`;
        const r = await fetch(url);
        if (!r.ok) throw new Error('directions ' + r.status);
        return r.json();
    }
    async function _drawBgRoute() {
        if (!map || !TRACKING_MAPBOX_TOKEN) return;
        try {
            const data = await _fetchRoute(RESTAURANT_COORDS.lng, RESTAURANT_COORDS.lat, DELIVERY_COORDS.lng, DELIVERY_COORDS.lat);
            const route = data.routes?.[0];
            if (!route) return;
            if (_routeBg) map.removeLayer(_routeBg);
            _routeBg = L.geoJSON(route.geometry, {
                style: { color:'#d1d5db', weight:5, opacity:.75, dashArray:'10 7', lineCap:'round', lineJoin:'round' }
            }).addTo(map);
        } catch(e) {}
    }
    async function _drawDriverRoute() {
        if (!map || !TRACKING_MAPBOX_TOKEN) return;
        if (!latestDriverCoords?.lat || !latestDriverCoords?.lng) return;
        try {
            const data = await _fetchRoute(latestDriverCoords.lng, latestDriverCoords.lat, DELIVERY_COORDS.lng, DELIVERY_COORDS.lat);
            const route = data.routes?.[0];
            if (!route) return;
            if (_routeDriver) map.removeLayer(_routeDriver);
            _routeDriver = L.geoJSON(route.geometry, {
                style: { color:'#009543', weight:5, opacity:.9, lineCap:'round', lineJoin:'round' }
            }).addTo(map);
            const distKm = (route.distance / 1000).toFixed(1);
            _setEtaFromRoute(route.duration, distKm);
            const dEl = document.getElementById('driverDistance');
            if (dEl) dEl.textContent = distKm + ' km';
        } catch(e) {}
    }

    // ── Smooth driver marker animation ────────────────────────────
    function _animateMarker(marker, toLat, toLng) {
        const from = marker.getLatLng();
        const dur = 2000;
        const t0 = performance.now();
        function step(now) {
            const t = Math.min((now - t0) / dur, 1);
            const e = 1 - Math.pow(1 - t, 3); // ease-out cubic
            marker.setLatLng([from.lat + (toLat - from.lat) * e, from.lng + (toLng - from.lng) * e]);
            if (t < 1) requestAnimationFrame(step);
            else _drawDriverRoute(); // redraw route once animation done
        }
        requestAnimationFrame(step);
    }

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
        if (IS_PICKUP_ORDER) return;
        const restaurantDistance = document.getElementById('restaurantDistance');
        const driverDistance = document.getElementById('driverDistance');
        if (restaurantDistance) {
            restaurantDistance.textContent = formatDistance(haversineKm(RESTAURANT_COORDS.lat, RESTAURANT_COORDS.lng, DELIVERY_COORDS.lat, DELIVERY_COORDS.lng));
        }
        if (driverDistance) {
            if (latestDriverCoords && latestDriverCoords.lat && latestDriverCoords.lng) {
                driverDistance.textContent = formatDistance(haversineKm(latestDriverCoords.lat, latestDriverCoords.lng, DELIVERY_COORDS.lat, DELIVERY_COORDS.lng));
            } else {
                driverDistance.textContent = 'En attente';
            }
        }
    }

    var _stepIcons = {pending:'fa-paper-plane',prepairing:'fa-fire-burner',assign:'fa-motorcycle',pickup:'fa-box',onway:'fa-route',completed:'fa-circle-check'};
    if (IS_PICKUP_ORDER) _stepIcons.assign = 'fa-bell-concierge';

    var _stepBadges = {
        pending:    {bg:'#fef3c7',c:'#92400e',icon:'',text:'En attente du restaurant',blink:true},
        prepairing: {bg:'#fef3c7',c:'#92400e',icon:'fa-fire-burner',text:'La cuisine prépare votre repas',blink:true},
        assign:     {bg:'#dbeafe',c:'#1d4ed8',icon:'fa-satellite-dish',text:IS_PICKUP_ORDER?'Prête au retrait':'Recherche d\'un livreur',spin:true},
        onway:      {bg:'#dcfce7',c:'#15803d',icon:'',text:'En route vers vous',pulse:true},
        pickup:     {bg:'#dbeafe',c:'#1d4ed8',icon:'fa-box',text:'Commande récupérée par le livreur',blink:false},
    };

    function updateTimeline(trackingStatus, progress, businessStatus) {
        const currentIndex = Math.max(trackingOrder.indexOf(trackingStatus), 0);
        document.querySelectorAll('.timeline-step').forEach((step, index) => {
            const isDone = index < currentIndex;
            const isActive = index === currentIndex;
            step.classList.toggle('is-done', isDone);
            step.classList.toggle('is-active', isActive);

            var idx = step.querySelector('.timeline-step__index');
            var stepKey = step.dataset.step || trackingOrder[index];
            var iconCls = _stepIcons[stepKey] || 'fa-circle';
            if (idx) {
                if (isDone) idx.innerHTML = '<i class="fas fa-check"></i>';
                else if (isActive) idx.innerHTML = '<i class="fas ' + iconCls + '" style="animation:trk-blink 1.2s ease-in-out infinite;"></i>';
                else idx.innerHTML = '<i class="fas ' + iconCls + '"></i>';
            }

            var existing = step.querySelector('.bd-step-live-badge');
            if (existing) existing.remove();
            if (isActive && _stepBadges[stepKey]) {
                var b = _stepBadges[stepKey];
                var badge = document.createElement('div');
                badge.className = 'bd-step-live-badge';
                badge.style.cssText = 'margin-top:6px;display:inline-flex;align-items:center;gap:6px;padding:4px 10px;border-radius:99px;font-size:.72rem;font-weight:700;background:'+b.bg+';color:'+b.c+';';
                var inner = '';
                if (b.blink) inner = '<span style="width:7px;height:7px;border-radius:50%;background:'+b.c+';animation:trk-blink 1s infinite;"></span>';
                else if (b.spin && b.icon) inner = '<i class="fas '+b.icon+'" style="animation:bd-spin 2s linear infinite;"></i>';
                else if (b.pulse) inner = '<span style="width:7px;height:7px;border-radius:50%;background:#22c55e;animation:trk-blink 1.5s infinite;"></span>';
                else if (b.icon) inner = '<i class="fas '+b.icon+'"></i>';
                inner += ' ' + b.text;
                badge.innerHTML = inner;
                var copyEl = step.querySelector('.timeline-copy');
                if (copyEl) copyEl.after(badge);
            }
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
        var drvWa = driver.phone ? 'https://wa.me/' + driver.phone.replace(/[^0-9]/g,'') : '';
        container.innerHTML = `
            <div class="trk-contact-top">
                <div class="trk-contact-avatar-ph trk-contact-avatar-ph--green">${driver.name ? driver.name.substring(0,2).toUpperCase() : 'DR'}</div>
                <div>
                    <div class="trk-contact-role"><i class="fas fa-motorcycle"></i> Livreur</div>
                    <div class="trk-contact-name">${driver.name || 'Livreur'}</div>
                    ${driver.phone ? '<div class="trk-contact-sub">' + driver.phone + '</div>' : ''}
                </div>
            </div>
            <div class="trk-contact-btns">
                ${driver.phone ? '<a href="tel:' + driver.phone + '" class="trk-btn-call"><i class="fas fa-phone"></i> Appeler</a>' : ''}
                ${drvWa ? '<a href="' + drvWa + '" class="trk-btn-wa" target="_blank" rel="noopener"><i class="fab fa-whatsapp"></i></a>' : ''}
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
                    const newLat = parseFloat(data.driver.location.latitude);
                    const newLng = parseFloat(data.driver.location.longitude);
                    if (newLat && newLng) {
                        if (driverMarker && map) {
                            _animateMarker(driverMarker, newLat, newLng);
                        } else if (map) {
                            driverMarker = L.marker([newLat, newLng], { icon: _makeIcon('#009543','🛵'), title: 'Livreur' }).addTo(map);
                            _drawDriverRoute();
                        }
                        latestDriverCoords = { lat: newLat, lng: newLng };
                        updateDistanceSummaries();
                    }
                }
            }
        } catch (error) {
            console.error(error);
        }
    }

    function refreshTrackingOnResume() {
        if (document.visibilityState && document.visibilityState !== 'visible') return;
        updateTracking();
    }

    const TRACKING_MAPBOX_TOKEN = @json(mapbox_public_token());

    function _makeIcon(color, emoji) {
        var el = document.createElement('div');
        el.style.cssText = 'width:36px;height:36px;border-radius:50%;background:' + color + ';display:flex;align-items:center;justify-content:center;font-size:17px;box-shadow:0 3px 10px rgba(0,0,0,.35);border:2.5px solid #fff;';
        el.textContent = emoji;
        return L.divIcon({ html: el.outerHTML, iconSize: [36, 36], iconAnchor: [18, 18], className: '' });
    }

    function initTrackingMap() {
        if (IS_PICKUP_ORDER) return;

        const box = document.getElementById('trackingMap');
        if (!box) return;

        if (!TRACKING_MAPBOX_TOKEN) {
            box.innerHTML = '<div class="trk-map-fallback"><i class="fas fa-map" style="font-size:2rem;color:#d1d5db"></i><span>Carte indisponible</span></div>';
            return;
        }

        if (typeof L === 'undefined') {
            // Leaflet pas encore prêt (rare) — retry dans 1 s sans afficher d'erreur
            setTimeout(initTrackingMap, 1000);
            return;
        }

        map = L.map(box, { zoomControl: true, attributionControl: false }).setView([DELIVERY_COORDS.lat, DELIVERY_COORDS.lng], 14);

        L.tileLayer('https://api.mapbox.com/styles/v1/mapbox/streets-v12/tiles/{z}/{x}/{y}?access_token=' + TRACKING_MAPBOX_TOKEN, {
            tileSize: 512,
            zoomOffset: -1,
            maxZoom: 18,
        }).addTo(map);

        // Ordre des layers : route bg → route driver → markers
        _drawBgRoute().then(() => {
            if (latestDriverCoords?.lat && latestDriverCoords?.lng) _drawDriverRoute();
        });

        restaurantMarker= L.marker([RESTAURANT_COORDS.lat, RESTAURANT_COORDS.lng],{ icon: _makeIcon('#f97316','🍽️'), title: 'Restaurant', zIndexOffset:100 }).addTo(map);
        deliveryMarker  = L.marker([DELIVERY_COORDS.lat, DELIVERY_COORDS.lng],   { icon: _makeIcon('#ef4444','📍'), title: 'Adresse de livraison', zIndexOffset:200 }).addTo(map);

        if (latestDriverCoords?.lat && latestDriverCoords?.lng) {
            driverMarker = L.marker([latestDriverCoords.lat, latestDriverCoords.lng], { icon: _makeIcon('#009543','🛵'), title: 'Livreur', zIndexOffset:300 }).addTo(map);
        }

        updateDistanceSummaries();
        _startEta();

        // Ajuster la vue pour inclure tous les points
        try {
            var bounds = L.latLngBounds([
                [DELIVERY_COORDS.lat, DELIVERY_COORDS.lng],
                [RESTAURANT_COORDS.lat, RESTAURANT_COORDS.lng]
            ]);
            if (latestDriverCoords?.lat) bounds.extend([latestDriverCoords.lat, latestDriverCoords.lng]);
            map.fitBounds(bounds, { padding: [50, 50], maxZoom: 15 });
        } catch(e) {}
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
.trk-star.active, .trk-star:hover { color:#f59e0b; }
</style>

<script>
(function () {
    // ── Étoiles interactives ───────────────────────────────────────────
    document.querySelectorAll('.trk-stars').forEach(function (group) {
        var stars  = group.querySelectorAll('.trk-star');
        var target = group.dataset.target;
        var hidden = document.getElementById(target === 'driver_rating' ? 'inputDriverRating' : 'inputRating');

        stars.forEach(function (star, idx) {
            star.addEventListener('mouseenter', function () {
                stars.forEach(function (s, i) { s.classList.toggle('active', i <= idx); });
            });
            star.addEventListener('click', function () {
                hidden.value = star.dataset.value;
                stars.forEach(function (s, i) { s.classList.toggle('active', i <= idx); });
            });
        });
        group.addEventListener('mouseleave', function () {
            var val = parseInt(hidden ? hidden.value : 0);
            stars.forEach(function (s, i) { s.classList.toggle('active', val > 0 && i < val); });
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
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Envoi…';

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
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': payload._token },
            credentials: 'same-origin',
            body: JSON.stringify(payload),
        })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            if (data.status) {
                feedback.style.color = '#009543';
                feedback.textContent = '✓ Merci pour votre avis !';
                feedback.style.display = 'block';
                form.querySelectorAll('button, input, textarea').forEach(function (el) { el.disabled = true; });
                btn.style.display = 'none';
            } else {
                feedback.style.color = '#ef4444';
                feedback.textContent = data.message || 'Une erreur est survenue.';
                feedback.style.display = 'block';
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-paper-plane"></i> Envoyer mon avis';
            }
        })
        .catch(function () {
            feedback.style.color = '#ef4444';
            feedback.textContent = 'Erreur réseau. Veuillez réessayer.';
            feedback.style.display = 'block';
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-paper-plane"></i> Envoyer mon avis';
        });
    });
})();
</script>

{{-- ── Soketi WebSocket — suivi de commande temps réel ──────────────────── --}}
<script src="https://cdn.jsdelivr.net/npm/pusher-js@8.5.0/dist/web/pusher.min.js"
        integrity="sha384-uL9egdFHAuMuLFCiNbD2ihk8UhS3m64kqg17C2aI6lwuJ3psgm2Dj20y9QgXyzWw"
        crossorigin="anonymous"></script>
<script>
(function () {
    var PUSHER_KEY  = @json(config('broadcasting.connections.pusher.key', ''));
    var ORDER_NO    = @json($order->order_no);
    var CSRF_TOKEN  = @json(csrf_token());

    if (!PUSHER_KEY || typeof Pusher === 'undefined') return;

    var pusher = new Pusher(PUSHER_KEY, {
        wsHost:            window.location.hostname,
        wsPort:            443,
        wssPort:           443,
        forceTLS:          true,
        disableStats:      true,
        enabledTransports: ['wss'],
        cluster:           'mt1',
        authEndpoint:      '/broadcasting/auth',
        auth: { headers: { 'X-CSRF-TOKEN': CSRF_TOKEN } },
    });

    var channel = pusher.subscribe('private-food.order.' + ORDER_NO + '.status');
    channel.bind('food.order.status.updated', function (data) {
        if (typeof updateTrackingUI === 'function' && data.business_status) {
            updateTrackingUI(data.business_status, data);
        } else {
            setTimeout(function () { window.location.reload(); }, 1500);
        }
    });

    var presenceChannel = pusher.subscribe('presence-food.order.' + ORDER_NO + '.presence');
    presenceChannel.bind('food.driver.location.updated', function (data) {
        if (data.latitude && data.longitude && typeof updateDriverMarker === 'function') {
            updateDriverMarker(data.latitude, data.longitude);
        }
    });

    pusher.connection.bind('error', function (err) {
        console.warn('[BdWS] connexion Soketi:', err?.error?.data?.code);
    });
})();
</script>
@endsection
