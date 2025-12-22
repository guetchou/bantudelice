@extends('layouts.app')
@section('title', 'Réconciliation Financière | BantuDelice')
@section('colis_nav', 'active')
@section('colis_nav_open', 'menu-open')
@section('colis_nav_finance', 'active')

@section('content')
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0 text-dark">Réconciliation Financière (COD)</h1>
            </div>
        </div>
    </div>
</div>

<section class="content">
    <div class="container-fluid">
        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <div class="row">
            <!-- Coursiers avec du Cash -->
            <div class="col-md-7">
                <div class="card card-warning">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-money-bill-wave mr-1"></i> Cash en attente de collecte</h3>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Coursier</th>
                                    <th>Nombre de Colis</th>
                                    <th>Montant à collecter</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($couriersWithCash as $courier)
                                <tr>
                                    <td><strong>{{ $courier->name }}</strong></td>
                                    <td>{{ $courier->pending_cod_count }} colis livrés</td>
                                    <td><span class="text-danger font-weight-bold">{{ number_format($courier->pending_cod_amount, 0, ',', ' ') }} FCFA</span></td>
                                    <td>
                                        <form action="{{ route('admin.colis.reconcile', $courier->id) }}" method="POST" onsubmit="return confirm('Confirmer la réception de {{ number_format($courier->pending_cod_amount, 0, ',', ' ') }} FCFA ?')">
                                            @csrf
                                            <input type="hidden" name="amount" value="{{ $courier->pending_cod_amount }}">
                                            @foreach($courier->pending_shipment_ids as $id)
                                                <input type="hidden" name="shipment_ids[]" value="{{ $id }}">
                                            @endforeach
                                            <button type="submit" class="btn btn-sm btn-success">
                                                <i class="fas fa-check-double"></i> Réconcilier
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="4" class="text-center">Aucun reversement de fonds en attente.</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Historique des réconciliations -->
            <div class="col-md-5">
                <div class="card card-info">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-history mr-1"></i> Derniers reversements</h3>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Coursier</th>
                                    <th>Montant</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($recentReconciliations as $log)
                                <tr>
                                    <td>{{ $log->created_at->format('d/m/Y H:i') }}</td>
                                    <td>{{ $log->courier->name }}</td>
                                    <td>{{ number_format($log->amount_reconciled, 0, ',', ' ') }} FCFA</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection

