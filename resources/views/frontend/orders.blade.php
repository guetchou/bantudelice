@extends('frontend.layouts.app-modern')
@php $foodBrandName = \App\Services\ConfigService::getCompanyName(); @endphp
@section('title', 'Mes commandes | ' . $foodBrandName)
@section('body_class', 'bd-orders-page')

@section('style')
<style>
.ord-shell {
    padding: 100px 0 60px;
}
.ord-container {
    max-width: 860px;
    margin: 0 auto;
    padding: 0 16px;
}
.ord-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 28px;
    flex-wrap: wrap;
    gap: 12px;
}
.ord-title {
    font-size: 1.9rem;
    font-weight: 900;
    color: #0f172a;
    margin: 0;
}
.ord-back {
    color: #009543;
    font-size: .9rem;
    text-decoration: none;
}
.ord-back:hover { text-decoration: underline; }

/* Tabs */
.ord-tabs {
    display: flex;
    gap: 4px;
    background: #f1f5f9;
    border-radius: 10px;
    padding: 4px;
    margin-bottom: 24px;
    width: fit-content;
}
.ord-tab {
    padding: 8px 20px;
    border-radius: 8px;
    font-size: .88rem;
    font-weight: 600;
    text-decoration: none;
    color: #64748b;
    transition: all .15s;
}
.ord-tab.is-active {
    background: #fff;
    color: #009543;
    box-shadow: 0 2px 8px rgba(0,0,0,.08);
}

