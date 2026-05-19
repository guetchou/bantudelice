@extends('layouts.app')
@section('title', 'Espace chauffeur | ' . \App\Services\ConfigService::getCompanyName())
@section('transport_driver_nav', 'active')

@section('style')
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
<style>
/* ── RESET ADMINLTE ─────────────────────────────────────────── */
.main-sidebar, .main-header { display:none!important; }
.content-wrapper {
    margin-left:0!important;
    background:#f1f5f9!important;
    font-family:'Manrope',sans-serif;
    min-height:100vh;
    padding-bottom:40px;
}

/* ── HEADER CHAUFFEUR ───────────────────────────────────────── */
.tx-header {
    background:#111827;
    color:#fff;
    padding:0 20px;
    position:sticky;
    top:0;
    z-index:900;
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:12px;
    height:56px;
}
.tx-header-brand {
    display:flex;
    align-items:center;
    gap:10px;
}
.tx-header-avatar {
    width:34px; height:34px;
    border-radius:50%;
    background:linear-gradient(135deg,#f97316,#ea580c);
    display:flex; align-items:center; justify-content:center;
    font-weight:900; font-size:.85rem; color:#fff; flex-shrink:0;
}
.tx-header-name {
    font-weight:800;
    font-size:.95rem;
    line-height:1.2;
}
.tx-header-role {
    font-size:.7rem;
    color:#94a3b8;
    font-weight:600;
    letter-spacing:.04em;
    text-transform:uppercase;
}
.tx-status-pill {
    display:inline-flex; align-items:center; gap:6px;
    background:#1e293b;
    border:1px solid #334155;
    border-radius:99px;
    padding:5px 12px;
    font-size:.78rem;
    font-weight:700;
    color:#94a3b8;
    white-space:nowrap;
}
.tx-status-pill.active {
    background:#052e16;
    border-color:#166534;
    color:#4ade80;
}
.tx-status-dot {
    width:7px; height:7px;
    border-radius:50%;
    background:currentColor;
    flex-shrink:0;
}

/* ── BODY ───────────────────────────────────────────────────── */
.tx-body {
    max-width:760px;
    margin:0 auto;
    padding:20px 16px;
    display:flex;
    flex-direction:column;
    gap:16px;
}

/* ── KPI STRIP ──────────────────────────────────────────────── */
.tx-kpi-strip {
    display:grid;
    grid-template-columns:repeat(3,1fr);
    gap:8px;
}
.tx-kpi {
    background:#fff;
    border-radius:14px;
    border:1px solid #e2e8f0;
    padding:14px 12px;
    text-align:center;
}
.tx-kpi-val {
    font-size:1.4rem;
    font-weight:900;
    color:#0f172a;
    line-height:1;
}
.tx-kpi-val.green { color:#16a34a; }
.tx-kpi-val.orange { color:#ea580c; }
.tx-kpi-lbl {
    font-size:.72rem;
    font-weight:700;
    color:#94a3b8;
    text-transform:uppercase;
    letter-spacing:.05em;
    margin-top:4px;
}

/* ── CARD ───────────────────────────────────────────────────── */
.tx-card {
    background:#fff;
    border-radius:18px;
    border:1px solid #e2e8f0;
    overflow:hidden;
}
.tx-card-stripe {
    height:4px;
    background:#e2e8f0;
}
.tx-card-stripe.orange { background:#f97316; }
.tx-card-stripe.green  { background:#22c55e; }
.tx-card-stripe.blue   { background:#3b82f6; }
.tx-card-stripe.gray   { background:#94a3b8; }
.tx-card-head {
    padding:16px 18px 0;
    display:flex;
    align-items:flex-start;
    justify-content:space-between;
    gap:12px;
}
.tx-card-title {
    font-size:1rem;
    font-weight:800;
    color:#0f172a;
}
.tx-card-sub {
    font-size:.78rem;
    color:#94a3b8;
    margin-top:2px;
}
.tx-card-body { padding:16px 18px 18px; }

/* ── COURSE ACTIVE ──────────────────────────────────────────── */
.tx-ride-amount {
    font-size:2.4rem;
    font-weight:900;
    color:#111827;
    line-height:1;
    margin-bottom:2px;
}
.tx-ride-unit { font-size:1rem; font-weight:700; color:#94a3b8; }
.tx-ride-meta {
    font-size:.82rem;
    color:#64748b;
    margin-top:4px;
    margin-bottom:16px;
}
.tx-info-grid {
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:8px;
    margin-bottom:16px;
}
.tx-info-cell {
    background:#f8fafc;
    border:1px solid #e2e8f0;
    border-radius:12px;
    padding:10px 12px;
}
.tx-info-cell-lbl {
    font-size:.7rem;
    font-weight:700;
    text-transform:uppercase;
    letter-spacing:.05em;
    color:#94a3b8;
    margin-bottom:3px;
}
.tx-info-cell-val {
    font-size:.9rem;
    font-weight:700;
    color:#0f172a;
}
.tx-info-cell-sub {
    font-size:.78rem;
    color:#64748b;
    margin-top:2px;
}
.tx-call-btn {
    display:inline-flex; align-items:center; gap:5px;
    margin-top:6px;
    background:#ecfdf5;
    color:#15803d;
    border:1px solid #bbf7d0;
    border-radius:8px;
    padding:4px 10px;
    font-size:.75rem;
    font-weight:700;
    text-decoration:none;
}

/* ── PROGRESS BAR ───────────────────────────────────────────── */
.tx-progress {
    display:flex;
    align-items:flex-start;
    gap:0;
    margin-bottom:16px;
    position:relative;
}
.tx-progress::before {
    content:'';
    position:absolute;
    top:9px; left:9px; right:9px;
    height:2px;
    background:#e2e8f0;
    z-index:0;
}
.tx-step {
    flex:1;
    text-align:center;
    position:relative;
    z-index:1;
}
.tx-step-dot {
    width:18px; height:18px;
    border-radius:50%;
    border:2px solid #e2e8f0;
    background:#fff;
    margin:0 auto 5px;
    transition:all .2s;
}
.tx-step.done .tx-step-dot { background:#22c55e; border-color:#22c55e; }
.tx-step.active .tx-step-dot { background:#f97316; border-color:#f97316; box-shadow:0 0 0 3px rgba(249,115,22,.2); }
.tx-step-lbl {
    font-size:.65rem;
    font-weight:700;
    color:#94a3b8;
    line-height:1.3;
}
.tx-step.done .tx-step-lbl  { color:#16a34a; }
.tx-step.active .tx-step-lbl { color:#ea580c; }

/* ── CTA PRINCIPAL ──────────────────────────────────────────── */
.tx-cta {
    display:block;
    width:100%;
    border:none;
    border-radius:14px;
    padding:16px;
    font-size:1rem;
    font-weight:800;
    cursor:pointer;
    transition:all .18s;
    text-align:center;
    letter-spacing:.01em;
}
.tx-cta-orange { background:#f97316; color:#fff; }
.tx-cta-orange:hover { background:#ea580c; }
.tx-cta-blue   { background:#3b82f6; color:#fff; }
.tx-cta-blue:hover { background:#2563eb; }
.tx-cta-green  { background:#16a34a; color:#fff; }
.tx-cta-green:hover { background:#15803d; }
.tx-cta-dark   { background:#111827; color:#fff; }
.tx-cta-dark:hover { background:#1e293b; }
.tx-cta:disabled { opacity:.5; cursor:wait; }

/* ── FINANCE STRIP ──────────────────────────────────────────── */
.tx-finance {
    display:grid;
    grid-template-columns:repeat(3,1fr);
    gap:8px;
}
.tx-finance-cell {
    border-radius:14px;
    border:1px solid #e2e8f0;
    padding:12px 14px;
    background:#fff;
}
.tx-finance-cell.positive { border-color:#bbf7d0; background:#f0fdf4; }
.tx-finance-cell.muted    { border-color:#e2e8f0; background:#f8fafc; }
.tx-finance-cell.warning  { border-color:#fde68a; background:#fffbeb; }
.tx-finance-lbl {
    font-size:.68rem;
    font-weight:700;
    text-transform:uppercase;
    letter-spacing:.05em;
    color:#94a3b8;
    margin-bottom:4px;
}
.tx-finance-cell.positive .tx-finance-lbl { color:#166534; }
.tx-finance-cell.warning  .tx-finance-lbl { color:#92400e; }
.tx-finance-val {
    font-size:1.1rem;
    font-weight:900;
    color:#0f172a;
    line-height:1;
}
.tx-finance-cell.positive .tx-finance-val { color:#16a34a; }
.tx-finance-cell.warning  .tx-finance-val { color:#d97706; }

/* ── DEMANDES TABLE ─────────────────────────────────────────── */
.tx-requests { display:flex; flex-direction:column; gap:0; }
.tx-request-row {
    display:flex;
    align-items:center;
    gap:12px;
    padding:14px 18px;
    border-bottom:1px solid #f1f5f9;
    flex-wrap:wrap;
}
.tx-request-row:last-child { border-bottom:none; }
.tx-request-type {
    background:#eff6ff;
    color:#1d4ed8;
    border-radius:8px;
    padding:4px 8px;
    font-size:.7rem;
    font-weight:800;
    text-transform:uppercase;
    white-space:nowrap;
    flex-shrink:0;
}
.tx-request-route {
    flex:1;
    min-width:0;
}
.tx-request-from {
    font-size:.85rem;
    font-weight:700;
    color:#0f172a;
    white-space:nowrap;
    overflow:hidden;
    text-overflow:ellipsis;
}
.tx-request-to {
    font-size:.78rem;
    color:#64748b;
    white-space:nowrap;
    overflow:hidden;
    text-overflow:ellipsis;
    margin-top:1px;
}
.tx-arrow {
    color:#94a3b8;
    flex-shrink:0;
    font-size:.7rem;
}
.tx-request-price {
    font-size:1rem;
    font-weight:900;
    color:#ea580c;
    white-space:nowrap;
    flex-shrink:0;
}
.tx-accept-btn {
    background:#111827;
    color:#fff;
    border:none;
    border-radius:10px;
    padding:9px 16px;
    font-size:.82rem;
    font-weight:800;
    cursor:pointer;
    transition:background .18s;
    flex-shrink:0;
    white-space:nowrap;
}
.tx-accept-btn:hover { background:#16a34a; }

/* ── GPS PILL ───────────────────────────────────────────────── */
.tx-gps-pill {
    display:flex; align-items:center; gap:8px;
    background:#f0fdf4;
    border:1px solid #bbf7d0;
    border-radius:12px;
    padding:10px 14px;
    font-size:.82rem;
    color:#166534;
    font-weight:600;
}
.tx-gps-pill.error {
    background:#fef2f2;
    border-color:#fca5a5;
    color:#991b1b;
}
.tx-gps-dot {
    width:8px; height:8px; border-radius:50%;
    background:#22c55e; flex-shrink:0;
    animation:gpsPulse 1.5s ease-in-out infinite;
}
@keyframes gpsPulse { 0%,100%{opacity:1} 50%{opacity:.4} }

/* ── EMPTY ──────────────────────────────────────────────────── */
.tx-empty {
    text-align:center;
    padding:32px 20px;
    color:#94a3b8;
}
.tx-empty-icon { font-size:2.5rem; margin-bottom:10px; }

/* ── RESPONSIVE ─────────────────────────────────────────────── */
@media (max-width:600px) {
    .tx-info-grid { grid-template-columns:1fr; }
    .tx-finance { grid-template-columns:1fr; }
    .tx-kpi-strip { grid-template-columns:repeat(3,1fr); }
    .tx-request-row { gap:8px; }
}
</style>
@endsection

@section('content')
@php
    $activeRideAmount  = (float)($activeBooking->total_price ?? $activeBooking->estimated_price ?? 0);
    $nNearby           = $nearbyRequests->count();
    $driverInitials    = strtoupper(substr($driver->name ?? 'C', 0, 2));
    $hasActiveBooking  = (bool) $activeBooking;

    // Finance depuis financialDashboard
    $finGross     = 0; $finNet = 0; $finAvailable = 0;
    if (!empty($financialDashboard['rows'])) {
        foreach ($financialDashboard['rows'] as $row) {
            foreach ((array)$row as $card) {
                if (!is_array($card)) continue;
                $lbl = strtolower($card['label'] ?? '');
                if (str_contains($lbl, 'brut'))         $finGross     = $card['amount'] ?? 0;
                if (str_contains($lbl, 'net partenaire')|| str_contains($lbl,'net partner')) $finNet = $card['amount'] ?? 0;
                if (str_contains($lbl, 'disponible'))   $finAvailable = $card['amount'] ?? 0;
            }
        }
    }

    // Progress steps transport
    $txSteps = [
        'assigned'       => 'En route',
        'driver_arriving'=> 'Arrivée',
        'picked_up'      => 'À bord',
        'in_progress'    => 'En course',
    ];
    $txStepKeys = array_keys($txSteps);
    $txCurrentStatus = $activeBooking->status->value ?? null;
    $txCurrentIndex  = $txCurrentStatus ? (int)array_search($txCurrentStatus, $txStepKeys, true) : -1;
    if ($txCurrentIndex === false) $txCurrentIndex = -1;
@endphp

{{-- HEADER --}}
<div class="tx-header">
    <div class="tx-header-brand">
        <div class="tx-header-avatar">{{ $driverInitials }}</div>
        <div>
            <div class="tx-header-name">{{ $driver->name ?? 'Chauffeur' }}</div>
            <div class="tx-header-role">Espace chauffeur</div>
        </div>
    </div>
    <div class="tx-status-pill {{ $hasActiveBooking ? 'active' : '' }}">
        <span class="tx-status-dot"></span>
        {{ $hasActiveBooking ? 'Course active' : 'Disponible' }}
    </div>
</div>

<div class="tx-body">

    {{-- ALERTE FLASH --}}
    @if(session()->has('alert'))
        <div class="alert alert-{{ session('alert.type','info') }} mb-0">
            {{ session('alert.message') }}
        </div>
    @endif

    {{-- KPI STRIP --}}
    <div class="tx-kpi-strip">
        <div class="tx-kpi">
            <div class="tx-kpi-val {{ $hasActiveBooking ? 'orange' : '' }}">{{ $hasActiveBooking ? '1' : '0' }}</div>
            <div class="tx-kpi-lbl">Course active</div>
        </div>
        <div class="tx-kpi">
            <div class="tx-kpi-val {{ $nNearby > 0 ? 'green' : '' }}">{{ $nNearby }}</div>
            <div class="tx-kpi-lbl">Demandes</div>
        </div>
        <div class="tx-kpi">
            <div class="tx-kpi-val green">{{ number_format($finAvailable, 0, ',', ' ') }}</div>
            <div class="tx-kpi-lbl">Dispo (FCFA)</div>
        </div>
    </div>

    {{-- FINANCE STRIP --}}
    <div class="tx-finance">
        <div class="tx-finance-cell muted">
            <div class="tx-finance-lbl">CA brut</div>
            <div class="tx-finance-val">{{ number_format(round($finGross), 0, ',', ' ') }} <span style="font-size:.7rem;font-weight:700;color:#94a3b8;">FCFA</span></div>
        </div>
        <div class="tx-finance-cell positive">
            <div class="tx-finance-lbl">Disponible</div>
            <div class="tx-finance-val">{{ number_format(round($finAvailable), 0, ',', ' ') }} <span style="font-size:.7rem;font-weight:700;color:#16a34a;">FCFA</span></div>
        </div>
        <div class="tx-finance-cell muted">
            <div class="tx-finance-lbl">Net partenaire</div>
            <div class="tx-finance-val">{{ number_format(round($finNet), 0, ',', ' ') }} <span style="font-size:.7rem;font-weight:700;color:#94a3b8;">FCFA</span></div>
        </div>
    </div>

    {{-- COURSE ACTIVE --}}
    @if($hasActiveBooking)
    <div class="tx-card">
        <div class="tx-card-stripe orange"></div>
        <div class="tx-card-head">
            <div>
                <div class="tx-card-title">Course en cours</div>
                <div class="tx-card-sub">Référence {{ $activeBooking->uuid }}</div>
            </div>
            <span id="activeBookingStatusBadge" style="background:#fff7ed;color:#9a3412;border:1px solid #fde68a;border-radius:8px;padding:4px 10px;font-size:.75rem;font-weight:800;white-space:nowrap;">
                {{ $activeBooking->status->label() }}
            </span>
        </div>
        <div class="tx-card-body">
            <div class="tx-ride-amount">{{ number_format(round($activeRideAmount), 0, ',', ' ') }} <span class="tx-ride-unit">FCFA</span></div>
            <div class="tx-ride-meta">Montant estimé de la course</div>

            {{-- Progress --}}
            <div class="tx-progress">
                @foreach($txSteps as $sk => $sl)
                @php
                    $si = (int)array_search($sk, $txStepKeys, true);
                    $sc = $si < $txCurrentIndex ? 'done' : ($sk === $txCurrentStatus ? 'active' : '');
                @endphp
                <div class="tx-step {{ $sc }}">
                    <div class="tx-step-dot"></div>
                    <div class="tx-step-lbl">{{ $sl }}</div>
                </div>
                @endforeach
            </div>

            {{-- Info client + trajet --}}
            <div class="tx-info-grid">
                <div class="tx-info-cell">
                    <div class="tx-info-cell-lbl">Client</div>
                    <div class="tx-info-cell-val">{{ $activeBooking->user->name ?? 'N/A' }}</div>
                    @php $clientPhone = $activeBooking->user->phone ?? null; @endphp
                    @if($clientPhone)
                        <div class="tx-info-cell-sub">{{ $clientPhone }}</div>
                        <a class="tx-call-btn" href="tel:{{ $clientPhone }}">📞 Appeler</a>
                    @endif
                </div>
                <div class="tx-info-cell">
                    <div class="tx-info-cell-lbl">Départ</div>
                    <div class="tx-info-cell-val">{{ $activeBooking->pickup_address ?? 'N/A' }}</div>
                </div>
                <div class="tx-info-cell" style="grid-column:1/-1">
                    <div class="tx-info-cell-lbl">Destination</div>
                    <div class="tx-info-cell-val">{{ $activeBooking->dropoff_address ?? 'N/A' }}</div>
                </div>
            </div>

            {{-- GPS --}}
            <div class="tx-gps-pill mb-3" id="txGpsPill">
                <span class="tx-gps-dot" id="txGpsDot"></span>
                <span id="txGpsLabel">Initialisation GPS…</span>
            </div>

            {{-- CTA --}}
            <div id="activeBookingActions">
                @if($activeBooking->status->value === 'assigned')
                    <button class="tx-cta tx-cta-orange" onclick="updateStatus('driver_arriving')">🚗 Je suis en route</button>
                @elseif($activeBooking->status->value === 'driver_arriving')
                    <button class="tx-cta tx-cta-blue" onclick="updateStatus('picked_up')">👤 Client pris en charge</button>
                @elseif($activeBooking->status->value === 'picked_up')
                    <button class="tx-cta tx-cta-blue" onclick="updateStatus('in_progress')">▶ Démarrer la course</button>
                @elseif($activeBooking->status->value === 'in_progress')
                    <button class="tx-cta tx-cta-green" onclick="updateStatus('completed')">✓ Terminer la course</button>
                @else
                    <div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:12px;padding:12px;text-align:center;color:#15803d;font-weight:700;">
                        ✓ Course synchronisée
                    </div>
                @endif
            </div>
        </div>
    </div>
    @else
    <div class="tx-card">
        <div class="tx-card-stripe gray"></div>
        <div class="tx-card-body">
            <div class="tx-empty">
                <div class="tx-empty-icon">🚗</div>
                <div style="font-weight:700;color:#0f172a;font-size:.95rem;">Aucune course active</div>
                <div style="font-size:.82rem;color:#94a3b8;margin-top:4px;">Acceptez une demande ci-dessous pour démarrer.</div>
            </div>
        </div>
    </div>
    @endif

    {{-- DEMANDES À PROXIMITÉ --}}
    <div class="tx-card">
        <div class="tx-card-stripe blue"></div>
        <div class="tx-card-head">
            <div>
                <div class="tx-card-title">Demandes à proximité</div>
                <div class="tx-card-sub">{{ $nNearby }} demande{{ $nNearby !== 1 ? 's' : '' }} en attente d'acceptation</div>
            </div>
        </div>
        <div class="tx-requests" style="margin-top:12px;">
            @forelse($nearbyRequests as $request)
            @php
                $fromAddr = $request->pickup_address ?? 'Départ inconnu';
                $toAddr   = $request->dropoff_address ?? 'Destination inconnue';
                $fromShort = strlen($fromAddr) > 40 ? substr($fromAddr,0,38).'…' : $fromAddr;
                $toShort   = strlen($toAddr)   > 40 ? substr($toAddr,  0,38).'…' : $toAddr;
            @endphp
            <div class="tx-request-row">
                <span class="tx-request-type">{{ $request->type->label() }}</span>
                <div class="tx-request-route" style="flex:1;min-width:0;">
                    <div class="tx-request-from">{{ $fromShort }}</div>
                    <div class="tx-request-to">→ {{ $toShort }}</div>
                </div>
                <div class="tx-request-price">{{ number_format(round($request->estimated_price), 0, ',', ' ') }}<br><span style="font-size:.65rem;font-weight:700;color:#94a3b8;">FCFA</span></div>
                <button class="tx-accept-btn" onclick="acceptBooking('{{ $request->uuid }}')">Accepter</button>
            </div>
            @empty
            <div class="tx-empty">
                <div class="tx-empty-icon">📡</div>
                <div style="font-weight:700;color:#0f172a;">Aucune demande en attente</div>
                <div style="font-size:.82rem;color:#94a3b8;margin-top:4px;">Les nouvelles demandes apparaîtront ici.</div>
            </div>
            @endforelse
        </div>
    </div>

    {{-- NAV ENTRE LES ESPACES --}}
    <div style="display:flex;gap:10px;flex-wrap:wrap;">
        <a href="{{ route('driver.deliveries') }}" style="flex:1;text-align:center;background:#fff;border:1px solid #e2e8f0;border-radius:14px;padding:14px;font-size:.85rem;font-weight:700;color:#334155;text-decoration:none;">
            🛵 Mes livraisons food
        </a>
    </div>

</div>
@endsection

@section('script')
<script>
const activeBookingUuid  = @json($activeBooking->uuid ?? null);
const initialBookingStatus = @json($activeBooking->status->value ?? null);

const statusLabels = {
    assigned:'En route vers client', driver_arriving:'Arrivée chauffeur',
    picked_up:'Client à bord', in_progress:'Course en cours',
    completed:'Terminée', paid:'Payée'
};

function acceptBooking(uuid) {
    if (!confirm('Accepter cette course ?')) return;
    fetch(`{{ url('transport/xhr/driver/bookings') }}/${uuid}/accept`, {
        method:'POST',
        headers:{
            'Content-Type':'application/json',
            'X-CSRF-TOKEN':'{{ csrf_token() }}',
            @if(auth()->user()->api_token)
            'Authorization':'Bearer {{ auth()->user()->api_token }}'
            @endif
        }
    })
    .then(async r => {
        const d = await r.json().catch(() => ({}));
        if (!r.ok) { alert(d.error || 'Erreur lors de l\'acceptation'); return; }
        window.location.reload();
    })
    .catch(() => alert('Erreur réseau'));
}

function updateStatus(status) {
    if (!confirm('Confirmer le changement de statut ?')) return;
    const btn = document.querySelector('#activeBookingActions button');
    if (btn) { btn.disabled = true; btn.textContent = '⏳ En cours…'; }
    fetch(`{{ url('transport/xhr/driver/bookings') }}/{{ $activeBooking->uuid ?? '' }}/status`, {
        method:'POST',
        headers:{
            'Content-Type':'application/json',
            'X-CSRF-TOKEN':'{{ csrf_token() }}',
            'Authorization':'Bearer {{ auth()->user()->api_token }}'
        },
        body: JSON.stringify({ status })
    })
    .then(async r => {
        const d = await r.json().catch(() => ({}));
        if (!r.ok) throw new Error(d.error || 'Erreur');
        applyBookingStatus(status);
    })
    .catch(e => {
        if (btn) { btn.disabled = false; btn.textContent = 'Réessayer'; }
        alert(e.message || 'Erreur technique');
    });
}

function applyBookingStatus(status) {
    const badge   = document.getElementById('activeBookingStatusBadge');
    const actions = document.getElementById('activeBookingActions');
    if (badge) badge.textContent = statusLabels[status] || status;
    if (!actions) return;

    const map = {
        assigned:       '<button class="tx-cta tx-cta-orange" onclick="updateStatus(\'driver_arriving\')">🚗 Je suis en route</button>',
        driver_arriving:'<button class="tx-cta tx-cta-blue"   onclick="updateStatus(\'picked_up\')">👤 Client pris en charge</button>',
        picked_up:      '<button class="tx-cta tx-cta-blue"   onclick="updateStatus(\'in_progress\')">▶ Démarrer la course</button>',
        in_progress:    '<button class="tx-cta tx-cta-green"  onclick="updateStatus(\'completed\')">✓ Terminer la course</button>',
    };
    actions.innerHTML = map[status] || '<div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:12px;padding:12px;text-align:center;color:#15803d;font-weight:700;">✓ Course synchronisée</div>';
}

// ── GPS tracking ───────────────────────────────────────────────
let driverLocationTimer = null;

function setGpsState(label, ok) {
    const pill  = document.getElementById('txGpsPill');
    const dot   = document.getElementById('txGpsDot');
    const lbl   = document.getElementById('txGpsLabel');
    if (lbl) lbl.textContent = label;
    if (pill) pill.className = 'tx-gps-pill mb-3' + (ok ? '' : ' error');
    if (dot)  dot.style.background = ok ? '#22c55e' : '#ef4444';
}

function sendDriverLocation() {
    if (!activeBookingUuid || !('geolocation' in navigator)) return;
    navigator.geolocation.getCurrentPosition(pos => {
        fetch(`{{ url('transport/xhr/driver/bookings') }}/${activeBookingUuid}/location`, {
            method:'POST',
            headers:{
                'Content-Type':'application/json',
                'X-CSRF-TOKEN':'{{ csrf_token() }}',
                @if(auth()->user()->api_token)
                'Authorization':'Bearer {{ auth()->user()->api_token }}'
                @endif
            },
            body: JSON.stringify({ lat: pos.coords.latitude, lng: pos.coords.longitude, speed: pos.coords.speed })
        })
        .then(async r => {
            const d = await r.json().catch(() => ({}));
            if (r.ok) setGpsState('Position envoyée à ' + new Date().toLocaleTimeString('fr-FR',{hour:'2-digit',minute:'2-digit'}), true);
            else setGpsState('Erreur GPS — réessai', false);
        })
        .catch(() => setGpsState('Connexion GPS perdue', false));
    }, () => setGpsState('Géolocalisation refusée — activez-la', false), {
        enableHighAccuracy: true, timeout: 12000, maximumAge: 0
    });
}

if (activeBookingUuid) {
    applyBookingStatus(initialBookingStatus);
    sendDriverLocation();
    driverLocationTimer = setInterval(sendDriverLocation, 8000);
    document.addEventListener('visibilitychange', () => {
        if (document.visibilityState === 'visible') sendDriverLocation();
    });
}
</script>
@endsection
