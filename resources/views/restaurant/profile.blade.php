@extends('layouts.restaurant_app')
@section('title', 'Mon Profil | ' . \App\Services\ConfigService::getCompanyName() . ' Restaurant')
@section('topbar_title', 'Profil restaurant')
@section('profile_nav', 'active')

@section('content')
    @php
        $restaurantEntity = $restaurantProfile ?? null;
        $restaurantLogoSrc = ($restaurantEntity && $restaurantEntity->logo)
            ? (strpos($restaurantEntity->logo, 'http') === 0 ? $restaurantEntity->logo : asset('images/restaurant_images/' . $restaurantEntity->logo))
            : asset('images/placeholder.png');
    @endphp
    <!-- Content Header -->
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0" style="font-weight: 700; color: #1F2937;">
                        <i class="fas fa-store" style="color: #ff5a1f; margin-right: 0.5rem;"></i>
                        Mon Profil Restaurant
                    </h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('restaurant.dashboard') }}" style="color: #6B7280;">Tableau de bord</a></li>
                        <li class="breadcrumb-item active" style="color: #ff5a1f;">Profil</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <section class="content" style="background: #F9FAFB; min-height: calc(100vh - 120px); padding: 2rem 0;">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    @if(session()->has('alert'))
                        <div class="alert alert-{{ session()->get('alert.type') }} alert-dismissible fade show" 
                             style="border-radius: 12px; border: none; box-shadow: 0 4px 12px rgba(0,0,0,0.1); margin-bottom: 2rem;">
                            <i class="fas fa-{{ session()->get('alert.type') === 'success' ? 'check-circle' : 'exclamation-circle' }} mr-2"></i>
                            {{ session()->get('alert.message') }}
                            <button type="button" class="close" data-dismiss="alert">&times;</button>
                        </div>
                    @endif

                    <div class="row align-items-start">
                        <!-- Sidebar Profile Card -->
                        <div class="col-12 col-xl-4 mb-4">
                            <div class="card shadow-sm" style="border-radius: 20px; border: none; overflow: hidden;">
                                <div class="card-body" style="background: linear-gradient(135deg, #05944F 0%, #009543 100%); padding: 2.5rem 2rem; text-align: center; color: white; position: relative;">
                                    <div style="position: relative; display: inline-block; margin-bottom: 1.5rem;">
                                        <img class="img-fluid rounded-circle"
                                             src="{{ $restaurantLogoSrc }}"
                                             alt="Photo principale du restaurant"
                                             style="width: 140px; height: 140px; object-fit: cover; border: 5px solid rgba(255,255,255,0.3); box-shadow: 0 8px 25px rgba(0,0,0,0.2);">
                                    </div>
                                    
                                    <h3 style="font-size: 1.5rem; font-weight: 700; margin: 0.5rem 0 0.25rem; color: white;">{{ $restaurant->name }}</h3>
                                    <p style="opacity: 0.9; font-size: 0.95rem; margin: 0;">
                                        <i class="fas fa-store mr-1"></i> Restaurant
                                    </p>
                                    
                                    @php
                                        $restaurantData = auth()->user()->restaurant ?? null;
                                        $totalOrders = $restaurantData ? \App\Order::where('restaurant_id', $restaurantData->id)->count() : 0;
                                        $totalProducts = $restaurantData ? \App\Product::where('restaurant_id', $restaurantData->id)->count() : 0;
                                    @endphp
                                    
                                    <div style="background: rgba(255,255,255,0.15); backdrop-filter: blur(10px); border-radius: 16px; padding: 1rem; margin-top: 1.5rem;">
                                        <div class="row text-center">
                                            <div class="col-4">
                                                <div style="font-size: 1.5rem; font-weight: 800;">{{ $totalOrders }}</div>
                                                <div style="font-size: 0.75rem; opacity: 0.85; text-transform: uppercase; letter-spacing: 0.5px;">Commandes</div>
                                            </div>
                                            <div class="col-4" style="border-left: 1px solid rgba(255,255,255,0.2); border-right: 1px solid rgba(255,255,255,0.2);">
                                                <div style="font-size: 1.5rem; font-weight: 800;">{{ $totalProducts }}</div>
                                                <div style="font-size: 0.75rem; opacity: 0.85; text-transform: uppercase; letter-spacing: 0.5px;">Produits</div>
                                            </div>
                                            <div class="col-4">
                                                <div style="font-size: 1.5rem; font-weight: 800;">{{ $restaurantData && $restaurantData->featured ? 'Oui' : 'Non' }}</div>
                                                <div style="font-size: 0.75rem; opacity: 0.85; text-transform: uppercase; letter-spacing: 0.5px;">En vedette</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Info Section -->
                                <div class="card-body" style="padding: 1.5rem;">
                                    <div style="margin-bottom: 1rem;">
                                        <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 0.75rem;">
                                            <i class="fas fa-envelope" style="color: #05944F; width: 20px;"></i>
                                            <span style="color: #6B7280; font-size: 0.9rem;">{{ $restaurant->email }}</span>
                                        </div>
                                        @if($restaurant->phone)
                                        <div style="display: flex; align-items: center; gap: 0.75rem;">
                                            <i class="fas fa-phone" style="color: #05944F; width: 20px;"></i>
                                            <span style="color: #6B7280; font-size: 0.9rem;">{{ $restaurant->phone }}</span>
                                        </div>
                                        @endif
                                    </div>
                                    
                                    <hr style="margin: 1.5rem 0; border-color: #E5E7EB;">
                                    
                                    <div style="color: #6B7280; font-size: 0.85rem;">
                                        <div style="margin-bottom: 0.5rem;">
                                            <strong>Membre depuis:</strong> {{ $restaurant->created_at->format('d/m/Y') }}
                                        </div>
                                        <div>
                                            <strong>Dernière mise à jour:</strong> {{ $restaurant->updated_at->diffForHumans() }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Main Content Card -->
                        <div class="col-12 col-xl-8">
                            <div class="card shadow-sm" style="border-radius: 20px; border: none;">
                                <div class="card-header" style="background: linear-gradient(180deg, #fafafa 0%, #ffffff 100%); border-bottom: 1px solid #E5E7EB; border-radius: 20px 20px 0 0; padding: 1.5rem 2rem;">
                                    <ul class="nav nav-pills nav-justified" style="border: none;">
                                        <li class="nav-item">
                                            <a class="nav-link active" data-toggle="pill" href="#profile" 
                                               style="border-radius: 12px; color: #374151; font-weight: 600; padding: 0.75rem 1rem;">
                                                <i class="fas fa-user-edit mr-2"></i> Compte
                                            </a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link" data-toggle="pill" href="#restaurant" 
                                               style="border-radius: 12px; color: #374151; font-weight: 600; padding: 0.75rem 1rem;">
                                                <i class="fas fa-store mr-2"></i> Restaurant
                                            </a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link" data-toggle="pill" href="#password" 
                                               style="border-radius: 12px; color: #374151; font-weight: 600; padding: 0.75rem 1rem;">
                                                <i class="fas fa-key mr-2"></i> Mot de passe
                                            </a>
                                        </li>
                                    </ul>
                                </div>
                                
                                <div class="card-body" style="padding: 2rem;">
                                    <div class="tab-content">
                                        <!-- Profile Tab -->
                                        <div id="profile" class="tab-pane fade show active">
                                            <div>
                                                <h3 style="font-size: 1.35rem; font-weight: 700; color: #1F2937; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.75rem;">
                                                    <i class="fas fa-user-edit" style="color: #05944F;"></i>
                                                    Informations du compte
                                                </h3>
                                                <div style="background:#f8fafc;border:1px solid #E5E7EB;border-radius:12px;padding:12px 14px;font-size:13px;color:#475569;margin-bottom:1.5rem;">
                                                    Cet onglet sert uniquement au compte de connexion.
                                                    La photo principale et la couverture visibles côté restaurant se gèrent dans l'onglet <strong>Restaurant</strong>.
                                                </div>
                                                
                                                <form action="{{ route('restaurant.profile.profile_update') }}" method="post" id="profileForm">
                                                    @csrf
                                                    <input type="hidden" name="profile_section" value="account">
                                                    
                                                    <div class="row">
                                                        <div class="col-md-6">
                                                            <div class="form-group" style="margin-bottom: 1.5rem;">
                                                                <label style="display: block; font-weight: 600; color: #374151; margin-bottom: 0.5rem; font-size: 0.9rem;">
                                                                    <i class="fas fa-user mr-1" style="color: #05944F;"></i>
                                                                    Nom complet
                                                                </label>
                                                                <input type="text" 
                                                                       name="name" 
                                                                       value="{{ $restaurant->name }}"
                                                                       class="form-control {{ $errors->has('name') ? 'is-invalid' : '' }}" 
                                                                       placeholder="Votre nom" 
                                                                       required
                                                                       style="padding: 0.875rem 1rem; border: 2px solid #E5E7EB; border-radius: 12px; font-size: 1rem; transition: all 0.3s;">
                                                                @if($errors->has('name'))
                                                                    <span class="invalid-feedback d-block" style="color: #EF4444; font-size: 0.85rem; margin-top: 0.5rem;">
                                                                        <i class="fas fa-exclamation-circle mr-1"></i>
                                                                        {{ $errors->first('name') }}
                                                                    </span>
                                                                @endif
                                                            </div>
                                                        </div>
                                                        
                                                        <div class="col-md-6">
                                                            <div class="form-group" style="margin-bottom: 1.5rem;">
                                                                <label style="display: block; font-weight: 600; color: #374151; margin-bottom: 0.5rem; font-size: 0.9rem;">
                                                                    <i class="fas fa-phone mr-1" style="color: #05944F;"></i>
                                                                    Téléphone
                                                                </label>
                                                                <input type="tel" 
                                                                       name="phone" 
                                                                       value="{{ $restaurant->phone }}"
                                                                       class="form-control {{ $errors->has('phone') ? 'is-invalid' : '' }}" 
                                                                       placeholder="+242 06 XXX XX XX" 
                                                                       style="padding: 0.875rem 1rem; border: 2px solid #E5E7EB; border-radius: 12px; font-size: 1rem; transition: all 0.3s;">
                                                                @if($errors->has('phone'))
                                                                    <span class="invalid-feedback d-block" style="color: #EF4444; font-size: 0.85rem; margin-top: 0.5rem;">
                                                                        <i class="fas fa-exclamation-circle mr-1"></i>
                                                                        {{ $errors->first('phone') }}
                                                                    </span>
                                                                @endif
                                                            </div>
                                                        </div>
                                                    </div>
                                                    
                                                    <div class="form-group" style="margin-bottom: 1.5rem;">
                                                        <label style="display: block; font-weight: 600; color: #374151; margin-bottom: 0.5rem; font-size: 0.9rem;">
                                                            <i class="fas fa-envelope mr-1" style="color: #05944F;"></i>
                                                            Email
                                                        </label>
                                                        <input type="email" 
                                                               value="{{ $restaurant->email }}" 
                                                               class="form-control" 
                                                               disabled
                                                               style="padding: 0.875rem 1rem; border: 2px solid #E5E7EB; border-radius: 12px; font-size: 1rem; background: #f3f4f6; color: #6B7280;">
                                                        <small style="color: #6B7280; font-size: 0.8rem; margin-top: 0.25rem; display: block;">
                                                            <i class="fas fa-info-circle mr-1"></i>
                                                            L'email ne peut pas être modifié
                                                        </small>
                                                    </div>
                                                    
                                                    <div style="display: flex; gap: 1rem; margin-top: 2rem;">
                                                        <button type="submit" 
                                                                class="btn btn-primary" 
                                                                style="background: linear-gradient(135deg, #05944F 0%, #009543 100%); border: none; border-radius: 12px; padding: 0.875rem 2rem; font-weight: 600; box-shadow: 0 4px 15px rgba(5, 148, 79, 0.35); transition: all 0.3s; flex: 1;">
                                                            <i class="fas fa-save mr-2"></i>
                                                            Enregistrer le compte
                                                        </button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                        
                                        <!-- Restaurant Tab -->
                                        <div id="restaurant" class="tab-pane fade">
                                            <div>
                                                <h3 style="font-size: 1.35rem; font-weight: 700; color: #1F2937; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.75rem;">
                                                    <i class="fas fa-store" style="color: #05944F;"></i>
                                                    Identité publique du restaurant
                                                </h3>

                                                @php
                                                    $rp = $restaurantProfile ?? null;
                                                    $logoSrc = ($rp && $rp->logo)
                                                        ? (strpos($rp->logo, 'http') === 0 ? $rp->logo : asset('images/restaurant_images/' . $rp->logo))
                                                        : asset('images/placeholder.png');
                                                    $coverSrc = ($rp && $rp->cover_image)
                                                        ? (strpos($rp->cover_image, 'http') === 0 ? $rp->cover_image : asset('images/restaurant_images/' . $rp->cover_image))
                                                        : $logoSrc;
                                                @endphp

                                                @if(!$rp)
                                                    <div class="alert alert-warning" style="border-radius: 12px; border: none;">
                                                        <i class="fas fa-exclamation-triangle mr-2"></i>
                                                        Aucun restaurant n'est associé à votre compte. Contactez l'administrateur.
                                                    </div>
                                                @else
                                                    <form action="{{ route('restaurant.profile.profile_update') }}" method="post" enctype="multipart/form-data">
                                                        @csrf
                                                        <input type="hidden" name="profile_section" value="restaurant">

                                                        <div class="row">
                                                            <div class="col-md-6">
                                                                <div class="form-group" style="margin-bottom: 1.5rem;">
                                                                    <label style="display:block; font-weight:600; color:#374151; margin-bottom:0.5rem;">
                                                                        Nom du restaurant
                                                                    </label>
                                                                    <input type="text" name="restaurant_name" value="{{ $rp->name }}" class="form-control"
                                                                           style="padding:0.875rem 1rem; border:2px solid #E5E7EB; border-radius:12px;">
                                                                </div>
                                                            </div>
                                                            <div class="col-md-6">
                                                                <div class="form-group" style="margin-bottom: 1.5rem;">
                                                                    <label style="display:block; font-weight:600; color:#374151; margin-bottom:0.5rem;">
                                                                        Ville
                                                                    </label>
                                                                    <input type="text" name="city" value="{{ $rp->city }}" class="form-control"
                                                                           style="padding:0.875rem 1rem; border:2px solid #E5E7EB; border-radius:12px;">
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <div class="form-group" style="margin-bottom: 1.5rem;">
                                                            <label style="display:block; font-weight:600; color:#374151; margin-bottom:0.5rem;">
                                                                Adresse
                                                            </label>
                                                            <input type="text" name="address" value="{{ $rp->address }}" class="form-control"
                                                                   style="padding:0.875rem 1rem; border:2px solid #E5E7EB; border-radius:12px;">
                                                        </div>

                                                        <div class="form-group" style="margin-bottom: 1.5rem;">
                                                            <label style="display:block; font-weight:600; color:#374151; margin-bottom:0.5rem;">
                                                                Slogan
                                                            </label>
                                                            <input type="text" name="slogan" value="{{ $rp->slogan }}" class="form-control"
                                                                   style="padding:0.875rem 1rem; border:2px solid #E5E7EB; border-radius:12px;">
                                                        </div>

                                                        <div class="form-group" style="margin-bottom: 1.5rem;">
                                                            <label style="display:block; font-weight:600; color:#374151; margin-bottom:0.5rem;">
                                                                Description
                                                            </label>
                                                            <textarea name="description" class="form-control" rows="4"
                                                                      style="padding:0.875rem 1rem; border:2px solid #E5E7EB; border-radius:12px;">{{ $rp->description }}</textarea>
                                                        </div>

                                                        <div class="row">
                                                            <div class="col-md-4">
                                                                <div class="form-group" style="margin-bottom: 1.5rem;">
                                                                    <label style="display:block; font-weight:600; color:#374151; margin-bottom:0.5rem;">
                                                                        Minimum commande (FCFA)
                                                                    </label>
                                                                    <input type="number" name="min_order" value="{{ $rp->min_order }}" class="form-control" min="0"
                                                                           style="padding:0.875rem 1rem; border:2px solid #E5E7EB; border-radius:12px;">
                                                                </div>
                                                            </div>
                                                            <div class="col-md-4">
                                                                <div class="form-group" style="margin-bottom: 1.5rem;">
                                                                    <label style="display:block; font-weight:600; color:#374151; margin-bottom:0.5rem;">
                                                                        Frais livraison (FCFA)
                                                                    </label>
                                                                    <input type="number" name="delivery_charges" value="{{ $rp->delivery_charges }}" class="form-control" min="0"
                                                                           style="padding:0.875rem 1rem; border:2px solid #E5E7EB; border-radius:12px;">
                                                                </div>
                                                            </div>
                                                            <div class="col-md-4">
                                                                <div class="form-group" style="margin-bottom: 1.5rem;">
                                                                    <label style="display:block; font-weight:600; color:#374151; margin-bottom:0.5rem;">
                                                                        Temps moyen (ex: 00:30:00)
                                                                    </label>
                                                                    <input type="text" name="avg_delivery_time" value="{{ $rp->avg_delivery_time }}" class="form-control"
                                                                           style="padding:0.875rem 1rem; border:2px solid #E5E7EB; border-radius:12px;">
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <div style="background:linear-gradient(135deg,#f0fdf4 0%,#ffffff 55%,#ecfdf5 100%);border:1px solid #BBF7D0;border-radius:20px;padding:18px 20px;margin-bottom:1.75rem;font-size:13px;color:#475569;box-shadow:0 12px 30px rgba(5,148,79,0.08);">
                                                            <div style="font-size:0.78rem;font-weight:800;letter-spacing:.08em;text-transform:uppercase;color:#05944F;margin-bottom:8px;">Identité visuelle</div>
                                                            <div style="font-size:1rem;font-weight:700;color:#111827;margin-bottom:6px;">Construisez une présence forte et cohérente</div>
                                                            <div>La <strong>photo principale</strong> représente votre restaurant partout. La <strong>couverture</strong> sert de bannière premium sur votre page restaurant.</div>
                                                        </div>

                                                        <div class="row">
                                                            <div class="col-md-6">
                                                                <div style="border:1px solid #DCE7E1;border-radius:22px;padding:20px;background:linear-gradient(180deg,#ffffff 0%,#fbfefc 100%);height:100%;margin-bottom:1.5rem;box-shadow:0 18px 40px rgba(15,23,42,0.06);">
                                                                    <label style="display:block; font-weight:800; color:#111827; margin-bottom:0.5rem;font-size:1.02rem;">
                                                                        <i class="fas fa-store mr-1" style="color:#05944F;"></i>
                                                                        Photo principale du restaurant
                                                                    </label>
                                                                    <div style="font-size:12px;color:#6B7280;margin-bottom:16px;line-height:1.6;">
                                                                        Utilisée dans le dashboard, les listes et les cartes restaurant.
                                                                    </div>
                                                                    <div style="display:flex;align-items:flex-start;gap:18px;margin-bottom:18px;">
                                                                        <img id="restaurantLogoPreview" src="{{ $logoSrc }}" alt="Photo principale du restaurant" style="width:132px; height:132px; object-fit:cover; border-radius:24px; border:1px solid #DCE7E1; box-shadow:0 12px 28px rgba(15,23,42,0.10); flex-shrink:0;">
                                                                        <div style="flex:1;">
                                                                            <div style="display:inline-flex;align-items:center;padding:6px 10px;border-radius:999px;background:#ECFDF5;color:#047857;font-size:11px;font-weight:700;margin-bottom:10px;">Visible partout</div>
                                                                            <label style="display:block;font-weight:700;color:#374151;margin-bottom:0.35rem;">Fichier image</label>
                                                                            <input type="file" name="logo" accept="image/*" class="form-control" onchange="previewRestaurantImage(this,'restaurantLogoPreview')" style="padding:0.95rem 1rem;border:2px solid #E5E7EB;border-radius:14px;">
                                                                            <small style="color:#6B7280;display:block;margin-top:6px;">PNG/JPG jusqu'à 4MB</small>
                                                                        </div>
                                                                    </div>
                                                                    <label style="display:block; font-weight:700; color:#374151; margin-bottom:0.5rem;">URL directe</label>
                                                                    <input type="url" name="logo_url" value="{{ (strpos($rp->logo ?? '', 'http') === 0) ? $rp->logo : '' }}" class="form-control"
                                                                           placeholder="https://..."
                                                                           oninput="previewUrlTo('restaurantLogoPreview', this.value)"
                                                                           style="padding:0.95rem 1rem; border:2px solid #E5E7EB; border-radius:14px;">
                                                                    @include('partials.unified_media_select', [
                                                                        'name' => 'logo_media_path',
                                                                        'label' => 'Ou choisir dans la médiathèque',
                                                                        'options' => $mediaLibraryOptions ?? [],
                                                                        'previewTarget' => 'restaurantLogoPreview',
                                                                    ])
                                                                </div>
                                                            </div>
                                                            <div class="col-md-6">
                                                                <div style="border:1px solid #DCE7E1;border-radius:22px;padding:20px;background:linear-gradient(180deg,#ffffff 0%,#fbfefc 100%);height:100%;margin-bottom:1.5rem;box-shadow:0 18px 40px rgba(15,23,42,0.06);">
                                                                    <label style="display:block; font-weight:800; color:#111827; margin-bottom:0.5rem;font-size:1.02rem;">
                                                                        <i class="fas fa-panorama mr-1" style="color:#05944F;"></i>
                                                                        Image de couverture du restaurant
                                                                    </label>
                                                                    <div style="font-size:12px;color:#6B7280;margin-bottom:16px;line-height:1.6;">
                                                                        Bannière affichée en tête de la page restaurant.
                                                                    </div>
                                                                    <div style="margin-bottom:18px;">
                                                                        <img id="restaurantCoverPreview" src="{{ $coverSrc }}" alt="Couverture" style="width:100%; height:210px; object-fit:cover; border-radius:22px; border:1px solid #DCE7E1; display:block; box-shadow:0 12px 28px rgba(15,23,42,0.10);">
                                                                    </div>
                                                                    <div style="display:inline-flex;align-items:center;padding:6px 10px;border-radius:999px;background:#FFF7ED;color:#C2410C;font-size:11px;font-weight:700;margin-bottom:10px;">Bannière page restaurant</div>
                                                                    <label style="display:block;font-weight:700;color:#374151;margin-bottom:0.35rem;">Fichier image</label>
                                                                    <input type="file" name="cover_image" accept="image/*" class="form-control" onchange="previewRestaurantImage(this,'restaurantCoverPreview')" style="padding:0.95rem 1rem;border:2px solid #E5E7EB;border-radius:14px;">
                                                                    <small style="color:#6B7280;display:block;margin-top:6px;margin-bottom:12px;">PNG/JPG jusqu'à 6MB</small>
                                                                    <label style="display:block; font-weight:700; color:#374151; margin-bottom:0.5rem;">URL directe</label>
                                                                    <input type="url" name="cover_image_url" value="{{ (strpos($rp->cover_image ?? '', 'http') === 0) ? $rp->cover_image : '' }}" class="form-control"
                                                                           placeholder="https://..."
                                                                           oninput="previewUrlTo('restaurantCoverPreview', this.value)"
                                                                           style="padding:0.95rem 1rem; border:2px solid #E5E7EB; border-radius:14px;">
                                                                    @include('partials.unified_media_select', [
                                                                        'name' => 'cover_image_media_path',
                                                                        'label' => 'Ou choisir dans la médiathèque',
                                                                        'options' => $mediaLibraryOptions ?? [],
                                                                        'previewTarget' => 'restaurantCoverPreview',
                                                                    ])
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <div style="display:flex; gap:1rem; margin-top: 1.25rem;">
                                                            <button type="submit" class="btn btn-primary"
                                                                    style="background: linear-gradient(135deg, #05944F 0%, #009543 100%); border:none; border-radius:16px; padding:1rem 2rem; font-weight:700; box-shadow:0 14px 32px rgba(5, 148, 79, 0.24); transition: all 0.3s; flex:1;">
                                                                <i class="fas fa-save mr-2"></i>
                                                                Enregistrer le restaurant
                                                            </button>
                                                        </div>
                                                    </form>
                                                @endif
                                            </div>
                                        </div>

                                        <!-- Password Tab -->
                                        <div id="password" class="tab-pane fade">
                                            <div style="max-width: 760px;">
                                                <h3 style="font-size: 1.35rem; font-weight: 700; color: #1F2937; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.75rem;">
                                                    <i class="fas fa-lock" style="color: #05944F;"></i>
                                                    Changer le mot de passe
                                                </h3>
                                                
                                                <form action="{{ route('restaurant.profile.profile_update') }}" method="post" id="passwordForm">
                                                    @csrf
                                                    <input type="hidden" name="profile_section" value="password">
                                                    
                                                    <div class="form-group" style="margin-bottom: 1.5rem;">
                                                        <label style="display: block; font-weight: 600; color: #374151; margin-bottom: 0.5rem; font-size: 0.9rem;">
                                                            <i class="fas fa-lock mr-1" style="color: #05944F;"></i>
                                                            Mot de passe actuel
                                                        </label>
                                                        <div style="position: relative;">
                                                            <input type="password" 
                                                                   name="old_password" 
                                                                   class="form-control" 
                                                                   placeholder="Entrez votre mot de passe actuel" 
                                                                   style="padding: 0.875rem 1rem; border: 2px solid #E5E7EB; border-radius: 12px; font-size: 1rem; transition: all 0.3s;">
                                                            <i class="fas fa-eye" 
                                                               style="position: absolute; right: 15px; top: 50%; transform: translateY(-50%); color: #9CA3AF; cursor: pointer;"
                                                               onclick="togglePassword(this)"></i>
                                                        </div>
                                                    </div>
                                                    
                                                    <div class="form-group" style="margin-bottom: 1.5rem;">
                                                        <label style="display: block; font-weight: 600; color: #374151; margin-bottom: 0.5rem; font-size: 0.9rem;">
                                                            <i class="fas fa-key mr-1" style="color: #05944F;"></i>
                                                            Nouveau mot de passe
                                                        </label>
                                                        <div style="position: relative;">
                                                            <input type="password" 
                                                                   name="password" 
                                                                   class="form-control" 
                                                                   placeholder="Entrez votre nouveau mot de passe" 
                                                                   id="newPassword"
                                                                   style="padding: 0.875rem 1rem; border: 2px solid #E5E7EB; border-radius: 12px; font-size: 1rem; transition: all 0.3s;">
                                                            <i class="fas fa-eye" 
                                                               style="position: absolute; right: 15px; top: 50%; transform: translateY(-50%); color: #9CA3AF; cursor: pointer;"
                                                               onclick="togglePassword(this)"></i>
                                                        </div>
                                                        <small style="color: #6B7280; font-size: 0.8rem; margin-top: 0.25rem; display: block;">
                                                            <i class="fas fa-info-circle mr-1"></i>
                                                            Minimum 6 caractères
                                                        </small>
                                                    </div>

                                                    <div class="form-group" style="margin-bottom: 1.5rem;">
                                                        <label style="display: block; font-weight: 600; color: #374151; margin-bottom: 0.5rem; font-size: 0.9rem;">
                                                            <i class="fas fa-check mr-1" style="color: #05944F;"></i>
                                                            Confirmer le mot de passe
                                                        </label>
                                                        <div style="position: relative;">
                                                            <input type="password" 
                                                                   name="password_confirmation" 
                                                                   class="form-control" 
                                                                   placeholder="Confirmez le mot de passe" 
                                                                   style="padding: 0.875rem 1rem; border: 2px solid #E5E7EB; border-radius: 12px; font-size: 1rem; transition: all 0.3s;">
                                                            <i class="fas fa-eye" 
                                                               style="position: absolute; right: 15px; top: 50%; transform: translateY(-50%); color: #9CA3AF; cursor: pointer;"
                                                               onclick="togglePassword(this)"></i>
                                                        </div>
                                                    </div>
                                                    
                                                    <div style="display: flex; gap: 1rem; margin-top: 2rem;">
                                                        <button type="submit" 
                                                                class="btn btn-primary" 
                                                                style="background: linear-gradient(135deg, #05944F 0%, #009543 100%); border: none; border-radius: 12px; padding: 0.875rem 2rem; font-weight: 600; box-shadow: 0 4px 15px rgba(5, 148, 79, 0.35); transition: all 0.3s; flex: 1;">
                                                            <i class="fas fa-save mr-2"></i>
                                                            Changer le mot de passe
                                                        </button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

<style>
    .form-control:focus {
        border-color: #05944F;
        box-shadow: 0 0 0 4px rgba(5, 148, 79, 0.1);
        outline: none;
    }
    
    .nav-link.active {
        background: linear-gradient(135deg, #05944F 0%, #009543 100%) !important;
        color: white !important;
    }
    
    .nav-link:hover {
        background: #f3f4f6;
        border-radius: 12px;
    }
    
    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(5, 148, 79, 0.45) !important;
    }
    
    @media (max-width: 991px) {
        .content {
            padding: 1rem 0;
        }
    }
</style>
@endsection

@section('script')
<script>
    function togglePassword(icon) {
        const input = icon.previousElementSibling;
        if (input.type === 'password') {
            input.type = 'text';
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        } else {
            input.type = 'password';
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        }
    }
    
    function previewImage(input) {
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('imagePreview').src = e.target.result;
                document.getElementById('imagePreview').style.display = 'block';
                document.getElementById('uploadText').style.display = 'none';
            };
            reader.readAsDataURL(input.files[0]);
        }
    }
    
function previewRestaurantImage(input, imgId) {
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function (e) {
                const img = document.getElementById(imgId);
                if (img) img.src = e.target.result;
            };
            reader.readAsDataURL(input.files[0]);
        }
    }

    function previewUrlTo(imgId, url) {
        if (!url) return;
        const img = document.getElementById(imgId);
        if (img) img.src = url;
    }

    document.querySelectorAll('.js-unified-media-select').forEach(function (select) {
        select.addEventListener('change', function () {
            const previewTarget = this.dataset.previewTarget;
            const option = this.options[this.selectedIndex];
            const previewUrl = option ? option.dataset.preview : '';
            if (!previewTarget || !previewUrl) {
                return;
            }
            const img = document.getElementById(previewTarget);
            if (img) img.src = previewUrl;
        });
    });
</script>
@endsection
