@extends('layouts.app')
@section('title','Détails de la commande')
@section('style')
<link rel="stylesheet" href="{{asset(env('ASSET_URL') .'/plugins/datatables-bs4/css/dataTables.bootstrap4.css')}}">
<link rel="stylesheet" href="{{asset(env('ASSET_URL').'/plugins/sweetalert2/sweetalert2.css')}}">
@endsection
@section('order_nav', 'active')
@section('order_nav_open', 'menu-open')

@section('content')
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0 text-dark">Orders</h1>
                </div><!-- /.col -->
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="#">Accueil</a></li>
                        <li class="breadcrumb-item "><a href="">Orders</a></li>
                        <li class="breadcrumb-item active">{{$order->order_no}}</li>
                    </ol>
                </div><!-- /.col -->
            </div><!-- /.row -->
        </div><!-- /.container-fluid -->
    </div>
    <section class="content">
        <div class="container-fluid">
            <div class="row justify-content-center" >
                <div class="col-11">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="card card-primary card-outline">
                              <div class="card-body box-profile">
                                <div class="text-center">
                                  @php
                                    $logo = $order->restaurant->logo ?? null;
                                    $logoSrc = $logo
                                      ? (strpos($logo, 'http') === 0 ? $logo : asset('images/restaurant_images/' . $logo))
                                      : asset('images/placeholder.png');
                                  @endphp
                                  <img class="rounded-circle" style="border: 2px solid #007bff; object-fit:cover;" width="75" height="75" src="{{ $logoSrc }}" alt="Logo restaurant" onerror="this.src='{{ asset('images/placeholder.png') }}'">
                                </div>

                                <h3 class="profile-username text-center">{{$order->restaurant->name}}</h3>

                                <p class="text-muted text-center"><b>Services: </b> {{$order->restaurant->services}}</p>

                                <ul class="list-group list-group-unbordered mb-3">
                                  <li class="list-group-item">
                                    <b>Email</b> <a class="float-right">{{$order->restaurant->email}}</a>
                                  </li>
                                  <li class="list-group-item">
                                    <b>Téléphone</b> <a class="float-right">{{$order->restaurant->phone}}</a>
                                  </li>
                                  <li class="list-group-item">
                                    <b>Adresse</b> <a class="float-right">{{$order->restaurant->address}}</a>
                                  </li>
                                </ul>

                                <a href="" class="btn bg-gradient-primary btn-block"><b>Restaurant</b></a>
                              </div>
                              <!-- /.card-body -->
                            </div>
                        </div>
                        <div class="col-md-4">
                        	@if($order->driver)
                            <div class="card card-warning card-outline">
                              <div class="card-body box-profile">
                                <div class="text-center">
                                    @if($order->driver->image)
                                    <img class="rounded-circle" style="border: 2px solid #ffc107;" width="75" height="75" src="{{asset(env('ASSET_URL') .'images/driver_images/'.$order->driver->image)}}" alt="Photo de profil utilisateur">
                                    @else
                                    <img class="rounded-circle" style="border: 2px solid #ffc107;" width="75" height="75" src="{{asset(env('ASSET_URL') .'images/5-512.png')}}" alt="Photo de profil utilisateur">
                                    @endif
                                </div>

                                <h3 class="profile-username text-center">{{$order->driver->name}}</h3>

                                <p class="text-muted text-center">{{$order->driver->user_name}}</p>

                                <ul class="list-group list-group-unbordered mb-3">
                                  <li class="list-group-item">
                                    <b>Email</b> <a class="float-right">{{$order->driver->email}}</a>
                                  </li>
                                  <li class="list-group-item">
                                    <b>Téléphone</b> <a class="float-right">{{$order->driver->phone}}</a>
                                  </li>
                                  <li class="list-group-item">
                                    <b>Adresse</b> <a class="float-right">{{$order->driver->address}}</a>
                                  </li>
                                </ul>

                                <a href="#" class="btn bg-gradient-warning btn-block"><b>Driver</b></a>
                              </div>
                              <!-- /.card-body -->
                            </div>
                            @else
                            <div class="card card-warning card-outline">
                              <div class="card-body box-profile">
                                <div class="text-center">
                                    <img class="rounded-circle" style="border: 2px solid #ffc107;" width="75" height="75" src="{{asset(env('ASSET_URL') .'images/5-512.png')}}" alt="Photo de profil utilisateur">
                                </div>

                                <h3 class="profile-username text-center">Driver Name</h3>

                                <p class="text-muted text-center">Driver</p>

                                <ul class="list-group list-group-unbordered mb-3">
                                  <li class="list-group-item">
                                    <b>Email</b> <a class="float-right">xxxxxx</a>
                                  </li>
                                  <li class="list-group-item">
                                    <b>Téléphone</b> <a class="float-right">xxxxxx</a>
                                  </li>
                                  <li class="list-group-item">
                                    <b>Adresse</b> <a class="float-right">xxxxxx</a>
                                  </li>
                                </ul>

                                <a href="#" class="btn bg-gradient-warning btn-block"><b>Livreur non assigné</b></a>
                              </div>
                              <!-- /.card-body -->
                            </div>
                            @endif
                        </div>
                        <div class="col-md-4">
                            <div class="card card-success card-outline">
                              <div class="card-body box-profile">
                                <div class="text-center">
                                    @if($order->user->image)
                                    <img class="rounded-circle" style="border: 2px solid #28a745;" width="75" height="75" src="{{asset(env('ASSET_URL') .'images/profile_images/'.$order->user->image)}}" alt="Photo de profil utilisateur">
                                    @else
                                    <img class="rounded-circle" style="border: 2px solid #28a745;" width="75" height="75" src="{{asset(env('ASSET_URL') .'images/5-512.png')}}" alt="Photo de profil utilisateur">
                                    @endif
                                </div>

                                <h3 class="profile-username text-center">{{$order->user->name}}</h3>

                                <p class="text-muted text-center">User</p>

                                <ul class="list-group list-group-unbordered mb-3">
                                  <li class="list-group-item">
                                    <b>Email</b> <a class="float-right">{{$order->user->email}}</a>
                                  </li>
                                  <li class="list-group-item">
                                    <b>Téléphone</b> <a class="float-right">{{$order->user->phone}}</a>
                                  </li>
                                  <li class="list-group-item">
                                    <b>Adresse</b> <a class="float-right">{{$order->user->address}}</a>
                                  </li>
                                </ul>

                                <a href="" class="btn bg-gradient-success btn-block"><b>User</b></a>
                              </div>
                              <!-- /.card-body -->
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-8">
                            <div class="col-md-12">
                                <div class="card ">
                              <div class="card-body">
                                <table class="table table-condensed">
                  <thead>
                    <tr>
                      <th style="width: 10px">#</th>
                      <th>Image</th>
                      <th>Product</th>
                      <th>qty</th>
                      <th>Prix</th>
                    </tr>
                  </thead>
                  <tbody>
                     @foreach($products as $index => $pro)
                    <tr>
                      <td>{{++$index}}</td>
                      <td><img src="{{url('images/product_images',$pro->product->image)}}" class="img-circle elevation-2" alt="User Image" width="65"></td>
                      <td>{{$order->product->name}}</td>
                      <td>{{$order->qty}}</td>
                      <td>{{$order->product->price}}</td>
                    </tr>
                    @endforeach
                  </tbody>
                </table>
                </div></div>
                                </div>
                           <div id="map" class="w-100" style="border:0; height: 350px;"></div>  
                        </div>
                        <div class="col-md-4">
                            <div class="card ">
                                <div class="card-body">
                                    <div class="small-box ">
                                        <div class="inner">

                                            <h3>Statut</h3>
                                        </div>
                                        <a class=" text-white bg-gradient-danger small-box-footer">{{$order->status}}</a>
                                    </div>
                                </div>
                                <div class="card-body pt-0">
                                    <div class="small-box ">
                                        <div class="inner">

                                            <h3>Ordered Date</h3>
                                        </div>
                                        <a class=" text-white bg-gradient-success small-box-footer">{{$order->created_at}}</a>
                                    </div>
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
<script src="{{asset(env('ASSET_URL') .'plugins/datatables/jquery.dataTables.js')}}"></script>
<script src="{{asset(env('ASSET_URL') .'plugins/datatables-bs4/js/dataTables.bootstrap4.js')}}"></script>
 <script>
        var pickup = {lat: @if($order->driver==null) 32.6576343 @else {{$order->driver->latitude ?? 32.6576343}}  @endif, lng: @if($order->driver==null) 74.6487362  @else {{$order->driver->longitude ?? 74.6487362}}  @endif};
        var dropoff = {lat: {{$order->d_lat}}, lng: {{$order->d_lng}}};
        var map, directionsService, directionsRenderer;
        var driverMarker = null;
        const orderNo = '{{ $order->order_no }}';
        const currentStatus = '{{ $order->status }}';
        
      function initMap() {
        directionsService = new google.maps.DirectionsService();
        directionsRenderer = new google.maps.DirectionsRenderer();

        map = new google.maps.Map(document.getElementById('map'), {
          zoom: 11,
          center: pickup
        });
        directionsRenderer.setMap(map);
        calculateAndDisplayRoute(directionsService, directionsRenderer);
        
        // Add driver marker if available
        @if($order->driver && $order->driver->latitude && $order->driver->longitude)
        updateDriverMarker({{$order->driver->latitude}}, {{$order->driver->longitude}});
        @endif
        
        // Start auto-refresh if order is not completed
        @if(!in_array($order->status, ['completed', 'cancelled']))
        startAutoRefresh();
        @endif
      }
      
      function updateDriverMarker(lat, lng) {
          if (driverMarker) {
              driverMarker.setPosition({ lat: lat, lng: lng });
          } else if (map) {
              driverMarker = new google.maps.Marker({
                  position: { lat: lat, lng: lng },
                  map: map,
                  title: 'Position du livreur',
                  icon: {
                      url: 'https://maps.google.com/mapfiles/ms/icons/green-dot.png',
                      scaledSize: new google.maps.Size(40, 40)
                  },
                  animation: google.maps.Animation.BOUNCE
              });
          }
          
          // Update pickup point and recalculate route
          pickup = { lat: lat, lng: lng };
          calculateAndDisplayRoute(directionsService, directionsRenderer);
      }

       function calculateAndDisplayRoute(directionsService, directionsRenderer) {
        directionsService.route({
          origin: pickup,  
          destination: dropoff,  
          travelMode: google.maps.TravelMode['DRIVING']
        }, function(response, status) {
          if (status == 'OK') {
            directionsRenderer.setDirections(response);
          } else {
            console.error('Directions request failed due to ' + status);
          }
        });
      }
      
      // Fetch order status via AJAX
      function fetchOrderStatus() {
          fetch(`/api/order/${orderNo}/status`)
              .then(response => response.json())
              .then(data => {
                  if (data.status && data.order) {
                      const order = data.order;
                      
                      // Update driver position if available
                      if (order.driver && order.driver.latitude && order.driver.longitude) {
                          updateDriverMarker(
                              parseFloat(order.driver.latitude),
                              parseFloat(order.driver.longitude)
                          );
                      }
                      
                      // Update status display if changed
                      if (order.status !== currentStatus && document.getElementById('orderStatus')) {
                          document.getElementById('orderStatus').textContent = 
                              order.status.charAt(0).toUpperCase() + order.status.slice(1);
                      }
                  }
              })
              .catch(error => {
                  console.error('Erreur lors de la récupération du statut:', error);
              });
      }
      
      // Auto-refresh every 10 seconds
      let refreshInterval;
      function startAutoRefresh() {
          refreshInterval = setInterval(() => {
              fetchOrderStatus();
          }, 10000); // 10 seconds
      }
      
      // Stop auto-refresh when page is hidden
      document.addEventListener('visibilitychange', () => {
          if (document.hidden) {
              clearInterval(refreshInterval);
          } else {
              @if(!in_array($order->status, ['completed', 'cancelled']))
              startAutoRefresh();
              @endif
          }
      });
</script>


<script async defer src="https://maps.googleapis.com/maps/api/js?key=AIzaSyCkXFIvxvN0M1Chg644bLwAnXEQUG_RKUI&callback=initMap"></script>
@endsection