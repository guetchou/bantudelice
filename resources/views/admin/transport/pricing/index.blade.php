@extends('layouts.app')
@section('title', 'Règles de Tarification Transport')
@section('transport_nav', 'active')
@section('transport_pricing_nav', 'active')

@section('content')
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0 text-dark">Tarification Transport</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('admin.transport.dashboard') }}">Transport</a></li>
                        <li class="breadcrumb-item active">Tarification</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
    <section class="content">
        <div class="container-fluid">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Paramètres de tarification</h3>
                    <div class="card-tools">
                        <button class="btn btn-primary btn-sm" data-toggle="modal" data-target="#modal-add-rule"><i class="fas fa-plus"></i> Ajouter une règle</button>
                    </div>
                </div>
                <div class="card-body table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>Service</th>
                                <th>Base Fare</th>
                                <th>Prix / KM</th>
                                <th>Prix / Minute</th>
                                <th>Min Fare</th>
                                <th>Surge</th>
                                <th>Statut</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($rules as $rule)
                                <tr>
                                    <td><b>{{ $rule->type->label() }}</b></td>
                                    <td>{{ number_format($rule->base_fare, 0) }} FCFA</td>
                                    <td>{{ number_format($rule->price_per_km, 0) }} FCFA</td>
                                    <td>{{ number_format($rule->price_per_minute, 0) }} FCFA</td>
                                    <td>{{ number_format($rule->minimum_fare, 0) }} FCFA</td>
                                    <td>{{ $rule->surge_multiplier }}x</td>
                                    <td>
                                        @if($rule->is_active)
                                            <span class="badge badge-success">Actif</span>
                                        @else
                                            <span class="badge badge-danger">Inactif</span>
                                        @endif
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-primary">Modifier</button>
                                    </td>
                                </tr>
                            @endforeach
                            @if($rules->isEmpty())
                                <tr>
                                    <td colspan="8" class="text-center">Aucune règle définie. Les prix par défaut du système seront utilisés.</td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>

    <!-- Modal Add Rule -->
    <div class="modal fade" id="modal-add-rule">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="{{ route('admin.transport.pricing.store') }}" method="POST">
                    @csrf
                    <div class="modal-header">
                        <h4 class="modal-title">Ajouter une règle</h4>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label>Type de service</label>
                            <select name="type" class="form-control" required>
                                <option value="taxi">Taxi</option>
                                <option value="carpool">Covoiturage</option>
                                <option value="rental">Location</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Frais de base (FCFA)</label>
                            <input type="number" name="base_fare" class="form-control" value="0" required>
                        </div>
                        <div class="form-group">
                            <label>Prix par KM (FCFA)</label>
                            <input type="number" name="price_per_km" class="form-control" value="0" required>
                        </div>
                        <div class="form-group">
                            <label>Prix par Minute (FCFA)</label>
                            <input type="number" name="price_per_minute" class="form-control" value="0" required>
                        </div>
                        <div class="form-group">
                            <label>Prix minimum (FCFA)</label>
                            <input type="number" name="minimum_fare" class="form-control" value="0" required>
                        </div>
                    </div>
                    <div class="modal-footer justify-content-between">
                        <button type="button" class="btn btn-default" data-dismiss="modal">Fermer</button>
                        <button type="submit" class="btn btn-primary">Enregistrer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

