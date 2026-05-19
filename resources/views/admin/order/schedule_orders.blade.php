@extends('layouts.admin-modern')
@section('title','Commandes planifiees')
@section('page_title', 'Commandes planifiées')
@section('nav_active', 'orders')
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
                <h1 class="m-0 text-dark">Commandes planifiees</h1>
            </div><!-- /.col -->
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="{{ url('/admin') }}">Accueil</a></li>
                    <li class="breadcrumb-item active">Commandes</li>
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
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Commandes planifiees</h3>

                        <div class="card-tools">
                            <div class="input-group input-group-sm" style="width: 150px;">
                                <input type="text" name="table_search" class="form-control float-right"
                                       placeholder="Rechercher">

                                <div class="input-group-append">
                                    <button type="submit" class="btn btn-default"><i class="fas fa-search"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- /.card-header -->
                    <div class="card-body table-responsive p-2">
                        <table class="table table-head-fixed text-nowrap" id="example1">
                            <thead>
                            <tr>
                                <th>ID</th>
                                <th>Client</th>
                                <th>Restaurant</th>
                                <th>Montant</th>
                                <th>Date planifiee</th>
                                <th>Statut</th>
                                <th>Action</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($orders as $index => $order)
                            <tr>
                                <td>{{++$index}}</td>
                                <td>{{$order->user->name}}</td>
                                <td>{{$order->restaurant->name}}</td>
                                <td>{{$order->total}}</td>
                                <td>{{$order->scheduled_date}}</td>
                                <td>{{$order->status}}</td>
                                <td>
                                   <a href="{{route('admin.show_order',$order)}}" class="btn btn-outline-warning"title="view"><i class="fa fa-eye"></i></a>
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
      $("#example1").DataTable({
          language: window.bdAdminDataTableLanguage
      });
  
    });
  </script>
@endsection
