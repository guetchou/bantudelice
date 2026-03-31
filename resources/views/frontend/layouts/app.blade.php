<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
<title>@yield('title')</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta name="keywords" content="livraison repas, livraison colis, transport local, BantuDelice" />
<link rel="icon" type="image/x-icon" href="{{asset('favicon.ico')}}">
<script type="application/x-javascript"> addEventListener("load", function() { setTimeout(hideURLbar, 0); }, false); function hideURLbar(){ window.scrollTo(0,1); } </script>
<!-- Custom Theme files -->
<link href="{{asset('frontend/css/bootstrap.css')}}" type="text/css" rel="stylesheet" media="all">
<link href="{{asset('frontend/css/style.css')}}" type="text/css" rel="stylesheet" media="all">
<link href="{{asset('frontend/css/checkout.css')}}" type="text/css" rel="stylesheet" media="all">
<link href="{{asset('frontend/css/font-awesome.css')}}" rel="stylesheet"> <!-- font-awesome icons -->
<link rel="stylesheet" href="{{asset('frontend/css/owl.carousel.css')}}" type="text/css" media="all"/> <!-- Owl-Carousel-CSS -->
<!-- //Custom Theme files -->
<!-- js -->
<script src="{{asset('frontend/js/jquery-2.2.3.min.js')}}"></script>
<!-- //js -->
@yield('style')
<!-- web-fonts -->
<link href="//fonts.googleapis.com/css?family=Berkshire+Swash" rel="stylesheet">
<link href="//fonts.googleapis.com/css?family=Yantramanav:100,300,400,500,700,900" rel="stylesheet">
<!-- //web-fonts -->
<style>
html, body {
    width: 100%;
    max-width: 100%;
    overflow-x: hidden;
}

img, video, iframe, canvas, svg {
    max-width: 100%;
    height: auto;
}

.container,
.container-fluid {
    max-width: 100%;
}

.navigation .navbar {
    margin-bottom: 0;
}

.navigation .navbar-nav {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
}

.navigation .navbar-nav > li > a {
    white-space: normal;
}

.cart.box_1 {
    max-width: 100%;
}

.cart.box_1 .m9view-cart {
    white-space: nowrap;
}

.footer .footer-grids ul,
.footer .footer-grids li,
.footer p {
    overflow-wrap: anywhere;
}

@media (max-width: 1199px) {
    .navigation .navbar-nav {
        gap: 0;
    }

    .navigation .navbar-nav > li > a {
        padding-left: 10px;
        padding-right: 10px;
        font-size: 0.9rem;
    }
}

@media (max-width: 991px) {
    .navigation .navbar-collapse {
        max-height: calc(100vh - 100px);
        overflow-y: auto;
        overflow-x: hidden;
    }

    .cart.box_1 {
        float: none !important;
        margin: 0.75rem 0 0;
        text-align: left;
    }
}
</style>
</head>
<body>
@php
    $foodEnabled = config('bantudelice_modules.food', true);
    $colisEnabled = config('bantudelice_modules.colis', true);
    $transportEnabled = config('bantudelice_modules.transport', true);
