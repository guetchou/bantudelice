<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>@yield('title')</title>
    <!-- Tell the browser to be responsive to screen width -->
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" type="image/x-icon" href="{{asset('favicon.ico')}}">
    <!-- Font Awesome -->
    <link 
  href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.3.0/css/font-awesome.css" 
  rel="stylesheet"  type='text/css'>
    <link rel="stylesheet" href="{{ asset(env('ASSET_URL') .'plugins/fontawesome-free/css/all.min.css')}}">
    <!-- Ionicons -->
    <link rel="stylesheet" href="https://code.ionicframework.com/ionicons/2.0.1/css/ionicons.min.css">
    <!-- Tempusdominus Bbootstrap 4 -->
    <link rel="stylesheet"
          href="{{ asset(env('ASSET_URL') .'plugins/tempusdominus-bootstrap-4/css/tempusdominus-bootstrap-4.min.css')}}">
    <!-- iCheck -->
    <link rel="stylesheet" href="{{ asset(env('ASSET_URL') .'plugins/icheck-bootstrap/icheck-bootstrap.min.css')}}">
    <!-- JQVMap -->
    <link rel="stylesheet" href="{{ asset(env('ASSET_URL') .'plugins/jqvmap/jqvmap.min.css')}}">
    <!-- Theme style -->
    <link rel="stylesheet" href="{{ asset(env('ASSET_URL') .'dist/css/adminlte.min.css')}}">

    <!-- overlayScrollbars -->
    <link rel="stylesheet"
          href="{{ asset(env('ASSET_URL') .'plugins/overlayScrollbars/css/OverlayScrollbars.min.css')}}">
    <!-- Daterange picker -->
    <link rel="stylesheet" href="{{ asset(env('ASSET_URL') .'plugins/daterangepicker/daterangepicker.css')}}">
    <!-- summernote -->
    <link rel="stylesheet" href="{{ asset(env('ASSET_URL') .'plugins/summernote/summernote-bs4.css')}}">
    <link rel="stylesheet" href="{{ asset(env('ASSET_URL') .'plugins/datatables-bs4/css/dataTables.bootstrap4.css')}}">
    <!-- Google Font: Source Sans Pro -->
    @yield('style')
    <link href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700" rel="stylesheet">
<style>
    .brand1-image {
    float: left;
    line-height: .8;
    max-height: 34px;
    width: auto;
    margin-left: .8rem;
    margin-right: .5rem;
    margin-top: -3px;
    border-radius: 50%;
}
.elevation-2 {
    box-shadow: 0 3px 6px rgba(0, 0, 0, 0.16), 0 3px 6px rgba(0, 0, 0, 0.23);
}

	.modal.right .modal-dialog {
		position: fixed;
		margin-top: 56px;
		width: 320px;
		height: 100%;
		-webkit-transform: translate3d(0%, 0, 0);
		    -ms-transform: translate3d(0%, 0, 0);
		     -o-transform: translate3d(0%, 0, 0);
		        transform: translate3d(0%, 0, 0);
	}


	.modal.right .modal-content {
		height: 100%;
		overflow-y: auto;
	}


	.modal.right .modal-body {
		padding: 0px;
	}
/*Right*/
	.modal.right.fade .modal-dialog {
		right: 0px;
		-webkit-transition: opacity 0.3s linear, right 0.3s ease-out;
		   -moz-transition: opacity 0.3s linear, right 0.3s ease-out;
		     -o-transition: opacity 0.3s linear, right 0.3s ease-out;
		        transition: opacity 0.3s linear, right 0.3s ease-out;
	}

	.modal.right.fade.in .modal-dialog {
		right: 0;
	}

/* ----- MODAL STYLE ----- */
	.modal-content {
		border-radius: 0;
		border: none;
	}

	.modal-header {
		border-bottom-color: #EEEEEE;
		background-color: #FAFAFA;
	}

/* ----- v CAN BE DELETED v ----- */
body {
	background-color: #78909C;
}

/* Correction des espaces manquants dans le menu */
.nav-sidebar .nav-link p,
.nav-sidebar .nav-treeview .nav-link p,
.nav-sidebar .nav-link,
.nav-sidebar .nav-treeview .nav-link,
.sidebar .nav-link p,
.sidebar .nav-link,
.nav-item p,
.nav-item .nav-link {
	white-space: normal !important;
	word-spacing: 0.1em !important;
	letter-spacing: 0.01em !important;
	text-rendering: optimizeLegibility !important;
	-webkit-font-smoothing: antialiased !important;
	-moz-osx-font-smoothing: grayscale !important;
	font-family: 'Source Sans Pro', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif !important;
}

/* Correction pour les labels et textes */
label, .form-label, .input-group-text, .breadcrumb-item, .card-title, h1, h2, h3, h4, h5, h6, .form-header p, .form-header h2 {
	white-space: normal !important;
	word-spacing: normal !important;
	letter-spacing: normal !important;
	text-rendering: optimizeLegibility !important;
}

.demo {
	padding-top: 60px;
	padding-bottom: 110px;
}

