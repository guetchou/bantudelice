@extends('layouts.admin-modern')
@section('title', 'Nouveau Point Relais | Mema')
@section('page_title', 'Nouveau point relais')
@section('nav_active', 'relay-points')

@section('style')
<style>
.rly-page { padding:24px; }
.rly-card { background:#fff; border:1px solid #e5e7eb; border-radius:10px; overflow:hidden; }
.rly-card__body { padding:20px; }
.rly-card__footer { padding:14px 20px; border-top:1px solid #f3f4f6; display:flex; gap:10px; }
.rly-form-grid { display:grid; gap:14px; margin-bottom:14px; }
.rly-form-grid--2 { grid-template-columns:1fr 1fr; }
.rly-form-grid--3 { grid-template-columns:1fr 1fr 1fr; }
.rly-field { display:flex; flex-direction:column; gap:5px; }
.rly-label { font-size:13px; font-weight:600; color:#374151; }
.rly-input { width:100%; padding:9px 12px; border:1px solid #d1d5db; border-radius:6px; font-size:14px; color:#111827; background:#fff; box-sizing:border-box; }
.rly-input:focus { outline:none; border-color:#1e3a5f; box-shadow:0 0 0 3px rgba(30,58,95,.12); }
.rly-btn-primary { display:inline-flex; align-items:center; gap:6px; padding:9px 20px; background:#1e3a5f; color:#fff; border:none; border-radius:6px; font-size:13px; font-weight:600; cursor:pointer; }
.rly-btn-cancel { display:inline-flex; align-items:center; padding:9px 18px; border:1px solid #d1d5db; border-radius:6px; font-size:13px; font-weight:600; color:#374151; background:#fff; text-decoration:none; }
.rly-btn-cancel:hover { background:#f9fafb; color:#111827; text-decoration:none; }
@media (max-width:768px) { .rly-form-grid--2, .rly-form-grid--3 { grid-template-columns:1fr; } }
</style>
@endsection

@section('content')
<div class="rly-page">
    <div class="rly-card">
        <form action="{{ route('admin.relay-points.store') }}" method="POST">
            @csrf
            <div class="rly-card__body">
                <div class="rly-form-grid rly-form-grid--2">
                    <div class="rly-field">
                        <label class="rly-label">Nom du partenaire</label>
                        <input type="text" name="name" class="rly-input" placeholder="Ex : Station Total Poto-Poto" required>
                    </div>
                    <div class="rly-field">
                        <label class="rly-label">Téléphone de contact</label>
                        <input type="text" name="contact_phone" class="rly-input" placeholder="06 000 00 00">
                    </div>
                </div>
                <div class="rly-form-grid rly-form-grid--3">
                    <div class="rly-field">
                        <label class="rly-label">Ville</label>
                        <input type="text" name="city" class="rly-input" value="Brazzaville" required>
                    </div>
                    <div class="rly-field">
                        <label class="rly-label">Quartier</label>
                        <input type="text" name="district" class="rly-input" required>
                    </div>
                    <div class="rly-field">
                        <label class="rly-label">Adresse exacte</label>
                        <input type="text" name="address" class="rly-input" required>
                    </div>
                </div>
                <div class="rly-field">
                    <label class="rly-label">Horaires d'ouverture</label>
                    <input type="text" name="opening_hours" class="rly-input" placeholder="Ex : 8h00 - 20h00">
                </div>
            </div>
            <div class="rly-card__footer">
                <button type="submit" class="rly-btn-primary"><i class="fas fa-save"></i> Enregistrer</button>
                <a href="{{ route('admin.relay-points.index') }}" class="rly-btn-cancel">Annuler</a>
            </div>
        </form>
    </div>
</div>
@endsection
