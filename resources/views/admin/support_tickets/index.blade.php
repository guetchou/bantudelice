@extends('layouts.admin-modern')
@section('title', 'Tickets support | Admin')
@section('page_title', 'Tickets support')
@section('nav_active', 'support')
@section('style')
<style>
.bd-support-shell {
    display:grid;
    gap:.9rem;
    padding:24px;
}
.bd-support-band {
    display:grid;
    grid-template-columns:minmax(0,1.6fr) minmax(330px,.8fr);
    gap:1rem;
    padding:1.15rem 1.15rem 1.05rem;
    border-radius:22px;
    background:
        radial-gradient(circle at top right, rgba(220,38,38,.08), transparent 25%),
        linear-gradient(180deg, rgba(255,255,255,.96), rgba(247,244,238,.95));
    border:1px solid rgba(15,23,42,.08);
    box-shadow:0 16px 30px rgba(15,23,42,.05);
}
.bd-support-band__eyebrow {
    font-size:.7rem;
    font-weight:800;
    text-transform:uppercase;
    letter-spacing:.12em;
    color:var(--text-3);
    margin-bottom:.25rem;
}
.bd-support-band__title {
    font-family:var(--f-d);
    font-size:1.35rem;
    line-height:1.05;
    color:var(--text);
}
.bd-support-band__copy {
    margin-top:.35rem;
    max-width:820px;
    color:var(--text-2);
    font-size:.78rem;
    line-height:1.5;
}
.bd-support-metrics {
    display:grid;
    grid-template-columns:repeat(3, minmax(0, 1fr));
    gap:.7rem;
    min-width:360px;
}
.bd-support-metric {
    padding:.8rem .85rem;
    border-radius:16px;
    background:rgba(255,255,255,.82);
    border:1px solid rgba(15,23,42,.08);
}
.bd-support-metric strong {
    display:block;
    font-family:var(--f-d);
    font-size:1.25rem;
    line-height:1;
    color:var(--text);
}
.bd-support-metric span {
    display:block;
    margin-top:.2rem;
    font-size:.66rem;
    text-transform:uppercase;
    letter-spacing:.08em;
    color:var(--text-3);
    font-weight:700;
}
.bd-support-desc {
    display: block;
    margin-top: .2rem;
    color: var(--text-3);
    white-space: normal;
    line-height: 1.45;
}
.bd-support-filter-grid {
    display:grid;
    grid-template-columns:repeat(4, minmax(0, 1fr));
    gap:.6rem;
    margin-bottom:14px;
}
.bd-support-badge {
    display:inline-flex;
    align-items:center;
    gap:.35rem;
    min-height:28px;
    padding:0 .65rem;
    border-radius:999px;
    font-size:.67rem;
    font-weight:800;
    text-transform:uppercase;
    letter-spacing:.05em;
    background:var(--bg-4);
    color:var(--text-2);
}
.bd-support-badge--high,
.bd-support-badge--critical {
    background:rgba(239,68,68,.1);
    color:#b91c1c;
}
.bd-support-badge--open,
.bd-support-badge--pending_review,
.bd-support-badge--pending_refund,
.bd-support-badge--pending_redelivery {
    background:rgba(245,158,11,.12);
    color:#b45309;
}
.bd-support-badge--resolved,
.bd-support-badge--closed {
    background:rgba(34,197,94,.12);
    color:#15803d;
}
.bd-support-card {
    border:1px solid rgba(15,23,42,.08) !important;
    border-radius:20px !important;
    box-shadow:0 14px 30px rgba(15,23,42,.05) !important;
    overflow:hidden;
    background:rgba(255,255,255,.88) !important;
}
.bd-support-filter-input { padding:8px 12px; border:1px solid #d1d5db; border-radius:6px; font-size:13px; width:100%; box-sizing:border-box; }
.bd-support-filter-actions { display:flex; gap:8px; }
.bd-support-btn-filter { display:inline-flex; align-items:center; justify-content:center; flex:1; padding:8px 12px; background:#1f2937; color:#fff; border:none; border-radius:6px; font-size:13px; font-weight:600; cursor:pointer; text-decoration:none; }
.bd-support-btn-reset { display:inline-flex; align-items:center; justify-content:center; flex:1; padding:8px 12px; border:1px solid #d1d5db; border-radius:6px; font-size:13px; font-weight:600; color:#374151; background:#fff; text-decoration:none; }
.bd-support-card__header { padding:14px 20px; border-bottom:1px solid #f3f4f6; background:#fff; }
.bd-support-card__title { margin:0; font-size:14px; font-weight:700; color:#111827; }
.bd-support-card__sub { font-size:12px; color:#9ca3af; margin-top:2px; }
.bd-support-table-wrap { overflow-x:auto; }
.bd-support-table { width:100%; border-collapse:collapse; font-size:13px; }
.bd-support-table thead th { padding:9px 14px; font-size:10px; font-weight:700; letter-spacing:.06em; text-transform:uppercase; color:#9ca3af; border-bottom:1px solid #f3f4f6; background:#f9fafb; text-align:left; }
.bd-support-table tbody td { padding:10px 14px; color:#374151; border-bottom:1px solid #f3f4f6; vertical-align:top; }
.bd-support-table tbody tr:last-child td { border-bottom:none; }
.bd-support-resolution { display:flex; gap:8px; align-items:center; }
.bd-support-select { padding:6px 10px; border:1px solid #d1d5db; border-radius:5px; font-size:12px; min-width:140px; }
.bd-support-btn-apply { display:inline-flex; align-items:center; padding:6px 12px; background:#1e3a5f; color:#fff; border:none; border-radius:5px; font-size:12px; font-weight:600; cursor:pointer; }
.bd-support-success { padding:10px 14px; background:#f0fdf4; border:1px solid #bbf7d0; border-radius:8px; font-size:13px; color:#166534; margin-bottom:12px; }
@media (max-width: 1100px) {
    .bd-support-band { grid-template-columns:1fr; }
    .bd-support-metrics,
    .bd-support-filter-grid { grid-template-columns:1fr; min-width:0; }
}
</style>
@endsection
@section('content')
<div class="bd-support-shell">
    <section class="bd-support-band">
        <div>
            <div class="bd-support-band__eyebrow">Support</div>
            <div class="bd-support-band__title">Files tickets, litiges et remboursements</div>
            <div class="bd-support-band__copy">La lecture doit remonter d'abord les tickets ouverts, les priorités hautes et les files qui demandent une décision. Le tableau reste l'outil principal.</div>
        </div>
        <div class="bd-support-metrics">
            <div class="bd-support-metric">
                <strong>{{ number_format($summary['open'], 0, ',', ' ') }}</strong>
                <span>Ouverts</span>
            </div>
            <div class="bd-support-metric">
                <strong>{{ number_format($summary['high_priority'], 0, ',', ' ') }}</strong>
                <span>Prioritaires</span>
            </div>
            <div class="bd-support-metric">
                <strong>{{ number_format($summary['resolved'], 0, ',', ' ') }}</strong>
                <span>Résolus / clos</span>
            </div>
        </div>
    </section>

    <div class="bd-support-card">
        <div class="bd-support-card__header">
            <h3 class="bd-support-card__title">Tickets support</h3>
            <div class="bd-support-card__sub">Incidents, remboursements, redélivrances, arbitrages et clôtures</div>
        </div>
        <div style="padding:16px 20px;">
            @if(session('success'))
                <div class="bd-support-success">{{ session('success') }}</div>
            @endif
            <form class="bd-support-filter-grid" method="GET">
                <input class="bd-support-filter-input" name="status" value="{{ request('status') }}" placeholder="Statut">
                <input class="bd-support-filter-input" name="module" value="{{ request('module') }}" placeholder="Module">
                <input class="bd-support-filter-input" name="priority" value="{{ request('priority') }}" placeholder="Priorité">
                <div class="bd-support-filter-actions">
                    <button type="submit" class="bd-support-btn-filter">Filtrer</button>
                    <a href="{{ route('admin.support-tickets.index') }}" class="bd-support-btn-reset">Reset</a>
                </div>
            </form>
            <div class="bd-support-table-wrap">
                <table class="bd-support-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Module</th>
                            <th>Catégorie</th>
                            <th>Titre</th>
                            <th>Statut</th>
                            <th>Priorité</th>
                            <th>Activité</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($tickets as $ticket)
                        <tr>
                            <td>{{ $ticket->id }}</td>
                            <td>{{ $ticket->module }}</td>
                            <td>{{ $ticket->category }}</td>
                            <td>
                                <strong>{{ $ticket->title }}</strong>
                                <span class="bd-support-desc">{{ $ticket->description ?: 'Aucune description fournie.' }}</span>
                            </td>
                            <td><span class="bd-support-badge bd-support-badge--{{ \Illuminate\Support\Str::slug((string) $ticket->status, '_') }}">{{ $ticket->status }}</span></td>
                            <td><span class="bd-support-badge bd-support-badge--{{ \Illuminate\Support\Str::slug((string) $ticket->priority, '_') }}">{{ $ticket->priority }}</span></td>
                            <td>{{ optional($ticket->last_activity_at)->format('d/m/Y H:i') }}</td>
                            <td>
                                <form method="POST" action="{{ route('admin.support-tickets.resolve', $ticket) }}">
                                    @csrf
                                    <div class="bd-support-resolution">
                                        <select name="resolution" class="bd-support-select">
                                            <option value="resolved">Resolved</option>
                                            <option value="closed">Closed</option>
                                            <option value="pending_review">Pending review</option>
                                            <option value="pending_refund">Pending refund</option>
                                            <option value="pending_redelivery">Pending redelivery</option>
                                        </select>
                                        <button type="submit" class="bd-support-btn-apply">Appliquer</button>
                                    </div>
                                </form>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div style="padding:14px 0;">
                {{ $tickets->links() }}
            </div>
        </div>
    </div>
</div>
@endsection
