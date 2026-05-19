@extends('layouts.admin-modern')
@section('title',$restaurant->name.' | Buntu Delice ')
@section('page_title', 'Détail restaurant')
@section('nav_active', 'restaurants')
@section('style')
 <link rel="stylesheet" type="text/css" href="{{asset('slick/slick.css')}}">
 <link rel="stylesheet" type="text/css" href="{{asset('slick/slick-theme.css')}}">
 <style>
    .bd-currency {
        font-variant-numeric: tabular-nums;
    }
 </style>
@endsection
@section('content')
 <style type="text/css">
 
    .slider {
        width: 50%;
        margin: 100px auto;
    }

    .slick-slide {
      margin: 0px 10px;
    }

    .slick-slide img {
      width: 100%;
    }

    .slick-prev:before,
    .slick-next:before {
      color: black;
    }


    .slick-slide {
      transition: all ease-in-out .3s;
    }


    .slick-current {
      opacity: 1;
    }
    .slick-next{
     margin-right:5px;   
    }
    .slick-prev{
        margin-left:5px;
    }
  </style>
<section class="content">
<div class="container-fluid">
<div class="row  justify-content-center">
<div class="col-md-12 mt-5 ">
<div class="row py-2" style="background-image: url({{asset('images/restaurant_images/').'/'.$restaurant->cover_image}}); background-size: cover; background-repeat: no-repeat; background-position: center; ">
<div class="col-md-2">
<img src="{{asset('images/restaurant_images/').'/'.$restaurant->logo}}" class="w-100 rounded" alt="logo">
</div>
<div class="col-md-7">
<h3 class="text-white">{{$restaurant->name}}</h3>
<h5 class="text-white">{{$restaurant->slogan}}</h5>
<p class="text-white">{{$restaurant->address}}</p>
<p>{!! str_repeat('<span><i class="fa fa-star checked" style="color:#ff5a1f;"></i></span>', number_format($restaurant->ratings, 1)) !!}{!! str_repeat('<span><i class="fa fa-star"></i></span>', 5 - number_format($restaurant->ratings, 1)) !!}</p>
</div>
<div class="col-md-3"><p class="text-white float-right"><a href="{{route('restaurant.edit',$restaurant->id)}}" class="text-white">Modifier le profil</a></p></div>
</div>

{{-- Bloc statut pause (T1.1) --}}
@if($restaurant->is_paused)
<div class="alert alert-warning d-flex align-items-center justify-content-between mt-3 mb-0" style="border-left:4px solid #e85d04;">
    <div>
        <strong><i class="fas fa-pause-circle mr-1"></i> Restaurant en pause</strong>
        @if($restaurant->pause_reason)
            — {{ $restaurant->pause_reason }}
        @endif
        @if($restaurant->paused_until)
            <br><small>Reprend automatiquement le {{ \Carbon\Carbon::parse($restaurant->paused_until)->format('d/m/Y à H:i') }}</small>
        @endif
    </div>
    <form method="POST" action="{{ route('admin.restaurants.force_resume', $restaurant->id) }}" style="margin:0;">
        @csrf
        <button type="submit" class="btn btn-sm btn-success">
            <i class="fas fa-play mr-1"></i> Reprendre maintenant
        </button>
    </form>
</div>
@else
<div class="d-flex align-items-center mt-3 mb-0 p-2" style="background:#f8f9fa;border-radius:6px;">
    <span class="badge badge-success mr-2"><i class="fas fa-circle"></i> Actif</span>
    <small class="text-muted">
        @if($restaurant->last_activity_at)
            Dernière activité : {{ \Carbon\Carbon::parse($restaurant->last_activity_at)->diffForHumans() }}
        @endif
    </small>
    <form method="POST" action="{{ route('admin.restaurants.force_pause', $restaurant->id) }}" class="ml-auto d-flex align-items-center gap-2" style="gap:8px;">
        @csrf
        <input type="text" name="reason" placeholder="Raison (optionnel)" class="form-control form-control-sm" style="width:200px;">
        <button type="submit" class="btn btn-sm btn-warning ml-2">
            <i class="fas fa-pause mr-1"></i> Mettre en pause
        </button>
    </form>
