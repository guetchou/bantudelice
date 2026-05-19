@extends('frontend.layouts.colis')
@section('title', 'Nouvel envoi | Mema')
@section('description', 'Expédiez facilement un colis avec Mema. Livraison rapide et sécurisée au Congo.')

@section('style')
<style>
    .colis-create-shell { padding: 48px 0 72px; }
    .colis-create-hero {
        background: linear-gradient(135deg, #0f172a 0%, #2448ff 52%, #3b82f6 100%);
        color: #fff;
        border-radius: 32px;
        padding: 40px;
        margin-bottom: 28px;
        overflow: hidden;
        position: relative;
        box-shadow: 0 28px 70px rgba(15, 23, 42, 0.18);
    }
    .colis-create-hero::after {
        content: "";
        position: absolute;
        inset: auto -80px -120px auto;
        width: 320px;
        height: 320px;
        border-radius: 999px;
        background: radial-gradient(circle, rgba(255,255,255,.2), transparent 68%);
        pointer-events: none;
    }
    .colis-create-eyebrow {
        display: inline-flex;
        align-items: center;
        gap: 10px;
        min-height: 34px;
        padding: 0 14px;
        border-radius: 999px;
        background: rgba(255,255,255,.12);
        border: 1px solid rgba(255,255,255,.14);
        font-size: .78rem;
        font-weight: 800;
        letter-spacing: .08em;
        text-transform: uppercase;
    }
    .colis-create-title {
        margin: 18px 0 12px;
        font-size: clamp(2rem, 4vw, 3.5rem);
        line-height: 1.02;
        font-weight: 800;
    }
    .colis-create-subtitle {
        max-width: 760px;
        color: rgba(255,255,255,.82);
        font-size: 1.02rem;
        line-height: 1.75;
        margin: 0;
    }
    .colis-create-hero-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 14px;
        margin-top: 24px;
    }
    .colis-create-hero-card {
        background: rgba(255,255,255,.12);
        border: 1px solid rgba(255,255,255,.14);
        border-radius: 22px;
        padding: 18px 20px;
    }
    .colis-create-hero-card strong {
        display: block;
        font-size: .78rem;
        letter-spacing: .08em;
        text-transform: uppercase;
        opacity: .8;
        margin-bottom: 8px;
    }
    .colis-create-hero-card span {
        display: block;
        font-size: 1rem;
        font-weight: 700;
        line-height: 1.6;
    }
    .shipment-form-container {
        background: #fff;
        padding: 34px;
        border-radius: 28px;
        border: 1px solid rgba(15,23,42,.07);
        box-shadow: 0 24px 60px rgba(15, 23, 42, 0.08);
        margin-bottom: 50px;
    }
    .step-section + .step-section { margin-top: 1.75rem; }
    .step-header {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 20px;
        padding-bottom: 14px;
        border-bottom: 1px solid #e5e7eb;
        color: #2448ff;
        font-size: 1.05rem;
        font-weight: 800;
    }
    .price-summary {
        background: #ffffff;
        padding: 24px;
        border-radius: 28px;
        border: 1px solid rgba(15,23,42,.07);
        box-shadow: 0 24px 60px rgba(15, 23, 42, 0.08);
    }
    .price-value { font-size: 24px; font-weight: bold; color: #009543; }
    .shipment-map-panel { border: 1px solid #E5E7EB; border-radius: 18px; padding: 1rem; background: #FCFCFD; }
    .shipment-map-targets { display: flex; gap: 0.75rem; flex-wrap: wrap; margin-bottom: 1rem; }
    .shipment-map-target-btn { border: 1px solid #D1D5DB; background: white; color: #1F2937; border-radius: 999px; padding: 0.55rem 0.9rem; font-weight: 600; cursor: pointer; }
    .shipment-map-target-btn.is-active { background: #0F172A; color: white; border-color: #0F172A; }
    .shipment-map-search-wrap { position: relative; margin-bottom: 1rem; }
    .shipment-map-suggestions { position: absolute; top: calc(100% + 6px); left: 0; right: 0; background: white; border: 1px solid #E5E7EB; border-radius: 14px; box-shadow: 0 12px 30px rgba(15, 23, 42, 0.12); max-height: 220px; overflow-y: auto; display: none; z-index: 20; }
    .shipment-map-suggestions.is-visible { display: block; }
    .shipment-map-suggestion { width: 100%; border: none; background: transparent; text-align: left; padding: 0.85rem 1rem; border-bottom: 1px solid #F3F4F6; color: #111827; }
    .shipment-map-suggestion:last-child { border-bottom: none; }
    .shipment-map-suggestion:hover { background: #FFF7ED; }
    .shipment-map-status { font-size: 0.85rem; color: #6B7280; margin-top: 0.5rem; }
    .shipment-precision-alert { display: none; margin-top: 0.75rem; padding: 0.85rem 1rem; border-radius: 14px; font-size: 0.85rem; font-weight: 600; line-height: 1.55; }
    .shipment-precision-alert.is-visible { display: block; }
    .shipment-precision-alert--warn { background: #FFF7ED; border: 1px solid #FDBA74; color: #9A3412; }
    .shipment-precision-alert--ok { background: #F0FDF4; border: 1px solid #86EFAC; color: #166534; }
    .shipment-map-meta { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 0.75rem; margin-top: 1rem; }
    .colis-create-panel-title {
        font-size: 1.35rem;
        font-weight: 800;
        color: #0f172a;
        margin-bottom: 8px;
    }
    .colis-create-panel-copy {
        color: #64748b;
        line-height: 1.7;
        margin-bottom: 24px;
    }
    .colis-create-submit {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: #2448ff;
        color: #fff;
        font-weight: 800;
        font-size: 1rem;
        padding: 1rem 1.6rem;
        border-radius: 16px;
        border: none;
        cursor: pointer;
        box-shadow: 0 18px 36px rgba(36, 72, 255, 0.18);
    }
    .colis-create-note {
        margin-top: 18px;
        padding: 16px 18px;
        border-radius: 18px;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        color: #475569;
        font-size: .92rem;
        line-height: 1.7;
    }
    .colis-price-kicker {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        min-height: 34px;
        padding: 0 12px;
        border-radius: 999px;
        background: #eef2ff;
        color: #2448ff;
        font-weight: 800;
        font-size: .78rem;
        letter-spacing: .08em;
        text-transform: uppercase;
        margin-bottom: 12px;
    }
    .colis-price-list .d-flex {
        padding: 10px 0;
        border-bottom: 1px dashed #e2e8f0;
    }
    .colis-price-list .d-flex:last-of-type { border-bottom: 0; }
    @media (max-width: 768px) {
        .shipment-map-meta { grid-template-columns: 1fr; }
        .colis-create-shell { padding: 28px 0 52px; }
        .colis-create-hero { padding: 26px 22px; border-radius: 24px; }
        .colis-create-hero-grid { grid-template-columns: 1fr; }
        .shipment-form-container,
        .price-summary { padding: 22px; border-radius: 22px; }
    }
</style>
@endsection

@section('styles')
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Poppins:wght@700;800;900&display=swap" rel="stylesheet">
@endsection

@section('content')
<div class="container colis-create-shell">
    <section class="colis-create-hero">
        <div class="colis-create-eyebrow">
            <i class="fas fa-box-open"></i>
            Nouvel envoi colis
        </div>
        <h1 class="colis-create-title">Préparez un envoi clair,<br>traçable et prêt au départ.</h1>
        <p class="colis-create-subtitle">
            Définissez le service, l’origine, la destination et les repères cartographiques avant de confirmer l’envoi.
        </p>
        <div class="colis-create-hero-grid">
            <div class="colis-create-hero-card">
                <strong>Étape 1</strong>
                <span>Décrivez le colis, le niveau de service et les options COD ou assurance.</span>
            </div>
            <div class="colis-create-hero-card">
                <strong>Étape 2</strong>
                <span>Renseignez des coordonnées précises pour le ramassage et la livraison.</span>
            </div>
            <div class="colis-create-hero-card">
                <strong>Étape 3</strong>
                <span>Placez les deux repères sur la carte pour fiabiliser l’intervention du coursier.</span>
            </div>
        </div>
    </section>

    <div class="row g-4">
        <div class="col-md-8">
            <div class="shipment-form-container shadow-sm">
                <h2 class="colis-create-panel-title">Créer un nouvel envoi</h2>
                <p class="colis-create-panel-copy">
                    Préparez ici votre expédition avec des points de ramassage et de livraison suffisamment précis pour une prise en charge fluide.
                </p>

                <form id="createShipmentForm" action="{{ route('colis.shipments.store') }}" method="POST">
                    @csrf

                    <div class="step-section">
                        <h4 class="step-header"><i class="fa fa-cube"></i> 1. Détails du colis</h4>
                        <div class="row">
                            <div class="col-md-6 form-group">
                                <label>Poids estimé (kg)</label>
                                <input type="number" name="weight_kg" id="weight_kg" class="form-control" step="0.1" min="0.1" value="1.0" required>
                            </div>
                            <div class="col-md-6 form-group">
                                <label>Niveau de service</label>
                                <select name="service_level" id="service_level" class="form-control" required>
                                    <option value="standard">Standard (48h-72h)</option>
                                    <option value="express">Express (24h)</option>
                                </select>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 form-group">
                                <label>Valeur déclarée (XAF)</label>
                                <input type="number" name="declared_value" id="declared_value" class="form-control" min="0" placeholder="Ex: 5000">
                                <small class="text-muted">Pour l'assurance (optionnel)</small>
                            </div>
                            <div class="col-md-6 form-group">
                                <label>Montant à collecter (COD)</label>
                                <input type="number" name="cod_amount" id="cod_amount" class="form-control" min="0" placeholder="Ex: 15000">
                                <small class="text-muted">Paiement à la livraison (optionnel)</small>
                            </div>
                        </div>
                    </div>

                    <div class="step-section mt-4">
                        <h4 class="step-header"><i class="fa fa-map-marker"></i> 2. Adresse de ramassage (Origine)</h4>
                        <div class="row">
                            <div class="col-md-6 form-group">
                                <label>Nom complet</label>
                                <input type="text" name="pickup_address[full_name]" class="form-control" required value="{{ auth()->user()->name }}">
                            </div>
                            <div class="col-md-6 form-group">
                                <label>Téléphone</label>
                                <input type="text" name="pickup_address[phone]" class="form-control" required value="{{ auth()->user()->phone }}">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4 form-group">
                                <label>Ville</label>
                                <select name="pickup_address[city]" class="form-control" required>
                                    <option value="Brazzaville">Brazzaville</option>
                                    <option value="Pointe-Noire">Pointe-Noire</option>
                                </select>
                            </div>
                            <div class="col-md-4 form-group">
                                <label>Quartier</label>
                                <input type="text" id="pickupDistrict" name="pickup_address[district]" class="form-control" required placeholder="Ex: Poto-Poto">
                            </div>
                            <div class="col-md-4 form-group">
                                <label>Rue / Avenue</label>
                                <input type="text" id="pickupAddressLine" name="pickup_address[address_line]" class="form-control" required placeholder="Ex: Rue de la Lékéti">
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Point de repère / complément</label>
                            <input type="text" id="pickupLandmark" name="pickup_address[landmark]" class="form-control" placeholder="Ex: En face de la pharmacie X, portail bleu, 2e étage">
                        </div>
                        <input type="hidden" id="pickupLat" name="pickup_address[lat]">
                        <input type="hidden" id="pickupLng" name="pickup_address[lng]">
                        <input type="hidden" id="pickupAddressConfirmed" name="pickup_address_confirmed" value="0">
                    </div>

                    <div class="step-section mt-4">
                        <h4 class="step-header"><i class="fa fa-truck"></i> 3. Adresse de livraison (Destination)</h4>
                        <div class="row">
                            <div class="col-md-6 form-group">
                                <label>Nom complet du destinataire</label>
                                <input type="text" name="dropoff_address[full_name]" class="form-control" required>
                            </div>
                            <div class="col-md-6 form-group">
                                <label>Téléphone du destinataire</label>
                                <input type="text" name="dropoff_address[phone]" class="form-control" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4 form-group">
                                <label>Ville</label>
                                <select name="dropoff_address[city]" class="form-control" required>
                                    <option value="Brazzaville">Brazzaville</option>
                                    <option value="Pointe-Noire">Pointe-Noire</option>
                                </select>
                            </div>
                            <div class="col-md-4 form-group">
                                <label>Quartier</label>
                                <input type="text" id="dropoffDistrict" name="dropoff_address[district]" class="form-control" required placeholder="Ex: Bacongo">
                            </div>
                            <div class="col-md-4 form-group">
                                <label>Rue / Avenue</label>
                                <input type="text" id="dropoffAddressLine" name="dropoff_address[address_line]" class="form-control" required placeholder="Ex: Avenue Matsoua">
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Point de repère / complément</label>
                            <input type="text" id="dropoffLandmark" name="dropoff_address[landmark]" class="form-control" placeholder="Ex: Derrière l'arrêt de bus, immeuble jaune">
                        </div>
                        <input type="hidden" id="dropoffLat" name="dropoff_address[lat]">
                        <input type="hidden" id="dropoffLng" name="dropoff_address[lng]">
                        <input type="hidden" id="dropoffAddressConfirmed" name="dropoff_address_confirmed" value="0">
                    </div>

                    <div class="step-section mt-4">
                        <h4 class="step-header"><i class="fa fa-map"></i> 4. Placer le repère sur la carte</h4>
                        <div class="shipment-map-panel">
                            <div class="shipment-map-targets">
                                <button type="button" class="shipment-map-target-btn is-active" data-target="pickup">Repère de ramassage</button>
                                <button type="button" class="shipment-map-target-btn" data-target="dropoff">Repère de livraison</button>
                                <button type="button" style="display:inline-flex;align-items:center;background:#fff;color:#64748b;font-weight:600;font-size:.82rem;padding:.4rem .85rem;border-radius:999px;border:1.5px solid #cbd5e1;cursor:pointer;" id="shipmentLocateMeBtn">Utiliser ma position</button>
                            </div>
                            <div class="shipment-map-search-wrap">
                                <input type="text" id="shipmentMapSearch" class="form-control" placeholder="Rechercher une rue, un quartier ou un lieu connu">
                                <div id="shipmentMapSuggestions" class="shipment-map-suggestions"></div>
                            </div>
                            <div id="shipmentMap" style="height: 340px; border-radius: 16px; overflow: hidden;"></div>
                            <div id="shipmentMapStatus" class="shipment-map-status">Cliquez sur la carte pour placer le repère du point actif. Si l'adresse exacte n'est pas trouvée, complétez avec le quartier et le repère.</div>
                            <div id="shipmentPrecisionAlert" class="shipment-precision-alert" aria-live="polite"></div>
                            <div class="shipment-map-meta">
                                <div class="alert alert-light border mb-0">
                                    <strong>Ramassage</strong><br>
                                    <span id="pickupMapSummary" class="text-muted">Aucun repère défini</span>
                                </div>
                                <div class="alert alert-light border mb-0">
                                    <strong>Livraison</strong><br>
                                    <span id="dropoffMapSummary" class="text-muted">Aucun repère défini</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <hr>
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                        <button type="submit" class="colis-create-submit">Confirmer et créer l'envoi</button>
                    </div>
                    <div class="colis-create-note">
                        Le devis affiché reste estimatif jusqu’à validation finale des points de ramassage et de livraison. Plus vos repères sont précis, plus la prise en charge sera fluide.
                    </div>
                </form>
            </div>
        </div>

        <div class="col-md-4">
            <div class="price-summary shadow-sm sticky-top" style="top: 120px;">
                <div class="colis-price-kicker">
                    <i class="fas fa-receipt"></i>
                    Résumé du devis
                </div>
                <h4 class="colis-create-panel-title" style="font-size:1.2rem; margin-bottom:6px;">Tarification estimée</h4>
                <p class="colis-create-panel-copy" style="font-size:.92rem; margin-bottom:18px;">
                    Le module colis calcule ici le tarif de base, les majorations éventuelles et le total estimé avant confirmation.
                </p>
                <hr>
                <div id="quoteResult" class="colis-price-list">
                    <div class="d-flex justify-content-between mb-2">
                        <span>Tarif de base :</span>
                        <span id="base_price">-- FCFA</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2 text-info" id="express_row" style="display:none !important;">
                        <span>Surcharge Express :</span>
                        <span id="express_fee">0 FCFA</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2 text-warning" id="cod_row" style="display:none !important;">
                        <span>Frais COD :</span>
                        <span id="cod_fee">0 FCFA</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2 text-primary" id="insurance_row" style="display:none !important;">
                        <span>Assurance :</span>
                        <span id="insurance_fee">0 FCFA</span>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="h5">TOTAL ESTIMÉ :</span>
                        <span id="total_price" class="price-value text-success">-- FCFA</span>
                    </div>
                </div>
                <div class="text-center mt-3">
                    <small class="text-muted"><i class="fa fa-info-circle"></i> Le prix final sera confirmé lors du ramassage.</small>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('script')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<script>
$(document).ready(function() {
    const MAPBOX_TOKEN = @json(mapbox_public_token());
    const DEFAULT_CENTER = { lat: -4.2767, lng: 15.2832 };
    let shipmentMap = null;
    let activeTarget = 'pickup';
    let pickupMarker = null;
    let dropoffMarker = null;
    let searchTimeout = null;
    const shipmentAddressState = {
        pickup: { precisionLevel: 'blind', confirmed: false, city: 'Brazzaville', department: 'Brazzaville', source: 'manual' },
        dropoff: { precisionLevel: 'blind', confirmed: false, city: 'Brazzaville', department: 'Brazzaville', source: 'manual' },
    };

    function calculateQuote() {
        const data = {
            weight_kg: $('#weight_kg').val(),
            service_level: $('#service_level').val(),
            declared_value: $('#declared_value').val() || 0,
            cod_amount: $('#cod_amount').val() || 0
        };

        if (data.weight_kg <= 0) return;

        $.ajax({
            url: '/api/v1/colis/quotes',
            method: 'POST',
            data: JSON.stringify(data),
            contentType: 'application/json',
            success: function(response) {
                const breakdown = response.price_breakdown;
                $('#base_price').text(breakdown.base_price.toLocaleString() + ' FCFA');
                $('#express_row').attr('style', breakdown.express_surcharge ? 'display: flex !important' : 'display: none !important');
                $('#express_fee').text((breakdown.express_surcharge || 0).toLocaleString() + ' FCFA');
                $('#cod_row').attr('style', breakdown.cod_fee ? 'display: flex !important' : 'display: none !important');
                $('#cod_fee').text((breakdown.cod_fee || 0).toLocaleString() + ' FCFA');
                $('#insurance_row').attr('style', breakdown.insurance_fee ? 'display: flex !important' : 'display: none !important');
                $('#insurance_fee').text((breakdown.insurance_fee || 0).toLocaleString() + ' FCFA');
                $('#total_price').text(response.total_price.toLocaleString() + ' FCFA');
            }
        });
    }

    function isShipmentPrecisionTooBroad(level) {
        return ['district', 'area', 'blind'].includes(String(level || 'blind'));
    }

    function inferShipmentDepartment(city) {
        const normalized = String(city || '').trim().toLowerCase();
        if (normalized.includes('pointe')) return 'Pointe-Noire';
        if (normalized.includes('brazzaville')) return 'Brazzaville';
        return city || 'Brazzaville';
    }

    function parseFeature(feature) {
        const context = feature.context || [];
        const districtMatch = context.find(item => item.id && (item.id.startsWith('neighborhood') || item.id.startsWith('locality') || item.id.startsWith('district')));
        const cityMatch = context.find(item => item.id && item.id.startsWith('place'));
        const departmentMatch = context.find(item => item.id && item.id.startsWith('region'));
        const district = districtMatch ? districtMatch.text : (feature.text || '');
        const landmark = (feature.place_type || []).includes('poi') ? feature.text : '';
        const center = feature.center || [null, null];
        const placeTypes = feature.place_type || [];
        let precisionLevel = 'blind';
        if (placeTypes.includes('poi') || feature.address) {
            precisionLevel = 'exact';
        } else if (placeTypes.includes('address')) {
            precisionLevel = 'street';
        } else if (placeTypes.includes('neighborhood') || placeTypes.includes('locality') || placeTypes.includes('district')) {
            precisionLevel = 'district';
        } else if (placeTypes.includes('place') || placeTypes.includes('region')) {
            precisionLevel = 'area';
        }
        return {
            lng: center[0],
            lat: center[1],
            label: feature.place_name || feature.text || '',
            district: district,
            city: cityMatch ? cityMatch.text : 'Brazzaville',
            department: departmentMatch ? departmentMatch.text : inferShipmentDepartment(cityMatch ? cityMatch.text : 'Brazzaville'),
            precisionLevel: precisionLevel,
            addressLine: feature.address ? `${feature.text}, ${feature.address}` : (feature.text || feature.place_name || ''),
            landmark: landmark
        };
    }

    async function searchPlaces(query) {
        if (!MAPBOX_TOKEN || !query || query.length < 3) return [];
        try {
            const url = `https://api.mapbox.com/geocoding/v5/mapbox.places/${encodeURIComponent(query)}.json?access_token=${MAPBOX_TOKEN}&autocomplete=true&limit=6&language=fr&country=cg&types=address,poi,neighborhood,locality,place`;
            const res = await fetch(url);
            const data = await res.json().catch(() => ({}));
            if (!res.ok) return [];
            return (data.features || []).map(parseFeature);
        } catch (e) {
            console.error('Shipment geocoding error:', e);
            return [];
        }
    }

    async function reverseGeocode(lat, lng) {
        if (!MAPBOX_TOKEN) return null;
        try {
            const url = `https://api.mapbox.com/geocoding/v5/mapbox.places/${lng},${lat}.json?access_token=${MAPBOX_TOKEN}&limit=1&language=fr&types=address,poi,neighborhood,locality,place`;
            const res = await fetch(url);
            const data = await res.json().catch(() => ({}));
            if (res.ok && data.features && data.features[0]) return parseFeature(data.features[0]);
        } catch (e) {
            console.error('Shipment reverse geocoding error:', e);
        }
        return {
            lat,
            lng,
            label: `${lat.toFixed(6)}, ${lng.toFixed(6)}`,
            district: '',
            city: 'Brazzaville',
            department: 'Brazzaville',
            precisionLevel: 'area',
            addressLine: `${lat.toFixed(6)}, ${lng.toFixed(6)}`,
            landmark: ''
        };
    }

    function updateShipmentPrecisionUi(target, forceMessage = '', isError = false) {
        const state = shipmentAddressState[target];
        const alertBox = $('#shipmentPrecisionAlert');
        const label = target === 'pickup' ? 'ramassage' : 'livraison';
        const needsConfirmation = isShipmentPrecisionTooBroad(state.precisionLevel) && !state.confirmed;
        const message = forceMessage || (needsConfirmation
            ? `Point de ${label} encore trop large. Placez le repère exact sur la carte avant de confirmer.`
            : '');

        if (!message) {
            alertBox.removeClass('is-visible shipment-precision-alert--warn shipment-precision-alert--ok').text('');
            return;
        }

        alertBox
            .removeClass('shipment-precision-alert--warn shipment-precision-alert--ok')
            .addClass(`is-visible ${isError || needsConfirmation ? 'shipment-precision-alert--warn' : 'shipment-precision-alert--ok'}`)
            .text(message);
    }

    function setActiveTarget(target) {
        activeTarget = target;
        $('.shipment-map-target-btn[data-target]').removeClass('is-active');
        $(`.shipment-map-target-btn[data-target="${target}"]`).addClass('is-active');
        $('#shipmentMapStatus').text(target === 'pickup'
            ? 'Repère actif : ramassage. Cliquez sur la carte ou recherchez un lieu pour positionner l’origine.'
            : 'Repère actif : livraison. Cliquez sur la carte ou recherchez un lieu pour positionner la destination.');
        updateShipmentPrecisionUi(target);
    }

    function updateSummary(target, feature) {
        const selector = target === 'pickup' ? '#pickupMapSummary' : '#dropoffMapSummary';
        $(selector).text(feature && feature.label ? feature.label : 'Aucun repère défini');
    }

    function setMarker(target, feature, options = {}) {
        if (!shipmentMap || !feature || !feature.lat || !feature.lng) return;
        const latLng = [parseFloat(feature.lat), parseFloat(feature.lng)];
        let marker = target === 'pickup' ? pickupMarker : dropoffMarker;
        if (marker) {
            marker.setLatLng(latLng);
        } else {
            marker = L.marker(latLng).addTo(shipmentMap);
            if (target === 'pickup') pickupMarker = marker; else dropoffMarker = marker;
        }

        if (target === 'pickup') {
            $('#pickupLat').val(feature.lat);
            $('#pickupLng').val(feature.lng);
            $('#pickupAddressConfirmed').val('0');
            $('#pickupDistrict').val(feature.district || $('#pickupDistrict').val());
            $('#pickupAddressLine').val(feature.addressLine || $('#pickupAddressLine').val());
            $('select[name="pickup_address[city]"]').val(feature.city || $('select[name="pickup_address[city]"]').val());
            if (feature.landmark && !$('#pickupLandmark').val()) $('#pickupLandmark').val(feature.landmark);
        } else {
            $('#dropoffLat').val(feature.lat);
            $('#dropoffLng').val(feature.lng);
            $('#dropoffAddressConfirmed').val('0');
            $('#dropoffDistrict').val(feature.district || $('#dropoffDistrict').val());
            $('#dropoffAddressLine').val(feature.addressLine || $('#dropoffAddressLine').val());
            $('select[name="dropoff_address[city]"]').val(feature.city || $('select[name="dropoff_address[city]"]').val());
            if (feature.landmark && !$('#dropoffLandmark').val()) $('#dropoffLandmark').val(feature.landmark);
        }

        const precisionLevel = feature.precisionLevel || 'blind';
        const confirmed = options.confirmed === true
            ? true
            : (options.confirmed === false ? false : !isShipmentPrecisionTooBroad(precisionLevel));
        shipmentAddressState[target] = {
            precisionLevel,
            confirmed,
            city: feature.city || shipmentAddressState[target].city,
            department: feature.department || shipmentAddressState[target].department,
            source: options.source || 'search',
        };
        $(`#${target}AddressConfirmed`).val(confirmed ? '1' : '0');

        updateSummary(target, feature);
        shipmentMap.setView(latLng, 15);
        const points = [];
        if (pickupMarker) points.push(pickupMarker.getLatLng());
        if (dropoffMarker) points.push(dropoffMarker.getLatLng());
        if (points.length > 1) shipmentMap.fitBounds(L.latLngBounds(points), { padding: [30, 30] });

        if (isShipmentPrecisionTooBroad(precisionLevel) && !confirmed) {
            $('#shipmentMapStatus').text(`Point de ${target === 'pickup' ? 'ramassage' : 'livraison'} trouvé au niveau quartier. Placez maintenant le repère exact sur la carte.`);
        } else {
            $('#shipmentMapStatus').text(`Repère ${target === 'pickup' ? 'de ramassage' : 'de livraison'} confirmé.`);
        }
        updateShipmentPrecisionUi(target);
    }

    function initShipmentMap() {
        const mapBox = document.getElementById('shipmentMap');
        if (!mapBox) return;
        if (!MAPBOX_TOKEN) {
            mapBox.innerHTML = '<div style="padding:1rem;color:#6B7280;">Mapbox non configuré. Ajoutez MAPBOX_PUBLIC_TOKEN pour activer le placement cartographique.</div>';
            return;
        }

        shipmentMap = L.map('shipmentMap', { zoomControl: true }).setView([DEFAULT_CENTER.lat, DEFAULT_CENTER.lng], 13);
        L.tileLayer('https://api.mapbox.com/styles/v1/mapbox/streets-v12/tiles/{z}/{x}/{y}?access_token=' + MAPBOX_TOKEN, {
            tileSize: 512,
            zoomOffset: -1,
            attribution: '&copy; OpenStreetMap contributors &copy; Mapbox',
            maxZoom: 19
        }).addTo(shipmentMap);

        shipmentMap.on('click', async function(event) {
            const feature = await reverseGeocode(event.latlng.lat, event.latlng.lng);
            if (feature) setMarker(activeTarget, feature, { confirmed: true, source: 'map' });
        });
    }

    $('.shipment-map-target-btn[data-target]').on('click', function() {
        setActiveTarget($(this).data('target'));
    });

    $('#shipmentLocateMeBtn').on('click', function() {
        if (!navigator.geolocation) {
            $('#shipmentMapStatus').text('La géolocalisation n’est pas disponible sur cet appareil.');
            return;
        }

        navigator.geolocation.getCurrentPosition(async function(position) {
            const feature = await reverseGeocode(position.coords.latitude, position.coords.longitude);
            if (feature) setMarker(activeTarget, feature, { confirmed: true, source: 'gps' });
        }, function() {
            $('#shipmentMapStatus').text('Impossible de récupérer votre position. Autorisez la localisation ou placez le repère manuellement.');
        }, { enableHighAccuracy: true, timeout: 12000, maximumAge: 0 });
    });

    $('#shipmentMapSearch').on('input', function() {
        const query = $(this).val().trim();
        clearTimeout(searchTimeout);
        if (query.length < 3) {
            $('#shipmentMapSuggestions').removeClass('is-visible').empty();
            return;
        }
        searchTimeout = setTimeout(async function() {
            const results = await searchPlaces(query);
            const box = $('#shipmentMapSuggestions');
            box.empty();
            if (!results.length) {
                box.removeClass('is-visible');
                return;
            }
            results.forEach(function(item) {
                const btn = $('<button type="button" class="shipment-map-suggestion"></button>').text(item.label);
                btn.on('click', function() {
                    setMarker(activeTarget, item, {
                        confirmed: !isShipmentPrecisionTooBroad(item.precisionLevel),
                        source: 'search',
                    });
                    box.removeClass('is-visible').empty();
                });
                box.append(btn);
            });
            box.addClass('is-visible');
        }, 250);
    });

    $(document).on('click', function(event) {
        if (!$(event.target).closest('.shipment-map-search-wrap').length) {
            $('#shipmentMapSuggestions').removeClass('is-visible');
        }
    });

    calculateQuote();
    $('#weight_kg, #service_level, #declared_value, #cod_amount').on('change keyup', calculateQuote);
    $('#pickupDistrict, #pickupAddressLine').on('input', function() {
        shipmentAddressState.pickup.confirmed = false;
        shipmentAddressState.pickup.precisionLevel = 'district';
        $('#pickupAddressConfirmed').val('0');
        if (activeTarget === 'pickup') {
            updateShipmentPrecisionUi('pickup');
        }
    });
    $('#dropoffDistrict, #dropoffAddressLine').on('input', function() {
        shipmentAddressState.dropoff.confirmed = false;
        shipmentAddressState.dropoff.precisionLevel = 'district';
        $('#dropoffAddressConfirmed').val('0');
        if (activeTarget === 'dropoff') {
            updateShipmentPrecisionUi('dropoff');
        }
    });
    setActiveTarget('pickup');
    initShipmentMap();

    $('#createShipmentForm').on('submit', function(e) {
        e.preventDefault();
        const submitBtn = $(this).find('button[type="submit"]');
        if (!$('#pickupLat').val() || !$('#pickupLng').val() || !$('#dropoffLat').val() || !$('#dropoffLng').val()) {
            alert('Placez les repères de ramassage et de livraison sur la carte avant de confirmer l’envoi.');
            return;
        }

        if (isShipmentPrecisionTooBroad(shipmentAddressState.pickup.precisionLevel) && !shipmentAddressState.pickup.confirmed) {
            setActiveTarget('pickup');
            updateShipmentPrecisionUi('pickup', 'Confirmez precisement le point de ramassage sur la carte avant de continuer.', true);
            alert('Confirmez precisement le point de ramassage sur la carte.');
            return;
        }

        if (isShipmentPrecisionTooBroad(shipmentAddressState.dropoff.precisionLevel) && !shipmentAddressState.dropoff.confirmed) {
            setActiveTarget('dropoff');
            updateShipmentPrecisionUi('dropoff', 'Confirmez precisement le point de livraison sur la carte avant de continuer.', true);
            alert('Confirmez precisement le point de livraison sur la carte.');
            return;
        }

        submitBtn.prop('disabled', true).text('Création en cours...');
        const formData = $(this).serializeArray();
        const payload = {};
        formData.forEach(item => {
            if (item.name.includes('[')) {
                const parts = item.name.split(/[\[\]]/).filter(p => p !== '');
                if (!payload[parts[0]]) payload[parts[0]] = {};
                payload[parts[0]][parts[1]] = item.value;
            } else {
                payload[item.name] = item.value;
            }
        });

        $.ajax({
            url: '/api/v1/colis/shipments',
            method: 'POST',
            data: JSON.stringify(payload),
            contentType: 'application/json',
            beforeSend: function(xhr) {
                xhr.setRequestHeader('Authorization', 'Bearer ' + '{{ auth()->user()->api_token }}');
            },
            success: function(response, _textStatus, xhr) {
                if (xhr.status !== 201 || !(response && (response.id || response.tracking_number))) {
                    alert('Le serveur Mema n’a pas confirmé une création valide de l’envoi.');
                    submitBtn.prop('disabled', false).text('Confirmer et créer l\'envoi');
                    return;
                }
                window.location.href = '/mes-colis';
            },
            error: function(xhr) {
                const message = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Erreur lors de la création.';
                if (xhr.responseJSON && xhr.responseJSON.errors) {
                    if (xhr.responseJSON.errors.pickup_address_confirmed) {
                        setActiveTarget('pickup');
                        updateShipmentPrecisionUi('pickup', 'Confirmez precisement le point de ramassage sur la carte avant de continuer.', true);
                    } else if (xhr.responseJSON.errors.dropoff_address_confirmed) {
                        setActiveTarget('dropoff');
                        updateShipmentPrecisionUi('dropoff', 'Confirmez precisement le point de livraison sur la carte avant de continuer.', true);
                    }
                }
                alert(message);
                submitBtn.prop('disabled', false).text('Confirmer et créer l\'envoi');
            }
        });
    });
});
</script>
@endsection
