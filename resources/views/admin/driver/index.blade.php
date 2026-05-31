@extends('layouts.admin-modern')
@section('title', 'Tous les livreurs | Food ops')
@section('page_title', 'Livreurs')
@section('nav_active', 'drivers')

@section('style')
<style>
/* ── drv-index ──────────────────────────────────────────────── */
.drv-page { padding: 24px; }

.drv-alerts { margin-bottom: 20px; }

.drv-alert {
    padding: 12px 16px;
    border-radius: 6px;
    font-size: 14px;
    margin-bottom: 12px;
}
.drv-alert--success { background: #dcfce7; color: #166534; border-left: 4px solid var(--adm-green, #22c55e); }
.drv-alert--danger  { background: #fee2e2; color: #991b1b; border-left: 4px solid #ef4444; }
.drv-alert--warning { background: #fef9c3; color: #854d0e; border-left: 4px solid #eab308; }
.drv-alert--info    { background: #dbeafe; color: #1e40af; border-left: 4px solid #3b82f6; }

/* Provisioned accounts card */
.drv-provisioned-card {
    background: #fefce8;
    border: 1px solid #fde68a;
    border-radius: 8px;
    padding: 16px 20px;
    margin-bottom: 20px;
}
.drv-provisioned-card__title {
    font-size: 13px;
    font-weight: 600;
    color: #78350f;
    margin: 0 0 12px 0;
    display: flex;
    align-items: center;
    gap: 6px;
}
.drv-prov-table { width: 100%; border-collapse: collapse; font-size: 13px; }
.drv-prov-table th {
    background: #fde68a;
    color: #78350f;
    font-weight: 600;
    padding: 6px 10px;
    text-align: left;
    white-space: nowrap;
}
.drv-prov-table td {
    padding: 6px 10px;
    border-bottom: 1px solid #fde68a;
    color: #451a03;
    white-space: nowrap;
}
.drv-prov-table tr:last-child td { border-bottom: none; }
.drv-prov-table code {
    background: #fff8c5;
    padding: 2px 6px;
    border-radius: 4px;
    font-family: monospace;
    font-size: 12px;
}

/* Page header */
.drv-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 20px;
    flex-wrap: wrap;
    gap: 12px;
}
.drv-header__title {
    font-size: 20px;
    font-weight: 700;
    color: #111827;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 8px;
}
.drv-header__title i { color: var(--adm-accent, #1e3a5f); }

.drv-btn-primary {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: var(--adm-accent, #1e3a5f);
    color: #fff;
    padding: 8px 16px;
    border-radius: 6px;
    font-size: 13px;
    font-weight: 600;
    text-decoration: none;
    transition: opacity .15s;
    border: none;
    cursor: pointer;
}
.drv-btn-primary:hover { opacity: .85; color: #fff; text-decoration: none; }

/* Main panel */
.drv-panel {
    background: #fff;
    border-radius: 8px;
    border: 1px solid #e5e7eb;
    overflow: hidden;
}

/* DataTable wrapper */
.drv-table-wrap { overflow-x: auto; padding: 4px; }

/* Table */
#drv-table { width: 100%; border-collapse: collapse; font-size: 13px; }
#drv-table thead th {
    background: #f9fafb;
    color: #374151;
    font-weight: 600;
    padding: 10px 12px;
    text-align: left;
    border-bottom: 2px solid #e5e7eb;
    white-space: nowrap;
}
#drv-table tbody td {
    padding: 9px 12px;
    border-bottom: 1px solid #f3f4f6;
    vertical-align: middle;
    color: #111827;
}
#drv-table tbody tr:last-child td { border-bottom: none; }
#drv-table tbody tr:hover td { background: #f9fafb; }

/* Driver avatar */
.drv-avatar {
    width: 44px;
    height: 44px;
    border-radius: 50%;
    object-fit: cover;
    display: block;
    border: 2px solid #e5e7eb;
}

/* Status badge toggle */
.drv-status-form { display: inline; }
.drv-status-btn {
    background: none;
    border: none;
    padding: 0;
    cursor: pointer;
}
.drv-badge {
    display: inline-block;
    padding: 3px 10px;
    border-radius: 999px;
    font-size: 11px;
    font-weight: 600;
    letter-spacing: .3px;
}
.drv-badge--active   { background: #dcfce7; color: #166534; }
.drv-badge--inactive { background: #fee2e2; color: #991b1b; }
.drv-badge--pending  { background: #fef9c3; color: #854d0e; }

/* Action buttons */
.drv-actions { display: flex; align-items: center; gap: 6px; flex-wrap: wrap; }
.drv-action-btn {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 5px 10px;
    border-radius: 5px;
    font-size: 12px;
    font-weight: 500;
    text-decoration: none;
    border: 1px solid transparent;
    cursor: pointer;
    background: none;
    white-space: nowrap;
    transition: background .15s, color .15s;
}
.drv-action-btn--dashboard { border-color: var(--adm-green, #22c55e); color: var(--adm-green, #22c55e); }
.drv-action-btn--dashboard:hover { background: var(--adm-green, #22c55e); color: #fff; text-decoration: none; }
.drv-action-btn--pay  { border-color: #3b82f6; color: #3b82f6; }
.drv-action-btn--pay:hover  { background: #3b82f6; color: #fff; text-decoration: none; }
.drv-action-btn--edit { border-color: #6366f1; color: #6366f1; }
.drv-action-btn--edit:hover { background: #6366f1; color: #fff; text-decoration: none; }
.drv-action-btn--del  { border-color: #ef4444; color: #ef4444; }
.drv-action-btn--del:hover  { background: #ef4444; color: #fff; }
</style>
@endsection

@section('content')
<div class="drv-page">

    {{-- Alertes session --}}
    @if(session()->has('alert') || session()->has('provisioned_accounts'))
    <div class="drv-alerts">

        @if(session()->has('alert'))
        <div class="drv-alert drv-alert--{{ session()->get('alert.type') }}">
            {{ session()->get('alert.message') }}
        </div>
        @endif

        @if(session()->has('provisioned_accounts'))
        <div class="drv-provisioned-card">
            <p class="drv-provisioned-card__title">
                <i class="fas fa-key"></i>
                Identifiants temporaires générés &mdash; à changer après première connexion via l'API livreur.
            </p>
            <div style="overflow-x:auto;">
                <table class="drv-prov-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Restaurant</th>
                            <th>Login</th>
                            <th>Email</th>
                            <th>Téléphone</th>
                            <th>Mot de passe temporaire</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach(session('provisioned_accounts', []) as $account)
                        <tr>
                            <td>{{ $account['id'] }}</td>
                            <td>{{ $account['restaurant'] }}</td>
                            <td>{{ $account['user_name'] }}</td>
                            <td>{{ $account['email'] }}</td>
                            <td>{{ $account['phone'] }}</td>
                            <td><code>{{ $account['password'] }}</code></td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif

    </div>
    @endif

    {{-- Header --}}
    <div class="drv-header">
        <h1 class="drv-header__title">
            <i class="fas fa-motorcycle"></i>
            Livreurs
        </h1>
        <a href="{{ route('driver.create') }}" class="drv-btn-primary">
            <i class="fas fa-plus"></i> Ajouter un livreur
        </a>
    </div>

    {{-- Panel --}}
    <div class="drv-panel">
        <div class="drv-table-wrap">
            <table id="drv-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Image</th>
                        <th>Nom</th>
                        <th>Email</th>
                        <th>Adresse</th>
                        <th>Mobile</th>
                        <th>Statut</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($drivers as $index => $driver)
                    <tr>
                        <td>{{ ++$index }}</td>
                        <td>
                            <img
                                src="{{ !empty($driver->image) ? asset('images/driver_images/'.$driver->image) : asset('images/placeholder.png') }}"
                                class="drv-avatar"
                                onerror="this.src='{{ asset('images/placeholder.png') }}'"
                                alt="{{ $driver->name }}">
                        </td>
                        <td>{{ $driver->name }}</td>
                        <td>{{ $driver->email }}</td>
                        <td>{{ $driver->address }}</td>
                        <td>{{ $driver->phone }}</td>
                        <td>
                            <form method="POST" action="{{ route('admin.change_driver_active_status', $driver->id) }}" class="drv-status-form">
                                @csrf
                                <button type="submit" class="drv-status-btn" title="Basculer le statut">
                                    <span class="drv-badge {{ $driver->approved ? 'drv-badge--active' : 'drv-badge--inactive' }}">
                                        {{ $driver->approved ? 'Actif' : 'Inactif' }}
                                    </span>
                                </button>
                            </form>
                        </td>
                        <td>
                            <div class="drv-actions">
                                <form action="{{ route('admin.impersonate.driver', $driver->id) }}" method="POST" style="display:inline;">
                                    @csrf
                                    <button type="submit" class="drv-action-btn drv-action-btn--dashboard" title="Ouvrir le dashboard livreur">
                                        <i class="fas fa-sign-in-alt"></i> Dashboard
                                    </button>
                                </form>
                                <a href="{{ route('admin.get_hourly_pay', $driver->id) }}" class="drv-action-btn drv-action-btn--pay" title="Définir le salaire horaire">
                                    <i class="fas fa-money-bill-wave"></i> Salaire
                                </a>
                                <a href="{{ route('driver.edit', $driver->id) }}" class="drv-action-btn drv-action-btn--edit" title="Modifier">
                                    <i class="fas fa-edit"></i> Modifier
                                </a>
                                <form action="{{ route('driver.destroy', $driver->id) }}" method="post" style="display:inline;" onsubmit="return confirm('Voulez-vous vraiment supprimer ce livreur ?');">
                                    @csrf
                                    @method('delete')
                                    <button type="submit" class="drv-action-btn drv-action-btn--del" title="Supprimer">
                                        <i class="fas fa-trash"></i>
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
    $('#drv-table').DataTable({
        pageLength: 25,
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/fr-FR.json'
        }
    });
});
</script>
@endsection