.btn-demo {
	margin: 15px;
	padding: 10px 15px;
	border-radius: 0;
	font-size: 16px;
	background-color: #FFFFFF;
}

.btn-demo:focus {
	outline: 0;
}


</style>
</head>
<body class="hold-transition sidebar-mini layout-fixed">
<div class="wrapper">

    <!-- Navbar -->
    <nav class="main-header navbar navbar-expand navbar-light" style="background-color:#4A67B2;">
        <!-- Left navbar links -->
        <ul class="navbar-nav" >
            <li class="nav-item">
                <a class="nav-link" data-widget="pushmenu" href="#"><i class="fas fa-bars text-white"></i></a>
            </li>
            <li class="p-2 text-white">Tableau de bord ({{date('d-M-Y')}})</li>
        </ul>
        
        <!-- Right navbar links -->
              <ul class="navbar-nav ml-auto">


           <!-- Messages Dropdown Menu -->

           <!-- Notifications Dropdown Menu -->
            @if(auth()->check() and auth()->user()->type === 'restaurant')
            <li class="nav-item dropdown">
               <a class="nav-link"data-toggle="modal" data-target="#myModal2" href="#">
                   <i class="fas fa-bell text-white"></i>
                   <span class="badge badge-warning navbar-badge" id="notiBell"></span>
                </a>
            </li>
            <li>
            @endif 
                <a class="nav-link" data-toggle="dropdown" href="#">
                    <span class="text-white">{{auth()->user()->name}}</span>
                    <!--<img src="{{asset('images/user3-128x128.jpg')}}"class="brand1-image mx-2  elevation-2" alt="foodage">-->
                </a>
               <div class="dropdown-menu dropdown-menu-right mr-3">


                   <a href="#" class="dropdown-item">
                       <i class="fas fa-user mr-2"></i>Profil

                   </a>
                   <div class="dropdown-divider"></div>
                   <a href="{{ route('logout') }}" class="dropdown-item">
                       <i class="fas fa-envelope mr-2"></i> Déconnexion

                   </a>

               </div>
           </li>

       </ul>
    </nav>
    <!-- /.navbar -->

    <!-- Main Sidebar Container -->
    <aside class="main-sidebar sidebar-light-primary elevation-4">
        <!-- Brand Logo -->
        <a href="{{url('/admin')}}" class="brand-image p-0" style="background-color:#fff">
            <img src="{{asset('frontend/images/BuntuDelice.png')}}" alt="Logo"
                 class="brand-image"
                 style="opacity: .8; width:250px; height:50px;">
            <!--<span class="brand-text  text-white" >Chutoro</span>-->
        </a>

        <!-- Sidebar -->
        <div class="sidebar">
            <!-- Sidebar user panel (optional) -->

            <div class="user-panel mt-3 pb-3 mb-3 d-flex">
                <div class="image">
                    <img src="{{ auth()->user()->image ? url('images/profile_images/' . auth()->user()->image) : url('assets/images/user-avatar.png') }}" class="img-circle elevation-2" alt="User Image">
                </div>
                <div class="info">
                    <a href="#" class="d-block"><b>{{auth()->user()->name}}</b></a>
                </div>
            </div> 
            <!-- Sidebar Menu -->
            <nav class="mt-2">
                <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu"
                    data-accordion="false">
                @if(auth()->check() and auth()->user()->type === 'admin')
                    <!-- Add icons to the links using the .nav-icon class
                         with font-awesome or any other icon font library -->
                        <li class=" nav-item">
                            <a class="nav-link  @yield('dashboard_nav')" href="{{url('/admin')}}">
                                <i class="nav-icon fas fa-tachometer-alt "></i>
                                <p>
                                    Tableau de bord
                                </p>
                            </a>
                        </li>
                        
                        <li class="nav-item has-treeview @yield('order_nav_open')">
                            <a href="#" class="nav-link @yield('order_nav')">
                                <i class="nav-icon fas fa-shopping-cart"></i>
                                <p>
                                    Commandes
                                    <i class="right fas fa-angle-left"></i>
                                </p>
                            </a>
                            <ul class="nav nav-treeview">
                                <li class="nav-item">
                                    <a href="{{route('admin.all_orders')}}" class="nav-link @yield('order_nav_all')">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Toutes les commandes ({{ \App\Order::all()->unique('order_no')->count() }})</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{route('admin.pending_orders')}}" class="nav-link @yield('pendding_order_nav')">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Nouvelles commandes ({{ \App\Order::whereStatus('pending')->get()->unique('order_no')->count() }})</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{route('admin.complete_orders')}}" class="nav-link  @yield('order_nav_complete')">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Terminées ({{ \Schema::hasTable('completed_orders') ? \App\CompletedOrder::whereStatus('completed')->get()->unique('order_no')->count() : 0 }})</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{route('admin.cancel_orders')}}" class="nav-link  @yield('order_nav_cancel')">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Annulées ({{ \App\Order::whereStatus('cancelled')->get()->unique('order_no')->count() }})</p>
                                    </a>
                                </li>
                            </ul>
                        </li>
                        <li class="nav-item has-treeview @yield('colis_nav_open')">
                            <a href="#" class="nav-link @yield('colis_nav')">
                                <i class="nav-icon fas fa-box"></i>
                                <p>
                                    Colis (Livraison)
                                    <i class="right fas fa-angle-left"></i>
                                </p>
                            </a>
                            <ul class="nav nav-treeview">
                                <li class="nav-item">
                                    <a href="{{ route('admin.colis.index') }}" class="nav-link @yield('colis_nav_all')">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Tous les colis</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ route('admin.colis.index', ['status' => 'created']) }}" class="nav-link @yield('colis_nav_pending')">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Nouveaux colis</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ route('admin.relay-points.index') }}" class="nav-link @yield('colis_nav_relay')">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Points Relais</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ route('admin.colis.finance') }}" class="nav-link @yield('colis_nav_finance')">
                                        <i class="fas fa-money-bill-wave nav-icon text-success"></i>
                                        <p>Finance & COD</p>
                                    </a>
                                </li>
                            </ul>
                        </li>
                        <li class="nav-item has-treeview @yield('transport_nav_open')">
                            <a href="#" class="nav-link @yield('transport_nav')">
                                <i class="nav-icon fas fa-car"></i>
                                <p>
                                    Transport
                                    <i class="right fas fa-angle-left"></i>
                                </p>
                            </a>
                            <ul class="nav nav-treeview">
                                <li class="nav-item">
                                    <a href="{{ route('admin.transport.dashboard') }}" class="nav-link @yield('transport_dashboard_nav')">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Tableau de bord</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ route('admin.transport.bookings.index') }}" class="nav-link @yield('transport_bookings_nav')">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Réservations</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ route('admin.transport.vehicles.index') }}" class="nav-link @yield('transport_vehicles_nav')">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Véhicules</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ route('admin.transport.pricing.index') }}" class="nav-link @yield('transport_pricing_nav')">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Tarification</p>
                                    </a>
                                </li>
                            </ul>
                        </li>

                        <li class="nav-item has-treeview @yield('add_restaurant_open_nav')">
                            <a href="#" class="nav-link @yield('add_restaurant_nav')">
                                <i class="nav-icon fas fa-building"></i>
                                <p>
                                    Restaurant
                                    <i class="right fas fa-angle-left"></i>
                                </p>
                            </a>
                            <ul class="nav nav-treeview">
                                <li class="nav-item">
                                    <a href="{{ route('restaurant.index') }}" class="nav-link @yield('add_restaurant_all_nav')">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Tous les restaurants</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{route('admin.pending')}}" class="nav-link @yield('add_restaurant_pending_nav')">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Demandes en attente</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ route('restaurant.create') }}" class="nav-link @yield('add_restaurant_create_nav')">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Ajouter un restaurant</p>
                                    </a>
                                </li>
                            </ul>
                        </li>
                        <li class="nav-item has-treeview @yield('cuisine_nav_open')">
                            <a href="#" class="nav-link @yield('cuisine_nav')">
                                <i class="nav-icon fas fa-hamburger"></i>
                                <p>
                                    Cuisine
                                    <i class="right fas fa-angle-left"></i>
                                </p>
                            </a>
                            <ul class="nav nav-treeview">
                                <li class="nav-item">
                                    <a href="{{route('cuisine.index')}}" class="nav-link @yield('cuisine_nav_all')">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Toutes les cuisines</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{route('cuisine.create')}}" class="nav-link @yield('cuisine_nav_add')">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Ajouter une cuisine</p>
                                    </a>
                                </li>
                            </ul>
                        </li>
                        
                        <li class="nav-item has-treeview @yield('news_nav_open')">
                            <a href="#" class="nav-link @yield('news_nav')">
                                <i class="nav-icon fas fa-newspaper"></i>
                                <p>
                                    Actualités
                                    <i class="right fas fa-angle-left"></i>
                                </p>
                            </a>
                            <ul class="nav nav-treeview">
                                <li class="nav-item">
                                    <a href="{{route('news.index')}}" class="nav-link @yield('news_nav_all')">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Toutes les actualités</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{route('news.create')}}" class="nav-link @yield('news_nav_add')">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Ajouter une actualité</p>
                                    </a>
                                </li>
                            </ul>
                        </li>
                        
                        <li class="nav-item had-treeview">
                            <a class="nav-link @yield('charge_nav')" href="{{route('charge.index')}}">
                                <i class="nav-icon fas fa-dollar-sign"></i>
                                <p>
                                    Charges
                                </p>
                            </a>
                        </li>
                        
                        <li class="nav-item has-treeview @yield('driver_nav_open')">
                            <a href="#" class="nav-link @yield('driver_nav')">
                                <i class="nav-icon fas fa-taxi"></i>
                                <p>
                                    Livreurs
                                    <i class="right fas fa-angle-left"></i>
                                </p>
                            </a>
                            <ul class="nav nav-treeview">
                                <li class="nav-item">
                                    <a href="{{ route('driver.index') }}" class="nav-link @yield('driver_nav_all')">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Tous les livreurs</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ route('driver.create') }}" class="nav-link @yield('driver_nav_add')">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Ajouter un livreur</p>
                                    </a>
                                </li>

                            </ul>
                        </li>
                        
                        <li class="nav-item has-treeview @yield('payout_nav_open')">
                            <a href="#" class="nav-link @yield('payout_nav')">
                                <i class="nav-icon fas fa-dollar-sign"></i>
                                <p>
                                    Paiements
                                    <i class="right fas fa-angle-left"></i>
                                </p>
                            </a>
                            <ul class="nav nav-treeview">
                                <li class="nav-item">
                                    <a href="{{route('restaurant_payout')}}" class="nav-link @yield('payout_nav_restaurant')">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Paiement restaurant</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{route('driver_payout')}}" class="nav-link @yield('payout_nav_driver')">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Paiement livreur</p>
                                    </a>
                                </li>
                            </ul>
                        </li>
                        
                        <li class=" nav-item has-treeview">
                            <a class="nav-link @yield('user_nav')"  href="{{ route('user.index') }}">
                                <i class="nav-icon fas fa-users "></i>
                                <p>
                                    Utilisateurs
                                </p>
                            </a>
                        </li>
                        <li class="nav-item has-treeview">
                            <a class="nav-link @yield('profile_nav')" href="{{ route('admin.profile') }}">
                                <i class="nav-icon fas fa-user"></i>
                                <p>
                                    Profil
                                </p>
                            </a>
                        </li>
                        <li class=" nav-item has-treeview">
                            <a class="nav-link" href="{{ route('logout') }}">
                                <i class="nav-icon fas fa-power-off"></i>
                                <p>
                                    Déconnexion
                                </p>
                            </a>
                        </li>
                    @elseif(auth()->check() and auth()->user()->type === 'restaurant' and auth()->user()->restaurant()->first()->services === 'both')
                        <li class="nav-item">
                            <a class="nav-link @yield('dashboard_nav')" href="{{url('restaurant')}}">
                                <i class="nav-icon fas fa-tachometer-alt "></i>
                                <p>
                                    Tableau de bord
                                </p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link @yield('media_nav')" href="{{ route('restaurant.media.index') }}">
                                <i class="nav-icon fas fa-images"></i>
                                <p>Médias (galerie)</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link @yield('menu_nav')" href="{{ route('restaurant.menu.index') }}">
                                <i class="nav-icon fas fa-utensils"></i>
                                <p>Menu (moderne)</p>
                            </a>
                        </li>
                        <li class="nav-item has-treeview">
                            <a href="#" class="nav-link @yield('category_nav')">
                                <i class="nav-icon fas fa-list-alt"></i>
                                <p>
                                    Catégories
                                    <i class="right fas fa-angle-left"></i>
                                </p>
                            </a>
                            <ul class="nav nav-treeview">
                                <li class="nav-item">
                                    <a href="{{route('category.index')}}" class="nav-link">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Toutes les catégories</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{route('category.create')}}" class="nav-link">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Ajouter une catégorie</p>
                                    </a>
                                </li>
                            </ul>
                        </li>
                        <!--<li class="nav-item has-treeview">-->
                        <!--    <a href="{{route('add-on.index')}}" class="nav-link @yield('add_on_nav')">-->
                        <!--         <i class="nav-icon fas fa-puzzle-piece"></i>-->
                        <!--        <p>Add ons</p>-->
                        <!--    </a>-->
                        <!--</li>-->
                        <li class="nav-item has-treeview @yield('vouchers_nav_open')">
                            <a href="" class="nav-link @yield('vouchers_nav')">
                                 <i class="nav-icon fas fa-tags"></i>
                                <p>Bons de réduction<i class="fas fa-angle-left right"></i></p>
                            </a>
                            <ul class="nav nav-treeview">
                                <li class="nav-item">
                                    <a href="{{route('voucher.index')}}" class="nav-link @yield('vouchers_nav_index')">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Tous les bons</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{route('voucher.create')}}" class="nav-link @yield('vouchers_nav_create')">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Ajouter un bon</p>
                                    </a>
                                </li>
                            </ul>
                        </li>
                        <li class="nav-item has-treeview @yield('working_hour_nav_open')">
                            <a href="#" class="nav-link @yield('working_hour_nav')">
                                <i class="nav-icon fas fa-calendar-alt"></i>
                                <p>
                                    Horaires
                                    <i class="right fas fa-angle-left"></i>
                                </p>
                            </a>
                            <ul class="nav nav-treeview">
                                <li class="nav-item">
                                    <a href="{{route('working_hour.index')}}" class="nav-link">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Voir les horaires</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{route('working_hour.create')}}" class="nav-link">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Ajouter un horaire</p>
                                    </a>
                                </li>
                            </ul>
                        </li>
                        <li class="nav-item has-treeview">
                            <a href="#" class="nav-link @yield('product_nav')">
                                <i class="nav-icon fas fa-list-alt"></i>
                                <p>
                                    Produits
                                    <i class="right fas fa-angle-left"></i>
                                </p>
                            </a>
                            <ul class="nav nav-treeview">
                                <li class="nav-item">
                                    <a href="{{route('product.index')}}" class="nav-link">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Tous les produits</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{route('product.create')}}" class="nav-link">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Ajouter un produit</p>
                                    </a>
                                </li>
                            </ul>
                        </li>
                        <li class="nav-item has-treeview @yield('order_nav_open')">
                            <a href="#" class="nav-link @yield('order_nav')">
                                <i class="nav-icon fas fa-shopping-cart"></i>
                                <p>
                                    Commandes
                                    <i class="right fas fa-angle-left"></i>
                                </p>
                            </a>
                            <ul class="nav nav-treeview">
                                <li class="nav-item">
                                    <a href="{{ route('restaurant.kitchen') }}" class="nav-link">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Kitchen Display</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{route('restaurant.all_orders')}}" class="nav-link @yield('order_nav_all')">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Nouvelles commandes ({{ \App\Order::where('restaurant_id', auth()->user()->restaurant()->first()->id ?? 0)->whereStatus('pending')->get()->unique('order_no')->count() }})</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{route('restaurant.getpreparing')}}" class="nav-link @yield('order_nav_preparing')">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>En préparation ({{ \App\Order::where('restaurant_id', auth()->user()->restaurant()->first()->id ?? 0)->whereStatus('prepairing')->get()->unique('order_no')->count() }})</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{route('restaurant.pending_orders')}}" class="nav-link @yield('order_nav_pending')">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Assignées ({{ \App\Order::where('restaurant_id', auth()->user()->restaurant()->first()->id ?? 0)->whereStatus('assign')->get()->unique('order_no')->count() }})</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{route('restaurant.complete_orders')}}" class="nav-link @yield('order_nav_complete')">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Terminées ({{ \Schema::hasTable('completed_orders') ? \App\CompletedOrder::where('restaurant_id', auth()->user()->restaurant()->first()->id ?? 0)->whereStatus('completed')->get()->unique('order_no')->count() : 0 }})</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{route('restaurant.cancel_orders')}}" class="nav-link @yield('order_nav_cancel')">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Annulées ({{ \App\Order::where('restaurant_id', auth()->user()->restaurant()->first()->id ?? 0)->whereStatus('cancelled')->get()->unique('order_no')->count() }})</p>
                                    </a>
                                </li>
                            </ul>
                        </li>
                        <li class="nav-item">
                             <a class=" nav-link @yield('earnings_nav')" href="{{route('r_earnings.index')}}">
                                <i class="nav-icon fas fa-history"></i>
                                <p>Historique des paiements</p>
                            </a>
                        </li>
                        <!--<li class="nav-item has-treeview">-->
                        <!--    <a href="#" class="nav-link @yield('working_hour_nav')">-->
                        <!--        <i class="nav-icon fas fa-calendar-times"></i>-->
                        <!--        <p>-->
                        <!--            Working Hours-->
                        <!--            <i class="right fas fa-angle-left"></i>-->
                        <!--        </p>-->
                        <!--    </a>-->
                        <!--    <ul class="nav nav-treeview">-->
                        <!--        <li class="nav-item">-->
                        <!--            <a href="{{route('working_hour.index')}}" class="nav-link">-->
                        <!--                <i class="far fa-circle nav-icon"></i>-->
                        <!--                <p>List</p>-->
                        <!--            </a>-->
                        <!--        </li>-->
                        <!--        <li class="nav-item">-->
                        <!--            <a href="{{route('working_hour.create')}}" class="nav-link">-->
                        <!--                <i class="far fa-circle nav-icon"></i>-->
                        <!--                <p>Add</p>-->
                        <!--            </a>-->
                        <!--        </li>-->
                        <!--    </ul>-->
                        <!--</li>-->
{{--                        <li class=" nav-link">--}}
{{--                            <a href="{{ route('profile') }}">--}}
{{--                                <i class="nav-icon fas fa-user"></i>--}}
{{--                                <p>--}}
{{--                                    Profile--}}
{{--                                    --}}{{-- <i class="right fas fa-angle-left"></i>--}}
{{--                                </p>--}}
{{--                            </a>--}}
{{--                        </li>--}}
                        <!--<li class="nav-item ">-->
                        <!--    <a class="nav-link " href="{{ route('delivery_boundary') }}">-->
                        <!--        <i class="nav-icon fas fa-map-marker"></i>-->
                        <!--        <p>-->
                        <!--            Delivery Boundary-->
                        <!--        </p>-->
                        <!--    </a>-->
                        <!--</li>-->
                        <li class=" nav-item">
                            <a class=" nav-link" href="{{ route('logout') }}">
                                <i class="nav-icon fas fa-power-off"></i>
                                <p>
                                    Déconnexion
                                </p>
                            </a>
                        </li>

                     @elseif(auth()->check() and auth()->user()->type === 'restaurant' and auth()->user()->restaurant()->first()->services === 'delivery')
                     <!-- Here starts the delivery nav-bar -->
                        <li class="nav-item">
                            <a class="nav-link @yield('colis_nav')" href="{{ route('admin.colis.index') }}" style="background-color: #ff0000 !important; color: #fff !important; margin-bottom: 10px;">
                                <i class="nav-icon fas fa-box"></i>
                                <p>
                                    LIVRAISON COLIS
                                </p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link @yield('dashboard_nav')" href="#">
                                <i class="nav-icon fas fa-tachometer-alt "></i>
                                <p>
                                    Tableau de bord
                                </p>
                            </a>
                        </li>
                       <li class="nav-item has-treeview">
                            <a href="#" class="nav-link @yield('order_nav')">
                                <i class="nav-icon fas fa-shopping-cart"></i>
                                <p>
                                    Orders
                                    <i class="right fas fa-angle-left"></i>
                                </p>
                            </a>
                            <ul class="nav nav-treeview">
                                <li class="nav-item">
                                    <a href="{{route('delivery.all_orders')}}" class="nav-link">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Toutes les commandes</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{route('delivery.complete_orders')}}" class="nav-link">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Complétées</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{route('delivery.cancel_orders')}}" class="nav-link">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Annulées</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{route('delivery.pending_orders')}}" class="nav-link">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>En attente</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{route('delivery.schedule_orders')}}" class="nav-link">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Programmées</p>
                                    </a>
                                </li>
                            </ul>
                        </li>
                        <li class="nav-item has-treeview">
                             <a class=" nav-link @yield('earnings_nav')" href="#">
                                <i class="nav-icon fas fa-history"></i>
                                <p>
                                    Historique des paiements
                                    <i class="right fas fa-angle-left"></i>
                                </p>
                            </a>
                            <ul class="nav nav-treeview">
                                <li class="nav-item">
                                    <a href="{{route('d_earnings.index')}}" class="nav-link ">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Revenus totaux</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{route('d_earnings.create')}}" class="nav-link ">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Historique des paiements</p>
                                    </a>
                                </li>
                            </ul>
                        </li>
                        <li class=" nav-item">
                            <a class=" nav-link" href="{{ route('logout') }}">
                                <i class="nav-icon fas fa-power-off"></i>
                                <p>
                                    Déconnexion
                                </p>
                            </a>
                        </li>
                    @endif
                </ul>
            </nav>
            <!-- /.sidebar-menu -->
        </div>
        <!-- /.sidebar -->
    </aside>
    <div class="content-wrapper">
        <!-- Content Header (Page header) -->
        @yield('content')
    </div>
