@extends('layouts.admin-modern')
@section('title', 'Tableau de bord Transport | Kende')
@section('page_title', 'Transport — Dashboard')
@section('nav_active', 'transport')

@section('content')
<div class="bd-ops-shell" style="padding:24px;">
    <div class="adm-page-bar">
        <div class="adm-page-bar__left">
            <nav class="adm-page-bar__breadcrumb">
                <span>Kende</span><span class="sep">/</span><span>Transport</span>
            </nav>
            <h1 class="adm-page-bar__title">Dashboard Transport</h1>
        </div>
        <div class="adm-page-bar__right">
            <a href="{{ route('admin.transport.bookings.index') }}" class="ops-primary-btn">Reservations</a>
        </div>
    </div>

    <section class="bd-ops-stat-grid">
        <article class="bd-ops-stat-card is-orange">
            <span>Reservations</span>
            <strong>{{ $stats['total_bookings'] }}</strong>
            <small>Flux total</small>
        </article>
        <article class="bd-ops-stat-card is-lemon">
            <span>En attente</span>
            <strong>{{ $stats['pending_bookings'] }}</strong>
            <small>Dispatch a traiter</small>
        </article>
        <article class="bd-ops-stat-card is-soft">
            <span>Revenus</span>
            <strong>{{ number_format($stats['total_revenue'], 0, ',', ' ') }} FCFA</strong>
            <small>Transport cumule</small>
        </article>
        <article class="bd-ops-stat-card is-dark">
            <span>Vehicules</span>
            <strong>{{ \App\Domain\Transport\Models\TransportVehicle::count() }}</strong>
            <small>Parc disponible</small>
        </article>
    </section>

    <div class="trk-dash-grid">
        <div class="bd-ops-table-card">
            <div style="padding:18px 20px;border-bottom:1px solid #f3f4f6;">
                <div class="bd-ops-table-card__header">
                    <div>
                        <h3>Verticales transport</h3>
                        <p>Lecture directe par service pour suivre le volume, la disponibilité et l'accès au paramétrage.</p>
                    </div>
                </div>
            </div>
            <div style="padding:18px;">
                <div class="bd-transport-service-grid">
                    <article class="bd-transport-service-card">
                        <span class="bd-transport-service-card__icon"><i class="fas fa-taxi"></i></span>
                        <strong>Taxi</strong>
                        <p>{{ \App\Domain\Transport\Models\TransportBooking::where('type', 'taxi')->count() }} reservations</p>
                        <span class="bd-ops-status is-success">Actif</span>
                        <a href="{{ route('admin.transport.pricing.index') }}">Parametrer</a>
                    </article>
                    <article class="bd-transport-service-card">
                        <span class="bd-transport-service-card__icon"><i class="fas fa-users"></i></span>
                        <strong>Covoiturage</strong>
                        <p>{{ \App\Domain\Transport\Models\TransportBooking::where('type', 'carpool')->count() }} reservations</p>
                        <span class="bd-ops-status is-success">Actif</span>
                        <a href="{{ route('admin.transport.pricing.index') }}">Parametrer</a>
                    </article>
                    <article class="bd-transport-service-card">
                        <span class="bd-transport-service-card__icon"><i class="fas fa-shuttle-van"></i></span>
                        <strong>Location</strong>
                        <p>{{ \App\Domain\Transport\Models\TransportBooking::where('type', 'rental')->count() }} reservations</p>
                        <span class="bd-ops-status is-success">Actif</span>
                        <a href="{{ route('admin.transport.pricing.index') }}">Parametrer</a>
                    </article>
                </div>
            </div>
        </div>

        <div class="bd-ops-table-card">
            <div style="padding:18px 20px;border-bottom:1px solid #f3f4f6;">
                <div class="bd-ops-table-card__header">
                    <div>
                        <h3>Actions rapides</h3>
                        <p>Entrées utiles pour le dispatch, la flotte et la tarification.</p>
                    </div>
                </div>
            </div>
            <div style="padding:18px;">
                <div class="bd-transport-links">
                    <a href="{{ route('admin.transport.bookings.index') }}">Reservations</a>
                    <a href="{{ route('admin.transport.vehicles.index') }}">Vehicules</a>
                    <a href="{{ route('admin.transport.pricing.index') }}">Tarification</a>
                    <a href="{{ route('admin.transport.bookings.index', ['status' => 'requested']) }}">Demandes en attente</a>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .bd-ops-shell { display:grid; gap:14px; }
    .bd-ops-hero {
        display:grid; grid-template-columns:minmax(0,1.55fr) minmax(220px,.45fr); align-items:end; gap:20px;
        padding:20px 22px; border-radius:24px;
        background:
            radial-gradient(circle at top right, rgba(59,130,246,.18), transparent 26%),
            linear-gradient(135deg, #10233f 0%, #15365a 56%, #1f4d72 100%);
        color:#fff; box-shadow:0 18px 34px rgba(15,23,42,.16);
    }
    .bd-ops-hero__eyebrow { margin:0 0 8px; font-size:.72rem; text-transform:uppercase; letter-spacing:.18em; font-weight:800; color:rgba(191,219,254,.96); }
    .bd-ops-hero h1 { margin:0; color:#fff; font-size:clamp(1.45rem,3vw,2rem); font-weight:900; line-height:1.08; max-width:980px; }
    .bd-ops-hero p { margin:8px 0 0; max-width:980px; color:rgba(226,232,240,.82); line-height:1.55; }
    .bd-ops-stat-grid { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:12px; }
    .bd-ops-stat-card { padding:15px; border-radius:18px; background:rgba(255,255,255,.92); border:1px solid rgba(15,23,42,.08); box-shadow:0 12px 24px rgba(15,23,42,.05); }
    .bd-ops-stat-card span { display:block; color:#64748b; font-size:.74rem; font-weight:800; letter-spacing:.08em; text-transform:uppercase; }
    .bd-ops-stat-card strong { display:block; margin-top:6px; font-size:1.45rem; line-height:1; font-weight:900; color:#111827; }
    .bd-ops-stat-card small { display:block; margin-top:5px; color:#94a3b8; line-height:1.45; }
    .bd-ops-stat-card.is-orange strong { color:#1d4ed8; }
    .bd-ops-stat-card.is-lemon strong { color:#0f766e; }
    .bd-ops-stat-card.is-soft strong { color:#0f172a; }
    .bd-ops-stat-card.is-dark strong { color:#475569; }
    .bd-ops-table-card { border:1px solid rgba(15,23,42,.08) !important; border-radius:20px !important; box-shadow:0 14px 28px rgba(15,23,42,.05) !important; overflow:hidden; background:rgba(255,255,255,.88) !important; }
    .bd-ops-table-card__header { display:flex; align-items:flex-end; justify-content:space-between; gap:14px; }
    .bd-ops-table-card__header h3 { margin:0; color:#111827; font-size:1.05rem; font-weight:900; }
    .bd-ops-table-card__header p { margin:6px 0 0; color:#64748b; line-height:1.5; }
    .bd-ops-status { display:inline-flex; align-items:center; min-height:28px; padding:0 10px; border-radius:999px; font-size:.68rem; font-weight:800; text-transform:uppercase; letter-spacing:.06em; }
    .bd-ops-status.is-success { background:#dcfce7; color:#007836; }
    .trk-dash-grid { display:grid; grid-template-columns:minmax(0,1fr) 320px; gap:20px; }
    .bd-transport-service-grid { display:grid; grid-template-columns:repeat(3, minmax(0,1fr)); gap:12px; }
    .bd-transport-service-card {
        padding:14px; border-radius:18px; background:linear-gradient(180deg, #ffffff 0%, #f8fbff 100%); border:1px solid rgba(15,23,42,.08);
        display:grid; gap:8px;
    }
    .bd-transport-service-card__icon {
        width:40px; height:40px; border-radius:12px; display:inline-flex; align-items:center; justify-content:center;
        background:linear-gradient(135deg, #1d4ed8 0%, #2563eb 58%, #38bdf8 100%); color:#fff; font-size:.92rem;
    }
    .bd-transport-service-card strong { font-size:.95rem; color:#111827; }
    .bd-transport-service-card p { margin:0; color:#64748b; line-height:1.45; }
    .bd-transport-service-card a { color:#1d4ed8; font-weight:800; text-decoration:none; }
    .bd-transport-links { display:grid; gap:10px; }
    .bd-transport-links a {
        display:flex; align-items:center; min-height:42px; padding:0 12px; border-radius:12px;
        background:#f8fbff; border:1px solid rgba(15,23,42,.08); color:#0f172a; text-decoration:none; font-weight:800;
    }
    @media (max-width: 992px) {
        .bd-ops-hero, .bd-ops-table-card__header { flex-direction:column; align-items:flex-start; }
        .bd-ops-stat-grid { grid-template-columns:repeat(2,minmax(0,1fr)); }
        .bd-transport-service-grid { grid-template-columns:1fr; }
        .trk-dash-grid { grid-template-columns:1fr; }
    }
    @media (max-width: 576px) {
        .bd-ops-stat-grid { grid-template-columns:1fr; }
        .bd-ops-hero { padding:24px; }
    }
</style>
@endsection
