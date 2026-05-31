@extends('layouts.admin-modern')
@section('style')@endsection
@section('title','Tous les restaurants | Food ops')
@section('page_title', 'Restaurants')
@section('nav_active', 'restaurants')

@php
    $restaurantsCollection = collect($restaurants instanceof \Illuminate\Contracts\Pagination\Paginator ? $restaurants->items() : $restaurants);
    $activeRestaurants = $restaurantsCollection->where('approved', 1)->count();
    $featuredRestaurants = $restaurantsCollection->where('featured', 1)->count();
    $inactiveRestaurants = $restaurantsCollection->where('approved', '!=', 1)->count();
@endphp

@section('content')
    <div class="bd-ops-page">
        @if(session()->has('alert'))
            <div style="padding:12px 16px;border-radius:10px;font-size:.85rem;font-weight:500;margin-bottom:4px;background:#fef2f2;color:#991b1b;border:1px solid #fecaca;">
                {{ session()->get('alert.message') }}
            </div>
        @endif

        <div class="bd-ops-shell">
            <div class="adm-page-bar">
                <div class="adm-page-bar__left">
                    <nav class="adm-page-bar__breadcrumb">
                        <span>Operations</span><span class="sep">/</span><span>Restaurants</span>
                    </nav>
                    <h1 class="adm-page-bar__title">Restaurants partenaires</h1>
                </div>
                <div class="adm-page-bar__right">
                    <a href="{{ route('restaurant.create') }}" class="ops-primary-btn">Ajouter un restaurant</a>
                </div>
            </div>

            <section class="bd-ops-stat-grid">
                <article class="bd-ops-stat-card is-orange">
                    <span>Restaurants</span>
                    <strong>{{ $restaurantsCollection->count() }}</strong>
                    <small>Sur la vue courante</small>
                </article>
                <article class="bd-ops-stat-card is-lemon">
                    <span>Actifs</span>
                    <strong>{{ $activeRestaurants }}</strong>
                    <small>Disponibles pour le catalogue</small>
                </article>
                <article class="bd-ops-stat-card is-dark">
                    <span>En vedette</span>
                    <strong>{{ $featuredRestaurants }}</strong>
                    <small>Mises en avant homepage</small>
                </article>
                <article class="bd-ops-stat-card is-soft">
                    <span>Inactifs</span>
                    <strong>{{ $inactiveRestaurants }}</strong>
                    <small>A revalider</small>
                </article>
            </section>
        </div>

        <div class="bd-ops-table-card">
            <div style="padding:18px 20px 16px;border-bottom:1px solid #f3f4f6;">
                <div class="bd-ops-table-card__header">
                    <div>
                        <h3>Reseau restaurants</h3>
                        <p>Activez, mettez en avant et ouvrez les fiches sans rester dans un tableau brut.</p>
                    </div>
                </div>
            </div>
            <div style="overflow-x:auto;">
                <table class="bd-ops-table" id="rst-table">
                    <thead>
                    <tr>
                        <th>#</th>
                        <th>Restaurant</th>
                        <th>Email</th>
                        <th>Adresse</th>
                        <th>Telephone</th>
                        <th>Mise en avant</th>
                        <th>Statut</th>
                        <th style="text-align:right;">Action</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($restaurants as $index => $restaurant)
                        <tr>
                            <td>{{ ++$index }}</td>
                            <td>
                                <a href="{{ route('admin.restaurant.dashboard-preview', $restaurant->id) }}" class="bd-ops-restaurant-link" title="Ouvrir le dashboard restaurant">
                                    <strong>{{ $restaurant->name }}</strong>
                                    <small>Ouvrir le dashboard</small>
                                </a>
                            </td>
                            <td>{{ $restaurant->email }}</td>
                            <td>{{ $restaurant->address }}</td>
                            <td>{{ $restaurant->phone }}</td>
                            <td>
                                <form method="POST" action="{{ route('admin.change_restaurant_featured_status', $restaurant->id) }}">
                                    @csrf
                                    <button type="submit" style="background:none;border:0;padding:0;">
                                        <span class="bd-ops-status {{ $restaurant->featured ? 'is-success' : 'is-soft' }}">{{ $restaurant->featured ? 'En vedette' : 'Standard' }}</span>
                                    </button>
                                </form>
                            </td>
                            <td>
                                <form method="POST" action="{{ route('admin.change_restaurant_active_status', $restaurant->id) }}">
                                    @csrf
                                    <button type="submit" style="background:none;border:0;padding:0;">
                                        <span class="bd-ops-status {{ $restaurant->approved ? 'is-lemon' : 'is-danger' }}">{{ $restaurant->approved ? 'Actif' : 'Inactif' }}</span>
                                    </button>
                                </form>
                            </td>
                            <td style="text-align:right;">
                                <a href="{{ route('admin.restaurant.dashboard-preview', $restaurant->id) }}" class="bd-ops-action-btn" title="Dashboard">
                                    <i class="fas fa-tachometer-alt"></i>
                                </a>
                                <form action="{{ route('admin.impersonate.restaurant', $restaurant->id) }}" method="POST" style="display:inline;">
                                    @csrf
                                    <button type="submit" class="bd-ops-action-btn bd-ops-action-btn--green" title="Impersoner">
                                        <i class="fas fa-sign-in-alt"></i>
                                    </button>
                                </form>
                                <a href="{{ route('restaurant.show', $restaurant->id) }}" class="bd-ops-action-btn" title="Voir"><i class="fas fa-eye"></i></a>
                                <a href="{{ route('restaurant.edit', $restaurant->id) }}" class="bd-ops-action-btn" title="Modifier"><i class="fas fa-pen"></i></a>
                                <form action="{{ route('restaurant.destroy', $restaurant->id) }}" method="post" style="display:inline;" onsubmit="return confirm('Supprimer ce restaurant ?');">
                                    @csrf
                                    @method('delete')
                                    <button type="submit" class="bd-ops-action-btn bd-ops-action-btn--danger" title="Supprimer"><i class="fas fa-trash-alt"></i></button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