</div>
<footer class="main-footer">
    <strong>Copyright &copy; {{date('Y')}} <a href="#">Buntu Delice</a>.</strong>
    Tous droits réservés.
</footer>
</div><!-- container -->

<!-- Notification panel -->

<div class="modal right fade" id="myModal2" tabindex="-1" role="dialog" aria-labelledby="myModalLabel2">
    <div class="modal-dialog" role="document">
        <div class="modal-content pb-5">

            <div class="modal-header p-3">
                <h4 class="modal-title" id="notiTitle"></h4>
                <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true" style="float:left"><i class="fas fa-arrow-right" style="margin-top:5px;"></i></span></button>
            </div>
            <div class="modal-body p-0 mb-5" id="notiBody">                   
            </div>

        </div><!-- modal-content -->
    </div><!-- modal-dialog -->
</div><!-- modal -->

<!-- ./wrapper -->

<!-- jQuery -->
<script src="{{ asset(env('ASSET_URL') .'plugins/jquery/jquery.min.js')}}"></script>
<!-- jQuery UI 1.11.4 -->
<script src="{{ asset(env('ASSET_URL') .'plugins/jquery-ui/jquery-ui.min.js')}}"></script>
<!-- Resolve conflict in jQuery UI tooltip with Bootstrap tooltip -->
<script>
    $.widget.bridge('uibutton', $.ui.button)
