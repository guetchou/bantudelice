@extends('layouts.admin-modern')
@section('title', 'Promotions')
@section('page_title', 'Promotions')
@section('nav_active', 'promotions')

@section('style')
<style>
.vch-page { padding:24px; }
.vch-alert { padding:12px 16px; border-radius:8px; font-size:13px; font-weight:500; border:1px solid transparent; margin-bottom:16px; }
.vch-alert--success { background:#f0fdf4; color:#166534; border-color:#bbf7d0; }
.vch-alert--danger  { background:#fef2f2; color:#991b1b; border-color:#fecaca; }
.vch-header { display:flex; align-items:center; justify-content:space-between; margin-bottom:20px; flex-wrap:wrap; gap:12px; }
.vch-header__title { font-size:20px; font-weight:700; color:#111827; margin:0; }
.vch-card { background:#fff; border:1px solid #e5e7eb; border-radius:10px; overflow:hidden; margin-bottom:20px; }
.vch-card__header { padding:14px 20px; border-bottom:1px solid #f3f4f6; }
.vch-card__title { font-size:14px; font-weight:700; color:#111827; margin:0; }
.vch-card__body { padding:20px; }
.vch-card__footer { padding:14px 20px; border-top:1px solid #f3f4f6; display:flex; justify-content:flex-end; gap:10px; }
.vch-form-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(180px,1fr)); gap:12px; align-items:flex-end; }
.vch-field { display:flex; flex-direction:column; gap:6px; }
.vch-label { font-size:13px; font-weight:600; color:#374151; }
.vch-input { width:100%; padding:9px 12px; border:1px solid #d1d5db; border-radius:6px; font-size:14px; color:#111827; background:#fff; transition:border-color .15s; box-sizing:border-box; }
.vch-input:focus { outline:none; border-color:#1e3a5f; box-shadow:0 0 0 3px rgba(30,58,95,.12); }
.vch-select { appearance:none; background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 20 20' fill='%236b7280'%3E%3Cpath fill-rule='evenodd' d='M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z'/%3E%3C/svg%3E"); background-repeat:no-repeat; background-position:right 10px center; background-size:18px; padding-right:36px; }
.vch-btn-primary { display:inline-flex; align-items:center; gap:6px; padding:8px 18px; background:#1e3a5f; color:#fff; border:none; border-radius:6px; font-size:13px; font-weight:600; cursor:pointer; text-decoration:none; transition:opacity .15s; }
.vch-btn-primary:hover { opacity:.85; color:#fff; }
.vch-table-wrap { overflow-x:auto; }
.vch-table { width:100%; border-collapse:collapse; font-size:13px; }
.vch-table thead th { padding:9px 14px; font-size:10px; font-weight:700; letter-spacing:.06em; text-transform:uppercase; color:#9ca3af; border-bottom:1px solid #f3f4f6; background:#f9fafb; text-align:left; white-space:nowrap; }
.vch-table tbody tr { border-bottom:1px solid #f3f4f6; transition:background .1s; }
.vch-table tbody tr:hover { background:#f9fafb; }
.vch-table td { padding:11px 14px; color:#374151; vertical-align:middle; }
.vch-table td .vch-sub { font-size:11px; color:#9ca3af; margin-top:2px; }
.vch-actions { display:flex; align-items:center; gap:6px; justify-content:flex-end; }
.vch-action-btn { display:inline-flex; align-items:center; gap:4px; padding:5px 10px; border-radius:5px; font-size:12px; font-weight:500; text-decoration:none; border:1px solid transparent; cursor:pointer; background:none; transition:background .15s,color .15s; }
.vch-action-btn--edit { border-color:#6366f1; color:#6366f1; }
.vch-action-btn--edit:hover { background:#6366f1; color:#fff; text-decoration:none; }
.vch-action-btn--del { border-color:#ef4444; color:#ef4444; }
.vch-action-btn--del:hover { background:#ef4444; color:#fff; }
.vch-badge { display:inline-flex; padding:2px 8px; border-radius:999px; font-size:11px; font-weight:600; }
.vch-badge--active { background:#f0fdf4; color:#166534; }
.vch-badge--inactive { background:#fef2f2; color:#991b1b; }
.vch-filter-bar { display:flex; flex-wrap:wrap; gap:10px; align-items:flex-end; }
.vch-empty { text-align:center; padding:40px 0; color:#9ca3af; font-size:14px; }
</style>
@endsection

@section('content')
<div class="vch-page">

    @if(session('success'))
        <div class="vch-alert vch-alert--success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="vch-alert vch-alert--danger">{{ session('error') }}</div>
    @endif

    <div class="vch-header">
        <h1 class="vch-header__title">Promotions &amp; coupons</h1>
        <a href="{{ route('admin.promotions.create') }}" class="vch-btn-primary">
            <i class="fa fa-plus"></i> Nouvelle promotion
        </a>
    </div>

    {{-- Filtres --}}
    <div class="vch-card" style="margin-bottom:16px;">
        <div class="vch-card__body">
            <form method="GET">
                <div class="vch-filter-bar">
                    <div class="vch-field">
                        <label class="vch-label">Recherche</label>
                        <input type="text" name="search" value="{{ request('search') }}" class="vch-input" placeholder="Code, restaurant...">
                    </div>
                    <div class="vch-field">
                        <label class="vch-label">Restaurant</label>
                        <select name="restaurant_id" class="vch-input vch-select">
                            <option value="">Tous les restaurants</option>
                            @foreach($restaurants as $restaurant)
                                <option value="{{ $restaurant->id }}" {{ (string) request('restaurant_id') === (string) $restaurant->id ? 'selected' : '' }}>
                                    {{ $restaurant->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="vch-field">
                        <label class="vch-label">Statut</label>
                        <select name="status" class="vch-input vch-select">
                            <option value="">Tous</option>
                            <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Actives</option>
                            <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactives</option>
                        </select>
                    </div>
                    <div class="vch-field">
                        <label class="vch-label" style="visibility:hidden">.</label>
                        <button type="submit" class="vch-btn-primary">
                            <i class="fa fa-search"></i> Filtrer
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- Table --}}
    <div class="vch-card">
        <div class="vch-table-wrap">
            <table class="vch-table">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Restaurant</th>
                        <th>Remise</th>
                        <th>Limites</th>
                        <th>Usage</th>
                        <th>Statut</th>
                        <th>Période</th>
                        <th style="text-align:right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($vouchers as $voucher)
                    <tr>
                        <td>
                            <strong>{{ $voucher->name }}</strong>
                            <div class="vch-sub">{{ strtoupper($voucher->discount_type ?? 'percentage') }}</div>
                        </td>
                        <td>{{ optional($voucher->restaurant)->name ?? 'Global' }}</td>
                        <td>
                            @if(($voucher->discount_type ?? 'percentage') === 'fixed')
                                {{ number_format((float) ($voucher->discount_value ?? $voucher->discount ?? 0), 0, ',', ' ') }} FCFA
                            @else
                                {{ number_format((float) ($voucher->discount_value ?? $voucher->discount ?? 0), 0, ',', ' ') }} %
                            @endif
                            @if(!empty($voucher->max_discount_amount))
                                <div class="vch-sub">Plafond {{ number_format((float) $voucher->max_discount_amount, 0, ',', ' ') }} FCFA</div>
                            @endif
                        </td>
                        <td>
                            <div>Min: {{ number_format((float) ($voucher->min_order_amount ?? 0), 0, ',', ' ') }} FCFA</div>
                            <div class="vch-sub">Par user: {{ (int) ($voucher->per_user_limit ?? 1) }}</div>
                        </td>
                        <td>
                            {{ (int) ($voucher->used_count ?? 0) }}
                            @if(!is_null($voucher->usage_limit))
                                / {{ (int) $voucher->usage_limit }}
                            @endif
                            <div class="vch-sub">{{ (int) ($voucher->redemptions_count ?? 0) }} validations</div>
                        </td>
                        <td>
                            <span class="vch-badge {{ !empty($voucher->is_active) ? 'vch-badge--active' : 'vch-badge--inactive' }}">
                                {{ !empty($voucher->is_active) ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                        <td>
                            <div>{{ optional($voucher->starts_at ?? $voucher->start_date)->format('d/m/Y H:i') ?? '—' }}</div>
                            <div class="vch-sub">{{ optional($voucher->ends_at ?? $voucher->end_date)->format('d/m/Y H:i') ?? '—' }}</div>
                        </td>
                        <td>
                            <div class="vch-actions">
                                <a href="{{ route('admin.promotions.edit', $voucher) }}" class="vch-action-btn vch-action-btn--edit">
                                    <i class="fa fa-edit"></i> Editer
                                </a>
                                <form action="{{ route('admin.promotions.destroy', $voucher) }}" method="POST" style="display:inline;" onsubmit="return confirm('Supprimer cette promotion ?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="vch-action-btn vch-action-btn--del">
                                        <i class="fa fa-trash"></i> Supprimer
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="vch-empty">Aucune promotion trouvée.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div style="margin-top:12px;">
        {{ $vouchers->links() }}
    </div>

</div>
@endsection
