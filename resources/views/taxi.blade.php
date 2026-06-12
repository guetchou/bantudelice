@extends('frontend.layouts.app-modern')
@section('title', 'Commander un Taxi | Kende')

@php
    $pricingData = [
        'base_fare' => (float) ($pricing->base_fare ?? 500),
        'price_per_km' => (float) ($pricing->price_per_km ?? 200),
        'price_per_minute' => (float) ($pricing->price_per_minute ?? 50),
        'minimum_fare' => (float) ($pricing->minimum_fare ?? 1000),
        'surge_multiplier' => (float) ($pricing->surge_multiplier ?? 1),
    ];

    $rideOptions = [
        [
            'key' => 'eco',
            'name' => 'Eco',
            'description' => 'Trajet du quotidien, prix le plus leger',
            'multiplier' => 1,
            'icon' => 'fas fa-taxi',
        ],
        [
            'key' => 'comfort',
            'name' => 'Confort',
            'description' => 'Plus d espace et prise en charge plus douce',
            'multiplier' => 1.18,
            'icon' => 'fas fa-star',
        ],
        [
            'key' => 'xl',
            'name' => 'XL',
            'description' => 'Ideal si vous etes plusieurs ou avec bagages',
            'multiplier' => 1.35,
            'icon' => 'fas fa-users',
        ],
    ];
@endphp