</div>
@endif

<div class="row mt-3">
<div class="col-md-8 pl-0">
<div class="card shadow">
<div class="card-header">
<h4>Gestion des commandes</h4>
</div>
<div class="card-body">
<div class="row">
<div class="col-12 col-sm-4">
<div class="info-box bg-light">
<div class="info-box-content">
<span class="info-box-text text-center text-muted">Total des commandes</span>
<span class="info-box-number text-center text-muted mb-0">{{$totalorders}}</span>
</div>
</div>
</div>
<div class="col-12 col-sm-4">
<div class="info-box bg-light">
<div class="info-box-content">
<span class="info-box-text text-center text-muted">Commandes actives</span>
<span class="info-box-number text-center text-muted mb-0">{{$getPendings}}</span>
</div>
</div>
</div>
<div class="col-12 col-sm-4">
<div class="info-box bg-light">
<div class="info-box-content">
<span class="info-box-text text-center text-muted">Commandes complétées</span>
<span class="info-box-number text-center text-muted mb-0">{{$getComleted}} <span>
</div>
</div>
</div>
</div>
</div>
</div>

<div class="card shadow">
<div class="card-header">
<h4>Revenus du restaurant</h4>
</div>
<div class="card-body">
<div class="row">
<div class="col-12 col-sm-4">
<div class="info-box bg-light">
<div class="info-box-content">
<span class="info-box-text text-center text-muted">Revenus totaux</span>
<span class="info-box-number text-center text-muted mb-0 bd-currency">{{ number_format((float) $OrdersByTotal, 0, ',', ' ') }} FCFA</span>
</div>
</div>
</div>
<div class="col-12 col-sm-4">
<div class="info-box bg-light">
<div class="info-box-content">
<span class="info-box-text text-center text-muted">Revenus du jour</span>
<span class="info-box-number text-center text-muted mb-0 bd-currency">{{ number_format((float) $OrdersByDay, 0, ',', ' ') }} FCFA</span>
</div>
</div>
</div>
<div class="col-12 col-sm-4">
<div class="info-box bg-light">
<div class="info-box-content">
<span class="info-box-text text-center text-muted">Revenus moyens</span>
<span class="info-box-number text-center text-muted mb-0 bd-currency">{{ number_format((float) $OrdersByDayAvg, 0, ',', ' ') }} FCFA<span>
</div>
</div>
</div>
<div class="col-12 col-sm-4">
<div class="info-box bg-light">
<div class="info-box-content">
<span class="info-box-text text-center text-muted">Revenus administrateur</span>
<span class="info-box-number text-center text-muted mb-0 bd-currency">{{ number_format((float) $adminEarnings, 0, ',', ' ') }} FCFA<span>
</div>
</div>
</div>
<div class="col-12 col-sm-4">
<div class="info-box bg-light">
<div class="info-box-content">
<span class="info-box-text text-center text-muted">Revenus administrateur du jour</span>
<span class="info-box-number text-center text-muted mb-0 bd-currency">{{ number_format((float) $adminEarnByDay, 0, ',', ' ') }} FCFA<span>
</div>
</div>
</div>
<div class="col-12 col-sm-4">
<div class="info-box bg-light">
<div class="info-box-content">
<span class="info-box-text text-center text-muted">Revenus administrateur moyens</span>
<span class="info-box-number text-center text-muted mb-0 bd-currency">{{ number_format((float) $adminEarnByAvg, 0, ',', ' ') }} FCFA<span>
</div>
</div>
</div>
</div>
</div>
</div>

