@extends('layouts.admin-modern')
@section('title', 'Promotions')
@section('page_title', 'Promotions')
@section('nav_active', 'promotions')
@section('content')
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0 text-dark">Promotions & coupons</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Accueil</a></li>
                    <li class="breadcrumb-item active">Promotions</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<section class="content">
    <div class="container-fluid">
        <div class="row mb-3">
            <div class="col-md-8">
                <form method="GET" class="form-inline">
                    <input type="text" name="search" value="{{ request('search') }}" class="form-control mr-2 mb-2" placeholder="Code, restaurant...">
                    <select name="restaurant_id" class="form-control mr-2 mb-2">
                        <option value="">Tous les restaurants</option>
                        @foreach($restaurants as $restaurant)
                            <option value="{{ $restaurant->id }}" {{ (string) request('restaurant_id') === (string) $restaurant->id ? 'selected' : '' }}>
                                {{ $restaurant->name }}
                            </option>
                        @endforeach
                    </select>
                    <select name="status" class="form-control mr-2 mb-2">
                        <option value="">Tous</option>
                        <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Actives</option>
                        <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactives</option>
                    </select>
                    <button class="btn btn-primary mb-2">Filtrer</button>
                </form>
            </div>
            <div class="col-md-4 text-md-right">
                <a href="{{ route('admin.promotions.create') }}" class="btn btn-success">
                    <i class="fa fa-plus"></i> Nouvelle promotion
                </a>
            </div>
        </div>

        <div class="card">
            <div class="card-body table-responsive p-0">
                <table class="table table-hover text-nowrap">
                    <thead>
                    <tr>
                        <th>Code</th>
                        <th>Restaurant</th>
                        <th>Remise</th>
                        <th>Limites</th>
                        <th>Usage</th>
                        <th>Statut</th>
                        <th>Période</th>
                        <th class="text-right">Actions</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($vouchers as $voucher)
                        <tr>
                            <td>
                                <strong>{{ $voucher->name }}</strong>
                                <div class="text-muted small">{{ strtoupper($voucher->discount_type ?? 'percentage') }}</div>
                            </td>
                            <td>{{ optional($voucher->restaurant)->name ?? 'Global' }}</td>
                            <td>
                                @if(($voucher->discount_type ?? 'percentage') === 'fixed')
                                    {{ number_format((float) ($voucher->discount_value ?? $voucher->discount ?? 0), 0, ',', ' ') }} FCFA
                                @else
                                    {{ number_format((float) ($voucher->discount_value ?? $voucher->discount ?? 0), 0, ',', ' ') }} %
                                @endif
                                @if(!empty($voucher->max_discount_amount))
                                    <div class="text-muted small">Plafond {{ number_format((float) $voucher->max_discount_amount, 0, ',', ' ') }} FCFA</div>
                                @endif
                            </td>
                            <td>
                                <div>Min: {{ number_format((float) ($voucher->min_order_amount ?? 0), 0, ',', ' ') }} FCFA</div>
                                <div>Par user: {{ (int) ($voucher->per_user_limit ?? 1) }}</div>
                            </td>
                            <td>
                                {{ (int) ($voucher->used_count ?? 0) }}
                                @if(!is_null($voucher->usage_limit))
                                    / {{ (int) $voucher->usage_limit }}
                                @endif
                                <div class="text-muted small">{{ (int) ($voucher->redemptions_count ?? 0) }} validations</div>
                            </td>
                            <td>
                                <span class="badge {{ !empty($voucher->is_active) ? 'badge-success' : 'badge-secondary' }}">
                                    {{ !empty($voucher->is_active) ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td>
                                <div>{{ optional($voucher->starts_at ?? $voucher->start_date)->format('d/m/Y H:i') ?? '—' }}</div>
                                <div>{{ optional($voucher->ends_at ?? $voucher->end_date)->format('d/m/Y H:i') ?? '—' }}</div>
                            </td>
                            <td class="text-right">
                                <a href="{{ route('admin.promotions.edit', $voucher) }}" class="btn btn-sm btn-info">Editer</a>
                                <form action="{{ route('admin.promotions.destroy', $voucher) }}" method="POST" class="d-inline" onsubmit="return confirm('Supprimer cette promotion ?');">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-danger">Supprimer</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">Aucune promotion trouvée.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-3">
            {{ $vouchers->links() }}
        </div>
    </div>
</section>
@endsection