<style>
    .bd-ops-page { padding:24px; display:grid; gap:20px; }
    .bd-ops-shell { display:grid; gap:20px; }
    .bd-ops-hero {
        display:flex; align-items:flex-end; justify-content:space-between; gap:20px;
        padding:30px 34px; border-radius:34px;
        background:linear-gradient(135deg, #2b1708 0%, #c2410c 56%, #bef264 100%);
        color:#fff; box-shadow:0 22px 56px rgba(154,52,18,.24);
    }
    .bd-ops-hero__eyebrow { margin:0 0 8px; font-size:.78rem; text-transform:uppercase; letter-spacing:.18em; font-weight:800; color:rgba(254,249,195,.96); }
    .bd-ops-hero h1 { margin:0; color:#fff; font-size:clamp(2rem,4vw,3rem); font-weight:900; line-height:1.04; max-width:760px; }
    .bd-ops-hero p { margin:14px 0 0; max-width:720px; color:rgba(255,255,255,.84); line-height:1.8; }
    .bd-ops-stat-grid { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:18px; }
    .bd-ops-stat-card { padding:22px; border-radius:26px; background:#fff; border:1px solid rgba(249,115,22,.12); box-shadow:0 14px 34px rgba(245,158,11,.08); }
    .bd-ops-stat-card span { display:block; color:#78716c; font-size:.86rem; font-weight:700; }
    .bd-ops-stat-card strong { display:block; margin-top:10px; font-size:2rem; line-height:1; font-weight:900; color:#111827; }
    .bd-ops-stat-card small { display:block; margin-top:8px; color:#a8a29e; }
    .bd-ops-stat-card.is-orange strong { color:#c2410c; }
    .bd-ops-stat-card.is-lemon strong { color:#65a30d; }
    .bd-ops-stat-card.is-soft strong { color:#b45309; }
    .bd-ops-table-card { border:1px solid rgba(249,115,22,.12) !important; border-radius:28px !important; box-shadow:0 14px 36px rgba(245,158,11,.08) !important; overflow:hidden; }
    .bd-ops-table-card__header { display:flex; align-items:flex-end; justify-content:space-between; gap:18px; }
    .bd-ops-table-card__header h3 { margin:0; color:#111827; font-size:1.3rem; font-weight:900; }
    .bd-ops-table-card__header p { margin:8px 0 0; color:#78716c; line-height:1.7; }
    .bd-ops-status { display:inline-flex; align-items:center; min-height:34px; padding:0 12px; border-radius:999px; font-size:.78rem; font-weight:800; }
    .bd-ops-status.is-success { background:#dcfce7; color:#007836; }
    .bd-ops-status.is-lemon { background:#fef9c3; color:#4d7c0f; }
    .bd-ops-status.is-danger { background:#fee2e2; color:#b91c1c; }
    .bd-ops-status.is-soft { background:#f3f4f6; color:#374151; }
    .bd-ops-restaurant-link { display:inline-flex; flex-direction:column; gap:4px; color:#111827; text-decoration:none; }
    .bd-ops-restaurant-link strong { color:#111827; }
    .bd-ops-restaurant-link small { color:#9a3412; font-weight:700; letter-spacing:.01em; }
    .bd-ops-restaurant-link:hover strong, .bd-ops-restaurant-link:focus strong { color:#c2410c; }
    .bd-ops-table { width:100%; border-collapse:collapse; font-size:13px; }
    .bd-ops-table thead th { padding:9px 14px; font-size:10px; font-weight:700; letter-spacing:.06em; text-transform:uppercase; color:#9ca3af; border-bottom:1px solid #f3f4f6; background:#f9fafb; text-align:left; white-space:nowrap; }
    .bd-ops-table tbody tr { border-bottom:1px solid #f3f4f6; transition:background .1s; }
    .bd-ops-table tbody tr:last-child { border-bottom:none; }
    .bd-ops-table tbody tr:hover { background:#fafaf9; }
    .bd-ops-table td { padding:11px 14px; color:#374151; vertical-align:middle; }
    .bd-ops-action-btn { display:inline-flex; align-items:center; justify-content:center; width:30px; height:30px; border-radius:7px; border:1px solid #e5e7eb; background:#fff; color:#6b7280; cursor:pointer; font-size:12px; transition:.12s; text-decoration:none; }
    .bd-ops-action-btn:hover { border-color:#1e3a5f; color:#1e3a5f; }
    .bd-ops-action-btn--green:hover { border-color:#22c55e; color:#22c55e; }
    .bd-ops-action-btn--danger { color:#dc2626; border-color:rgba(239,68,68,.2); }
    .bd-ops-action-btn--danger:hover { background:rgba(239,68,68,.06); border-color:#dc2626; color:#dc2626; }
    @media (max-width: 992px) {
        .bd-ops-hero, .bd-ops-table-card__header { flex-direction:column; align-items:flex-start; }
        .bd-ops-stat-grid { grid-template-columns:repeat(2,minmax(0,1fr)); }
    }
    @media (max-width: 576px) {
        .bd-ops-stat-grid { grid-template-columns:1fr; }
        .bd-ops-hero { padding:24px; }
    }
</style>
@endsection
@section('script')
<script src="{{ asset('plugins/datatables/jquery.dataTables.js') }}"></script>
<script>
$(function () {
    $('#rst-table').DataTable({ pageLength: 25, order: [] });
});
</script>
@endsection
