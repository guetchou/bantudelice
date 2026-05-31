@extends('layouts.admin-modern')
@section('title', 'Nouveau véhicule | Admin')
@section('page_title', 'Nouveau véhicule')
@section('nav_active', 'vehicles')

@section('style')
<style>
.veh-form-wrap { max-width: 720px; margin: 0 auto; display: flex; flex-direction: column; gap: 20px; }

.veh-form-card { background: #fff; border: 1px solid #e5e7eb; border-radius: 12px; overflow: hidden; }
.veh-form-card__head { padding: 16px 20px; border-bottom: 1px solid #f3f4f6; display: flex; align-items: center; gap: 10px; }
.veh-form-card__head h3 { margin: 0; font-size: 14px; font-weight: 700; color: #111827; }
.veh-form-card__head p  { margin: 2px 0 0; font-size: 12px; color: #6b7280; }

.veh-section-label {
    font-size: 11px; font-weight: 700; letter-spacing: .08em; text-transform: uppercase;
    color: #6b7280; padding: 12px 20px 0; border-top: 1px solid #f3f4f6; margin-top: 4px;
}

.veh-form-body { padding: 16px 20px; display: flex; flex-direction: column; gap: 14px; }
.veh-form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 14px; }

.veh-field label { display: block; font-size: 12px; font-weight: 600; color: #374151; margin-bottom: 5px; }
.veh-input {
    width: 100%; padding: 9px 12px; border: 1px solid #d1d5db; border-radius: 7px;
    font-size: 13px; font-family: 'Manrope', sans-serif; color: #111827;
    background: #fff; outline: none; box-sizing: border-box; transition: border-color .12s;
}
.veh-input:focus { border-color: #22c55e; }
.veh-input--error { border-color: #ef4444; }
.veh-field-error { font-size: 11px; color: #ef4444; margin-top: 3px; }

.veh-licence-upload {
    border: 2px dashed #d1d5db; border-radius: 8px; padding: 20px;
    text-align: center; cursor: pointer; transition: border-color .12s;
    background: #fafafa;
}
.veh-licence-upload:hover { border-color: #22c55e; }
.veh-licence-preview {
    width: 90px; height: auto; max-height: 90px; object-fit: contain;
    display: none; margin: 0 auto 8px;
}
.veh-licence-label { font-size: 12px; color: #6b7280; }
.veh-licence-label span { color: #ef4444; font-weight: 600; }

.veh-form-footer { display: flex; justify-content: flex-end; gap: 8px; padding: 14px 20px; border-top: 1px solid #f3f4f6; }
.veh-btn { display: inline-flex; align-items: center; gap: 5px; padding: 8px 18px; border-radius: 7px; font-size: 13px; font-weight: 600; text-decoration: none; transition: .12s; cursor: pointer; }
.veh-btn--outline { background: #fff; color: #374151; border: 1px solid #d1d5db; }
.veh-btn--outline:hover { border-color: #374151; color: #111827; }
.veh-btn--primary { background: #22c55e; color: #fff; border: none; }
.veh-btn--primary:hover { background: #16a34a; }
</style>
@endsection

@section('content')
<div class="veh-form-wrap">

    <div class="veh-form-card">
        <div class="veh-form-card__head">
            <div>
                <h3>Ajouter un véhicule</h3>
                <p>Renseignez les informations du véhicule et du livreur associé.</p>
            </div>
        </div>

        <form method="post" action="{{ route('vehicle.store') }}" enctype="multipart/form-data">
            @csrf
            <div class="veh-form-body">

                <div class="veh-field">
                    <label>Image permis de conduire</label>
                    <div class="veh-licence-upload" onclick="document.getElementById('file_input').click()">
                        <img src="" id="licence" class="veh-licence-preview" alt="">
                        <div class="veh-licence-label">
                            <i class="fas fa-id-card" style="font-size:22px;color:#d1d5db;margin-bottom:6px;display:block;"></i>
                            <span>Cliquez pour sélectionner</span> l'image du permis
                        </div>
                    </div>
                    <input type="file" id="file_input" name="license_image"
                           style="display:none;"
                           onchange="licen(this)"
                           class="{{ $errors->has('license_image') ? 'veh-input--error' : '' }}">
                    @if($errors->has('license_image'))
                        <div class="veh-field-error">{{ $errors->first('license_image') }}</div>
                    @endif
                </div>

                <div class="veh-form-grid">
                    <div class="veh-field">
                        <label>N° permis de conduire</label>
                        <input type="text" name="license_number" id="license_number"
                               value="{{ old('license_number') }}"
                               class="veh-input{{ $errors->has('license_number') ? ' veh-input--error' : '' }}">
                        @if($errors->has('license_number'))
                            <div class="veh-field-error">{{ $errors->first('license_number') }}</div>
                        @endif
                    </div>
                    <div class="veh-field">
                        <label>Modèle</label>
                        <input type="text" name="model" id="model"
                               value="{{ old('model') }}"
                               class="veh-input{{ $errors->has('model') ? ' veh-input--error' : '' }}">
                        @if($errors->has('model'))
                            <div class="veh-field-error">{{ $errors->first('model') }}</div>
                        @endif
                    </div>
                    <div class="veh-field">
                        <label>Immatriculation</label>
                        <input type="text" name="number" id="number"
                               value="{{ old('number') }}"
                               class="veh-input{{ $errors->has('number') ? ' veh-input--error' : '' }}">
                        @if($errors->has('number'))
                            <div class="veh-field-error">{{ $errors->first('number') }}</div>
                        @endif
                    </div>
                    <div class="veh-field">
                        <label>Couleur</label>
                        <input type="text" name="color" id="color"
                               value="{{ old('color') }}"
                               class="veh-input{{ $errors->has('color') ? ' veh-input--error' : '' }}">
                        @if($errors->has('color'))
                            <div class="veh-field-error">{{ $errors->first('color') }}</div>
                        @endif
                    </div>
                    <div class="veh-field">
                        <label>Livreur associé</label>
                        <select name="driver_id" id="driver" class="veh-input">
                            <option value="">Choisir un livreur…</option>
                            @foreach(\App\Driver::all() as $driver)
                                <option value="{{ $driver->id }}" {{ old('driver_id') == $driver->id ? 'selected' : '' }}>
                                    {{ $driver->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

            </div>
            <div class="veh-form-footer">
                <a href="{{ route('vehicle.index') }}" class="veh-btn veh-btn--outline">Annuler</a>
                <button type="submit" class="veh-btn veh-btn--primary">
                    <i class="fas fa-save"></i> Enregistrer
                </button>
            </div>
        </form>
    </div>

</div>
@endsection

@section('script')
<script>
function licen(input) {
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function (e) {
            var img = document.getElementById('licence');
            img.src = e.target.result;
            img.style.display = 'block';
        };
        reader.readAsDataURL(input.files[0]);
    }
}
</script>
@endsection