<div class="row">
                <!-- Left col -->
                <section class="col-lg-12 connectedSortable">
                    <!-- Custom tabs (Charts with tabs)-->
                    <div class="card shadow">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-chart-pie mr-1"></i>
                                Ventes mensuelles
                            </h3>
                            <div class="card-tools">
                                <ul class="nav nav-pills ml-auto">
                                    <li class="nav-item">
                                        <a class="nav-link active" href="#revenue-chart" data-toggle="tab">Ventes</a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link" href="#sales-chart" data-toggle="tab">Commandes</a>
                                    </li>
                                </ul>
                            </div>
                        </div><!-- /.card-header -->
                        <div class="card-body">
                            <div class="tab-content p-0">
                                <!-- Morris chart - Sales -->
                                <div class="chart tab-pane active" id="revenue-chart"
                                     style="position: relative; height: 300px;">
                                    <canvas id="revenue-chart-canvas" height="300" style="height: 300px;"></canvas>
                                </div>
                                <div class="chart tab-pane" id="sales-chart" style="position: relative; height: 300px;">
                                    <canvas id="sales-chart-canvas" height="300" style="height: 300px;"></canvas>
                                </div>
                            </div>
                        </div><!-- /.card-body -->
                    </div>
                    <!-- /.card -->

                    
                </section>
                </div>

</div>
<div class="col-md-4 pr-0">
<div class="card shadow">
<div class="card-header">
<h4>À propos du restaurant</h4>
</div>
<!-- /.card-header -->
<div class="card-body">
<strong><i class="fas fa-envelope mr-1"></i>Email</strong>

<p class="text-muted">
{{$restaurant->email}}
</p>

<hr>
<strong><i class="fas fa-phone mr-1"></i> Téléphone</strong>

<p class="text-muted">
{{$restaurant->phone}}
</p>

<hr>

<strong><i class="fas fa-map-marker-alt mr-1"></i> Adresse</strong>

<p class="text-muted">{{$restaurant->address}}</p>

<hr>
</div>
<!-- /.card-body -->
</div>
<div class="card shadow">
<div class="card-body">
<h4>Offres</h4>
@foreach($restaurant->cuisines as $cuisine)
<span class="badge badge-primary">{{$cuisine->name}}</span>
@endforeach
</div>
</div>
</div>
</div>
</div>
</div>
</div>

</section>
@endsection

@section('script')

<script>
/*
 * Author: Abdullah A Almsaeed
 * Date: 4 Jan 2014
 * Description:
 *      This is a demo file used only for the main dashboard (index.html)
 **/

$(function () {

  'use strict'


  /* Chart.js Charts */
  // Sales chart
  var salesChartCanvas = document.getElementById('revenue-chart-canvas').getContext('2d');
  //$('#revenue-chart').get(0).getContext('2d');

  var salesChartData = {
    labels  : [{!!$monthstring!!}],
    datasets: [
      {
        label               : 'Revenus totaux',
        backgroundColor     : 'rgba(60,141,188,0.9)',
        borderColor         : 'rgba(60,141,188,0.8)',
        pointRadius          : false,
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
  }

  var salesChartOptions = {
    maintainAspectRatio : false,
    responsive : true,
    legend: {
      display: false
    },
    scales: {
      xAxes: [{
        gridLines : {
          display : false,
        }
      }],
      yAxes: [{
        gridLines : {
          display : false,
        }
      }]
    }
  }

  // This will get the first returned node in the jQuery collection.
  var salesChart = new Chart(salesChartCanvas, { 
      type: 'line', 
      data: salesChartData, 
      options: salesChartOptions
    }
  )

  // Donut Chart
  var pieChartCanvas = $('#sales-chart-canvas').get(0).getContext('2d')
  var pieData        = {
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
  }
  var pieOptions = {
    legend: {
      display: false
    },
    maintainAspectRatio : false,
    responsive : true,
  }
  //Create pie or douhnut chart
  // You can switch between pie and douhnut using the method below.
  var pieChart = new Chart(pieChartCanvas, {
    type: 'doughnut',
    data: pieData,
    options: pieOptions      
  });



})
</script> 

<script>
function logo1(input) {
if (input.files && input.files[0]) {
var reader = new FileReader();

reader.onload = function (e) {
$('#logo')
.attr('src', e.target.result);
};

reader.readAsDataURL(input.files[0]);
}
}
</script>




@endsection
