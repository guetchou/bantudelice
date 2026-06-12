@extends('layouts.admin-modern')

@section('title', 'Surcharge météo | BantuDelice Admin')
@section('page_title', 'Surcharge — Saison des pluies')
@section('nav_active', 'weather-surcharge')

@section('style')
<style>
.wth-page { padding:24px; display:flex; justify-content:center; }
.wth-inner { width:100%; max-width:600px; display:grid; gap:16px; }
.wth-alert { padding:12px 16px; border-radius:8px; font-size:13px; font-weight:500; border:1px solid transparent; }
.wth-alert--warning { background:#fffbeb; color:#92400e; border-color:#fde68a; }
.wth-alert--success { background:#f0fdf4; color:#166534; border-color:#bbf7d0; }
.wth-status-card { border-radius:10px; overflow:hidden; }
.wth-status-card--active { border:2px solid #f59e0b; }
.wth-status-card--inactive { border:2px solid #16a34a; }
.wth-status-header { padding:12px 18px; display:flex; align-items:center; justify-content:space-between; gap:12px; }
.wth-status-header--active { background:#f59e0b; color:#1c1917; }
.wth-status-header--inactive { background:#16a34a; color:#fff; }
.wth-status-header span { font-size:13px; font-weight:600; }
.wth-status-body { padding:16px 18px; background:#fff; }
.wth-status-body p { margin:0; font-size:13px; color:#9ca3af; }
.wth-inline-alert { padding:12px 14px; background:#fffbeb; border:1px solid #fde68a; border-radius:8px; font-size:13px; color:#92400e; }
.wth-toggle-btn { display:inline-flex; align-items:center; padding:6px 14px; border:none; border-radius:6px; font-size:12px; font-weight:700; cursor:pointer; }
.wth-toggle-btn--deactivate { background:#fff; color:#92400e; }
.wth-toggle-btn--activate { background:#fef3c7; color:#92400e; }
.wth-card { background:#fff; border:1px solid #e5e7eb; border-radius:10px; overflow:hidden; }
.wth-card__header { padding:12px 18px; border-bottom:1px solid #f3f4f6; background:#f9fafb; display:flex; align-items:center; gap:8px; }
.wth-card__title { font-size:14px; font-weight:700; color:#111827; margin:0; }
.wth-card__body { padding:18px; }
.wth-field { margin-bottom:16px; }
.wth-field:last-of-type { margin-bottom:0; }
.wth-label { font-size:13px; font-weight:700; color:#374151; display:block; margin-bottom:8px; }
.wth-radio-group { display:flex; flex-direction:column; gap:8px; }
.wth-radio-row { display:flex; align-items:center; gap:8px; font-size:13px; color:#374151; cursor:pointer; }
.wth-radio-row input[type="radio"] { width:16px; height:16px; cursor:pointer; }
.wth-amount-row { display:flex; align-items:center; gap:0; max-width:180px; }
.wth-amount-input { padding:9px 12px; border:1px solid #d1d5db; border-radius:6px 0 0 6px; font-size:14px; width:100%; box-sizing:border-box; }
.wth-amount-suffix { display:inline-flex; align-items:center; padding:9px 12px; border:1px solid #d1d5db; border-left:none; border-radius:0 6px 6px 0; background:#f9fafb; font-size:13px; color:#6b7280; white-space:nowrap; }
.wth-hint { font-size:12px; color:#9ca3af; margin-top:6px; }
.wth-text-input { width:100%; padding:9px 12px; border:1px solid #d1d5db; border-radius:6px; font-size:14px; box-sizing:border-box; }
.wth-btn-primary { display:inline-flex; align-items:center; gap:6px; padding:9px 18px; background:#1e3a5f; color:#fff; border:none; border-radius:6px; font-size:13px; font-weight:600; cursor:pointer; margin-top:16px; }
.wth-info-card { background:#f9fafb; border:1px solid #e5e7eb; border-radius:10px; padding:16px 18px; }
.wth-info-card h6 { font-size:13px; font-weight:700; color:#374151; margin:0 0 10px; }
.wth-info-list { margin:0; padding-left:18px; font-size:13px; color:#9ca3af; line-height:1.7; }
</style>
@endsection

@section('content')
<div class="wth-page">
<div class="wth-inner">
    @if(session()->has('alert'))
    <div class="wth-alert {{ session()->get('alert.type') === 'success' ? 'wth-alert--success' : 'wth-alert--warning' }}">
        {{ session()->get('alert.message') }}
    </div>
    @endif

    <div class="wth-status-card {{ $enabled ? 'wth-status-card--active' : 'wth-status-card--inactive' }}">
        <div class="wth-status-header {{ $enabled ? 'wth-status-header--active' : 'wth-status-header--inactive' }}">
            <span>
                <i class="fas {{ $enabled ? 'fa-exclamation-triangle' : 'fa-check-circle' }}" style="margin-right:6px;"></i>
                Statut : <strong>{{ $enabled ? 'Surcharge ACTIVE' : 'Tarifs normaux' }}</strong>
            </span>
            <form method="POST" action="{{ route('admin.weather-surcharge.toggle') }}" style="margin:0;">
                @csrf
                <button type="submit" class="wth-toggle-btn {{ $enabled ? 'wth-toggle-btn--deactivate' : 'wth-toggle-btn--activate' }}">
                    {{ $enabled ? 'Désactiver la surcharge' : 'Activer la surcharge' }}
                </button>
            </form>
        </div>
        <div class="wth-status-body">
            @if($enabled)
                <div class="wth-inline-alert">
                    <strong>Surcharge active :</strong> +{{ number_format($percent, 0) }}% sur les frais de livraison.<br>
                    Message affiché au client : <em>{{ $label }}</em>
                </div>
            @else
                <p>La surcharge n'est pas active. Les frais de livraison s'appliquent normalement.</p>
            @endif
        </div>
    </div>

    <div class="wth-card">
        <div class="wth-card__header">
            <i class="fas fa-cog" style="color:#9ca3af;"></i>
            <h5 class="wth-card__title">Configuration</h5>
        </div>
        <div class="wth-card__body">
            <form method="POST" action="{{ route('admin.weather-surcharge.update') }}">
                @csrf

                <div class="wth-field">
                    <label class="wth-label">État de la surcharge</label>
                    <div class="wth-radio-group">
                        <label class="wth-radio-row">
                            <input type="radio" id="sur-on" name="weather_surcharge_enabled" value="1" {{ $enabled ? 'checked' : '' }}>
                            <i class="fas fa-cloud-rain" style="color:#f59e0b;"></i>
                            Activer la surcharge (saison des pluies)
                        </label>
                        <label class="wth-radio-row">
                            <input type="radio" id="sur-off" name="weather_surcharge_enabled" value="0" {{ !$enabled ? 'checked' : '' }}>
                            <i class="fas fa-sun" style="color:#16a34a;"></i>
                            Désactivée (tarifs normaux)
                        </label>
                    </div>
                </div>

                <div class="wth-field">
                    <label class="wth-label" for="percent">Majoration (%)</label>
                    <div class="wth-amount-row">
                        <input type="number" id="percent" name="weather_surcharge_percent"
                               class="wth-amount-input" value="{{ $percent }}"
                               min="0" max="200" step="1">
                        <span class="wth-amount-suffix">%</span>
                    </div>
                    <div class="wth-hint">
                        Ex: 20 = frais de livraison × 1.20.
                        Frais de base actuels : {{ number_format(\App\Services\ConfigService::getDefaultDeliveryFee(), 0, ',', ' ') }} FCFA →
                        avec surcharge : {{ number_format(\App\Services\ConfigService::getDefaultDeliveryFee() * (1 + $percent / 100), 0, ',', ' ') }} FCFA
                    </div>
                </div>

                <div class="wth-field">
                    <label class="wth-label" for="label">Libellé affiché au client</label>
                    <input type="text" id="label" name="weather_surcharge_label"
                           class="wth-text-input" value="{{ $label }}"
                           maxlength="100" placeholder="Ex: Majoration saison des pluies">
                    <div class="wth-hint">Visible sur la page checkout et le reçu de commande.</div>
                </div>

                <button type="submit" class="wth-btn-primary">
                    <i class="fas fa-save"></i> Enregistrer
                </button>
            </form>
        </div>
    </div>

    <div class="wth-info-card">
        <h6><i class="fas fa-info-circle" style="color:#0369a1;margin-right:6px;"></i>Comment ça marche</h6>
        <ul class="wth-info-list">
            <li>Quand la surcharge est active, les frais de livraison sont automatiquement majorés du pourcentage configuré.</li>
            <li>La ligne de surcharge apparaît séparément sur le checkout pour la transparence client.</li>
            <li>Le montant réel est enregistré dans chaque commande (champ <code>weather_surcharge</code>).</li>
            <li>La désactivation est instantanée — les nouvelles commandes reprennent le tarif de base.</li>
        </ul>
    </div>
</div>
</div>
@endsection