</script>
<!-- Bootstrap 4 -->
<script src="{{ asset(env('ASSET_URL') .'plugins/bootstrap/js/bootstrap.bundle.min.js')}}"></script>
<!-- ChartJS -->
<script src="{{ asset(env('ASSET_URL') .'plugins/chart.js/Chart.min.js')}}"></script>
<!-- Sparkline -->
<script src="{{ asset(env('ASSET_URL') .'plugins/sparklines/sparkline.js')}}"></script>
<!-- JQVMap -->
<script src="{{ asset(env('ASSET_URL') .'plugins/jqvmap/jquery.vmap.min.js')}}"></script>
<script src="{{ asset(env('ASSET_URL') .'plugins/jqvmap/maps/jquery.vmap.usa.js')}}"></script>
<!-- jQuery Knob Chart -->
<script src="{{ asset(env('ASSET_URL') .'plugins/jquery-knob/jquery.knob.min.js')}}"></script>
<!-- daterangepicker -->
<script src="{{ asset(env('ASSET_URL') .'plugins/moment/moment.min.js')}}"></script>
<script src="{{ asset(env('ASSET_URL') .'plugins/daterangepicker/daterangepicker.js')}}"></script>
<!-- Tempusdominus Bootstrap 4 -->
<script
    src="{{ asset(env('ASSET_URL') .'plugins/tempusdominus-bootstrap-4/js/tempusdominus-bootstrap-4.min.js')}}"></script>
