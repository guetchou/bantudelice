@extends('layouts.restaurant_app')
@section('title','Tableau de bord | ' . \App\Services\ConfigService::getCompanyName())
@section('topbar_title', 'Tableau de bord')
@section('dashboard_nav', 'active')

@section('style')
<style>
/* ═══════════════════════════════════════════════════════
   Dashboard restaurant — design professionnel
   Palette : neutre + vert accent strict
   Typographie : Inter uniquement
   ═══════════════════════════════════════════════════════ */

/* ── Reset de page ─────────────────────────────────── */
.db { display: flex; flex-direction: column; gap: 20px; }

/* ── En-tête de page ───────────────────────────────── */
.db-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    flex-wrap: wrap;
}
.db-header__left {}
.db-header__title {
    font-size: 20px;
    font-weight: 700;
    color: #111827;
    letter-spacing: -.02em;
    line-height: 1.2;
}
.db-header__sub {
    font-size: 13px;
    color: #6b7280;
    margin-top: 2px;
}
.db-header__actions { display: flex; gap: 8px; align-items: center; }
.db-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 7px 14px;
    border-radius: 6px;
    font-size: 13px;
    font-weight: 500;
    cursor: pointer;
    transition: .12s;
    font-family: 'Inter', sans-serif;
}
.db-btn--outline {
    background: #fff;
    border: 1px solid #e5e7eb;
    color: #374151;
}
.db-btn--outline:hover { border-color: #009543; color: #009543; }
.db-btn--primary {
    background: #009543;
    border: 1px solid #009543;
    color: #fff;
    text-decoration: none;
}
.db-btn--primary:hover { background: #007836; border-color: #007836; color: #fff; }

/* ── KPI strip ─────────────────────────────────────── */
.db-kpi-row {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 12px;
}
.db-kpi {
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    padding: 16px 18px;
    display: flex;
    flex-direction: column;
    gap: 0;
    text-decoration: none;
    color: inherit;
    transition: border-color .12s, box-shadow .12s;
}
.db-kpi:hover { border-color: #009543; box-shadow: 0 0 0 3px rgba(0,149,67,.06); }
.db-kpi__label {
    font-size: 12px;
    font-weight: 500;
    color: #6b7280;
    white-space: nowrap;
}
.db-kpi__value {
    font-size: 26px;
    font-weight: 700;
    color: #111827;
    letter-spacing: -.03em;
    line-height: 1.1;
    margin: 6px 0 4px;
}
.db-kpi__hint { font-size: 11px; color: #9ca3af; }
.db-kpi__indicator {
    display: inline-flex;
    align-items: center;
    gap: 3px;
    font-size: 11px;
    font-weight: 600;
    margin-top: 4px;
}
.db-kpi__indicator--green { color: #009543; }
.db-kpi__indicator--amber { color: #d97706; }
.db-kpi__indicator--neutral { color: #9ca3af; }

/* ── Grid 2 colonnes ───────────────────────────────── */
.db-main-grid {
    display: grid;
    grid-template-columns: 1fr 320px;
    gap: 16px;
    align-items: start;
}

/* ── Card ──────────────────────────────────────────── */
.db-card {
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    overflow: hidden;
}
.db-card__head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding: 14px 18px;
    border-bottom: 1px solid #f3f4f6;
    flex-wrap: wrap;
}
.db-card__title {
    font-size: 13px;
    font-weight: 600;
    color: #111827;
}
.db-card__sub { font-size: 12px; color: #9ca3af; margin-top: 1px; }
.db-card__body { padding: 18px; }
.db-card__body--flush { padding: 0; }

/* ── Tabs ──────────────────────────────────────────── */
.db-tabs {
    display: inline-flex;
    border: 1px solid #e5e7eb;
    border-radius: 6px;
    overflow: hidden;
    background: #f9fafb;
}
.db-tabs a {
    display: inline-flex;
    align-items: center;
    padding: 5px 12px;
    font-size: 12px;
    font-weight: 500;
    color: #6b7280;
    border: none;
    background: transparent;
    cursor: pointer;
    transition: .12s;
    text-decoration: none;
}
.db-tabs a.active { background: #fff; color: #009543; font-weight: 600; }
.db-tabs a:not(:last-child) { border-right: 1px solid #e5e7eb; }

/* ── Chart ─────────────────────────────────────────── */
.db-chart-wrap { position: relative; height: 220px; }
.db-chart-wrap canvas { width: 100% !important; height: 100% !important; }

/* ── Pipeline stack ────────────────────────────────── */
.db-pipeline { display: flex; flex-direction: column; }
.db-pipeline-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding: 12px 18px;
    border-bottom: 1px solid #f3f4f6;
    text-decoration: none;
    color: inherit;
    transition: background .1s;
}
.db-pipeline-item:last-child { border-bottom: none; }
.db-pipeline-item:hover { background: #f9fafb; }
.db-pipeline-item__left { display: flex; align-items: center; gap: 10px; }
.db-pipeline-item__dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    flex-shrink: 0;
}
.db-pipeline-item__dot--amber { background: #f59e0b; }
.db-pipeline-item__dot--blue  { background: #3b82f6; }
.db-pipeline-item__dot--indigo{ background: #6366f1; }
.db-pipeline-item__dot--green { background: #009543; }
.db-pipeline-item__label { font-size: 13px; font-weight: 500; color: #374151; }
.db-pipeline-item__hint { font-size: 11px; color: #9ca3af; }
.db-pipeline-item__value {
    font-size: 18px;
    font-weight: 700;
    color: #111827;
    letter-spacing: -.02em;
}

/* ── Séparateur section ────────────────────────────── */
.db-section-label {
    font-size: 11px;
    font-weight: 700;
    letter-spacing: .07em;
    text-transform: uppercase;
    color: #9ca3af;
    margin-bottom: -8px;
}

/* ── Finance row ───────────────────────────────────── */
.db-finance-row {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 12px;
}
.db-finance-card {
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    padding: 16px 18px;
    display: flex;
    flex-direction: column;
    gap: 4px;
    border-top: 3px solid transparent;
}
.db-finance-card--green  { border-top-color: #009543; }
.db-finance-card--blue   { border-top-color: #3b82f6; }
.db-finance-card--amber  { border-top-color: #f59e0b; }
.db-finance-card--slate  { border-top-color: #64748b; }
.db-finance-card__label  { font-size: 12px; font-weight: 500; color: #6b7280; }
.db-finance-card__value  { font-size: 20px; font-weight: 700; color: #111827; letter-spacing: -.025em; line-height: 1.2; }
.db-finance-card--green .db-finance-card__value  { color: #009543; }
.db-finance-card__desc   { font-size: 11px; color: #9ca3af; margin-top: 2px; }

/* ── Performance cards ─────────────────────────────── */
.db-perf-row {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 12px;
}
.db-perf-card {
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    padding: 14px 16px;
    text-decoration: none;
    color: inherit;
    transition: border-color .12s;
}
.db-perf-card:hover { border-color: #009543; }
.db-perf-card__label { font-size: 12px; font-weight: 500; color: #6b7280; }
.db-perf-card__value { font-size: 22px; font-weight: 700; color: #111827; letter-spacing: -.025em; margin: 4px 0 2px; line-height: 1.2; }
.db-perf-card__hint { font-size: 11px; color: #9ca3af; }

/* ── Table ─────────────────────────────────────────── */
.db-table { width: 100%; border-collapse: collapse; }
.db-table th {
    padding: 10px 16px;
    font-size: 11px;
    font-weight: 700;
    letter-spacing: .06em;
    text-transform: uppercase;
    color: #9ca3af;
    text-align: left;
    border-bottom: 1px solid #f3f4f6;
    background: #f9fafb;
}
.db-table td {
    padding: 12px 16px;
    font-size: 13px;
    color: #374151;
    border-bottom: 1px solid #f9fafb;
    vertical-align: middle;
}
.db-table tr:last-child td { border-bottom: none; }
.db-table tr:hover td { background: #f9fafb; }
.db-table td:first-child { font-weight: 600; color: #111827; }

/* ── Badge statut ──────────────────────────────────── */
.db-badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 2px 8px;
    border-radius: 999px;
    font-size: 11px;
    font-weight: 600;
}
.db-badge::before { content: ''; width: 5px; height: 5px; border-radius: 50%; background: currentColor; display: block; }
.db-badge--new       { background: #fef9c3; color: #854d0e; }
.db-badge--preparing { background: #fef3c7; color: #d97706; }
.db-badge--delivering{ background: #dbeafe; color: #1d4ed8; }
.db-badge--done      { background: #dcfce7; color: #007836; }
.db-badge--cancelled { background: #fee2e2; color: #b91c1c; }

/* ── Reversement aside ─────────────────────────────── */
.db-rev-stack { display: flex; flex-direction: column; gap: 0; }
.db-rev-item {
    padding: 14px 18px;
    border-bottom: 1px solid #f3f4f6;
}
.db-rev-item:last-child { border-bottom: none; }
.db-rev-item__label { font-size: 11px; font-weight: 600; color: #9ca3af; text-transform: uppercase; letter-spacing: .06em; }
.db-rev-item__value { font-size: 18px; font-weight: 700; color: #111827; letter-spacing: -.025em; margin: 3px 0 1px; line-height: 1.2; }
.db-rev-item__value--green { color: #009543; }
.db-rev-item__value--amber { color: #d97706; }
.db-rev-item__desc { font-size: 11px; color: #9ca3af; }

/* ── Quick links ───────────────────────────────────── */
.db-quick-links { display: flex; flex-wrap: wrap; gap: 8px; }
.db-quick-link {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 14px;
    border-radius: 6px;
    border: 1px solid #e5e7eb;
    background: #fff;
    font-size: 13px;
    font-weight: 500;
    color: #374151;
    text-decoration: none;
    transition: .12s;
}
.db-quick-link:hover { border-color: #009543; color: #009543; }
.db-quick-link i { font-size: 11px; color: #9ca3af; }

/* ── Responsive ────────────────────────────────────── */
@media (max-width: 1280px) {
    .db-main-grid { grid-template-columns: 1fr; }
    .db-finance-row { grid-template-columns: repeat(2, 1fr); }
    .db-perf-row { grid-template-columns: repeat(2, 1fr); }
}
@media (max-width: 768px) {
    .db-kpi-row { grid-template-columns: repeat(2, 1fr); }
    .db-finance-row { grid-template-columns: 1fr 1fr; }
    .db-perf-row { grid-template-columns: 1fr 1fr; }
}
@media (max-width: 480px) {
    .db-kpi-row { grid-template-columns: 1fr 1fr; }
    .db-finance-row { grid-template-columns: 1fr; }
    .db-perf-row { grid-template-columns: 1fr; }
}
</style>
@endsection

@section('content')
<div class="db">

    {{-- ── T1.1 — Bandeau disponibilité restaurant ────────── --}}
    @php
        $restaurant = \App\Restaurant::where('user_id', auth()->id())->first();
        $isPaused   = $restaurant && $restaurant->is_paused;
        $pauseUntil = $restaurant?->paused_until;
        $pauseLabel = [
            'e2c'         => 'Coupure électrique',
            'weather'     => 'Routes impraticables',
            'overloaded'  => 'Trop de commandes',
            'short_break' => 'Pause courte',
            'manual'      => 'Fermeture manuelle',
            'auto_inactive' => 'Pause automatique (inactivité)',
            'other'       => 'Autre raison',
        ][$restaurant?->pause_reason] ?? null;
    @endphp
    <div id="availability-banner"
         class="db-availability-banner {{ $isPaused ? 'db-availability-banner--paused' : 'db-availability-banner--online' }}"
         style="border-radius:12px;padding:14px 20px;margin-bottom:20px;display:flex;align-items:center;gap:16px;flex-wrap:wrap;">

        <span id="availability-dot" style="width:12px;height:12px;border-radius:50%;flex-shrink:0;background:{{ $isPaused ? '#f59e0b' : '#22c55e' }};box-shadow:0 0 0 3px {{ $isPaused ? 'rgba(245,158,11,.25)' : 'rgba(34,197,94,.25)' }};"></span>

        <div style="flex:1;min-width:180px;">
            <div id="availability-status-text" style="font-weight:700;font-size:15px;color:{{ $isPaused ? '#92400e' : '#14532d' }};">
                @if($isPaused)
                    Restaurant en pause
                    @if($pauseLabel) — {{ $pauseLabel }} @endif
                @else
                    Restaurant en ligne — vous recevez des commandes
                @endif
            </div>
            @if($isPaused && $pauseUntil)
                <div style="font-size:12px;color:#78350f;margin-top:2px;">
                    Réouverture automatique à {{ $pauseUntil->format('H:i') }}
                </div>
            @endif
        </div>

        @if(!$isPaused)
            {{-- Bouton Mettre en pause + sélecteur raison --}}
            <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                <select id="pause-reason-select" style="border-radius:8px;border:1px solid #d1d5db;padding:6px 10px;font-size:13px;background:#fff;">
                    <option value="e2c">⚡ Coupure électrique (E2C)</option>
                    <option value="weather">🌧 Routes impraticables</option>
                    <option value="overloaded">🔥 Trop de commandes</option>
                    <option value="short_break">☕ Pause courte</option>
                    <option value="manual">🔒 Fermeture manuelle</option>
                    <option value="other">Autre raison</option>
                </select>
                <select id="pause-duration-select" style="border-radius:8px;border:1px solid #d1d5db;padding:6px 10px;font-size:13px;background:#fff;">
                    <option value="">Durée libre</option>
                    <option value="30">30 minutes</option>
                    <option value="60">1 heure</option>
                    <option value="120">2 heures</option>
                    <option value="240">4 heures</option>
                    <option value="480">8 heures</option>
                </select>
                <button onclick="toggleAvailability('pause')"
                        style="background:#f59e0b;color:#fff;border:none;border-radius:8px;padding:7px 16px;font-weight:600;cursor:pointer;font-size:13px;">
                    <i class="fas fa-pause-circle"></i> Mettre en pause
                </button>
            </div>
        @else
            {{-- Bouton Reprendre --}}
            <button onclick="toggleAvailability('resume')"
                    style="background:#22c55e;color:#fff;border:none;border-radius:8px;padding:7px 16px;font-weight:600;cursor:pointer;font-size:13px;">
                <i class="fas fa-play-circle"></i> Reprendre l'activité
            </button>
        @endif
    </div>

    <style>
    .db-availability-banner--online  { background: #f0fdf4; border: 1px solid #bbf7d0; }
    .db-availability-banner--paused  { background: #fffbeb; border: 1px solid #fde68a; }
    </style>

    <script>
    function toggleAvailability(action) {
        const url    = action === 'pause'
            ? '{{ route("restaurant.availability.pause") }}'
            : '{{ route("restaurant.availability.resume") }}';
        const reason   = document.getElementById('pause-reason-select')?.value || 'manual';
        const duration = document.getElementById('pause-duration-select')?.value || '';

        const body = action === 'pause'
            ? JSON.stringify({ reason, duration_minutes: duration ? parseInt(duration) : null })
            : '{}';

        fetch(url, {
            method:  'POST',
            headers: {
                'Content-Type':     'application/json',
                'Accept':           'application/json',
                'X-CSRF-TOKEN':     '{{ csrf_token() }}',
            },
            body,
        })
        .then(r => r.json())
        .then(data => {
            if (data.status) {
                window.location.reload();
            } else {
                alert(data.message || 'Erreur lors de la mise à jour.');
            }
        })
        .catch(() => alert('Erreur réseau. Réessayez.'));
    }
    </script>

    {{-- ── En-tête de page ──────────────────────────────── --}}
    <div class="db-header">
        <div class="db-header__left">
            <div class="db-header__title">Tableau de bord</div>
            <div class="db-header__sub">{{ \Carbon\Carbon::now()->locale('fr')->isoFormat('dddd D MMMM YYYY') }}</div>
        </div>
        <div class="db-header__actions">
            <a href="{{ route('restaurant.all_orders') }}" class="db-btn db-btn--outline">
                <i class="fas fa-receipt"></i> Commandes
            </a>
            <a href="{{ route('product.index') }}" class="db-btn db-btn--primary">
                <i class="fas fa-plus"></i> Nouveau produit
            </a>
        </div>
    </div>

    {{-- ── 4 KPI du jour ─────────────────────────────────── --}}
    <div class="db-kpi-row">
        @foreach($pipeline as $i => $step)
            @php
                $dotColors = ['amber','amber','blue','green'];
                $dotClass  = 'db-pipeline-item__dot--' . ($dotColors[$i] ?? 'green');
                $indClass  = $step['value'] > 0
                    ? ($i === 3 ? 'db-kpi__indicator--green' : 'db-kpi__indicator--amber')
                    : 'db-kpi__indicator--neutral';
            @endphp
            <a href="{{ $step['route'] }}" class="db-kpi" style="text-decoration:none;">
                <span class="db-kpi__label">{{ $step['label'] }}</span>
                <span class="db-kpi__value">{{ $step['value'] }}</span>
                <span class="db-kpi__hint">{{ $step['hint'] }}</span>
                <span class="db-kpi__indicator {{ $indClass }}">
                    @if($step['value'] > 0)
                        <i class="fas fa-circle" style="font-size:6px;"></i>
                        {{ $step['value'] > 1 ? 'Commande' . 's' : 'Commande' }}
                    @else
                        <i class="fas fa-minus" style="font-size:9px;"></i> Aucune
                    @endif
                </span>
            </a>
        @endforeach
    </div>

    {{-- ── Graphique + Pipeline ──────────────────────────── --}}
    <div class="db-main-grid">

        {{-- Chart hebdo --}}
        <div class="db-card">
            <div class="db-card__head">
                <div>
                    <div class="db-card__title">Ventes de la semaine</div>
                    <div class="db-card__sub">CA brut jour par jour · semaine en cours</div>
                </div>
                <div class="db-tabs nav nav-pills" role="tablist">
                    <a class="active" href="#db-bar" data-toggle="tab">Barres</a>
                    <a href="#db-line" data-toggle="tab">Courbe</a>
                </div>
            </div>
            <div class="db-card__body">
                <div class="tab-content p-0">
                    <div class="tab-pane fade show active" id="db-bar">
                        <div class="db-chart-wrap"><canvas id="barCanvas"></canvas></div>
                    </div>
                    <div class="tab-pane fade" id="db-line">
                        <div class="db-chart-wrap"><canvas id="lineCanvas"></canvas></div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Reversements --}}
        <div class="db-card">
            <div class="db-card__head">
                <div>
                    <div class="db-card__title">Reversements</div>
                    <div class="db-card__sub">État de votre trésorerie partenaire</div>
                </div>
            </div>
            <div class="db-card__body--flush">
                <div class="db-rev-stack">
                    <div class="db-rev-item">
                        <div class="db-rev-item__label">Net partenaire — mois</div>
                        <div class="db-rev-item__value db-rev-item__value--green">{{ number_format($kpis['net_partner_month'], 0, ',', ' ') }} F</div>
                        <div class="db-rev-item__desc">CA du mois après commission plateforme</div>
                    </div>
                    <div class="db-rev-item">
                        <div class="db-rev-item__label">En attente de versement</div>
                        <div class="db-rev-item__value db-rev-item__value--amber">{{ number_format($kpis['pending_settlement'], 0, ',', ' ') }} F</div>
                        <div class="db-rev-item__desc">Retenu par validation ou rapprochement</div>
                    </div>
                    <div class="db-rev-item">
                        <div class="db-rev-item__label">Disponible au retrait</div>
                        <div class="db-rev-item__value db-rev-item__value--green" style="font-size:22px;">{{ number_format($kpis['available_withdrawal'], 0, ',', ' ') }} F</div>
                        <div class="db-rev-item__desc">Net libéré sur le ledger de reversement</div>
                    </div>
                    <div class="db-rev-item">
                        <div class="db-rev-item__label">Ticket moyen aujourd'hui</div>
                        <div class="db-rev-item__value">{{ number_format($kpis['average_ticket'], 0, ',', ' ') }} F</div>
                        <div class="db-rev-item__desc">Moyenne par commande sur la journée</div>
                    </div>
                </div>
            </div>
        </div>

    </div>{{-- /.db-main-grid --}}

    {{-- ── Finance ────────────────────────────────────────── --}}
    <p class="db-section-label">Finances du mois</p>
    <div class="db-finance-row">
        <div class="db-finance-card db-finance-card--green">
            <div class="db-finance-card__label">CA aujourd'hui</div>
            <div class="db-finance-card__value">{{ number_format($kpis['gross_today'], 0, ',', ' ') }} F</div>
            <div class="db-finance-card__desc">{{ $kpis['orders_today'] }} commande{{ $kpis['orders_today'] !== 1 ? 's' : '' }} reçue{{ $kpis['orders_today'] !== 1 ? 's' : '' }}</div>
        </div>
        <div class="db-finance-card db-finance-card--blue">
            <div class="db-finance-card__label">CA du mois</div>
            <div class="db-finance-card__value">{{ number_format($kpis['gross_month'], 0, ',', ' ') }} F</div>
            <div class="db-finance-card__desc">Cumul brut du mois calendaire</div>
        </div>
        <div class="db-finance-card db-finance-card--amber">
            <div class="db-finance-card__label">Commission plateforme</div>
            <div class="db-finance-card__value">{{ number_format($kpis['commission_month'], 0, ',', ' ') }} F</div>
            <div class="db-finance-card__desc">Déduite du CA mensuel brut</div>
        </div>
        <div class="db-finance-card db-finance-card--slate">
            <div class="db-finance-card__label">Net partenaire</div>
            <div class="db-finance-card__value">{{ number_format($kpis['net_partner_month'], 0, ',', ' ') }} F</div>
            <div class="db-finance-card__desc">Après déduction commission</div>
        </div>
    </div>

    {{-- ── Performance ─────────────────────────────────────── --}}
    <p class="db-section-label">Catalogue & performance</p>
    <div class="db-perf-row">
        @foreach($performanceCards as $card)
            @if($card['route'])
                <a href="{{ $card['route'] }}" class="db-perf-card">
            @else
                <div class="db-perf-card">
            @endif
                <div class="db-perf-card__label">{{ $card['label'] }}</div>
                <div class="db-perf-card__value">{{ $card['value'] }}</div>
                <div class="db-perf-card__hint">{{ $card['hint'] }}</div>
            @if($card['route'])
                </a>
            @else
                </div>
            @endif
        @endforeach
    </div>

    {{-- ── Accès rapides ────────────────────────────────────── --}}
    <div class="db-card">
        <div class="db-card__head">
            <div class="db-card__title">Accès rapides</div>
        </div>
        <div class="db-card__body">
            <div class="db-quick-links">
                @foreach($quickActions as $action)
                    <a href="{{ $action['route'] }}" class="db-quick-link">
                        <i class="fas fa-arrow-up-right-from-square"></i>
                        {{ $action['label'] }}
                    </a>
                @endforeach
            </div>
        </div>
    </div>

    {{-- ── Commandes récentes ───────────────────────────────── --}}
    @if(!empty($recentOrders))
    <div class="db-card">
        <div class="db-card__head">
            <div>
                <div class="db-card__title">Commandes récentes</div>
                <div class="db-card__sub">8 dernières commandes</div>
            </div>
            <a href="{{ route('restaurant.all_orders') }}" class="db-btn db-btn--outline" style="font-size:12px;padding:5px 12px;">
                Tout voir <i class="fas fa-arrow-right fa-xs"></i>
            </a>
        </div>
        <div class="db-card__body--flush">
            <table class="db-table">
                <thead>
                    <tr>
                        <th>Référence</th>
                        <th>Client</th>
                        <th>Montant</th>
                        <th>Statut</th>
                        <th>Heure</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($recentOrders as $order)
                        @php
                            $badgeClass = match($order['status']) {
                                'Nouvelle'    => 'db-badge--new',
                                'Préparation' => 'db-badge--preparing',
                                'Livraison'   => 'db-badge--delivering',
                                'Terminée'    => 'db-badge--done',
                                default       => 'db-badge--cancelled',
                            };
                        @endphp
                        <tr>
                            <td>{{ $order['ref'] }}</td>
                            <td style="color:#6b7280;">{{ $order['customer'] }}</td>
                            <td style="font-weight:600;color:#009543;">{{ number_format($order['amount'], 0, ',', ' ') }} F</td>
                            <td><span class="db-badge {{ $badgeClass }}">{{ $order['status'] }}</span></td>
                            <td style="color:#9ca3af;">{{ $order['time'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

</div>{{-- /.db --}}
@endsection

@section('script')
<script>
$(function () {
    var labels  = {!! json_encode($salesLabels) !!};
    var series  = {!! json_encode($salesSeries) !!};
    var green   = '#009543';
    var pale    = 'rgba(0,149,67,.08)';
    var today   = (new Date().getDay() + 6) % 7; // 0=Lun

    var gridOpts = {
        xAxes: [{ gridLines: { display: false }, ticks: { fontColor: '#9ca3af', fontSize: 11, fontFamily: 'Inter' } }],
        yAxes: [{ gridLines: { color: '#f3f4f6', drawBorder: false }, ticks: { fontColor: '#9ca3af', fontSize: 11, fontFamily: 'Inter' } }]
    };

    /* Bar */
    new Chart(document.getElementById('barCanvas').getContext('2d'), {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                data: series,
                backgroundColor: series.map(function(_, i) { return i === today ? green : 'rgba(0,149,67,.18)'; }),
                borderRadius: 5,
                borderWidth: 0,
            }]
        },
        options: {
            maintainAspectRatio: false, responsive: true,
            legend: { display: false },
            tooltips: { callbacks: { label: function(t) { return ' ' + parseInt(t.yLabel).toLocaleString('fr') + ' F'; } } },
            scales: gridOpts,
        }
    });

    /* Line */
    var lineCtx = document.getElementById('lineCanvas').getContext('2d');
    var grad = lineCtx.createLinearGradient(0, 0, 0, 220);
    grad.addColorStop(0, 'rgba(0,149,67,.18)');
    grad.addColorStop(1, 'rgba(0,149,67,0)');
    new Chart(lineCtx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                data: series,
                borderColor: green,
                backgroundColor: grad,
                borderWidth: 2,
                pointBackgroundColor: green,
                pointBorderColor: '#fff',
                pointBorderWidth: 2,
                pointRadius: 4,
                pointHoverRadius: 6,
                fill: true,
                lineTension: 0.4,
            }]
        },
        options: {
            maintainAspectRatio: false, responsive: true,
            legend: { display: false },
            tooltips: { callbacks: { label: function(t) { return ' ' + parseInt(t.yLabel).toLocaleString('fr') + ' F'; } } },
            scales: gridOpts,
        }
    });
});
</script>
@endsection
