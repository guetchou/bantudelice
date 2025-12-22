@extends('frontend.layouts.app-modern')
@section('title', 'Suivi de commande #' . $order->order_no . ' | BantuDelice')
@section('description', 'Suivez votre commande en temps réel sur BantuDelice.')

@section('content')
<!-- Track Order Section -->
<section style="background: linear-gradient(135deg, #FF6B35 0%, #F59E0B 100%); padding: 120px 0 60px; position: relative; overflow: hidden;">
    <div class="container" style="position: relative; z-index: 1;">
        <div style="text-align: center; color: white;">
            <h1 style="font-size: 2.5rem; font-weight: 800; margin-bottom: 0.5rem;">
                <i class="fas fa-truck"></i> Suivi de commande
            </h1>
            <p style="font-size: 1.125rem; opacity: 0.9;">
                Commande #{{ $order->order_no }}
            </p>
        </div>
    </div>
</section>

<!-- Order Status Timeline -->
<section class="section" style="background: #F9FAFB; padding: 3rem 0;">
    <div class="container">
        <div style="max-width: 900px; margin: 0 auto;">
            
            <!-- Status Card -->
            <div style="background: white; border-radius: 24px; box-shadow: 0 10px 40px rgba(0,0,0,0.08); overflow: hidden; margin-bottom: 2rem;">
                <div style="background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%); padding: 2rem; border-bottom: 1px solid #E5E7EB;">
                    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
                        <div>
                            <p style="color: #6B7280; font-size: 0.875rem; margin-bottom: 0.25rem;">Numéro de commande</p>
                            <p style="font-size: 1.5rem; font-weight: 700; color: #FF6B35; margin: 0;">#{{ $order->order_no }}</p>
                        </div>
                        <div style="text-align: right;">
                            <p style="color: #6B7280; font-size: 0.875rem; margin-bottom: 0.25rem;">Date</p>
                            <p style="font-weight: 600; margin: 0;">{{ $order->created_at->format('d/m/Y à H:i') }}</p>
                        </div>
                    </div>
                </div>
                
                <!-- Timeline -->
                <div style="padding: 2.5rem;">
                    <div style="position: relative; padding-left: 3rem;">
                        <!-- Timeline Line -->
                        <div style="position: absolute; left: 20px; top: 0; bottom: 0; width: 3px; background: #E5E7EB;">
                            <div id="progressLine" style="position: absolute; top: 0; left: 0; width: 100%; background: #10B981; transition: height 0.5s; height: 0%;"></div>
                        </div>
                        
                        <!-- Step 1: Order Confirmed -->
                        <div class="timeline-step" data-status="confirmed" style="position: relative; margin-bottom: 3rem;">
                            <div style="position: absolute; left: -3rem; top: 0; width: 44px; height: 44px; background: #10B981; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3); z-index: 2;">
                                <i class="fas fa-check"></i>
                            </div>
                            <div>
                                <h3 style="font-size: 1.125rem; font-weight: 700; color: #1F2937; margin-bottom: 0.25rem;">
                                    Commande confirmée
                                </h3>
                                <p style="color: #6B7280; font-size: 0.9375rem; margin: 0;">
                                    Votre commande a été reçue et confirmée
                                </p>
                                <p style="color: #9CA3AF; font-size: 0.8125rem; margin-top: 0.5rem;">
                                    {{ $order->created_at->format('H:i') }}
                                </p>
                            </div>
                        </div>
                        
                        <!-- Step 2: Preparing -->
                        <div class="timeline-step" data-status="preparing" style="position: relative; margin-bottom: 3rem;">
                            <div style="position: absolute; left: -3rem; top: 0; width: 44px; height: 44px; background: {{ in_array($order->status, ['prepairing', 'assign', 'completed']) ? '#10B981' : '#E5E7EB' }}; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: {{ in_array($order->status, ['prepairing', 'assign', 'completed']) ? 'white' : '#9CA3AF' }}; z-index: 2; transition: all 0.3s;">
                                <i class="fas fa-utensils"></i>
                            </div>
                            <div>
                                <h3 style="font-size: 1.125rem; font-weight: 700; color: {{ in_array($order->status, ['prepairing', 'assign', 'completed']) ? '#1F2937' : '#9CA3AF' }}; margin-bottom: 0.25rem;">
                                    En préparation
                                </h3>
                                <p style="color: #6B7280; font-size: 0.9375rem; margin: 0;">
                                    Le restaurant prépare votre commande
                                </p>
                            </div>
                        </div>
                        
                        <!-- Step 3: Picked Up (si delivery existe) -->
                        @if(isset($delivery) && $delivery)
                        <div class="timeline-step" data-status="picked_up" style="position: relative; margin-bottom: 3rem;">
                            <div style="position: absolute; left: -3rem; top: 0; width: 44px; height: 44px; background: {{ in_array($delivery->status, ['PICKED_UP', 'ON_THE_WAY', 'DELIVERED']) ? '#10B981' : '#E5E7EB' }}; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: {{ in_array($delivery->status, ['PICKED_UP', 'ON_THE_WAY', 'DELIVERED']) ? 'white' : '#9CA3AF' }}; z-index: 2; transition: all 0.3s;">
                                <i class="fas fa-shopping-bag"></i>
                            </div>
                            <div>
                                <h3 style="font-size: 1.125rem; font-weight: 700; color: {{ in_array($delivery->status, ['PICKED_UP', 'ON_THE_WAY', 'DELIVERED']) ? '#1F2937' : '#9CA3AF' }}; margin-bottom: 0.25rem;">
                                    Récupérée au restaurant
                                </h3>
                                <p style="color: #6B7280; font-size: 0.9375rem; margin: 0;">
                                    @if($delivery->driver)
                                        {{ $delivery->driver->name }} a récupéré votre commande
                                    @else
                                        En attente de récupération
                                    @endif
                                </p>
                                @if($delivery->picked_up_at)
                                <p style="color: #9CA3AF; font-size: 0.8125rem; margin-top: 0.5rem;">
                                    {{ $delivery->picked_up_at->format('H:i') }}
                                </p>
                                @endif
                            </div>
                        </div>
                        @endif
                        
                        <!-- Step 4: On the way -->
                        <div class="timeline-step" data-status="onway" style="position: relative; margin-bottom: 3rem;">
                            <div style="position: absolute; left: -3rem; top: 0; width: 44px; height: 44px; background: {{ (isset($delivery) && in_array($delivery->status, ['ON_THE_WAY', 'DELIVERED'])) || (!isset($delivery) && in_array($order->status, ['assign', 'completed'])) ? '#10B981' : '#E5E7EB' }}; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: {{ (isset($delivery) && in_array($delivery->status, ['ON_THE_WAY', 'DELIVERED'])) || (!isset($delivery) && in_array($order->status, ['assign', 'completed'])) ? 'white' : '#9CA3AF' }}; z-index: 2; transition: all 0.3s;">
                                <i class="fas fa-motorcycle"></i>
                            </div>
                            <div>
                                <h3 style="font-size: 1.125rem; font-weight: 700; color: {{ (isset($delivery) && in_array($delivery->status, ['ON_THE_WAY', 'DELIVERED'])) || (!isset($delivery) && in_array($order->status, ['assign', 'completed'])) ? '#1F2937' : '#9CA3AF' }}; margin-bottom: 0.25rem;">
                                    En route
                                </h3>
                                <p style="color: #6B7280; font-size: 0.9375rem; margin: 0;">
                                    @if(isset($delivery) && $delivery->driver)
                                        {{ $delivery->driver->name }} est en route vers vous
                                    @elseif($order->driver)
                                        {{ $order->driver->name }} est en route vers vous
                                    @else
                                        En attente d'assignation d'un livreur
                                    @endif
                                </p>
                            </div>
                        </div>
                        
                        <!-- Step 4: Delivered -->
                        <div class="timeline-step" data-status="delivered" style="position: relative;">
                            <div style="position: absolute; left: -3rem; top: 0; width: 44px; height: 44px; background: {{ $order->status === 'completed' ? '#10B981' : '#E5E7EB' }}; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: {{ $order->status === 'completed' ? 'white' : '#9CA3AF' }}; z-index: 2; transition: all 0.3s;">
                                <i class="fas fa-home"></i>
                            </div>
                            <div>
                                <h3 style="font-size: 1.125rem; font-weight: 700; color: {{ $order->status === 'completed' ? '#1F2937' : '#9CA3AF' }}; margin-bottom: 0.25rem;">
                                    Livrée
                                </h3>
                                <p style="color: #6B7280; font-size: 0.9375rem; margin: 0;">
                                    @if($order->status === 'completed')
                                        Votre commande a été livrée avec succès !
                                    @else
                                        En attente de livraison
                                    @endif
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Estimated Time -->
                <div style="background: linear-gradient(135deg, rgba(255, 107, 53, 0.1) 0%, rgba(245, 158, 11, 0.1) 100%); padding: 1.5rem 2rem; margin: 0 2rem 2rem; border-radius: 16px; display: flex; align-items: center; gap: 1rem;">
                    <div style="width: 50px; height: 50px; background: #FF6B35; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; flex-shrink: 0;">
                        <i class="fas fa-clock" style="font-size: 1.25rem;"></i>
                    </div>
                    <div style="flex: 1;">
                        <p style="font-size: 0.875rem; color: #6B7280; margin-bottom: 0.25rem;">Temps de livraison estimé</p>
                        <p id="remainingTime" style="font-size: 1.5rem; font-weight: 700; color: #FF6B35; margin: 0;">
                            @if($remainingMinutes > 0)
                                {{ $remainingMinutes }} min restantes
                            @else
                                Livraison en cours
                            @endif
                        </p>
                    </div>
                </div>
            </div>
            
            <!-- Order Details & Map -->
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; margin-bottom: 2rem;">
                <!-- Order Items -->
                <div style="background: white; border-radius: 24px; box-shadow: 0 10px 40px rgba(0,0,0,0.08); padding: 2rem;">
                    <h3 style="font-size: 1.125rem; font-weight: 700; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.5rem;">
                        <i class="fas fa-shopping-bag" style="color: #FF6B35;"></i>
                        Détails de la commande
                    </h3>
                    
                    <div style="display: flex; flex-direction: column; gap: 1rem;">
                        @foreach($orderItems as $item)
                        <div style="display: flex; gap: 1rem; padding: 1rem; background: #f8f9fa; border-radius: 12px;">
                            @if($item->product && $item->product->image)
                            <img src="{{ asset('images/product_images/' . $item->product->image) }}" 
                                 style="width: 60px; height: 60px; border-radius: 8px; object-fit: cover;">
                            @endif
                            <div style="flex: 1;">
                                <p style="font-weight: 600; margin-bottom: 0.25rem;">{{ $item->product ? $item->product->name : 'Produit' }}</p>
                                <p style="color: #6B7280; font-size: 0.875rem; margin: 0;">Qté: {{ $item->qty }}</p>
                            </div>
                            <p style="font-weight: 700; color: #FF6B35;">{{ number_format($item->price * $item->qty, 0, ',', ' ') }} FCFA</p>
                        </div>
                        @endforeach
                    </div>
                    
                    <div style="border-top: 2px solid #E5E7EB; margin-top: 1.5rem; padding-top: 1.5rem;">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                            <span style="color: #6B7280;">Sous-total</span>
                            <span style="font-weight: 600;">{{ number_format($order->sub_total, 0, ',', ' ') }} FCFA</span>
                        </div>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                            <span style="color: #6B7280;">Livraison</span>
                            <span>{{ number_format($order->delivery_charges, 0, ',', ' ') }} FCFA</span>
                        </div>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                            <span style="color: #6B7280;">Taxes</span>
                            <span>{{ number_format($order->tax, 0, ',', ' ') }} FCFA</span>
                        </div>
                        @if($order->driver_tip > 0)
                        <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                            <span style="color: #6B7280;">Pourboire</span>
                            <span>{{ number_format($order->driver_tip, 0, ',', ' ') }} FCFA</span>
                        </div>
                        @endif
                        <div style="display: flex; justify-content: space-between; padding-top: 1rem; border-top: 2px solid #E5E7EB; margin-top: 1rem;">
                            <span style="font-size: 1.125rem; font-weight: 700;">Total</span>
                            <span style="font-size: 1.25rem; font-weight: 800; color: #FF6B35;">{{ number_format($order->total, 0, ',', ' ') }} FCFA</span>
                        </div>
                    </div>
                </div>
                
                <!-- Delivery Map -->
                <div style="background: white; border-radius: 24px; box-shadow: 0 10px 40px rgba(0,0,0,0.08); padding: 2rem; overflow: hidden;">
                    <h3 style="font-size: 1.125rem; font-weight: 700; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.5rem;">
                        <i class="fas fa-map-marker-alt" style="color: #FF6B35;"></i>
                        Suivi de livraison
                    </h3>
                    
                    <div id="trackingMap" style="height: 400px; border-radius: 12px; overflow: hidden; margin-bottom: 1rem;"></div>
                    
                    <div style="background: #f8f9fa; padding: 1rem; border-radius: 12px; margin-bottom: 1rem;">
                        <p style="font-weight: 600; margin-bottom: 0.5rem; color: #1F2937;">
                            <i class="fas fa-home" style="color: #FF6B35; margin-right: 0.5rem;"></i>
                            Adresse de livraison
                        </p>
                        <p style="color: #6B7280; font-size: 0.9375rem; margin: 0; line-height: 1.6;">
                            {{ $order->delivery_address }}
                        </p>
                    </div>
                    
                    <!-- Driver Info (mis à jour dynamiquement via API) -->
                    <div id="driverInfoContainer">
                        @if(isset($delivery) && $delivery->driver)
                        <div style="background: linear-gradient(135deg, rgba(5, 148, 79, 0.1) 0%, rgba(16, 185, 129, 0.1) 100%); padding: 1.5rem; border-radius: 12px;">
                            <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 1rem;">
                                <div style="width: 50px; height: 50px; background: #05944F; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: 700; font-size: 1.25rem;">
                                    {{ substr($delivery->driver->name, 0, 2) }}
                                </div>
                                <div style="flex: 1;">
                                    <p style="font-weight: 700; color: #1F2937; margin-bottom: 0.25rem;">{{ $delivery->driver->name }}</p>
                                    <p style="color: #6B7280; font-size: 0.875rem; margin: 0;">
                                        <i class="fas fa-phone"></i> {{ $delivery->driver->phone }}
                                    </p>
                                </div>
                            </div>
                            <div style="display: flex; gap: 0.5rem;">
                                <a href="tel:{{ $delivery->driver->phone }}" style="flex: 1; padding: 0.75rem; background: #05944F; color: white; text-align: center; border-radius: 8px; text-decoration: none; font-weight: 600;">
                                    <i class="fas fa-phone"></i> Appeler
                                </a>
                            </div>
                        </div>
                        @elseif($order->driver)
                        <div style="background: linear-gradient(135deg, rgba(5, 148, 79, 0.1) 0%, rgba(16, 185, 129, 0.1) 100%); padding: 1.5rem; border-radius: 12px;">
                            <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 1rem;">
                                <div style="width: 50px; height: 50px; background: #05944F; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: 700; font-size: 1.25rem;">
                                    {{ substr($order->driver->name, 0, 2) }}
                                </div>
                                <div style="flex: 1;">
                                    <p style="font-weight: 700; color: #1F2937; margin-bottom: 0.25rem;">{{ $order->driver->name }}</p>
                                    <p style="color: #6B7280; font-size: 0.875rem; margin: 0;">
                                        <i class="fas fa-phone"></i> {{ $order->driver->phone }}
                                    </p>
                                </div>
                            </div>
                            <div style="display: flex; gap: 0.5rem;">
                                <a href="tel:{{ $order->driver->phone }}" style="flex: 1; padding: 0.75rem; background: #05944F; color: white; text-align: center; border-radius: 8px; text-decoration: none; font-weight: 600;">
                                    <i class="fas fa-phone"></i> Appeler
                                </a>
                            </div>
                        </div>
                        @else
                        <div style="background: #f8f9fa; padding: 1.5rem; border-radius: 12px; text-align: center; color: #6B7280;">
                            <i class="fas fa-user-clock" style="font-size: 2rem; margin-bottom: 0.5rem; color: #9CA3AF;"></i>
                            <p style="margin: 0;">En attente d'assignation d'un livreur</p>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
            
            <!-- Actions -->
            <div style="background: white; border-radius: 24px; box-shadow: 0 10px 40px rgba(0,0,0,0.08); padding: 2rem; text-align: center;">
                <div style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
                    <a href="{{ route('user.profile') }}" class="btn btn-secondary" style="padding: 0.875rem 2rem;">
                        <i class="fas fa-list"></i> Mes commandes
                    </a>
                    <a href="{{ route('home') }}" class="btn btn-primary" style="padding: 0.875rem 2rem;">
                        <i class="fas fa-utensils"></i> Commander à nouveau
                    </a>
                    @if($order->driver && $order->driver->phone)
                    <a href="https://wa.me/{{ str_replace('+', '', $order->driver->phone) }}" 
                       class="btn" 
                       style="background: #25D366; color: white; padding: 0.875rem 2rem; border: none; border-radius: 12px; text-decoration: none; display: inline-flex; align-items: center; gap: 0.5rem;">
                        <i class="fab fa-whatsapp"></i> Contacter le livreur
                    </a>
                    @endif
                </div>
            </div>
            
        </div>
    </div>
