@extends('layouts.admin-modern')
@section('title', 'Modifier le livreur | BantuDelice')
@section('page_title', 'Modifier livreur')
@section('nav_active', 'drivers')

@section('style')
<style>
/* ── drv-edit ───────────────────────────────────────────────── */
.drv-page { padding: 24px; }

.drv-edit-wrap {
    max-width: 840px;
    margin: 0 auto;
}

/* Section titles */
.drv-section-title {
    display: flex;
    align-items: center;
    gap: 8px;
    background: var(--adm-accent, #1e3a5f);
    color: #fff;
    padding: 10px 16px;
    border-radius: 6px;
    font-size: 14px;
    font-weight: 600;
    margin: 0 0 18px 0;
}

/* Form card */
.drv-edit-card {
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: 10px;
    overflow: hidden;
}

.drv-edit-card__header {
    background: var(--adm-accent, #1e3a5f);
    color: #fff;
    padding: 16px 20px;
    font-size: 15px;
    font-weight: 600;
    text-align: center;
}

.drv-edit-card__body { padding: 24px 24px 8px; }

/* Photos section — 2 upload zones */
.drv-photos-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    margin-bottom: 32px;
}
@media (max-width: 560px) {
    .drv-photos-grid { grid-template-columns: 1fr; }
}

.drv-upload-zone {
    border: 2px dashed #d1d5db;
    border-radius: 8px;
    padding: 20px 16px;
    text-align: center;
    cursor: pointer;
    transition: border-color .15s, background .15s;
    position: relative;
}
.drv-upload-zone:hover { border-color: var(--adm-accent, #1e3a5f); background: #f0f4fa; }

.drv-upload-zone input[type="file"] {
    position: absolute;
    inset: 0;
    opacity: 0;
    cursor: pointer;
    width: 100%;
    height: 100%;
}

.drv-upload-zone img {
    display: block;
    margin: 0 auto 10px;
    border-radius: 4px;
    object-fit: cover;
}

.drv-upload-zone__label {
    display: block;
    font-size: 12px;
    color: var(--adm-text-muted, #6b7280);
    line-height: 1.4;
}
.drv-upload-zone__label span {
    display: block;
    color: #ff5a1f;
    font-weight: 600;
    margin-bottom: 2px;
}

/* Fields */
.drv-field { margin-bottom: 16px; }

.drv-label {
    display: block;
    font-size: 13px;
    font-weight: 600;
    color: #374151;
    margin-bottom: 5px;
}

.drv-input {
    width: 100%;
    padding: 9px 12px;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    font-size: 13px;
    color: #111827;
    background: #fff;
    transition: border-color .15s, box-shadow .15s;
    box-sizing: border-box;
}
.drv-input:focus {
    outline: none;
    border-color: var(--adm-accent, #1e3a5f);
    box-shadow: 0 0 0 3px rgba(30,58,95,.1);
}
.drv-input--error { border-color: #ef4444; }

.drv-field-error {
    display: block;
    margin-top: 3px;
    font-size: 11px;
    color: #dc2626;
    font-weight: 500;
}

/* Grid layouts */
.drv-grid-3 {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 16px;
}
@media (max-width: 640px) {
    .drv-grid-3 { grid-template-columns: 1fr; }
}
@media (min-width: 641px) and (max-width: 800px) {
    .drv-grid-3 { grid-template-columns: repeat(2, 1fr); }
}

.drv-grid-2 {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 16px;
}
@media (max-width: 560px) {
    .drv-grid-2 { grid-template-columns: 1fr; }
}

/* Footer */
.drv-edit-card__footer {
    padding: 16px 24px;
    border-top: 1px solid #f3f4f6;
    display: flex;
    justify-content: flex-end;
    align-items: center;
    gap: 10px;
}

.drv-btn-cancel {
    display: inline-flex;
    align-items: center;
    padding: 9px 18px;
    border-radius: 6px;
    font-size: 13px;
    font-weight: 600;
    text-decoration: none;
    border: 1px solid #d1d5db;
    color: #374151;
    background: #fff;
    transition: background .15s;
}
.drv-btn-cancel:hover { background: #f9fafb; color: #111827; text-decoration: none; }

.drv-btn-save {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 9px 20px;
    border-radius: 6px;
    font-size: 13px;
    font-weight: 600;
    background: var(--adm-accent, #1e3a5f);
    color: #fff;
    border: none;
    cursor: pointer;
    transition: opacity .15s;
}
.drv-btn-save:hover { opacity: .85; }

/* Upload error (not invalid-feedback) */
.drv-upload-error {
    display: block;
    margin-top: 6px;
    font-size: 11px;
    color: #dc2626;
    font-weight: 500;
    text-align: left;
}
</style>
@endsection

@section('content')
<div class="drv-page">
    <div class="drv-edit-wrap">
        <div class="drv-edit-card">

            <div class="drv-edit-card__header">
                <i class="fas fa-user-edit" style="margin-right:8px;"></i>
                Mise à jour des informations du livreur
            </div>

            <form role="form" method="post" action="{{ route('driver.update', $driver->id) }}" enctype="multipart/form-data">
                @csrf
                @method('put')

                <div class="drv-edit-card__body">

                    {{-- Section 1 : Photos --}}
                    <h4 class="drv-section-title">
                        <i class="fas fa-camera"></i> Photos
                    </h4>

                    <div class="drv-photos-grid">

                        {{-- Photo profil --}}
                        <div>
                            <label class="drv-upload-zone" for="upload_file">
                                <img
                                    src="{{ !empty($driver->image) ? asset('images/driver_images/'.$driver->image) : asset('images/placeholder.png') }}"
                                    id="cover"
                                    style="width:120px; height:90px;"
                                    onerror="this.src='{{ asset('images/placeholder.png') }}'"
                                    alt="Photo de profil">
                                <span class="drv-upload-zone__label">
                                    <span><i class="fas fa-upload" style="margin-right:4px;"></i>Photo de profil</span>
                                    Taille recommandée 320×220
                                </span>
                                <input type="file" id="upload_file" name="image" onchange="cover1(this);">
                            </label>
                            @if($errors->has('image'))
                                <span class="drv-upload-error" role="alert">{{ $errors->first('image') }}</span>
                            @endif
                        </div>

                        {{-- Permis --}}
                        <div>
                            <label class="drv-upload-zone" for="file-input">
                                <img
                                    src="{{ !empty($driver->licence_image) ? asset('images/driver_images/'.$driver->licence_image) : asset('images/placeholder.png') }}"
                                    id="logo"
                                    style="width:90px; height:70px;"
                                    onerror="this.src='{{ asset('images/placeholder.png') }}'"
                                    alt="Permis">
                                <span class="drv-upload-zone__label">
                                    <span><i class="fas fa-id-card" style="margin-right:4px;"></i>Télécharger le permis</span>
                                    Taille recommandée 90×90
                                </span>
                                <input type="file" id="file-input" name="licence_image" onchange="logo1(this);">
                            </label>
                            @if($errors->has('licence_image'))
                                <span class="drv-upload-error" role="alert">{{ $errors->first('licence_image') }}</span>
                            @endif
                        </div>

                    </div>

                    {{-- Section 2 : Informations --}}
                    <h4 class="drv-section-title">
                        <i class="fas fa-user"></i> Informations du livreur
                    </h4>

                    <div class="drv-grid-3" style="margin-bottom:16px;">
                        <div class="drv-field">
                            <label for="name" class="drv-label">Nom</label>
                            <input type="text" id="name" name="name" value="{{ $driver->name }}"
                                class="drv-input {{ $errors->has('name') ? 'drv-input--error' : '' }}" placeholder="">
                            @if($errors->has('name'))
                                <span class="drv-field-error" role="alert">{{ $errors->first('name') }}</span>
                            @endif
                        </div>
                        <div class="drv-field">
                            <label for="email" class="drv-label">Email</label>
                            <input type="text" id="email" name="email" value="{{ $driver->email }}"
                                class="drv-input {{ $errors->has('email') ? 'drv-input--error' : '' }}" placeholder="">
                            @if($errors->has('email'))
                                <span class="drv-field-error" role="alert">{{ $errors->first('email') }}</span>
                            @endif
                        </div>
                        <div class="drv-field">
                            <label for="pass" class="drv-label">Mot de passe</label>
                            <input type="password" id="pass" name="password" value=""
                                class="drv-input {{ $errors->has('password') ? 'drv-input--error' : '' }}" placeholder="">
                            @if($errors->has('password'))
                                <span class="drv-field-error" role="alert">{{ $errors->first('password') }}</span>
                            @endif
                        </div>
                        <div class="drv-field">
                            <label for="phone" class="drv-label">Téléphone</label>
                            <input type="text" id="phone" name="phone" value="{{ $driver->phone }}"
                                class="drv-input {{ $errors->has('phone') ? 'drv-input--error' : '' }}">
                            @if($errors->has('phone'))
                                <span class="drv-field-error" role="alert">{{ $errors->first('phone') }}</span>
                            @endif
                        </div>
                        <div class="drv-field" style="grid-column: span 2;">
                            <label for="address" class="drv-label">Adresse</label>
                            <input type="text" id="address" name="address" value="{{ $driver->address }}"
                                class="drv-input {{ $errors->has('address') ? 'drv-input--error' : '' }}" placeholder="">
                            @if($errors->has('address'))
                                <span class="drv-field-error" role="alert">{{ $errors->first('address') }}</span>
                            @endif
                        </div>
                    </div>

                    {{-- Section 3 : Coordonnées bancaires --}}
                    <h4 class="drv-section-title" style="margin-top:24px;">
                        <i class="fas fa-university"></i> Coordonnées bancaires
                    </h4>

                    <div class="drv-grid-2" style="margin-bottom:8px;">
                        <div class="drv-field">
                            <label for="account_name" class="drv-label">Titulaire du compte</label>
                            <input type="text" id="account_name" name="account_name" value="{{ $driver->account_name }}"
                                class="drv-input {{ $errors->has('account_name') ? 'drv-input--error' : '' }}" placeholder="">
                            @if($errors->has('account_name'))
                                <span class="drv-field-error" role="alert">{{ $errors->first('account_name') }}</span>
                            @endif
                        </div>
                        <div class="drv-field">
                            <label for="account_number" class="drv-label">Numéro de compte</label>
                            <input type="text" id="account_number" name="account_number" value="{{ $driver->account_number }}"
                                class="drv-input {{ $errors->has('account_number') ? 'drv-input--error' : '' }}" placeholder="">
                            @if($errors->has('account_number'))
                                <span class="drv-field-error" role="alert">{{ $errors->first('account_number') }}</span>
                            @endif
                        </div>
                        <div class="drv-field">
                            <label for="bank_name" class="drv-label">Banque</label>
                            <input type="text" id="bank_name" name="bank_name" value="{{ $driver->bank_name }}"
                                class="drv-input {{ $errors->has('bank_name') ? 'drv-input--error' : '' }}" placeholder="">
                            @if($errors->has('bank_name'))
                                <span class="drv-field-error" role="alert">{{ $errors->first('bank_name') }}</span>
                            @endif
                        </div>
                        <div class="drv-field">
                            <label for="branch_name" class="drv-label">Agence</label>
                            <input type="text" id="branch_name" name="branch_name" value="{{ $driver->branch_name }}"
                                class="drv-input {{ $errors->has('branch_name') ? 'drv-input--error' : '' }}" placeholder="">
                            @if($errors->has('branch_name'))
                                <span class="drv-field-error" role="alert">{{ $errors->first('branch_name') }}</span>
                            @endif
                        </div>
                        <div class="drv-field" style="grid-column: span 2;">
                            <label for="branch_address" class="drv-label">Adresse de l'agence</label>
                            <input type="text" id="branch_address" name="branch_address" value="{{ $driver->branch_address }}"
                                class="drv-input {{ $errors->has('branch_address') ? 'drv-input--error' : '' }}" placeholder="">
                            @if($errors->has('branch_address'))
                                <span class="drv-field-error" role="alert">{{ $errors->first('branch_address') }}</span>
                            @endif
                        </div>
                    </div>

                </div>

                {{-- Footer --}}
                <div class="drv-edit-card__footer">
                    <a href="{{ route('driver.index') }}" class="drv-btn-cancel">Annuler</a>
                    <button type="submit" class="drv-btn-save">
                        <i class="fas fa-save"></i> Mettre à jour le livreur
                    </button>
                </div>

            </form>
        </div>
    </div>
</div>
@endsection

@section('script')
<script>
function cover1(input) {
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function (e) {
            document.getElementById('cover').src = e.target.result;
        };
        reader.readAsDataURL(input.files[0]);
    }
}

function logo1(input) {
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function (e) {
            document.getElementById('logo').src = e.target.result;
        };
        reader.readAsDataURL(input.files[0]);
    }
}
</script>
@endsection
