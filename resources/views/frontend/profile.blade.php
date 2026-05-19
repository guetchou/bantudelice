@extends('frontend.layouts.app-modern')
@php
    $profileBrand = app(\App\Services\AuthBrandingService::class)->resolve(request());
    $profileBrandName = $profileBrand['name'] ?? 'Plateforme';
@endphp
@section('title', trans('ui.profile.title') . ' | ' . $profileBrandName)
@section('description', 'Gérez votre profil, vos adresses et vos préférences sur ' . $profileBrandName . '.')

@section('styles')
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Poppins:wght@700;800;900&display=swap" rel="stylesheet">
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
        background: linear-gradient(135deg, #009543 0%, #F59E0B 100%);
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
        z-index: 2;
        overflow: hidden;
    }
    
    .avatar-upload-btn:hover {
        transform: scale(1.1);
        box-shadow: 0 6px 20px rgba(0,0,0,0.2);
    }
    
    .avatar-upload-btn i {
        color: #009543;
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
        color: #009543;
        padding-left: 2rem;
    }
    
    .profile-nav-item.active {
        background: linear-gradient(90deg, rgba(0, 149, 67, 0.1) 0%, transparent 100%);
        color: #009543;
        border-left: 4px solid #009543;
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
        color: #009543;
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
        border-color: #009543;
        box-shadow: 0 0 0 4px rgba(0, 149, 67, 0.1);
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
        border-color: #009543;
        color: #009543;
    }
    
    .order-filter.active {
        background: #009543;
        border-color: #009543;
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
        border-color: rgba(0, 149, 67, 0.2);
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
        color: #009543;
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

    .chat-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        padding: 0.4rem 0.95rem;
        border-radius: 999px;
        font-size: 0.75rem;
        font-weight: 800;
        color: #ffffff;
        background: linear-gradient(135deg, #009543 0%, #F59E0B 100%);
        box-shadow: 0 10px 18px rgba(245, 158, 11, 0.18);
        text-transform: uppercase;
        letter-spacing: 0.25px;
        white-space: nowrap;
    }

    .chat-badge i {
        font-size: 0.85rem;
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
        border-color: #009543;
        box-shadow: 0 5px 20px rgba(0, 149, 67, 0.1);
    }
    
    .address-card.default {
        border-color: #009543;
        background: linear-gradient(180deg, rgba(0, 149, 67, 0.05) 0%, #f8f9fa 100%);
    }
    
    .address-default-badge {
        position: absolute;
        top: 1rem;
        right: 1rem;
        background: linear-gradient(135deg, #009543 0%, #F59E0B 100%);
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
        color: #009543;
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
        border-color: #009543;
        background: rgba(0, 149, 67, 0.02);
    }
    
    .add-address-card i {
        font-size: 2.5rem;
        color: #9CA3AF;
        margin-bottom: 0.75rem;
    }
    
    .add-address-card:hover i {
        color: #009543;
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
        background: #009543;
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
        background: linear-gradient(135deg, #009543 0%, #e04d15 100%);
        color: #ffffff;
        box-shadow: 0 4px 15px rgba(0, 149, 67, 0.35);
    }
    
    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(0, 149, 67, 0.45);
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
            background: linear-gradient(135deg, #009543 0%, #e04d15 100%);
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

    @media (max-width: 1199px) {
        .profile-layout {
            grid-template-columns: 1fr;
            max-width: 100%;
        }

        .profile-sidebar {
            position: static;
            top: auto;
        }
    }

    @media (max-width: 767px) {
        .profile-section {
            padding: 110px 0 48px;
        }

        .profile-header,
        .content-body,
        .content-header {
            padding: 1.25rem;
        }

        .content-header {
            gap: 0.75rem;
            flex-wrap: wrap;
            align-items: flex-start;
        }

        .profile-nav-item {
            padding: 0.9rem 1rem;
        }

        .profile-stats {
            grid-template-columns: 1fr;
            gap: 0;
        }

        .profile-stat:not(:last-child) {
            border-right: 0;
            border-bottom: 1px solid rgba(255,255,255,0.2);
        }
    }
</style>
@endsection

@section('content')
@php
    $profileUi = trans('ui.profile');
    $commonUi = trans('ui.common');
@endphp
<section class="profile-section">
    <div class="container">
        @php
            $viewErrors = isset($errors) ? $errors : new \Illuminate\Support\ViewErrorBag;
        @endphp
        <div class="profile-layout">
            <!-- Sidebar -->
            <aside class="profile-sidebar">
                <div class="profile-header">
                    <div class="profile-avatar-wrapper">
                        <img src="{{ auth()->user()->avatarUrl() }}" 
                             class="profile-avatar" alt="Photo de profil" id="avatarPreview">
                        <form id="avatarForm" action="{{ route('profile.update.avatar') }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            <label class="avatar-upload-btn" for="avatarInput" title="Changer la photo de profil">
                                <i class="fas fa-camera"></i>
                                <input type="file" name="avatar" id="avatarInput" class="avatar-input-hidden" accept="image/jpeg,image/png,image/jpg,image/gif,image/webp">
                            </label>
                            <div style="margin-top:1rem; text-align:left;">
                                @include('partials.unified_media_select', [
                                    'name' => 'avatar_media_path',
                                    'label' => 'Ou choisir dans la médiathèque',
                                    'options' => $mediaLibraryOptions ?? [],
                                    'previewTarget' => 'avatarPreview',
                                ])
                                <button type="submit" class="btn btn-sm btn-light" style="margin-top:0.5rem; border-radius:999px;">
                                    Utiliser cette image
                                </button>
                            </div>
                        </form>
                    </div>
                    <div id="avatarFeedback" style="display: none;"></div>
                    @if ($viewErrors->has('avatar'))
                        <div class="avatar-feedback error">{{ $viewErrors->first('avatar') }}</div>
                    @endif
                    @if (session('success'))
                        <div class="avatar-feedback success">{{ session('success') }}</div>
                    @endif
                    <div class="profile-name">{{ auth()->user()->name }}</div>
                    <div class="profile-email">{{ auth()->user()->email }}</div>
                    
                    <div class="profile-stats">
                        <div class="profile-stat">
                            <div class="profile-stat-value">{{ $totalOrders ?? 0 }}</div>
                            <div class="profile-stat-label">{{ $profileUi['orders'] ?? 'Commandes' }}</div>
                        </div>
                        <div class="profile-stat">
                            <div class="profile-stat-value">{{ $completedOrdersCount ?? 0 }}</div>
                            <div class="profile-stat-label">Livrées</div>
                        </div>
                        <div class="profile-stat">
                            <div class="profile-stat-value">{{ auth()->user()->created_at->format('Y') }}</div>
                            <div class="profile-stat-label">Membre</div>
                        </div>
                    </div>
                </div>
                
                <nav class="profile-nav">
                    @if(!empty($dashboardLink) && !empty($dashboardLabel))
                    <a href="{{ $dashboardLink }}" class="profile-nav-item">
                        <i class="fas fa-compass"></i> {{ $dashboardLabel }}
                    </a>
                    @endif
                    <button class="profile-nav-item active" data-tab="info" type="button">
                        <i class="fas fa-user"></i> {{ $profileUi['info'] ?? 'Mes Informations' }}
                    </button>
                    <button class="profile-nav-item" data-tab="orders" type="button">
                        <i class="fas fa-shopping-bag"></i> {{ $profileUi['orders'] ?? 'Mes Commandes' }}
                    </button>
                    <button class="profile-nav-item" data-tab="addresses" type="button">
                        <i class="fas fa-map-marker-alt"></i> {{ $profileUi['addresses'] ?? 'Mes Adresses' }}
                    </button>
                    <button class="profile-nav-item" data-tab="security" type="button">
                        <i class="fas fa-shield-alt"></i> {{ $profileUi['security'] ?? 'Sécurité' }}
                    </button>
                    <button class="profile-nav-item" data-tab="loyalty" type="button">
                        <i class="fas fa-star"></i> {{ $profileUi['loyalty'] ?? 'Points de fidélité' }}
                    </button>
                    <button class="profile-nav-item" data-tab="notifications" type="button">
                        <i class="fas fa-bell"></i> {{ $profileUi['notifications'] ?? 'Notifications' }}
                    </button>
                    <form method="POST" action="{{ route('user.logout') }}" style="margin:0;">
                        @csrf
                        <button type="submit" class="profile-nav-item logout-btn" style="width:100%;text-align:left;">
                            <i class="fas fa-sign-out-alt"></i> {{ $profileUi['sign_out'] ?? 'Déconnexion' }}
                        </button>
                    </form>
                </nav>
            </aside>
            
            <!-- Main Content -->
            <main class="profile-content">
                <!-- Informations -->
                <div class="tab-pane active" id="info">
                    <div class="content-header">
                        <h2><i class="fas fa-user"></i> {{ $profileUi['info'] ?? 'Mes Informations' }}</h2>
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
                                    <label class="form-label">{{ $profileUi['full_name'] ?? 'Nom complet' }}</label>
                                    <input type="text" name="name" class="form-input" value="{{ auth()->user()->name }}" required>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">{{ $profileUi['email'] ?? 'Adresse email' }}</label>
                                    <input type="email" class="form-input" value="{{ auth()->user()->email }}" disabled>
                                    <small style="color: #6B7280; font-size: 0.8rem; margin-top: 0.25rem; display: block;">L'email ne peut pas être modifié</small>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">{{ $profileUi['phone'] ?? 'Téléphone' }}</label>
                                    <input type="tel" name="phone" class="form-input" value="{{ auth()->user()->phone }}" placeholder="+242 06 XXX XX XX">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">{{ $profileUi['birthday'] ?? 'Date de naissance' }}</label>
                                    <input type="date" name="birthday" class="form-input" value="{{ auth()->user()->birthday ?? '' }}">
                                </div>
                            </div>
                            <div style="margin-top: 2rem;">
                                <button type="submit" style="display:inline-flex;align-items:center;justify-content:center;background:#009543;color:#fff;font-weight:700;padding:.7rem 1.35rem;border-radius:999px;border:none;cursor:pointer;text-decoration:none;">
                                    <i class="fas fa-save"></i> {{ $profileUi['save_changes'] ?? 'Enregistrer les modifications' }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Commandes -->
                <div class="tab-pane" id="orders">
                    <div class="content-header">
                        <h2><i class="fas fa-shopping-bag"></i> {{ $profileUi['orders'] ?? 'Mes Commandes' }}</h2>
                        <div class="order-filters">
                            <button class="order-filter active" data-filter="all" type="button">{{ $profileUi['all'] ?? 'Toutes' }}</button>
                            <button class="order-filter" data-filter="active" type="button">{{ $profileUi['active'] ?? 'En cours' }}</button>
                            <button class="order-filter" data-filter="completed" type="button">{{ $profileUi['completed'] ?? 'Terminées' }}</button>
                        </div>
                    </div>
                    <div class="content-body">
                        @php
                            $statusUiMap = [
                                'pending_restaurant_acceptance' => ['class' => 'pending', 'label' => 'En attente'],
                                'accepted' => ['class' => 'pending', 'label' => 'Acceptée'],
                                'in_kitchen' => ['class' => 'prepairing', 'label' => 'En préparation'],
                                'ready_for_pickup' => ['class' => 'assign', 'label' => 'Prête'],
                                'driver_assigned' => ['class' => 'assign', 'label' => 'Livreur assigné'],
                                'picked_up' => ['class' => 'assign', 'label' => 'Récupérée'],
                                'out_for_delivery' => ['class' => 'assign', 'label' => 'En livraison'],
                                'customer_arrived' => ['class' => 'assign', 'label' => 'Au retrait'],
                                'picked_up_by_customer' => ['class' => 'completed', 'label' => 'Retirée'],
                                'closed' => ['class' => 'completed', 'label' => 'Clôturée'],
                                'no_show' => ['class' => 'cancelled', 'label' => 'Absent'],
                                'delivered' => ['class' => 'completed', 'label' => 'Livrée'],
                                'cancelled' => ['class' => 'cancelled', 'label' => 'Annulée'],
                            ];
                            // Récupérer les commandes depuis orders
                            $orders = \App\Order::where('user_id', auth()->user()->id)
                                ->with(['restaurant', 'rating', 'delivery.driver', 'driver'])
                                ->orderBy('created_at', 'desc')
                                ->get();
                            
                            // Récupérer les commandes complétées depuis completed_orders
                            $completedOrders = \App\CompletedOrder::where('user_id', auth()->user()->id)
                                ->where('status', 'completed')
                                ->with(['restaurant', 'driver'])
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
                                $businessStatus = method_exists($order, 'resolveEffectiveBusinessStatus')
                                    ? $order->resolveEffectiveBusinessStatus()
                                    : (($order->status ?? '') === 'completed' ? 'delivered' : ($order->status ?? 'pending_restaurant_acceptance'));
                                $trackingStatus = method_exists($order, 'resolveTrackingStatus')
                                    ? $order->resolveTrackingStatus()
                                    : ($order->status ?? 'pending');
                                $statusMeta = $statusUiMap[$businessStatus] ?? ['class' => 'pending', 'label' => 'En attente'];
                                $isCompleted = $businessStatus === 'delivered';
                                $isActive = in_array($businessStatus, [
                                    'pending_restaurant_acceptance',
                                    'accepted',
                                    'in_kitchen',
                                    'ready_for_pickup',
                                    'driver_assigned',
                                    'picked_up',
                                    'out_for_delivery',
                                    'customer_arrived',
                                ], true);
                                $isFromCompleted = $order instanceof \App\CompletedOrder;
                                $receiptIdentifier = $order->order_no ?: ($isFromCompleted ? 'completed-' . $orderId : $orderNo);
                                $driverModel = $order->delivery->driver ?? $order->driver ?? null;
                                $existingDriverReview = null;
                                if ($driverModel && class_exists(\App\Review::class)) {
                                    $existingDriverReview = \App\Review::where('driver_id', $driverModel->id)
                                        ->where('user_id', auth()->user()->id)
                                        ->when(\Illuminate\Support\Facades\Schema::hasColumn('reviews', 'order_id'), function ($query) use ($orderId) {
                                            $query->where('order_id', $orderId);
                                        })
                                        ->first();
                                }
                                
                                // Vérifier si la commande a déjà été notée
                                $existingRating = \App\Rating::where('order_id', $orderId)
                                    ->where('user_id', auth()->user()->id)
                                    ->first();
                                $hasRestaurantRating = (bool) $existingRating;
                                $hasDriverRating = (bool) $existingDriverReview;
                                $canRate = $isCompleted && (!$hasRestaurantRating || ($driverModel && !$hasDriverRating));
                                $ratingActionLabel = $hasRestaurantRating && $driverModel && !$hasDriverRating ? 'Noter le livreur' : 'Noter';
                                $canEditOrder = !$isFromCompleted && method_exists($order, 'canBeModified') && $order->canBeModified();
                            @endphp
                            <div class="order-card" data-status="{{ $isActive ? 'active' : 'completed' }}">
                                <div class="order-header">
                                    <div>
                                        <div class="order-number">#{{ $orderNo }}</div>
                                        <div class="order-date">{{ ($order->created_at ?? now())->format('d/m/Y à H:i') }}</div>
                                    </div>
                                    <div style="display: flex; gap: 0.5rem; flex-wrap: wrap; justify-content: flex-end;">
                                        <span class="status-badge status-{{ $statusMeta['class'] }}">
                                            <i class="fas fa-circle"></i>
                                            {{ $statusMeta['label'] }}
                                        </span>
                                        @if(!empty($order->chatBadge['has_unread']))
                                            <span class="chat-badge">
                                                <i class="fas fa-comments"></i>
                                                {{ $order->chatBadge['label'] }}
                                            </span>
                                        @endif
                                    </div>
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
                                        @if($canEditOrder)
                                            <button style="display:inline-flex;align-items:center;background:#fff;color:#0f172a;font-weight:600;font-size:.82rem;padding:.4rem .85rem;border-radius:999px;border:1.5px solid #e2e8f0;cursor:pointer;" type="button" onclick="window.location.href='{{ route('orders.edit', ['orderNo' => $orderNo]) }}'">
                                                <i class="fas fa-pen"></i> Modifier
                                            </button>
                                        @endif
                                        @if($isActive)
                                            <button style="display:inline-flex;align-items:center;background:#009543;color:#fff;font-weight:600;font-size:.82rem;padding:.4rem .85rem;border-radius:999px;border:none;cursor:pointer;" type="button" onclick="window.location.href='{{ route('track.order', ['orderNo' => $orderNo]) }}'">
                                                <i class="fas fa-truck"></i> Suivre
                                            </button>
                                        @elseif($canRate)
                                            <button style="display:inline-flex;align-items:center;background:#009543;color:#fff;font-weight:600;font-size:.82rem;padding:.4rem .85rem;border-radius:999px;border:none;cursor:pointer;" type="button" onclick="openRatingModal({{ $orderId }}, '{{ addslashes($order->restaurant->name ?? 'Restaurant') }}', '{{ $orderNo }}', {{ $driverModel ? $driverModel->id : 'null' }}, '{{ addslashes($driverModel->name ?? '') }}', {{ $hasRestaurantRating ? 'true' : 'false' }}, {{ $hasDriverRating ? 'true' : 'false' }})">
                                                <i class="fas fa-star"></i> {{ $ratingActionLabel }}
                                            </button>
                                        @elseif($existingRating || $existingDriverReview)
                                            <button style="display:inline-flex;align-items:center;background:#fff;color:#0f172a;font-weight:600;font-size:.82rem;padding:.4rem .85rem;border-radius:999px;border:1.5px solid #e2e8f0;cursor:pointer;" type="button" disabled style="opacity: 0.6;">
                                                <i class="fas fa-check-circle"></i> Noté
                                            </button>
                                        @endif
                                        @if($isCompleted)
                                            <button style="display:inline-flex;align-items:center;background:#fff;color:#0f172a;font-weight:600;font-size:.82rem;padding:.4rem .85rem;border-radius:999px;border:1.5px solid #e2e8f0;cursor:pointer;" type="button" onclick="window.location.href='{{ route('order.receipt', ['orderNo' => $receiptIdentifier]) }}'">
                                                <i class="fas fa-file-invoice"></i> Reçu
                                            </button>
                                            <button style="display:inline-flex;align-items:center;background:#fff;color:#0f172a;font-weight:600;font-size:.82rem;padding:.4rem .85rem;border-radius:999px;border:1.5px solid #e2e8f0;cursor:pointer;" type="button" onclick="window.location.href='{{ route('home') }}'">
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
                                <a href="{{ route('home') }}" style="display:inline-flex;align-items:center;justify-content:center;background:#009543;color:#fff;font-weight:700;padding:.7rem 1.35rem;border-radius:999px;border:none;cursor:pointer;text-decoration:none;">
                                    <i class="fas fa-utensils"></i> {{ $profileUi['discover_restaurants'] ?? 'Découvrir les restaurants' }}
                                </a>
                            </div>
                        @endif
                    </div>
                </div>
                
                <!-- Adresses -->
                <div class="tab-pane" id="addresses">
                    <div class="content-header">
                        <h2><i class="fas fa-map-marker-alt"></i> {{ $profileUi['addresses'] ?? 'Mes Adresses' }}</h2>
                        <button style="display:inline-flex;align-items:center;background:#009543;color:#fff;font-weight:600;font-size:.82rem;padding:.4rem .85rem;border-radius:999px;border:none;cursor:pointer;" type="button" onclick="document.getElementById('addAddressModal').style.display='flex'">
                            <i class="fas fa-plus"></i> {{ $profileUi['add_address'] ?? 'Ajouter' }}
                        </button>
                    </div>
                    <div class="content-body">
                        @if(isset($addresses) && $addresses->count() > 0)
                            <div class="addresses-grid">
                                @foreach($addresses as $address)
                                    <div class="address-card {{ $address->is_default ? 'default' : '' }}">
                                        @if($address->is_default)
                                            <span class="address-default-badge">{{ $profileUi['primary'] ?? 'Par défaut' }}</span>
                                        @endif
                                        <h4><i class="fas fa-map-marker-alt"></i> {{ $address->title }}</h4>
                                        <p>{{ $address->complete_address }}</p>
                                        <p style="font-size: 0.875rem; color: #6B7280; margin-top: 0.5rem;">
                                            {{ $address->area }}
                                            @if($address->building_no || $address->street_no || $address->floor)
                                                <br>
                                                {{ collect([$address->building_no, $address->street_no, $address->floor])->filter()->implode(' · ') }}
                                            @endif
                                        </p>
                                        <div class="address-actions" style="display:flex; gap:0.5rem; flex-wrap:wrap;">
                                            @if(!$address->is_default)
                                                <form method="POST" action="{{ route('profile.addresses.default', $address) }}">
                                                    @csrf
                                                    <button class="edit-btn" type="submit"><i class="fas fa-star"></i> {{ $profileUi['set_default'] ?? 'Définir' }}</button>
                                                </form>
                                            @else
                                                <button class="edit-btn" type="button" disabled style="opacity:0.7; cursor:default;"><i class="fas fa-check"></i> {{ $profileUi['primary'] ?? 'Principale' }}</button>
                                            @endif
                                            <form method="POST" action="{{ route('profile.addresses.destroy', $address) }}" onsubmit="return confirm('Supprimer cette adresse ?')">
                                                @csrf
                                                @method('DELETE')
                                                <button class="edit-btn" type="submit" style="background: #FEE2E2; color: #991B1B; border-color: #FECACA;"><i class="fas fa-trash"></i> {{ $profileUi['delete'] ?? 'Supprimer' }}</button>
                                            </form>
                                        </div>
                                    </div>
                                @endforeach

                                <div class="address-card add-address-card" onclick="document.getElementById('addAddressModal').style.display='flex'">
                                    <i class="fas fa-plus-circle"></i>
                                    <span>{{ $profileUi['add_address'] ?? 'Ajouter une adresse' }}</span>
                                </div>
                            </div>
                        @else
                            <div class="empty-state">
                                <div class="empty-state-icon">
                                    <i class="fas fa-map-marker-alt"></i>
                                </div>
                                <h3>Aucune adresse enregistrée</h3>
                                <p>Ajoutez votre domicile, votre bureau ou tout autre lieu de livraison.</p>
                                <button style="display:inline-flex;align-items:center;justify-content:center;background:#009543;color:#fff;font-weight:700;padding:.7rem 1.35rem;border-radius:999px;border:none;cursor:pointer;text-decoration:none;" type="button" onclick="document.getElementById('addAddressModal').style.display='flex'">
                                    <i class="fas fa-plus"></i> Ajouter une adresse
                                </button>
                            </div>
                        @endif
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
                                    <input type="password" name="current_password" class="form-input" autocomplete="current-password" required>
                                </div>
                                <div class="form-group" style="margin-bottom: 1.25rem;">
                                    <label class="form-label">Nouveau mot de passe</label>
                                    <input type="password" name="password" class="form-input" autocomplete="new-password" required>
                                    <small style="color: #6B7280; font-size: 0.8rem;">Minimum 6 caractères</small>
                                </div>
                                <div class="form-group" style="margin-bottom: 1.5rem;">
                                    <label class="form-label">Confirmer le nouveau mot de passe</label>
                                    <input type="password" name="password_confirmation" class="form-input" autocomplete="new-password" required>
                                </div>
                                <button type="submit" style="display:inline-flex;align-items:center;justify-content:center;background:#009543;color:#fff;font-weight:700;padding:.7rem 1.35rem;border-radius:999px;border:none;cursor:pointer;text-decoration:none;">
                                    <i class="fas fa-lock"></i> Mettre à jour le mot de passe
                                </button>
                            </div>
                        </form>
                        
                        <hr style="margin: 2.5rem 0; border: none; border-top: 1px solid #E5E7EB;">
                        
                        <div>
                            <h3 style="font-size: 1.1rem; margin-bottom: 1rem; color: #EF4444;">Zone dangereuse</h3>
                            <p style="color: #6B7280; margin-bottom: 1rem;">La suppression de votre compte est irréversible. Toutes vos données seront perdues.</p>
                            <button style="display:inline-flex;align-items:center;background:#dc2626;color:#fff;font-weight:700;padding:.7rem 1.35rem;border-radius:999px;border:none;cursor:pointer;" type="button">
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
                        <div style="background: linear-gradient(135deg, #009543 0%, #F59E0B 100%); border-radius: 20px; padding: 2.5rem; color: white; margin-bottom: 2rem; text-align: center;">
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
                                <i class="fas fa-info-circle" style="color: #009543;"></i>
                                Comment ça marche ?
                            </h3>
                            <div style="display: grid; gap: 1rem;">
                                <div style="display: flex; gap: 1rem; align-items: start;">
                                    <div style="width: 40px; height: 40px; background: #009543; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: 700; flex-shrink: 0;">1</div>
                                    <div>
                                        <h4 style="font-weight: 600; margin-bottom: 0.25rem;">Gagnez des points</h4>
                                        <p style="color: #6B7280; font-size: 0.9375rem; margin: 0;">Gagnez 10 points pour chaque 1000 FCFA dépensés</p>
                                    </div>
                                </div>
                                <div style="display: flex; gap: 1rem; align-items: start;">
                                    <div style="width: 40px; height: 40px; background: #009543; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: 700; flex-shrink: 0;">2</div>
                                    <div>
                                        <h4 style="font-weight: 600; margin-bottom: 0.25rem;">Utilisez vos points</h4>
                                        <p style="color: #6B7280; font-size: 0.9375rem; margin: 0;">100 points = 1000 FCFA de réduction (max 20% par commande)</p>
                                    </div>
                                </div>
                                <div style="display: flex; gap: 1rem; align-items: start;">
                                    <div style="width: 40px; height: 40px; background: #009543; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: 700; flex-shrink: 0;">3</div>
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
                                                <i class="fas fa-plus-circle" style="color: #009543;"></i> Points gagnés
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
                                        <div style="font-size: 1.25rem; font-weight: 700; color: {{ $transaction->points > 0 ? '#009543' : '#EF4444' }};">
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
                                <a href="{{ route('home') }}" style="display:inline-flex;align-items:center;justify-content:center;background:#009543;color:#fff;font-weight:700;padding:.7rem 1.35rem;border-radius:999px;border:none;cursor:pointer;text-decoration:none;">
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
                        
                        <button style="display:inline-flex;align-items:center;justify-content:center;background:#009543;color:#fff;font-weight:700;padding:.7rem 1.35rem;border-radius:999px;border:none;cursor:pointer;text-decoration:none;" style="margin-top: 2rem;" type="button">
                            <i class="fas fa-save"></i> {{ $profileUi['save_preferences'] ?? 'Sauvegarder les préférences' }}
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
            <h3 style="margin: 0; font-size: 1.25rem;">{{ $profileUi['add_address'] ?? 'Ajouter une adresse' }}</h3>
            <button onclick="document.getElementById('addAddressModal').style.display='none'" style="background: none; border: none; cursor: pointer; font-size: 1.75rem; color: #9CA3AF; line-height: 1;" type="button">&times;</button>
        </div>
        <form style="padding: 2rem;" method="POST" action="{{ route('profile.addresses.store') }}">
            @csrf
            <div class="form-group" style="margin-bottom: 1.25rem;">
                <label class="form-label">{{ $profileUi['address_name'] ?? "Nom de l'adresse" }}</label>
                <input type="text" name="title" class="form-input" placeholder="Ex: Domicile, Bureau..." required>
            </div>
            <div class="form-group" style="margin-bottom: 1.25rem;">
                <label class="form-label">{{ $profileUi['district'] ?? 'Quartier / zone' }}</label>
                <input type="text" name="area" class="form-input" placeholder="Ex: Poto-Poto" required>
            </div>
            <div class="form-group" style="margin-bottom: 1.25rem;">
                <label class="form-label">{{ $profileUi['full_address'] ?? 'Adresse complète' }}</label>
                <textarea name="complete_address" class="form-input" rows="3" placeholder="Rue, numéro, bâtiment, ville..." required style="resize: none;"></textarea>
            </div>
            <div class="form-grid" style="display:grid; grid-template-columns:1fr 1fr; gap:1rem; margin-bottom: 1.25rem;">
                <div class="form-group">
                    <label class="form-label">{{ $profileUi['building'] ?? 'Bâtiment' }}</label>
                    <input type="text" name="building_no" class="form-input" placeholder="Bloc A">
                </div>
                <div class="form-group">
                    <label class="form-label">{{ $profileUi['street'] ?? 'Rue' }}</label>
                    <input type="text" name="street_no" class="form-input" placeholder="Rue 12">
                </div>
            </div>
            <div class="form-group" style="margin-bottom: 1.25rem;">
                <label class="form-label">{{ $profileUi['floor'] ?? 'Étage / complément' }}</label>
                <input type="text" name="floor" class="form-input" placeholder="2e étage, porte 8...">
            </div>
            <label style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 1.5rem; cursor: pointer;">
                <input type="checkbox" name="is_default" value="1" checked style="width: 18px; height: 18px; accent-color: #009543;">
                <span style="color: #374151;">{{ $profileUi['set_default'] ?? 'Définir comme adresse par défaut' }}</span>
            </label>
            <div style="display: flex; gap: 1rem;">
                <button type="button" style="display:inline-flex;align-items:center;justify-content:center;background:#fff;color:#0f172a;font-weight:600;padding:.7rem 1.35rem;border-radius:999px;border:1.5px solid #e2e8f0;cursor:pointer;" style="flex: 1;" onclick="document.getElementById('addAddressModal').style.display='none'">{{ $profileUi['cancel'] ?? 'Annuler' }}</button>
                <button type="submit" style="display:inline-flex;align-items:center;justify-content:center;background:#009543;color:#fff;font-weight:700;padding:.7rem 1.35rem;border-radius:999px;border:none;cursor:pointer;text-decoration:none;" style="flex: 1;">{{ $profileUi['save'] ?? 'Enregistrer' }}</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Rating -->
<div id="ratingModal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.6); z-index: 1000; align-items: center; justify-content: center; padding: 1rem; backdrop-filter: blur(4px);">
    <div style="background: white; border-radius: 24px; max-width: 500px; width: 100%; max-height: 90vh; overflow-y: auto; box-shadow: 0 25px 50px rgba(0,0,0,0.25);">
        <div style="padding: 1.5rem 2rem; border-bottom: 1px solid #E5E7EB; display: flex; justify-content: space-between; align-items: center;">
            <h3 style="margin: 0; font-size: 1.25rem;">{{ $profileUi['rate_order'] ?? 'Noter votre commande' }}</h3>
            <button onclick="closeRatingModal()" style="background: none; border: none; cursor: pointer; font-size: 1.75rem; color: #9CA3AF; line-height: 1;" type="button">&times;</button>
        </div>
        <form id="ratingForm" style="padding: 2rem;" onsubmit="submitRating(event)">
            @csrf
            <input type="hidden" id="ratingOrderId" name="order_id">
            <input type="hidden" id="ratingDriverId" name="driver_id">
            <input type="hidden" id="hasExistingRestaurantRating" value="0">
            <div style="text-align: center; margin-bottom: 1.5rem;">
                <p style="color: #6B7280; margin-bottom: 1rem;" id="ratingRestaurantName">Restaurant</p>
                <p style="color: #9CA3AF; font-size: 0.875rem; margin: 0;" id="ratingOrderNo">{{ $profileUi['order_no'] ?? 'Commande #' }}</p>
            </div>
            
            <div id="restaurantRatingSection" class="form-group" style="margin-bottom: 1.5rem;">
                <label class="form-label" style="text-align: center; display: block; margin-bottom: 1rem;">{{ $profileUi['restaurant_rating'] ?? 'Note du restaurant' }}</label>
                <div style="display: flex; justify-content: center; gap: 0.5rem; margin-bottom: 1rem;">
                    @for($i = 5; $i >= 1; $i--)
                    <button type="button" class="star-btn restaurant-star-btn" data-rating="{{ $i }}" onclick="selectRating('restaurant', {{ $i }})" style="background: none; border: none; font-size: 2.5rem; color: #E5E7EB; cursor: pointer; transition: all 0.2s; padding: 0.25rem;">
                        <i class="fas fa-star"></i>
                    </button>
                    @endfor
                </div>
                <input type="hidden" id="ratingValue" name="rating" required>
                <p id="ratingText" style="text-align: center; color: #6B7280; font-size: 0.875rem; margin: 0; min-height: 1.5rem;"></p>
            </div>

            <div id="driverRatingSection" class="form-group" style="margin-bottom: 1.5rem; display: none;">
                <label class="form-label" style="text-align: center; display: block; margin-bottom: 0.35rem;">{{ $profileUi['driver_rating'] ?? 'Note du livreur' }}</label>
                <p id="ratingDriverName" style="text-align:center; color:#6B7280; font-size:0.875rem; margin-bottom:1rem;"></p>
                <div style="display: flex; justify-content: center; gap: 0.5rem; margin-bottom: 1rem;">
                    @for($i = 5; $i >= 1; $i--)
                    <button type="button" class="star-btn driver-star-btn" data-rating="{{ $i }}" onclick="selectRating('driver', {{ $i }})" style="background: none; border: none; font-size: 2.5rem; color: #E5E7EB; cursor: pointer; transition: all 0.2s; padding: 0.25rem;">
                        <i class="fas fa-star"></i>
                    </button>
                    @endfor
                </div>
                <input type="hidden" id="driverRatingValue" name="driver_rating">
                <p id="driverRatingText" style="text-align: center; color: #6B7280; font-size: 0.875rem; margin: 0; min-height: 1.5rem;"></p>
            </div>
            
            <div id="restaurantCommentGroup" class="form-group" style="margin-bottom: 1.5rem;">
                <label class="form-label">Commentaire restaurant (optionnel)</label>
                <textarea id="ratingComment" name="comment" class="form-input" rows="4" placeholder="Partagez votre expérience..." maxlength="1000" style="resize: none;"></textarea>
                <small style="color: #9CA3AF; font-size: 0.75rem; display: block; margin-top: 0.5rem;">
                    <span id="charCount">0</span>/1000 caractères
                </small>
            </div>

            <div id="driverCommentGroup" class="form-group" style="margin-bottom: 1.5rem; display: none;">
                <label class="form-label">Commentaire livreur (optionnel)</label>
                <textarea id="driverRatingComment" name="driver_comment" class="form-input" rows="3" placeholder="Décrivez la qualité de la livraison..." maxlength="1000" style="resize: none;"></textarea>
            </div>
            
            <div id="ratingError" style="display: none; background: #FEE2E2; color: #991B1B; padding: 0.75rem 1rem; border-radius: 8px; margin-bottom: 1rem; font-size: 0.875rem;"></div>
            
            <div style="display: flex; gap: 1rem;">
                <button type="button" style="display:inline-flex;align-items:center;justify-content:center;background:#fff;color:#0f172a;font-weight:600;padding:.7rem 1.35rem;border-radius:999px;border:1.5px solid #e2e8f0;cursor:pointer;" style="flex: 1;" onclick="closeRatingModal()">Annuler</button>
                <button type="submit" style="display:inline-flex;align-items:center;justify-content:center;background:#009543;color:#fff;font-weight:700;padding:.7rem 1.35rem;border-radius:999px;border:none;cursor:pointer;text-decoration:none;" style="flex: 1;" id="ratingSubmitBtn">
                    <i class="fas fa-star"></i> Enregistrer
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
    const PROFILE_USER_ID = {{ auth()->id() }};
    const ratingTexts = {
        1: 'Très décevant',
        2: 'Peut mieux faire',
        3: 'Correct',
        4: 'Très bien',
        5: 'Excellent'
    };

    // Tab navigation
    document.querySelectorAll('.profile-nav-item[data-tab]').forEach(tab => {
        tab.addEventListener('click', function() {
            document.querySelectorAll('.profile-nav-item').forEach(t => t.classList.remove('active'));
            document.querySelectorAll('.tab-pane').forEach(p => p.classList.remove('active'));
            
            this.classList.add('active');
            document.getElementById(this.dataset.tab).classList.add('active');
        });
    });
    
    // Avatar upload: flux isole en AJAX pour eviter toute collision avec les autres formulaires de la page.
    document.getElementById('avatarInput')?.addEventListener('change', async function() {
        if (!this.files || !this.files[0]) {
            return;
        }

        const file = this.files[0];
        const feedback = document.getElementById('avatarFeedback');
        const preview = document.getElementById('avatarPreview');
        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        const uploadUrl = document.getElementById('avatarForm')?.getAttribute('action');
        const allowedTypes = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif', 'image/webp'];
        const maxBytes = 8 * 1024 * 1024;

        const showAvatarFeedback = (message, type) => {
            if (!feedback) {
                showToast(message, type === 'error' ? 'error' : 'success');
                return;
            }

            feedback.className = 'avatar-feedback ' + (type === 'error' ? 'error' : 'success');
            feedback.textContent = message;
            feedback.style.display = 'block';
        };

        if (!allowedTypes.includes(file.type)) {
            showAvatarFeedback('Format non pris en charge. Utilisez JPG, PNG, GIF ou WEBP.', 'error');
            this.value = '';
            return;
        }

        if (file.size > maxBytes) {
            showAvatarFeedback('La photo depasse 8 Mo.', 'error');
            this.value = '';
            return;
        }

        if (preview) {
            preview.src = window.URL.createObjectURL(file);
        }

        const formData = new FormData();
        formData.append('avatar', file);

        this.disabled = true;

        try {
            const response = await fetch(uploadUrl, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': token || '',
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                body: formData,
                credentials: 'same-origin'
            });

            const data = await response.json().catch(() => ({}));

            if (!response.ok) {
                const message = data.message || (data.errors && data.errors.avatar && data.errors.avatar[0]) || 'Impossible de televerser la photo.';
                showAvatarFeedback(message, 'error');
                return;
            }

            if (preview && data.avatar_url) {
                preview.src = data.avatar_url;
            }

            showAvatarFeedback(data.message || 'Photo de profil mise a jour !', 'success');
        } catch (error) {
            showAvatarFeedback('Erreur reseau pendant le televersement.', 'error');
        } finally {
            this.disabled = false;
            this.value = '';
        }
    });

    document.querySelectorAll('.js-unified-media-select').forEach(function (select) {
        select.addEventListener('change', function () {
            const previewTarget = this.dataset.previewTarget;
            const option = this.options[this.selectedIndex];
            const previewUrl = option ? option.dataset.preview : '';
            if (!previewTarget || !previewUrl) {
                return;
            }
            const img = document.getElementById(previewTarget);
            if (img) {
                img.src = previewUrl;
            }
        });
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

    document.getElementById('ratingModal')?.addEventListener('click', function(e) {
        if (e.target === this) {
            closeRatingModal();
        }
    });

    document.getElementById('ratingComment')?.addEventListener('input', function() {
        const counter = document.getElementById('charCount');
        if (counter) counter.textContent = this.value.length;
    });

    function openRatingModal(orderId, restaurantName, orderNo, driverId = null, driverName = '', hasRestaurantRating = false, hasDriverRating = false) {
        const hasExistingRestaurantRating = !!hasRestaurantRating;
        document.getElementById('ratingOrderId').value = orderId;
        document.getElementById('ratingDriverId').value = driverId || '';
        document.getElementById('hasExistingRestaurantRating').value = hasExistingRestaurantRating ? '1' : '0';
        document.getElementById('ratingRestaurantName').textContent = restaurantName;
        document.getElementById('ratingOrderNo').textContent = `Commande #${orderNo}`;
        document.getElementById('ratingValue').value = '';
        document.getElementById('driverRatingValue').value = '';
        document.getElementById('ratingComment').value = '';
        document.getElementById('driverRatingComment').value = '';
        document.getElementById('ratingText').textContent = '';
        document.getElementById('driverRatingText').textContent = '';
        document.getElementById('charCount').textContent = '0';
        document.getElementById('ratingError').style.display = 'none';
        document.querySelectorAll('.restaurant-star-btn, .driver-star-btn').forEach((btn) => {
            btn.style.color = '#E5E7EB';
        });

        const driverSection = document.getElementById('driverRatingSection');
        const driverCommentGroup = document.getElementById('driverCommentGroup');
        const driverNameLabel = document.getElementById('ratingDriverName');
        const restaurantRatingSection = document.getElementById('restaurantRatingSection');
        const restaurantCommentGroup = document.getElementById('restaurantCommentGroup');
        const restaurantRatingInput = document.getElementById('ratingValue');

        if (hasExistingRestaurantRating) {
            restaurantRatingSection.style.display = 'none';
            restaurantCommentGroup.style.display = 'none';
            restaurantRatingInput.removeAttribute('required');
        } else {
            restaurantRatingSection.style.display = 'block';
            restaurantCommentGroup.style.display = 'block';
            restaurantRatingInput.setAttribute('required', 'required');
        }

        if (driverId && !hasDriverRating) {
            driverSection.style.display = 'block';
            driverCommentGroup.style.display = 'block';
            driverNameLabel.textContent = driverName ? `Livreur: ${driverName}` : 'Notez aussi la qualité de la livraison';
        } else {
            driverSection.style.display = 'none';
            driverCommentGroup.style.display = 'none';
            driverNameLabel.textContent = '';
        }

        if (hasExistingRestaurantRating && !driverId) {
            document.getElementById('ratingError').textContent = 'Cette commande a déjà été notée.';
            document.getElementById('ratingError').style.display = 'block';
        }

        document.getElementById('ratingModal').style.display = 'flex';
    }

    function closeRatingModal() {
        document.getElementById('ratingModal').style.display = 'none';
    }

    function selectRating(type, value) {
        const inputId = type === 'driver' ? 'driverRatingValue' : 'ratingValue';
        const textId = type === 'driver' ? 'driverRatingText' : 'ratingText';
        const buttonSelector = type === 'driver' ? '.driver-star-btn' : '.restaurant-star-btn';

        document.getElementById(inputId).value = value;
        document.getElementById(textId).textContent = ratingTexts[value] || '';

        document.querySelectorAll(buttonSelector).forEach((btn) => {
            btn.style.color = parseInt(btn.dataset.rating, 10) <= value ? '#F59E0B' : '#E5E7EB';
        });
    }

    async function submitRating(event) {
        event.preventDefault();

        const orderId = document.getElementById('ratingOrderId').value;
        const rating = document.getElementById('ratingValue').value;
        const comment = document.getElementById('ratingComment').value;
        const driverId = document.getElementById('ratingDriverId').value;
        const driverRating = document.getElementById('driverRatingValue').value;
        const driverComment = document.getElementById('driverRatingComment').value;
        const hasExistingRestaurantRating = document.getElementById('hasExistingRestaurantRating').value === '1';
        const errorBox = document.getElementById('ratingError');
        const submitBtn = document.getElementById('ratingSubmitBtn');

        if (!hasExistingRestaurantRating && !rating) {
            errorBox.textContent = 'Veuillez donner une note au restaurant.';
            errorBox.style.display = 'block';
            return;
        }

        if (hasExistingRestaurantRating && driverId && !driverRating) {
            errorBox.textContent = 'Veuillez donner une note au livreur.';
            errorBox.style.display = 'block';
            return;
        }

        errorBox.style.display = 'none';
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Enregistrement...';

        try {
            const response = await fetch(`/api/orders/${orderId}/rating`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    user_id: PROFILE_USER_ID,
                    rating: hasExistingRestaurantRating ? null : parseInt(rating, 10),
                    comment: hasExistingRestaurantRating ? null : comment,
                    driver_rating: driverId && driverRating ? parseInt(driverRating, 10) : null,
                    driver_comment: driverId ? driverComment : null,
                }),
                credentials: 'same-origin'
            });

            const data = await response.json();
            if (!response.ok || !data.status) {
                throw new Error(data.message || 'Impossible d’enregistrer votre note.');
            }

            closeRatingModal();
            window.location.reload();
        } catch (error) {
            errorBox.textContent = error.message;
            errorBox.style.display = 'block';
        } finally {
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="fas fa-star"></i> Enregistrer';
        }
    }
</script>
@endsection
