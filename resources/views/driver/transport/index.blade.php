@extends('layouts.driver-modern')
@section('title', 'Courses transport | ' . \App\Services\ConfigService::getCompanyName())

@php
    $driverInitials    = strtoupper(substr($driver->name ?? 'C', 0, 2));
    $hasActiveBooking  = (bool) $activeBooking;
    $activeRideAmount  = (float)($activeBooking->total_price ?? $activeBooking->estimated_price ?? 0);
    $nNearby           = $nearbyRequests->count();

    $finGross = 0; $finNet = 0; $finAvailable = 0;
    if (!empty($financialDashboard['rows'])) {
        foreach ($financialDashboard['rows'] as $row) {
            foreach ((array)$row as $card) {
                if (!is_array($card)) continue;
                $lbl = strtolower($card['label'] ?? '');
                if (str_contains($lbl,'brut'))           $finGross     = $card['amount'] ?? 0;
                if (str_contains($lbl,'net partenaire')) $finNet       = $card['amount'] ?? 0;
                if (str_contains($lbl,'disponible'))     $finAvailable = $card['amount'] ?? 0;
            }
        }
    }

    $txSteps = ['assigned'=>'En route','driver_arriving'=>'Arrivée','picked_up'=>'À bord','in_progress'=>'En course'];
    $txStepKeys = array_keys($txSteps);
    $txCurrentStatus = $activeBooking->status->value ?? null;
    $txCurrentIndex  = $txCurrentStatus ? (int)array_search($txCurrentStatus, $txStepKeys, true) : -1;
    if ($txCurrentIndex === false) $txCurrentIndex = -1;
@endphp

@section('nav_transport', 'is-active')
@section('driver_initials', $driverInitials)
@section('driver_name', $driver->name ?? 'Chauffeur')
@section('driver_phone', $driver->phone ?? '')
@section('online_pill_class', $hasActiveBooking ? '' : 'offline')
@section('online_pill_label', $hasActiveBooking ? 'Course active' : 'Disponible')
@section('page_title', 'Courses transport')

@section('topbar_right')
    @if($hasActiveBooking)
    <div style="display:inline-flex;align-items:center;gap:5px;background:rgba(255,90,31,.1);border:1px solid rgba(255,90,31,.3);border-radius:99px;padding:4px 10px;font-size:.72rem;font-weight:700;color:var(--c-primary);">
        <span style="width:6px;height:6px;border-radius:50%;background:var(--c-primary);animation:gpsBlink 1.5s ease-in-out infinite;"></span>
        Course active
    </div>
    @endif
@endsection

@section('style')
<style>
@keyframes gpsBlink { 0%,100%{opacity:1} 50%{opacity:.3} }
@keyframes pulseGreen { 0%{box-shadow:0 0 0 0 rgba(34,197,94,.5)} 70%{box-shadow:0 0 0 8px rgba(34,197,94,0)} 100%{box-shadow:0 0 0 0 rgba(34,197,94,0)} }

