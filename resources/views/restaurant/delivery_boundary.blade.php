@extends('layouts.restaurant_app')
@section('title', 'Zone de livraison | ' . \App\Services\ConfigService::getCompanyName())
@section('topbar_title', 'Zone de livraison')
@section('delivery_boundary_nav', 'active')

@section('content')
@php
    $restaurant  = auth()->user()->restaurant()->first();
    $centerLat   = (float) ($restaurant->latitude  ?? -4.2767);
    $centerLng   = (float) ($restaurant->longitude ?? 15.2832);
    $deliveryKm  = (float) ($restaurant->delivery_radius ?? 5);
@endphp

<div style="display:flex;flex-direction:column;gap:20px;">

    <div style="background:var(--bd-surface);border:1px solid var(--bd-border);border-radius:var(--bd-radius);overflow:hidden;">
        <div style="padding:14px 20px;border-bottom:1px solid var(--bd-border-2);display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;">
            <div>
                <div style="font-size:13px;font-weight:700;color:var(--bd-text);">Zone de livraison active</div>
                <div style="font-size:11px;color:var(--bd-text-3);margin-top:2px;">
                    {{ $restaurant->address ?? 'Adresse non définie' }}
                    @if($centerLat && $centerLng)
                        &nbsp;·&nbsp; {{ $centerLat }}, {{ $centerLng }}
                    @endif
                </div>
            </div>
            <div style="display:flex;align-items:center;gap:10px;">
                <span style="display:inline-flex;align-items:center;gap:5px;padding:5px 12px;border-radius:999px;background:rgba(0,149,67,.1);color:var(--bd-green);font-size:12px;font-weight:700;">
                    <i class="fas fa-circle" style="font-size:7px;"></i>
                    Rayon {{ $deliveryKm }} km
                </span>
                <a href="{{ route('restaurant.profile') }}"
                   style="display:inline-flex;align-items:center;gap:5px;padding:6px 12px;border-radius:var(--bd-radius);border:1px solid var(--bd-border);background:var(--bd-surface);color:var(--bd-text-2);font-size:12px;font-weight:600;text-decoration:none;transition:.12s;"
                   onmouseover="this.style.borderColor='var(--bd-green)';this.style.color='var(--bd-green)';"
                   onmouseout="this.style.borderColor='var(--bd-border)';this.style.color='var(--bd-text-2)';">
                    <i class="fas fa-sliders"></i> Configurer
                </a>
            </div>
        </div>

        @if($centerLat && $centerLng)
            <div id="map" style="height:460px;width:100%;"></div>
        @else
            <div style="padding:48px 20px;text-align:center;color:var(--bd-text-3);">
                <i class="fas fa-map-marker-alt" style="font-size:28px;display:block;margin-bottom:12px;color:var(--bd-border);"></i>
                <div style="font-size:13px;margin-bottom:14px;">Coordonnées du restaurant non configurées.</div>
                <a href="{{ route('restaurant.profile') }}"
                   style="display:inline-flex;align-items:center;gap:6px;padding:8px 16px;border-radius:var(--bd-radius);background:var(--bd-green);color:#fff;font-size:12px;font-weight:700;text-decoration:none;">
                    <i class="fas fa-sliders"></i> Configurer le profil
                </a>
            </div>
        @endif
    </div>

</div>
@endsection

@section('script')
@php $googleMapsKey = env('GOOGLE_MAPS_API_KEY', ''); @endphp
@if(!empty($googleMapsKey) && $centerLat && $centerLng)
<script>
    function initMap() {
        var center = { lat: {{ $centerLat }}, lng: {{ $centerLng }} };
        var radiusMeters = {{ $deliveryKm * 1000 }};

        var map = new google.maps.Map(document.getElementById('map'), {
            zoom: 13,
            center: center,
            mapTypeId: google.maps.MapTypeId.ROADMAP,
            disableDefaultUI: false,
        });

        new google.maps.Marker({
            position: center,
            map: map,
            title: '{{ addslashes($restaurant->name ?? "Restaurant") }}'
        });

        new google.maps.Circle({
            map: map,
            center: center,
            radius: radiusMeters,
            strokeColor: '#009543',
            strokeOpacity: 0.8,
            strokeWeight: 2,
            fillColor: '#009543',
            fillOpacity: 0.12
        });
    }
</script>
<script async defer src="https://maps.googleapis.com/maps/api/js?key={{ $googleMapsKey }}&callback=initMap"></script>
@else
<script>
    var mapEl = document.getElementById('map');
    if (mapEl) mapEl.innerHTML = '<div style="padding:2rem;text-align:center;color:var(--bd-text-3,#9ca3af);font-size:13px;">Carte indisponible — clé Google Maps non configurée.</div>';
</script>
@endif
@endsection
