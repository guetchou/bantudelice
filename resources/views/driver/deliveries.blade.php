@extends('layouts.app')
@section('title', 'Mes Livraisons | BantuDelice')
@section('deliveries_nav', 'active')

@section('style')
<style>
    .delivery-card {
        background: white;
        border-radius: 16px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        padding: 1.5rem;
        margin-bottom: 1.5rem;
        transition: all 0.3s ease;
    }
    
    .delivery-card:hover {
        box-shadow: 0 8px 24px rgba(0,0,0,0.12);
        transform: translateY(-2px);
    }
    
    .status-badge {
        display: inline-block;
        padding: 0.5rem 1rem;
        border-radius: 500px;
        font-weight: 600;
        font-size: 0.875rem;
    }
    
    .status-PENDING { background: #FEF3C7; color: #92400E; }
    .status-ASSIGNED { background: #DBEAFE; color: #1E40AF; }
    .status-PICKED_UP { background: #D1FAE5; color: #065F46; }
    .status-ON_THE_WAY { background: #E0E7FF; color: #3730A3; }
    .status-DELIVERED { background: #D1FAE5; color: #065F46; }
    .status-CANCELLED { background: #FEE2E2; color: #991B1B; }
    
    .btn-status {
        padding: 0.75rem 1.5rem;
        border-radius: 8px;
        font-weight: 600;
        border: none;
        cursor: pointer;
        transition: all 0.2s;
    }
    
    .btn-status:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    }
    
    .btn-picked-up { background: #10B981; color: white; }
    .btn-on-way { background: #3B82F6; color: white; }
    .btn-delivered { background: #05944F; color: white; }
    
    .info-row {
        display: flex;
        justify-content: space-between;
        padding: 0.75rem 0;
        border-bottom: 1px solid #E5E7EB;
    }
    
    .info-row:last-child {
        border-bottom: none;
    }
    
    .info-label {
        color: #6B7280;
        font-size: 0.875rem;
    }
    
    .info-value {
        font-weight: 600;
        color: #1F2937;
    }
    
    .empty-state {
        text-align: center;
        padding: 4rem 2rem;
        color: #6B7280;
    }
    
    .empty-state i {
        font-size: 4rem;
        color: #D1D5DB;
        margin-bottom: 1rem;
    }
</style>
@endsection

@section('content')
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0 text-dark">
                    <i class="fas fa-truck mr-2"></i>Mes Livraisons
                </h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="{{ url('/driver') }}">Accueil</a></li>
                    <li class="breadcrumb-item active">Livraisons</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<section class="content">
    <div class="container-fluid">
        @if(session()->has('alert'))
            <div class="alert alert-{{ session()->get('alert.type') }} alert-dismissible fade show">
                {{ session()->get('alert.message') }}
                <button type="button" class="close" data-dismiss="alert">&times;</button>
            </div>
        @endif
        
        <div id="deliveriesContainer">
            <div class="text-center py-5">
                <i class="fas fa-spinner fa-spin fa-2x text-primary"></i>
                <p class="mt-2">Chargement des livraisons...</p>
            </div>
        </div>
    </div>
</section>

<!-- Toast Container -->
<div id="toastContainer" style="position: fixed; top: 20px; right: 20px; z-index: 10000; display: flex; flex-direction: column; gap: 0.75rem; pointer-events: none;"></div>
@endsection

@section('scripts')
<script>
    const API_BASE = '{{ url('/api') }}';
    const CSRF_TOKEN = '{{ csrf_token() }}';
    
    // Fonction pour afficher un toast
    function showToast(message, type = 'success') {
        const container = document.getElementById('toastContainer');
        if (!container) return;
        
        const toast = document.createElement('div');
        toast.style.cssText = `
            background: ${type === 'success' ? '#05944F' : '#EF4444'};
            color: white;
            padding: 1rem 1.5rem;
            border-radius: 12px;
            box-shadow: 0 8px 24px rgba(0,0,0,0.15);
            font-weight: 500;
            min-width: 300px;
            animation: slideInRight 0.3s ease-out;
            pointer-events: auto;
        `;
        
        toast.textContent = message;
        container.appendChild(toast);
        
        setTimeout(() => {
            toast.style.animation = 'slideOutRight 0.3s ease-out';
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    }
    
    // Charger les livraisons
    async function loadDeliveries() {
        const container = document.getElementById('deliveriesContainer');
        
        try {
            // Utiliser la route web qui retourne JSON
            const response = await fetch(`{{ route('driver.deliveries') }}?json=1`, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': CSRF_TOKEN
                },
                credentials: 'same-origin'
            });
            
            if (!response.ok) {
                throw new Error('Erreur lors du chargement');
            }
            
            const data = await response.json();
            
            if (!data.status || !data.data || data.data.length === 0) {
                container.innerHTML = `
                    <div class="empty-state">
                        <i class="fas fa-inbox"></i>
                        <h3>Aucune livraison active</h3>
                        <p>Vous n'avez actuellement aucune livraison en cours.</p>
                    </div>
                `;
                return;
            }
            
            container.innerHTML = data.data.map(delivery => {
                const statusLabels = {
                    'PENDING': 'En attente',
                    'ASSIGNED': 'Assignée',
                    'PICKED_UP': 'Récupérée',
                    'ON_THE_WAY': 'En route',
                    'DELIVERED': 'Livrée',
                    'CANCELLED': 'Annulée'
                };
                
                let actionButtons = '';
                if (delivery.status === 'ASSIGNED') {
                    actionButtons = `
                        <button class="btn-status btn-picked-up" onclick="updateStatus(${delivery.id}, 'PICKED_UP')">
                            <i class="fas fa-check"></i> Marquer comme récupérée
                        </button>
                    `;
                } else if (delivery.status === 'PICKED_UP') {
                    actionButtons = `
                        <button class="btn-status btn-on-way" onclick="updateStatus(${delivery.id}, 'ON_THE_WAY')">
                            <i class="fas fa-motorcycle"></i> En route
                        </button>
                    `;
                } else if (delivery.status === 'ON_THE_WAY') {
                    actionButtons = `
                        <button class="btn-status btn-delivered" onclick="updateStatus(${delivery.id}, 'DELIVERED')">
                            <i class="fas fa-check-circle"></i> Marquer comme livrée
                        </button>
                    `;
                }
                
                return `
                    <div class="delivery-card">
                        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1.5rem;">
                            <div>
                                <h3 style="font-size: 1.25rem; font-weight: 700; color: #1F2937; margin-bottom: 0.5rem;">
                                    Commande #${delivery.order_no || delivery.order_id}
                                </h3>
                                <span class="status-badge status-${delivery.status}">
                                    ${statusLabels[delivery.status] || delivery.status}
                                </span>
                            </div>
                            <div style="text-align: right;">
                                <p style="color: #6B7280; font-size: 0.875rem; margin-bottom: 0.25rem;">Frais de livraison</p>
                                <p style="font-size: 1.25rem; font-weight: 700; color: #05944F; margin: 0;">
                                    ${delivery.delivery_fee ? delivery.delivery_fee.toLocaleString() : 0} FCFA
                                </p>
                            </div>
                        </div>
                        
                        <div class="info-row">
                            <span class="info-label">Restaurant</span>
                            <span class="info-value">${delivery.restaurant?.name || 'N/A'}</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Adresse restaurant</span>
                            <span class="info-value">${delivery.restaurant?.address || 'N/A'}</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Client</span>
                            <span class="info-value">${delivery.customer?.name || 'N/A'}</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Téléphone client</span>
                            <span class="info-value">${delivery.customer?.phone || 'N/A'}</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Adresse de livraison</span>
                            <span class="info-value">${delivery.delivery_address || 'N/A'}</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Total commande</span>
                            <span class="info-value">${delivery.total ? delivery.total.toLocaleString() : 0} FCFA</span>
                        </div>
                        
                        ${delivery.assigned_at ? `
                        <div class="info-row">
                            <span class="info-label">Assignée le</span>
                            <span class="info-value">${new Date(delivery.assigned_at).toLocaleString('fr-FR')}</span>
                        </div>
                        ` : ''}
                        
                        ${actionButtons ? `
                        <div style="margin-top: 1.5rem; padding-top: 1.5rem; border-top: 2px solid #E5E7EB;">
                            ${actionButtons}
                        </div>
                        ` : ''}
                    </div>
                `;
            }).join('');
            
        } catch (error) {
            console.error('Error loading deliveries:', error);
            container.innerHTML = `
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i> Erreur lors du chargement des livraisons. Veuillez rafraîchir la page.
                </div>
            `;
        }
    }
    
    // Mettre à jour le statut
    async function updateStatus(deliveryId, status) {
        const statusLabels = {
            'PICKED_UP': 'récupérée',
            'ON_THE_WAY': 'en route',
            'DELIVERED': 'livrée'
        };
        
        if (!confirm(`Confirmer que la livraison est ${statusLabels[status]} ?`)) {
            return;
        }
        
        try {
            // Utiliser la route web avec formulaire
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = `{{ url('/driver/deliveries') }}/${deliveryId}/status`;
            
            const csrfInput = document.createElement('input');
            csrfInput.type = 'hidden';
            csrfInput.name = '_token';
            csrfInput.value = CSRF_TOKEN;
            form.appendChild(csrfInput);
            
            const statusInput = document.createElement('input');
            statusInput.type = 'hidden';
            statusInput.name = 'status';
            statusInput.value = status;
            form.appendChild(statusInput);
            
            document.body.appendChild(form);
            form.submit();
        } catch (error) {
            console.error('Error updating status:', error);
            showToast('Erreur réseau. Veuillez réessayer.', 'error');
        }
    }
    
    // Charger au démarrage
    document.addEventListener('DOMContentLoaded', function() {
        loadDeliveries();
        
        // Recharger toutes les 30 secondes
        setInterval(loadDeliveries, 30000);
    });
    
    // Styles d'animation
    const style = document.createElement('style');
    style.textContent = `
        @keyframes slideInRight {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        @keyframes slideOutRight {
            from { transform: translateX(0); opacity: 1; }
            to { transform: translateX(100%); opacity: 0; }
        }
    `;
    document.head.appendChild(style);
</script>
@endsection

