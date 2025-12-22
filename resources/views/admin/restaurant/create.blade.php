@extends('layouts.app')
@section('title','Créer un restaurant')
@section('add_restaurant_nav', 'active')
@section('add_restaurant_open_nav', 'menu-open')
@section('add_restaurant_create_nav', 'active')
@section('style')
    <link rel="stylesheet"
          href="{{ asset(env('ASSET_URL') .'plugins/bootstrap-colorpicker/css/bootstrap-colorpicker.min.css')}}">
    <link rel="stylesheet"
          href="{{ asset(env('ASSET_URL') .'plugins/bootstrap4-duallistbox/bootstrap-duallistbox.min.css')}}">
    <link rel="stylesheet" href="{{ asset(env('ASSET_URL') .'plugins/select2/css/select2.min.css')}}">
    <link rel="stylesheet"
          href="{{ asset(env('ASSET_URL') .'plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css')}}">

    <style>
        .note-table, .note-insert {
            display: none;
        }
    </style>
@endsection

@section('content')
    <div class="content-header">
        @if(session()->has('alert'))
            <div class="alert alert-{{ session()->get('alert.type') }}">
                {{ session()->get('alert.message') }}
            </div>
        @endif
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0 text-dark"><i class="fas fa-building mr-2"></i>Créer un restaurant</h1>
                </div><!-- /.col -->
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{url('/admin')}}">Accueil</a></li>
                        <li class="breadcrumb-item "><a href="{{route('restaurant.index')}}">Restaurants</a></li>
                        <li class="breadcrumb-item active">Créer</li>
                    </ol>
                </div><!-- /.col -->
            </div><!-- /.row -->
        </div><!-- /.container-fluid -->
    </div>
    <section class="content" style="padding: 20px; ">
        <div class="container-fluid main-content">
            <div class="row justify-content-center">
                <div class="col-md-10">
                    <div class="card card-primary shadow-sm">
                        <div class="card-header text-center">
                            <h3 class="card-title"><i class="fas fa-plus-circle mr-2"></i>Ajouter un restaurant</h3>
                        </div>
                        <!-- /.card-header -->
                        <!-- form start -->
                        <form role="form" method="post" action="{{ route('restaurant.store') }}"
                              enctype="multipart/form-data">
                            @csrf
                            <div class="card-body">
                                <div class="form-row">
                                    <div class="form-group col-md-6">
                                        <div class="qr-el qr-el-3"
                                             style="min-height: auto;  box-shadow: 2px 0px 30px 5px rgba(0, 0, 0, 0.03); padding:25px; margin:0px 20px;">
                                            <label for="file-input" class="hoviringdell uploadBox" id="uploadTrigger"
                                                   style="height: 110px; text-align:center; width:100%; border:dotted 2px #cccccc;">
                                                <img src="" style="width: 90px;" id="logo" alt="logo">
                                                <div class="uploadText" style="font-size: 12px;">
                                                    <span style="color:#F69518;"><i class="fas fa-upload mr-1"></i>Télécharger le logo</span><br>
                                                    Taille 90x90
                                                </div>
                                            </label>
                                            <input type="file" id="file-input" name="logo" onchange="logo1(this);">
                                            @if($errors->has('logo'))
                                                <span class="invalid-feedback" role="alert">
                                            <strong>{{ $errors->first('logo') }}</strong>
                                        </span>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="form-group col-md-6">
                                        <div class="qr-el qr-el-3"
                                             style="min-height: auto;  box-shadow: 2px 0px 30px 5px rgba(0, 0, 0, 0.03); padding:25px; margin:0px 20px;">
                                            <label for="upload_file" class="hoviringdell uploadBox"
                                                   id="uploadTrigger"
                                                   style="height: 110px; text-align:center; width:100%; border:dotted 2px #cccccc;">
                                                <img src="" style="width: 90px;" id="cover" alt="cover">
                                                <div class="uploadText" style="font-size: 12px;">
                                                    <span style="color:#F69518;"><i class="fas fa-upload mr-1"></i>Télécharger la bannière</span><br>
                                                    Taille 320x220
                                                </div>
                                            </label>
                                            <input type="file" id="upload_file" name="cover_image"
                                                   onchange="cover1(this);">
                                            @if($errors->has('cover_image'))
                                                <span class="invalid-feedback" role="alert">
                                            <strong>{{ $errors->first('cover_image') }}</strong>
                                        </span>
                                            @endif
                                        </div>

                                    </div>
                                </div>
                                <div class="form-row">
                                    <div class="form-group col-md-6">
                                        <label for="name">Nom du restaurant <span class="text-danger">*</span></label>
                                        <input type="text"
                                               class="form-control {{ $errors->has('name') ? ' is-invalid' : '' }}"
                                               id="name" value="{{ old('name') }}" name="name" placeholder="Ex: Restaurant Le Gourmet" required>
                                        @if($errors->has('name'))
                                            <span class="invalid-feedback" role="alert">
                                            <strong>{{ $errors->first('name') }}</strong>
                                        </span>
                                        @endif
                                    </div>
                                    <div class="form-group col-md-6">
                                        <label for="slogan">Slogan</label>
                                        <input type="text"
                                               class="form-control {{ $errors->has('slogan') ? ' is-invalid' : '' }}"
                                               name="slogan" id="slogan" value="{{ old('slogan') }}" placeholder="Ex: La qualité avant tout">
                                        @if($errors->has('slogan'))
                                            <span class="invalid-feedback" role="alert">
                                            <strong>{{ $errors->first('slogan') }}</strong>
                                        </span>
                                        @endif
                                    </div>
                                </div>
                                <div class="form-row">
                                    <div class="form-group col-md-6">
                                        <label for="select">Cuisines</label>
                                        <select class="select2" id="select" name="cuisine_id" multiple
                                                style="width: 100%;"
                                                class="form-control">
                                            <option value="">Choisir...</option>
                                            @foreach(\App\Cuisine::all() as $cuisine)
                                                <option
                                                    value="{{$cuisine->id}}"{{old('cuisine_id') == $cuisine->id ? 'selected' : ''}}>{{$cuisine->name}}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="form-group col-md-3">
                                        <label for="phone">Téléphone</label>
                                        <input type="text" value="{{ old('phone') }}" name="phone"
                                               class="form-control {{ $errors->has('phone') ? ' is-invalid' : '' }}"
                                               id="phone">
                                        @if($errors->has('phone'))
                                            <span class="invalid-feedback" role="alert">
                                            <strong>{{ $errors->first('phone') }}</strong>
                                        </span>
                                        @endif
                                    </div>
                                    
                                    <div class="form-group col-md-3">
                                        <label for="city">Ville <span class="text-danger">*</span></label>
                                        <input type="text" value="{{ old('city') }}" name="city"
                                               class="form-control {{ $errors->has('city') ? ' is-invalid' : '' }}"
                                               id="city" placeholder="Ex: Brazzaville" required>
                                        @if($errors->has('city'))
                                            <span class="invalid-feedback" role="alert">
                                            <strong>{{ $errors->first('city') }}</strong>
                                        </span>
                                        @endif
                                    </div>
                                </div>
                                </div>
                                <style>
                                        #map {
                                            width: 100%;
                                            height: 400px;
                                        }
                                </style>
                                <div class="form-row pr-3 pl-3">
                                <div class="form-group col-md-12">
                              <label for="description">Adresse <span style="color: red;">*</span></label>
                                <input id="searchMapInput" name="address" class="form-control" type="text" placeholder="Entrez une adresse" value="" required="">
                                
                                <input type="hidden" id="latitude" name="latitude" value="4.222432">
                                <input type="hidden" id="longitude" name="longitude" value="103.420257">
                                <span id="address_err" class="text-danger"></span>
                                </div>
                                <div class="form-group col-md-12">
                                <div id="map"></div>
                              </div>
                              </div>
                                <div class="form-row pr-3 pl-3">
                                    <div class="form-group col-md-4">
                                        <label for="min_order">Commande minimum (FCFA)</label>
                                        <input type="number" value="{{ old('min_order') }}" name="min_order"
                                               class="form-control {{ $errors->has('min_order') ? ' is-invalid' : '' }}"
                                               id="min_order" min="1" placeholder="Ex: 5000"/>
                                    </div>
                                    <div class="form-group col-md-4">
                                        <label for="avg_delivery_time">Temps de livraison moyen (minutes)</label>
                                        <select id="avg_delivery_time" class="form-control" name="avg_delivery_time">
                                            <option value="">Choisir...</option>
                                            <option value="10">10 minutes</option>
                                            <option value="20">20 minutes</option>
                                            <option value="30">30 minutes</option>
                                            <option value="40">40 minutes</option>
                                            <option value="50">50 minutes</option>
                                            <option value="60">60 minutes</option>
                                        </select>
                                    </div>
                                    
                                    
                                    
                                    
                                <div class="form-group col-md-4">
                                        <label for="admin_commission">Commission admin (%)</label>
                                        <input type="text" value="{{ old('admin_commission') }}" name="admin_commission"
                                               class="form-control {{ $errors->has('admin_commission') ? ' is-invalid' : '' }}"
                                               id="admin_commission" placeholder="Ex: 10, 15, 20">
                                               
                                        @if($errors->has('admin_commission'))
                                            <span class="invalid-feedback" role="alert">
                                            <strong>{{ $errors->first('admin_commission') }}</strong>
                                        </span>
                                        @endif
                                    </div>
                                    
                                    
                                    
                                    
                                    
                                </div>
            
                                <div class="form-row pr-3 pl-3">
                                <div class="form-group col-md-4">
                                    <label for="user_name">Nom d'utilisateur <span class="text-danger">*</span></label>
                                    <input type="text" value="{{ old('user_name') }}" name="user_name"
                                           class="form-control {{ $errors->has('user_name') ? ' is-invalid' : '' }}"
                                           id="user_name" placeholder="Ex: restaurant_gourmet" required>
                                    @if($errors->has('user_name'))
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $errors->first('user_name') }}</strong>
                                        </span>
                                    @endif
                                </div>

                                    <div class="form-group col-md-4">
                                        <label for="email">Email <span class="text-danger">*</span></label>
                                        <input type="email" value="{{ old('email') }}" name="email"
                                               class="form-control {{ $errors->has('email') ? ' is-invalid' : '' }}"
                                               id="email" placeholder="Ex: contact@restaurant.com" required>
                                        @if($errors->has('email'))
                                            <span class="invalid-feedback" role="alert">
                                            <strong>{{ $errors->first('email') }}</strong>
                                        </span>
                                        @endif
                                    </div>
                                    <div class="form-group col-md-4">
                                        <label for="password">Mot de passe <span class="text-danger">*</span></label>
                                        <input type="password" value="{{ old('password') }}"
                                               class="form-control {{ $errors->has('password') ? ' is-invalid' : '' }}"
                                               id="password" name="password" placeholder="Minimum 6 caractères" required>
                                        @if($errors->has('password'))
                                            <span class="invalid-feedback" role="alert">
                                            <strong>{{ $errors->first('password') }}</strong>
                                        </span>
                                        @endif
                                    </div>
                                </div>
                                
                                <div class="form-group pl-3 pr-3">
                                    <label for="description">Description <span class="text-danger">*</span></label>
                                    <textarea rows="3"
                                           class="form-control  {{ $errors->has('description') ? ' is-invalid' : '' }}"
                                           id="description" name="description"
                                           placeholder="Décrivez votre restaurant...">{{ old('description') }}</textarea>
                                    @if($errors->has('description'))
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $errors->first('description') }}</strong>
                                        </span>
                                    @endif
                                </div>
                                
                            <div class="form-group ">
                                <h4 style="text-align:center; padding:10px; margin-top:5px;"
                                    class="text-light bg-dark">
                                    <i class="fas fa-university mr-2"></i>Détails du compte bancaire
                                </h4>
                            </div>
                            <div class="form-row pr-3 pl-3">
                                <div class="form-group col-md-6">
                                    <label for="bank_name">Nom de la banque <span class="text-danger">*</span></label>
                                    <input type="text" value="{{ old('bank_name') }}" name="bank_name"
                                           class="form-control {{ $errors->has('bank_name') ? ' is-invalid' : '' }}"
                                           id="bank_name" placeholder="Ex: BGFI Bank" required>
                                    @if($errors->has('bank_name'))
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $errors->first('bank_name') }}</strong>
                                        </span>
                                    @endif
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="branch_name">Nom de l'agence <span class="text-danger">*</span></label>
                                    <input type="text" value="{{ old('branch_name') }}"
                                           class="form-control {{ $errors->has('branch_name') ? ' is-invalid' : '' }}"
                                           id="branch_name" name="branch_name" placeholder="Ex: Agence Centre-Ville" required>
                                    @if($errors->has('branch_name'))
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $errors->first('branch_name') }}</strong>
                                        </span>
                                    @endif
                                </div>
                            </div>
                            <div class="form-row pr-3 pl-3">
                                <div class="form-group col-md-6">
                                    <label for="account_name">Nom du titulaire <span class="text-danger">*</span></label>
                                    <input type="text" value="{{ old('account_name') }}" name="account_name"
                                           class="form-control {{ $errors->has('account_name') ? ' is-invalid' : '' }}"
                                           id="account_name" placeholder="Ex: Restaurant Le Gourmet" required>
                                    @if($errors->has('account_name'))
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $errors->first('account_name') }}</strong>
                                        </span>
                                    @endif
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="account_number">Numéro de compte <span class="text-danger">*</span></label>
                                    <input type="text" value="{{ old('account_number') }}"
                                           class="form-control {{ $errors->has('account_number') ? ' is-invalid' : '' }}"
                                           id="account_number" name="account_number" placeholder="Ex: 1234567890" required>
                                    @if($errors->has('account_number'))
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $errors->first('account_number') }}</strong>
                                        </span>
                                    @endif
                                </div>
                            </div>
                            </div>
                            <!-- /.card-body -->

                            <div class="card-footer">
                                <a style="float:right; margin:0 5px;" href="{{ route('restaurant.index') }}"
                                   class="btn btn-secondary btn-sm">
                                   <i class="fas fa-times mr-1"></i>Annuler
                                </a>
                                <button type="submit" class="btn btn-primary btn-sm"
                                        style="float:right;">
                                        <i class="fas fa-save mr-1"></i>Ajouter
                                </button>
                            </div>
                            @csrf
                        </form>
                    </div>
                </div>
            </div>
            <!-- /.row (main row) -->
        </div><!-- /.container-fluid -->
    </section>
