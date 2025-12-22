@extends('frontend.layouts.app-modern')
@section('title', 'Nouvel envoi de colis | BantuDelice')

@section('style')
<style>
    .shipment-form-container { background: #fff; padding: 30px; border-radius: 8px; margin-bottom: 50px; }
    .step-header { border-bottom: 2px solid #f4f4f4; margin-bottom: 25px; padding-bottom: 10px; color: #4A67B2; }
    .price-summary { background: #f8f9fa; padding: 20px; border-radius: 8px; border: 1px solid #dee2e6; }
    .price-value { font-size: 24px; font-weight: bold; color: #28a745; }
    .loader { display: none; margin-left: 10px; }
</style>
@endsection

@section('content')
<div class="container my-5" style="margin-top: 100px !important;">
    <div class="row">
        <div class="col-md-8">
            <div class="shipment-form-container shadow-sm">
                <h2 class="mb-4">Créer un nouvel envoi</h2>
                
                <form id="createShipmentForm" action="{{ route('colis.shipments.store') }}" method="POST">
                    @csrf
                    
                    <!-- Étape 1 : Détails du colis -->
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
                                <label>Valeur déclarée (XAF) <i class="fa fa-info-circle text-primary" data-toggle="tooltip" title="Valeur estimée du colis pour l'assurance (frais de 1% prélevés)."></i></label>
                                <input type="number" name="declared_value" id="declared_value" class="form-control" min="0" placeholder="Ex: 5000">
                                <small class="text-muted">Pour l'assurance (optionnel)</small>
                            </div>
                            <div class="col-md-6 form-group">
                                <label>Montant à collecter (COD) <i class="fa fa-info-circle text-primary" data-toggle="tooltip" title="Montant que notre livreur collectera en espèces auprès du destinataire."></i></label>
                                <input type="number" name="cod_amount" id="cod_amount" class="form-control" min="0" placeholder="Ex: 15000">
                                <small class="text-muted">Paiement à la livraison (optionnel)</small>
                            </div>
                        </div>
                    </div>

                    <!-- Étape 2 : Expéditeur -->
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
                                <input type="text" name="pickup_address[district]" class="form-control" required placeholder="Ex: Poto-Poto">
                            </div>
                            <div class="col-md-4 form-group">
                                <label>Rue / Avenue</label>
                                <input type="text" name="pickup_address[address_line]" class="form-control" required placeholder="Ex: Av. de la Paix">
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Point de repère (Landmark)</label>
                            <input type="text" name="pickup_address[landmark]" class="form-control" placeholder="Ex: En face de la pharmacie X">
                        </div>
                    </div>

                    <!-- Étape 3 : Destinataire -->
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
                                <input type="text" name="dropoff_address[district]" class="form-control" required>
                            </div>
                            <div class="col-md-4 form-group">
                                <label>Rue / Avenue</label>
                                <input type="text" name="dropoff_address[address_line]" class="form-control" required>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Point de repère (Landmark)</label>
                            <input type="text" name="dropoff_address[landmark]" class="form-control">
                        </div>
                    </div>

                    <hr>
                    <div class="d-flex justify-content-between align-items-center">
                        <button type="submit" class="btn btn-success btn-lg">
                            Confirmer et créer l'envoi
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="col-md-4">
            <div class="price-summary shadow-sm sticky-top" style="top: 120px;">
                <h4>Résumé du devis</h4>
                <hr>
                <div id="quoteResult">
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
<script>
$(document).ready(function() {
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
                
                if (breakdown.express_surcharge) {
                    $('#express_row').attr('style', 'display: flex !important');
                    $('#express_fee').text(breakdown.express_surcharge.toLocaleString() + ' FCFA');
                } else {
                    $('#express_row').attr('style', 'display: none !important');
                }

                if (breakdown.cod_fee) {
                    $('#cod_row').attr('style', 'display: flex !important');
                    $('#cod_fee').text(breakdown.cod_fee.toLocaleString() + ' FCFA');
                } else {
                    $('#cod_row').attr('style', 'display: none !important');
                }

                if (breakdown.insurance_fee) {
                    $('#insurance_row').attr('style', 'display: flex !important');
                    $('#insurance_fee').text(breakdown.insurance_fee.toLocaleString() + ' FCFA');
                } else {
                    $('#insurance_row').attr('style', 'display: none !important');
                }

                $('#total_price').text(response.total_price.toLocaleString() + ' FCFA');
            }
        });
    }

    // Calculer au chargement et à chaque modification
    calculateQuote();
    $('#weight_kg, #service_level, #declared_value, #cod_amount').on('change keyup', calculateQuote);

    // Gestion de la soumission du formulaire via API puis redirection
    $('#createShipmentForm').on('submit', function(e) {
        e.preventDefault();
        const submitBtn = $(this).find('button[type="submit"]');
        submitBtn.prop('disabled', true).text('Création en cours...');

        const formData = $(this).serializeArray();
        const payload = {};
        
        // Convertir serializeArray en objet imbriqué
        formData.forEach(item => {
            if (item.name.includes('[')) {
                const parts = item.name.split(/[\[\]]/).filter(p => p !== "");
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
            success: function(response) {
                window.location.href = '/mes-colis';
            },
            error: function(xhr) {
                alert('Erreur lors de la création : ' + xhr.responseJSON.message);
                submitBtn.prop('disabled', false).text("Confirmer et créer l'envoi");
            }
        });
    });
});
</script>
@endsection

