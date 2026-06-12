@extends('layouts.admin-modern')

@section('style')
<style>
/* ── Scoped cuisine index ── */
.cuis-page { padding: 24px 28px; }

.cuis-alert {
    padding: 10px 16px;
    border-radius: 6px;
    margin-bottom: 18px;
    font-size: 14px;
    font-weight: 500;
}
.cuis-alert-success { background: #dcfce7; color: #166534; border-left: 4px solid #22c55e; }
.cuis-alert-danger  { background: #fee2e2; color: #991b1b; border-left: 4px solid #ef4444; }
.cuis-alert-warning { background: #fef9c3; color: #854d0e; border-left: 4px solid #eab308; }
.cuis-alert-info    { background: #dbeafe; color: #1e40af; border-left: 4px solid #3b82f6; }

.cuis-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 20px;
    flex-wrap: wrap;
    gap: 12px;
}
.cuis-header-title {
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 20px;
    font-weight: 700;
    color: #1e3a5f;
    margin: 0;
}
.cuis-header-title i { color: #22c55e; font-size: 18px; }

.cuis-btn-add {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 8px 18px;
    background: #22c55e;
    color: #fff;
    border: none;
    border-radius: 6px;
    font-size: 13px;
    font-weight: 600;
    text-decoration: none;
    cursor: pointer;
    transition: background .18s;
}
.cuis-btn-add:hover { background: #16a34a; color: #fff; text-decoration: none; }

.cuis-card {
    background: #fff;
    border-radius: 10px;
    box-shadow: 0 1px 4px rgba(0,0,0,.10);
    overflow: hidden;
}
.cuis-card-head {
    padding: 14px 20px;
    background: #1e3a5f;
    display: flex;
    align-items: center;
    gap: 8px;
    color: #fff;
    font-size: 14px;
    font-weight: 600;
}
.cuis-card-head i { color: #22c55e; }

.cuis-table-wrap { overflow-x: auto; padding: 12px 16px 20px; }

.cuis-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 13.5px;
    color: #374151;
}
.cuis-table thead tr {
    background: #f0f4f8;
    border-bottom: 2px solid #e5e7eb;
}
.cuis-table thead th {
    padding: 10px 14px;
    text-align: left;
    font-weight: 700;
    color: #1e3a5f;
    white-space: nowrap;
}
.cuis-table tbody tr { border-bottom: 1px solid #f0f4f8; }
.cuis-table tbody tr:last-child { border-bottom: none; }
.cuis-table tbody tr:hover { background: #f9fafb; }
.cuis-table td { padding: 10px 14px; vertical-align: middle; }

.cuis-thumb {
    width: 44px;
    height: 44px;
    border-radius: 50%;
    object-fit: cover;
    border: 2px solid #e5e7eb;
}

.cuis-btn-edit {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
    border-radius: 6px;
    border: 1.5px solid #3b82f6;
    color: #3b82f6;
    background: transparent;
    text-decoration: none;
    transition: background .15s, color .15s;
    font-size: 13px;
}
.cuis-btn-edit:hover { background: #3b82f6; color: #fff; text-decoration: none; }

.cuis-btn-del {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
    border-radius: 6px;
    border: none;
    background: #ef4444;
    color: #fff;
    cursor: pointer;
    font-size: 13px;
    transition: background .15s;
}
.cuis-btn-del:hover { background: #dc2626; }

.cuis-actions { display: flex; align-items: center; gap: 6px; }
</style>
@endsection

@section('title', 'Toutes les cuisines | Food ops')
@section('page_title', 'Cuisines')
@section('nav_active', 'cuisine')

@section('content')
<div class="cuis-page">

    @if(session()->has('alert'))
        <div class="cuis-alert cuis-alert-{{ session()->get('alert.type') }}">
            {{ session()->get('alert.message') }}
        </div>
    @endif

    <div class="cuis-header">
        <h1 class="cuis-header-title">
            <i class="fas fa-utensils"></i>
            Toutes les cuisines
        </h1>
        <a href="{{ route('cuisine.create') }}" class="cuis-btn-add">
            <i class="fas fa-plus"></i> Ajouter une cuisine
        </a>
    </div>

    <div class="cuis-card">
        <div class="cuis-card-head">
            <i class="fas fa-list"></i>
            Liste des cuisines
        </div>
        <div class="cuis-table-wrap">
            <table class="cuis-table" id="cuis-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Image</th>
                        <th>Nom</th>
                        <th>Créé le</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                @foreach($cuisines as $index => $cuisine)
                    <tr>
                        <td>{{ ++$index }}</td>
                        <td>
                            <img src="{{ !empty($cuisine->image) ? asset('images/cuisine/' . $cuisine->image) : asset('images/placeholder.png') }}"
                                 class="cuis-thumb"
                                 alt="{{ $cuisine->name }}"
                                 onerror="this.src='{{ asset('images/placeholder.png') }}'">
                        </td>
                        <td>{{ $cuisine->name }}</td>
                        <td>{{ \Carbon\Carbon::parse($cuisine->created_at)->format('d/m/Y') }}</td>
                        <td>
                            <div class="cuis-actions">
                                <a href="{{ route('cuisine.edit', $cuisine->id) }}" class="cuis-btn-edit" title="Modifier">
                                    <i class="far fa-edit"></i>
                                </a>
                                <form action="{{ route('cuisine.destroy', $cuisine->id) }}"
                                      method="post"
                                      style="display:inline;"
                                      onsubmit="return confirm('Voulez-vous vraiment supprimer cette cuisine ?');">
                                    @csrf
                                    @method('delete')
                                    <button type="submit" class="cuis-btn-del" title="Supprimer">
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
        $("#cuis-table").DataTable({ pageLength: 25 });
    });
</script>
@endsection
