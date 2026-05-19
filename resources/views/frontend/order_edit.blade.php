@extends('frontend.layouts.app-modern')
@section('title', 'Modifier la commande #' . $order->order_no . ' | ' . \App\Services\ConfigService::getCompanyName())
@section('description', 'Modifier une commande avant le début de la préparation.')

@section('content')
<section style="padding:120px 0 40px; background:linear-gradient(135deg,#111827 0%,#1f2937 45%,#ff5a1f 120%); color:#fff;">
    <div class="container">
        <div style="max-width:1100px; margin:0 auto; display:flex; justify-content:space-between; gap:1rem; align-items:flex-end; flex-wrap:wrap;">
            <div>
                <p style="margin:0 0 .6rem; opacity:.8;">Commande modifiable</p>
                <h1 style="font-size:2.35rem; font-weight:900; margin:0 0 .5rem;">Modifier la commande #{{ $order->order_no }}</h1>
                <p style="margin:0; opacity:.9;">Les changements sont possibles tant que le restaurant n'a pas commencé la préparation.</p>
            </div>
            <div style="text-align:right;">
                <div style="font-size:.9rem; opacity:.75;">Statut actuel</div>
                <div style="font-weight:800;">{{ ucfirst(str_replace('_', ' ', $order->resolveEffectiveBusinessStatus())) }}</div>
                <div style="margin-top:.5rem;">
                    <a href="{{ route('track.order', ['orderNo' => $order->order_no]) }}" style="display:inline-flex;align-items:center;justify-content:center;background:#fff;color:#0f172a;font-weight:600;padding:.7rem 1.35rem;border-radius:999px;border:1.5px solid #e2e8f0;cursor:pointer;text-decoration:none;" style="border-radius:999px; padding:.7rem 1rem;">Retour au suivi</a>
                </div>
            </div>
        </div>
    </div>
</section>

