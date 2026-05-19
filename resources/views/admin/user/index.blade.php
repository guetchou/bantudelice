@extends('layouts.admin-modern')
@section('title', 'Tous les utilisateurs | Admin')
@section('page_title', 'Utilisateurs')
@section('nav_active', 'users')
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
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0 text-dark"><i class="fas fa-users mr-2"></i>Tous les utilisateurs</h1>
                </div><!-- /.col -->
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{url('/admin')}}">Accueil</a></li>
                        <li class="breadcrumb-item active">Utilisateurs</li>
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
                            <h3 class="card-title"><i class="fas fa-list mr-2"></i>Liste des utilisateurs</h3>
                        </div>
                        <!-- /.card-header -->
                        <div class="card-body table-responsive p-2">
                            <table class="table table-head-fixed text-nowrap" id="example1">
                                <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Nom</th>
                                    <th>Email</th>
                                    <th>Image</th>
                                    <th>Téléphone</th>
                                    <th>Statut</th>
                                    <th>Action</th>

                                </tr>
                                </thead>
                                <tbody>
                                @foreach($users as $index=> $user)
                                    <tr>
                                        <td>{{++$index}}</td>
                                        <td>{{$user->name}}</td>
                                        <td>{{$user->email}}</td>
                                        <td><img src="{{ $user->avatarUrl() }}" class="img-circle elevation-2"
                                                 alt="Image" width="50"></td>
                                        <td>{{$user->phone}}</td>
                                        <td>
                                            <form method="POST" action="{{ route('admin.change_block_status', $user->id) }}" style="display:inline;">
                                                @csrf
                                                <button type="submit" style="background:none;border:0;padding:0;">
                                                    <span class="badge badge-{{ $user->blocked ? 'danger' : 'success' }}">{{ $user->blocked ? 'Bloqué' : 'Actif' }}</span>
                                                </button>
                                            </form>
                                        </td>
                                        
                                        
                                        
                                        
                                        
                                        <td>
<!--{{--                                            <li class="nav-item">--}}-->
<!--{{--                                                <a href="{{ route('admin.set_hourly_pay') }}" class="nav-link">--}}-->
<!--{{--                                                    <i class="far fa-circle nav-icon"></i>--}}-->
<!--{{--                                                    <p>Add Hourly Pay</p>--}}-->
<!--{{--                                                </a>--}}-->
<!--{{--                                            </li>--}}-->
{{--                                            <button class="btn btn-default">Voir</button>--}}
                                            <!--<a href="{{ route('admin.get_hourly_pay', $user->id) }}"-->
                                            <!--   class="btn btn-outline-primary btn-sm">Add Pay</a>-->
                                            <!--<a href="{{ route('driver.edit', $user->id) }}"-->
                                            <!--   class="btn btn-outline-info btn-sm"><i class="fa fa-edit"></i></a>-->
                                            <form action="{{ route('user.destroy', $user->id) }}"
                                                  method="post"
                                                  style="display:inline;"
                                                  onsubmit="return confirm('Voulez-vous vraiment supprimer cet utilisateur ?');">
                                                @csrf
                                                @method('delete')
                                                <button type="submit" class="btn btn-outline-danger btn-sm" title="Supprimer">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
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
