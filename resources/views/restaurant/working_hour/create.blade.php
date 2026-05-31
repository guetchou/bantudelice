@extends('layouts.restaurant_app')
@section('title', 'Ajouter un horaire | ' . \App\Services\ConfigService::getCompanyName())
@section('topbar_title', 'Ajouter un horaire')
@section('working_hour_nav', 'active')

@section('content')
<div style="max-width:600px;">

    @if(session()->has('alert'))
        <div class="alert alert-{{ session()->get('alert.type') }} alert-dismissible" role="alert">
            {{ session()->get('alert.message') }}
            <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
        </div>
    @endif

    <div style="background:var(--bd-surface);border:1px solid var(--bd-border);border-radius:var(--bd-radius);overflow:hidden;">
        <div style="padding:16px 20px;border-bottom:1px solid var(--bd-border-2);display:flex;align-items:center;justify-content:space-between;gap:12px;">
            <div>
                <div style="font-size:13px;font-weight:700;color:var(--bd-text);">Ajouter un horaire</div>
                <div style="font-size:11px;color:var(--bd-text-3);margin-top:2px;">Définissez les heures d'ouverture pour un jour de la semaine</div>
            </div>
            <a href="{{ route('working_hour.index') }}"
               style="display:inline-flex;align-items:center;gap:5px;padding:6px 12px;border-radius:var(--bd-radius);border:1px solid var(--bd-border);background:var(--bd-surface);color:var(--bd-text-2);font-size:12px;font-weight:600;text-decoration:none;transition:.12s;"
               onmouseover="this.style.borderColor='var(--bd-green)';this.style.color='var(--bd-green)';"
               onmouseout="this.style.borderColor='var(--bd-border)';this.style.color='var(--bd-text-2)';">
                <i class="fas fa-arrow-left"></i> Retour
            </a>
        </div>

        <form method="post" action="{{ route('working_hour.store') }}" id="hourForm">
            @csrf
            <div style="padding:24px 20px;display:flex;flex-direction:column;gap:18px;">

                <div>
                    <label for="day-select" style="display:block;font-size:12px;font-weight:600;color:var(--bd-text);margin-bottom:6px;">
                        Jour de la semaine <span style="color:#dc2626;">*</span>
                    </label>
                    <select name="day" id="day-select" required
                            style="width:100%;box-sizing:border-box;padding:9px 12px;border:1px solid {{ $errors->has('day') ? '#dc2626' : 'var(--bd-border)' }};border-radius:var(--bd-radius);font-size:13px;font-family:var(--bd-font);background:var(--bd-surface);color:var(--bd-text);outline:none;appearance:auto;">
                        <option value="">Choisir un jour…</option>
                        <option value="monday"    {{ old('day') === 'monday'    ? 'selected' : '' }}>Lundi</option>
                        <option value="tuesday"   {{ old('day') === 'tuesday'   ? 'selected' : '' }}>Mardi</option>
                        <option value="wednesday" {{ old('day') === 'wednesday' ? 'selected' : '' }}>Mercredi</option>
                        <option value="thursday"  {{ old('day') === 'thursday'  ? 'selected' : '' }}>Jeudi</option>
                        <option value="friday"    {{ old('day') === 'friday'    ? 'selected' : '' }}>Vendredi</option>
                        <option value="saturday"  {{ old('day') === 'saturday'  ? 'selected' : '' }}>Samedi</option>
                        <option value="sunday"    {{ old('day') === 'sunday'    ? 'selected' : '' }}>Dimanche</option>
                    </select>
                    @error('day')
                        <div style="font-size:11px;color:#dc2626;margin-top:4px;">{{ $message }}</div>
                    @enderror
                </div>

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
                    <div>
                        <label for="opening-select" style="display:block;font-size:12px;font-weight:600;color:var(--bd-text);margin-bottom:6px;">
                            Heure d'ouverture <span style="color:#dc2626;">*</span>
                        </label>
                        <select name="opening_time" id="opening-select" required
                                style="width:100%;box-sizing:border-box;padding:9px 12px;border:1px solid {{ $errors->has('opening_time') ? '#dc2626' : 'var(--bd-border)' }};border-radius:var(--bd-radius);font-size:13px;font-family:var(--bd-font);background:var(--bd-surface);color:var(--bd-text);outline:none;appearance:auto;">
                            <option value="">Choisir…</option>
                            @for($h = 0; $h < 24; $h++)
                                @php $val = str_pad($h,2,'0',STR_PAD_LEFT).':00:00'; @endphp
                                <option value="{{ $val }}" {{ old('opening_time') === $val ? 'selected' : '' }}>{{ str_pad($h,2,'0',STR_PAD_LEFT) }}:00</option>
                                @php $val30 = str_pad($h,2,'0',STR_PAD_LEFT).':30:00'; @endphp
                                <option value="{{ $val30 }}" {{ old('opening_time') === $val30 ? 'selected' : '' }}>{{ str_pad($h,2,'0',STR_PAD_LEFT) }}:30</option>
                            @endfor
                        </select>
                        @error('opening_time')
                            <div style="font-size:11px;color:#dc2626;margin-top:4px;">{{ $message }}</div>
                        @enderror
                    </div>
                    <div>
                        <label for="closing-select" style="display:block;font-size:12px;font-weight:600;color:var(--bd-text);margin-bottom:6px;">
                            Heure de fermeture <span style="color:#dc2626;">*</span>
                        </label>
                        <select name="closing_time" id="closing-select" required
                                style="width:100%;box-sizing:border-box;padding:9px 12px;border:1px solid {{ $errors->has('closing_time') ? '#dc2626' : 'var(--bd-border)' }};border-radius:var(--bd-radius);font-size:13px;font-family:var(--bd-font);background:var(--bd-surface);color:var(--bd-text);outline:none;appearance:auto;">
                            <option value="">Choisir…</option>
                            @for($h = 0; $h < 24; $h++)
                                @php $val = str_pad($h,2,'0',STR_PAD_LEFT).':00:00'; @endphp
                                <option value="{{ $val }}" {{ old('closing_time') === $val ? 'selected' : '' }}>{{ str_pad($h,2,'0',STR_PAD_LEFT) }}:00</option>
                                @php $val30 = str_pad($h,2,'0',STR_PAD_LEFT).':30:00'; @endphp
                                <option value="{{ $val30 }}" {{ old('closing_time') === $val30 ? 'selected' : '' }}>{{ str_pad($h,2,'0',STR_PAD_LEFT) }}:30</option>
                            @endfor
                        </select>
                        @error('closing_time')
                            <div style="font-size:11px;color:#dc2626;margin-top:4px;">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div style="background:rgba(0,149,67,.06);border:1px solid rgba(0,149,67,.2);border-radius:var(--bd-radius);padding:12px 14px;font-size:12px;color:var(--bd-green);">
                    <i class="fas fa-info-circle" style="margin-right:6px;"></i>
                    Pour ajouter une tranche horaire (ex : midi + soir), ajoutez deux entrées pour le même jour.
                </div>

            </div>

            <div style="padding:14px 20px;border-top:1px solid var(--bd-border-2);background:var(--bd-surface-2);display:flex;align-items:center;justify-content:flex-end;gap:8px;">
                <a href="{{ route('working_hour.index') }}"
                   style="display:inline-flex;align-items:center;gap:5px;padding:8px 16px;border-radius:var(--bd-radius);border:1px solid var(--bd-border);background:var(--bd-surface);color:var(--bd-text-2);font-size:12px;font-weight:600;text-decoration:none;transition:.12s;"
                   onmouseover="this.style.borderColor='var(--bd-green)';this.style.color='var(--bd-green)';"
                   onmouseout="this.style.borderColor='var(--bd-border)';this.style.color='var(--bd-text-2)';">
                    Annuler
                </a>
                <button type="submit"
                        style="display:inline-flex;align-items:center;gap:6px;padding:8px 18px;border-radius:var(--bd-radius);background:var(--bd-green);color:#fff;font-size:12px;font-weight:700;border:none;cursor:pointer;font-family:var(--bd-font);transition:.12s;"
                        onmouseover="this.style.background='var(--bd-green-dark,#007836)';"
                        onmouseout="this.style.background='var(--bd-green)';">
                    <i class="fas fa-save"></i> Enregistrer
                </button>
            </div>
        </form>
    </div>

</div>
@endsection
