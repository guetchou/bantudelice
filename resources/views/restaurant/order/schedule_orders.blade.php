@extends('layouts.restaurant_app')
@section('title', 'Commandes programmées | ' . \App\Services\ConfigService::getCompanyName())
@section('topbar_title', 'Commandes programmées')
@section('order_nav', 'active')

@section('content')
@include('restaurant.order._ord_shared', ['activeTab' => 'scheduled'])

<div class="ord" style="margin-top:20px;">

    @if(session()->has('alert'))
        <div class="alert alert-{{ session()->get('alert.type') }} alert-dismissible" role="alert">
            {{ session()->get('alert.message') }}
            <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
        </div>
    @endif

    {{-- Tableau --}}
    <div class="ord-card">
        <div class="ord-card__head">
            <div>
                <div class="ord-card__title">Commandes programmées</div>
                <div class="ord-card__meta">Livraison différée · confirmées par le client</div>
            </div>
            <span style="font-size:12px;color:var(--bd-text-3);">{{ $orders->count() }} commande(s)</span>
        </div>
        <div class="ord-table-wrap">
            @if($orders->count() > 0)
                <table class="ord-table">
                    <thead>
                        <tr>
                            <th>Référence</th>
                            <th class="ord-col-hide">Client</th>
                            <th>Montant</th>
                            <th>Date prévue</th>
                            <th>Statut</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($orders as $order)
                            @php
                                $schedDate = $order->scheduled_date ?? $order->schedule_date ?? null;
                            @endphp
                            <tr>
                                <td>
                                    <span class="ord-ref">{{ $order->order_no }}</span>
                                    <span class="ord-ref-time">Passée le {{ \Carbon\Carbon::parse($order->created_at)->format('d/m · H:i') }}</span>
                                </td>
                                <td class="ord-col-hide">{{ $order->user->name ?? $order->customer_name ?? '—' }}</td>
                                <td>
                                    <span class="ord-amount">{{ number_format((float) $order->total, 0, ',', ' ') }}</span>
                                    <span class="ord-amount-cur">FCFA</span>
                                </td>
                                <td>
                                    @if($schedDate)
                                        <span class="ord-sched">
                                            <i class="fas fa-calendar-check" style="color:var(--bd-green);margin-right:4px;"></i>
                                            {{ \Carbon\Carbon::parse($schedDate)->format('d/m/Y · H:i') }}
                                        </span>
                                    @else
                                        <span style="color:var(--bd-text-3);">—</span>
                                    @endif
                                </td>
                                <td>
                                    @php
                                        $stMap = [
                                            'pending'    => ['Nouvelle',    'new'],
                                            'new'        => ['Nouvelle',    'new'],
                                            'accepted'   => ['Acceptée',    'preparing'],
                                            'preparing'  => ['Préparation', 'preparing'],
                                            'assigned'   => ['Assignée',    'delivering'],
                                            'delivering' => ['En route',    'delivering'],
                                            'completed'  => ['Terminée',    'done'],
                                            'delivered'  => ['Livrée',      'done'],
                                            'cancelled'  => ['Annulée',     'cancelled'],
                                        ];
                                        $st = $stMap[strtolower($order->status ?? '')] ?? ['Programmée', 'scheduled'];
                                    @endphp
                                    <span class="ord-badge ord-badge--{{ $st[1] }}">{{ $st[0] }}</span>
                                </td>
                                <td>
                                    <a href="{{ route('restaurant.show_order', $order->order_no) }}" class="ord-action-btn" title="Voir">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <div class="ord-empty">
                    <i class="fas fa-calendar-clock" style="color:var(--bd-text-3);"></i>
                    Aucune commande programmée
                </div>
            @endif
        </div>
    </div>

</div>
@endsection
