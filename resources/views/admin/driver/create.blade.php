@extends('layouts.admin-modern')
@section('title', 'Créer un livreur | BantuDelice')
@section('page_title', 'Nouveau livreur')
@section('nav_active', 'drivers')

@section('style')
<style>
.drv-page { padding:24px; }

/* Alert */
.drv-alert { padding:12px 16px; border-radius:8px; font-size:13px; font-weight:500; border:1px solid transparent; margin-bottom:16px; }
.drv-alert--danger { background:#fef2f2; color:#991b1b; border-color:#fecaca; }

/* Cards */
.drv-card { background:#fff; border:1px solid #e5e7eb; border-radius:10px; overflow:hidden; margin-bottom:20px; }
.drv-card__header { padding:14px 20px; border-bottom:1px solid #f3f4f6; display:flex; align-items:center; gap:8px; }
.drv-card__header--yellow { background:#fef3c7; border-color:#fde68a; }
.drv-card__header--blue   { background:#eff6ff; border-color:#bfdbfe; }
.drv-card__title { font-size:14px; font-weight:700; color:#111827; margin:0; }
.drv-card__body { padding:20px; }
.drv-card__footer { padding:14px 20px; border-top:1px solid #f3f4f6; display:flex; justify-content:flex-end; align-items:center; gap:10px; }

/* Section heading */
.drv-section-title {
    display:flex; align-items:center; gap:8px;
    padding:10px 14px; margin:20px 0 16px;
    background:#1e3a5f; color:#fff;
    border-radius:6px; font-size:13px; font-weight:700;
}

/* Form grid */
.drv-form-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(200px,1fr)); gap:16px; }
.drv-form-grid--2 { grid-template-columns:repeat(2,1fr); }
.drv-form-grid--3 { grid-template-columns:repeat(3,1fr); }
.drv-field { display:flex; flex-direction:column; gap:5px; }
.drv-label { font-size:13px; font-weight:600; color:#374151; }
.drv-label sup { color:#ef4444; }
.drv-input { width:100%; padding:9px 12px; border:1px solid #d1d5db; border-radius:6px; font-size:14px; color:#111827; background:#fff; transition:border-color .15s,box-shadow .15s; box-sizing:border-box; }
.drv-input:focus { outline:none; border-color:#1e3a5f; box-shadow:0 0 0 3px rgba(30,58,95,.12); }
.drv-input--error { border-color:#ef4444; }
.drv-field-error { font-size:11px; color:#dc2626; font-weight:500; }
.drv-select { appearance:none; background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 20 20' fill='%236b7280'%3E%3Cpath fill-rule='evenodd' d='M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z'/%3E%3C/svg%3E"); background-repeat:no-repeat; background-position:right 10px center; background-size:18px; padding-right:36px; }
.drv-check-row { display:flex; align-items:center; gap:8px; margin-top:20px; }
.drv-check-row input[type=checkbox] { width:16px; height:16px; accent-color:#1e3a5f; cursor:pointer; }
.drv-check-row label { font-size:13px; color:#374151; cursor:pointer; font-weight:500; }

/* Upload zones */
.drv-uploads { display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-bottom:4px; }
.drv-upload-zone { border:2px dashed #d1d5db; border-radius:8px; padding:24px 16px; text-align:center; cursor:pointer; transition:border-color .15s; }
.drv-upload-zone:hover { border-color:#1e3a5f; }
.drv-upload-zone img { width:90px; height:90px; object-fit:contain; }
.drv-upload-zone p { margin:8px 0 0; font-size:12px; color:#6b7280; }
.drv-upload-zone span { color:#f97316; font-weight:600; font-size:12px; }
.drv-upload-zone input[type=file] { display:none; }

/* Buttons */
.drv-btn-primary { display:inline-flex; align-items:center; gap:6px; padding:8px 18px; background:#1e3a5f; color:#fff; border:none; border-radius:6px; font-size:13px; font-weight:600; cursor:pointer; transition:opacity .15s; }
.drv-btn-primary:hover { opacity:.85; }
.drv-btn-cancel { display:inline-flex; align-items:center; padding:8px 16px; border-radius:6px; font-size:13px; font-weight:600; text-decoration:none; border:1px solid #d1d5db; color:#374151; background:#fff; transition:background .15s; }
.drv-btn-cancel:hover { background:#f9fafb; color:#111827; text-decoration:none; }
.drv-btn-warn { display:inline-flex; align-items:center; gap:6px; padding:8px 18px; background:#f59e0b; color:#fff; border:none; border-radius:6px; font-size:13px; font-weight:600; cursor:pointer; transition:opacity .15s; }
.drv-btn-warn:hover { opacity:.85; }

@media (max-width:640px) {
    .drv-uploads { grid-template-columns:1fr; }
    .drv-form-grid--2, .drv-form-grid--3 { grid-template-columns:1fr; }
}
</style>
@endsection

@section('content')
<div class="drv-page">

    @if($errors->any())
        <div class="drv-alert drv-alert--danger">
            <strong>Veuillez corriger les erreurs ci-dessous.</strong>
        </div>
    @endif

    {{-- ── Provisioning express ── --}}
    <div class="drv-card">
        <div class="drv-card__header drv-card__header--yellow">
            <i class="fas fa-bolt" style="color:#92400e;"></i>
            <h2 class="drv-card__title" style="color:#92400e;">Provisioning express</h2>
        </div>
        <form method="post" action="{{ route('driver.store') }}">
            @csrf
            <input type="hidden" name="provision_batch" value="1">
            <div class="drv-card__body">
                <p style="font-size:13px;color:#6b7280;margin:0 0 16px;">
                    Crée rapidement un lot de comptes livreur opérationnels avec identifiants, CNIC, email et téléphone générés automatiquement.
                </p>
                <div class="drv-form-grid">
                    <div class="drv-field">
                        <label class="drv-label" for="provision_restaurant_id">Restaurant ciblé</label>
                        <select name="restaurant_id" id="provision_restaurant_id" class="drv-input drv-select {{ $errors->has('restaurant_id') ? 'drv-input--error' : '' }}">
                            <option value="">Pool mutualisé</option>
                            @foreach($restaurants as $restaurant)
                                <option value="{{ $restaurant->id }}" {{ old('restaurant_id') == $restaurant->id ? 'selected' : '' }}>{{ $restaurant->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="drv-field">
                        <label class="drv-label" for="provision_quantity">Quantité</label>
                        <input type="number" min="1" max="20" name="quantity" value="{{ old('quantity', 1) }}" class="drv-input {{ $errors->has('quantity') ? 'drv-input--error' : '' }}" id="provision_quantity">
                    </div>
                    <div class="drv-field">
                        <label class="drv-label" for="provision_status">Statut initial</label>
                        <select name="status" id="provision_status" class="drv-input drv-select">
                            <option value="online" {{ old('status', 'online') === 'online' ? 'selected' : '' }}>Online</option>
                            <option value="offline" {{ old('status') === 'offline' ? 'selected' : '' }}>Offline</option>
                        </select>
                    </div>
                    <div class="drv-field">
                        <label class="drv-label" for="name_prefix">Préfixe nom</label>
                        <input type="text" name="name_prefix" value="{{ old('name_prefix') }}" class="drv-input" id="name_prefix" placeholder="Ex : Livreur Ops Nganda">
                    </div>
                    <div class="drv-field" style="grid-column:span 2;">
                        <label class="drv-label" for="provision_address">Adresse de base</label>
                        <input type="text" name="address" value="{{ old('address') }}" class="drv-input" id="provision_address" placeholder="Ex : Brazzaville">
                    </div>
                </div>
                <div class="drv-check-row">
                    <input type="checkbox" id="provision_approved" name="approved" value="1" {{ old('approved', 1) ? 'checked' : '' }}>
                    <label for="provision_approved">Compte approuvé immédiatement</label>
                </div>
            </div>
            <div class="drv-card__footer">
                <button type="submit" class="drv-btn-warn">
                    <i class="fas fa-cubes"></i> Provisionner le lot
                </button>
            </div>
        </form>
    </div>

    {{-- ── Formulaire individuel ── --}}
    <div class="drv-card">
        <div class="drv-card__header drv-card__header--blue">
            <i class="fas fa-plus-circle" style="color:#1e40af;"></i>
            <h2 class="drv-card__title" style="color:#1e40af;">Ajouter un livreur</h2>
        </div>
        <form role="form" method="post" action="{{ route('driver.store') }}" enctype="multipart/form-data">
            @csrf
            <div class="drv-card__body">

                {{-- Photos --}}
                <div class="drv-uploads">
                    <div>
                        <label class="drv-upload-zone" for="file-input" onclick="document.getElementById('file-input').click()">
                            <img src="" id="logo" alt="permis" style="display:none;">
                            <div>
                                <span><i class="fas fa-upload"></i> Télécharger le permis</span>
                                <p>Taille recommandée 90×90</p>
                            </div>
                        </label>
                        <input type="file" id="file-input" name="licence_image" style="display:none;" onchange="logo1(this);">
                        @if($errors->has('licence_image'))
                            <span class="drv-field-error" role="alert">{{ $errors->first('licence_image') }}</span>
                        @endif
                    </div>
                    <div>
                        <label class="drv-upload-zone" for="upload_file" onclick="document.getElementById('upload_file').click()">
                            <img src="" id="cover" alt="photo" style="display:none;">
                            <div>
                                <span><i class="fas fa-upload"></i> Photo de profil</span>
                                <p>Taille recommandée 320×220</p>
                            </div>
                        </label>
                        <input type="file" id="upload_file" name="image" style="display:none;" onchange="cover1(this);">
                        @if($errors->has('image'))
                            <span class="drv-field-error" role="alert">{{ $errors->first('image') }}</span>
                        @endif
                    </div>
                </div>

                {{-- Informations --}}
                <div class="drv-section-title">
                    <i class="fas fa-user"></i> Informations du livreur
                </div>
                <div class="drv-form-grid">
                    <div class="drv-field">
                        <label class="drv-label" for="restaurant_id">Restaurant rattaché</label>
                        <select name="restaurant_id" id="restaurant_id" class="drv-input drv-select {{ $errors->has('restaurant_id') ? 'drv-input--error' : '' }}">
                            <option value="">Pool mutualisé</option>
                            @foreach($restaurants as $restaurant)
                                <option value="{{ $restaurant->id }}" {{ old('restaurant_id') == $restaurant->id ? 'selected' : '' }}>{{ $restaurant->name }}</option>
                            @endforeach
                        </select>
                        @if($errors->has('restaurant_id'))
                            <span class="drv-field-error">{{ $errors->first('restaurant_id') }}</span>
                        @endif
                    </div>
                    <div class="drv-field">
                        <label class="drv-label" for="name">Nom <sup>*</sup></label>
                        <input type="text" name="name" value="{{ old('name') }}" class="drv-input {{ $errors->has('name') ? 'drv-input--error' : '' }}" id="name" placeholder="Ex : Jean Dupont" required>
                        @if($errors->has('name'))
                            <span class="drv-field-error">{{ $errors->first('name') }}</span>
                        @endif
                    </div>
                    <div class="drv-field">
                        <label class="drv-label" for="email">Email <sup>*</sup></label>
                        <input type="email" name="email" value="{{ old('email') }}" class="drv-input {{ $errors->has('email') ? 'drv-input--error' : '' }}" id="email" placeholder="jean.dupont@example.com" required>
                        @if($errors->has('email'))
                            <span class="drv-field-error">{{ $errors->first('email') }}</span>
                        @endif
                    </div>
                    <div class="drv-field">
                        <label class="drv-label" for="pass">Mot de passe <sup>*</sup></label>
                        <input type="password" name="password" class="drv-input {{ $errors->has('password') ? 'drv-input--error' : '' }}" id="pass" placeholder="Minimum 6 caractères" required>
                        @if($errors->has('password'))
                            <span class="drv-field-error">{{ $errors->first('password') }}</span>
                        @endif
                    </div>
                    <div class="drv-field">
                        <label class="drv-label" for="phone">Téléphone</label>
                        <input type="text" name="phone" value="{{ old('phone') }}" class="drv-input {{ $errors->has('phone') ? 'drv-input--error' : '' }}" id="phone">
                        @if($errors->has('phone'))
                            <span class="drv-field-error">{{ $errors->first('phone') }}</span>
                        @endif
                    </div>
                    <div class="drv-field">
                        <label class="drv-label" for="address">Adresse</label>
                        <input type="text" name="address" value="{{ old('address') }}" class="drv-input {{ $errors->has('address') ? 'drv-input--error' : '' }}" id="address">
                        @if($errors->has('address'))
                            <span class="drv-field-error">{{ $errors->first('address') }}</span>
                        @endif
                    </div>
                    <div class="drv-field">
                        <label class="drv-label" for="cnic">CNIC / Référence <sup>*</sup></label>
                        <input type="text" name="cnic" value="{{ old('cnic') }}" class="drv-input {{ $errors->has('cnic') ? 'drv-input--error' : '' }}" id="cnic" placeholder="Ex : CNIC-DRV-001" required>
                        @if($errors->has('cnic'))
                            <span class="drv-field-error">{{ $errors->first('cnic') }}</span>
                        @endif
                    </div>
                    <div class="drv-field">
                        <label class="drv-label" for="status">Statut initial</label>
                        <select name="status" id="status" class="drv-input drv-select">
                            <option value="offline" {{ old('status', 'offline') === 'offline' ? 'selected' : '' }}>Offline</option>
                            <option value="online" {{ old('status') === 'online' ? 'selected' : '' }}>Online</option>
                        </select>
                    </div>
                </div>
                <div class="drv-check-row">
                    <input type="checkbox" id="approved" name="approved" value="1" {{ old('approved', 1) ? 'checked' : '' }}>
                    <label for="approved">Compte approuvé</label>
                </div>

                {{-- Détails bancaires --}}
                <div class="drv-section-title" style="margin-top:28px;">
                    <i class="fas fa-university"></i> Détails bancaires
                </div>
                <div class="drv-form-grid">
                    <div class="drv-field">
                        <label class="drv-label" for="account_name">Nom du titulaire</label>
                        <input type="text" value="{{ old('account_name') }}" class="drv-input {{ $errors->has('account_name') ? 'drv-input--error' : '' }}" id="account_name" name="account_name" placeholder="Ex : Jean Dupont">
                        @if($errors->has('account_name'))
                            <span class="drv-field-error">{{ $errors->first('account_name') }}</span>
                        @endif
                    </div>
                    <div class="drv-field">
                        <label class="drv-label" for="account_number">Numéro de compte</label>
                        <input type="text" class="drv-input {{ $errors->has('account_number') ? 'drv-input--error' : '' }}" id="account_number" name="account_number" placeholder="Ex : 1234567890">
                        @if($errors->has('account_number'))
                            <span class="drv-field-error">{{ $errors->first('account_number') }}</span>
                        @endif
                    </div>
                    <div class="drv-field">
                        <label class="drv-label" for="bank_name">Nom de la banque</label>
                        <input type="text" value="{{ old('bank_name') }}" class="drv-input {{ $errors->has('bank_name') ? 'drv-input--error' : '' }}" id="bank_name" name="bank_name" placeholder="Ex : BGFI Bank">
                        @if($errors->has('bank_name'))
                            <span class="drv-field-error">{{ $errors->first('bank_name') }}</span>
                        @endif
                    </div>
                    <div class="drv-field">
                        <label class="drv-label" for="branch_name">Numéro d'agence</label>
                        <input type="text" class="drv-input {{ $errors->has('branch_name') ? 'drv-input--error' : '' }}" id="branch_name" name="branch_name" placeholder="Ex : 001">
                        @if($errors->has('branch_name'))
                            <span class="drv-field-error">{{ $errors->first('branch_name') }}</span>
                        @endif
                    </div>
                    <div class="drv-field">
                        <label class="drv-label" for="branch_address">Adresse de l'agence</label>
                        <input type="text" value="{{ old('branch_address') }}" class="drv-input {{ $errors->has('branch_address') ? 'drv-input--error' : '' }}" id="branch_address" name="branch_address" placeholder="Ex : Centre-Ville, Brazzaville">
                        @if($errors->has('branch_address'))
                            <span class="drv-field-error">{{ $errors->first('branch_address') }}</span>
                        @endif
                    </div>
                </div>

            </div>
            <div class="drv-card__footer">
                <a href="{{ route('driver.index') }}" class="drv-btn-cancel">
                    <i class="fas fa-times"></i> Annuler
                </a>
                <button type="submit" class="drv-btn-primary">
                    <i class="fas fa-save"></i> Ajouter
                </button>
            </div>
        </form>
    </div>

</div>
@endsection

@section('script')
<script>
function logo1(input) {
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function (e) {
            var img = document.getElementById('logo');
            img.src = e.target.result;
            img.style.display = 'block';
        };
        reader.readAsDataURL(input.files[0]);
    }
}
function cover1(input) {
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function (e) {
            var img = document.getElementById('cover');
            img.src = e.target.result;
            img.style.display = 'block';
        };
        reader.readAsDataURL(input.files[0]);
    }
}
</script>
@endsection
