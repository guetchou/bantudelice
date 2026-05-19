@extends('layouts.restaurant_app')
@section('working_hour_nav', 'active')
@section('title', 'Ajouter un horaire')
@section('topbar_title', 'Ajouter un horaire')

@section('content')
<div style="max-width:640px;">
    @if(session('alert'))
        <div class="alert alert-{{ session('alert.type', 'success') }} alert-dismissible fade show" role="alert">
            {{ session('alert.message', session('alert')) }}
            <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
    @endif

    {{-- ── Formulaire ─────────────────────────────────── --}}
    <form method="post" action="{{ route('working_hour.store') }}" id="hourForm">
        @csrf
        <div class="card">
            <div class="card-header">
                <div class="card-title">Horaire d'ouverture hebdomadaire</div>
                <div style="font-size:12px;color:#9ca3af;margin-top:2px;">
                    Définissez les heures pour un jour de la semaine
                </div>
            </div>
            <div class="card-body">

                <div class="form-group">
                    <label>Jour de la semaine</label>
                    <select name="day" id="day-select" class="form-control" required>
                        <option value="">Choisir un jour…</option>
                        <option value="monday"    {{ old('day') === 'monday'    ? 'selected' : '' }}>Lundi</option>
                        <option value="tuesday"   {{ old('day') === 'tuesday'   ? 'selected' : '' }}>Mardi</option>
                        <option value="wednesday" {{ old('day') === 'wednesday' ? 'selected' : '' }}>Mercredi</option>
                        <option value="thursday"  {{ old('day') === 'thursday'  ? 'selected' : '' }}>Jeudi</option>
                        <option value="friday"    {{ old('day') === 'friday'    ? 'selected' : '' }}>Vendredi</option>
                        <option value="saturday"  {{ old('day') === 'saturday'  ? 'selected' : '' }}>Samedi</option>
                        <option value="sunday"    {{ old('day') === 'sunday'    ? 'selected' : '' }}>Dimanche</option>
                    </select>
                    @error('day')<div style="color:#dc2626;font-size:12px;margin-top:4px;">{{ $message }}</div>@enderror
                </div>

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                    <div class="form-group">
                        <label>Heure d'ouverture</label>
                        <select name="opening_time" id="opening-select" class="form-control" required>
                            <option value="">Choisir…</option>
                            @for($h = 0; $h < 24; $h++)
                                @php $val = str_pad($h,2,'0',STR_PAD_LEFT).':00:00'; @endphp
                                <option value="{{ $val }}" {{ old('opening_time') === $val ? 'selected' : '' }}>
                                    {{ str_pad($h,2,'0',STR_PAD_LEFT) }}:00
                                </option>
                                @php $val30 = str_pad($h,2,'0',STR_PAD_LEFT).':30:00'; @endphp
                                <option value="{{ $val30 }}" {{ old('opening_time') === $val30 ? 'selected' : '' }}>
                                    {{ str_pad($h,2,'0',STR_PAD_LEFT) }}:30
                                </option>
                            @endfor
                        </select>
                        @error('opening_time')<div style="color:#dc2626;font-size:12px;margin-top:4px;">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-group">
                        <label>Heure de fermeture</label>
                        <select name="closing_time" id="closing-select" class="form-control" required>
                            <option value="">Choisir…</option>
                            @for($h = 0; $h < 24; $h++)
                                @php $val = str_pad($h,2,'0',STR_PAD_LEFT).':00:00'; @endphp
                                <option value="{{ $val }}" {{ old('closing_time') === $val ? 'selected' : '' }}>
                                    {{ str_pad($h,2,'0',STR_PAD_LEFT) }}:00
                                </option>
                                @php $val30 = str_pad($h,2,'0',STR_PAD_LEFT).':30:00'; @endphp
                                <option value="{{ $val30 }}" {{ old('closing_time') === $val30 ? 'selected' : '' }}>
                                    {{ str_pad($h,2,'0',STR_PAD_LEFT) }}:30
                                </option>
                            @endfor
                        </select>
                        @error('closing_time')<div style="color:#dc2626;font-size:12px;margin-top:4px;">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:6px;padding:12px;font-size:12px;color:#007836;margin-top:4px;">
                    <i class="fas fa-info-circle mr-1"></i>
                    Pour ajouter une tranche horaire (ex: midi + soir), ajoutez deux entrées pour le même jour.
                </div>
            </div>
            <div class="card-footer" style="display:flex;justify-content:flex-end;gap:8px;">
                <a href="{{ route('working_hour.index') }}" class="btn btn-outline-info btn-sm">Annuler</a>
                <button type="submit" class="btn btn-primary btn-sm">
                    <i class="fas fa-save mr-1"></i> Enregistrer l'horaire
                </button>
            </div>
        </div>

        {{-- Mode fermeture spéciale --}}
        <div class="card" id="panel-special" style="{{ !$isSpecial ? 'display:none;' : '' }}">
            <div class="card-header">
                <div class="card-title">Fermeture ou information spéciale</div>
                <div style="font-size:12px;color:#9ca3af;margin-top:2px;">
                    Planifiez une fermeture exceptionnelle visible par vos clients
                </div>
            </div>
            <div class="card-body">

                <div class="form-group">
                    <label>Type de fermeture</label>
                <div style="background:#f8fafc;border:1px solid #E5E7EB;border-radius:6px;padding:12px;font-size:12px;color:#475569;margin-top:4px;">
                    <i class="fas fa-info-circle mr-1"></i>
                    Les fermetures spéciales sont désormais gérées dans un écran dédié, avec de vraies dates de début et de fin.
                </div>
            </div>
            <div class="card-footer" style="display:flex;justify-content:flex-end;gap:8px;">
                <a href="{{ route('working_hour.index') }}" class="btn btn-outline-info btn-sm">Annuler</a>
                <a href="{{ route('restaurant.special_closures.create') }}" class="btn btn-outline-warning btn-sm">
                    <i class="fas fa-calendar-xmark mr-1"></i> Gérer les fermetures spéciales
                </a>
            </div>
        </div>
    </form>
</div>

@endsection
