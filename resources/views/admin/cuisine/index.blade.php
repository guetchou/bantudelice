@extends('layouts.app')
@section('style')
<link rel="stylesheet" href="{{asset(env('ASSET_URL') .'/plugins/datatables-bs4/css/dataTables.bootstrap4.css')}}">
<link rel="stylesheet" href="{{asset(env('ASSET_URL').'/plugins/sweetalert2/sweetalert2.css')}}">
@endsection
@section('cuisine_nav', 'active')
@section('cuisine_nav_open', 'menu-open')
@section('cuisine_nav_all', 'active')
@section('title', 'Toutes les cuisines | BantuDelice')

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
                <h1 class="m-0 text-dark"><i class="fas fa-utensils mr-2"></i>Toutes les cuisines</h1>
            </div><!-- /.col -->
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="{{url('/admin')}}">Accueil</a></li>
                    <li class="breadcrumb-item active">Cuisines</li>
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
                <h3 class="card-title"><i class="fas fa-list mr-2"></i>Liste des cuisines</h3>
                <div class="card-tools">
                    <a href="{{ route('cuisine.create') }}" class="btn btn-primary btn-sm">
                        <i class="fas fa-plus mr-1"></i> Ajouter une cuisine
                    </a>
                </div>
            </div>
          <!-- /.card-header -->
          <div class="card-body table-responsive pt-2" >
            <table class="table table-head-fixed text-nowrap" id="example1">
              <thead>
                <tr>
                  <th>ID</th>
                  <th>image</th>
                  <th>Nom</th>
                  <th>Créé le</th>
                  <th>Action</th>

                </tr>
              </thead>
              <tbody>
              @foreach($cuisines as $index=> $cuisine)
                  <tr>
                      <td>{{++$index}}</td>
                      <td><image src="{{asset('images/cuisine')}}/{{$cuisine->image}}" class="img-circle elevation-2" style="width:50px;height:50px;"></td>
                      <td>{{$cuisine->name}}</td>
                      <td>{{$cuisine->created_at}}</td>
                      <td>
                          <a href="{{ route('cuisine.edit', $cuisine->id) }}"
                             class="btn btn-sm btn-outline-info"><i class="far fa-edit"></i></a>
                          <a href="javascript:void(0);" onclick="if(confirm('Voulez-vous vraiment supprimer cette cuisine ?')) { $(this).find('form').submit(); }"
                             class=" btn btn-sm btn-danger" title="Supprimer">
                             <i class="fas fa-trash-alt"></i>
                              <form action="{{ route('cuisine.destroy', $cuisine->id) }}"
                                    method="post">
                                  @csrf
                                  @method('delete')
                              </form>
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
