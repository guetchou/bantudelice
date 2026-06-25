@extends('layouts.admin-modern')
@section('title', 'Exports commandes')
@section('page_title', 'Exports comptables et commerciaux')
@section('nav_active', 'orders')

@section('style')
<style>
.rep-wrap{padding:20px;display:flex;flex-direction:column;gap:16px}.rep-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px}.rep-card{background:#fff;border:1px solid #e5e7eb;border-radius:10px;padding:15px}.rep-label{font-size:11px;text-transform:uppercase;letter-spacing:.05em;color:#6b7280}.rep-value{font-size:22px;font-weight:800;color:#111827;margin-top:5px}.rep-panel{background:#fff;border:1px solid #e5e7eb;border-radius:10px;overflow:hidden}.rep-head{padding:15px 18px;border-bottom:1px solid #eef0f3}.rep-head h2{font-size:15px;margin:0;color:#111827}.rep-head p{font-size:12px;color:#6b7280;margin:4px 0 0}.rep-form{display:grid;grid-template-columns:repeat(5,minmax(0,1fr)) auto;gap:10px;padding:16px 18px}.rep-form input,.rep-form select{width:100%;border:1px solid #d1d5db;border-radius:7px;padding:9px 10px;font-size:12px;background:#fff}.rep-actions{display:flex;gap:10px;flex-wrap:wrap;padding:0 18px 18px}.rep-btn{display:inline-flex;align-items:center;justify-content:center;padding:9px 13px;border-radius:7px;font-size:12px;font-weight:700;text-decoration:none;border:1px solid #1e3a5f}.rep-btn--primary{background:#1e3a5f;color:#fff}.rep-btn--light{background:#fff;color:#1e3a5f}.rep-note{font-size:12px;color:#6b7280;padding:0 18px 16px}.rep-note strong{color:#374151}@media(max-width:1000px){.rep-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.rep-form{grid-template-columns:repeat(2,minmax(0,1fr))}}@media(max-width:600px){.rep-grid,.rep-form{grid-template-columns:1fr}}
</style>
@endsection

@section('content')
@php
    $exportQuery = [
        'date_from' => $filters['date_from']->format('Y-m-d'),
        'date_to' => $filters['date_to']->format('Y-m-d'),
        'restaurant_id' => $filters['restaurant_id'],
        'payment_method' => $filters['payment_method'],
        'business_status' => $filters['business_status'],
    ];
@endphp
<div class="rep-wrap">
    <div class="rep-grid">
        <div class="rep-card"><div class="rep-label">Commandes</div><div class="rep-value">{{ number_format($summary['orders'], 0, ',', ' ') }}</div></div>
        <div class="rep-card"><div class="rep-label">Chiffre brut</div><div class="rep-value">{{ number_format($summary['gross_total'], 0, ',', ' ') }} <small>FCFA</small></div></div>
        <div class="rep-card"><div class="rep-label">Commission plateforme</div><div class="rep-value">{{ number_format($summary['admin_commission'], 0, ',', ' ') }} <small>FCFA</small></div></div>
        <div class="rep-card"><div class="rep-label">Commission restaurants</div><div class="rep-value">{{ number_format($summary['restaurant_commission'], 0, ',', ' ') }} <small>FCFA</small></div></div>
    </div>

    <div class="rep-panel">
        <div class="rep-head">
            <h2>Périmètre de l’export</h2>
            <p>Une commande est comptée une seule fois, même si elle contient plusieurs produits.</p>
        </div>
        <form method="GET" action="{{ route('admin.reports.orders.index') }}" class="rep-form">
            <input type="date" name="date_from" value="{{ $filters['date_from']->format('Y-m-d') }}" aria-label="Date de début">
            <input type="date" name="date_to" value="{{ $filters['date_to']->format('Y-m-d') }}" aria-label="Date de fin">
            <select name="restaurant_id">
                <option value="">Tous les restaurants</option>
                @foreach($restaurants as $restaurant)
                    <option value="{{ $restaurant->id }}" @selected((string) $filters['restaurant_id'] === (string) $restaurant->id)>{{ $restaurant->name }}</option>
                @endforeach
            </select>
            <select name="payment_method">
                <option value="">Tous les paiements</option>
                @foreach(['cash' => 'Cash', 'momo' => 'Mobile Money', 'paypal' => 'PayPal'] as $value => $label)
                    <option value="{{ $value }}" @selected($filters['payment_method'] === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <select name="business_status">
                <option value="">Tous les statuts</option>
                @foreach(['pending_restaurant_acceptance' => 'En attente', 'accepted' => 'Acceptée', 'in_kitchen' => 'En préparation', 'ready_for_pickup' => 'Prête', 'out_for_delivery' => 'En livraison', 'delivered' => 'Livrée', 'closed' => 'Clôturée', 'cancelled' => 'Annulée', 'refunded' => 'Remboursée'] as $value => $label)
                    <option value="{{ $value }}" @selected($filters['business_status'] === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <button class="rep-btn rep-btn--primary" type="submit">Appliquer</button>
        </form>
        <div class="rep-actions">
            <a class="rep-btn rep-btn--primary" href="{{ route('admin.reports.orders.accounting', array_filter($exportQuery, fn($value) => $value !== null && $value !== '')) }}">Exporter le fichier comptable</a>
            <a class="rep-btn rep-btn--light" href="{{ route('admin.reports.orders.commercial', array_filter($exportQuery, fn($value) => $value !== null && $value !== '')) }}">Exporter le fichier commercial</a>
        </div>
        <div class="rep-note"><strong>Protection des données :</strong> aucun nom, téléphone, e-mail ou adresse client n’est inclus dans ces fichiers.</div>
    </div>
</div>
@endsection