@endsection
@section('script')
<script>
function initMap() {
    var lati = document.getElementById('latitude').value;
    var long = document.getElementById('longitude').value;

    var myLatlng = new google.maps.LatLng(Number(lati),Number(long));
    var geocoder = new google.maps.Geocoder();
    var map = new google.maps.Map(document.getElementById('map'), {
      center: {lat: Number(lati), lng: Number(long)},
      zoom: 13
    });
    
    //
    var input = document.getElementById('searchMapInput');

    var autocomplete = new google.maps.places.Autocomplete(input);
    autocomplete.bindTo('bounds', map);
  
    var infowindow = new google.maps.InfoWindow();
     var marker = new google.maps.Marker({
          position: myLatlng,
          map: map,
          draggable:true
         
        });

    autocomplete.addListener('place_changed', function() {
        //infowindow.close();
        marker.setVisible(true);
        var place = autocomplete.getPlace();
    
        /* If the place has a geometry, then present it on a map. */
        if (place.geometry.viewport) {
            map.fitBounds(place.geometry.viewport);
        } else {
            map.setCenter(place.geometry.location);
            map.setZoom(17);
        }

        marker.setPosition(place.geometry.location);
        marker.setVisible(true);
      
        var address = '';
        if (place.address_components) {
            address = [
              (place.address_components[0] && place.address_components[0].short_name || ''),
              (place.address_components[1] && place.address_components[1].short_name || ''),
              (place.address_components[2] && place.address_components[2].short_name || '')
            ].join(' ');
        }
      console.log('Place'+place.name);
        document.getElementById('latitude').value = place.geometry.location.lat();
        document.getElementById('longitude').value = place.geometry.location.lng();
        infowindow.setContent('<div><strong>' + place.name + '</strong><br>' + address);
        infowindow.open(map, marker);
        
        //check rerstaurant address comes within area selected
        fun_check_restaurant(place.geometry.location.lat(), place.geometry.location.lng());

        /* Location details */
    });
        // draggabled address /* Start

        google.maps.event.addListener(marker, 'dragend', 
        function(marker){
        var latLng = marker.latLng; 
        currentLatitude = latLng.lat();
        currentLongitude = latLng.lng();

        
  console.log('dragend'+currentLatitude);

            geocoder.geocode({'latLng': latLng }, function(results, status) {
                    if (status == google.maps.GeocoderStatus.OK) {
                    if (results[0]) {
                    document.getElementById('searchMapInput').value = results[0].formatted_address;
                    document.getElementById('latitude').value = currentLatitude;
                    document.getElementById('longitude').value = currentLongitude;
                    infowindow.setContent('<div>' + results[0].formatted_address + '<br>');
                    infowindow.open(map, marker);
                    }
                }
            });
        }); 

        // draggabled address /* End

}
</script>
<script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyD5p8RYu8df9stqowX53VM1G1HYWW2JCkc&libraries=places&callback=initMap" async defer></script>



    <script src="{{ asset(env('ASSET_URL') .'plugins/select2/js/select2.full.min.js')}}"></script>
    <script src="{{ asset(env('ASSET_URL') .'plugins/inputmask/min/jquery.inputmask.bundle.min.js')}}"></script>
    <script
        src="{{ asset(env('ASSET_URL') .'plugins/bootstrap-colorpicker/js/bootstrap-colorpicker.min.js')}}"></script>
    <script
        src="{{ asset(env('ASSET_URL') .'plugins/bootstrap4-duallistbox/jquery.bootstrap-duallistbox.min.js')}}"></script>
    <script src="{{ asset(env('ASSET_URL') .'plugins/bootstrap-switch/js/bootstrap-switch.min.js')}}"></script>
    <script>
        function cover1(input) {
            if (input.files && input.files[0]) {
                var reader = new FileReader();

                reader.onload = function (e) {
                    $('#cover')
                        .attr('src', e.target.result);
                };

                reader.readAsDataURL(input.files[0]);
            }
        }

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
    <script>
        $(function () {
            $('.select2').select2();

            //Initialize Sélectionner2 Elements
            $('.select2bs4').select2({
                theme: 'bootstrap4'
            })
        });
        //Colorpicker
        $('.my-colorpicker1').colorpicker();
        //color picker with addon
        $('.my-colorpicker2').colorpicker();

        $('.my-colorpicker2').on('colorpickerChange', function (event) {
            $('.my-colorpicker2 .fa-square').css('color', event.color.toString());
        });

        $("input[data-bootstrap-switch]").each(function () {
            $(this).bootstrapSwitch('state', $(this).prop('checked'));
        });
    </script>
@endsection
