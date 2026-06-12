@php
    $partnerDashboardHeader = $partnerDashboardHeader ?? [];
    $partnerDashboardStats = $partnerDashboardHeader['stats'] ?? [];
    $partnerDashboardMedia = $partnerDashboardHeader['media'] ?? [];
@endphp

<section class="bd-partner-shell">
    <span class="bd-partner-shell__eyebrow">{{ $partnerDashboardHeader['eyebrow'] ?? 'Espace partenaire' }}</span>
    <div class="bd-partner-shell__layout">
        <div>
            <h1>{{ $partnerDashboardHeader['title'] ?? 'Tableau de bord partenaire' }}</h1>
            <p>{{ $partnerDashboardHeader['description'] ?? 'Vue consolidée des opérations et de la trésorerie partenaire.' }}</p>
        </div>
        <div class="bd-partner-shell__aside">
            @if(!empty($partnerDashboardMedia))
                <div class="bd-partner-shell__identity">
                    <div class="bd-partner-shell__identity-mark">
                        @if(!empty($partnerDashboardMedia['image']))
                            <img src="{{ $partnerDashboardMedia['image'] }}" alt="{{ $partnerDashboardMedia['alt'] ?? ($partnerDashboardMedia['title'] ?? 'Profil partenaire') }}">
                        @else
                            <span>{{ $partnerDashboardMedia['fallback'] ?? 'BD' }}</span>
                        @endif
                    </div>
                    <div class="bd-partner-shell__identity-copy">
                        @if(!empty($partnerDashboardMedia['tag']))
                            <span class="bd-partner-shell__identity-tag">{{ $partnerDashboardMedia['tag'] }}</span>
                        @endif
                        <strong>{{ $partnerDashboardMedia['title'] ?? 'Profil partenaire' }}</strong>
                        @if(!empty($partnerDashboardMedia['subtitle']))
                            <small>{{ $partnerDashboardMedia['subtitle'] }}</small>
                        @endif
                    </div>
                </div>
            @endif
            @if(!empty($partnerDashboardStats))
                <div class="bd-partner-shell__list">
                    @foreach($partnerDashboardStats as $stat)
                        <div class="bd-partner-shell__item">
                            <span class="bd-partner-shell__item-label">{{ $stat['label'] }}</span>
                            <span class="bd-partner-shell__item-value">{{ $stat['value'] }}</span>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</section>