/* Order card */
.ord-card {
    background: #fff;
    border-radius: 16px;
    box-shadow: 0 2px 12px rgba(15,23,42,.06);
    border: 1px solid #e2e8f0;
    padding: 18px 22px;
    margin-bottom: 12px;
    display: flex;
    align-items: center;
    gap: 16px;
    flex-wrap: wrap;
}
.ord-card__logo {
    width: 52px;
    height: 52px;
    border-radius: 10px;
    object-fit: cover;
    flex-shrink: 0;
}
.ord-card__info {
    flex: 1;
    min-width: 160px;
}
.ord-card__name {
    font-weight: 800;
    color: #0f172a;
    font-size: .95rem;
}
.ord-card__meta {
    font-size: .82rem;
    color: #64748b;
    margin-top: 2px;
}
.ord-card__total {
    font-weight: 900;
    color: #0f172a;
    font-size: 1rem;
    white-space: nowrap;
}
.ord-badge {
    padding: 5px 12px;
    border-radius: 99px;
    font-size: .75rem;
    font-weight: 700;
    white-space: nowrap;
    flex-shrink: 0;
}
.ord-actions {
    display: flex;
    gap: 8px;
    flex-shrink: 0;
}
.ord-btn-track {
    padding: 7px 16px;
    border-radius: 99px;
    background: #009543;
    color: #fff;
    font-size: .82rem;
    font-weight: 700;
    text-decoration: none;
    transition: background .15s;
}
.ord-btn-track:hover { background: #007a38; color: #fff; }
.ord-btn-receipt {
    padding: 7px 16px;
    border-radius: 99px;
    background: #f8fafc;
    color: #475569;
    border: 1px solid #e2e8f0;
    font-size: .82rem;
    font-weight: 600;
    text-decoration: none;
    transition: background .15s;
}
.ord-btn-receipt:hover { background: #f1f5f9; }
.ord-btn-rate {
    padding: 7px 16px;
    border-radius: 99px;
    background: #fffbeb;
    color: #d97706;
    border: 1px solid #fde68a;
    font-size: .82rem;
    font-weight: 700;
    text-decoration: none;
    transition: background .15s;
}
.ord-btn-rate:hover { background: #fef3c7; color: #b45309; }

/* Empty state */
.ord-empty {
    text-align: center;
    padding: 60px 20px;
    color: #94a3b8;
}
.ord-empty__icon {
    width: 64px;
    height: 64px;
    margin: 0 auto 16px;
    opacity: .35;
}
.ord-empty__title {
    font-size: 1rem;
    font-weight: 700;
    color: #475569;
    margin-bottom: 8px;
}
.ord-empty__cta {
    color: #009543;
    font-weight: 700;
    text-decoration: none;
}
.ord-empty__cta:hover { text-decoration: underline; }

/* Pagination */
.ord-pagination {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 8px;
    margin-top: 24px;
}
.ord-page-btn {
    padding: 8px 16px;
    border-radius: 8px;
    background: #fff;
    border: 1px solid #e2e8f0;
    color: #0f172a;
    font-size: .88rem;
    text-decoration: none;
    transition: background .15s;
}
.ord-page-btn:hover { background: #f8fafc; }
.ord-page-btn.is-disabled {
    background: #f1f5f9;
    color: #94a3b8;
    pointer-events: none;
    border-color: transparent;
}
.ord-page-info {
    padding: 8px 16px;
    border-radius: 8px;
    background: #f1f5f9;
    color: #64748b;
    font-size: .88rem;
}
</style>
@endsection

@section('content')
<section class="ord-shell">
    <div class="ord-container">

        <div class="ord-header">
            <h1 class="ord-title">Mes commandes</h1>
            <a href="{{ route('user.profile') }}" class="ord-back">← Retour au profil</a>
        </div>

        <div class="ord-tabs">
            <a href="{{ route('user.orders', ['tab' => 'active']) }}"
               class="ord-tab {{ $tab === 'active' ? 'is-active' : '' }}">En cours</a>
            <a href="{{ route('user.orders', ['tab' => 'completed']) }}"
               class="ord-tab {{ $tab === 'completed' ? 'is-active' : '' }}">Historique</a>
        </div>

        @forelse($orders as $order)
        @php
            $statusMap = [
                'pending'                       => ['label' => 'En attente',          'color' => '#f59e0b', 'bg' => '#fffbeb'],
                'pending_restaurant_acceptance' => ['label' => 'Attente restaurant',  'color' => '#f59e0b', 'bg' => '#fffbeb'],
                'accepted'                      => ['label' => 'Acceptée',            'color' => '#3b82f6', 'bg' => '#eff6ff'],
                'in_kitchen'                    => ['label' => 'En préparation',      'color' => '#8b5cf6', 'bg' => '#f5f3ff'],
                'ready_for_pickup'              => ['label' => 'Prête',               'color' => '#06b6d4', 'bg' => '#ecfeff'],
                'assign'                        => ['label' => 'Livreur assigné',     'color' => '#3b82f6', 'bg' => '#eff6ff'],
                'driver_assigned'               => ['label' => 'Livreur assigné',     'color' => '#3b82f6', 'bg' => '#eff6ff'],
                'picked_up'                     => ['label' => 'Récupérée',           'color' => '#0ea5e9', 'bg' => '#f0f9ff'],
                'out_for_delivery'              => ['label' => 'En livraison',         'color' => '#0ea5e9', 'bg' => '#f0f9ff'],
                'delivered'                     => ['label' => 'Livrée',              'color' => '#009543', 'bg' => '#f0fdf4'],
                'completed'                     => ['label' => 'Livrée',              'color' => '#009543', 'bg' => '#f0fdf4'],
                'picked_up_by_customer'         => ['label' => 'Retirée',             'color' => '#009543', 'bg' => '#f0fdf4'],
                'closed'                        => ['label' => 'Clôturée',            'color' => '#64748b', 'bg' => '#f8fafc'],
                'cancelled'                     => ['label' => 'Annulée',             'color' => '#ef4444', 'bg' => '#fef2f2'],
                'canceled'                      => ['label' => 'Annulée',             'color' => '#ef4444', 'bg' => '#fef2f2'],
                'failed'                        => ['label' => 'Échouée',             'color' => '#ef4444', 'bg' => '#fef2f2'],
            ];
            $rawStatus = $order->business_status ?? $order->status ?? 'pending';
            $statusLabel = $statusMap[$rawStatus] ?? ['label' => business_status_label($rawStatus), 'color' => '#64748b', 'bg' => '#f8fafc'];
        @endphp
        <div class="ord-card">

            @if(optional($order->restaurant)->logo)
            <img src="{{ strpos($order->restaurant->logo, 'http') === 0 ? $order->restaurant->logo : asset('images/restaurant_images/' . $order->restaurant->logo) }}"
                 alt="{{ optional($order->restaurant)->name }}"
                 class="ord-card__logo"
                 loading="lazy"
                 onerror="this.style.display='none'">
            @endif

            <div class="ord-card__info">
                <div class="ord-card__name">{{ optional($order->restaurant)->name ?? 'Restaurant' }}</div>
                <div class="ord-card__meta">#{{ $order->order_no }} · {{ \Carbon\Carbon::parse($order->created_at)->format('d/m/Y H:i') }}</div>
            </div>

            <div class="ord-card__total">{{ number_format((float)($order->total ?? 0), 0, ',', ' ') }} FCFA</div>

            <span class="ord-badge" style="background:{{ $statusLabel['bg'] }};color:{{ $statusLabel['color'] }};">
                {{ $statusLabel['label'] }}
            </span>

            @if($order->order_no)
            <div class="ord-actions">
                <a href="{{ route('track.order', $order->order_no) }}" class="ord-btn-track">Suivre</a>
                <a href="{{ route('order.receipt', $order->order_no) }}" class="ord-btn-receipt">Reçu</a>
                @if(in_array($rawStatus, ['completed', 'delivered', 'picked_up_by_customer', 'closed']))
                <a href="{{ route('track.order', $order->order_no) }}?rate=1" class="ord-btn-rate">
                    <i class="fas fa-star"></i> Avis
                </a>
                @endif
            </div>
            @endif
        </div>
        @empty
        <div class="ord-empty">
            <svg class="ord-empty__icon" viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg">
                <rect x="8" y="16" width="48" height="36" rx="6" stroke="currentColor" stroke-width="3"/>
                <path d="M22 16V13a10 10 0 0 1 20 0v3" stroke="currentColor" stroke-width="3" stroke-linecap="round"/>
                <path d="M22 32h20M22 40h12" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"/>
            </svg>
            <p class="ord-empty__title">Aucune commande {{ $tab === 'active' ? 'en cours' : 'dans votre historique' }}</p>
            <a href="{{ route('restaurants.all') }}" class="ord-empty__cta">Explorer les restaurants →</a>
        </div>
        @endforelse

        @if(isset($orders) && method_exists($orders, 'lastPage') && $orders->lastPage() > 1)
        <div class="ord-pagination">
            @if($orders->onFirstPage())
                <span class="ord-page-btn is-disabled">← Précédent</span>
            @else
                <a href="{{ $orders->previousPageUrl() }}&tab={{ $tab }}" class="ord-page-btn">← Précédent</a>
            @endif

            <span class="ord-page-info">Page {{ $orders->currentPage() }} / {{ $orders->lastPage() }}</span>

            @if($orders->hasMorePages())
                <a href="{{ $orders->nextPageUrl() }}&tab={{ $tab }}" class="ord-page-btn">Suivant →</a>
            @else
                <span class="ord-page-btn is-disabled">Suivant →</span>
            @endif
        </div>
        @endif

    </div>
</section>
@endsection
