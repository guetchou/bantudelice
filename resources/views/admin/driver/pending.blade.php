@extends('layouts.admin-modern')
@section('title', 'Livreurs en attente | BantuDelice')
@section('page_title', 'Livreurs en attente')
@section('nav_active', 'drivers')

@section('style')
<style>
/* ── drv-pending ────────────────────────────────────────────── */
.drv-page { padding: 24px; }

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

.drv-btn-back {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    color: var(--adm-accent, #1e3a5f);
    font-size: 13px;
    font-weight: 500;
    text-decoration: none;
    padding: 7px 14px;
    border: 1px solid var(--adm-accent, #1e3a5f);
    border-radius: 6px;
    transition: background .15s, color .15s;
}
.drv-btn-back:hover { background: var(--adm-accent, #1e3a5f); color: #fff; text-decoration: none; }

/* Panel */
.drv-panel {
    background: #fff;
    border-radius: 8px;
    border: 1px solid #e5e7eb;
    overflow: hidden;
}

.drv-table-wrap { overflow-x: auto; }

.drv-pending-table { width: 100%; border-collapse: collapse; font-size: 13px; }
.drv-pending-table thead th {
    background: #f9fafb;
    color: #374151;
    font-weight: 600;
    padding: 10px 14px;
    text-align: left;
    border-bottom: 2px solid #e5e7eb;
    white-space: nowrap;
}
.drv-pending-table tbody td {
    padding: 10px 14px;
    border-bottom: 1px solid #f3f4f6;
    vertical-align: middle;
    color: #111827;
}
.drv-pending-table tbody tr:last-child td { border-bottom: none; }
.drv-pending-table tbody tr:hover td { background: #fafafa; }

/* Badge */
.drv-badge {
    display: inline-block;
    padding: 3px 10px;
    border-radius: 999px;
    font-size: 11px;
    font-weight: 600;
}
.drv-badge--pending { background: #fef3c7; color: #92400e; }

/* Actions */
.drv-actions { display: flex; align-items: center; gap: 6px; }
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
    transition: background .15s, color .15s;
}
.drv-action-btn--edit { border-color: #6366f1; color: #6366f1; }
.drv-action-btn--edit:hover { background: #6366f1; color: #fff; text-decoration: none; }
.drv-action-btn--del  { border-color: #ef4444; color: #ef4444; }
.drv-action-btn--del:hover  { background: #ef4444; color: #fff; }

/* Empty state */
.drv-empty {
    text-align: center;
    padding: 40px 20px;
    color: var(--adm-text-muted, #6b7280);
    font-size: 14px;
}
.drv-empty i { font-size: 32px; display: block; margin-bottom: 10px; opacity: .5; }
</style>
@endsection

@section('content')
<div class="drv-page">

    {{-- Header --}}
    <div class="drv-header">
        <h1 class="drv-header__title">
            <i class="fas fa-hourglass-half"></i>
            Livreurs en attente
        </h1>
        <a href="{{ route('driver.index') }}" class="drv-btn-back">
            <i class="fas fa-arrow-left"></i> Retour
        </a>
    </div>

    {{-- Panel --}}
    <div class="drv-panel">
        <div class="drv-table-wrap">
            <table class="drv-pending-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Nom</th>
                        <th>Email</th>
                        <th>Adresse</th>
                        <th>Mobile</th>
                        <th>Statut</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($drivers ?? [] as $index => $driver)
                    <tr>
                        <td>{{ ++$index }}</td>
                        <td>{{ $driver->name }}</td>
                        <td>{{ $driver->email }}</td>
                        <td>{{ $driver->address ?? '—' }}</td>
                        <td>{{ $driver->phone ?? '—' }}</td>
                        <td>
                            <span class="drv-badge drv-badge--pending">
                                <i class="far fa-clock"></i> En attente
                            </span>
                        </td>
                        <td>
                            <div class="drv-actions">
                                <a href="{{ route('driver.edit', $driver->id) }}" class="drv-action-btn drv-action-btn--edit" title="Modifier">
                                    <i class="fas fa-edit"></i>
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
                    @empty
                    <tr>
                        <td colspan="7">
                            <div class="drv-empty">
                                <i class="fas fa-check-circle"></i>
                                Aucun livreur en attente.
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection
