@extends('layouts.admin-modern')
@section('style')
<link rel="stylesheet" href="{{asset('plugins/datatables-bs4/css/dataTables.bootstrap4.css')}}">
<link rel="stylesheet" href="{{asset('plugins/sweetalert2/sweetalert2.css')}}">
@endsection
@section('title','Tous les produits')
@section('page_title', 'Produits')
@section('nav_active', 'products')

@php
    $readyProducts = max(0, ($totalProducts ?? 0) - ($productsMissingMedia ?? 0) - ($productsWithExternalMedia ?? 0));
    $currentRestaurantId = $currentRestaurant->id ?? null;
    $currentRestaurantName = $currentRestaurant->name ?? null;
    $mediaFilterLinks = [
        ['key' => 'all', 'label' => 'Tout le catalogue', 'count' => $totalProducts ?? 0],
        ['key' => 'missing', 'label' => 'A enrichir', 'count' => $productsMissingMedia ?? 0],
        ['key' => 'ready', 'label' => 'Media local', 'count' => $readyProducts],
        ['key' => 'external', 'label' => 'URL externes', 'count' => $productsWithExternalMedia ?? 0],
    ];
@endphp

@section('content')
    <div class="content-header">
        @if(session()->has('alert'))
            <div class="alert alert-{{ session()->get('alert.type') }}">
                {{ session()->get('alert.message') }}
            </div>
        @endif
        <div class="container-fluid">
            <div class="bd-media-shell">
                <section class="bd-media-hero">
                    <div>
                        <p class="bd-media-hero__eyebrow">Enrichissement catalogue</p>
                        <h1>Pilotez les visuels produits depuis l’admin sans repasser par du SQL.</h1>
                        <p>Reperez les plats encore sur image par defaut, ouvrez leur fiche et remplacez-les directement depuis la mediathèque CMS.</p>
                    </div>
                    <div class="bd-media-hero__actions">
                        <a href="{{ route('admin.product.create') }}" class="btn btn-light">Ajouter un plat</a>
                    </div>
                </section>

                <section class="bd-media-stat-grid">
                    <article class="bd-media-stat-card is-orange">
                        <span>Total produits</span>
                        <strong>{{ $totalProducts }}</strong>
                        <small>Catalogue admin</small>
                    </article>
                    <article class="bd-media-stat-card is-danger">
                        <span>A enrichir</span>
                        <strong>{{ $productsMissingMedia }}</strong>
                        <small>Encore sur image par defaut</small>
                    </article>
                    <article class="bd-media-stat-card is-success">
                        <span>Media local</span>
                        <strong>{{ $readyProducts }}</strong>
                        <small>Copie locale issue du CMS ou upload</small>
                    </article>
                    <article class="bd-media-stat-card is-dark">
                        <span>Vedette sans media</span>
                        <strong>{{ $featuredProductsMissingMedia }}</strong>
                        <small>A traiter en priorite</small>
                    </article>
                </section>

                @if($currentRestaurant)
                    <section class="bd-media-focus-banner">
                        <div>
                            <span>Filtre actif</span>
                            <strong>{{ $currentRestaurantName }}</strong>
                            <small>{{ $productsMissingMedia }} produit(s) encore sur image par defaut dans ce restaurant.</small>
                        </div>
                        <a href="{{ route('total.pro', ['media_status' => $mediaStatus]) }}" class="btn btn-dark btn-sm">Retirer le filtre restaurant</a>
                    </section>
                @endif
            </div>
        </div><!-- /.container-fluid -->
    </div>
    <section class="content">
        <div class="container-fluid">
            @if(($restaurantBacklog ?? collect())->isNotEmpty())
                <div class="card bd-media-backlog-card">
                    <div class="card-header border-0">
                        <div class="bd-media-table-card__header">
                            <div>
                                <h3>Traitement par restaurant</h3>
                                <p>Ouvrez un backlog cible par partenaire pour enrichir les visuels par lot et prioriser les fiches vedette.</p>
                            </div>
                            <div class="bd-media-inline-kpi">
                                <span>{{ $restaurantsWithMissingMedia }}</span>
                                <small>restaurant(s) avec produits sans media</small>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="bd-media-restaurant-grid">
                            @foreach(($restaurantBacklog ?? collect())->take(8) as $restaurantItem)
                                <a
                                    href="{{ route('total.pro', ['media_status' => 'missing', 'restaurant_id' => $restaurantItem['restaurant_id']]) }}"
                                    class="bd-media-restaurant-card {{ $currentRestaurantId === $restaurantItem['restaurant_id'] ? 'is-active' : '' }}">
                                    <span>{{ $restaurantItem['restaurant_name'] }}</span>
                                    <strong>{{ $restaurantItem['missing_count'] }} media manquant(s)</strong>
                                    <small>{{ $restaurantItem['featured_missing_count'] }} produit(s) vedette a corriger</small>
                                </a>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif

            <div class="card bd-media-table-card">
                <div class="card-header border-0">
                    <div class="bd-media-table-card__header">
                        <div>
                            <h3>Backlog visuels produits</h3>
                            <p>Filtrez le catalogue pour ne traiter que les plats encore sur image par defaut, puis ouvrez directement la fiche a enrichir.</p>
                        </div>
                        <div class="bd-media-filter-row">
                            @foreach($mediaFilterLinks as $filter)
                                <a
                                    href="{{ route('total.pro', array_filter([
                                        'media_status' => $filter['key'],
                                        'restaurant_id' => $currentRestaurantId,
                                    ])) }}"
                                    class="bd-media-filter-chip {{ ($mediaStatus ?? 'all') === $filter['key'] ? 'is-active' : '' }}">
                                    <span>{{ $filter['label'] }}</span>
                                    <strong>{{ $filter['count'] }}</strong>
                                </a>
                            @endforeach
                        </div>
                    </div>
                </div>
                <div class="card-body table-responsive p-0">
                    <table class="table table-hover text-nowrap" id="example1">
                        <thead>
                        <tr>
                            <th>#</th>
                            <th>Image</th>
                            <th>Plat</th>
                            <th>Media</th>
                            <th>Catégorie</th>
                            <th>Restaurant</th>
                            <th>Prix</th>
                            <th>Vedette</th>
                            <th class="text-right">Action</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($products as $index => $product)
                            @php
                                $img = $product->image ?? null;
                                $imgSrc = method_exists($product, 'publicImageUrl')
                                    ? $product->publicImageUrl()
                                    : ($img
                                        ? (strpos($img, 'http') === 0 ? $img : asset('images/product_images/' . $img))
                                        : asset('images/product_images/default-food.jpg'));
                                $isMissingMedia = empty($img) || $img === 'default-food.jpg';
                                $isExternalMedia = !$isMissingMedia && strpos((string) $img, 'http') === 0;
                                $mediaLabel = $isMissingMedia ? 'Image par defaut' : ($isExternalMedia ? 'URL externe' : 'Media local');
                                $mediaClass = $isMissingMedia ? 'is-danger' : ($isExternalMedia ? 'is-soft' : 'is-success');
                            @endphp
                            <tr>
                                <td>{{ ++$index }}</td>
                                <td>
                                    <img src="{{ $imgSrc }}" style="width:100px; height:80px; object-fit:cover; border-radius:14px;" onerror="this.src='{{ asset('images/product_images/default-food.jpg') }}'">
                                </td>
                                <td>
                                    <div class="bd-media-product-cell">
                                        <strong>{{ $product->name }}</strong>
                                        <small>#{{ $product->id }}</small>
                                    </div>
                                </td>
                                <td><span class="bd-media-status {{ $mediaClass }}">{{ $mediaLabel }}</span></td>
                                <td>{{ optional($product->categories)->name ?: 'N/A' }}</td>
                                <td>{{ optional($product->restaurants)->name ?: 'N/A' }}</td>
                                <td>{{ number_format((float) $product->price, 0, ',', ' ') }} FCFA</td>
                                <td><span class="bd-media-status {{ $product->featured ? 'is-lemon' : 'is-soft' }}">{{ $product->featured ? 'En vedette' : 'Standard' }}</span></td>
                                <td class="text-right">
                                    <a href="{{ route('admin.product.edit', $product->id) }}{{ !empty(array_filter(['media_status' => $mediaStatus ?? null, 'restaurant_id' => $currentRestaurantId])) ? '?' . http_build_query(array_filter(['media_status' => $mediaStatus ?? null, 'restaurant_id' => $currentRestaurantId])) : '' }}" class="btn btn-sm btn-outline-primary">
                                        {{ $isMissingMedia ? 'Enrichir le media' : 'Modifier' }}
                                    </a>
                                    <form action="{{ route('admin.product.destroy', $product->id) }}" method="post" style="display:inline;" onsubmit="return confirm('Voulez-vous vraiment supprimer ce plat ?');">
                                        @csrf
                                        @method('DELETE')
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
    .bd-media-shell { display:grid; gap:20px; }
    .bd-media-hero {
        display:flex; align-items:flex-end; justify-content:space-between; gap:20px;
        padding:30px 34px; border-radius:34px;
        background:linear-gradient(135deg, #14532d 0%, #009543 55%, #facc15 100%);
        color:#fff; box-shadow:0 22px 56px rgba(0, 149, 67, .22);
    }
    .bd-media-hero__eyebrow { margin:0 0 8px; font-size:.78rem; text-transform:uppercase; letter-spacing:.18em; font-weight:800; color:rgba(254,249,195,.96); }
    .bd-media-hero h1 { margin:0; color:#fff; font-size:clamp(2rem,4vw,3rem); font-weight:900; line-height:1.04; max-width:760px; }
    .bd-media-hero p { margin:14px 0 0; max-width:760px; color:rgba(255,255,255,.88); line-height:1.8; }
    .bd-media-stat-grid { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:18px; }
    .bd-media-stat-card { padding:22px; border-radius:26px; background:#fff; border:1px solid rgba(20,83,45,.08); box-shadow:0 14px 36px rgba(21,128,61,.08); }
    .bd-media-stat-card span { display:block; color:#6b7280; font-size:.86rem; font-weight:700; }
    .bd-media-stat-card strong { display:block; margin-top:10px; font-size:2rem; line-height:1; font-weight:900; color:#111827; }
    .bd-media-stat-card small { display:block; margin-top:8px; color:#9ca3af; }
    .bd-media-stat-card.is-orange strong { color:#b45309; }
    .bd-media-stat-card.is-danger strong { color:#b91c1c; }
    .bd-media-stat-card.is-success strong { color:#15803d; }
    .bd-media-stat-card.is-dark strong { color:#1f2937; }
    .bd-media-table-card { border:1px solid rgba(20,83,45,.08) !important; border-radius:28px !important; box-shadow:0 14px 36px rgba(21,128,61,.08) !important; overflow:hidden; }
    .bd-media-backlog-card { margin-bottom:20px; border:1px solid rgba(20,83,45,.08) !important; border-radius:28px !important; box-shadow:0 14px 36px rgba(21,128,61,.08) !important; overflow:hidden; }
    .bd-media-table-card__header { display:flex; align-items:flex-end; justify-content:space-between; gap:18px; }
    .bd-media-table-card__header h3 { margin:0; color:#111827; font-size:1.3rem; font-weight:900; }
    .bd-media-table-card__header p { margin:8px 0 0; color:#6b7280; line-height:1.7; max-width:720px; }
    .bd-media-focus-banner {
        display:flex; align-items:center; justify-content:space-between; gap:18px;
        padding:18px 22px; border-radius:24px; background:#111827; color:#fff;
    }
    .bd-media-focus-banner span { display:block; font-size:.72rem; text-transform:uppercase; letter-spacing:.12em; color:#93c5fd; font-weight:800; }
    .bd-media-focus-banner strong { display:block; margin-top:8px; font-size:1.2rem; }
    .bd-media-focus-banner small { display:block; margin-top:6px; color:rgba(255,255,255,.76); }
    .bd-media-inline-kpi { text-align:right; }
    .bd-media-inline-kpi span { display:block; font-size:1.6rem; font-weight:900; color:#14532d; line-height:1; }
    .bd-media-inline-kpi small { display:block; margin-top:6px; color:#6b7280; }
    .bd-media-restaurant-grid { display:grid; grid-template-columns:repeat(4, minmax(0, 1fr)); gap:14px; }
    .bd-media-restaurant-card {
        display:grid; gap:8px; padding:18px; border-radius:22px;
        border:1px solid #d1fae5; background:#f8fafc; text-decoration:none;
        box-shadow:0 10px 24px rgba(15,23,42,.04);
    }
    .bd-media-restaurant-card span { color:#166534; font-size:.72rem; font-weight:800; letter-spacing:.08em; text-transform:uppercase; }
    .bd-media-restaurant-card strong { color:#111827; font-size:1rem; line-height:1.4; }
    .bd-media-restaurant-card small { color:#6b7280; line-height:1.45; }
    .bd-media-restaurant-card.is-active { background:#14532d; border-color:#14532d; }
    .bd-media-restaurant-card.is-active span,
    .bd-media-restaurant-card.is-active strong,
    .bd-media-restaurant-card.is-active small { color:#fff; }
    .bd-media-filter-row { display:flex; flex-wrap:wrap; gap:10px; }
    .bd-media-filter-chip {
        display:inline-flex; align-items:center; gap:10px; min-height:42px; padding:0 14px;
        border-radius:999px; border:1px solid #d1fae5; background:#f0fdf4; color:#166534; text-decoration:none;
        font-size:.82rem; font-weight:800;
    }
    .bd-media-filter-chip strong { font-size:.88rem; }
    .bd-media-filter-chip.is-active { background:#14532d; border-color:#14532d; color:#fff; }
    .bd-media-status { display:inline-flex; align-items:center; min-height:34px; padding:0 12px; border-radius:999px; font-size:.78rem; font-weight:800; }
    .bd-media-status.is-success { background:#dcfce7; color:#166534; }
    .bd-media-status.is-danger { background:#fee2e2; color:#b91c1c; }
    .bd-media-status.is-soft { background:#f3f4f6; color:#374151; }
    .bd-media-status.is-lemon { background:#fef9c3; color:#854d0e; }
    .bd-media-product-cell { display:inline-flex; flex-direction:column; gap:4px; }
    .bd-media-product-cell strong { color:#111827; }
    .bd-media-product-cell small { color:#6b7280; font-weight:700; }
    @media (max-width: 992px) {
        .bd-media-hero, .bd-media-table-card__header { flex-direction:column; align-items:flex-start; }
        .bd-media-stat-grid { grid-template-columns:repeat(2,minmax(0,1fr)); }
        .bd-media-restaurant-grid { grid-template-columns:repeat(2,minmax(0,1fr)); }
        .bd-media-focus-banner { flex-direction:column; align-items:flex-start; }
    }
    @media (max-width: 576px) {
        .bd-media-stat-grid { grid-template-columns:1fr; }
        .bd-media-restaurant-grid { grid-template-columns:1fr; }
        .bd-media-hero { padding:24px; }
    }
</style>
@endsection
@section('script')
<script src="{{asset('plugins/datatables/jquery.dataTables.js')}}"></script>
<script src="{{asset('plugins/datatables-bs4/js/dataTables.bootstrap4.js')}}"></script>
<script>
    $(function () {
      $("#example1").DataTable({
          language: window.bdAdminDataTableLanguage,
          pageLength: 25,
          order: [[0, 'asc']]
      });
  
    });
  </script>
@endsection
