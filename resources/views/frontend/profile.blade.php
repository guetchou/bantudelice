@extends('frontend.layouts.app-modern')
@section('title', 'Mon Profil | BantuDelice')

@section('styles')
<style>
    /* Profile Section */
    .profile-section {
        padding: 140px 0 80px;
        background: linear-gradient(180deg, #f8f9fa 0%, #ffffff 100%);
        min-height: 100vh;
    }
    
    .profile-layout {
        display: grid;
        grid-template-columns: 300px 1fr;
        gap: 2rem;
        align-items: start;
        max-width: 1200px;
        margin: 0 auto;
    }
    
    /* Sidebar */
    .profile-sidebar {
        background: #ffffff;
        border-radius: 24px;
        box-shadow: 0 10px 40px rgba(0,0,0,0.08);
        overflow: hidden;
        position: sticky;
        top: 120px;
    }
    
    .profile-header {
        background: linear-gradient(135deg, #FF6B35 0%, #F59E0B 100%);
        padding: 2.5rem 2rem;
        text-align: center;
        color: white;
        position: relative;
    }
    
    .profile-header::after {
        content: '';
        position: absolute;
        bottom: -20px;
        left: 50%;
        transform: translateX(-50%);
        width: 40px;
        height: 40px;
        background: #ffffff;
        border-radius: 50%;
    }
    
    .profile-avatar-wrapper {
        position: relative;
        display: inline-block;
        margin-bottom: 1rem;
    }
    
    .profile-avatar {
        width: 110px;
        height: 110px;
        border-radius: 50%;
        border: 5px solid rgba(255,255,255,0.3);
        object-fit: cover;
        box-shadow: 0 8px 25px rgba(0,0,0,0.2);
    }
    
    .avatar-upload-btn {
        position: absolute;
        bottom: 5px;
        right: 5px;
        width: 36px;
        height: 36px;
        background: #ffffff;
        border-radius: 50%;
        border: none;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        transition: all 0.3s ease;
    }
    
    .avatar-upload-btn:hover {
        transform: scale(1.1);
        box-shadow: 0 6px 20px rgba(0,0,0,0.2);
    }
    
    .avatar-upload-btn i {
        color: #FF6B35;
        font-size: 14px;
    }
    
    .profile-name {
        font-size: 1.4rem;
        font-weight: 700;
        margin: 0.5rem 0 0.25rem;
    }
    
    .profile-email {
        opacity: 0.9;
        font-size: 0.9rem;
    }
    
    .profile-stats {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        background: rgba(255,255,255,0.15);
        backdrop-filter: blur(10px);
        margin-top: 1.5rem;
        padding: 1rem 0;
        border-radius: 16px;
    }
    
    .profile-stat {
        text-align: center;
        padding: 0.5rem;
    }
    
    .profile-stat:not(:last-child) {
        border-right: 1px solid rgba(255,255,255,0.2);
    }
    
    .profile-stat-value {
        font-size: 1.5rem;
        font-weight: 800;
    }
    
    .profile-stat-label {
        font-size: 0.75rem;
        opacity: 0.85;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    /* Navigation */
    .profile-nav {
        padding: 1.5rem 0;
    }
    
    .profile-nav-item {
        display: flex;
        align-items: center;
        gap: 1rem;
        padding: 1rem 1.5rem;
        color: #4B5563;
        font-weight: 500;
        font-size: 0.95rem;
        cursor: pointer;
        transition: all 0.3s ease;
        border: none;
        background: none;
        width: 100%;
        text-align: left;
        text-decoration: none;
    }
    
    .profile-nav-item:hover {
        background: #f8f9fa;
        color: #FF6B35;
        padding-left: 2rem;
    }
    
    .profile-nav-item.active {
        background: linear-gradient(90deg, rgba(255, 107, 53, 0.1) 0%, transparent 100%);
        color: #FF6B35;
        border-left: 4px solid #FF6B35;
        font-weight: 600;
    }
    
    .profile-nav-item i {
        width: 22px;
        text-align: center;
        font-size: 1.1rem;
    }
    
    .logout-btn {
        color: #EF4444 !important;
        margin-top: 0.75rem;
        border-top: 1px solid #E5E7EB;
        padding-top: 1.25rem;
    }
    
    .logout-btn:hover {
        background: rgba(239, 68, 68, 0.05) !important;
        color: #DC2626 !important;
    }
    
    /* Main Content */
    .profile-content {
        background: #ffffff;
        border-radius: 24px;
        box-shadow: 0 10px 40px rgba(0,0,0,0.08);
        overflow: hidden;
    }
    
    .content-header {
        padding: 1.5rem 2rem;
        border-bottom: 1px solid #E5E7EB;
        display: flex;
        align-items: center;
        justify-content: space-between;
        background: linear-gradient(180deg, #fafafa 0%, #ffffff 100%);
    }
    
    .content-header h2 {
        font-size: 1.35rem;
        font-weight: 700;
        margin: 0;
        display: flex;
        align-items: center;
        gap: 0.75rem;
        color: #1F2937;
    }
    
    .content-header h2 i {
        color: #FF6B35;
        font-size: 1.1rem;
    }
    
    .content-body {
        padding: 2rem;
    }
    
    /* Tab Panes */
    .tab-pane {
        display: none;
        animation: fadeSlideIn 0.4s ease;
    }
    
    .tab-pane.active {
        display: block;
    }
    
    @keyframes fadeSlideIn {
        from {
            opacity: 0;
            transform: translateY(15px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    /* Forms */
    .form-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 1.5rem;
    }
    
    .form-group {
        margin-bottom: 0;
    }
    
    .form-label {
        display: block;
        font-weight: 600;
        color: #374151;
        margin-bottom: 0.5rem;
        font-size: 0.9rem;
    }
    
    .form-input {
        width: 100%;
        padding: 0.875rem 1rem;
        border: 2px solid #E5E7EB;
        border-radius: 12px;
        font-size: 1rem;
        transition: all 0.3s ease;
        background: #ffffff;
    }
    
    .form-input:focus {
        outline: none;
        border-color: #FF6B35;
        box-shadow: 0 0 0 4px rgba(255, 107, 53, 0.1);
    }
    
    .form-input:disabled {
        background: #f3f4f6;
        cursor: not-allowed;
        color: #6B7280;
    }
    
    /* Orders */
    .order-filters {
        display: flex;
        gap: 0.5rem;
    }
    
    .order-filter {
        padding: 0.5rem 1rem;
        border: 2px solid #E5E7EB;
        background: #ffffff;
        border-radius: 20px;
        font-size: 0.85rem;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.2s ease;
    }
    
    .order-filter:hover {
        border-color: #FF6B35;
        color: #FF6B35;
    }
    
    .order-filter.active {
        background: #FF6B35;
        border-color: #FF6B35;
        color: #ffffff;
    }
    
    .order-card {
        background: #f8f9fa;
        border-radius: 16px;
        padding: 1.5rem;
        margin-bottom: 1rem;
        transition: all 0.3s ease;
        border: 2px solid transparent;
    }
    
    .order-card:hover {
        box-shadow: 0 8px 25px rgba(0,0,0,0.1);
        transform: translateY(-3px);
        border-color: rgba(255, 107, 53, 0.2);
    }
    
    .order-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 1rem;
    }
    
    .order-number {
        font-weight: 700;
        font-size: 1.05rem;
        color: #1F2937;
    }
    
    .order-date {
        color: #6B7280;
        font-size: 0.85rem;
        margin-top: 0.25rem;
    }
    
    .order-restaurant {
        display: flex;
        align-items: center;
        gap: 0.875rem;
        margin-bottom: 1rem;
        padding: 0.75rem;
        background: #ffffff;
        border-radius: 12px;
    }
    
    .order-restaurant img {
        width: 50px;
        height: 50px;
        border-radius: 10px;
        object-fit: cover;
    }
    
    .order-restaurant-name {
        font-weight: 600;
        color: #1F2937;
    }
    
    .order-restaurant-items {
        color: #6B7280;
        font-size: 0.85rem;
    }
    
    .order-footer {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding-top: 1rem;
        border-top: 1px solid #E5E7EB;
    }
    
    .order-total {
        font-size: 1.2rem;
        font-weight: 800;
        color: #FF6B35;
    }
    
    /* Status Badges */
    .status-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.375rem;
        padding: 0.4rem 1rem;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.3px;
    }
    
    .status-badge i {
        font-size: 6px;
    }
    
    .status-pending {
        background: linear-gradient(135deg, #FEF3C7 0%, #FDE68A 100%);
        color: #92400E;
    }
    
    .status-assign {
        background: linear-gradient(135deg, #DBEAFE 0%, #BFDBFE 100%);
        color: #1E40AF;
    }
    
    .status-prepairing {
        background: linear-gradient(135deg, #E0E7FF 0%, #C7D2FE 100%);
        color: #3730A3;
    }
    
    .status-completed {
        background: linear-gradient(135deg, #D1FAE5 0%, #A7F3D0 100%);
        color: #065F46;
    }
    
    .status-cancelled {
        background: linear-gradient(135deg, #FEE2E2 0%, #FECACA 100%);
        color: #991B1B;
    }
    
    /* Addresses */
    .addresses-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        gap: 1.25rem;
    }
    
    .address-card {
        background: #f8f9fa;
        border-radius: 16px;
        padding: 1.5rem;
        position: relative;
        border: 2px solid transparent;
        transition: all 0.3s ease;
    }
    
    .address-card:hover {
        border-color: #FF6B35;
        box-shadow: 0 5px 20px rgba(255, 107, 53, 0.1);
    }
    
    .address-card.default {
        border-color: #FF6B35;
        background: linear-gradient(180deg, rgba(255, 107, 53, 0.05) 0%, #f8f9fa 100%);
    }
    
    .address-default-badge {
        position: absolute;
        top: 1rem;
        right: 1rem;
        background: linear-gradient(135deg, #FF6B35 0%, #F59E0B 100%);
        color: white;
        font-size: 0.7rem;
        padding: 0.3rem 0.75rem;
        border-radius: 20px;
        font-weight: 600;
        letter-spacing: 0.3px;
    }
    
    .address-card h4 {
        margin: 0 0 0.75rem;
        font-size: 1rem;
        color: #1F2937;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    
    .address-card h4 i {
        color: #FF6B35;
    }
    
    .address-card p {
        color: #6B7280;
        margin: 0;
        font-size: 0.925rem;
        line-height: 1.5;
    }
    
    .address-actions {
        display: flex;
        gap: 0.5rem;
        margin-top: 1.25rem;
    }
    
    .address-actions button {
        padding: 0.5rem 1rem;
        border: none;
        border-radius: 8px;
        font-size: 0.85rem;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.2s ease;
    }
    
    .edit-btn {
        background: #E5E7EB;
        color: #374151;
    }
    
    .edit-btn:hover {
        background: #D1D5DB;
    }
    
    .add-address-card {
        border: 2px dashed #D1D5DB;
        background: transparent;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        min-height: 180px;
        cursor: pointer;
        transition: all 0.3s ease;
    }
    
    .add-address-card:hover {
        border-color: #FF6B35;
        background: rgba(255, 107, 53, 0.02);
    }
    
    .add-address-card i {
        font-size: 2.5rem;
        color: #9CA3AF;
        margin-bottom: 0.75rem;
    }
    
    .add-address-card:hover i {
        color: #FF6B35;
    }
    
    .add-address-card span {
        color: #6B7280;
        font-weight: 500;
    }
    
    /* Settings toggles */
    .settings-list {
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }
    
    .settings-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 1.25rem;
        background: #f8f9fa;
        border-radius: 12px;
        cursor: pointer;
        transition: all 0.2s ease;
    }
    
    .settings-item:hover {
        background: #f3f4f6;
    }
    
    .settings-item-info h4 {
        margin: 0 0 0.25rem;
        font-size: 1rem;
        font-weight: 600;
        color: #1F2937;
    }
    
    .settings-item-info p {
        margin: 0;
        color: #6B7280;
        font-size: 0.875rem;
    }
    
    .toggle-switch {
        width: 50px;
        height: 28px;
        background: #D1D5DB;
        border-radius: 20px;
        position: relative;
        cursor: pointer;
        transition: all 0.3s ease;
    }
    
    .toggle-switch::after {
        content: '';
        position: absolute;
        width: 22px;
        height: 22px;
        background: #ffffff;
        border-radius: 50%;
        top: 3px;
        left: 3px;
        transition: all 0.3s ease;
        box-shadow: 0 2px 5px rgba(0,0,0,0.2);
    }
    
    .toggle-switch.active {
        background: #FF6B35;
    }
    
    .toggle-switch.active::after {
        left: 25px;
    }
    
    /* Empty State */
    .empty-state {
        text-align: center;
        padding: 4rem 2rem;
    }
    
    .empty-state-icon {
        width: 100px;
        height: 100px;
        background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%);
        border-radius: 50%;
        margin: 0 auto 1.5rem;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .empty-state-icon i {
        font-size: 2.5rem;
        color: #9CA3AF;
    }
    
    .empty-state h3 {
        font-size: 1.25rem;
        color: #1F2937;
        margin-bottom: 0.5rem;
    }
    
    .empty-state p {
        color: #6B7280;
        margin-bottom: 1.5rem;
    }
    
    /* Buttons */
    .btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        padding: 0.75rem 1.5rem;
        border-radius: 12px;
        font-weight: 600;
        font-size: 0.95rem;
        cursor: pointer;
        transition: all 0.3s ease;
        border: none;
        text-decoration: none;
    }
    
    .btn-primary {
        background: linear-gradient(135deg, #FF6B35 0%, #E55A2B 100%);
        color: #ffffff;
        box-shadow: 0 4px 15px rgba(255, 107, 53, 0.35);
    }
    
    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(255, 107, 53, 0.45);
    }
    
    .btn-secondary {
        background: #f3f4f6;
        color: #374151;
    }
    
    .btn-secondary:hover {
        background: #e5e7eb;
    }
    
    .btn-sm {
        padding: 0.5rem 1rem;
        font-size: 0.85rem;
    }
    
    .btn-danger {
        background: rgba(239, 68, 68, 0.1);
        color: #EF4444;
    }
    
    .btn-danger:hover {
        background: rgba(239, 68, 68, 0.15);
    }
    
    /* Success Alert */
    .alert-success {
        background: linear-gradient(135deg, #D1FAE5 0%, #A7F3D0 100%);
        color: #065F46;
        padding: 1rem 1.5rem;
        border-radius: 12px;
        margin-bottom: 1.5rem;
        display: flex;
        align-items: center;
        gap: 0.75rem;
        font-weight: 500;
    }
    
    /* Responsive */
    @media (max-width: 991px) {
        .profile-layout {
            grid-template-columns: 1fr;
            padding: 0 1rem;
        }
        
        .profile-sidebar {
            position: static;
        }
        
        .profile-header::after {
            display: none;
        }
        
        .profile-nav {
            display: flex;
            overflow-x: auto;
            padding: 1rem;
            gap: 0.5rem;
            -webkit-overflow-scrolling: touch;
        }
        
        .profile-nav-item {
            padding: 0.75rem 1.25rem;
            white-space: nowrap;
            border-radius: 25px;
            border-left: none !important;
        }
        
        .profile-nav-item.active {
            background: linear-gradient(135deg, #FF6B35 0%, #E55A2B 100%);
            color: #ffffff;
        }
        
        .profile-nav-item.active:hover {
            padding-left: 1.25rem;
        }
        
        .logout-btn {
            border-top: none;
            padding-top: 0.75rem;
            margin-top: 0;
        }
        
        .form-grid {
            grid-template-columns: 1fr;
        }
        
        .content-header {
            flex-direction: column;
            gap: 1rem;
            align-items: flex-start;
        }
        
        .order-filters {
            width: 100%;
            justify-content: flex-start;
            overflow-x: auto;
        }
    }
    
    @media (max-width: 576px) {
        .profile-section {
            padding: 120px 0 60px;
        }
        
        .profile-stats {
            grid-template-columns: repeat(3, 1fr);
        }
        
        .addresses-grid {
            grid-template-columns: 1fr;
        }
    }
</style>
@endsection

@section('content')
<section class="profile-section">
    <div class="container">
        <div class="profile-layout">
            <!-- Sidebar -->
            <aside class="profile-sidebar">
                <div class="profile-header">
                    <div class="profile-avatar-wrapper">
                        <img src="{{ auth()->user()->image ? url('images/profile_images/' . auth()->user()->image) : url('assets/images/user-avatar.png') }}" 
                             class="profile-avatar" alt="Photo de profil" id="avatarPreview">
                        <form id="avatarForm" action="{{ route('profile.update.avatar') }}" method="POST" enctype="multipart/form-data" style="display: none;">
                            @csrf
                            <input type="file" name="avatar" id="avatarInput" accept="image/*">
                        </form>
                        <button class="avatar-upload-btn" onclick="document.getElementById('avatarInput').click()" type="button">
                            <i class="fas fa-camera"></i>
                        </button>
                    </div>
                    <div class="profile-name">{{ auth()->user()->name }}</div>
                    <div class="profile-email">{{ auth()->user()->email }}</div>
                    
                    @php
                        $totalOrders = \App\Order::where('user_id', auth()->user()->id)->count();
                        $completedOrders = \App\Order::where('user_id', auth()->user()->id)->where('status', 'completed')->count();
                    @endphp
                    
                    <div class="profile-stats">
                        <div class="profile-stat">
                            <div class="profile-stat-value">{{ $totalOrders }}</div>
                            <div class="profile-stat-label">Commandes</div>
                        </div>
                        <div class="profile-stat">
                            <div class="profile-stat-value">{{ $completedOrders }}</div>
                            <div class="profile-stat-label">Livrées</div>
                        </div>
                        <div class="profile-stat">
                            <div class="profile-stat-value">{{ auth()->user()->created_at->format('Y') }}</div>
                            <div class="profile-stat-label">Membre</div>
                        </div>
                    </div>
                </div>
                
                <nav class="profile-nav">
                    <button class="profile-nav-item active" data-tab="info" type="button">
                        <i class="fas fa-user"></i> Mes Informations
                    </button>
                    <button class="profile-nav-item" data-tab="orders" type="button">
                        <i class="fas fa-shopping-bag"></i> Mes Commandes
                    </button>
                    <button class="profile-nav-item" data-tab="addresses" type="button">
                        <i class="fas fa-map-marker-alt"></i> Mes Adresses
                    </button>
                    <button class="profile-nav-item" data-tab="security" type="button">
                        <i class="fas fa-shield-alt"></i> Sécurité
                    </button>
                    <button class="profile-nav-item" data-tab="loyalty" type="button">
                        <i class="fas fa-star"></i> Points de fidélité
                    </button>
                    <button class="profile-nav-item" data-tab="notifications" type="button">
                        <i class="fas fa-bell"></i> Notifications
                    </button>
                    <a href="{{ route('user.logout') }}" class="profile-nav-item logout-btn">
                        <i class="fas fa-sign-out-alt"></i> Déconnexion
                    </a>
                </nav>
            </aside>
            
            <!-- Main Content -->
            <main class="profile-content">
                <!-- Informations -->
                <div class="tab-pane active" id="info">
                    <div class="content-header">
                        <h2><i class="fas fa-user"></i> Mes Informations</h2>
                    </div>
                    <div class="content-body">
                        @if(session()->has('success'))
                            <div class="alert-success">
                                <i class="fas fa-check-circle"></i> {{ session()->get('success') }}
                            </div>
                        @endif
                        
                        <form method="post" action="{{ route('profile.update') }}">
                            @csrf
                            <div class="form-grid">
                                <div class="form-group">
                                    <label class="form-label">Nom complet</label>
                                    <input type="text" name="name" class="form-input" value="{{ auth()->user()->name }}" required>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Adresse email</label>
                                    <input type="email" class="form-input" value="{{ auth()->user()->email }}" disabled>
                                    <small style="color: #6B7280; font-size: 0.8rem; margin-top: 0.25rem; display: block;">L'email ne peut pas être modifié</small>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Téléphone</label>
                                    <input type="tel" name="phone" class="form-input" value="{{ auth()->user()->phone }}" placeholder="+242 06 XXX XX XX">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Date de naissance</label>
                                    <input type="date" name="birthday" class="form-input" value="{{ auth()->user()->birthday ?? '' }}">
                                </div>
                            </div>
                            <div style="margin-top: 2rem;">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Enregistrer les modifications
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Commandes -->
                <div class="tab-pane" id="orders">
                    <div class="content-header">
                        <h2><i class="fas fa-shopping-bag"></i> Mes Commandes</h2>
                        <div class="order-filters">
                            <button class="order-filter active" data-filter="all" type="button">Toutes</button>
                            <button class="order-filter" data-filter="active" type="button">En cours</button>
                            <button class="order-filter" data-filter="completed" type="button">Terminées</button>
                        </div>
                    </div>
                    <div class="content-body">
                        @php
                            // Récupérer les commandes depuis orders
                            $orders = \App\Order::where('user_id', auth()->user()->id)
                                ->with(['restaurant', 'rating'])
                                ->orderBy('created_at', 'desc')
                                ->get();
                            
                            // Récupérer les commandes complétées depuis completed_orders
                            $completedOrders = \App\CompletedOrder::where('user_id', auth()->user()->id)
                                ->where('status', 'completed')
                                ->with(['restaurant'])
                                ->orderBy('created_at', 'desc')
                                ->get();
                            
                            // Combiner et trier
                            $allOrders = $orders->concat($completedOrders)->sortByDesc('created_at');
                        @endphp
                        
                        @if($allOrders->count() > 0)
                            @foreach($allOrders as $order)
                            @php
                                $orderId = $order->id;
                                $orderNo = $order->order_no ?? str_pad($order->id, 6, '0', STR_PAD_LEFT);
                                $isCompleted = ($order->status ?? '') === 'completed';
                                $isFromCompleted = $order instanceof \App\CompletedOrder;
                                
                                // Vérifier si la commande a déjà été notée
                                $existingRating = \App\Rating::where('order_id', $orderId)
                                    ->where('user_id', auth()->user()->id)
                                    ->first();
                                $canRate = $isCompleted && !$existingRating;
                            @endphp
                            <div class="order-card" data-status="{{ in_array($order->status ?? '', ['pending', 'assign', 'prepairing']) ? 'active' : 'completed' }}">
                                <div class="order-header">
                                    <div>
                                        <div class="order-number">#{{ $orderNo }}</div>
                                        <div class="order-date">{{ ($order->created_at ?? now())->format('d/m/Y à H:i') }}</div>
                                    </div>
                                    <span class="status-badge status-{{ $order->status ?? 'completed' }}">
                                        <i class="fas fa-circle"></i>
                                        @switch($order->status ?? 'completed')
                                            @case('pending') En attente @break
                                            @case('assign') Assignée @break
                                            @case('prepairing') En préparation @break
                                            @case('completed') Livrée @break
                                            @case('cancelled') Annulée @break
                                            @default Livrée
                                        @endswitch
                                    </span>
                                </div>
                                
                                @if($order->restaurant ?? null)
                                <div class="order-restaurant">
                                    <img src="{{ ($order->restaurant->logo ?? null) ? (strpos($order->restaurant->logo, 'http') === 0 ? $order->restaurant->logo : asset('images/restaurant_images/' . $order->restaurant->logo)) : asset('images/placeholder.png') }}" alt="{{ $order->restaurant->name ?? 'Restaurant' }}" onerror="this.src='{{ asset('images/placeholder.png') }}'">
                                    <div>
                                        <div class="order-restaurant-name">{{ $order->restaurant->name ?? 'Restaurant' }}</div>
                                        <div class="order-restaurant-items">{{ $order->qty ?? 1 }} article(s)</div>
                                    </div>
                                </div>
                                @endif
                                
                                <div class="order-footer">
                                    <div class="order-total">{{ number_format($order->total ?? 0, 0, ',', ' ') }} FCFA</div>
                                    <div style="display: flex; gap: 0.5rem;">
                                        @if(in_array($order->status ?? '', ['pending', 'assign', 'prepairing']))
                                            <button class="btn btn-primary btn-sm" type="button" onclick="window.location.href='{{ route('track.order', ['orderNo' => $orderNo]) }}'">
                                                <i class="fas fa-truck"></i> Suivre
                                            </button>
                                        @elseif($canRate)
                                            <button class="btn btn-primary btn-sm" type="button" onclick="openRatingModal({{ $orderId }}, '{{ $order->restaurant->name ?? 'Restaurant' }}', '{{ $orderNo }}')">
                                                <i class="fas fa-star"></i> Noter
                                            </button>
                                        @elseif($existingRating)
                                            <button class="btn btn-secondary btn-sm" type="button" disabled style="opacity: 0.6;">
                                                <i class="fas fa-check-circle"></i> Noté ({{ $existingRating->rating }}/5)
                                            </button>
                                        @endif
                                        @if($isCompleted)
                                            <button class="btn btn-secondary btn-sm" type="button" onclick="window.location.href='{{ route('home') }}'">
                                                <i class="fas fa-redo"></i> Commander à nouveau
                                            </button>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        @else
                            <div class="empty-state">
                                <div class="empty-state-icon">
                                    <i class="fas fa-shopping-bag"></i>
                                </div>
                                <h3>Aucune commande</h3>
                                <p>Vous n'avez pas encore passé de commande.</p>
                                <a href="{{ route('home') }}" class="btn btn-primary">
                                    <i class="fas fa-utensils"></i> Découvrir les restaurants
                                </a>
                            </div>
                        @endif
                    </div>
                </div>
                
                <!-- Adresses -->
                <div class="tab-pane" id="addresses">
                    <div class="content-header">
                        <h2><i class="fas fa-map-marker-alt"></i> Mes Adresses</h2>
                        <button class="btn btn-primary btn-sm" type="button" onclick="document.getElementById('addAddressModal').style.display='flex'">
                            <i class="fas fa-plus"></i> Ajouter
                        </button>
                    </div>
                    <div class="content-body">
                        <div class="addresses-grid">
                            <div class="address-card default">
                                <span class="address-default-badge">Par défaut</span>
                                <h4><i class="fas fa-home"></i> Domicile</h4>
                                <p>{{ auth()->user()->address ?? 'Aucune adresse enregistrée' }}</p>
                                <div class="address-actions">
                                    <button class="edit-btn" type="button"><i class="fas fa-edit"></i> Modifier</button>
                                </div>
                            </div>
                            
                            <div class="address-card add-address-card" onclick="document.getElementById('addAddressModal').style.display='flex'">
                                <i class="fas fa-plus-circle"></i>
                                <span>Ajouter une adresse</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Sécurité -->
                <div class="tab-pane" id="security">
                    <div class="content-header">
                        <h2><i class="fas fa-shield-alt"></i> Sécurité</h2>
                    </div>
                    <div class="content-body">
                        <form method="post" action="{{ route('profile.password') }}">
                            @csrf
                            <h3 style="font-size: 1.1rem; margin-bottom: 1.5rem; color: #1F2937;">Changer le mot de passe</h3>
                            
                            <div style="max-width: 450px;">
                                <div class="form-group" style="margin-bottom: 1.25rem;">
                                    <label class="form-label">Mot de passe actuel</label>
                                    <input type="password" name="current_password" class="form-input" required>
                                </div>
                                <div class="form-group" style="margin-bottom: 1.25rem;">
                                    <label class="form-label">Nouveau mot de passe</label>
                                    <input type="password" name="password" class="form-input" required>
                                    <small style="color: #6B7280; font-size: 0.8rem;">Minimum 6 caractères</small>
                                </div>
                                <div class="form-group" style="margin-bottom: 1.5rem;">
                                    <label class="form-label">Confirmer le nouveau mot de passe</label>
                                    <input type="password" name="password_confirmation" class="form-input" required>
                                </div>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-lock"></i> Mettre à jour le mot de passe
                                </button>
                            </div>
                        </form>
                        
                        <hr style="margin: 2.5rem 0; border: none; border-top: 1px solid #E5E7EB;">
                        
                        <div>
                            <h3 style="font-size: 1.1rem; margin-bottom: 1rem; color: #EF4444;">Zone dangereuse</h3>
                            <p style="color: #6B7280; margin-bottom: 1rem;">La suppression de votre compte est irréversible. Toutes vos données seront perdues.</p>
                            <button class="btn btn-danger" type="button">
                                <i class="fas fa-trash-alt"></i> Supprimer mon compte
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Points de fidélité -->
                <div class="tab-pane" id="loyalty">
                    <div class="content-header">
                        <h2><i class="fas fa-star"></i> Points de fidélité</h2>
                    </div>
                    <div class="content-body">
                        @php
                            $loyaltyPoints = \App\Services\LoyaltyService::getBalance(auth()->user()->id);
                            $loyaltyHistory = \App\Services\LoyaltyService::getHistory(auth()->user()->id, 10);
                            $loyaltyDiscount = \App\Services\LoyaltyService::calculateDiscount($loyaltyPoints);
                        @endphp
                        
                        <!-- Points Card -->
                        <div style="background: linear-gradient(135deg, #FF6B35 0%, #F59E0B 100%); border-radius: 20px; padding: 2.5rem; color: white; margin-bottom: 2rem; text-align: center;">
                            <div style="font-size: 3.5rem; font-weight: 800; margin-bottom: 0.5rem;">
                                {{ number_format($loyaltyPoints, 0, ',', ' ') }}
                            </div>
                            <div style="font-size: 1.125rem; opacity: 0.9; margin-bottom: 1.5rem;">Points disponibles</div>
                            <div style="background: rgba(255,255,255,0.15); backdrop-filter: blur(10px); border-radius: 16px; padding: 1rem; display: inline-block;">
                                <div style="font-size: 0.875rem; opacity: 0.85; margin-bottom: 0.25rem;">Valeur équivalente</div>
                                <div style="font-size: 1.5rem; font-weight: 700;">{{ number_format($loyaltyDiscount, 0, ',', ' ') }} FCFA</div>
                            </div>
                        </div>
                        
                        <!-- How it works -->
                        <div style="background: #f8f9fa; border-radius: 16px; padding: 1.5rem; margin-bottom: 2rem;">
                            <h3 style="font-size: 1.125rem; font-weight: 700; margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem;">
                                <i class="fas fa-info-circle" style="color: #FF6B35;"></i>
                                Comment ça marche ?
                            </h3>
                            <div style="display: grid; gap: 1rem;">
                                <div style="display: flex; gap: 1rem; align-items: start;">
                                    <div style="width: 40px; height: 40px; background: #FF6B35; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: 700; flex-shrink: 0;">1</div>
                                    <div>
                                        <h4 style="font-weight: 600; margin-bottom: 0.25rem;">Gagnez des points</h4>
                                        <p style="color: #6B7280; font-size: 0.9375rem; margin: 0;">Gagnez 10 points pour chaque 1000 FCFA dépensés</p>
                                    </div>
                                </div>
                                <div style="display: flex; gap: 1rem; align-items: start;">
                                    <div style="width: 40px; height: 40px; background: #FF6B35; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: 700; flex-shrink: 0;">2</div>
                                    <div>
                                        <h4 style="font-weight: 600; margin-bottom: 0.25rem;">Utilisez vos points</h4>
                                        <p style="color: #6B7280; font-size: 0.9375rem; margin: 0;">100 points = 1000 FCFA de réduction (max 20% par commande)</p>
                                    </div>
                                </div>
                                <div style="display: flex; gap: 1rem; align-items: start;">
                                    <div style="width: 40px; height: 40px; background: #FF6B35; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: 700; flex-shrink: 0;">3</div>
                                    <div>
                                        <h4 style="font-weight: 600; margin-bottom: 0.25rem;">Points valides 1 an</h4>
                                        <p style="color: #6B7280; font-size: 0.9375rem; margin: 0;">Vos points expirent après 365 jours</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- History -->
                        <div>
                            <h3 style="font-size: 1.125rem; font-weight: 700; margin-bottom: 1rem;">Historique des transactions</h3>
                            
                            @if($loyaltyHistory->count() > 0)
                            <div style="display: flex; flex-direction: column; gap: 1rem;">
                                @foreach($loyaltyHistory as $transaction)
                                <div style="background: white; border: 2px solid #E5E7EB; border-radius: 12px; padding: 1.25rem; display: flex; justify-content: space-between; align-items: center;">
                                    <div>
                                        <h4 style="font-weight: 600; margin-bottom: 0.25rem; color: #1F2937;">
                                            @if($transaction->type === 'earned')
                                                <i class="fas fa-plus-circle" style="color: #10B981;"></i> Points gagnés
                                            @elseif($transaction->type === 'spent')
                                                <i class="fas fa-minus-circle" style="color: #EF4444;"></i> Points utilisés
                                            @elseif($transaction->type === 'expired')
                                                <i class="fas fa-clock" style="color: #F59E0B;"></i> Points expirés
                                            @else
                                                <i class="fas fa-gift" style="color: #3B82F6;"></i> Bonus
                                            @endif
                                        </h4>
                                        <p style="color: #6B7280; font-size: 0.875rem; margin: 0;">
                                            {{ $transaction->description ?? 'Transaction' }}
                                        </p>
                                        <p style="color: #9CA3AF; font-size: 0.8125rem; margin-top: 0.25rem;">
                                            {{ $transaction->created_at->format('d/m/Y à H:i') }}
                                        </p>
                                    </div>
                                    <div style="text-align: right;">
                                        <div style="font-size: 1.25rem; font-weight: 700; color: {{ $transaction->points > 0 ? '#10B981' : '#EF4444' }};">
                                            {{ $transaction->points > 0 ? '+' : '' }}{{ number_format($transaction->points, 0, ',', ' ') }}
                                        </div>
                                        <div style="font-size: 0.75rem; color: #9CA3AF; margin-top: 0.25rem;">points</div>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                            @else
                            <div style="text-align: center; padding: 3rem 2rem; background: #f8f9fa; border-radius: 16px;">
                                <i class="fas fa-star" style="font-size: 3rem; color: #D1D5DB; margin-bottom: 1rem;"></i>
                                <h3 style="color: #6B7280; margin-bottom: 0.5rem;">Aucune transaction</h3>
                                <p style="color: #9CA3AF; margin-bottom: 1.5rem;">Commencez à commander pour gagner des points !</p>
                                <a href="{{ route('home') }}" class="btn btn-primary">
                                    <i class="fas fa-utensils"></i> Découvrir les restaurants
                                </a>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
                
                <!-- Notifications -->
                <div class="tab-pane" id="notifications">
                    <div class="content-header">
                        <h2><i class="fas fa-bell"></i> Notifications</h2>
                    </div>
                    <div class="content-body">
                        <div class="settings-list">
                            <label class="settings-item">
                                <div class="settings-item-info">
                                    <h4>Notifications par email</h4>
                                    <p>Recevoir les confirmations et mises à jour par email</p>
                                </div>
                                <div class="toggle-switch active" onclick="this.classList.toggle('active')"></div>
                            </label>
                            
                            <label class="settings-item">
                                <div class="settings-item-info">
                                    <h4>Notifications SMS</h4>
                                    <p>Recevoir les alertes par SMS</p>
                                </div>
                                <div class="toggle-switch active" onclick="this.classList.toggle('active')"></div>
                            </label>
                            
                            <label class="settings-item">
                                <div class="settings-item-info">
                                    <h4>Offres promotionnelles</h4>
                                    <p>Recevoir les offres et réductions exclusives</p>
                                </div>
                                <div class="toggle-switch" onclick="this.classList.toggle('active')"></div>
                            </label>
                            
                            <label class="settings-item">
                                <div class="settings-item-info">
                                    <h4>Nouveaux restaurants</h4>
                                    <p>Être informé des nouveaux partenaires</p>
                                </div>
                                <div class="toggle-switch active" onclick="this.classList.toggle('active')"></div>
                            </label>
                        </div>
                        
                        <button class="btn btn-primary" style="margin-top: 2rem;" type="button">
                            <i class="fas fa-save"></i> Sauvegarder les préférences
                        </button>
                    </div>
                </div>
            </main>
        </div>
    </div>
</section>

<!-- Modal Add Address -->
<div id="addAddressModal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.6); z-index: 1000; align-items: center; justify-content: center; padding: 1rem; backdrop-filter: blur(4px);">
    <div style="background: white; border-radius: 24px; max-width: 500px; width: 100%; max-height: 90vh; overflow-y: auto; box-shadow: 0 25px 50px rgba(0,0,0,0.25);">
        <div style="padding: 1.5rem 2rem; border-bottom: 1px solid #E5E7EB; display: flex; justify-content: space-between; align-items: center;">
            <h3 style="margin: 0; font-size: 1.25rem;">Ajouter une adresse</h3>
            <button onclick="document.getElementById('addAddressModal').style.display='none'" style="background: none; border: none; cursor: pointer; font-size: 1.75rem; color: #9CA3AF; line-height: 1;" type="button">&times;</button>
        </div>
        <form style="padding: 2rem;">
            @csrf
            <div class="form-group" style="margin-bottom: 1.25rem;">
                <label class="form-label">Nom de l'adresse</label>
                <input type="text" class="form-input" placeholder="Ex: Domicile, Bureau..." required>
            </div>
            <div class="form-group" style="margin-bottom: 1.25rem;">
                <label class="form-label">Adresse complète</label>
                <textarea class="form-input" rows="3" placeholder="Rue, quartier, ville..." required style="resize: none;"></textarea>
            </div>
            <div class="form-group" style="margin-bottom: 1.25rem;">
                <label class="form-label">Instructions de livraison (optionnel)</label>
                <input type="text" class="form-input" placeholder="Ex: Sonner 2 fois, code portail...">
            </div>
            <label style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 1.5rem; cursor: pointer;">
                <input type="checkbox" style="width: 18px; height: 18px; accent-color: #FF6B35;">
                <span style="color: #374151;">Définir comme adresse par défaut</span>
            </label>
            <div style="display: flex; gap: 1rem;">
                <button type="button" class="btn btn-secondary" style="flex: 1;" onclick="document.getElementById('addAddressModal').style.display='none'">Annuler</button>
                <button type="submit" class="btn btn-primary" style="flex: 1;">Enregistrer</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Rating -->
<div id="ratingModal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.6); z-index: 1000; align-items: center; justify-content: center; padding: 1rem; backdrop-filter: blur(4px);">
    <div style="background: white; border-radius: 24px; max-width: 500px; width: 100%; max-height: 90vh; overflow-y: auto; box-shadow: 0 25px 50px rgba(0,0,0,0.25);">
        <div style="padding: 1.5rem 2rem; border-bottom: 1px solid #E5E7EB; display: flex; justify-content: space-between; align-items: center;">
            <h3 style="margin: 0; font-size: 1.25rem;">Noter votre commande</h3>
            <button onclick="closeRatingModal()" style="background: none; border: none; cursor: pointer; font-size: 1.75rem; color: #9CA3AF; line-height: 1;" type="button">&times;</button>
        </div>
        <form id="ratingForm" style="padding: 2rem;" onsubmit="submitRating(event)">
            @csrf
            <input type="hidden" id="ratingOrderId" name="order_id">
            <div style="text-align: center; margin-bottom: 1.5rem;">
                <p style="color: #6B7280; margin-bottom: 1rem;" id="ratingRestaurantName">Restaurant</p>
                <p style="color: #9CA3AF; font-size: 0.875rem; margin: 0;" id="ratingOrderNo">Commande #</p>
            </div>
            
            <div class="form-group" style="margin-bottom: 1.5rem;">
                <label class="form-label" style="text-align: center; display: block; margin-bottom: 1rem;">Votre note</label>
                <div style="display: flex; justify-content: center; gap: 0.5rem; margin-bottom: 1rem;">
                    @for($i = 5; $i >= 1; $i--)
                    <button type="button" class="star-btn" data-rating="{{ $i }}" onclick="selectRating({{ $i }})" style="background: none; border: none; font-size: 2.5rem; color: #E5E7EB; cursor: pointer; transition: all 0.2s; padding: 0.25rem;">
                        <i class="fas fa-star"></i>
                    </button>
                    @endfor
                </div>
                <input type="hidden" id="ratingValue" name="rating" required>
                <p id="ratingText" style="text-align: center; color: #6B7280; font-size: 0.875rem; margin: 0; min-height: 1.5rem;"></p>
            </div>
            
            <div class="form-group" style="margin-bottom: 1.5rem;">
                <label class="form-label">Commentaire (optionnel)</label>
                <textarea id="ratingComment" name="comment" class="form-input" rows="4" placeholder="Partagez votre expérience..." maxlength="1000" style="resize: none;"></textarea>
                <small style="color: #9CA3AF; font-size: 0.75rem; display: block; margin-top: 0.5rem;">
                    <span id="charCount">0</span>/1000 caractères
                </small>
            </div>
            
            <div id="ratingError" style="display: none; background: #FEE2E2; color: #991B1B; padding: 0.75rem 1rem; border-radius: 8px; margin-bottom: 1rem; font-size: 0.875rem;"></div>
            
            <div style="display: flex; gap: 1rem;">
                <button type="button" class="btn btn-secondary" style="flex: 1;" onclick="closeRatingModal()">Annuler</button>
                <button type="submit" class="btn btn-primary" style="flex: 1;" id="ratingSubmitBtn">
                    <i class="fas fa-star"></i> Enregistrer
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
    // Tab navigation
    document.querySelectorAll('.profile-nav-item[data-tab]').forEach(tab => {
        tab.addEventListener('click', function() {
            document.querySelectorAll('.profile-nav-item').forEach(t => t.classList.remove('active'));
            document.querySelectorAll('.tab-pane').forEach(p => p.classList.remove('active'));
            
            this.classList.add('active');
            document.getElementById(this.dataset.tab).classList.add('active');
        });
    });
    
    // Avatar upload
    document.getElementById('avatarInput')?.addEventListener('change', function() {
        if (this.files && this.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('avatarPreview').src = e.target.result;
            };
            reader.readAsDataURL(this.files[0]);
            document.getElementById('avatarForm').submit();
        }
    });
    
    // Order filter
    document.querySelectorAll('.order-filter').forEach(btn => {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.order-filter').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            
            const filter = this.dataset.filter;
            document.querySelectorAll('.order-card').forEach(card => {
                if (filter === 'all' || card.dataset.status === filter) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        });
    });
    
    // Close modal on outside click
    document.getElementById('addAddressModal')?.addEventListener('click', function(e) {
        if (e.target === this) {
            this.style.display = 'none';
        }
    });
</script>
@endsection