/* Summary bar */
.tx-summary {
    background: var(--c-dark);
    display: grid; grid-template-columns: 2fr repeat(3, 1fr);
    overflow: hidden;
}
.tx-summary-main { padding: 18px 22px; border-right: 1px solid rgba(255,255,255,.06); }
.tx-summary-greeting { font-size: .7rem; font-weight: 700; color: rgba(255,255,255,.4); text-transform: uppercase; letter-spacing: .08em; }
.tx-summary-name { font-size: 1.1rem; font-weight: 900; color: #fff; letter-spacing: -.02em; line-height: 1.1; margin-top: 2px; }
.tx-summary-pill {
    display: inline-flex; align-items: center; gap: 5px;
    margin-top: 8px; padding: 4px 10px; border-radius: 99px;
    border: 1px solid rgba(255,255,255,.12);
    background: rgba(255,255,255,.06);
    font-size: .7rem; font-weight: 700; color: rgba(255,255,255,.5);
}
.tx-summary-pill.active { background: rgba(34,197,94,.15); border-color: rgba(34,197,94,.4); color: #86efac; }
.tx-pill-dot { width: 6px; height: 6px; border-radius: 50%; background: currentColor; }
.tx-summary-pill.active .tx-pill-dot { animation: pulseGreen 2s infinite; }

.tx-kpi { padding: 18px 14px; text-align: center; border-right: 1px solid rgba(255,255,255,.06); }
.tx-kpi:last-child { border-right: none; }
.tx-kpi-val { font-size: 1.2rem; font-weight: 900; color: #fff; line-height: 1; }
.tx-kpi-val.active  { color: var(--c-primary); }
.tx-kpi-val.green   { color: #86efac; }
.tx-kpi-lbl { font-size: .62rem; font-weight: 700; color: rgba(255,255,255,.35); text-transform: uppercase; letter-spacing: .06em; margin-top: 4px; }

/* Body */
.tx-body { padding: 20px 24px 48px; display: flex; flex-direction: column; gap: 18px; }

.tx-sec { display: flex; align-items: center; justify-content: space-between; margin-bottom: 10px; }
.tx-sec-title {
    font-size: .75rem; font-weight: 800; text-transform: uppercase;
    letter-spacing: .1em; color: var(--c-text-muted);
    display: flex; align-items: center; gap: 6px;
}

/* Card */
.tx-card { background: var(--c-surface); border: 1px solid var(--c-border); border-radius: 14px; overflow: hidden; }
.tx-card-accent { height: 3px; }
.tx-card-accent.orange { background: var(--c-primary); }
.tx-card-accent.blue   { background: var(--c-info); }
.tx-card-accent.gray   { background: var(--c-border); }
.tx-card-accent.green  { background: var(--c-green-lt); }

.tx-card-head { padding: 14px 16px 0; display: flex; align-items: flex-start; justify-content: space-between; gap: 10px; }
.tx-card-title { font-size: .92rem; font-weight: 900; color: var(--c-text); }
.tx-card-sub   { font-size: .72rem; color: var(--c-text-muted); margin-top: 2px; }
.tx-card-body  { padding: 14px 16px 16px; }

/* Montant */
.tx-amount { font-size: 2.2rem; font-weight: 900; color: var(--c-text); line-height: 1; }
.tx-amount-unit { font-size: .95rem; font-weight: 700; color: var(--c-text-muted); }
.tx-amount-meta { font-size: .78rem; color: var(--c-text-muted); margin-top: 4px; margin-bottom: 16px; }

/* Progress steps */
.tx-steps { display: flex; align-items: flex-start; position: relative; margin-bottom: 16px; }
.tx-steps::before { content:''; position:absolute; top:8px; left:8px; right:8px; height:2px; background:var(--c-border); z-index:0; }
.tx-step { flex: 1; text-align: center; position: relative; z-index: 1; }
.tx-step-dot { width: 17px; height: 17px; border-radius: 50%; border: 2px solid var(--c-border); background: var(--c-surface); margin: 0 auto 5px; transition: all .25s; }
.tx-step.done   .tx-step-dot { background: var(--c-green-lt);   border-color: var(--c-green-lt); }
.tx-step.active .tx-step-dot { background: var(--c-primary);    border-color: var(--c-primary); box-shadow: 0 0 0 4px rgba(255,90,31,.2); }
.tx-step-lbl { font-size: .6rem; font-weight: 700; color: var(--c-text-dim); }
.tx-step.done   .tx-step-lbl { color: var(--c-green-lt); }
.tx-step.active .tx-step-lbl { color: var(--c-primary); }

/* Info grid */
.tx-info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-bottom: 14px; }
.tx-info-cell { background: var(--c-bg); border: 1px solid var(--c-border); border-radius: 10px; padding: 10px 12px; }
.tx-info-lbl  { font-size: .65rem; font-weight: 800; text-transform: uppercase; letter-spacing: .05em; color: var(--c-text-dim); margin-bottom: 3px; }
.tx-info-val  { font-size: .88rem; font-weight: 700; color: var(--c-text); }
.tx-info-sub  { font-size: .74rem; color: var(--c-text-muted); margin-top: 2px; }

/* Call button */
.tx-call-btn {
    display: inline-flex; align-items: center; gap: 5px;
    margin-top: 5px; padding: 4px 10px;
    background: rgba(0,149,67,.1); color: var(--c-green);
    border: 1px solid rgba(0,149,67,.3); border-radius: 7px;
    font-size: .7rem; font-weight: 700; text-decoration: none;
    transition: background .15s;
}
.tx-call-btn:hover { background: rgba(0,149,67,.18); }

/* GPS */
.tx-gps {
    display: flex; align-items: center; gap: 8px;
    background: rgba(0,149,67,.08); border: 1px solid rgba(0,149,67,.2);
    border-radius: 10px; padding: 9px 12px;
    font-size: .8rem; color: var(--c-green); font-weight: 600; margin-bottom: 14px;
}
.tx-gps.error { background: rgba(239,68,68,.07); border-color: rgba(239,68,68,.2); color: var(--c-err); }
.tx-gps-dot { width: 7px; height: 7px; border-radius: 50%; background: var(--c-green-lt); flex-shrink: 0; animation: gpsBlink 1.5s ease-in-out infinite; }
.tx-gps.error .tx-gps-dot { background: var(--c-err); animation: none; }

/* Action buttons */
.tx-btn {
    display: inline-flex; align-items: center; gap: 7px;
    padding: 9px 18px; border-radius: 9px; font-size: .85rem; font-weight: 700;
    border: none; cursor: pointer; font-family: var(--font-body); transition: opacity .15s, transform .1s;
}
.tx-btn:active { transform: scale(.97); }
.tx-btn:disabled { opacity: .5; cursor: wait; }
.tx-btn-orange { background: var(--c-primary); color: #fff; }
.tx-btn-blue   { background: var(--c-info);    color: #fff; }
.tx-btn-green  { background: var(--c-green-lt); color: #fff; }
.tx-btn-done   { background: rgba(34,197,94,.12); color: #166534; border: 1px solid rgba(34,197,94,.3); font-size: .82rem; padding: 10px 14px; width: 100%; justify-content: center; }

/* Request rows */
.tx-request-row {
    display: flex; align-items: center; gap: 11px;
    padding: 13px 16px; border-bottom: 1px solid var(--c-bg); flex-wrap: wrap;
    transition: background .12s;
}
.tx-request-row:last-child { border-bottom: none; }
.tx-request-row:hover { background: var(--c-bg); }
.tx-request-type { background: rgba(59,130,246,.1); color: #1d4ed8; border: 1px solid rgba(59,130,246,.25); border-radius: 7px; padding: 3px 9px; font-size: .68rem; font-weight: 800; text-transform: uppercase; white-space: nowrap; flex-shrink: 0; }
.tx-request-route { flex: 1; min-width: 0; }
.tx-request-from { font-size: .86rem; font-weight: 700; color: var(--c-text); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.tx-request-to   { font-size: .76rem; color: var(--c-text-muted); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; margin-top: 1px; }
.tx-request-price { font-size: 1rem; font-weight: 900; color: var(--c-primary); white-space: nowrap; flex-shrink: 0; text-align: right; }
.tx-request-price small { font-size: .62rem; font-weight: 700; color: var(--c-text-dim); display: block; }
.tx-accept-btn {
    background: var(--c-dark); color: #fff; border: none; border-radius: 8px;
    padding: 8px 14px; font-size: .78rem; font-weight: 700;
    cursor: pointer; transition: background .15s; flex-shrink: 0; font-family: var(--font-body);
}
.tx-accept-btn:hover { background: var(--c-green); }

/* Finance */
.tx-finance { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; }
.tx-fin-cell { border-radius: 12px; border: 1px solid var(--c-border); padding: 12px 14px; background: var(--c-surface); }
.tx-fin-cell.hi { border-color: rgba(34,197,94,.3); background: rgba(34,197,94,.06); }
.tx-fin-lbl { font-size: .65rem; font-weight: 700; text-transform: uppercase; letter-spacing: .05em; color: var(--c-text-dim); margin-bottom: 4px; }
.tx-fin-cell.hi .tx-fin-lbl { color: #166534; }
.tx-fin-val { font-size: 1rem; font-weight: 900; color: var(--c-text); line-height: 1; }
.tx-fin-cell.hi .tx-fin-val { color: #166534; }

/* Empty */
.tx-empty { text-align: center; padding: 30px 20px; }
.tx-empty-icon { font-size: 1.7rem; color: var(--c-text-dim); margin-bottom: 8px; }

@media (max-width: 768px) {
    .tx-summary { grid-template-columns: 1fr 1fr; }
    .tx-body { padding: 14px 14px 40px; }
    .tx-info-grid { grid-template-columns: 1fr; }
    .tx-finance { grid-template-columns: 1fr; }
}
</style>
@endsection

@section('content')

{{-- ═══ SUMMARY BAR ═══ --}}
<div class="tx-summary">
    <div class="tx-summary-main">
        <div class="tx-summary-greeting">Espace chauffeur</div>
        <div class="tx-summary-name">{{ $driver->name ?? 'Chauffeur' }}</div>
        <div class="tx-summary-pill {{ $hasActiveBooking ? 'active' : '' }}">
            <span class="tx-pill-dot"></span>
            {{ $hasActiveBooking ? 'Course active' : 'Disponible' }}
        </div>
    </div>
    <div class="tx-kpi">
        <div class="tx-kpi-val {{ $hasActiveBooking ? 'active' : '' }}">{{ $hasActiveBooking ? '1' : '0' }}</div>
        <div class="tx-kpi-lbl">Course active</div>
    </div>
    <div class="tx-kpi">
        <div class="tx-kpi-val {{ $nNearby > 0 ? 'green' : '' }}">{{ $nNearby }}</div>
        <div class="tx-kpi-lbl">Demandes</div>
    </div>
    <div class="tx-kpi">
        <div class="tx-kpi-val green">{{ number_format(round($finAvailable),0,',',' ') }}</div>
        <div class="tx-kpi-lbl">FCFA dispo</div>
    </div>
</div>

{{-- ═══ BODY ═══ --}}
<div class="tx-body">

    @if(session()->has('alert'))
    <div style="padding:11px 14px;border-radius:10px;background:rgba(245,158,11,.1);border:1px solid rgba(245,158,11,.3);font-size:.82rem;color:#92400e;font-weight:600;">
        <i class="fas fa-circle-exclamation" style="margin-right:6px;"></i>{{ session('alert.message') }}
    </div>
    @endif

    {{-- ── 1. COURSE ACTIVE ── --}}
    <div>
        <div class="tx-sec">
            <div class="tx-sec-title"><i class="fas fa-route" style="color:var(--c-primary);"></i> Course en cours</div>
        </div>

        @if($hasActiveBooking)
        <div class="tx-card">
            <div class="tx-card-accent orange"></div>
            <div class="tx-card-head">
                <div>
                    <div class="tx-card-title">Course active</div>
                    <div class="tx-card-sub">Réf. {{ $activeBooking->uuid }}</div>
                </div>
                <span id="activeBookingStatusBadge" style="background:rgba(255,90,31,.1);color:var(--c-primary);border:1px solid rgba(255,90,31,.25);border-radius:7px;padding:3px 10px;font-size:.74rem;font-weight:800;white-space:nowrap;">
                    {{ $activeBooking->status->label() }}
                </span>
            </div>
            <div class="tx-card-body">
                <div class="tx-amount">{{ number_format(round($activeRideAmount),0,',',' ') }} <span class="tx-amount-unit">FCFA</span></div>
                <div class="tx-amount-meta">Montant estimé</div>

                <div class="tx-steps">
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

                <div class="tx-info-grid">
                    <div class="tx-info-cell">
                        <div class="tx-info-lbl">Client</div>
                        <div class="tx-info-val">{{ $activeBooking->user->name ?? 'N/A' }}</div>
                        @php $clientPhone = $activeBooking->user->phone ?? null; @endphp
                        @if($clientPhone)
                        <div class="tx-info-sub">{{ $clientPhone }}</div>
                        <a class="tx-call-btn" href="tel:{{ $clientPhone }}"><i class="fas fa-phone"></i> Appeler</a>
                        @endif
                    </div>
                    <div class="tx-info-cell">
                        <div class="tx-info-lbl">Départ</div>
                        <div class="tx-info-val">{{ $activeBooking->pickup_address ?? 'N/A' }}</div>
                    </div>
                    <div class="tx-info-cell" style="grid-column:1/-1">
                        <div class="tx-info-lbl">Destination</div>
                        <div class="tx-info-val">{{ $activeBooking->dropoff_address ?? 'N/A' }}</div>
                    </div>
                </div>

                <div class="tx-gps" id="txGpsPill">
                    <span class="tx-gps-dot" id="txGpsDot"></span>
                    <span id="txGpsLabel">Initialisation GPS&hellip;</span>
                </div>

                <div id="activeBookingActions" style="display:flex;gap:8px;flex-wrap:wrap;">
                    @if($activeBooking->status->value === 'assigned')
                        <button class="tx-btn tx-btn-orange" onclick="txUpdateStatus('driver_arriving')"><i class="fas fa-car"></i> Je suis en route</button>
                    @elseif($activeBooking->status->value === 'driver_arriving')
                        <button class="tx-btn tx-btn-blue" onclick="txUpdateStatus('picked_up')"><i class="fas fa-user-check"></i> Client pris en charge</button>
                    @elseif($activeBooking->status->value === 'picked_up')
                        <button class="tx-btn tx-btn-blue" onclick="txUpdateStatus('in_progress')"><i class="fas fa-play"></i> Démarrer la course</button>
                    @elseif($activeBooking->status->value === 'in_progress')
                        <button class="tx-btn tx-btn-green" onclick="txUpdateStatus('completed')"><i class="fas fa-flag-checkered"></i> Terminer la course</button>
                    @else
                        <div class="tx-btn tx-btn-done"><i class="fas fa-circle-check"></i> Course terminée &mdash; synchronisée</div>
                    @endif
                </div>
            </div>
        </div>

        @else
        <div class="tx-card">
            <div class="tx-card-accent gray"></div>
            <div class="tx-card-body">
                <div class="tx-empty">
                    <div class="tx-empty-icon"><i class="fas fa-car"></i></div>
                    <div style="font-weight:800;color:var(--c-text);font-size:.9rem;margin-bottom:4px;">Aucune course active</div>
                    <div style="font-size:.8rem;color:var(--c-text-muted);">Acceptez une demande ci-dessous pour démarrer.</div>
                </div>
            </div>
        </div>
        @endif
    </div>

    {{-- ── 2. DEMANDES À PROXIMITÉ ── --}}
    <div>
        <div class="tx-sec">
            <div class="tx-sec-title">
                <i class="fas fa-satellite-dish" style="color:var(--c-info);"></i>
                Demandes à proximité
                @if($nNearby > 0)
                <span style="background:var(--c-info);color:#fff;border-radius:99px;padding:1px 7px;font-size:.6rem;font-weight:900;">{{ $nNearby }}</span>
                @endif
            </div>
        </div>
        <div class="tx-card">
            <div class="tx-card-accent blue"></div>
            @forelse($nearbyRequests as $request)
            @php
                $fromAddr  = $request->pickup_address ?? 'Départ inconnu';
                $toAddr    = $request->dropoff_address ?? 'Destination inconnue';
                $fromShort = mb_strlen($fromAddr)>44 ? mb_substr($fromAddr,0,42).'…' : $fromAddr;
                $toShort   = mb_strlen($toAddr)>44   ? mb_substr($toAddr,0,42).'…'   : $toAddr;
            @endphp
            <div class="tx-request-row">
                <span class="tx-request-type">{{ $request->type->label() }}</span>
                <div class="tx-request-route">
                    <div class="tx-request-from">{{ $fromShort }}</div>
                    <div class="tx-request-to"><i class="fas fa-arrow-right" style="font-size:.65rem;"></i> {{ $toShort }}</div>
                </div>
                <div class="tx-request-price">
                    {{ number_format(round($request->estimated_price),0,',',' ') }}
                    <small>FCFA</small>
                </div>
                <button class="tx-accept-btn" onclick="txAcceptBooking('{{ $request->uuid }}')">
                    <i class="fas fa-check"></i> Accepter
                </button>
            </div>
            @empty
            <div class="tx-empty">
                <div class="tx-empty-icon"><i class="fas fa-satellite-dish"></i></div>
                <div style="font-weight:700;color:var(--c-text);margin-bottom:4px;">Aucune demande en attente</div>
                <div style="font-size:.8rem;color:var(--c-text-muted);">Les nouvelles demandes apparaîtront ici en temps réel.</div>
            </div>
            @endforelse
        </div>
    </div>

    {{-- ── 3. GAINS RAPIDES ── --}}
    <div>
        <div class="tx-sec">
            <div class="tx-sec-title"><i class="fas fa-coins" style="color:var(--c-primary);"></i> Mes gains</div>
            @if(app('router')->has('driver.gains'))
            <a href="{{ route('driver.gains') }}" style="font-size:.75rem;font-weight:700;color:var(--c-primary);text-decoration:none;">
                Voir le détail <i class="fas fa-arrow-right" style="font-size:.65rem;"></i>
            </a>
            @endif
        </div>
        <div class="tx-finance">
            <div class="tx-fin-cell">
                <div class="tx-fin-lbl">CA Brut</div>
                <div class="tx-fin-val">{{ number_format(round($finGross),0,',',' ') }} <span style="font-size:.68rem;color:var(--c-text-dim);">FCFA</span></div>
            </div>
            <div class="tx-fin-cell hi">
                <div class="tx-fin-lbl">Disponible</div>
                <div class="tx-fin-val">{{ number_format(round($finAvailable),0,',',' ') }} <span style="font-size:.68rem;">FCFA</span></div>
            </div>
            <div class="tx-fin-cell">
                <div class="tx-fin-lbl">Net partenaire</div>
                <div class="tx-fin-val">{{ number_format(round($finNet),0,',',' ') }} <span style="font-size:.68rem;color:var(--c-text-dim);">FCFA</span></div>
            </div>
        </div>
    </div>

    {{-- Lien espace food --}}
    <a href="{{ route('driver.deliveries') }}"
       style="display:inline-flex;align-items:center;gap:8px;background:var(--c-surface);border:1px solid var(--c-border);border-radius:10px;padding:11px 16px;font-size:.82rem;font-weight:700;color:var(--c-text-2);text-decoration:none;transition:border-color .15s;width:fit-content;">
        <i class="fas fa-motorcycle" style="color:var(--c-primary);"></i> Aller vers mes livraisons food
    </a>

</div>
@endsection

@section('script')
<script>
const activeBookingUuid    = @json($activeBooking->uuid ?? null);
const initialBookingStatus = @json($activeBooking->status->value ?? null);

const txStatusLabels = {
    assigned:'En route vers client', driver_arriving:'Arrivée chauffeur',
    picked_up:'Client à bord', in_progress:'Course en cours',
    completed:'Terminée', paid:'Payée'
};
const txStatusMap = {
    assigned:       '<button class="tx-btn tx-btn-orange" onclick="txUpdateStatus(\'driver_arriving\')"><i class="fas fa-car"></i> Je suis en route</button>',
    driver_arriving:'<button class="tx-btn tx-btn-blue"   onclick="txUpdateStatus(\'picked_up\')"><i class="fas fa-user-check"></i> Client pris en charge</button>',
    picked_up:      '<button class="tx-btn tx-btn-blue"   onclick="txUpdateStatus(\'in_progress\')"><i class="fas fa-play"></i> Démarrer la course</button>',
    in_progress:    '<button class="tx-btn tx-btn-green"  onclick="txUpdateStatus(\'completed\')"><i class="fas fa-flag-checkered"></i> Terminer la course</button>',
};

function txAcceptBooking(uuid) {
    if (!confirm('Accepter cette course ?')) return;
    fetch(`{{ url('transport/xhr/driver/bookings') }}/${uuid}/accept`, {
        method: 'POST',
        headers: { 'Content-Type':'application/json', 'X-CSRF-TOKEN':'{{ csrf_token() }}'
            @if(auth()->user()->api_token), 'Authorization':'Bearer {{ auth()->user()->api_token }}'@endif },
    })
    .then(async r => {
        const d = await r.json().catch(() => ({}));
        if (!r.ok) { alert(d.error || 'Erreur'); return; }
        window.location.reload();
    })
    .catch(() => alert('Erreur réseau'));
}

function txUpdateStatus(status) {
    if (!confirm('Confirmer le changement ?')) return;
    const btn = document.querySelector('#activeBookingActions button');
    if (btn) { btn.disabled = true; btn.innerHTML = '<i class="fas fa-circle-notch fa-spin"></i> En cours&hellip;'; }
    fetch(`{{ url('transport/xhr/driver/bookings') }}/{{ $activeBooking->uuid ?? '' }}/status`, {
        method: 'POST',
        headers: { 'Content-Type':'application/json', 'X-CSRF-TOKEN':'{{ csrf_token() }}', 'Authorization':'Bearer {{ auth()->user()->api_token }}' },
        body: JSON.stringify({ status })
    })
    .then(async r => {
        const d = await r.json().catch(() => ({}));
        if (!r.ok) throw new Error(d.error || 'Erreur');
        txApplyStatus(status);
    })
    .catch(e => {
        if (btn) { btn.disabled = false; btn.textContent = 'Réessayer'; }
        alert(e.message || 'Erreur technique');
    });
}

function txApplyStatus(status) {
    const badge   = document.getElementById('activeBookingStatusBadge');
    const actions = document.getElementById('activeBookingActions');
    if (badge) badge.textContent = txStatusLabels[status] || status;
    if (actions) actions.innerHTML = txStatusMap[status] ||
        '<div class="tx-btn tx-btn-done"><i class="fas fa-circle-check"></i> Course synchronisée</div>';
}

// GPS
let txLocationInFlight = false;
let txLocationTimer = null;
function txSetGps(label, ok) {
    var pill = document.getElementById('txGpsPill');
    var dot  = document.getElementById('txGpsDot');
    var lbl  = document.getElementById('txGpsLabel');
    if (lbl) lbl.textContent = label;
    if (pill) pill.className = 'tx-gps' + (ok ? '' : ' error');
    if (dot) dot.style.background = ok ? 'var(--c-green-lt)' : 'var(--c-err)';
}
function txScheduleLocation(delay) {
    clearTimeout(txLocationTimer);
    txLocationTimer = setTimeout(txSendLocation, delay);
}
function txSendLocation() {
    if (!activeBookingUuid || !('geolocation' in navigator) || txLocationInFlight) return;
    if (document.visibilityState && document.visibilityState !== 'visible') return;
    txLocationInFlight = true;
    navigator.geolocation.getCurrentPosition(pos => {
        fetch(`{{ url('transport/xhr/driver/bookings') }}/${activeBookingUuid}/location`, {
            method: 'POST',
            headers: { 'Content-Type':'application/json', 'X-CSRF-TOKEN':'{{ csrf_token() }}'
                @if(auth()->user()->api_token), 'Authorization':'Bearer {{ auth()->user()->api_token }}'@endif },
            body: JSON.stringify({
                lat: pos.coords.latitude,
                lng: pos.coords.longitude,
                accuracy: pos.coords.accuracy || null,
                heading: pos.coords.heading || null,
                speed: pos.coords.speed || null,
                recorded_at: new Date(pos.timestamp || Date.now()).toISOString()
            })
        })
        .then(async r => {
            if (r.ok) txSetGps('Position envoyée · ' + new Date().toLocaleTimeString('fr-FR',{hour:'2-digit',minute:'2-digit'}), true);
            else txSetGps('Erreur GPS · réessai', false);
        })
        .catch(() => txSetGps('Connexion perdue', false))
        .finally(() => {
            txLocationInFlight = false;
            txScheduleLocation(8000);
        });
    }, () => {
        txLocationInFlight = false;
        txSetGps('Géolocalisation refusée — activez-la', false);
        txScheduleLocation(15000);
    }, { enableHighAccuracy:true, timeout:12000, maximumAge:5000 });
}

if (activeBookingUuid) {
    txApplyStatus(initialBookingStatus);
    txSendLocation();
    document.addEventListener('visibilitychange', () => {
        if (document.visibilityState === 'visible') txSendLocation();
        else clearTimeout(txLocationTimer);
    });
}
</script>
@endsection
