@extends('layouts.admin-modern')
@section('title', 'Utilisateurs | Admin')
@section('page_title', 'Utilisateurs')
@section('nav_active', 'users')

@section('style')
<style>
.usr { display: flex; flex-direction: column; gap: 20px; }

.usr-header {
    display: flex; align-items: center; justify-content: space-between;
    gap: 12px; flex-wrap: wrap;
}
.usr-header__title { font-size: 18px; font-weight: 700; color: #111827; margin: 0; }

.usr-alert {
    padding: 12px 16px; border-radius: 8px; font-size: 13px; font-weight: 500;
    border: 1px solid transparent;
}
.usr-alert--success { background: #f0fdf4; color: #166534; border-color: #bbf7d0; }
.usr-alert--danger  { background: #fef2f2; color: #991b1b; border-color: #fecaca; }

.usr-card {
    background: #fff; border: 1px solid #e5e7eb;
    border-radius: 12px; overflow: hidden;
}
.usr-card__head {
    display: flex; align-items: center; justify-content: space-between;
    padding: 14px 20px; border-bottom: 1px solid #f3f4f6;
}
.usr-card__head-title { font-size: 13px; font-weight: 600; color: #111827; }
.usr-card__head-count { font-size: 11px; color: #6b7280; margin-top: 2px; }

.usr-table-wrap { overflow-x: auto; }
.usr-table {
    width: 100%; border-collapse: collapse;
    font-size: 13px; font-family: 'Manrope', sans-serif;
}
.usr-table thead th {
    padding: 9px 16px; font-size: 10px; font-weight: 700;
    letter-spacing: .06em; text-transform: uppercase;
    color: #9ca3af; border-bottom: 1px solid #f3f4f6;
    background: #f9fafb; text-align: left; white-space: nowrap;
}
.usr-table tbody tr { border-bottom: 1px solid #f3f4f6; transition: background .1s; }
.usr-table tbody tr:last-child { border-bottom: none; }
.usr-table tbody tr:hover { background: #f9fafb; }
.usr-table td { padding: 11px 16px; color: #374151; vertical-align: middle; }

.usr-avatar {
    width: 36px; height: 36px; border-radius: 50%;
    object-fit: cover; border: 2px solid #e5e7eb;
}
.usr-name { font-weight: 600; color: #111827; }
.usr-email { font-size: 12px; color: #6b7280; }

.usr-badge {
    display: inline-flex; align-items: center;
    padding: 2px 8px; border-radius: 6px;
    font-size: 11px; font-weight: 600;
}
.usr-badge--active  { background: #f0fdf4; color: #166534; }
.usr-badge--blocked { background: #fef2f2; color: #991b1b; }

.usr-actions { display: flex; align-items: center; gap: 6px; justify-content: flex-end; }
.usr-action-btn {
    display: inline-flex; align-items: center; justify-content: center;
    width: 30px; height: 30px; border-radius: 7px;
    border: 1px solid #e5e7eb; background: #fff;
    color: #6b7280; cursor: pointer; font-size: 12px;
    transition: .12s; text-decoration: none;
}
.usr-action-btn--delete { color: #dc2626; border-color: rgba(239,68,68,.2); }
.usr-action-btn--delete:hover { background: rgba(239,68,68,.06); border-color: #dc2626; }
</style>
@endsection

@section('content')
<div class="usr">

    @if(session()->has('alert'))
        <div class="usr-alert usr-alert--{{ session('alert.type') === 'success' ? 'success' : 'danger' }}">
            {{ session('alert.message') }}
        </div>
    @endif

    <div class="usr-header">
        <h1 class="usr-header__title">Utilisateurs</h1>
    </div>

    <div class="usr-card">
        <div class="usr-card__head">
            <div>
                <div class="usr-card__head-title">Tous les comptes</div>
                <div class="usr-card__head-count">{{ $users->count() }} utilisateur(s)</div>
            </div>
        </div>
        <div class="usr-table-wrap">
            <table class="usr-table" id="usr-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Utilisateur</th>
                        <th>Téléphone</th>
                        <th>Statut</th>
                        <th style="text-align:right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($users as $index => $user)
                    <tr>
                        <td style="color:#9ca3af;">{{ $index + 1 }}</td>
                        <td>
                            <div style="display:flex;align-items:center;gap:10px;">
                                <img src="{{ $user->avatarUrl() }}" class="usr-avatar" alt="{{ $user->name }}">
                                <div>
                                    <div class="usr-name">{{ $user->name }}</div>
                                    <div class="usr-email">{{ $user->email }}</div>
                                </div>
                            </div>
                        </td>
                        <td>{{ $user->phone ?: '—' }}</td>
                        <td>
                            <form method="POST" action="{{ route('admin.change_block_status', $user->id) }}" style="display:inline;">
                                @csrf
                                <button type="submit" style="background:none;border:0;padding:0;cursor:pointer;">
                                    <span class="usr-badge usr-badge--{{ $user->blocked ? 'blocked' : 'active' }}">
                                        {{ $user->blocked ? 'Bloqué' : 'Actif' }}
                                    </span>
                                </button>
                            </form>
                        </td>
                        <td>
                            <div class="usr-actions">
                                <form action="{{ route('user.destroy', $user->id) }}"
                                      method="post" style="display:inline;"
                                      onsubmit="return confirm('Supprimer cet utilisateur ?');">
                                    @csrf
                                    @method('delete')
                                    <button type="submit" class="usr-action-btn usr-action-btn--delete" title="Supprimer">
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
    $('#usr-table').DataTable({ pageLength: 25, order: [] });
});
</script>
@endsection
