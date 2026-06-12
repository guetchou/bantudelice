@extends('layouts.admin-modern')
@section('title',$restaurant->name.' | BantuDelice')
@section('page_title', 'Détail restaurant')
@section('nav_active', 'restaurants')
@section('style')
<link rel="stylesheet" type="text/css" href="{{asset('slick/slick.css')}}">
<link rel="stylesheet" type="text/css" href="{{asset('slick/slick-theme.css')}}">
<style>
.rst-page { padding:24px; }
.rst-alert { padding:12px 16px; border-radius:8px; font-size:13px; font-weight:500; border:1px solid transparent; margin-bottom:16px; }
.rst-alert--success { background:#f0fdf4; color:#166534; border-color:#bbf7d0; }
.rst-alert--danger  { background:#fef2f2; color:#991b1b; border-color:#fecaca; }
.rst-alert--warning { background:#fefce8; color:#854d0e; border-color:#fde68a; }

/* Hero */
.rst-hero { border-radius:10px; overflow:hidden; background-size:cover; background-repeat:no-repeat; background-position:center; padding:28px 24px; display:flex; align-items:flex-start; gap:20px; margin-bottom:16px; flex-wrap:wrap; }
.rst-hero__logo { width:90px; height:90px; border-radius:8px; object-fit:cover; border:2px solid rgba(255,255,255,.5); flex-shrink:0; }
.rst-hero__info { flex:1; min-width:160px; }
.rst-hero__name { font-size:22px; font-weight:700; color:#fff; margin:0 0 4px; text-shadow:0 1px 4px rgba(0,0,0,.4); }
.rst-hero__slogan { font-size:14px; color:rgba(255,255,255,.9); margin:0 0 4px; }
.rst-hero__address { font-size:13px; color:rgba(255,255,255,.85); margin:0 0 8px; }
.rst-hero__actions { margin-left:auto; }
.rst-btn-edit { display:inline-flex; align-items:center; gap:6px; padding:7px 14px; background:rgba(255,255,255,.15); color:#fff; border:1px solid rgba(255,255,255,.4); border-radius:6px; font-size:13px; font-weight:600; text-decoration:none; backdrop-filter:blur(4px); transition:background .15s; }
.rst-btn-edit:hover { background:rgba(255,255,255,.3); color:#fff; text-decoration:none; }

/* Pause banner */
.rst-pause-banner { display:flex; align-items:center; justify-content:space-between; gap:12px; flex-wrap:wrap; padding:12px 16px; border-radius:8px; margin-bottom:16px; border-left:4px solid #e85d04; background:#fefce8; color:#854d0e; font-size:13px; }
.rst-active-bar { display:flex; align-items:center; gap:10px; flex-wrap:wrap; padding:10px 14px; background:#f8fafb; border-radius:8px; margin-bottom:16px; font-size:13px; }
.rst-active-bar__badge { display:inline-flex; align-items:center; gap:5px; padding:3px 10px; border-radius:999px; font-size:11px; font-weight:700; background:#f0fdf4; color:#166534; }
.rst-active-bar__muted { color:#6b7280; font-size:12px; }
.rst-active-bar__form { display:flex; align-items:center; gap:8px; margin-left:auto; flex-wrap:wrap; }
.rst-input-sm { padding:6px 10px; border:1px solid #d1d5db; border-radius:6px; font-size:12px; color:#111827; background:#fff; }
.rst-input-sm:focus { outline:none; border-color:#1e3a5f; }

/* Layout */
.rst-layout { display:grid; grid-template-columns:1fr 320px; gap:20px; align-items:start; }
@media(max-width:900px){ .rst-layout { grid-template-columns:1fr; } }

/* Card */
.rst-card { background:#fff; border:1px solid #e5e7eb; border-radius:10px; overflow:hidden; margin-bottom:20px; }
.rst-card__header { padding:14px 20px; border-bottom:1px solid #f3f4f6; }
.rst-card__title { font-size:14px; font-weight:700; color:#111827; margin:0; }
.rst-card__body { padding:20px; }

/* Stat grid */
.rst-stat-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(140px,1fr)); gap:12px; }
.rst-stat { background:#f9fafb; border:1px solid #f3f4f6; border-radius:8px; padding:14px 16px; text-align:center; }
.rst-stat__label { font-size:11px; color:#6b7280; font-weight:500; margin-bottom:4px; }
.rst-stat__value { font-size:18px; font-weight:700; color:#111827; font-variant-numeric:tabular-nums; }

/* Chart tabs */
.rst-tabs { display:flex; gap:6px; margin-bottom:12px; }
.rst-tab { padding:6px 14px; border-radius:6px; font-size:12px; font-weight:600; border:1px solid #d1d5db; color:#374151; background:#fff; cursor:pointer; transition:background .15s,color .15s; text-decoration:none; }
.rst-tab.active, .rst-tab:hover { background:#1e3a5f; color:#fff; border-color:#1e3a5f; text-decoration:none; }

/* Aside */
.rst-about-item { margin-bottom:14px; }
.rst-about-item:last-child { margin-bottom:0; }
.rst-about-label { font-size:12px; font-weight:700; color:#374151; display:flex; align-items:center; gap:6px; margin-bottom:4px; }
.rst-about-label i { color:#1e3a5f; width:14px; }
.rst-about-value { font-size:13px; color:#6b7280; margin:0; }

/* Badge */
.rst-badge { display:inline-flex; align-items:center; padding:2px 8px; border-radius:999px; font-size:11px; font-weight:600; white-space:nowrap; margin:2px; }
.rst-badge--soft { background:#f3f4f6; color:#374151; }

/* Buttons */
.rst-btn-primary { display:inline-flex; align-items:center; gap:6px; padding:8px 16px; background:#1e3a5f; color:#fff; border:none; border-radius:6px; font-size:13px; font-weight:600; cursor:pointer; text-decoration:none; transition:opacity .15s; }
.rst-btn-primary:hover { opacity:.85; color:#fff; }
.rst-btn-warning { display:inline-flex; align-items:center; gap:6px; padding:6px 14px; background:#fef9c3; color:#854d0e; border:1px solid #fde68a; border-radius:6px; font-size:12px; font-weight:600; cursor:pointer; transition:background .15s; }
.rst-btn-warning:hover { background:#fde68a; }
.rst-btn-success { display:inline-flex; align-items:center; gap:6px; padding:6px 14px; background:#f0fdf4; color:#166534; border:1px solid #bbf7d0; border-radius:6px; font-size:12px; font-weight:600; cursor:pointer; transition:background .15s; }
.rst-btn-success:hover { background:#dcfce7; }

/* Stars */
.rst-star { color:#ff5a1f; }
.rst-star--empty { color:#d1d5db; }

.bd-currency { font-variant-numeric:tabular-nums; }
</style>
@endsection

@section('content')
<div class="rst-page">

    {{-- Hero banner --}}
    <div class="rst-hero" style="background-image:url({{asset('images/restaurant_images/').'/'.$restaurant->cover_image}});">
        <img src="{{asset('images/restaurant_images/').'/'.$restaurant->logo}}" class="rst-hero__logo" alt="logo">
        <div class="rst-hero__info">
            <h3 class="rst-hero__name">{{$restaurant->name}}</h3>
            <p class="rst-hero__slogan">{{$restaurant->slogan}}</p>
            <p class="rst-hero__address">{{$restaurant->address}}</p>
            <p style="margin:0;">
                {!! str_repeat('<span class="rst-star"><i class="fa fa-star"></i></span>', number_format($restaurant->ratings, 1)) !!}{!! str_repeat('<span class="rst-star--empty"><i class="fa fa-star"></i></span>', 5 - number_format($restaurant->ratings, 1)) !!}
            </p>
        </div>
        <div class="rst-hero__actions">
            <a href="{{route('restaurant.edit',$restaurant->id)}}" class="rst-btn-edit">
                <i class="fas fa-edit"></i> Modifier le profil
            </a>
        </div>
    </div>

    {{-- Bloc statut pause (T1.1) --}}
    @if($restaurant->is_paused)
    <div class="rst-pause-banner">
        <div>
            <strong><i class="fas fa-pause-circle" style="margin-right:4px;"></i> Restaurant en pause</strong>
            @if($restaurant->pause_reason)
                — {{ $restaurant->pause_reason }}
            @endif
            @if($restaurant->paused_until)
                <br><small>Reprend automatiquement le {{ \Carbon\Carbon::parse($restaurant->paused_until)->format('d/m/Y à H:i') }}</small>
            @endif
        </div>
        <form method="POST" action="{{ route('admin.restaurants.force_resume', $restaurant->id) }}" style="margin:0;">
            @csrf
            <button type="submit" class="rst-btn-success">
                <i class="fas fa-play"></i> Reprendre maintenant
            </button>
        </form>
    </div>
    @else
    <div class="rst-active-bar">
        <span class="rst-active-bar__badge"><i class="fas fa-circle"></i> Actif</span>
        <span class="rst-active-bar__muted">
            @if($restaurant->last_activity_at)
                Dernière activité : {{ \Carbon\Carbon::parse($restaurant->last_activity_at)->diffForHumans() }}
            @endif
        </span>
        <form method="POST" action="{{ route('admin.restaurants.force_pause', $restaurant->id) }}" class="rst-active-bar__form">
            @csrf
            <input type="text" name="reason" placeholder="Raison (optionnel)" class="rst-input-sm" style="width:200px;">
            <button type="submit" class="rst-btn-warning">
                <i class="fas fa-pause"></i> Mettre en pause
            </button>
        </form>
    </div>
    @endif

    <div class="rst-layout">
        {{-- Colonne principale --}}
        <div>
            {{-- Gestion des commandes --}}
            <div class="rst-card">
                <div class="rst-card__header">
                    <h4 class="rst-card__title">Gestion des commandes</h4>
                </div>
                <div class="rst-card__body">
                    <div class="rst-stat-grid">
                        <div class="rst-stat">
                            <div class="rst-stat__label">Total des commandes</div>
                            <div class="rst-stat__value">{{$totalorders}}</div>
                        </div>
                        <div class="rst-stat">
                            <div class="rst-stat__label">Commandes actives</div>
                            <div class="rst-stat__value">{{$getPendings}}</div>
                        </div>
                        <div class="rst-stat">
                            <div class="rst-stat__label">Commandes complétées</div>
                            <div class="rst-stat__value">{{$getComleted}}</div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Revenus --}}
            <div class="rst-card">
                <div class="rst-card__header">
                    <h4 class="rst-card__title">Revenus du restaurant</h4>
                </div>
                <div class="rst-card__body">
                    <div class="rst-stat-grid">
                        <div class="rst-stat">
                            <div class="rst-stat__label">Revenus totaux</div>
                            <div class="rst-stat__value bd-currency">{{ number_format((float) $OrdersByTotal, 0, ',', ' ') }} FCFA</div>
                        </div>
                        <div class="rst-stat">
                            <div class="rst-stat__label">Revenus du jour</div>
                            <div class="rst-stat__value bd-currency">{{ number_format((float) $OrdersByDay, 0, ',', ' ') }} FCFA</div>
                        </div>
                        <div class="rst-stat">
                            <div class="rst-stat__label">Revenus moyens</div>
                            <div class="rst-stat__value bd-currency">{{ number_format((float) $OrdersByDayAvg, 0, ',', ' ') }} FCFA</div>
                        </div>
                        <div class="rst-stat">
                            <div class="rst-stat__label">Revenus administrateur</div>
                            <div class="rst-stat__value bd-currency">{{ number_format((float) $adminEarnings, 0, ',', ' ') }} FCFA</div>
                        </div>
                        <div class="rst-stat">
                            <div class="rst-stat__label">Revenus admin du jour</div>
                            <div class="rst-stat__value bd-currency">{{ number_format((float) $adminEarnByDay, 0, ',', ' ') }} FCFA</div>
                        </div>
                        <div class="rst-stat">
                            <div class="rst-stat__label">Revenus admin moyens</div>
                            <div class="rst-stat__value bd-currency">{{ number_format((float) $adminEarnByAvg, 0, ',', ' ') }} FCFA</div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Charts --}}
            <div class="rst-card">
                <div class="rst-card__header">
                    <h4 class="rst-card__title"><i class="fas fa-chart-pie" style="margin-right:6px;color:#1e3a5f;"></i>Ventes mensuelles</h4>
                    <div class="rst-tabs">
                        <a class="rst-tab active" href="#revenue-chart" data-toggle="tab">Ventes</a>
                        <a class="rst-tab" href="#sales-chart" data-toggle="tab">Commandes</a>
                    </div>
                </div>
                <div class="rst-card__body">
                    <div class="tab-content" style="padding:0;">
                        <div class="chart tab-pane active" id="revenue-chart" style="position:relative;height:300px;">
                            <canvas id="revenue-chart-canvas" height="300" style="height:300px;"></canvas>
                        </div>
                        <div class="chart tab-pane" id="sales-chart" style="position:relative;height:300px;">
                            <canvas id="sales-chart-canvas" height="300" style="height:300px;"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Colonne latérale --}}
        <div>
            <div class="rst-card">
                <div class="rst-card__header">
                    <h4 class="rst-card__title">À propos du restaurant</h4>
                </div>
                <div class="rst-card__body">
                    <div class="rst-about-item">
                        <div class="rst-about-label"><i class="fas fa-envelope"></i> Email</div>
                        <p class="rst-about-value">{{$restaurant->email}}</p>
                    </div>
                    <hr style="border:none;border-top:1px solid #f3f4f6;margin:12px 0;">
                    <div class="rst-about-item">
                        <div class="rst-about-label"><i class="fas fa-phone"></i> Téléphone</div>
                        <p class="rst-about-value">{{$restaurant->phone}}</p>
                    </div>
                    <hr style="border:none;border-top:1px solid #f3f4f6;margin:12px 0;">
                    <div class="rst-about-item">
                        <div class="rst-about-label"><i class="fas fa-map-marker-alt"></i> Adresse</div>
                        <p class="rst-about-value">{{$restaurant->address}}</p>
                    </div>
                </div>
            </div>

            <div class="rst-card">
                <div class="rst-card__body">
                    <h4 class="rst-card__title" style="margin-bottom:12px;">Offres</h4>
                    @foreach($restaurant->cuisines as $cuisine)
                        <span class="rst-badge rst-badge--soft">{{$cuisine->name}}</span>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

</div>
@endsection

@section('script')
<script>
$(function () {
    'use strict';

    /* Chart.js Charts */
    var salesChartCanvas = document.getElementById('revenue-chart-canvas').getContext('2d');

    var salesChartData = {
        labels  : [{!!$monthstring!!}],
        datasets: [
            {
                label               : 'Revenus totaux',
                backgroundColor     : 'rgba(60,141,188,0.9)',
                borderColor         : 'rgba(60,141,188,0.8)',
                pointRadius         : false,
                pointColor          : '#3b8bba',
                pointStrokeColor    : 'rgba(60,141,188,1)',
                pointHighlightFill  : '#fff',
                pointHighlightStroke: 'rgba(60,141,188,1)',
                data                : [{{$total}}]
            },
            {
                label               : 'Total des commandes',
                backgroundColor     : 'rgba(210, 214, 222, 1)',
                borderColor         : 'rgba(210, 214, 222, 1)',
                pointRadius         : false,
                pointColor          : 'rgba(210, 214, 222, 1)',
                pointStrokeColor    : '#c1c7d1',
                pointHighlightFill  : '#fff',
                pointHighlightStroke: 'rgba(220,220,220,1)',
                data                : [{{$count}}]
            },
        ]
    };

    var salesChartOptions = {
        maintainAspectRatio : false,
        responsive : true,
        legend: { display: false },
        scales: {
            xAxes: [{ gridLines: { display: false } }],
            yAxes: [{ gridLines: { display: false } }]
        }
    };

    var salesChart = new Chart(salesChartCanvas, {
        type: 'line',
        data: salesChartData,
        options: salesChartOptions
    });

    // Donut Chart
    var pieChartCanvas = $('#sales-chart-canvas').get(0).getContext('2d');
    var pieData = {
        labels: [
            'Commandes en attente',
            'Commandes complétées',
            'Commandes annulées',
        ],
        datasets: [
            {
                data: [{{$getPendingAvg}},{{$getCompletedAvg}},{{$getCanceledAvg}}],
                backgroundColor : ['#f56954', '#00a65a', '#f39c12'],
            }
        ]
    };
    var pieOptions = {
        legend: { display: false },
        maintainAspectRatio : false,
        responsive : true,
    };
    var pieChart = new Chart(pieChartCanvas, {
        type: 'doughnut',
        data: pieData,
        options: pieOptions
    });
});
</script>

<script>
function logo1(input) {
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function (e) {
            $('#logo').attr('src', e.target.result);
        };
        reader.readAsDataURL(input.files[0]);
    }
}
</script>
@endsection
