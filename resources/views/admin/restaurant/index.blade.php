@extends('layouts.app')
@section('style')
<link rel="stylesheet" href="{{asset(env('ASSET_URL') .'/plugins/datatables-bs4/css/dataTables.bootstrap4.css')}}">
<link rel="stylesheet" href="{{asset(env('ASSET_URL').'/plugins/sweetalert2/sweetalert2.css')}}">
@endsection
@section('title','Tous les restaurants | BantuDelice')
@section('add_restaurant_nav', 'active')
@section('add_restaurant_open_nav', 'menu-open')
@section('add_restaurant_all_nav', 'active')

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
                    <h1 class="m-0 text-dark"><i class="fas fa-building mr-2"></i>Tous les restaurants</h1>
                </div><!-- /.col -->
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{url('/admin')}}">Accueil</a></li>
                        <li class="breadcrumb-item active">Restaurants</li>
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
                            <h3 class="card-title"><i class="fas fa-list mr-2"></i>Liste des restaurants</h3>
                            <div class="card-tools">
                                <a href="{{ route('restaurant.create') }}" class="btn btn-primary btn-sm">
                                    <i class="fas fa-plus mr-1"></i> Ajouter un restaurant
                                </a>
                            </div>
                        </div>
                        <!-- /.card-header -->
                        <div class="card-body table-responsive p-2">
                            <table class="table table-head-fixed text-nowrap" id="example1">
                                
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
                                    <td> <a href="{{ route('admin.change_restaurant_featured_status', $restaurant->id) }}">
                                            <span class="badge badge-{{ $restaurant->featured ? 'success' : 'danger' }}">{{ $restaurant->featured ? 'En vedette' : 'Non En vedette' }}</span>
                                        </a>
                                    </td>
                                    <td>
                                        <a href="{{ route('admin.change_restaurant_active_status', $restaurant->id) }}">
                                            <span class="badge badge-{{ $restaurant->approved ? 'success' : 'danger' }}">{{ $restaurant->approved ? 'Actif' : 'Inactif' }}</span>
                                        </a>
                                    </td>
                                    <td>
                                        <a href="{{ route('restaurant.show', $restaurant->id) }}"
                                           class="btn btn-outline-info btn-sm" title="Voir les détails">
                                           <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="{{ route('restaurant.edit', $restaurant->id) }}"
                                           class="btn btn-outline-primary btn-sm" title="Modifier">
                                           <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="javascript:void(0);" onclick="if(confirm('Voulez-vous vraiment supprimer ce restaurant ?')) { $(this).find('form').submit(); }"
                                           class="btn btn-outline-danger btn-sm">
                                           <i class="fas fa-trash mr-1"></i> Supprimer
                                            <form action="{{ route('restaurant.destroy', $restaurant->id) }}"
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

