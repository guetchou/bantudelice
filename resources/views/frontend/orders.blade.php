@extends('frontend.layouts.app-modern')
@php $foodBrandName = \App\Services\ConfigService::getCompanyName(); @endphp
@section('title', 'Mes commandes | ' . $foodBrandName)
@section('body_class', 'bd-orders-page')

@section('content')
<section class="section" style="padding: 100px 0 60px;">
    <div class="container" style="max-width:860px;">

        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:28px;flex-wrap:wrap;gap:12px;">
            <h1 style="font-family:'Poppins',sans-serif;font-size:1.9rem;font-weight:900;color:#0f172a;margin:0;">Mes commandes</h1>
            <a href="{{ route('user.profile') }}" style="color:#009543;font-size:.9rem;">← Retour au profil</a>
        </div>

        {{-- Onglets --}}
        <div style="display:flex;gap:4px;background:#f1f5f9;border-radius:10px;padding:4px;margin-bottom:24px;width:fit-content;">
            <a href="{{ route('user.orders', ['tab' => 'active']) }}"
               style="padding:8px 20px;border-radius:8px;font-size:.88rem;font-weight:600;text-decoration:none;
                      {{ $tab === 'active' ? 'background:#fff;color:#009543;box-shadow:0 2px 8px rgba(0,0,0,.08);' : 'color:#64748b;' }}">
               En cours
            </a>
            <a href="{{ route('user.orders', ['tab' => 'completed']) }}"
               style="padding:8px 20px;border-radius:8px;font-size:.88rem;font-weight:600;text-decoration:none;
                      {{ $tab === 'completed' ? 'background:#fff;color:#009543;box-shadow:0 2px 8px rgba(0,0,0,.08);' : 'color:#64748b;' }}">
               Historique
            </a>
        </div>

        @forelse($orders as $order)
        @php
            $statusLabel = [
                'pending'   => ['label' => 'En attente', 'color' => '#f59e0b', 'bg' => '#fffbeb'],
                'assign'    => ['label' => 'Livreur assigné', 'color' => '#3b82f6', 'bg' => '#eff6ff'],
                'completed' => ['label' => 'Livrée', 'color' => '#009543', 'bg' => '#f0fdf4'],
                'cancelled' => ['label' => 'Annulée', 'color' => '#ef4444', 'bg' => '#fef2f2'],
            ][$order->status ?? 'pending'] ?? ['label' => ucfirst($order->status ?? ''), 'color' => '#64748b', 'bg' => '#f8fafc'];
        @endphp
        <div style="background:#fff;border-radius:16px;box-shadow:0 2px 12px rgba(15,23,42,.06);border:1px solid #e2e8f0;padding:20px 22px;margin-bottom:14px;display:flex;align-items:center;gap:16px;flex-wrap:wrap;">

            {{-- Logo restaurant --}}
            @if(optional($order->restaurant)->logo)
            <img src="{{ strpos($order->restaurant->logo, 'http') === 0 ? $order->restaurant->logo : asset('images/restaurant_images/' . $order->restaurant->logo) }}"
                 alt="{{ optional($order->restaurant)->name }}"
                 style="width:52px;height:52px;border-radius:10px;object-fit:cover;flex-shrink:0;"
                 onerror="this.style.display='none'">
            @endif

            <div style="flex:1;min-width:160px;">
                <div style="font-weight:800;color:#0f172a;font-size:.95rem;">{{ optional($order->restaurant)->name ?? 'Restaurant' }}</div>
                <div style="font-size:.82rem;color:#64748b;margin-top:2px;">#{{ $order->order_no }} · {{ \Carbon\Carbon::parse($order->created_at)->format('d/m/Y H:i') }}</div>
            </div>

            <div style="text-align:center;">
                <div style="font-weight:900;color:#0f172a;font-size:1rem;">{{ number_format((float)($order->total ?? 0), 0, ',', ' ') }} FCFA</div>
            </div>

            <span style="padding:5px 12px;border-radius:99px;font-size:.78rem;font-weight:700;background:{{ $statusLabel['bg'] }};color:{{ $statusLabel['color'] }};">
                {{ $statusLabel['label'] }}
            </span>

            <div style="display:flex;gap:8px;">
                @if($order->order_no)
                    <a href="{{ route('track.order', $order->order_no) }}"
                       style="padding:7px 16px;border-radius:99px;background:#009543;color:#fff;font-size:.82rem;font-weight:700;text-decoration:none;">
                       Suivre
                    </a>
                    <a href="{{ route('order.receipt', $order->order_no) }}"
                       style="padding:7px 16px;border-radius:99px;background:#f8fafc;color:#475569;border:1px solid #e2e8f0;font-size:.82rem;font-weight:600;text-decoration:none;">
                       Reçu
                    </a>
                @endif
            </div>
        </div>
        @empty
        <div style="text-align:center;padding:60px 20px;color:#94a3b8;">
            <div style="font-size:3rem;margin-bottom:12px;">🛒</div>
            <p style="font-size:1rem;font-weight:600;">Aucune commande {{ $tab === 'active' ? 'en cours' : 'dans l\'historique' }}</p>
            <a href="{{ route('restaurants.all') }}" style="color:#009543;font-weight:700;">Explorer les restaurants →</a>
        </div>
        @endforelse

        {{-- Pagination --}}
        @if($orders->lastPage() > 1)
        <div style="display:flex;justify-content:center;gap:8px;margin-top:24px;">
            @if($orders->onFirstPage())
                <span style="padding:8px 16px;border-radius:8px;background:#f1f5f9;color:#94a3b8;font-size:.88rem;">← Précédent</span>
            @else
                <a href="{{ $orders->previousPageUrl() }}&tab={{ $tab }}" style="padding:8px 16px;border-radius:8px;background:#fff;border:1px solid #e2e8f0;color:#0f172a;font-size:.88rem;text-decoration:none;">← Précédent</a>
            @endif

            <span style="padding:8px 16px;border-radius:8px;background:#f1f5f9;color:#64748b;font-size:.88rem;">
                Page {{ $orders->currentPage() }} / {{ $orders->lastPage() }}
            </span>

            @if($orders->hasMorePages())
                <a href="{{ $orders->nextPageUrl() }}&tab={{ $tab }}" style="padding:8px 16px;border-radius:8px;background:#fff;border:1px solid #e2e8f0;color:#0f172a;font-size:.88rem;text-decoration:none;">Suivant →</a>
            @else
                <span style="padding:8px 16px;border-radius:8px;background:#f1f5f9;color:#94a3b8;font-size:.88rem;">Suivant →</span>
            @endif
        </div>
        @endif

    </div>
</section>
@endsection
