@extends('frontend.layouts.app-modern')
@section('title', 'Statut de suppression | BantuDelice')

@section('content')
<section style="background: linear-gradient(135deg, #111827 0%, #1F2937 100%); padding: 150px 0 80px; text-align: center;">
    <div class="container">
        <span class="section-badge" style="background: rgba(255,255,255,0.1); color: white;">
            <i class="fas fa-clipboard-check"></i> Statut
        </span>
        <h1 style="color: white; font-size: clamp(1.75rem, 4vw, 2.5rem); margin-top: 1rem;">
            Demande de suppression des données
        </h1>
        <p style="color: rgba(255,255,255,0.85); max-width: 760px; margin: 1rem auto 0; font-size: 1.05rem;">
            Code de confirmation : <strong>{{ $code }}</strong>
        </p>
    </div>
</section>

<section class="section">
    <div class="container">
        <div style="max-width: 760px; margin: 0 auto; background: #fff; padding: 2rem; border-radius: var(--radius-2xl); box-shadow: var(--shadow-lg);">
            @php
                $label = 'Inconnu';
                $color = '#6B7280';
                if ($status === 'processed') { $label = 'Traitée'; $color = '#05944F'; }
                if ($status === 'not_found') { $label = 'Compte non trouvé'; $color = '#F59E0B'; }
            @endphp

            <div style="display:flex; gap: 1rem; align-items:center;">
                <div style="width: 44px; height: 44px; border-radius: 12px; background: rgba(5,148,79,0.08); display:flex; align-items:center; justify-content:center;">
                    <i class="fas fa-user-shield" style="color:#05944F;"></i>
                </div>
                <div>
                    <div style="font-weight: 800; font-size: 1.1rem; margin-bottom: 2px;">Statut</div>
                    <div style="font-weight: 700; color: {{ $color }};">{{ $label }}</div>
                </div>
            </div>

            <hr style="margin: 1.5rem 0; border: none; border-top: 1px solid #E5E7EB;">

            <p style="color: var(--gray-600); line-height: 1.8; margin: 0;">
                Si vous avez des questions, consultez les <a href="{{ route('data.deletion') }}" style="color: var(--primary); font-weight: 600;">instructions de suppression</a>
                ou contactez <strong>{{ \App\Services\ConfigService::getContactEmail() }}</strong>.
            </p>
        </div>
    </div>
</section>
@endsection


