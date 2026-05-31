@extends('layouts.admin-modern')
@section('title', 'Actualités | Admin')
@section('page_title', 'Actualités')
@section('nav_active', 'news')

@section('style')
<style>
.nws { display: flex; flex-direction: column; gap: 20px; }

.nws-alert {
    padding: 12px 16px; border-radius: 8px; font-size: 13px; font-weight: 500;
    border: 1px solid transparent;
}
.nws-alert--success { background: #f0fdf4; color: #166534; border-color: #bbf7d0; }
.nws-alert--danger  { background: #fef2f2; color: #991b1b; border-color: #fecaca; }

.nws-hero { display:none; }

.nws-btn {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 9px 18px; border-radius: 8px; font-size: 13px;
    font-weight: 600; text-decoration: none; transition: .12s; white-space: nowrap;
}
.nws-btn--primary { background: #22c55e; color: #fff; }
.nws-btn--primary:hover { background: #16a34a; color: #fff; }
.nws-btn--outline { background: rgba(255,255,255,.12); color: #fff; border: 1px solid rgba(255,255,255,.3); }
.nws-btn--outline:hover { background: rgba(255,255,255,.2); color: #fff; }
.nws-btn--ghost { background: transparent; color: #374151; border: 1px solid #e5e7eb; }
.nws-btn--ghost:hover { border-color: #22c55e; color: #22c55e; }
.nws-btn--danger { background: #ef4444; color: #fff; border: none; cursor: pointer; }
.nws-btn--danger:hover { background: #dc2626; color: #fff; }
.nws-btn--sm { padding: 6px 12px; font-size: 12px; }

.nws-card {
    background: #fff; border: 1px solid #e5e7eb; border-radius: 12px; overflow: hidden;
}
.nws-card__head {
    display: flex; align-items: center; justify-content: space-between;
    padding: 14px 20px; border-bottom: 1px solid #f3f4f6; flex-wrap: wrap; gap: 10px;
}
.nws-card__head-title { font-size: 14px; font-weight: 700; color: #111827; margin: 0; }
.nws-card__head-sub   { font-size: 12px; color: #6b7280; margin: 3px 0 0; }

.nws-pill {
    display: inline-flex; align-items: center;
    padding: 4px 12px; border-radius: 999px;
    background: #eff6ff; color: #1d4ed8; font-size: 12px; font-weight: 700;
}

.nws-table-wrap { overflow-x: auto; }
.nws-table {
    width: 100%; border-collapse: collapse; font-size: 13px;
    font-family: 'Manrope', sans-serif;
}
.nws-table thead th {
    padding: 9px 16px; font-size: 10px; font-weight: 700; letter-spacing: .06em;
    text-transform: uppercase; color: #9ca3af;
    border-bottom: 1px solid #f3f4f6; background: #f9fafb;
    text-align: left; white-space: nowrap;
}
.nws-table tbody tr { border-bottom: 1px solid #f3f4f6; transition: background .1s; }
.nws-table tbody tr:last-child { border-bottom: none; }
.nws-table tbody tr:hover { background: #f9fafb; }
.nws-table td { padding: 12px 16px; color: #374151; vertical-align: middle; }

.nws-title-cell strong { display: block; color: #111827; font-weight: 600; }
.nws-title-cell span   { font-size: 11px; color: #9ca3af; }

.nws-actions { display: flex; align-items: center; gap: 6px; justify-content: flex-end; }
</style>
@endsection

@section('content')
<div class="nws">

    @if(session()->has('alert'))
        <div class="nws-alert nws-alert--{{ session('alert.type') === 'success' ? 'success' : 'danger' }}">
            {{ session('alert.message') }}
        </div>
    @endif

    <div class="adm-page-bar">
        <div class="adm-page-bar__left">
            <nav class="adm-page-bar__breadcrumb">
                <span>Contenu</span><span class="sep">/</span><span>Actualites</span>
            </nav>
            <h1 class="adm-page-bar__title">Actualites</h1>
        </div>
        <div class="adm-page-bar__right">
            <a href="{{ route('news.create') }}" class="nws-btn nws-btn--primary">
                <i class="fas fa-plus"></i> Ajouter
            </a>
        </div>
    </div>

    <div class="nws-card">
        <div class="nws-card__head">
            <div>
                <div class="nws-card__head-title">Liste des actualités</div>
                <div class="nws-card__head-sub">Publications et annonces diffusées sur le site.</div>
            </div>
            <span class="nws-pill">{{ count($news) }} publication(s)</span>
        </div>
        <div class="nws-table-wrap">
            <table class="nws-table" id="nws-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Titre</th>
                        <th>Description</th>
                        <th>Créé le</th>
                        <th style="text-align:right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($news as $index => $item)
                    <tr>
                        <td style="color:#9ca3af;">{{ $index + 1 }}</td>
                        <td>
                            <div class="nws-title-cell">
                                <strong>{{ $item->title }}</strong>
                                <span>Actualité publique</span>
                            </div>
                        </td>
                        <td style="max-width:260px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                            {{ $item->description }}
                        </td>
                        <td style="font-size:12px;white-space:nowrap;">
                            {{ \Carbon\Carbon::parse($item->created_at)->format('d/m/Y') }}
                        </td>
                        <td>
                            <div class="nws-actions">
                                <a href="{{ route('news.edit', $item->id) }}" class="nws-btn nws-btn--ghost nws-btn--sm">
                                    <i class="fas fa-pen"></i> Modifier
                                </a>
                                <form action="{{ route('news.destroy', $item->id) }}" method="post"
                                      style="display:inline;"
                                      onsubmit="return confirm('Supprimer cette actualité ?');">
                                    @csrf
                                    @method('delete')
                                    <button type="submit" class="nws-btn nws-btn--danger nws-btn--sm">
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
    $('#nws-table').DataTable({ pageLength: 25, order: [[3, 'desc']] });
});
</script>
@endsection
