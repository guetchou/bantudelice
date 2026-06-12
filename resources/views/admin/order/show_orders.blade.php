@extends('layouts.admin-modern')
@section('title', 'Détails de la commande')
@section('page_title', 'Détail commande')
@section('nav_active', 'orders')

@section('style')
<style>
.osh-page { padding:24px; }
/* Actor cards */
.osh-actors { display:grid; grid-template-columns:repeat(3,1fr); gap:16px; margin-bottom:20px; }
.osh-actor { background:#fff; border:1px solid #e5e7eb; border-radius:10px; overflow:hidden; }
.osh-actor__top { padding:20px 16px 12px; text-align:center; }
.osh-actor__avatar { width:72px; height:72px; border-radius:50%; object-fit:cover; border:2px solid #e5e7eb; }
.osh-actor__name { font-size:14px; font-weight:700; color:#111827; margin:10px 0 2px; }
.osh-actor__sub { font-size:12px; color:#9ca3af; margin:0 0 10px; }
.osh-actor__list { list-style:none; padding:0; margin:0; border-top:1px solid #f3f4f6; }
.osh-actor__list li { display:flex; justify-content:space-between; padding:8px 16px; font-size:12px; border-bottom:1px solid #f3f4f6; gap:8px; }
.osh-actor__list li:last-child { border-bottom:none; }
.osh-actor__list li b { color:#374151; flex-shrink:0; }
.osh-actor__list li span { color:#6b7280; text-align:right; overflow-wrap:anywhere; }
.osh-actor__footer { padding:10px 16px; border-top:1px solid #f3f4f6; }
.osh-actor__btn { display:block; width:100%; padding:8px; text-align:center; border-radius:6px; font-size:13px; font-weight:600; text-decoration:none; border:none; cursor:pointer; transition:opacity .15s; }
.osh-actor__btn--blue   { background:#1e3a5f; color:#fff; }
.osh-actor__btn--amber  { background:#d97706; color:#fff; }
.osh-actor__btn--green  { background:#16a34a; color:#fff; }
.osh-actor__btn:hover   { opacity:.85; color:#fff; text-decoration:none; }
.osh-actor__btn:disabled, .osh-actor__btn[disabled] { opacity:.5; cursor:not-allowed; }
/* Stat boxes */
.osh-stats { display:grid; grid-template-columns:repeat(auto-fit,minmax(160px,1fr)); gap:12px; margin-bottom:20px; }
.osh-stat { background:#fff; border:1px solid #e5e7eb; border-radius:8px; padding:14px 16px; }
.osh-stat__label { font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.06em; color:#9ca3af; margin-bottom:4px; }
.osh-stat__value { font-size:18px; font-weight:700; color:#111827; }
/* Incident card */
.osh-card { background:#fff; border:1px solid #e5e7eb; border-radius:10px; overflow:hidden; margin-bottom:16px; }
.osh-card__header { padding:12px 20px; border-bottom:1px solid #f3f4f6; border-left:3px solid #f59e0b; }
.osh-card__title { font-size:14px; font-weight:700; color:#111827; margin:0; }
.osh-card__body { padding:20px; }
.osh-incident-grid { display:grid; grid-template-columns:repeat(4,1fr); gap:12px; font-size:13px; color:#374151; margin-bottom:12px; }
.osh-incident-grid span { color:#6b7280; display:block; font-size:12px; margin-top:2px; }
.osh-incident-notes { font-size:13px; color:#6b7280; background:#f9fafb; border-radius:6px; padding:10px 14px; margin-top:8px; }
/* Resolve form */
.osh-resolve-form { display:grid; grid-template-columns:1fr 3fr auto; gap:12px; align-items:end; margin-top:16px; }
.osh-field { display:flex; flex-direction:column; gap:4px; }
.osh-label { font-size:13px; font-weight:600; color:#374151; }
.osh-input { width:100%; padding:8px 12px; border:1px solid #d1d5db; border-radius:6px; font-size:13px; color:#111827; background:#fff; box-sizing:border-box; }
.osh-input:focus { outline:none; border-color:#f59e0b; }
.osh-btn-apply { padding:8px 18px; background:#f59e0b; color:#fff; border:none; border-radius:6px; font-size:13px; font-weight:600; cursor:pointer; white-space:nowrap; }
.osh-btn-apply:hover { opacity:.85; }
/* Products + map layout */
.osh-bottom { display:grid; grid-template-columns:1fr 260px; gap:16px; margin-bottom:20px; }
.osh-products-col { display:flex; flex-direction:column; gap:16px; }
.osh-product-table-wrap { overflow-x:auto; }
.osh-product-table { width:100%; border-collapse:collapse; font-size:13px; }
.osh-product-table thead th { padding:9px 14px; font-size:10px; font-weight:700; letter-spacing:.06em; text-transform:uppercase; color:#9ca3af; border-bottom:1px solid #f3f4f6; background:#f9fafb; text-align:left; }
.osh-product-table tbody td { padding:10px 14px; color:#374151; vertical-align:middle; border-bottom:1px solid #f3f4f6; }
.osh-product-table tbody tr:last-child td { border-bottom:none; }
.osh-product-img { width:56px; height:56px; object-fit:cover; border-radius:6px; }
#map { width:100%; height:350px; border-radius:8px; border:1px solid #e5e7eb; }
/* Side status */
.osh-status-col { display:flex; flex-direction:column; gap:12px; }
.osh-status-box { background:#fff; border:1px solid #e5e7eb; border-radius:8px; overflow:hidden; }
.osh-status-box__label { padding:10px 14px; font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.06em; color:#9ca3af; border-bottom:1px solid #f3f4f6; }
.osh-status-box__value { padding:12px 14px; font-size:14px; font-weight:600; color:#111827; }
@media (max-width:900px) {
    .osh-actors { grid-template-columns:1fr; }
    .osh-bottom { grid-template-columns:1fr; }
    .osh-incident-grid { grid-template-columns:1fr 1fr; }
    .osh-resolve-form { grid-template-columns:1fr; }
}
/* Barre d'actions admin */
.osh-admin-actions { display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:12px; margin-bottom:20px; padding:14px 20px; background:#fff; border:1px solid #e5e7eb; border-radius:10px; }
.osh-admin-actions__title { font-size:14px; font-weight:700; color:#111827; margin:0; }
.osh-admin-actions__btns { display:flex; gap:10px; flex-wrap:wrap; }
.osh-btn-cancel { display:inline-flex; align-items:center; gap:6px; padding:8px 18px; background:#ef4444; color:#fff; border:none; border-radius:7px; font-size:13px; font-weight:600; cursor:pointer; transition:opacity .15s; }
.osh-btn-cancel:hover { opacity:.85; }
/* Modal annulation */
.osh-cancel-modal-bg { display:none; position:fixed; inset:0; background:rgba(0,0,0,.45); z-index:9999; align-items:center; justify-content:center; }
.osh-cancel-modal-bg.active { display:flex; }
.osh-cancel-modal { background:#fff; border-radius:14px; padding:28px 28px 24px; max-width:480px; width:90%; box-shadow:0 20px 60px rgba(0,0,0,.2); }
.osh-cancel-modal__title { font-size:16px; font-weight:700; color:#111827; margin:0 0 6px; }
.osh-cancel-modal__sub { font-size:13px; color:#6b7280; margin:0 0 16px; }
.osh-cancel-modal__label { font-size:13px; font-weight:600; color:#374151; display:block; margin-bottom:6px; }
.osh-cancel-modal__textarea { width:100%; padding:10px 14px; border:1px solid #d1d5db; border-radius:7px; font-size:13px; resize:vertical; min-height:80px; font-family:inherit; box-sizing:border-box; }
.osh-cancel-modal__textarea:focus { outline:none; border-color:#ef4444; }
.osh-cancel-modal__actions { display:flex; justify-content:flex-end; gap:10px; margin-top:16px; }
.osh-cancel-modal__btn-confirm { padding:9px 22px; background:#ef4444; color:#fff; border:none; border-radius:7px; font-size:13px; font-weight:700; cursor:pointer; }
.osh-cancel-modal__btn-confirm:hover { opacity:.85; }
.osh-cancel-modal__btn-cancel { padding:9px 18px; background:#f3f4f6; color:#374151; border:1px solid #e5e7eb; border-radius:7px; font-size:13px; font-weight:600; cursor:pointer; }
</style>
@endsection

@section('content')
<div class="osh-page">

    @php
        $isCancellable = isset($order) && !in_array(
            strtolower((string) ($order->business_status ?? $order->status ?? '')),
            ['cancelled', 'canceled', 'delivered', 'picked_up_by_customer', 'closed']
        );
    @endphp

    {{-- Alert flash --}}
    @if(session('alert'))
        <div class="osh-alert osh-alert--{{ session('alert.type') ?? 'info' }}" style="margin-bottom:16px;padding:12px 16px;border-radius:8px;font-size:13px;font-weight:500;border:1px solid transparent;{{ session('alert.type') === 'success' ? 'background:#f0fdf4;color:#166534;border-color:#bbf7d0;' : 'background:#fef2f2;color:#991b1b;border-color:#fecaca;' }}">
            {{ session('alert.message') }}
        </div>
    @endif

    {{-- Barre d'actions admin --}}
    <div class="osh-admin-actions">
        <p class="osh-admin-actions__title">
            <i class="fas fa-cog" style="color:#9ca3af;margin-right:6px;"></i>
            Actions administrateur — Commande #{{ $order->order_no }}
            &nbsp;<span style="font-size:12px;padding:2px 8px;border-radius:999px;background:{{ $isCancellable ? '#fef3c7' : '#f3f4f6' }};color:{{ $isCancellable ? '#92400e' : '#6b7280' }};font-weight:600;">
                {{ strtoupper($order->business_status ?? $order->status ?? '—') }}
            </span>
        </p>
        <div class="osh-admin-actions__btns">
            <a href="{{ route('admin.all_orders') }}" style="display:inline-flex;align-items:center;gap:6px;padding:8px 16px;border:1px solid #e5e7eb;border-radius:7px;font-size:13px;font-weight:600;color:#374151;text-decoration:none;">
                <i class="fas fa-arrow-left"></i> Retour
            </a>
            @if($isCancellable)
                <button type="button" class="osh-btn-cancel" onclick="document.getElementById('osh-cancel-modal').classList.add('active')">
                    <i class="fas fa-times-circle"></i> Annuler la commande
                </button>
            @else
                <button class="osh-btn-cancel" disabled style="opacity:.4;cursor:not-allowed;">
                    <i class="fas fa-times-circle"></i> Déjà {{ in_array(strtolower($order->business_status ?? ''), ['delivered','picked_up_by_customer']) ? 'livrée' : 'annulée' }}
                </button>
            @endif
        </div>
    </div>

    {{-- Modal d'annulation --}}
    @if($isCancellable)
    <div class="osh-cancel-modal-bg" id="osh-cancel-modal" onclick="if(event.target===this) this.classList.remove('active')">
        <div class="osh-cancel-modal">
            <h3 class="osh-cancel-modal__title"><i class="fas fa-exclamation-triangle" style="color:#ef4444;"></i> Annuler la commande</h3>
            <p class="osh-cancel-modal__sub">Commande <strong>#{{ $order->order_no }}</strong> — client : {{ $order->user->name ?? '—' }}<br>Cette action est irréversible. Le client sera notifié.</p>
            <form method="POST" action="{{ route('admin.cancel_order', $order->id) }}">
                @csrf
                <label class="osh-cancel-modal__label">Motif d'annulation <span style="color:#ef4444;">*</span></label>
                <textarea name="reason" class="osh-cancel-modal__textarea" required minlength="5" maxlength="500"
                    placeholder="Ex: Commande en doublon, problème de paiement, demande du client…"></textarea>
                <div class="osh-cancel-modal__actions">
                    <button type="button" class="osh-cancel-modal__btn-cancel" onclick="document.getElementById('osh-cancel-modal').classList.remove('active')">Annuler</button>
                    <button type="submit" class="osh-cancel-modal__btn-confirm">Confirmer l'annulation</button>
                </div>
            </form>
        </div>
    </div>
    @endif

    {{-- Actor cards: restaurant / driver / client --}}
    <div class="osh-actors">
        {{-- Restaurant --}}
        <div class="osh-actor">
            <div class="osh-actor__top">
                <img class="osh-actor__avatar" src="{{ asset('images/restaurant_images/'.$order->restaurant->logo) }}" alt="logo">
                <div class="osh-actor__name">{{ $order->restaurant->name }}</div>
                <div class="osh-actor__sub"><b>Services : </b>{{ $order->restaurant->services }}</div>
            </div>
            <ul class="osh-actor__list">
                <li><b>Email</b><span>{{ $order->restaurant->email }}</span></li>
                <li><b>Téléphone</b><span>{{ $order->restaurant->phone }}</span></li>
                <li><b>Adresse</b><span>{{ $order->restaurant->address }}</span></li>
            </ul>
            <div class="osh-actor__footer">
                <a href="{{ route('restaurant.show', $order->restaurant->id) }}" class="osh-actor__btn osh-actor__btn--blue">Restaurant</a>
            </div>
        </div>

        {{-- Driver --}}
        <div class="osh-actor">
            <div class="osh-actor__top">
                @if($order->driver && $order->driver->image)
                    <img class="osh-actor__avatar" style="border-color:#d97706;"
                         src="{{ asset('images/driver_images/'.$order->driver->image) }}"
                         onerror="this.src='{{ asset('images/placeholder.png') }}'" alt="livreur">
                @else
                    <img class="osh-actor__avatar" style="border-color:#d97706;" src="{{ asset('images/5-512.png') }}" alt="livreur">
                @endif
                <div class="osh-actor__name">{{ $order->driver ? $order->driver->name : 'Livreur non assigné' }}</div>
                <div class="osh-actor__sub">{{ $order->driver ? $order->driver->user_name : 'Livreur' }}</div>
            </div>
            <ul class="osh-actor__list">
                <li><b>Email</b><span>{{ $order->driver ? $order->driver->email : '—' }}</span></li>
                <li><b>Téléphone</b><span>{{ $order->driver ? $order->driver->phone : '—' }}</span></li>
                <li><b>Adresse</b><span>{{ $order->driver ? $order->driver->address : '—' }}</span></li>
            </ul>
            <div class="osh-actor__footer">
                @if($order->driver)
                    <a href="{{ route('driver.show', $order->driver->id) }}" class="osh-actor__btn osh-actor__btn--amber">Livreur</a>
                @else
                    <button class="osh-actor__btn osh-actor__btn--amber" disabled>Livreur non assigné</button>
                @endif
            </div>
        </div>

        {{-- Client --}}
        <div class="osh-actor">
            <div class="osh-actor__top">
                @if($order->user && $order->user->image)
                    <img class="osh-actor__avatar" style="border-color:#16a34a;"
                         src="{{ $order->user->avatarUrl() }}" alt="client">
                @else
                    <img class="osh-actor__avatar" style="border-color:#16a34a;" src="{{ asset('images/5-512.png') }}" alt="client">
                @endif
                <div class="osh-actor__name">{{ $order->user->name }}</div>
                <div class="osh-actor__sub">Client</div>
            </div>
            <ul class="osh-actor__list">
                <li><b>Email</b><span>{{ $order->user->email }}</span></li>
                <li><b>Téléphone</b><span>{{ $order->user->phone }}</span></li>
                <li><b>Adresse</b><span>{{ $order->user->address }}</span></li>
            </ul>
            <div class="osh-actor__footer">
                @php $customerPhoneLink = preg_replace('/[^0-9+]/', '', (string)($order->user->phone ?? '')); @endphp
                @if($customerPhoneLink)
                    <a href="tel:{{ $customerPhoneLink }}" class="osh-actor__btn osh-actor__btn--green">Appeler le client</a>
                @else
                    <button class="osh-actor__btn osh-actor__btn--green" disabled>Client</button>
                @endif
            </div>
        </div>
    </div>

    {{-- Stat boxes --}}
    <div class="osh-stats">
        <div class="osh-stat">
            <div class="osh-stat__label">Numéro de commande</div>
            <div class="osh-stat__value">{{ $order->order_no }}</div>
        </div>
        <div class="osh-stat">
            <div class="osh-stat__label">Taxe</div>
            <div class="osh-stat__value">{{ $order->tax }}</div>
        </div>
        <div class="osh-stat">
            <div class="osh-stat__label">Frais de livraison</div>
            <div class="osh-stat__value">{{ $order->delivery_charges }}</div>
        </div>
        <div class="osh-stat">
            <div class="osh-stat__label">Pourboire</div>
            <div class="osh-stat__value">{{ $order->driver_tip }}</div>
        </div>
        <div class="osh-stat">
            <div class="osh-stat__label">Sous-total</div>
            <div class="osh-stat__value">{{ $order->sub_total }}</div>
        </div>
        <div class="osh-stat">
            <div class="osh-stat__label">Total</div>
            <div class="osh-stat__value">{{ $order->total }}</div>
        </div>
    </div>

    {{-- Incident block --}}
    @if($order->delivery)
        <div class="osh-card">
            <div class="osh-card__header">
                <h3 class="osh-card__title">Incident et support livraison</h3>
            </div>
            <div class="osh-card__body">
                <div class="osh-incident-grid">
                    <div><b>Statut livraison</b><span>{{ $order->delivery->status }}</span></div>
                    <div><b>Incident</b><span>{{ $order->delivery->incident_status ?? 'aucun' }}</span></div>
                    <div><b>Motif</b><span>{{ $order->delivery->incident_reason ?? 'n/a' }}</span></div>
                    <div><b>Support</b><span>{{ $order->delivery->support_status ?? 'n/a' }}</span></div>
                </div>
                @if($order->delivery->incident_notes || $order->delivery->support_notes)
                    <div class="osh-incident-notes">
                        {{ $order->delivery->incident_notes ?? $order->delivery->support_notes }}
                    </div>
                @endif
                @if(($order->delivery->incident_status ?? null) === 'open')
                    <form method="POST" action="{{ route('admin.resolve_incident', ['order' => $order->order_no]) }}">
                        @csrf
                        <div class="osh-resolve-form">
                            <div class="osh-field">
                                <label class="osh-label">Décision support</label>
                                <select name="resolution" class="osh-input">
                                    <option value="resolved">Clôturer le litige</option>
                                    <option value="redelivery">Relancer la livraison</option>
                                    <option value="cancelled">Annuler la commande</option>
                                </select>
                            </div>
                            <div class="osh-field">
                                <label class="osh-label">Notes support</label>
                                <textarea name="support_notes" rows="2" class="osh-input" placeholder="Décision, contexte, action prise."></textarea>
                            </div>
                            <div>
                                <button type="submit" class="osh-btn-apply">Appliquer</button>
                            </div>
                        </div>
                    </form>
                @endif
            </div>
        </div>
    @endif

    {{-- Chat (frontend partial, layout-agnostic) --}}
    @if(!empty($chatData) && ($chatData['can_view'] ?? false))
        <div style="margin-bottom:16px;">
            @include('frontend.partials.order_chat', ['chatData' => $chatData])
        </div>
    @endif

    {{-- Products + map / Status --}}
    <div class="osh-bottom">
        <div class="osh-products-col">
            <div class="osh-card" style="margin-bottom:0;">
                <div class="osh-card__body" style="padding:0;">
                    <div class="osh-product-table-wrap">
                        <table class="osh-product-table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Image</th>
                                    <th>Produit</th>
                                    <th>Qté</th>
                                    <th>Prix</th>
                                </tr>
                            </thead>
                            <tbody>
                            @foreach($products as $index => $pro)
                                <tr>
                                    <td>{{ ++$index }}</td>
                                    <td><img src="{{ url('images/product_images', $pro->product->image) }}" class="osh-product-img" alt="produit"></td>
                                    <td>{{ $pro->product->name ?? '—' }}</td>
                                    <td>{{ $pro->qty ?? $pro->quantity ?? 1 }}</td>
                                    <td>{{ number_format((float)($pro->product->price ?? 0), 0, ',', ' ') }} FCFA</td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div id="map"></div>
        </div>

        <div class="osh-status-col">
            <div class="osh-status-box">
                <div class="osh-status-box__label">Statut</div>
                <div class="osh-status-box__value">{{ $order->status }}</div>
            </div>
            <div class="osh-status-box">
                <div class="osh-status-box__label">Date de commande</div>
                <div class="osh-status-box__value" style="font-size:12px;">{{ $order->created_at }}</div>
            </div>
        </div>
    </div>

</div>
@endsection

@section('script')
<script>
var pickup  = { lat: {{ (float)($order->driver->latitude  ?? -4.2767) }}, lng: {{ (float)($order->driver->longitude ?? 15.2832) }} };
var dropoff = { lat: {{ (float)($order->d_lat ?? -4.2767) }}, lng: {{ (float)($order->d_lng ?? 15.2832) }} };

@if($order->driver === null)
pickup = { lat: -4.2767, lng: 15.2832 };
@endif

function initMap() {
    var directionsService = new google.maps.DirectionsService();
    var directionsRenderer = new google.maps.DirectionsRenderer();
    var map = new google.maps.Map(document.getElementById('map'), {
        zoom: 11,
        center: pickup
    });
    directionsRenderer.setMap(map);
    calculateAndDisplayRoute(directionsService, directionsRenderer);
}

function renderMapNotice(message) {
    var mapBox = document.getElementById('map');
    if (mapBox) {
        mapBox.innerHTML = '<div style="padding:1rem;color:#6B7280;">' + message + '</div>';
    }
}

function calculateAndDisplayRoute(directionsService, directionsRenderer) {
    directionsService.route({
        origin: pickup,
        destination: dropoff,
        travelMode: google.maps.TravelMode['DRIVING']
    }, function (response, status) {
        if (status == 'OK') {
            directionsRenderer.setDirections(response);
        } else {
            console.warn('Directions request failed due to ' + status);
            renderMapNotice('Itinéraire indisponible pour le moment.');
        }
    });
}
</script>
<script async defer src="https://maps.googleapis.com/maps/api/js?key={{ env('GOOGLE_MAPS_API_KEY') }}&callback=initMap"></script>
@endsection
