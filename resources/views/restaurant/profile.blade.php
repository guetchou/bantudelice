@extends('layouts.restaurant_app')
@section('title', 'Paramètres | ' . \App\Services\ConfigService::getCompanyName())
@section('topbar_title', 'Paramètres')
@section('profile_nav', 'active')

@section('style')
<style>
/* ── Layout onglets horizontaux ────────────────────────── */
.cfg { display: flex; flex-direction: column; gap: 0; }

/* Barre d'onglets */
.cfg-tabs {
    display: flex; gap: 0;
    border-bottom: 1px solid var(--bd-border);
    margin-bottom: 24px;
    overflow-x: auto;
}
.cfg-tab {
    display: inline-flex; align-items: center; gap: 7px;
    padding: 10px 18px; white-space: nowrap;
    font-size: 13px; font-weight: 500;
    color: var(--bd-text-2); background: transparent; border: none;
    border-bottom: 2px solid transparent;
    cursor: pointer; font-family: var(--bd-font);
    transition: color .12s;
}
.cfg-tab i { font-size: 12px; }
.cfg-tab:hover { color: var(--bd-text); }
.cfg-tab.is-active { color: var(--bd-green); border-bottom-color: var(--bd-green); font-weight: 600; }

/* Panels */
.cfg-panel { display: none; }
.cfg-panel.is-active { display: block; }

/* Card section */
.cfg-card {
    background: var(--bd-surface);
    border: 1px solid var(--bd-border);
    border-radius: var(--bd-radius);
    overflow: hidden;
    transition: background .2s;
    margin-bottom: 16px;
}
.cfg-card:last-child { margin-bottom: 0; }
.cfg-card__head {
    padding: 14px 20px;
    border-bottom: 1px solid var(--bd-border-2);
    display: flex; align-items: center; gap: 10px;
}
.cfg-card__icon {
    width: 30px; height: 30px; border-radius: 7px;
    background: var(--bd-green-pale); color: var(--bd-green);
    display: flex; align-items: center; justify-content: center;
    font-size: 12px; flex-shrink: 0;
}
[data-theme="dark"] .cfg-card__icon { background: rgba(0,149,67,.12); }
.cfg-card__title { font-size: 13px; font-weight: 600; color: var(--bd-text); }
.cfg-card__sub   { font-size: 11px; color: var(--bd-text-3); margin-top: 1px; }
.cfg-card__body  { padding: 20px; }

/* Grilles */
.cfg-g2 { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
.cfg-g3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 14px; }

