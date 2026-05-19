@extends('layouts.admin-modern')

@section('title', 'Surcharge météo | BantuDelice Admin')
@section('page_title', 'Surcharge — Saison des pluies')
@section('nav_active', 'weather-surcharge')

@section('content')
@if(session()->has('alert'))
    <div class="alert alert-{{ session()->get('alert.type') }} mb-4" style="border-radius:10px;">
        {{ session()->get('alert.message') }}
    </div>
@endif
<div class="row justify-content-center">
<div class="col-md-7">

    {{-- Statut actuel --}}
    <div class="card @if($enabled) border-warning @else border-success @endif" style="border-width:2px;">
        <div class="card-header @if($enabled) bg-warning text-dark @else bg-success text-white @endif d-flex justify-content-between align-items-center">
            <span>
                <i class="fas @if($enabled) fa-exclamation-triangle @else fa-check-circle @endif mr-2"></i>
                Statut : <strong>{{ $enabled ? 'Surcharge ACTIVE' : 'Tarifs normaux' }}</strong>
            </span>
            <form method="POST" action="{{ route('admin.weather-surcharge.toggle') }}" style="margin:0;">
                @csrf
                <button type="submit" class="btn btn-sm @if($enabled) btn-light @else btn-warning @endif">
                    {{ $enabled ? 'Désactiver la surcharge' : 'Activer la surcharge' }}
                </button>
            </form>
        </div>
        <div class="card-body">
            @if($enabled)
                <div class="alert alert-warning mb-0">
                    <strong>Surcharge active :</strong> +{{ number_format($percent, 0) }}% sur les frais de livraison.<br>
                    Message affiché au client : <em>{{ $label }}</em>
                </div>
            @else
                <p class="text-muted mb-0">La surcharge n'est pas active. Les frais de livraison s'appliquent normalement.</p>
            @endif
        </div>
    </div>

    {{-- Formulaire de configuration --}}
    <div class="card">
        <div class="card-header bg-light">
            <h5 class="mb-0"><i class="fas fa-cog mr-2"></i>Configuration</h5>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('admin.weather-surcharge.update') }}">
                @csrf

                <div class="form-group">
                    <label><strong>État de la surcharge</strong></label>
                    <div>
                        <div class="custom-control custom-radio custom-control-inline">
                            <input type="radio" id="sur-on" name="weather_surcharge_enabled" value="1" class="custom-control-input" {{ $enabled ? 'checked' : '' }}>
                            <label class="custom-control-label text-warning" for="sur-on">
                                <i class="fas fa-cloud-rain"></i> Activer la surcharge (saison des pluies)
                            </label>
                        </div>
                        <div class="custom-control custom-radio custom-control-inline">
                            <input type="radio" id="sur-off" name="weather_surcharge_enabled" value="0" class="custom-control-input" {{ !$enabled ? 'checked' : '' }}>
                            <label class="custom-control-label text-success" for="sur-off">
                                <i class="fas fa-sun"></i> Désactivée (tarifs normaux)
                            </label>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="percent"><strong>Majoration (%)</strong></label>
                    <div class="input-group" style="max-width:200px;">
                        <input type="number" id="percent" name="weather_surcharge_percent"
                               class="form-control" value="{{ $percent }}"
                               min="0" max="200" step="1">
                        <div class="input-group-append">
                            <span class="input-group-text">%</span>
                        </div>
                    </div>
                    <small class="form-text text-muted">
                        Ex: 20 = frais de livraison × 1.20.
                        Frais de base actuels : {{ number_format(\App\Services\ConfigService::getDefaultDeliveryFee(), 0, ',', ' ') }} FCFA →
                        avec surcharge : {{ number_format(\App\Services\ConfigService::getDefaultDeliveryFee() * (1 + $percent / 100), 0, ',', ' ') }} FCFA
                    </small>
                </div>

                <div class="form-group">
                    <label for="label"><strong>Libellé affiché au client</strong></label>
                    <input type="text" id="label" name="weather_surcharge_label"
                           class="form-control" value="{{ $label }}"
                           maxlength="100" placeholder="Ex: Majoration saison des pluies">
                    <small class="form-text text-muted">Visible sur la page checkout et le reçu de commande.</small>
                </div>

                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save mr-1"></i> Enregistrer
                </button>
            </form>
        </div>
    </div>

    {{-- Info contextuelle --}}
    <div class="card bg-light">
        <div class="card-body">
            <h6><i class="fas fa-info-circle text-info mr-1"></i>Comment ça marche</h6>
            <ul class="mb-0 text-muted" style="font-size:13px;">
                <li>Quand la surcharge est active, les frais de livraison sont automatiquement majorés du pourcentage configuré.</li>
                <li>La ligne de surcharge apparaît séparément sur le checkout pour la transparence client.</li>
                <li>Le montant réel est enregistré dans chaque commande (champ <code>weather_surcharge</code>).</li>
                <li>La désactivation est instantanée — les nouvelles commandes reprennent le tarif de base.</li>
            </ul>
        </div>
    </div>

</div>
</div>
@endsection
