@extends('frontend.layouts.app-modern')
@php
    $foodBrandName = \App\Services\ConfigService::getCompanyName();
@endphp
@section('title', 'Confirmation de commande | ' . $foodBrandName)
@section('description', 'Votre commande ' . $foodBrandName . ' a été confirmée. Suivez votre livraison en temps réel.')
@php
    $receiptReference = $order->order_no ?? ('CMD-' . str_pad($order->id, 6, '0', STR_PAD_LEFT));
@endphp
@section('title', 'Reçu de commande #' . $receiptReference . ' | ' . $foodBrandName)

@php
    $driverEntity = optional($delivery)->driver ?: ($order->driver ?? null);
    $paymentExperience = $paymentExperience ?? null;
    $paymentStatus = $paymentExperience['status'] ?? strtoupper($order->payment_status ?? 'PENDING');
    $paymentMethod = ucfirst($order->payment_method ?? 'Non précisé');
    $isPickup = method_exists($order, 'isPickup') ? $order->isPickup() : (($order->fulfillment_mode ?? 'delivery') === 'pickup');
    $statusLabel = method_exists($order, 'resolveEffectiveBusinessStatus')
        ? $order->resolveEffectiveBusinessStatus()
        : ($order->status ?? 'completed');
    $receiptQrTarget = route('track.order', ['orderNo' => $order->order_no ?? $receiptReference]);
@endphp

@section('styles')
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800;900&family=Poppins:wght@400;600;700;800;900&display=swap" rel="stylesheet">
@endsection

