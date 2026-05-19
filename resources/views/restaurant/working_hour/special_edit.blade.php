@extends('layouts.restaurant_app')
@section('working_hour_nav', 'active')
@section('title', 'Modifier une fermeture spéciale')
@section('topbar_title', 'Modifier une fermeture spéciale')

@section('content')
<div style="max-width:680px;">
    <div class="card">
        <div class="card-header">
            <div class="card-title">Modifier la fermeture spéciale</div>
            <div style="font-size:12px;color:#9ca3af;margin-top:2px;">
                Ajustez la période d’indisponibilité enregistrée pour votre restaurant.
            </div>
        </div>
        <form method="post" action="{{ route('restaurant.special_closures.update', $specialClosure->id) }}">
            @csrf
            @method('put')
            <div class="card-body">
                <div class="form-group">
                    <label>Type de fermeture</label>
                    <select name="label" class="form-control" required>
                        <option value="Férié" {{ old('label', $specialClosure->label) === 'Férié' ? 'selected' : '' }}>Jour férié</option>
                        <option value="Congé" {{ old('label', $specialClosure->label) === 'Congé' ? 'selected' : '' }}>Congé / Vacances</option>
                        <option value="Fermeture exceptionnelle" {{ old('label', $specialClosure->label) === 'Fermeture exceptionnelle' ? 'selected' : '' }}>Fermeture exceptionnelle</option>
                        <option value="Travaux" {{ old('label', $specialClosure->label) === 'Travaux' ? 'selected' : '' }}>Travaux / Rénovation</option>
                        <option value="Inventaire" {{ old('label', $specialClosure->label) === 'Inventaire' ? 'selected' : '' }}>Inventaire</option>
                        <option value="Événement privé" {{ old('label', $specialClosure->label) === 'Événement privé' ? 'selected' : '' }}>Événement privé / Réservé</option>
                    </select>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                    <div class="form-group">
                        <label>Date de début</label>
                        <input type="date" name="starts_on" value="{{ old('starts_on', optional($specialClosure->starts_on)->format('Y-m-d')) }}" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Date de fin</label>
                        <input type="date" name="ends_on" value="{{ old('ends_on', optional($specialClosure->ends_on)->format('Y-m-d')) }}" class="form-control" required>
                    </div>
                </div>
                <div class="form-group">
                    <label>Note interne (optionnel)</label>
                    <textarea name="notes" rows="3" class="form-control">{{ old('notes', $specialClosure->notes) }}</textarea>
                </div>
            </div>
            <div class="card-footer" style="display:flex;justify-content:flex-end;gap:8px;">
                <a href="{{ route('working_hour.index') }}" class="btn btn-secondary btn-sm">Annuler</a>
                <button type="submit" class="btn btn-warning btn-sm">Mettre à jour</button>
            </div>
        </form>
    </div>
</div>
@endsection
