@extends('layouts.admin-modern')
@section('title','Restaurants en attente')
@section('page_title', 'Restaurants en attente')
@section('nav_active', 'restaurants')
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
                <h1 class="m-0 text-dark">Demandes de restaurants en attente</h1>
            </div><!-- /.col -->
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="{{ url('/admin') }}">Accueil</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('restaurant.index') }}">Restaurant</a></li>
                    <li class="breadcrumb-item active">Demandes de restaurants en attente</li>
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
        <div class="card shadow">
         
          <!-- /.card-header -->
          <div class="card-body table-responsive p-4" >
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
                      <td> <form method="POST" action="{{ route('admin.change_restaurant_featured_status', $restaurant->id) }}" style="display:inline;">
                              @csrf
                              <button type="submit" style="background:none;border:0;padding:0;">
                                  <span class="badge badge-{{ $restaurant->featured ? 'success' : 'danger' }}">{{ $restaurant->featured ? 'En vedette' : 'Non En vedette' }}</span>
                              </button>
                          </form>
                      </td>
                      <td>
                          <form method="POST" action="{{ route('admin.change_restaurant_active_status', $restaurant->id) }}" style="display:inline;">
                              @csrf
                              <button type="submit" style="background:none;border:0;padding:0;">
                                  <span class="badge badge-{{ $restaurant->approved ? 'success' : 'danger' }}">{{ $restaurant->approved ? 'Actif' : 'Inactif' }}</span>
                              </button>
                          </form>
                      </td>
                      <td>
                          <a href="{{ route('restaurant.show', $restaurant->id) }}"
                             class="btn btn-outline-info btn-sm">Voir</a>
                          <a href="{{ route('restaurant.edit', $restaurant->id) }}"
                             class="btn btn-outline-info btn-sm">Modifier</a>
                          <form action="{{ route('restaurant.destroy', $restaurant->id) }}"
                                method="post"
                                style="display:inline;"
                                onsubmit="return confirm('Voulez-vous vraiment supprimer this country?');">
                              @csrf
                              @method('delete')
                              <button type="submit" class="btn btn-outline-danger btn-sm">Delete</button>
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