/* Champs */
.cfg-field { display: flex; flex-direction: column; gap: 5px; margin-bottom: 14px; }
.cfg-field:last-child, .cfg-field--last { margin-bottom: 0; }
.cfg-label {
    font-size: 11px; font-weight: 700; letter-spacing: .05em;
    text-transform: uppercase; color: var(--bd-text-2);
}
.cfg-hint { font-size: 11px; color: var(--bd-text-3); margin-top: 2px; }
.cfg-hint--warn { color: #d97706; }
.cfg-input-wrap { position: relative; }
.cfg-input-icon { position: absolute; left: 10px; top: 50%; transform: translateY(-50%); font-size: 12px; color: var(--bd-text-3); pointer-events: none; }
.cfg-input {
    width: 100%; box-sizing: border-box;
    padding: 9px 12px; border: 1px solid var(--bd-border);
    border-radius: var(--bd-radius); font-size: 13px;
    font-family: var(--bd-font); background: var(--bd-surface);
    color: var(--bd-text); outline: none; transition: border-color .12s;
}
.cfg-input:focus { border-color: var(--bd-green); }
.cfg-input:disabled { opacity: .6; cursor: not-allowed; background: var(--bd-surface-2); }
.cfg-input--error { border-color: #dc2626; }
.cfg-input-wrap .cfg-input { padding-left: 30px; }
.cfg-input-wrap--eye .cfg-input { padding-right: 36px; }
.cfg-eye {
    position: absolute; right: 10px; top: 50%; transform: translateY(-50%);
    background: none; border: none; cursor: pointer; color: var(--bd-text-3);
    font-size: 13px; padding: 2px; transition: color .12s;
}
.cfg-eye:hover { color: var(--bd-text); }

/* Badge */
.cfg-badge {
    display: inline-flex; align-items: center; gap: 4px;
    padding: 2px 8px; border-radius: 999px;
    font-size: 10px; font-weight: 700;
}
.cfg-badge--green { background: var(--bd-green-pale); color: var(--bd-green); }
.cfg-badge--amber { background: rgba(245,158,11,.1); color: #d97706; }
[data-theme="dark"] .cfg-badge--green { background: rgba(0,149,67,.12); color: #00c957; }
[data-theme="dark"] .cfg-badge--amber { background: rgba(245,158,11,.12); color: #fbbf24; }

/* Info note */
.cfg-note {
    display: flex; gap: 9px;
    padding: 10px 14px; margin-bottom: 16px;
    background: var(--bd-surface-2); border: 1px solid var(--bd-border);
    border-left: 3px solid var(--bd-green);
    border-radius: 0 var(--bd-radius) var(--bd-radius) 0;
    font-size: 12px; color: var(--bd-text-2);
}
.cfg-note i { color: var(--bd-green); flex-shrink: 0; margin-top: 1px; }

/* Médias upload */
.cfg-media {
    background: var(--bd-surface-2); border: 1px solid var(--bd-border);
    border-radius: var(--bd-radius); padding: 16px;
}
.cfg-media__title { font-size: 12px; font-weight: 700; color: var(--bd-text); margin-bottom: 3px; }
.cfg-media__sub   { font-size: 11px; color: var(--bd-text-3); margin-bottom: 12px; }
.cfg-media__row   { display: flex; gap: 12px; align-items: flex-start; }
.cfg-media__thumb-logo  { width:72px;height:72px;border-radius:8px;object-fit:cover;border:1px solid var(--bd-border);flex-shrink:0; }
.cfg-media__thumb-cover { width:100%;height:130px;border-radius:8px;object-fit:cover;border:1px solid var(--bd-border);margin-bottom:12px;display:block; }
.cfg-media__inputs { flex: 1; min-width: 0; display: flex; flex-direction: column; gap: 8px; }

/* Bouton submit */
.cfg-submit {
    display: inline-flex; align-items: center; gap: 7px;
    padding: 9px 20px; border-radius: 8px; border: none;
    background: var(--bd-green); color: #fff;
    font-size: 13px; font-weight: 600; font-family: var(--bd-font);
    cursor: pointer; transition: background .12s;
}
.cfg-submit:hover { background: var(--bd-green-dark); }

/* Table horaires */
.cfg-table { width:100%;border-collapse:collapse; }
.cfg-table th { padding:9px 14px;font-size:10px;font-weight:700;letter-spacing:.07em;text-transform:uppercase;color:var(--bd-text-3);border-bottom:1px solid var(--bd-border-2);background:var(--bd-surface-2); }
.cfg-table td { padding:10px 14px;font-size:13px;color:var(--bd-text);border-bottom:1px solid var(--bd-border-2);vertical-align:middle; }
.cfg-table tr:last-child td { border-bottom:none; }
.cfg-table tr:hover td { background:var(--bd-surface-2); }
.cfg-table-actions { display:flex;gap:6px; }
.cfg-action-btn {
    display:inline-flex;align-items:center;gap:5px;
    padding:5px 10px;border-radius:6px;border:1px solid var(--bd-border);
    background:var(--bd-surface);color:var(--bd-text-2);
    font-size:12px;font-weight:500;font-family:var(--bd-font);cursor:pointer;
    text-decoration:none;transition:.12s;
}
.cfg-action-btn:hover { border-color:var(--bd-green);color:var(--bd-green); }
.cfg-action-btn--danger:hover { border-color:#dc2626;color:#dc2626; }

/* Responsive */
@media (max-width: 768px) {
    .cfg-g2, .cfg-g3 { grid-template-columns: 1fr; }
}
</style>
@endsection

@section('content')
@php
    $rp     = $restaurantProfile ?? null;
    $rUser  = auth()->user();
    $rData  = $rUser?->restaurant;
    $logoSrc  = ($rp && $rp->logo)
        ? (str_starts_with($rp->logo,   'http') ? $rp->logo   : asset('images/restaurant_images/' . $rp->logo))
        : asset('images/placeholder.png');
    $coverSrc = ($rp && $rp->cover_image)
        ? (str_starts_with($rp->cover_image, 'http') ? $rp->cover_image : asset('images/restaurant_images/' . $rp->cover_image))
        : $logoSrc;
    $activeTab = old('profile_section', session('active_profile_tab', 'restaurant'));
@endphp

@if(session()->has('alert'))
    <div class="alert alert-{{ session('alert.type') }} alert-dismissible fade show mb-4" role="alert">
        <i class="fas fa-{{ session('alert.type') === 'success' ? 'check-circle' : 'exclamation-circle' }} mr-2"></i>
        {{ session('alert.message') }}
        <button type="button" class="close" data-dismiss="alert">&times;</button>
    </div>
@endif

<div class="cfg">

    {{-- ── Onglets ─────────────────────────────────────────── --}}
    <div class="cfg-tabs">
        <button class="cfg-tab {{ $activeTab === 'restaurant' ? 'is-active' : '' }}" onclick="cfgSwitch('restaurant',this)">
            <i class="fas fa-store"></i> Restaurant
        </button>
        <button class="cfg-tab {{ $activeTab === 'horaires'   ? 'is-active' : '' }}" onclick="cfgSwitch('horaires',this)">
            <i class="fas fa-clock"></i> Horaires
        </button>
        <button class="cfg-tab {{ $activeTab === 'livraison'  ? 'is-active' : '' }}" onclick="cfgSwitch('livraison',this)">
            <i class="fas fa-motorcycle"></i> Livraison
        </button>
        <button class="cfg-tab {{ $activeTab === 'promotions' ? 'is-active' : '' }}" onclick="cfgSwitch('promotions',this)">
            <i class="fas fa-tags"></i> Promotions
        </button>
        <button class="cfg-tab {{ $activeTab === 'compte'     ? 'is-active' : '' }}" onclick="cfgSwitch('compte',this)">
            <i class="fas fa-user"></i> Compte
        </button>
    </div>

    {{-- ══════════════════════════════════════════════════════
         ONGLET RESTAURANT
         ══════════════════════════════════════════════════════ --}}
    <div class="cfg-panel {{ $activeTab === 'restaurant' ? 'is-active' : '' }}" id="cfg-panel-restaurant">
        @if(!$rp)
            <div class="alert alert-warning"><i class="fas fa-exclamation-triangle mr-2"></i>Aucun restaurant associé. Contactez l'administrateur.</div>
        @else
        <form action="{{ route('restaurant.profile.profile_update') }}" method="post" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="profile_section" value="restaurant">

            {{-- Identité --}}
            <div class="cfg-card">
                <div class="cfg-card__head">
                    <div class="cfg-card__icon"><i class="fas fa-store"></i></div>
                    <div>
                        <div class="cfg-card__title">Identité publique</div>
                        <div class="cfg-card__sub">Informations visibles par les clients sur l'application</div>
                    </div>
                </div>
                <div class="cfg-card__body">
                    <div class="cfg-g2" style="margin-bottom:14px;">
                        <div class="cfg-field cfg-field--last">
                            <label class="cfg-label">Nom du restaurant</label>
                            <input type="text" name="restaurant_name" value="{{ $rp->name }}" class="cfg-input" autocomplete="off">
                        </div>
                        <div class="cfg-field cfg-field--last">
                            <label class="cfg-label">Ville</label>
                            <input type="text" name="city" value="{{ $rp->city }}" class="cfg-input" autocomplete="off">
                        </div>
                    </div>
                    <div class="cfg-field" style="margin-bottom:14px;">
                        <label class="cfg-label">Adresse complète</label>
                        <input type="text" name="address" value="{{ $rp->address }}" class="cfg-input" autocomplete="off">
                    </div>
                    <div class="cfg-field" style="margin-bottom:14px;">
                        <label class="cfg-label">Slogan <span style="font-weight:400;text-transform:none;letter-spacing:0;">(accroche client)</span></label>
                        <input type="text" name="slogan" value="{{ $rp->slogan }}" class="cfg-input" placeholder="Ex : Grillades et spécialités du Congo" autocomplete="off">
                    </div>
                    <div class="cfg-field cfg-field--last">
                        <label class="cfg-label">Description</label>
                        <textarea name="description" class="cfg-input" rows="3" style="resize:vertical;">{{ $rp->description }}</textarea>
                    </div>
                </div>
            </div>

            {{-- Visuels --}}
            <div class="cfg-card">
                <div class="cfg-card__head">
                    <div class="cfg-card__icon"><i class="fas fa-image"></i></div>
                    <div>
                        <div class="cfg-card__title">Identité visuelle</div>
                        <div class="cfg-card__sub">Photo et bannière affichées aux clients</div>
                    </div>
                </div>
                <div class="cfg-card__body">
                    <div class="cfg-g2">
                        <div class="cfg-media">
                            <div class="cfg-media__title"><span class="cfg-badge cfg-badge--green" style="margin-right:5px;">Visible partout</span> Photo principale</div>
                            <div class="cfg-media__sub">Dashboard, listes et cartes restaurant</div>
                            <div class="cfg-media__row">
                                <img id="cfgLogoPreview" src="{{ $logoSrc }}" alt="Logo" class="cfg-media__thumb-logo">
                                <div class="cfg-media__inputs">
                                    <div class="cfg-field cfg-field--last">
                                        <label class="cfg-label">Fichier <span style="font-weight:400;text-transform:none;letter-spacing:0;">PNG/JPG ≤ 4MB</span></label>
                                        <input type="file" name="logo" accept="image/*" class="cfg-input" onchange="cfgPreviewFile(this,'cfgLogoPreview')">
                                    </div>
                                    <div class="cfg-field cfg-field--last">
                                        <label class="cfg-label">URL directe</label>
                                        <input type="url" name="logo_url" value="{{ str_starts_with($rp->logo ?? '', 'http') ? $rp->logo : '' }}" class="cfg-input" placeholder="https://..." oninput="cfgPreviewUrl('cfgLogoPreview',this.value)">
                                    </div>
                                    @include('partials.unified_media_select', ['name'=>'logo_media_path','label'=>'Médiathèque','options'=>$mediaLibraryOptions??[],'previewTarget'=>'cfgLogoPreview'])
                                </div>
                            </div>
                        </div>
                        <div class="cfg-media">
                            <div class="cfg-media__title"><span class="cfg-badge cfg-badge--amber" style="margin-right:5px;">Page restaurant</span> Couverture</div>
                            <div class="cfg-media__sub">Bannière en en-tête de votre page publique</div>
                            <img id="cfgCoverPreview" src="{{ $coverSrc }}" alt="Couverture" class="cfg-media__thumb-cover">
                            <div class="cfg-media__inputs">
                                <div class="cfg-field cfg-field--last">
                                    <label class="cfg-label">Fichier <span style="font-weight:400;text-transform:none;letter-spacing:0;">PNG/JPG ≤ 6MB</span></label>
                                    <input type="file" name="cover_image" accept="image/*" class="cfg-input" onchange="cfgPreviewFile(this,'cfgCoverPreview')">
                                </div>
                                <div class="cfg-field cfg-field--last">
                                    <label class="cfg-label">URL directe</label>
                                    <input type="url" name="cover_image_url" value="{{ str_starts_with($rp->cover_image ?? '', 'http') ? $rp->cover_image : '' }}" class="cfg-input" placeholder="https://..." oninput="cfgPreviewUrl('cfgCoverPreview',this.value)">
                                </div>
                                @include('partials.unified_media_select', ['name'=>'cover_image_media_path','label'=>'Médiathèque','options'=>$mediaLibraryOptions??[],'previewTarget'=>'cfgCoverPreview'])
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <button type="submit" class="cfg-submit"><i class="fas fa-check"></i> Enregistrer</button>
        </form>
        @endif
    </div>

    {{-- ══════════════════════════════════════════════════════
         ONGLET HORAIRES
         ══════════════════════════════════════════════════════ --}}
    <div class="cfg-panel {{ $activeTab === 'horaires' ? 'is-active' : '' }}" id="cfg-panel-horaires">
        <div class="cfg-card">
            <div class="cfg-card__head">
                <div class="cfg-card__icon"><i class="fas fa-clock"></i></div>
                <div style="flex:1;">
                    <div class="cfg-card__title">Horaires d'ouverture</div>
                    <div class="cfg-card__sub">Jours et créneaux affichés aux clients</div>
                </div>
                <a href="{{ route('working_hour.create') }}" class="cfg-submit" style="font-size:12px;padding:7px 14px;">
                    <i class="fas fa-plus"></i> Ajouter
                </a>
            </div>
            <div class="cfg-card__body" style="padding:0;">
                @if(isset($workingHours) && count($workingHours))
                <table class="cfg-table">
                    <thead><tr><th>Jour</th><th>Ouverture</th><th>Fermeture</th><th>Statut</th><th></th></tr></thead>
                    <tbody>
                        @foreach($workingHours as $wh)
                        <tr>
                            <td style="font-weight:600;">{{ $wh->day ?? '—' }}</td>
                            <td>{{ $wh->opening_time ?? '—' }}</td>
                            <td>{{ $wh->closing_time ?? '—' }}</td>
                            <td>
                                @if($wh->status ?? true)
                                    <span class="cfg-badge cfg-badge--green">Ouvert</span>
                                @else
                                    <span class="cfg-badge cfg-badge--amber">Fermé</span>
                                @endif
                            </td>
                            <td>
                                <div class="cfg-table-actions">
                                    <a href="{{ route('working_hour.edit', $wh->id) }}" class="cfg-action-btn"><i class="fas fa-pen"></i></a>
                                    <form method="POST" action="{{ route('working_hour.destroy', $wh->id) }}" onsubmit="return confirm('Supprimer ?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="cfg-action-btn cfg-action-btn--danger"><i class="fas fa-trash"></i></button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                @else
                    <div style="padding:32px;text-align:center;color:var(--bd-text-3);font-size:13px;">
                        <i class="fas fa-clock" style="font-size:24px;display:block;margin-bottom:8px;"></i>
                        Aucun horaire configuré.
                        <a href="{{ route('working_hour.create') }}" style="color:var(--bd-green);font-weight:600;margin-left:4px;">Ajouter le premier créneau</a>
                    </div>
                @endif
            </div>
        </div>

        <div class="cfg-card">
            <div class="cfg-card__head">
                <div class="cfg-card__icon"><i class="fas fa-calendar-xmark"></i></div>
                <div style="flex:1;">
                    <div class="cfg-card__title">Fermetures exceptionnelles</div>
                    <div class="cfg-card__sub">Jours fériés, congés ponctuels</div>
                </div>
                <a href="{{ route('restaurant.special_closures.create') }}" class="cfg-submit" style="font-size:12px;padding:7px 14px;">
                    <i class="fas fa-plus"></i> Ajouter
                </a>
            </div>
            @if(isset($specialClosures) && count($specialClosures))
            <div class="cfg-card__body" style="padding:0;">
                <table class="cfg-table">
                    <thead><tr><th>Date</th><th>Motif</th><th></th></tr></thead>
                    <tbody>
                        @foreach($specialClosures as $sc)
                        <tr>
                            <td style="font-weight:600;">{{ \Carbon\Carbon::parse($sc->date)->locale('fr')->isoFormat('dddd D MMMM YYYY') }}</td>
                            <td style="color:var(--bd-text-2);">{{ $sc->reason ?? '—' }}</td>
                            <td>
                                <div class="cfg-table-actions">
                                    <a href="{{ route('restaurant.special_closures.edit', $sc->id) }}" class="cfg-action-btn"><i class="fas fa-pen"></i></a>
                                    <form method="POST" action="{{ route('restaurant.special_closures.destroy', $sc->id) }}" onsubmit="return confirm('Supprimer ?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="cfg-action-btn cfg-action-btn--danger"><i class="fas fa-trash"></i></button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════
         ONGLET LIVRAISON
         ══════════════════════════════════════════════════════ --}}
    <div class="cfg-panel {{ $activeTab === 'livraison' ? 'is-active' : '' }}" id="cfg-panel-livraison">
        @if(!$rp)
            <div class="alert alert-warning">Restaurant non configuré.</div>
        @else
        <form action="{{ route('restaurant.profile.profile_update') }}" method="post">
            @csrf
            <input type="hidden" name="profile_section" value="restaurant">

            <div class="cfg-card">
                <div class="cfg-card__head">
                    <div class="cfg-card__icon"><i class="fas fa-motorcycle"></i></div>
                    <div>
                        <div class="cfg-card__title">Conditions de livraison</div>
                        <div class="cfg-card__sub">Paramètres affichés sur votre fiche restaurant</div>
                    </div>
                </div>
                <div class="cfg-card__body">
                    <div class="cfg-g3">
                        <div class="cfg-field cfg-field--last">
                            <label class="cfg-label">Commande minimum (FCFA)</label>
                            <input type="number" name="min_order" value="{{ $rp->min_order }}" class="cfg-input" min="0">
                        </div>
                        <div class="cfg-field cfg-field--last">
                            <label class="cfg-label">Frais de livraison (FCFA)</label>
                            <input type="number" name="delivery_charges" value="{{ $rp->delivery_charges }}" class="cfg-input" min="0">
                        </div>
                        <div class="cfg-field cfg-field--last">
                            <label class="cfg-label">Délai moyen (ex: 30 min)</label>
                            <input type="text" name="avg_delivery_time" value="{{ $rp->avg_delivery_time }}" class="cfg-input" placeholder="30 min">
                        </div>
                    </div>
                </div>
            </div>

            <div class="cfg-card">
                <div class="cfg-card__head">
                    <div class="cfg-card__icon"><i class="fas fa-map-location-dot"></i></div>
                    <div style="flex:1;">
                        <div class="cfg-card__title">Zone de livraison</div>
                        <div class="cfg-card__sub">Délimitez la zone géographique couverte</div>
                    </div>
                    <a href="{{ route('delivery_boundary') }}" class="cfg-submit" style="font-size:12px;padding:7px 14px;">
                        <i class="fas fa-map"></i> Modifier la zone
                    </a>
                </div>
            </div>

            <button type="submit" class="cfg-submit"><i class="fas fa-check"></i> Enregistrer</button>
        </form>
        @endif
    </div>

    {{-- ══════════════════════════════════════════════════════
         ONGLET PROMOTIONS
         ══════════════════════════════════════════════════════ --}}
    <div class="cfg-panel {{ $activeTab === 'promotions' ? 'is-active' : '' }}" id="cfg-panel-promotions">
        <div class="cfg-card">
            <div class="cfg-card__head">
                <div class="cfg-card__icon"><i class="fas fa-tags"></i></div>
                <div style="flex:1;">
                    <div class="cfg-card__title">Bons de réduction</div>
                    <div class="cfg-card__sub">Codes promo actifs pour vos clients</div>
                </div>
                <a href="{{ route('voucher.create') }}" class="cfg-submit" style="font-size:12px;padding:7px 14px;">
                    <i class="fas fa-plus"></i> Nouveau bon
                </a>
            </div>
            <div class="cfg-card__body" style="padding:0;">
                @if(isset($vouchers) && count($vouchers))
                <table class="cfg-table">
                    <thead><tr><th>Code</th><th>Remise</th><th>Utilisations</th><th>Expiration</th><th>Statut</th><th></th></tr></thead>
                    <tbody>
                        @foreach($vouchers as $v)
                        <tr>
                            <td style="font-weight:700;font-family:monospace;letter-spacing:.05em;">{{ $v->code }}</td>
                            <td>
                                @if($v->discount_type === 'percentage')
                                    {{ $v->discount }}%
                                @else
                                    {{ number_format((float)($v->discount_value ?? $v->discount ?? 0),0,',',' ') }} FCFA
                                @endif
                            </td>
                            <td style="color:var(--bd-text-2);">{{ $v->used_count ?? 0 }} / {{ $v->usage_limit ?? '∞' }}</td>
                            @php $vExp = $v->ends_at ?? $v->end_date ?? $v->expiry_date ?? null; @endphp
                            <td style="color:var(--bd-text-2);">{{ $vExp ? \Carbon\Carbon::parse($vExp)->format('d/m/Y') : '—' }}</td>
                            <td>
                                @if($v->is_active ?? $v->status ?? true)
                                    <span class="cfg-badge cfg-badge--green">Actif</span>
                                @else
                                    <span class="cfg-badge cfg-badge--amber">Inactif</span>
                                @endif
                            </td>
                            <td>
                                <div class="cfg-table-actions">
                                    <a href="{{ route('voucher.edit', $v->id) }}" class="cfg-action-btn"><i class="fas fa-pen"></i></a>
                                    <form method="POST" action="{{ route('voucher.destroy', $v->id) }}" onsubmit="return confirm('Supprimer ce bon ?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="cfg-action-btn cfg-action-btn--danger"><i class="fas fa-trash"></i></button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                @else
                    <div style="padding:32px;text-align:center;color:var(--bd-text-3);font-size:13px;">
                        <i class="fas fa-tags" style="font-size:24px;display:block;margin-bottom:8px;"></i>
                        Aucun bon de réduction.
                        <a href="{{ route('voucher.create') }}" style="color:var(--bd-green);font-weight:600;margin-left:4px;">Créer le premier</a>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════
         ONGLET COMPTE
         ══════════════════════════════════════════════════════ --}}
    <div class="cfg-panel {{ $activeTab === 'compte' ? 'is-active' : '' }}" id="cfg-panel-compte">

        <div class="cfg-card">
            <div class="cfg-card__head">
                <div class="cfg-card__icon"><i class="fas fa-user"></i></div>
                <div>
                    <div class="cfg-card__title">Informations de connexion</div>
                    <div class="cfg-card__sub">Nom et téléphone associés à votre compte</div>
                </div>
            </div>
            <div class="cfg-card__body">
                <div class="cfg-note">
                    <i class="fas fa-info-circle"></i>
                    <span>Ces informations s'appliquent à votre accès. Le nom et la ville affichés aux clients se gèrent dans l'onglet <strong>Restaurant</strong>.</span>
                </div>
                <form action="{{ route('restaurant.profile.profile_update') }}" method="post">
                    @csrf
                    <input type="hidden" name="profile_section" value="account">
                    <div class="cfg-g2" style="margin-bottom:14px;">
                        <div class="cfg-field cfg-field--last">
                            <label class="cfg-label">Nom complet</label>
                            <div class="cfg-input-wrap">
                                <i class="fas fa-user cfg-input-icon"></i>
                                <input type="text" name="name" value="{{ $restaurant->name }}" class="cfg-input {{ $errors->has('name') ? 'cfg-input--error' : '' }}" autocomplete="off" required>
                                @if($errors->has('name'))<div style="font-size:11px;color:#dc2626;margin-top:3px;">{{ $errors->first('name') }}</div>@endif
                            </div>
                        </div>
                        <div class="cfg-field cfg-field--last">
                            <label class="cfg-label">Téléphone</label>
                            <div class="cfg-input-wrap">
                                <i class="fas fa-phone cfg-input-icon"></i>
                                <input type="tel" name="phone" value="{{ $restaurant->phone }}" class="cfg-input {{ $errors->has('phone') ? 'cfg-input--error' : '' }}" autocomplete="off">
                                @if($errors->has('phone'))<div style="font-size:11px;color:#dc2626;margin-top:3px;">{{ $errors->first('phone') }}</div>@endif
                            </div>
                        </div>
                    </div>
                    <div class="cfg-field" style="margin-bottom:16px;">
                        <label class="cfg-label">Email <span class="cfg-badge cfg-badge--amber" style="vertical-align:middle;">non modifiable</span></label>
                        <div class="cfg-input-wrap">
                            <i class="fas fa-envelope cfg-input-icon"></i>
                            <input type="email" value="{{ $restaurant->email }}" class="cfg-input" disabled>
                        </div>
                    </div>
                    <button type="submit" class="cfg-submit"><i class="fas fa-check"></i> Enregistrer</button>
                </form>
            </div>
        </div>

        <div class="cfg-card">
            <div class="cfg-card__head">
                <div class="cfg-card__icon"><i class="fas fa-lock"></i></div>
                <div>
                    <div class="cfg-card__title">Mot de passe</div>
                    <div class="cfg-card__sub">Minimum 6 caractères</div>
                </div>
            </div>
            <div class="cfg-card__body" style="max-width:440px;">
                <form action="{{ route('restaurant.profile.profile_update') }}" method="post">
                    @csrf
                    <input type="hidden" name="profile_section" value="password">
                    <div class="cfg-field">
                        <label class="cfg-label">Mot de passe actuel</label>
                        <div class="cfg-input-wrap cfg-input-wrap--eye">
                            <i class="fas fa-lock cfg-input-icon"></i>
                            <input type="password" name="old_password" class="cfg-input" placeholder="Mot de passe actuel" autocomplete="current-password">
                            <button type="button" class="cfg-eye" onclick="cfgEye(this)"><i class="fas fa-eye"></i></button>
                        </div>
                    </div>
                    <div class="cfg-field">
                        <label class="cfg-label">Nouveau mot de passe</label>
                        <div class="cfg-input-wrap cfg-input-wrap--eye">
                            <i class="fas fa-key cfg-input-icon"></i>
                            <input type="password" name="password" class="cfg-input" placeholder="6 caractères minimum" autocomplete="new-password">
                            <button type="button" class="cfg-eye" onclick="cfgEye(this)"><i class="fas fa-eye"></i></button>
                        </div>
                    </div>
                    <div class="cfg-field" style="margin-bottom:16px;">
                        <label class="cfg-label">Confirmation</label>
                        <div class="cfg-input-wrap cfg-input-wrap--eye">
                            <i class="fas fa-check cfg-input-icon"></i>
                            <input type="password" name="password_confirmation" class="cfg-input" placeholder="Répétez le mot de passe" autocomplete="new-password">
                            <button type="button" class="cfg-eye" onclick="cfgEye(this)"><i class="fas fa-eye"></i></button>
                        </div>
                    </div>
                    <button type="submit" class="cfg-submit"><i class="fas fa-lock"></i> Changer le mot de passe</button>
                </form>
            </div>
        </div>

    </div>

</div>
@endsection

@section('script')
<script>
function cfgSwitch(tab, btn) {
    document.querySelectorAll('.cfg-panel').forEach(function(p){ p.classList.remove('is-active'); });
    document.querySelectorAll('.cfg-tab').forEach(function(b){ b.classList.remove('is-active'); });
    var p = document.getElementById('cfg-panel-' + tab);
    if (p)   p.classList.add('is-active');
    if (btn) btn.classList.add('is-active');
}
function cfgEye(btn) {
    var input = btn.closest('.cfg-input-wrap').querySelector('input');
    var icon  = btn.querySelector('i');
    input.type = input.type === 'password' ? 'text' : 'password';
    icon.className = input.type === 'password' ? 'fas fa-eye' : 'fas fa-eye-slash';
}
function cfgPreviewFile(input, id) {
    if (!input.files || !input.files[0]) return;
    var r = new FileReader();
    r.onload = function(e){ var el=document.getElementById(id); if(el) el.src=e.target.result; };
    r.readAsDataURL(input.files[0]);
}
function cfgPreviewUrl(id, url) {
    if (!url) return;
    var el = document.getElementById(id);
    if (el) el.src = url;
}
document.querySelectorAll('.js-unified-media-select').forEach(function(s){
    s.addEventListener('change', function(){
        var opt = this.options[this.selectedIndex];
        var target = this.dataset.previewTarget;
        var url = opt ? opt.dataset.preview : '';
        if (target && url){ var el=document.getElementById(target); if(el) el.src=url; }
    });
});
/* Ouvrir le bon onglet en cas d'erreur de validation */
@if($errors->has('name') || $errors->has('phone'))
    cfgSwitch('compte', document.querySelector('[onclick*="compte"]'));
@elseif($errors->has('restaurant_name') || $errors->has('logo') || $errors->has('cover_image'))
    cfgSwitch('restaurant', document.querySelector('[onclick*="restaurant"]'));
@elseif($errors->has('old_password') || $errors->has('password'))
    cfgSwitch('compte', document.querySelector('[onclick*="compte"]'));
@endif
</script>
@endsection
