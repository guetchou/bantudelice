@extends('frontend.layouts.app')
@section('title','BantuDelice | Accueil')
@section('keywords','livraison repas, colis, transport')
@php
    $foodEnabled = (bool) config('bantudelice_modules.food.enabled', true);
    $colisEnabled = (bool) config('bantudelice_modules.colis.enabled', true);
@endphp
@section('content')

		<!-- banner-text -->
		<div class="banner-text">
			<div class="container">
				<h2>{{ $foodEnabled ? 'Repas, colis et transport' : 'Colis et transport' }} <br> <span>des services locaux simples et fiables.</span></h2>
				<div class="agileits_search">
					@if($foodEnabled)
					<form action="{{route('serach')}}" method="get">
						<input name="qurey" type="text" placeholder="Rechercher un restaurant ou un plat" required="">
						<input type="submit" value="Rechercher">
					</form>
					@elseif($colisEnabled)
					<a href="{{ route('colis.landing') }}" style="display:inline-flex;align-items:center;justify-content:center;background:#16a34a;color:#fff;font-weight:700;padding:.7rem 1.35rem;border-radius:999px;border:none;cursor:pointer;text-decoration:none;">Découvrir le service colis</a>
					@endif
				</div>
			</div>
		</div>
	</div>
	<!-- //banner -->