@section('content')
<style>
  .or-btn-primary { display:inline-flex;align-items:center;gap:.45rem;background:#009543;color:#fff;font-weight:700;font-size:.9rem;padding:.7rem 1.35rem;border-radius:999px;border:none;cursor:pointer;text-decoration:none;transition:background .18s; }
  .or-btn-primary:hover { background:#007836;color:#fff; }
  .or-btn-secondary { display:inline-flex;align-items:center;gap:.45rem;background:#fff;color:#0f172a;font-weight:600;font-size:.9rem;padding:.7rem 1.35rem;border-radius:999px;border:1.5px solid #e2e8f0;cursor:pointer;text-decoration:none;transition:border-color .18s; }
  .or-btn-secondary:hover { border-color:#009543;color:#009543; }
</style>

<section style="padding:120px 0 40px; background:linear-gradient(135deg,#052e16 0%,#009543 100%); color:#fff;">
    <div class="container" style="max-width:980px;">
        <p style="margin:0 0 .5rem; opacity:.8;">Reçu de commande</p>
        <h1 style="margin:0; font-size:2.2rem; font-weight:900;">Commande #{{ $receiptReference }}</h1>
    </div>
</section>

<section style="background:#f8fafc; padding:2rem 0 3rem;">
    <div class="container" style="max-width:980px;">
        <div style="background:#fff; border-radius:24px; box-shadow:0 12px 30px rgba(15,23,42,.06); overflow:hidden;">
            <div style="padding:1.5rem 2rem; border-bottom:1px solid #e5e7eb; display:flex; justify-content:space-between; gap:1rem; flex-wrap:wrap;">
                <div>
                    <div style="color:#64748b; font-size:.82rem;">Client</div>
                    <div style="font-weight:800; color:#0f172a;">{{ $order->user->name ?? 'Client' }}</div>
                    <div style="color:#475569;">{{ $order->user->email ?? '' }}</div>
                </div>
                <div>
                    <div style="color:#64748b; font-size:.82rem;">Restaurant</div>
                    <div style="font-weight:800; color:#0f172a;">{{ $order->restaurant->name ?? 'Restaurant' }}</div>
                    <div style="color:#475569;">{{ $order->restaurant->address ?? '' }}</div>
                </div>
                <div style="text-align:right;">
                    <div style="color:#64748b; font-size:.82rem;">Date</div>
                    <div style="font-weight:800; color:#0f172a;">{{ $order->created_at->format('d/m/Y à H:i') }}</div>
                    <div style="color:#475569;">Paiement: {{ $paymentStatus }}</div>
                    @if($paymentExperience)
                        <div style="color:#0f172a; font-weight:700; margin-top:.35rem;">{{ $paymentExperience['customer_message'] }}</div>
                    @endif
                </div>
            </div>

            <div style="padding:2rem;">
                <div style="display:grid; gap:1rem;">
                    @foreach($items as $item)
                        <div style="display:flex; align-items:center; gap:1rem; border-bottom:1px solid #f1f5f9; padding-bottom:1rem;">
                            @if(optional($item->product)->image)
                                <img src="{{ asset('images/product_images/' . $item->product->image) }}" alt="{{ $item->product->name ?? 'Produit' }}" style="width:60px; height:60px; border-radius:14px; object-fit:cover;">
                            @endif
                            <div style="flex:1;">
                                <div style="font-weight:800; color:#0f172a;">{{ $item->product->name ?? 'Produit' }}</div>
                                <div style="color:#64748b;">Quantité: {{ $item->qty }}</div>
                            </div>
                            <div style="font-weight:800; color:#0f172a;">{{ number_format($item->price * $item->qty, 0, ',', ' ') }} FCFA</div>
                        </div>
                    @endforeach
                </div>

                <div style="margin-top:1.5rem; display:grid; grid-template-columns:1.2fr .8fr; gap:1.25rem;">
                    <div style="background:#f8fafc; border-radius:18px; padding:1.25rem;">
                        <h3 style="margin:0 0 .85rem; font-size:1rem; font-weight:900;">{{ $isPickup ? 'Retrait' : 'Livraison' }}</h3>
                        <div style="color:#475569; line-height:1.7;">
                            <div><strong>{{ $isPickup ? 'Référence:' : 'Adresse:' }}</strong> {{ $order->delivery_address ?? 'Adresse non renseignée' }}</div>
                            <div><strong>Mode de paiement:</strong> {{ $paymentMethod }}</div>
                            @if($paymentExperience)
                                <div><strong>Diagnostic paiement:</strong> {{ $paymentExperience['customer_message'] }}</div>
                                @if(!empty($paymentExperience['support_action']))
                                    <div><strong>Action support:</strong> {{ $paymentExperience['support_action'] }}</div>
                                @endif
                                @if(!empty($paymentExperience['failure_reason']))
                                    <div><strong>Code provider:</strong> {{ $paymentExperience['failure_reason'] }}</div>
                                @endif
                            @endif
                            <div><strong>Statut:</strong> {{ str_replace('_', ' ', $statusLabel) }}</div>
                            @if(!$isPickup && $driverEntity)
                                <div><strong>Livreur:</strong> {{ $driverEntity->name }}{{ !empty($driverEntity->phone) ? ' (' . $driverEntity->phone . ')' : '' }}</div>
                            @endif
                            @if(!$isPickup && $delivery?->delivered_at)
                                <div><strong>Remise:</strong> {{ $delivery->delivered_at->format('d/m/Y à H:i') }}</div>
                            @endif
                            @if(!$isPickup && $delivery?->customer_confirmed_at)
                                <div><strong>Confirmation:</strong> {{ $delivery->customer_confirmed_at->format('d/m/Y à H:i') }}</div>
                            @endif
                            @if(!$isPickup && $delivery && !empty($delivery->delivery_proof_path))
                                <div><strong>Preuve:</strong> <a href="{{ asset($delivery->delivery_proof_path) }}" target="_blank" rel="noopener">Voir la preuve de remise</a></div>
                            @endif
                            @if($isPickup && !empty($order->pickup_code))
                                <div><strong>Code retrait:</strong> {{ $order->pickup_code }}</div>
                            @endif
                        </div>
                    </div>

                    <div style="background:#f0fdf4; border:1px solid #bbf7d0; border-radius:18px; padding:1.25rem;">
                        <div style="display:flex; justify-content:space-between; margin-bottom:.45rem;"><span style="color:#64748b;">Sous-total</span><strong>{{ number_format($order->sub_total, 0, ',', ' ') }} FCFA</strong></div>
                        <div style="display:flex; justify-content:space-between; margin-bottom:.45rem;"><span style="color:#64748b;">{{ $isPickup ? 'Frais de retrait' : 'Livraison' }}</span><strong>{{ number_format($order->delivery_charges, 0, ',', ' ') }} FCFA</strong></div>
                        <div style="display:flex; justify-content:space-between; margin-bottom:.45rem;"><span style="color:#64748b;">Taxes</span><strong>{{ number_format($order->tax, 0, ',', ' ') }} FCFA</strong></div>
                        @if($order->driver_tip > 0)
                            <div style="display:flex; justify-content:space-between; margin-bottom:.45rem;"><span style="color:#64748b;">Pourboire</span><strong>{{ number_format($order->driver_tip, 0, ',', ' ') }} FCFA</strong></div>
                        @endif
                        <div style="display:flex; justify-content:space-between; border-top:1px solid #d1fae5; padding-top:.85rem; margin-top:.85rem;">
                            <span style="font-size:1.05rem; font-weight:900;">Total</span>
                            <span style="font-size:1.2rem; font-weight:900; color:#009543;">{{ number_format($order->total, 0, ',', ' ') }} FCFA</span>
                        </div>
                        <div style="margin-top:1rem; padding-top:1rem; border-top:1px solid #d1fae5; text-align:center;">
                            <div style="font-size:.82rem; color:#64748b; margin-bottom:.5rem;">QR de suivi</div>
                            <div style="display:inline-flex; align-items:center; justify-content:center; width:160px; min-height:160px; max-width:100%; background:#fff; border-radius:14px; padding:.5rem; border:1px solid rgba(15,23,42,.08);">
                                {!! QrCode::format('svg')->size(148)->margin(1)->generate($receiptQrTarget) !!}
                            </div>
                            <div style="margin-top:.75rem; font-size:.82rem; color:#475569; word-break:break-word;">{{ $receiptReference }}</div>
                            <a href="{{ $receiptQrTarget }}" style="display:inline-block; margin-top:.35rem; font-size:.82rem;">Ouvrir le suivi</a>
                        </div>
                    </div>
                </div>

                <div style="display:flex; gap:1rem; flex-wrap:wrap; margin-top:1.5rem;">
                    <a href="{{ route('track.order', $order->order_no) }}" class="or-btn-primary">Suivre la commande</a>
                    <button type="button" class="or-btn-secondary" onclick="window.print()">Imprimer le reçu</button>
                    <a href="{{ route('user.profile') }}" class="or-btn-secondary">Retour au profil</a>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