@endphp
<!-- banner -->
	<div class="banner @if(url()->current()!=url('/')) about-m9bnr @else @endif" style="background: url({{asset('images/thedrop24BG.jpg')}}) no-repeat center; background-size: cover;
    -webkit-background-size: cover;">
		<!-- header -->
		<div class="header">
			<div class="m9ls-header"><!-- header-one -->
				<div class="container">
					<div class="m9ls-header-left">
						<p></p>
					</div>
					<div class="m9ls-header-right">
						<ul>
							@if(Auth::check())
							<li class="head-dpdn">
								<a href="{{route('user.profile')}}">Profil</a>
							</li>
							<li class="head-dpdn">
								<form method="POST" action="{{ route('user.logout') }}" style="display:inline;">
									@csrf
									<button type="submit" style="background:none;border:0;padding:0;color:inherit;font:inherit;cursor:pointer;">Déconnexion</button>
								</form>
							</li>
							@endif
						</ul>
					</div>
					<div class="clearfix"> </div>
				</div>
			</div>
			<!-- //header-one -->
			<!-- navigation -->
			<div class="navigation agiletop-nav">
				<div class="container">
					<nav class="navbar navbar-default">
						<!-- Brand and toggle get grouped for better mobile display -->
						<div class="navbar-header m9l_logo">
							<button type="button" class="navbar-toggle collapsed navbar-toggle1" data-toggle="collapse" data-target="#bs-megadropdown-tabs">
								<span class="sr-only">Toggle navigation</span>
								<span class="icon-bar"></span>
								<span class="icon-bar"></span>
								<span class="icon-bar"></span>
							</button>
							<a href="{{route('home')}}"><img src="{{asset('frontend/images/BuntuDelice.png')}}" class="img-responsive" height="70" width="150" alt="BantuDelice"></a>
						</div>
						<div class="collapse navbar-collapse" id="bs-megadropdown-tabs">
							<ul class="nav navbar-nav navbar-right">
								<li><a href="{{route('home')}}" class="active">ACCUEIL</a></li>
								<!-- Mega Menu -->
								@if($foodEnabled)
									<li class="dropdown">
										<button type="button" class="dropdown-toggle bd-nav-dropdown-btn" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" style="background:none;border:0;padding:0;">CUISINES <b class="caret"></b></button>
										<ul class="dropdown-menu multi-column columns-3">
											<div class="row">
												<div class="col-sm-12">
													<ul class="multi-column-dropdown">
														<h6>Explorer par cuisine</h6>
													</ul>
												</div>
												@php $cuisines=DB::table('cuisines')->get(); @endphp
												<div class="col-sm-4">
													<ul class="multi-column-dropdown">
													    @foreach($cuisines->slice(0,4) as $cuisine)
														<li><a href="{{route('restaurant.cuisine',$cuisine->id)}}">{{$cuisine->name}}</a></li>
														@endforeach
													</ul>
												</div>
												<div class="col-sm-4">
													<ul class="multi-column-dropdown">
														@foreach($cuisines->slice(4,4) as $cuisine)
														<li><a href="{{route('restaurant.cuisine',$cuisine->id)}}">{{$cuisine->name}}</a></li>
														@endforeach
													</ul>
												</div>
												<div class="col-sm-4">
													<ul class="multi-column-dropdown">
														@foreach($cuisines->slice(8,4) as $cuisine)
														<li><a href="{{route('restaurant.cuisine',$cuisine->id)}}">{{$cuisine->name}}</a></li>
														@endforeach
													</ul>
												</div>
												<div class="clearfix"></div>
											</div>
										</ul>
									</li>
								@endif
								<li><a href="{{ route('home') }}#services">NOS SERVICES</a></li>
								@if($foodEnabled)
									<li><a href="{{ route('track.order') }}">SUIVI COMMANDE</a></li>
								@endif
								@if($colisEnabled || $transportEnabled)
									<li class="dropdown">
										<button type="button" class="dropdown-toggle bd-nav-dropdown-btn" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" style="background:none;border:0;padding:0;">AUTRES SERVICES <b class="caret"></b></button>
										<ul class="dropdown-menu">
											@if($colisEnabled)
												<li><a href="{{ route('colis.landing') }}">Colis</a></li>
												<li><a href="{{ route('colis.track_public') }}">Suivi colis</a></li>
											@endif
											@if($transportEnabled)
												<li><a href="{{ route('transport.taxi') }}">Transport</a></li>
											@endif
										</ul>
									</li>
								@endif
								<li><a href="{{route('about.us')}}">À PROPOS</a></li>
								<li><a href="{{route('contact.us')}}">CONTACT</a></li>
							</ul>
						</div>
						@php
						if(Auth::check())
						{
                        $id=auth()->user()->id;
                        $count_items=DB::table('carts')->where('user_id',$id)->count();
                        }
                        else{
                        $count_items=0;
                        }
                       @endphp
						<div class="cart cart box_1" style=" ">
								<a href="{{route('cart.detail')}}" class="m9view-cart" style="width:auto; margin-top:-17px; padding: 0.4rem 0.75rem; display: inline-block; text-decoration:none;">Panier <span class="badge badge-primary" style="margin-left:0.35rem;">{{$count_items}}</span></a>
						</div>
					</nav>
				</div>
			</div>
			<!-- //navigation -->
		</div>
		<!-- //header-end -->