<section style="background:#f8fafc; padding:2rem 0 3rem;">
    <div class="container">
        @php($viewErrors = isset($errors) ? $errors : new \Illuminate\Support\ViewErrorBag)
        @if(session()->has('message'))
            <div style="max-width:1100px; margin:0 auto 1rem; background:#fff7ed; border:1px solid rgba(249,115,22,.15); color:#9a3412; padding:1rem 1.2rem; border-radius:18px;">
                {{ session()->get('message') }}
            </div>
        @endif
        @if(session()->has('success'))
            <div style="max-width:1100px; margin:0 auto 1rem; background:#ecfdf5; border:1px solid rgba(16,185,129,.15); color:#065f46; padding:1rem 1.2rem; border-radius:18px;">
                {{ session()->get('success') }}
            </div>
        @endif
        @if($viewErrors->any())
            <div style="max-width:1100px; margin:0 auto 1rem; background:#fef2f2; border:1px solid rgba(239,68,68,.18); color:#991b1b; padding:1rem 1.2rem; border-radius:18px;">
                <ul style="margin:0; padding-left:1.2rem;">
                    @foreach($viewErrors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('orders.update', ['orderNo' => $order->order_no]) }}">
            @csrf
            @method('PATCH')
            <div style="max-width:1100px; margin:0 auto; display:grid; grid-template-columns:minmax(0, 1.15fr) minmax(320px, .85fr); gap:1.25rem; align-items:start;">
                <div style="display:grid; gap:1.25rem;">
                    <div style="background:#fff; border-radius:24px; padding:1.5rem; box-shadow:0 12px 30px rgba(15,23,42,.06);">
                        <div style="display:flex; justify-content:space-between; gap:1rem; align-items:center; margin-bottom:1rem;">
                            <div>
                                <h2 style="margin:0; font-size:1.25rem; font-weight:900; color:#0f172a;">Ajuster les informations</h2>
                                <p style="margin:.3rem 0 0; color:#64748b;">Vous ne modifiez pas les articles ni le paiement dans cette version.</p>
                            </div>
                        </div>

                        <div style="display:grid; gap:1rem;">
                            @if($order->isPickup())
                                <div>
                                    <label style="display:block; font-weight:700; color:#334155; margin-bottom:.35rem;">Note de retrait</label>
                                    <textarea name="pickup_note" rows="4" placeholder="Heure de passage, nom de la personne qui récupère, repère utile..." style="width:100%; border:1px solid #cbd5e1; border-radius:14px; padding:.9rem 1rem;">{{ old('pickup_note', preg_replace('/^.*?\\|\\s*Note:\\s*/', '', (string) $order->delivery_address)) }}</textarea>
                                </div>
                            @else
                                @if(isset($savedAddresses) && $savedAddresses->count() > 0)
                                    <div>
                                        <label style="display:block; font-weight:700; color:#334155; margin-bottom:.35rem;">Adresse enregistrée</label>
                                        <select id="savedAddressSelect" name="address_id" style="width:100%; border:1px solid #cbd5e1; border-radius:14px; padding:.9rem 1rem; background:#fff;">
                                            <option value="">Choisir une adresse enregistrée</option>
                                            @foreach($savedAddresses as $savedAddress)
                                                <option
                                                    value="{{ $savedAddress->id }}"
                                                    data-title="{{ $savedAddress->title }}"
                                                    data-address="{{ $savedAddress->complete_address }}"
                                                    data-area="{{ $savedAddress->area }}"
                                                    data-building="{{ $savedAddress->building_no }}"
                                                    data-street="{{ $savedAddress->street_no }}"
                                                    data-floor="{{ $savedAddress->floor }}"
                                                    data-lat="{{ $savedAddress->latitude }}"
                                                    data-lng="{{ $savedAddress->longitude }}"
                                                    @if(
                                                        trim((string) $order->delivery_address) === trim(implode(' | ', array_filter([
                                                            $savedAddress->title,
                                                            $savedAddress->complete_address,
                                                            $savedAddress->area,
                                                            $savedAddress->building_no,
                                                            $savedAddress->street_no,
                                                            $savedAddress->floor,
                                                        ])))
                                                    ) selected @endif
                                                >
                                                    {{ $savedAddress->title }} - {{ $savedAddress->complete_address }}
                                                    @if($savedAddress->is_default) (Par défaut) @endif
                                                </option>
                                            @endforeach
                                        </select>
                                        <p style="margin:.45rem 0 0; color:#64748b; font-size:.85rem;">Sélectionnez une adresse enregistrée ou saisissez une nouvelle adresse ci-dessous.</p>
                                    </div>
                                @endif

                                <div>
                                    <label style="display:block; font-weight:700; color:#334155; margin-bottom:.35rem;">Adresse de livraison</label>
                                    <input type="text" id="deliveryAddressInput" name="delivery_address" value="{{ old('delivery_address', $order->delivery_address) }}" placeholder="Entrez votre adresse de livraison" style="width:100%; border:1px solid #cbd5e1; border-radius:14px; padding:.9rem 1rem;">
                                </div>

                                <div style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:1rem;">
                                    <div>
                                        <label style="display:block; font-weight:700; color:#334155; margin-bottom:.35rem;">Quartier / zone</label>
                                        <input type="text" id="deliveryDistrict" name="delivery_district" value="{{ old('delivery_district') }}" placeholder="Ex: Poto-Poto" style="width:100%; border:1px solid #cbd5e1; border-radius:14px; padding:.9rem 1rem;">
                                    </div>
                                    <div>
                                        <label style="display:block; font-weight:700; color:#334155; margin-bottom:.35rem;">Lieu connu / repère</label>
                                        <input type="text" id="deliveryLandmark" name="delivery_landmark" value="{{ old('delivery_landmark') }}" placeholder="Ex: Marché Total" style="width:100%; border:1px solid #cbd5e1; border-radius:14px; padding:.9rem 1rem;">
                                    </div>
                                    <div>
                                        <label style="display:block; font-weight:700; color:#334155; margin-bottom:.35rem;">Coordonnées</label>
                                        <div style="display:grid; grid-template-columns:1fr 1fr; gap:.5rem;">
                                            <input type="text" id="dLat" name="d_lat" value="{{ old('d_lat', $order->d_lat) }}" placeholder="Lat" style="width:100%; border:1px solid #cbd5e1; border-radius:14px; padding:.9rem 1rem;">
                                            <input type="text" id="dLng" name="d_lng" value="{{ old('d_lng', $order->d_lng) }}" placeholder="Lng" style="width:100%; border:1px solid #cbd5e1; border-radius:14px; padding:.9rem 1rem;">
                                        </div>
                                    </div>
                                </div>

                                <div>
                                    <label style="display:block; font-weight:700; color:#334155; margin-bottom:.35rem;">Complément d'adresse</label>
                                    <textarea id="deliveryComplement" name="delivery_complement" rows="4" placeholder="Bâtiment, portail, étage, code d'accès..." style="width:100%; border:1px solid #cbd5e1; border-radius:14px; padding:.9rem 1rem;">{{ old('delivery_complement') }}</textarea>
                                </div>
                            @endif

                            <div>
                                <label style="display:block; font-weight:700; color:#334155; margin-bottom:.35rem;">Planification</label>
                                <input type="datetime-local" name="scheduled_date" value="{{ old('scheduled_date', optional($order->scheduled_date)->format('Y-m-d\\TH:i')) }}" style="width:100%; border:1px solid #cbd5e1; border-radius:14px; padding:.9rem 1rem;">
                                <p style="margin:.45rem 0 0; color:#64748b; font-size:.85rem;">Si vous laissez vide, la commande reste non planifiée.</p>
                            </div>
                        </div>
                    </div>

                    <div style="background:linear-gradient(135deg,#111827 0%,#1f2937 48%,#ff5a1f 125%); color:#fff; border-radius:24px; padding:1.5rem; box-shadow:0 14px 34px rgba(15,23,42,.12);">
                        <div style="display:flex; justify-content:space-between; gap:1rem; align-items:center; flex-wrap:wrap;">
                            <div>
                                <div style="font-size:.8rem; opacity:.7; text-transform:uppercase; letter-spacing:.08em;">Règle métier</div>
                                <div style="font-size:1.1rem; font-weight:800;">Modifiable tant que la préparation n'a pas commencé</div>
                            </div>
                            <button type="submit" style="display:inline-flex;align-items:center;justify-content:center;background:#009543;color:#fff;font-weight:700;padding:.7rem 1.35rem;border-radius:999px;border:none;cursor:pointer;text-decoration:none;" style="border-radius:999px; padding:.9rem 1.2rem;">Enregistrer les modifications</button>
                        </div>
                    </div>
                </div>

                <div style="display:grid; gap:1.25rem;">
                    <div style="background:#fff; border-radius:24px; padding:1.5rem; box-shadow:0 12px 30px rgba(15,23,42,.06);">
                        <h3 style="margin:0 0 1rem; font-size:1.15rem; font-weight:900; color:#0f172a;">Résumé de la commande</h3>
                        <div style="display:grid; gap:1rem;">
                            @foreach($orderItems as $item)
                                <div style="display:flex; align-items:center; gap:1rem; padding-bottom:1rem; border-bottom:1px solid #e2e8f0;">
                                    @if(optional($item->product)->image)
                                        <img src="{{ asset('images/product_images/' . $item->product->image) }}" alt="{{ $item->product->name ?? 'Produit' }}" style="width:56px; height:56px; border-radius:14px; object-fit:cover;">
                                    @endif
                                    <div style="flex:1;">
                                        <div style="font-weight:800; color:#0f172a;">{{ $item->product->name ?? 'Produit' }}</div>
                                        <div style="color:#64748b; font-size:.92rem;">Qté: {{ $item->qty }} × {{ number_format($item->price ?? 0, 0, ',', ' ') }} FCFA</div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <div style="display:grid; gap:.75rem; margin-top:1rem; color:#334155;">
                            <div style="display:flex; justify-content:space-between; gap:1rem;">
                                <span>Restaurant</span>
                                <strong>{{ $order->restaurant->name ?? 'Restaurant' }}</strong>
                            </div>
                            <div style="display:flex; justify-content:space-between; gap:1rem;">
                                <span>Mode</span>
                                <strong>{{ $order->isPickup() ? 'Retrait' : 'Livraison' }}</strong>
                            </div>
                            <div style="display:flex; justify-content:space-between; gap:1rem;">
                                <span>Statut</span>
                                <strong>{{ ucfirst(str_replace('_', ' ', $order->resolveEffectiveBusinessStatus())) }}</strong>
                            </div>
                            <div style="display:flex; justify-content:space-between; gap:1rem;">
                                <span>Total</span>
                                <strong>{{ number_format($order->total ?? 0, 0, ',', ' ') }} FCFA</strong>
                            </div>
                            <div style="display:flex; justify-content:space-between; gap:1rem;">
                                <span>Commande</span>
                                <strong>#{{ $order->order_no }}</strong>
                            </div>
                        </div>
                    </div>

                    <div style="background:linear-gradient(135deg,#fff7ed 0%,#fff 100%); border:1px solid rgba(249,115,22,.16); border-radius:24px; padding:1.5rem; box-shadow:0 12px 30px rgba(15,23,42,.06);">
                        <h3 style="margin:0 0 .75rem; font-size:1.1rem; font-weight:900; color:#0f172a;">Important</h3>
                        <ul style="margin:0; padding-left:1.2rem; color:#475569; display:grid; gap:.5rem;">
                            <li>Les articles de la commande ne sont pas modifiables dans ce premier lot.</li>
                            <li>Si l'adresse change alors qu'un livreur est déjà assigné, le dispatch repart automatiquement.</li>
                            <li>Une fois la préparation lancée, le bouton de modification disparaît.</li>
                        </ul>
                    </div>
                </div>
            </div>
        </form>
    </div>
</section>

@if(!$order->isPickup())
<script>
document.addEventListener('DOMContentLoaded', function () {
    const savedAddressSelect = document.getElementById('savedAddressSelect');
    const deliveryAddressInput = document.getElementById('deliveryAddressInput');
    const dLat = document.getElementById('dLat');
    const dLng = document.getElementById('dLng');

    if (!savedAddressSelect || !deliveryAddressInput) {
        return;
    }

    const applySelectedAddress = () => {
        const option = savedAddressSelect.selectedOptions[0];
        if (!option || !option.value) {
            return;
        }

        const parts = [];
        if (option.dataset.title) parts.push(option.dataset.title);
        if (option.dataset.address) parts.push(option.dataset.address);
        if (option.dataset.area) parts.push(option.dataset.area);

        deliveryAddressInput.value = parts.join(' | ');
        if (dLat && option.dataset.lat) dLat.value = option.dataset.lat;
        if (dLng && option.dataset.lng) dLng.value = option.dataset.lng;
    };

    savedAddressSelect.addEventListener('change', applySelectedAddress);
    if (savedAddressSelect.value) {
        applySelectedAddress();
    }
});
</script>
@endif
@endsection
