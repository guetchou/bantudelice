@extends('layouts.admin-modern')
@section('title', 'Créer un restaurant')
@section('page_title', 'Nouveau restaurant')
@section('nav_active', 'restaurants')

@section('style')
<style>
.rst-page { padding:24px; }
.rst-alert { padding:12px 16px; border-radius:8px; font-size:13px; font-weight:500; border:1px solid transparent; margin-bottom:16px; }
.rst-alert--danger { background:#fef2f2; color:#991b1b; border-color:#fecaca; }
.rst-card { background:#fff; border:1px solid #e5e7eb; border-radius:10px; overflow:hidden; }
.rst-card__header { background:#eff6ff; padding:14px 20px; border-bottom:1px solid #bfdbfe; display:flex; align-items:center; gap:8px; }
.rst-card__title { font-size:14px; font-weight:700; color:#1e40af; margin:0; }
.rst-card__body { padding:24px; }
.rst-card__footer { padding:14px 20px; border-top:1px solid #f3f4f6; display:flex; justify-content:flex-end; align-items:center; gap:10px; }
.rst-section-title { display:flex; align-items:center; gap:8px; padding:10px 14px; margin:24px 0 16px; background:#1e3a5f; color:#fff; border-radius:6px; font-size:13px; font-weight:700; }
.rst-form-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(200px,1fr)); gap:16px; }
.rst-form-grid--2 { grid-template-columns:1fr 1fr; }
.rst-field { display:flex; flex-direction:column; gap:5px; }
.rst-label { font-size:13px; font-weight:600; color:#374151; }
.rst-label sup { color:#ef4444; }
.rst-input { width:100%; padding:9px 12px; border:1px solid #d1d5db; border-radius:6px; font-size:14px; color:#111827; background:#fff; transition:border-color .15s,box-shadow .15s; box-sizing:border-box; }
.rst-input:focus { outline:none; border-color:#1e3a5f; box-shadow:0 0 0 3px rgba(30,58,95,.12); }
.rst-input--error { border-color:#ef4444; }
.rst-field-error { font-size:11px; color:#dc2626; font-weight:500; }
.rst-select { appearance:none; background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 20 20' fill='%236b7280'%3E%3Cpath fill-rule='evenodd' d='M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z'/%3E%3C/svg%3E"); background-repeat:no-repeat; background-position:right 10px center; background-size:18px; padding-right:36px; }
.rst-textarea { resize:vertical; min-height:80px; }
.rst-uploads { display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-bottom:16px; }
.rst-upload-zone { border:2px dashed #d1d5db; border-radius:8px; padding:20px 16px; text-align:center; cursor:pointer; transition:border-color .15s; }
.rst-upload-zone:hover { border-color:#1e3a5f; }
.rst-upload-zone img { width:90px; height:90px; object-fit:contain; }
.rst-upload-zone p { margin:8px 0 0; font-size:12px; color:#6b7280; }
.rst-upload-zone span { color:#f97316; font-weight:600; font-size:12px; }
.rst-btn-primary { display:inline-flex; align-items:center; gap:6px; padding:8px 18px; background:#1e3a5f; color:#fff; border:none; border-radius:6px; font-size:13px; font-weight:600; cursor:pointer; transition:opacity .15s; }
.rst-btn-primary:hover { opacity:.85; }
.rst-btn-cancel { display:inline-flex; align-items:center; padding:8px 16px; border-radius:6px; font-size:13px; font-weight:600; text-decoration:none; border:1px solid #d1d5db; color:#374151; background:#fff; transition:background .15s; }
.rst-btn-cancel:hover { background:#f9fafb; color:#111827; text-decoration:none; }
#map { width:100%; height:360px; border-radius:8px; border:1px solid #e5e7eb; margin-top:8px; }
@media (max-width:640px) { .rst-uploads, .rst-form-grid--2 { grid-template-columns:1fr; } }
</style>
@endsection

@section('content')
<div class="rst-page">

    @if(session()->has('alert'))
        <div class="rst-alert rst-alert--{{ session()->get('alert.type') }}">
            {{ session()->get('alert.message') }}
        </div>
    @endif
    @if($errors->any())
        <div class="rst-alert rst-alert--danger">Veuillez corriger les erreurs ci-dessous.</div>
    @endif

    <div class="rst-card">
        <div class="rst-card__header">
            <i class="fas fa-plus-circle"></i>
            <h2 class="rst-card__title">Ajouter un restaurant</h2>
        </div>

        <form role="form" method="post" action="{{ route('restaurant.store') }}" enctype="multipart/form-data">
            @csrf
            <div class="rst-card__body">

                {{-- Photos --}}
                <div class="rst-uploads">
                    <div>
                        <label class="rst-upload-zone" onclick="document.getElementById('file-input').click()">
                            <img src="{{ asset('images/home/service-restaurant.jpg') }}" id="logo" alt="logo">
                            <div>
                                <span><i class="fas fa-upload"></i> Logo</span>
                                <p>90×90 recommandé</p>
                            </div>
                        </label>
                        <input type="file" id="file-input" name="logo" style="display:none;" onchange="logo1(this);">
                        @if($errors->has('logo'))
                            <span class="rst-field-error">{{ $errors->first('logo') }}</span>
                        @endif
                        @include('partials.unified_media_select', [
                            'name' => 'logo_media_path',
                            'label' => 'Depuis la médiathèque',
                            'selected' => old('logo_media_path'),
                            'options' => $mediaLibraryOptions ?? [],
                            'previewTarget' => 'logo',
                        ])
                    </div>
                    <div>
                        <label class="rst-upload-zone" onclick="document.getElementById('upload_file').click()">
                            <img src="{{ asset('images/home/service-restaurant.jpg') }}" id="cover" alt="bannière">
                            <div>
                                <span><i class="fas fa-upload"></i> Bannière</span>
                                <p>320×220 recommandé</p>
                            </div>
                        </label>
                        <input type="file" id="upload_file" name="cover_image" style="display:none;" onchange="cover1(this);">
                        @if($errors->has('cover_image'))
                            <span class="rst-field-error">{{ $errors->first('cover_image') }}</span>
                        @endif
                        @include('partials.unified_media_select', [
                            'name' => 'cover_image_media_path',
                            'label' => 'Depuis la médiathèque',
                            'selected' => old('cover_image_media_path'),
                            'options' => $mediaLibraryOptions ?? [],
                            'previewTarget' => 'cover',
                        ])
                    </div>
                </div>

                {{-- Infos principales --}}
                <div class="rst-form-grid">
                    <div class="rst-field">
                        <label class="rst-label" for="name">Nom du restaurant <sup>*</sup></label>
                        <input type="text" class="rst-input {{ $errors->has('name') ? 'rst-input--error' : '' }}" id="name" value="{{ old('name') }}" name="name" placeholder="Ex : Restaurant Le Gourmet" required>
                        @if($errors->has('name'))
                            <span class="rst-field-error">{{ $errors->first('name') }}</span>
                        @endif
                    </div>
                    <div class="rst-field">
                        <label class="rst-label" for="slogan">Slogan</label>
                        <input type="text" class="rst-input {{ $errors->has('slogan') ? 'rst-input--error' : '' }}" name="slogan" id="slogan" value="{{ old('slogan') }}" placeholder="Ex : La qualité avant tout">
                        @if($errors->has('slogan'))
                            <span class="rst-field-error">{{ $errors->first('slogan') }}</span>
                        @endif
                    </div>
                    <div class="rst-field">
                        <label class="rst-label" for="select">Cuisines</label>
                        <select class="select2" id="select" name="cuisine_id" multiple style="width:100%;">
                            <option value="">Choisir...</option>
                            @foreach(\App\Cuisine::all() as $cuisine)
                                <option value="{{ $cuisine->id }}" {{ old('cuisine_id') == $cuisine->id ? 'selected' : '' }}>{{ $cuisine->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="rst-field">
                        <label class="rst-label" for="phone">Téléphone</label>
                        <input type="text" value="{{ old('phone') }}" name="phone" class="rst-input {{ $errors->has('phone') ? 'rst-input--error' : '' }}" id="phone">
                        @if($errors->has('phone'))
                            <span class="rst-field-error">{{ $errors->first('phone') }}</span>
                        @endif
                    </div>
                    <div class="rst-field">
                        <label class="rst-label" for="city">Ville <sup>*</sup></label>
                        <input type="text" value="{{ old('city') }}" name="city" class="rst-input {{ $errors->has('city') ? 'rst-input--error' : '' }}" id="city" placeholder="Ex : Brazzaville" required>
                        @if($errors->has('city'))
                            <span class="rst-field-error">{{ $errors->first('city') }}</span>
                        @endif
                    </div>
                </div>

                {{-- Adresse + carte --}}
                <div class="rst-field" style="margin-top:16px;">
                    <label class="rst-label">Adresse <sup>*</sup></label>
                    <input id="searchMapInput" name="address" class="rst-input" type="text" placeholder="Entrez une adresse" value="" required>
                    <input type="hidden" id="latitude" name="latitude" value="-4.2767">
                    <input type="hidden" id="longitude" name="longitude" value="15.2832">
                    <span id="address_err" style="font-size:11px;color:#dc2626;"></span>
                    <div id="map"></div>
                </div>

                {{-- Paramètres opérationnels --}}
                <div class="rst-form-grid" style="margin-top:16px;">
                    <div class="rst-field">
                        <label class="rst-label" for="min_order">Commande minimum (FCFA)</label>
                        <input type="number" value="{{ old('min_order') }}" name="min_order" class="rst-input" id="min_order" min="1" placeholder="Ex : 5000">
                    </div>
                    <div class="rst-field">
                        <label class="rst-label" for="avg_delivery_time">Temps de livraison moyen</label>
                        <select id="avg_delivery_time" class="rst-input rst-select" name="avg_delivery_time">
                            <option value="">Choisir...</option>
                            @foreach([10,20,30,40,50,60] as $min)
                                <option value="{{ $min }}">{{ $min }} minutes</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="rst-field">
                        <label class="rst-label" for="admin_commission">Commission admin (%)</label>
                        <input type="text" value="{{ old('admin_commission') }}" name="admin_commission" class="rst-input {{ $errors->has('admin_commission') ? 'rst-input--error' : '' }}" id="admin_commission" placeholder="Ex : 10, 15, 20">
                        @if($errors->has('admin_commission'))
                            <span class="rst-field-error">{{ $errors->first('admin_commission') }}</span>
                        @endif
                    </div>
                </div>

                {{-- Description --}}
                <div class="rst-field" style="margin-top:16px;">
                    <label class="rst-label" for="description">Description <sup>*</sup></label>
                    <textarea class="rst-input rst-textarea {{ $errors->has('description') ? 'rst-input--error' : '' }}" id="description" name="description" placeholder="Décrivez votre restaurant...">{{ old('description') }}</textarea>
                    @if($errors->has('description'))
                        <span class="rst-field-error">{{ $errors->first('description') }}</span>
                    @endif
                </div>

                {{-- Compte restaurant --}}
                <div class="rst-section-title">
                    <i class="fas fa-user-circle"></i> Compte restaurant
                </div>
                <div class="rst-form-grid">
                    <div class="rst-field">
                        <label class="rst-label" for="user_name">Nom d'utilisateur <sup>*</sup></label>
                        <input type="text" value="{{ old('user_name') }}" name="user_name" class="rst-input {{ $errors->has('user_name') ? 'rst-input--error' : '' }}" id="user_name" placeholder="Ex : restaurant_gourmet" required>
                        @if($errors->has('user_name'))
                            <span class="rst-field-error">{{ $errors->first('user_name') }}</span>
                        @endif
                    </div>
                    <div class="rst-field">
                        <label class="rst-label" for="email">Email <sup>*</sup></label>
                        <input type="email" value="{{ old('email') }}" name="email" class="rst-input {{ $errors->has('email') ? 'rst-input--error' : '' }}" id="email" placeholder="contact@restaurant.com" required>
                        @if($errors->has('email'))
                            <span class="rst-field-error">{{ $errors->first('email') }}</span>
                        @endif
                    </div>
                    <div class="rst-field">
                        <label class="rst-label" for="password">Mot de passe <sup>*</sup></label>
                        <input type="password" value="{{ old('password') }}" class="rst-input {{ $errors->has('password') ? 'rst-input--error' : '' }}" id="password" name="password" placeholder="Minimum 6 caractères" required>
                        @if($errors->has('password'))
                            <span class="rst-field-error">{{ $errors->first('password') }}</span>
                        @endif
                    </div>
                </div>

                {{-- Détails bancaires --}}
                <div class="rst-section-title">
                    <i class="fas fa-university"></i> Détails bancaires
                </div>
                <div class="rst-form-grid">
                    <div class="rst-field">
                        <label class="rst-label" for="bank_name">Nom de la banque <sup>*</sup></label>
                        <input type="text" value="{{ old('bank_name') }}" name="bank_name" class="rst-input {{ $errors->has('bank_name') ? 'rst-input--error' : '' }}" id="bank_name" placeholder="Ex : BGFI Bank" required>
                        @if($errors->has('bank_name'))
                            <span class="rst-field-error">{{ $errors->first('bank_name') }}</span>
                        @endif
                    </div>
                    <div class="rst-field">
                        <label class="rst-label" for="branch_name">Nom de l'agence <sup>*</sup></label>
                        <input type="text" value="{{ old('branch_name') }}" class="rst-input {{ $errors->has('branch_name') ? 'rst-input--error' : '' }}" id="branch_name" name="branch_name" placeholder="Ex : Agence Centre-Ville" required>
                        @if($errors->has('branch_name'))
                            <span class="rst-field-error">{{ $errors->first('branch_name') }}</span>
                        @endif
                    </div>
                    <div class="rst-field">
                        <label class="rst-label" for="account_name">Nom du titulaire <sup>*</sup></label>
                        <input type="text" value="{{ old('account_name') }}" name="account_name" class="rst-input {{ $errors->has('account_name') ? 'rst-input--error' : '' }}" id="account_name" placeholder="Ex : Restaurant Le Gourmet" required>
                        @if($errors->has('account_name'))
                            <span class="rst-field-error">{{ $errors->first('account_name') }}</span>
                        @endif
                    </div>
                    <div class="rst-field">
                        <label class="rst-label" for="account_number">Numéro de compte <sup>*</sup></label>
                        <input type="text" value="{{ old('account_number') }}" class="rst-input {{ $errors->has('account_number') ? 'rst-input--error' : '' }}" id="account_number" name="account_number" placeholder="Ex : 1234567890" required>
                        @if($errors->has('account_number'))
                            <span class="rst-field-error">{{ $errors->first('account_number') }}</span>
                        @endif
                    </div>
                </div>

            </div>
            <div class="rst-card__footer">
                <a href="{{ route('restaurant.index') }}" class="rst-btn-cancel">
                    <i class="fas fa-times"></i> Annuler
                </a>
                <button type="submit" class="rst-btn-primary">
                    <i class="fas fa-save"></i> Ajouter
                </button>
            </div>
        </form>
    </div>

</div>
@endsection

@section('script')
<script src="{{ asset('plugins/select2/js/select2.full.min.js') }}"></script>
<script src="{{ asset('plugins/bootstrap-colorpicker/js/bootstrap-colorpicker.min.js') }}"></script>
<script>
function initMap() {
    var lati = document.getElementById('latitude').value;
    var long = document.getElementById('longitude').value;
    var myLatlng = new google.maps.LatLng(Number(lati), Number(long));
    var geocoder = new google.maps.Geocoder();
    var map = new google.maps.Map(document.getElementById('map'), {
        center: { lat: Number(lati), lng: Number(long) },
        zoom: 13
    });
    var input = document.getElementById('searchMapInput');
    var autocomplete = new google.maps.places.Autocomplete(input);
    autocomplete.bindTo('bounds', map);
    var infowindow = new google.maps.InfoWindow();
    var marker = new google.maps.Marker({ position: myLatlng, map: map, draggable: true });

    autocomplete.addListener('place_changed', function () {
        marker.setVisible(true);
        var place = autocomplete.getPlace();
        if (place.geometry.viewport) {
            map.fitBounds(place.geometry.viewport);
        } else {
            map.setCenter(place.geometry.location);
            map.setZoom(17);
        }
        marker.setPosition(place.geometry.location);
        marker.setVisible(true);
        document.getElementById('latitude').value = place.geometry.location.lat();
        document.getElementById('longitude').value = place.geometry.location.lng();
        infowindow.setContent('<div><strong>' + place.name + '</strong></div>');
        infowindow.open(map, marker);
    });

    google.maps.event.addListener(marker, 'dragend', function (marker) {
        var latLng = marker.latLng;
        var currentLatitude = latLng.lat();
        var currentLongitude = latLng.lng();
        geocoder.geocode({ 'latLng': latLng }, function (results, status) {
            if (status == google.maps.GeocoderStatus.OK && results[0]) {
                document.getElementById('searchMapInput').value = results[0].formatted_address;
                document.getElementById('latitude').value = currentLatitude;
                document.getElementById('longitude').value = currentLongitude;
            }
        });
    });
}

function cover1(input) {
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function (e) { document.getElementById('cover').src = e.target.result; };
        reader.readAsDataURL(input.files[0]);
    }
}
function logo1(input) {
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function (e) { document.getElementById('logo').src = e.target.result; };
        reader.readAsDataURL(input.files[0]);
    }
}

document.querySelectorAll('.js-unified-media-select').forEach(function (select) {
    select.addEventListener('change', function () {
        var previewTarget = this.dataset.previewTarget;
        var option = this.options[this.selectedIndex];
        var previewUrl = option ? option.dataset.preview : '';
        if (previewTarget && previewUrl) {
            document.getElementById(previewTarget).src = previewUrl;
        }
    });
});

$(function () {
    $('.select2').select2();
});
</script>
<script src="https://maps.googleapis.com/maps/api/js?key={{ env('GOOGLE_MAPS_API_KEY') }}&libraries=places&callback=initMap" async defer></script>
@endsection