@yield('content')
	<!-- footer -->
	<div class="footer agileits-m9layouts">
		<div class="container">
			<div class="m9_footer_grids">
				<div class="col-xs-6 col-sm-3 footer-grids m9-agileits">
					<h3>Food</h3>
					<ul>
						@if($foodEnabled)
							<li><a href="{{ route('restaurants.all') }}">Restaurants</a></li>
							<li><a href="{{ route('track.order') }}">Suivre une commande</a></li>
						@endif
						<li><a href="{{route('about.us')}}">À propos</a></li>
						<li><a href="{{route('driver')}}">Devenir livreur</a></li>
						<li><a href="{{route('partner')}}">Devenir partenaire</a></li>
					</ul>
				</div>
				<div class="col-xs-6 col-sm-3 footer-grids m9-agileits">
					<h3>Autres services</h3>
					<ul>
						@if($colisEnabled)
							<li><a href="{{ route('colis.landing') }}">Service colis</a></li>
							<li><a href="{{ route('colis.track_public') }}">Suivre un colis</a></li>
						@endif
						@if($transportEnabled)
							<li><a href="{{ route('transport.taxi') }}">Transport</a></li>
							<li><a href="{{ route('transport.my_bookings') }}">Mes réservations</a></li>
						@endif
					</ul>
				</div>
				<div class="col-xs-6 col-sm-3 footer-grids m9-agileits">
					<h3>Informations légales</h3>
					<ul>
						<li><a href="{{route('terms.conditions')}}">Conditions générales</a></li>
						<li><a href="{{route('privacy.policy')}}">Politique de confidentialité</a></li>
						<li><a href="{{route('refund.policy')}}">Politique de remboursement</a></li>
						<li><a href="{{route('data.deletion')}}">Suppression des données</a></li>
						<li><a href="{{ route('guidance.execution') }}">Guidance execution</a></li>
					</ul>
				</div>
				<div class="col-xs-6 col-sm-3 footer-grids m9-agileits">
					<h3>Support</h3>
					<ul>
						<li><a href="{{route('faq')}}">FAQ</a></li>
						<li><a href="{{route('refund.policy')}}">Retours et remboursements</a></li>
						<li><a href="{{route('contact.us')}}">Nous contacter</a></li>
						
					</ul>
					<p style="margin-top: 1rem; color: #999; font-size: 0.9rem;">Assistance client, suivi de commande et informations utiles.</p>
				</div>
				<div class="clearfix"> </div>
			</div>
		</div>
	</div>
	<div class="copym9-agile">
		<div class="container">
			<p>&copy; {{date('Y')}} BantuDelice. Tous droits réservés</p>
		</div>
	</div>

	<!-- Owl-Carousel-JavaScript -->
	<script src="{{asset('frontend/js/owl.carousel.js')}}"></script>
	<script>
		$(document).ready(function() {
			$("#owl-demo").owlCarousel ({
				items : 3,
				lazyLoad : true,
				autoPlay : true,
				pagination : true,
			});
		});
	</script>
	<!-- //Owl-Carousel-JavaScript -->
	<!-- the jScrollPane script -->
	<script type="text/javascript" src="{{asset('frontend/js/jquery.jscrollpane.min.js')}}"></script>
	<script type="text/javascript" id="sourcecode">
		$(function()
		{
			$('.scroll-pane').jScrollPane();
		});
	</script>
	<!-- start-smooth-scrolling -->
	<script src="{{asset('frontend/js/SmoothScroll.min.js')}}"></script>
	<script type="text/javascript" src="{{asset('frontend/js/move-top.js')}}"></script>
	<script type="text/javascript" src="{{asset('frontend/js/easing.js')}}"></script>
	<script type="text/javascript">
			jQuery(document).ready(function($) {
				$(".scroll").click(function(event){
					event.preventDefault();

			$('html,body').animate({scrollTop:$(this.hash).offset().top},1000);
				});
			});
	</script>
	<!-- //end-smooth-scrolling -->
	<!-- smooth-scrolling-of-move-up -->
	<script type="text/javascript">
		$(document).ready(function() {
			/*
			var defaults = {
				containerID: 'toTop', // fading element id
				containerHoverID: 'toTopHover', // fading element hover id
				scrollSpeed: 1200,
				easingType: 'linear'
			};
			*/

			$().UItoTop({ easingType: 'easeOutQuart' });

		});
	</script>
    <script src="{{asset('frontend/js/bootstrap.js')}}"></script>
@yield('script')
</body>
</html>
