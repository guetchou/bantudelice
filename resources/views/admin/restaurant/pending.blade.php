@extends('layouts.admin-modern')
@section('title','Restaurants en attente')
@section('page_title', 'Restaurants en attente')
@section('nav_active', 'restaurants')
@section('style')
<style>
.rst-page { padding:24px; }
.rst-alert { padding:12px 16px; border-radius:8px; font-size:13px; font-weight:500; border:1px solid transparent; margin-bottom:16px; }
.rst-alert--success { background:#f0fdf4; color:#166534; border-color:#bbf7d0; }
.rst-alert--danger  { background:#fef2f2; color:#991b1b; border-color:#fecaca; }
.rst-alert--warning { background:#fefce8; color:#854d0e; border-color:#fde68a; }

/* Header */
.rst-header { display:flex; align-items:center; justify-content:space-between; margin-bottom:20px; flex-wrap:wrap; gap:12px; }
.rst-header__title { font-size:20px; font-weight:700; color:#111827; margin:0; display:flex; align-items:center; gap:8px; }
.rst-header__title i { color:#1e3a5f; }

/* Card */
.rst-card { background:#fff; border:1px solid #e5e7eb; border-radius:10px; overflow:hidden; margin-bottom:20px; }
.rst-card__header { padding:14px 20px; border-bottom:1px solid #f3f4f6; display:flex; align-items:center; justify-content:space-between; gap:8px; flex-wrap:wrap; }
.rst-card__title { font-size:14px; font-weight:700; color:#111827; margin:0; }
.rst-card__body { padding:20px; }

/* Table */
.rst-table-wrap { overflow-x:auto; }
.rst-table { width:100%; border-collapse:collapse; font-size:13px; }
.rst-table thead th { padding:9px 14px; font-size:10px; font-weight:700; letter-spacing:.06em; text-transform:uppercase; color:#9ca3af; border-bottom:1px solid #f3f4f6; background:#f9fafb; text-align:left; white-space:nowrap; }
.rst-table tbody tr { border-bottom:1px solid #f3f4f6; transition:background .1s; }
.rst-table tbody tr:hover { background:#f9fafb; }
.rst-table td { padding:11px 14px; color:#374151; vertical-align:middle; }

/* Badge */
.rst-badge { display:inline-flex; align-items:center; padding:2px 8px; border-radius:999px; font-size:11px; font-weight:600; white-space:nowrap; }
.rst-badge--ok     { background:#f0fdf4; color:#166534; }
.rst-badge--danger { background:#fef2f2; color:#991b1b; }

/* Buttons */
.rst-btn-primary { display:inline-flex; align-items:center; gap:6px; padding:8px 18px; background:#1e3a5f; color:#fff; border:none; border-radius:6px; font-size:13px; font-weight:600; cursor:pointer; text-decoration:none; transition:opacity .15s; }
.rst-btn-primary:hover { opacity:.85; color:#fff; }
.rst-btn-info { display:inline-flex; align-items:center; gap:4px; padding:5px 12px; background:#eff6ff; color:#1d4ed8; border:1px solid #bfdbfe; border-radius:6px; font-size:12px; font-weight:600; text-decoration:none; transition:background .15s; }
.rst-btn-info:hover { background:#dbeafe; color:#1d4ed8; text-decoration:none; }
.rst-btn-danger { display:inline-flex; align-items:center; gap:4px; padding:5px 12px; background:#fef2f2; color:#dc2626; border:1px solid #fecaca; border-radius:6px; font-size:12px; font-weight:600; cursor:pointer; transition:background .15s; }
.rst-btn-danger:hover { background:#fee2e2; }
.rst-action-group { display:flex; align-items:center; gap:6px; flex-wrap:wrap; }
</style>
@endsection

@section('content')
<div class="rst-page">
    @if(session()->has('alert'))
        <div class="rst-alert rst-alert--{{ session()->get('alert.type') === 'success' ? 'success' : (session()->get('alert.type') === 'danger' ? 'danger' : 'warning') }}">
            {{ session()->get('alert.message') }}
        </div>
    @endif

    <div class="rst-header">
        <h1 class="rst-header__title">
            <i class="fas fa-hourglass-half"></i>
            Demandes de restaurants en attente
        </h1>
    </div>

    <div class="rst-card">
        <div class="rst-card__header">
            <span class="rst-card__title">Liste des demandes</span>
        </div>
        <div class="rst-card__body">
            <div class="rst-table-wrap">
                <table class="rst-table" id="example1">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nom</th>
                            <th>Email</th>
                            <th>Adresse</th>
                            <th>Téléphone</th>
                            <th>En vedette</th>
                            <th>Statut</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach($restaurants as $index => $restaurant)
                        <tr>
                            <td>{{ ++$index }}</td>
                            <td>{{$restaurant->name}}</td>
                            <td>{{$restaurant->email}}</td>
                            <td>{{$restaurant->address}}</td>
                            <td>{{$restaurant->phone}}</td>
                            <td>
                                <form method="POST" action="{{ route('admin.change_restaurant_featured_status', $restaurant->id) }}" style="display:inline;">
                                    @csrf
                                    <button type="submit" style="background:none;border:0;padding:0;cursor:pointer;">
                                        <span class="rst-badge {{ $restaurant->featured ? 'rst-badge--ok' : 'rst-badge--danger' }}">
                                            {{ $restaurant->featured ? 'En vedette' : 'Non En vedette' }}
                                        </span>
                                    </button>
                                </form>
                            </td>
                            <td>
                                <form method="POST" action="{{ route('admin.change_restaurant_active_status', $restaurant->id) }}" style="display:inline;">
                                    @csrf
                                    <button type="submit" style="background:none;border:0;padding:0;cursor:pointer;">
                                        <span class="rst-badge {{ $restaurant->approved ? 'rst-badge--ok' : 'rst-badge--danger' }}">
                                            {{ $restaurant->approved ? 'Actif' : 'Inactif' }}
                                        </span>
                                    </button>
                                </form>
                            </td>
                            <td>
                                <div class="rst-action-group">
                                    <a href="{{ route('restaurant.show', $restaurant->id) }}" class="rst-btn-info">
                                        <i class="fas fa-eye"></i> Voir
                                    </a>
                                    <a href="{{ route('restaurant.edit', $restaurant->id) }}" class="rst-btn-info">
                                        <i class="fas fa-edit"></i> Modifier
                                    </a>
                                    <form action="{{ route('restaurant.destroy', $restaurant->id) }}"
                                          method="post"
                                          style="display:inline;"
                                          onsubmit="return confirm('Voulez-vous vraiment supprimer this country?');">
                                        @csrf
                                        @method('delete')
                                        <button type="submit" class="rst-btn-danger">
                                            <i class="fas fa-trash"></i> Delete
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
</div>
@endsection

@section('script')
<script src="{{asset('plugins/datatables/jquery.dataTables.js')}}"></script>
<script>
    $(function () {
        $("#example1").DataTable();
    });
</script>
@endsection
