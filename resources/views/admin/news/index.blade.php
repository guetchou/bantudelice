@extends('layouts.admin-modern')
@section('title', 'Toutes les actualités | Admin')
@section('page_title', 'Actualités')
@section('nav_active', 'news')

@section('style')
<link rel="stylesheet" href="{{asset('plugins/datatables-bs4/css/dataTables.bootstrap4.css')}}">
<link rel="stylesheet" href="{{asset('plugins/sweetalert2/sweetalert2.css')}}">
@endsection

@section('content')
<div class="content-header">
    <div class="container-fluid">
        <div class="bd-admin-editor-shell">
            @if(session()->has('alert'))
                <div class="alert alert-{{ session()->get('alert.type') }}">
                    {{ session()->get('alert.message') }}
                </div>
            @endif

            <section class="bd-admin-editor-hero">
                <div>
                    <p class="bd-admin-editor-hero__eyebrow">Newsroom</p>
                    <h1>Actualités</h1>
                    <p>Gérez les annonces, informations importantes et notifications publiques depuis une vue unique.</p>
                </div>
                <a href="{{ route('news.create') }}" class="btn btn-primary">Ajouter une actualité</a>
            </section>
        </div>
    </div>
</div>

<section class="content">
    <div class="container-fluid">
        <div class="card bd-admin-editor-card">
            <div class="card-header border-0">
                <div class="bd-admin-editor-card__header">
                    <div>
                        <h3>Liste des actualités</h3>
                        <p>Diffusez une nouvelle publication, modifiez les annonces existantes ou envoyez une notification directement.</p>
                    </div>
                    <span class="bd-admin-table-pill">{{ count($news) }} publication(s)</span>
                </div>
            </div>
            <div class="card-body table-responsive pt-0">
                <table class="table table-hover" id="example1">
                    <thead>
                    <tr>
                        <th>#</th>
                        <th>Titre</th>
                        <th>Description</th>
                        <th>Créé le</th>
                        <th></th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($news as $index => $item)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td>
                                <div class="bd-admin-table-primary">
                                    <strong>{{ $item->title }}</strong>
                                    <span>Actualité publique</span>
                                </div>
                            </td>
                            <td>{{ $item->description }}</td>
                            <td>{{ $item->created_at }}</td>
                            <td class="text-right">
                                <a href="{{ route('news.edit', $item->id) }}" class="btn btn-sm btn-outline-primary">Modifier</a>
                                <button type="button" class="btn btn-sm btn-outline-secondary" disabled title="Fonction non disponible">Notification indisponible</button>
                                <form action="{{ route('news.destroy', $item->id) }}" method="post" style="display:inline;" onsubmit="return confirm('Voulez-vous vraiment supprimer cette actualité ?');">
                                    @csrf
                                    @method('delete')
                                    <button type="submit" class="btn btn-sm btn-danger">Supprimer</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>

<style>
    .bd-admin-editor-shell { display:grid; gap:14px; }
    .bd-admin-editor-hero { display:flex; justify-content:space-between; align-items:flex-end; gap:16px; padding:18px 20px; border-radius:18px; background:linear-gradient(135deg,#020617 0%,#0f172a 60%,#155e75 100%); color:#fff; box-shadow:0 12px 30px rgba(15,23,42,.16); }
    .bd-admin-editor-hero__eyebrow { margin:0 0 8px; font-size:.78rem; letter-spacing:.18em; text-transform:uppercase; font-weight:800; color:#bae6fd; }
    .bd-admin-editor-hero h1 { margin:0; color:#fff !important; font-size:clamp(1.45rem,3vw,2rem); font-weight:900; line-height:1.08; }
    .bd-admin-editor-hero p { margin:8px 0 0; max-width:980px; color:rgba(255,255,255,.82); line-height:1.55; }
    .bd-admin-editor-card__header { display:flex; justify-content:space-between; align-items:flex-end; gap:14px; }
    .bd-admin-editor-card__header h3 { margin:0; color:#020617; font-size:1.2rem; font-weight:900; }
    .bd-admin-editor-card__header p { margin:6px 0 0; color:#64748b; line-height:1.5; }
    .bd-admin-table-pill { display:inline-flex; min-height:38px; align-items:center; padding:0 14px; border-radius:999px; background:#eff6ff; color:#1d4ed8; font-weight:800; }
    .bd-admin-table-primary { display:flex; flex-direction:column; gap:4px; }
    .bd-admin-table-primary strong { color:#020617; }
    .bd-admin-table-primary span { color:#94a3b8; font-size:.84rem; }
    @media (max-width: 991.98px) { .bd-admin-editor-hero, .bd-admin-editor-card__header { flex-direction:column; align-items:flex-start; } }
</style>
@endsection

@section('script')
<script src="{{asset('plugins/datatables/jquery.dataTables.js')}}"></script>
<script src="{{asset('plugins/datatables-bs4/js/dataTables.bootstrap4.js')}}"></script>
<script>
    $(function () {
      $("#example1").DataTable();
    });
</script>
@endsection
