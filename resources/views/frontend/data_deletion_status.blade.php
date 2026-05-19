@php
    $authBrand = app(\App\Services\AuthBrandingService::class)->resolve(request());
    $brandRouteParams = $authBrand['key'] !== 'bantudelice' ? ['brand' => $authBrand['key']] : [];
    $brandColor = $authBrand['primary'];
    $brandDark = $authBrand['primary_dark'];
    $brandSoft = $authBrand['primary_soft'] ?? 'rgba(0, 149, 67, 0.12)';

    $label = 'Inconnu';
    $color = '#6B7280';
    if ($status === 'processed') {
        $label = 'Traitée';
        $color = '#05944F';
    }
    if ($status === 'not_found') {
        $label = 'Compte non trouvé';
        $color = '#F59E0B';
    }
@endphp
@extends('frontend.layouts.app-modern')
@section('title', 'Statut de suppression | ' . $authBrand['name'])
@section('description', 'Statut de la demande de suppression de vos données personnelles sur ' . $authBrand['name'] . '.')
@section('body_class', 'bd-support-page bd-data-deletion-status-page')
@section('body_style', "--support-brand-color: {$brandColor}; --support-brand-dark: {$brandDark}; --support-brand-soft: {$brandSoft}; --support-brand-veil: {$brandColor}1A; --support-status-color: {$color};")

@section('content')
<section class="support-hero support-hero--dark">
    <div class="container">
        <span class="section-badge support-hero-badge">
            <i class="fas fa-clipboard-check"></i> Statut
        </span>
        <h1 class="support-hero-title support-hero-title--compact">Demande de suppression des données</h1>
        <p class="support-hero-copy support-hero-copy--compact">
            Référence de confirmation : <strong>{{ $code }}</strong>
        </p>
    </div>
</section>

<section class="section support-section">
    <div class="container">
        <div class="support-status-shell">
            <article class="support-status-card">
                <div class="support-status-head">
                    <div class="support-status-icon">
                        <i class="fas fa-user-shield"></i>
                    </div>
                    <div>
                        <div class="support-status-label">Statut</div>
                        <div class="support-status-value">{{ $label }}</div>
                    </div>
                </div>

                <hr class="support-status-divider">

                <p class="support-status-copy">
                    Si vous avez des questions, consultez les <a href="{{ route('data.deletion', $brandRouteParams) }}" class="support-status-link">instructions de suppression</a>
                    ou contactez <strong>{{ $authBrand['support_email'] }}</strong>.
                </p>

                <a href="{{ route('privacy.policy', $brandRouteParams) }}" class="support-primary-link support-primary-link--fit">
                    Politique de confidentialité
                </a>
            </article>
        </div>
    </div>
</section>
@endsection
