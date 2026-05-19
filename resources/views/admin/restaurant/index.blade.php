@extends('layouts.admin-modern')
@section('style')
<link rel="stylesheet" href="{{asset('plugins/datatables-bs4/css/dataTables.bootstrap4.css')}}">
<link rel="stylesheet" href="{{asset('plugins/sweetalert2/sweetalert2.css')}}">
@endsection
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
    <div class="content-header">
        <div class="container-fluid">
            @if(session()->has('alert'))
                <div class="alert alert-{{ session()->get('alert.type') }}">
                    {{ session()->get('alert.message') }}
                </div>
            @endif

            <div class="bd-ops-shell">
                <section class="bd-ops-hero">
                    <div>
                        <p class="bd-ops-hero__eyebrow">Partenaires</p>
                        <h1>Gardez les restaurants visibles, activables et bien relies a la home.</h1>
                        <p>Une vue plus nette pour suivre l'etat des partenaires, les mises en avant et les acces de gestion.</p>
                    </div>
                    <div class="bd-ops-hero__actions">
                        <a href="{{ route('restaurant.create') }}" class="btn btn-light">Ajouter un restaurant</a>
                    </div>
                </section>

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
        </div>
    </div>
    <section class="content">
        <div class="container-fluid">
            <div class="card bd-ops-table-card">
                <div class="card-header border-0">
                    <div class="bd-ops-table-card__header">
                        <div>
                            <h3>Reseau restaurants</h3>
                            <p>Activez, mettez en avant et ouvrez les fiches sans rester dans un tableau brut.</p>
                        </div>
                    </div>
                </div>
                <div class="card-body table-responsive p-0">
                    <table class="table table-hover text-nowrap" id="example1">
                        <thead>
                        <tr>
                            <th>#</th>
                            <th>Restaurant</th>
                            <th>Email</th>
                            <th>Adresse</th>
                            <th>Telephone</th>
                            <th>Mise en avant</th>
                            <th>Statut</th>
                            <th class="text-right">Action</th>
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
                                <td class="text-right">
                                    <a href="{{ route('admin.restaurant.dashboard-preview', $restaurant->id) }}" class="btn btn-sm btn-outline-dark" title="Ouvrir le dashboard restaurant">
                                        Voir le dashboard
                                    </a>
                                    <form action="{{ route('admin.impersonate.restaurant', $restaurant->id) }}" method="POST" style="display:inline;">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-outline-success" title="Ouvrir le dashboard restaurant en session déléguée">
                                            Impersoner
                                        </button>
                                    </form>
                                    <a href="{{ route('restaurant.show', $restaurant->id) }}" class="btn btn-sm btn-outline-primary" title="Voir les détails">Voir</a>
                                    <a href="{{ route('restaurant.edit', $restaurant->id) }}" class="btn btn-sm btn-outline-secondary" title="Modifier">Modifier</a>
                                    <form action="{{ route('restaurant.destroy', $restaurant->id) }}" method="post" style="display:inline;" onsubmit="return confirm('Voulez-vous vraiment supprimer ce restaurant ?');">
                                        @csrf
                                        @method('delete')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">Supprimer</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>

<style>
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
<script src="{{asset('plugins/datatables/jquery.dataTables.js')}}"></script>
<script src="{{asset('plugins/datatables-bs4/js/dataTables.bootstrap4.js')}}"></script>
<script>
    $(function () {
      $("#example1").DataTable();
    });
</script>
@endsection
