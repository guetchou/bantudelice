@extends('layouts.driver-modern')
@section('title', 'Livraisons | ' . \App\Services\ConfigService::getCompanyName())

@php
    $driverIsOnline = ($driver->status ?? 'offline') === 'online';
    $driverInitials = strtoupper(substr($driver->name ?? 'L', 0, 2));
    $heroH          = (int) date('H');
    $greeting       = $heroH < 6 ? 'Bonne nuit' : ($heroH < 12 ? 'Bonjour' : ($heroH < 18 ? 'Bon après-midi' : 'Bonne soirée'));

    $todayDelivered = \App\Delivery::where('driver_id',$driver->id)->where('status','DELIVERED')->whereDate('delivered_at',today())->count();
    $activeCount    = $deliveries->count();

    $finNet = 0; $finAvailable = 0; $finGross = 0;
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

    $stepOrder    = ['ASSIGNED','PICKED_UP','ON_THE_WAY'];
    $statusLabels = ['ASSIGNED'=>'À récupérer','PICKED_UP'=>'Ramassage','ON_THE_WAY'=>'En route'];
@endphp

@section('nav_deliveries', 'is-active')
@section('driver_initials', $driverInitials)
@section('driver_name', $driver->name ?? 'Livreur')
@section('driver_phone', $driver->phone ?? '')
@section('online_pill_class', $driverIsOnline ? 'online' : 'offline')
@section('online_pill_label', $driverIsOnline ? 'En ligne' : 'Hors ligne')
@section('page_title', 'Mes livraisons')

@section('nav_deliveries_badge')
    @if($activeCount > 0)<span class="bd-drv-nav-badge">{{ $activeCount }}</span>@endif
@endsection

@section('topbar_right')
    <button type="button"
            id="driverToggleBtn"
            class="drv-toggle-btn {{ $driverIsOnline ? 'online' : 'offline' }}"
            data-driver-id="{{ $driver->id }}"
            data-is-online="{{ $driverIsOnline ? '1' : '0' }}"
            data-url-online="{{ url('api/set_driver_online/'.$driver->id) }}"
            data-url-offline="{{ url('api/set_driver_offline/'.$driver->id) }}"
            data-csrf="{{ csrf_token() }}">
        <span class="drv-toggle-dot" id="driverStatusDot"></span>
        <span id="driverToggleBtnLabel">{{ $driverIsOnline ? 'En ligne' : 'Hors ligne' }}</span>
    </button>
@endsection

