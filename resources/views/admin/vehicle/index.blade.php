@extends('layouts.admin-modern')
@section('title', 'Véhicules | Admin')
@section('page_title', 'Véhicules')
@section('nav_active', 'vehicles')

@section('style')
<style>
.veh { display: flex; flex-direction: column; gap: 20px; }

.veh-header { display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap; }
.veh-header__title { font-size: 18px; font-weight: 700; color: #111827; margin: 0; }

.veh-alert { padding: 12px 16px; border-radius: 8px; font-size: 13px; font-weight: 500; border: 1px solid transparent; }
.veh-alert--success { background: #f0fdf4; color: #166534; border-color: #bbf7d0; }
.veh-alert--danger  { background: #fef2f2; color: #991b1b; border-color: #fecaca; }

.veh-btn { display: inline-flex; align-items: center; gap: 6px; padding: 8px 16px; border-radius: 7px; font-size: 13px; font-weight: 600; text-decoration: none; transition: .12s; cursor: pointer; }
.veh-btn--primary { background: #22c55e; color: #fff; border: none; }
.veh-btn--primary:hover { background: #16a34a; color: #fff; }
.veh-btn--outline { background: #fff; color: #374151; border: 1px solid #d1d5db; }
.veh-btn--outline:hover { border-color: #22c55e; color: #22c55e; }
.veh-btn--danger-outline { background: #fff; color: #dc2626; border: 1px solid rgba(239,68,68,.3); }
.veh-btn--danger-outline:hover { background: #fef2f2; border-color: #dc2626; color: #dc2626; }
.veh-btn--sm { padding: 5px 12px; font-size: 12px; }

.veh-card { background: #fff; border: 1px solid #e5e7eb; border-radius: 12px; overflow: hidden; }
.veh-card__head { display: flex; align-items: center; justify-content: space-between; padding: 14px 20px; border-bottom: 1px solid #f3f4f6; }
.veh-card__head-title { font-size: 13px; font-weight: 600; color: #111827; }
.veh-card__head-count { font-size: 11px; color: #6b7280; margin-top: 2px; }

.veh-table-wrap { overflow-x: auto; }
.veh-table { width: 100%; border-collapse: collapse; font-size: 13px; font-family: 'Manrope', sans-serif; }
.veh-table thead th {
    padding: 9px 16px; font-size: 10px; font-weight: 700; letter-spacing: .06em;
    text-transform: uppercase; color: #9ca3af; border-bottom: 1px solid #f3f4f6;
    background: #f9fafb; text-align: left; white-space: nowrap;
}
.veh-table tbody tr { border-bottom: 1px solid #f3f4f6; transition: background .1s; }
.veh-table tbody tr:last-child { border-bottom: none; }
.veh-table tbody tr:hover { background: #f9fafb; }
.veh-table td { padding: 11px 16px; color: #374151; vertical-align: middle; }

.veh-model { font-weight: 600; color: #111827; }
.veh-plate { font-family: monospace; font-size: 12px; background: #f3f4f6; padding: 2px 7px; border-radius: 4px; color: #374151; }

.veh-actions { display: flex; align-items: center; gap: 6px; justify-content: flex-end; }
</style>
@endsection

@section('content')
<div class="veh">

    @if(session()->has('alert'))
        <div class="veh-alert veh-alert--{{ session('alert.type') === 'success' ? 'success' : 'danger' }}">
            {{ session('alert.message') }}
        </div>
    @endif

    <div class="veh-header">
        <h1 class="veh-header__title">Véhicules</h1>
        <a href="{{ route('vehicle.create') }}" class="veh-btn veh-btn--primary">
            <i class="fas fa-plus"></i> Ajouter un véhicule
        </a>
    </div>

    <div class="veh-card">
        <div class="veh-card__head">
            <div>
                <div class="veh-card__head-title">Tous les véhicules</div>
                <div class="veh-card__head-count">{{ $vehicles->count() }} véhicule(s)</div>
            </div>
        </div>
        <div class="veh-table-wrap">
            <table class="veh-table" id="veh-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Modèle</th>
                        <th>Immatriculation</th>
                        <th>N° permis</th>
                        <th>Couleur</th>
                        <th>Livreur</th>
                        <th style="text-align:right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($vehicles as $index => $vehicle)
                    <tr>
                        <td style="color:#9ca3af;">{{ $index + 1 }}</td>
                        <td><span class="veh-model">{{ $vehicle->model }}</span></td>
                        <td><span class="veh-plate">{{ $vehicle->number }}</span></td>
                        <td>{{ $vehicle->license_number }}</td>
                        <td>{{ $vehicle->color }}</td>
                        <td>{{ $vehicle->driver->name }}</td>
                        <td>
                            <div class="veh-actions">
                                <a href="{{ route('vehicle.edit', $vehicle->id) }}" class="veh-btn veh-btn--outline veh-btn--sm">
                                    <i class="fas fa-pen"></i> Modifier
                                </a>
                                <form action="{{ route('vehicle.destroy', $vehicle->id) }}"
                                      method="post" style="display:inline;"
                                      onsubmit="return confirm('Supprimer ce véhicule ?');">
                                    @csrf
                                    @method('delete')
                                    <button type="submit" class="veh-btn veh-btn--danger-outline veh-btn--sm">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection

@section('script')
<script src="{{ asset('plugins/datatables/jquery.dataTables.js') }}"></script>
<script>
$(function () {
    $('#veh-table').DataTable({ pageLength: 25, order: [] });
});
</script>
@endsection
