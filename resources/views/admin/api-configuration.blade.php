@extends('admin.layouts.app')
@section('title', 'Configuration API | Admin BantuDelice')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">
                        <i class="fas fa-cog"></i> Configuration des API Externes
                    </h4>
                    <small>Configurez et testez les services API de BantuDelice</small>
                </div>
                <div class="card-body">
                    
                    <!-- Status Cards -->
                    <div class="row mb-4">
                        <div class="col-md-2 col-sm-6 mb-3">
                            <div class="card border-left-primary shadow h-100">
                                <div class="card-body">
                                    <div class="d-flex align-items-center">
                                        <div class="flex-grow-1">
                                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                                Géolocalisation
                                            </div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="geo-status">
                                                <span class="badge badge-success">OpenStreetMap</span>
                                            </div>
                                        </div>
                                        <div class="ml-2">
                                            <i class="fas fa-map-marker-alt fa-2x text-primary"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-2 col-sm-6 mb-3">
                            <div class="card border-left-warning shadow h-100">
                                <div class="card-body">
                                    <div class="d-flex align-items-center">
                                        <div class="flex-grow-1">
                                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                                SMS
                                            </div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="sms-status">
                                                <span class="badge badge-secondary">Mode Démo</span>
                                            </div>
                                        </div>
                                        <div class="ml-2">
                                            <i class="fas fa-sms fa-2x text-warning"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-2 col-sm-6 mb-3">
                            <div class="card border-left-info shadow h-100">
                                <div class="card-body">
                                    <div class="d-flex align-items-center">
                                        <div class="flex-grow-1">
                                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                                Mobile Money
                                            </div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="momo-status">
                                                <span class="badge badge-secondary">Mode Démo</span>
                                            </div>
                                        </div>
                                        <div class="ml-2">
                                            <i class="fas fa-mobile-alt fa-2x text-info"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-2 col-sm-6 mb-3">
                            <div class="card border-left-success shadow h-100">
                                <div class="card-body">
                                    <div class="d-flex align-items-center">
                                        <div class="flex-grow-1">
                                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                                Social Auth
                                            </div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="social-status">
                                                <span class="badge badge-secondary">Non configuré</span>
                                            </div>
                                        </div>
                                        <div class="ml-2">
                                            <i class="fas fa-sign-in-alt fa-2x text-success"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-2 col-sm-6 mb-3">
                            <div class="card border-left-danger shadow h-100">
                                <div class="card-body">
                                    <div class="d-flex align-items-center">
                                        <div class="flex-grow-1">
                                            <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">
                                                Email/SMTP
                                            </div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="email-status">
                                                <span class="badge badge-secondary">Non configuré</span>
                                            </div>
                                        </div>
                                        <div class="ml-2">
                                            <i class="fas fa-envelope fa-2x text-danger"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Instructions -->
                    <div class="alert alert-info">
                        <h5><i class="fas fa-info-circle"></i> Instructions</h5>
                        <ol>
                            <li>Ajoutez vos clés API dans l'onglet <strong>"Configuration"</strong> ci-dessous</li>
                            <li>Ou modifiez directement le fichier <code>.env</code> (voir guide ci-dessous)</li>
                            <li>Videz le cache après modification : <button class="btn btn-sm btn-outline-primary" onclick="clearCache()">Vider le cache</button></li>
                            <li>Testez chaque service avec les onglets ci-dessous</li>
                        </ol>
                    </div>
                    
                    <!-- Tabs -->
                    <ul class="nav nav-tabs" id="apiTabs" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" id="configuration-tab" data-toggle="tab" href="#configuration" role="tab">
                                <i class="fas fa-key"></i> Configuration
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="geolocation-tab" data-toggle="tab" href="#geolocation" role="tab">
                                <i class="fas fa-map-marker-alt"></i> Géolocalisation
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="sms-tab" data-toggle="tab" href="#sms" role="tab">
                                <i class="fas fa-sms"></i> SMS
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="momo-tab" data-toggle="tab" href="#momo" role="tab">
                                <i class="fas fa-mobile-alt"></i> Mobile Money
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="social-tab" data-toggle="tab" href="#social" role="tab">
                                <i class="fas fa-sign-in-alt"></i> Social Auth
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="email-tab" data-toggle="tab" href="#email" role="tab">
                                <i class="fas fa-envelope"></i> Email/SMTP
                            </a>
                        </li>
                    </ul>
                    
                    <div class="tab-content mt-4" id="apiTabsContent">
                        
                        <!-- Configuration Tab -->
                        <div class="tab-pane fade show active" id="configuration" role="tabpanel">
                            <div class="card">
                                <div class="card-header bg-warning text-dark">
                                    <h5><i class="fas fa-key"></i> Configuration des Clés API</h5>
                                    <small>Ajoutez ou modifiez vos clés API directement depuis cette interface</small>
                                </div>
                                <div class="card-body">
                                    <form id="api-keys-form">
                                        @csrf
                                        
                                        <!-- Google Maps -->
                                        <div class="card mb-3">
                                            <div class="card-header" data-toggle="collapse" data-target="#google-maps-config" style="cursor: pointer;">
                                                <h6 class="mb-0">
                                                    <i class="fas fa-map-marker-alt"></i> Google Maps (Optionnel)
                                                    <span class="badge badge-info float-right">OpenStreetMap fonctionne gratuitement</span>
                                                </h6>
                                            </div>
                                            <div id="google-maps-config" class="collapse">
                                                <div class="card-body">
                                                    <div class="form-group">
                                                        <label>GOOGLE_MAPS_API_KEY</label>
                                                        <input type="text" name="keys[GOOGLE_MAPS_API_KEY]" 
                                                               class="form-control api-key-input" 
                                                               placeholder="Votre clé API Google Maps"
                                                               id="key-GOOGLE_MAPS_API_KEY">
                                                        <small class="form-text text-muted">Optionnel. OpenStreetMap fonctionne déjà gratuitement.</small>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- SMS - Twilio -->
                                        <div class="card mb-3">
                                            <div class="card-header" data-toggle="collapse" data-target="#twilio-config" style="cursor: pointer;">
                                                <h6 class="mb-0"><i class="fas fa-sms"></i> SMS - Twilio</h6>
                                            </div>
                                            <div id="twilio-config" class="collapse">
                                                <div class="card-body">
                                                    <div class="row">
                                                        <div class="col-md-6">
                                                            <div class="form-group">
                                                                <label>TWILIO_SID</label>
                                                                <input type="text" name="keys[TWILIO_SID]" 
                                                                       class="form-control api-key-input" 
                                                                       placeholder="ACxxxxxxxxxxxxx"
                                                                       id="key-TWILIO_SID">
                                                            </div>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <div class="form-group">
                                                                <label>TWILIO_TOKEN</label>
                                                                <input type="password" name="keys[TWILIO_TOKEN]" 
                                                                       class="form-control api-key-input" 
                                                                       placeholder="Votre token secret"
                                                                       id="key-TWILIO_TOKEN">
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="row">
                                                        <div class="col-md-6">
                                                            <div class="form-group">
                                                                <label>TWILIO_FROM</label>
                                                                <input type="text" name="keys[TWILIO_FROM]" 
                                                                       class="form-control api-key-input" 
                                                                       placeholder="+242064000000"
                                                                       id="key-TWILIO_FROM">
                                                            </div>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <div class="form-group">
                                                                <label>TWILIO_VERIFY_SID (Optionnel)</label>
                                                                <input type="text" name="keys[TWILIO_VERIFY_SID]" 
                                                                       class="form-control api-key-input" 
                                                                       placeholder="VAxxxxxxxxxxxxx"
                                                                       id="key-TWILIO_VERIFY_SID">
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- SMS - Africa's Talking -->
                                        <div class="card mb-3">
                                            <div class="card-header" data-toggle="collapse" data-target="#africastalking-config" style="cursor: pointer;">
                                                <h6 class="mb-0"><i class="fas fa-sms"></i> SMS - Africa's Talking</h6>
                                            </div>
                                            <div id="africastalking-config" class="collapse">
                                                <div class="card-body">
                                                    <div class="row">
                                                        <div class="col-md-4">
                                                            <div class="form-group">
                                                                <label>AFRICASTALKING_USERNAME</label>
                                                                <input type="text" name="keys[AFRICASTALKING_USERNAME]" 
                                                                       class="form-control api-key-input" 
                                                                       placeholder="Votre username"
                                                                       id="key-AFRICASTALKING_USERNAME">
                                                            </div>
                                                        </div>
                                                        <div class="col-md-4">
                                                            <div class="form-group">
                                                                <label>AFRICASTALKING_API_KEY</label>
                                                                <input type="password" name="keys[AFRICASTALKING_API_KEY]" 
                                                                       class="form-control api-key-input" 
                                                                       placeholder="Votre API key"
                                                                       id="key-AFRICASTALKING_API_KEY">
                                                            </div>
                                                        </div>
                                                        <div class="col-md-4">
                                                            <div class="form-group">
                                                                <label>AFRICASTALKING_FROM</label>
                                                                <input type="text" name="keys[AFRICASTALKING_FROM]" 
                                                                       class="form-control api-key-input" 
                                                                       placeholder="BantuDelice"
                                                                       id="key-AFRICASTALKING_FROM">
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- SMS - BulkGate -->
                                        <div class="card mb-3">
                                            <div class="card-header" data-toggle="collapse" data-target="#bulkgate-config" style="cursor: pointer;">
                                                <h6 class="mb-0"><i class="fas fa-sms"></i> SMS - BulkGate</h6>
                                            </div>
                                            <div id="bulkgate-config" class="collapse">
                                                <div class="card-body">
                                                    <div class="alert alert-info">
                                                        <i class="fas fa-info-circle"></i> 
                                                        <strong>BulkGate:</strong> Service SMS européen. Vous devez disposer de votre Application ID et API Key depuis votre compte BulkGate.
                                                    </div>
                                                    <div class="row">
                                                        <div class="col-md-6">
                                                            <div class="form-group">
                                                                <label>BULKGATE_APPLICATION_ID</label>
                                                                <input type="text" name="keys[BULKGATE_APPLICATION_ID]" 
                                                                       class="form-control api-key-input" 
                                                                       placeholder="Votre Application ID"
                                                                       id="key-BULKGATE_APPLICATION_ID">
                                                                <small class="form-text text-muted">ID de votre application BulkGate</small>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <div class="form-group">
                                                                <label>BULKGATE_API_KEY</label>
                                                                <input type="password" name="keys[BULKGATE_API_KEY]" 
                                                                       class="form-control api-key-input" 
                                                                       placeholder="Votre API Key"
                                                                       id="key-BULKGATE_API_KEY">
                                                                <small class="form-text text-muted">Clé API de votre compte BulkGate</small>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="row">
                                                        <div class="col-md-6">
                                                            <div class="form-group">
                                                                <label>BULKGATE_SENDER_ID</label>
                                                                <input type="text" name="keys[BULKGATE_SENDER_ID]" 
                                                                       class="form-control api-key-input" 
                                                                       placeholder="BantuDelice"
                                                                       value="BantuDelice"
                                                                       id="key-BULKGATE_SENDER_ID">
                                                                <small class="form-text text-muted">Nom de l'expéditeur (max 11 caractères)</small>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Mobile Money - MTN MoMo -->
                                        <div class="card mb-3">
                                            <div class="card-header" data-toggle="collapse" data-target="#mtn-config" style="cursor: pointer;">
                                                <h6 class="mb-0"><i class="fas fa-mobile-alt"></i> Mobile Money - MTN MoMo</h6>
                                            </div>
                                            <div id="mtn-config" class="collapse">
                                                <div class="card-body">
                                                    <div class="row">
                                                        <div class="col-md-6">
                                                            <div class="form-group">
                                                                <label>MTN_MOMO_API_KEY</label>
                                                                <input type="text" name="keys[MTN_MOMO_API_KEY]" 
                                                                       class="form-control api-key-input" 
                                                                       placeholder="Votre API key"
                                                                       id="key-MTN_MOMO_API_KEY">
                                                            </div>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <div class="form-group">
                                                                <label>MTN_MOMO_API_USER</label>
                                                                <input type="text" name="keys[MTN_MOMO_API_USER]" 
                                                                       class="form-control api-key-input" 
                                                                       placeholder="Votre API user"
                                                                       id="key-MTN_MOMO_API_USER">
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="row">
                                                        <div class="col-md-6">
                                                            <div class="form-group">
                                                                <label>MTN_MOMO_API_SECRET</label>
                                                                <input type="password" name="keys[MTN_MOMO_API_SECRET]" 
                                                                       class="form-control api-key-input" 
                                                                       placeholder="Votre API secret"
                                                                       id="key-MTN_MOMO_API_SECRET">
                                                            </div>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <div class="form-group">
                                                                <label>MTN_MOMO_SUBSCRIPTION_KEY</label>
                                                                <input type="text" name="keys[MTN_MOMO_SUBSCRIPTION_KEY]" 
                                                                       class="form-control api-key-input" 
                                                                       placeholder="Votre subscription key"
                                                                       id="key-MTN_MOMO_SUBSCRIPTION_KEY">
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Mobile Money - Airtel -->
                                        <div class="card mb-3">
                                            <div class="card-header" data-toggle="collapse" data-target="#airtel-config" style="cursor: pointer;">
                                                <h6 class="mb-0"><i class="fas fa-mobile-alt"></i> Mobile Money - Airtel Money</h6>
                                            </div>
                                            <div id="airtel-config" class="collapse">
                                                <div class="card-body">
                                                    <div class="row">
                                                        <div class="col-md-6">
                                                            <div class="form-group">
                                                                <label>AIRTEL_MONEY_CLIENT_ID</label>
                                                                <input type="text" name="keys[AIRTEL_MONEY_CLIENT_ID]" 
                                                                       class="form-control api-key-input" 
                                                                       placeholder="Votre Client ID"
                                                                       id="key-AIRTEL_MONEY_CLIENT_ID">
                                                            </div>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <div class="form-group">
                                                                <label>AIRTEL_MONEY_CLIENT_SECRET</label>
                                                                <input type="password" name="keys[AIRTEL_MONEY_CLIENT_SECRET]" 
                                                                       class="form-control api-key-input" 
                                                                       placeholder="Votre Client Secret"
                                                                       id="key-AIRTEL_MONEY_CLIENT_SECRET">
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Social Auth - Google -->
                                        <div class="card mb-3">
                                            <div class="card-header" data-toggle="collapse" data-target="#google-auth-config" style="cursor: pointer;">
                                                <h6 class="mb-0"><i class="fab fa-google"></i> Authentification Sociale - Google</h6>
                                            </div>
                                            <div id="google-auth-config" class="collapse">
                                                <div class="card-body">
                                                    <div class="row">
                                                        <div class="col-md-6">
                                                            <div class="form-group">
                                                                <label>GOOGLE_CLIENT_ID</label>
                                                                <input type="text" name="keys[GOOGLE_CLIENT_ID]" 
                                                                       class="form-control api-key-input" 
                                                                       placeholder="xxx.apps.googleusercontent.com"
                                                                       id="key-GOOGLE_CLIENT_ID">
                                                            </div>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <div class="form-group">
                                                                <label>GOOGLE_CLIENT_SECRET</label>
                                                                <input type="password" name="keys[GOOGLE_CLIENT_SECRET]" 
                                                                       class="form-control api-key-input" 
                                                                       placeholder="Votre Client Secret"
                                                                       id="key-GOOGLE_CLIENT_SECRET">
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Social Auth - Facebook -->
                                        <div class="card mb-3">
                                            <div class="card-header" data-toggle="collapse" data-target="#facebook-config" style="cursor: pointer;">
                                                <h6 class="mb-0"><i class="fab fa-facebook"></i> Authentification Sociale - Facebook</h6>
                                            </div>
                                            <div id="facebook-config" class="collapse">
                                                <div class="card-body">
                                                    <div class="row">
                                                        <div class="col-md-6">
                                                            <div class="form-group">
                                                                <label>FACEBOOK_CLIENT_ID</label>
                                                                <input type="text" name="keys[FACEBOOK_CLIENT_ID]" 
                                                                       class="form-control api-key-input" 
                                                                       placeholder="Votre App ID"
                                                                       id="key-FACEBOOK_CLIENT_ID">
                                                            </div>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <div class="form-group">
                                                                <label>FACEBOOK_CLIENT_SECRET</label>
                                                                <input type="password" name="keys[FACEBOOK_CLIENT_SECRET]" 
                                                                       class="form-control api-key-input" 
                                                                       placeholder="Votre App Secret"
                                                                       id="key-FACEBOOK_CLIENT_SECRET">
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="alert alert-warning">
                                            <i class="fas fa-exclamation-triangle"></i> 
                                            <strong>Important:</strong> Après avoir sauvegardé vos clés, n'oubliez pas de vider le cache!
                                        </div>
                                        
                                        <div class="text-right">
                                            <button type="button" class="btn btn-secondary" onclick="loadApiKeys()">
                                                <i class="fas fa-sync"></i> Recharger
                                            </button>
                                            <button type="submit" class="btn btn-primary">
                                                <i class="fas fa-save"></i> Sauvegarder les Clés
                                            </button>
                                        </div>
                                    </form>
                                    
                                    <div id="save-result" class="mt-3"></div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Géolocalisation Tab -->
                        <div class="tab-pane fade" id="geolocation" role="tabpanel">
                            <div class="card">
                                <div class="card-header">
                                    <h5><i class="fas fa-map-marker-alt"></i> Test Géolocalisation</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <h6>Géocodage d'adresse</h6>
                                            <div class="form-group">
                                                <label>Adresse</label>
                                                <input type="text" id="geocode-address" class="form-control" 
                                                       placeholder="Ex: Centre-ville, Brazzaville">
                                            </div>
                                            <button class="btn btn-primary" onclick="testGeocode()">
                                                <i class="fas fa-search"></i> Géocoder
                                            </button>
                                        </div>
                                        <div class="col-md-6">
                                            <h6>Géocodage inverse</h6>
                                            <div class="row">
                                                <div class="col-6">
                                                    <div class="form-group">
                                                        <label>Latitude</label>
                                                        <input type="number" id="reverse-lat" class="form-control" 
                                                               step="0.000001" value="-4.2767">
                                                    </div>
                                                </div>
                                                <div class="col-6">
                                                    <div class="form-group">
                                                        <label>Longitude</label>
                                                        <input type="number" id="reverse-lng" class="form-control" 
                                                               step="0.000001" value="15.2832">
                                                    </div>
                                                </div>
                                            </div>
                                            <button class="btn btn-primary" onclick="testReverseGeocode()">
                                                <i class="fas fa-map"></i> Obtenir l'adresse
                                            </button>
                                        </div>
                                    </div>
                                    
                                    <hr>
                                    
                                    <div class="form-group">
                                        <label>Calcul de distance</label>
                                        <div class="row">
                                            <div class="col-md-3">
                                                <input type="number" id="distance-lat1" class="form-control" 
                                                       placeholder="Lat 1" value="-4.2767" step="0.000001">
                                            </div>
                                            <div class="col-md-3">
                                                <input type="number" id="distance-lng1" class="form-control" 
                                                       placeholder="Lng 1" value="15.2832" step="0.000001">
                                            </div>
                                            <div class="col-md-3">
                                                <input type="number" id="distance-lat2" class="form-control" 
                                                       placeholder="Lat 2" value="-4.2700" step="0.000001">
                                            </div>
                                            <div class="col-md-3">
                                                <input type="number" id="distance-lng2" class="form-control" 
                                                       placeholder="Lng 2" value="15.2600" step="0.000001">
                                            </div>
                                        </div>
                                        <button class="btn btn-primary mt-2" onclick="testDistance()">
                                            <i class="fas fa-ruler"></i> Calculer la distance
                                        </button>
                                    </div>
                                    
                                    <div id="geolocation-result" class="mt-3"></div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- SMS Tab -->
                        <div class="tab-pane fade" id="sms" role="tabpanel">
                            <div class="card">
                                <div class="card-header">
                                    <h5><i class="fas fa-sms"></i> Test SMS</h5>
                                </div>
                                <div class="card-body">
                                    <div class="form-group">
                                        <label>Numéro de téléphone</label>
                                        <input type="tel" id="sms-phone" class="form-control" 
                                               placeholder="+242 06 XXX XX XX" value="+242064000000">
                                        <small class="form-text text-muted">Format international requis</small>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label>Message (optionnel pour OTP)</label>
                                        <textarea id="sms-message" class="form-control" rows="3" 
                                                  placeholder="Laissez vide pour tester l'OTP"></textarea>
                                    </div>
                                    
                                    <div class="btn-group" role="group">
                                        <button class="btn btn-primary" onclick="testSms()">
                                            <i class="fas fa-paper-plane"></i> Envoyer SMS
                                        </button>
                                        <button class="btn btn-success" onclick="testOtp()">
                                            <i class="fas fa-key"></i> Envoyer OTP
                                        </button>
                                    </div>
                                    
                                    <div id="sms-result" class="mt-3"></div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Mobile Money Tab -->
                        <div class="tab-pane fade" id="momo" role="tabpanel">
                            <div class="card">
                                <div class="card-header">
                                    <h5><i class="fas fa-mobile-alt"></i> Test Mobile Money</h5>
                                </div>
                                <div class="card-body">
                                    <div class="alert alert-warning">
                                        <i class="fas fa-exclamation-triangle"></i> 
                                        <strong>Mode démo:</strong> Les paiements sont simulés. Configurez les clés API pour activer les paiements réels.
                                    </div>
                                    
                                    <div class="form-group">
                                        <label>Numéro de téléphone</label>
                                        <input type="tel" id="momo-phone" class="form-control" 
                                               placeholder="+242 06 XXX XX XX" value="+242064000000">
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Montant (FCFA)</label>
                                                <input type="number" id="momo-amount" class="form-control" 
                                                       value="5000" min="100">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Opérateur</label>
                                                <select id="momo-operator" class="form-control">
                                                    <option value="mtn">MTN MoMo</option>
                                                    <option value="airtel">Airtel Money</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <button class="btn btn-primary" onclick="testMobileMoney()">
                                        <i class="fas fa-credit-card"></i> Tester le paiement
                                    </button>
                                    
                                    <div id="momo-result" class="mt-3"></div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Social Auth Tab -->
                        <div class="tab-pane fade" id="social" role="tabpanel">
                            <div class="card">
                                <div class="card-header">
                                    <h5><i class="fas fa-sign-in-alt"></i> Authentification Sociale</h5>
                                </div>
                                <div class="card-body">
                                    <div class="alert alert-info">
                                        <h6>Configuration requise:</h6>
                                        <ul>
                                            <li><strong>Google:</strong> Créez un projet sur <a href="https://console.cloud.google.com" target="_blank">Google Cloud Console</a></li>
                                            <li><strong>Facebook:</strong> Créez une app sur <a href="https://developers.facebook.com" target="_blank">Facebook Developers</a></li>
                                        </ul>
                                        <p class="mb-0">Voir le guide complet dans <code>docs/GUIDE_CONFIGURATION_API.md</code></p>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="card">
                                                <div class="card-body text-center">
                                                    <i class="fab fa-google fa-3x text-danger mb-3"></i>
                                                    <h5>Google Sign-In</h5>
                                                    <p class="text-muted">Authentification via Google</p>
                                                    <span id="google-status" class="badge badge-secondary">Non configuré</span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="card">
                                                <div class="card-body text-center">
                                                    <i class="fab fa-facebook fa-3x text-primary mb-3"></i>
                                                    <h5>Facebook Login</h5>
                                                    <p class="text-muted">Authentification via Facebook</p>
                                                    <span id="facebook-status" class="badge badge-secondary">Non configuré</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Email/SMTP Tab -->
                        <div class="tab-pane fade" id="email" role="tabpanel">
                            <div class="card">
                                <div class="card-header bg-info text-white">
                                    <h5><i class="fas fa-envelope"></i> Configuration Email/SMTP</h5>
                                    <small>Configurez les paramètres SMTP pour l'envoi d'emails</small>
                                </div>
                                <div class="card-body">
                                    <!-- Status Card -->
                                    <div class="alert alert-info" id="email-status-alert">
                                        <i class="fas fa-spinner fa-spin"></i> Chargement du statut...
                                    </div>
                                    
                                    <!-- Configuration Form -->
                                    <form id="mail-config-form">
                                        @csrf
                                        
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>Serveur SMTP (MAIL_HOST) <span class="text-danger">*</span></label>
                                                    <input type="text" name="MAIL_HOST" id="mail-host" 
                                                           class="form-control" 
                                                           placeholder="mail.bantudelice.cg"
                                                           required>
                                                    <small class="form-text text-muted">Adresse du serveur SMTP</small>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    <label>Port SMTP (MAIL_PORT) <span class="text-danger">*</span></label>
                                                    <input type="number" name="MAIL_PORT" id="mail-port" 
                                                           class="form-control" 
                                                           placeholder="465"
                                                           value="465"
                                                           min="1" max="65535" required>
                                                    <small class="form-text text-muted">Port SMTP (465 pour SSL, 587 pour TLS)</small>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    <label>Chiffrement (MAIL_ENCRYPTION)</label>
                                                    <select name="MAIL_ENCRYPTION" id="mail-encryption" class="form-control">
                                                        <option value="ssl">SSL</option>
                                                        <option value="tls">TLS</option>
                                                    </select>
                                                    <small class="form-text text-muted">SSL pour port 465, TLS pour port 587</small>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>Nom d'utilisateur (MAIL_USERNAME) <span class="text-danger">*</span></label>
                                                    <input type="text" name="MAIL_USERNAME" id="mail-username" 
                                                           class="form-control" 
                                                           placeholder="noreply@bantudelice.cg"
                                                           required>
                                                    <small class="form-text text-muted">Adresse email ou nom d'utilisateur SMTP</small>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>Mot de passe (MAIL_PASSWORD) <span class="text-danger">*</span></label>
                                                    <input type="password" name="MAIL_PASSWORD" id="mail-password" 
                                                           class="form-control" 
                                                           placeholder="Votre mot de passe SMTP"
                                                           required>
                                                    <small class="form-text text-muted">Mot de passe SMTP</small>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>Adresse expéditeur (MAIL_FROM_ADDRESS) <span class="text-danger">*</span></label>
                                                    <input type="email" name="MAIL_FROM_ADDRESS" id="mail-from-address" 
                                                           class="form-control" 
                                                           placeholder="noreply@bantudelice.cg"
                                                           required>
                                                    <small class="form-text text-muted">Adresse email d'expédition</small>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>Nom expéditeur (MAIL_FROM_NAME)</label>
                                                    <input type="text" name="MAIL_FROM_NAME" id="mail-from-name" 
                                                           class="form-control" 
                                                           placeholder="BantuDelice"
                                                           value="BantuDelice">
                                                    <small class="form-text text-muted">Nom affiché comme expéditeur</small>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="form-group">
                                            <div class="form-check">
                                                <input type="checkbox" name="MAIL_ENABLED" id="mail-enabled" 
                                                       class="form-check-input" value="1" checked>
                                                <label class="form-check-label" for="mail-enabled">
                                                    Activer l'envoi d'emails (MAIL_ENABLED)
                                                </label>
                                            </div>
                                        </div>
                                        
                                        <div class="alert alert-warning">
                                            <i class="fas fa-exclamation-triangle"></i> 
                                            <strong>Important:</strong> Après avoir sauvegardé, n'oubliez pas de vider le cache!
                                        </div>
                                        
                                        <div class="text-right">
                                            <button type="button" class="btn btn-secondary" onclick="loadMailConfig()">
                                                <i class="fas fa-sync"></i> Recharger
                                            </button>
                                            <button type="submit" class="btn btn-primary">
                                                <i class="fas fa-save"></i> Sauvegarder la Configuration
                                            </button>
                                        </div>
                                    </form>
                                    
                                    <div id="mail-save-result" class="mt-3"></div>
                                    
                                    <hr class="my-4">
                                    
                                    <!-- Test Email Section -->
                                    <div class="card">
                                        <div class="card-header bg-success text-white">
                                            <h5><i class="fas fa-paper-plane"></i> Test d'Envoi d'Email</h5>
                                        </div>
                                        <div class="card-body">
                                            <div class="form-group">
                                                <label>Adresse email de destination</label>
                                                <input type="email" id="test-email-to" class="form-control" 
                                                       placeholder="test@example.com" required>
                                                <small class="form-text text-muted">L'email de test sera envoyé à cette adresse</small>
                                            </div>
                                            
                                            <div class="form-group">
                                                <label>Sujet (optionnel)</label>
                                                <input type="text" id="test-email-subject" class="form-control" 
                                                       placeholder="Test Email - BantuDelice">
                                            </div>
                                            
                                            <div class="form-group">
                                                <label>Message (optionnel)</label>
                                                <textarea id="test-email-message" class="form-control" rows="3" 
                                                          placeholder="Ceci est un email de test depuis BantuDelice..."></textarea>
                                            </div>
                                            
                                            <button class="btn btn-success" onclick="testEmail()">
                                                <i class="fas fa-paper-plane"></i> Envoyer l'Email de Test
                                            </button>
                                            
                                            <div id="test-email-result" class="mt-3"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                    </div>
                    
                    <!-- Guide -->
                    <div class="card mt-4">
                        <div class="card-header bg-secondary text-white">
                            <h5><i class="fas fa-book"></i> Guide de Configuration</h5>
                        </div>
                        <div class="card-body">
                            <p>Consultez le fichier <code>docs/GUIDE_CONFIGURATION_API.md</code> pour les instructions détaillées.</p>
                            <p>Ou éditez directement le fichier <code>.env</code> avec vos clés API.</p>
                            
                            <h6>Variables principales à configurer:</h6>
                            <ul>
                                <li><code>GOOGLE_MAPS_API_KEY</code> - Pour Google Maps (optionnel, OpenStreetMap fonctionne)</li>
                                <li><code>TWILIO_SID</code> et <code>TWILIO_TOKEN</code> - Pour SMS via Twilio</li>
                                <li><code>AFRICASTALKING_USERNAME</code> et <code>AFRICASTALKING_API_KEY</code> - Pour SMS via Africa's Talking</li>
                                <li><code>BULKGATE_APPLICATION_ID</code> et <code>BULKGATE_API_KEY</code> - Pour SMS via BulkGate</li>
                                <li><code>MTN_MOMO_API_KEY</code> - Pour MTN Mobile Money</li>
                                <li><code>AIRTEL_MONEY_CLIENT_ID</code> - Pour Airtel Money</li>
                                <li><code>GOOGLE_CLIENT_ID</code> - Pour Google Sign-In</li>
                                <li><code>FACEBOOK_CLIENT_ID</code> - Pour Facebook Login</li>
                            </ul>
                        </div>
                    </div>
                    
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    // Charger le statut au chargement
    $(document).ready(function() {
        loadApiStatus();
        loadApiKeys();
        loadMailConfig();
        loadMailStatus();
        
        // Gérer la soumission du formulaire de clés
        $('#api-keys-form').on('submit', function(e) {
            e.preventDefault();
            saveApiKeys();
        });
        
        // Gérer la soumission du formulaire email
        $('#mail-config-form').on('submit', function(e) {
            e.preventDefault();
            saveMailConfig();
        });
    });
    
    // Charger les clés API existantes
    function loadApiKeys() {
        $.get('{{ route("admin.api.keys") }}')
            .done(function(data) {
                if (data.success && data.keys) {
                    // Remplir les champs avec les valeurs masquées ou vides
                    Object.keys(data.keys).forEach(function(key) {
                        const input = $('#key-' + key);
                        if (input.length) {
                            const currentValue = input.val();
                            const maskedValue = data.keys[key];
                            
                            // Si le champ est vide ou contient des astérisques, ne pas remplacer
                            // Sinon, garder la valeur actuelle (l'utilisateur peut la modifier)
                            if (!currentValue || currentValue.includes('*')) {
                                // Ne pas remplacer, laisser vide pour que l'utilisateur saisisse
                            }
                        }
                    });
                }
            })
            .fail(function() {
                console.error('Erreur lors du chargement des clés');
            });
    }
    
    // Sauvegarder les clés API
    function saveApiKeys() {
        const formData = {};
        
        // Collecter toutes les valeurs des champs
        $('.api-key-input').each(function() {
            const key = $(this).attr('name').replace('keys[', '').replace(']', '');
            const value = $(this).val().trim();
            
            // Ne pas envoyer les valeurs vides
            if (value) {
                formData[key] = value;
            }
        });
        
        if (Object.keys(formData).length === 0) {
            alert('Aucune clé à sauvegarder. Remplissez au moins un champ.');
            return;
        }
        
        showLoading('#save-result');
        
        $.ajax({
            url: '{{ route("admin.api.keys.save") }}',
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                keys: formData
            },
            success: function(data) {
                if (data.success) {
                    showResult('#save-result', {
                        success: true,
                        message: data.message || 'Clés sauvegardées avec succès!',
                    });
                    
                    // Recharger le statut
                    setTimeout(function() {
                        loadApiStatus();
                        clearCache();
                    }, 1000);
                } else {
                    showError('#save-result', data.message || 'Erreur lors de la sauvegarde');
                }
            },
            error: function(xhr) {
                showError('#save-result', xhr.responseJSON?.message || 'Erreur lors de la sauvegarde');
            }
        });
    }
    
    function loadApiStatus() {
        $.get('{{ route("admin.api.status") }}')
            .done(function(data) {
                if (data.success) {
                    updateStatusDisplay(data.status);
                }
            });
    }
    
    function updateStatusDisplay(status) {
        // Géolocalisation
        if (status.geolocation.google_maps.configured) {
            $('#geo-status').html('<span class="badge badge-success">Google Maps</span>');
        } else {
            $('#geo-status').html('<span class="badge badge-info">OpenStreetMap (Gratuit)</span>');
        }
        
        // SMS
        if (status.sms.twilio.configured) {
            $('#sms-status').html('<span class="badge badge-success">Twilio</span>');
        } else if (status.sms.africastalking.configured) {
            $('#sms-status').html('<span class="badge badge-success">Africa\'s Talking</span>');
        } else if (status.sms.bulkgate && status.sms.bulkgate.configured) {
            $('#sms-status').html('<span class="badge badge-success">BulkGate</span>');
        } else {
            $('#sms-status').html('<span class="badge badge-secondary">Mode Démo</span>');
        }
        
        // Mobile Money
        if (status.mobile_money.mtn_momo.configured) {
            $('#momo-status').html('<span class="badge badge-success">MTN MoMo</span>');
        } else if (status.mobile_money.airtel_money.configured) {
            $('#momo-status').html('<span class="badge badge-success">Airtel Money</span>');
        } else {
            $('#momo-status').html('<span class="badge badge-secondary">Mode Démo</span>');
        }
        
        // Social Auth
        let socialConfigured = false;
        if (status.social_auth.google.configured) {
            $('#google-status').removeClass('badge-secondary').addClass('badge-success').text('Configuré');
            socialConfigured = true;
        }
        if (status.social_auth.facebook.configured) {
            $('#facebook-status').removeClass('badge-secondary').addClass('badge-success').text('Configuré');
            socialConfigured = true;
        }
        if (socialConfigured) {
            $('#social-status').html('<span class="badge badge-success">Configuré</span>');
        }
        
        // Email
        if (status.email && status.email.smtp) {
            if (status.email.smtp.configured && status.email.smtp.enabled) {
                $('#email-status').html('<span class="badge badge-success">Configuré</span>');
            } else if (status.email.smtp.configured) {
                $('#email-status').html('<span class="badge badge-warning">Configuré (désactivé)</span>');
            } else {
                $('#email-status').html('<span class="badge badge-secondary">Non configuré</span>');
            }
        } else {
            $('#email-status').html('<span class="badge badge-secondary">Non configuré</span>');
        }
    }
    
    function testGeocode() {
        const address = $('#geocode-address').val();
        if (!address) {
            alert('Veuillez entrer une adresse');
            return;
        }
        
        showLoading('#geolocation-result');
        
        $.post('{{ route("admin.api.test.geolocation") }}', {
            _token: '{{ csrf_token() }}',
            address: address
        })
        .done(function(data) {
            showResult('#geolocation-result', data);
        })
        .fail(function(xhr) {
            showError('#geolocation-result', xhr.responseJSON?.message || 'Erreur');
        });
    }
    
    function testReverseGeocode() {
        const lat = $('#reverse-lat').val();
        const lng = $('#reverse-lng').val();
        
        showLoading('#geolocation-result');
        
        $.post('{{ route("admin.api.test.geolocation") }}', {
            _token: '{{ csrf_token() }}',
            lat: lat,
            lng: lng
        })
        .done(function(data) {
            showResult('#geolocation-result', data);
        })
        .fail(function(xhr) {
            showError('#geolocation-result', xhr.responseJSON?.message || 'Erreur');
        });
    }
    
    function testDistance() {
        const lat1 = $('#distance-lat1').val();
        const lng1 = $('#distance-lng1').val();
        const lat2 = $('#distance-lat2').val();
        const lng2 = $('#distance-lng2').val();
        
        showLoading('#geolocation-result');
        
        $.post('{{ route("admin.api.test.geolocation") }}', {
            _token: '{{ csrf_token() }}',
            lat: lat1,
            lng: lng1,
            lat2: lat2,
            lng2: lng2
        })
        .done(function(data) {
            showResult('#geolocation-result', data);
        })
        .fail(function(xhr) {
            showError('#geolocation-result', xhr.responseJSON?.message || 'Erreur');
        });
    }
    
    function testSms() {
        const phone = $('#sms-phone').val();
        const message = $('#sms-message').val();
        
        if (!phone) {
            alert('Veuillez entrer un numéro de téléphone');
            return;
        }
        
        showLoading('#sms-result');
        
        $.post('{{ route("admin.api.test.sms") }}', {
            _token: '{{ csrf_token() }}',
            phone: phone,
            message: message
        })
        .done(function(data) {
            showResult('#sms-result', data);
            if (data.demo && data.result.otp_code) {
                $('#sms-result').append('<div class="alert alert-warning mt-2"><strong>Code OTP (mode démo):</strong> ' + data.result.otp_code + '</div>');
            }
        })
        .fail(function(xhr) {
            showError('#sms-result', xhr.responseJSON?.message || 'Erreur');
        });
    }
    
    function testOtp() {
        const phone = $('#sms-phone').val();
        
        if (!phone) {
            alert('Veuillez entrer un numéro de téléphone');
            return;
        }
        
        showLoading('#sms-result');
        
        $.post('{{ route("admin.api.test.otp") }}', {
            _token: '{{ csrf_token() }}',
            phone: phone
        })
        .done(function(data) {
            showResult('#sms-result', data);
            if (data.otp_code) {
                $('#sms-result').append('<div class="alert alert-info mt-2"><strong>Code OTP (mode démo):</strong> ' + data.otp_code + '</div>');
            }
        })
        .fail(function(xhr) {
            showError('#sms-result', xhr.responseJSON?.message || 'Erreur');
        });
    }
    
    function testMobileMoney() {
        const phone = $('#momo-phone').val();
        const amount = $('#momo-amount').val();
        const operator = $('#momo-operator').val();
        
        if (!phone || !amount) {
            alert('Veuillez remplir tous les champs');
            return;
        }
        
        showLoading('#momo-result');
        
        $.post('{{ route("admin.api.test.momo") }}', {
            _token: '{{ csrf_token() }}',
            phone: phone,
            amount: amount,
            operator: operator
        })
        .done(function(data) {
            showResult('#momo-result', data);
        })
        .fail(function(xhr) {
            showError('#momo-result', xhr.responseJSON?.message || 'Erreur');
        });
    }
    
    function clearCache() {
        if (!confirm('Voulez-vous vider le cache de configuration?')) {
            return;
        }
        
        $.post('{{ route("admin.api.clear-cache") }}', {
            _token: '{{ csrf_token() }}'
        })
        .done(function(data) {
            alert('Cache vidé avec succès!');
            loadApiStatus(); // Recharger le statut
        })
        .fail(function() {
            alert('Erreur lors du vidage du cache');
        });
    }
    
    function showLoading(selector) {
        $(selector).html('<div class="alert alert-info"><i class="fas fa-spinner fa-spin"></i> En cours...</div>');
    }
    
    function showResult(selector, data) {
        const alertClass = data.success ? 'alert-success' : 'alert-danger';
        const icon = data.success ? 'fa-check-circle' : 'fa-times-circle';
        
        let html = '<div class="alert ' + alertClass + '">';
        html += '<i class="fas ' + icon + '"></i> <strong>' + (data.message || 'Résultat') + '</strong>';
        
        if (data.demo) {
            html += '<br><small class="text-muted">Mode démo activé</small>';
        }
        
        if (data.result) {
            html += '<pre class="mt-2 mb-0" style="max-height: 300px; overflow: auto;">' + JSON.stringify(data.result, null, 2) + '</pre>';
        }
        
        html += '</div>';
        
        $(selector).html(html);
    }
    
    function showError(selector, message) {
        $(selector).html('<div class="alert alert-danger"><i class="fas fa-exclamation-triangle"></i> ' + message + '</div>');
    }
    
    // ========== Fonctions Email/SMTP ==========
    
    function loadMailStatus() {
        $.get('{{ route("admin.api.mail.status") }}')
            .done(function(data) {
                if (data.success && data.status) {
                    const status = data.status;
                    let alertClass = 'alert-secondary';
                    let statusText = 'Non configuré';
                    
                    if (status.configured) {
                        alertClass = status.enabled ? 'alert-success' : 'alert-warning';
                        statusText = status.enabled ? 'Configuré et activé' : 'Configuré mais désactivé';
                    }
                    
                    let html = '<div class="alert ' + alertClass + '">';
                    html += '<i class="fas fa-envelope"></i> <strong>Statut Email:</strong> ' + statusText;
                    if (status.configured) {
                        html += '<br><small>';
                        html += 'Serveur: ' + (status.host || 'N/A') + ':' + (status.port || 'N/A');
                        html += ' | Chiffrement: ' + (status.encryption || 'N/A');
                        html += ' | De: ' + (status.from_address || 'N/A');
                        html += '</small>';
                    }
                    html += '</div>';
                    
                    $('#email-status-alert').html(html);
                    
                    // Mettre à jour aussi la carte de statut
                    if (status.configured && status.enabled) {
                        $('#email-status').html('<span class="badge badge-success">Configuré</span>');
                    } else if (status.configured) {
                        $('#email-status').html('<span class="badge badge-warning">Configuré (désactivé)</span>');
                    } else {
                        $('#email-status').html('<span class="badge badge-secondary">Non configuré</span>');
                    }
                }
            })
            .fail(function() {
                $('#email-status-alert').html('<div class="alert alert-danger">Erreur lors du chargement du statut</div>');
            });
    }
    
    function loadMailConfig() {
        // Charger les valeurs depuis les clés API (qui incluent maintenant les variables MAIL_*)
        $.get('{{ route("admin.api.keys") }}')
            .done(function(data) {
                if (data.success && data.keys) {
                    const keys = data.keys;
                    
                    // Ne remplir que si les champs sont vides (pour ne pas écraser les modifications en cours)
                    if (!$('#mail-host').val()) {
                        $('#mail-host').val(keys.MAIL_HOST || '');
                    }
                    if (!$('#mail-port').val() || $('#mail-port').val() === '587') {
                        $('#mail-port').val(keys.MAIL_PORT || '465');
                    }
                    if (keys.MAIL_ENCRYPTION) {
                        $('#mail-encryption').val(keys.MAIL_ENCRYPTION);
                    }
                    if (!$('#mail-username').val()) {
                        $('#mail-username').val(keys.MAIL_USERNAME || '');
                    }
                    // Ne pas remplir le mot de passe automatiquement
                    if (!$('#mail-from-address').val()) {
                        $('#mail-from-address').val(keys.MAIL_FROM_ADDRESS || '');
                    }
                    if (!$('#mail-from-name').val() || $('#mail-from-name').val() === 'BantuDelice') {
                        $('#mail-from-name').val(keys.MAIL_FROM_NAME || 'BantuDelice');
                    }
                    if (keys.MAIL_ENABLED) {
                        $('#mail-enabled').prop('checked', keys.MAIL_ENABLED === 'true' || keys.MAIL_ENABLED === true);
                    }
                }
            })
            .fail(function() {
                console.error('Erreur lors du chargement de la configuration email');
            });
    }
    
    function saveMailConfig() {
        const formData = {
            MAIL_HOST: $('#mail-host').val().trim(),
            MAIL_PORT: parseInt($('#mail-port').val()),
            MAIL_USERNAME: $('#mail-username').val().trim(),
            MAIL_PASSWORD: $('#mail-password').val(),
            MAIL_ENCRYPTION: $('#mail-encryption').val(),
            MAIL_FROM_ADDRESS: $('#mail-from-address').val().trim(),
            MAIL_FROM_NAME: $('#mail-from-name').val().trim(),
            MAIL_ENABLED: $('#mail-enabled').is(':checked'),
        };
        
        // Validation basique
        if (!formData.MAIL_HOST || !formData.MAIL_PORT || !formData.MAIL_USERNAME || !formData.MAIL_PASSWORD || !formData.MAIL_FROM_ADDRESS) {
            alert('Veuillez remplir tous les champs obligatoires');
            return;
        }
        
        showLoading('#mail-save-result');
        
        $.ajax({
            url: '{{ route("admin.api.mail.save") }}',
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                ...formData
            },
            success: function(data) {
                if (data.success) {
                    showResult('#mail-save-result', {
                        success: true,
                        message: data.message || 'Configuration sauvegardée avec succès!',
                    });
                    
                    // Recharger le statut
                    setTimeout(function() {
                        loadMailStatus();
                        clearCache();
                    }, 1000);
                } else {
                    showError('#mail-save-result', data.message || 'Erreur lors de la sauvegarde');
                }
            },
            error: function(xhr) {
                showError('#mail-save-result', xhr.responseJSON?.message || 'Erreur lors de la sauvegarde');
            }
        });
    }
    
    function testEmail() {
        const to = $('#test-email-to').val().trim();
        const subject = $('#test-email-subject').val().trim() || 'Test Email - BantuDelice';
        const message = $('#test-email-message').val().trim() || 'Ceci est un email de test depuis BantuDelice. Si vous recevez ce message, la configuration SMTP fonctionne correctement.';
        
        if (!to) {
            alert('Veuillez entrer une adresse email de destination');
            return;
        }
        
        if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(to)) {
            alert('Veuillez entrer une adresse email valide');
            return;
        }
        
        showLoading('#test-email-result');
        
        $.post('{{ route("admin.api.test.email") }}', {
            _token: '{{ csrf_token() }}',
            to: to,
            subject: subject,
            message: message
        })
        .done(function(data) {
            showResult('#test-email-result', data);
        })
        .fail(function(xhr) {
            showError('#test-email-result', xhr.responseJSON?.message || 'Erreur lors de l\'envoi de l\'email');
        });
    }
</script>
@endsection

