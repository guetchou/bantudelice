@extends('layouts.admin-modern')
@section('title', 'Modifier le restaurant')
@section('page_title', 'Modifier restaurant')
@section('nav_active', 'restaurants')

@section('style')
<link rel="stylesheet" href="{{ asset('plugins/select2/css/select2.min.css') }}">
<style>
.rst-page { padding:24px; }
.rst-card { background:#fff; border:1px solid #e5e7eb; border-radius:10px; overflow:hidden; margin-bottom:20px; }
.rst-card__header { padding:14px 20px; border-bottom:1px solid #f3f4f6; }
.rst-card__title { font-size:14px; font-weight:700; color:#111827; margin:0; }
.rst-card__body { padding:20px; }
.rst-card__footer { padding:14px 20px; border-top:1px solid #f3f4f6; display:flex; justify-content:flex-end; gap:10px; }
.rst-section-title { display:flex; align-items:center; gap:8px; padding:10px 14px; background:#1e3a5f; color:#fff; border-radius:6px; font-size:13px; font-weight:700; margin:20px 0 14px; }
.rst-upload-grid { display:grid; grid-template-columns:1fr 1fr; gap:20px; margin-bottom:20px; }
.rst-upload-zone { border:2px dashed #d1d5db; border-radius:8px; padding:20px; text-align:center; cursor:pointer; transition:border-color .15s; }
.rst-upload-zone:hover { border-color:#1e3a5f; }
.rst-upload-zone img { width:80px; height:80px; object-fit:contain; border-radius:4px; }
.rst-upload-hint { font-size:11px; color:#9ca3af; margin-top:6px; }
.rst-upload-hint span { color:#ff5a1f; font-weight:600; }
.rst-field { display:flex; flex-direction:column; gap:5px; margin-bottom:14px; }
.rst-field:last-child { margin-bottom:0; }
.rst-label { font-size:13px; font-weight:600; color:#374151; }
.rst-input { width:100%; padding:9px 12px; border:1px solid #d1d5db; border-radius:6px; font-size:14px; color:#111827; background:#fff; transition:border-color .15s; box-sizing:border-box; }
.rst-input:focus { outline:none; border-color:#1e3a5f; box-shadow:0 0 0 3px rgba(30,58,95,.12); }
.rst-select { appearance:none; background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 20 20' fill='%236b7280'%3E%3Cpath fill-rule='evenodd' d='M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z'/%3E%3C/svg%3E"); background-repeat:no-repeat; background-position:right 10px center; background-size:18px; padding-right:36px; }
.rst-form-grid { display:grid; gap:14px; }
.rst-form-grid--2 { grid-template-columns:1fr 1fr; }
.rst-form-grid--3 { grid-template-columns:2fr 1fr 1fr; }
.rst-form-grid--4 { grid-template-columns:1fr 1fr 1fr 1fr; }
.rst-err { font-size:12px; color:#dc2626; margin-top:3px; }
#map { width:100%; height:380px; border-radius:6px; border:1px solid #e5e7eb; }
.rst-btn-primary { display:inline-flex; align-items:center; gap:6px; padding:9px 20px; background:#1e3a5f; color:#fff; border:none; border-radius:6px; font-size:13px; font-weight:600; cursor:pointer; transition:opacity .15s; }
.rst-btn-primary:hover { opacity:.85; }
.rst-btn-cancel { display:inline-flex; align-items:center; padding:9px 20px; border:1px solid #d1d5db; border-radius:6px; font-size:13px; font-weight:600; text-decoration:none; color:#374151; background:#fff; transition:background .15s; }
.rst-btn-cancel:hover { background:#f9fafb; color:#111827; text-decoration:none; }
@media (max-width:768px) {
    .rst-upload-grid { grid-template-columns:1fr; }
    .rst-form-grid--2, .rst-form-grid--3, .rst-form-grid--4 { grid-template-columns:1fr; }
}
</style>
@endsection

@section('content')
<div class="rst-page">
    <form role="form" method="post" action="{{ route('restaurant.update', $restaurant->id) }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        {{-- Upload images --}}
        <div class="rst-card">
            <div class="rst-card__header">
                <h3 class="rst-card__title">Images du restaurant</h3>
            </div>
            <div class="rst-card__body">
                <div class="rst-upload-grid">
                    <div>
                        <label for="file-input" class="rst-upload-zone" style="display:block;">
                            <img src="{{ method_exists($restaurant, 'publicIdentityImageUrl') ? $restaurant->publicIdentityImageUrl() : url('images/restaurant_images', $restaurant->logo) }}" id="logo" alt="logo">
                            <div class="rst-upload-hint"><span><i class="fas fa-upload"></i> Télécharger le logo</span><br>Recommandé 90×90 · jpg, png, webp · max 8 Mo</div>
                        </label>
                        <input type="file" id="file-input" name="logo" onchange="logo1(this);" style="display:none;">
                        @if($errors->has('logo'))
                            <div class="rst-err">{{ $errors->first('logo') }}</div>
                        @endif
                        @include('partials.unified_media_select', [
                            'name'          => 'logo_media_path',
                            'label'         => 'Ou choisir le logo depuis la médiathèque CMS',
                            'selected'      => old('logo_media_path'),
                            'options'       => $mediaLibraryOptions ?? [],
                            'previewTarget' => 'logo',
                        ])
                        @if($errors->has('logo_media_path'))
                            <div class="rst-err">{{ $errors->first('logo_media_path') }}</div>
                        @endif
                    </div>
                    <div>
                        <label for="upload_file" class="rst-upload-zone" style="display:block;">
                            <img src="{{ method_exists($restaurant, 'publicCoverImageUrl') ? $restaurant->publicCoverImageUrl() : url('images/restaurant_images', $restaurant->cover_image) }}" id="cover" alt="cover">
                            <div class="rst-upload-hint"><span><i class="fas fa-upload"></i> Télécharger la bannière</span><br>Recommandé 320×220 · jpg, png, webp · max 8 Mo</div>
                        </label>
                        <input type="file" id="upload_file" name="cover_image" onchange="cover1(this);" style="display:none;">
                        @if($errors->has('cover_image'))
                            <div class="rst-err">{{ $errors->first('cover_image') }}</div>
                        @endif
                        @include('partials.unified_media_select', [
                            'name'          => 'cover_image_media_path',
                            'label'         => 'Ou choisir la bannière depuis la médiathèque CMS',
                            'selected'      => old('cover_image_media_path'),
                            'options'       => $mediaLibraryOptions ?? [],
                            'previewTarget' => 'cover',
                        ])
                        @if($errors->has('cover_image_media_path'))
                            <div class="rst-err">{{ $errors->first('cover_image_media_path') }}</div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- Informations principales --}}
        <div class="rst-card">
            <div class="rst-card__header">
                <h3 class="rst-card__title">Informations principales</h3>
            </div>
            <div class="rst-card__body">
                <div class="rst-form-grid rst-form-grid--2" style="margin-bottom:14px;">
                    <div class="rst-field">
                        <label class="rst-label">Nom du restaurant</label>
                        <input type="text" name="name" value="{{ old('name', $restaurant->name) }}" class="rst-input">
                        @if($errors->has('name'))
                            <div class="rst-err">{{ $errors->first('name') }}</div>
                        @endif
                    </div>
                    <div class="rst-field">
                        <label class="rst-label">Slogan</label>
                        <input type="text" name="slogan" value="{{ old('slogan', $restaurant->slogan) }}" class="rst-input">
                        @if($errors->has('slogan'))
                            <div class="rst-err">{{ $errors->first('slogan') }}</div>
                        @endif
                    </div>
                </div>

                <div class="rst-form-grid rst-form-grid--3" style="margin-bottom:14px;">
                    <div class="rst-field">
                        <label class="rst-label">Cuisines</label>
                        <select class="select2 rst-input" name="cuisine_id[]" multiple style="width:100%;">
                            <option value="">Choisir...</option>
                            @foreach(\App\Cuisine::all() as $cuisine)
                                <option value="{{ $cuisine->id }}" {{ $restaurant->hasCuisine($cuisine->id) ? 'selected' : '' }}>
                                    {{ $cuisine->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="rst-field">
                        <label class="rst-label">Téléphone</label>
                        <input type="text" name="phone" value="{{ old('phone', $restaurant->phone) }}" class="rst-input">
                        @if($errors->has('phone'))
                            <div class="rst-err">{{ $errors->first('phone') }}</div>
                        @endif
                    </div>
                    <div class="rst-field">
                        <label class="rst-label">Ville</label>
                        <input type="text" name="city" value="{{ old('city', $restaurant->city) }}" class="rst-input">
                        @if($errors->has('city'))
                            <div class="rst-err">{{ $errors->first('city') }}</div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- Adresse + Google Maps --}}
        <div class="rst-card">
            <div class="rst-card__header">
                <h3 class="rst-card__title">Adresse &amp; localisation</h3>
            </div>
            <div class="rst-card__body">
                <div class="rst-field" style="margin-bottom:14px;">
                    <label class="rst-label">Adresse <span style="color:red;">*</span></label>
                    <input id="searchMapInput" name="address" type="text" class="rst-input" placeholder="Entrer une adresse" value="{{ old('address', $restaurant->address) }}" required>
                    <input type="hidden" id="latitude" name="latitude" value="{{ $restaurant->latitude }}">
                    <input type="hidden" id="longitude" name="longitude" value="{{ $restaurant->longitude }}">
                    <span id="address_err" style="font-size:12px;color:#dc2626;"></span>
                </div>
                <div id="map"></div>
            </div>
        </div>

        {{-- Paramètres opérationnels --}}
        <div class="rst-card">
            <div class="rst-card__header">
                <h3 class="rst-card__title">Paramètres opérationnels</h3>
            </div>
            <div class="rst-card__body">
                <div class="rst-form-grid rst-form-grid--3">
                    <div class="rst-field">
                        <label class="rst-label">Commande minimum (FCFA)</label>
                        <input type="number" name="min_order" value="{{ old('min_order', $restaurant->min_order) }}" min="1" class="rst-input">
                    </div>
                    <div class="rst-field">
                        <label class="rst-label">Temps de livraison moyen (min)</label>
                        <select name="avg_delivery_time" class="rst-input rst-select">
                            <option value="">Choisir...</option>
                            @foreach([10,20,30,40,50,60] as $t)
                                <option value="{{ $t }}" {{ (int)old('avg_delivery_time', $restaurant->avg_delivery_time) === $t ? 'selected' : '' }}>{{ $t }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="rst-field">
                        <label class="rst-label">Commission admin (%)</label>
                        <input type="number" name="admin_commission" value="{{ old('admin_commission', $restaurant->admin_commission ?? '') }}" min="0" max="100" step="0.01" class="rst-input">
                    </div>
                </div>

                <div class="rst-field">
                    <label class="rst-label">Description</label>
                    <input type="text" name="description" value="{{ old('description', $restaurant->description) }}" class="rst-input">
                    @if($errors->has('description'))
                        <div class="rst-err">{{ $errors->first('description') }}</div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Compte restaurant --}}
        <div class="rst-card">
            <div class="rst-card__header">
                <h3 class="rst-card__title">Compte restaurant</h3>
            </div>
            <div class="rst-card__body">
                <div class="rst-form-grid rst-form-grid--3">
                    <div class="rst-field">
                        <label class="rst-label">Identifiant</label>
                        <input type="text" name="user_name" value="{{ old('user_name', $restaurant->user_name) }}" class="rst-input">
                        @if($errors->has('user_name'))
                            <div class="rst-err">{{ $errors->first('user_name') }}</div>
                        @endif
                    </div>
                    <div class="rst-field">
                        <label class="rst-label">Email</label>
                        <input type="email" name="email" value="{{ old('email', $restaurant->email) }}" class="rst-input">
                        @if($errors->has('email'))
                            <div class="rst-err">{{ $errors->first('email') }}</div>
                        @endif
                    </div>
                    <div class="rst-field">
                        <label class="rst-label">Mot de passe <span style="font-weight:400;color:#9ca3af;">(laisser vide pour conserver)</span></label>
                        <input type="password" name="password" value="" class="rst-input" autocomplete="new-password">
                        @if($errors->has('password'))
                            <div class="rst-err">{{ $errors->first('password') }}</div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- Détails bancaires --}}
        <div class="rst-card">
            <div class="rst-card__header">
                <h3 class="rst-card__title"><i class="fas fa-university" style="margin-right:6px;"></i>Détails bancaires</h3>
            </div>
            <div class="rst-card__body">
                <div class="rst-form-grid rst-form-grid--2" style="margin-bottom:14px;">
                    <div class="rst-field">
                        <label class="rst-label">Nom de la banque</label>
                        <input type="text" name="bank_name" value="{{ old('bank_name', $restaurant->bank_name) }}" class="rst-input">
                        @if($errors->has('bank_name'))
                            <div class="rst-err">{{ $errors->first('bank_name') }}</div>
                        @endif
                    </div>
                    <div class="rst-field">
                        <label class="rst-label">Numéro d'agence</label>
                        <input type="text" name="branch_name" value="{{ old('branch_name', $restaurant->branch_name) }}" class="rst-input">
                        @if($errors->has('branch_name'))
                            <div class="rst-err">{{ $errors->first('branch_name') }}</div>
                        @endif
                    </div>
                </div>
                <div class="rst-form-grid rst-form-grid--2">
                    <div class="rst-field">
                        <label class="rst-label">Nom du titulaire</label>
                        <input type="text" name="account_name" value="{{ old('account_name', $restaurant->account_name) }}" class="rst-input">
                        @if($errors->has('account_name'))
                            <div class="rst-err">{{ $errors->first('account_name') }}</div>
                        @endif
                    </div>
                    <div class="rst-field">
                        <label class="rst-label">Numéro de compte</label>
                        <input type="text" name="account_number" value="{{ old('account_number', $restaurant->account_number) }}" class="rst-input">
                        @if($errors->has('account_number'))
                            <div class="rst-err">{{ $errors->first('account_number') }}</div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- Footer --}}
        <div class="rst-card">
            <div class="rst-card__footer">
                <a href="{{ route('restaurant.index') }}" class="rst-btn-cancel">Annuler</a>
                <button type="submit" class="rst-btn-primary"><i class="fas fa-save"></i> Mettre à jour le restaurant</button>
            </div>
        </div>

    </form>
</div>
@endsection

@section('script')
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
    var marker = new google.maps.Marker({
        position: myLatlng,
        map: map,
        draggable: true
    });
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
        var address = '';
        if (place.address_components) {
            address = [
                (place.address_components[0] && place.address_components[0].short_name || ''),
                (place.address_components[1] && place.address_components[1].short_name || ''),
                (place.address_components[2] && place.address_components[2].short_name || '')
            ].join(' ');
        }
        document.getElementById('latitude').value = place.geometry.location.lat();
        document.getElementById('longitude').value = place.geometry.location.lng();
        infowindow.setContent('<div><strong>' + place.name + '</strong><br>' + address);
        infowindow.open(map, marker);
        fun_check_restaurant(place.geometry.location.lat(), place.geometry.location.lng());
    });
    google.maps.event.addListener(marker, 'dragend', function (marker) {
        var latLng = marker.latLng;
        var currentLatitude = latLng.lat();
        var currentLongitude = latLng.lng();
        geocoder.geocode({ 'latLng': latLng }, function (results, status) {
            if (status == google.maps.GeocoderStatus.OK) {
                if (results[0]) {
                    document.getElementById('searchMapInput').value = results[0].formatted_address;
                    document.getElementById('latitude').value = currentLatitude;
                    document.getElementById('longitude').value = currentLongitude;
                    infowindow.setContent('<div>' + results[0].formatted_address + '<br>');
                    infowindow.open(map, marker);
                }
            }
        });
    });
}
</script>
<script src="https://maps.googleapis.com/maps/api/js?key={{ env('GOOGLE_MAPS_API_KEY') }}&libraries=places&callback=initMap" async defer></script>
<script src="{{ asset('plugins/select2/js/select2.full.min.js') }}"></script>
<script>
function cover1(input) {
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function (e) { $('#cover').attr('src', e.target.result); };
        reader.readAsDataURL(input.files[0]);
    }
}
function logo1(input) {
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function (e) { $('#logo').attr('src', e.target.result); };
        reader.readAsDataURL(input.files[0]);
    }
}
document.querySelectorAll('.js-unified-media-select').forEach(function (select) {
    select.addEventListener('change', function () {
        var previewTarget = this.dataset.previewTarget;
        var option = this.options[this.selectedIndex];
        var previewUrl = option ? option.dataset.preview : '';
        if (previewTarget && previewUrl) { $('#' + previewTarget).attr('src', previewUrl); }
    });
});
$(function () { $('.select2').select2(); });
</script>
@endsection
