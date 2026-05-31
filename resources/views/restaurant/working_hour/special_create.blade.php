@extends('layouts.restaurant_app')
@section('title', 'Ajouter une fermeture spéciale | ' . \App\Services\ConfigService::getCompanyName())
@section('topbar_title', 'Fermeture spéciale')
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
                <div style="font-size:13px;font-weight:700;color:var(--bd-text);">Nouvelle fermeture spéciale</div>
                <div style="font-size:11px;color:var(--bd-text-3);margin-top:2px;">Planifiez une période d'indisponibilité avec dates précises</div>
            </div>
            <a href="{{ route('working_hour.index') }}"
               style="display:inline-flex;align-items:center;gap:5px;padding:6px 12px;border-radius:var(--bd-radius);border:1px solid var(--bd-border);background:var(--bd-surface);color:var(--bd-text-2);font-size:12px;font-weight:600;text-decoration:none;transition:.12s;"
               onmouseover="this.style.borderColor='var(--bd-green)';this.style.color='var(--bd-green)';"
               onmouseout="this.style.borderColor='var(--bd-border)';this.style.color='var(--bd-text-2)';">
                <i class="fas fa-arrow-left"></i> Retour
            </a>
        </div>

        <form method="post" action="{{ route('restaurant.special_closures.store') }}">
            @csrf
            <div style="padding:24px 20px;display:flex;flex-direction:column;gap:18px;">

                <div>
                    <label for="label" style="display:block;font-size:12px;font-weight:600;color:var(--bd-text);margin-bottom:6px;">
                        Type de fermeture <span style="color:#dc2626;">*</span>
                    </label>
                    <select name="label" id="label" required
                            style="width:100%;box-sizing:border-box;padding:9px 12px;border:1px solid {{ $errors->has('label') ? '#dc2626' : 'var(--bd-border)' }};border-radius:var(--bd-radius);font-size:13px;font-family:var(--bd-font);background:var(--bd-surface);color:var(--bd-text);outline:none;appearance:auto;">
                        <option value="">Choisir un type…</option>
                        @foreach(['Férié'=>'Jour férié','Congé'=>'Congé / Vacances','Fermeture exceptionnelle'=>'Fermeture exceptionnelle','Travaux'=>'Travaux / Rénovation','Inventaire'=>'Inventaire','Événement privé'=>'Événement privé / Réservé'] as $val => $label)
                            <option value="{{ $val }}" {{ old('label') === $val ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('label')
                        <div style="font-size:11px;color:#dc2626;margin-top:4px;">{{ $message }}</div>
                    @enderror
                </div>

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
                    <div>
                        <label for="starts_on" style="display:block;font-size:12px;font-weight:600;color:var(--bd-text);margin-bottom:6px;">
                            Date de début <span style="color:#dc2626;">*</span>
                        </label>
                        <input required type="date" name="starts_on" id="starts_on"
                               value="{{ old('starts_on', date('Y-m-d')) }}"
                               style="width:100%;box-sizing:border-box;padding:9px 12px;border:1px solid {{ $errors->has('starts_on') ? '#dc2626' : 'var(--bd-border)' }};border-radius:var(--bd-radius);font-size:13px;font-family:var(--bd-font);background:var(--bd-surface);color:var(--bd-text);outline:none;" />
                        @error('starts_on')
                            <div style="font-size:11px;color:#dc2626;margin-top:4px;">{{ $message }}</div>
                        @enderror
                    </div>
                    <div>
                        <label for="ends_on" style="display:block;font-size:12px;font-weight:600;color:var(--bd-text);margin-bottom:6px;">
                            Date de fin <span style="color:#dc2626;">*</span>
                        </label>
                        <input required type="date" name="ends_on" id="ends_on"
                               value="{{ old('ends_on', date('Y-m-d')) }}"
                               style="width:100%;box-sizing:border-box;padding:9px 12px;border:1px solid {{ $errors->has('ends_on') ? '#dc2626' : 'var(--bd-border)' }};border-radius:var(--bd-radius);font-size:13px;font-family:var(--bd-font);background:var(--bd-surface);color:var(--bd-text);outline:none;" />
                        @error('ends_on')
                            <div style="font-size:11px;color:#dc2626;margin-top:4px;">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div>
                    <label for="notes" style="display:block;font-size:12px;font-weight:600;color:var(--bd-text);margin-bottom:6px;">
                        Note interne <span style="font-weight:400;color:var(--bd-text-3);">(optionnel)</span>
                    </label>
                    <textarea name="notes" id="notes" rows="3"
                              placeholder="Ex : maintenance cuisine, équipe absente…"
                              style="width:100%;box-sizing:border-box;padding:9px 12px;border:1px solid var(--bd-border);border-radius:var(--bd-radius);font-size:13px;font-family:var(--bd-font);background:var(--bd-surface);color:var(--bd-text);outline:none;resize:vertical;transition:border-color .12s;"
                              onfocus="this.style.borderColor='var(--bd-green)';"
                              onblur="this.style.borderColor='var(--bd-border)';">{{ old('notes') }}</textarea>
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
                    <i class="fas fa-calendar-xmark"></i> Enregistrer
                </button>
            </div>
        </form>
    </div>

</div>
@endsection
