@extends('layouts.restaurant_app')
@section('title', 'Détails de la commande | ' . \App\Services\ConfigService::getCompanyName())
@section('topbar_title', 'Détails de la commande')
@section('order_nav', 'active')
@section('order_nav_open', 'menu-open')

@section('style')
<style>
.sord { display: flex; flex-direction: column; gap: 20px; }

.sord-actors { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; }
@media (max-width: 900px) { .sord-actors { grid-template-columns: 1fr 1fr; } }
@media (max-width: 580px) { .sord-actors { grid-template-columns: 1fr; } }

.sord-card {
    background: var(--bd-surface); border: 1px solid var(--bd-border);
    border-radius: var(--bd-radius); overflow: hidden;
}
.sord-card__accent { height: 3px; border-radius: var(--bd-radius) var(--bd-radius) 0 0; }
.sord-card__accent--blue   { background: #3b82f6; }
.sord-card__accent--amber  { background: #f59e0b; }
.sord-card__accent--green  { background: var(--bd-green); }
.sord-card__body { padding: 20px 18px; display: flex; flex-direction: column; align-items: center; gap: 8px; }

.sord-avatar {
    width: 68px; height: 68px; border-radius: 50%;
    object-fit: cover; border: 2px solid var(--bd-border);
    background: var(--bd-surface-2);
}
.sord-avatar--blue  { border-color: #3b82f6; }
.sord-avatar--amber { border-color: #f59e0b; }
.sord-avatar--green { border-color: var(--bd-green); }

.sord-actor-name { font-size: 14px; font-weight: 700; color: var(--bd-text); text-align: center; margin: 0; }
.sord-actor-role { font-size: 11px; color: var(--bd-text-3); text-align: center; }

.sord-info-list { width: 100%; border-top: 1px solid var(--bd-border-2); margin-top: 6px; }
.sord-info-row {
    display: flex; justify-content: space-between; align-items: flex-start;
    padding: 7px 0; border-bottom: 1px solid var(--bd-border-2); gap: 8px;
}
.sord-info-row:last-child { border-bottom: none; }
.sord-info-label { font-size: 11px; font-weight: 700; color: var(--bd-text-3); white-space: nowrap; }
.sord-info-value { font-size: 11px; color: var(--bd-text-2); text-align: right; word-break: break-all; }

.sord-cta {
    display: inline-flex; align-items: center; justify-content: center; gap: 5px;
    width: 100%; padding: 8px 14px; border-radius: var(--bd-radius);
    font-size: 12px; font-weight: 700; border: none; cursor: pointer;
    text-decoration: none; margin-top: 4px; transition: .12s;
}
.sord-cta--blue   { background: #3b82f6; color: #fff; }
.sord-cta--blue:hover   { background: #2563eb; color: #fff; }
.sord-cta--amber  { background: #f59e0b; color: #fff; }
.sord-cta--amber:hover  { background: #d97706; color: #fff; }
.sord-cta--green  { background: var(--bd-green); color: #fff; }
.sord-cta--green:hover  { background: var(--bd-green-dark, #007836); color: #fff; }
.sord-cta--ghost  { background: var(--bd-surface-2); color: var(--bd-text-3); border: 1px solid var(--bd-border); cursor: not-allowed; opacity: .7; }

.sord-main { display: grid; grid-template-columns: 1fr 280px; gap: 16px; }
@media (max-width: 860px) { .sord-main { grid-template-columns: 1fr; } }

.sord-table-wrap { overflow-x: auto; }
.sord-table { width: 100%; border-collapse: collapse; font-size: 13px; }
.sord-table thead th {
    padding: 8px 14px; font-size: 10px; font-weight: 700;
    letter-spacing: .06em; text-transform: uppercase;
    color: var(--bd-text-3); border-bottom: 1px solid var(--bd-border-2);
    background: var(--bd-surface-2); text-align: left; white-space: nowrap;
}
.sord-table tbody tr { border-bottom: 1px solid var(--bd-border-2); transition: background .1s; }
.sord-table tbody tr:last-child { border-bottom: none; }
.sord-table tbody tr:hover { background: var(--bd-surface-2); }
.sord-table td { padding: 10px 14px; color: var(--bd-text-2); vertical-align: middle; }
.sord-prod-img { width: 48px; height: 48px; border-radius: 8px; object-fit: cover; background: var(--bd-surface-2); border: 1px solid var(--bd-border-2); }
.sord-amount { font-family: var(--bd-font-display,'League Spartan',sans-serif); font-size: 13px; font-weight: 800; color: var(--bd-text); white-space: nowrap; }
.sord-amount-cur { font-size: 10px; font-weight: 600; color: var(--bd-text-3); font-family: var(--bd-font); }

.sord-card__head { padding: 14px 18px; border-bottom: 1px solid var(--bd-border-2); font-size: 13px; font-weight: 700; color: var(--bd-text); display: flex; align-items: center; gap: 8px; }
.sord-card__head i { color: var(--bd-text-3); font-size: 13px; }

.sord-sidebar { display: flex; flex-direction: column; gap: 14px; }
.sord-meta { display: flex; flex-direction: column; gap: 0; }
.sord-meta-row {
    display: flex; justify-content: space-between; align-items: flex-start;
    padding: 10px 16px; border-bottom: 1px solid var(--bd-border-2); gap: 8px;
}
.sord-meta-row:last-child { border-bottom: none; }
.sord-meta-label { font-size: 11px; font-weight: 700; color: var(--bd-text-3); }
.sord-meta-value { font-size: 12px; color: var(--bd-text); font-weight: 600; text-align: right; }

.sord-status-badge {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 3px 10px; border-radius: 999px;
    font-size: 11px; font-weight: 700;
    background: var(--bd-surface-2); color: var(--bd-text-3); border: 1px solid var(--bd-border);
}
.sord-status-badge--pending    { background: rgba(245,158,11,.1); color: #d97706; border-color: rgba(245,158,11,.3); }
.sord-status-badge--processing { background: rgba(59,130,246,.1); color: #2563eb; border-color: rgba(59,130,246,.3); }
.sord-status-badge--delivered  { background: rgba(0,149,67,.1); color: var(--bd-green); border-color: rgba(0,149,67,.3); }
.sord-status-badge--completed  { background: rgba(0,149,67,.1); color: var(--bd-green); border-color: rgba(0,149,67,.3); }
.sord-status-badge--cancelled  { background: rgba(220,38,38,.1); color: #dc2626; border-color: rgba(220,38,38,.3); }

.sord-dist { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-top: 4px; }
.sord-dist-card {
    background: var(--bd-surface-2); border: 1px solid var(--bd-border);
    border-radius: var(--bd-radius); padding: 12px 14px;
}
.sord-dist-label { font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: .05em; color: var(--bd-text-3); margin-bottom: 4px; }
.sord-dist-value { font-family: var(--bd-font-display,'League Spartan',sans-serif); font-size: 16px; font-weight: 800; color: var(--bd-text); }

.sord-refuse { background: var(--bd-surface); border: 1px solid rgba(220,38,38,.3); border-radius: var(--bd-radius); overflow: hidden; }
.sord-refuse__head { padding: 12px 18px; border-bottom: 1px solid rgba(220,38,38,.2); background: rgba(220,38,38,.05); font-size: 12px; font-weight: 700; color: #dc2626; display: flex; align-items: center; gap: 6px; }
.sord-refuse__body { padding: 16px 18px; display: flex; flex-direction: column; gap: 12px; }
.sord-field-label { font-size: 12px; font-weight: 600; color: var(--bd-text); margin-bottom: 5px; }
.sord-select, .sord-textarea {
    width: 100%; box-sizing: border-box;
    padding: 9px 12px; border: 1px solid var(--bd-border);
    border-radius: var(--bd-radius); font-size: 13px;
    font-family: var(--bd-font); background: var(--bd-surface);
    color: var(--bd-text); outline: none; transition: border-color .12s;
}
.sord-select:focus, .sord-textarea:focus { border-color: var(--bd-green); }
.sord-textarea { resize: vertical; }
.sord-btn-refuse {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 9px 18px; border-radius: var(--bd-radius);
    background: #dc2626; color: #fff; font-size: 12px; font-weight: 700;
    border: none; cursor: pointer; font-family: var(--bd-font); transition: .12s;
}
.sord-btn-refuse:hover { background: #b91c1c; }

.sord-chat { background: var(--bd-surface); border: 1px solid var(--bd-border); border-radius: var(--bd-radius); overflow: hidden; }
.sord-chat__head { padding: 12px 18px; border-bottom: 1px solid var(--bd-border-2); font-size: 12px; font-weight: 700; color: var(--bd-text); display: flex; align-items: center; gap: 8px; }
.sord-unread { display: inline-flex; align-items: center; justify-content: center; min-width: 18px; height: 18px; padding: 0 5px; border-radius: 999px; background: #dc2626; color: #fff; font-size: 10px; font-weight: 700; }
</style>
@endsection

@section('content')
@php
    $googleMapsKey = env('GOOGLE_MAPS_API_KEY', '');
    $logo = $order->restaurant->logo ?? null;
    $logoSrc = $logo
        ? (str_starts_with($logo, 'http') ? $logo : asset('images/restaurant_images/' . $logo))
        : asset('images/placeholder.png');
    $statusClass = match($order->status ?? '') {
        'pending'    => 'sord-status-badge--pending',
        'processing' => 'sord-status-badge--processing',
        'delivered'  => 'sord-status-badge--delivered',
        'completed'  => 'sord-status-badge--completed',
        'cancelled'  => 'sord-status-badge--cancelled',
        default      => '',
    };
    $userPhoneLink   = preg_replace('/[^0-9+]/', '', (string) ($order->user->phone ?? ''));
    $driverPhoneLink = preg_replace('/[^0-9+]/', '', (string) ($order->driver->phone ?? ''));
@endphp

<div class="sord">

    @if(session()->has('alert'))
        <div class="alert alert-{{ session()->get('alert.type') }} alert-dismissible" role="alert">
            {{ session()->get('alert.message') }}
            <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
        </div>
    @endif

    {{-- ── Acteurs ─────────────────────────────────────────── --}}
    <div class="sord-actors">

        {{-- Restaurant --}}
        <div class="sord-card">
            <div class="sord-card__accent sord-card__accent--blue"></div>
            <div class="sord-card__body">
                <img class="sord-avatar sord-avatar--blue" src="{{ $logoSrc }}" alt="Logo restaurant"
                     onerror="this.src='{{ asset('images/placeholder.png') }}'">
                <p class="sord-actor-name">{{ $order->restaurant->name ?? '—' }}</p>
                <span class="sord-actor-role">{{ $order->restaurant->services ?? 'Restaurant' }}</span>
                <div class="sord-info-list">
                    <div class="sord-info-row"><span class="sord-info-label">Email</span><span class="sord-info-value">{{ $order->restaurant->email ?? '—' }}</span></div>
                    <div class="sord-info-row"><span class="sord-info-label">Téléphone</span><span class="sord-info-value">{{ $order->restaurant->phone ?? '—' }}</span></div>
                    <div class="sord-info-row"><span class="sord-info-label">Adresse</span><span class="sord-info-value">{{ $order->restaurant->address ?? '—' }}</span></div>
                </div>
                <a href="{{ route('restaurant.profile') }}" class="sord-cta sord-cta--blue">
                    <i class="fas fa-store"></i> Profil restaurant
                </a>
            </div>
        </div>

        {{-- Livreur --}}
        <div class="sord-card">
            <div class="sord-card__accent sord-card__accent--amber"></div>
            <div class="sord-card__body">
                @if($order->driver && $order->driver->image)
                    <img class="sord-avatar sord-avatar--amber"
                         src="{{ asset('images/driver_images/' . $order->driver->image) }}"
                         alt="Livreur"
                         onerror="this.src='{{ asset('images/placeholder.png') }}'">
                @else
                    <img class="sord-avatar sord-avatar--amber" src="{{ asset('images/5-512.png') }}" alt="Livreur">
                @endif
                <p class="sord-actor-name">{{ $order->driver->name ?? 'Non assigné' }}</p>
                <span class="sord-actor-role">{{ $order->driver->user_name ?? 'Livreur' }}</span>
                <div class="sord-info-list">
                    <div class="sord-info-row"><span class="sord-info-label">Email</span><span class="sord-info-value">{{ $order->driver->email ?? '—' }}</span></div>
                    <div class="sord-info-row"><span class="sord-info-label">Téléphone</span><span class="sord-info-value">{{ $order->driver->phone ?? '—' }}</span></div>
                    <div class="sord-info-row"><span class="sord-info-label">Adresse</span><span class="sord-info-value">{{ $order->driver->address ?? '—' }}</span></div>
                </div>
                @if($order->driver && $driverPhoneLink)
                    <a href="tel:{{ $driverPhoneLink }}" class="sord-cta sord-cta--amber">
                        <i class="fas fa-phone"></i> Appeler le livreur
                    </a>
                @else
                    <button type="button" class="sord-cta sord-cta--ghost" disabled>
                        <i class="fas fa-user-slash"></i> Livreur non assigné
                    </button>
                @endif
            </div>
        </div>

        {{-- Client --}}
        <div class="sord-card">
            <div class="sord-card__accent sord-card__accent--green"></div>
            <div class="sord-card__body">
                @if($order->user && $order->user->image)
                    <img class="sord-avatar sord-avatar--green"
                         src="{{ $order->user->avatarUrl() }}"
                         alt="Client"
                         onerror="this.src='{{ asset('images/placeholder.png') }}'">
                @else
                    <img class="sord-avatar sord-avatar--green" src="{{ asset('images/5-512.png') }}" alt="Client">
                @endif
                <p class="sord-actor-name">{{ $order->user->name ?? '—' }}</p>
                <span class="sord-actor-role">Client</span>
                <div class="sord-info-list">
                    <div class="sord-info-row"><span class="sord-info-label">Email</span><span class="sord-info-value">{{ $order->user->email ?? '—' }}</span></div>
                    <div class="sord-info-row"><span class="sord-info-label">Téléphone</span><span class="sord-info-value">{{ $order->user->phone ?? '—' }}</span></div>
                    <div class="sord-info-row"><span class="sord-info-label">Adresse</span><span class="sord-info-value">{{ $order->user->address ?? '—' }}</span></div>
                </div>
                @if($userPhoneLink)
                    <a href="tel:{{ $userPhoneLink }}" class="sord-cta sord-cta--green">
                        <i class="fas fa-phone"></i> Appeler le client
                    </a>
                @else
                    <button type="button" class="sord-cta sord-cta--ghost" disabled>
                        <i class="fas fa-phone-slash"></i> Téléphone non renseigné
                    </button>
                @endif
            </div>
        </div>

    </div>

    {{-- ── Corps principal ─────────────────────────────────── --}}
    <div class="sord-main">

        {{-- Colonne gauche --}}
        <div style="display:flex;flex-direction:column;gap:16px;">

            {{-- Produits commandés --}}
            <div class="sord-card">
                <div class="sord-card__head">
                    <i class="fas fa-basket-shopping"></i>
                    Produits commandés
                </div>
                <div class="sord-table-wrap">
                    <table class="sord-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Image</th>
                                <th>Produit</th>
                                <th>Qté</th>
                                <th>Prix unitaire</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($products as $index => $pro)
                            <tr>
                                <td style="color:var(--bd-text-3);font-size:12px;">{{ $loop->iteration }}</td>
                                <td>
                                    <img class="sord-prod-img"
                                         src="{{ url('images/product_images', $pro->product->image ?? 'placeholder.png') }}"
                                         alt="{{ $pro->product->name ?? 'Produit' }}"
                                         onerror="this.src='{{ asset('images/placeholder.png') }}'">
                                </td>
                                <td style="font-weight:600;color:var(--bd-text);">{{ $pro->product->name ?? '—' }}</td>
                                <td>{{ $pro->qty ?? $pro->quantity ?? 1 }}</td>
                                <td>
                                    <span class="sord-amount">{{ number_format((float)($pro->product->price ?? 0), 0, ',', ' ') }}<span class="sord-amount-cur"> FCFA</span></span>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Refus commande --}}
            @if(!in_array($order->status, ['completed', 'cancelled', 'delivered']))
            <div class="sord-refuse">
                <div class="sord-refuse__head">
                    <i class="fas fa-ban"></i> Refuser la commande
                </div>
                <div class="sord-refuse__body">
                    <form method="POST" action="{{ route('restaurant.cancel_order', $order->id) }}" id="refuseOrderForm">
                        @csrf
                        <div style="margin-bottom:12px;">
                            <div class="sord-field-label">Motif du refus <span style="color:#dc2626;">*</span></div>
                            <select name="reason" class="sord-select" required>
                                <option value="">— Choisir un motif —</option>
                                <option value="restaurant_closed">Restaurant fermé</option>
                                <option value="product_unavailable">Produit indisponible</option>
                                <option value="too_many_orders">Trop de commandes en cours</option>
                                <option value="delivery_zone_issue">Zone de livraison non couverte</option>
                                <option value="other">Autre raison</option>
                            </select>
                        </div>
                        <div style="margin-bottom:12px;">
                            <div class="sord-field-label" style="font-weight:400;color:var(--bd-text-3);">Message au client <span style="font-size:11px;">(optionnel)</span></div>
                            <textarea name="cancel_note" class="sord-textarea" rows="2" placeholder="Précision pour le client…" maxlength="300"></textarea>
                        </div>
                        <button type="submit" class="sord-btn-refuse" onclick="return confirm('Confirmer le refus de cette commande ?')">
                            <i class="fas fa-ban"></i> Refuser et notifier le client
                        </button>
                    </form>
                </div>
            </div>
            @endif

            {{-- Chat --}}
            @if(isset($chatData) && ($chatData['can_view'] ?? false))
            <div class="sord-chat">
                <div class="sord-chat__head">
                    <i class="fas fa-comments" style="color:var(--bd-text-3);"></i>
                    Messagerie — Client &amp; Support
                    @if(($chatData['unread_count'] ?? 0) > 0)
                        <span class="sord-unread">{{ $chatData['unread_count'] }}</span>
                    @endif
                </div>
                <div>
                    @include('frontend.partials.order_chat', ['chatData' => $chatData])
                </div>
            </div>
            @endif

            {{-- Carte --}}
            <div class="sord-card">
                <div class="sord-card__head">
                    <i class="fas fa-map"></i> Carte de livraison
                </div>
                <div id="map" style="height:340px;width:100%;border-top:1px solid var(--bd-border-2);"></div>
                <div class="sord-dist" style="padding:14px 16px;">
                    <div class="sord-dist-card">
                        <div class="sord-dist-label">Restaurant → Client</div>
                        <div class="sord-dist-value" id="restaurantCustomerDistance">—</div>
                    </div>
                    <div class="sord-dist-card">
                        <div class="sord-dist-label">Livreur → Client</div>
                        <div class="sord-dist-value" id="driverCustomerDistance">—</div>
                    </div>
                </div>
            </div>

        </div>

        {{-- Colonne droite (sidebar) --}}
        <div class="sord-sidebar">

            <div class="sord-card">
                <div class="sord-card__head">
                    <i class="fas fa-circle-info"></i> Récapitulatif
                </div>
                <div class="sord-meta">
                    <div class="sord-meta-row">
                        <span class="sord-meta-label">N° commande</span>
                        <span class="sord-meta-value" style="font-family:monospace;">{{ $order->order_no }}</span>
                    </div>
                    <div class="sord-meta-row">
                        <span class="sord-meta-label">Statut</span>
                        <span class="sord-status-badge {{ $statusClass }}" id="orderStatus">{{ $order->status ?? '—' }}</span>
                    </div>
                    <div class="sord-meta-row">
                        <span class="sord-meta-label">Date</span>
                        <span class="sord-meta-value">{{ \Carbon\Carbon::parse($order->created_at)->format('d/m/Y H:i') }}</span>
                    </div>
                    @if($order->delivery_address ?? $order->address ?? null)
                    <div class="sord-meta-row">
                        <span class="sord-meta-label">Adresse de livraison</span>
                        <span class="sord-meta-value">{{ $order->delivery_address ?? $order->address }}</span>
                    </div>
                    @endif
                    @if($order->total ?? $order->grand_total ?? null)
                    <div class="sord-meta-row">
                        <span class="sord-meta-label">Total</span>
                        <span class="sord-meta-value">
                            <span class="sord-amount">{{ number_format((float)($order->total ?? $order->grand_total ?? 0), 0, ',', ' ') }}<span class="sord-amount-cur"> FCFA</span></span>
                        </span>
                    </div>
                    @endif
                    @if($order->payment_method ?? null)
                    <div class="sord-meta-row">
                        <span class="sord-meta-label">Paiement</span>
                        <span class="sord-meta-value">{{ $order->payment_method }}</span>
                    </div>
                    @endif
                </div>
            </div>

        </div>
    </div>

</div>
@endsection

@section('script')
<script>
    var pickup = {
        lat: {{ (float)(($order->driver->latitude ?? null) ?: -4.2767) }},
        lng: {{ (float)(($order->driver->longitude ?? null) ?: 15.2832) }}
    };
    var dropoff = {lat: {{ (float)($order->d_lat ?? -4.2767) }}, lng: {{ (float)($order->d_lng ?? 15.2832) }}};
    var restaurantCoords = {lat: {{ (float)($order->restaurant->latitude ?? -4.2767) }}, lng: {{ (float)($order->restaurant->longitude ?? 15.2832) }}};
    var map, directionsService, directionsRenderer;
    var driverMarker = null;
    const orderNo = '{{ $order->order_no }}';
    const currentStatus = '{{ $order->status }}';

    function haversineKm(lat1, lon1, lat2, lon2) {
        const R = 6371;
        const dLat = (lat2 - lat1) * Math.PI / 180;
        const dLon = (lon2 - lon1) * Math.PI / 180;
        const a = Math.sin(dLat / 2) * Math.sin(dLat / 2) +
                  Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
                  Math.sin(dLon / 2) * Math.sin(dLon / 2);
        return R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
    }

    function formatDistance(km) {
        if (!Number.isFinite(km) || km <= 0) return '—';
        return km < 1 ? Math.round(km * 1000) + ' m' : km.toFixed(1) + ' km';
    }

    function updateDistanceCards() {
        var el1 = document.getElementById('restaurantCustomerDistance');
        var el2 = document.getElementById('driverCustomerDistance');
        if (el1) el1.textContent = formatDistance(haversineKm(restaurantCoords.lat, restaurantCoords.lng, dropoff.lat, dropoff.lng));
        if (el2) el2.textContent = formatDistance(haversineKm(pickup.lat, pickup.lng, dropoff.lat, dropoff.lng));
    }

    function renderMapNotice(message) {
        var box = document.getElementById('map');
        if (box) box.innerHTML = '<div style="padding:1.5rem;color:var(--bd-text-3);font-size:13px;">' + message + '</div>';
    }

    function initMap() {
        if (typeof google === 'undefined' || !google.maps) {
            renderMapNotice('Carte indisponible pour le moment.');
            return;
        }
        directionsService = new google.maps.DirectionsService();
        directionsRenderer = new google.maps.DirectionsRenderer();
        map = new google.maps.Map(document.getElementById('map'), { zoom: 11, center: pickup });
        directionsRenderer.setMap(map);
        calculateAndDisplayRoute(directionsService, directionsRenderer);
        @if($order->driver && $order->driver->latitude && $order->driver->longitude)
        updateDriverMarker({{ (float)$order->driver->latitude }}, {{ (float)$order->driver->longitude }});
        @endif
        @if(!in_array($order->status, ['completed', 'cancelled']))
        startAutoRefresh();
        @endif
    }

    function updateDriverMarker(lat, lng) {
        if (driverMarker) {
            driverMarker.setPosition({ lat: lat, lng: lng });
        } else if (map) {
            driverMarker = new google.maps.Marker({
                position: { lat: lat, lng: lng },
                map: map,
                title: 'Position du livreur',
                icon: { url: 'https://maps.google.com/mapfiles/ms/icons/green-dot.png', scaledSize: new google.maps.Size(40, 40) },
                animation: google.maps.Animation.BOUNCE
            });
        }
        pickup = { lat: lat, lng: lng };
        updateDistanceCards();
        calculateAndDisplayRoute(directionsService, directionsRenderer);
    }

    function calculateAndDisplayRoute(ds, dr) {
        ds.route({
            origin: pickup, destination: dropoff,
            travelMode: google.maps.TravelMode.DRIVING
        }, function(response, status) {
            if (status === 'OK') {
                dr.setDirections(response);
            } else {
                renderMapNotice('Itinéraire indisponible pour le moment.');
            }
        });
    }

    function fetchOrderStatus() {
        fetch('/api/order/' + orderNo + '/status')
            .then(r => r.json())
            .then(data => {
                if (data.status && data.order) {
                    var order = data.order;
                    if (order.driver && order.driver.latitude && order.driver.longitude) {
                        updateDriverMarker(parseFloat(order.driver.latitude), parseFloat(order.driver.longitude));
                    }
                    if (order.status !== currentStatus) {
                        var el = document.getElementById('orderStatus');
                        if (el) el.textContent = order.status;
                    }
                }
            })
            .catch(function(e) { console.error('Erreur statut commande:', e); });
    }

    var refreshInterval;
    function startAutoRefresh() {
        refreshInterval = setInterval(fetchOrderStatus, 10000);
    }

    updateDistanceCards();

    document.addEventListener('visibilitychange', function() {
        if (document.hidden) {
            clearInterval(refreshInterval);
        } else {
            @if(!in_array($order->status, ['completed', 'cancelled']))
            startAutoRefresh();
            @endif
        }
    });
</script>
@php $googleMapsKey = env('GOOGLE_MAPS_API_KEY', ''); @endphp
@if(!empty($googleMapsKey))
<script async defer src="https://maps.googleapis.com/maps/api/js?key={{ $googleMapsKey }}&callback=initMap"></script>
@else
<script>document.addEventListener('DOMContentLoaded', function() { renderMapNotice('Carte indisponible — clé Google Maps non configurée.'); updateDistanceCards(); });</script>
@endif
@endsection
