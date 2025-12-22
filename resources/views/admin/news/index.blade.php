@extends('layouts.app')
@section('title', 'Toutes les actualités | BantuDelice')
@section('news_nav', 'active')
@section('news_nav_open', 'menu-open')
@section('news_nav_all', 'active')

@section('style')
<link rel="stylesheet" href="{{asset(env('ASSET_URL') .'/plugins/datatables-bs4/css/dataTables.bootstrap4.css')}}">
<link rel="stylesheet" href="{{asset(env('ASSET_URL').'/plugins/sweetalert2/sweetalert2.css')}}">
@endsection
@section('content')
<div class="content-header">
    @if(session()->has('alert'))
        <div class="alert alert-{{ session()->get('alert.type') }}">
            {{ session()->get('alert.message') }}
        </div>
    @endif
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0 text-dark"><i class="fas fa-newspaper mr-2"></i>Toutes les actualités</h1>
            </div><!-- /.col -->
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="{{url('/admin')}}">Accueil</a></li>
                    <li class="breadcrumb-item active">Actualités</li>
                </ol>
            </div><!-- /.col -->
        </div><!-- /.row -->
    </div><!-- /.container-fluid -->
</div>

<section class="content">
    <div class="container-fluid">
        <!-- Small boxes (Stat box) -->
        <div class="row">
        <div class="col-12">
        <div class="card shadow-sm">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-list mr-2"></i>Liste des actualités</h3>
                <div class="card-tools">
                    <a href="{{ route('news.create') }}" class="btn btn-primary btn-sm">
                        <i class="fas fa-plus mr-1"></i> Ajouter une actualité
                    </a>
                </div>
            </div>
          <!-- /.card-header -->
          <div class="card-body table-responsive pt-2" >
            <table class="table table-head-fixed text-nowrap" id="example1">
              <thead>
                <tr>
                  <th>ID</th>
                  <th>Titre</th>
                  <th>Description</th>
                  <th>Créé le</th>
                  <th>Action</th>

                </tr>
              </thead>
              <tbody>
              @foreach($news as $index=> $cuisine)
                  <tr>
                      <td>{{++$index}}</td>
                      <td>{{$cuisine->title}}</td>
                      <td>{{$cuisine->description}}</td>
                      <td>{{$cuisine->created_at}}</td>
                      <td>
                          <a href="{{ route('news.edit', $cuisine->id) }}"
                             class="btn btn-sm btn-outline-info"><i class="far fa-edit"></i></a>
                          <a href="javascript:void(0);" onclick="if(confirm('Voulez-vous vraiment supprimer cette actualité ?')) { $(this).find('form').submit(); }"
                             class=" btn btn-sm btn-danger" title="Supprimer">
                             <i class="fas fa-trash-alt"></i>
                              <form action="{{ route('news.destroy', $cuisine->id) }}"
                                    method="post">
                                  @csrf
                                  @method('delete')
                              </form>
                          </a>
                          <a href="{{ route('send.notification', $cuisine->id) }}"
                             class="btn btn-sm btn-outline-warning" title="Envoyer une notification">
                             <i class="fas fa-bell"></i>
                          </a>
                      </td>
                  </tr>
              @endforeach
              </tbody>
            </table>
          </div>
          <!-- /.card-body -->
        </div>
        <!-- /.card -->
      </div>

        </div>
    </div>
</section>
@endsection
@section('script')
<script src="{{asset(env('ASSET_URL') .'plugins/datatables/jquery.dataTables.js')}}"></script>
<script src="{{asset(env('ASSET_URL') .'plugins/datatables-bs4/js/dataTables.bootstrap4.js')}}"></script>
<script>
    $(function () {
      $("#example1").DataTable();
  
    });
  </script>
@endsection
