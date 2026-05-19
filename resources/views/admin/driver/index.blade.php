@extends('layouts.admin-modern')
@section('title', 'Tous les livreurs | Food ops')
@section('page_title', 'Livreurs')
@section('nav_active', 'drivers')
@section('style')
<link rel="stylesheet" href="{{asset('plugins/datatables-bs4/css/dataTables.bootstrap4.css')}}">
<link rel="stylesheet" href="{{asset('plugins/sweetalert2/sweetalert2.css')}}">
@endsection

@section('content')
    <div class="content-header">
        @if(session()->has('alert'))
            <div class="alert alert-{{ session()->get('alert.type') }}">
                {{ session()->get('alert.message') }}
            </div>
        @endif
        @if(session()->has('provisioned_accounts'))
            <div class="alert alert-warning">
                <div class="mb-2"><strong>Identifiants temporaires générés</strong> , à changer après première connexion via l'API livreur.</div>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered bg-white mb-0">
                        <thead>
                        <tr>
                            <th>ID</th>
                            <th>Restaurant</th>
                            <th>Login</th>
                            <th>Email</th>
                            <th>Téléphone</th>
                            <th>Mot de passe temporaire</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach(session('provisioned_accounts', []) as $account)
                            <tr>
                                <td>{{ $account['id'] }}</td>
                                <td>{{ $account['restaurant'] }}</td>
                                <td>{{ $account['user_name'] }}</td>
                                <td>{{ $account['email'] }}</td>
                                <td>{{ $account['phone'] }}</td>
                                <td><code>{{ $account['password'] }}</code></td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
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
                                        <td><img src="{{ !empty($driver->image) ? asset('images/driver_images/'.$driver->image) : asset('images/placeholder.png') }}" class="img-circle elevation-2" style="width:50px;height:50px;" onerror="this.src='{{ asset('images/placeholder.png') }}'" alt="driver"></td>
                                        <td>{{$driver->name}}</td>
                                        <td>{{$driver->email}}</td>
                                        <td>{{$driver->address}}</td>
                                        <td>{{$driver->phone}}</td>
                                        <td>
                                            <form method="POST" action="{{ route('admin.change_driver_active_status', $driver->id) }}" style="display:inline;">
                                                @csrf
                                                <button type="submit" style="background:none;border:0;padding:0;">
                                                    <span
                                                        class="badge badge-{{ $driver->approved ? 'success' : 'danger' }}">{{ $driver->approved ? 'Actif' : 'Inactif' }}</span>
                                                </button>
                                            </form>
                                        </td>
                                        <td>
                                            <form action="{{ route('admin.impersonate.driver', $driver->id) }}" method="POST" style="display:inline;">
                                                @csrf
                                                <button type="submit" class="btn btn-outline-success btn-sm" title="Ouvrir le dashboard livreur">
                                                    <i class="fas fa-sign-in-alt mr-1"></i> Dashboard
                                                </button>
                                            </form>
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
                                            <form action="{{ route('driver.destroy', $driver->id) }}"
                                                  method="post"
                                                  style="display:inline;"
                                                  onsubmit="return confirm('Voulez-vous vraiment supprimer ce livreur ?');">
                                                @csrf
                                                @method('delete')
                                                <button type="submit" class="btn btn-outline-danger btn-sm" title="Supprimer">
                                                    <i class="fas fa-trash"></i>
                                                </button>
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
<script src="{{asset('plugins/datatables/jquery.dataTables.js')}}"></script>
<script src="{{asset('plugins/datatables-bs4/js/dataTables.bootstrap4.js')}}"></script>
<script>
    $(function () {
      $("#example1").DataTable();
  
    });
  </script>
@endsection
