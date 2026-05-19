@extends('layouts.admin-modern')
@section('title', 'Tickets support | Admin')
@section('page_title', 'Tickets support')
@section('nav_active', 'support')
@section('style')
<style>
.bd-support-shell {
    display:grid;
    gap:.9rem;
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
.bd-support-card .card-header {
    padding: 1rem 1.05rem .85rem !important;
}
.bd-support-card .card-body {
    padding: .1rem 1.05rem 1rem !important;
}
.bd-support-card .table td {
    vertical-align: top !important;
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
@media (max-width: 1100px) {
    .bd-support-band { grid-template-columns:1fr; }
    .bd-support-metrics,
    .bd-support-filter-grid { grid-template-columns:1fr; min-width:0; }
}
</style>
@endsection
@section('content')
<div class="container-fluid bd-support-shell">
    <section class="bd-support-band">
        <div>
            <div class="bd-support-band__eyebrow">Support</div>
            <div class="bd-support-band__title">Files tickets, litiges et remboursements</div>
            <div class="bd-support-band__copy">La lecture doit remonter d’abord les tickets ouverts, les priorités hautes et les files qui demandent une décision. Le tableau reste l’outil principal.</div>
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

    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm border-0 bd-support-card">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <div>
                        <h3 class="card-title mb-0">Tickets support</h3>
                        <small class="text-muted">Incidents, remboursements, redélivrances, arbitrages et clôtures</small>
                    </div>
                </div>
                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success">{{ session('success') }}</div>
                    @endif
                    <form class="bd-support-filter-grid mb-3" method="GET">
                        <div><input class="form-control" name="status" value="{{ request('status') }}" placeholder="Statut"></div>
                        <div><input class="form-control" name="module" value="{{ request('module') }}" placeholder="Module"></div>
                        <div><input class="form-control" name="priority" value="{{ request('priority') }}" placeholder="Priorité"></div>
                        <div class="d-flex" style="gap:.55rem;">
                            <button class="btn btn-dark btn-block">Filtrer</button>
                            <a href="{{ route('admin.support-tickets.index') }}" class="btn btn-default btn-block">Reset</a>
                        </div>
                    </form>
                    <div class="table-responsive">
                        <table class="table table-striped">
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
                                            <strong>{{ $ticket->title }}</strong><br>
                                            <small class="bd-support-desc">{{ $ticket->description ?: 'Aucune description fournie.' }}</small>
                                        </td>
                                        <td><span class="bd-support-badge bd-support-badge--{{ \Illuminate\Support\Str::slug((string) $ticket->status, '_') }}">{{ $ticket->status }}</span></td>
                                        <td><span class="bd-support-badge bd-support-badge--{{ \Illuminate\Support\Str::slug((string) $ticket->priority, '_') }}">{{ $ticket->priority }}</span></td>
                                        <td>{{ optional($ticket->last_activity_at)->format('d/m/Y H:i') }}</td>
                                        <td>
                                            <form method="POST" action="{{ route('admin.support-tickets.resolve', $ticket) }}" class="d-flex gap-2">
                                                @csrf
                                                <select name="resolution" class="form-control form-control-sm" style="min-width:140px;">
                                                    <option value="resolved">Resolved</option>
                                                    <option value="closed">Closed</option>
                                                    <option value="pending_review">Pending review</option>
                                                    <option value="pending_refund">Pending refund</option>
                                                    <option value="pending_redelivery">Pending redelivery</option>
                                                </select>
                                                <button class="btn btn-sm btn-primary">Appliquer</button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    {{ $tickets->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
