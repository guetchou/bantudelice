<!DOCTYPE html>
<html lang="fr">
<head>
<title>@yield('title')</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta name="keywords" content="abc" />
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
</head>
<body>
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
							<li class="head-dpdn">
							    @if(Auth::check())
								<a href="{{route('user.profile')}}"><i class="fa fa-user" aria-hidden="true"></i> Profil</a>
								@else
								<a href="{{route('user.login')}}"><i class="fa fa-sign-in" aria-hidden="true"></i> Connexion</a>
								@endif
							</li>
							<li class="head-dpdn">
							    @if(Auth::check())
								<a href="{{ route('user.logout') }}"><i class="fa fa-power-off" aria-hidden="true"></i> Déconnexion</a>
								@else
								<a href="{{route('user.signup')}}"><i class="fa fa-user-plus" aria-hidden="true"></i> Inscription</a>
								@endif
							</li>
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
							<a href="{{route('home')}}"><img src="{{asset('frontend/images/BuntuDelice.png')}}" class="img-responsive" height="70" width="150"></a>
						</div>
						<div class="collapse navbar-collapse" id="bs-megadropdown-tabs">
							<ul class="nav navbar-nav navbar-right">
								<li><a href="{{route('home')}}" class="active">ACCUEIL</a></li>
								<!-- Mega Menu -->
								<li class="dropdown">
									<a href="#" class="dropdown-toggle" data-toggle="dropdown">MENU <b class="caret"></b></a>
									<ul class="dropdown-menu multi-column columns-3">
										<div class="row">
											<div class="col-sm-12">
												<ul class="multi-column-dropdown">
													<h6>Cuisines populaires</h6>
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
								<li><a href="#services">SERVICES</a></li>
								<li><a href="{{ route('colis.landing') }}" style="color: #ff0000 !important; font-weight: bold;">LIVRAISON COLIS</a></li>
								<li><a href="{{ route('colis.track_public') }}">SUIVI COLIS</a></li>
								<li><a href="{{route('about.us')}}">À PROPOS</a></li>
								<li><a href="{{route('contact.us')}}">CONTACT</a></li>
								<li class="dropdown">
									<a href="#" class="dropdown-toggle" data-toggle="dropdown">COLIS <b class="caret"></b></a>
									<ul class="dropdown-menu">
										<li><a href="{{ route('colis.mes-envois') }}">Mes envois</a></li>
										<li><a href="{{ route('colis.create') }}">Nouvel envoi</a></li>
									</ul>
								</li>
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
								<a href="{{route('cart.detail')}}" class="m9view-cart" style="width:50px; margin-top:-17px;">
								<span class="badge badge-primary" style="float:right;">{{$count_items}}</span><br><i class="fa fa-cart-arrow-down"></i>
								</a>
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
					<h3>Rejoignez-nous</h3>
					<ul>
						<li><a href="{{ route('colis.landing') }}" style="color: #ff0000; font-weight: bold;">LIVRAISON COLIS</a></li>
						<li><a href="{{ route('colis.track_public') }}">Suivre un colis</a></li>
						<li><a href="{{route('about.us')}}">À propos</a></li>
						<li><a href="{{route('driver')}}">Devenir livreur</a></li>
						<li><a href="{{route('partner')}}">Devenir partenaire</a></li>
					</ul>
				</div>
				<div class="col-xs-6 col-sm-3 footer-grids m9-agileits">
					<h3>Menu</h3>
					<ul>
						<li><a href="#">Menu du jour</a></li>
						<li><a href="#">Déjeuner</a></li>
						<li><a href="#">Dîner</a></li>
						<li><a href="#">Petit-déjeuner</a></li>
					</ul>
				</div>
				<div class="col-xs-6 col-sm-3 footer-grids m9-agileits">
					<h3>Informations légales</h3>
					<ul>
						<li><a href="{{route('terms.conditions')}}">Conditions générales</a></li>
						<li><a href="{{route('privacy.policy')}}">Politique de confidentialité</a></li>
						<li><a href="{{route('refund.policy')}}">Politique de remboursement</a></li>
						<li><a href="{{route('data.deletion')}}">Suppression des données</a></li>
					</ul>
				</div>
				<div class="col-xs-6 col-sm-3 footer-grids m9-agileits">
					<h3>Aide</h3>
					<ul>
						<li><a href="#">FAQ</a></li>
						<li><a href="#">Retours</a></li>
						<li><a href="{{route('contact.us')}}">Nous contacter</a></li>
						
					</ul
					<div class="row">
                    <div class="col-lg-4 col-md-6 col-sm-6 col-6" style="padding:0px;margin:0px;">
                        <img src="{{asset('images/playstore.png')}}" width="80">
                    </div>
                    <div class="col-lg-4 col-md-6 col-sm-6 col-6" style="padding:0px;margin:0px;">
                        <img src="{{asset('images/applestore.png')}}" width="80">
                    </div>
                </div>
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
