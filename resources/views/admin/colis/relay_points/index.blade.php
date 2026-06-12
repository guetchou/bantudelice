@extends('layouts.admin-modern')
@section('title', 'Points Relais | Mema')
@section('page_title', 'Points relais')
@section('nav_active', 'relay-points')

@section('style')
<style>
.rly-page { padding:24px; }
.rly-topbar { display:flex; justify-content:space-between; align-items:center; margin-bottom:16px; }
.rly-btn-primary { display:inline-flex; align-items:center; gap:6px; padding:9px 18px; background:#1e3a5f; color:#fff; border:none; border-radius:6px; font-size:13px; font-weight:600; text-decoration:none; }
.rly-btn-primary:hover { opacity:.85; color:#fff; text-decoration:none; }
.rly-card { background:#fff; border:1px solid #e5e7eb; border-radius:10px; overflow:hidden; }
.rly-table-wrap { overflow-x:auto; }
.rly-table { width:100%; border-collapse:collapse; font-size:13px; }
.rly-table thead th { padding:9px 14px; font-size:10px; font-weight:700; letter-spacing:.06em; text-transform:uppercase; color:#9ca3af; border-bottom:1px solid #f3f4f6; background:#f9fafb; text-align:left; }
.rly-table tbody td { padding:10px 14px; color:#374151; vertical-align:middle; border-bottom:1px solid #f3f4f6; }
.rly-table tbody tr:last-child td { border-bottom:none; }
.rly-pill { display:inline-flex; align-items:center; padding:3px 10px; border-radius:20px; font-size:11px; font-weight:600; }
.rly-pill--success { background:#d1fae5; color:#065f46; }
.rly-pill--danger  { background:#fee2e2; color:#991b1b; }
.rly-btn-toggle-on  { display:inline-flex; align-items:center; padding:5px 12px; background:#f59e0b; color:#fff; border:none; border-radius:5px; font-size:12px; font-weight:600; cursor:pointer; }
.rly-btn-toggle-off { display:inline-flex; align-items:center; padding:5px 12px; background:#16a34a; color:#fff; border:none; border-radius:5px; font-size:12px; font-weight:600; cursor:pointer; }
</style>
@endsection

@section('content')
<div class="rly-page">
    <div class="rly-topbar">
        <div></div>
        <a href="{{ route('admin.relay-points.create') }}" class="rly-btn-primary">
            <i class="fas fa-plus"></i> Ajouter un point relais
        </a>
    </div>

    <div class="rly-card">
        <div class="rly-table-wrap">
            <table class="rly-table">
                <thead>
                    <tr>
                        <th>Nom</th>
                        <th>Ville / Quartier</th>
                        <th>Contact</th>
                        <th>Statut</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($relayPoints as $rp)
                    <tr>
                        <td>{{ $rp->name }}</td>
                        <td>{{ $rp->city }} ({{ $rp->district }})</td>
                        <td>{{ $rp->contact_phone }}</td>
                        <td>
                            <span class="rly-pill {{ $rp->is_active ? 'rly-pill--success' : 'rly-pill--danger' }}">
                                {{ $rp->is_active ? 'Actif' : 'Inactif' }}
                            </span>
                        </td>
                        <td>
                            <form action="{{ route('admin.relay-points.toggle', $rp->id) }}" method="POST" style="display:inline;">
                                @csrf
                                <button type="submit" class="{{ $rp->is_active ? 'rly-btn-toggle-on' : 'rly-btn-toggle-off' }}">
                                    {{ $rp->is_active ? 'Désactiver' : 'Activer' }}
                                </button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
