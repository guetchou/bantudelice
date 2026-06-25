@extends('layouts.admin-modern')
@section('title','Litiges encaissement cash')
@section('page_title', 'Litiges encaissement cash')
@section('nav_active', 'orders')
@section('style')
<style>
.ord-wrap { display:flex; flex-direction:column; gap:16px; }
.ord-alert { padding:12px 16px; border-radius:8px; font-size:13px; font-weight:500; border:1px solid transparent; }
.ord-alert--success { background:#f0fdf4; color:#166534; border-color:#bbf7d0; }
.ord-alert--danger  { background:#fef2f2; color:#991b1b; border-color:#fecaca; }
.ord-card { background:#fff; border:1px solid #e5e7eb; border-radius:10px; overflow:hidden; }
.ord-card__head { display:flex; align-items:center; justify-content:space-between; padding:14px 20px; border-bottom:1px solid #f3f4f6; flex-wrap:wrap; gap:8px; }
.ord-card__title { font-size:14px; font-weight:700; color:#111827; margin:0; }
.ord-card__count { font-size:11px; color:#6b7280; margin-top:2px; }
.ord-table-wrap { overflow-x:auto; }
.ord-table { width:100%; border-collapse:collapse; font-size:13px; }
.ord-table thead th { padding:9px 14px; font-size:10px; font-weight:700; letter-spacing:.06em; text-transform:uppercase; color:#9ca3af; border-bottom:1px solid #f3f4f6; background:#f9fafb; text-align:left; white-space:nowrap; }
.ord-table tbody tr { border-bottom:1px solid #f3f4f6; }
.ord-table tbody tr:last-child { border-bottom:none; }
.ord-table td { padding:11px 14px; color:#374151; vertical-align:middle; }
.ord-pill { display:inline-flex; align-items:center; padding:2px 8px; border-radius:999px; font-size:11px; font-weight:600; white-space:nowrap; }
.ord-pill--warn   { background:#fefce8; color:#854d0e; }
.ord-pill--danger { background:#fef2f2; color:#991b1b; }
.ord-resolve-form { display:inline-flex; gap:6px; align-items:center; }
.ord-resolve-form select { border:1px solid #d1d5db; border-radius:6px; padding:5px 8px; font-size:12px; }
.ord-resolve-form button,.ord-link { padding:6px 12px; border:1px solid #1e3a5f; border-radius:6px; color:#1e3a5f; background:#fff; font-size:12px; font-weight:600; cursor:pointer; text-decoration:none; }
.ord-resolve-form button:hover,.ord-link:hover { background:#1e3a5f; color:#fff; }
</style>
@endsection

@section('content')
<div class="ord-wrap" style="padding:20px;">

    @if(session()->has('alert'))
        <div class="ord-alert ord-alert--{{ session()->get('alert.type') }}">
            {{ session()->get('alert.message') }}
        </div>
    @endif

    <div class="ord-card">
        <div class="ord-card__head">
            <div>
                <p class="ord-card__title">Litiges encaissement cash</p>
                <p class="ord-card__count">{{ $orders->count() }} commande(s) à traiter</p>
            </div>
            <a class="ord-link" href="{{ route('admin.cash_collections.index') }}">Voir toute la collecte cash</a>
        </div>
        <div class="ord-table-wrap">
            <table class="ord-table">
                <thead>
                    <tr>
                        <th>N° commande</th>
                        <th>Restaurant</th>
                        <th>Client</th>
                        <th>Montant</th>
                        <th>Statut</th>
                        <th>Référence / notes</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($orders as $order)
                    <tr>
                        <td>
                            <a href="{{ route('admin.show_order', $order->order_no) }}">{{ $order->order_no }}</a>
                        </td>
                        <td>{{ $order->restaurant->name ?? '—' }}</td>
                        <td>{{ $order->user->name ?? '—' }}</td>
                        <td>{{ number_format((float) ($order->total ?? 0), 0, ',', ' ') }} FCFA</td>
                        <td>
                            @if($order->cash_collection_status === 'disputed')
                                <span class="ord-pill ord-pill--warn">Litige déclaré</span>
                            @else
                                <span class="ord-pill ord-pill--danger">Collecte échouée</span>
                            @endif
                        </td>
                        <td>{{ $order->cash_collection_reference ?? '—' }}</td>
                        <td>
                            <form method="POST" action="{{ route('admin.cash_disputes.resolve', $order->order_no) }}" class="ord-resolve-form"
                                  onsubmit="return confirm('Confirmer cette résolution ?');">
                                @csrf
                                <select name="resolution" required>
                                    <option value="confirmed_collected">Espèces bien reçues</option>
                                    <option value="confirmed_not_collected">Espèces non reçues confirmées</option>
                                </select>
                                <button type="submit">Résoudre</button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" style="text-align:center;color:#9ca3af;padding:24px;">Aucun litige en cours</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection
