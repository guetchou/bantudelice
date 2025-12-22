@extends('layouts.app')
@section('title', 'Mon Profil | BantuDelice Admin')
@section('profile_nav', 'active')

@section('content')
<div class="content-wrapper">
    <!-- Content Header -->
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0" style="font-weight: 700; color: #1F2937;">
                        <i class="fas fa-user-circle" style="color: #FF6B35; margin-right: 0.5rem;"></i>
                        Mon Profil
                    </h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{url('/admin')}}" style="color: #6B7280;">Accueil</a></li>
                        <li class="breadcrumb-item active" style="color: #FF6B35;">Profil</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <section class="content" style="background: #F9FAFB; min-height: calc(100vh - 120px); padding: 2rem 0;">
        <div class="container-fluid">
            <div class="row justify-content-center">
                <div class="col-lg-10">
                    @if(session()->has('alert'))
                        <div class="alert alert-{{ session()->get('alert.type') }} alert-dismissible fade show" 
                             style="border-radius: 12px; border: none; box-shadow: 0 4px 12px rgba(0,0,0,0.1); margin-bottom: 2rem;">
                            <i class="fas fa-{{ session()->get('alert.type') === 'success' ? 'check-circle' : 'exclamation-circle' }} mr-2"></i>
                            {{ session()->get('alert.message') }}
                            <button type="button" class="close" data-dismiss="alert">&times;</button>
                        </div>
                    @endif

                    <div class="row">
                        <!-- Sidebar Profile Card -->
                        <div class="col-lg-4 col-md-12 mb-4">
                            <div class="card shadow-sm" style="border-radius: 20px; border: none; overflow: hidden;">
                                <div class="card-body" style="background: linear-gradient(135deg, #FF6B35 0%, #F59E0B 100%); padding: 2.5rem 2rem; text-align: center; color: white; position: relative;">
                                    <div style="position: relative; display: inline-block; margin-bottom: 1.5rem;">
                                        <img class="img-fluid rounded-circle" 
                                             src="{{ $admin->image ? url('images/profile_images/' . $admin->image) : url('assets/images/user-avatar.png') }}" 
                                             alt="Photo de profil" 
                                             style="width: 140px; height: 140px; object-fit: cover; border: 5px solid rgba(255,255,255,0.3); box-shadow: 0 8px 25px rgba(0,0,0,0.2);"
                                             id="profileImagePreview">
                                        <div style="position: absolute; bottom: 10px; right: 10px; width: 40px; height: 40px; background: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 12px rgba(0,0,0,0.2); cursor: pointer; transition: transform 0.3s;"
                                             onclick="document.getElementById('imageInput').click()">
                                            <i class="fas fa-camera" style="color: #FF6B35; font-size: 16px;"></i>
                                        </div>
                                    </div>
                                    
                                    <h3 style="font-size: 1.5rem; font-weight: 700; margin: 0.5rem 0 0.25rem; color: white;">{{ $admin->name }}</h3>
                                    <p style="opacity: 0.9; font-size: 0.95rem; margin: 0;">
                                        <i class="fas fa-shield-alt mr-1"></i> Administrateur
                                    </p>
                                    
                                    <div style="background: rgba(255,255,255,0.15); backdrop-filter: blur(10px); border-radius: 16px; padding: 1rem; margin-top: 1.5rem;">
                                        <div class="row text-center">
                                            <div class="col-4">
                                                <div style="font-size: 1.5rem; font-weight: 800;">{{ \App\User::where('type', 'user')->count() }}</div>
                                                <div style="font-size: 0.75rem; opacity: 0.85; text-transform: uppercase; letter-spacing: 0.5px;">Utilisateurs</div>
                                            </div>
                                            <div class="col-4" style="border-left: 1px solid rgba(255,255,255,0.2); border-right: 1px solid rgba(255,255,255,0.2);">
                                                <div style="font-size: 1.5rem; font-weight: 800;">{{ \App\Restaurant::count() }}</div>
                                                <div style="font-size: 0.75rem; opacity: 0.85; text-transform: uppercase; letter-spacing: 0.5px;">Restaurants</div>
                                            </div>
                                            <div class="col-4">
                                                <div style="font-size: 1.5rem; font-weight: 800;">{{ \App\Order::count() }}</div>
                                                <div style="font-size: 0.75rem; opacity: 0.85; text-transform: uppercase; letter-spacing: 0.5px;">Commandes</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Info Section -->
                                <div class="card-body" style="padding: 1.5rem;">
                                    <div style="margin-bottom: 1rem;">
                                        <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 0.75rem;">
                                            <i class="fas fa-envelope" style="color: #FF6B35; width: 20px;"></i>
                                            <span style="color: #6B7280; font-size: 0.9rem;">{{ $admin->email }}</span>
                                        </div>
                                        @if($admin->phone)
                                        <div style="display: flex; align-items: center; gap: 0.75rem;">
                                            <i class="fas fa-phone" style="color: #FF6B35; width: 20px;"></i>
                                            <span style="color: #6B7280; font-size: 0.9rem;">{{ $admin->phone }}</span>
                                        </div>
                                        @endif
                                    </div>
                                    
                                    <hr style="margin: 1.5rem 0; border-color: #E5E7EB;">
                                    
                                    <div style="color: #6B7280; font-size: 0.85rem;">
                                        <div style="margin-bottom: 0.5rem;">
                                            <strong>Membre depuis:</strong> {{ $admin->created_at->format('d/m/Y') }}
                                        </div>
                                        <div>
                                            <strong>Dernière connexion:</strong> {{ $admin->updated_at->diffForHumans() }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Main Content Card -->
                        <div class="col-lg-8 col-md-12">
                            <div class="card shadow-sm" style="border-radius: 20px; border: none;">
                                <div class="card-header" style="background: linear-gradient(180deg, #fafafa 0%, #ffffff 100%); border-bottom: 1px solid #E5E7EB; border-radius: 20px 20px 0 0; padding: 1.5rem 2rem;">
                                    <ul class="nav nav-pills nav-justified" style="border: none;">
                                        <li class="nav-item">
                                            <a class="nav-link active" data-toggle="pill" href="#password" 
                                               style="border-radius: 12px; color: #374151; font-weight: 600; padding: 0.75rem 1rem;">
                                                <i class="fas fa-key mr-2"></i> Mot de passe
                                            </a>
                                        </li>
                                    </ul>
                                </div>
                                
                                <div class="card-body" style="padding: 2rem;">
                                    <div class="tab-content">
                                        <!-- Password Tab -->
                                        <div id="password" class="tab-pane fade show active">
                                            <div style="max-width: 600px; margin: 0 auto;">
                                                <h3 style="font-size: 1.35rem; font-weight: 700; color: #1F2937; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.75rem;">
                                                    <i class="fas fa-lock" style="color: #FF6B35;"></i>
                                                    Changer le mot de passe
                                                </h3>
                                                
                                                <form action="{{ route('admin.profile_update') }}" method="post" id="passwordForm">
                                                    @csrf
                                                    
                                                    <div class="form-group" style="margin-bottom: 1.5rem;">
                                                        <label style="display: block; font-weight: 600; color: #374151; margin-bottom: 0.5rem; font-size: 0.9rem;">
                                                            <i class="fas fa-lock mr-1" style="color: #FF6B35;"></i>
                                                            Mot de passe actuel
                                                        </label>
                                                        <div style="position: relative;">
                                                            <input type="password" 
                                                                   name="current_password" 
                                                                   class="form-control {{ $errors->has('current_password') ? 'is-invalid' : '' }}" 
                                                                   placeholder="Entrez votre mot de passe actuel" 
                                                                   required
                                                                   style="padding: 0.875rem 1rem; border: 2px solid #E5E7EB; border-radius: 12px; font-size: 1rem; transition: all 0.3s;">
                                                            <i class="fas fa-eye" 
                                                               style="position: absolute; right: 15px; top: 50%; transform: translateY(-50%); color: #9CA3AF; cursor: pointer;"
                                                               onclick="togglePassword(this)"></i>
                                                        </div>
                                                        @if($errors->has('current_password'))
                                                            <span class="invalid-feedback d-block" style="color: #EF4444; font-size: 0.85rem; margin-top: 0.5rem;">
                                                                <i class="fas fa-exclamation-circle mr-1"></i>
                                                                {{ $errors->first('current_password') }}
                                                            </span>
                                                        @endif
                                                    </div>
                                                    
                                                    <div class="form-group" style="margin-bottom: 1.5rem;">
                                                        <label style="display: block; font-weight: 600; color: #374151; margin-bottom: 0.5rem; font-size: 0.9rem;">
                                                            <i class="fas fa-key mr-1" style="color: #FF6B35;"></i>
                                                            Nouveau mot de passe
                                                        </label>
                                                        <div style="position: relative;">
                                                            <input type="password" 
                                                                   name="new_password" 
                                                                   class="form-control {{ $errors->has('new_password') ? 'is-invalid' : '' }}" 
                                                                   placeholder="Entrez votre nouveau mot de passe" 
                                                                   required
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
                                                        @if($errors->has('new_password'))
                                                            <span class="invalid-feedback d-block" style="color: #EF4444; font-size: 0.85rem; margin-top: 0.5rem;">
                                                                <i class="fas fa-exclamation-circle mr-1"></i>
                                                                {{ $errors->first('new_password') }}
                                                            </span>
                                                        @endif
                                                    </div>
                                                    
                                                    <div class="form-group" style="margin-bottom: 2rem;">
                                                        <label style="display: block; font-weight: 600; color: #374151; margin-bottom: 0.5rem; font-size: 0.9rem;">
                                                            <i class="fas fa-check-circle mr-1" style="color: #FF6B35;"></i>
                                                            Confirmer le nouveau mot de passe
                                                        </label>
                                                        <div style="position: relative;">
                                                            <input type="password" 
                                                                   name="pwdnew_confirm" 
                                                                   class="form-control" 
                                                                   placeholder="Confirmez votre nouveau mot de passe" 
                                                                   required
                                                                   id="confirmPassword"
                                                                   style="padding: 0.875rem 1rem; border: 2px solid #E5E7EB; border-radius: 12px; font-size: 1rem; transition: all 0.3s;">
                                                            <i class="fas fa-eye" 
                                                               style="position: absolute; right: 15px; top: 50%; transform: translateY(-50%); color: #9CA3AF; cursor: pointer;"
                                                               onclick="togglePassword(this)"></i>
                                                        </div>
                                                        <div id="passwordMatch" style="display: none; margin-top: 0.5rem;">
                                                            <small style="color: #10B981; font-size: 0.85rem;">
                                                                <i class="fas fa-check-circle mr-1"></i>
                                                                Les mots de passe correspondent
                                                            </small>
                                                        </div>
                                                    </div>
                                                    
                                                    <div style="display: flex; gap: 1rem;">
                                                        <button type="submit" 
                                                                class="btn btn-primary" 
                                                                style="background: linear-gradient(135deg, #FF6B35 0%, #E55A2B 100%); border: none; border-radius: 12px; padding: 0.875rem 2rem; font-weight: 600; box-shadow: 0 4px 15px rgba(255, 107, 53, 0.35); transition: all 0.3s; flex: 1;">
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
</div>

<!-- Image Upload Form (Hidden) -->
<form id="imageForm" action="{{ route('admin.profile_update') }}" method="POST" enctype="multipart/form-data" style="display: none;">
    @csrf
    <input type="file" name="image" id="imageInput" accept="image/*" onchange="uploadImage(this)">
</form>

<style>
    .form-control:focus {
        border-color: #FF6B35;
        box-shadow: 0 0 0 4px rgba(255, 107, 53, 0.1);
        outline: none;
    }
    
    .nav-link.active {
        background: linear-gradient(135deg, #FF6B35 0%, #E55A2B 100%) !important;
        color: white !important;
    }
    
    .nav-link:hover {
        background: #f3f4f6;
        border-radius: 12px;
    }
    
    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(255, 107, 53, 0.45) !important;
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
    
    // Password match validation
    document.getElementById('confirmPassword')?.addEventListener('input', function() {
        const newPassword = document.getElementById('newPassword').value;
        const confirmPassword = this.value;
        const matchDiv = document.getElementById('passwordMatch');
        
        if (confirmPassword && newPassword === confirmPassword) {
            matchDiv.style.display = 'block';
            this.style.borderColor = '#10B981';
        } else {
            matchDiv.style.display = 'none';
            this.style.borderColor = '#E5E7EB';
        }
    });
    
    // Image upload
    function uploadImage(input) {
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('profileImagePreview').src = e.target.result;
            };
            reader.readAsDataURL(input.files[0]);
            document.getElementById('imageForm').submit();
        }
    }
    
    // Form validation
    document.getElementById('passwordForm')?.addEventListener('submit', function(e) {
        const newPassword = document.getElementById('newPassword').value;
        const confirmPassword = document.getElementById('confirmPassword').value;
        
        if (newPassword !== confirmPassword) {
            e.preventDefault();
            alert('Les mots de passe ne correspondent pas');
            return false;
        }
        
        if (newPassword.length < 6) {
            e.preventDefault();
            alert('Le mot de passe doit contenir au moins 6 caractères');
            return false;
        }
    });
</script>
@endsection
