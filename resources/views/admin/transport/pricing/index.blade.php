@extends('layouts.admin-modern')
@section('title', 'Règles de Tarification Transport')
@section('page_title', 'Tarification transport')
@section('nav_active', 'transport')

@section('style')
<style>
.tpr-page { padding:24px; }
.tpr-topbar { display:flex; justify-content:space-between; align-items:center; margin-bottom:16px; }
.tpr-btn-primary { display:inline-flex; align-items:center; gap:6px; padding:8px 16px; background:#1e3a5f; color:#fff; border:none; border-radius:6px; font-size:13px; font-weight:600; cursor:pointer; text-decoration:none; }
.tpr-btn-primary:hover { opacity:.85; color:#fff; text-decoration:none; }
.tpr-btn-secondary { display:inline-flex; align-items:center; padding:8px 16px; border:1px solid #d1d5db; border-radius:6px; font-size:13px; font-weight:600; color:#374151; background:#fff; cursor:pointer; }
.tpr-card { background:#fff; border:1px solid #e5e7eb; border-radius:10px; overflow:hidden; }
.tpr-card__header { display:flex; align-items:center; justify-content:space-between; padding:14px 20px; border-bottom:1px solid #f3f4f6; }
.tpr-card__title { font-size:14px; font-weight:700; color:#111827; margin:0; }
.tpr-table-wrap { overflow-x:auto; }
.tpr-table { width:100%; border-collapse:collapse; font-size:13px; }
.tpr-table thead th { padding:9px 14px; font-size:10px; font-weight:700; letter-spacing:.06em; text-transform:uppercase; color:#9ca3af; border-bottom:1px solid #f3f4f6; background:#f9fafb; text-align:left; }
.tpr-table tbody td { padding:10px 14px; color:#374151; vertical-align:middle; border-bottom:1px solid #f3f4f6; }
.tpr-table tbody tr:last-child td { border-bottom:none; }
.tpr-pill { display:inline-flex; align-items:center; padding:3px 10px; border-radius:20px; font-size:11px; font-weight:600; }
.tpr-pill--success { background:#d1fae5; color:#065f46; }
.tpr-pill--danger  { background:#fee2e2; color:#991b1b; }
.tpr-dialog { border:none; border-radius:12px; padding:0; max-width:480px; width:100%; box-shadow:0 20px 60px rgba(0,0,0,.18); }
.tpr-dialog::backdrop { background:rgba(0,0,0,.45); }
.tpr-dialog__header { display:flex; align-items:center; justify-content:space-between; padding:14px 20px; border-bottom:1px solid #f3f4f6; background:#1e3a5f; }
.tpr-dialog__title { font-size:14px; font-weight:700; color:#fff; margin:0; }
.tpr-dialog__close { background:none; border:none; font-size:18px; color:#fff; cursor:pointer; }
.tpr-dialog__body { padding:20px; display:grid; gap:14px; }
.tpr-dialog__footer { display:flex; justify-content:space-between; gap:10px; padding:14px 20px; border-top:1px solid #f3f4f6; }
.tpr-field { display:flex; flex-direction:column; gap:5px; }
.tpr-label { font-size:13px; font-weight:600; color:#374151; }
.tpr-input { padding:9px 12px; border:1px solid #d1d5db; border-radius:6px; font-size:14px; width:100%; box-sizing:border-box; }
</style>
@endsection

@section('content')
<div class="tpr-page">
    <div class="tpr-topbar">
        <div></div>
        <button type="button" class="tpr-btn-primary" onclick="document.getElementById('dlg-add-rule').showModal()">
            <i class="fas fa-plus"></i> Ajouter une règle
        </button>
    </div>

    <div class="tpr-card">
        <div class="tpr-card__header">
            <h3 class="tpr-card__title">Paramètres de tarification</h3>
        </div>
        <div class="tpr-table-wrap">
            <table class="tpr-table">
                <thead>
                    <tr>
                        <th>Service</th>
                        <th>Base Fare</th>
                        <th>Prix / KM</th>
                        <th>Prix / Minute</th>
                        <th>Min Fare</th>
                        <th>Surge</th>
                        <th>Statut</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($rules as $rule)
                    <tr>
                        <td><b>{{ $rule->type->label() }}</b></td>
                        <td>{{ number_format($rule->base_fare, 0) }} FCFA</td>
                        <td>{{ number_format($rule->price_per_km, 0) }} FCFA</td>
                        <td>{{ number_format($rule->price_per_minute, 0) }} FCFA</td>
                        <td>{{ number_format($rule->minimum_fare, 0) }} FCFA</td>
                        <td>{{ $rule->surge_multiplier }}x</td>
                        <td>
                            <span class="tpr-pill {{ $rule->is_active ? 'tpr-pill--success' : 'tpr-pill--danger' }}">
                                {{ $rule->is_active ? 'Actif' : 'Inactif' }}
                            </span>
                        </td>
                        <td>
                            <button type="button" class="tpr-btn-primary" style="padding:5px 12px;font-size:12px;">Modifier</button>
                        </td>
                    </tr>
                    @endforeach
                    @if($rules->isEmpty())
                    <tr>
                        <td colspan="8" style="text-align:center;color:#9ca3af;padding:32px;font-size:13px;">Aucune règle définie. Les prix par défaut du système seront utilisés.</td>
                    </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>
</div>

<dialog class="tpr-dialog" id="dlg-add-rule">
    <div class="tpr-dialog__header">
        <h4 class="tpr-dialog__title">Ajouter une règle</h4>
        <button class="tpr-dialog__close" onclick="this.closest('dialog').close()">&times;</button>
    </div>
    <form action="{{ route('admin.transport.pricing.store') }}" method="POST">
        @csrf
        <div class="tpr-dialog__body">
            <div class="tpr-field">
                <label class="tpr-label">Type de service</label>
                <select name="type" class="tpr-input" required>
                    <option value="taxi">Taxi</option>
                    <option value="carpool">Covoiturage</option>
                    <option value="rental">Location</option>
                </select>
            </div>
            <div class="tpr-field">
                <label class="tpr-label">Frais de base (FCFA)</label>
                <input type="number" name="base_fare" class="tpr-input" value="0" required>
            </div>
            <div class="tpr-field">
                <label class="tpr-label">Prix par KM (FCFA)</label>
                <input type="number" name="price_per_km" class="tpr-input" value="0" required>
            </div>
            <div class="tpr-field">
                <label class="tpr-label">Prix par Minute (FCFA)</label>
                <input type="number" name="price_per_minute" class="tpr-input" value="0" required>
            </div>
            <div class="tpr-field">
                <label class="tpr-label">Prix minimum (FCFA)</label>
                <input type="number" name="minimum_fare" class="tpr-input" value="0" required>
            </div>
        </div>
        <div class="tpr-dialog__footer">
            <button type="button" class="tpr-btn-secondary" onclick="this.closest('dialog').close()">Fermer</button>
            <button type="submit" class="tpr-btn-primary">Enregistrer</button>
        </div>
    </form>
</dialog>
@endsection
