@extends('layouts.admin-modern')
@section('title', 'Gestion des Véhicules')
@section('page_title', 'Véhicules transport')
@section('nav_active', 'transport')

@section('style')
<style>
.veh-page { padding:24px; }
.veh-card { background:#fff; border:1px solid #e5e7eb; border-radius:10px; overflow:hidden; }
.veh-card__header { display:flex; align-items:center; justify-content:space-between; padding:14px 20px; border-bottom:1px solid #f3f4f6; }
.veh-card__title { font-size:14px; font-weight:700; color:#111827; margin:0; }
.veh-table-wrap { overflow-x:auto; }
.veh-table { width:100%; border-collapse:collapse; font-size:13px; }
.veh-table thead th { padding:9px 14px; font-size:10px; font-weight:700; letter-spacing:.06em; text-transform:uppercase; color:#9ca3af; border-bottom:1px solid #f3f4f6; background:#f9fafb; text-align:left; }
.veh-table tbody td { padding:10px 14px; color:#374151; vertical-align:middle; border-bottom:1px solid #f3f4f6; }
.veh-table tbody tr:last-child td { border-bottom:none; }
.veh-pill { display:inline-flex; align-items:center; padding:3px 10px; border-radius:20px; font-size:11px; font-weight:600; }
.veh-pill--success { background:#d1fae5; color:#065f46; }
.veh-pill--danger  { background:#fee2e2; color:#991b1b; }
.veh-pill--warning { background:#fef3c7; color:#92400e; }
.veh-pill--info    { background:#dbeafe; color:#1e40af; }
.veh-pill--neutral { background:#f3f4f6; color:#374151; }
.veh-btn { display:inline-flex; align-items:center; justify-content:center; width:30px; height:30px; border:none; border-radius:6px; cursor:pointer; font-size:13px; }
.veh-btn--primary { background:#1e3a5f; color:#fff; }
.veh-btn--success { background:#16a34a; color:#fff; }
.veh-btn--danger  { background:#dc2626; color:#fff; }
.veh-btn--primary:hover { opacity:.85; }
.veh-btn--success:hover { opacity:.85; }
.veh-btn--danger:hover  { opacity:.85; }
.veh-btn-add { display:inline-flex; align-items:center; gap:6px; padding:8px 16px; background:#1e3a5f; color:#fff; border:none; border-radius:6px; font-size:13px; font-weight:600; cursor:pointer; }
.veh-btn-add:hover { opacity:.85; }
.veh-dialog { border:none; border-radius:12px; padding:0; max-width:460px; width:100%; box-shadow:0 20px 60px rgba(0,0,0,.18); }
.veh-dialog::backdrop { background:rgba(0,0,0,.45); }
.veh-dialog__header { display:flex; align-items:center; justify-content:space-between; padding:14px 20px; border-bottom:1px solid #f3f4f6; background:#dc2626; }
.veh-dialog__title { font-size:14px; font-weight:700; color:#fff; margin:0; }
.veh-dialog__close { background:none; border:none; font-size:18px; color:#fff; cursor:pointer; }
.veh-dialog__body { padding:20px; }
.veh-dialog__footer { display:flex; justify-content:space-between; gap:10px; padding:14px 20px; border-top:1px solid #f3f4f6; }
.veh-field { margin-bottom:14px; }
.veh-label { font-size:13px; font-weight:600; color:#374151; display:block; margin-bottom:5px; }
.veh-input { width:100%; padding:9px 12px; border:1px solid #d1d5db; border-radius:6px; font-size:14px; box-sizing:border-box; resize:vertical; }
.veh-btn-secondary { display:inline-flex; align-items:center; padding:8px 16px; border:1px solid #d1d5db; border-radius:6px; font-size:13px; font-weight:600; color:#374151; background:#fff; cursor:pointer; }
.veh-btn-confirm-danger { display:inline-flex; align-items:center; gap:6px; padding:8px 16px; background:#dc2626; color:#fff; border:none; border-radius:6px; font-size:13px; font-weight:600; cursor:pointer; }
</style>
@endsection

@section('content')
<div class="veh-page">
    <div class="veh-card">
        <div class="veh-card__header">
            <h3 class="veh-card__title">Liste des véhicules</h3>
            <button type="button" class="veh-btn-add">
                <i class="fas fa-plus"></i> Ajouter un véhicule
            </button>
        </div>
        <div class="veh-table-wrap">
            <table class="veh-table">
                <thead>
                    <tr>
                        <th>Véhicule</th>
                        <th>Immatriculation</th>
                        <th>Propriétaire</th>
                        <th>Type</th>
                        <th>Places</th>
                        <th>Disponibilité</th>
                        <th>Statut</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($vehicles as $vehicle)
                    <tr>
                        <td>
                            <b>{{ $vehicle->make }} {{ $vehicle->model }}</b>
                            <div style="font-size:12px;color:#9ca3af;margin-top:2px;">{{ $vehicle->color }} ({{ $vehicle->year }})</div>
                        </td>
                        <td>{{ $vehicle->plate_number }}</td>
                        <td>{{ $vehicle->owner->name ?? 'Admin' }}</td>
                        <td><span class="veh-pill veh-pill--info">{{ $vehicle->type }}</span></td>
                        <td>{{ $vehicle->seats }}</td>
                        <td>
                            <span class="veh-pill {{ $vehicle->is_available ? 'veh-pill--success' : 'veh-pill--danger' }}">
                                {{ $vehicle->is_available ? 'Oui' : 'Non' }}
                            </span>
                        </td>
                        <td>
                            @if($vehicle->status === 'active')
                                <span class="veh-pill veh-pill--success">Actif / Approuvé</span>
                            @elseif($vehicle->status === 'pending')
                                <span class="veh-pill veh-pill--warning">En attente</span>
                            @elseif($vehicle->status === 'rejected')
                                <span class="veh-pill veh-pill--danger">Rejeté</span>
                            @else
                                <span class="veh-pill veh-pill--neutral">{{ $vehicle->status }}</span>
                            @endif
                        </td>
                        <td>
                            <div style="display:flex;gap:6px;align-items:center;">
                                <button type="button" class="veh-btn veh-btn--primary" title="Modifier"><i class="fas fa-edit"></i></button>
                                @if($vehicle->status === 'pending')
                                    <form action="{{ route('admin.transport.vehicles.approve', $vehicle->id) }}" method="POST" style="display:inline;">
                                        @csrf
                                        <button type="submit" class="veh-btn veh-btn--success" title="Approuver"><i class="fas fa-check"></i></button>
                                    </form>
                                    <button type="button" class="veh-btn veh-btn--danger btn-reject" data-id="{{ $vehicle->id }}" title="Rejeter"><i class="fas fa-times"></i></button>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            <div style="padding:14px 20px;border-top:1px solid #f3f4f6;">
                {{ $vehicles->links() }}
            </div>
        </div>
    </div>
</div>

<dialog class="veh-dialog" id="dlg-reject">
    <div class="veh-dialog__header">
        <h4 class="veh-dialog__title">Rejeter le véhicule</h4>
        <button class="veh-dialog__close" onclick="this.closest('dialog').close()">&times;</button>
    </div>
    <form id="form-reject" method="POST">
        @csrf
        <div class="veh-dialog__body">
            <div class="veh-field">
                <label class="veh-label">Raison du rejet</label>
                <textarea name="reason" class="veh-input" rows="3" required placeholder="Ex: Documents illisibles, véhicule non conforme..."></textarea>
            </div>
        </div>
        <div class="veh-dialog__footer">
            <button type="button" class="veh-btn-secondary" onclick="this.closest('dialog').close()">Fermer</button>
            <button type="submit" class="veh-btn-confirm-danger">Confirmer le rejet</button>
        </div>
    </form>
</dialog>
@endsection

@section('scripts')
<script>
    document.querySelectorAll('.btn-reject').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var id = this.dataset.id;
            var url = "{{ route('admin.transport.vehicles.reject', ':id') }}";
            document.getElementById('form-reject').action = url.replace(':id', id);
            document.getElementById('dlg-reject').showModal();
        });
    });
</script>
@endsection
