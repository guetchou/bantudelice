@extends('layouts.admin-modern')
@section('title', 'Gestion des charges | Finance')
@section('page_title', 'Paramètres')
@section('nav_active', 'settings')

@section('style')
<style>
.chg-page { padding:24px; }
.chg-alert { padding:12px 16px; border-radius:8px; font-size:13px; font-weight:500; border:1px solid transparent; margin-bottom:16px; }
.chg-alert--success { background:#f0fdf4; color:#166534; border-color:#bbf7d0; }
.chg-alert--danger  { background:#fef2f2; color:#991b1b; border-color:#fecaca; }

.chg-layout { display:grid; grid-template-columns:1fr 1fr; gap:20px; align-items:start; }
.chg-card { background:#fff; border:1px solid #e5e7eb; border-radius:10px; overflow:hidden; }
.chg-card__header { padding:14px 20px; border-bottom:1px solid #f3f4f6; display:flex; align-items:center; gap:8px; }
.chg-card__title { font-size:14px; font-weight:700; color:#111827; margin:0; }
.chg-card__body { padding:20px; }
.chg-card__footer { padding:14px 20px; border-top:1px solid #f3f4f6; display:flex; justify-content:flex-end; gap:10px; }

.chg-field { display:flex; flex-direction:column; gap:5px; margin-bottom:14px; }
.chg-label { font-size:13px; font-weight:600; color:#374151; }
.chg-label sup { color:#ef4444; }
.chg-input { width:100%; padding:9px 12px; border:1px solid #d1d5db; border-radius:6px; font-size:14px; color:#111827; background:#fff; transition:border-color .15s; box-sizing:border-box; }
.chg-input:focus { outline:none; border-color:#1e3a5f; box-shadow:0 0 0 3px rgba(30,58,95,.12); }
.chg-input-sm { padding:6px 10px; font-size:13px; }
.chg-hint { font-size:11px; color:#9ca3af; margin-top:3px; }

.chg-btn-primary { display:inline-flex; align-items:center; gap:6px; padding:8px 18px; background:#1e3a5f; color:#fff; border:none; border-radius:6px; font-size:13px; font-weight:600; cursor:pointer; transition:opacity .15s; }
.chg-btn-primary:hover { opacity:.85; }
.chg-btn-reset { display:inline-flex; align-items:center; padding:8px 16px; border:1px solid #d1d5db; border-radius:6px; font-size:13px; font-weight:600; color:#374151; background:#fff; cursor:pointer; transition:background .15s; }
.chg-btn-reset:hover { background:#f9fafb; }

.chg-table-wrap { overflow-x:auto; }
.chg-table { width:100%; border-collapse:collapse; font-size:13px; }
.chg-table thead th { padding:9px 14px; font-size:10px; font-weight:700; letter-spacing:.06em; text-transform:uppercase; color:#9ca3af; border-bottom:1px solid #f3f4f6; background:#f9fafb; text-align:left; white-space:nowrap; }
.chg-table tbody td { padding:10px 14px; color:#374151; vertical-align:middle; border-bottom:1px solid #f3f4f6; }
.chg-table tbody tr:last-child td { border-bottom:none; }

.chg-gif-card { background:#f9fafb; border:1px solid #e5e7eb; border-radius:10px; overflow:hidden; display:flex; align-items:center; justify-content:center; padding:20px; }

@media (max-width:768px) { .chg-layout { grid-template-columns:1fr; } }
</style>
@endsection

@section('content')
<div class="chg-page">

    @if(session()->has('alert'))
        <div class="chg-alert chg-alert--{{ session()->get('alert.type') }}">
            {{ session()->get('alert.message') }}
        </div>
    @endif

    @if(!$charge)
    {{-- ── Création ── --}}
    <div class="chg-layout">
        <div class="chg-card">
            <div class="chg-card__header">
                <i class="fas fa-plus-circle" style="color:#1e3a5f;"></i>
                <h2 class="chg-card__title">Ajouter des charges</h2>
            </div>
            <form role="form" method="post" action="{{ route('charge.store') }}">
                @csrf
                <div class="chg-card__body">
                    <div class="chg-field">
                        <label class="chg-label" for="service_fee">Frais de service (%) <sup>*</sup></label>
                        <input type="text" class="chg-input" name="service_fee" id="service_fee" placeholder="Ex : 10" required>
                        <span class="chg-hint">Pourcentage des frais de service</span>
                    </div>
                    <div class="chg-field">
                        <label class="chg-label" for="tax">Taxe (%) <sup>*</sup></label>
                        <input type="text" class="chg-input" name="tax" id="tax" placeholder="Ex : 5" required>
                        <span class="chg-hint">Pourcentage de taxe</span>
                    </div>
                    <div class="chg-field">
                        <label class="chg-label" for="delivery_fee">Frais de livraison (FCFA) <sup>*</sup></label>
                        <input type="text" class="chg-input" name="delivery_fee" id="delivery_fee" placeholder="Ex : 2000" required>
                        <span class="chg-hint">Montant fixe pour la livraison</span>
                    </div>
                    <div class="chg-field">
                        <label class="chg-label" for="pickup_fee">Frais de retrait (FCFA)</label>
                        <input type="text" class="chg-input" name="pickup_fee" id="pickup_fee" placeholder="Ex : 500">
                        <span class="chg-hint">Montant fixe pour le retrait sur place</span>
                    </div>
                </div>
                <div class="chg-card__footer">
                    <button type="reset" class="chg-btn-reset">
                        <i class="fas fa-times"></i> Annuler
                    </button>
                    <button type="submit" class="chg-btn-primary">
                        <i class="fas fa-save"></i> Enregistrer
                    </button>
                </div>
            </form>
        </div>

        <div class="chg-gif-card">
            <img src="{{ asset('images/banner-in-gif.gif') }}" alt="Finance" style="max-width:100%; border-radius:8px;">
        </div>
    </div>

    @else
    {{-- ── Édition inline ── --}}
    <div class="chg-card">
        <div class="chg-card__header">
            <i class="fas fa-cog" style="color:#1e3a5f;"></i>
            <h2 class="chg-card__title">Gestion des charges</h2>
        </div>
        <div class="chg-table-wrap">
            <table class="chg-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Frais de service (%)</th>
                        <th>Taxe (%)</th>
                        <th>Frais livraison (FCFA)</th>
                        <th>Frais retrait (FCFA)</th>
                        <th>Modifié le</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <form role="form" method="post" action="{{ route('charge.update', $charge) }}">
                            @csrf
                            @method('PUT')
                            <td>1</td>
                            <td><input class="chg-input chg-input-sm" type="text" name="service_fee" value="{{ $charge->service_fee }}" required></td>
                            <td><input class="chg-input chg-input-sm" type="text" name="tax" value="{{ $charge->tax }}" required></td>
                            <td><input class="chg-input chg-input-sm" type="text" name="delivery_fee" value="{{ $charge->delivery_fee }}" required></td>
                            <td><input class="chg-input chg-input-sm" type="text" name="pickup_fee" value="{{ $charge->pickup_fee }}"></td>
                            <td>{{ $charge->updated_at->format('d/m/Y H:i') }}</td>
                            <td>
                                <button type="submit" class="chg-btn-primary" style="padding:6px 14px;font-size:12px;">
                                    <i class="fas fa-save"></i> Mettre à jour
                                </button>
                        </form>
                            </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    @endif

</div>
@endsection
