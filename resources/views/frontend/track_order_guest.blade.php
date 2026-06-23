@extends('frontend.layouts.app-modern')
@php
    $foodBrandName = \App\Services\ConfigService::getCompanyName();
    $businessStatus = $order->effective_business_status ?? $order->resolveEffectiveBusinessStatus();
    $trackingProgress = $order->tracking_progress ?? $order->resolveTrackingProgress();
@endphp
@section('title', 'Suivi commande #' . $order->order_no . ' | ' . $foodBrandName)
@section('content')
<div style="max-width:720px;margin:24px auto;padding:16px">
    <div style="background:#fff;border-radius:18px;padding:22px;box-shadow:0 2px 14px rgba(0,0,0,.08);margin-bottom:14px">
        <p style="color:#009543;font-weight:800;text-transform:uppercase;font-size:12px;margin:0 0 8px">Suivi commande</p>
        <h1 style="margin:0 0 10px;font-size:28px">Commande #{{ $order->order_no }}</h1>
        <p style="margin:0;color:#6b7280">Statut : <strong>{{ str_replace('_', ' ', $businessStatus) }}</strong></p>
        <div style="height:10px;background:#e5e7eb;border-radius:99px;overflow:hidden;margin-top:16px">
            <span style="display:block;height:100%;width:{{ max(0, min(100, (int) $trackingProgress)) }}%;background:#009543"></span>
        </div>
    </div>

    <div style="background:#fff;border-radius:18px;padding:22px;box-shadow:0 2px 14px rgba(0,0,0,.08);margin-bottom:14px">
        <h2 style="font-size:18px;margin:0 0 12px">Articles</h2>
        @foreach($orderItems as $item)
            <div style="padding:10px 0;border-bottom:1px solid #f3f4f6">
                <strong>{{ optional($item->product)->name ?? 'Article' }}</strong><br>
                <span style="color:#6b7280">Quantité : {{ (int) $item->qty }}</span>
            </div>
        @endforeach
    </div>

    <div style="background:#eff6ff;border:1px solid #dbeafe;border-radius:18px;padding:16px;color:#1e3a8a">
        Les détails complets restent réservés au propriétaire connecté.
    </div>
</div>
@endsection