@section('style')
<style>
/* ── GPS indicator ─────────────────────────────────── */
.drv-gps-bar {
    display: flex; align-items: center; gap: 8px;
    padding: 9px 14px; margin-bottom: 16px;
    border-radius: var(--bd-radius);
    font-size: 12px; font-weight: 600;
    transition: all .3s;
}
.drv-gps-bar.active {
    background: var(--bd-green-pale); border: 1px solid rgba(0,149,67,.2); color: var(--bd-green-dark);
}
.drv-gps-bar.error {
    background: rgba(239,68,68,.06); border: 1px solid rgba(239,68,68,.2); color: #991b1b;
}
.drv-gps-bar.off {
    background: var(--bd-surface-2); border: 1px solid var(--bd-border); color: var(--bd-text-3);
}
.drv-gps-dot {
    width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0;
}
.drv-gps-bar.active .drv-gps-dot { background: var(--bd-green); animation: gpsPulse 2s infinite; }
.drv-gps-bar.error .drv-gps-dot  { background: #ef4444; }
.drv-gps-bar.off .drv-gps-dot    { background: var(--bd-text-3); }
@keyframes gpsPulse { 0%,100%{opacity:1} 50%{opacity:.3} }

/* ── Toggle button ─────────────────────────────────── */
.drv-toggle-btn {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 5px 12px; border-radius: 7px;
    font-size: 12px; font-weight: 600;
    border: 1px solid var(--bd-border);
    background: var(--bd-surface); color: var(--bd-text-2);
    cursor: pointer; font-family: var(--bd-font);
    transition: all .15s;
}
.drv-toggle-btn:disabled { opacity: .5; cursor: wait; }
.drv-toggle-dot { width: 7px; height: 7px; border-radius: 50%; background: var(--bd-text-3); flex-shrink: 0; }
.drv-toggle-btn.online .drv-toggle-dot { background: #22c55e; }
.drv-toggle-btn.online { border-color: rgba(34,197,94,.3); color: #16a34a; }

/* ── WELCOME BANNER ────────────────────────────────── */
.drv-welcome {
    background: var(--bd-green-pale);
    border-left: 4px solid var(--bd-green);
    border-radius: var(--bd-radius);
    padding: 18px 22px;
    margin-bottom: 20px;
    display: flex; align-items: center; gap: 14px;
}
.drv-welcome-avatar {
    width: 48px; height: 48px; border-radius: 50%;
    background: var(--bd-green);
    display: flex; align-items: center; justify-content: center;
    font-size: 18px; font-weight: 800; color: #fff;
    flex-shrink: 0;
}
.drv-welcome-text h2 {
    font-family: var(--bd-font-display);
    font-size: 18px; font-weight: 800; color: var(--bd-text);
    margin: 0 0 2px;
}
.drv-welcome-text p {
    font-size: 13px; color: var(--bd-text-2); margin: 0;
}
[data-theme="dark"] .drv-welcome { background: rgba(0,149,67,.08); }

/* ── KPI CARDS (style ElektriNet) ──────────────────── */
.drv-kpis {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 16px;
    margin-bottom: 24px;
}
.drv-kpi {
    background: var(--bd-surface);
    border: 1px solid var(--bd-border);
    border-radius: 12px;
    padding: 24px 16px 20px;
    text-align: center;
    box-shadow: var(--bd-shadow-sm);
    transition: box-shadow .15s, transform .15s;
    position: relative;
}
.drv-kpi:hover { box-shadow: var(--bd-shadow-md); transform: translateY(-2px); }
.drv-kpi-icon {
    width: 52px; height: 52px; border-radius: 16px;
    display: flex; align-items: center; justify-content: center;
    font-size: 22px; margin: 0 auto 14px;
}
.drv-kpi-icon.green  { background: rgba(0,149,67,.1);  color: var(--bd-green); }
.drv-kpi-icon.amber  { background: rgba(245,158,11,.1); color: #d97706; }
.drv-kpi-icon.blue   { background: rgba(59,130,246,.1); color: #2563eb; }
.drv-kpi-value {
    font-family: var(--bd-font-display);
    font-size: 32px; font-weight: 800; color: var(--bd-text);
    letter-spacing: -.03em; line-height: 1;
}
.drv-kpi-label {
    font-size: 12px; font-weight: 600; color: var(--bd-text-3);
    margin-top: 6px;
}

/* ── Section header ────────────────────────────────── */
.drv-sec {
    display: flex; align-items: center; gap: 8px; margin-bottom: 12px;
}
.drv-sec-title {
    font-size: 11px; font-weight: 700;
    text-transform: uppercase; letter-spacing: .08em;
    color: var(--bd-text-3);
}
.drv-sec-count {
    background: var(--bd-green); color: #fff;
    border-radius: 99px; padding: 1px 7px;
    font-size: 10px; font-weight: 700;
}

/* ── Mission card ──────────────────────────────────── */
.drv-missions { display: flex; flex-direction: column; gap: 10px; }

.drv-mission {
    background: var(--bd-surface);
    border: 1px solid var(--bd-border);
    border-radius: var(--bd-radius);
    overflow: hidden;
    transition: border-color .12s, box-shadow .12s;
}
.drv-mission:hover { box-shadow: var(--bd-shadow); }
.drv-mission-bar { height: 3px; }
.drv-mission-bar.assigned { background: #f59e0b; }
.drv-mission-bar.picked   { background: #3b82f6; }
.drv-mission-bar.onway    { background: var(--bd-green); }

/* Head row */
.drv-mh {
    display: flex; align-items: center; gap: 12px;
    padding: 10px 14px;
}
.drv-mh-ref {
    font-family: 'Courier New', monospace;
    font-size: 13px; font-weight: 700;
    color: var(--bd-text); flex-shrink: 0;
}
.drv-mh-route {
    flex: 1; min-width: 0;
    font-size: 12px; color: var(--bd-text-2);
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}
.drv-mh-route strong { color: var(--bd-text); font-weight: 600; }
.drv-mh-right { display: flex; align-items: center; gap: 8px; flex-shrink: 0; }

/* Badges */
.drv-badge {
    display: inline-flex; align-items: center;
    padding: 2px 7px; border-radius: 999px;
    font-size: 10px; font-weight: 700; text-transform: uppercase;
    white-space: nowrap;
}
.drv-badge.assigned { background: rgba(245,158,11,.1); color: #d97706; }
.drv-badge.picked   { background: rgba(59,130,246,.1);  color: #1d4ed8; }
.drv-badge.onway    { background: rgba(0,149,67,.1);    color: #15803d; }
.drv-badge.warn     { background: rgba(245,158,11,.12); color: #92400e; }
.drv-badge.danger   { background: rgba(239,68,68,.1);   color: #b91c1c; }
[data-theme="dark"] .drv-badge.assigned { background: rgba(251,191,36,.15); color: #fbbf24; }
[data-theme="dark"] .drv-badge.picked   { background: rgba(96,165,250,.15); color: #60a5fa; }
[data-theme="dark"] .drv-badge.onway    { background: rgba(0,201,87,.12);   color: #00c957; }

.drv-mh-fee { font-size: 13px; font-weight: 700; color: var(--bd-text); white-space: nowrap; }
.drv-mh-fee small { font-size: 10px; color: var(--bd-text-3); font-weight: 500; }

/* Expand */
.drv-expand-btn {
    background: none; border: none; cursor: pointer;
    color: var(--bd-text-3); padding: 2px 4px;
    border-radius: 4px; font-size: 12px;
    transition: color .12s;
}
.drv-expand-btn:hover { color: var(--bd-text-2); }
.drv-expand-btn i { transition: transform .2s; }
.drv-expand-btn.open i { transform: rotate(180deg); }

/* Steps bar */
.drv-steps-bar { display: flex; align-items: center; gap: 0; padding: 0 14px 8px; }
.drv-step-item { display: flex; align-items: center; gap: 4px; font-size: 11px; font-weight: 600; color: var(--bd-text-3); }
.drv-step-item.done   { color: var(--bd-green); }
.drv-step-item.active { color: #d97706; font-weight: 700; }
.drv-step-dot { width: 7px; height: 7px; border-radius: 50%; background: var(--bd-border); flex-shrink: 0; }
.drv-step-item.done   .drv-step-dot { background: #22c55e; }
.drv-step-item.active .drv-step-dot { background: #f59e0b; }
.drv-step-line { flex: 1; height: 1px; background: var(--bd-border); min-width: 16px; max-width: 40px; }

/* Details */
.drv-details { max-height: 0; overflow: hidden; transition: max-height .25s ease; border-top: 1px solid transparent; }
.drv-details.open { max-height: 500px; border-top-color: var(--bd-border); }
.drv-details-inner { padding: 12px 14px; display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
.drv-detail-cell { display: flex; flex-direction: column; gap: 1px; }
.drv-detail-lbl {
    font-size: 10px; font-weight: 700;
    text-transform: uppercase; letter-spacing: .06em;
    color: var(--bd-text-3);
}
.drv-detail-val { font-size: 13px; font-weight: 600; color: var(--bd-text); }
.drv-detail-val.addr { font-weight: 500; color: var(--bd-text-2); font-size: 12px; }
.drv-call-link {
    display: inline-flex; align-items: center; gap: 3px;
    margin-top: 3px; font-size: 11px; font-weight: 600;
    color: var(--bd-green); text-decoration: none;
}

/* Proofs */
.drv-proofs { display: flex; gap: 6px; flex-wrap: wrap; padding: 0 14px 10px; }
.drv-proof-link {
    display: inline-flex; align-items: center; gap: 4px;
    padding: 3px 8px; border-radius: 6px;
    background: var(--bd-surface-2); border: 1px solid var(--bd-border);
    font-size: 11px; font-weight: 600; color: var(--bd-text-2);
    text-decoration: none;
}

/* Actions */
.drv-actions {
    padding: 10px 14px 12px;
    border-top: 1px solid var(--bd-border);
    display: flex; align-items: center; gap: 8px;
}
.drv-btn {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 7px 14px; border-radius: 7px;
    font-size: 12px; font-weight: 600;
    border: none; cursor: pointer;
    font-family: var(--bd-font);
    transition: all .15s; white-space: nowrap;
}
.drv-btn:disabled { opacity: .5; cursor: wait; }
.drv-btn.green  { background: var(--bd-green); color: #fff; }
.drv-btn.green:hover { background: var(--bd-green-dark); }
.drv-btn.blue   { background: #3b82f6; color: #fff; }
.drv-btn.blue:hover { background: #2563eb; }
.drv-btn.dark   { background: #111827; color: #fff; }
.drv-btn.dark:hover { background: #0f172a; }
.drv-btn.ghost  { background: transparent; color: var(--bd-text-2); border: 1px solid var(--bd-border); font-size: 11px; }
.drv-btn.ghost:hover { background: var(--bd-surface-2); border-color: var(--bd-green); color: var(--bd-green); }

/* Form panel */
.drv-form-panel { display: none; }
.drv-form-panel.open {
    display: block; border-top: 1px solid var(--bd-border);
    padding: 12px 14px; background: var(--bd-surface-2);
}
.drv-field { margin-bottom: 8px; }
.drv-field label {
    display: block; font-size: 11px; font-weight: 600;
    color: var(--bd-text-2); margin-bottom: 3px;
}
.drv-field input,
.drv-field textarea,
.drv-field select {
    width: 100%; border: 1px solid var(--bd-border); border-radius: 6px;
    padding: 7px 10px; font-size: 13px; font-family: var(--bd-font);
    color: var(--bd-text); background: var(--bd-surface);
    transition: border-color .12s;
}
.drv-field input:focus, .drv-field textarea:focus {
    outline: none; border-color: var(--bd-green);
    box-shadow: 0 0 0 3px var(--bd-green-glow);
}
.drv-field textarea { min-height: 56px; resize: vertical; }
.drv-form-hint { font-size: 11px; color: var(--bd-text-3); margin-bottom: 8px; }
.drv-form-actions { display: flex; align-items: center; gap: 8px; }
.drv-form-submit {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 7px 16px; background: var(--bd-green); color: #fff;
    border: none; border-radius: 7px;
    font-size: 12px; font-weight: 600;
    cursor: pointer; font-family: var(--bd-font);
}
.drv-form-submit:hover { background: var(--bd-green-dark); }
.drv-form-cancel {
    font-size: 12px; font-weight: 500; color: var(--bd-text-3);
    background: none; border: none; cursor: pointer; font-family: var(--bd-font);
}

/* Incident bottom sheet */
.drv-bs-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,.4); z-index: 9000; }
.drv-bs-overlay.open { display: block; }
.drv-bs-panel {
    position: fixed; bottom: 0; left: 50%; right: auto;
    transform: translateX(-50%) translateY(100%);
    width: min(460px, 95vw);
    background: var(--bd-surface); border-radius: 14px 14px 0 0;
    padding: 0 20px 24px; z-index: 9001;
    transition: transform .28s cubic-bezier(.32,.72,0,1);
    max-height: 80vh; overflow-y: auto;
    box-shadow: 0 -4px 30px rgba(0,0,0,.15);
}
.drv-bs-panel.open { transform: translateX(-50%) translateY(0); }
.drv-bs-handle { width: 32px; height: 3px; background: var(--bd-border); border-radius: 99px; margin: 12px auto 16px; }
.drv-bs-head { display: flex; align-items: center; justify-content: space-between; margin-bottom: 14px; font-size: 14px; font-weight: 600; color: var(--bd-text); }
.drv-bs-close { width: 28px; height: 28px; border-radius: 50%; background: var(--bd-surface-2); border: none; cursor: pointer; font-size: 14px; color: var(--bd-text-3); display: flex; align-items: center; justify-content: center; }
.drv-bs-field { margin-bottom: 12px; }
.drv-bs-field label { display: block; font-size: 12px; font-weight: 600; color: var(--bd-text-2); margin-bottom: 4px; }
.drv-bs-field select, .drv-bs-field textarea {
    width: 100%; border: 1px solid var(--bd-border); border-radius: 7px;
    padding: 8px 10px; font-size: 13px; font-family: var(--bd-font); color: var(--bd-text); background: var(--bd-surface);
}
.drv-bs-field select:focus, .drv-bs-field textarea:focus { outline: none; border-color: var(--bd-green); box-shadow: 0 0 0 3px var(--bd-green-glow); }
.drv-bs-field textarea { min-height: 80px; resize: vertical; }
.drv-bs-submit {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 8px 18px; background: var(--bd-green); color: #fff;
    border: none; border-radius: 7px; font-size: 13px; font-weight: 600;
    cursor: pointer; font-family: var(--bd-font);
    width: fit-content;
}
.drv-zone-alert {
    display: none; padding: 8px 12px; margin-bottom: 10px;
    background: rgba(239,68,68,.06); border: 1px solid rgba(239,68,68,.2);
    border-radius: 7px; font-size: 12px; color: #991b1b; font-weight: 500;
}

/* Avatar in details */
.drv-avatar-sm {
    width: 36px; height: 36px; border-radius: 50%;
    object-fit: cover; border: 2px solid var(--bd-border);
    flex-shrink: 0;
}
.drv-avatar-placeholder {
    width: 36px; height: 36px; border-radius: 50%;
    background: var(--bd-surface-2); border: 2px solid var(--bd-border);
    display: flex; align-items: center; justify-content: center;
    font-size: 14px; font-weight: 700; color: var(--bd-text-3);
    flex-shrink: 0;
}
.drv-contact-row {
    display: flex; align-items: center; gap: 10px;
}
.drv-contact-info { flex: 1; min-width: 0; }
.drv-contact-phone {
    font-size: 11px; color: var(--bd-text-3); margin-top: 1px;
}

/* Chat compact */
.drv-chat-section {
    border-top: 1px solid var(--bd-border);
    padding: 12px 14px;
}
.drv-chat-toggle {
    display: inline-flex; align-items: center; gap: 8px;
    background: none; border: 1px solid var(--bd-border);
    border-radius: 7px; padding: 7px 12px;
    font-size: 12px; font-weight: 600; color: var(--bd-text-2);
    cursor: pointer; font-family: var(--bd-font);
    transition: border-color .12s;
}
.drv-chat-toggle:hover { border-color: var(--bd-green); color: var(--bd-green); }
.drv-chat-toggle .drv-chat-unread {
    margin-left: auto; background: var(--bd-green);
    color: #fff; border-radius: 99px; padding: 1px 7px;
    font-size: 10px; font-weight: 700;
}
.drv-chat-body { display: none; margin-top: 10px; }
.drv-chat-body.open { display: block; }
.drv-chat-messages {
    max-height: 220px; overflow-y: auto;
    display: flex; flex-direction: column; gap: 6px;
    padding: 4px 0; margin-bottom: 10px;
    max-width: 520px;
}
.drv-chat-msg {
    padding: 8px 10px; border-radius: 10px;
    max-width: 85%; font-size: 12px; line-height: 1.45;
}
.drv-chat-msg.mine {
    margin-left: auto;
    background: rgba(0,149,67,.08); border: 1px solid rgba(0,149,67,.2);
    color: var(--bd-text);
}
.drv-chat-msg.other {
    margin-right: auto;
    background: var(--bd-surface-2); border: 1px solid var(--bd-border);
    color: var(--bd-text);
}
.drv-chat-msg-meta {
    display: flex; justify-content: space-between; gap: 8px;
    font-size: 10px; font-weight: 700; margin-bottom: 3px;
}
.drv-chat-msg-role { text-transform: uppercase; letter-spacing: .04em; }
.drv-chat-msg.mine .drv-chat-msg-role { color: var(--bd-green); }
.drv-chat-msg.other .drv-chat-msg-role { color: var(--bd-text-3); }
.drv-chat-msg-time { color: var(--bd-text-3); font-weight: 500; }
.drv-chat-msg-body { word-break: break-word; }
.drv-chat-form {
    display: flex; gap: 6px; align-items: flex-end;
    max-width: 480px;
}
.drv-chat-input {
    flex: 1; border: 1px solid var(--bd-border); border-radius: 7px;
    padding: 7px 10px; font-size: 12px; font-family: var(--bd-font);
    color: var(--bd-text); background: var(--bd-surface);
    resize: none; min-height: 36px; max-height: 80px;
    width: auto;
}
.drv-chat-input:focus { outline: none; border-color: var(--bd-green); box-shadow: 0 0 0 2px var(--bd-green-glow); }
.drv-chat-send {
    background: var(--bd-green); color: #fff; border: none;
    border-radius: 7px; padding: 7px 12px; font-size: 12px;
    font-weight: 600; cursor: pointer; font-family: var(--bd-font);
    white-space: nowrap;
}
.drv-chat-send:hover { background: var(--bd-green-dark); }
.drv-chat-empty {
    text-align: center; padding: 16px 10px;
    font-size: 12px; color: var(--bd-text-3);
}

/* Empty state */
.drv-empty {
    text-align: center; padding: 40px 20px;
    background: var(--bd-surface); border: 1px dashed var(--bd-border);
    border-radius: var(--bd-radius);
}
.drv-empty-icon {
    width: 44px; height: 44px; border-radius: 12px;
    background: var(--bd-surface-2);
    display: flex; align-items: center; justify-content: center;
    margin: 0 auto 14px;
    font-size: 18px; color: var(--bd-text-3);
}
.drv-empty-title { font-size: 14px; font-weight: 600; color: var(--bd-text); margin-bottom: 5px; }
.drv-empty-sub   { font-size: 12px; color: var(--bd-text-2); line-height: 1.5; margin-bottom: 16px; }
.drv-wait-dots span {
    display: inline-block; width: 6px; height: 6px;
    border-radius: 50%; background: #22c55e;
    margin: 0 2px; animation: wdot 1.4s infinite;
}
.drv-wait-dots span:nth-child(2) { animation-delay: .2s; }
.drv-wait-dots span:nth-child(3) { animation-delay: .4s; }
@keyframes wdot { 0%,80%{transform:scale(.6);opacity:.3} 40%{transform:scale(1);opacity:1} }

/* Finance strip */
.drv-finance { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; margin-top: 20px; }
.drv-fin-cell {
    background: var(--bd-surface); border: 1px solid var(--bd-border);
    border-radius: var(--bd-radius); padding: 14px 16px;
    transition: border-color .12s;
}
.drv-fin-cell:hover { border-color: var(--bd-green); }
.drv-fin-cell.hi { border-color: rgba(0,149,67,.25); background: var(--bd-green-pale); }
.drv-fin-lbl { font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: .05em; color: var(--bd-text-3); margin-bottom: 4px; }
.drv-fin-cell.hi .drv-fin-lbl { color: var(--bd-green-dark); }
.drv-fin-val { font-family: var(--bd-font-display); font-size: 18px; font-weight: 800; color: var(--bd-text); line-height: 1; }
.drv-fin-cell.hi .drv-fin-val { color: var(--bd-green); }

@media (max-width: 768px) {
    .drv-kpis { grid-template-columns: 1fr; gap: 10px; }
    .drv-kpi { padding: 18px 14px 16px; }
    .drv-kpi-icon { width: 40px; height: 40px; font-size: 18px; margin-bottom: 10px; }
    .drv-kpi-value { font-size: 26px; }
    .drv-welcome { flex-direction: column; text-align: center; }
    .drv-details-inner { grid-template-columns: 1fr; }
    .drv-finance { grid-template-columns: 1fr; }
    .drv-missions { max-width: 100%; }
}

/* ── ZONE 1: Hero statut ─────────────────────────────── */
@keyframes fd2-pulse {
    0%   { box-shadow: 0 0 0 0 rgba(34,197,94,.6); }
    70%  { box-shadow: 0 0 0 14px rgba(34,197,94,0); }
    100% { box-shadow: 0 0 0 0 rgba(34,197,94,0); }
}
.fd2-hero {
    position: sticky; top: 0; z-index: 100;
    padding: 14px 20px 18px;
    transition: background .4s;
    display: flex; flex-direction: column; align-items: center;
    gap: 6px; margin-bottom: 20px;
    border-radius: 0 0 16px 16px;
    box-shadow: 0 4px 20px rgba(0,0,0,.12);
}
.fd2-hero.online  { background: #007836; }
.fd2-hero.offline { background: #334155; }
.fd2-hero-row {
    display: flex; align-items: center; gap: 8px; justify-content: center;
}
.fd2-hero-greeting {
    font-size: 17px; font-weight: 700; color: rgba(255,255,255,.95);
    text-align: center;
}
.fd2-hero-sub {
    font-size: 12px; color: rgba(255,255,255,.72);
    text-align: center; min-height: 16px;
}
.fd2-gps-dot {
    display: inline-block;
    width: 8px; height: 8px; border-radius: 50%;
    background: #22c55e; flex-shrink: 0;
}
.fd2-hero.online .fd2-gps-dot  { animation: fd2-pulse 2s infinite; }
.fd2-hero.offline .fd2-gps-dot { display: none !important; }
.fd2-toggle-hero {
    display: inline-flex; align-items: center; gap: 8px;
    padding: 8px 24px; border-radius: 9px;
    font-size: 13px; font-weight: 700;
    border: 2px solid rgba(255,255,255,.35);
    background: rgba(255,255,255,.12); color: #fff;
    cursor: pointer; font-family: var(--bd-font);
    transition: all .2s; margin-top: 6px;
}
.fd2-toggle-hero:hover    { background: rgba(255,255,255,.22); }
.fd2-toggle-hero:disabled { opacity: .5; cursor: wait; }
@media (max-width: 480px) {
    .fd2-hero { border-radius: 0 0 12px 12px; padding: 12px 16px 16px; }
    .fd2-hero-greeting { font-size: 15px; }
    .fd2-toggle-hero { padding: 7px 18px; font-size: 12px; }
}

/* ── ZONE 2: Carte mission prioritaire ───────────────── */
.fd2-zone2 { margin-bottom: 20px; }
.fd2-z2-card {
    background: var(--bd-surface);
    border: 1px solid var(--bd-border);
    border-radius: var(--bd-radius);
    overflow: hidden;
    box-shadow: var(--bd-shadow);
}
.fd2-z2-card::before {
    content: ''; display: block; height: 3px;
    background: linear-gradient(90deg, #007836, #22c55e);
}
.fd2-z2-head {
    display: flex; align-items: center; gap: 8px;
    padding: 10px 14px 0;
}
.fd2-z2-ref {
    font-family: 'Courier New', monospace;
    font-size: 13px; font-weight: 700; color: var(--bd-text);
}
.fd2-z2-fee {
    margin-left: auto;
    font-size: 13px; font-weight: 700; color: var(--bd-text);
    white-space: nowrap;
}
.fd2-z2-route {
    display: flex; align-items: center; gap: 4px;
    padding: 6px 14px; font-size: 12px; color: var(--bd-text-2);
    white-space: nowrap; overflow: hidden;
}
.fd2-z2-steps {
    display: flex; align-items: center;
    padding: 8px 14px 10px;
}
.fd2-z2-step {
    display: flex; flex-direction: column; align-items: center; gap: 3px;
    flex-shrink: 0;
}
.fd2-z2-step-dot {
    width: 10px; height: 10px; border-radius: 50%;
    background: var(--bd-border); flex-shrink: 0;
}
.fd2-z2-step.done   .fd2-z2-step-dot { background: #22c55e; }
.fd2-z2-step.active .fd2-z2-step-dot { background: #f59e0b; box-shadow: 0 0 0 4px rgba(245,158,11,.2); }
.fd2-z2-step-lbl {
    font-size: 9px; font-weight: 600; text-transform: uppercase;
    letter-spacing: .04em; color: var(--bd-text-3); white-space: nowrap;
}
.fd2-z2-step.done   .fd2-z2-step-lbl { color: var(--bd-green); }
.fd2-z2-step.active .fd2-z2-step-lbl { color: #d97706; }
.fd2-z2-step-line {
    flex: 1; height: 2px; background: var(--bd-border);
    min-width: 12px; max-width: 36px; margin-bottom: 12px;
}
.fd2-z2-step-line.done { background: #22c55e; }
.fd2-z2-nav-btn {
    display: flex; align-items: center; justify-content: center; gap: 6px;
    margin: 0 14px 12px;
    padding: 8px 20px; border-radius: 8px;
    background: #007836; color: #fff;
    font-size: 13px; font-weight: 700;
    text-decoration: none; transition: background .15s;
}
.fd2-z2-nav-btn:hover { background: #005c2a; color: #fff; }
.fd2-zone2-wait {
    display: flex; flex-direction: column; align-items: center;
    gap: 8px; padding: 20px;
    background: var(--bd-green-pale);
    border: 1px dashed rgba(0,149,67,.3);
    border-radius: var(--bd-radius);
    margin-bottom: 20px; text-align: center;
}
.fd2-z2-wait-icon {
    width: 40px; height: 40px; border-radius: 12px;
    background: rgba(0,149,67,.1);
    display: flex; align-items: center; justify-content: center;
    color: var(--bd-green); font-size: 18px;
}
.fd2-z2-wait-text { font-size: 14px; font-weight: 600; color: var(--bd-text); }

/* ── ZONE 3: Panel offre entrante ────────────────────── */
.fd2-offer-overlay {
    position: fixed; inset: 0;
    background: rgba(0,0,0,.4); z-index: 9990;
}
.fd2-offer-panel {
    position: fixed; bottom: 20px; left: 50%; transform: translateX(-50%);
    z-index: 9999; width: min(380px,95vw);
    background: var(--bd-surface);
    border: 1px solid var(--bd-border);
    border-radius: var(--bd-radius-lg);
    box-shadow: 0 12px 40px rgba(0,0,0,.18);
    padding: 16px; overflow: hidden;
    font-family: var(--bd-font);
}
.fd2-offer-head {
    display: flex; align-items: center; gap: 10px; margin-bottom: 12px;
}
.fd2-offer-icon {
    width: 36px; height: 36px; border-radius: 8px;
    background: var(--bd-green);
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0; color: #fff; font-size: 14px;
}
.fd2-offer-title { font-weight: 600; font-size: 14px; color: var(--bd-text); }
.fd2-offer-sub   { font-size: 12px; color: var(--bd-text-3); margin-top: 1px; }
.fd2-countdown-wrap {
    height: 4px; background: var(--bd-border);
    border-radius: 2px; overflow: hidden; margin-bottom: 10px;
}
.fd2-countdown-bar { height: 100%; background: #f59e0b; width: 100%; }
.fd2-offer-timer-row {
    background: rgba(245,158,11,.08);
    border: 1px solid rgba(245,158,11,.25);
    border-radius: 7px; padding: 7px 10px;
    margin-bottom: 12px;
    font-size: 12px; color: #92400e; font-weight: 600;
    display: flex; align-items: center; gap: 6px;
}
.fd2-offer-actions { display: flex; gap: 8px; }
.fd2-offer-btn {
    flex: 1; display: inline-flex; align-items: center;
    justify-content: center; gap: 6px;
    padding: 9px 14px; border-radius: 8px;
    font-size: 13px; font-weight: 700;
    border: none; cursor: pointer; font-family: var(--bd-font);
    transition: all .15s;
}
.fd2-offer-btn.accept  { background: var(--bd-green); color: #fff; }
.fd2-offer-btn.accept:hover  { background: var(--bd-green-dark); }
.fd2-offer-btn.decline { background: var(--bd-surface-2); color: var(--bd-text-2); border: 1px solid var(--bd-border); }
.fd2-offer-btn.decline:hover { background: rgba(239,68,68,.08); border-color: rgba(239,68,68,.3); color: #b91c1c; }
.fd2-offer-btn:disabled { opacity: .5; cursor: wait; }
</style>
@endsection

@section('content')

{{-- ── ZONE 1: HERO STATUT ── --}}
<div class="fd2-hero {{ $driverIsOnline ? 'online' : 'offline' }}" id="fd2Hero">
    <div class="fd2-hero-row">
        <span class="fd2-gps-dot" id="fd2GpsDot"
              style="{{ !$driverIsOnline ? 'display:none;' : '' }}"></span>
        <span class="fd2-hero-greeting">{{ $greeting }}, {{ explode(' ', $driver->name ?? 'Livreur')[0] }} !</span>
    </div>
    <span class="fd2-hero-sub" id="gpsLabel">
        {{-- gpsLabel ID is used by the GPS watchPosition script to update this text --}}
        {{ $driverIsOnline ? 'Localisation GPS en cours...' : 'Passez en ligne pour recevoir des missions' }}
    </span>
    <button type="button" class="fd2-toggle-hero" id="fd2HeroBtn"
            onclick="document.getElementById('driverToggleBtn').click();this.disabled=true;var _b=this;setTimeout(function(){_b.disabled=false;},2500);">
        <i class="fas fa-circle-{{ $driverIsOnline ? 'pause' : 'play' }}" id="fd2HeroToggleIcon"></i>
        <span id="fd2HeroToggleLabel">{{ $driverIsOnline ? 'Passer hors ligne' : 'Passer en ligne' }}</span>
    </button>
</div>
{{-- GPS bar preserved hidden — GPS script sets its class via id="gpsBar" --}}
<div id="gpsBar" class="drv-gps-bar {{ $driverIsOnline ? 'active' : 'off' }}"
     style="display:none;" aria-hidden="true">
    <span class="drv-gps-dot"></span>
</div>

{{-- ── ZONE 2: MISSION PRIORITAIRE ── --}}
@if($driverIsOnline)
@if($activeCount > 0)
@php
    $primD       = $deliveries->first();
    $z2Steps     = ['ASSIGNED' => 'Assigné', 'PICKED_UP' => 'Récupéré', 'ON_THE_WAY' => 'En route', 'DELIVERED' => 'Livré'];
    $z2StepKeys  = array_keys($z2Steps);
    $z2CurIdx    = ($k = array_search($primD->status, $z2StepKeys)) !== false ? $k : 0;
    $z2Badge     = ['ASSIGNED' => 'assigned', 'PICKED_UP' => 'picked', 'ON_THE_WAY' => 'onway'][$primD->status] ?? 'assigned';
    $z2RestName  = $primD->restaurant->name ?? '—';
    $z2Addr      = $primD->order->delivery_address ?? '';
@endphp
<div class="fd2-zone2">
    <div class="fd2-z2-card">
        <div class="fd2-z2-head">
            <span class="fd2-z2-ref">#{{ $primD->order->order_no ?? $primD->order_id }}</span>
            <span class="drv-badge {{ $z2Badge }}">{{ $statusLabels[$primD->status] ?? $primD->status }}</span>
            <span class="fd2-z2-fee">{{ number_format($primD->delivery_fee ?? 0, 0, ',', ' ') }} <small style="font-size:10px;font-weight:500;color:var(--bd-text-3);">FCFA</small></span>
        </div>
        <div class="fd2-z2-route">
            <i class="fas fa-store" style="color:var(--bd-green);font-size:11px;flex-shrink:0;"></i>
            <span>{{ Str::limit($z2RestName, 22) }}</span>
            <i class="fas fa-arrow-right" style="font-size:9px;color:var(--bd-text-3);margin:0 3px;flex-shrink:0;"></i>
            <i class="fas fa-location-dot" style="color:#ef4444;font-size:11px;flex-shrink:0;"></i>
            <span>{{ Str::limit($z2Addr, 30) }}</span>
        </div>
        <div class="fd2-z2-steps">
            @foreach($z2Steps as $z2Key => $z2Label)
            @php $z2Idx = array_search($z2Key, $z2StepKeys); @endphp
            @if($z2Idx > 0)
            <div class="fd2-z2-step-line {{ $z2Idx <= $z2CurIdx ? 'done' : '' }}"></div>
            @endif
            <div class="fd2-z2-step {{ $z2Idx < $z2CurIdx ? 'done' : ($z2Key === $primD->status ? 'active' : '') }}">
                <div class="fd2-z2-step-dot"></div>
                <span class="fd2-z2-step-lbl">{{ $z2Label }}</span>
            </div>
            @endforeach
        </div>
        @if($z2Addr)
        <a class="fd2-z2-nav-btn"
           href="https://maps.google.com/?q={{ urlencode($z2Addr) }}"
           target="_blank" rel="noopener noreferrer">
            <i class="fas fa-map-location-dot"></i> Naviguer
        </a>
        @endif
    </div>
</div>
@else
<div class="fd2-zone2-wait">
    <div class="fd2-z2-wait-icon"><i class="fas fa-satellite-dish"></i></div>
    <div class="fd2-z2-wait-text">En attente de mission</div>
    <div class="drv-wait-dots"><span></span><span></span><span></span></div>
</div>
@endif
@endif

{{-- ── KPIs ── --}}
<div class="drv-kpis">
    <div class="drv-kpi">
        <div class="drv-kpi-icon green"><i class="fas fa-circle-check"></i></div>
        <div class="drv-kpi-value" style="color:var(--bd-green);">{{ $todayDelivered }}</div>
        <div class="drv-kpi-label">Livrées aujourd'hui</div>
    </div>
    <div class="drv-kpi">
        <div class="drv-kpi-icon amber"><i class="fas fa-motorcycle"></i></div>
        <div class="drv-kpi-value">{{ $activeCount }}</div>
        <div class="drv-kpi-label">Missions actives</div>
    </div>
    <div class="drv-kpi">
        <div class="drv-kpi-icon blue"><i class="fas fa-wallet"></i></div>
        <div class="drv-kpi-value" data-countup="{{ round($finAvailable) }}">{{ number_format(round($finAvailable),0,',',' ') }}</div>
        <div class="drv-kpi-label">Disponible FCFA</div>
    </div>
</div>

{{-- ── MISSIONS ── --}}
<div class="drv-sec">
    <span class="drv-sec-title">Missions actives</span>
    @if($activeCount > 0)<span class="drv-sec-count">{{ $activeCount }}</span>@endif
</div>

<div class="drv-missions">
@forelse($deliveries as $delivery)
@php
    $stepIdx     = array_search($delivery->status, $stepOrder, true);
    $stepIdx     = $stepIdx === false ? 0 : $stepIdx;
    $requiresCash= ($delivery->order->payment_method ?? null) === 'cash' && empty($delivery->cash_collected_at);
    $stripeClass = ['ASSIGNED'=>'assigned','PICKED_UP'=>'picked','ON_THE_WAY'=>'onway'][$delivery->status] ?? 'assigned';
    $badgeClass  = $stripeClass;
    $restName    = $delivery->restaurant->name ?? '—';
    $restPhone   = $delivery->restaurant->phone ?? null;
    $restLogo    = $delivery->restaurant->logo ?? null;
    $restLogoUrl = $restLogo ? (\Str::startsWith($restLogo, ['http://', 'https://']) ? $restLogo : asset('images/restaurant_images/' . $restLogo)) : null;
    $clientName  = $delivery->order->user->name ?? '—';
    $clientPhone = $delivery->order->user->phone ?? null;
    $clientImg   = $delivery->order->user->image ?? $delivery->order->user->social_avatar ?? null;
    $clientImgUrl= $clientImg ? (\Str::startsWith($clientImg, ['http://', 'https://']) ? $clientImg : asset('images/user_images/' . $clientImg)) : null;
    $delivAddr   = $delivery->order->delivery_address ?? '—';
    $incPhase    = ['ASSIGNED'=>'pre','PICKED_UP'=>'pickup','ON_THE_WAY'=>'otw'][$delivery->status] ?? 'pre';
    $chatData    = $delivery->chatData ?? null;
    $chatBadge   = $delivery->chatBadge ?? [];
    $hasUnread   = !empty($chatBadge['has_unread']);
    $unreadCount = $chatBadge['count'] ?? 0;
@endphp

<div class="drv-mission" id="mission-{{ $delivery->id }}">
    <div class="drv-mission-bar {{ $stripeClass }}"></div>

    <div class="drv-mh">
        <span class="drv-mh-ref">#{{ $delivery->order->order_no ?? $delivery->order_id }}</span>
        <span class="drv-mh-route">
            <strong>{{ Str::limit($restName, 22) }}</strong>
            <i class="fas fa-arrow-right" style="font-size:9px;margin:0 3px;color:var(--bd-text-3);"></i>
            {{ Str::limit($delivAddr, 28) }}
        </span>
        <div class="drv-mh-right">
            <span class="drv-badge {{ $badgeClass }}">{{ $statusLabels[$delivery->status] ?? $delivery->status }}</span>
            @if($requiresCash) <span class="drv-badge warn">Cash</span> @endif
            @if($delivery->incident_status === 'open') <span class="drv-badge danger">Incident</span> @endif
            @if($hasUnread)
            <span class="drv-badge" style="background:rgba(0,149,67,.1);color:var(--bd-green);">
                <i class="fas fa-comment-dots" style="font-size:9px;"></i> {{ $unreadCount }}
            </span>
            @endif
            <span class="drv-mh-fee">{{ number_format($delivery->delivery_fee??0,0,',',' ') }} <small>FCFA</small></span>
            <button type="button" class="drv-expand-btn" id="exp-btn-{{ $delivery->id }}"
                    onclick="drvToggleDetails('{{ $delivery->id }}')">
                <i class="fas fa-chevron-down"></i>
            </button>
        </div>
    </div>

    <div class="drv-steps-bar">
        @foreach(['ASSIGNED'=>'Récupération','PICKED_UP'=>'Ramassage','ON_THE_WAY'=>'Livraison'] as $sc => $sl)
        @php
            $si  = array_search($sc, $stepOrder, true);
            $cls = $si < $stepIdx ? 'done' : ($sc === $delivery->status ? 'active' : '');
        @endphp
        @if(!$loop->first)<div class="drv-step-line"></div>@endif
        <div class="drv-step-item {{ $cls }}">
            <span class="drv-step-dot"></span>
            {{ $sl }}
        </div>
        @endforeach
    </div>

    <div class="drv-details" id="details-{{ $delivery->id }}">
        <div class="drv-details-inner">
            <div class="drv-detail-cell">
                <span class="drv-detail-lbl">Restaurant</span>
                <div class="drv-contact-row">
                    @if($restLogoUrl)
                    <img src="{{ $restLogoUrl }}" alt="{{ $restName }}" class="drv-avatar-sm">
                    @else
                    <span class="drv-avatar-placeholder"><i class="fas fa-store"></i></span>
                    @endif
                    <div class="drv-contact-info">
                        <span class="drv-detail-val">{{ $restName }}</span>
                        @if($restPhone)
                        <div class="drv-contact-phone">{{ $restPhone }}</div>
                        <a class="drv-call-link" href="tel:{{ tel_clean($restPhone) }}"><i class="fas fa-phone" style="font-size:10px;"></i> Appeler</a>
                        @endif
                    </div>
                </div>
            </div>
            <div class="drv-detail-cell">
                <span class="drv-detail-lbl">Client</span>
                <div class="drv-contact-row">
                    @if($clientImgUrl)
                    <img src="{{ $clientImgUrl }}" alt="{{ $clientName }}" class="drv-avatar-sm">
                    @else
                    <span class="drv-avatar-placeholder">{{ strtoupper(substr($clientName, 0, 1)) }}</span>
                    @endif
                    <div class="drv-contact-info">
                        <span class="drv-detail-val">{{ $clientName }}</span>
                        @if($clientPhone)
                        <div class="drv-contact-phone">{{ $clientPhone }}</div>
                        <a class="drv-call-link" href="tel:{{ tel_clean($clientPhone) }}"><i class="fas fa-phone" style="font-size:10px;"></i> Appeler</a>
                        @endif
                    </div>
                </div>
            </div>
            <div class="drv-detail-cell" style="grid-column:1/-1">
                <span class="drv-detail-lbl">Adresse de livraison</span>
                <span class="drv-detail-val addr">{{ $delivAddr }}</span>
            </div>
            <div class="drv-detail-cell">
                <span class="drv-detail-lbl">Commande</span>
                <span class="drv-detail-val">{{ number_format($delivery->order->total??0,0,',',' ') }} FCFA</span>
            </div>
            <div class="drv-detail-cell">
                <span class="drv-detail-lbl">Paiement</span>
                <span class="drv-detail-val">
                    @if($requiresCash) Cash &mdash; à encaisser
                    @elseif(($delivery->order->payment_status??null)==='paid') Confirmé
                    @else En attente
                    @endif
                </span>
            </div>
        </div>
        @if($delivery->pickup_proof_path || $delivery->delivery_proof_path || $delivery->customer_confirmed_at)
        <div class="drv-proofs" style="border-top:1px solid var(--bd-border);padding-top:8px;">
            @if($delivery->pickup_proof_path)
            <a class="drv-proof-link" href="{{ asset($delivery->pickup_proof_path) }}" target="_blank"><i class="fas fa-image"></i> Photo ramassage</a>
            @endif
            @if($delivery->delivery_proof_path)
            <a class="drv-proof-link" href="{{ asset($delivery->delivery_proof_path) }}" target="_blank"><i class="fas fa-image"></i> Photo remise</a>
            @endif
            @if($delivery->customer_confirmed_at)
            <span class="drv-proof-link"><i class="fas fa-check"></i> Confirmé {{ $delivery->customer_confirmed_at->format('d/m H:i') }}</span>
            @endif
        </div>
        @endif
    </div>

    {{-- Chat compact --}}
    @if($chatData && ($chatData['can_view'] ?? false))
    <div class="drv-chat-section">
        <button type="button" class="drv-chat-toggle" onclick="drvToggleChat('{{ $delivery->id }}')">
            <i class="fas fa-comments"></i>
            Conversation
            @if($hasUnread)
            <span class="drv-chat-unread">{{ $unreadCount }}</span>
            @endif
        </button>
        <div class="drv-chat-body" id="chat-body-{{ $delivery->id }}">
            <div class="drv-chat-messages" id="chat-msgs-{{ $delivery->id }}"
                 data-refresh-url="{{ $chatData['messages_url'] ?? '' }}"
                 data-role="{{ $chatData['role'] ?? 'driver' }}">
                @php $msgs = $chatData['messages'] ?? []; @endphp
                @forelse($msgs as $msg)
                <div class="drv-chat-msg {{ ($msg['mine'] ?? false) ? 'mine' : 'other' }}">
                    <div class="drv-chat-msg-meta">
                        <span class="drv-chat-msg-role">{{ $msg['label'] ?? '' }}</span>
                        <span class="drv-chat-msg-time">{{ $msg['time'] ?? '' }}</span>
                    </div>
                    <div class="drv-chat-msg-body">{{ $msg['body'] ?? '' }}</div>
                </div>
                @empty
                <div class="drv-chat-empty">
                    <i class="fas fa-comments" style="font-size:16px;margin-bottom:4px;display:block;"></i>
                    Aucun message. Écrivez au client ou au restaurant.
                </div>
                @endforelse
            </div>
            @if($chatData['can_write'] ?? false)
            <form class="drv-chat-form" onsubmit="return drvSendChat(event, '{{ $chatData['store_url'] ?? '' }}', '{{ $delivery->id }}')">
                <textarea class="drv-chat-input" id="chat-input-{{ $delivery->id }}" rows="1" maxlength="500" placeholder="Votre message..."></textarea>
                <button type="submit" class="drv-chat-send"><i class="fas fa-paper-plane"></i></button>
            </form>
            @endif
        </div>
    </div>
    @endif

    <div class="drv-actions">
        @if($delivery->status === 'ASSIGNED')
            @if(empty($delivery->restaurant_arrived_at))
            <button type="button" class="drv-btn blue" onclick="drvOpenPanel('restaurant-arrival-{{ $delivery->id }}')">
                <i class="fas fa-store"></i> Je suis arrivé au restaurant
            </button>
            @else
            <button type="button" class="drv-btn green" onclick="drvOpenPanel('pickup-{{ $delivery->id }}')">
                <i class="fas fa-check"></i> Récupérer
            </button>
            @endif
        @elseif($delivery->status === 'PICKED_UP')
        <button type="button" class="drv-btn blue" onclick="drvOpenPanel('onway-{{ $delivery->id }}')">
            <i class="fas fa-motorcycle"></i> Démarrer livraison
        </button>
        @elseif($delivery->status === 'ON_THE_WAY')
        <button type="button" class="drv-btn dark" onclick="drvOpenPanel('deliver-{{ $delivery->id }}')">
            <i class="fas fa-box-open"></i> Confirmer remise
        </button>
        @endif

        <button type="button" class="drv-btn ghost" onclick="drvOpenIncident('{{ $delivery->id }}')">
            <i class="fas fa-triangle-exclamation"></i> Problème
        </button>
    </div>

    {{-- Forms --}}
    @if($delivery->status === 'ASSIGNED')
    @if(empty($delivery->restaurant_arrived_at))
    <div class="drv-form-panel" id="restaurant-arrival-{{ $delivery->id }}">
        <form method="POST" action="{{ route('driver.deliveries.update', $delivery->id) }}"
              class="delivery-action-form" data-lat-prefix="restaurant_arrival">
            @csrf
            <input type="hidden" name="status" value="ARRIVED_AT_RESTAURANT">
            <input type="hidden" name="restaurant_arrival_latitude">
            <input type="hidden" name="restaurant_arrival_longitude">
            <div class="drv-field">
                <label>Confirmation arrivée</label>
                <p style="margin:0;font-size:12px;color:var(--bd-muted);line-height:1.4;">
                    Votre position GPS sera comparée à l’adresse du restaurant avant d’autoriser le retrait.
                </p>
            </div>
            <div class="drv-form-hint"><i class="fas fa-location-dot"></i> Géolocalisation haute précision requise</div>
            <div class="drv-form-actions">
                <button type="submit" class="drv-form-submit"><i class="fas fa-store"></i> Confirmer arrivée</button>
                <button type="button" class="drv-form-cancel" onclick="drvClosePanel('restaurant-arrival-{{ $delivery->id }}')">Annuler</button>
            </div>
        </form>
    </div>
    @else
    <div class="drv-form-panel" id="pickup-{{ $delivery->id }}">
        <form method="POST" action="{{ route('driver.deliveries.update', $delivery->id) }}"
              enctype="multipart/form-data" class="delivery-action-form" data-lat-prefix="pickup">
            @csrf
            <input type="hidden" name="status" value="PICKED_UP">
            <input type="hidden" name="pickup_latitude">
            <input type="hidden" name="pickup_longitude">
            <div class="drv-field">
                <label>Note de ramassage (optionnel)</label>
                <textarea name="pickup_notes" placeholder="Commande récupérée, vérifiée..." rows="2"></textarea>
            </div>
            <div class="drv-field">
                <label>Photo de prise en charge</label>
                <input type="file" name="pickup_proof" accept="image/*" capture="environment">
            </div>
            <div class="drv-form-hint"><i class="fas fa-location-dot"></i> Géolocalisation capturée automatiquement</div>
            <div class="drv-form-actions">
                <button type="submit" class="drv-form-submit"><i class="fas fa-check"></i> Confirmer</button>
                <button type="button" class="drv-form-cancel" onclick="drvClosePanel('pickup-{{ $delivery->id }}')">Annuler</button>
            </div>
        </form>
    </div>
    @endif
    @elseif($delivery->status === 'PICKED_UP')
    <div class="drv-form-panel" id="onway-{{ $delivery->id }}">
        <form method="POST" action="{{ route('driver.deliveries.update', $delivery->id) }}"
              class="delivery-action-form" data-lat-prefix="delivery">
            @csrf
            <input type="hidden" name="status" value="ON_THE_WAY">
            <input type="hidden" name="delivery_latitude">
            <input type="hidden" name="delivery_longitude">
            <div class="drv-field">
                <label>Note de départ (optionnel)</label>
                <textarea name="delivery_notes" placeholder="Je pars du restaurant..." rows="2"></textarea>
            </div>
            <div class="drv-form-hint"><i class="fas fa-location-dot"></i> Position de départ capturée</div>
            <div class="drv-form-actions">
                <button type="submit" class="drv-form-submit"><i class="fas fa-motorcycle"></i> Passer en route</button>
                <button type="button" class="drv-form-cancel" onclick="drvClosePanel('onway-{{ $delivery->id }}')">Annuler</button>
            </div>
        </form>
    </div>
    @elseif($delivery->status === 'ON_THE_WAY')
    <div class="drv-form-panel" id="deliver-{{ $delivery->id }}">
        <form method="POST" action="{{ route('driver.deliveries.update', $delivery->id) }}"
              enctype="multipart/form-data" class="delivery-action-form" data-lat-prefix="delivery">
            @csrf
            <input type="hidden" name="status" value="DELIVERED">
            <input type="hidden" name="delivery_latitude">
            <input type="hidden" name="delivery_longitude">
            <div class="drv-field">
                <label>Code OTP client</label>
                <input type="text" name="delivery_otp" inputmode="numeric" placeholder="Code à 4 chiffres">
            </div>
            <div class="drv-field">
                <label>Photo preuve de remise</label>
                <input type="file" name="delivery_proof" accept="image/*" capture="environment">
            </div>
            <div class="drv-field">
                <label>Note de remise (optionnel)</label>
                <textarea name="delivery_notes" placeholder="Remise au client..." rows="2"></textarea>
            </div>
            <div class="drv-field">
                <label style="display:flex;align-items:center;gap:6px;cursor:pointer;">
                    <input type="checkbox" name="customer_confirmed" value="1" style="width:auto;">
                    Le client confirme la réception sur place
                </label>
            </div>
            @if($requiresCash)
            <div style="padding:8px 10px;background:rgba(245,158,11,.08);border:1px solid rgba(245,158,11,.25);border-radius:7px;font-size:12px;color:#92400e;margin-bottom:8px;">
                <i class="fas fa-money-bill"></i>
                Encaisser {{ number_format($delivery->order->total??0,0,',',' ') }} FCFA avant de confirmer.
            </div>
            @endif
            <div class="drv-form-actions">
                <button type="submit" class="drv-form-submit"><i class="fas fa-box"></i> Marquer livrée</button>
                <button type="button" class="drv-form-cancel" onclick="drvClosePanel('deliver-{{ $delivery->id }}')">Annuler</button>
            </div>
        </form>
    </div>
    @endif
</div>

{{-- Incident bottom sheet --}}
<div class="drv-bs-overlay" id="bs-overlay-{{ $delivery->id }}" onclick="drvCloseIncident('{{ $delivery->id }}')"></div>
<div class="drv-bs-panel" id="bs-panel-{{ $delivery->id }}">
    <div class="drv-bs-handle"></div>
    <div class="drv-bs-head">
        <span><i class="fas fa-triangle-exclamation" style="color:#f59e0b;margin-right:6px;"></i> Signaler un incident</span>
        <button class="drv-bs-close" onclick="drvCloseIncident('{{ $delivery->id }}')"><i class="fas fa-xmark"></i></button>
    </div>
    <form method="POST" action="{{ route('driver.deliveries.incident', $delivery->id) }}">
        @csrf
        <div id="zone-alert-{{ $delivery->id }}-{{ $incPhase }}" class="drv-zone-alert">
            <i class="fas fa-road-barrier"></i> Zone inaccessible — le support sera notifié.
        </div>
        <div class="drv-bs-field">
            <label>Motif</label>
            <select name="reason" onchange="drvZoneAlert(this,'{{ $delivery->id }}-{{ $incPhase }}')">
                @if($delivery->status === 'ASSIGNED')
                <option value="restaurant_issue">Restaurant indisponible</option>
                <option value="order_missing">Commande introuvable</option>
                <option value="address_issue">Adresse problématique</option>
                <option value="zone_inaccessible">Zone inaccessible</option>
                @elseif($delivery->status === 'PICKED_UP')
                <option value="packaging_issue">Produit endommagé</option>
                <option value="restaurant_issue">Restaurant bloqué</option>
                <option value="zone_inaccessible">Zone inaccessible</option>
                <option value="incident_open">Autre incident</option>
                @elseif($delivery->status === 'ON_THE_WAY')
                <option value="customer_absent">Client absent</option>
                <option value="recipient_unreachable">Client injoignable</option>
                <option value="address_issue">Adresse introuvable</option>
                <option value="zone_inaccessible">Zone inaccessible</option>
                <option value="delivery_failed">Tentative échouée</option>
                @endif
            </select>
        </div>
        <div class="drv-bs-field">
            <label>Détails</label>
            <textarea name="notes" placeholder="Décrivez le problème..."></textarea>
        </div>
        <button type="submit" class="drv-bs-submit"><i class="fas fa-paper-plane"></i> Soumettre</button>
    </form>
</div>

@empty

@if(!$driverIsOnline)
<div class="drv-empty">
    <div class="drv-empty-icon"><i class="fas fa-circle-pause"></i></div>
    <div class="drv-empty-title">Vous êtes hors ligne</div>
    <div class="drv-empty-sub">Activez votre disponibilité pour recevoir des missions.</div>
    <button type="button" class="drv-btn green" onclick="document.getElementById('driverToggleBtn').click()">
        <i class="fas fa-circle-play"></i> Passer en ligne
    </button>
</div>
@else
<div class="drv-empty" style="border-color:rgba(0,149,67,.2);background:var(--bd-green-pale);">
    <div class="drv-empty-icon" style="background:rgba(0,149,67,.1);color:var(--bd-green);"><i class="fas fa-satellite-dish"></i></div>
    <div class="drv-empty-title">En attente de missions</div>
    <div class="drv-empty-sub">Vous êtes actif. Les missions s'afficheront automatiquement.</div>
    <div class="drv-wait-dots"><span></span><span></span><span></span></div>
</div>
@endif

@endforelse
</div>

{{-- Gains --}}
@if($finGross > 0)
<div style="margin-top:20px;">
    <div class="drv-sec">
        <span class="drv-sec-title">Résumé financier</span>
        @if(app('router')->has('driver.gains'))
        <a href="{{ route('driver.gains') }}" style="margin-left:auto;font-size:12px;font-weight:600;color:var(--bd-green);text-decoration:none;">
            Voir le détail <i class="fas fa-arrow-right" style="font-size:10px;"></i>
        </a>
        @endif
    </div>
    <div class="drv-finance">
        <div class="drv-fin-cell hi">
            <div class="drv-fin-lbl">Disponible</div>
            <div class="drv-fin-val">{{ number_format(round($finAvailable),0,',',' ') }} FCFA</div>
        </div>
        <div class="drv-fin-cell">
            <div class="drv-fin-lbl">Net partenaire</div>
            <div class="drv-fin-val">{{ number_format(round($finNet),0,',',' ') }} FCFA</div>
        </div>
        <div class="drv-fin-cell">
            <div class="drv-fin-lbl">CA brut</div>
            <div class="drv-fin-val">{{ number_format(round($finGross),0,',',' ') }} FCFA</div>
        </div>
    </div>
</div>
@endif

{{-- ── ZONE 3: PANEL OFFRE ENTRANTE (statique, show/hide via JS) ── --}}
<div id="fd2OfferOverlay" class="fd2-offer-overlay" style="display:none;"></div>
<div id="fd2OfferPanel"   class="fd2-offer-panel"   style="display:none;">
    <div class="fd2-offer-head">
        <div class="fd2-offer-icon"><i class="fas fa-motorcycle"></i></div>
        <div>
            <div class="fd2-offer-title">Nouvelle mission</div>
            <div class="fd2-offer-sub" id="fd2OfferSub">—</div>
        </div>
    </div>
    <div class="fd2-countdown-wrap">
        <div class="fd2-countdown-bar" id="fd2CountdownBar"></div>
    </div>
    <div class="fd2-offer-timer-row">
        <i class="fas fa-clock"></i>
        Expire dans <strong id="fd2OfferCd">--</strong>s
    </div>
    <div class="fd2-offer-actions">
        <button id="fd2BtnAccept"  class="fd2-offer-btn accept"  onclick="acceptOffer()">
            <i class="fas fa-check"></i> Accepter
        </button>
        <button id="fd2BtnDecline" class="fd2-offer-btn decline" onclick="declineOffer()">
            <i class="fas fa-xmark"></i> Refuser
        </button>
    </div>
</div>

@endsection

@section('script')
<script>
function drvToggleDetails(id) {
    var d = document.getElementById('details-' + id);
    var b = document.getElementById('exp-btn-' + id);
    if (!d) return;
    var open = d.classList.toggle('open');
    if (b) b.classList.toggle('open', open);
}
function drvOpenPanel(panelId) {
    var p = document.getElementById(panelId);
    if (p) p.classList.toggle('open');
}
function drvClosePanel(panelId) {
    var p = document.getElementById(panelId);
    if (p) p.classList.remove('open');
}
function drvOpenIncident(id) {
    document.getElementById('bs-overlay-' + id).classList.add('open');
    document.getElementById('bs-panel-' + id).classList.add('open');
    document.body.style.overflow = 'hidden';
}
function drvCloseIncident(id) {
    document.getElementById('bs-overlay-' + id).classList.remove('open');
    document.getElementById('bs-panel-' + id).classList.remove('open');
    document.body.style.overflow = '';
}
function drvZoneAlert(sel, key) {
    var el = document.getElementById('zone-alert-' + key);
    if (el) el.style.display = sel.value === 'zone_inaccessible' ? 'block' : 'none';
}

// Chat
function drvToggleChat(id) {
    var body = document.getElementById('chat-body-' + id);
    if (!body) return;
    var open = body.classList.toggle('open');
    if (open) {
        var msgs = document.getElementById('chat-msgs-' + id);
        if (msgs) msgs.scrollTop = msgs.scrollHeight;
        drvRefreshChat(id);
    }
}
function drvRefreshChat(id) {
    var msgs = document.getElementById('chat-msgs-' + id);
    if (!msgs) return;
    var url = msgs.dataset.refreshUrl;
    var role = msgs.dataset.role;
    if (!url) return;
    fetch(url, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' })
    .then(function(r) { return r.json(); })
    .then(function(d) {
        if (d.status && d.html) { msgs.innerHTML = d.html; msgs.scrollTop = msgs.scrollHeight; }
    }).catch(function() {});
}
function drvSendChat(e, url, id) {
    e.preventDefault();
    var input = document.getElementById('chat-input-' + id);
    var msg = (input.value || '').trim();
    if (!msg || !url) return false;
    input.disabled = true;
    fetch(url, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/x-www-form-urlencoded', 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        credentials: 'same-origin',
        body: 'message=' + encodeURIComponent(msg)
    })
    .then(function(r) { return r.json(); })
    .then(function(d) {
        input.value = '';
        input.disabled = false;
        drvRefreshChat(id);
    })
    .catch(function() { input.disabled = false; });
    return false;
}
// Auto-refresh chat toutes les 10s
setInterval(function() {
    document.querySelectorAll('.drv-chat-body.open').forEach(function(el) {
        var id = el.id.replace('chat-body-', '');
        drvRefreshChat(id);
    });
}, 10000);
</script>

<script>
(function () {
    var btn = document.getElementById('driverToggleBtn');
    if (!btn) return;
    function applyState(online) {
        btn.setAttribute('data-is-online', online ? '1' : '0');
        btn.className = 'drv-toggle-btn ' + (online ? 'online' : 'offline');
        document.getElementById('driverToggleBtnLabel').textContent = online ? 'En ligne' : 'Hors ligne';
        var badge = document.getElementById('sidebarStatusBadge');
        var label = document.getElementById('sidebarStatusLabel');
        if (badge) badge.className = 'bd-drv-status ' + (online ? 'online' : 'offline');
        if (label) label.textContent = online ? 'En ligne' : 'Hors ligne';
        // Hero zone 1 sync
        var hero    = document.getElementById('fd2Hero');
        var gpsDot  = document.getElementById('fd2GpsDot');
        var heroLbl = document.getElementById('fd2HeroToggleLabel');
        var heroIco = document.getElementById('fd2HeroToggleIcon');
        if (hero)    hero.className = 'fd2-hero ' + (online ? 'online' : 'offline');
        if (gpsDot)  gpsDot.style.display = online ? '' : 'none';
        if (heroLbl) heroLbl.textContent = online ? 'Passer hors ligne' : 'Passer en ligne';
        if (heroIco) heroIco.className = online ? 'fas fa-circle-pause' : 'fas fa-circle-play';
    }
    btn.addEventListener('click', function () {
        var isOnline = btn.getAttribute('data-is-online') === '1';
        var url = isOnline ? btn.getAttribute('data-url-offline') : btn.getAttribute('data-url-online');
        btn.disabled = true;
        fetch(url, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': btn.getAttribute('data-csrf'), 'Accept': 'application/json', 'Content-Type': 'application/json' },
            body: JSON.stringify({})
        })
        .then(function (r) { return r.json(); })
        .then(function (d) {
            if (d && d.status) applyState(!isOnline);
            else alert((d && d.message) || 'Erreur serveur');
            btn.disabled = false;
        })
        .catch(function () { btn.disabled = false; });
    });
})();
</script>

<script>
(function () {
    function ease(t) { return 1 - Math.pow(1 - t, 4); }
    function run(el, v) {
        var s = null;
        (function step(ts) {
            if (!s) s = ts;
            var p = Math.min((ts - s) / 800, 1);
            el.textContent = Math.round(ease(p) * v).toLocaleString('fr-FR') + (el.dataset.suffix || '');
            if (p < 1) requestAnimationFrame(step);
        })(performance.now());
    }
    document.querySelectorAll('[data-countup]').forEach(function (el) {
        var v = parseFloat(el.getAttribute('data-countup')) || 0;
        if (v > 0) run(el, Math.round(v));
    });
})();
</script>

<script>
(function () {
    var POLL_URL  = "{{ route('driver.deliveries.poll') }}";
    var POLL_MS   = 10000;
    var lastCount = null;
    var _ctx = null, _unlocked = false, _offerTimer = null, _offerId = null;
    var _offerDuration = null, _vibrated = false;

    ['click','touchstart'].forEach(function(e){ document.addEventListener(e, function(){ _unlocked = true; }, { once:true, passive:true }); });

    function beep() {
        if (!_unlocked) return;
        try {
            var C = window.AudioContext || window.webkitAudioContext;
            if (!C) return;
            if (!_ctx || _ctx.state === 'closed') _ctx = new C();
            if (_ctx.state === 'suspended') _ctx.resume();
            var o = _ctx.createOscillator(), g = _ctx.createGain();
            o.connect(g); g.connect(_ctx.destination);
            o.frequency.value = 660;
            var t = _ctx.currentTime;
            g.gain.setValueAtTime(.001,t); g.gain.exponentialRampToValueAtTime(.2,t+.01); g.gain.exponentialRampToValueAtTime(.001,t+.4);
            o.start(t); o.stop(t+.41);
        } catch(e){}
    }

    function showOffer(offer) {
        if (_offerId === offer.delivery_id) return;
        _offerId = offer.delivery_id;
        var exp = new Date(offer.expires_at).getTime();
        _offerDuration = Math.max(1, (exp - Date.now()) / 1000);
        _vibrated = false;

        document.getElementById('fd2OfferSub').textContent =
            (offer.restaurant_name || 'Restaurant') + ' · #' + offer.order_no;
        document.getElementById('fd2BtnAccept').disabled  = false;
        document.getElementById('fd2BtnDecline').disabled = false;
        document.getElementById('fd2BtnAccept').innerHTML = '<i class="fas fa-check"></i> Accepter';
        document.getElementById('fd2CountdownBar').style.width = '100%';
        document.getElementById('fd2OfferOverlay').style.display = '';
        document.getElementById('fd2OfferPanel').style.display   = '';

        beep();
        clearInterval(_offerTimer);
        _offerTimer = setInterval(function() {
            var remaining = Math.max(0, (exp - Date.now()) / 1000);
            document.getElementById('fd2OfferCd').textContent = Math.round(remaining);
            var bar = document.getElementById('fd2CountdownBar');
            if (bar) bar.style.width = ((remaining / _offerDuration) * 100) + '%';
            if (!_vibrated && remaining <= 10 && navigator.vibrate) {
                navigator.vibrate([200, 100, 200]);
                _vibrated = true;
            }
            if (remaining <= 0) { clearInterval(_offerTimer); hideOffer(); }
        }, 100);
    }
    function hideOffer() {
        clearInterval(_offerTimer); _offerId = null;
        document.getElementById('fd2OfferOverlay').style.display = 'none';
        document.getElementById('fd2OfferPanel').style.display   = 'none';
    }
    function acceptOffer() {
        var btn = document.getElementById('fd2BtnAccept');
        if (btn) { btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Acceptation...'; }
        fetch('{{ url("/driver/deliveries") }}/' + _offerId + '/offer/accept', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json', 'Content-Type': 'application/json' },
            credentials: 'same-origin'
        }).then(function(r) { return r.json(); }).then(function(d) {
            hideOffer();
            if (d.status) setTimeout(function() { window.location.reload(); }, 600);
            else alert(d.message || 'Mission non disponible.');
        }).catch(function() { window.location.reload(); });
    }
    function declineOffer() {
        var btn = document.getElementById('fd2BtnDecline');
        if (btn) btn.disabled = true;
        fetch('{{ url("/driver/deliveries") }}/' + _offerId + '/offer/decline', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json', 'Content-Type': 'application/json' },
            credentials: 'same-origin'
        }).then(function() { hideOffer(); }).catch(function() { hideOffer(); });
    }

    function poll() {
        fetch(POLL_URL, {headers:{'Accept':'application/json','X-Requested-With':'XMLHttpRequest'}, credentials:'same-origin', cache:'no-store'})
        .then(function(r){return r.ok?r.json():null;})
        .then(function(d){
            if (!d||!d.status) return;
            var count = parseInt(d.count)||0;
            if (d.pending_offer) showOffer(d.pending_offer);
            else if (!d.pending_offer && _offerId) hideOffer();
            if (lastCount!==null && count>lastCount) { beep(); setTimeout(function(){window.location.reload();},1500); }
            lastCount = count;
        }).catch(function(){});
    }
    poll();
    setInterval(poll, POLL_MS);
})();
</script>

<script>
function drvGeoSubmit(form) {
    var prefix = form.dataset.latPrefix;
    if (!prefix || !navigator.geolocation) { form.submit(); return; }
    navigator.geolocation.getCurrentPosition(
        function(pos){
            var lat = form.querySelector('input[name="'+prefix+'_latitude"]');
            var lng = form.querySelector('input[name="'+prefix+'_longitude"]');
            if (lat) lat.value = pos.coords.latitude;
            if (lng) lng.value = pos.coords.longitude;
            form.submit();
        },
        function(){ form.submit(); },
        { enableHighAccuracy:true, timeout:5000, maximumAge:30000 }
    );
}
document.addEventListener('DOMContentLoaded', function(){
    document.querySelectorAll('.delivery-action-form').forEach(function(form){
        form.addEventListener('submit', function(e){
            if (form.dataset.submitting==='1') return;
            e.preventDefault(); form.dataset.submitting = '1';
            drvGeoSubmit(form);
        });
    });
});
</script>

<script>
(function(){
    var GPS_URL = @json(route('driver.location.update'));
    var CSRF    = @json(csrf_token());
    var MIN_M = 30;
    var HEARTBEAT_MS = 45000;
    var _wid = null, _lat = null, _lng = null, _lastPos = null, _lastSentAt = 0, _heartbeatTimer = null, _on = {{ $driverIsOnline ? 'true' : 'false' }}, _send = false;
    var gpsBar = document.getElementById('gpsBar');
    var gpsLabel = document.getElementById('gpsLabel');

    function setGps(state, text) {
        if (gpsBar) gpsBar.className = 'drv-gps-bar ' + state;
        if (gpsLabel) gpsLabel.textContent = text;
    }

    function hdist(a,b,c,d){var e=(c-a)*Math.PI/180,f=(d-b)*Math.PI/180,g=Math.sin(e/2)*Math.sin(e/2)+Math.cos(a*Math.PI/180)*Math.cos(c*Math.PI/180)*Math.sin(f/2)*Math.sin(f/2);return 2*6371000*Math.atan2(Math.sqrt(g),Math.sqrt(1-g));}

    function send(pos, heartbeat){
        _lastPos = pos;
        var la=pos.coords.latitude,ln=pos.coords.longitude;
        var capturedAt = heartbeat ? new Date() : (pos.timestamp ? new Date(pos.timestamp) : new Date());
        var stationary = _lat!==null && hdist(_lat,_lng,la,ln)<MIN_M;
        if (stationary && Date.now() - _lastSentAt < HEARTBEAT_MS) {
            setGps('active', 'Position OK · ' + new Date().toLocaleTimeString('fr-FR',{hour:'2-digit',minute:'2-digit'}));
            return;
        }
        if (_send) return; _send=true;
        setGps('active', 'Envoi de la position...');
        fetch(GPS_URL,{method:'POST',headers:{'X-CSRF-TOKEN':CSRF,'Content-Type':'application/json','Accept':'application/json'},credentials:'same-origin',body:JSON.stringify({latitude:la,longitude:ln,accuracy:pos.coords.accuracy||null,heading:pos.coords.heading||null,speed:pos.coords.speed||null,recorded_at:capturedAt.toISOString()})})
        .then(function(r){return r.ok?r.json():null;})
        .then(function(d){
            if(d&&d.status){if(!d.stale){_lat=la;_lng=ln;_lastSentAt=Date.now();} setGps('active', d.stale?'Ancienne position ignorée':'Position envoyée · ' + new Date().toLocaleTimeString('fr-FR',{hour:'2-digit',minute:'2-digit'}));}
            else setGps('error', 'Erreur serveur · réessai en cours');
        })
        .catch(function(){ setGps('error', 'Connexion perdue · réessai...'); })
        .finally(function(){_send=false;});
    }

    function onGpsError(e) {
        if (e.code === e.PERMISSION_DENIED) setGps('error', 'Géolocalisation refusée — autorisez-la dans les paramètres du navigateur');
        else if (e.code === e.POSITION_UNAVAILABLE) setGps('error', 'Position indisponible — activez le GPS');
        else if (e.code !== e.TIMEOUT) { setGps('error', 'Erreur GPS · réessai...'); stop(); }
    }

    function start(){
        if(!navigator.geolocation)return;
        if(_wid===null){
            setGps('active', 'Localisation GPS en cours...');
            _wid=navigator.geolocation.watchPosition(send, onGpsError, {enableHighAccuracy:true,maximumAge:10000,timeout:20000});
        }
        if(_heartbeatTimer===null){
            _heartbeatTimer=setInterval(function(){
                if(_on&&_lastPos&&!_send&&Date.now()-_lastSentAt>=HEARTBEAT_MS) send(_lastPos, true);
            },5000);
        }
    }
    function stop(){
        if(_wid!==null){navigator.geolocation.clearWatch(_wid);_wid=null;}
        if(_heartbeatTimer!==null){clearInterval(_heartbeatTimer);_heartbeatTimer=null;}
        if(!_on) setGps('off', 'GPS inactif — passez en ligne pour activer');
    }

    if(_on) start();
    else setGps('off', 'GPS inactif — passez en ligne pour activer');

    var tb=document.getElementById('driverToggleBtn');
    if(tb){tb.addEventListener('click',function(){setTimeout(function(){
        _on=tb.getAttribute('data-is-online')==='1';
        if(_on) start(); else stop();
    },600);});}
    window.addEventListener('beforeunload',stop);
    document.addEventListener('visibilitychange',function(){if(document.hidden)stop();else if(_on)start();});
})();
</script>

<script>
(function(){
    var input = document.getElementById('drvSearchInput');
    if (!input) return;
    input.addEventListener('input', function() {
        var q = this.value.toLowerCase().trim();
        document.querySelectorAll('.drv-mission').forEach(function(card) {
            var text = card.textContent.toLowerCase();
            card.style.display = (!q || text.indexOf(q) !== -1) ? '' : 'none';
        });
    });
})();
</script>
@endsection