<!-- Summernote -->
<script src="{{ asset(env('ASSET_URL') .'plugins/summernote/summernote-bs4.min.js')}}"></script>
<!-- overlayScrollbars -->
<script src="{{ asset(env('ASSET_URL') .'plugins/overlayScrollbars/js/jquery.overlayScrollbars.min.js')}}"></script>
<!-- AdminLTE App -->
<script src="{{ asset(env('ASSET_URL') .'dist/js/adminlte.js')}}"></script>
<script src="{{ asset(env('ASSET_URL') .'plugins/datatables/jquery.dataTables.js')}}"></script>
<script src="{{ asset(env('ASSET_URL') .'plugins/datatables-bs4/js/dataTables.bootstrap4.js')}}"></script>
<!-- AdminLTE dashboard demo (This is only for demo purposes) -->
<script src="{{ asset(env('ASSET_URL') .'dist/js/pages/dashboard.js')}}"></script>
<!-- AdminLTE for demo purposes -->
<script src="{{ asset(env('ASSET_URL') .'dist/js/demo.js')}}"></script>
@if(auth()->check() and auth()->user()->type === 'restaurant' and auth()->user()->restaurant)
<Script>

get_notification();
setInterval(get_notification, 5000);
function get_notification()
{
    @if(auth()->user()->restaurant)
    var id = {{auth()->user()->restaurant->id}};
    @else
    var id = null;
    @endif
    
    if(!id) {
        console.error('Aucun restaurant associé à ce compte');
        return;
    }
    
    $.ajax({
        type: "GET",
        url: "{{url('/')}}/restaurant/notifications/"+id,
        dataType:'json',
        success: function(data){
            var value = '';
            if(data.count > 0){
                data.orders.forEach( function(element, index) {
                    value +=` <a href="{{url('/')}}/restaurant/show_order/`+element.order_no+`" class="dropdown-item">
                            <i class="fas fa-envelope mr-2"></i>`+element.order_no+`<span class="float-right text-muted text-sm">
                            `+element.time+`</span> </a> <div class="dropdown-divider"></div>`;
                });
            }else{
                value += `<a class="dropdown-item text-center">
                            <b>Aucune nouvelle notification</b>
                          </a> <div class="dropdown-divider"></div>`;
            }
            if(document.getElementById('notiBody')) {
                document.getElementById('notiBody').innerHTML = value;
            }
            if(document.getElementById('notiTitle')) {
                document.getElementById('notiTitle').innerHTML = data.count + ' Notifications';
            }
            if(document.getElementById('notiBell')) {
                document.getElementById('notiBell').innerHTML = data.count;
            }
            if(data.new && document.getElementById('myAudio')){
                document.getElementById('myAudio').play();
            }
        },
        error: function(xhr, status, error) {
            console.error('Erreur lors de la récupération des notifications:', error);
        }
    });
  
}
</Script>
@endif
@yield('script')
<script>
<task>
  <title>Créer le module Transport/Reservation en clonant le modèle Food Delivery</title>

  <objectif_global>
    Implémenter un module "Transport" dans BantuDelice (Laravel) en réutilisant l’architecture, les patterns et les briques du module "Livraison de repas".
    Le module doit couvrir 3 sous-produits : Taxi (ride-hailing), Covoiturage (ride-sharing), Location de voiture (rental).
  </objectif_global>

  <principes_non_negociables>
    1) Lecture avant écriture : scanner le module Food existant (routes, controllers, services, events, jobs, models, migrations, policies, notifications, payments, tracking).
    2) Copier le modèle ET adapter proprement : pas de duplication sauvage, tout doit être renommé et cohérent.
    3) Aucun breaking change : pas de modification du module Food sauf extraction de composants partagés (si nécessaire).
    4) Approche DB-driven : migrations + modèles + validations + policies avant UI.
    5) Traçabilité : logs, events, et états de réservation stricts (FSM).
  </principes_non_negociables>

  <phase0_scan_obligatoire>
    <livrables>
      - INVENTAIRE_FOOD.md : liste exhaustive des fichiers Food (backend + frontend), routes, tables, jobs, events, endpoints, webhooks paiement, websockets tracking.
      - MATRICE_REUTILISATION.md : quoi copier tel quel, quoi factoriser en "Shared", quoi réécrire.
    </livrables>
    <commandes>
      - grep -R "food\|restaurant\|order\|driver\|tracking\|payment" -n ./ (adapter au repo)
      - php artisan route:list | grep -i "food\|order"
      - lister tables liées Food (migrations + schema dump)
    </commandes>
  </phase0_scan_obligatoire>

  <phase1_domain_model_transport>
    <exigences_metier>
      <taxi>
        - Demande de course (pickup, dropoff, distance, ETA, pricing estimate)
        - Matching chauffeur (disponibilité, zone, rating)
        - États: DRAFT -> REQUESTED -> OFFERED/ASSIGNED -> DRIVER_ARRIVING -> IN_PROGRESS -> COMPLETED -> PAID -> CLOSED
        - Annulation + frais éventuels
      </taxi>
      <covoiturage>
        - Création trajet par conducteur (itinerary, places, prix/place, horaires)
        - Réservation place(s) par passager
        - États: PUBLISHED -> BOOKED -> CONFIRMED -> IN_PROGRESS -> COMPLETED -> CLOSED
        - Annulation selon politique
      </covoiturage>
      <location_voiture>
        - Catalogue véhicules (disponibilité calendrier, dépôt/garantie, options)
        - Réservation (dates, lieu retrait, retour, conditions)
        - États: REQUESTED -> CONFIRMED -> PICKED_UP -> RETURNED -> INSPECTED -> PAID -> CLOSED
      </location_voiture>
    </exigences_metier>

    <a_creer_tables_minimales>
      - transport_products (taxi|carpool|rental)
      - transport_requests (ride/rental request)
      - transport_offers (propositions prix/assignation)
      - transport_bookings (réservations confirmées)
      - transport_vehicles (pour rental) + availability
      - transport_drivers_profiles (si déjà drivers, réutiliser)
      - transport_tracking_points (positions, vitesse, timestamp)
      - transport_pricing_rules (zone, km, minute, surge)
      - transport_payments (ou réutiliser payments existant)
      - transport_reviews, transport_loyalty_points (ou réutiliser existant)
    </a_creer_tables_minimales>

    <regles>
      - Utiliser UUID si Food l’utilise.
      - Soft deletes + audit columns.
      - Indexes sur user_id, driver_id, status, created_at.
    </regles>
  </phase1_domain_model_transport>

  <phase2_api_routes>
    <routes_minimales>
      <client>
        POST /api/v1/transport/estimate
        POST /api/v1/transport/requests
        GET  /api/v1/transport/requests/{id}
        POST /api/v1/transport/requests/{id}/cancel
        POST /api/v1/transport/bookings/{id}/pay
        GET  /api/v1/transport/bookings/{id}/track
      </client>

      <driver>
        GET  /api/v1/driver/transport/requests/nearby
        POST /api/v1/driver/transport/requests/{id}/accept
        POST /api/v1/driver/transport/bookings/{id}/status
        POST /api/v1/driver/transport/bookings/{id}/location
      </driver>

      <admin>
        GET  /admin/transport/dashboard
        CRUD /admin/transport/pricing-rules
        CRUD /admin/transport/vehicles
        CRUD /admin/transport/bookings
      </admin>
    </routes_minimales>

    <websocket_si_existant>
      - Réutiliser le canal tracking Food (ou cloner) : "transport.booking.{id}.tracking"
    </websocket_si_existant>
  </phase2_api_routes>

  <phase3_reuse_food_components>
    <a_reutiliser_de_food>
      - Paiement : même pipeline (intent, webhook, confirmation, refund partiel)
      - Tracking : même format event + stockage points
      - Notifications : push/SMS/email (booking accepted, driver arriving, completed, paid)
      - Fidélisation : points/cashback par transaction
      - Marketing : coupons/promo codes (adapter aux produits transport)
      - Social : partage trajet / lien de suivi / parrainage (si existant)
    </a_reutiliser_de_food>

    <a_factoriser_en_shared_si_necessaire>
      - Money/Price calculator
      - State machine + guards
      - Address/Geo utils
      - Payment contracts
      - Notification templates
    </a_factoriser_en_shared_si_necessaire>
  </phase3_reuse_food_components>

  <phase4_frontend_ui>
    <pages_minimales>
      - /transport (choix taxi/covoiturage/location)
      - /transport/taxi (pickup/dropoff + estimate + request)
      - /transport/carpool (liste trajets + réservation)
      - /transport/rental (catalogue + disponibilité + réservation)
      - /transport/booking/{id} (status + tracking + paiement)
      - /driver/transport (demandes + course en cours + envoi position)
      - /admin/transport (dash + gestion)
    </pages_minimales>

    <exigences_uiux>
      - Copier le design system Food (composants, layout)
      - Ajout UX moderne : timeline d’état, carte live, récap prix clair, CTA uniques
      - Zéro régression styles globaux
    </exigences_uiux>
  </phase4_frontend_ui>

  <phase5_quality_gates>
    <tests_obligatoires>
      - Feature tests: création request, accept driver, tracking, paiement, annulation
      - Tests de règles pricing (zone/km/minute)
      - Tests de sécurité (policies: user ne voit que ses bookings)
    </tests_obligatoires>

    <observabilite>
      - Logs structurés + correlation_id par réservation
      - Events: TransportRequestCreated, BookingAssigned, TrackingUpdated, PaymentConfirmed
    </observabilite>
  </phase5_quality_gates>

  <output_format_obligatoire>
    <fichiers_et_routes>
      <fichiers_a_creer>...</fichiers_a_creer>
      <fichiers_a_modifier>...</fichiers_a_modifier>
      <routes_exactes>...</routes_exactes>
    </fichiers_et_routes>

    <implementation>
      Code complet (migrations, models, controllers, services, events, jobs, policies, tests, UI pages)
    </implementation>

    <rapport>
      - Ce qui a été fait
      - Preuves (commandes + extraits route:list + tests)
      - ToDo list restante (MS Project style)
      - Risques + plan rollback
    </rapport>
  </output_format_obligatoire>
</task>
    $(function () {
        $('[data-toggle="tooltip"]').tooltip()
    })
</script>
</body>
</html>