@section('styles')
<style>
    .taxi-shell {
        background:
            radial-gradient(circle at top left, rgba(255,90,31,0.12), transparent 28%),
            radial-gradient(circle at top right, rgba(37,99,235,0.12), transparent 24%),
            linear-gradient(180deg, #fff9f5 0%, #f8fafc 48%, #ffffff 100%);
        padding: 96px 0 56px;
    }

    .taxi-hero {
        display: grid;
        grid-template-columns: minmax(0, 1.15fr) minmax(320px, 0.85fr);
        gap: 24px;
        align-items: stretch;
        margin-bottom: 28px;
    }

    .taxi-hero__copy,
    .taxi-hero__trust {
        border-radius: 32px;
        overflow: hidden;
        position: relative;
        box-shadow: 0 24px 70px rgba(15, 23, 42, 0.08);
        border: 1px solid rgba(15, 23, 42, 0.06);
        background: #ffffff;
    }

    .taxi-hero__copy {
        padding: 34px 34px 30px;
        background:
            linear-gradient(135deg, rgba(255,90,31,0.95) 0%, rgba(245,158,11,0.92) 54%, rgba(255,214,10,0.88) 100%);
        color: #ffffff;
    }

    .taxi-hero__eyebrow {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        min-height: 36px;
        padding: 0 14px;
        border-radius: 999px;
        background: rgba(255,255,255,0.18);
        border: 1px solid rgba(255,255,255,0.24);
        font-size: 0.78rem;
        font-weight: 800;
        letter-spacing: 0.08em;
        text-transform: uppercase;
    }

    .taxi-hero__copy h1 {
        margin: 18px 0 10px;
        font-size: clamp(2.1rem, 4vw, 3.7rem);
        line-height: 0.95;
        font-weight: 900;
        letter-spacing: -0.05em;
    }

    .taxi-hero__copy p {
        margin: 0;
        max-width: 680px;
        color: rgba(255,255,255,0.9);
        font-size: 1rem;
        line-height: 1.75;
    }

    .taxi-hero__metrics {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 12px;
        margin-top: 22px;
    }

    .taxi-hero__metric {
        padding: 16px 18px;
        border-radius: 22px;
        background: rgba(15,23,42,0.14);
        border: 1px solid rgba(255,255,255,0.18);
    }

    .taxi-hero__metric span,
    .taxi-hero__metric small {
        display: block;
    }

    .taxi-hero__metric span {
        font-size: 1.15rem;
        font-weight: 900;
    }

    .taxi-hero__metric small {
        margin-top: 4px;
        color: rgba(255,255,255,0.82);
        font-size: 0.8rem;
    }

    .taxi-hero__trust {
        padding: 28px;
        display: grid;
        gap: 14px;
        align-content: center;
        background:
            linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
    }

    .taxi-hero__trust h2 {
        margin: 0;
        color: #0f172a;
        font-size: 1.35rem;
        font-weight: 900;
        letter-spacing: -0.03em;
    }

    .taxi-trust-list {
        display: grid;
        gap: 12px;
    }

    .taxi-trust-item {
        display: flex;
        align-items: flex-start;
        gap: 12px;
        padding: 14px 16px;
        border-radius: 20px;
        background: #ffffff;
        border: 1px solid rgba(15,23,42,0.06);
    }

    .taxi-trust-item i {
        width: 42px;
        height: 42px;
        border-radius: 14px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: rgba(255,90,31,0.1);
        color: #ff5a1f;
        font-size: 1rem;
    }

    .taxi-trust-item strong {
        display: block;
        color: #0f172a;
        font-size: 0.96rem;
        font-weight: 800;
    }

    .taxi-trust-item small {
        display: block;
        margin-top: 4px;
        color: #64748b;
        line-height: 1.5;
    }

    .taxi-booking-grid {
        display: grid;
        grid-template-columns: minmax(0, 1.25fr) minmax(330px, 0.8fr);
        gap: 24px;
        align-items: start;
    }

    .taxi-map-card,
    .taxi-side-card {
        background: rgba(255,255,255,0.98);
        border-radius: 32px;
        border: 1px solid rgba(15,23,42,0.06);
        box-shadow: 0 24px 70px rgba(15,23,42,0.08);
    }

    .taxi-map-card {
        overflow: hidden;
        position: relative;
        min-height: 690px;
    }

    #taxiMap {
        width: 100%;
        height: 690px;
        z-index: 1;
    }

    #taxiMap .leaflet-control-container {
        z-index: 900;
    }

    .taxi-overlay-card {
        position: absolute;
        top: 18px;
        left: 18px;
        right: 18px;
        z-index: 1200;
        padding: 18px;
        border-radius: 28px;
        background: rgba(255,255,255,0.98);
        border: 1px solid rgba(15,23,42,0.06);
        box-shadow: 0 20px 40px rgba(15,23,42,0.12);
        backdrop-filter: blur(14px);
    }

    .taxi-overlay-card__top {
        display: flex;
        justify-content: space-between;
        gap: 12px;
        align-items: center;
        margin-bottom: 14px;
    }

    .taxi-overlay-card__title {
        margin: 0;
        color: #0f172a;
        font-size: 1.05rem;
        font-weight: 900;
        letter-spacing: -0.03em;
    }

    .taxi-overlay-card__subtitle {
        margin: 4px 0 0;
        color: #64748b;
        font-size: 0.86rem;
        line-height: 1.55;
    }

    .taxi-geostate {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        min-height: 38px;
        padding: 0 12px;
        border-radius: 999px;
        background: #f8fafc;
        border: 1px solid rgba(15,23,42,0.08);
        color: #0f172a;
        font-size: 0.78rem;
        font-weight: 800;
        white-space: nowrap;
    }

    .taxi-geostate[data-state="success"] {
        color: #047857;
        background: #ecfdf5;
        border-color: rgba(16,185,129,0.25);
    }

    .taxi-geostate[data-state="error"] {
        color: #b91c1c;
        background: #fef2f2;
        border-color: rgba(239,68,68,0.22);
    }

    .taxi-fields {
        display: grid;
        gap: 14px;
    }

    .taxi-field {
        position: relative;
    }

    .taxi-field__label {
        display: block;
        margin-bottom: 8px;
        font-size: 0.78rem;
        font-weight: 800;
        color: #475569;
        text-transform: uppercase;
        letter-spacing: 0.08em;
    }

    .taxi-field__icon {
        position: absolute;
        left: 16px;
        top: 46px;
        color: #0f172a;
        font-size: 0.95rem;
    }

    #pickupInput,
    #dropoffInput,
    #scheduledAtInput,
    #passengerCount {
        width: 100%;
        min-height: 56px;
        padding: 0 16px 0 46px;
        border-radius: 18px;
        border: 1px solid rgba(15,23,42,0.1) !important;
        background: #ffffff !important;
        color: #111827 !important;
        font-size: 0.96rem;
        position: relative;
        z-index: 1201;
        box-shadow: inset 0 1px 2px rgba(15,23,42,0.04);
    }

    #pickupInput {
        padding-right: 110px;
    }

    #pickupInput::placeholder,
    #dropoffInput::placeholder,
    #scheduledAtInput::placeholder {
        color: #64748b !important;
        opacity: 1 !important;
    }

    .taxi-locate-btn {
        position: absolute;
        right: 8px;
        top: 34px;
        min-height: 40px;
        border: 0;
        background: #111827;
        color: #ffffff;
        padding: 0 12px;
        border-radius: 14px;
        font-size: 0.78rem;
        font-weight: 800;
        cursor: pointer;
    }

    .taxi-status {
        margin-top: 8px;
        font-size: 0.8rem;
        color: #64748b;
    }

    .taxi-note {
        width: 100%;
        margin-top: 10px;
        padding: 0.85rem 1rem;
        border: 1px solid rgba(15,23,42,0.09);
        border-radius: 16px;
        font-size: 0.92rem;
        color: #111827;
        resize: vertical;
        min-height: 82px;
        background: #ffffff;
    }

    .taxi-suggestions {
        position: absolute;
        left: 0;
        right: 0;
        top: calc(100% + 6px);
        background: #ffffff;
        border: 1px solid rgba(15,23,42,0.08);
        border-radius: 16px;
        box-shadow: 0 18px 36px rgba(15,24,39,0.12);
        z-index: 1300;
        max-height: 220px;
        overflow-y: auto;
        display: none;
    }

    .taxi-suggestions.is-visible {
        display: block;
    }

    .taxi-suggestion-item {
        width: 100%;
        border: none;
        background: transparent;
        text-align: left;
        padding: 0.9rem 1rem;
        color: #111827;
        cursor: pointer;
        border-bottom: 1px solid #f1f5f9;
        font-size: 0.92rem;
    }

    .taxi-suggestion-item:last-child {
        border-bottom: none;
    }

    .taxi-suggestion-item:hover {
        background: #fff7ed;
    }

    .taxi-helper {
        display: flex;
        gap: 0.6rem;
        flex-wrap: wrap;
        margin-top: 4px;
    }

    .taxi-helper button {
        border: 1px solid rgba(15,23,42,0.08);
        background: #f8fafc;
        color: #0f172a;
        padding: 0.58rem 0.9rem;
        border-radius: 999px;
        font-size: 0.78rem;
        font-weight: 800;
        cursor: pointer;
    }

    .taxi-helper button.is-active {
        background: #111827;
        color: #ffffff;
        border-color: #111827;
    }

    .taxi-side-card {
        padding: 28px;
        position: sticky;
        top: 112px;
    }

    .taxi-side-card__header {
        display: flex;
        justify-content: space-between;
        gap: 16px;
        align-items: flex-start;
        margin-bottom: 18px;
    }

    .taxi-side-card__title {
        margin: 0;
        font-size: 1.28rem;
        font-weight: 900;
        color: #0f172a;
        letter-spacing: -0.03em;
    }

    .taxi-side-card__subtitle {
        margin: 4px 0 0;
        color: #64748b;
        font-size: 0.9rem;
        line-height: 1.55;
    }

    .taxi-service-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 72px;
        min-height: 42px;
        padding: 0 14px;
        border-radius: 999px;
        background: #fff7ed;
        color: #c2410c;
        font-weight: 900;
        font-size: 0.88rem;
    }

    .taxi-trip-card {
        display: grid;
        gap: 12px;
        margin-bottom: 18px;
    }

    .taxi-trip-point {
        display: flex;
        gap: 12px;
        padding: 14px 16px;
        border-radius: 20px;
        background: #f8fafc;
        border: 1px solid rgba(15,23,42,0.06);
    }

    .taxi-trip-point__pin {
        width: 16px;
        height: 16px;
        border-radius: 999px;
        margin-top: 5px;
        flex-shrink: 0;
    }

    .taxi-trip-point__pin.is-pickup {
        background: #009543;
        box-shadow: 0 0 0 6px rgba(16,185,129,0.14);
    }

    .taxi-trip-point__pin.is-dropoff {
        background: #ef4444;
        box-shadow: 0 0 0 6px rgba(239,68,68,0.12);
    }

    .taxi-trip-point strong {
        display: block;
        color: #475569;
        font-size: 0.78rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.08em;
    }

    .taxi-trip-point span {
        display: block;
        margin-top: 4px;
        color: #0f172a;
        font-size: 0.95rem;
        font-weight: 700;
        line-height: 1.45;
    }

    .taxi-estimate-panel {
        display: none;
        padding: 18px;
        border-radius: 24px;
        background: linear-gradient(180deg, #fff7ed 0%, #ffffff 100%);
        border: 1px solid rgba(255,90,31,0.12);
        margin-bottom: 18px;
    }

    .taxi-estimate-panel.is-visible {
        display: block;
    }

    .taxi-estimate-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 12px;
    }

    .taxi-estimate-stat {
        padding: 12px 14px;
        border-radius: 18px;
        background: rgba(255,255,255,0.92);
        border: 1px solid rgba(15,23,42,0.05);
    }

    .taxi-estimate-stat small {
        display: block;
        color: #64748b;
        font-size: 0.78rem;
        margin-bottom: 6px;
    }

    .taxi-estimate-stat strong {
        color: #0f172a;
        font-size: 1rem;
        font-weight: 900;
    }

    .taxi-price-band {
        margin-top: 14px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 16px;
        padding-top: 14px;
        border-top: 1px solid rgba(15,23,42,0.08);
    }

    .taxi-price-band small {
        color: #64748b;
    }

    .taxi-price-band strong {
        color: #ff5a1f;
        font-size: 1.55rem;
        font-weight: 900;
        letter-spacing: -0.04em;
    }

    .taxi-option-grid {
        display: grid;
        gap: 10px;
        margin-bottom: 18px;
    }

    .taxi-option-card {
        display: flex;
        align-items: flex-start;
        gap: 12px;
        width: 100%;
        padding: 16px;
        border-radius: 22px;
        border: 1px solid rgba(15,23,42,0.08);
        background: #ffffff;
        cursor: pointer;
        text-align: left;
        transition: .18s ease;
    }

    .taxi-option-card.is-selected {
        background: #fff7ed;
        border-color: rgba(255,90,31,0.35);
        box-shadow: 0 14px 32px rgba(255,90,31,0.12);
    }

    .taxi-option-card__icon {
        width: 48px;
        height: 48px;
        border-radius: 16px;
        background: #f8fafc;
        color: #111827;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 1rem;
        flex-shrink: 0;
    }

    .taxi-option-card__copy strong,
    .taxi-mode-card strong {
        display: block;
        color: #0f172a;
        font-size: 0.95rem;
        font-weight: 900;
    }

    .taxi-option-card__copy small,
    .taxi-mode-card small {
        display: block;
        margin-top: 4px;
        color: #64748b;
        line-height: 1.45;
    }

    .taxi-option-card__price {
        margin-left: auto;
        color: #0f172a;
        font-size: 0.92rem;
        font-weight: 900;
        white-space: nowrap;
    }

    .taxi-side-section {
        margin-bottom: 18px;
    }

    .taxi-side-section__title {
        display: block;
        margin-bottom: 10px;
        color: #475569;
        font-size: 0.78rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.08em;
    }

    .taxi-mode-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 10px;
    }

    .taxi-mode-card {
        display: flex;
        gap: 12px;
        align-items: flex-start;
        padding: 15px;
        border-radius: 20px;
        border: 1px solid rgba(15,23,42,0.08);
        background: #ffffff;
        cursor: pointer;
    }

    .taxi-mode-card input {
        margin-top: 3px;
    }

    .taxi-side-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 12px;
    }

    .taxi-hint {
        margin-top: 8px;
        color: #64748b;
        font-size: 0.8rem;
        line-height: 1.5;
    }

    .taxi-confirm {
        width: 100%;
        min-height: 60px;
        border: 0;
        border-radius: 20px;
        background: linear-gradient(135deg, #111827 0%, #334155 100%);
        color: #ffffff;
        font-size: 1rem;
        font-weight: 900;
        letter-spacing: 0.01em;
        box-shadow: 0 20px 40px rgba(15,23,42,0.18);
    }

    .taxi-confirm[disabled] {
        opacity: 0.55;
        cursor: not-allowed;
        box-shadow: none;
    }

    .taxi-legal {
        margin-top: 12px;
        color: #64748b;
        font-size: 0.8rem;
        line-height: 1.6;
        text-align: center;
    }

    .taxi-login-note {
        margin-top: 14px;
        padding: 14px 16px;
        border-radius: 18px;
        background: #eff6ff;
        color: #1d4ed8;
        font-size: 0.84rem;
        font-weight: 700;
        line-height: 1.5;
    }

    @media (max-width: 1180px) {
        .taxi-hero,
        .taxi-booking-grid {
            grid-template-columns: 1fr;
        }

        .taxi-side-card {
            position: static;
            top: auto;
        }
    }

    @media (max-width: 767px) {
        .taxi-shell {
            padding-top: 88px;
        }

        .taxi-hero__copy,
        .taxi-hero__trust,
        .taxi-side-card {
            padding: 22px;
        }

        .taxi-hero__metrics,
        .taxi-estimate-grid,
        .taxi-mode-grid,
        .taxi-side-grid {
            grid-template-columns: 1fr;
        }

        #taxiMap,
        .taxi-map-card {
            min-height: 720px;
            height: 720px;
        }

        .taxi-overlay-card {
            top: 12px;
            left: 12px;
            right: 12px;
            padding: 14px;
            border-radius: 24px;
        }

        .taxi-overlay-card__top,
        .taxi-side-card__header,
        .taxi-price-band {
            flex-direction: column;
            align-items: flex-start;
        }
    }
</style>
@endsection

@section('content')
<section class="taxi-shell">
    <div class="container">
        <div class="taxi-hero">
            <div class="taxi-hero__copy">
                <span class="taxi-hero__eyebrow"><i class="fas fa-location-arrow"></i> Taxi urbain en temps reel</span>
                <h1>Un trajet plus clair, du point de depart jusqu a l arrivee.</h1>
                <p>La page taxi a ete repensee pour se rapprocher des meilleurs usages Uber, BlaBlaCar et ride-hailing moderne: localisation visible, estimation lisible, choix de service et confirmation simple.</p>
                <div class="taxi-hero__metrics">
                    <article class="taxi-hero__metric">
                        <span>GPS</span>
                        <small>Detection navigateur + repere manuel sur carte</small>
                    </article>
                    <article class="taxi-hero__metric">
                        <span>Prix</span>
                        <small>Estimation avant confirmation avec option de course</small>
                    </article>
                    <article class="taxi-hero__metric">
                        <span>Suivi</span>
                        <small>Reservation creee puis detail de course et paiement</small>
                    </article>
                </div>
            </div>
            <aside class="taxi-hero__trust">
                <h2>Ce qui change sur cette page</h2>
                <div class="taxi-trust-list">
                    <article class="taxi-trust-item">
                        <i class="fas fa-map-marked-alt"></i>
                        <div>
                            <strong>Geolocalisation plus robuste</strong>
                            <small>Depart detecte automatiquement si le navigateur l autorise, sinon recherche adresse ou repere sur la carte.</small>
                        </div>
                    </article>
                    <article class="taxi-trust-item">
                        <i class="fas fa-car-side"></i>
                        <div>
                            <strong>Choix de course plus explicite</strong>
                            <small>Eco, Confort ou XL, avec un prix qui se met a jour avant creation de la reservation.</small>
                        </div>
                    </article>
                    <article class="taxi-trust-item">
                        <i class="fas fa-clock"></i>
                        <div>
                            <strong>Depart maintenant ou programme</strong>
                            <small>Le client peut laisser la course immediate ou saisir une date de depart.</small>
                        </div>
                    </article>
                </div>
            </aside>
        </div>

        <div class="taxi-booking-grid">
            <div class="taxi-map-card">
                <div id="taxiMap"></div>
                <div class="taxi-overlay-card">
                    <div class="taxi-overlay-card__top">
                        <div>
                            <h2 class="taxi-overlay-card__title">Localisez votre trajet</h2>
                            <p class="taxi-overlay-card__subtitle">Saisissez vos adresses, utilisez "Me localiser" ou posez les reperes directement sur la carte.</p>
                        </div>
                        <span class="taxi-geostate" id="geoState" data-state="idle"><i class="fas fa-crosshairs"></i> Attente GPS</span>
                    </div>

                    <div class="taxi-fields">
                        <div class="taxi-field">
                            <label for="pickupInput" class="taxi-field__label">Point de depart</label>
                            <i class="fas fa-circle taxi-field__icon" style="color:#009543;"></i>
                            <input type="text" id="pickupInput" placeholder="Ex: Mfilou, Marche Total ou aeroport" autocomplete="off">
                            <button type="button" id="locateMeBtn" class="taxi-locate-btn">Me localiser</button>
                            <div id="pickupSuggestions" class="taxi-suggestions"></div>
                            <div id="pickupStatus" class="taxi-status">Nous pouvons recuperer votre position ou vous laisser saisir l adresse manuellement.</div>
                            <textarea id="pickupNote" class="taxi-note" placeholder="Repere depart: portail, immeuble, station, commerce, etage..."></textarea>
                        </div>

                        <div class="taxi-field">
                            <label for="dropoffInput" class="taxi-field__label">Destination</label>
                            <i class="fas fa-map-marker-alt taxi-field__icon" style="color:#ef4444;"></i>
                            <input type="text" id="dropoffInput" placeholder="Ex: Aeroport Maya-Maya, Poto-Poto, Talangai" autocomplete="off">
                            <div id="dropoffSuggestions" class="taxi-suggestions"></div>
                            <div id="dropoffStatus" class="taxi-status">Choisissez votre arrivee par recherche ou en cliquant sur la carte.</div>
                            <textarea id="dropoffNote" class="taxi-note" placeholder="Repere arrivee: quartier, batiment, barriere, pharmacie, commerce..."></textarea>
                        </div>
                    </div>

                    <div class="taxi-helper">
                        <button type="button" id="setPickupPinBtn" class="is-active">Repere depart</button>
                        <button type="button" id="setDropoffPinBtn">Repere arrivee</button>
                        <button type="button" id="centerRouteBtn">Centrer la carte</button>
                        <button type="button" id="clearRouteBtn">Reinitialiser</button>
                    </div>
                </div>
            </div>

            <aside class="taxi-side-card">
                <div class="taxi-side-card__header">
                    <div>
                        <h3 class="taxi-side-card__title">Votre course</h3>
                        <p class="taxi-side-card__subtitle">Un resume simple, puis la reservation vous emmene vers la page de suivi de course et de paiement.</p>
                    </div>
                    <span class="taxi-service-badge">Taxi</span>
                </div>

                <div class="taxi-trip-card">
                    <article class="taxi-trip-point">
                        <span class="taxi-trip-point__pin is-pickup"></span>
                        <div>
                            <strong>Depart</strong>
                            <span id="summaryPickup">Non defini pour l instant</span>
                        </div>
                    </article>
                    <article class="taxi-trip-point">
                        <span class="taxi-trip-point__pin is-dropoff"></span>
                        <div>
                            <strong>Arrivee</strong>
                            <span id="summaryDropoff">Ajoutez une destination</span>
                        </div>
                    </article>
                </div>

                <div id="estimateSection" class="taxi-estimate-panel">
                    <div class="taxi-estimate-grid">
                        <article class="taxi-estimate-stat">
                            <small>Distance</small>
                            <strong id="estDistance">-- km</strong>
                        </article>
                        <article class="taxi-estimate-stat">
                            <small>Duree</small>
                            <strong id="estDuration">-- min</strong>
                        </article>
                        <article class="taxi-estimate-stat">
                            <small>Tarif min.</small>
                            <strong id="estBaseFare">{{ number_format((float) ($pricingData['minimum_fare'] ?? 0), 0, ',', ' ') }} FCFA</strong>
                        </article>
                    </div>
                    <div class="taxi-price-band">
                        <div>
                            <small id="selectedRideMeta">Selectionnez une formule pour affiner la course.</small>
                        </div>
                        <strong id="estPrice">-- FCFA</strong>
                    </div>
                </div>

                <div class="taxi-side-section">
                    <span class="taxi-side-section__title">Choisissez votre formule</span>
                    <div class="taxi-option-grid" id="rideOptionGrid">
                        @foreach($rideOptions as $index => $option)
                            <button
                                type="button"
                                class="taxi-option-card{{ $index === 0 ? ' is-selected' : '' }}"
                                data-ride-option
                                data-option-key="{{ $option['key'] }}"
                                data-option-name="{{ $option['name'] }}"
                                data-option-multiplier="{{ $option['multiplier'] }}"
                            >
                                <span class="taxi-option-card__icon"><i class="{{ $option['icon'] }}"></i></span>
                                <span class="taxi-option-card__copy">
                                    <strong>{{ $option['name'] }}</strong>
                                    <small>{{ $option['description'] }}</small>
                                </span>
                                <span class="taxi-option-card__price" id="ridePrice{{ ucfirst($option['key']) }}">--</span>
                            </button>
                        @endforeach
                    </div>
                </div>

                <div class="taxi-side-section">
                    <span class="taxi-side-section__title">Moment du depart</span>
                    <div class="taxi-mode-grid">
                        <label class="taxi-mode-card">
                            <input type="radio" name="ride_timing" value="now" checked>
                            <span>
                                <strong>Maintenant</strong>
                                <small>Recherche de chauffeur des que vous confirmez.</small>
                            </span>
                        </label>
                        <label class="taxi-mode-card">
                            <input type="radio" name="ride_timing" value="later">
                            <span>
                                <strong>Programmer</strong>
                                <small>Choisissez l heure de prise en charge.</small>
                            </span>
                        </label>
                    </div>
                    <div class="taxi-field" style="margin-top:10px;">
                        <i class="far fa-calendar-alt taxi-field__icon" style="top:18px;"></i>
                        <input type="datetime-local" id="scheduledAtInput" disabled>
                    </div>
                </div>

                <div class="taxi-side-section">
                    <span class="taxi-side-section__title">Paiement et personnes</span>
                    <div class="taxi-side-grid">
                        <div>
                            <label class="taxi-mode-card">
                                <input type="radio" name="payment_method" value="cash" checked>
                                <span>
                                    <strong>Especes</strong>
                                    <small>Reglement a bord ou a l arrivee.</small>
                                </span>
                            </label>
                        </div>
                        <div>
                            <label class="taxi-mode-card">
                                <input type="radio" name="payment_method" value="momo">
                                <span>
                                    <strong>Mobile Money</strong>
                                    <small>La reservation sera creee puis le paiement pourra suivre.</small>
                                </span>
                            </label>
                        </div>
                    </div>
                    <div class="taxi-field" style="margin-top:10px;">
                        <label for="passengerCount" class="taxi-field__label">Nombre de passagers</label>
                        <i class="fas fa-user-friends taxi-field__icon" style="top:46px;"></i>
                        <select id="passengerCount">
                            <option value="1">1 passager</option>
                            <option value="2">2 passagers</option>
                            <option value="3">3 passagers</option>
                            <option value="4">4 passagers</option>
                            <option value="5">5 passagers</option>
                            <option value="6">6 passagers ou plus</option>
                        </select>
                    </div>
                    <p class="taxi-hint">Le type de course, le nombre de passagers et l horaire seront joins a la reservation pour aider l attribution chauffeur.</p>
                </div>

                <input type="hidden" id="p_lat">
                <input type="hidden" id="p_lng">
                <input type="hidden" id="d_lat">
                <input type="hidden" id="d_lng">
                <input type="hidden" id="estimatedDistance">
                <input type="hidden" id="estimatedDuration">
                <input type="hidden" id="estimatedPriceValue">
                <input type="hidden" id="selectedRideOption" value="eco">

                <button id="confirmBtn" class="taxi-confirm" disabled>Confirmer la course</button>

                @guest
                    <div class="taxi-login-note">
                        Vous devrez etre connecte pour finaliser la reservation taxi et suivre la course.
                    </div>
                @endguest

                <p class="taxi-legal">Le prix reste une estimation avant attribution. La course sera creee avec vos points GPS, vos reperes, votre formule choisie et le mode de paiement demande.</p>
            </aside>
        </div>
    </div>
</section>
@endsection

@section('scripts')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<script>
    let map, pickupMarker, dropoffMarker, routeLayer;
    let pickupSearchTimeout = null, dropoffSearchTimeout = null;
    let lastPickupQuery = '', lastDropoffQuery = '';
    let activePinTarget = 'pickup';
    let currentEstimate = { distance: 0, duration: 0, basePrice: 0, finalPrice: 0 };

    const MAPBOX_TOKEN = @json(mapbox_public_token());
    const DEFAULT_CITY = { lat: -4.2767, lng: 15.2832, label: 'Brazzaville, Republique du Congo' };
    const RIDE_OPTIONS = @json($rideOptions);
    const PRICING = @json($pricingData);
    const TRANSPORT_AUTHENTICATED = @json(auth()->check());
    const LOGIN_URL = @json(route('user.login', ['redirect' => url()->current()]));

    function initTaxiMap() {
        const mapBox = document.getElementById('taxiMap');
        if (!mapBox) return;

        bindRideOptions();
        bindTimingControls();
        bindPaymentCards();
        bindSummaryPrefill();

        if (!MAPBOX_TOKEN) {
            mapBox.innerHTML = '<div style="padding:2rem;color:#64748b;">Mapbox non configure. Ajoutez MAPBOX_PUBLIC_TOKEN pour activer la carte taxi.</div>';
            updateGeoState('error', 'Mapbox absent');
            return;
        }

        map = L.map('taxiMap', { zoomControl: true }).setView([DEFAULT_CITY.lat, DEFAULT_CITY.lng], 13);

        L.tileLayer('https://api.mapbox.com/styles/v1/mapbox/streets-v12/tiles/{z}/{x}/{y}?access_token=' + MAPBOX_TOKEN, {
            tileSize: 512,
            zoomOffset: -1,
            attribution: '&copy; OpenStreetMap contributors &copy; Mapbox',
            maxZoom: 19
        }).addTo(map);

        wireAddressSearch('pickup');
        wireAddressSearch('dropoff');
        wirePinHelpers();
        detectGeoPermission();
        requestCurrentPosition();

        const locateBtn = document.getElementById('locateMeBtn');
        if (locateBtn) {
            locateBtn.addEventListener('click', () => requestCurrentPosition(true));
        }

        map.on('click', async (event) => {
            const target = activePinTarget || 'pickup';
            const details = await reverseGeocode({ lat: event.latlng.lat, lng: event.latlng.lng });
            applySelectedAddress(target, details);
        });

        document.addEventListener('click', (event) => {
            if (!event.target.closest('.taxi-field')) {
                hideSuggestions('pickup');
                hideSuggestions('dropoff');
            }
        });
    }

    function bindRideOptions() {
        document.querySelectorAll('[data-ride-option]').forEach((button) => {
            button.addEventListener('click', () => {
                document.querySelectorAll('[data-ride-option]').forEach((item) => item.classList.remove('is-selected'));
                button.classList.add('is-selected');
                document.getElementById('selectedRideOption').value = button.dataset.optionKey;
                refreshEstimatePrice();
            });
        });
    }

    function bindTimingControls() {
        const timingInputs = document.querySelectorAll('input[name="ride_timing"]');
        const scheduledField = document.getElementById('scheduledAtInput');
        timingInputs.forEach((input) => {
            input.addEventListener('change', () => {
                const isLater = document.querySelector('input[name="ride_timing"]:checked')?.value === 'later';
                if (scheduledField) {
                    scheduledField.disabled = !isLater;
                    if (!isLater) {
                        scheduledField.value = '';
                    }
                }
            });
        });
    }

    function bindPaymentCards() {
        document.querySelectorAll('.taxi-mode-card input[name="payment_method"]').forEach((input) => {
            input.addEventListener('change', () => {
                document.querySelectorAll('.taxi-mode-card').forEach((card) => {
                    const radio = card.querySelector('input[name="payment_method"], input[name="ride_timing"]');
                    if (!radio) return;
                    card.style.borderColor = radio.checked ? 'rgba(255,90,31,0.35)' : 'rgba(15,23,42,0.08)';
                    card.style.background = radio.checked ? '#fff7ed' : '#ffffff';
                });
            });
        });

        document.querySelectorAll('.taxi-mode-card input[name="ride_timing"]').forEach((input) => {
            input.addEventListener('change', () => {
                document.querySelectorAll('.taxi-mode-card').forEach((card) => {
                    const radio = card.querySelector('input[name="payment_method"], input[name="ride_timing"]');
                    if (!radio) return;
                    card.style.borderColor = radio.checked ? 'rgba(255,90,31,0.35)' : 'rgba(15,23,42,0.08)';
                    card.style.background = radio.checked ? '#fff7ed' : '#ffffff';
                });
            });
        });

        document.querySelectorAll('.taxi-mode-card input').forEach((input) => input.dispatchEvent(new Event('change')));
    }

    function bindSummaryPrefill() {
        const pickupInput = document.getElementById('pickupInput');
        const dropoffInput = document.getElementById('dropoffInput');

        if (pickupInput) {
            pickupInput.addEventListener('input', () => {
                document.getElementById('summaryPickup').textContent = pickupInput.value.trim() || 'Non defini pour l instant';
            });
        }

        if (dropoffInput) {
            dropoffInput.addEventListener('input', () => {
                document.getElementById('summaryDropoff').textContent = dropoffInput.value.trim() || 'Ajoutez une destination';
            });
        }
    }

    function detectGeoPermission() {
        if (!navigator.permissions || !navigator.permissions.query) {
            updateGeoState('idle', 'GPS navigateur');
            return;
        }

        navigator.permissions.query({ name: 'geolocation' }).then((result) => {
            if (result.state === 'granted') {
                updateGeoState('success', 'GPS autorise');
            } else if (result.state === 'denied') {
                updateGeoState('error', 'GPS bloque');
            } else {
                updateGeoState('idle', 'GPS a autoriser');
            }

            result.onchange = () => detectGeoPermission();
        }).catch(() => updateGeoState('idle', 'GPS navigateur'));
    }

    function updateGeoState(state, label) {
        const chip = document.getElementById('geoState');
        if (!chip) return;
        chip.dataset.state = state;
        chip.innerHTML = `<i class="fas fa-crosshairs"></i> ${label}`;
    }

    function wireAddressSearch(type) {
        const input = document.getElementById(type === 'pickup' ? 'pickupInput' : 'dropoffInput');
        if (!input) return;

        input.addEventListener('focus', () => setActivePinTarget(type));

        input.addEventListener('input', () => {
            const query = input.value.trim();
            if (type === 'pickup') {
                lastPickupQuery = query;
                if (pickupSearchTimeout) clearTimeout(pickupSearchTimeout);
                if (query.length < 3) {
                    hideSuggestions(type);
                    return;
                }
                pickupSearchTimeout = setTimeout(() => searchAddressSuggestions(type, query), 250);
                return;
            }

            lastDropoffQuery = query;
            if (dropoffSearchTimeout) clearTimeout(dropoffSearchTimeout);
            if (query.length < 3) {
                hideSuggestions(type);
                return;
            }
            dropoffSearchTimeout = setTimeout(() => searchAddressSuggestions(type, query), 250);
        });

        input.addEventListener('keydown', async (event) => {
            if (event.key !== 'Enter') return;
            event.preventDefault();
            const query = input.value.trim();
            if (query.length < 3) return;
            const result = await geocodeAddress(query);
            if (result) {
                applySelectedAddress(type, result);
            }
        });

        input.addEventListener('focus', () => {
            const suggestions = document.getElementById(type + 'Suggestions');
            if (suggestions && suggestions.children.length) {
                suggestions.classList.add('is-visible');
            }
        });
    }

    function wirePinHelpers() {
        const pickupBtn = document.getElementById('setPickupPinBtn');
        const dropoffBtn = document.getElementById('setDropoffPinBtn');
        const clearRouteBtn = document.getElementById('clearRouteBtn');
        const centerRouteBtn = document.getElementById('centerRouteBtn');

        if (pickupBtn) pickupBtn.addEventListener('click', () => setActivePinTarget('pickup'));
        if (dropoffBtn) dropoffBtn.addEventListener('click', () => setActivePinTarget('dropoff'));

        if (centerRouteBtn) {
            centerRouteBtn.addEventListener('click', () => {
                if (!map) return;
                if (routeLayer) {
                    map.fitBounds(routeLayer.getBounds(), { padding: [30, 30] });
                    return;
                }
                if (pickupMarker) {
                    map.setView(pickupMarker.getLatLng(), 15);
                    return;
                }
                map.setView([DEFAULT_CITY.lat, DEFAULT_CITY.lng], 13);
            });
        }

        if (clearRouteBtn) {
            clearRouteBtn.addEventListener('click', () => {
                if (pickupMarker) map.removeLayer(pickupMarker);
                if (dropoffMarker) map.removeLayer(dropoffMarker);
                if (routeLayer) map.removeLayer(routeLayer);
                pickupMarker = null;
                dropoffMarker = null;
                routeLayer = null;
                ['pickupInput', 'dropoffInput', 'pickupNote', 'dropoffNote', 'p_lat', 'p_lng', 'd_lat', 'd_lng', 'estimatedDistance', 'estimatedDuration', 'estimatedPriceValue'].forEach((id) => {
                    const field = document.getElementById(id);
                    if (field) field.value = '';
                });
                updatePickupStatus('Depart a repositionner.');
                updateDropoffStatus('Arrivee a repositionner.');
                document.getElementById('estimateSection').classList.remove('is-visible');
                document.getElementById('confirmBtn').disabled = true;
                document.getElementById('summaryPickup').textContent = 'Non defini pour l instant';
                document.getElementById('summaryDropoff').textContent = 'Ajoutez une destination';
                document.getElementById('estDistance').textContent = '-- km';
                document.getElementById('estDuration').textContent = '-- min';
                document.getElementById('estPrice').textContent = '-- FCFA';
                map.setView([DEFAULT_CITY.lat, DEFAULT_CITY.lng], 13);
                setActivePinTarget('pickup');
            });
        }
    }

    function setActivePinTarget(type) {
        activePinTarget = type;
        const pickupBtn = document.getElementById('setPickupPinBtn');
        const dropoffBtn = document.getElementById('setDropoffPinBtn');
        if (pickupBtn) pickupBtn.classList.toggle('is-active', type === 'pickup');
        if (dropoffBtn) dropoffBtn.classList.toggle('is-active', type === 'dropoff');
    }

    async function searchAddressSuggestions(type, query) {
        const currentQuery = type === 'pickup' ? lastPickupQuery : lastDropoffQuery;
        if (query !== currentQuery) return;

        const suggestions = await geocodeAddressList(query);
        renderSuggestions(type, suggestions);
    }

    async function geocodeAddress(query) {
        const items = await geocodeAddressList(query, 1);
        return items[0] || null;
    }

    async function geocodeAddressList(query, limit = 5) {
        try {
            const proximity = getPickupCoordinates();
            let url = `https://api.mapbox.com/geocoding/v5/mapbox.places/${encodeURIComponent(query)}.json?access_token=${MAPBOX_TOKEN}&autocomplete=true&limit=${limit}&language=fr&country=cg&types=address,poi,neighborhood,locality,place`;
            if (proximity) {
                url += `&proximity=${proximity.lng},${proximity.lat}`;
            }
            const res = await fetch(url);
            const data = await res.json();
            if (!data.features) return [];
            return data.features.map((feature) => {
                const [lng, lat] = feature.center;
                return buildAddressDetails(lat, lng, feature);
            });
        } catch (e) {
            console.error('Geocoding Mapbox error:', e);
            return [];
        }
    }

    function renderSuggestions(type, suggestions) {
        const box = document.getElementById(type + 'Suggestions');
        if (!box) return;

        box.innerHTML = '';
        if (!suggestions.length) {
            box.classList.remove('is-visible');
            return;
        }

        suggestions.forEach((item) => {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'taxi-suggestion-item';
            button.textContent = item.label;
            button.addEventListener('click', () => applySelectedAddress(type, item));
            box.appendChild(button);
        });
        box.classList.add('is-visible');
    }

    function hideSuggestions(type) {
        const box = document.getElementById(type + 'Suggestions');
        if (!box) return;
        box.classList.remove('is-visible');
    }

    function applySelectedAddress(type, item) {
        const input = document.getElementById(type === 'pickup' ? 'pickupInput' : 'dropoffInput');
        if (input) {
            input.value = item.label || item.addressLine || '';
        }

        const noteField = document.getElementById(type === 'pickup' ? 'pickupNote' : 'dropoffNote');
        if (noteField && !noteField.value && (item.landmark || item.district)) {
            noteField.value = [item.landmark, item.district].filter(Boolean).join(' | ');
        }

        setMarker(type, item);
        hideSuggestions(type);

        if (type === 'pickup') {
            updatePickupStatus(item.district ? `Depart confirme: ${item.district}` : 'Position de depart definie.');
            document.getElementById('summaryPickup').textContent = input?.value || item.label || 'Depart defini';
            map.setView([item.lat, item.lng], 15);
            return;
        }

        updateDropoffStatus(item.district ? `Arrivee confirmee: ${item.district}` : 'Destination positionnee.');
        document.getElementById('summaryDropoff').textContent = input?.value || item.label || 'Destination definie';
    }

    function updatePickupStatus(message, isError = false) {
        const status = document.getElementById('pickupStatus');
        if (!status) return;
        status.textContent = message;
        status.style.color = isError ? '#b91c1c' : '#64748b';
    }

    function updateDropoffStatus(message, isError = false) {
        const status = document.getElementById('dropoffStatus');
        if (!status) return;
        status.textContent = message;
        status.style.color = isError ? '#b91c1c' : '#64748b';
    }

    function requestCurrentPosition(showErrors = false) {
        if (!navigator.geolocation) {
            updatePickupStatus('Geolocalisation non supportee sur cet appareil.', true);
            updateGeoState('error', 'GPS indisponible');
            return;
        }

        updatePickupStatus('Detection de votre position en cours...');
        updateGeoState('idle', 'Recherche GPS');

        navigator.geolocation.getCurrentPosition(async (pos) => {
            const myPos = { lat: pos.coords.latitude, lng: pos.coords.longitude };
            const details = await reverseGeocode(myPos);
            setMarker('pickup', details);
            map.setView([myPos.lat, myPos.lng], 15);
            const input = document.getElementById('pickupInput');
            if (input && details.label) input.value = details.label;
            document.getElementById('summaryPickup').textContent = details.label || 'Position detectee';
            updatePickupStatus(details.district ? `Position detectee: ${details.district}` : 'Position detectee automatiquement.');
            updateGeoState('success', 'GPS actif');
        }, () => {
            updatePickupStatus("Position non recuperee. Autorisez la localisation ou saisissez l'adresse.", true);
            updateGeoState('error', 'GPS refuse');
            if (showErrors) {
                alert("Impossible d'obtenir la position. Autorisez la localisation dans le navigateur.");
            }
        }, {
            enableHighAccuracy: true,
            timeout: 12000,
            maximumAge: 0
        });
    }

    async function reverseGeocode(pos) {
        try {
            const url = `https://api.mapbox.com/geocoding/v5/mapbox.places/${pos.lng},${pos.lat}.json?access_token=${MAPBOX_TOKEN}&limit=1&language=fr&types=address,poi,neighborhood,locality,place`;
            const res = await fetch(url);
            const data = await res.json();
            if (data.features && data.features[0]) return buildAddressDetails(pos.lat, pos.lng, data.features[0]);
        } catch (e) {
            console.error('Reverse geocode error:', e);
        }

        return {
            lat: pos.lat,
            lng: pos.lng,
            label: `${pos.lat.toFixed(6)}, ${pos.lng.toFixed(6)}`,
            district: 'Brazzaville',
            addressLine: `${pos.lat.toFixed(6)}, ${pos.lng.toFixed(6)}`,
            landmark: ''
        };
    }

    function buildAddressDetails(lat, lng, feature) {
        const district = extractContextText(feature, ['neighborhood', 'locality', 'place', 'district']) || 'Brazzaville';
        const landmark = extractContextText(feature, ['poi', 'address']) || '';
        return {
            lat,
            lng,
            label: feature.place_name || `${lat.toFixed(6)}, ${lng.toFixed(6)}`,
            district,
            addressLine: feature.text || feature.place_name || `${lat.toFixed(6)}, ${lng.toFixed(6)}`,
            landmark
        };
    }

    function extractContextText(feature, preferredTypes) {
        if (!feature) return '';
        const context = Array.isArray(feature.context) ? feature.context : [];
        for (const type of preferredTypes) {
            if ((feature.place_type || []).includes(type) && feature.text) {
                return feature.text;
            }
            const hit = context.find((entry) => (entry.id || '').startsWith(type + '.'));
            if (hit && hit.text) return hit.text;
        }
        return '';
    }

    function getPickupCoordinates() {
        const lat = parseFloat(document.getElementById('p_lat').value || '0');
        const lng = parseFloat(document.getElementById('p_lng').value || '0');
        if (!lat || !lng) return null;
        return { lat, lng };
    }

    function setMarker(type, pos) {
        if (type === 'pickup') {
            if (pickupMarker) map.removeLayer(pickupMarker);
            pickupMarker = L.marker([pos.lat, pos.lng]).addTo(map);
            document.getElementById('p_lat').value = pos.lat;
            document.getElementById('p_lng').value = pos.lng;
        } else {
            if (dropoffMarker) map.removeLayer(dropoffMarker);
            dropoffMarker = L.marker([pos.lat, pos.lng]).addTo(map);
            document.getElementById('d_lat').value = pos.lat;
            document.getElementById('d_lng').value = pos.lng;
        }

        if (pickupMarker && dropoffMarker) {
            calculateRoute();
        }
    }

    function haversineKm(lat1, lon1, lat2, lon2) {
        const R = 6371;
        const dLat = (lat2 - lat1) * Math.PI / 180;
        const dLon = (lon2 - lon1) * Math.PI / 180;
        const a = Math.sin(dLat / 2) * Math.sin(dLat / 2) +
            Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
            Math.sin(dLon / 2) * Math.sin(dLon / 2);
        const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
        return R * c;
    }

    async function calculateRoute() {
        const pLat = parseFloat(document.getElementById('p_lat').value || '0');
        const pLng = parseFloat(document.getElementById('p_lng').value || '0');
        const dLat = parseFloat(document.getElementById('d_lat').value || '0');
        const dLng = parseFloat(document.getElementById('d_lng').value || '0');
        if (!pLat || !pLng || !dLat || !dLng) return;

        const distance = haversineKm(pLat, pLng, dLat, dLng);
        const duration = Math.max(5, (distance / 30) * 60);

        try {
            const routeUrl = `https://api.mapbox.com/directions/v5/mapbox/driving/${pLng},${pLat};${dLng},${dLat}?geometries=geojson&overview=full&language=fr&access_token=${MAPBOX_TOKEN}`;
            const res = await fetch(routeUrl);
            const data = await res.json();
            if (data.routes && data.routes[0] && data.routes[0].geometry) {
                if (routeLayer) map.removeLayer(routeLayer);
                routeLayer = L.geoJSON(data.routes[0].geometry, {
                    style: { color: '#ff5a1f', weight: 5, opacity: 0.88 }
                }).addTo(map);
                map.fitBounds(routeLayer.getBounds(), { padding: [30, 30] });

                const distKm = (data.routes[0].distance || 0) / 1000;
                const durMin = (data.routes[0].duration || 0) / 60;
                await updateEstimates(distKm || distance, durMin || duration);
                return;
            }
        } catch (e) {
            console.warn('Mapbox directions fallback:', e);
        }

        const line = {
            type: 'Feature',
            geometry: {
                type: 'LineString',
                coordinates: [[pLng, pLat], [dLng, dLat]]
            }
        };
        if (routeLayer) map.removeLayer(routeLayer);
        routeLayer = L.geoJSON(line, { style: { color: '#ff5a1f', weight: 4, dashArray: '8 6' } }).addTo(map);
        map.fitBounds(routeLayer.getBounds(), { padding: [30, 30] });
        await updateEstimates(distance, duration);
    }

    async function updateEstimates(distance, duration) {
        try {
            const response = await fetch('{{ route('transport.xhr.estimate') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    type: 'taxi',
                    distance: distance,
                    duration: duration
                })
            });
            const data = await response.json();

            currentEstimate.distance = Number(distance) || 0;
            currentEstimate.duration = Number(duration) || 0;
            currentEstimate.basePrice = Number(data.estimated_price || 0);
            refreshEstimatePrice();
            document.getElementById('confirmBtn').disabled = false;
        } catch (error) {
            console.error('Estimation error:', error);
        }
    }

    function refreshEstimatePrice() {
        const selectedKey = document.getElementById('selectedRideOption').value || 'eco';
        const selectedOption = RIDE_OPTIONS.find((item) => item.key === selectedKey) || RIDE_OPTIONS[0];
        const multiplier = Number(selectedOption.multiplier || 1);
        const finalPrice = Math.max(Math.round(currentEstimate.basePrice * multiplier), Math.round(PRICING.minimum_fare || 0));
        currentEstimate.finalPrice = finalPrice;

        const estimateSection = document.getElementById('estimateSection');
        estimateSection.classList.add('is-visible');
        document.getElementById('estDistance').textContent = `${currentEstimate.distance.toFixed(1)} km`;
        document.getElementById('estDuration').textContent = `${Math.ceil(currentEstimate.duration)} min`;
        document.getElementById('estBaseFare').textContent = `${Number(PRICING.minimum_fare || 0).toLocaleString('fr-FR')} FCFA`;
        document.getElementById('estPrice').textContent = `${finalPrice.toLocaleString('fr-FR')} FCFA`;
        document.getElementById('selectedRideMeta').textContent = `${selectedOption.name} x${multiplier.toFixed(2)} · Base ${Number(currentEstimate.basePrice || 0).toLocaleString('fr-FR')} FCFA`;
        document.getElementById('estimatedDistance').value = currentEstimate.distance.toFixed(2);
        document.getElementById('estimatedDuration').value = Math.ceil(currentEstimate.duration);
        document.getElementById('estimatedPriceValue').value = finalPrice;

        RIDE_OPTIONS.forEach((option) => {
            const node = document.getElementById(`ridePrice${option.key.charAt(0).toUpperCase() + option.key.slice(1)}`);
            if (!node) return;
            const optionPrice = Math.max(Math.round(currentEstimate.basePrice * Number(option.multiplier || 1)), Math.round(PRICING.minimum_fare || 0));
            node.textContent = `${optionPrice.toLocaleString('fr-FR')} FCFA`;
        });
    }

    window.addEventListener('load', initTaxiMap, { once: true });

    document.getElementById('confirmBtn').addEventListener('click', async function() {
        const btn = this;

        if (!TRANSPORT_AUTHENTICATED) {
            window.location.href = LOGIN_URL;
            return;
        }

        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Creation de la course...';

        const pickupInput = document.getElementById('pickupInput').value.trim();
        const dropoffInput = document.getElementById('dropoffInput').value.trim();
        const pickupLat = document.getElementById('p_lat').value;
        const pickupLng = document.getElementById('p_lng').value;
        const dropoffLat = document.getElementById('d_lat').value;
        const dropoffLng = document.getElementById('d_lng').value;
        const pickupNote = document.getElementById('pickupNote').value.trim();
        const dropoffNote = document.getElementById('dropoffNote').value.trim();
        const timing = document.querySelector('input[name="ride_timing"]:checked')?.value || 'now';
        const scheduledAt = document.getElementById('scheduledAtInput').value;
        const passengerCount = document.getElementById('passengerCount').value;
        const rideOption = document.getElementById('selectedRideOption').value || 'eco';
        const paymentMethod = document.querySelector('input[name="payment_method"]:checked')?.value || 'cash';

        if (!pickupInput || !dropoffInput || !pickupLat || !pickupLng || !dropoffLat || !dropoffLng) {
            btn.disabled = false;
            btn.textContent = 'Confirmer la course';
            alert('Definissez le depart et la destination avec la recherche, le GPS ou les reperes sur la carte.');
            return;
        }

        if (timing === 'later' && !scheduledAt) {
            btn.disabled = false;
            btn.textContent = 'Confirmer la course';
            alert('Choisissez une date et une heure si vous programmez la course.');
            return;
        }

        const payload = {
            type: 'taxi',
            pickup_address: pickupNote ? `${pickupInput} | Repere: ${pickupNote}` : pickupInput,
            pickup_lat: pickupLat,
            pickup_lng: pickupLng,
            dropoff_address: dropoffNote ? `${dropoffInput} | Repere: ${dropoffNote}` : dropoffInput,
            dropoff_lat: dropoffLat,
            dropoff_lng: dropoffLng,
            estimated_distance: document.getElementById('estimatedDistance').value || null,
            estimated_duration: document.getElementById('estimatedDuration').value || null,
            estimated_price: document.getElementById('estimatedPriceValue').value || null,
            total_price: document.getElementById('estimatedPriceValue').value || null,
            scheduled_at: timing === 'later' ? scheduledAt : null,
            notes: JSON.stringify({
                pickup_note: pickupNote,
                dropoff_note: dropoffNote,
                ride_option: rideOption,
                passenger_count: passengerCount,
                timing: timing
            }),
            payment_method: paymentMethod
        };

        try {
            const response = await fetch('{{ route('transport.xhr.bookings.store') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify(payload)
            });

            const data = await response.json();

            if (response.ok && data.booking) {
                window.location.href = `/transport/booking/${data.booking.uuid}`;
                return;
            }

            const message = data.message || data.error?.message || 'Erreur lors de la reservation. Veuillez reessayer.';
            alert(message);
        } catch (error) {
            console.error('Booking error:', error);
            alert('Erreur reseau lors de la reservation. Veuillez reessayer.');
        }

        btn.disabled = false;
        btn.textContent = 'Confirmer la course';
    });
</script>
@endsection
