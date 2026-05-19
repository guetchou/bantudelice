@extends('layouts.restaurant_app')
@section('working_hour_nav', 'active')
@section('title', 'Ajouter une fermeture spéciale')
@section('topbar_title', 'Ajouter une fermeture spéciale')

@section('content')
<div style="max-width:680px;">
    <div class="card">
        <div class="card-header">
            <div class="card-title">Nouvelle fermeture spéciale</div>
            <div style="font-size:12px;color:#9ca3af;margin-top:2px;">
                Enregistrez une période d’indisponibilité avec de vraies dates de début et de fin.
            </div>
        </div>
        <form method="post" action="{{ route('restaurant.special_closures.store') }}">
            @csrf
            <div class="card-body">
                <div class="form-group">
                    <label>Type de fermeture</label>
                    <select name="label" class="form-control" required>
                        <option value="">Choisir un type…</option>
                        <option value="Férié" {{ old('label') === 'Férié' ? 'selected' : '' }}>Jour férié</option>
                        <option value="Congé" {{ old('label') === 'Congé' ? 'selected' : '' }}>Congé / Vacances</option>
                        <option value="Fermeture exceptionnelle" {{ old('label') === 'Fermeture exceptionnelle' ? 'selected' : '' }}>Fermeture exceptionnelle</option>
                        <option value="Travaux" {{ old('label') === 'Travaux' ? 'selected' : '' }}>Travaux / Rénovation</option>
                        <option value="Inventaire" {{ old('label') === 'Inventaire' ? 'selected' : '' }}>Inventaire</option>
                        <option value="Événement privé" {{ old('label') === 'Événement privé' ? 'selected' : '' }}>Événement privé / Réservé</option>
                    </select>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                    <div class="form-group">
                        <label>Date de début</label>
                        <input type="date" name="starts_on" value="{{ old('starts_on', date('Y-m-d')) }}" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Date de fin</label>
                        <input type="date" name="ends_on" value="{{ old('ends_on', date('Y-m-d')) }}" class="form-control" required>
                    </div>
                </div>
                <div class="form-group">
                    <label>Note interne (optionnel)</label>
                    <textarea name="notes" rows="3" class="form-control" placeholder="Ex: maintenance cuisine, équipe absente, fermeture exceptionnelle.">{{ old('notes') }}</textarea>
                </div>
            </div>
            <div class="card-footer" style="display:flex;justify-content:flex-end;gap:8px;">
                <a href="{{ route('working_hour.index') }}" class="btn btn-secondary btn-sm">Annuler</a>
                <button type="submit" class="btn btn-warning btn-sm">Enregistrer la fermeture</button>
            </div>
        </form>
    </div>
</div>
@endsection