</section>
@endsection

@section('scripts')
<script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyCkXFIvxvN0M1Chg644bLwAnXEQUG_RKUI&libraries=places,directions"></script>
<script>
    const API_BASE = '{{ url('/api') }}';
    const ORDER_ID = {{ $order->id }};
    
    // Mettre à jour le suivi via API
    async function updateTracking() {
        try {
            const response = await fetch(`${API_BASE}/orders/${ORDER_ID}/tracking`, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    @auth
                    'Authorization': 'Bearer {{ auth()->user()->token ?? '' }}',
                    @endauth
                },
                credentials: 'same-origin'
            });
            
            if (!response.ok) {
                return; // Ne pas bloquer si l'API échoue
            }
            
            const data = await response.json();
            
            if (data.status && data.data) {
                // Mettre à jour les statuts dans la timeline
                updateTimelineStatus(data.data.delivery_status || data.data.order_status);
                
                // Mettre à jour les informations du livreur si disponibles
                if (data.data.driver) {
                    updateDriverInfo(data.data.driver);
                }
            }
        } catch (error) {
            console.error('Error updating tracking:', error);
        }
    }
    
    // Mettre à jour le statut dans la timeline
    function updateTimelineStatus(status) {
        const statusMap = {
            'PENDING': 'confirmed',
            'ASSIGNED': 'preparing',
            'PICKED_UP': 'picked_up',
            'ON_THE_WAY': 'onway',
            'DELIVERED': 'delivered'
        };
        
        const currentStep = statusMap[status] || 'confirmed';
        
        // Mettre à jour les étapes de la timeline
        document.querySelectorAll('.timeline-step').forEach(step => {
            const stepStatus = step.getAttribute('data-status');
            const icon = step.querySelector('div[style*="background"]');
            const title = step.querySelector('h3');
            
            if (stepStatus === currentStep || isStepCompleted(stepStatus, currentStep)) {
                if (icon) {
                    icon.style.background = '#10B981';
                    icon.style.color = 'white';
                }
                if (title) {
                    title.style.color = '#1F2937';
                }
            }
        });
        
        // Mettre à jour la ligne de progression
        updateProgressLine(status);
    }
    
    // Vérifier si une étape est complétée
    function isStepCompleted(stepStatus, currentStatus) {
        const order = ['confirmed', 'preparing', 'picked_up', 'onway', 'delivered'];
        const currentIndex = order.indexOf(currentStatus);
        const stepIndex = order.indexOf(stepStatus);
        return stepIndex < currentIndex;
    }
    
    // Mettre à jour la ligne de progression
    function updateProgressLine(status) {
        const progressMap = {
            'PENDING': 0,
            'ASSIGNED': 25,
            'PICKED_UP': 50,
            'ON_THE_WAY': 75,
            'DELIVERED': 100
        };
        
        const progress = progressMap[status] || 0;
        const progressLine = document.getElementById('progressLine');
        if (progressLine) {
            progressLine.style.height = progress + '%';
        }
    }
    
    // Mettre à jour les informations du livreur
    function updateDriverInfo(driver) {
        const container = document.getElementById('driverInfoContainer');
        if (!container || !driver) return;
        
        container.innerHTML = `
            <div style="background: linear-gradient(135deg, rgba(5, 148, 79, 0.1) 0%, rgba(16, 185, 129, 0.1) 100%); padding: 1.5rem; border-radius: 12px;">
                <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 1rem;">
                    <div style="width: 50px; height: 50px; background: #05944F; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: 700; font-size: 1.25rem;">
                        ${driver.name ? driver.name.substring(0, 2).toUpperCase() : '??'}
                    </div>
                    <div style="flex: 1;">
                        <p style="font-weight: 700; color: #1F2937; margin-bottom: 0.25rem;">${driver.name || 'Livreur'}</p>
                        <p style="color: #6B7280; font-size: 0.875rem; margin: 0;">
                            <i class="fas fa-phone"></i> ${driver.phone || 'N/A'}
                        </p>
                    </div>
                </div>
                <div style="display: flex; gap: 0.5rem;">
                    ${driver.phone ? `
                    <a href="tel:${driver.phone}" style="flex: 1; padding: 0.75rem; background: #05944F; color: white; text-align: center; border-radius: 8px; text-decoration: none; font-weight: 600;">
                        <i class="fas fa-phone"></i> Appeler
                    </a>
                    ` : ''}
                </div>
            </div>
        `;
    }
    
    // Polling toutes les 10 secondes
    setInterval(updateTracking, 10000);
    
    // Initialize map
    function initTrackingMap() {
        const deliveryLat = parseFloat({{ $order->d_lat ?? -4.2767 }});
        const deliveryLng = parseFloat({{ $order->d_lng ?? 15.2832 }});
        
        map = new google.maps.Map(document.getElementById('trackingMap'), {
            zoom: 13,
            center: { lat: deliveryLat, lng: deliveryLng },
            mapTypeId: 'roadmap',
            styles: [
                {
                    featureType: 'poi',
                    elementType: 'labels',
                    stylers: [{ visibility: 'off' }]
                }
            ]
        });
        
        // Delivery marker
        deliveryMarker = new google.maps.Marker({
            position: { lat: deliveryLat, lng: deliveryLng },
            map: map,
            title: 'Adresse de livraison',
            icon: {
                url: 'https://maps.google.com/mapfiles/ms/icons/red-dot.png',
                scaledSize: new google.maps.Size(40, 40)
            },
            animation: google.maps.Animation.DROP
        });
        
        // Info window pour l'adresse de livraison
        const deliveryInfo = new google.maps.InfoWindow({
            content: '<div style="padding: 0.75rem; max-width: 250px;"><strong style="color: #FF6B35;">📍 Adresse de livraison</strong><br><p style="margin: 0.5rem 0 0 0; color: #6B7280;">{{ $order->delivery_address }}</p></div>'
        });
        deliveryMarker.addListener('click', () => deliveryInfo.open(map, deliveryMarker));
        deliveryInfo.open(map, deliveryMarker); // Ouvrir automatiquement
        
        // Restaurant marker (if available)
        @if($order->restaurant && $order->restaurant->latitude && $order->restaurant->longitude)
        const restaurantLat = parseFloat({{ $order->restaurant->latitude }});
        const restaurantLng = parseFloat({{ $order->restaurant->longitude }});
        
        restaurantMarker = new google.maps.Marker({
            position: { lat: restaurantLat, lng: restaurantLng },
            map: map,
            title: '{{ $order->restaurant->name }}',
            icon: {
                url: 'https://maps.google.com/mapfiles/ms/icons/blue-dot.png',
                scaledSize: new google.maps.Size(40, 40)
            },
            animation: google.maps.Animation.DROP
        });
        
        const restaurantInfo = new google.maps.InfoWindow({
            content: '<div style="padding: 0.75rem; max-width: 250px;"><strong style="color: #3B82F6;">🍽️ {{ $order->restaurant->name }}</strong><br><p style="margin: 0.5rem 0 0 0; color: #6B7280;">Restaurant</p></div>'
        });
        restaurantMarker.addListener('click', () => restaurantInfo.open(map, restaurantMarker));
        
        // Draw route from restaurant to delivery
        const directionsService = new google.maps.DirectionsService();
        directionsRenderer = new google.maps.DirectionsRenderer({
            map: map,
            suppressMarkers: true,
            polylineOptions: {
                strokeColor: '#FF6B35',
                strokeWeight: 4,
                strokeOpacity: 0.8
            }
        });
        
        directionsService.route({
            origin: { lat: restaurantLat, lng: restaurantLng },
            destination: { lat: deliveryLat, lng: deliveryLng },
            travelMode: google.maps.TravelMode.DRIVING
        }, (response, status) => {
            if (status === 'OK') {
                directionsRenderer.setDirections(response);
                
                // Ajuster la vue pour voir tout l'itinéraire
                const bounds = new google.maps.LatLngBounds();
                bounds.extend({ lat: restaurantLat, lng: restaurantLng });
                bounds.extend({ lat: deliveryLat, lng: deliveryLng });
                map.fitBounds(bounds);
            } else {
                console.error('Erreur lors du calcul de l\'itinéraire:', status);
            }
        });
        
        // Driver marker (if assigned and has location)
        @if($order->driver && $order->driver->latitude && $order->driver->longitude)
        const driverLat = parseFloat({{ $order->driver->latitude }});
        const driverLng = parseFloat({{ $order->driver->longitude }});
        
        const driverMarker = new google.maps.Marker({
            position: { lat: driverLat, lng: driverLng },
            map: map,
            title: 'Livreur: {{ $order->driver->name }}',
            icon: {
                url: 'https://maps.google.com/mapfiles/ms/icons/green-dot.png',
                scaledSize: new google.maps.Size(40, 40)
            },
            animation: google.maps.Animation.BOUNCE
        });
        
        const driverInfo = new google.maps.InfoWindow({
            content: '<div style="padding: 0.75rem; max-width: 250px;"><strong style="color: #10B981;">🚴 Livreur</strong><br><p style="margin: 0.5rem 0 0 0; color: #6B7280;">{{ $order->driver->name }}</p>@if($order->driver->phone)<br><a href="tel:{{ $order->driver->phone }}" style="color: #FF6B35; text-decoration: none; margin-top: 0.5rem; display: inline-block;"><i class="fas fa-phone"></i> {{ $order->driver->phone }}</a>@endif</div>'
        });
        driverMarker.addListener('click', () => driverInfo.open(map, driverMarker));
        @endif
        @endif
    }
    
    // Global variables for map and markers
    let map, deliveryMarker, restaurantMarker, driverMarker, directionsRenderer;
    let currentStatus = '{{ $order->status }}';
    
    // Update progress line based on status
    function updateProgress(status) {
        const progressLine = document.getElementById('progressLine');
        let progress = 0;
        const statusSteps = {
            'pending': 25,
            'prepairing': 50,
            'assign': 75,
            'onway': 90,
            'completed': 100
        };
        
        progress = statusSteps[status] || 25;
        progressLine.style.height = progress + '%';
    }
    
    // Update timeline steps based on status
    function updateTimeline(status) {
        const steps = document.querySelectorAll('.timeline-step');
        const statusMap = {
            'pending': ['confirmed'],
            'prepairing': ['confirmed', 'preparing'],
            'assign': ['confirmed', 'preparing', 'onway'],
            'onway': ['confirmed', 'preparing', 'onway'],
            'completed': ['confirmed', 'preparing', 'onway', 'delivered']
        };
        
        const activeSteps = statusMap[status] || ['confirmed'];
        steps.forEach(step => {
            const stepStatus = step.getAttribute('data-status');
            if (activeSteps.includes(stepStatus)) {
                step.querySelector('div[style*="background"]').style.background = '#10B981';
                step.querySelector('div[style*="background"]').style.color = 'white';
            }
        });
    }
    
    // Update driver marker position
    function updateDriverMarker(lat, lng, driverName, driverPhone) {
        if (driverMarker) {
            driverMarker.setPosition({ lat: lat, lng: lng });
        } else if (map && lat && lng) {
            driverMarker = new google.maps.Marker({
                position: { lat: lat, lng: lng },
                map: map,
                title: 'Livreur: ' + driverName,
                icon: {
                    url: 'https://maps.google.com/mapfiles/ms/icons/green-dot.png',
                    scaledSize: new google.maps.Size(40, 40)
                },
                animation: google.maps.Animation.BOUNCE
            });
            
            const driverInfo = new google.maps.InfoWindow({
                content: '<div style="padding: 0.75rem; max-width: 250px;"><strong style="color: #10B981;">🚴 Livreur</strong><br><p style="margin: 0.5rem 0 0 0; color: #6B7280;">' + driverName + '</p>' + 
                         (driverPhone ? '<br><a href="tel:' + driverPhone + '" style="color: #FF6B35; text-decoration: none; margin-top: 0.5rem; display: inline-block;"><i class="fas fa-phone"></i> ' + driverPhone + '</a>' : '') + '</div>'
            });
            driverMarker.addListener('click', () => driverInfo.open(map, driverMarker));
        }
    }
    
    // Fetch order status via AJAX
    function fetchOrderStatus() {
        const orderNo = '{{ $order->order_no }}';
        
        fetch(`/api/order/${orderNo}/status`)
            .then(response => response.json())
            .then(data => {
                if (data.status && data.order) {
                    const order = data.order;
                    
                    // Update status if changed
                    if (order.status !== currentStatus) {
                        currentStatus = order.status;
                        updateProgress(order.status);
                        updateTimeline(order.status);
                        
                        // Show notification if status changed
                        if (order.status === 'completed') {
                            showNotification('Commande livrée!', 'Votre commande a été livrée avec succès.', 'success');
                        } else if (order.status === 'assign' && currentStatus !== 'assign') {
                            showNotification('Livreur assigné!', 'Un livreur a été assigné à votre commande.', 'info');
                        }
                    }
                    
                    // Update driver position if available
                    if (order.driver && order.driver.latitude && order.driver.longitude) {
                        updateDriverMarker(
                            parseFloat(order.driver.latitude),
                            parseFloat(order.driver.longitude),
                            order.driver.name,
                            order.driver.phone
                        );
                    }
                    
                    // Update remaining time
                    if (document.getElementById('remainingTime')) {
                        document.getElementById('remainingTime').textContent = 
                            order.remaining_minutes > 0 ? `${Math.round(order.remaining_minutes)} min` : 'En cours';
                    }
                }
            })
            .catch(error => {
                console.error('Erreur lors de la récupération du statut:', error);
            });
    }
    
    // Show notification
    function showNotification(title, message, type) {
        // Simple notification (you can enhance this with a toast library)
        const notification = document.createElement('div');
        notification.style.cssText = 'position: fixed; top: 20px; right: 20px; background: ' + 
            (type === 'success' ? '#10B981' : '#3B82F6') + '; color: white; padding: 1rem 1.5rem; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.15); z-index: 10000; max-width: 300px;';
        notification.innerHTML = `<strong>${title}</strong><br>${message}`;
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.style.opacity = '0';
            notification.style.transition = 'opacity 0.3s';
            setTimeout(() => notification.remove(), 300);
        }, 5000);
    }
    
    // Auto-refresh every 10 seconds
    let refreshInterval;
    function startAutoRefresh() {
        refreshInterval = setInterval(() => {
            fetchOrderStatus();
        }, 10000); // 10 seconds
    }
    
    // Initialize on page load
    window.addEventListener('load', () => {
        initTrackingMap();
        updateProgress(currentStatus);
        updateTimeline(currentStatus);
        @if(!in_array($order->status, ['completed', 'cancelled']))
        startAutoRefresh();
        @endif
    });
    
    // Stop auto-refresh when page is hidden
    document.addEventListener('visibilitychange', () => {
        if (document.hidden) {
            clearInterval(refreshInterval);
        } else {
            @if(!in_array($order->status, ['completed', 'cancelled']))
            startAutoRefresh();
            @endif
        }
    });
</script>
@endsection


