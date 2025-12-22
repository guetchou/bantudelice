@extends('layouts.app')
@section('title', 'Tous les livreurs | BantuDelice')
@section('driver_nav', 'active')
@section('driver_nav_open', 'menu-open')
@section('driver_nav_all', 'active')
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
                    <h1 class="m-0 text-dark"><i class="fas fa-taxi mr-2"></i>Tous les livreurs</h1>
                </div><!-- /.col -->
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{url('/admin')}}">Accueil</a></li>
                        <li class="breadcrumb-item active">Livreurs</li>
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
                            <h3 class="card-title"><i class="fas fa-list mr-2"></i>Liste des livreurs</h3>
                            <div class="card-tools">
                                <a href="{{ route('driver.create') }}" class="btn btn-primary btn-sm">
                                    <i class="fas fa-plus mr-1"></i> Ajouter un livreur
                                </a>
                            </div>
                        </div>
                        <!-- /.card-header -->
                        <div class="card-body table-responsive p-2">
                            <table class="table table-head-fixed text-nowrap" id="example1">
                                <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Image</th>
                                    <th>Nom</th>
                                    <th>Email</th>
                                    <th>Adresse</th>
                                    <th>Mobile</th>
                                    <th>Statut</th>
                                    <th>Action</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($drivers as $index => $driver)
                                    <tr>
                                        <td>{{ ++$index }}</td>
                                        <td><image src="{{asset('images/driver_images')}}/{{$driver->image}}" class="img-circle elevation-2" style="width:50px;height:50px;"></td>
                                        <td>{{$driver->name}}</td>
                                        <td>{{$driver->email}}</td>
                                        <td>{{$driver->address}}</td>
                                        <td>{{$driver->phone}}</td>
                                        <td>
                                            <a href="{{ route('admin.change_driver_active_status', $driver->id) }}">
                                                <span
                                                    class="badge badge-{{ $driver->approved ? 'success' : 'danger' }}">{{ $driver->approved ? 'Actif' : 'Inactif' }}</span>
                                            </a>
                                        </td>
                                        <td>
{{--                                            <li class="nav-item">--}}
{{--                                                <a href="{{ route('admin.set_hourly_pay') }}" class="nav-link">--}}
{{--                                                    <i class="far fa-circle nav-icon"></i>--}}
{{--                                                    <p>Add Hourly Pay</p>--}}
{{--                                                </a>--}}
{{--                                            </li>--}}
{{--                                            <button class="btn btn-default">Voir</button>--}}
                                            <a href="{{ route('admin.get_hourly_pay', $driver->id) }}"
                                               class="btn btn-outline-primary btn-sm" title="Définir le salaire horaire">
                                               <i class="fas fa-money-bill-wave mr-1"></i> Salaire
                                            </a>
                                            <a href="{{ route('driver.edit', $driver->id) }}"
                                               class="btn btn-outline-info btn-sm" title="Modifier">
                                               <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="javascript:void(0);" onclick="if(confirm('Voulez-vous vraiment supprimer ce livreur ?')) { $(this).find('form').submit(); }"
                                               class="btn btn-outline-danger btn-sm" title="Supprimer">
                                                <i class="fas fa-trash"></i>
                                                <form action="{{ route('driver.destroy', $driver->id) }}"
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