<!-- order -->
	@if($foodEnabled)
	<div class="wthree-order">
		<img src="{{asset('images/i2.jpg')}}" class="m9order-img" alt=""/>
		<div class="container">
			<h3 class="m9ls-title">Restaurants populaires</h3>
			<p class="m9lsorder-text">Commandez auprès de restaurants sélectionnés près de chez vous.</p>
			<div class="order-agileinfo">

  @foreach($restaurants as $restaurant)
  <div class="m9resturants col-md-3 col-sm-3 col-xs-6">
  	<a href="{{route('resturant.detail',$restaurant->id)}}">
    <div class="thumbnail">
      <img src="{{asset('images/restaurant_images/' . ($restaurant->logo ?: 'placeholder.png'))}}" alt="..." style="height:200px;" onerror="this.src='{{ asset('images/placeholder.png') }}'">
      <div class="caption">
        <h5>{{$restaurant->name}}</h5>
        <p>
            <b>{{ $restaurant->cuisines->pluck('name')->implode(', ') }}</b>
         <!--   @foreach($restaurant->cuisines as $cuisine)-->
        	<!--{{  $cuisine->name }},-->
        	<!--@endforeach-->
        </p>
        <p><span>{{ number_format($restaurant->ratings, 1) }}/5</span></p>
      </div>
    </div>
    </a>
  </div>
  @endforeach

				<div class="clearfix"> </div>
			</div>
		</div>
	</div>
	@endif
	<!-- //order -->
	<!-- deals -->
	<div class="m9agile-deals" id="services">
		<div class="container">
			<h3 class="m9ls-title">Services essentiels</h3>
			<div class="row">
			    
				@if($foodEnabled)
				<div class="col-md-6 col-sm-6 deals-grids">

					<div class="deals-right">
						<h4>Livraison de repas</h4>
						<p>Commandez vos repas et faites-vous livrer rapidement à domicile ou au bureau.</p>
					</div>
					<div class="clearfix"> </div>
				</div>
				@endif
				@if($foodEnabled)
				<div class="col-md-6 col-sm-6 deals-grids">

					<div class="deals-right">
						<h4>Suivi de commande</h4>
						<p>Consultez l'avancement de votre commande et restez informé jusqu'à la livraison.</p>
					</div>
					<div class="clearfix"> </div>
				</div>
				@endif
				<div class="col-md-6 col-sm-6 deals-grids">

					<div class="deals-right">
						<h4>Transport local</h4>
						<p>Réservez un trajet ou une solution de déplacement adaptée à vos besoins locaux.</p>
					</div>
					<div class="clearfix"> </div>
				</div>
				@if($colisEnabled)
				<div class="col-md-6 col-sm-6 deals-grids">
					<a href="{{ route('colis.landing') }}" style="text-decoration: none; color: inherit;">
						
						<div class="deals-right">
							<h4>Colis</h4>
							<p>Envoyez ou suivez un colis avec un parcours simple et clair.</p>
							<p style="margin-top: 0.5rem;"><a href="{{ route('colis.create') }}">Expédier</a> | <a href="{{ route('colis.track_public') }}">Suivre</a></p>
						</div>
					</a>
					<div class="clearfix"> </div>
				</div>
				@endif
				<div class="clearfix"> </div>
			</div>
		</div>
	</div>
	<!-- //deals -->
        <div class="m9agile-deals" id="services">
            <div class="container">
                <h3 class="m9ls-title">Autres services</h3>
                <div class="dealsrow">
                    
                    <div class="col-md-6 col-sm-6 deals-grids">
                        
                        <div class="deals-right">
                            <h4>Commandes spéciales</h4>
                            <p>Passez vos demandes spécifiques via notre réseau de partenaires locaux.</p>
                        </div>
                        <div class="clearfix"> </div>
                    </div>
                    <div class="col-md-6 col-sm-6 deals-grids">
                        
                        <div class="deals-right">
                            <h4>Assistance courses</h4>
                            <p>Pour certains besoins ponctuels, notre équipe peut vous orienter vers le bon service.</p>
                        </div>
                        <div class="clearfix"> </div>
                    </div>
                    <div class="col-md-6 col-sm-6 deals-grids">
                        
                        <div class="deals-right">
                            <h4>Événements</h4>
                            <p>Préparez vos commandes pour anniversaires, réunions ou occasions spéciales.</p>
                        </div>
                        <div class="clearfix"> </div>
                    </div>
                    @if($colisEnabled)
                    <div class="col-md-6 col-sm-6 deals-grids">
                        
                        <div class="deals-right">
                            <h4>Colis</h4>
                            <p>Nous prenons en charge des colis locaux avec suivi et coordination.</p>
                        </div>
                        <div class="clearfix"> </div>
                    </div>
                    @endif
                    <div class="col-md-6 col-sm-6 deals-grids">
                        
                        <div class="deals-right">
                            <h4>Demandes personnalisées</h4>
                            <p>Certaines demandes peuvent être traitées sur devis selon disponibilité.</p>
                        </div>
                        <div class="clearfix"> </div>
                    </div>
                    <div class="col-md-6 col-sm-6 deals-grids">
                        
                        <div class="deals-right">
                            <h4>Courses du quotidien</h4>
                            <p>Nous facilitons aussi certaines courses et achats du quotidien.</p>
                        </div>
                        <div class="clearfix"> </div>
                    </div>
                    
                    <div class="col-md-6 col-sm-6 deals-grids">
                        
                        <div class="deals-right">
                            <h4>Collecte ponctuelle</h4>
                            <p>Des collectes ponctuelles peuvent être organisées selon la zone couverte.</p>
                        </div>
                        <div class="clearfix"> </div>
                    </div>
                    <div class="col-md-6 col-sm-6 deals-grids">
                        
                        <div class="deals-right">
                            <h4>Livraison locale</h4>
                            <p>Une logistique locale plus simple pour vos besoins de proximité.</p>
                        </div>
                        <div class="clearfix"> </div>
                    </div>
                    <div class="clearfix"> </div>
                </div>
            </div>
        </div>
	<!-- dishes -->
	@if($foodEnabled)
	<div class="m9agile-spldishes">
		<div class="container">
			<h3 class="m9ls-title">Sélection du moment</h3>
			<div class="spldishes-agileinfo">
				<div class="col-md-3 spldishes-m9left">
					<h5 class="m9ltitle">Les suggestions BantuDelice</h5>
					<p>Découvrez quelques produits mis en avant selon les disponibilités du moment.</p>
				</div>
				<div class="col-md-9 spldishes-grids">
					<!-- Owl-Carousel -->
					<div id="owl-demo" class="owl-carousel text-center agileinfo-gallery-row">
						@foreach($products as $product)
						<a href="{{route('pro.detail', $product->id)}}" class="item g1">
							<img class="lazyOwl" src="{{asset('images/product_images/' . ($product->image ?: 'default-food.jpg'))}}" title="{{$product->name}}" alt="{{$product->name}}" style="height:217px;" onerror="this.src='{{ asset('images/product_images/default-food.jpg') }}'">
							<div class="agile-dish-caption">
								<h4>{{$product->name}}</h4>
								<span>Disponible à la commande selon le restaurant</span>
							</div>
						</a>
						@endforeach
					</div>
				</div>
				<div class="clearfix"> </div>
			</div>
		</div>
	</div>
	@endif
	<!-- //dishes -->
@endsection
